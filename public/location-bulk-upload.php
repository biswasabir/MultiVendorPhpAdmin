<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice For Mobile</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
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
</head>

<body>
    <?php
    include_once('includes/functions.php');
    include_once('includes/custom-functions.php');
    include_once('library/jwt.php');
    include_once('includes/crud.php');
    $function = new custom_functions;
    $settings = $function->get_configurations();
    $currency = $function->get_settings('currency');
    $db = new Database();
    $db->connect();
    $db->sql("SET NAMES 'utf8'");

    function verify_token()
    {
        $jwt = new JWT();
        try {
            $token = $_GET['token'];
        } catch (Exception $e) {
            $response['error'] = true;
            $response['message'] = $e->getMessage();
            print_r(json_encode($response));
            return false;
        }
        if (!empty($token)) {
            try {
                $payload = $jwt->decode($token, JWT_SECRET_KEY, ['HS256']);
                if (!isset($payload->iss) || $payload->iss != 'eKart') { ?>
                    <h2 class="text-center" style="margin-top: 20%;">Invalid Hash</h2>
                <?php return false;
                } else {
                    return true;
                }
            } catch (Exception $e) { ?>
                <h2 class="text-center" style="margin-top: 20%;">Signature verification failed</h2>
            <?php return false;
            }
        } else { ?>
            <h2 class="text-center" style="margin-top: 20%;">Unauthorized access not allowed</h2>
        <?php return false;
        }
    }

    if (!isset($_GET['id']) || empty($_GET['id']) && !isset($_GET['token']) || empty($_GET['token'])) { ?>
        <h2 class="text-center" style="margin-top: 20%;">Please Pass Order Id & Token</h2>
    <?php return false;
    } else {
        $ID = $_GET['id'];
        $sql = "SELECT * FROM orders WHERE id  =" . $ID;
        $db->sql($sql);
        $res = $db->getResult();
    }

    if (!verify_token()) {
        return false;
    }

    if (!empty($res)) {
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
        <section class="container-fluid">
            <section class="content-header">
                <h1>Invoice</h1>
            </section>

            <section class="content">
                <section class="invoice">
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
                                    Delivery By : &nbsp; <?= $res_outer[0]['delivery_boy']; ?>
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
                        <div class="col-sm-2 invoice-col">
                            Retail Invoice
                            <address>
                                <b>No : </b>#<?php echo $order_item_id; ?>
                            </address>
                            <address>
                                <b>Date: </b><?php echo date('d-m-Y h:i:s A', strtotime($res_outer[0]['date_added'])); ?>
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
                                <div class="row">
                                    <p><b>Product Details : </b></p>
                                    <br>
                                    <!-- <div class="row"> -->
                                    <div class="col-md-12 table-responsive">
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
                                    <!-- </div> -->
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="row col-md-6 col-md-offset-6">
                        <p><b>Payment Method : </b> <?= $res_outer[0]['payment_method'] ?></p>
                    </div>

                    <?php if ($res_outer[0]['discount'] > 0) {
                        $discount_in_rupees = ($res_outer[0]['discount'] / 100) * $res_outer[0]['total'];
                    } else {
                        $discount_in_rupees = 0;
                    } ?>
                    <div class="row">
                        <div class="col-md-6 col-md-offset-6">
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
                </section>
            </section>
        <?php
    } else { ?>
            <h1 class="text-center">Invalid Order Id</h1>
        <?php return false;
    } ?>
        </section>
</body>

</html>