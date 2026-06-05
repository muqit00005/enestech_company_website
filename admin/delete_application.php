<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

if(isset($_POST['id'])){

    $id = intval($_POST['id']);

    // Optional: delete CV file too
    $stmt = $conn->prepare("SELECT cv_file FROM job_applications WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if($row && !empty($row['cv_file'])){
        $filePath = "../uploads_cvs/cvs/" . $row['cv_file'];
        if(file_exists($filePath)){
            unlink($filePath);
        }
    }

    // Delete record
    $stmt = $conn->prepare("DELETE FROM job_applications WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: view_applications.php");
    exit;
}
?>