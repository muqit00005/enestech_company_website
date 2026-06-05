<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
GET PRODUCT ID
========================================
*/
if(!isset($_GET['id'])){
    header("Location: products.php");
    exit;
}

$id = intval($_GET['id']);

/*
========================================
FETCH PRODUCT
========================================
*/
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    die("Product not found.");
}

$product = $result->fetch_assoc();

/*
========================================
UPDATE PRODUCT
========================================
*/
if(isset($_POST['update_product_btn'])){

    $title = $_POST['product_title'];
    $description = $_POST['product_description'];
    $price = $_POST['product_price'];
    $category = $_POST['product_category'];
    $status = $_POST['product_status'];
    $btn_text = $_POST['product_button_text'];
    $btn_link = $_POST['product_button_link'];

    $image_path = $product['product_image'];

    /*
    ================================
    IMAGE UPLOAD (OPTIONAL)
    ================================
    */
    if(!empty($_FILES['product_image']['name'])){

        $target_dir = "../uploads/";
        $file_name = time() . "-" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;

        if(move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)){

            // delete old image
            if(file_exists("../" . $product['product_image'])){
                unlink("../" . $product['product_image']);
            }

            $image_path = "uploads/" . $file_name;
        }
    }

    /*
    ================================
    UPDATE QUERY
    ================================
    */
    $update = $conn->prepare("UPDATE products SET 
        product_title=?,
        product_description=?,
        product_price=?,
        product_category=?,
        product_status=?,
        product_image=?,
        product_button_text=?,
        product_button_link=?
        WHERE id=?");

    $update->bind_param(
        "ssssssssi",
        $title,
        $description,
        $price,
        $category,
        $status,
        $image_path,
        $btn_text,
        $btn_link,
        $id
    );

    if($update->execute()){
        $_SESSION['broadcast_success'] = "Product updated successfully!";
        header("Location: products.php");
        exit;
    }else{
        $error = "Failed to update product.";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Product</title>
<style>
body{
    font-family: Arial;
    background:#f4f6f9;
    padding:20px;
}

.container{
    max-width:700px;
    margin:auto;
    background:white;
    padding:20px;
    border-radius:10px;
}

input, textarea, select{
    width:100%;
    padding:10px;
    margin:8px 0;
    border:1px solid #ccc;
    border-radius:6px;
}

button{
    background:black;
    color:white;
    padding:10px;
    width:100%;
    border:none;
    cursor:pointer;
}

img{
    width:120px;
    border-radius:6px;
    margin-bottom:10px;
}
</style>
</head>

<body>

<div class="container">

<h2>Edit Product</h2>

<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

<img src="../<?= $product['product_image'] ?>" />

<form method="POST" enctype="multipart/form-data">

<input type="text" name="product_title" value="<?= $product['product_title'] ?>" required>

<textarea name="product_description" required><?= $product['product_description'] ?></textarea>

<input type="text" name="product_price" value="<?= $product['product_price'] ?>">

<select name="product_category" required>
    <option value="Templates" <?= $product['product_category']=="Templates"?"selected":"" ?>>Templates</option>
    <option value="Digital Tools" <?= $product['product_category']=="Digital Tools"?"selected":"" ?>>Digital Tools</option>
    <option value="UI Kits" <?= $product['product_category']=="UI Kits"?"selected":"" ?>>UI Kits</option>
    <option value="Courses" <?= $product['product_category']=="Courses"?"selected":"" ?>>Courses</option>
    <option value="Softwares" <?= $product['product_category']=="Softwares"?"selected":"" ?>>Softwares</option>
    <option value="Apps" <?= $product['product_category']=="Apps"?"selected":"" ?>>Apps</option>
</select>

<select name="product_status">
    <option value="For sale" <?= $product['product_status']=="For sale"?"selected":"" ?>>For Sale</option>
    <option value="Sold" <?= $product['product_status']=="Sold"?"selected":"" ?>>Sold</option>
</select>

<label>Change Image (optional)</label>
<input type="file" name="product_image" accept="image/*">

<input type="text" name="product_button_text" value="<?= $product['product_button_text'] ?>" required>

<input type="url" name="product_button_link" value="<?= $product['product_button_link'] ?>" required>

<button type="submit" name="update_product_btn">Update Product</button>

</form>

</div>

</body>
</html>