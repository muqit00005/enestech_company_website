<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

if(!isset($_GET['id'])){
    header("Location: products.php");
    exit;
}

$id = intval($_GET['id']);

/*
========================================
GET PRODUCT IMAGE
========================================
*/
$stmt = $conn->prepare("SELECT product_image FROM products WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    header("Location: products.php");
    exit;
}

$product = $result->fetch_assoc();

/*
========================================
DELETE IMAGE FILE
========================================
*/
$image_path = "../" . $product['product_image'];

if(file_exists($image_path)){
    unlink($image_path);
}

/*
========================================
DELETE PRODUCT FROM DB
========================================
*/
$delete = $conn->prepare("DELETE FROM products WHERE id=?");
$delete->bind_param("i", $id);

if($delete->execute()){
    $_SESSION['broadcast_success'] = "Product deleted successfully!";
}else{
    $_SESSION['broadcast_error'] = "Failed to delete product!";
}

header("Location: products.php");
exit;