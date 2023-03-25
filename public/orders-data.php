<?php
include_once('includes/variables.php');
include_once('includes/crud.php');
include_once('includes/custom-functions.php');
$function = new custom_functions();
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $ID = $db->escapeString($function->xss_clean($_GET['id']));
} else { ?>
    <script>
        alert("Something went wrong, No data available.");
        window.location.href = "orders.php";
    </script>
<?php
}
$currency = $function->get_settings('currency');

// create array variable to handle error
// print_r($permissions);
$update_order_permission = $permissions['ordes']['update'];
$allowed = ALLOW_MODIFICATION;
$seller_name = "";

$error = array();

// $shipping_type = ($function->get_settings('local_shipping') == 1) ? 'local' : 'standard';



?>



<section class="content-header">
    <h1>Order Detail</h1>
    <?php echo isset($error['update_data']) ? $error['update_data'] : ''; ?>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
</section>
<?php

$sql = "SELECT oi.*,oi.tax_amount as amount_tax,oi.tax_percentage as amount_percentage,o.final_total as payable_total,oi.id as order_item_id,v.product_id,v.measurement_unit_id,p.cancelable_status,p.id as product_id,p.pickup_location,p.standard_shipping,v.weight as weight,v.length as length,v.breadth as breadth, v.height as height,o.*,o.total as order_total,o.wallet_balance,oi.active_status as oi_active_status,u.email,u.name as uname,u.country_code FROM `order_items` oi JOIN users u ON u.id=oi.user_id LEFT JOIN product_variant v ON oi.product_variant_id=v.id LEFT JOIN products p ON p.id=v.product_id JOIN orders o ON o.id=oi.order_id WHERE o.id=$ID";
$db->sql($sql);
$res = $db->getResult();


if ($res[0]['address_id'] != 0) {
    $user_address = $function->get_data(['area', 'pincode', 'city'], 'id=' . $res[0]['address_id'], 'user_addresses');
}



$sql1 = "SELECT obt.* FROM order_bank_transfers obt WHERE obt.order_id = $ID";
$db->sql($sql1);
$res1 = $db->getResult();

