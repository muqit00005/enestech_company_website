<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
DELETE PORTFOLIO PROJECT
========================================
*/
if(isset($_GET['id'])){

    $id = intval($_GET['id']);

    // OPTIONAL: delete image first
    $stmt = $conn->prepare("SELECT project_image FROM portfolio_projects WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if($row && !empty($row['project_image'])){
        $filePath = "../" . $row['project_image'];
        if(file_exists($filePath)){
            unlink($filePath);
        }
    }

    // delete record
    $stmt = $conn->prepare("DELETE FROM portfolio_projects WHERE id=?");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        $_SESSION['broadcast_success'] = "Portfolio deleted successfully!";
    } else {
        $_SESSION['broadcast_error'] = "Failed to delete portfolio.";
    }
}

header("Location: portfolio.php");
exit;