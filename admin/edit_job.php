<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
GET JOB ID
========================================
*/
if(!isset($_GET['id'])){
    header("Location: add_job.php");
    exit;
}

$id = intval($_GET['id']);

/*
========================================
FETCH JOB DATA
========================================
*/
$stmt = $conn->prepare("SELECT * FROM careers_jobs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    echo "Job not found";
    exit;
}

$job = $result->fetch_assoc();

/*
========================================
UPDATE JOB
========================================
*/
if(isset($_POST['update_job'])){

    $job_title = trim($_POST['job_title']);
    $job_location = trim($_POST['job_location']);
    $positions_available = trim($_POST['positions_available']);
    $job_description = trim($_POST['job_description']);

    $update = $conn->prepare("
        UPDATE careers_jobs
        SET job_title = ?,
            job_location = ?,
            positions_available = ?,
            job_description = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "ssssi",
        $job_title,
        $job_location,
        $positions_available,
        $job_description,
        $id
    );

    if($update->execute()){
        echo "
        <script>
            alert('Job updated successfully');
            window.location.href='add_job.php';
        </script>
        ";
    }else{
        echo "<script>alert('Update failed');</script>";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Job</title>

    <style>

        body{
            font-family:Arial;
            background:#f5f5f5;
            margin:0;
        }

        .main-content{
            margin-left:260px;
            padding:40px;
        }

        form{
            background:#fff;
            padding:30px;
            border-radius:10px;
            max-width:700px;
            margin:auto;
            box-shadow:0 2px 10px rgba(0,0,0,0.08);
        }

        input, textarea{
            width:100%;
            padding:15px;
            margin-bottom:20px;
            border:1px solid #ccc;
            border-radius:8px;
        }

        button{
            width:100%;
            padding:15px;
            border:none;
            background:#111;
            color:#fff;
            border-radius:8px;
            cursor:pointer;
        }

        button:hover{
            background:#333;
        }

        @media(max-width:768px){
            .main-content{
                margin-left:0;
                padding:20px;
            }
        }

    </style>

</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <form method="POST">

        <h1>Edit Job</h1>

        <input type="text" name="job_title"
               value="<?php echo $job['job_title']; ?>" required>

        <input type="text" name="job_location"
               value="<?php echo $job['job_location']; ?>" required>

        <input type="text" name="positions_available"
               value="<?php echo $job['positions_available']; ?>" required>

        <textarea name="job_description" required><?php echo $job['job_description']; ?></textarea>

        <button type="submit" name="update_job">
            Update Job
        </button>

    </form>

</div>

</body>
</html>