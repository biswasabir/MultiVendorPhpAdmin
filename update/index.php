<?php
$current_version = "3.0.1";
$latest_version = "3.0.2";
$app_name = "eCart Multivendor";
include('../includes/crud.php');
$db = new Database();
$db->connect();

$class_exist = "0";
if (class_exists('ZipArchive')) {
    $db->sql("SELECT version FROM `updates` ORDER BY id desc limit 1 ");
    $res = $db->getResult();
    if ($res[0]['version'] == "v3.0.1") {
        $class_exist = "3";
    } else {
        $class_exist = "2";
    }
} else {
    $class_exist = "0";
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="WRteam - https://wrteam.in/">
    <title>Auto Updater for <?= $app_name; ?> by WRteam</title>
    <link rel="icon" type="image/png" href="logo.png" />
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <div class="site-wrapper">
        <div class="site-wrapper-inner">
            <div class="cover-container">
                <div class="masthead clearfix">
                    <div class="inner">
                        <h3 class="masthead-brand"><?= $app_name; ?> - WRteam</h3>
                        <nav>
                            <img src='logo.png' width='80' style='float:right;' />
                        </nav>
                    </div>
                </div>
                <div class="inner cover">
                    <h1 class="cover-heading"><?= $app_name; ?> Updater</h1>
                    <p class="lead">This is an Automatic Updater which helps you update your PHP Admin Panel Code From v<?= $current_version; ?> to v<?= $latest_version; ?> </p>
                    <p>
                        <b>Please note</b> : Use this script only when, you have <b><?= $app_name; ?> v<?= $current_version; ?> App & Admin Panel</b> installed on your server. <span style='color: #d2120e;'>Ignore this if you are starting it freshly from scratch.</span>
                        <br /><br />Make sure you update system only once using this auto updater. Once update work is done delete the <b>/update</b> folder from your server directory
                    </p>
                    <br>
                    <?php
                    if ($class_exist == "3") { ?>
                        <p>Your server has ZIP module(extention). You can go ahead.</p>
                    <?php } else if ($class_exist == "0") { ?>
                        <p style='color: #d2120e;'>Your server not have enabled ZIP module(extention). Please enable it and then try later.</p>
                    <?php } else if ($class_exist == "2") { ?>
                        <p style='color: #d2120e;'>Your server has lower version to update the multivendor. Please update it till v<?= $current_version ?>.</p>
                    <?php } ?>
                    <p id='result' style='display:none;'>Do not close window or refresh the page while system is being updated.</p>
                    <p class="lead">
                        <a href="#" id='update_btn' class="btn btn-lg btn-success">Update Now to v<?= $latest_version; ?></a>
                    </p>
                    <p>
                        <a href="https://codecanyon.net/item/city-ecommerce-app/22015911" target='_blank' class="btn btn-lg btn-default">See What's New Here</a>
                    </p>
                </div>
                <div class="mastfoot">
                    <div class="inner">
                        <p>&copy <?= date('Y'); ?> <?= $app_name; ?> - Developed by <a href="https://wrteam.in/" target='_blank'>WRteam</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap core JavaScript ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script>
    <!-- Bootstrap -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js"></script>

    <script>
        $(document).on('click', '#update_btn', function(e) {
            var class_exists = '<?= $class_exist; ?>';
            var current_version = '<?= $current_version; ?>';
            if (class_exists == "2") {
                 alert('Your server has lower version to update the multivendor. Please update it till '+current_version);
                 return false;
            }
            if (class_exists == "3") {
                if (confirm("Are you sure want to upgrade the system from v<?= $current_version ?> to v<?= $latest_version; ?> ? Make sure you do it only once! ")) {
                    e.preventDefault();
                    $.ajax({
                        url: 'updater.php',
                        type: "POST",
                        data: $(this).serialize(),
                        beforeSend: function() {
                            $('#result').fadeIn(100);
                            $('#update_btn').html('Please Wait... System is being updated');
                            $('#update_btn').prop('disabled', true);
                        },
                        success: function(result) {
                            $('#result').html(result);
                            $('#result').show();
                            $('#update_btn').removeClass('btn-warning');
                            $('#update_btn').addClass('btn-success');
                            $('#update_btn').html('Congrats! Your system is now up-to-date');
                        }
                    });
                }
            } else {
                $('#update_btn').prop('disabled', true);
                confirm("Your server not have enabled ZIP module(extention). Please enable it and then try later.")
            }
        });
    </script>
</body>

</html>