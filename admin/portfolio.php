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

/*
========================================
EXPORT HANDLER
========================================
*/
if(isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf'])){

    $export_type = $_GET['export'];

    $export_query = $conn->query("SELECT * FROM portfolio_projects ORDER BY id DESC");

    if($export_type === 'excel'){
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=portfolio_projects.xls");

        echo "ID\tTitle\tCategory\tYear\tClient\tCreated At\n";

        while($row = $export_query->fetch_assoc()){
            echo $row['id']."\t".
                 $row['project_title']."\t".
                 $row['category']."\t".
                 $row['project_year']."\t".
                 $row['client_name']."\t".
                 ($row['created_at'] ?? 'N/A')."\n";
        }
        exit;
    }

    if($export_type === 'pdf'){
        header("Content-Type: application/pdf");
        header("Content-Disposition: attachment; filename=portfolio_projects.pdf");

        echo "<h2>Portfolio Projects</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Category</th><th>Year</th><th>Client</th><th>Date</th></tr>";

        while($row = $export_query->fetch_assoc()){
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['project_title']}</td>
                    <td>{$row['category']}</td>
                    <td>{$row['project_year']}</td>
                    <td>{$row['client_name']}</td>
                    <td>".($row['created_at'] ?? 'N/A')."</td>
                  </tr>";
        }

        echo "</table>";
        exit;
    }
}

/*
========================================
FILTERS
========================================
*/
$search_title = $_GET['search_title'] ?? '';
$search_category = $_GET['search_category'] ?? '';
$search_client = $_GET['search_client'] ?? '';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

$rows_per_page = isset($_GET['rows']) ? (int)$_GET['rows'] : 10;
if($rows_per_page < 1) $rows_per_page = 10;
if($rows_per_page > 100) $rows_per_page = 100;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;

$offset = ($page - 1) * $rows_per_page;

/*
========================================
BUILD QUERY
========================================
*/
$where = "WHERE 1=1";

if(!empty($search_title)){
    $where .= " AND project_title LIKE '%" . $conn->real_escape_string($search_title) . "%'";
}

if(!empty($search_category)){
    $where .= " AND category = '" . $conn->real_escape_string($search_category) . "'";
}

if(!empty($search_client)){
    $where .= " AND client_name LIKE '%" . $conn->real_escape_string($search_client) . "%'";
}

if(!empty($start_date)){
    $where .= " AND DATE(created_at) >= '" . $conn->real_escape_string($start_date) . "'";
}

if(!empty($end_date)){
    $where .= " AND DATE(created_at) <= '" . $conn->real_escape_string($end_date) . "'";
}

if(!empty($start_time)){
    $where .= " AND TIME(created_at) >= '" . $conn->real_escape_string($start_time) . "'";
}

if(!empty($end_time)){
    $where .= " AND TIME(created_at) <= '" . $conn->real_escape_string($end_time) . "'";
}

/*
========================================
PAGINATION DATA
========================================
*/
$total_query = $conn->query("SELECT COUNT(*) AS total FROM portfolio_projects $where");
$total_data = $total_query->fetch_assoc();
$total_records = $total_data['total'];
$total_pages = ceil($total_records / $rows_per_page);

