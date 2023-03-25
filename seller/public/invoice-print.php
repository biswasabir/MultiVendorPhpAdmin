<?php
include_once('../includes/functions.php');
include_once('../includes/custom-functions.php');
$function = new custom_functions;
$settings = $function->get_configurations();
$currency = $function->get_settings('currency');
?>

<?php
if (!isset($_SESSION['seller_id']) && !isset($_SESSION['seller_name'])) {
    header("location:index.php");
} else {
    $seller_id = $_SESSION['seller_id'];
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $ID = $_GET['id'];
} else { ?>
    <script>
        window.location.href = "invoices.php";
    </script>
<?php
}
// $sql_outer = "SELECT oi.price as order_item_price,oi.id as order_item_id,oi.*,u.*,p.*,v.*,o.*,u.name as uname,oi.discounted_price as dis_price,d.name as delivery_boy,o.status as order_status,oi.active_status as order_item_status,p.name as pname,(SELECT short_code FROM unit un where un.id=v.measurement_unit_id)as mesurement_unit_name FROM `order_items` oi JOIN users u ON u.id=oi.user_id JOIN product_variant v ON oi.product_variant_id=v.id JOIN products p ON p.id=v.product_id JOIN orders o ON o.id=oi.order_id LEFT JOIN delivery_boys d ON oi.delivery_boy_id=d.id WHERE o.id = $ID AND oi.seller_id = $seller_id";
$sql_outer = "SELECT oi.price as order_item_price,oi.id as order_item_id,oi.*,u.*,v.*,p.*,o.*,u.name as uname,oi.discounted_price as dis_price,d.name as delivery_boy,o.status as order_status,oi.active_status as order_item_status,oi.seller_id as seller_id,(SELECT short_code FROM unit un where un.id=v.measurement_unit_id) as mesurement_unit_name FROM `order_items` oi JOIN users u ON u.id=oi.user_id LEFT JOIN product_variant v ON oi.product_variant_id=v.id LEFT JOIN products p ON p.id=v.product_id JOIN orders o ON o.id=oi.order_id LEFT JOIN delivery_boys d ON oi.delivery_boy_id=d.id WHERE o.id=$ID AND oi.seller_id = $seller_id";

$db->sql($sql_outer);
$res_outer = $db->getResult();

$sql1 = "SELECT s.*,c.name as city FROM seller s left join cities c on c.id=s.city_id wHERE s.id =" . $seller_id;
$db->sql($sql1);
$seller_res = $db->getResult();
// print_r($seller_res);
$sql2 = "SELECT * FROM pincodes wHERE id =" . $seller_res[0]['pincode_id'];
$db->sql($sql2);
$pincode_res = $db->getResult();


$items = [];
$final_price = 0;
foreach ($res_outer as $row) {
    if ($row['dis_price'] == 0 || $row['dis_price'] == '') {
        $final_price = $row['order_item_price'];
    } else {
        $final_price = $row['dis_price'];
    }
    $data = array($row['product_variant_id'], $row['product_name'], $row['quantity'], $row['measurement'], $row['mesurement_unit_name'], $row['discounted_price'] * $row['quantity'], $row['discount'], $row['sub_total'], $row['order_item_status'], $row['tax_id'], $final_price);
    array_push($items, $data);
}
$encoded_items = $db->escapeString(json_encode($items));
$id = $res_outer[0]['order_id'];

$order_list = $encoded_items;
?>
<style>
    @page {
        size: auto;
        margin: 0mm;
    }
</style>

<style>
    .borderless td,
    .heading th {
        border: none !important;
        padding: 0px !important;
    }

    address {
        margin-bottom: 1px;
        font-style: normal;
        line-height: 1.42857143;
    }

    .row1 {
        margin-right: -15px;
        margin-left: -15px;
        margin-top: 46px;
    }
</style>

<section class="content-header">
    <h1>
        Invoice
        <small><a href="home.php"><i class="fa fa-home"></i> Home</a></small>
    </h1>
