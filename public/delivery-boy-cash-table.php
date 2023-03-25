<?php

include_once('includes/crud.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES 'utf8'");

include('includes/variables.php');
include_once('includes/custom-functions.php');

$fn = new custom_functions;
$config = $fn->get_configurations();
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.16.0/jquery.validate.min.js"></script>
<section class="content-header">
    <h1>Delivery Boy Cash Collection<small><a href="home.php"><i class="fa fa-home"></i> Home</a></small></h1>

</section>
<!-- Main content -->
<section class="content">
    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <div class="col-md-6">

            <?php if ($permissions['delivery_boys']['update'] == 1) { ?>
                <div class="box box-primary">
                    <!-- form start -->
                    <form id="transfer_form" method="POST" action="public/db-operation.php" data-parsley-validate class="form-horizontal form-label-left">
                        <input type='hidden' name="boy_id" id="boy_id" value='' />
                        <input type='hidden' name="cash_collection" id="cash_collection" value='1' />
                        <div class="box-body">
                            <div class="form-group">

                                <div class="col-md-12 col-sm-6 col-xs-12">
                                    <label for="details">Details:</label>
                                    <textarea class="form-control" rows="3" id="details" readonly></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-12 col-sm-6 col-xs-12">
                                    <label>Collected Amount</label>
                                    <input type="text" name="amount" id="amount" class="form-control" onkeyup="validate_amount(this.value);">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-12 col-sm-6 col-xs-12">
                                    <label>Date <small>(DD-MM-YYYY)</small></label>
                                    <input type="datetime-local" name="date" id="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-12 col-sm-6 col-xs-12">
                                    <label>Message</label>
                                    <textarea class="form-control" rows="3" id="details" name="message" id="message"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="ln_solid"></div>
                        <div class="form-group">
                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                <button type="submit" id="submit_button" class="btn btn-success">Submit</button>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-md-offset-3 col-md-8" style="display:none;" id="transfer_result"></div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="alert alert-danger">You have no permission to view fund transfer of delivery boys</div>
            <?php } ?>
        </div>
        <div class="col-md-6">
            <?php if ($permissions['delivery_boys']['read'] == 1) { ?>
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Delivery Boys</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-hover" data-toggle="table" id="delivery_boys" data-url="api-firebase/get-bootstrap-table-data.php?table=delivery-boys" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-click-to-select="true" data-mobile-responsive="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-maintain-selected="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-export-options='{
                            "fileName": "users-list-<?= date('d-m-y') ?>",
                            "ignoreColumn": ["state"]   
                        }'>
                            <thead>
                                <tr>
                                    <th data-field="state" data-radio="true"></th>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="name" data-sortable="true">Name</th>
                                    <th data-field="cash_received" data-sortable="true">Cash To Collect (<?= $settings['currency'] ?>)</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            <?php } else { ?>
                <div class="alert alert-danger">You have no permission to view delivery boys</div>
            <?php } ?>
        </div>
        <div class="separator"> </div>
    </div>
