<?php
/**
 * Revoke License
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

Auth::requireLogin();

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'revoke';

if ($id > 0) {
    $pdo = Database::getInstance()->getPdo();
    $revoked = ($action === 'unrevoke') ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE licenses SET revoked = ? WHERE id = ?");
    $stmt->execute([$revoked, $id]);
}

header('Location: dashboard.php');
exit;
