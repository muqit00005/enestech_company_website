<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
GET ID
========================================
*/
if(!isset($_GET['id'])){
    header("Location: portfolio.php");
    exit;
}

$id = $_GET['id'];

/*
========================================
FETCH EXISTING DATA
========================================
*/
$stmt = $conn->prepare("SELECT * FROM portfolio_projects WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    header("Location: portfolio.php");
    exit;
}

$data = $result->fetch_assoc();

/*
========================================
UPDATE PROCESS
========================================
*/
if(isset($_POST['update_portfolio'])){

    $title = $_POST['project_title'];
    $overview = $_POST['project_overview'];
    $client = $_POST['client_name'];
    $services = $_POST['services'];
    $year = $_POST['project_year'];
    $duration = $_POST['duration'];
    $url = $_POST['project_url'];
    $category = $_POST['category'];

    $imagePath = $data['project_image'];

    /*
    ============================
    IMAGE UPLOAD (OPTIONAL)
    ============================
    */
    if(!empty($_FILES['project_image']['name'])){

        $targetDir = "../uploads/";
        $fileName = uniqid() . "-" . basename($_FILES["project_image"]["name"]);
        $targetFile = $targetDir . $fileName;

        move_uploaded_file($_FILES["project_image"]["tmp_name"], $targetFile);

        $imagePath = "uploads/" . $fileName;
    }

    $stmt = $conn->prepare("
        UPDATE portfolio_projects 
        SET project_title=?, project_overview=?, client_name=?, services=?, project_year=?, duration=?, project_url=?, category=?, project_image=? 
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssssissssi",
        $title,
        $overview,
        $client,
        $services,
        $year,
        $duration,
        $url,
        $category,
        $imagePath,
        $id
    );

    if($stmt->execute()){
        $_SESSION['broadcast_success'] = "Portfolio updated successfully!";
    } else {
        $_SESSION['broadcast_error'] = "Failed to update portfolio.";
    }

    header("Location: portfolio.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Portfolio</title>

<style>
body{
    font-family: Arial;
    background:#f4f6f9;
    padding:20px;
}

.container{
    max-width:700px;
    background:white;
    padding:20px;
    border-radius:10px;
}

input, textarea, select, button{
    width:100%;
    padding:10px;
    margin:8px 0;
}
button{
    background:black;
    color:white;
    border:none;
    cursor:pointer;
}
img{
    margin-top:10px;
}
</style>
</head>

<body>

<div class="container">
<h2>Edit Portfolio Project</h2>

<form method="POST" enctype="multipart/form-data">

<input type="text" name="project_title" value="<?= $data['project_title'] ?>" required>

<textarea name="project_overview" required><?= $data['project_overview'] ?></textarea>

<input type="text" name="client_name" value="<?= $data['client_name'] ?>">

<input type="text" name="services" value="<?= $data['services'] ?>">

<input type="number" name="project_year" value="<?= $data['project_year'] ?>">

<input type="text" name="duration" value="<?= $data['duration'] ?>">

<input type="url" name="project_url" value="<?= $data['project_url'] ?>">

<select name="category" required>
    <option value="<?= $data['category'] ?>"><?= $data['category'] ?></option>
    <option value="UI/UX Design">UI/UX Design</option>
    <option value="Website Development">Website Development</option>
    <option value="Brand Design">Brand Design</option>
    <option value="Mobile App">Mobile App</option>
    <option value="Marketing">Marketing</option>
</select>

<p>Current Image:</p>
<img src="../<?= $data['project_image'] ?>" width="120">

<input type="file" name="project_image">

<button type="submit" name="update_portfolio">Update Project</button>

</form>
</div>

</body>
</html>