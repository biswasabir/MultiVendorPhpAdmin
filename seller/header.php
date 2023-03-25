<?php
if (!isset($_SESSION['seller_id']) && !isset($_SESSION['seller_name'])) {
    header("location:index.php");
}
include_once('../includes/crud.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES 'utf8'");

include('../includes/variables.php');
include_once('../includes/custom-functions.php');

$fn = new custom_functions();
$config = $fn->get_configurations();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

$settings['app_name'] = $config['app_name'];
$words = explode(" ", $settings['app_name']);
$acronym = "";
foreach ($words as $w) {
    $acronym .= $w[0];
}
$currency = $fn->get_settings('currency');
$settings['currency'] = $currency;
$logo = $fn->get_settings('logo');

?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" type="image/ico" href="<?= '../dist/img/' . $logo ?>">
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.5 -->
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../bootstrap/css/custom.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../dist/css/AdminLTE.min.css">
    <link href="../dist/css/multiple-select.css" rel="stylesheet" />

    <script src="../dist/js/v5.tinymce.min.js"></script>

    <link rel="stylesheet" href="../dist/css/print.css" type="text/css" media="print">
    <link rel="stylesheet" href="../dist/css/skins/_all-skins.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="../plugins/iCheck/flat/blue.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/switchery/0.8.2/switchery.min.css" integrity="sha256-2kJr1Z0C1y5z0jnhr/mCu46J3R6Uud+qCQHA39i1eYo=" crossorigin="anonymous" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/switchery/0.8.2/switchery.min.js" integrity="sha256-CgrKEb54KXipsoTitWV+7z/CVYrQ0ZagFB3JOvq2yjo=" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function() {
            var date = new Date();
            var currentMonth = date.getMonth() - 10;
            var currentDate = date.getDate();
            var currentYear = date.getFullYear() - 10;

            $('.datepicker').datepicker({
                minDate: new Date(currentYear, currentMonth, currentDate),
                dateFormat: 'yy-mm-dd',
            });
        });
    </script>
    <script language="javascript">
        function printpage() {
            window.print();
        }
    </script>
    <link rel="stylesheet" href="https://rawgit.com/enyo/dropzone/master/dist/dropzone.css">

    <link rel="stylesheet" href="../plugins/morris/morris.css">
    <!-- jvectormap -->
    <link rel="stylesheet" href="../plugins/jvectormap/jquery-jvectormap-1.2.2.css">
    <!-- Date Picker -->
    <link rel="stylesheet" href="../plugins/datepicker/datepicker3.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="../plugins/daterangepicker/daterangepicker-bs3.css">
    <!-- bootstrap wysihtml5 - text editor -->
    <link rel="stylesheet" href="../plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.12.1/bootstrap-table.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.12.1/extensions/filter-control/bootstrap-table-filter-control.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.4.1/jquery.fancybox.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.4.1/jquery.fancybox.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.1/css/lightbox.min.css" integrity="sha256-tBxlolRHP9uMsEFKVk+hk//ekOlXOixLKvye5W2WR5c=" crossorigin="anonymous" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.1/js/lightbox.min.js" integrity="sha256-CtKylYan+AJuoH8jrMht1+1PMhMqrKnB8K5g012WN5I=" crossorigin="anonymous"></script>

</head>

