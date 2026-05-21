<?php
/**
 * Auth Guard - include at the top of every API endpoint.
 * Returns 401 JSON if user is not logged in.
 */
require_once __DIR__ . '/../session.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthenticated', 'success' => false]);
    exit;
}
