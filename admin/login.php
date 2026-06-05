<?php
session_start();
require_once('../config.php');
/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true){
    header("Location: cdxrcdpi2341904456.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/
$error = "";

if(isset($_POST['admin_login_btn'])){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if(empty($username) || empty($password)){
        $error = "All fields are required!";
    }else{

        /*
        |--------------------------------------------------------------------------
        | CHECK ADMIN USER
        |--------------------------------------------------------------------------
        */
        $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if($result->num_rows > 0){

            $admin = $result->fetch_assoc();

            /*
            |--------------------------------------------------------------------------
            | PASSWORD CHECK
            |--------------------------------------------------------------------------
            | For plain password:
            */
            if($password === $admin['password']){

                /*
                |--------------------------------------------------------------------------
                | UPDATE LAST LOGIN
                |--------------------------------------------------------------------------
                */
                $update = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $admin['id']);
                $update->execute();

                /*
                |--------------------------------------------------------------------------
                | CREATE SESSION
                |--------------------------------------------------------------------------
                */
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];

                header("Location: cdxrcdpi2341904456.php");
                exit;

            }else{
                $error = "Invalid username or password!";
            }

        }else{
            $error = "Invalid username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Login - enesicode</title>

<link rel="stylesheet" href="../css/style.css">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    background:#f4f6f9;
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
}

.login-container{
    width:100%;
    max-width:420px;
    background:#fff;
    padding:35px;
    border-radius:12px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

.logo{
    text-align:center;
    margin-bottom:25px;
}

.logo h1{
    font-size:26px;
    color:#111;
}

.logo p{
    color:#666;
    font-size:14px;
    margin-top:5px;
}

.error{
    background:#fee2e2;
    color:#7f1d1d;
    padding:12px;
    border-radius:6px;
    margin-bottom:15px;
    font-size:14px;
}

.input-group{
    margin-bottom:18px;
}

.input-group label{
    display:block;
    margin-bottom:6px;
    font-size:14px;
    color:#333;
}

.input-group input{
    width:100%;
    padding:13px;
    border:1px solid #ccc;
    border-radius:6px;
    outline:none;
    font-size:15px;
}

.input-group input:focus{
    border-color:#111;
}

button{
    width:100%;
    padding:14px;
    background:#111;
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:15px;
    transition:0.3s;
}

button:hover{
    background:#333;
}

.footer-text{
    margin-top:20px;
    text-align:center;
    color:#888;
    font-size:13px;
}
</style>
</head>

<body>

<div class="login-container">

    <div class="logo">
        <h1>🔐 Admin Login</h1>
        <p>enesicode Dashboard</p>
    </div>

    <?php if(!empty($error)): ?>
        <div class="error">
            <?= $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="input-group">
            <label>Username</label>
            <input 
                type="text" 
                name="username" 
                placeholder="Enter username"
                required
            >
        </div>

        <div class="input-group">
            <label>Password</label>
            <input 
                type="password" 
                name="password" 
                placeholder="Enter password"
                required
            >
        </div>

        <button type="submit" name="admin_login_btn">
            Login
        </button>

    </form>

    <div class="footer-text">
        © <?php echo date("Y"); ?> enesicode
    </div>

</div>

</body>
</html>