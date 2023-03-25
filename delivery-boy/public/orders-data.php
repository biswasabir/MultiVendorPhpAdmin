<?php
include_once('../includes/custom-functions.php');
$function = new custom_functions;
if (isset($_GET['id'])) {
    $ID = $db->escapeString($function->xss_clean($_GET['id']));
} else {
    $ID = "";
}
$currency = $function->get_settings('currency');

// create array variable to handle error
$allowed = ALLOW_MODIFICATION;
$error = array();
if (isset($_POST['update_order_status'])) {
    $process = $db->escapeString($fn->xss_clean($_POST['status']));
}
$sql = "SELECT order_id FROM `order_items` where id=$ID";
$db->sql($sql);
$res_order_id = $db->getResult();
$o_id = $res_order_id[0]['order_id'];
$sql = "SELECT oi.id FROM `order_items` oi JOIN users u ON u.id=oi.user_id JOIN product_variant v ON oi.product_variant_id=v.id JOIN products p ON p.id=v.product_id JOIN orders o ON o.id=oi.order_id WHERE o.id=$o_id";
$db->sql($sql);
$res_o_id = $db->getResult();

$sql = "SELECT oi.*,oi.tax_amount as item_tax_amt,oi.tax_percentage as item_tax_per ,oi.id as order_item_id,oi.active_status as oi_active_status,o.total as order_total,u.*,p.*,v.*,o.*,u.name as uname,p.name as pname,(SELECT short_code FROM unit un where un.id=v.measurement_unit_id)as mesurement_unit_name FROM `order_items` oi JOIN users u ON u.id=oi.user_id JOIN product_variant v ON oi.product_variant_id=v.id JOIN products p ON p.id=v.product_id JOIN orders o ON o.id=oi.order_id WHERE oi.id=" . $ID;
$db->sql($sql);
$res = $db->getResult();
// delivery boy update status remain
$config = $fn->get_configurations();
$generate_otp = $config['generate-otp'];
$items = [];
$otp = "";
foreach ($res as $row) {
    $otp = $row['otp'];
    $data = array($row['product_id'], $row['product_variant_id'], $row['pname'], $row['measurement'], $row['mesurement_unit_name'], $row['quantity'], $row['discounted_price'], $row['price'], $row['oi_active_status'], $row['otp'], $row['seller_id'], $row['order_item_id'], $row['sub_total']);
    array_push($items, $data);
}

