<?php
/**
 * License Verification API
 * POST /api/verify
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Validate required fields
$required = ['license_key', 'device_id', 'nonce', 'timestamp'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: {$field}"]);
        exit;
    }
}

// Extract fields
$licenseKey = trim($input['license_key']);
$deviceId = trim($input['device_id']);
$nonce = trim($input['nonce']);
$timestamp = intval($input['timestamp']);

// Validate timestamp
if ($timestamp <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid timestamp']);
    exit;
}

try {
    // Verify license
    $result = LicenseManager::verify($licenseKey, $deviceId, $nonce, $timestamp);
    
    // Sign response
    $signedResponse = LicenseManager::signResponse($result);
    
    http_response_code(200);
    echo json_encode($signedResponse);
    
} catch (Exception $e) {
    error_log("License verification error: " . $e->getMessage());
    http_response_code(500);
    
    $errorResponse = [
        'valid' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'server_time' => time()
    ];
    
    $signedResponse = LicenseManager::signResponse($errorResponse);
    echo json_encode($signedResponse);
}
