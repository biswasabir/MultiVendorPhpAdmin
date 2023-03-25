<?php
include_once('../includes/functions.php');
?>
<section class="content-header">
    <h1>Areas /<small><a href="home.php"><i class="fa fa-home"></i> Home</a></small></h1>
</section>

<!-- Main content -->
<section class="content">
    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                </div>
                <div class="box-header">
                    <h3 class="box-title">Areas</h3>
                </div>
                <?php $db->sql("SET NAMES 'utf8'");
                $sql = "SELECT * FROM pincodes ORDER BY id + 0 ASC";
                $db->sql($sql);
                $pincodes = $db->getResult();
                ?>

                <div class="box-body table-responsive">
                    <div class="form-group">
                        <select id="filter_area" name="filter_area" required class="form-control" style="width: 300px;">
                            <option value="">Select Pincode</option>
                            <?php foreach ($pincodes as $row) { ?>
                                <option value='<?= $row['id'] ?>'><?= $row['pincode'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <table class="table table-hover" data-toggle="table" id="areas_list" data-url="get-bootstrap-table-data.php?table=area" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-query-params="queryParams_1">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-visible="false">ID</th>
                                <th data-field="city_id" data-visible="false" data-sortable="true">City ID</th>
                                <th data-field="pincode_id" data-sortable="true" data-visible="false">Pincode Id</th>
                                <th data-field="pincode" data-sortable="true">Pincode</th>
                                <th data-field="city_name" data-sortable="true">City Name</th>
                                <th data-field="name" data-sortable="true">Name</th>
                                <th data-field="delivery_charges" data-sortable="true">Delivery Charges</th>
                                <th data-field="minimum_free_delivery_order_amount" data-sortable="true">Minimum Free Delivery Order Amount</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        <div class="separator"> </div>
    </div>
</section>
<script>
    $('#filter_area').on('change', function() {
        $('#areas_list').bootstrapTable('refresh');

    });

    function queryParams_1(p) {
        return {
            "filter_area": $('#filter_area').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>