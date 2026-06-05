<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

if(!isset($_GET['id'])){
    header("Location: admin_blogs.php");
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM blogs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    die("Blog not found.");
}

$blog = $result->fetch_assoc();

/*
========================================
UPDATE BLOG (WITH IMAGE REPLACEMENT)
========================================
*/
if(isset($_POST['update_blog'])){

    $title = $_POST['title'];
    $content = $_POST['content'];
    $sub_title1 = $_POST['sub_title1'];
    $sub_content1 = $_POST['sub_content1'];
    $sub_title2 = $_POST['sub_title2'];
    $sub_content2 = $_POST['sub_content2'];
    $quote = $_POST['quote'];
    $category = $_POST['category'];
    $reading_time = $_POST['total_time_of_reading'];
    $youtube = $_POST['youtube_link'];
    $author = $_POST['author'];
    $author_bio = $_POST['author_bio'];

    $image_name = $blog['image']; // default keep old image

    /*
    ================================
    HANDLE IMAGE UPLOAD
    ================================
    */
    if(!empty($_FILES['image']['name'])){

        $target_dir = "../uploads/";

        if(!is_dir($target_dir)){
            mkdir($target_dir, 0777, true);
        }

        $image_name_new = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name_new;

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed = ["jpg", "jpeg", "png", "webp"];

        if(in_array($imageFileType, $allowed)){

            if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)){

                // DELETE OLD IMAGE (if exists)
                if(!empty($blog['image'])){

                    $oldImage = "../" . $blog['image'];

                    if(file_exists($oldImage)){
                        unlink($oldImage);
                    }
                }

                $image_name = "uploads/" . $image_name_new;
            }
        }
    }

    /*
    ================================
    UPDATE DATABASE
    ================================
    */
    $stmt = $conn->prepare("
        UPDATE blogs 
        SET title=?, content=?, sub_title1=?, sub_content1=?, sub_title2=?, sub_content2=?, quote=?, category=?, total_time_of_reading=?, youtube_link=?, author=?, author_bio=?, image=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sssssssssssssi",
        $title, $content, $sub_title1, $sub_content1,
        $sub_title2, $sub_content2, $quote, $category,
        $reading_time, $youtube, $author, $author_bio,
        $image_name, $id
    );

    if($stmt->execute()){

        $_SESSION['success_message'] = "Blog updated successfully.";

        header("Location: edit_blog.php?id=" . $id);
        exit;

    } else {
        $error = "Failed to update blog.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Blog</title>

<style>
body{
    font-family:Arial;
    background:#f4f6f9;
    margin:0;
}

.container{
    max-width:800px;
    margin:40px auto;
    background:white;
    padding:20px;
    border-radius:10px;
}

input, textarea{
    width:100%;
    padding:10px;
    margin:8px 0;
    border:1px solid #ccc;
    border-radius:6px;
}

button{
    background:#111;
    color:#fff;
    padding:10px;
    border:none;
    cursor:pointer;
}

button:hover{
    background:#333;
}

img{
    width:150px;
    border-radius:8px;
    margin:10px 0;
}
.success-message{
    background:#dcfce7;
    color:#166534;
    padding:12px;
    border-radius:6px;
    margin-bottom:15px;
    border:1px solid #bbf7d0;
}
</style>
</head>

<body>

<div class="container">

<h2>Edit Blog</h2>
<?php if(isset($_SESSION['success_message'])): ?>

    <div class="success-message">
        <?= $_SESSION['success_message']; ?>
    </div>

    <?php unset($_SESSION['success_message']); ?>

<?php endif; ?>

<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST" enctype="multipart/form-data">

<input type="text" name="title" value="<?= htmlspecialchars($blog['title']) ?>" required>

<textarea name="content" required><?= htmlspecialchars($blog['content']) ?></textarea>

<input type="text" name="sub_title1" value="<?= htmlspecialchars($blog['sub_title1']) ?>" required>
<textarea name="sub_content1" required><?= htmlspecialchars($blog['sub_content1']) ?></textarea>

<input type="text" name="sub_title2" value="<?= htmlspecialchars($blog['sub_title2']) ?>" required>
<textarea name="sub_content2" required><?= htmlspecialchars($blog['sub_content2']) ?></textarea>

<textarea name="quote" required><?= htmlspecialchars($blog['quote']) ?></textarea>

<input type="text" name="category" value="<?= htmlspecialchars($blog['category']) ?>" required>

<input type="text" name="total_time_of_reading" value="<?= htmlspecialchars($blog['total_time_of_reading']) ?>" required>

<input type="text" name="youtube_link" value="<?= htmlspecialchars($blog['youtube_link']) ?>">

<input type="text" name="author" value="<?= htmlspecialchars($blog['author']) ?>" required>

<textarea name="author_bio" required><?= htmlspecialchars($blog['author_bio']) ?></textarea>

<!-- CURRENT IMAGE -->
<p>Current Image:</p>
<?php if(!empty($blog['image'])): ?>
    <img src="../<?= $blog['image'] ?>" alt="Blog Image">
<?php else: ?>
    <p>No image uploaded</p>
<?php endif; ?>

<!-- NEW IMAGE -->
<p>Replace Image:</p>
<input type="file" name="image" accept="image/*">

<button type="submit" name="update_blog">Update Blog</button>

</form>

</div>

</body>
</html>