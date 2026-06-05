<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit;
}

require_once('../config.php');

/*
========================================
ADD JOB
========================================
*/
if(isset($_POST['add_job'])){

    $job_title = trim($_POST['job_title']);
    $job_location = trim($_POST['job_location']);
    $positions_available = trim($_POST['positions_available']);
    $job_description = trim($_POST['job_description']);

    $stmt = $conn->prepare("
        INSERT INTO careers_jobs
        (
            job_title,
            job_location,
            positions_available,
            job_description
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssss",
        $job_title,
        $job_location,
        $positions_available,
        $job_description
    );

    if($stmt->execute()){

        echo "
        <script>
            alert('Job added successfully');
            window.location.href='add_job.php';
        </script>
        ";

    }else{

        echo "
        <script>
            alert('Failed to add job');
        </script>
        ";
    }
}

/*
========================================
FETCH JOBS
========================================
*/
$jobs = $conn->query("SELECT * FROM careers_jobs ORDER BY id DESC");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Job</title>

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

        input,
        textarea, select{
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
            transition:0.3s;
        }

        button:hover{
            background:#333;
        }

        /* TABLE STYLING */
        .job-table{
            width:100%;
            margin-top:40px;
            background:#fff;
            border-radius:10px;
            overflow:hidden;
            box-shadow:0 2px 10px rgba(0,0,0,0.08);
        }

        .job-table table{
            width:100%;
            border-collapse:collapse;
        }

        .job-table th,
        .job-table td{
            padding:12px;
            border-bottom:1px solid #eee;
            text-align:left;
            font-size:14px;
        }

        .job-table th{
            background:#111;
            color:#fff;
        }

        .action-btn{
            padding:6px 10px;
            border-radius:5px;
            text-decoration:none;
            color:#fff;
            font-size:13px;
        }

        .edit-btn{
            background:#007bff;
        }

        .delete-btn{
            background:#dc3545;
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

    <!-- ADD JOB FORM -->
    <form method="POST">

        <h1>Add New Job</h1>
        <select name="job_title" required>
            <option value="">Select Job Title</option>
            <option value="UI/UX Designer">UI/UX Designer</option>
            <option value="Frontend Developer">Frontend Developer</option>
            <option value="Backend Developer">Backend Developer</option>
            <option value="Full Stack Developer">Full Stack Developer</option>
            <option value="Brand Designer">Brand Designer</option>
            <option value="Product Designer">Product Designer</option>
            <option value="No-Code Developer">No-Code Developer</option>
            <option value="Project Manager">Project Manager</option>
        </select>

        <input type="text" name="job_location" placeholder="Job Location" required>

        <input type="text" name="positions_available" placeholder="Positions Available" required>

        <textarea name="job_description" placeholder="Job Description"></textarea>

        <button type="submit" name="add_job">
            Upload Job
        </button>

    </form>

    <!-- JOB TABLE -->
    <div class="job-table">

        <table>

            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Location</th>
                <th>Positions</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>

            <?php while($row = $jobs->fetch_assoc()){ ?>

            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['job_title']; ?></td>
                <td><?php echo $row['job_location']; ?></td>
                <td><?php echo $row['positions_available']; ?></td>


                <td>
                    <?php echo substr($row['job_description'], 0, 60); ?>...
                </td>

                <td>
                    <a class="action-btn edit-btn"
                    href="edit_job.php?id=<?php echo $row['id']; ?>">
                        Edit
                    </a>

                    <a class="action-btn delete-btn"
                    href="delete_job.php?id=<?php echo $row['id']; ?>"
                    onclick="return confirm('Are you sure you want to delete this job?')">
                        Delete
                    </a>
                </td>
            </tr>

            <?php } ?>

        </table>

    </div>

</div>

</body>
</html>