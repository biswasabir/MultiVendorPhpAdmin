<?php
session_start();
// set time for session timeout
$currentTime = time() + 25200;
$expired = 3600;

// if session not set go to login page
if (!isset($_SESSION['user'])) {
    header("location:index.php");
}

// if current time is more than session timeout back to login page
if ($currentTime > $_SESSION['timeout']) {
    session_destroy();
    header("location:index.php");
}

// destroy previous session timeout and create new one
unset($_SESSION['timeout']);
$_SESSION['timeout'] = $currentTime + $expired;
?>
<?php include "header.php"; ?>
<html>

<head>
    <title>Add Product | <?= $settings['app_name'] ?> - Dashboard</title>
    <style>
        .asterik {
            font-size: 20px;
            line-height: 0px;
            vertical-align: middle;
        }

        .tox .tox-menubar {
            background-color: #e7e8e7;
            display: flex;
            flex: 0 0 auto;
            flex-shrink: 0;
            flex-wrap: wrap;
            padding: 0 4px 0 4px;
        }

        .tox .tox-notification--warn,
        .tox .tox-notification--warning {
            background-color: #fffaea;
            border-color: #ffe89d;
            color: #222f3e;
            display: none;
        }
    </style>
</head>
</body>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <?php include('public/add-product-form.php'); ?>
</div><!-- /.content-wrapper -->
</body>

</html>
<?php include "footer.php"; ?>