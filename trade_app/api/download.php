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
} elseif (isset($_GET['trade_id'])) {
    $trade_id = $_GET['trade_id'];
    
    // Get trade details
    $stmt = $pdo->prepare("SELECT * FROM trades WHERE id = :id");
    $stmt->execute(['id' => $trade_id]);
    $trade = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trade) {
        die('Trade not found');
    }
    
    // Get all students enrolled in this trade
    $stmt = $pdo->prepare("SELECT * FROM students WHERE trade_id = :trade_id");
    $stmt->execute(['trade_id' => $trade_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        die('No students enrolled in this trade.');
    }
    
    $zip_filename = preg_replace('/[^a-zA-Z0-9]+/', '_', $trade['name']) . "_Trade_Photos.zip";
    $zip_filepath = sys_get_temp_dir() . '/' . $zip_filename;
    
    $zip = new ZipArchive();
    if ($zip->open($zip_filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $hasFiles = false;
        
        foreach ($students as $student) {
            $student_id = $student['id'];
            $dir = "../uploads/{$student_id}";
            
            if (is_dir($dir)) {
                $files = scandir($dir);
                $folderName = preg_replace('/[^a-zA-Z0-9]+/', '_', $student['name']) . "_" . preg_replace('/[^a-zA-Z0-9]+/', '_', $student['father_name']);
                
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $zip->addFile("{$dir}/{$file}", "{$folderName}/{$file}");
                        $hasFiles = true;
                    }
                }
            }
        }
        
        $zip->close();
        
        if (!$hasFiles) {
            die('No photos have been captured for any student in this trade.');
        }
        
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
    die('Student ID or Trade ID required.');
}
