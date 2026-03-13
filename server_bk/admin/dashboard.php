<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

Auth::requireLogin();

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$licenses = LicenseManager::getAllLicenses($search, $status);

// Stats
$total = count($licenses);
$active = count(array_filter($licenses, fn($l) => !$l['revoked'] && strtotime($l['expiry_date']) > time()));
$expired = count(array_filter($licenses, fn($l) => strtotime($l['expiry_date']) <= time()));
$revoked = count(array_filter($licenses, fn($l) => $l['revoked']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - License Manager</title>
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
        .header h1 { font-size: 24px; }
        .header a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        .actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .filters input, .filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
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
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 3px;
            text-decoration: none;
        }
        .btn-view { background: #667eea; color: white; }
        .btn-revoke { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 License Manager</h1>
        <div>
            <span>Welcome, <?php echo htmlspecialchars(Auth::getCurrentAdmin()); ?> | </span>
            <a href="change_password.php">Change Password</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <h3>Total Licenses</h3>
                <div class="number"><?php echo $total; ?></div>
            </div>
            <div class="stat-card">
                <h3>Active</h3>
                <div class="number" style="color: #28a745;"><?php echo $active; ?></div>
            </div>
            <div class="stat-card">
                <h3>Expired</h3>
                <div class="number" style="color: #ffc107;"><?php echo $expired; ?></div>
            </div>
            <div class="stat-card">
                <h3>Revoked</h3>
                <div class="number" style="color: #dc3545;"><?php echo $revoked; ?></div>
            </div>
        </div>
        
        <div class="actions">
            <a href="create.php" class="btn btn-primary">➕ Create New License</a>
        </div>
        
        <form class="filters" method="GET">
            <input type="text" name="search" placeholder="Search license, user, email..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
                <option value="">All Status</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                <option value="revoked" <?php echo $status === 'revoked' ? 'selected' : ''; ?>>Revoked</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        
        <table>
            <thead>
                <tr>
                    <th>License Key</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Expiry</th>
                    <th>Devices</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license): 
                    $isExpired = strtotime($license['expiry_date']) <= time();
                    $statusClass = $license['revoked'] ? 'status-revoked' : ($isExpired ? 'status-expired' : 'status-active');
                    $statusText = $license['revoked'] ? 'Revoked' : ($isExpired ? 'Expired' : 'Active');
                ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($license['license_key']); ?></code></td>
                    <td>
                        <?php echo htmlspecialchars($license['customer_name']); ?><br>
                        <small style="color: #666;"><?php echo htmlspecialchars($license['email']); ?></small>
                    </td>
                    <td><span class="status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                    <td><?php echo date('Y-m-d', strtotime($license['expiry_date'])); ?></td>
                    <td><?php echo $license['max_devices']; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($license['created_at'])); ?></td>
                    <td>
                        <a href="view.php?id=<?php echo $license['id']; ?>" class="btn-sm btn-view">View</a>
                        <a href="edit.php?id=<?php echo $license['id']; ?>" class="btn-sm" style="background: #ffc107; color: #000;">Edit</a>
                        <?php if (!$license['revoked']): ?>
                        <a href="revoke.php?id=<?php echo $license['id']; ?>&action=revoke" class="btn-sm btn-revoke" onclick="return confirm('Revoke this license?')">Revoke</a>
                        <?php else: ?>
                        <a href="revoke.php?id=<?php echo $license['id']; ?>&action=unrevoke" class="btn-sm" style="background: #28a745; color: #fff;" onclick="return confirm('Un-revoke this license?')">Unrevoke</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
