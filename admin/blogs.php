<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
FILTERS
========================================
*/
$search_title = $_GET['search_title'] ?? '';
$search_category = $_GET['search_category'] ?? '';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

$rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 10;

/*
========================================
PAGINATION (ADDED)
========================================
*/
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;

/*
========================================
LIMIT ROWS (MAX 100)
========================================
*/
$allowed_rows = [10, 20, 50, 100];

if(!in_array($rows, $allowed_rows)){
    $rows = 10;
}

$offset = ($page - 1) * $rows;

/*
========================================
BUILD QUERY
========================================
*/
$query = "SELECT * FROM blogs WHERE 1=1";

/* SEARCH TITLE */
if(!empty($search_title)){
    $title = mysqli_real_escape_string($conn, $search_title);
    $query .= " AND title LIKE '%$title%'";
}

/* SEARCH CATEGORY */
if(!empty($search_category)){
    $category = mysqli_real_escape_string($conn, $search_category);
    $query .= " AND category LIKE '%$category%'";
}

/* START DATE */
if(!empty($start_date)){
    $start_date_safe = mysqli_real_escape_string($conn, $start_date);
    $query .= " AND DATE(created_at) >= '$start_date_safe'";
}

/* END DATE */
if(!empty($end_date)){
    $end_date_safe = mysqli_real_escape_string($conn, $end_date);
    $query .= " AND DATE(created_at) <= '$end_date_safe'";
}

/* START TIME */
if(!empty($start_time)){
    $start_time_safe = mysqli_real_escape_string($conn, $start_time);
    $query .= " AND TIME(created_at) >= '$start_time_safe'";
}

/* END TIME */
if(!empty($end_time)){
    $end_time_safe = mysqli_real_escape_string($conn, $end_time);
    $query .= " AND TIME(created_at) <= '$end_time_safe'";
}

/*
========================================
COUNT TOTAL ROWS (ADDED)
========================================
*/
$count_query = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$count_result = $conn->query($count_query);
$count_data = $count_result->fetch_assoc();
$total_rows = $count_data['total'];

$total_pages = ceil($total_rows / $rows);

/*
========================================
FINAL QUERY (WITH PAGINATION)
========================================
*/
$query .= " ORDER BY id DESC LIMIT $offset, $rows";

/*
========================================
FETCH BLOGS
========================================
*/
$blogs = $conn->query($query);

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
<title>Admin Blogs - enesicode</title>

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
  max-width:1000px;
}

