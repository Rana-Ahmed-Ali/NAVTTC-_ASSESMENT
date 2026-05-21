<?php
require_once __DIR__ . '/auth_guard.php';
require 'db.php';

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    
    // Get student details
    $stmt = $pdo->prepare("SELECT s.*, t.name as trade_name FROM students s JOIN trades t ON s.trade_id = t.id WHERE s.id = :id");
    $stmt->execute(['id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        die('Student not found');
    }
    
    $dir = "../uploads/{$student_id}";
    if (!is_dir($dir)) {
        die('No photos available for this student.');
    }
    
    $zip_filename = preg_replace('/[^a-zA-Z0-9]+/', '_', $student['name']) . "_Photos.zip";
    $zip_filepath = sys_get_temp_dir() . '/' . $zip_filename;
    
    $zip = new ZipArchive();
    if ($zip->open($zip_filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $zip->addFile("{$dir}/{$file}", $file);
            }
        }
        $zip->close();
        
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . $zip_filename);
        header('Content-Length: ' . filesize($zip_filepath));
        readfile($zip_filepath);
        unlink($zip_filepath);
        exit;
    } else {
        die('Failed to create ZIP archive.');
    }
} else {
    die('Student ID required.');
}