$items = [];
// print_r($res);
if (isset($res[0]) && !empty($res[0])) {

    foreach ($res as $row) {
        $shipping_type = ($row['standard_shipping'] == 1) ? "standard" : "local";
        $data = array($row['product_id'], $row['product_variant_id'], $row['product_name'], $row['variant_name'], $row['measurement_unit_id'], $row['quantity'], $row['discounted_price'], $row['price'], $row['oi_active_status'], $row['cancelable_status'], $row['order_item_id'], $row['sub_total'], $row['amount_tax'], $row['amount_percentage'], $row['seller_id'], $row['delivery_boy_id'], $row['user_id'], $row['standard_shipping'], $row['pickup_location'], $row['weight']);
        array_push($items, $data);
    }
    // print_r($items);
    $count_standard_product = 1;



?>
    <style>
        @media (min-width: 992px) {
            .col-md-3 {
                width: 20% !important;

            }
        }

        .track {
            position: relative;
            background-color: #ddd;
            height: 7px;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            margin-bottom: 60px;
            margin-top: 50px
        }

        .track .step {
            -webkit-box-flex: 1;
            -ms-flex-positive: 1;
            flex-grow: 1;
            width: 25%;
            margin-top: -18px;
            text-align: center;
            position: relative
        }

        .track .step.active:before {
            background: #45b4ff;
        }

        .track .step::before {
            height: 7px;
            position: absolute;
            content: "";
            width: 100%;
            left: 0;
            top: 18px
        }

        .track .step.active .icon {
            background: #45b4ff;
            color: #fff
        }

        .track .icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            position: relative;
            border-radius: 100%;
            background: #ddd
        }

        .track i {
            width: 15px;
            padding-top: 11.5px;
        }

        .track .step.active .text {
            font-weight: 400;
            color: #000
        }

        .track .text {
            display: block;
            margin-top: 7px
        }
    </style>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <?php
                if ($permissions['orders']['read'] == 1) {

                    if ($permissions['orders']['update'] == 0) { ?>
                        <div class="alert alert-danger topmargin-sm">You have no permission to update orders.</div>
                    <?php }
                    ?>
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Order Detail</h3>
                        </div>
                        <!-- /.box-header -->
                        <div class="box-body">
                            <table class="table table-bordered">
                                <tr>
                                    <input type="hidden" name="hidden" id="order_id" value="<?php echo $res[0]['id']; ?>">
                                    <th style="width: 10px">ID</th>
                                    <td><?php echo $res[0]['id']; ?></td>
                                </tr>
                                <tr>
                                    <th style="width: 10px">Name</th>
                                    <td><?php echo $res[0]['uname']; ?></td>
                                </tr>
                                <tr>
                                    <th style="width: 10px">Email</th>
                                    <td><?php echo $res[0]['email']; ?></td>
                                </tr>

                                <tr>
                                    <th style="width: 10px">Contact</th>
                                    <td><?php echo $res[0]['mobile']; ?></td>
                                </tr>
                                <tr>
                                    <th style="width: 10px">O. Note</th>
                                    <td><?php echo $res[0]['order_note']; ?></td>
                                </tr>
                                <tr>
                                    <th style="width: 10px">Area</th>
                                    <?php
                                    if (!empty($res[0]['area_id'])) {
                                        $area_id = $res[0]['area_id'];
                                        $sql = "SELECT * FROM `area` WHERE id =$area_id";
                                        $db->sql($sql);
                                        $res_areas = $db->getResult();
                                    } else {
                                        $res_areas = $user_address[0]['area'];
                                    }
                                    ?>
                                    <td><?= (!empty($res_areas[0]['name'])) ? $res_areas[0]['name'] : $res_areas ?></td>
                                </tr>
                                <tr>
                                    <th style="width: 10px">Pincode</th>
                                    <?php
                                    $pincode_id = $res[0]['pincode_id'];
                                    $sql = "SELECT * FROM `pincodes` WHERE id =$pincode_id";
                                    $db->sql($sql);
                                    $res_pincodes = $db->getResult();
                                    ?>
                                    <td><?= (!empty($res_pincodes)) ? $res_pincodes[0]['pincode'] :  $user_address[0]['pincode']; ?></td>
                                </tr>
                                <tr>
                                    <th style="width: 10px">OTP</th>
                                    <td><?= (isset($res[0]['otp']) && !empty($res[0]['otp'])) ? $res[0]['otp'] : "-" ?></td>
                                </tr>
                                <?php
                                // $sql = "SELECT id,name FROM delivery_boys WHERE status=1";
                                $sql = "SELECT id,name,pincode_id,is_available FROM delivery_boys WHERE status=1 and FIND_IN_SET($pincode_id, pincode_id) ";
                                $db->sql($sql);
                                $result = $db->getResult();
                                ?>
                                <tr>
                                    <th>Items</th>
                                    <td>
                                        <?php if ($shipping_type == 'standard') {
                                            if ($count_standard_product != 0) {
                                        ?>

                                                <div class="col-md-12">
                                                    <h4>Standard Shipping Order items</h4><small><a data-toggle="modal" data-target="#howtomanage"> How to manage shiprocket order ?</a></small>
                                                    <div class="modal  fade" id="howtomanage" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="exampleModalLabel">How to manage shiprocket order </h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="container">
                                                                        <div class="row">
                                                                            <div class="col-md-12">
                                                                                <h5><b>Create shiprocket order</b></h5>
                                                                                <b>Steps:</b><br>
                                                                                1.Select order items which you have to add in parcel and click on create order button<br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/create-ordeer.png" alt="" style="max-width:75%;">
                                                                                <br>
                                                                                2.After create order generate AWB code(its unique number use for identify order) like this<br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/awb.png" alt="" style="max-width:75%;">
                                                                                <br>
                                                                                3.Send request for pickup <br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/send-pickup-request.png" alt="" style="max-width:75%;">
                                                                                <br>
                                                                                4.Track order <br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/trackin.png" alt="" style="max-width:75%;">
                                                                                <br>
                                                                                5.Cancel order <br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/cancel order.png" alt="" style="max-width:75%;">
                                                                                <br>
                                                                                6.generate manifest and download <br>
                                                                                <br>
                                                                                <img src="documentation/assets/img/manifest&label.png" alt="" style="max-width:75%;">
                                                                                <img src="documentation/assets/img/download-manifest-label.png" alt="" style="max-width:75%;">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Standard Shipping Pending Creating orders Order items </h5>
                                <div id="result-shiprocket"></div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" disabled class="btn btn-primary create-shiprocket" data-toggle="modal" data-target="#exampleModal">
                                    Create Shipprocket Order
                                </button>
                            </div>
                        </div>
                        <?php $total = 0;
                                                $min_weight = $min_height = $min_breadth = $min_lenght = 0;
                                                foreach ($items as $item) {
                                                    if ($item[17] == 1) {
                                                        $sql = 'Select order_item_id from order_trackings where order_item_id=' . $item[10];
                                                        $db->sql($sql);
                                                        if (empty($db->getResult())) {
                                                            $min_weight = ($res[$i]['weight'] > $min_weight) ? $res[$i]['weight'] : $min_weight;
                                                            $min_height = ($res[$i]['height'] > $min_weight) ? $res[$i]['height'] : $min_height;
                                                            $min_breadth = ($res[$i]['breadth'] > $min_weight) ? $res[$i]['breadth'] : $min_breadth;
                                                            $min_lenght = ($res[$i]['length'] > $min_weight) ? $res[$i]['length'] : $min_lenght;
                                                            $active_status = '<label class="label label-primary">Order Not Created</label>'; ?>
                                    <div class="card col-md-3">
                                        <div class="card-body">
                                            <?php
                                                            $array[] = $item[8];
                                                            if (!empty($item[14])) {
                                                                $s_id = $item[14];
                                                                $db->sql("SET NAMES 'utf8'");
                                                                $sql = "SELECT `name` FROM `seller` where id= $s_id ORDER BY id DESC";
                                                                $db->sql($sql);
                                                                $seller_name = $db->getResult();
                                                                $seller_name = (!empty($seller_name)) ? $seller_name[0]['name'] : "Not Assigned";
                                                                $sql = "SELECT `name` FROM `delivery_boys` where id= " . $item[15] . "";
                                                                $db->sql($sql);
                                                                $delivery_boy_name = $db->getResult();
                                                                $delivery_boy_name = isset($delivery_boy_name[0]['name']) && (!empty($delivery_boy_name[0]['name'])) ?  $delivery_boy_name[0]['name'] : "Not assigned";
                                                                $sql = "SELECT `name` FROM `users` where id= " . $item[16] . "";
                                                                $db->sql($sql);
                                                                $user_name = $db->getResult();
                                                                $user_name = isset($user_name[0]['name']) && (!empty($user_name[0]['name'])) ?  $user_name[0]['name'] : "";
                                                            }
                                                            $view_product = "";
                                                            $is_product = $function->get_data($column = ['id'], 'id=' . $item[1], 'product_variant');
                                                            $is_product = isset($is_product[0]) && !empty($is_product[0]) ? 1 : 0;
                                                            if ($is_product == 1) {
                                                                $view_product = " <a href='" . DOMAIN_URL . "/view-product-variants.php?id=" . $item[0] . "' class='btn btn-success btn-xs' title='View Product'><i class='fa fa-eye'></i></a>";
                                                            }
                                                            // echo $view_product;
                                                            $total += $subtotal = ($item[6] != 0 && $item[6] < $item[7]) ? ($item[6] * $item[5]) : ($item[7] * $item[5]);
                                                            echo "<br><input type='checkbox' data-qty='$item[5]' data-order-item-id=" . $item[10] . " data-product-weight=" . $item[19] . " data-pickup-location=" . $item[18] . "  data-sub-total=" . $item[11] . " name='order_items[]' class='seller_id' data-seller-id=" . $s_id . " value=" . $item[10] . ">" . "</br>";
                                                            echo  "</br>" . $active_status . "<br><br><b>Order Item Id : </b>" . $item[10] . "<br><b>D.boy : </b>" . $delivery_boy_name . "</br><b>Product Id : </b>" . $item[0] . $view_product . "</br>";
                                                            if (!empty($seller_name)) {
                                                                echo " <b>Seller : </b>" . $seller_name . "</br>";
                                                            }
                                                            if (!empty($user_name)) {
                                                                echo " <b>User Name : </b>" . $user_name . "</br>";
                                                            }
                                                            echo "<b>Variant Id : </b>" . $item[1] . "</br>";
                                                            echo " <b>Name : </b>" . $item[2] . "(" . $item[3] . ")</br>";
                                                            echo " <b>Quantity : </b>" . $item[5] . "</br>";
                                                            echo " <b>Price(" . $currency . ") : </b>" . $item[7] . "</br>";
                                                            echo " <b>Discounted Price(" . $currency . ") : </b>" . $item[6] . "</br>";
                                                            echo " <b>Tax Amount(" . $currency . ") : </b>" . $item[12] . "</br>";
                                                            echo " <b>Tax Percentage(%) : </b>" . $item[13] . "</br>";
                                                            echo " <b>Subtotal(" . $currency . ") : </b>" . $item[11] . "  ";
                                            ?>
                                            <div class="clearfix">
                                                <?php $whatsapp_message = "Hello " . ucwords($res[0]['uname']) . ", Your order with ID : " . $res[0]['id'] . " is " . ucwords($item[8]) . ". Please take a note of it. If you have further queries feel free to contact us. Thank you."; ?>
                                                <a class=" col-sm-12 btn btn-success" href="https://api.whatsapp.com/send?phone=<?= '+' . $res[0]['country_code'] . ' ' . $res[0]['mobile']; ?>&text=<?= $whatsapp_message; ?>" target='_blank' title="Send Whatsapp Notification"><i class="fa fa-whatsapp"></i></a>

                                            </div><br>
                                        </div>
                                    </div>


                        <?php          }
                                                    }
                                                }
                        ?><br>
                    </div>
                    <h5>Standard Shipping Order items</h5>
                    <?php

                                                // print_r($res);
                                                $total = 0;

                                                $min_weight = $min_height = $min_breadth = $min_lenght = 0;
                                                for ($i = 0; $i < count($res); $i++) {
                                                    foreach ($items as $item) {


                                                        if ($res[$i]['standard_shipping']) {
                                                            $sql = 'Select order_item_id,shipment_id,awb_code,is_canceled,courier_company_id,pickup_status,manifests,labels from order_trackings where order_item_id=' . $item[10];
                                                            $db->sql($sql);
                                                            $shiprocket_order = $db->getResult();

                                                            if (!empty($shiprocket_order)) {
                                                                $min_weight = ($res[$i]['weight'] > $min_weight) ? $res[$i]['weight'] : $min_weight;
                                                                $min_height = ($res[$i]['height'] > $min_weight) ? $res[$i]['height'] : $min_height;
                                                                $min_breadth = ($res[$i]['breadth'] > $min_weight) ? $res[$i]['breadth'] : $min_breadth;
                                                                $min_lenght = ($res[$i]['length'] > $min_weight) ? $res[$i]['length'] : $min_lenght;
                                                                $active_status = '<label class="label label-success">Order Created <i class="fa fa-check-square"></i></label>';
                                                                if ($shiprocket_order[0]['pickup_status'] == 0) {

                                                                    if (empty($shiprocket_order[0]['awb_code']) or is_null($shiprocket_order[0]['awb_code'])) {
                                                                        $send_pickup_request = " <a href='#/' data-seller-id='$s_id'   data-shipment-id=" . $shiprocket_order[0]['shipment_id'] . " class='btn btn-primary btn-sm generate_awb_code'  title='generate AWB code'><i class=''></i>AWB</a>";
                                                                    } else {
                                                                        $send_pickup_request = " <a href='#/' data-seller-id='$s_id'   data-shipment-id=" . $shiprocket_order[0]['shipment_id'] . " class='btn btn-primary btn-sm send-pickup-request'  title='send order pickup request'><i class='fa fa-chevron-circle-up'></i></a>";
                                                                    }
                                                                } elseif ($shiprocket_order[0]['is_canceled'] == 1) {
                                                                    $send_pickup_request = " <a href='#/'   class='btn btn-primary btn-sm ' title='this order is canceled'><i class='fa fa-ban' aria-hidden='true'></i></a>";
                                                                } else {
                                                                    $send_pickup_request = " <a href='#/' data-awb-code=" . $shiprocket_order[0]['awb_code'] . " data-seller-id='$s_id' data-currier-id=" . $shiprocket_order[0]['courier_company_id'] . "  data-toggle='modal' data-target='#track_order' data-shipment-id=" . $shiprocket_order[0]['shipment_id'] . " class='btn btn-primary btn-sm track-order' title='track order'><i class='fa fa-eye' aria-hidden='true'></i></a>  <a href='#/' data-seller-id='$s_id' data-currier-id=" . $shiprocket_order[0]['courier_company_id'] . "   data-shipment-id=" . $shiprocket_order[0]['shipment_id'] . " class='btn btn-primary btn-sm cancel-order' title='cancel order'><i class='fa fa-repeat' aria-hidden='true'></i></a>";
                                                                    $manifest = "";
                                                                    if (!is_null($shiprocket_order[0]['manifests'])) {
                                                                        $manifest = json_decode($shiprocket_order[0]['manifests'], true);
                                                                        $manifest_url = $manifest['manifest_url'];
                                                                        // echo "bhai bhai km chho ";
                                                                        $send_pickup_request .= " <a href='$manifest_url' data-shipment-id=" . $shiprocket_order[0]['shipment_id'] . " class='btn nav-link btn-primary btn-sm'  title='download manifest' target='_blank'> <i class='fa fa-download' >  <small>Manifest</small></i></a>";
                                                                    } else {
                                                                        $send_pickup_request .= " <a href='#/'data-shipment-id=" . $shiprocket_order[0]['shipment_id'] . " class='btn btn-primary btn-sm generate-manifests'  title='generate manifest'> <i class='fa fa-sticky-note-o'></i></a>";
                                                                    }
                                                                    if (!is_null($shiprocket_order[0]['labels'])) {
                                                                        $labels = json_decode($shiprocket_order[0]['labels'], true);
                                                                        $label_url = $labels['label_url'];
                                                                        // echo "bhai bhai km chho ";
                                                                        $send_pickup_request .= " <a href='$label_url' data-shipment-id=" . $shiprocket_order[0]['shipment_id'] . " class='btn nav-link btn-primary btn-sm'  title='download labels' target='_blank'> <i class='fa fa-download' >  <small>label</small></i></a>";
                                                                    } else {
                                                                        $send_pickup_request .= " <a href='#/'data-shipment-id=" . $shiprocket_order[0]['shipment_id'] . " class='btn btn-primary btn-sm generate-labels'  title='generate labels'> <i class='fa fa-tag'></i></a>";
                                                                    }
                                                                }

                    ?>
                                    <div class="card col-md-3">
                                        <div class="card-body">
                                            <?php

                                                                
                                                                $array[] = $item[8];
                                                                if (!empty($item[14])) {
                                                                    $s_id = $item[14];
                                                                    $db->sql("SET NAMES 'utf8'");
                                                                    $sql = "SELECT `name` FROM `seller` where id= $s_id ORDER BY id DESC";
                                                                    $db->sql($sql);
                                                                    $seller_name = $db->getResult();
                                                                    $seller_name = (!empty($seller_name)) ? $seller_name[0]['name'] : "Not Assigned";
                                                                    $sql = "SELECT `name` FROM `delivery_boys` where id= " . $item[15] . "";
                                                                    $db->sql($sql);
                                                                    $delivery_boy_name = $db->getResult();
                                                                    $delivery_boy_name = isset($delivery_boy_name[0]['name']) && (!empty($delivery_boy_name[0]['name'])) ?  $delivery_boy_name[0]['name'] : "Not assigned";
                                                                    $sql = "SELECT `name` FROM `users` where id= " . $item[16] . "";
                                                                    $db->sql($sql);
                                                                    $user_name = $db->getResult();
                                                                    $user_name = isset($user_name[0]['name']) && (!empty($user_name[0]['name'])) ?  $user_name[0]['name'] : "";
                                                                }
                                                                $view_product = "";
                                                                $is_product = $function->get_data($column = ['id'], 'id=' . $item[1], 'product_variant');
                                                                $is_product = isset($is_product[0]) && !empty($is_product[0]) ? 1 : 0;
                                                                if ($is_product == 1) {
                                                                    $view_product = " <a href='" . DOMAIN_URL . "/view-product-variants.php?id=" . $item[0] . "' class='btn btn-success btn-xs' title='View Product'><i class='fa fa-eye'></i></a>";
                                                                }
                                                                // echo $view_product;
                                                                $total += $subtotal = ($item[6] != 0 && $item[6] < $item[7]) ? ($item[6] * $item[5]) : ($item[7] * $item[5]);
                                                                echo  "</br>" . $active_status . "" . $send_pickup_request . "<br><br><b>Order Item Id : </b>" . $item[10] . "<br><b>D.boy : </b>" . $delivery_boy_name . "</br><b>Product Id : </b>" . $item[0] . $view_product . "</br>";
                                                                if (!empty($seller_name)) {
                                                                    echo " <b>Seller : </b>" . $seller_name . "</br>";
                                                                }
                                                                if (!empty($user_name)) {
                                                                    echo " <b>User Name : </b>" . $user_name . "</br>";
                                                                }
                                                                echo "<b>Variant Id : </b>" . $item[1] . "</br>";
                                                                echo " <b>Name : </b>" . $item[2] . "(" . $item[3] . ")</br>";
                                                                echo " <b>shipment id : </b>" . $shiprocket_order[0]['shipment_id'] . "</br>";
                                                                echo " <b>AWB code : </b>" . $shiprocket_order[0]['awb_code'] . "</br>";
                                                                echo " <b>Quantity : </b>" . $item[5] . "</br>";
                                                                echo " <b>Price(" . $currency . ") : </b>" . $item[7] . "</br>";
                                                                echo " <b>Discounted Price(" . $currency . ") : </b>" . $item[6] . "</br>";
                                                                echo " <b>Tax Amount(" . $currency . ") : </b>" . $item[12] . "</br>";
                                                                echo " <b>Tax Percentage(%) : </b>" . $item[13] . "</br>";
                                                                echo " <b>Subtotal(" . $currency . ") : </b>" . $item[11] . "  ";
                                            ?>


                                            <div class="clearfix">
                                                <?php $whatsapp_message = "Hello " . ucwords($res[0]['uname']) . ", Your order with ID : " . $res[0]['id'] . " is " . ucwords($item[8]) . ". Please take a note of it. If you have further queries feel free to contact us. Thank you."; ?>
                                                <a class=" col-sm-12 btn btn-success" href="https://api.whatsapp.com/send?phone=<?= '+' . $res[0]['country_code'] . ' ' . $res[0]['mobile']; ?>&text=<?= $whatsapp_message; ?>" target='_blank' title="Send Whatsapp Notification"><i class="fa fa-whatsapp"></i></a>

                                            </div><br>
                                        </div>
                                    </div>

                <?php  }
                                                        }
                                                    }
                                                    break;
                                                }
                                            } else {

                                                echo "<h1 class='label label-sm  label-info' style='font-size:13px;'>Sorry you have not any standard shipping order</h1>";
                                            } ?><br>
            <?php } ?>
            </div>
            <?php if ($shipping_type == 'local') { ?>

                <form id="update_form">
                    <input type="hidden" name="update_order_items" value="1">
                    <input type="hidden" name="accesskey" value="90336">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12  mb-5">

                                <lable class="badge badge-primary">Select status, delivery boy and square box of item which you want to update</lable>
                                <?php echo (empty($result)) ? "<div class='alert alert-danger'> you have not any delivery boy at this address</div>" : "" ?>
                            </div>
                            <div class="col-md-12  mb-5">
                                <div id="save_result"></div>
                            </div>
                            <div class="col-md-4">
                                <select name="status" id="status" class="form-control status" required>
                                    <option value=''>Select Status</option>
                                    <option value="awaiting_payment">Awaiting Payment</option>
                                    <option value="received">Received</option>
                                    <option value="processed">Processed</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancel</option>
                                    <option value="returned">Returned</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name='delivery_boy_id' class='form-control deliver_by' required>
                                    <option value=''>Select Delivery Boy</option>
                                    <?php if (isset($result[0]) && !empty($result[0])) {
                                        foreach ($result as $row1) {
                                            $pending_orders = $function->rows_count('order_items', 'distinct(order_id)', 'delivery_boy_id=' . $row1['id'] . ' and active_status != "cancelled" and active_status != "returned" and active_status != "delivered"');
                                            $disabled = $row1['is_available'] == 0 ? 'disabled' : '';
                                            if ($items[15] == $row1['id']) { ?>
                                                <option value='<?= $row1['id'] ?>'><?= $row1['name'] . ' - ' .  $pending_orders ?> - Pending Orders</option>
                                            <?php } else { ?>
                                                <option value='<?= $row1['id'] ?>' <?= $disabled ?>><?= $row1['name'] . ' - ' .  $pending_orders ?> - Pending Orders</option>
                                    <?php }
                                        }
                                    } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <a href="#" title='update' id="submit_btn" class="btn btn-primary col-sm-12 col-md-12 update_order_items">Bulk Update</a>
                            </div>
                        </div>

                        <?php $total = 0;
                        //   for ($j=0;$j<count($res); $j++) { 
                        foreach ($items as $item) {
                            if ($item[17] == 0) {
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
                                        if (!empty($item[14])) {
                                            $s_id = $item[14];
                                            $db->sql("SET NAMES 'utf8'");
                                            $sql = "SELECT `name` FROM `seller` where id= $s_id ORDER BY id DESC";
                                            $db->sql($sql);
                                            $seller_name = $db->getResult();
                                            $seller_name = (!empty($seller_name)) ? $seller_name[0]['name'] : "Not Assigned";
                                            $sql = "SELECT `name` FROM `delivery_boys` where id= " . $item[15] . "";
                                            $db->sql($sql);
                                            $delivery_boy_name = $db->getResult();
                                            $delivery_boy_name = isset($delivery_boy_name[0]['name']) && (!empty($delivery_boy_name[0]['name'])) ?  $delivery_boy_name[0]['name'] : "Not assigned";
                                            $sql = "SELECT `name` FROM `users` where id= " . $item[16] . "";
                                            $db->sql($sql);
                                            $user_name = $db->getResult();
                                            $user_name = isset($user_name[0]['name']) && (!empty($user_name[0]['name'])) ?  $user_name[0]['name'] : "";
                                        }
                                        $view_product = "";
                                        $is_product = $function->get_data($column = ['id'], 'id=' . $item[1], 'product_variant');
                                        $is_product = isset($is_product[0]) && !empty($is_product[0]) ? 1 : 0;
                                        if ($is_product == 1) {
                                            $view_product = " <a href='" . DOMAIN_URL . "/view-product-variants.php?id=" . $item[0] . "' class='btn btn-success btn-xs' title='View Product'><i class='fa fa-eye'></i></a>";
                                        }
                                        // echo $view_product;
                                        $total += $subtotal = ($item[6] != 0 && $item[6] < $item[7]) ? ($item[6] * $item[5]) : ($item[7] * $item[5]);
                                        echo "<br><input type='checkbox' name='order_items[]' value=" . $item[10] . ">" . "</br>";
                                        echo  "</br>" . $active_status . "<br><br><b>Order Item Id : </b>" . $item[10] . "<br><b>D.boy : </b>" . $delivery_boy_name . "</br><b>Product Id : </b>" . $item[0] . $view_product . "</br>";
                                        if (!empty($seller_name)) {
                                            echo " <b>Seller : </b>" . $seller_name . "</br>";
                                        }
                                        if (!empty($user_name)) {
                                            echo " <b>User Name : </b>" . $user_name . "</br>";
                                        }
                                        echo "<b>Variant Id : </b>" . $item[1] . "</br>";
                                        echo " <b>Name : </b>" . $item[2] . "(" . $item[3] . ")</br>";
                                        echo " <b>Quantity : </b>" . $item[5] . "</br>";
                                        echo " <b>Price(" . $currency . ") : </b>" . $item[7] . "</br>";
                                        echo " <b>Discounted Price(" . $currency . ") : </b>" . $item[6] . "</br>";
                                        echo " <b>Tax Amount(" . $currency . ") : </b>" . $item[12] . "</br>";
                                        echo " <b>Tax Percentage(%) : </b>" . $item[13] . "</br>";
                                        echo " <b>Subtotal(" . $currency . ") : </b>" . $item[11] . "  ";
                                        ?>


                                        <div class="clearfix">
                                            <?php $whatsapp_message = "Hello " . ucwords($res[0]['uname']) . ", Your order with ID : " . $res[0]['id'] . " is " . ucwords($item[8]) . ". Please take a note of it. If you have further queries feel free to contact us. Thank you."; ?>
                                            <a class=" col-sm-12 btn btn-success" href="https://api.whatsapp.com/send?phone=<?= '+' . $res[0]['country_code'] . ' ' . $res[0]['mobile']; ?>&text=<?= $whatsapp_message; ?>" target='_blank' title="Send Whatsapp Notification"><i class="fa fa-whatsapp"></i></a>

                                        </div><br>
                                    </div>
                                </div>

                        <?php


                            }
                        }
                        ?><br>


                        <!-- <div class="col-md-4"> -->

                        <div class="mt-5" id="save_result_bottom"></div>
                        <!-- </div> -->
                    </div>
                </form>
            <?php } ?>
            </td>
            </tr>
            <tr>
                <th style="width: 10px">Total (<?= $settings['currency'] ?>)</th>
                <td><?php echo $res[0]['order_total']; ?></td>
            </tr>
            <tr>
                <th style="width: 10px">D.Charge (<?= $settings['currency'] ?>)</th>
                <td><?php echo $res[0]['delivery_charge']; ?></td>

            </tr>

            <?php if ($res[0]['discount'] > 0) {
                        $discounted_amount = $res[0]['total'] * $res[0]['discount'] / 100; /*  */
                        $final_total = $res[0]['total'] - $discounted_amount;
                        $discount_in_rupees = $res[0]['total'] - $final_total;
                        $discount_in_rupees = $discount_in_rupees;
                    } else {
                        $discount_in_rupees = 0;
                    } ?>
            <tr>
                <th style="width: 10px">Disc. <?= $settings['currency'] ?>(%)</th>
                <td><?php echo  $discount_in_rupees . '(' . round($res[0]['discount'], 2) . '%)'; ?></td>
            </tr>

            <tr>
                <th style="width: 10px">Promo Disc. (<?= $settings['currency'] ?>)</th>
                <td><?php echo $res[0]['promo_discount']; ?></td>
            </tr>
            <tr>
                <th style="width: 10px">Wallet Used</th>
                <td><?php echo $res[0]['wallet_balance']; ?></td>
            </tr>
            <input type="hidden" name="total_amount" id="total_amount" value="<?php echo $res[0]['payable_total']; ?>">
            <tr>
                <th style="width: 10px">Discount %</th>
                <td><input type="number" class="form-control" id="input_discount" name="input_discount" value="<?php echo $res[0]['discount']; ?>" min=0 max=100></td>
                <td><a href="#" title='save_discount' class="btn btn-primary form-control update_order_total_payable" data-id='<?= $row['id']; ?>'>Save</a></td>
            </tr>
            <tr>
                <th style="width: 10px">Payable Total(<?= $settings['currency'] ?>)</th>
                <td><input type="number" class="form-control" id="final_total" name="final_total" value="<?= $res[0]['payable_total']; ?>" disabled></td>
            </tr>
            <tr>
                <th style="width: 10px">Payment Method</th>
                <td><?php echo $res[0]['payment_method']; ?></td>
            </tr>
            <?php if (!empty($res1)) { ?>
                <tr>
                    <th style="width: 10px">Bank Transfer</th>
                    <td>
                        <?php for ($i = 0; $i < count($res1); $i++) { ?>
                            <img src="<?php echo $res1[$i]['attachment']; ?>" height="100" />
                            <a class='btn btn-xs btn-danger delete-image' data-i='<?= $res1[$i]['id']; ?>' data-pid='<?= $ID; ?>'>Delete</a>
                        <?php } ?>
                    </td>
                    <td>
                        <?php
                        if ($shipping_type == 'standard') {
                            echo ($count_standard_product != 0) ? '<button type="submit" class="btn btn-xl btn-primary" id="edit_bank_transfer" data-toggle="modal" data-target="#editBankTransferModal" title="Edit"><i class="fa fa-pencil-square-o"></i></button>' : '';
                        } else {
                            echo '<button type="submit" class="btn btn-xl btn-primary" id="edit_bank_transfer" data-toggle="modal" data-target="#editBankTransferModal" title="Edit"><i class="fa fa-pencil-square-o"></i></button>';
                        } ?>
                    </td>
                </tr>
            <?php } ?>
            <tr>
                <th style="width: 10px">Promo Code</th>
                <td><?= (!empty($res[0]['promo_code']) || $res[0]['promo_code'] != null) ? $res[0]['promo_code'] : ""; ?></td>
            </tr>
            <tr>
                <th style="width: 10px">Address</th>
                <!-- <td><?php echo $res[0]['address']; ?></td> -->
                <td><?php
                    $address = explode(',', $res[0]['address']);
                    // print_r($address);
                    echo (empty($user_address[0]['pincode'])) ? $address[0] . ' ' . $address[1] . ', ' . $address[2] . ' ' . $address[3] . ' ' . $address[4] . ', ' . $address[5] . ', ' . $address[6] . "" . $address[7] : $address[0] . ' ' . $address[1] . ', ' . $address[2] . ' ' . $address[3] . ' ' . $address[4] . ', ' . $address[5] . ', ' . $address[6] . "" . $user_address[0]['pincode'];

                    ?></td>
            </tr>
            <tr>
                <th style="width: 10px">Order Date</th>
                <td><?php echo date('d-m-Y h:i:s A', strtotime($row['date_added'])); ?></td>
            </tr>
            <tr>
                <th style="width: 10px">Delivery Time</th>
                <td><?php echo $res[0]['delivery_time']; ?></td>
            </tr>
            </table>
            <div class="box-footer clearfix">
                <?php
                    $check_array = array("awaiting_payment", "cancelled", "returned");
                    $result1 = array_diff($array, $check_array);
                    if (!empty($result1)) { ?>
                    <button class="btn btn-primary pull-right" onclick="myfunction()"><i class="fa fa-download"></i>Generate Invoice</button>
                <?php } else { ?>
                    <button class="btn btn-primary disabled pull-right"><i class="fa fa-download"></i> Generate Invoice</button>
                <?php } ?>
            </div>
        </div>
        </div>
        <div class="modal fade" id='editBankTransferModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Update Bank Transfer</h4>
                    </div>

                    <div class="modal-body">
                        <div class="box-body">
                            <form id="update_status_form" method="POST" action="public/db-operation.php" data-parsley-validate class="form-horizontal form-label-left">
                                <input type='hidden' name="order_id" id="order_id" value="<?= $ID ?>" />
                                <input type='hidden' name="update_bank_transfer" id="update_bank_transfer" value='1' />

                                <div class="form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Status</label>
                                    <div class="col-md-7 col-sm-6 col-xs-12">
                                        <div id="status" class="btn-group">
                                            <label class="btn btn-warning" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="status" value="0" <?php echo ($res1[0]['status'] == 0) ? 'checked' : '' ?>> Pending
                                            </label>
                                            <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="status" value="1" <?php echo ($res1[0]['status'] == 1) ? 'checked' : '' ?>> Accepted
                                            </label>
                                            <label class="btn btn-danger" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="status" value="2" <?php echo ($res1[0]['status'] == 2) ? 'checked' : '' ?>> Rejected
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="" for="">Message</label>
                                    <textarea id="message" name="message" class="form-control col-md-7 col-xs-12" style=" min-width:500px; max-width:100%;min-height:100px;height:100%;width:100%;"><?= $res1[0]['message'] ?></textarea>
                                </div>
                                <div class="ln_solid"></div>
                                <div class="form-group">
                                    <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-9">
                                        <button type="submit" id="update_btn" class="btn btn-primary">Update</button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-8" style="display:none;" id="update_result"></div>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="alert alert-danger">You have no permission to view orders</div>
    <?php }  ?>
    <!-- /.box -->
    </div>

    </div>
    </section>
    <div class="modal fade " id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="" method="post" id="create_order_form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Create Shipprocket Order Parcel</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-4 mt-5">
                                    <label for="" class="">seller Pickup location: </label>
                                </div>
                                <div class="col-md-8">

                                    <input type="text" name="seller_pickup_location" class="form-control" value="" id="pickup-location" readonly="readonly">

                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="order_id" value="<?= $_GET['id'] ?>">
                        <div id="create_order_result">

                        </div>
                        <input type="hidden" name="order_item_ids[]" id="order_item_ids" value="'">
                        <label for="">Total Weight of Boox</label>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">Weight </label><small> Kg</small>
                                <input type="number" name="weight" required class="form-control weight" placeholder="enter weight of parcel" id="">
                            </div>
                            <div class="col-md-3">
                                <label for="">Height </label><small> cms</small>
                                <input type="number" name="hieght" required class="form-control" placeholder="enter weight of parcel" id="">
                            </div>
                            <div class="col-md-3">
                                <label for="">Breadth </label><small> cms</small>
                                <input type="number" name="breadth" required class="form-control" placeholder="enter weight of parcel" id="">
                            </div>

                            <div class="col-md-3">
                                <label for="">Length</label><small> cms</small>
                                <input type="number" name="length" required class="form-control" placeholder="enter weight of parcel" id="">
                            </div>
                        </div>
                        <label for="" class="parcel_error text-danger"></label>

                    </div>
                    <input name="subtotal" type="hidden" id="subtotal">
                    <input type="hidden" name="create_order_btn" value="1">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="close" data-dismiss="modal">Close</button>
                        <button type="submit" id="create_order_btn" class="btn btn-success create_order_btn">Create orders</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <style>

    </style>

    <div class="modal fade bd-example-modal-lg" id="track_order" tabindex="-1" role="dialog" aria-labelledby="track_order" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Trak Order</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Shipment ID: <label id="show_shipment_id"></label></h6>
                        </div>
                        <div class="col-md-6">
                            <h6 class="current_status"></h6>
                        </div>
                    </div>


                    <div class="track">
                        <div class="step  active"> <span class="icon"> <i class="fa fa-check"></i> </span> <span class="text">Request Pickup Sended</span> </div>
                        <div class="step  pickuped"> <span class="icon"> <i class="fa fa-user"></i> </span> <span class="text">Pickuped</span> </div>
                        <div class="step  on-the-way"> <span class="icon"> <i class="fa fa-truck"></i> </span> <span class="text"> On the way </span> </div>
                        <div class="step delivered"> <span class="icon"><i class="fa fa-check-square" aria-hidden="true" style="color:white;"></i></span> <span class="text">Delivered successfuly</span> </div>
                    </div>
                    <hr>


                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-md-4 pt-1 shiprocket-link"></div>
                        <div class="col-md-8"> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } else { ?>
    <div class="alert alert-danger">Something went wrong</div>
<?php } ?>
<script src="https://code.jquery.com/jquery-1.11.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.16.0/jquery.validate.min.js"></script>
<script>
    var selected_pickup_location = 0;
    $('.seller_id').on('click', function() {


        var seller_id = $(this).data('seller-id');
        var pickup_location = $(this).data('pickup-location');


        var all = $('.seller_id');
        if ($(this).is(':checked')) {
            $('.create-shiprocket').attr('disabled', false);
            for (var i = 0; i < all.length; i++) {
                if ($(all[i]).data('seller-id') == seller_id && $(all[i]).data('pickup-location') == pickup_location) {
                    selected_pickup_location = pickup_location;
                    if ($(all[i]).is(':checked')) {
                        $(all[i]).addClass('checked')
                    }
                    $(all[i]).attr("disabled", false)
                } else {
                    $(all[i]).attr("disabled", true)
                }
            }
        } else {
            for (var i = 0; i < all.length; i++) {
                if ($(all[i]).is(':checked')) {
                    $(all[i]).removeClass("checked")

                    $(all[i]).attr("disabled", false)
                } else {

                    $(all[i]).removeClass("checked")

                    $(all[i]).attr("disabled", false)
                }

            }
        }
    });



    var weight = 0;
    $('.create-shiprocket').on('click', function() {
        weight = 0;
        var all = $('.checked');
        var temparr = [];
        var sub_total = 0;
        for (var i = 0; i < all.length; i++) {
            if ($(all[i]).is(':checked')) {
                var seller_id = $(all[i]).data('seller-id');
                $('#create_order_result').html('<input type="hidden" name="select_seller_id" id="create_order_seller_id" value="' + seller_id + '">')
                // temparr[$(all[i]).data('product-id')] = $(all[i]).data('product-name');
                if (seller_id != "") {
                    temparr = [...temparr, $(all[i]).data('order-item-id')]
                    sub_total += parseFloat($(all[i]).data('sub-total'))
                    weight += parseFloat($(all[i]).data('product-weight')) * $(all[i]).data('qty');
                }
            }
        }

        $('#pickup-location').val(selected_pickup_location)
        $('#order_item_ids').attr('value', temparr);
        $('#subtotal').attr('value', sub_total);
        $('.weight').attr('value', weight);
    });

    console.log(weight);
    $('.weight').keyup(function() {
        if ($('.weight').val() < weight) {
            $('.create_order_btn').attr('disabled', true);
            $('.parcel_error').html('Sorry you cannot set weight of parsel less then total of products weight')
        } else {
            $('.create_order_btn').attr('disabled', false);
        }
    });

    $('.weight').on('change', function() {
        if ($('.weight').val() < weight) {
            $('.create_order_btn').attr('disabled', true);
            $('.parcel_error').html('Sorry you cannot set weight of parsel less then total of weight products ')
        } else {
            $('.create_order_btn').attr('disabled', false);
            $('.parcel_error').html('')
        }
    });



    $('#create_order_form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(document.getElementById("create_order_form"));
        $.ajax({
            type: 'POST',
            url: "public/db-operation.php",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            beforeSend: function() {
                $('.create_order_btn').html('Please Wait....').attr('disabled', true);
            },
            error: function(request, error) {
                console.log(request)
            },
            success: function(data) {
                if (data.error == false) {
                    $('#close').trigger('click');
                    $('.create_order_btn').html('Create Order').attr('disabled', false);
                    $('#result-shiprocket').html('<h4><label class="label m-5 label-success">' + data.message + '</label></h4>')
                    location.reload();
                } else {
                    $('#close').trigger('click');
                    $('.create_order_btn').html('Create Order').attr('disabled', false);
                    $('#result-shiprocket').html('<h4><label class="label m-5 label-danger">' + data.message + '</label></h4>')
                    data.data.forEach(Element => {
                        $('#result-shiprocket').append('<label class="label m-5 label-danger">' + Element + '</label>')

                    });
                }
            }
        });

    });

    $('.generate_awb_code').on('click', function() {
        if (confirm('Are you sure to generate awb code')) {
            var sellerarr = []
            var sendpickup = $(this).data('shipment-id');
            $.ajax({
                type: 'POST',
                url: "public/db-operation.php",
                data: 'generate_awb=1&shipment_id=' + sendpickup,
                dataType: "json",
                beforeSend: function() {
                    $(this).html('<i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>').attr('disabled', true);
                },
                error: function(request, error) {
                    console.log(request)
                },
                success: function(data) {
                    if (data.error == false) {
                        location.reload();
                        $('#close').trigger('click');
                        $('.create_order_btn').html('Create Order').attr('disabled', false);
                        $('#result-shiprocket').html('<h4><label class="label m-5 label-success">' + data.message + '</label></h4>')
                    } else {
                        $('#close').trigger('click');
                        $('.create_order_btn').html('Create Order').attr('disabled', false);
                        $('#result-shiprocket').html('<h4><label class="label m-5 label-danger">' + data.message + '</label></h4>')
                        data.data.forEach(Element => {
                            $('#result-shiprocket').append('<label class="label m-5 label-danger">' + Element + '</label>')

                        });
                    }
                }
            });
        }

    });


    $('.send-pickup-request').on('click', function() {
        if (confirm('Are you sure to send pickup request')) {
            var sellerarr = []
            var sendpickup = $(this).data('shipment-id');
            var courier_company_id = $(this).data('currier-id');

            $.ajax({
                type: 'POST',
                url: "public/db-operation.php",
                data: 'request_pickup=1&shipment_id=' + sendpickup + '&courier_company_id=' + courier_company_id,
                dataType: "json",
                beforeSend: function() {
                    $(this).html('<i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>').attr('disabled', true);
                },
                error: function(request, error) {
                    console.log(request)
                },
                success: function(data) {
                    if (data.error == false) {
                        location.reload();
                        $('#close').trigger('click');
                        $('.create_order_btn').html('Create Order').attr('disabled', false);
                        $('#result-shiprocket').html('<h4><label class="label m-5 label-success">' + data.message + '</label></h4>')
                    } else {
                        $('#close').trigger('click');
                        $('.create_order_btn').html('Create Order').attr('disabled', false);
                        $('#result-shiprocket').html('<h4><label class="label m-5 label-danger">' + data.message + '</label></h4>')
                        data.data.forEach(Element => {
                            $('#result-shiprocket').append('<label class="label m-5 label-danger">' + Element + '</label>')

                        });
                    }
                }
            });
        }

    });

    $('.track-order').on('click', function() {
        var shipment_id = $(this).data('shipment-id');
        var awb_code = $(this).data('awb-code');
        $('#show_shipment_id').html(shipment_id);

        $.ajax({
            type: 'POST',
            url: "public/db-operation.php",
            data: 'track_order=1&shipment_id=' + shipment_id,
            dataType: "json",
            beforeSend: function() {
                $(this).html('<i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>').attr('disabled', true);
            },
            error: function(request, error) {
                console.log(request)
            },
            success: function(data) {

                $('.shiprocket-link').html('<a href="https://shiprocket.co/tracking/' + awb_code + '" target = "_blank" > check live update </a>');

                if (data.error == false) {
                    $('.current_status').html(data.current_status)
                    if (data.current_status == 'PICKED UP') {
                        $('.pickuped').addClass('active')
                    }
                    if (data.current_status == 'PICKED UP') {
                        $('.on-the-way').addClass('active')
                    }
                    if (data.current_status == 'Delivered') {
                        $('.delivered').addClass('active')
                    }
                } else {
                    $('.current_status').html(data.message)
                }
            }
        });

    });

    $('.cancel-order').on('click', function() {
        if (confirm('Are you sure to cancel this order ?')) {
            var shipment_id = $(this).data('shipment-id');
            $.ajax({
                type: 'POST',
                url: "public/db-operation.php",
                data: 'cancel_order=1&shipment_id=' + shipment_id,
                dataType: "json",
                beforeSend: function() {
                    $(this).html('<i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>').attr('disabled', true);
                },
                error: function(request, error) {
                    console.log(request)
                },
                success: function(data) {
                    if (data.error == false) {
                        location.reload();
                    } else {
                        $('#result-shiprocket').html('<h4><label class="label m-5 label-success">' + data.message + '</label></h4>')
                    }
                }
            });
        }
    })
    $('.generate-manifests').on('click', function() {
        if (confirm('Are you sure to generate manifest ?')) {
            var shipment_id = $(this).data('shipment-id');
            $.ajax({
                type: 'POST',
                url: "public/db-operation.php",
                data: 'generate_manifests=1&shipment_id=' + shipment_id,
                dataType: "json",
                beforeSend: function() {
                    $(this).html('<i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>').attr('disabled', true);
                },
                error: function(request, error) {
                    console.log(request)
                },
                success: function(data) {
                    if (data.error == false) {
                        location.reload();
                    } else {
                        $('#result-shiprocket').html('<h4><label class="label m-5 label-success">' + data.message + '</label></h4>')
                    }
                }
            });
        }
    })
    $('.generate-labels').on('click', function() {
        if (confirm('Are you sure to generate label ?')) {
            var shipment_id = $(this).data('shipment-id');
            $.ajax({
                type: 'POST',
                url: "public/db-operation.php",
                data: 'generate_labels=1&shipment_id=' + shipment_id,
                dataType: "json",
                beforeSend: function() {
                    $(this).html('<i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>').attr('disabled', true);
                },
                error: function(request, error) {
                    console.log(request)
                },
                success: function(data) {
                    if (data.error == false) {
                        location.reload();
                    } else {
                        $('#result-shiprocket').html('<h4><label class="label m-5 label-success">' + data.message + '</label></h4>')
                    }
                }
            });
        }
    })




    var allowed = '<?= $allowed; ?>';
    var delivery_by = "";
    $(".deliver_by").change(function(e) {
        delivery_by = $(this).val();
    });
    var status = "";
    $(".status").change(function(e) {
        status = $(this).val();
    });
    $(document).on('click', '.update_order_item_status', function(e) {
        e.preventDefault();
        if (allowed == 0) {
            alert('Sorry! This operation is not allowed in demo panel!.');
            window.location.reload();
            return false;
        }
        var update_permission = '<?= $update_order_permission; ?>';
        if (update_permission == 0) {
            alert('Sorry! you have no permission to update orders.');
            window.location.reload();
            return false;
        }
        var status1 = status;
        var id = $('#order_id').val();
        var item_id = $(this).data('value1');
        var delivery_by1 = delivery_by;
        var dataString = 'update_order_status=1&order_id=' + id + '&status=' + status1 + '&order_item_id=' + item_id + '&delivery_boy_id=' + delivery_by + '&ajaxCall=1';
        if (confirm("Are you sure? you want to change the order item status")) {
            $.ajax({
                url: "api-firebase/order-process.php",
                type: "POST",
                data: dataString,
                dataType: "json",
                beforeSend: function() {
                    $('#submit_btn').html('Please wait...').attr('disabled', true);
                },
                success: function(data) {
                    if (data.error == true) {
                        alert(data.message);
                        location.reload(true);
                    } else {
                        alert(data.message);
                        location.reload(true);
                    }
                    $('#status option:selected').attr('disabled', false);
                }
            });
        }
    });

    $(document).on('click', '.update_order_total_payable', function(e) {
        e.preventDefault();
        if (allowed == 0) {
            alert('Sorry! This operation is not allowed in demo panel!.');
            window.location.reload();
            return false;
        }
        var update_permission = '<?= $update_order_permission; ?>';
        if (update_permission == 0) {
            alert('Sorry! you have no permission to update orders.');
            window.location.reload();
            return false;
        }
        var discount = $('#input_discount').val();
        var total_payble = $('#final_total').val();
        var id = $('#order_id').val();
        var dataString = 'update_order_total_payable=true&id=' + id + '&discount=' + discount + '&total_payble=' + total_payble + '&ajaxCall=1';
        $.ajax({
            url: "api-firebase/order-process.php",
            type: "POST",
            data: dataString,
            beforeSend: function() {
                $(this).html('...');
            },
            dataType: "json",
            success: function(data) {
                var result = $.map(data, function(value, index) {
                    return [value];
                });
                alert(result[1]);
                if (!result[0]) {}
                location.reload();
            }

        });
    });

    function myfunction() {
        var create = '<?php echo $permissions['reports']['create']; ?>';
        if (create == 0) {
            alert('You have no permission to create invoice');
            return false;

        }
        window.location.href = 'invoice.php?id=<?php echo $res[0]['id']; ?>';
    }
    $('#input_discount').on('input', function() {
        var total = $("#total_amount").val();
        var discount = $('#input_discount').val();
        discounted_amount = total * discount / 100;
        final_total = total - discounted_amount;
        if (discount >= 0) {
            $("#final_total").val(Math.round((final_total + Number.EPSILON) * 100) / 100);
        }
    });