<body class="hold-transition skin-blue fixed sidebar-mini">
    <div class="wrapper">
        <header class="main-header">
            <!-- Logo -->
            <a href="home.php" class="logo">
                <!-- mini logo for sidebar mini 50x50 pixels -->
                <span class="logo-mini">
                    <h2><?= $acronym ?></h2>
                </span>
                <!-- logo for regular state and mobile devices -->
                <span class="logo-lg">
                    <h3><?= $settings['app_name'] ?></h3>
                </span>
            </a>
            <?php
            $sql_query = "SELECT * FROM seller where id=" . $_SESSION['seller_id'];

            $db->sql($sql_query);
            $result = $db->getResult();
            foreach ($result as $row) {
                $user = $row['name'];
                $email = $row['email'];
                $seller_profile = ($row['logo'] != "") ? 'upload/seller/' . $row['logo'] : 'images/avatar.png';
            }
            ?>
            <!-- Header Navbar: style can be found in header.less -->
            <nav class="navbar navbar-static-top" role="navigation">
                <!-- Sidebar toggle button-->
                <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                    <span class="sr-only">Toggle navigation</span>
                </a>
                <div class="navbar-custom-menu">

                    <ul class="nav navbar-nav">
                        <!-- User Account: style can be found in dropdown.less -->
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="<?= DOMAIN_URL . $seller_profile; ?>" class="user-image" alt="User Image">
                                <span class="hidden-xs"><?= $_SESSION['seller_name'] ?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <!-- User image -->
                                <li class="user-header">
                                    <img src="<?= DOMAIN_URL . $seller_profile; ?>" class="img-circle" alt="User Image">
                                    <p>
                                        <?= $_SESSION['seller_name'] ?>
                                        <small><?= $email; ?></small>
                                    </p>
                                </li>
                                <li class="user-footer">
                                    <div class="pull-left">
                                        <a href="edit-profile.php" class="btn btn-default btn-flat"> Edit Profile</a>
                                    </div>
                                    <div class="pull-right">
                                        <a href="logout.php" class="btn btn-default btn-flat">Log out</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>
        <!-- Left side column. contains the logo and sidebar -->
        <aside class="main-sidebar">

            <!-- sidebar: style can be found in sidebar.less -->
            <section class="sidebar">
                <!-- <?php  ?> -->
                <ul class="sidebar-menu">
                    <li class="treeview">
                        <a href="home.php">
                            <i class="fa fa-home" class="active"></i> <span>Home</span>
                        </a>
                    </li>
                    <li class="treeview">
                        <a href="orders.php">
                            <i class="fa fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="treeview">
                        <a href="categories.php">
                            <i class="fa fa-sliders"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li>
                        <a href="subcategories.php">
                            <i class="fa fa-bullseye"></i> <span>Sub Categories</span>
                        </a>
                    </li>
                    <li class="treeview">
                        <a href="#">
                            <i class="fa fa-cubes"></i>
                            <span>Products</span>
                            <i class="fa fa-angle-right pull-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="add-product.php"><i class="fa fa-plus"></i> Add Product</a></li>
                            <li><a href="products.php"><i class="fa fa-sliders"></i> Manage Products</a></li>
                            <li><a href="media.php"><i class="fa fa-file-image-o"></i> Media</a></li>

                            <li><a href="bulk-upload.php"><i class="fa fa-upload"></i> Bulk Upload</a></li>
                            <!-- <li><a href="bulk-update.php"><i class="fa fa-pencil"></i> Bulk Update</a></li> -->
                            <li><a href="products-taxes.php"><i class="fa fa-plus"></i> Taxes</a></li>
                        </ul>
                    </li>
                    <li class="">
                        <a href="pickup-locations.php">
                            <i class="fa fa-map-marker"></i> <span>Pickup Locations</span>
                        </a>
                    </li>
                    <!-- <li class="treeview">
                        <a href="media.php">
                            <i class="fa fa-file-image-o"></i>
                            <span>Media</span>
                        </a>
                    </li> -->

                    <li class="treeview">
                        <a href="customers.php">
                            <i class="fa fa-users"></i>
                            <span>Customers</span>
                        </a>
                    </li>


                    <li class="treeview">
                        <a href="#">
                            <i class="fa fa-map-marker"></i>
                            <span>Location</span>
                            <i class="fa fa-angle-right pull-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="pincodes.php"><i class="fa fa-map-pin"></i> Pincodes</a></li>
                            <li><a href="city.php"><i class="fa fa-location-arrow"></i> Cities</a></li>
                            <li><a href="areas.php"><i class="fa fa-reorder"></i> Areas </a></li>
                        </ul>
                    </li>

                    <li class="treeview">
                        <a href="seller-wallet-transactions.php">
                            <i class="fa fa-exchange"></i> <span> Wallet Transactions </span>
                        </a>
                    </li>
                    <li class="treeview">
                        <a href="withdrawal-requests.php">
                            <i class="fa fa-credit-card"></i> <span> Withdrawal Requests </span>
                        </a>
                    </li>
                    <li class="treeview">
                        <a href="#">
                            <i class="fa fa-folder-open"></i>
                            <span>Reports</span>
                            <i class="fa fa-angle-right pull-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="product-sales-report.php"><i class="fa fa-money"></i>Product Sales Report</a></li>
                            <li><a href="sales-report.php"><i class="fa fa-money"></i>Sales Report</a></li>
                            <!-- <li><a href="invoices.php"><i class="fa fa-money"></i>Invoice Report</a></li> -->
                        </ul>
                    </li>
                    <li class="treeview">
                        <a href="units.php">
                            <i class="fa fa-dot-circle-o"></i> <span> Units </span>
                        </a>
                    </li>

                </ul>
            </section>
            <!-- /.sidebar -->
        </aside>
</body>

</html>