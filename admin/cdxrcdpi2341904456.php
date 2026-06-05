<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

$subs = $conn->query("SELECT COUNT(*) AS total FROM sub_email");
$data = $subs->fetch_assoc();
$total_subs = $data['total'];


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" type="text/css" href="../css/style.css">
<title>Admin Dashboard - enesicode</title>

<style>
body{
  font-family: Arial, sans-serif;
  background:#f4f6f9;
  margin:0;
}

/* =========================
MAIN CONTENT (adjust for sidebar)
========================= */
.main-content{
  margin-left:260px;
  padding:20px;
}

/* =========================
DASHBOARD UI
========================= */

section{
  background:white;
  padding:25px;
  border-radius:10px;
  margin-bottom:30px;
  box-shadow:0 0 10px rgba(0,0,0,.05);
  max-width:900px;
}

input, textarea, select, button{
  padding:10px;
  margin:8px 0;
  width:100%;
  border-radius:6px;
  border:1px solid #ccc;
}

button{
  background:#111;
  color:white;
  border:none;
  cursor:pointer;
  font-size:15px;
}

button:hover{
  background:#333;
}

.status{
  padding:10px;
  background:#ecfdf5;
  margin-bottom:10px;
  border-radius:5px;
  color:#06603a;
}

.error{
  background:#fee2e2;
  color:#7f1d1d;
}

.counter{
  font-size:18px;
  background:black;
  color:white;
  padding:12px;
  display:inline-block;
  border-radius:30px;
  margin-bottom:20px;
}

/* =========================
MOBILE FIX
========================= */
@media(max-width:768px){
  .main-content{
    margin-left:0;
  }
}
</style>
</head>

<body>

<!-- ✅ SIDEBAR INCLUDED HERE -->
<?php include __DIR__ . "/sidebar.php"; ?>

<!-- MAIN CONTENT START -->
<div class="main-content">

<div class="counter">
📊 Total Subscribers: <?php echo $total_subs; ?>
</div>

<?php if(isset($_SESSION['broadcast_success'])): ?>
  <div class="status"><?= $_SESSION['broadcast_success']; unset($_SESSION['broadcast_success']) ?></div>
<?php endif; ?>

<?php if(isset($_SESSION['broadcast_error'])): ?>
  <div class="status error"><?= $_SESSION['broadcast_error']; unset($_SESSION['broadcast_error']) ?></div>
<?php endif; ?>


</div>
<!-- MAIN CONTENT END -->
</body>
</html>