</section>
<section class="content">
    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <div class="col-xs-12">

            <?php if ($permissions['delivery_boys']['read'] == 1) { ?>
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Delivery boy cash transactions</h3>
                        <form method="POST" id="filter_form" name="filter_form">
                            <div class="form-group">
                                <!-- <label for="from" class="control-label col-md-1 col-sm-3 col-xs-12">Date</label> -->
                                <div class="col-md-3">
                                    <input type="date" class="form-control" id="filter_date" name="date" onchange="refresh(event);" autocomplete="off" />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-3">
                                    <select id="type" name="type" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value='delivery_boy_cash'>Delivery boy cash</option>
                                        <option value='delivery_boy_cash_collection'>Delivery boy cash collection</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-3">
                                    <?php
                                    $sql = "SELECT id,name FROM delivery_boys ORDER BY id + 0 ASC";
                                    $db->sql($sql);
                                    $delivery_boys = $db->getResult();
                                    ?>
                                    <select id='delivery_boy_id' name="delivery_boy_id" class='form-control'>
                                        <option value=''>Select Delivery Boy</option>
                                        <?php foreach ($delivery_boys as $delivery_boy) { ?>
                                            <option value='<?= $delivery_boy['id'] ?>'><?= $delivery_boy['name'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-3">
                                    <input type="reset" id="reset" value="Reset" class="form-control btn btn-success">
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- <div> -->


                    <div class="box-body table-responsive">
                        <table class="table table-hover" data-toggle="table" id="cash_collections" data-url="api-firebase/get-bootstrap-table-data.php?table=delivery-boy-cash" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th data-field="id" data-visible="false" data-sortable="true">ID</th>
                                    <th data-field="delivery_boy_id" data-visible="false" data-sortable="true">D.Boy ID</th>
                                    <th data-field="name" data-sortable="true">Name</th>
                                    <th data-field="mobile" data-sortable="true">Mobile</th>
                                    <th data-field="address" data-sortable="true">Address</th>
                                    <th data-field="amount" data-sortable="true">Amount (<?= $settings['currency'] ?>)</th>
                                    <th data-field="type" data-sortable="true">Type</th>
                                    <th data-field="message" data-sortable="true">Message</th>
                                    <th data-field="status" data-sortable="true" data-visible="false">Status</th>
                                    <th data-field="date_created" data-sortable="true">Date Time</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
        </div>
    <?php } else { ?>
        <div class="alert alert-danger">You have no permission to view fund transfer of delivery boys</div>
    <?php } ?>
    </div>
    <div class="separator"> </div>
    </div>
</section>


<script>
    var d_boy_cash = "";
    $('#transfer_form').validate({
        rules: {
            amount: "required"
        }
    });

$('#transfer_form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        if ($("#transfer_form").validate().form()) {
            var details_val = $('#details').val();
            if (details_val == "") {
                alert("you have to select delivery boy to collect cash.");
                $('#amount').val('');
            } else {
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function() {
                        $('#submit_button').html('Please wait..');
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(result) {
                        $('#transfer_result').html(result);
                        $('#transfer_result').show().delay(3000).fadeOut();
                        $('#submit_button').html('Submit');
                        $('#transfer_form')[0].reset();
                        $('#delivery_boys, #cash_collections').bootstrapTable('refresh');
                    }
                });
            }
        }
    });
    $('#delivery_boys').on('check.bs.table', function(e, row) {
        d_boy_cash = row.cash_received;
        $('#details').val("Id: " + row.id + " | Name:" + row.name + " | Mobile: " + row.mobile + " | Cash: " + row.cash_received);
        $('#boy_id').val(row.id);
    });

    function validate_amount() {
        var cash = d_boy_cash;
        var amount = $('#amount').val();
        var details_val = $('#details').val();
        if (details_val == "") {
            alert("you have to select delivery boy to collect cash.");
            $('#amount').val('');
        } else {
            if (parseInt(cash) > 0) {
                if (parseInt(amount) > parseInt(cash)) {
                    alert('You Can not enter amount greater than cash.');
                    $('#amount').val('');

                }
            } else {
                alert('Cash must be greater than zero.');
                $('#amount').val('');
            }
        }

    }

    function refresh(e) {
        var date = $('#filter_date').val();
        $('#cash_collections').bootstrapTable('refresh');
    }
</script>
<script>
    $('#delivery_boy_id').on('change', function() {
        $('#cash_collections').bootstrapTable('refresh');
    });
    $('#type').on('change', function() {
        $('#cash_collections').bootstrapTable('refresh');
    });
    $('#reset').on('click', function() {
        $('#type').val('');
        $('#filter_date').val('');
        $('#delivery_boy_id').val('');
        $('#cash_collections').bootstrapTable('refresh');
    });
</script>
<script>
    function queryParams(p) {
        return {
            "type": $('#type').val(),
            "date": $('#filter_date').val(),
            "delivery_boy_id": $('#delivery_boy_id').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>