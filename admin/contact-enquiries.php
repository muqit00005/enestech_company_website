<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
UPDATE JOB & PAYMENT STATUS
========================================
*/
if(isset($_POST['update_status'])){

    $id = intval($_POST['id']);
    $job_status = $_POST['job_status'];
    $payment_status = $_POST['payment_status'];

    $allowed_job = ['pending', 'process', 'successful', 'rejected'];
    $allowed_payment = ['pending', 'paid', 'rejected'];

    if(
        in_array($job_status, $allowed_job) &&
        in_array($payment_status, $allowed_payment)
    ){

        $stmt = $conn->prepare("
            UPDATE contact_enquiries
            SET job_status=?, payment_status=?
            WHERE id=?
        ");

        $stmt->bind_param("ssi", $job_status, $payment_status, $id);
        $stmt->execute();
    }

    header("Location: contact-enquiries.php");
    exit;
}

/*
========================================
DELETE JOB
========================================
*/
if(isset($_GET['delete'])){

    $delete_id = intval($_GET['delete']);

    // Only fetch needed columns (optimized)
    $check = $conn->prepare("
        SELECT job_status, payment_status
        FROM contact_enquiries
        WHERE id=?
    ");

    $check->bind_param("i", $delete_id);
    $check->execute();

    $result = $check->get_result();

    if($result->num_rows > 0){

        $job = $result->fetch_assoc();

        $isLocked =
            ($job['job_status'] == 'successful' && $job['payment_status'] == 'paid') ||
            ($job['job_status'] == 'rejected' && $job['payment_status'] == 'rejected');

        if(!$isLocked){

            $stmt = $conn->prepare("
                DELETE FROM contact_enquiries
                WHERE id=?
            ");

            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
        }
    }

    header("Location: contact-enquiries.php");
    exit;
}

/*
========================================
FILTERS
========================================
*/
$search_full_name = $_GET['search_full_name'] ?? '';
$search_email = $_GET['search_email'] ?? '';
$search_phone = $_GET['search_phone'] ?? '';
$search_whatsapp = $_GET['search_whatsapp'] ?? '';
$search_referral_code = $_GET['search_referral_code'] ?? '';
$search_job_status = $_GET['search_job_status'] ?? '';
$search_payment_status = $_GET['search_payment_status'] ?? '';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

$rows = isset($_GET['rows']) ? intval($_GET['rows']) : 10;

if($rows <= 0) $rows = 10;
if($rows > 100) $rows = 100;

/*
========================================
PAGINATION
========================================
*/
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if($page <= 0) $page = 1;

$offset = ($page - 1) * $rows;

/*
========================================
BUILD WHERE QUERY
========================================
*/
$where = " WHERE 1=1 ";
$params = [];
$types = '';

if(!empty($search_full_name)){
    $where .= " AND full_name LIKE ? ";
    $params[] = "%".$search_full_name."%";
    $types .= 's';
}

if(!empty($search_email)){
    $where .= " AND email LIKE ? ";
    $params[] = "%".$search_email."%";
    $types .= 's';
}

if(!empty($search_phone)){
    $where .= " AND phone LIKE ? ";
    $params[] = "%".$search_phone."%";
    $types .= 's';
}

if(!empty($search_whatsapp)){
    $where .= " AND whatsapp LIKE ? ";
    $params[] = "%".$search_whatsapp."%";
    $types .= 's';
}

if(!empty($search_referral_code)){
    $where .= " AND referral_code LIKE ? ";
    $params[] = "%".$search_referral_code."%";
    $types .= 's';
}

if(!empty($search_job_status)){
    $where .= " AND job_status = ? ";
    $params[] = $search_job_status;
    $types .= 's';
}

if(!empty($search_payment_status)){
    $where .= " AND payment_status = ? ";
    $params[] = $search_payment_status;
    $types .= 's';
}

/*
========================================
DATE FILTER
========================================
*/
if(!empty($start_date) && !empty($end_date)){

    $startDateTime = $start_date . ' ' . (!empty($start_time) ? $start_time : '00:00:00');
    $endDateTime = $end_date . ' ' . (!empty($end_time) ? $end_time : '23:59:59');

    $where .= " AND created_at BETWEEN ? AND ? ";

    $params[] = $startDateTime;
    $params[] = $endDateTime;
    $types .= 'ss';
}

/*
========================================
COUNT TOTAL ROWS (OPTIMIZED)
========================================
*/
$countQuery = "
    SELECT COUNT(id) as total
    FROM contact_enquiries
    $where
";

$countStmt = $conn->prepare($countQuery);

if(!empty($params)){
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];

$totalPages = ceil($totalRows / $rows);

/*
========================================
FETCH CONTACT ENQUIRIES (OPTIMIZED SELECT)
========================================
ONLY REQUIRED FIELDS ARE SELECTED
========================================
*/
$query = "
    SELECT
        id,
        full_name,
        email,
        phone,
        whatsapp,
        message,
        budget,
        referral_code,
        job_status,
        payment_status,
        created_at
    FROM contact_enquiries
    $where
    ORDER BY id DESC
    LIMIT ?, ?
";

$stmt = $conn->prepare($query);

$finalParams = $params;
$finalTypes = $types . 'ii';

$finalParams[] = $offset;
$finalParams[] = $rows;

$stmt->bind_param($finalTypes, ...$finalParams);

$stmt->execute();

$enquiries = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" type="text/css" href="../css/style.css">
<title>Contact Enquiries - Admin Panel</title>

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

/* FILTER BOX */
.filter-box{
  background:white;
  padding:20px;
  border-radius:10px;
  margin-bottom:20px;
  box-shadow:0 0 10px rgba(0,0,0,.05);
}

.filter-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
  gap:15px;
}

.filter-box input,
.filter-box select{
  width:100%;
  padding:10px;
  border:1px solid #ccc;
  border-radius:6px;
  font-size:14px;
  box-sizing:border-box;
}

.filter-btns{
  margin-top:20px;
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

.filter-btn{
  padding:10px 18px;
  border:none;
  border-radius:6px;
  cursor:pointer;
  font-size:14px;
  text-decoration:none;
}

.search-btn{
  background:#111;
  color:white;
}

.reset-btn{
  background:#dc2626;
  color:white;
}

/* TABLE */
.table-wrapper{
  overflow-x:auto;
}

table{
  width:100%;
  border-collapse:collapse;
  background:white;
  border-radius:10px;
  overflow:hidden;
  box-shadow:0 0 10px rgba(0,0,0,.05);
}

th, td{
  padding:12px;
  border-bottom:1px solid #eee;
  text-align:left;
  font-size:14px;
  vertical-align:top;
}

th{
  background:#111;
  color:white;
}

tr:hover{
  background:#f9f9f9;
}

.badge{
  display:inline-block;
  padding:4px 10px;
  border-radius:20px;
  background:#ecfdf5;
  color:#06603a;
  font-size:12px;
}

.status-select{
  padding:8px;
  border-radius:6px;
  border:1px solid #ccc;
  outline:none;
  font-size:13px;
  width:100%;
}

.update-btn{
  padding:7px 12px;
  border:none;
  border-radius:6px;
  background:#111;
  color:white;
  cursor:pointer;
  margin-top:8px;
  font-size:12px;
  width:100%;
}

.update-btn:hover{
  opacity:.9;
}

.delete-btn{
  display:inline-block;
  padding:8px 14px;
  background:#dc2626;
  color:white;
  text-decoration:none;
  border-radius:6px;
  font-size:13px;
}

.delete-btn:hover{
  background:#b91c1c;
}

.locked-btn{
  display:inline-block;
  padding:8px 14px;
  background:#9ca3af;
  color:white;
  border-radius:6px;
  font-size:13px;
  cursor:not-allowed;
}

.copy-btn{
  padding:6px 10px;
  border:none;
  border-radius:6px;
  background:#2563eb;
  color:white;
  cursor:pointer;
  font-size:12px;
  margin-top:5px;
}

.copy-btn:hover{
  background:#1d4ed8;
}

.referral-link-box{
  max-width:250px;
  word-break:break-all;
  font-size:12px;
  color:#333;
}

/* PAGINATION */
.pagination{
  margin-top:20px;
  display:flex;
  flex-wrap:wrap;
  gap:8px;
}

.pagination a,
.pagination span{
  padding:8px 14px;
  border-radius:6px;
  text-decoration:none;
  background:white;
  color:#111;
  border:1px solid #ddd;
  font-size:14px;
}

.pagination .active{
  background:#111;
  color:white;
  border-color:#111;
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

<h2>📩 Contact Enquiries</h2>
<br>

<!-- FILTER FORM -->
<div class="filter-box">

<form method="GET">

<div class="filter-grid">

  <div>
    <label>Full Name</label>
    <input
      type="text"
      name="search_full_name"
      value="<?= htmlspecialchars($search_full_name) ?>"
      placeholder="Search full name"
    >
  </div>

  <div>
    <label>Email</label>
    <input
      type="text"
      name="search_email"
      value="<?= htmlspecialchars($search_email) ?>"
      placeholder="Search email"
    >
  </div>

  <div>
    <label>Phone</label>
    <input
      type="text"
      name="search_phone"
      value="<?= htmlspecialchars($search_phone) ?>"
      placeholder="Search phone"
    >
  </div>

  <div>
    <label>WhatsApp</label>
    <input
      type="text"
      name="search_whatsapp"
      value="<?= htmlspecialchars($search_whatsapp) ?>"
      placeholder="Search whatsapp"
    >
  </div>

  <div>
    <label>Referral Code</label>
    <input
      type="text"
      name="search_referral_code"
      value="<?= htmlspecialchars($search_referral_code) ?>"
      placeholder="Search referral code"
    >
  </div>

  <div>
    <label>Job Status</label>
    <select name="search_job_status">
      <option value="">All</option>

      <option
        value="pending"
        <?= $search_job_status == 'pending' ? 'selected' : '' ?>
      >
        Pending
      </option>

      <option
        value="process"
        <?= $search_job_status == 'process' ? 'selected' : '' ?>
      >
        Process
      </option>

      <option
        value="successful"
        <?= $search_job_status == 'successful' ? 'selected' : '' ?>
      >
        Successful
      </option>

      <option
        value="rejected"
        <?= $search_job_status == 'rejected' ? 'selected' : '' ?>
      >
        Rejected
      </option>
    </select>
  </div>

  <div>
    <label>Payment Status</label>
    <select name="search_payment_status">
      <option value="">All</option>

      <option
        value="pending"
        <?= $search_payment_status == 'pending' ? 'selected' : '' ?>
      >
        Pending
      </option>

      <option
        value="paid"
        <?= $search_payment_status == 'paid' ? 'selected' : '' ?>
      >
        Paid
      </option>

      <option
        value="rejected"
        <?= $search_payment_status == 'rejected' ? 'selected' : '' ?>
      >
        Rejected
      </option>
    </select>
  </div>

  <div>
    <label>Start Date</label>
    <input
      type="date"
      name="start_date"
      value="<?= htmlspecialchars($start_date) ?>"
    >
  </div>

  <div>
    <label>End Date</label>
    <input
      type="date"
      name="end_date"
      value="<?= htmlspecialchars($end_date) ?>"
    >
  </div>

  <div>
    <label>Start Time</label>
    <input
      type="time"
      name="start_time"
      value="<?= htmlspecialchars($start_time) ?>"
    >
  </div>

  <div>
    <label>End Time</label>
    <input
      type="time"
      name="end_time"
      value="<?= htmlspecialchars($end_time) ?>"
    >
  </div>

  <div>
    <label>Rows Per Page</label>
    <select name="rows">

      <option value="10" <?= $rows == 10 ? 'selected' : '' ?>>
        10
      </option>

      <option value="20" <?= $rows == 20 ? 'selected' : '' ?>>
        20
      </option>

      <option value="50" <?= $rows == 50 ? 'selected' : '' ?>>
        50
      </option>

      <option value="100" <?= $rows == 100 ? 'selected' : '' ?>>
        100
      </option>

    </select>
  </div>

</div>

<div class="filter-btns">

  <button type="submit" class="filter-btn search-btn">
    Search
  </button>

  <a href="contact-enquiries.php" class="filter-btn reset-btn">
    Reset Filter
  </a>

</div>

</form>

</div>

<div class="table-wrapper">

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Full Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>WhatsApp</th>
      <th>Message</th>
      <th>Budget</th>
      <th>Referral Code</th>
      <th>Referral Link</th>
      <th>Job Status</th>
      <th>Payment Status</th>
      <th>Update</th>
      <th>Date</th>
      <th>Action</th>
    </tr>
  </thead>

  <tbody>

    <?php if($enquiries && $enquiries->num_rows > 0): ?>

        <?php while($row = $enquiries->fetch_assoc()): ?>

        <?php
          $referralLink = '';

          if(!empty($row['referral_code'])){
              $referralLink =
              "http://localhost/enestech_company_website/customer_dashboard/auth/register.php?ref="
              . urlencode($row['referral_code']);
          }

          /*
          ========================================
          CHECK IF JOB IS LOCKED
          ========================================
          */
          $isLocked = false;

          if(
              $row['job_status'] == 'successful' &&
              $row['payment_status'] == 'paid'
          ){
              $isLocked = true;
          }

          if(
              $row['job_status'] == 'rejected' &&
              $row['payment_status'] == 'rejected'
          ){
              $isLocked = true;
          }
        ?>

        <tr>

          <td><?= $row['id'] ?></td>

          <td><?= htmlspecialchars($row['full_name']) ?></td>

          <td><?= htmlspecialchars($row['email']) ?></td>

          <td><?= htmlspecialchars($row['phone']) ?></td>

          <td><?= htmlspecialchars($row['whatsapp']) ?></td>

          <td>
            <?= htmlspecialchars(substr($row['message'], 0, 60)) ?>
            <?= strlen($row['message']) > 60 ? '...' : '' ?>
          </td>

          <td>
            <?php if(!empty($row['budget'])): ?>
                <span class="badge">
                  <?= htmlspecialchars($row['budget']) ?>
                </span>
            <?php else: ?>
                -
            <?php endif; ?>
          </td>

          <td>
            <?php if(!empty($row['referral_code'])): ?>
                <span class="badge">
                  <?= htmlspecialchars($row['referral_code']) ?>
                </span>
            <?php else: ?>
                -
            <?php endif; ?>
          </td>

          <td>
            <?php if(!empty($referralLink)): ?>

                <div
                  class="referral-link-box"
                  id="referralLink<?= $row['id'] ?>"
                >
                    <?= htmlspecialchars($referralLink) ?>
                </div>

                <button
                  class="copy-btn"
                  onclick="copyReferralLink('referralLink<?= $row['id'] ?>')"
                >
                  Copy Link
                </button>

            <?php else: ?>
                -
            <?php endif; ?>
          </td>

          <form method="POST">

            <input
              type="hidden"
              name="id"
              value="<?= $row['id'] ?>"
            >

            <!-- JOB STATUS -->
            <td>

              <select
                name="job_status"
                class="status-select"
                <?= $isLocked ? 'disabled' : '' ?>
              >

                <option
                  value="pending"
                  <?= ($row['job_status'] ?? '') == 'pending' ? 'selected' : '' ?>
                >
                  Pending
                </option>

                <option
                  value="process"
                  <?= ($row['job_status'] ?? '') == 'process' ? 'selected' : '' ?>
                >
                  Process
                </option>

                <option
                  value="successful"
                  <?= ($row['job_status'] ?? '') == 'successful' ? 'selected' : '' ?>
                >
                  Successful
                </option>

                <option
                  value="rejected"
                  <?= ($row['job_status'] ?? '') == 'rejected' ? 'selected' : '' ?>
                >
                  Rejected
                </option>

              </select>

            </td>

            <!-- PAYMENT STATUS -->
            <td>

              <select
                name="payment_status"
                class="status-select"
                <?= $isLocked ? 'disabled' : '' ?>
              >

                <option
                  value="pending"
                  <?= ($row['payment_status'] ?? '') == 'pending' ? 'selected' : '' ?>
                >
                  Pending
                </option>

                <option
                  value="paid"
                  <?= ($row['payment_status'] ?? '') == 'paid' ? 'selected' : '' ?>
                >
                  Paid
                </option>

                <option
                  value="rejected"
                  <?= ($row['payment_status'] ?? '') == 'rejected' ? 'selected' : '' ?>
                >
                  Rejected
                </option>

              </select>

            </td>

            <!-- SINGLE UPDATE BUTTON -->
            <td>

              <?php if($isLocked): ?>

                <span class="locked-btn">
                  Locked
                </span>

              <?php else: ?>

                <button
                  type="submit"
                  name="update_status"
                  class="update-btn"
                >
                  Update
                </button>

              <?php endif; ?>

            </td>

          </form>

          <td><?= $row['created_at'] ?></td>

          <td>

            <?php if($isLocked): ?>

                <span class="locked-btn">
                  Locked
                </span>

            <?php else: ?>

                <a
                  href="?delete=<?= $row['id'] ?>"
                  class="delete-btn"
                  onclick="return confirm('Are you sure you want to delete this job?')"
                >
                  Delete
                </a>

            <?php endif; ?>

          </td>

        </tr>

        <?php endwhile; ?>

    <?php else: ?>

        <tr>
          <td colspan="14" style="text-align:center;">
            No contact enquiries found.
          </td>
        </tr>

    <?php endif; ?>

  </tbody>
</table>

</div>

<!-- PAGINATION -->
<?php if($totalPages > 1): ?>

<div class="pagination">

  <?php for($i = 1; $i <= $totalPages; $i++): ?>

    <a
      href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
      class="<?= $page == $i ? 'active' : '' ?>"
    >
      <?= $i ?>
    </a>

  <?php endfor; ?>

</div>

<?php endif; ?>

</div>

<script>
function copyReferralLink(elementId){

    const text =
      document.getElementById(elementId).innerText;

    navigator.clipboard.writeText(text)
    .then(() => {
        alert("Referral link copied successfully!");
    });

}
</script>

</body>
</html>