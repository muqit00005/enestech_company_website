<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/* -----------------------------
   PAGINATION SETTINGS
------------------------------*/

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$limit = max(10, min($limit, 100));

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

$offset = ($page - 1) * $limit;

/* -----------------------------
   FILTERS
------------------------------*/

$search   = trim($_GET['search'] ?? '');
$position = trim($_GET['position'] ?? '');
$status   = trim($_GET['status'] ?? '');

/* -----------------------------
   BUILD WHERE CLAUSE SAFELY
------------------------------*/

$where = "WHERE 1=1";
$params = [];
$types  = "";

/* Search filter */
if (!empty($search)) {
    $where .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR whatsapp LIKE ?)";
    $searchParam = "%{$search}%";

    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;

    $types .= "ssss";
}

/* Position filter */
if (!empty($position)) {
    $where .= " AND position = ?";
    $params[] = $position;
    $types .= "s";
}

/* Status filter */
if (!empty($status)) {
    $where .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

/* -----------------------------
   COUNT QUERY (FAST)
------------------------------*/

$countSql = "SELECT COUNT(id) AS total FROM job_applications $where";
$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = (int)$countResult->fetch_assoc()['total'];

$totalPages = max(1, ceil($totalRecords / $limit));

/* -----------------------------
   DATA QUERY (ONLY NEEDED FIELDS)
------------------------------*/

$dataSql = "
    SELECT
        id,
        full_name,
        email,
        phone,
        whatsapp,
        position,
        experience,
        portfolio,
        cv_file,
        status,
        created_at
    FROM job_applications
    $where
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$dataStmt = $conn->prepare($dataSql);

/* bind filters + pagination */
$bindParams = $params;
$bindTypes  = $types . "ii";
$bindParams[] = $limit;
$bindParams[] = $offset;

$dataStmt->bind_param($bindTypes, ...$bindParams);
$dataStmt->execute();

$result = $dataStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Job Applications</title>

    <style>
        body{margin:0;font-family:Arial;background:#f5f5f5;}
        .main-content{margin-left:260px;padding:20px;}
        h1{margin-top:0;}

        table{width:100%;border-collapse:collapse;background:#fff;margin-top:20px;}
        th,td{padding:12px;border:1px solid #ddd;text-align:left;font-size:13px;}
        th{background:#111;color:#fff;}

        a{color:green;text-decoration:none;font-weight:bold;}

        .filter-box{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
        .filter-box input,.filter-box select{padding:8px;border:1px solid #ccc;}

        .pagination{margin-top:20px;display:flex;gap:8px;flex-wrap:wrap;}
        .pagination a{padding:8px 12px;background:#111;color:#fff;text-decoration:none;}
        .pagination a.active{background:green;}

        @media(max-width:768px){
            .main-content{margin-left:0;}
        }
    </style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">

    <h1>Applicants</h1>

    <form method="GET" class="filter-box">

        <input type="text" name="search" placeholder="Search name/email/phone"
               value="<?= htmlspecialchars($search) ?>">

        <select name="position">
            <option value="">All Positions</option>
            <option value="UI/UX Designer" <?= $position=='UI/UX Designer'?'selected':'' ?>>UI/UX Designer</option>
            <option value="Frontend Developer" <?= $position=='Frontend Developer'?'selected':'' ?>>Frontend Developer</option>
            <option value="Backend Developer" <?= $position=='Backend Developer'?'selected':'' ?>>Backend Developer</option>
            <option value="Full Stack Developer" <?= $position=='Full Stack Developer'?'selected':'' ?>>Full Stack Developer</option>
            <option value="Brand Designer" <?= $position=='Brand Designer'?'selected':'' ?>>Brand Designer</option>
            <option value="Product Designer" <?= $position=='Product Designer'?'selected':'' ?>>Product Designer</option>
            <option value="No-Code Developer" <?= $position=='No-Code Developer'?'selected':'' ?>>No-Code Developer</option>
            <option value="Project Manager" <?= $position=='Project Manager'?'selected':'' ?>>Project Manager</option>
        </select>

        <select name="status">
            <option value="">All Status</option>
            <option value="Pending" <?= $status=='Pending'?'selected':'' ?>>Pending</option>
            <option value="Reviewed" <?= $status=='Reviewed'?'selected':'' ?>>Reviewed</option>
            <option value="Shortlisted" <?= $status=='Shortlisted'?'selected':'' ?>>Shortlisted</option>
            <option value="Rejected" <?= $status=='Rejected'?'selected':'' ?>>Rejected</option>
        </select>

        <select name="limit">
            <?php for($i=10; $i<=100; $i+=10){ ?>
                <option value="<?= $i ?>" <?= $limit==$i?'selected':'' ?>>
                    Show <?= $i ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit">Filter</button>

    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>WhatsApp</th>
            <th>Position</th>
            <th>Experience</th>
            <th>Portfolio</th>
            <th>CV</th>
            <th>Status</th>
            <th>Action</th>
            <th>Date</th>
        </tr>

        <?php while($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= htmlspecialchars($row['full_name']); ?></td>
            <td><a href="mailto:<?= $row['email']; ?>"><?= $row['email']; ?></a></td>
            <td><a href="tel:<?= $row['phone']; ?>"><?= $row['phone']; ?></a></td>

            <td>
                <a target="_blank"
                   href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $row['whatsapp']); ?>">
                    <?= $row['whatsapp']; ?>
                </a>
            </td>

            <td><?= htmlspecialchars($row['position']); ?></td>
            <td><?= htmlspecialchars($row['experience']); ?></td>

            <td>
                <a href="<?= $row['portfolio']; ?>" target="_blank">View</a>
            </td>

            <td>
                <a href="../uploads_cvs/cvs/<?= $row['cv_file']; ?>" target="_blank">
                    Download
                </a>
            </td>

            <td><strong><?= $row['status']; ?></strong></td>

            <td>

                <form action="update_application_status.php" method="POST">
                    <input type="hidden" name="id" value="<?= $row['id']; ?>">

                    <select name="status">
                        <option <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                        <option <?= $row['status']=='Reviewed'?'selected':'' ?>>Reviewed</option>
                        <option <?= $row['status']=='Shortlisted'?'selected':'' ?>>Shortlisted</option>
                        <option <?= $row['status']=='Rejected'?'selected':'' ?>>Rejected</option>
                    </select>

                    <button type="submit">Update</button>
                </form>

                <form action="delete_application.php" method="POST"
                      onsubmit="return confirm('Delete this application?');"
                      style="margin-top:8px;">

                    <input type="hidden" name="id" value="<?= $row['id']; ?>">

                    <button style="background:red;color:#fff;padding:6px;border:none;">
                        Delete
                    </button>

                </form>

            </td>

            <td><?= $row['created_at']; ?></td>
        </tr>
        <?php } ?>
    </table>

    <div class="pagination">
        <?php for($i=1; $i<=$totalPages; $i++){ ?>
            <a class="<?= $i==$page?'active':'' ?>"
               href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&position=<?= urlencode($position) ?>&status=<?= urlencode($status) ?>">
                <?= $i ?>
            </a>
        <?php } ?>
    </div>

</div>

</body>
</html>