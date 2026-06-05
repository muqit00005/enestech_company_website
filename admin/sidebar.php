<div class="sidebar">

    <div class="sidebar-header">
        <h2>⚙ Admin Panel</h2>
    </div>

    <ul class="sidebar-menu">

        <li>
            <a href="cdxrcdpi2341904456.php">
                📊 Dashboard
            </a>
        </li>

        <li>
            <a href="quote-jobs.php">
                📋 Quote Jobs
            </a>
        </li>
        
        <li>
            <a href="add_job.php">
                ➕ Add Job
            </a>
        </li>

        <li>
            <a href="view_applications.php">
                👨‍💼 Job Applications
            </a>
        </li>

        <li>
            <a href="blogs.php">
                📝 Blogs
            </a>
        </li>

        <li>
            <a href="portfolio.php">
                📁 Portfolio
            </a>
        </li>

        <li>
            <a href="products.php">
                🛒 Products
            </a>
        </li>

        <li>
            <a href="newsletter.php">
                📧 Newsletter
            </a>
        </li>

        <li>
            <a href="contact-enquiries.php">
                📩 Contact Enquiries
            </a>
        </li>


        <li>
            <a href="logout.php" class="logout-link">
                🚪 Logout
            </a>
        </li>

    </ul>

</div>

<style>

body{
    margin:0;
    font-family:Arial, sans-serif;
}

/* =========================
SIDEBAR
========================= */

.sidebar{
    width:260px;
    height:100vh;
    background:#111827;
    position:fixed;
    left:0;
    top:0;
    overflow-y:auto;
    box-shadow:2px 0 10px rgba(0,0,0,0.1);
}

.sidebar-header{
    padding:25px 20px;
    border-bottom:1px solid rgba(255,255,255,0.08);
}

.sidebar-header h2{
    margin:0;
    color:white;
    font-size:22px;
}

.sidebar-menu{
    list-style:none;
    padding:0;
    margin:0;
}

.sidebar-menu li{
    border-bottom:1px solid rgba(255,255,255,0.04);
}

.sidebar-menu li a{
    display:block;
    padding:16px 20px;
    color:#e5e7eb;
    text-decoration:none;
    font-size:15px;
    transition:.3s;
}

.sidebar-menu li a:hover{
    background:#1f2937;
    padding-left:28px;
}

.logout-link{
    color:#f87171 !important;
}

/* =========================
PAGE CONTENT
========================= */

.main-content{
    margin-left:260px;
    padding:20px;
}

/* =========================
MOBILE
========================= */

@media(max-width:768px){

    .sidebar{
        width:100%;
        height:auto;
        position:relative;
    }

    .main-content{
        margin-left:0;
    }

}

</style>
