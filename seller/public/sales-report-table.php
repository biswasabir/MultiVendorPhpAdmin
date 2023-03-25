<section class="content-header">
    <h1>Sales reports</h1>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
    <hr />
</section>
<style>
    .btn {
        padding: 9px 12px;
        line-height: 0.42857143;
    }
</style>
<?php 
if (!isset($_SESSION['seller_id']) && !isset($_SESSION['seller_name'])) {
    header("location:index.php");
} else {
    $id = $_SESSION['seller_id'];
}
?>
<!-- search form -->
<section class="content">
    <!-- Main row -->
    <div class="row">
        <div class="col-md-12">

            <div class="box box-info">
                <div class="box-header with-border">
                    <form method="POST" id="filter_form" name="filter_form">
                        <div class="form-group">
                            <label for="from" class="control-label col-md-1 col-sm-3 col-xs-12">From & To Date</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="date" name="date" autocomplete="off" />
                            </div>
                            <input type="hidden" id="start_date" name="start_date">
                            <input type="hidden" id="end_date" name="end_date">
                        </div>
                    </form>
                    <?php
                     $sql = "SELECT categories FROM seller WHERE id = " . $id;
                     $db->sql($sql);
                     $res = $db->getResult();
                 
                     $sql = "SELECT id, name FROM `category` WHERE id IN(" . $res[0]['categories'] . ") ORDER BY id ASC ";
                     $db->sql($sql);
                     $categories = $db->getResult(); ?>
                    <div class="form-group col-md-3">
                        <select id='cat_id' name="cat_id" class='form-control'>
                            <option value=''>Select Category</option>
                            <?php foreach ($categories as $cat) { ?>
                                <option value='<?= $cat['id'] ?>'><?= $cat['name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="box-body">
                    <div class="table-responsive">

                        <table class="table no-margin" data-toggle="table" id="reports_list" data-show-footer="true" data-url="get-bootstrap-table-data.php?table=sales_reports" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true"  data-sort-name="id" data-sort-order="desc" data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable='true'>Order Item ID</th>
                                    <th data-field="user_id" data-sortable='true' data-visible='false'>User ID</th>
                                    <th data-field="uname" data-sortable='true'>User</th>
                                    <th data-field="product_name" data-sortable='true'>Product</th>
                                    <th data-field="mobile" data-sortable='true'>Mob.</th>
                                    <th data-field="address" data-sortable='true' data-visible="false">Address</th>
                                    <th data-field="final_total" data-sortable='true' data-footer-formatter="final_totalFormatter">Final Total(<?= $settings['currency'] ?>)</th>
                                    <th data-field="date_added" data-sortable='true' data-visible="true">Date</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
<script>


    $('#cat_id').on('change', function() {
        $('#reports_list').bootstrapTable('refresh');
    });
    $(document).ready(function() {
        $('#date').daterangepicker({
            "autoApply": true,
            "showDropdowns": true,
            "alwaysShowCalendars": true,
            "startDate": moment(),
            "endDate": moment(),
            "locale": {
                "format": "DD/MM/YYYY",
                "separator": " - "
            },
        });

        $('#date').on('apply.daterangepicker', function(ev, picker) {
            var drp = $('#date').data('daterangepicker');
            $('#start_date').val(drp.startDate.format('YYYY-MM-DD'));
            $('#end_date').val(drp.endDate.format('YYYY-MM-DD'));
        });
        $('#date').on('apply.daterangepicker', function(ev, picker) {
            var drp = $('#date').data('daterangepicker');
            $('#start_date').val(drp.startDate.format('YYYY-MM-DD'));
            $('#end_date').val(drp.endDate.format('YYYY-MM-DD'));
            $('#reports_list').bootstrapTable('refresh');
        });

    });

    function queryParams(p) {
        return {
            "start_date": $('#start_date').val(),
            "end_date": $('#end_date').val(),
            "cat_id": $('#cat_id').val(),
            // limit: p.limit,
            // sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
    function final_totalFormatter(data) {
        var field = this.field
        return '<span style="color:green;font-weight:bold;font-size:large;"><?= $settings['currency'] ?> ' + data.map(function(row) {
                return +row[field]
            })
            .reduce(function(sum, i) {
                return sum + i
            }, 0);
    }
</script>
<?php
$db->disconnect();
?>