<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
CHECK ID
========================================
*/
if(!isset($_GET['id'])){
    header("Location: add_job.php");
    exit;
}

$id = intval($_GET['id']);

/*
========================================
DELETE JOB
========================================
*/
$stmt = $conn->prepare("DELETE FROM careers_jobs WHERE id = ?");
$stmt->bind_param("i", $id);

if($stmt->execute()){

    echo "
    <script>
        alert('Job deleted successfully');
        window.location.href='add_job.php';
    </script>
    ";

}else{

    echo "
    <script>
        alert('Failed to delete job');
        window.location.href='add_job.php';
    </script>
    ";
}

?>