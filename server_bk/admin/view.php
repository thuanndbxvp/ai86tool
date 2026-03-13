<?php
/**
 * View License Details
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

$isExpired = strtotime($license['expiry_date']) <= time();
$daysLeft = max(0, ceil((strtotime($license['expiry_date']) - time()) / 86400));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Details - License Manager</title>
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
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { margin-bottom: 20px; color: #333; }
        h2 { margin: 30px 0 15px; color: #555; font-size: 18px; }
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label {
            width: 150px;
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            flex: 1;
            color: #333;
        }
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-expired { background: #fff3cd; color: #856404; }
        .status-revoked { background: #f8d7da; color: #721c24; }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .device-table {
            width: 100%;
            border-collapse: collapse;
        }
        .device-table th, .device-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .device-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #666;
            font-size: 12px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .actions {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 License Details</h1>
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h1><?php echo htmlspecialchars($license['license_key']); ?></h1>
            
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <?php if ($license['revoked']): ?>
                        <span class="status status-revoked">Revoked</span>
                    <?php elseif ($isExpired): ?>
                        <span class="status status-expired">Expired</span>
                    <?php else: ?>
                        <span class="status status-active">Active</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Customer</div>
                <div class="detail-value"><?php echo htmlspecialchars($license['customer_name']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Email</div>
                <div class="detail-value"><?php echo htmlspecialchars($license['email']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Created</div>
                <div class="detail-value"><?php echo date('Y-m-d H:i:s', strtotime($license['created_at'])); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Expires</div>
                <div class="detail-value">
                    <?php echo date('Y-m-d H:i:s', strtotime($license['expiry_date'])); ?>
                    <?php if (!$isExpired && !$license['revoked']): ?>
                        <span style="color: #28a745;">(<?php echo $daysLeft; ?> days remaining)</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Max Devices</div>
                <div class="detail-value"><?php echo $license['max_devices']; ?></div>
            </div>
            
            <div class="actions">
                <a href="dashboard.php" class="btn btn-secondary">← Back</a>
                <?php if (!$license['revoked']): ?>
                <a href="revoke.php?id=<?php echo $license['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to revoke this license?')">🚫 Revoke License</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h2>🔌 Active Devices (<?php echo count($license['devices']); ?>/<?php echo $license['max_devices']; ?>)</h2>
            <?php if (empty($license['devices'])): ?>
                <p style="color: #666;">No devices activated yet.</p>
            <?php else: ?>
                <table class="device-table">
                    <thead>
                        <tr>
                            <th>Device ID</th>
                            <th>Activated</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($license['devices'] as $device): ?>
                        <tr>
                            <td><code><?php echo substr($device['device_id'], 0, 16); ?>...</code></td>
                            <td><?php echo date('Y-m-d', strtotime($device['activated_at'])); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($device['last_seen'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
