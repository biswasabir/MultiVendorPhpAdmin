<?php
if ($permissions['orders']['read'] == 1) {
?>
    <section class="content-header">
        <h1>Order List</h1>
        <ol class="breadcrumb">
            <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
        </ol>
        <hr />
    </section>
    <style>
        .uppercase {
            text-transform: uppercase;
        }

        .btn {
            padding: 9px 12px;
            line-height: 0.42857143;
        }
    </style>

    <!-- search form -->
    <section class="content">
        <!-- Main row -->

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
                            <div class="form-group">
                                <label for="from" class="control-label col-md-1 col-sm-3 col-xs-12">From & To Date</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="date" name="date" autocomplete="off" />
                                </div>
                                <input type="hidden" id="start_date" name="start_date">
                                <input type="hidden" id="end_date" name="end_date">
                            </div>
                            <div class="form-group col-md-4" id="filter_order1">
                                <select id="filter_order" name="filter_order" placeholder="Select Status" required class="form-control">
                                    <option value="">Select status</option>
                                    <option value='awaiting_payment'>Awaiting Payment</option>
                                    <option value='received'>Received</option>
                                    <option value='processed'>Processed</option>
                                    <option value='shipped'>Shipped</option>
                                    <option value='delivered'>Delivered</option>
                                    <option value='cancelled'>Cancelled</option>
                                    <option value='returned'>Returned</option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
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
                            <div class="form-group col-md-2">
                                <select id="shipping_type" name="shipping_type" placeholder="Select Status" required class="form-control">
                                    <option value="2">All</option>
                                    <option value='0'>Local shipping</option>
                                    <option value='1'>Standard shipping</option>
                                </select>
                            </div>

                            <input type="hidden" id="filter_order_status" name="filter_order_status">
                            <input type="hidden" id="shipping_type_status" name="shipping_type_status">
                        </form>
                    </div>
                    <div class="box-body">
                        <!-- <div class="container"> -->
                        <ul class="nav nav-tabs">
                            <li class="active" id="orders_table" name="orders_table"><a data-toggle="tab" href="#order">Orders</a></li>
                            <li id="orders_item_table" name="orders_item_table"><a data-toggle="tab" href="#order_items">Order Items</a></li>
                        </ul>
                        <div class="tab-content">
                            <div id="order" class="tab-pane fade in active">
                                <div class="table-responsive">
                                    <table class="table no-margin" data-toggle="table" id="order_list" data-url="api-firebase/get-bootstrap-table-data.php?table=orders" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-columns="true" data-show-refresh="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-query-params="queryParams_1" data-show-footer="true" data-footer-style="footerStyle" data-show-export="true" data-export-types='["txt","excel"]' data-export-options='{"fileName": "orders-list-<?= date('d-m-Y') ?>","ignoreColumn": ["operate"] }'>
                                        <thead>
                                            <tr>
                                                <th data-field="id" data-sortable='true'>O.ID</th>
                                                <th data-field="user_id" data-sortable='true' data-visible="false">User ID</th>
                                                <th data-field="qty" data-sortable='false' data-visible="false">Qty</th>
                                                <th data-field="name">U.Name</th>
                                                <th data-field="seller_name">Sellers</th>
                                                <th data-field="mobile" data-visible="true" data-footer-formatter="totalFormatter">Mob.</th>
                                                <th data-field="order_note" data-sortable='false' data-visible="false">Order Note</th>
                                                <th data-field="items" data-sortable='false' data-visible="false">Items</th>
                                                <th data-field="total" data-sortable='true' data-visible="true" data-footer-formatter="priceFormatter">Total(<?= $settings['currency'] ?>)</th>
                                                <th data-field="delivery_charge" data-sortable='true' data-footer-formatter="delivery_chargeFormatter">D.Chrg</th>
                                                <th data-field="tax" data-sortable='false'>Tax <?= $settings['currency'] ?>(%)</th>
                                                <th data-field="discount" data-sortable='true' data-visible="true">Disc.<?= $settings['currency'] ?>(%)</th>
                                                <th data-field="promo_code" data-sortable='true' data-visible="false">Promo Code</th>
                                                <th data-field="promo_discount" data-sortable='true' data-visible="true">Promo Disc.(<?= $settings['currency'] ?>)</th>
                                                <th data-field="wallet_balance" data-sortable='true' data-visible="true">Wallet Used(<?= $settings['currency'] ?>)</th>
                                                <th data-field="final_total" data-sortable='true' data-footer-formatter="final_totalFormatter">F.Total(<?= $settings['currency'] ?>)</th>
                                                <th data-field="payment_method" data-sortable='true' data-visible="true">P.Method</th>
                                                <th data-field="address" data-sortable='true' data-visible="false">Address</th>
                                                <th data-field="area_id" data-sortable='true' data-visible="false">Area Id</th>
                                                <th data-field="delivery_time" data-sortable='true' data-visible='true'>D.Time</th>
                                                <th data-field="date_added" data-sortable='true' data-visible="false">O.Date</th>
                                                <th data-field="operate">Action</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                            <div id="order_items" class="tab-pane fade">
                                <div class="table-responsive">
                                    <table class="table no-margin" data-toggle="table" id="orderitem_list" data-url="api-firebase/get-bootstrap-table-data.php?table=order_items" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-columns="true" data-show-refresh="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="oi.id" data-sort-order="desc" data-query-params="queryParams" data-show-footer="true" data-footer-style="footerStyle">
                                        <thead>
                                            <tr>
                                                <th data-field="order_id" data-sortable='true'>O.ID</th>
                                                <th data-field="id" data-sortable='true'>O.Item ID</th>
                                                <th data-field="user_id" data-sortable='true' data-visible="false">User ID</th>
                                                <th data-field="is_credited" data-sortable='true'>Commission</th>
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
                                                <th data-field="operate">Action</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>

                        </div>
                        <!-- </div> -->

                    </div>
                </div>
            </div>
        </div>
    </section>
<?php } else { ?>
    <div class="alert alert-danger topmargin-sm leftmargin-sm">You have no permission to view orders.</div>
<?php } ?>
<script>
    $('#filter_order').on('change', function() {
        status = $('#filter_order').val();
        $('#filter_order_status').val(status);
    });
    $('#shipping_type').on('change', function() {
        status = $('#shipping_type').val();
        $('#shipping_type_status').val(status);
        $('#order_list').bootstrapTable('refresh');
        $('#orderitem_list').bootstrapTable('refresh');
    });
    $('#seller_id').on('change', function() {
        $('#order_list').bootstrapTable('refresh');
        $('#orderitem_list').bootstrapTable('refresh');
    });