</script>

<script>
    $('.update_order_items').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure? want to update.')) {
            var data = $('#update_form').serialize();
            $.ajax({
                type: 'POST',
                url: 'api-firebase/order-process.php',
                data: data,
                beforeSend: function() {
                    $('#submit_btn').html('Please wait..').attr('disabled', true);
                },
                cache: false,
                processData: false,
                dataType: "json",
                success: function(result) {
                    $('#save_result').html(result.message);
                    $('#save_result').show().delay(3000).fadeOut();
                    $('#submit_btn').html('Bulk Update').attr('disabled', false);
                    if (result.error == false) {
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    }
                }
            });
        }

    });
</script>
<script>
    $('#update_status_form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        if ($("#update_status_form").validate().form()) {
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(result) {
                    $('#update_result').html(result);
                    $('#update_result').show().delay(6000).fadeOut();
                    $('#update_btn').html('Update');
                    $('#update_status_form')[0].reset();
                    setTimeout(function() {
                        $('#editBankTransferModal').modal('hide');
                        window.location.reload();
                    }, 3000);
                }
            });
        }
    });
</script>
<script>
    $(document).on('click', '.delete-image', function() {
        var pid = $(this).data('pid');
        var i = $(this).data('i');
        if (confirm('Are you sure want to delete the image?')) {
            $.ajax({
                type: 'POST',
                url: 'public/delete-attachments.php',
                data: 'i=' + i + '&pid=' + pid,
                success: function(result) {
                    if (result == '1') {
                        alert('Image deleted successfully');
                        window.location.reload();
                    } else
                        alert('Image could not be deleted!');

                }
            });
        }
    });
</script>
<script>
    $('.update_order_items_bottom').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure? want to update.')) {
            var data = $('#update_form').serialize();
            $.ajax({
                type: 'POST',
                url: 'api-firebase/order-process.php',
                data: data,
                beforeSend: function() {
                    $('#submit_btn_bottom').html('Please wait..').attr('disabled', true);
                },
                cache: false,
                processData: false,
                dataType: "json",
                success: function(result) {
                    $('#save_result_bottom').html(result.message);
                    $('#save_result_bottom').show().delay(3000).fadeOut();
                    $('#submit_btn_bottom').html('Bulk Update').attr('disabled', false);
                    if (result.error == false) {
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    }
                }
            });
        }
    });
</script>
<?php $db->disconnect(); ?>