/*
========================================
FETCH DATA
========================================
*/
$portfolio = $conn->query("
    SELECT * FROM portfolio_projects
    $where
    ORDER BY id DESC
    LIMIT $offset, $rows_per_page
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" type="text/css" href="../css/style.css">
<title>Admin Portfolio - enesicode</title>

<style>
body{
  font-family: Arial, sans-serif;
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
}

button:hover{
  background:#333;
}

.table-wrap{
  overflow-x:auto;
}

table{
  width:100%;
  border-collapse:collapse;
  min-width:900px;
}

table th, table td{
  border:1px solid #ddd;
  padding:10px;
  font-size:14px;
}

table th{
  background:#111;
  color:white;
}

.action-btn{
  padding:6px 10px;
  border-radius:5px;
  text-decoration:none;
  display:inline-block;
  margin-right:5px;
}

.edit-btn{background:#0ea5e9;color:#fff;}
.delete-btn{background:#ef4444;color:#fff;}

/* PAGINATION */
.pagination a{
  padding:6px 10px;
  margin:2px;
  background:#111;
  color:#fff;
  text-decoration:none;
  border-radius:4px;
}

.pagination a.active{
  background:#0ea5e9;
}

/* MOBILE */
@media(max-width:768px){
  .main-content{margin-left:0;}
}
</style>
</head>

<body>

<?php include __DIR__ . "/sidebar.php"; ?>

<div class="main-content">

<!-- ADD -->
<section>
<h1>📁 Add Portfolio Project</h1>

<form action="../auth.php" method="POST" enctype="multipart/form-data">
<input type="text" name="project_title" placeholder="Project Title" required>
<textarea name="project_overview" placeholder="Project Overview" required></textarea>
<input type="text" name="client_name" placeholder="Client Name">
<input type="text" name="services" placeholder="Services">
<input type="number" name="project_year" placeholder="Year">
<input type="text" name="duration" placeholder="Duration">
<input type="url" name="project_url" placeholder="Project URL">
<input type="file" name="project_image">

<select name="category" required>
<option value="">--Select Category--</option>
<option value="UI/UX Design">UI/UX Design</option>
<option value="Website Development">Website Development</option>
<option value="Brand Design">Brand Design</option>
<option value="Mobile App">Mobile App</option>
<option value="Marketing">Marketing</option>
</select>

<button type="submit" name="portfolio_submit_btn">Add Project</button>
</form>
</section>

<!-- FILTERS -->
<section>
<h1>🔎 Filters</h1>

<form method="GET">

<input type="text" name="search_title" placeholder="Title" value="<?= $search_title ?>">
<input type="text" name="search_client" placeholder="Client" value="<?= $search_client ?>">

<select name="search_category">
<option value="">All Categories</option>
<option value="UI/UX Design">UI/UX Design</option>
<option value="Website Development">Website Development</option>
<option value="Brand Design">Brand Design</option>
<option value="Mobile App">Mobile App</option>
<option value="Marketing">Marketing</option>
</select>

<label>Start Date</label>
<input type="date" name="start_date" value="<?= $start_date ?>">

<label>End Date</label>
<input type="date" name="end_date" value="<?= $end_date ?>">

<label>Start Time</label>
<input type="time" name="start_time" value="<?= $start_time ?>">

<label>End Time</label>
<input type="time" name="end_time" value="<?= $end_time ?>">

<label>Rows (max 100)</label>
<input type="number" name="rows" max="100" value="<?= $rows_per_page ?>">

<button type="submit">Apply Filters</button>

<a href="?" style="display:inline-block;padding:10px;background:#6b7280;color:#fff;text-decoration:none;border-radius:6px;">Reset Filters</a>

<a href="?export=excel" style="display:inline-block;padding:10px;background:#16a34a;color:#fff;text-decoration:none;border-radius:6px;">Export Excel</a>

<a href="?export=pdf" style="display:inline-block;padding:10px;background:#dc2626;color:#fff;text-decoration:none;border-radius:6px;">Export PDF</a>

</form>
</section>

<!-- TABLE -->
<section>
<h1>📊 Portfolio Projects</h1>

<div class="table-wrap">
<table>
<thead>
<tr>
  <th>ID</th>
  <th>Title</th>
  <th>Category</th>
  <th>Year</th>
  <th>Client</th>
  <th>Date/Time</th>
  <th>Image</th>
  <th>Actions</th>
</tr>
</thead>

<tbody>

<?php if($portfolio->num_rows > 0): ?>
<?php while($row = $portfolio->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['project_title'] ?></td>
<td><?= $row['category'] ?></td>
<td><?= $row['project_year'] ?></td>
<td><?= $row['client_name'] ?></td>
<td><?= $row['created_at'] ?? 'N/A' ?></td>

<td>
<?php if(!empty($row['project_image'])): ?>
<img src="../<?= $row['project_image'] ?>" width="60">
<?php else: ?>No Image<?php endif; ?>
</td>

<td>
<a class="action-btn edit-btn" href="edit_portfolio.php?id=<?= $row['id'] ?>">Edit</a>
<a class="action-btn delete-btn" href="delete_portfolio.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="8">No data found</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>

<!-- PAGINATION -->
<div class="pagination">
<?php for($i=1;$i<=$total_pages;$i++): ?>
<a class="<?= ($i==$page)?'active':'' ?>"
href="?page=<?= $i ?>&search_title=<?= $search_title ?>&search_category=<?= $search_category ?>&search_client=<?= $search_client ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&start_time=<?= $start_time ?>&end_time=<?= $end_time ?>&rows=<?= $rows_per_page ?>">
<?= $i ?>
</a>
<?php endfor; ?>
</div>

</section>

</div>
</body>
</html>