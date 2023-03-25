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
include 'header.php'; ?>

<head>
    <title>Seller Orders | <?= $settings['app_name'] ?> - Dashboard</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
    <?php
    $ID = (isset($_GET['id'])) ? $db->escapeString($fn->xss_clean($_GET['id'])) : "";
    ?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Orders /
                <small><a href="home.php"><i class="fa fa-home"></i> Home</a></small>
            </h1>
        </section>
        <?php
        if ($permissions['products']['read'] == 1) {
        ?>
            <!-- Main content -->
            <section class="content">
                <!-- Main row -->
                <div class="row">
                    <!-- Left col -->
                    <div class="col-xs-12">
                        <div class="box">
                            <!-- /.box-header -->
                            <div class="box-body table-responsive">
                                <div class="form-group col-md-4">
                                    <select id="filter_order" name="filter_order" placeholder="Select Status" required class="form-control ">
                                        <option value="">All Orders</option>
                                        <option value='awaiting_payment'>Awaiting Payment</option>
                                        <option value='received'>Received</option>
                                        <option value='processed'>Processed</option>
                                        <option value='shipped'>Shipped</option>
                                        <option value='delivered'>Delivered</option>
                                        <option value='cancelled'>Cancelled</option>
                                        <option value='returned'>Returned</option>
                                    </select>

                                </div>
                                <table class="table no-margin" data-toggle="table" id="orderitem_list" data-url="api-firebase/get-bootstrap-table-data.php?table=order_items" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-columns="true" data-show-refresh="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="oi.id" data-sort-order="desc" data-query-params="queryParams">
                                    <thead>
                                        <tr>
                                            <th data-field="order_id" data-sortable='true'>O.ID</th>
                                            <th data-field="id" data-sortable='true'>O.Item ID</th>
                                            <th data-field="user_id" data-sortable='true' data-visible="false">User ID</th>
                                            <th data-field="qty" data-sortable='true' data-visible="false">Qty</th>
                                            <th data-field="name" data-sortable='true'>U.Name</th>
                                            <th data-field="product_variant_id" data-sortable='true' data-visible="false">Product Variant Id</th>
                                            <th data-field="seller_name">S.Name</th>
                                            <th data-field="product_name">Product </th>
                                            <th data-field="seller_id" data-sortable='true' data-visible="false">S.Id</th>
                                            <th data-field="mobile" data-sortable='true' data-visible="true" data-footer-formatter="totalFormatter">Mob.</th>
                                            <th data-field="order_note" data-sortable='false' data-visible="false">Order Note</th>
                                            <th data-field="total" data-sortable='true' data-visible="true" data-footer-formatter="priceFormatter">Total(<?= $settings['currency'] ?>)</th>
                                            <!-- <th data-field="delivery_charge" data-sortable='true' data-footer-formatter="delivery_chargeFormatter">D.Chrg</th> -->
                                            <th data-field="tax" data-sortable='false'>Tax <?= $settings['currency'] ?>(%)</th>
                                            <!-- <th data-field="discount" data-sortable='true' data-visible="true">Disc.<?= $settings['currency'] ?>(%)</th> -->
                                            <!-- <th data-field="promo_code" data-sortable='true' data-visible="false">Promo Code</th> -->
                                            <!-- <th data-field="promo_discount" data-sortable='true' data-visible="true">Promo Disc.(<?= $settings['currency'] ?>)</th> -->
                                            <!-- <th data-field="wallet_balance" data-sortable='true' data-visible="true">Wallet Used(<?= $settings['currency'] ?>)</th> -->
                                            <!-- <th data-field="final_total" data-sortable='true' data-footer-formatter="final_totalFormatter">F.Total(<?= $settings['currency'] ?>)</th> -->
                                            <th data-field="deliver_by" data-sortable='true' data-visible='false'>Deliver By</th>
                                            <th data-field="payment_method" data-sortable='true' data-visible="true">P.Method</th>
                                            <th data-field="address" data-sortable='true' data-visible="false">Address</th>
                                            <th data-field="delivery_time" data-sortable='true' data-visible='true'>D.Time</th>
                                            <th data-field="status" data-sortable='true' data-visible='false'>Status</th>
                                            <th data-field="active_status" data-sortable='true' data-visible='true'>A.Status</th>
                                            <th data-field="date_added" data-sortable='true' data-visible="false">O.Date</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <!-- /.box-body -->
                        </div>
                        <!-- /.box -->
                    </div>
                    <div class="separator"> </div>
                </div>
                <!-- /.row (main row) -->
            </section>
        <?php } else { ?>
            <div class="alert alert-danger topmargin-sm" style="margin-top: 20px;">You have no permission to view products.</div>
        <?php } ?>


    </div><!-- /.content-wrapper -->
</body>
<script>
    $('#filter_order').on('change', function() {
        $('#orderitem_list').bootstrapTable('refresh');
    });

    var seller_id = '<?= $ID; ?>';

    function queryParams(p) {
        return {
            "filter_order": $('#filter_order').val(),
            "seller_id": seller_id,
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>


</html>
<?php include "footer.php"; ?>