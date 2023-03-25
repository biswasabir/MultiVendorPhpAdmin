<?php

include_once('../includes/crud.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES 'utf8'");

include('../includes/variables.php');
include_once('../includes/custom-functions.php');

$fn = new custom_functions;
$config = $fn->get_configurations();
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.16.0/jquery.validate.min.js"></script>
<section class="content-header">
    <h1>Seller Wallet Transactions /<small><a href="home.php"><i class="fa fa-home"></i> Home</a></small></h1>

</section>
<!-- Main content -->
<section class="content">
    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Seller Wallet Transactions</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover" data-toggle="table" id="fund_transfers" data-query-params="queryParams" data-url="get-bootstrap-table-data.php?table=seller_wallet_transactions" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="seller_id" data-sortable="true">Seller ID</th>
                                <th data-field="name" data-sortable="true">Name</th>
                                <th data-field="type" data-sortable="true">Type</th>
                                <th data-field="amount" data-sortable="true">Amount</th>
                                <th data-field="message" data-sortable="true">Message</th>
                                <th data-field="status" data-sortable="true">Status</th>
                                <th data-field="date_created" data-sortable="true">Transaction Date</th>
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
    var d_boy_balance = "";
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
                alert("you have to select seller to transfer the funds.");
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
                        $('#sellers, #fund_transfers').bootstrapTable('refresh');
                        // $('#fund_transfers').bootstrapTable('refresh');
                    }
                });
            }
        }
    });
    $('#sellers').on('check.bs.table', function(e, row) {
        d_boy_balance = row.balance;
        $('#details').val("Id: " + row.id + " | Name:" + row.name + " | Mobile: " + row.mobile + " | Balance: " + row.balance);
        $('#seller_id').val(row.id);
        $('#seller_balance').val(row.balance);
    });

    function validate_amount() {
        var balance = d_boy_balance;
        var amount = $('#amount').val();
        var details_val = $('#details').val();
        if (details_val == "") {
            alert("you have to select delivery boy to transfer the funds.");
            $('#amount').val('');
        } else {
            if (parseInt(balance) > 0) {
                if (parseInt(amount) > parseInt(balance)) {
                    alert('You Can not enter amount greater than balance.');
                    $('#amount').val('');

                }
            } else {
                alert('Balance must be greater than zero.');
                $('#amount').val('');
            }
            if (parseInt(amount) <= 0) {
                alert('Amount must be greater than zero.');
                $('#amount').val('');
            }
        }

    }
</script>
<script>
    function queryParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>