</script>
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
            $('#order_list').bootstrapTable('refresh');
            $('#orderitem_list').bootstrapTable('refresh');
        });
        $('#filter_order').on('change', function() {
            $('#order_list').bootstrapTable('refresh');
            $('#orderitem_list').bootstrapTable('refresh');
        });
    });

    function queryParams_1(p) {
        return {
            "start_date": $('#start_date').val(),
            "end_date": $('#end_date').val(),
            "seller_id": $('#seller_id').val(),
            "filter_order": $('#filter_order_status').val(),
            "shipping_type": $('#shipping_type_status').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    function queryParams(p) {
        return {
            "start_date": $('#start_date').val(),
            "end_date": $('#end_date').val(),
            "filter_order": $('#filter_order_status').val(),
            "seller_id": $('#seller_id').val(),
            "shipping_type": $('#shipping_type_status').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    function totalFormatter() {
        return '<span style="color:green;font-weight:bold;font-size:large;">TOTAL</span>'
    }

    function orderFormatter(data) {
        return '<span style="color:green;font-weight:bold;font-size:large;">' + data.length + ' Order'
    }
    var total = 0;

    function priceFormatter(data) {
        var field = this.field
        return '<span style="color:green;font-weight:bold;font-size:large;"> <?= $settings['currency'] ?> ' + data.map(function(row) {
                return +row[field]
            })
            .reduce(function(sum, i) {
                return sum + i
            }, 0);
    }

    function delivery_chargeFormatter(data) {
        var field = this.field
        return '<span style="color:green;font-weight:bold;font-size:large;"><?= $settings['currency'] ?> ' + data.map(function(row) {
                return +row[field]
            })
            .reduce(function(sum, i) {
                return sum + i
            }, 0);
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