?>
<section class="content-header">
    <h1>Order Detail</h1>
    <?php echo isset($error['update_data']) ? $error['update_data'] : ''; ?>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Order Detail</h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <!--                    <form  id="update_status_form">-->
                    <table class="table table-bordered">
                        <tr>
                            <input type="hidden" name="hidden" id="order_id" value="<?php echo $res[0]['id']; ?>">
                            <th class="col-md-1 text-center">ID</th>
                            <td><?php echo $res[0]['id']; ?></td>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Name</th>
                            <td><?php echo $res[0]['uname']; ?></td>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Email</th>
                            <td><?php echo $res[0]['email']; ?></td>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Contact</th>
                            <td><?php echo $res[0]['mobile']; ?></td>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Order Note</th>
                            <td><?php echo $res[0]['order_note']; ?></td>
                        </tr>
                        <?php
                        $seller_address = $fn->get_seller_address($res[0]['seller_id']);
                        ?>
                        <tr>
                            <th class="col-md-1 text-center">Seller Details</th>
                            <td><?php echo (!empty($seller_address)) ?  $seller_address : "Not Added"; ?></td>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Items</th>
                            <td>
                                <div class="container-fluid">
                                    <?php $total = 0;
                                    foreach ($items as $item) {
                                    ?>
                                        <div class="card col-md-3">
                                            <div class="card-body">
                                                <?php if ($item[8] == 'received') {
                                                    $active_status = '<label class="label label-primary">Received</label>';
                                                }
                                                if ($item[8] == 'processed') {
                                                    $active_status = '<label class="label label-info">Processed</label>';
                                                }
                                                if ($item[8] == 'shipped') {
                                                    $active_status = '<label class="label label-warning">Shipped</label>';
                                                }
                                                if ($item[8] == 'delivered') {
                                                    $active_status = '<label class="label label-success">Delivered</label>';
                                                }
                                                if ($item[8] == 'returned') {
                                                    $active_status = '<label class="label label-danger">Returned</label>';
                                                }
                                                if ($item[8] == 'cancelled') {
                                                    $active_status = '<label class="label label-danger">Cancelled</label>';
                                                }
                                                if ($item[8] == 'awaiting_payment') {
                                                    $active_status = '<label class="label label-secondary">Awaiting Payment</label>';
                                                }
                                                $array[] = $item[8];
                                                // $total += $subtotal = ($item[6] != 0 && $item[6] < $item[7]) ? ($item[6] * $item[5]) : ($item[7] * $item[5]);
                                                echo  "</br>" . $active_status . "<br><br><b>Product Id : </b>" . $item[0] . "</br>";
                                                echo "<b> Product Variant Id : </b>" . $item[1] . "</br>";
                                                echo " <b>Name : </b>" . $item[2] . "</br>";
                                                echo " <b>Unit : </b>" . $item[3] . " " . $item[4] . "</br>";
                                                echo " <b>Quantity : </b>" . $item[5] . "</br>";
                                                echo " <b>Price(" . $currency . ") : </b>" . $item[7] . "</br>";
                                                echo " <b>Discounted Price(" . $currency . ") : </b>" . $item[6] . "</br>";
                                                echo " <b>Subtotal(" . $currency . ") : </b>" .  $item[12] . "</br>";
                                                echo " <b>Active Status : </b>" . $active_status . "<br><br>";

                                                ?>
                                                <select name="status" id="status" class="form-control">
                                                    <option value="received" <?= ($item[8] == "received") ? 'selected' : ''; ?> data-value1='<?= $item[11] ?>'>Received</option>
                                                    <option value="processed" <?= ($item[8] == "processed") ? 'selected' : ''; ?> data-value1='<?= $item[11] ?>'>Processed</option>
                                                    <option value="shipped" <?= ($item[8] == "shipped") ? 'selected' : ''; ?> data-value1='<?= $item[11] ?>'>Shipped</option>
                                                    <option value="delivered" <?= ($item[8] == "delivered") ? 'selected' : ''; ?> data-value1='<?= $item[11] ?>'>Delivered</option>
                                                    <option value="cancelled" <?= ($item[8] == "cancelled") ? 'selected' : ''; ?> data-value1='<?= $item[11] ?>'>Cancel</option>
                                                    <option value="returned" <?= ($item[8] == "returned") ? 'selected' : ''; ?> data-value1='<?= $item[11] ?>'>Returned</option>
                                                </select>
                                                </br>
                                                <a href="#" title='update' id="submit_btn" class="btn btn-primary col-sm-12 col-md-12 update_order_item_status" style="margin-bottom:7px;">Update</a>

                                            </div>
                                            <div class="alert alert-danger" id="result_fail" style="display:none"></div>
                                            <div class="alert alert-success" id="result_success" style="display:none"></div>
                                        </div>
                                    <?php } ?>
                                </div>

                            </td>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Total (<?= $settings['currency'] ?>)</th>
                            <td><?php echo $res[0]['sub_total']; ?></td>
                        </tr>
                        <!-- <tr>
                            <th class="col-md-1 text-center">Delivery Charge (<?= $settings['currency'] ?>)</th>
                            <td><?php echo $res[0]['delivery_charge']; ?></td>

                        </tr> -->
                        <tr>
                            <th class="col-md-1 text-center">Payable Total(<?= $settings['currency'] ?>)</th>
                            <?php
                            if ($ID == $res_o_id[0]['id']) { ?>
                                <td><input type="text" class="form-control" id="final_total" name="final_total" value="<?= ceil($res[0]['sub_total'] + $res[0]['delivery_charge']); ?>" disabled></td>
                            <?php } else {
                            ?>
                                <td><input type="text" class="form-control" id="final_total" name="final_total" value="<?= ceil($res[0]['sub_total']); ?>" disabled></td>
                            <?php }
                            ?>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Deliver By</th>
                            <td>
                                <p>You.</p>
                            </td>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Payment Method</th>
                            <td><?php echo $res[0]['payment_method']; ?></td>
                        </tr>

                        <tr>
                            <th class="col-md-1 text-center">Address</th>
                            <td><?php echo $res[0]['address']; ?></td>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Order Date</th>
                            <td><?php echo date('d-m-Y', strtotime($row['date_added'])); ?></td>
                        </tr>
                        <tr>
                            <th class="col-md-1 text-center">Delivery Time</th>
                            <td><?php echo $res[0]['delivery_time']; ?></td>
                        </tr>
                    </table>
                    <!-- /.box-body -->

                    <div class="box-footer clearfix">
                        <?php
                        $check_array = array("awaiting_payment", "cancelled", "returned");
                        $result1 = array_diff($array, $check_array);
                        if (!empty($result1)) { ?>
                            <button class="btn btn-primary pull-right" onclick="myfunction()"><i class="fa fa-download"></i>Generate Invoice</button>
                        <?php } else { ?>
                            <button class="btn btn-primary disabled pull-right"><i class="fa fa-download"></i> Generate Invoice</button>
                        <?php } ?>
                        <!-- <a class="btn btn-primary" data-izimodal-open="#user-review-images">data</a> -->
                        <!-- <a class="btn btn-primary" id="map_locate" data-izimodal-open="#user-review-images" >Locate</a> -->
                        <a class="btn btn-primary" data-fancybox="" data-options="{&quot;iframe&quot; : {&quot;css&quot; : {&quot;width&quot; : &quot;80%&quot;, &quot;height&quot; : &quot;80%&quot;}}}" href="https://www.google.com/maps/search/?api=1&amp;query=<?= $res[0]['latitude']; ?>,<?= $res[0]['longitude']; ?>&hl=es;z=14&amp;output=embed">Locate</a>
                        <div id="load_more"></div>
                    </div>

                </div>
            </div>
            <!-- /.box -->
        </div>

    </div>
