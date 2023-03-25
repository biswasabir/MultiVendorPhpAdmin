<?php
include_once('../includes/functions.php');
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
$settings = $fn->get_configurations();
$currency = $fn->get_settings('currency');
?>

<?php
if (isset($_GET['id'])) {
    $ID = $db->escapeString($fn->xss_clean($_GET['id']));
} else { ?>
    <script>
        window.location.href = "invoice.php";
    </script>
<?php
}
$sql_outer = "SELECT oi.price as order_item_price,oi.*,oi.id as order_item_id,u.*,p.*,v.*,o.*,u.name as uname,d.name as delivery_boy,o.status as order_status,oi.active_status as order_item_status FROM `order_items` oi JOIN users u ON u.id=oi.user_id LEFT JOIN product_variant v ON oi.product_variant_id=v.id LEFT JOIN products p ON p.id=v.product_id JOIN orders o ON o.id=oi.order_id LEFT JOIN delivery_boys d ON oi.delivery_boy_id=d.id WHERE oi.id=" . $ID;
$db->sql($sql_outer);
$res_outer = $db->getResult();
$items = [];
$final_price = 0;
foreach ($res_outer as $row) {
    if ($row['discounted_price'] == 0 || $row['discounted_price'] == '') {
        $final_price = $row['order_item_price'];
    } else {
        $final_price = $row['discounted_price'];
    }
    $data = array($row['product_variant_id'], $row['product_name'], $row['quantity'], $row['measurement'], $row['measurement_unit_id'], $row['discounted_price'] * $row['quantity'], $row['discount'], $row['sub_total'], $row['order_item_status'], $row['tax_id'], $final_price, $row['order_id'], $row['seller_id']);
    array_push($items, $data);
}
$encoded_items = $db->escapeString(json_encode($items));
$id = $res_outer[0]['id'];
$order_item_id = $res_outer[0]['order_item_id'];
$seller_id = $res_outer[0]['seller_id'];
$sql = "select * from seller where id =$seller_id";
$db->sql($sql);
$seller_info = $db->getResult();

$sql = "SELECT * FROM pincodes wHERE id =" . $seller_info[0]['pincode_id'];
$db->sql($sql);
$pincode_res = $db->getResult();
if (!empty($seller_info[0]['city_id'])) {
    $sql = "SELECT name FROM cities wHERE id =" . $seller_info[0]['city_id'];
    $db->sql($sql);
    $city_res = $db->getResult();
}

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
</style>

<section class="content-header">
    <h1>
        Invoice /
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
                        <?= $seller_info[0]['store_name']; ?>
                    </h2>
                </div>
                <div class="col-md-6">
                    <h2 class="page-header text-right">
                        Mo. +91 <?= $seller_info[0]['mobile']; ?>
                    </h2>
                </div>
            </div><!-- /.col -->
        </div>
        <!-- info row -->
        <div class="row invoice-info">
            <div class="col-sm-4 invoice-col">
                Sold By
                <address>
                    <strong><?= $seller_info[0]['store_name']; ?></strong><br>
                    Email: <?= $seller_info[0]['email']; ?><br>
                    Customer Care : <?= $seller_info[0]['mobile']; ?><br>
                    Address : <?= (!empty($seller_info[0]['street'])) ? $seller_info[0]['street'] : ""; ?>,<?= (!empty($seller_info[0]['state'])) ? $seller_info[0]['state'] : ""; ?>,<?= (!empty($city_res[0]['name'])) ? $city_res[0]['name'] . " - " : ""; ?> <?= (!empty($pincode_res[0]['pincode'])) ? $pincode_res[0]['pincode'] : ""; ?><br>
                    Delivery By: &nbsp; <?php echo $res_outer[0]['delivery_boy']; ?>
                </address>

            </div><!-- /.col -->
            <div class="col-sm-5 invoice-col">
                Shipping Address
                <address>
                    <strong><?php echo $res_outer[0]['uname']; ?></strong><br>
                    <?php echo $res_outer[0]['address']; ?><br>
                    <strong><?php echo $res_outer[0]['mobile']; ?></strong><br>
                    <strong><?php echo $res_outer[0]['email']; ?></strong><br>
                </address>

            </div><!-- /.col -->
            <div class="col-sm-2 invoice-col">
                Retail Invoice<br>
                <b>No : </b>#<?php echo $order_item_id; ?>
                <br>
                <b>Date: </b><?php echo date('d-m-Y h:i A', strtotime($res_outer[0]['date_added'])); ?>
                <br>
                <address>
                    <br><strong>PAN No.: <?= $seller_info[0]['pan_number']; ?></strong><br>
                    <strong><?= $seller_info[0]['tax_name'] . " : " . $seller_info[0]['tax_number'] ?></strong><br>
                </address>
            </div>
        </div><!-- /.row -->

        <!-- Table row -->
        <div class="row">
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
                        $decoded_items = json_decode(stripSlashes($order_list));
                        $qty = 0;
                        $i = 1;
                        $total = $total_tax_amt = 0;
                        $total_tax = array();
                        foreach ($decoded_items as $item) {
                            // print_r($item);
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
                                        $tax_amount1 = round(($res_tax[0]['percentage'] / 100) * $item[10]);
                                        // print_r($res_tax);
                                    ?>
                                        <td><?php echo $tax_amount1 . " (" . $res_tax[0]['percentage'] . "%) " . $res_tax[0]['title'];
                                        } else { ?><br></td>
                                        <td><?= "0 %";
                                        } ?><br></td>
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $item[2] ?><br></td>
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $item[7] ?><br></td>
                                </tr>
                        <?php $qty = $qty + $item[2];
                                $i++;
                                $total += $item[7];
                            }
                        } ?>
                    </tbody>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Total</th>
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $res_outer[0]['sub_total']; ?><br></td>
                    </tr>
                </table>
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