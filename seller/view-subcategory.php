<?php

session_start();

// set time for session timeout
$currentTime = time() + 25200;
$expired = 3600;

// if session not set go to login page
if (!isset($_SESSION['seller_id']) && !isset($_SESSION['seller_name'])) {
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
    <title>Sub Category Products | <?= $settings['app_name'] ?> - Dashboard</title>
</head>
<?php
include_once('../includes/custom-functions.php');
$fn = new custom_functions;

if (isset($_GET['id'])) {
    $ID = $db->escapeString($fn->xss_clean($_GET['id']));
} else {
    $ID = "";
}
// get image file from table


$db->select('subcategory', '*', null, 'category_id=' . $ID);
$res = $db->getResult();
?>
<?php
if ($db->numRows($result) == 0) { ?>
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                No Subcategory Available
                <small><a href='categories.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Categories</a></small>
            </h1>
        </section>
    </div>
<?php } else {
?>
    <div class="content-wrapper">
        <section class="content">
            <!-- Main row -->
            <div class="row">
                <!-- Left col -->
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header">
                            <?php
                            $db->select('category', 'name', null, 'id=' . $ID);
                            $category_name = $db->getResult();
                            // echo $sql;
                            ?>
                            <h3 class="box-title">Category : <?php echo $category_name[0]['name']; ?><small><a href='categories.php'> <i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Categories</a></small></h3>
                            <div class="box-tools">
                            </div>
                        </div>

                        <div class="box-body table-responsive">
                            <table id='products_table' class="table table-hover" data-toggle="table" data-url="get-bootstrap-table-data.php?table=subcategory" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-filter-control="true" data-query-params="queryParams" data-sort-name="id" data-sort-order="desc" data-show-export="true" data-export-types='["txt","excel"]' data-export-options='{
                            "fileName": "products-list-<?= date('d-m-Y') ?>",
                            "ignoreColumn": ["operate"] 
                        }'>
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="name" data-sortable="true">Name</th>
                                        <th data-field="image">Image</th>
                                        <th data-field="operate" data-events="actionEvents">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <!-- /.box -->
                </div>
                <!-- right col (We are only adding the ID to make the widgets sortable)-->
            </div>
            <!-- /.row (main row) -->
        </section>

        <!-- /.content -->
    </div>
<?php } ?>
<?php $db->disconnect(); ?>
<?php include 'footer.php'; ?>
<script>
    var id = '<?= $ID; ?>';

    function queryParams(p) {
        return {
            "category_id": id,
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>