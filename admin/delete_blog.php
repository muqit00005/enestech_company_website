<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
DELETE BLOG
========================================
*/
if(!isset($_GET['id'])){
    header("Location: admin_blogs.php");
    exit;
}

$id = intval($_GET['id']);

/*
Optional safety: check if blog exists first
*/
$stmt = $conn->prepare("SELECT id FROM blogs WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    header("Location: admin_blogs.php?error=notfound");
    exit;
}

/*
DELETE
*/
$stmt = $conn->prepare("DELETE FROM blogs WHERE id=?");
$stmt->bind_param("i", $id);

if($stmt->execute()){
    header("Location: blogs.php?msg=deleted");
    exit;
} else {
    header("Location: blogs.php?error=failed");
    exit;
}
?>