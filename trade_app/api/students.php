<?php
require 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['trade_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE trade_id = :trade_id ORDER BY created_at DESC");
        $stmt->execute(['trade_id' => $_GET['trade_id']]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch photo counts for each student to show completion status
        foreach ($students as &$student) {
            $stmt = $pdo->prepare("SELECT photo_type, file_path FROM student_photos WHERE student_id = :student_id");
            $stmt->execute(['student_id' => $student['id']]);
            $student['photos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'students' => $students]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Trade ID is required']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['trade_id']) && !empty($data['name']) && !empty($data['father_name'])) {
        $stmt = $pdo->prepare("INSERT INTO students (trade_id, name, father_name) VALUES (:trade_id, :name, :father_name)");
        $stmt->execute([
            'trade_id' => $data['trade_id'],
            'name' => $data['name'],
            'father_name' => $data['father_name']
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['id']) && !empty($data['trade_id']) && !empty($data['name']) && !empty($data['father_name'])) {
        $stmt = $pdo->prepare("UPDATE students SET trade_id = :trade_id, name = :name, father_name = :father_name WHERE id = :id");
        $stmt->execute([
            'trade_id' => $data['trade_id'],
            'name' => $data['name'],
            'father_name' => $data['father_name'],
            'id' => $data['id']
        ]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'All fields (id, trade_id, name, father_name) are required']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        // Clean up uploads directory for this student
        $dir = "../uploads/{$id}";
        if (is_dir($dir)) {
            deleteDirectory($dir);
        }
        
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
    }
}
