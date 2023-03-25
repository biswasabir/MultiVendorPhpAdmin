<section class="content-header">
    <h1>Sales Reports</h1>
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

<!-- search form -->
<section class="content">
    <!-- Main row -->
    <?php if ($permissions['reports']['read'] == 1) { ?>
        <div class="row">
            <div class="col-md-12">

                <div class="box box-info">
                    <div class="box-header with-border">
                        <form method="POST" id="filter_form" name="filter_form">
                            <div class="form-group">
                                <label for="from" class="control-label col-md-1 col-sm-3 col-xs-12">From & To Date</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="date" name="date" autocomplete="off" />
                                </div>
                                <input type="hidden" id="start_date" name="start_date">
                                <input type="hidden" id="end_date" name="end_date">
                            </div>
                        </form>
                        <?php
                        $Query = "select id,name from seller where status=1";
                        $db->sql($Query);
                        $sellers = $db->getResult(); ?>
                        <div class="form-group col-md-3">
                            <select id='seller_id' name="seller_id" class='form-control'>
                                <option value=''>Select Seller</option>
                                <?php foreach ($sellers as $seller) { ?>
                                    <option value='<?= $seller['id'] ?>'><?= $seller['name'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php
                        $Query = "select id,name from category";
                        $db->sql($Query);
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

                            <table class="table no-margin" data-toggle="table" id="reports_list" data-url="api-firebase/get-bootstrap-table-data.php?table=sales_reports" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-footer="true" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-query-params="queryParams" data-show-export="true" data-export-types='["txt","excel"]' data-export-options='{"fileName": "sales-report-list-<?= date('d-m-Y') ?>","ignoreColumn": ["operate"] }'>
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable='true'>Order Item ID</th>
                                        <th data-field="user_id" data-sortable='true' data-visible='false'>User ID</th>
                                        <th data-field="uname" data-sortable='true'>User</th>
                                        <th data-field="product_name" data-sortable='true'>Product</th>
                                        <th data-field="mobile" data-sortable='true'>Mob.</th>
                                        <th data-field="address" data-sortable='true' data-visible="false">Address</th>
                                        <th data-field="final_total" data-sortable='true' data-footer-formatter="final_totalFormatter">Total(<?= $settings['currency'] ?>)</th>
                                        <th data-field="date_added" data-sortable='true' data-visible="true">Date</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="alert alert-danger">You have no permission to view sales reports.</div>
    <?php } ?>
</section>
<!-- /.content -->
<script>
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
    $('#seller_id').on('change', function() {
        $('#reports_list').bootstrapTable('refresh');
    });
    $('#cat_id').on('change', function() {
        $('#reports_list').bootstrapTable('refresh');
    });

    function queryParams(p) {
        return {
            "start_date": $('#start_date').val(),
            "end_date": $('#end_date').val(),
            limit: p.limit,
            sort: p.sort,
            "seller_id": $('#seller_id').val(),
            "cat_id": $('#cat_id').val(),
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