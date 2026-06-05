<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
LOCK CHECK FUNCTION
========================================
*/
function isJobLocked($job_status, $payment_status){

    return (
        ($job_status === 'successful' && $payment_status === 'paid') ||
        ($job_status === 'rejected' && $payment_status === 'rejected')
    );
}

/*
========================================
UPDATE JOB STATUS & PAYMENT STATUS
========================================
*/
if(isset($_POST['update_status'])){

    $quote_id = intval($_POST['quote_id']);

    $check = $conn->prepare("SELECT job_status, payment_status FROM quotes WHERE id=?");
    $check->bind_param("i", $quote_id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();

    if(!$row){
        $_SESSION['error'] = "Job not found.";
        header("Location: quote-jobs.php");
        exit;
    }

    if(isJobLocked($row['job_status'], $row['payment_status'])){
        $_SESSION['error'] = "This job is locked and cannot be modified.";
        header("Location: quote-jobs.php");
        exit;
    }

    $job_status = trim($_POST['job_status']);
    $payment_status = trim($_POST['payment_status']);

    $allowed_job_status = ['pending', 'processing', 'successful', 'rejected'];
    $allowed_payment_status = ['pending', 'paid', 'rejected'];

    if(in_array($job_status, $allowed_job_status) && in_array($payment_status, $allowed_payment_status)){

        $stmt = $conn->prepare("
            UPDATE quotes
            SET job_status=?, payment_status=?
            WHERE id=?
        ");

        $stmt->bind_param("ssi", $job_status, $payment_status, $quote_id);

        $_SESSION['success'] = $stmt->execute()
            ? "Status updated successfully."
            : "Failed to update status.";

    } else {
        $_SESSION['error'] = "Invalid status selected.";
    }

    header("Location: quote-jobs.php");
    exit;
}

/*
========================================
DELETE QUOTE
========================================
*/
if(isset($_GET['delete'])){

    $delete_id = intval($_GET['delete']);

    $check = $conn->prepare("SELECT job_status, payment_status FROM quotes WHERE id=?");
    $check->bind_param("i", $delete_id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();

    if($row){

        if(isJobLocked($row['job_status'], $row['payment_status'])){
            $_SESSION['error'] = "This job is locked and cannot be deleted.";
        } else {

            $stmt = $conn->prepare("DELETE FROM quotes WHERE id=?");
            $stmt->bind_param("i", $delete_id);

            $_SESSION['success'] = $stmt->execute()
                ? "Quote deleted successfully."
                : "Failed to delete quote.";
        }

    } else {
        $_SESSION['error'] = "Quote not found.";
    }

    header("Location: quote-jobs.php");
    exit;
}

/*
========================================
FILTERS
========================================
*/
$search = trim($_GET['search'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

$limit = min(max(intval($_GET['limit'] ?? 50), 1), 100);
$page = max(intval($_GET['page'] ?? 1), 1);
$offset = ($page - 1) * $limit;

/*
========================================
WHERE BUILDER (OPTIMIZED)
========================================
*/
$where = [];
$params = [];
$types = "";

if($search !== ""){

    $like = "%$search%";

    $searchCols = [
        "full_name","email","phone","whatsapp","source",
        "service","budget","product_name","product_price",
        "project_name","referral_code","job_status","payment_status"
    ];

    $where[] = "(" . implode(" OR ", array_map(fn($c) => "$c LIKE ?", $searchCols)) . ")";

    foreach($searchCols as $c){
        $params[] = $like;
        $types .= "s";
    }
}

if($start_date){
    $where[] = "created_at >= ?";
    $params[] = "$start_date 00:00:00";
    $types .= "s";
}

if($end_date){
    $where[] = "created_at <= ?";
    $params[] = "$end_date 23:59:59";
    $types .= "s";
}

if($start_date && $start_time){
    $where[] = "created_at >= ?";
    $params[] = "$start_date $start_time:00";
    $types .= "s";
}

if($end_date && $end_time){
    $where[] = "created_at <= ?";
    $params[] = "$end_date $end_time:59";
    $types .= "s";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/*
========================================
COUNT QUERY (LIGHTWEIGHT)
========================================
*/
$count_sql = "SELECT COUNT(id) AS total FROM quotes $whereSQL";
$count_stmt = $conn->prepare($count_sql);

if($params){
    $bind = [$types];
    foreach($params as $k => $v){
        $bind[] = &$params[$k];
    }
    call_user_func_array([$count_stmt, 'bind_param'], $bind);
}

$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_rows / $limit);

/*
========================================
MAIN QUERY (ONLY NEEDED FIELDS)
========================================
*/
$sql = "
SELECT
    id,
    full_name,
    email,
    phone,
    whatsapp,
    source,
    service,
    budget,
    product_name,
    product_price,
    project_name,
    referral_code,
    job_status,
    payment_status,
    message,
    created_at
FROM quotes
$whereSQL
ORDER BY created_at DESC
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

/*
BIND
*/
if($params){

    $bind = [$types . "ii"];
    foreach($params as $k => $v){
        $bind[] = &$params[$k];
    }
    $bind[] = &$limit;
    $bind[] = &$offset;

    call_user_func_array([$stmt, 'bind_param'], $bind);

} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$quotes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Quote Jobs - Admin</title>

<link rel="stylesheet" type="text/css" href="quotes.css">

</head>

<body>

<?php include('sidebar.php'); ?>

<div class="main-content">

<div class="topbar">
    <h1>📋 Quote Job Submissions</h1>
</div>

<?php if(isset($_SESSION['success'])): ?>

<div class="success-message">
    <?= $_SESSION['success']; ?>
</div>

<?php unset($_SESSION['success']); endif; ?>

<?php if(isset($_SESSION['error'])): ?>

<div class="error-message">
    <?= $_SESSION['error']; ?>
</div>

<?php unset($_SESSION['error']); endif; ?>

<form method="GET" class="search-box">

    <input
        type="text"
        name="search"
        placeholder="Search anything..."
        value="<?= htmlspecialchars($search); ?>"
    >

    <input type="date" name="start_date" value="<?= $start_date; ?>">

    <input type="date" name="end_date" value="<?= $end_date; ?>">

    <input type="time" name="start_time" value="<?= $start_time; ?>">

    <input type="time" name="end_time" value="<?= $end_time; ?>">

    <select name="limit">

        <?php for($i=10; $i<=100; $i+=10): ?>

            <option
                value="<?= $i; ?>"
                <?= ($limit == $i) ? 'selected' : ''; ?>
            >
                <?= $i; ?> rows
            </option>

        <?php endfor; ?>

    </select>

    <button type="submit">Filter</button>

    <?php if($search || $start_date || $end_date || $start_time || $end_time || isset($_GET['limit'])): ?>

        <a href="quote-jobs.php" class="clear-btn">
            Clear
        </a>

    <?php endif; ?>

</form>

<div class="table-container">

<?php if($quotes->num_rows > 0): ?>

<table>

<thead>

<tr>

    <th>ID</th>

    <th>Full Name</th>

    <th>Email</th>

    <th>Phone</th>

    <th>WhatsApp</th>

    <th>Source</th>

    <th>Service</th>

    <th>Budget</th>

    <th>Product Name</th>

    <th>Product Price</th>

    <th>Project Name</th>

    <th>Referral Code</th>

    <th>Referral Link</th>

    <th>Job Status</th>

    <th>Payment Status</th>

    <th>Message</th>

    <th>Date</th>

    <th>Action</th>

</tr>

</thead>

<tbody>

<?php while($row = $quotes->fetch_assoc()): ?>

<?php
$locked = isJobLocked($row['job_status'], $row['payment_status']);
?>

<?php
$referral_link = "";

if(!empty($row['referral_code'])){

    $referral_link =
    "http://localhost/enestech_company_website/customer_dashboard/auth/register.php?ref=" .
    urlencode($row['referral_code']);
}
?>

<tr>

    <td>#<?= $row['id']; ?></td>

    <td><?= htmlspecialchars($row['full_name']); ?></td>

    <td>
        <a href="mailto:<?= $row['email']; ?>">
            <?= htmlspecialchars($row['email']); ?>
        </a>
    </td>

    <td><?= htmlspecialchars($row['phone']); ?></td>

    <td><?= htmlspecialchars($row['whatsapp']); ?></td>

    <td><?= ucfirst($row['source']); ?></td>

    <td><?= htmlspecialchars($row['service']); ?></td>

    <td><?= htmlspecialchars($row['budget']); ?></td>

    <td><?= htmlspecialchars($row['product_name']); ?></td>

    <td><?= htmlspecialchars($row['product_price']); ?></td>

    <td><?= htmlspecialchars($row['project_name']); ?></td>

    <td><?= htmlspecialchars($row['referral_code']); ?></td>

    <td>

        <?php if($referral_link != ""): ?>

            <div class="referral-link-box">

                <input
                    type="text"
                    id="referral_link_<?= $row['id']; ?>"
                    value="<?= htmlspecialchars($referral_link); ?>"
                    readonly
                >

                <button
                    type="button"
                    class="copy-btn"
                    onclick="copyReferralLink('referral_link_<?= $row['id']; ?>')"
                >
                    Copy
                </button>

            </div>

        <?php else: ?>

            N/A

        <?php endif; ?>

    </td>

    <!-- JOB STATUS -->
    <td>

        <form method="POST">

            <input
                type="hidden"
                name="quote_id"
                value="<?= $row['id']; ?>"
            >

            <select name="job_status" <?= $locked ? 'disabled' : ''; ?>>

                <option value="pending"
                    <?= ($row['job_status'] == 'pending') ? 'selected' : ''; ?>>
                    Pending
                </option>

                <option value="processing"
                    <?= ($row['job_status'] == 'processing') ? 'selected' : ''; ?>>
                    Processing
                </option>

                <option value="successful"
                    <?= ($row['job_status'] == 'successful') ? 'selected' : ''; ?>>
                    Successful
                </option>

                <option value="rejected"
                    <?= ($row['job_status'] == 'rejected') ? 'selected' : ''; ?>>
                    Rejected
                </option>

            </select>

    </td>

    <!-- PAYMENT STATUS -->
    <td>

            <select name="payment_status" <?= $locked ? 'disabled' : ''; ?>>

                <option value="pending"
                    <?= ($row['payment_status'] == 'pending') ? 'selected' : ''; ?>>
                    Pending
                </option>

                <option value="paid"
                    <?= ($row['payment_status'] == 'paid') ? 'selected' : ''; ?>>
                    Paid
                </option>

                <option value="rejected"
                    <?= ($row['payment_status'] == 'rejected') ? 'selected' : ''; ?>>
                    Rejected
                </option>

            </select>

            <button type="submit" name="update_status" <?= $locked ? 'disabled' : ''; ?>>
                Save
            </button>

        </form>

    </td>

    <td>

        <div class="message-box">
            <?= nl2br(htmlspecialchars($row['message'])); ?>
        </div>

    </td>

    <td>
        <?= date("M d, Y h:i A", strtotime($row['created_at'])); ?>
    </td>

    <td>

        <a
            class="delete-btn"
            href="?delete=<?= $row['id']; ?>&page=<?= $page; ?>"
            onclick="return confirm('Delete this quote submission?')"
        >
            Delete
        </a>

    </td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<!-- =========================
PAGINATION
========================= -->

<div class="pagination">

<?php
$queryString = $_GET;

unset($queryString['page']);

$baseQuery = http_build_query($queryString);
?>

<?php if($page > 1): ?>

    <a href="?<?= $baseQuery; ?>&page=<?= $page-1; ?>">
        ← Prev
    </a>

<?php endif; ?>

<?php for($i = 1; $i <= $total_pages; $i++): ?>

    <a
        href="?<?= $baseQuery; ?>&page=<?= $i; ?>"
        style="background: <?= ($i == $page) ? '#444' : '#111'; ?>;"
    >

        <?= $i; ?>

    </a>

<?php endfor; ?>

<?php if($page < $total_pages): ?>

    <a href="?<?= $baseQuery; ?>&page=<?= $page+1; ?>">
        Next →
    </a>

<?php endif; ?>

</div>

<?php else: ?>

<div class="empty">
    No quote submissions found.
</div>

<?php endif; ?>

</div>
</div>

<script>

function copyReferralLink(inputId){

    var copyText = document.getElementById(inputId);

    copyText.select();

    copyText.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(copyText.value);

    alert("Referral link copied successfully.");
}

</script>

</body>
</html>