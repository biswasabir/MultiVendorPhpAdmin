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
<?php include "header.php";
$allowed = ALLOW_MODIFICATION;
include_once('library/jwt.php');
include_once('includes/crud.php');
function generate_token()
{
    $jwt = new JWT();
    $payload = [
        'iat' => time(), /* issued at time */
        'iss' => 'eKart',
        'exp' => time() + (30 * 60), /* expires after 1 minute */
        'sub' => 'eKart Authentication'
    ];
    $token = $jwt::encode($payload, JWT_SECRET_KEY);
    return $token;
}
?>
<html>

<head>
    <title>Manage Api Keys | <?= $settings['app_name'] ?> - Dashboard</title>
    <script src="dist/js/jquery.min.js" crossorigin="anonymous"></script>
</head>

<body>
    <div class="content-wrapper">
        <section class="content-header">
            <h1>Manage Api Keys</h1>
            <ol class="breadcrumb">
                <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
            </ol>
            <hr />
        </section>
        <?php
        include_once('includes/functions.php'); ?>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-primary">
                            <div class="container-fluid">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Manage Api Keys</h3>
                                </div>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="admin_key">User APP Api Link <small>(Use this link as your API link in user app)</small></label>
                                                <input type="text" class="form-control" name="admin_key" value="<?= DOMAIN_URL . 'api-firebase/'; ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="delivery_boy_key">Delivery Boy APP Api Link <small>(Use this link as your api link in delivery boy app)</small></label>
                                                <input type="text" class="form-control" name="delivery_boy_key" value="<?= DOMAIN_URL . 'delivery-boy/api/api-v1.php'; ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Seller APP Api Link <small>(Use this link as your api link in seller app)</small></label>
                                                <input type="text" class="form-control" name="" value="<?= DOMAIN_URL . 'seller/api/api-v1.php'; ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="secret_key">Secret Key<small>(use this secret key in android application)</small></label>
                                                <input type="text" class="form-control" name="secret_key" value="<?= JWT_SECRET_KEY; ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="bearer_token">Bearer Token <small>(Use token for testing purpose in API)</small></label>
                                                <textarea name="bearer_token" id="bearer_token" class="form-control" rows="2" disabled><?= generate_token(); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <div id="result"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>

</html>
<?php include "footer.php"; ?>