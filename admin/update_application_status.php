<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

if(isset($_POST['id']) && isset($_POST['status'])){

    $id = intval($_POST['id']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE job_applications SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    header("Location: view_applications.php");
    exit;
}
?>