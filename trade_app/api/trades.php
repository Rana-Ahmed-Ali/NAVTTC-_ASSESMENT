<?php
require 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM trades ORDER BY created_at DESC");
    $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'trades' => $trades]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['name'])) {
        $stmt = $pdo->prepare("INSERT INTO trades (name) VALUES (:name)");
        $stmt->execute(['name' => $data['name']]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Trade name is required']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['id']) && !empty($data['name'])) {
        $stmt = $pdo->prepare("UPDATE trades SET name = :name WHERE id = :id");
        $stmt->execute(['name' => $data['name'], 'id' => $data['id']]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID and name are required']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        // Find all student IDs under this trade to delete their uploads directories
        $stmt = $pdo->prepare("SELECT id FROM students WHERE trade_id = :trade_id");
        $stmt->execute(['trade_id' => $id]);
        $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($students as $student_id) {
            $dir = "../uploads/{$student_id}";
            if (is_dir($dir)) {
                deleteDirectory($dir);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM trades WHERE id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
    }
}
