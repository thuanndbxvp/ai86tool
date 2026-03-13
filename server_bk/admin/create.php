<?php
/**
 * Create License Page
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

Auth::requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $licenseKey = strtoupper(trim($_POST['license_key'] ?? ''));
    $customerName = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $validity = intval($_POST['validity'] ?? DEFAULT_LICENSE_VALIDITY);
    $maxDevices = intval($_POST['max_devices'] ?? DEFAULT_MAX_DEVICES);
    
    // Validate
    if (!preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $licenseKey)) {
        $error = 'License key must be in format: XXXX-XXXX-XXXX-XXXX';
    } elseif (empty($customerName)) {
        $error = 'Customer name is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Valid email is required';
    } else {
        try {
            LicenseManager::createLicense($licenseKey, $customerName, $email, $validity, $maxDevices);
            $success = "License {$licenseKey} created successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'License key already exists';
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Generate random key
function generateKey() {
    $parts = [];
    for ($i = 0; $i < 4; $i++) {
        $parts[] = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
    }
    return implode('-', $parts);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create License - License Manager</title>
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
        h1 { margin-bottom: 20px; color: #333; }
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
        .input-group {
            display: flex;
            gap: 10px;
        }
        .input-group input { flex: 1; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 100%;
            padding: 14px;
            font-size: 16px;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>➕ Create License</h1>
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="card">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>License Key</label>
                    <div class="input-group">
                        <input type="text" name="license_key" id="license_key" placeholder="XXXX-XXXX-XXXX-XXXX" 
                               pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}" 
                               value="<?php echo generateKey(); ?>" required>
                        <button type="button" class="btn btn-secondary" onclick="generateKey()">🎲 Generate</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="customer_name" placeholder="John Doe" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="john@example.com" required>
                </div>
                
                <div class="form-group">
                    <label>Validity (days)</label>
                    <input type="number" name="validity" value="365" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>Max Devices</label>
                    <select name="max_devices">
                        <option value="1">1 device</option>
                        <option value="2" selected>2 devices</option>
                        <option value="3">3 devices</option>
                        <option value="5">5 devices</option>
                        <option value="10">10 devices</option>
                    </select>
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn btn-primary">✓ Create License</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function generateKey() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            const parts = [];
            for (let i = 0; i < 4; i++) {
                let part = '';
                for (let j = 0; j < 4; j++) {
                    part += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                parts.push(part);
            }
            document.getElementById('license_key').value = parts.join('-');
        }
    </script>
</body>
</html>
