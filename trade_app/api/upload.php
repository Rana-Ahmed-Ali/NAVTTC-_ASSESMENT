<?php
require 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $student_id = $data['student_id'] ?? null;
    $photo_type = $data['photo_type'] ?? null;
    $image_base64 = $data['image'] ?? null;
    
    if (!$student_id || !$photo_type || !$image_base64) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        exit;
    }
    
    // Validate photo_type
    $valid_types = ['Practical 1', 'Practical 2', 'Subjective', 'Objective'];
    if (!in_array($photo_type, $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid photo type']);
        exit;
    }
    
    // Check base64 size before decoding (roughly 1.37 * actual size). 200MB limit.
    if (strlen($image_base64) > 200 * 1024 * 1024 * 1.37) {
        echo json_encode(['success' => false, 'message' => 'Image too large (max 200MB)']);
        exit;
    }

    $image_parts = explode(";base64,", $image_base64);
    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1];
    $image_base64 = base64_decode($image_parts[1]);
    
    $dir = "../uploads/{$student_id}";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Format the filename safely
    $safe_type = str_replace(' ', '_', strtolower($photo_type));
    $filename = "{$safe_type}.png";
    $filepath = "{$dir}/{$filename}";
    
    if (file_put_contents($filepath, $image_base64)) {
        // Delete old entry if exists
        $stmt = $pdo->prepare("DELETE FROM student_photos WHERE student_id = :student_id AND photo_type = :photo_type");
        $stmt->execute(['student_id' => $student_id, 'photo_type' => $photo_type]);
        
        // Insert new entry
        $stmt = $pdo->prepare("INSERT INTO student_photos (student_id, photo_type, file_path) VALUES (:student_id, :photo_type, :file_path)");
        $stmt->execute([
            'student_id' => $student_id,
            'photo_type' => $photo_type,
            'file_path' => "uploads/{$student_id}/{$filename}"
        ]);
        
        echo json_encode(['success' => true, 'path' => "uploads/{$student_id}/{$filename}"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
    }
}
