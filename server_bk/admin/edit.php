<?php
/**
 * Edit License Page
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

Auth::requireLogin();

$id = intval($_GET['id'] ?? 0);
$license = LicenseManager::getLicense($id);

if (!$license) {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $expiryDate = $_POST['expiry_date'] ?? '';
    $maxDevices = intval($_POST['max_devices'] ?? 2);
    $revoked = isset($_POST['revoked']) ? 1 : 0;
    
    // Validate
    if (empty($customerName)) {
        $error = 'Customer name is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Valid email is required';
    } elseif (empty($expiryDate)) {
        $error = 'Expiry date is required';
    } else {
        try {
            $pdo = Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("UPDATE licenses SET customer_name = ?, email = ?, expiry_date = ?, max_devices = ?, revoked = ? WHERE id = ?");
            $stmt->execute([$customerName, $email, $expiryDate, $maxDevices, $revoked, $id]);
            
            $success = "License updated successfully!";
            
            // Refresh license data
            $license = LicenseManager::getLicense($id);
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit License - License Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a {
            color: white;
            text-decoration: none;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { margin-bottom: 10px; color: #333; }
        .key-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            margin-bottom: 25px;
            text-align: center;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 100%;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✏️ Edit License</h1>
        <a href="dashboard.php">← Dashboard</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h1>Edit License</h1>
            <div class="key-display"><?php echo htmlspecialchars($license['license_key']); ?></div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="customer_name" value="<?php echo htmlspecialchars($license['customer_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($license['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="datetime-local" name="expiry_date" value="<?php echo date('Y-m-d\TH:i', strtotime($license['expiry_date'])); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Max Devices</label>
                    <select name="max_devices">
                        <option value="1" <?php echo $license['max_devices'] == 1 ? 'selected' : ''; ?>>1 device</option>
                        <option value="2" <?php echo $license['max_devices'] == 2 ? 'selected' : ''; ?>>2 devices</option>
                        <option value="3" <?php echo $license['max_devices'] == 3 ? 'selected' : ''; ?>>3 devices</option>
                        <option value="5" <?php echo $license['max_devices'] == 5 ? 'selected' : ''; ?>>5 devices</option>
                        <option value="10" <?php echo $license['max_devices'] == 10 ? 'selected' : ''; ?>>10 devices</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-group">
                        <input type="checkbox" name="revoked" value="1" <?php echo $license['revoked'] ? 'checked' : ''; ?>>
                        <span>Revoke this license</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">✓ Save Changes</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
