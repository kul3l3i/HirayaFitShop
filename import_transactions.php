<?php
// Start session
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page
    header("Location: sign-in.php");
    exit;
}

// Database connection configuration
$db_host = 'localhost';
$db_user = 'u801377270_hiraya_2025'; // Change to your DB username
$db_pass = 'Hiraya_2025';     // Change to your DB password
$db_name = 'u801377270_hiraya_2025'; // Change to your DB name

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define variables
$importSuccess = false;
$errorMessage = '';
$transactionsAdded = 0;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_xml'])) {
    // Check if file is uploaded
    if (isset($_FILES['xml_file']) && $_FILES['xml_file']['error'] === UPLOAD_ERR_OK) {
        $xmlFile = $_FILES['xml_file']['tmp_name'];
        
        // Check if it's an XML file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $xmlFile);
        finfo_close($finfo);
        
        if ($fileType === 'application/xml' || $fileType === 'text/xml') {
            // Load XML file
            $xml = simplexml_load_file($xmlFile);
            if ($xml === false) {
                $errorMessage = "Failed to parse XML file.";
            } else {
                // Begin transaction
                $conn->begin_transaction();
                try {
                    // Prepare statements for transactions and items
                    $stmtTransaction = $conn->prepare("INSERT INTO transactions (transaction_id, user_id, transaction_date, status, payment_method, subtotal, shipping_fee, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtItem = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, product_name, price, quantity, color, size, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtShipping = $conn->prepare("INSERT INTO shipping_info (transaction_id, fullname, email, phone, address, city, postal_code, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    // Process each transaction
                    foreach ($xml->transaction as $transaction) {
                        // Check if transaction already exists
                        $checkStmt = $conn->prepare("SELECT transaction_id FROM transactions WHERE transaction_id = ?");
                        $transactionId = (string)$transaction->transaction_id;
                        $checkStmt->bind_param("s", $transactionId);
                        $checkStmt->execute();
                        $result = $checkStmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            // Transaction already exists, skip
                            continue;
                        }
                        
                        // Insert transaction
                        $userId = (int)$transaction->user_id;
                        $transactionDate = (string)$transaction->transaction_date;
                        $status = (string)$transaction->status;
                        $paymentMethod = (string)$transaction->payment_method;
                        $subtotal = (float)$transaction->subtotal;
                        $shippingFee = (float)$transaction->shipping_fee;
                        $totalAmount = (float)$transaction->total_amount;
                        
                        $stmtTransaction->bind_param("sisssddd", $transactionId, $userId, $transactionDate, $status, $paymentMethod, $subtotal, $shippingFee, $totalAmount);
                        $stmtTransaction->execute();
                        
                        // Insert items
                        foreach ($transaction->items->item as $item) {
                            $productId = (string)$item->product_id;
                            $productName = (string)$item->product_name;
                            $price = (float)$item->price;
                            $quantity = (int)$item->quantity;
                            $color = (string)$item->color;
                            $size = (string)$item->size;
                            $itemSubtotal = (float)$item->subtotal;
                            
                            $stmtItem->bind_param("sssdssd", $transactionId, $productId, $productName, $price, $quantity, $color, $size, $itemSubtotal);
                            $stmtItem->execute();
                        }
                        
                        // Insert shipping info
                        $fullname = (string)$transaction->shipping_info->fullname;
                        $email = (string)$transaction->shipping_info->email;
                        $phone = (string)$transaction->shipping_info->phone;
                        $address = (string)$transaction->shipping_info->address;
                        $city = (string)$transaction->shipping_info->city;
                        $postalCode = (string)$transaction->shipping_info->postal_code;
                        $notes = (string)$transaction->shipping_info->notes;
                        
                        $stmtShipping->bind_param("sssssss", $transactionId, $fullname, $email, $phone, $address, $city, $postalCode, $notes);
                        $stmtShipping->execute();
                        
                        $transactionsAdded++;
                    }
                    
                    // Commit the transaction
                    $conn->commit();
                    $importSuccess = true;
                    
                } catch (Exception $e) {
                    // Rollback in case of error
                    $conn->rollback();
                    $errorMessage = "Error importing transactions: " . $e->getMessage();
                }
            }
        } else {
            $errorMessage = "Uploaded file is not a valid XML file.";
        }
    } else {
        $errorMessage = "Please select a valid XML file to upload.";
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Transactions - HirayaFit Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        h1 {
            font-size: 24px;
            color: #111;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .file-input {
            display: block;
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0071c5;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Import Transactions</h1>
        
        <?php if ($importSuccess): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Successfully imported <?php echo $transactionsAdded; ?> transaction(s) from the XML file.
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="xml_file">Select XML File</label>
                <input type="file" name="xml_file" id="xml_file" class="file-input" accept=".xml" required>
                <small>Upload the transactions XML file to import into the database.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="import_xml" class="btn">
                    <i class="fas fa-file-import"></i> Import Transactions
                </button>
                <a href="payment-history.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Payment History
                </a>
            </div>
        </form>
    </div>
</body>
</html>