</section>

<script>
    var otp = '<?php echo $otp; ?>';
    var allowed = '<?= $allowed; ?>';
    var generate_otp = '<?= $generate_otp ?>';
    $(document).on('click', '.update_order_item_status', function(e) {
        e.preventDefault();
        if (allowed == 0) {
            alert('Sorry! This operation is not allowed in demo panel!.');
            window.location.reload();
            return false;
        }
        var status1 = $('#status').val();
        var id = $('#order_id').val();
        var item_id = '<?= $res[0]['order_item_id']; ?>';
        var dataString = 'update_order_status=1&order_id=' + id + '&status=' + status1 + '&order_item_id=' + item_id + '&delivery_boy_id=' + <?= $_SESSION['delivery_boy_id']; ?> + '&ajaxCall=1';

        if (generate_otp == 1) {
            if (status1 == "delivered") {
                var entered_otp = prompt("Please enter OTP:");
                if (entered_otp == null || entered_otp == "") {
                    alert("Must required valid OTP.");
                } else {
                    if (entered_otp == otp) {
                        $.ajax({
                            url: "../api-firebase/order-process.php",
                            type: "POST",
                            data: dataString,
                            dataType: "json",
                            beforeSend: function() {
                                $('#submit_btn').html('Please wait...').attr('disabled', true);
                            },
                            success: function(data) {
                                if (data.error == true) {
                                    $('#result_fail').html(data.message);
                                    $('#result_fail').show().delay(6000).fadeOut();
                                    $('#submit_btn').html('Update').attr('disabled', false);
                                } else {
                                    $('#result_success').html(data.message);
                                    $('#result_success').show().delay(6000).fadeOut();
                                    $('#submit_btn').html('Update').attr('disabled', false);
                                    // location.reload(true);
                                }
                            }
                        });
                    } else {
                        alert("Incorrect OTP");
                    }
                }
            } else if (status1 == "received" || status1 == "processed" || status1 == "shipped" || status1 == "cancelled" || status1 == "returned") {
                $.ajax({
                    url: "../api-firebase/order-process.php",
                    type: "POST",
                    data: dataString,
                    dataType: "json",
                    success: function(data) {
                        if (data.error == true) {
                            $('#result_fail').html(data.message);
                            $('#result_fail').show().delay(6000).fadeOut();
                        } else {
                            $('#result_success').html(data.message);
                            $('#result_success').show().delay(6000).fadeOut();
                            // location.reload(true);
                        }
                    }
                });
            }
        } else {
            $.ajax({
                url: "../api-firebase/order-process.php",
                type: "POST",
                data: dataString,
                dataType: "json",
                success: function(data) {
                    if (data.error == true) {
                        $('#result_fail').html(data.message);
                        $('#result_fail').show().delay(6000).fadeOut();
                    } else {
                        $('#result_success').html(data.message);
                        $('#result_success').show().delay(6000).fadeOut();
                        // location.reload(true);
                    }
                }
            });
        }
    });
</script>



<script>
    function myfunction() {
        window.location.href = 'invoice.php?id=<?php echo $ID; ?>';
    }
</script>
<?php $db->disconnect(); ?>