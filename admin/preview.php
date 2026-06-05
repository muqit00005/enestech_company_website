<?php
session_start();

if (!isset($_SESSION['preview_subject'])) {
    header("Location: cdxrcdpi2341904456.php");
    exit();
}

$subject = $_SESSION['preview_subject'];
$message = $_SESSION['preview_message'];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Newsletter Preview</title>
</head>
<body style="background:#f4f4f4;padding:40px;">

  <h2>Newsletter Preview</h2>
  <p><b>Subject:</b> <?php echo $subject; ?></p>

  <div style="background:#fff;padding:30px;max-width:700px;">
    <?php echo $message; ?>
  </div>

  <br>
  <a href="cdxrcdpi2341904456.php">← Back to Admin</a>

</body>
</html>
