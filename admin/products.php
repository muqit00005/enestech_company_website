<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
SUB COUNT (UNCHANGED)
========================================
*/
$subs = $conn->query("SELECT COUNT(*) AS total FROM sub_email");
$data = $subs->fetch_assoc();
$total_subs = $data['total'];

/*
========================================
FILTERS
========================================
*/
$search_title = $_GET['search_title'] ?? '';
$search_category = $_GET['search_category'] ?? '';
$search_price = $_GET['search_price'] ?? '';
$search_status = $_GET['search_status'] ?? '';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

$limit = $_GET['limit'] ?? 10;
if($limit > 100) $limit = 100;

$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

/*
========================================
WHERE QUERY
========================================
*/
$where = "WHERE 1=1";

if($search_title != ""){
    $where .= " AND product_title LIKE '%$search_title%'";
}

if($search_category != ""){
    $where .= " AND product_category = '$search_category'";
}

if($search_price != ""){
    $where .= " AND product_price LIKE '%$search_price%'";
}

if($search_status != ""){
    $where .= " AND product_status = '$search_status'";
}

if($start_date != "" && $end_date != ""){
    $where .= " AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
}

if($start_time != "" && $end_time != ""){
    $where .= " AND TIME(created_at) BETWEEN '$start_time' AND '$end_time'";
}

/*
========================================
TOTAL + PAGINATION
========================================
*/
$totalQuery = $conn->query("SELECT COUNT(*) as total FROM products $where");
$totalData = $totalQuery->fetch_assoc();
$total_records = $totalData['total'];
$total_pages = ceil($total_records / $limit);

/*
========================================
FETCH PRODUCTS
========================================
*/
$products = $conn->query("SELECT * FROM products $where ORDER BY id DESC LIMIT $limit OFFSET $offset");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" type="text/css" href="../css/style.css">
<title>Admin Products</title>

<style>
body{
  font-family: Arial;
  background:#f4f6f9;
  margin:0;
}

.main-content{
  margin-left:260px;
  padding:20px;
}

section{
  background:white;
  padding:25px;
  border-radius:10px;
  margin-bottom:30px;
  box-shadow:0 0 10px rgba(0,0,0,.05);
  max-width:1000px;
}

input, textarea, select, button{
  padding:10px;
  margin:6px 0;
  width:100%;
  border-radius:6px;
  border:1px solid #ccc;
}

/* =========================
ORIGINAL BUTTON STYLE RESTORED
========================= */
button{
  background:#111;
  color:white;
  border:none;
  cursor:pointer;
}

button:hover{
  background:#333;
}

/* =========================
STATUS BOX (UNCHANGED)
========================= */
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

/* =========================
TABLE
========================= */
.table-container{
  overflow-x:auto;
}

table{
  width:100%;
  border-collapse:collapse;
}

table th, table td{
  border:1px solid #ddd;
  padding:10px;
}

table th{
  background:#111;
  color:#fff;
}

.product-img{
  width:55px;
  height:55px;
  object-fit:cover;
  border-radius:5px;
}

/* =========================
ACTION BUTTONS (RESTORED EXACTLY)
========================= */
.action-btn{
  padding:6px 10px;
  border-radius:5px;
  text-decoration:none;
  font-size:13px;
  margin-right:5px;
  display:inline-block;
  color:white;
}

.edit-btn{
  background:#2563eb;
}

.delete-btn{
  background:#dc2626;
}

.learn-btn{
  background:#16a34a;
}

/* =========================
PAGINATION
========================= */
.pagination a{
  padding:6px 10px;
  background:#111;
  color:#fff;
  margin:2px;
  text-decoration:none;
  display:inline-block;
}

@media(max-width:768px){
  .main-content{margin-left:0;}
}
</style>
</head>

<body>

<?php include __DIR__ . "/sidebar.php"; ?>

<div class="main-content">

<?php if(isset($_SESSION['broadcast_success'])): ?>
<div class="status"><?= $_SESSION['broadcast_success']; unset($_SESSION['broadcast_success']) ?></div>
<?php endif; ?>

<?php if(isset($_SESSION['broadcast_error'])): ?>
<div class="status error"><?= $_SESSION['broadcast_error']; unset($_SESSION['broadcast_error']) ?></div>
<?php endif; ?>

<!-- ================= ADD PRODUCT (UNCHANGED) ================= -->
<section>
<h1>🛒 Add New Product</h1>

<form action="../auth.php" method="POST" enctype="multipart/form-data">

<input type="text" name="product_title" placeholder="Product Title" required>

<textarea name="product_description" rows="5" required></textarea>

<input type="text" name="product_price">

<select name="product_category" required>
<option value="">Category</option>
<option>Templates</option>
<option>Digital Tools</option>
<option>UI Kits</option>
<option>Courses</option>
<option>Softwares</option>
<option>Apps</option>
</select>

<select name="product_status">
<option>For sale</option>
<option>Sold</option>
</select>

<input type="file" name="product_image" required>

<input type="text" name="product_button_text">

<input type="url" name="product_button_link">

<button name="add_product_btn">Add Product</button>
</form>
</section>

<!-- ================= FILTERS ================= -->
<section>
<h2>🔍 Filters</h2>

<form method="GET">

<input type="text" name="search_title" placeholder="Title">

<input type="text" name="search_price" placeholder="Price">

<select name="search_category">
<option value="">Category</option>
<option>Templates</option>
<option>Digital Tools</option>
<option>UI Kits</option>
<option>Courses</option>
<option>Softwares</option>
<option>Apps</option>
</select>

<select name="search_status">
<option value="">Status</option>
<option>For sale</option>
<option>Sold</option>
</select>

<label>Start Date</label>
<input type="date" name="start_date">

<label>End Date</label>
<input type="date" name="end_date">

<label>Start Time</label>
<input type="time" name="start_time">

<label>End Time</label>
<input type="time" name="end_time">

<label>Rows</label>
<select name="limit">
<?php for($i=10;$i<=100;$i+=10): ?>
<option value="<?= $i ?>"><?= $i ?></option>
<?php endfor; ?>
</select>

<button type="submit">Filter</button>
<a href="products.php"><button type="button">Reset</button></a>

</form>
</section>

<!-- ================= TABLE ================= -->
<section>
<h2>📦 Products (<?= $total_records ?>)</h2>

<div class="table-container">
<table>
<tr>
<th>ID</th>
<th>Image</th>
<th>Title</th>
<th>Price</th>
<th>Category</th>
<th>Status</th>
<th>Date/Time</th>
<th>Button</th>
<th>Actions</th>
</tr>

<?php while($row = $products->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>

<td><img src="../<?= $row['product_image'] ?>" class="product-img"></td>

<td><?= $row['product_title'] ?></td>
<td><?= $row['product_price'] ?></td>
<td><?= $row['product_category'] ?></td>
<td><?= $row['product_status'] ?></td>

<td><?= $row['created_at'] ?? 'N/A' ?></td>

<!-- ================= BUTTON COLUMN RESTORED ================= -->
<td>
<a class="action-btn learn-btn" href="<?= $row['product_button_link'] ?>" target="_blank">
<?= $row['product_button_text'] ?>
</a>
</td>

<td>
<a class="action-btn edit-btn" href="edit_product.php?id=<?= $row['id'] ?>">Edit</a>
<a class="action-btn delete-btn" href="delete_product.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this product?')">Delete</a>
</td>

</tr>
<?php endwhile; ?>

</table>
</div>

<!-- PAGINATION -->
<div class="pagination">
<?php for($i=1;$i<=$total_pages;$i++): ?>
<a href="?page=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

</section>

</div>
</body>
</html>