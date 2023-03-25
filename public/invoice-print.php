<?php
include_once('includes/functions.php');
include_once('includes/custom-functions.php');
$function = new custom_functions;
$settings = $function->get_configurations();
$currency = $function->get_settings('currency');
?>

<?php
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $ID = $db->escapeString($function->xss_clean($_GET['id']));
} else { ?>
    <script>
        window.location.href = "invoices.php";
    </script>
<?php
}
$sql_outer = "SELECT oi.price as order_item_price,oi.id as order_item_id,oi.*,u.*,v.*,p.*,o.*,u.name as uname,oi.discounted_price as dis_price,d.name as delivery_boy,o.status as order_status,oi.active_status as order_item_status,oi.seller_id as seller_id FROM `order_items` oi JOIN users u ON u.id=oi.user_id LEFT JOIN product_variant v ON oi.product_variant_id=v.id LEFT JOIN products p ON p.id=v.product_id JOIN orders o ON o.id=oi.order_id LEFT JOIN delivery_boys d ON oi.delivery_boy_id=d.id WHERE o.id=" . $ID;
$db->sql($sql_outer);
$res_outer = $db->getResult();
$items = $seller_items = [];
$final_price = 0;
foreach ($res_outer as $row) {
    if ($row['dis_price'] == 0 || $row['dis_price'] == '') {
        $final_price = $row['order_item_price'];
    } else {
        $final_price = $row['dis_price'];
    }
    $data = array($row['product_variant_id'], $row['product_name'], $row['quantity'], $row['measurement'], $row['measurement_unit_id'], $row['discounted_price'] * $row['quantity'], $row['discount'], $row['sub_total'], $row['order_item_status'], $row['tax_id'], $final_price, $row['seller_id']);
    array_push($items, $data);
}
$seller_ids = array_values(array_unique(array_column($res_outer, "seller_id")));

$encoded_items = $db->escapeString(json_encode($items));
$id = $res_outer[0]['order_id'];
$order_item_id = $res_outer[0]['order_item_id'];

$sql = "SELECT COUNT(id) as total FROM `invoice` where order_id=" . $id;
$db->sql($sql);
$res = $db->getResult();
$seller_info = $function->get_data($columns = [], "id=" . $res_outer[0]['seller_id'], 'seller');
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

    p {
        margin: 0 0 0px;
    }
</style>

<section class="content-header">
    <h1>
        Invoice
        <small><a href="home.php"><i class="fa fa-home"></i> Home</a></small>
    </h1>
