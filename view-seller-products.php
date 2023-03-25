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
    <title>Seller Products | <?= $settings['app_name'] ?> - Dashboard</title>
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
                Products /
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
                                <table id='products_table' class="table table-hover" data-toggle="table" data-url="api-firebase/get-bootstrap-table-data.php?table=products" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-filter-control="true" data-query-params="queryParams" data-sort-name="id" data-sort-order="desc" data-show-export="true" data-export-types='["txt","excel"]' data-export-options='{
                            "fileName": "products-list-<?= date('d-m-Y') ?>",
                            "ignoreColumn": ["operate"] 
                        }'>
                                    <thead>
                                        <tr>
                                            <th data-field="id" data-sortable="true">ID</th>
                                            <th data-field="product_id" data-sortable="true">Product ID</th>
                                            <th data-field="tax_id" data-sortable="true">Tax ID</th>
                                            <th data-field="seller_id" data-sortable="true" data-visible="false">Seller ID</th>
                                            <th data-field="seller_name" data-sortable="true">Seller Name</th>
                                            <th data-field="name" data-sortable="true">Name</th>
                                            <th data-field="image">Image</th>
                                            <th data-field="price" data-sortable="true">Price</th>
                                            <th data-field="discounted_price" data-sortable="true">D.Price</th>
                                            <th data-field="measurement" data-sortable="true">Measurement</th>
                                            <th data-field="stock" data-sortable="true">Stock</th>
                                            <th data-field="serve_for" data-sortable="true">Availability</th>
                                            <th data-field="indicator" data-sortable="true">Indicator</th>
                                            <th data-field="is_approved" data-sortable="true">Is Approved?</th>
                                            <th data-field="description" data-sortable="true" data-visible="false">Description</th>
                                            <th data-field="manufacturer" data-sortable="true" data-visible="false">Manufacturer</th>
                                            <th data-field="made_in" data-sortable="true" data-visible="false">Made In</th>
                                            <th data-field="return_status" data-sortable="true">Return</th>
                                            <th data-field="cancelable_status" data-sortable="true">Cancellation</th>
                                            <th data-field="till_status" data-sortable="true">Till Status</th>
                                            <th data-field="type" data-sortable="true" data-visible="false">Delivrable Type</th>
                                            <th data-field="pincodes" data-sortable="true" data-visible="false">Pincode Ids</th>
                                            <th data-field="return_days" data-sortable="true" data-visible="false">Return Days</th>
                                            <th data-field="operate" data-events="actionEvents">Action</th>
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
var seller_id = '<?=$ID; ?>';
    function queryParams(p) {
        return {
            "seller_id": seller_id,
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>
<script>
 window.actionEvents = {
        'click .set-product-deactive': function(e, value, rows, index) {
            var p_id = $(this).data("id");
            $.ajax({
                url: 'public/db-operation.php',
                type: "get",
                data: 'id=' + p_id + '&product_status=1&type=deactive',
                success: function(result) {
                    if (result == 1)
                        $('#products_table').bootstrapTable('refresh');
                    else
                        alert('Error! Product could not be deactivated.');
                }
            });

        },
        'click .set-product-active': function(e, value, rows, index) {
            var p_id = $(this).data("id");
            $.ajax({
                url: 'public/db-operation.php',
                type: "get",
                data: 'id=' + p_id + '&product_status=1&type=active',
                success: function(result) {
                    if (result == 1)
                        $('#products_table').bootstrapTable('refresh');
                    else
                        alert('Error! Product could not be deactivated.');
                }
            });
        }


    };
</script>

</html>
<?php include "footer.php"; ?>