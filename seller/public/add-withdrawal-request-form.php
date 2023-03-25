<?php
include_once('../includes/functions.php');
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
?>
<?php
if (!isset($_SESSION['seller_id']) && !isset($_SESSION['seller_name'])) {
    header("location:index.php");
} else {
    $ID = $_SESSION['seller_id'];
}
$balance = $fn->get_user_or_delivery_boy_balance('seller', $ID);
if (isset($_POST['btnAdd'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
    $message = $db->escapeString($fn->xss_clean($_POST['message']));
    if ($balance >= $amount) {
        // Debit amount requeted
        $new_balance =  $balance - $amount;
        if ($fn->debit_balance('seller', $ID, $new_balance)) {
            // store wallet transaction
           
                $fn->add_wallet_transaction(0, 0, $ID, 'seller', $amount, $message, 'seller_wallet_transactions');
           
            // store withdrawal request
            if ($fn->store_withdrawal_request('seller',$ID, $amount, $message)) {
                $error['add_pincode'] = "<section class='content-header'><span class='label label-success'>Withdrawal request accepted successfully!please wait for confirmation.</span><h4><small><a  href='withdrawal-requests.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Withdrawal Requests</a></small></h4></section>";

            } else {
                $error['add_pincode'] = "<section class='content-header'><span class='label label-danger'>Something went wrong please try again later.</span><h4><small><a  href='withdrawal-requests.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Withdrawal Requests</a></small></h4></section>";

            }
        } else {
            $error['add_pincode'] = "<section class='content-header'><span class='label label-danger'>Something went wrong please try again later.</span><h4><small><a  href='withdrawal-requests.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Withdrawal Requests</a></small></h4></section>";

        }
    } else {
        $error['add_pincode'] = "<section class='content-header'><span class='label label-danger'>Insufficient balance.</span><h4><small><a  href='withdrawal-requests.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Withdrawal Requests</a></small></h4></section>";
    }
}
?>
<section class="content-header">
    <h1>Send Withdrawal Request <small><a href='withdrawal-requests.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back</a></small></h1>

    <?php echo isset($error['add_pincode']) ? $error['add_pincode'] : ''; ?>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
    <hr />
</section>
<section class="content">
    <div class="row">
        <div class="col-md-6">
            <!-- INSERT INTO `withdrawal_requests`(`id`, `type`, `type_id`, `amount`, `message`, `status`, `last_updated`, `date_created`) VALUES () -->
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Send Withdrawal Request</h3>
                </div><!-- /.box-header -->
                <!-- form start -->
                <form method="post" id="area_form" enctype="multipart/form-data">
                    <div class="box-body">
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" id="amount" required class="form-control" onkeyup="validate_amount(this.value);">
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea class="form-control" rows="3" name="message" id="message"></textarea>
                        </div>
                       
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <input type="submit" class="btn-primary btn" value="Add" name="btnAdd" />&nbsp;
                        <input type="reset" class="btn-danger btn" value="Clear" />
                    </div>
                </form>
            </div><!-- /.box -->
        </div>
    </div>
</section>
<div class="separator"> </div>

<?php $db->disconnect(); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
<script>
    $('#area_form').validate({
        debug: false,
        rules: {
            amount: "required"
        }
    });
    var s_balance = '<?= $balance ?>';

    function validate_amount() {
        var balance = s_balance;
        var amount = $('#amount').val();

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
</script>