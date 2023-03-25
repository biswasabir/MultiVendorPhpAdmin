<?php session_start();

include_once('includes/custom-functions.php');
include_once('includes/functions.php');
include_once('library/shiprocket.php');

$function = new custom_functions;

// set time for session timeout
$currentTime = time() + 25200;
$expired = 3600;
$shiprocket = new Shiprocket;
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
$function = new custom_functions;
$permissions = $function->get_permissions($_SESSION['id']);

$shipping_type = ($function->get_settings('local_shipping') == 0) ? 'standard' : 'local';
include "header.php";
$low_stock_limit = isset($config['low-stock-limit']) && (!empty($config['low-stock-limit'])) ? $config['low-stock-limit'] : 0;



?>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= $settings['app_name'] ?> - Dashboard</title>
</head>


<body>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <h1>Home</h1>
            <ol class="breadcrumb">
                <li>
                    <a href="home.php"> <i class="fa fa-home"></i> Home</a>
                </li>
            </ol>

        </section>
        <section class="content">
            <div class="row ">
                <div class="col-lg-3 col-xs-6 standard_shipping">
                    <div class="small-box bg-aqua">
                        <div class="inner">
                            <h3><?= $function->rows_count('orders', "id"); ?></h3>
                            <p>Orders</p>
                        </div>
                        <div class="icon"><i class="fa fa-shopping-cart"></i></div>
                        <a href="orders.php" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-xs-6 standard_shipping">
                    <div class="small-box bg-yellow">
                        <div class="inner">
                            <h3><?= $function->rows_count('products', "id"); ?></h3>
                            <p>Products</p>
                        </div>
                        <div class="icon"><i class="fa fa-cubes"></i></div>
                        <a href="products.php" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-xs-6 standard_shipping">
                    <div class="small-box bg-red">
                        <div class="inner">
                            <h3><?= $function->rows_count('users', "id"); ?></h3>
                            <p>Customers</p>
                        </div>
                        <div class="icon"><i class="fa fa-users"></i></div>
                        <a href="customers.php" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-xs-6 standard_shipping">
                    <div class="small-box bg-green">
                        <div class="inner">
                            <h3><?= $function->rows_count('seller', "id"); ?></h3>
                            <p>Sellers</p>
                        </div>
                        <div class="icon"><i class="fa fa-users"></i></div>
                        <a href="sellers.php" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>


                <div class="col-lg-4 col-xs-6 standard_shipping shiprocket d-none">
                    <div class="small-box bg-purple">
                        <div class="inner">
                            <h3>
                                <?= $shiprocket->get_wallet_balance()['data']['balance_amount'] ?>
                            </h3>
                            <p>Shiprocket Wallet Balance</p>
                        </div>
                        <div class="icon"><i class="fa fa-money"></i></div>
                        <a href="https://app.shiprocket.in/dashboard" class="small-box-footer" target="_blank"> More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6">
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h4><i class="icon fa fa-info"></i> <?= $function->sold_out_count(); ?> Product(s) sold out!</h4>
                        <a href="sold-out-products.php" style="text-decoration:none !important;" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-6 col-xs-6">
                    <div class="alert alert-info alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h4><i class="icon fa fa-info"></i><?= $function->low_stock_count($low_stock_limit); ?> Product(s) in low stock! (Low stock limt <?= $low_stock_limit ?>)</h4>
                        <a href="low-stock-products.php" style="text-decoration:none !important;" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="box box-success">
                        <?php $year = date("Y");
                        $curdate = date('Y-m-d');
                        $sql = "SELECT SUM(final_total) AS total_sale,DATE(date_added) AS order_date FROM orders WHERE YEAR(date_added) = '$year' AND DATE(date_added)<='$curdate' GROUP BY DATE(date_added) ORDER BY DATE(date_added) DESC  LIMIT 0,7";
                        $db->sql($sql);
                        $result_order = $db->getResult(); ?>
                        <div class="tile-stats" style="padding:10px;">
                            <div id="earning_chart" style="width:100%;height:350px;"></div>
                        </div>
                    </div>

                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="box box-danger">
                        <?php
                        $sql = "SELECT `name`,(SELECT count(id) from `products` p WHERE p.category_id = c.id ) as `product_count` FROM `category` c";
                        $db->sql($sql);
                        $result_products = $db->getResult(); ?>
                        <div class="tile-stats" style="padding:10px;">
                            <div id="piechart" style="width:100%;height:350px;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title">Top Sellers <small>( Month: <?= date("M"); ?>)</small></h3>
                            <div class="box-tools pull-right">
                                <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                            </div>
                        </div>
                        <div class="box-body">

                            <div class="table-responsive">
                                <table class="table no-margin" id='top_seller_table' data-toggle="table" data-url="api-firebase/get-bootstrap-table-data.php?table=top_sellers" data-page-list="[5,10]" data-page-size="5" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-sort-name="total_revenue" data-sort-order="desc" data-toolbar="#toolbar" data-query-params="queryParams_top_seller">
                                    <thead>
                                        <tr>
                                            <th data-field="id" data-sortable='true'>Rank</th>
                                            <th data-field="seller_name" data-sortable='true' data-visible="true">Seller</th>
                                            <th data-field="store_name" data-sortable='true' data-visible="true">Store Name</th>
                                            <th data-field="total_revenue" data-sortable='true'>Total Revenue(<?= $settings['currency'] ?>)</th>
                                            <th data-field="operate">Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-md-6">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Top Categories <small> ( Month: <?= date("M"); ?>) </small></h3>
                            <div class="box-tools pull-right">
                                <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                            </div>
                        </div>
                        <div class="box-body">

                            <div class="table-responsive">
                                <table class="table no-margin" id='top_seller_table' data-toggle="table" data-url="api-firebase/get-bootstrap-table-data.php?table=top_categories" data-page-list="[5,10]" data-page-size="5" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-sort-name="total_revenues" data-sort-order="desc" data-toolbar="#toolbar" data-query-params="queryParams_top_cat">
                                    <thead>
                                        <tr>
                                            <th data-field="id" data-sortable='true'>Rank</th>
                                            <th data-field="cat_name" data-sortable='true' data-visible="true">Category</th>
                                            <th data-field="p_name" data-sortable='true' data-visible="true">Product Name</th>
                                            <th data-field="total_revenues" data-sortable='true'>Total Revenue(<?= $settings['currency'] ?>)</th>
                                            <th data-field="operate">Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>



            <?php if ($permissions['orders']['read'] == 1) { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-info">
                            <div class="box-header with-border">
                                <h3 class="box-title">Latest Orders</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                                <form method="POST" id="filter_form" name="filter_form">

                                    <div class="form-group pull-left">
                                        <select id="filter_order" name="filter_order" placeholder="Select Status" required class="form-control" style="width: 300px;">
                                            <option value="">All Orders</option>
                                            <option value='awaiting_payment'>Awaiting Payment</option>
                                            <option value='received'>Received</option>
                                            <option value='processed'>Processed</option>
                                            <option value='shipped'>Shipped</option>
                                            <option value='delivered'>Delivered</option>
                                            <option value='cancelled'>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="form-group pull-left ml-5">
                                        <?php
                                        $sql = "SELECT id,name FROM seller ORDER BY id + 0 ASC";
                                        $db->sql($sql);
                                        $sellers = $db->getResult();
                                        ?>
                                        <select id='seller_id' name="seller_id" class='form-control'>
                                            <option value=''>Select Seller</option>
                                            <?php foreach ($sellers as $row) { ?>
                                                <option value='<?= $row['id'] ?>'><?= $row['name'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="box-body">

                                <div class="table-responsive">
                                    <table class="table no-margin" id='orders_table' data-toggle="table" data-url="api-firebase/get-bootstrap-table-data.php?table=orders" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-toolbar="#toolbar" data-show-export="true" data-export-types='["txt","excel"]' data-export-options='{"fileName": "orders-list-<?= date('d-m-Y') ?>","ignoreColumn": ["operate"] }' data-query-params="queryParams">
                                        <thead>
                                            <tr>
                                                <th data-field="id" data-sortable='true'>O.ID</th>
                                                <th data-field="user_id" data-sortable='true' data-visible="false">User ID</th>
                                                <th data-field="qty" data-sortable='true' data-visible="false">Qty</th>
                                                <th data-field="name" data-sortable='true'>U.Name</th>
                                                <th data-field="mobile" data-sortable='true' data-visible="true">Mob.</th>
                                                <th data-field="order_note" data-sortable='true' data-visible="false">Order Note</th>
                                                <th data-field="items" data-sortable='true' data-visible="false">Items</th>
                                                <th data-field="total" data-sortable='true' data-visible="true">Total(<?= $settings['currency'] ?>)</th>
                                                <th data-field="delivery_charge" data-sortable='true'>D.Chrg</th>
                                                <th data-field="tax" data-sortable='false'>Tax <?= $settings['currency'] ?>(%)</th>
                                                <th data-field="discount" data-sortable='true' data-visible="true">Disc.<?= $settings['currency'] ?>(%)</th>
                                                <th data-field="promo_code" data-sortable='true' data-visible="false">Promo Code</th>
                                                <th data-field="promo_discount" data-sortable='true' data-visible="true">Promo Disc.(<?= $settings['currency'] ?>)</th>
                                                <th data-field="wallet_balance" data-sortable='true' data-visible="true">Wallet Used(<?= $settings['currency'] ?>)</th>
                                                <th data-field="final_total" data-sortable='true'>F.Total(<?= $settings['currency'] ?>)</th>
                                                <th data-field="payment_method" data-sortable='true' data-visible="true">P.Method</th>
                                                <th data-field="address" data-sortable='true' data-visible="false">Address</th>
                                                <th data-field="delivery_time" data-sortable='true' data-visible='true'>D.Time</th>
                                                <!-- <th data-field="status" data-sortable='true' data-visible='false'>Status</th> -->
                                                <!-- <th data-field="active_status" data-sortable='true' data-visible='true'>A.Status</th> -->
                                                <th data-field="date_added" data-sortable='true' data-visible="false">O.Date</th>
                                                <th data-field="operate">Action</th>


                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                            <div class="box-footer clearfix">
                                <a href="orders.php" class="btn btn-sm btn-default btn-flat pull-right">View All Orders</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="alert alert-danger">You have no permission to view orders.</div>
            <?php } ?>
        </section>
    </div>
    <script>
        $('#filter_order').on('change', function() {
            $('#orders_table').bootstrapTable('refresh');
        });
        $('#seller_id').on('change', function() {
            $('#orders_table').bootstrapTable('refresh');
        });
    </script>
    <script>
        function queryParams(p) {
            return {
                "filter_order": $('#filter_order').val(),
                "seller_id": $('#seller_id').val(),
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }

        function queryParams_top_seller(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset
            };
        }

        function queryParams_top_cat(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset
            };
        }
    </script>
    <?php include "footer.php"; ?>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
        google.charts.load('current', {
            'packages': ['corechart']
        });
        google.charts.setOnLoadCallback(drawPieChart);

        function drawPieChart() {

            var data1 = google.visualization.arrayToDataTable([
                ['Product', 'Count'],
                <?php
                foreach ($result_products as $row) {
                    echo "['" . $db->escapeString($row['name']) . "'," . $row['product_count'] . "],";
                }
                ?>
            ]);

            var options1 = {
                title: 'Product Category Count',
                is3D: true
            };

            var chart1 = new google.visualization.PieChart(document.getElementById('piechart'));

            chart1.draw(data1, options1);
        }
    </script>

    <script>
        google.charts.load('current', {
            'packages': ['bar']
        });
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Date', 'Total Sale In <?= $settings['currency'] ?>'],
                <?php foreach ($result_order as $row) {
                    $date = date('d-M', strtotime($row['order_date']));
                    echo "['" . $date . "'," . $row['total_sale'] . "],";
                } ?>
            ]);
            var options = {
                chart: {
                    title: 'Weekly Sales',
                    subtitle: 'Total Sale In Last Week (Month: <?php echo date("M"); ?>)',
                }
            };
            var chart = new google.charts.Bar(document.getElementById('earning_chart'));
            chart.draw(data, google.charts.Bar.convertOptions(options));
        }

        $(document).ready(function() {
            var shipping_type = "<?= $shipping_type ?>";
            if (shipping_type == "standard") {
                $('.standard_shipping').removeClass('col-lg-3')
                $('.standard_shipping').addClass('col-lg-2')
                $('.shiprocket').removeClass('d-none')
                $('.shiprocket').removeClass('col-lg-2')
            }
        })
    </script>
</body>

</html>