</section>
<section class="content">
    <section class="invoice">
        <!-- title row -->
        <div class="row">
            <div class="col-xs-12">
                <div class="col-md-6">
                    <h2 class="page-header text-left">
                        <?= $seller_res[0]['store_name']; ?>
                    </h2>
                </div>
                <div class="col-md-6">
                    <h2 class="page-header text-right">
                        Mo. +91 <?= $seller_res[0]['mobile']; ?>
                    </h2>
                </div>
            </div><!-- /.col -->
        </div>
        <!-- info row -->
        <div class="row invoice-info">
            <div class="col-sm-5 invoice-col">
                <strong>Sold By :</strong>
                <address>
                    <strong><?= $settings['app_name']; ?></strong>
                </address>
                <address>
                    Store Name: <?= $seller_res[0]['store_name']; ?><br>
                </address>
                <address>
                    Email : <?= $seller_res[0]['email']; ?>
                </address>
                <address>
                    Mobile : <?= $seller_res[0]['mobile']; ?>
                </address>
                <address>
                    Address : <?= $seller_res[0]['street']; ?>,<?= $seller_res[0]['state']; ?>,<?= $seller_res[0]['city']; ?> Pincode - <?= $pincode_res[0]['pincode']; ?>
                </address>
                <address>
                    <?php if (!empty($seller_res[0]['delivery_boy']) && $seller_res[0]['delivery_boy']) { ?>
                        Delivery By : &nbsp; <?= $seller_res[0]['delivery_boy']; ?>
                    <?php } else {
                        echo "";
                    } ?>
                </address>

            </div><!-- /.col -->
            <?php
            if ($fn->get_seller_permission($seller_id, 'customer_privacy')) {  ?>
                <div class="col-sm-5 invoice-col">
                    <strong>Shipping Address : </strong>
                    <address>
                        <strong><?php echo $res_outer[0]['uname']; ?></strong>
                    </address>
                    <address>
                        <?php echo $res_outer[0]['address']; ?><br>

                    </address>
                    <address>
                        <strong><?php echo $res_outer[0]['mobile']; ?></strong><br>
                    </address>
                    <address>
                        <strong><?php echo $res_outer[0]['email']; ?></strong><br>
                    </address>
                </div><!-- /.col -->
            <?php } else { ?>
                <div class="col-sm-5 invoice-col">
                    <strong>Shipping Address : </strong>
                    <address>
                        <strong><?php echo $res_outer[0]['uname']; ?></strong>
                    </address>
                    <address>
                        <?php echo $res_outer[0]['address']; ?><br>

                    </address>
                    <address>
                        <strong><?= str_repeat("*", strlen($res_outer[0]['mobile']) - 3) . substr($res_outer[0]['mobile'], -3) ?></strong><br>
                    </address>
                    <address>
                        <strong><?= str_repeat("*", strlen($res_outer[0]['email']) - 13) . substr($res_outer[0]['email'], -13) ?></strong><br>
                    </address>
                </div><!-- /.col -->
            <?php } ?>


            <div class="col-sm-2 invoice-col">
                <strong>Retail Invoice : </strong>
                <address>
                    <b>No : </b>#<?= $res_outer[0]['id']; ?>

                </address>
                <address>
                    <b>Date: </b><?php echo date('d-m-Y h:i:s A', strtotime($res_outer[0]['date_added'])); ?>
                </address>
                <address>
                    <?php if (!empty($seller_res[0]['pan_number']) && $seller_res[0]['pan_number']) { ?>
                        <strong> PAN NO :</strong> &nbsp; <?= $seller_res[0]['pan_number']; ?>
                    <?php } else {
                        echo "";
                    } ?>
                </address>
                <address>
                    <?php if (!empty($seller_res[0]['tax_name']) && $seller_res[0]['tax_number']) { ?>
                        <strong> <?= $seller_res[0]['tax_name']; ?> : </strong> &nbsp; <?= $seller_res[0]['tax_number']; ?>
                    <?php } else {
                        echo "";
                    } ?>
                </address>
            </div>
        </div>
        <div class="row1">
            <div class="col-xs-12 table-responsive">
                <table class="table borderless">
                    <thead class="text-center">
                        <tr>
                            <th>Sr No.</th>
                            <th>Product Code</th>
                            <th>Name</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Tax <?= $currency; ?>(%)</th>
                            <th>Qty</th>
                            <th>SubTotal (<?= $currency; ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_total = 'select sub_total from order_items where id=' . $res_outer[0]['order_item_id'];
                        $db->sql($sql_total);
                        $res_total = $db->getResult();

                        $sql_total1 = 'select total from orders where id=' . $ID;
                        $db->sql($sql_total1);
                        $res_total1 = $db->getResult();
                        ?>
                        <?php
                        $decoded_items = json_decode(stripSlashes($order_list));
                        $qty = 0;
                        $i = 1;
                        $total = $total_tax_amt = $tax_amount1 = 0;
                        $total_tax = array();
                        foreach ($decoded_items as $item) {
                            if ($item[8] != 'cancelled' && $item[8] != 'returned') {
                        ?>
                                <tr>
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $i ?><br></td>
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $item[0] ?><br></td>
                                    <td><?= $item[1] ?><br></td>
                                    <td><?= $item[3] . " " . $item[4] ?><br></td>
                                    <td><?= $item[10] ?></td>
                                    <?php if ($item[9] != 0) {
                                        $sql_tax = "SELECT * FROM `taxes` where id=" . $item[9];
                                        $db->sql($sql_tax);
                                        $res_tax = $db->getResult();
                                        $tax_amount1 = ($res_tax[0]['percentage'] / 100) * $item[10] * $item[2];
                                    ?>
                                        <td><?php echo $tax_amount1 . " (" . $res_tax[0]['percentage'] . "%) " . $res_tax[0]['title'];
                                        } else {
                                            $tax_amount1 = 0;
                                            ?><br></td>
                                        <td><?= "0 %";
                                        } ?><br></td>
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $item[2] ?><br></td>
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $item[7] ?><br></td>

                                </tr>
                        <?php $qty = $qty + $item[2];
                                $i++;
                                $total += $item[7];
                                $total_tax_amt += $tax_amount1;
                            }
                        }
                        $order_total_price = $total; ?>

                    </tbody>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Total</th>
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $total_tax_amt ?><br></td>
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $qty ?><br></td>
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $total; ?><br></td>
                    </tr>
                </table>
            </div><!-- /.col -->
        </div>
        <!-- /.row -->
        <div class="row col-md-offset-0">
            <p><b>Payment Method : </b> <?= $res_outer[0]['payment_method'] ?></p>
        </div>
        <!-- <?php if ($res_outer[0]['discount'] > 0) {
                    $discounted_amount = $res_total1[0]['total'] * $res_outer[0]['discount'] / 100; /*  */
                    $final_total = $res_total1[0]['total'] - $discounted_amount;
                    $discount_in_rupees = $res_total1[0]['total'] - $final_total;
                    $discount_in_rupees = $discount_in_rupees;
                } else {
                    $discount_in_rupees = 0;
                } ?> -->
        <div class="row">
            <!-- accepted payments column -->
            <div class="col-xs-6 col-xs-offset-6">
                <div class="table-responsive">
                    <table class="table borderless heading">
                        <th></th>
                        <tr>
                            <th>Total Order Price (<?= $currency; ?>)</th>
                            <td><?php echo '+ ' . $total; ?></td>
                        </tr>
                        <tr>
                            <th>Delivery Charge (<?= $currency; ?>)</th>

                            <td><?= '+ ' . $res_outer[0]['delivery_charge']; ?></td>
                        </tr>
                        <tr>
                            <th>Discount <?= $currency; ?>(%)</th>
                            <td><?= '- ' . $discount_in_rupees . ' (' . $res_outer[0]['discount'] . '%)'; ?></td>
                        </tr>
                        <tr>
                            <th>Promo (<?= $res_outer[0]['promo_code']; ?>) Discount (<?= $currency; ?>)</th>
                            <td><?= '- ' . $res_outer[0]['promo_discount']; ?></td>
                        </tr>
                        <tr>
                            <th>Wallet Used (<?= $currency; ?>)</th>
                            <td><?= '- ' . $res_outer[0]['wallet_balance']; ?></td>
                        </tr>
                        <?php $final_total = $total + $res_outer[0]['delivery_charge'] - $discount_in_rupees - $res_outer[0]['promo_discount'] - $res_outer[0]['wallet_balance'];?>
                        <tr>
                            <th>Final Total (<?= $currency; ?>)</th>
                            <td><?= '= '. $final_total; ?></td>
                        </tr>
                    </table>
                </div>
            </div><!-- /.col -->
        </div><!-- /.row -->

        <!-- this row will not appear when printing -->
        <div class="row no-print">
            <div class="col-xs-12">
                <form><button type='button' value='Print this page' onclick='printpage();' class="btn btn-default"><i class="fa fa-print"></i> Print</button>
                </form>
                <script>
                    function printpage() {
                        var is_chrome = function() {
                            return Boolean(window.chrome);
                        }
                        if (is_chrome) {
                            window.print();
                            setTimeout(function() {
                                window.close();
                            }, 10000);
                            //give them 10 seconds to print, then close
                        } else {
                            window.print();
                            window.close();
                        }
                    }
                </script>
            </div>
        </div>
    </section>
</section>
<script>
    $("br").remove();
</script>