</section>
<section class="content">
    <?php if ($permissions['reports']['create'] == 0) { ?>
        <div class="alert alert-danger topmargin-sm">You have no permission to generate invoice</div>
    <?php exit();
    } ?>
    <section class="invoice">
        <!-- title row -->
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-6">
                    <h2 class="page-header text-left">
                        <?= $settings['app_name']; ?>
                    </h2>
                </div>
                <div class="col-md-6">
                    <h2 class="page-header text-right">
                        Mo. <?= $settings['support_number']; ?>
                    </h2>
                </div>
            </div><!-- /.col -->
        </div>
        <!-- info row -->
        <div class="row invoice-info">
            <div class="col-sm-4 invoice-col">
                From
                <address>
                    <strong><?= $settings['app_name']; ?></strong>
                </address>
                <address>
                    Email: <?= $settings['support_email']; ?><br>

                </address>
                <address>
                    Customer Care : <?= $settings['support_number']; ?>
                </address>
                <?php if (isset($res_outer[0]['delivery_boy'])) { ?>
                    <address>
                        Delivery By: &nbsp; <?= $res_outer[0]['delivery_boy']; ?>
                    </address>
                <?php } ?>
                <?php if (isset($settings['tax_name']) && isset($settings['tax_number'])) { ?>
                    <address>
                        <?= $settings['tax_name'] ?> : &nbsp; <?= $settings['tax_number'] ?>
                    </address>
                <?php } ?>
            </div><!-- /.col -->
            <div class="col-sm-5 invoice-col">
                Shipping Address
                <address>
                    <strong><?= $res_outer[0]['uname']; ?></strong>
                </address>
                <address>
                    <?= $res_outer[0]['address']; ?><br>

                </address>
                <address>
                    <strong><?= $res_outer[0]['mobile']; ?></strong><br>
                </address>
                <address>
                    <strong><?= $res_outer[0]['email']; ?></strong><br>
                </address>
            </div><!-- /.col -->
            <div class="col-sm-2 invoice-col">
                Retail Invoice
                <address>
                    <b>No : </b>#<?= $order_item_id; ?>
                </address>
                <address>
                    <b>Date: </b><?= date('d-m-Y h:i:s A', strtotime($res_outer[0]['date_added'])); ?>
                </address>
            </div>
        </div>
        <hr>
        <?php for ($i = 0; $i < count($seller_ids); $i++) {
            $seller_items = [];
            $sql = "SELECT oi.price as order_item_price,oi.id as order_item_id,oi.*,u.*,p.*,v.*,o.*,u.name as uname,oi.discounted_price as dis_price,d.name as delivery_boy,o.status as order_status,oi.active_status as order_item_status,p.name as pname,(SELECT short_code FROM unit un where un.id=v.measurement_unit_id)as mesurement_unit_name FROM `order_items` oi JOIN users u ON u.id=oi.user_id JOIN product_variant v ON oi.product_variant_id=v.id JOIN products p ON p.id=v.product_id JOIN orders o ON o.id=oi.order_id LEFT JOIN delivery_boys d ON oi.delivery_boy_id=d.id WHERE o.id = $ID AND oi.seller_id = " . $seller_ids[$i];
            $db->sql($sql);
            $res_items = $db->getResult();
            foreach ($res_items as $row) {
                if ($row['dis_price'] == 0 || $row['dis_price'] == '') {
                    $final_price = $row['order_item_price'];
                } else {
                    $final_price = $row['dis_price'];
                }
                $data = array($row['product_id'], $row['pname'], $row['quantity'], $row['measurement'], $row['mesurement_unit_name'], $row['discounted_price'] * $row['quantity'], $row['discount'], $row['sub_total'], $row['order_item_status'], $row['tax_id'], $final_price, $row['seller_id']);

                array_push($seller_items, $data);
            }

            $seller_info = $function->get_data($columns = [], "id=" . $seller_ids[$i], 'seller');
        ?>
            <div class="container col-md-12">
                <div class="well">
                    <div class="row"><strong>Item : <?= $i + 1 ?></strong></div>
                    <div class="row">
                        <div class="col-md-4">
                            <p>Sold By</p>
                            <strong><?= $seller_info[0]['store_name'] ?></strong>
                            <p>Email: <?= $seller_info[0]['email'] ?></p>
                            <p> Customer Care : +91 <?= $seller_info[0]['mobile'] ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>
                                <p>Pan Number : <?= $seller_info[0]['pan_number'] ?></p>
                                <p><?= $seller_info[0]['tax_name'] ?> : <?= $seller_info[0]['tax_number'] ?></p>
                            </strong>
                            <?php
                            if (!empty($res_items[0]['delivery_boy_id'])) {
                                $delivery_noy_name = $function->get_data($columns = ['name'], 'id=' . $res_items[0]['delivery_boy_id'], 'delivery_boys');
                            }
                            ?>
                            <p>Delivery By : <?= (!empty($delivery_noy_name[0]['name'])) ? $delivery_noy_name[0]['name'] : "Not Assigned" ?></p>
                        </div>
                    </div>
                    <hr>
                    <h4>Product Details:</h4>
                    
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-responsive">
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
                                        $qty = 0;
                                        $h = 1;
                                        $total = $total_tax_amt = $tax_amount1 = 0;
                                        $total_tax = array();
                                        foreach ($seller_items as $item) {
                                            if ($item[8] != 'cancelled' && $item[8] != 'returned') {
                                        ?>
                                                <tr>
                                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $h ?><br></td>
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
                                                        <td><?= $tax_amount1 . " (" . $res_tax[0]['percentage'] . "%) " . $res_tax[0]['title'];
                                                        } else {
                                                            $tax_amount1 = 0;
                                                            ?><br></td>
                                                        <td><?= "0 %";
                                                        } ?><br></td>
                                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $item[2] ?><br></td>
                                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $item[7] ?><br></td>
                                                </tr>
                                        <?php $qty = $qty + $item[2];
                                                $h++;
                                                $total += $item[7];
                                                $total_tax_amt += $tax_amount1;
                                            }
                                        } ?>
                                    </tbody>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th>Total</th>
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $total_tax_amt ?><br></td>
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $qty ?><br></td>
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<?= $total ?><br></td>
                                    </tr>
                                </table>
                            </div><!-- /.col -->
                        </div><!-- /.col -->
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="row col-md-12">
            <p><b>Payment Method : </b> <?= $res_outer[0]['payment_method'] ?></p><br>
        </div>

        <!-- /.row -->

        <?php if ($res_outer[0]['discount'] > 0) {
            $discount_in_rupees = ($res_outer[0]['discount'] / 100) * $res_outer[0]['total'];
        } else {
            $discount_in_rupees = 0;
        } ?>
        <div class="row">
            <!-- accepted payments column -->
            <div class="col-md-6 col-md-offset-6">

                <!--<p class="lead">Payment Date: </p>-->
                <div class="table-responsive">
                    <table class="table borderless heading">
                        <th></th>
                        <tr>
                            <th>Total Order Price (<?= $currency; ?>)</th>
                            <td><?php echo '+ ' . $res_outer[0]['total']; ?></td>
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
                        <tr>
                            <th>Final Total (<?= $currency; ?>)</th>
                            <td><?= '= ' . ceil($res_outer[0]['final_total']); ?></td>
                        </tr>
                    </table>
                </div>
            </div><!-- /.col -->
        </div><!-- /.row -->

        <!-- this row will not appear when printing -->
        <div class="row no-print">
            <div class="col-md-12">
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