input, textarea, select, button{
  padding:10px;
  margin:8px 0;
  width:100%;
  border-radius:6px;
  border:1px solid #ccc;
  box-sizing:border-box;
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

table{
  width:100%;
  border-collapse:collapse;
  margin-top:20px;
  background:white;
}

table th, table td{
  border:1px solid #ddd;
  padding:12px;
  text-align:left;
  font-size:14px;
}

table th{
  background:#111;
  color:#fff;
}

table tr:nth-child(even){
  background:#f9f9f9;
}

.action-btn{
  padding:6px 10px;
  border-radius:5px;
  text-decoration:none;
  font-size:13px;
  color:#fff;
  display:inline-block;
  margin-right:5px;
}

.edit-btn{ background:#2563eb; }
.delete-btn{ background:#dc2626; }

.filter-box{
  background:#f9fafb;
  padding:15px;
  border-radius:10px;
  margin-bottom:20px;
  border:1px solid #e5e7eb;
}

.filter-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
  gap:15px;
}

.filter-btn{
  background:#111;
  color:#fff;
}

.reset-btn{
  background:#dc2626;
  color:#fff;
  text-decoration:none;
  padding:10px;
  border-radius:6px;
  display:inline-block;
  text-align:center;
}

/* PAGINATION STYLE (ADDED) */
.pagination{
  margin-top:20px;
  display:flex;
  flex-wrap:wrap;
  gap:8px;
}

.pagination a{
  padding:8px 12px;
  background:#eee;
  border-radius:5px;
  text-decoration:none;
  color:#000;
}

.pagination a.active{
  background:#111;
  color:#fff;
}

/* MOBILE */
@media(max-width:768px){
  .main-content{
    margin-left:0;
  }
}
</style>
</head>

<body>

<?php include __DIR__ . "/sidebar.php"; ?>

<div class="main-content">

<!-- ================= BLOG CREATE ================= -->
<section>
<h1>📝 Create Blog Post</h1>

<form action="../auth.php" method="POST" enctype="multipart/form-data">

<input type="text" name="title" placeholder="Title" required>
<textarea name="content" placeholder="Content" required></textarea>

<input type="text" name="sub_title1" placeholder="Sub Title 1" required>
<textarea name="sub_content1" placeholder="Sub Content 1" required></textarea>

<input type="text" name="sub_title2" placeholder="Sub Title 2" required>
<textarea name="sub_content2" placeholder="Sub Content 2" required></textarea>

<textarea name="quote" placeholder="Quote" required></textarea>
<input type="text" name="category" placeholder="Category" required>
<input type="text" name="total_time_of_reading" placeholder="Total Time of Reading" required>
<input type="text" name="youtube_link" placeholder="https://www.youtube.com/watch?v=xxxxxx">
<input type="file" name="image" accept="image/*" required>

<h2>Author Information</h2>

<input type="text" name="author" placeholder="Author Name" required>
<input type="file" name="author_image" accept="image/*">
<textarea name="author_bio" placeholder="Author Bio" required></textarea>

<button type="submit" name="blog_submit_btn">Submit Blog</button>

</form>
</section>

<!-- ================= BLOG HISTORY ================= -->
<section>

<h1>📚 Blog History</h1>

<div class="filter-box">
<form method="GET">

<div class="filter-grid">

<div>
<label>Search By Title</label>
<input type="text" name="search_title" value="<?= htmlspecialchars($search_title) ?>">
</div>

<div>
<label>Search By Category</label>
<input type="text" name="search_category" value="<?= htmlspecialchars($search_category) ?>">
</div>

<div>
<label>Start Date</label>
<input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
</div>

<div>
<label>End Date</label>
<input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
</div>

<div>
<label>Start Time</label>
<input type="time" name="start_time" value="<?= htmlspecialchars($start_time) ?>">
</div>

<div>
<label>End Time</label>
<input type="time" name="end_time" value="<?= htmlspecialchars($end_time) ?>">
</div>

<div>
<label>Rows To Show</label>
<select name="rows">
<option value="10" <?= $rows == 10 ? 'selected' : '' ?>>10</option>
<option value="20" <?= $rows == 20 ? 'selected' : '' ?>>20</option>
<option value="50" <?= $rows == 50 ? 'selected' : '' ?>>50</option>
<option value="100" <?= $rows == 100 ? 'selected' : '' ?>>100</option>
</select>
</div>

</div>

<button type="submit" class="filter-btn">Apply Filters</button>
<a href="blogs.php" class="reset-btn">Reset Filters</a>

</form>
</div>

<table>
<thead>
<tr>
<th>ID</th>
<th>Image</th>
<th>Title</th>
<th>Category</th>
<th>Date</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php if($blogs->num_rows > 0): ?>
<?php while($row = $blogs->fetch_assoc()): ?>

<tr>
<td><?= $row['id'] ?></td>

<td>
<?php if(!empty($row['image'])): ?>
    <img src="../<?= htmlspecialchars($row['image']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:5px;">
<?php else: ?>
    N/A
<?php endif; ?>
</td>

<td><?= htmlspecialchars($row['title']) ?></td>
<td><?= htmlspecialchars($row['category']) ?></td>
<td><?= $row['created_at'] ?? 'N/A' ?></td>

<td>
<a class="action-btn edit-btn" href="edit_blog.php?id=<?= $row['id'] ?>">Edit</a>
<a class="action-btn delete-btn" href="delete_blog.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?');">Delete</a>
</td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6">No blogs found</td></tr>
<?php endif; ?>

</tbody>
</table>

<!-- ================= PAGINATION (ADDED) ================= -->
<div class="pagination">

<?php for($i = 1; $i <= $total_pages; $i++): ?>

<a 
href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
class="<?= ($i == $page) ? 'active' : '' ?>">
<?= $i ?>
</a>

<?php endfor; ?>

</div>

</section>

</div>
</body>
</html>