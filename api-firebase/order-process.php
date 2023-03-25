<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
include_once('send-email.php');
include_once('../includes/crud.php');
include_once('../includes/custom-functions.php');
include_once('../includes/variables.php');
include_once('verify-token.php');
include_once('../includes/functions.php');
include_once('../library/shiprocket.php');

$db = new Database();

$db->connect();
$db->sql("SET NAMES utf8");
$function = new custom_functions();
$settings = $function->get_settings('system_timezone', true);
$app_name = $settings['app_name'];
$support_email = $settings['support_email'];
$config = $function->get_configurations();
$shiprocket = new Shiprocket();
$fn = new functions();

if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}
$generate_otp = $config['generate-otp'];
$response = array();
$cancel_order_from = "";
$order_cancelled =  $order_item_cancelled = false;

$shipping_type = ($function->get_settings('local_shipping') == 1) ? 'local' : 'standard';

if (isset($_POST['ajaxCall']) && !empty($_POST['ajaxCall'])) {
    $accesskey = "90336";
    $cancel_order_from = "admin";
} else {
    if (isset($_POST['accesskey']) && !empty($_POST['accesskey'])) {
        $accesskey = $db->escapeString($function->xss_clean($_POST['accesskey']));
    } else {
        $response['error'] = true;
        $response['message'] = "accesskey required";
        print_r(json_encode($response));
        return false;
    }
}

if ($access_key != $accesskey) {
    $response['error'] = true;
    $response['message'] = "invalid accesskey";
    print_r(json_encode($response));
    return false;
}
/* 
  i. place_order
        accesskey:90336
        place_order:1
        user_id:441
        order_note:extra      // {optional}
        product_variant_id:[462,312]
        quantity:[3,3]
        total:552.69     (total price of products including tax)
        delivery_charge:0  (area wise)
        wallet_balance:0
        wallet_used:false
        address_id:996
        final_total:552.69  (total + delivery_charge - promo_discount - discount)
        payment_method:Paypal / Payumoney / COD / PAYTM
        promo_code:NEW20    // {optional}
        promo_discount:123  //{optional}
        delivery_time:morning 10:30 to 5:00
        status:received / awaiting_payment  //{optional}
*/

if (isset($_POST['place_order']) && isset($_POST['user_id']) && !empty($_POST['product_variant_id']) && !empty($_POST['place_order'])) {
    if (!verify_token()) {
        return false;
    }
    $res_msg = "";
    $res_msg .= (empty($_POST['total'])) ? "total," : "";
    $res_msg .= ($_POST['delivery_charge'] == "") ? "delivery_charge," : "";
    $res_msg .= (empty($_POST['delivery_time']) || $_POST['delivery_time'] == "") ? "delivery_time," : "";
    $res_msg .= ($_POST['final_total'] == "") ? "final_total," : "";
    $res_msg .= (empty($_POST['payment_method']) || $_POST['payment_method'] == "") ? "payment_method," : "";
    $res_msg .= (empty($_POST['address_id']) || $_POST['address_id'] == "") ? "address_id," : "";
    $res_msg .= (empty($_POST['quantity']) || $_POST['quantity'] == "") ? "quantity," : "";
    if ($res_msg != "") {
        $response['error'] = true;
        $response['message'] = "This fields " . trim($res_msg, ",") . " should be Passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $sql = "select status from users where id = " . $_POST['user_id'];
    $db->sql($sql);
    $result = $db->getResult();
    if (!isset($result[0]['status']) || $result[0]['status'] == 0) {
        $response['error'] = true;
        $response['message'] = "Not allowed to place order as your account is de-activated!";
        echo json_encode($response);
        return false;
    }
    $user_id = $db->escapeString($function->xss_clean($_POST['user_id']));
    $order_note = (isset($_POST['order_note']) && !empty($_POST['order_note'])) ? $db->escapeString($function->xss_clean($_POST['order_note'])) : "";
    $wallet_used = (isset($_POST['wallet_used']) && $function->xss_clean($_POST['wallet_used']) == 'true') ? 'true' : 'false';
    $items = $function->xss_clean($_POST['product_variant_id']);
    $total = $db->escapeString($function->xss_clean($_POST['total']));
    $delivery_charge = $db->escapeString($function->xss_clean($_POST['delivery_charge']));
    $address_id = $db->escapeString($function->xss_clean($_POST['address_id']));

    if ($shipping_type == 'local') {
        $delivery_charge = $function->get_delivery_charge($address_id, $total);
        $dc = $_POST['delivery_charge'] == 0 && $delivery_charge > 0 ? $delivery_charge : 0;
    }
    $final_total = $db->escapeString($function->xss_clean($_POST['final_total']));
    $final_total = $final_total + $dc;

    $wallet_balance = (isset($_POST['wallet_balance']) && is_numeric($_POST['wallet_balance'])) ? $db->escapeString($function->xss_clean($_POST['wallet_balance'])) : 0;
    $payment_method = $db->escapeString($function->xss_clean($_POST['payment_method']));
    $delivery_time = (isset($_POST['delivery_time'])) ? $db->escapeString($function->xss_clean($_POST['delivery_time'])) : "";
    $promo_code = (isset($_POST['promo_code']) && !empty($_POST['promo_code'])) ? $db->escapeString($function->xss_clean($_POST['promo_code'])) : "";
    $promo_discount = (isset($_POST['promo_discount']) && !empty($_POST['promo_discount'])) ? $db->escapeString($function->xss_clean($_POST['promo_discount'])) : 0;
    $active_status = (isset($_POST['status']) && $_POST['status'] != "") ? $db->escapeString($function->xss_clean($_POST['status'])) : 'received';
    $order_from = (isset($_POST['order_from']) && !empty($_POST['order_from'])) ? $db->escapeString($function->xss_clean($_POST['order_from'])) : 0;

    $status[] = array($active_status, date("d-m-Y h:i:sa"));
    $quantity = $function->xss_clean($_POST['quantity']);
    $quantity_arr = json_decode($quantity, true);
    $item_arr = json_decode($items, true);
    $Date = date('Y-m-d');


    for ($i = 0; $i < count($item_arr); $i++) {
        $res = $function->get_data($columns = ['id',], 'id=' . $item_arr[$i], 'product_variant');
        if (empty($res[0])) {
            $response['error'] = true;
            $response['message'] = "Found one or more items in order is not available for order";
            echo json_encode($response);
            return false;
        }
    }
    $item_details = $function->get_product_by_variant_id($items);

    $order_total_tax_amt = 0;
    $order_total_tax_per = 0;
    for ($i = 0; $i < count($item_details); $i++) {
        $price = $db->escapeString($item_details[$i]['price']);
        $discounted_price = (empty($item_details[$i]['discounted_price']) || $item_details[$i]['discounted_price'] == "") ? 0 : $db->escapeString($item_details[$i]['discounted_price']);
        $quantity = $db->escapeString($quantity_arr[$i]);
        $tax_percentage = (empty($item_details[$i]['tax_percentage']) || $item_details[$i]['tax_percentage'] == "") ? 0 : $db->escapeString($item_details[$i]['tax_percentage']);
        $final_price = ($discounted_price != 0) ? ($discounted_price * $quantity) : ($price * $quantity);
        $tax_count = ($tax_percentage / 100) * $final_price;
        $order_total_tax_amt += $tax_count;
        $order_total_tax_per += $tax_percentage;
    }

    $otp_number = $sub_total = $promo_code_discount = 0;
    if ($generate_otp == 1) {
        $otp_number = mt_rand(100000, 999999);
    } else {
        $otp_number = 0;
    }


    /* check for wallet balance */
    if ($wallet_used == 'true') {
        $user_wallet_balance = $function->get_wallet_balance($user_id, 'users');
        if ($user_wallet_balance < $wallet_balance) {
            $response['error'] = true;
            $response['message'] = "Insufficient wallet balance.";
            echo json_encode($response);
            return false;
        }
    }

    /* check for minimum order amount */
    if ($total < $settings['min_order_amount']) {
        $response['error'] = true;
        $response['message'] = "Minimum order amount is " . $settings['min_order_amount'] . ".";
        echo json_encode($response);
        return false;
    }
    $walletvalue = ($wallet_used) ? $wallet_balance : 0;
    $order_status = $db->escapeString(json_encode($status));

    /* getting user address data */
    $user_address = $function->get_user_address($address_id);
    if (!empty($user_address)) {
        $address = $user_address['user_address'];
        $mobile = $user_address['mobile'];
        $latitude = $user_address['latitude'];
        $longitude = $user_address['longitude'];
        $pincode_id = $user_address['pincode_id'];
        $area_id = $user_address['area_id'];
    } else {
        $response['error'] = true;
        $response['message'] = "Some thing is missing in your address.";
        echo json_encode($response);
        return false;
    }

    /* insert data into order table */
    $sql = "INSERT INTO `orders`(`user_id`,`otp`,`mobile`,`order_note`, `total`, `delivery_charge`, `tax_amount`, `tax_percentage`, `wallet_balance`, `promo_code`,`promo_discount`, `final_total`, `payment_method`, `address`, `latitude`, `longitude`, `delivery_time`, `status`, `active_status`,`order_from`,`pincode_id`,`area_id`,`address_id`) VALUES ('$user_id','$otp_number','$mobile','$order_note','$total','$delivery_charge','$order_total_tax_amt','$order_total_tax_per','$walletvalue','$promo_code','$promo_discount', '$final_total','$payment_method','$address','$latitude','$longitude','$delivery_time','$order_status','$active_status','$order_from','$pincode_id','$area_id','$address_id')";
    $db->sql($sql);
    $sql = "SELECT id FROM orders where user_id=$user_id and active_status = '$active_status' order by id desc limit 1";
    $db->sql($sql);
    $res_order_id = $db->getResult();
    $order_id = $res_order_id[0]['id'];
    if (empty($order_id)) {
        $response['error'] = true;
        $response['message'] = "Order can not place due to some reason! try again after some time.";
        echo json_encode($response);
        return false;
    }

    /* process wallet balance */
    $user_wallet_balance = $function->get_wallet_balance($user_id, 'users');
    if ($wallet_used == 'true') {
        /* deduct the balance & set the wallet transaction */
        $new_balance = $user_wallet_balance < $wallet_balance ? 0 : $user_wallet_balance - $wallet_balance;
        $function->update_wallet_balance($new_balance, $user_id, 'users');
        $wallet_txn_id = $function->add_wallet_transaction($order_id, 0, $user_id, 'debit', $wallet_balance, 'Used against Order Placement', 'wallet_transactions');
    }
    $shiprocket_settings = $function->get_settings('shiprocket', true);
    $standard_shipping = 0;
    if (isset($shiprocket_settings['shiprocket']) && $shiprocket_settings['shiprocket'] == 1 && isset($shiprocket_settings['shiprocket_email']) && !empty($shiprocket_settings['shiprocket_email']) && isset($shiprocket_settings['shiprocket_password']) && !empty($shiprocket_settings['shiprocket_password'])) {
        $standard_shipping = 1;
    }
    /* process each product in order from variants of products */
    for ($i = 0; $i < count($item_details); $i++) {
        $product_id = $item_details[$i]['product_id'];
        $product_name = $db->escapeString($item_details[$i]['name']);
        $measurement = $item_details[$i]['measurement'];
        $variant_name = $measurement . $item_details[$i]['measurement_unit_name'];
        $product_variant_id = $db->escapeString($item_details[$i]['id']);
        $measurement_unit_id = $item_details[$i]['measurement_unit_id'];
        $stock_unit_id = $item_details[$i]['stock_unit_id'];
        $price = $db->escapeString($item_details[$i]['price']);
        $discounted_price = (empty($item_details[$i]['discounted_price']) || $item_details[$i]['discounted_price'] == "") ? 0 : $db->escapeString($item_details[$i]['discounted_price']);
        $type = $item_details[$i]['product_type'];
        $total_stock = $item_details[$i]['stock'];
        $quantity = $db->escapeString($quantity_arr[$i]);
        $tax_title = $item_details[$i]['tax_title'];
        $seller_id = (!empty($item_details[$i]['seller_id'])) ? $db->escapeString($item_details[$i]['seller_id']) : "";
        $tax_percentage = (empty($item_details[$i]['tax_percentage']) || $item_details[$i]['tax_percentage'] == "") ? 0 : $db->escapeString($item_details[$i]['tax_percentage']);
        $tax_amt = $discounted_price != 0 ? (($tax_percentage / 100) * $discounted_price)  : (($tax_percentage / 100) * $price);
        $sub_total = $discounted_price != 0 ? ($discounted_price + ($tax_percentage / 100) * $discounted_price) * $quantity : ($price + ($tax_percentage / 100) * $price) * $quantity;

        $neworder_id = $db->escapeString($order_id);
        $tax_amount = $db->escapeString($tax_amt);
        $order_sub_total = $db->escapeString($sub_total);
        $order_item_status = $db->escapeString(json_encode($status));

        $sql = "INSERT INTO `order_items`(`user_id`, `order_id`,`product_name`,`variant_name`, `product_variant_id`, `quantity`, `price`, `discounted_price`,`tax_amount`,`tax_percentage`, `sub_total`, `status`, `active_status`,seller_id) VALUES ('$user_id','$neworder_id','$product_name','$variant_name','$product_variant_id','$quantity','$price','$discounted_price','$tax_amount', $tax_percentage,'$order_sub_total','$order_item_status','$active_status','$seller_id')";
        $db->sql($sql);
        $res = $db->getResult();
        $sql = "SELECT id from order_items order by id desc limit 1";
        $db->sql($sql);
        $res_oi_id = $db->getResult();
        $oi_id = $res_oi_id[0]['id'];
        if ($type == 'packet') {
            $stock = $total_stock - $quantity;
            $sql = "update product_variant set stock = $stock where id = $product_variant_id";
            $db->sql($sql);
            $res = $db->getResult();
            $db->select("product_variant", "stock", null, "id='" . $product_variant_id . "'");
            $variant_qty = $db->getResult();
            if ($variant_qty[0]['stock'] <= 0) {
                $data = array(
                    "serve_for" => "Sold Out",
                );
                $db->update("product_variant", $data, "id=$product_variant_id");
                $res = $db->getResult();
            }
        } elseif ($type == 'loose') {
            if ($measurement_unit_id == $stock_unit_id) {
                $stock = $quantity * $measurement;
            } else {
                $db->select('unit', '*', null, 'id=' . $measurement_unit_id);
                $unit = $db->getResult();
                $stock = $function->convert_to_parent(($measurement * $quantity), $unit[0]['id']);
            }
            $sql = "update product_variant set stock = stock - $stock where product_id = $product_id AND id=$product_variant_id AND type='loose'";
            $db->sql($sql);
            $res = $db->getResult();
            $sql = "select stock from product_variant where product_id=" . $product_id;
            $db->sql($sql);
            $res_stck = $db->getResult();
            if ($res_stck[0]['stock'] <= 0) {
                $sql = "update product_variant set serve_for='Sold Out' where product_id=" . $product_id;
                $db->sql($sql);
            }
        }
    }
    //create shiprocket order
    $data = array(
        'final_total' => $final_total
    );
    if ($db->update('orders', $data, 'id=' . $order_id)) {
        $res = $db->getResult();
        $response['error'] = false;
        $response['message'] = "Order placed successfully.";
        $response['order_id'] = $order_id;
        print_r(json_encode($response));
        /* send email notification for the order received */
        if ($active_status == "received") {
            //$res = $function->get_data($columns = ['name', 'email', 'mobile', 'country_code'], 'id=' . $user_id, 'users');
            $sql = 'select name,email,mobile,country_code from users where id = ' . $user_id;
            $db->sql($sql);
            $res = $db->getResult();
            $to = $res[0]['email'];
            $mobile = $res[0]['mobile'];
            $country_code = $res[0]['country_code'];
            $subject = "Order received successfully";
            $message = $user_msg = "Hello, Dear " . ucwords($res[0]['name']) . ", We have received your order successfully. Your order summaries are as followed:<br><br>";
            $otp_msg = "Here is your OTP. Please, give it to delivery boy only while getting your order.";
            $message .= "<b>Order ID :</b> #" . $response['order_id'] . "<br><br>Ordered Items : <br>";
            $items = $function->xss_clean_array($_POST['product_variant_id']);
            $item_data1 = array();
            for ($i = 0; $i < count($item_details); $i++) {
                $product_id = $item_details[$i]['product_id'];
                $measurement = $item_details[$i]['measurement'];
                $product_variant_id = $item_details[$i]['id'];
                $measurement_unit_id = $item_details[$i]['measurement_unit_id'];
                $stock_unit_id = $item_details[$i]['stock_unit_id'];
                $price = $item_details[$i]['price'];
                $discounted_price = $item_details[$i]['discounted_price'];
                $type = $item_details[$i]['product_type'];
                $total_stock = $item_details[$i]['stock'];
                $seller_id = (!empty($item_details[$i]['seller_id'])) ? $db->escapeString($item_details[$i]['seller_id']) : "";
                $quantity = $quantity_arr[$i];
                $price = $item_details[$i]['discounted_price'] == 0 ? $item_details[$i]['price'] : $item_details[$i]['discounted_price'];
                $tax_percentage = (empty($item_details[$i]['tax_percentage']) || $item_details[$i]['tax_percentage'] == "") ? 0 : $db->escapeString($item_details[$i]['tax_percentage']);
                $tax_amt = $discounted_price != 0 ? (($tax_percentage / 100) * $discounted_price)  : (($tax_percentage / 100) * $price);
                if (!empty($seller_id)) {
                    //$store_details = $function->get_data($columns = ['email', 'store_name'], 'id=' . $seller_id, 'seller');
                    $sql = 'select email,store_name from seller where id = ' . $seller_id;
                    $db->sql($sql);
                    $store_details = $db->getResult();
                }

                $message .= "<b>Name : </b>" . $item_details[$i]['name'] . "<b> Unit :</b>" . $item_details[$i]['measurement'] . " " . $item_details[$i]['measurement_unit_name'] . "<b> QTY :</b>" . $quantity . "<b> Subtotal :</b>" . $sub_total . "<br>";
                $item_data1[] = array('name' => $item_details[$i]['name'], 'store_name' => $store_details[0]['store_name'], 'tax_amount' => $order_total_tax_amt, 'tax_percentage' => $order_total_tax_per, 'tax_title' => $item_details[$i]['tax_title'], 'unit' =>  $item_details[$i]['measurement'] . " " . $item_details[$i]['measurement_unit_name'], 'qty' => $quantity, 'subtotal' => $sub_total);
                if (!empty($seller_id)) {
                }
            }
            $seller_subject = "New order placed for " . $store_details[0]['store_name'];
            $seller_message = "New order ID : #" . $order_id  . " received please take note of it and proceed further";
            send_email($store_details[0]['email'], $seller_subject, $seller_message);
            $function->send_notification_to_seller($seller_id, $seller_subject, $seller_message, 'order', $response['order_id']);

            $order_data = array('total_amount' => $total, 'delivery_charge' => $delivery_charge, 'wallet_used' => $wallet_balance, 'final_total' => $final_total, 'payment_method' => $payment_method, 'address' => $address, 'user_msg' => $user_msg, 'otp_msg' => $otp_msg, 'otp' => $otp_number);
            $subject = "New order placed for $app_name";
            $message = "New order ID : #" . $response['order_id'] . " received please take note of it and proceed further";
            send_smtp_mail($to, $subject, $item_data1, $order_data);
            $function->send_notification_to_admin("New Order Arrived.", $message, "admin_notification", $response['order_id']);
            send_email($support_email, $subject, $message);
            $function->send_order_update_notification($user_id, "Your order has been received", $message, 'order', $response['order_id']);
        }
        $shiprocket_settings = $function->get_settings('shiprocket', true);
        if (isset($shiprocket_settings['shiprocket']) && $shiprocket_settings['shiprocket'] == 1 && isset($shiprocket_settings['shiprocket_email']) && !empty($shiprocket_settings['shiprocket_email']) && isset($shiprocket_settings['shiprocket_password']) && !empty($shiprocket_settings['shiprocket_password']) && !empty($shiprocket_settings['pickup_location'])) {
            // $function->process_shiprocket($order_id);
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Could not place order. Try again!";
        $response['order_id'] = 0;
        print_r(json_encode($response));
    }
} elseif (isset($_POST['place_order']) && isset($_POST['user_id']) && empty($_POST['product_variant_id'])) {
    $response['error'] = true;
    $response['message'] = "Order without items in cart can not be placed!";
    $response['order_id'] = 0;
    print_r(json_encode($response));
}

if (isset($_POST['update_order_status']) && isset($_POST['order_item_id']) && isset($_POST['order_id'])) {
    $id = $db->escapeString($function->xss_clean($_POST['order_id']));
    $order_item_id = $db->escapeString($function->xss_clean($_POST['order_item_id']));
    $postStatus = $db->escapeString($function->xss_clean($_POST['status']));
    $res = $function->get_data($columns = ['user_id', 'payment_method', 'wallet_balance', 'total', 'delivery_charge', 'tax_amount'], 'id=' . $id, 'orders');
    $res_order_item = $function->get_data($columns = ['active_status', 'status'], 'id=' . $order_item_id, 'order_items');
    $delivery_boy_id = 0;

    if ($postStatus == 'awaiting_payment') {
        $response['error'] = true;
        $response['message'] = "Order can not be awaiting status. Because it is on " . $res_order_item[0]['active_status'] . ".";
        print_r(json_encode($response));
        return false;
    }

    /* check for awaiting status */
    if ($res_order_item[0]['active_status'] == 'awaiting_payment' && ($postStatus == 'returned' || $postStatus == 'delivered' || $postStatus == 'shipped' || $postStatus == 'processed')) {
        $response['error'] = true;
        $response['message'] = "Order can not be $postStatus. Because it is on awaiting status.";
        print_r(json_encode($response));
        return false;
    }

    /* update delivery boy to the order item */
    if (isset($_POST['delivery_boy_id']) && !empty($_POST['delivery_boy_id']) && $_POST['delivery_boy_id'] != "") {
        $delivery_boy_id = $db->escapeString($function->xss_clean($_POST['delivery_boy_id']));
        $res_delivery_boy_id = $function->get_data($columns = ['active_status', 'status', 'delivery_boy_id'], 'id=' . $order_item_id, 'order_items');
        if ($res_delivery_boy_id[0]['active_status'] == "awaiting_payment") {
            $response['error'] = true;
            $response['message'] = " You can not assign Delivery boy. Because order is on Awaiting status.";
            print_r(json_encode($response));
            return false;
        } else {
            if (($res_delivery_boy_id[0]['delivery_boy_id'] == 0)
                || ($res_delivery_boy_id[0]['delivery_boy_id'] != $delivery_boy_id && $res_delivery_boy_id[0]['active_status'] != 'cancelled')
            ) {
                $delivery_boy_name = $function->get_data($columns = ['name'], 'id=' . $delivery_boy_id, 'delivery_boys');
                if ($postStatus == 'delivered') {
                    $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", your order has been delivered. ID : #" . $order_item_id . ". Please take a note of it.";
                } else {
                    $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", You have new order to deliver. Here is your ID : #" . $order_item_id . ". Please take a note of it.";
                }
                $function->send_notification_to_delivery_boy($delivery_boy_id, "Your new order item with ID : #$order_item_id has been " . ucwords($postStatus), $message_delivery_boy, 'delivery_boys', $order_item_id);
                $function->store_delivery_boy_notification($delivery_boy_id, $order_item_id, "Your new order item with ID : #$order_item_id  has been " . ucwords($postStatus), $message_delivery_boy, 'order_reward');
                $sql = "UPDATE order_items SET `delivery_boy_id`='" . $delivery_boy_id . "' WHERE id=" . $order_item_id;
                $db->sql($sql);
            }
        }
    }
    if ($res_order_item[0]['active_status'] == 'delivered' && $postStatus == 'cancelled') {
        $response['error'] = true;
        $response['message'] = '';
        $response['message'] = ($delivery_boy_id != 0) ? 'Delivery Boy updated, Could not cancel delivered order' : 'Could not cancel delivered order';
        print_r(json_encode($response));
        return false;
    }
    /* Could not update order status once cancelled or returned! */
    if ($function->is_order_item_cancelled($order_item_id)) {
        $response['error'] = true;
        $response['message'] = 'Could not update order status  cancelled or returned!';
        print_r(json_encode($response));
        return false;
    }

    /* Cannot return order unless it is delivered */
    if ($function->is_order_item_returned($res_order_item[0]['active_status'], $postStatus)) {
        $response['error'] = true;
        $response['message'] = 'Cannot return order unless it is delivered!';
        print_r(json_encode($response));
        return false;
    }
    $sql = "SELECT * FROM `users` WHERE id=" . $res[0]['user_id'];
    $db->sql($sql);
    $res_user = $db->getResult();
    if (!empty($postStatus) && $postStatus != $res_order_item[0]['active_status']) {
        /* return if only delivery boy will update and order status is already changed */
        $sql = "SELECT COUNT(id) as total FROM `orders` WHERE user_id=" . $res[0]['user_id'] . " && status LIKE '%delivered%'";
        $db->sql($sql);
        $res_count = $db->getResult();

        if (!empty($res)) {
            $status = json_decode($res_order_item[0]['status']);

            $user_id =  $res[0]['user_id'];
            foreach ($status as $each) {
                if (in_array($postStatus, $each)) {
                    $response['error'] = true;
                    $response['message'] = ($delivery_boy_id != 0) ? 'Delivery Boy updated, Order already ' . $postStatus : 'Order already ' . $postStatus;
                    print_r(json_encode($response));
                    return false;
                }
            }

            /* if given status is cancel or return */
            if ($postStatus == 'cancelled' || $postStatus == 'returned') {

                /* fetch order items details */
                $sql = 'SELECT oi.`id` as order_item_id,oi.`product_variant_id`,oi.`quantity`,pv.`product_id`,pv.`type`,pv.`stock`,pv.`stock_unit_id`,pv.`measurement`,pv.`measurement_unit_id` FROM `order_items` oi join `product_variant` pv on pv.id = oi.product_variant_id WHERE oi.`id`=' . $order_item_id;
                $db->sql($sql);
                $res_oi = $db->getResult();

                /* check for item cancellable or not */
                if ($postStatus == 'cancelled') {
                    if ($cancel_order_from == "") {
                        $cancelation_error = 0;
                        $resp = $function->is_product_cancellable($res_oi[0]['order_item_id']);
                        if ($resp['till_status_error'] == 1 || $resp['cancellable_status_error'] == 1) {
                            $cancelation_error = 1;
                        }
                        if ($cancelation_error == 1) {
                            $resp['error'] = true;
                            $resp['message'] = "Found one or more items in order which is either not cancelable or not matching cancelation criteria!";
                            print_r(json_encode($resp));
                            return false;
                        }
                    }

                    if ($function->cancel_order_item($id, $order_item_id)) {
                        $order_item_cancelled = true;
                    } else {
                        $order_item_cancelled = false;
                    }
                } else if ($postStatus == 'returned') {
                    /* check for item returnable or not */
                    $return_error = 0;
                    $resp = $function->is_product_returnable($res_oi[0]['order_item_id']);
                    if ($resp['return_status_error'] == 1) {
                        $return_error = 1;
                    }
                    if ($return_error == 1) {
                        $resp['error'] = true;
                        $resp['message'] = "Found one or more items in order which is not returnable!";
                        print_r(json_encode($resp));
                        return false;
                    }
                    $is_item_delivered = 0;
                    $product_details = $function->get_product_by_variant_id2($res_oi[0]['product_variant_id']);

                    $return_days = $function->get_data($columns = ['return_days'], 'id=' . $product_details['product_id'], 'products');
                    $return_day = $return_days[0]['return_days'];
                    foreach ($status as $each_status) {
                        if (in_array('delivered', $each_status)) {
                            $is_item_delivered = 1;
                            $now = time(); // or your date as well
                            $status_date = strtotime($each_status[1]);
                            $datediff = $now - $status_date;
                            $no_of_days = round($datediff / (60 * 60 * 24));
                            if ($no_of_days > $return_day) {
                                $response['error'] = true;
                                $response['message'] = 'Oops! Sorry you cannot return the item now. You have crossed product\'s maximum return period';
                                print_r(json_encode($response));
                                return false;
                            }
                        }
                    }
                    if (!$is_item_delivered) {
                        $response['error'] = true;
                        $response['message'] = 'Cannot return item unless it is delivered!';
                        print_r(json_encode($response));
                        return false;
                    }
                    if ($function->is_return_request_exists($res[0]['user_id'], $order_item_id)) {
                        $response['error'] = true;
                        $response['message'] = 'Already applied for return';
                        print_r(json_encode($response));
                        return false;
                    }
                    /* store return request */
                    $function->store_return_request($res[0]['user_id'], $id, $order_item_id);

                    $response['error'] = false;
                    $response['message'] = 'Order item returned request received successfully! Please wait for approval.';

                    // if (strtolower($res[0]['payment_method']) != 'cod') {
                    //     /* update user's wallet */
                    //     $user_id = $res[0]['user_id'];
                    //     $total = $res[0]['total'] + $res[0]['delivery_charge'] + $res[0]['tax_amount'];
                    //     $user_wallet_balance = $function->get_wallet_balance($user_id, 'users');
                    //     $new_balance = $user_wallet_balance + $total;
                    //     $function->update_wallet_balance($new_balance, $user_id, 'users');
                    //     /* add wallet transaction */
                    //     $wallet_txn_id = $function->add_wallet_transaction($id, $order_item_id, $user_id, 'credit', $total, 'Balance credited against item cancellation..', 'wallet_transactions', '1');
                    // } else {
                    //     if ($res[0]['wallet_balance'] != 0) {
                    //         /* update user's wallet */
                    //         $user_id = $res[0]['user_id'];
                    //         $total = $res[0]['total'] + $res[0]['delivery_charge'] + $res[0]['tax_amount'];
                    //         $user_wallet_balance = $function->get_wallet_balance($user_id, 'users');
                    //         $new_balance = ($user_wallet_balance + $res[0]['wallet_balance']);
                    //         $function->update_wallet_balance($new_balance, $user_id, 'users');
                    //         /* add wallet transaction */
                    //         $wallet_txn_id = $function->add_wallet_transaction($id, $order_item_id, $user_id, 'credit', $total, 'Balance credited against item cancellation!', 'wallet_transactions');
                    //     }
                    // }
                    if ($res_oi[0]['type'] == 'packet') {
                        $sql = "UPDATE product_variant SET stock = stock + " . $res_oi[0]['quantity'] . " WHERE id='" . $res_oi[0]['product_variant_id'] . "'";
                        $db->sql($sql);
                        $sql = "select stock from product_variant where id=" . $res_oi[0]['product_variant_id'];
                        $db->sql($sql);
                        $res_stock = $db->getResult();
                        if ($res_stock[0]['stock'] > 0) {
                            $sql = "UPDATE product_variant set serve_for='Available' WHERE id='" . $res_oi[0]['product_variant_id'] . "'";
                            $db->sql($sql);
                        }
                    } else {
                        /* When product type is loose */
                        if ($res_oi[0]['measurement_unit_id'] != $res_oi[0]['stock_unit_id']) {
                            $stock = $function->convert_to_parent($res_oi[0]['measurement'], $res_oi[0]['measurement_unit_id']);
                            $stock = $stock * $res_oi[0]['quantity'];
                            $sql = "UPDATE product_variant SET stock = stock + " . $stock . " WHERE product_id='" . $res_oi[0]['product_id'] . "'" .  " AND id='" . $res_oi[0]['product_variant_id'] . "'";
                            $db->sql($sql);
                        } else {
                            $stock = $res_oi[0]['measurement'] * $res_oi[0]['quantity'];
                            $sql = "UPDATE product_variant SET stock = stock + " . $stock . " WHERE product_id='" . $res_oi[0]['product_id'] . "'" .  " AND id='" . $res_oi[0]['product_variant_id'] . "'";
                            $db->sql($sql);
                        }
                    }
                }
            }
            if ($postStatus == 'delivered') {
                $sql = "SELECT oi.delivery_boy_id,oi.sub_total,o.final_total,o.total,o.payment_method,o.delivery_charge FROM orders o join order_items oi on oi.order_id=o.id WHERE oi.id=" . $order_item_id;
                $db->sql($sql);
                $res_boy = $db->getResult();
                if ($res_boy[0]['delivery_boy_id'] != 0) {
                    if (strtolower($res_boy[0]['payment_method']) == 'cod') {
                        $cash_received = $res_boy[0]['sub_total'] + $res_boy[0]['delivery_charge'];
                        $sql = "UPDATE delivery_boys SET cash_received = cash_received + $cash_received WHERE id=" . $res_boy[0]['delivery_boy_id'];
                        $db->sql($sql);
                        $function->add_transaction($order_item_id, $res_boy[0]['delivery_boy_id'], 'delivery_boy_cash', $cash_received, 'Delivery boy collected COD');
                    }
                    $sql = "select name,bonus from delivery_boys where id=" . $res_boy[0]['delivery_boy_id'];
                    $db->sql($sql);
                    $res_bonus = $db->getResult();
                    $reward = $res_boy[0]['sub_total'] / 100 * $res_bonus[0]['bonus'];
                    if ($reward > 0) {
                        $sql = "UPDATE delivery_boys SET balance = balance + $reward WHERE id=" . $res_boy[0]['delivery_boy_id'];
                        $db->sql($sql);
                        $comission = $function->add_delivery_boy_commission($delivery_boy_id, 'credit', $reward, 'Order Delivery Commission.');
                        $currency = $function->get_settings('currency');
                        $message_delivery_boy = "Hello, Dear " . ucwords($res_bonus[0]['name']) . ", Here is the new update on your order for the order item ID : #" . $order_item_id . ". Your Commission of" . $reward . " is credited. Please take a note of it.";
                        $function->send_notification_to_delivery_boy($delivery_boy_id, "Your commission " . $reward . " " . $currency . " has been credited", "$message_delivery_boy", 'delivery_boys', $order_item_id);
                        $function->store_delivery_boy_notification($delivery_boy_id, $order_item_id, "Your commission " . $reward . " " . $currency . " has been credited", $message_delivery_boy, 'order_reward');
                    }
                }

                /* referal system processing */
                if ($config['is-refer-earn-on'] == 1) {
                    if ($res_boy[0]['total'] >= $config['min-refer-earn-order-amount']) {
                        if ($res_count[0]['total'] == 0) {
                            if ($res_user[0]['friends_code'] != '') {
                                if ($config['refer-earn-method'] == 'percentage') {
                                    $percentage = $config['refer-earn-bonus'];
                                    $bonus_amount = $res_boy[0]['total'] / 100 * $percentage;
                                    if ($bonus_amount > $config['max-refer-earn-amount']) {
                                        $bonus_amount = $config['max-refer-earn-amount'];
                                    }
                                } else {
                                    $bonus_amount = $config['refer-earn-bonus'];
                                }
                                $res_data = $function->get_data($columns = ['friends_code', 'name'], "id='" . $res[0]['user_id'] . "'", 'users');
                                $friend_user = $function->get_data($columns = ['id'], "referral_code='" . $res_data[0]['friends_code'] . "'", 'users');
                                if (!empty($friend_user))
                                    $function->add_wallet_transaction($id, 0, $friend_user[0]['id'], 'credit', floor($bonus_amount), 'Refer & Earn Bonus on first order by ' . ucwords($res_data[0]['name']), 'wallet_transactions');

                                $friend_code = $res_data[0]['friends_code'];
                                $sql = "UPDATE users SET balance = balance + floor($bonus_amount) WHERE referral_code='$friend_code' ";
                                $db->sql($sql);
                            }
                        }
                    }
                }
            }
            $temp = [];
            foreach ($status as $s) {
                array_push($temp, $s[0]);
            }
            if ($postStatus == 'cancelled') {
                if ($order_item_cancelled == true) {
                    if (!in_array('cancelled', $temp)) {
                        $status[] = array('cancelled', date("d-m-Y h:i:sa"));
                        $data = array(
                            'status' =>  $db->escapeString(json_encode($status)),
                        );
                    }
                    $db->update('order_items', $data, 'id=' . $order_item_id);
                }
            }


            if ($postStatus == 'processed') {
                if (!in_array('received', $temp)) {
                    $status[] = array('received', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('processed', $temp)) {
                    $status[] = array('processed', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                $db->update('order_items', $data, 'id=' . $order_item_id);
            }

            if ($postStatus == 'received') {
                if (!in_array('received', $temp)) {
                    $status[] = array('received', date("d-m-Y h:i:sa"));
                    $data = array(
                        'status' => $db->escapeString(json_encode($status)),
                        'active_status' => 'received'
                    );
                }
                // $db->update('order_items', $data, 'id=' . $order_item_id);
                $db->update('order_items', $data, 'order_id=' . $id);
                // $db->update('order_items', "received", 'order_id=' . $id);

                /* get order data */
                $user_id1 = $function->get_data($columns = ['user_id', 'total', 'delivery_charge', 'discount', 'final_total', 'payment_method', 'address', 'otp'], 'id=' . $id, 'orders');

                /* get user data */
                $user_email = $function->get_data($columns = ['email', 'name'], 'id=' . $user_id1[0]['user_id'], 'users');
                $subject = "Order received successfully";

                /* get order item by order id */
                $order_item = $function->get_order_item_by_order_id($id);
                $item_ids = array_column($order_item, 'product_variant_id');

                /* get product details by varient id */
                $item_details = $function->get_product_by_variant_id(json_encode($item_ids));

                for ($i = 0; $i < count($item_details); $i++) {
                    $seller_id = $item_details[$i]['seller_id'];
                    if (!empty($seller_id)) {
                        $store_details = $function->get_data($columns = ['email', 'store_name'], 'id=' . $seller_id, 'seller');
                    }
                    $item_data1[] = array(
                        'name' => $item_details[$i]['name'], 'store_name' => $store_details[0]['store_name'], 'tax_amount' => $order_item[$i]['tax_amount'], 'tax_percentage' => $order_item[$i]['tax_percentage'], 'tax_title' => $item_details[$i]['tax_title'], 'unit' =>  $item_details[$i]['measurement'] . " " . $item_details[$i]['measurement_unit_name'],
                        'qty' => $order_item[$i]['quantity'], 'subtotal' => $order_item[$i]['sub_total']
                    );
                    if (!empty($seller_id)) {
                        $seller_subject = "New order placed for " . $store_details[0]['store_name'];
                        $seller_message = "New order item ID : #" . $order_item_id . " received please take note of it and proceed further";
                        send_email($store_details[0]['email'], $seller_subject, $seller_message);
                        $function->send_notification_to_seller($seller_id, $seller_subject, $seller_message, 'order', $order_item_id);
                        //  notification to seller test is  remain
                    }
                }
                $user_wallet_balance = $function->get_wallet_balance($user_id1[0]['user_id'], 'users');
                $user_msg = "Hello, Dear " . $user_email[0]['name'] . ", We have received your order successfully. Your order summaries are as followed:<br><br>";
                $otp_msg = "Here is your OTP. Please, give it to delivery boy only while getting your order.";

                $order_data = array('total_amount' => $user_id1[0]['total'], 'delivery_charge' => $user_id1[0]['delivery_charge'], 'discount' => $user_id1[0]['discount'], 'wallet_used' => $user_wallet_balance, 'final_total' => $user_id1[0]['final_total'], 'payment_method' => $user_id1[0]['payment_method'], 'address' => $user_id1[0]['address'], 'user_msg' => $user_msg, 'otp_msg' => $otp_msg, 'otp' => $user_id1[0]['otp']);
                $function->send_order_update_notification($user_id1[0]['user_id'], "Your order has been " . ucwords($postStatus), $user_msg, 'order', $id);
                $subject = "New order placed for $app_name";
                $message = "New order ID : #" . $id . " received please take note of it and proceed further";
                send_smtp_mail($user_email[0]['email'], $subject, $item_data1, $order_data);
                $function->send_notification_to_admin("New Order Arrived.", $message, "admin_notification", $id);
                send_email($support_email, $subject, $message);
            }

            if ($postStatus == 'shipped') {
                if (!in_array('received', $temp)) {
                    $status[] = array('received', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('processed', $temp)) {
                    $status[] = array('processed', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('shipped', $temp)) {
                    $status[] = array('shipped', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                $db->update('order_items', $data, 'id=' . $order_item_id);
            }
            if ($postStatus == 'delivered') {
                if (!in_array('received', $temp)) {
                    $status[] = array('received', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('processed', $temp)) {
                    $status[] = array('processed', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('shipped', $temp)) {
                    $status[] = array('shipped', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('delivered', $temp)) {
                    $status[] = array('delivered', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                $db->update('order_items', $data, 'id=' . $order_item_id);
                $item_data = array(
                    'status' => $db->escapeString(json_encode($status)),
                    'active_status' => 'delivered'
                );
            }
            // if ($postStatus == 'returned') {
            //     $status[] = array('returned', date("d-m-Y h:i:sa"));
            //     $data = array('status' => $db->escapeString(json_encode($status)));
            //     $db->update('order_items', $data, 'id=' . $order_item_id);
            //     $item_data = array(
            //         'status' => $db->escapeString(json_encode($status)),
            //         'active_status' => 'returned'
            //     );
            // }
            $i = sizeof($status);
            $currentStatus = $status[$i - 1][0];
            $final_status = array(
                'active_status' => $currentStatus
            );
            if ($db->update('order_items', $final_status, 'id=' . $order_item_id)) {
                $response['error'] = false;
                if ($postStatus == 'cancelled') {
                    $response['message'] = "Order has been cancelled!";
                } elseif ($postStatus == 'returned') {
                    $response['message'] = "Order item returned request received successfully! Please wait for approval.";
                } else {
                    $response['message'] = "Order updated successfully.";
                }
                if ($postStatus != 'received' && $postStatus != 'returned') {
                    $user_data = $function->get_data($columns = ['name', 'email', 'mobile', 'country_code'], 'id=' . $user_id, 'users');
                    $to = $user_data[0]['email'];
                    $mobile = $user_data[0]['mobile'];
                    $country_code = $user_data[0]['country_code'];
                    $subject = "Your order has been " . ucwords($postStatus);
                    $message = "Hello, Dear " . ucwords($user_data[0]['name']) . ", Here is the new update on your order for the order ID : #" . $id . ". Your order has been " . ucwords($postStatus) . ". Please take a note of it.";
                    $message .= "Thank you for using our services!You will receive future updates on your order via Email!";
                    $function->send_order_update_notification($user_id, "Your order has been " . ucwords($postStatus), $message, 'order', $id);
                    send_email($to, $subject, $message);
                    $message = "Hello, Dear " . ucwords($user_data[0]['name']) . ", Here is the new update on your order for the order ID : #" . $id . ". Your order has been " . ucwords($postStatus) . ". Please take a note of it.";
                    $message .= "Thank you for using our services! Contact us for more information";
                    // need to send notification to seller for update order
                }
                $res = $db->getResult();

                print_r(json_encode($response));
            } else {
                $response['error'] = true;
                $response['message'] = isset($_POST['delivery_boy_id']) && $_POST['delivery_boy_id'] != '' ? 'Delivery Boy updated, But could not update order status try again!' : 'Could not update order status try again!';
                print_r(json_encode($response));
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Sorry Invalid order ID";
            print_r(json_encode($response));
        }
    } else {
        if ($delivery_boy_id != 0 && $res_delivery_boy_id[0]['delivery_boy_id'] != $delivery_boy_id) {
            $response['error'] = false;
            $response['message'] = "Delivery boy updated successfully";
            print_r(json_encode($response));
        } else {
            $response['error'] = false;
            $response['message'] = "No changes were made";
            print_r(json_encode($response));
        }
    }
}
if (isset($_POST['update_order_items']) && $_POST['update_order_items'] == 1) {

    $order_items = $function->xss_clean_array($_POST['order_items']);
    $delivery_boy_id = "";
    $postStatus = "";

    if (isset($_POST['status']) && !empty($_POST['status'])) {
        $postStatus = $db->escapeString($_POST['status']);
    }
    if (isset($_POST['delivery_boy_id']) && !empty($_POST['delivery_boy_id'])) {
        $delivery_boy_id = $db->escapeString($_POST['delivery_boy_id']);
    }
    if (empty($delivery_boy_id) && empty($postStatus)) {
        $response['error'] = true;
        $response['message'] = '<p class="alert alert-info">No changes were made.</p>';
        print_r(json_encode($response));
        return false;
    }

    if (!empty($order_items)) {
        for ($j = 0; $j < count($order_items); $j++) {
            $order_item_id = $order_items[$j];
            $sql = 'select user_id,order_id,active_status,status from order_items where id = ' . $order_item_id;
            $db->sql($sql);
            $res_order_item = $db->getResult();

            $status = json_decode($res_order_item[0]['status']);

            if (!empty($_POST['status'])) {
                foreach ($status as $each) {
                    if (in_array($postStatus, $each)) {
                        $response['error'] = true;
                        $response['message'] = '<p class="alert alert-danger">Order already ' . $postStatus . ". Order item ID " . $order_item_id . '</p>';
                        print_r(json_encode($response));
                        return false;
                    }
                }
                /* Cannot return order unless it is delivered */
                if ($function->is_order_item_returned($res_order_item[0]['active_status'], $postStatus)) {
                    $response['error'] = true;
                    $response['message'] = '<p class="alert alert-danger">Cannot return order unless it is delivered! please check order item ID ' . $order_item_id . '</p>';
                    print_r(json_encode($response));
                    return false;
                }
            } else {
                foreach ($res_order_item as $order_status) {
                    $postStatus = $order_status['active_status'];
                }
            }

            $order_id = $res_order_item[0]['order_id'];
            $user_id = $res_order_item[0]['user_id'];
            if ($postStatus != '' && $postStatus == 'awaiting_payment') {
                $response['error'] = true;
                $response['message'] = "<p class='alert alert-danger'>Order can not be awaiting status. Because it is on " . $res_order_item[0]['active_status'] . ". Order item ID " . $order_item_id . '</p>';
                print_r(json_encode($response));
                return false;
            }


            if ($res_order_item[0]['active_status'] == 'delivered' && $postStatus == 'cancelled') {
                $response['error'] = true;
                $response['message'] = '';
                $response['message'] = '<p class="alert alert-danger">Could not cancel delivered item. please check order item ID ' . $order_item_id . '</p>';
                print_r(json_encode($response));
                return false;
            }
            /* check for awaiting status */
            if ($res_order_item[0]['active_status'] == 'awaiting_payment' && ($postStatus == 'returned' || $postStatus == 'delivered' || $postStatus == 'shipped' || $postStatus == 'processed')) {
                $response['error'] = true;
                $response['message'] = "<p class='alert alert-danger'>Order can not be $postStatus. Because it is on awaiting status. Order item ID " . $order_item_id . '</p>';
                print_r(json_encode($response));
                return false;
            }
            /* Could not update order status once cancelled or returned! */
            if ($function->is_order_item_cancelled($order_item_id)) {
                $response['error'] = true;
                $response['message'] = '<p class="alert alert-danger">Could not update order status  cancelled or returned! please check order item ID ' . $order_item_id . '</p>';
                print_r(json_encode($response));
                return false;
            }


            /* if given status is cancel or return */
            if ($postStatus == 'cancelled' || $postStatus == 'returned') {

                /* fetch order items details */
                $sql = 'SELECT oi.`id` as order_item_id,oi.`product_variant_id`,oi.`quantity`,oi.`user_id`,oi.`order_id`,pv.`product_id`,pv.`type`,pv.`stock`,pv.`stock_unit_id`,pv.`measurement`,pv.`measurement_unit_id` FROM `order_items` oi join `product_variant` pv on pv.id = oi.product_variant_id WHERE oi.`id`=' . $order_item_id;
                $db->sql($sql);
                $res_oi = $db->getResult();

                /* check for item cancellable or not */
                if ($postStatus == 'cancelled') {
                    if ($cancel_order_from == "") {
                        $cancelation_error = 0;
                        $resp = $function->is_product_cancellable($res_oi[0]['order_item_id']);
                        if ($resp['till_status_error'] == 1 || $resp['cancellable_status_error'] == 1) {
                            $cancelation_error = 1;
                        }
                        if ($cancelation_error == 1) {
                            $resp['error'] = true;
                            $resp['message'] = "<p class='alert alert-danger'>Order item ID " . $order_item_id . " is not cancelable or not matching cancelation criteria!</p>";
                            print_r(json_encode($resp));
                            return false;
                        }
                    }

                    if ($function->cancel_order_item($order_id, $order_item_id)) {
                        $order_item_cancelled = true;
                    } else {
                        $order_item_cancelled = false;
                    }
                } else if ($postStatus == 'returned') {
                    /* check for item returnable or not */
                    $return_error = 0;
                    $resp = $function->is_product_returnable($res_oi[0]['order_item_id']);
                    if ($resp['return_status_error'] == 1) {
                        $return_error = 1;
                    }
                    if ($return_error == 1) {
                        $resp['error'] = true;
                        $resp['message'] = "<p class='alert alert-danger'>Order item ID " . $order_item_id . " is not returnable</p>";
                        print_r(json_encode($resp));
                        return false;
                    }
                    if ($function->is_return_request_exists($res_oi[0]['user_id'], $order_item_id)) {
                        $response['error'] = true;
                        $response['message'] = '<p class="alert alert-danger">Order item ID ' . $order_item_id . ' Already applied for return.</p>';
                        print_r(json_encode($response));
                        return false;
                    }
                    $is_item_delivered = 0;
                    $product_details = $function->get_product_by_variant_id2($res_oi[0]['product_variant_id']);

                    // $return_days = $function->get_data($columns = ['return_days'], 'id=' . $product_details['product_id'], 'products');

                    $sql = 'select return_days from products where id = ' . $product_details['product_id'];
                    $db->sql($sql);
                    $return_days = $db->getResult();


                    $return_day = $return_days[0]['return_days'];
                    foreach ($status as $each_status) {
                        if (in_array('delivered', $each_status)) {
                            $is_item_delivered = 1;
                            $status_date = $each_status[1];
                            $status_date = date("d-m-Y", strtotime($status_date));
                            $last_date = date('Y-m-d', strtotime($status_date . ' + ' . $return_day . ' days'));
                            $today = date('d-m-Y');
                            $today = strval($today);
                            $last_date = strval($last_date);

                            $datediff = $now - $status_date;
                            $no_of_days = round($datediff / (60 * 60 * 24));
                            // echo $no_of_days;
                            if ($today > $status_date) {
                                $response['error'] = true;
                                $response['message'] = '<p class="alert alert-danger">Oops! Sorry you cannot return the item now. You have crossed product\'s maximum return period for order item ID ' . $order_item_id . '</p>';
                                print_r(json_encode($response));
                                return false;
                            }
                        }
                    }

                    /* store return request */
                    $function->store_return_request($res_oi[0]['user_id'], $res_oi[0]['order_id'], $order_item_id);

                    // $response['error'] = false;
                    // $response['message'] = '<Order item returned request received successfully! Please wait for approval.';

                    if ($res_oi[0]['type'] == 'packet') {
                        $sql = "UPDATE product_variant SET stock = stock + " . $res_oi[0]['quantity'] . " WHERE id='" . $res_oi[0]['product_variant_id'] . "'";
                        $db->sql($sql);
                        $sql = "select stock from product_variant where id=" . $res_oi[0]['product_variant_id'];
                        $db->sql($sql);
                        $res_stock = $db->getResult();
                        if ($res_stock[0]['stock'] > 0) {
                            $sql = "UPDATE product_variant set serve_for='Available' WHERE id='" . $res_oi[0]['product_variant_id'] . "'";
                            $db->sql($sql);
                        }
                    } else {
                        /* When product type is loose */
                        if ($res_oi[0]['measurement_unit_id'] != $res_oi[0]['stock_unit_id']) {
                            $stock = $function->convert_to_parent($res_oi[0]['measurement'], $res_oi[0]['measurement_unit_id']);
                            $stock = $stock * $res_oi[0]['quantity'];
                            $sql = "UPDATE product_variant SET stock = stock + " . $stock . " WHERE product_id='" . $res_oi[0]['product_id'] . "'" .  " AND id='" . $res_oi[0]['product_variant_id'] . "'";
                            $db->sql($sql);
                        } else {
                            $stock = $res_oi[0]['measurement'] * $res_oi[0]['quantity'];
                            $sql = "UPDATE product_variant SET stock = stock + " . $stock . " WHERE product_id='" . $res_oi[0]['product_id'] . "'" .  " AND id='" . $res_oi[0]['product_variant_id'] . "'";
                            $db->sql($sql);
                        }
                    }
                }
            }
            // $res_delivery_boy_id = $function->get_data($columns = ['active_status', 'status', 'delivery_boy_id'], 'id=' . $order_item_id, 'order_items');

            $sql = 'select active_status,status,delivery_boy_id from order_items where id = ' . $order_item_id;
            $db->sql($sql);
            $res_delivery_boy_id = $db->getResult();


            if (isset($_POST['delivery_boy_id']) && !empty($_POST['delivery_boy_id']) && $_POST['delivery_boy_id'] != "") {
                if ($res_delivery_boy_id[0]['active_status'] == "awaiting_payment") {
                    $response['error'] = true;
                    $response['message'] = "<p class='alert alert-danger'>You can not assign Delivery boy. Because order is on Awaiting status. Order item ID " . $order_item_id . '</p>';
                    print_r(json_encode($response));
                    return false;
                } else {
                    if ($res_delivery_boy_id[0]['active_status'] != 'cancelled') {
                        if ($postStatus != '') {
                            $delivery_boy_name = $function->get_data($columns = ['name'], 'id=' . $delivery_boy_id, 'delivery_boys');
                            if ($postStatus == 'delivered') {
                                $title_delivery_boy = "Order item with ID : #" . $order_item_id . "  has been delivered";
                                $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", your order has been delivered. ID : #" . $order_item_id . ". Please take a note of it.";
                            } else {
                                if ($postStatus == 'received') {
                                    $title_delivery_boy = "New Order item with ID : #" . $order_item_id . "  has been received";
                                    $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", You have new order to deliver. Here is your ID : #" . $order_item_id . ". Please take a note of it.";
                                } else {
                                    $title_delivery_boy = "Order item with ID : #" . $order_item_id . "  has been " . ucwords($postStatus);
                                    $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", Your order item ID #" . $order_item_id . ". has been " . $postStatus . " Please take a note of it.";
                                }
                            }

                            $function->send_notification_to_delivery_boy($delivery_boy_id, $title_delivery_boy, $message_delivery_boy, 'delivery_boys', $order_item_id);
                            $function->store_delivery_boy_notification($delivery_boy_id, $order_item_id, $title_delivery_boy, $message_delivery_boy, 'order_reward');
                        }

                        $sql = "UPDATE order_items SET `delivery_boy_id`='" . $delivery_boy_id . "' WHERE id=" . $order_item_id;
                        $db->sql($sql);
                    }
                }
            } else {
                if (isset($res_delivery_boy_id[0]['delivery_boy_id']) && !empty($res_delivery_boy_id[0]['delivery_boy_id'])) {
                    if ($postStatus != '') {
                        $delivery_boy_name = $function->get_data($columns = ['name'], 'id=' . $res_delivery_boy_id[0]['delivery_boy_id'], 'delivery_boys');
                        if ($postStatus == 'delivered') {
                            $title_delivery_boy = "Order item with ID : #" . $order_item_id . "  has been delivered";
                            $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", your order has been delivered. ID : #" . $order_item_id . ". Please take a note of it.";
                        } else {
                            if ($postStatus == 'received') {
                                $title_delivery_boy = "New Order item with ID : #" . $order_item_id . "  has been received";
                                $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", You have new order to deliver. Here is your ID : #" . $order_item_id . ". Please take a note of it.";
                            } else {
                                $title_delivery_boy = "Order item with ID : #" . $order_item_id . "  has been " . ucwords($postStatus);
                                $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", Your order item ID #" . $order_item_id . ". has been " . $postStatus . " Please take a note of it.";
                            }
                        }
                        $function->send_notification_to_delivery_boy($res_delivery_boy_id[0]['delivery_boy_id'], $title_delivery_boy, $message_delivery_boy, 'delivery_boys', $order_item_id);
                        $function->store_delivery_boy_notification($res_delivery_boy_id[0]['delivery_boy_id'], $order_item_id, $title_delivery_boy, $message_delivery_boy, 'order_reward');
                    }
                }
            }
            if ($postStatus == 'delivered') {
                $sql = "SELECT oi.order_id,oi.user_id,oi.delivery_boy_id,oi.sub_total,o.final_total,o.total,o.payment_method,o.delivery_charge FROM orders o join order_items oi on oi.order_id=o.id WHERE oi.id=" . $order_item_id;
                $db->sql($sql);
                $res_boy = $db->getResult();
                if ($res_boy[0]['delivery_boy_id'] != 0) {
                    if (strtolower($res_boy[0]['payment_method']) == 'cod') {
                        $cash_received = $res_boy[0]['sub_total'] + $res_boy[0]['delivery_charge'];
                        $sql = "UPDATE delivery_boys SET cash_received = cash_received + $cash_received WHERE id=" . $res_boy[0]['delivery_boy_id'];
                        $db->sql($sql);
                        $function->add_transaction($order_item_id, $res_boy[0]['delivery_boy_id'], 'delivery_boy_cash', $cash_received, 'Delivery boy collected COD');
                    }
                    $res_bonus = $function->get_data($columns = ['name', 'bonus'], 'id=' . $res_boy[0]['delivery_boy_id'], 'delivery_boys');
                    $sql = 'select name,bonus from delivery_boys where id = ' . $res_boy[0]['delivery_boy_id'];
                    $db->sql($sql);
                    $res_bonus = $db->getResult();
                    $reward = $res_boy[0]['sub_total'] / 100 * $res_bonus[0]['bonus'];
                    if ($reward > 0) {
                        $sql = "UPDATE delivery_boys SET balance = balance + $reward WHERE id=" . $res_boy[0]['delivery_boy_id'];
                        $db->sql($sql);
                        $delivery_boy_id = $res_boy[0]['delivery_boy_id'];
                        $comission = $function->add_delivery_boy_commission($delivery_boy_id, 'credit', $reward, 'Order Delivery Commission.');
                        $currency = $function->get_settings('currency');
                        $message_delivery_boy = "Hello, Dear " . ucwords($res_bonus[0]['name']) . ", Here is the new update on your order for the order item ID : #" . $order_item_id . ". Your Commission of" . $reward . " is credited. Please take a note of it.";
                        $function->send_notification_to_delivery_boy($delivery_boy_id, "Your commission " . $reward . " " . $currency . " has been credited", "$message_delivery_boy", 'delivery_boys', $order_item_id);
                        $function->store_delivery_boy_notification($delivery_boy_id, $order_item_id, "Your commission " . $reward . " " . $currency . " has been credited", $message_delivery_boy, 'order_reward');
                    }
                }
                $sql = "SELECT COUNT(id) as total FROM `order_items` WHERE user_id=" . $res_boy[0]['user_id'] . " && status LIKE '%delivered%'";
                $db->sql($sql);
                $res_count = $db->getResult();

                $sql = "SELECT friends_code,referral_code FROM `users` WHERE id=" . $user_id;
                $db->sql($sql);
                $res_user = $db->getResult();
                /* referal system processing */
                if ($config['is-refer-earn-on'] == 1) {
                    if ($res_boy[0]['total'] >= $config['min-refer-earn-order-amount']) {
                        if ($res_count[0]['total'] == 0) {
                            if ($res_user[0]['friends_code'] != '') {
                                if ($config['refer-earn-method'] == 'percentage') {
                                    $percentage = $config['refer-earn-bonus'];
                                    $bonus_amount = $res_boy[0]['total'] / 100 * $percentage;
                                    if ($bonus_amount > $config['max-refer-earn-amount']) {
                                        $bonus_amount = $config['max-refer-earn-amount'];
                                    }
                                } else {
                                    $bonus_amount = $config['refer-earn-bonus'];
                                }
                                $res_data = $function->get_data($columns = ['friends_code', 'name'], "referral_code='" . $res_user[0]['referral_code'] . "'", 'users');
                                $friend_user = $function->get_data($columns = ['id'], "referral_code='" . $res_data[0]['friends_code'] . "'", 'users');
                                if (!empty($friend_user))
                                    $function->add_wallet_transaction($res_boy[0]['order_id'], $order_item_id, $friend_user[0]['id'], 'credit', floor($bonus_amount), 'Refer & Earn Bonus on first order by ' . ucwords($res_data[0]['name']), 'wallet_transactions');

                                $friend_code = $res_data[0]['friends_code'];
                                $sql = "UPDATE users SET balance = balance + floor($bonus_amount) WHERE referral_code='$friend_code' ";
                                $db->sql($sql);
                            }
                        }
                    }
                }
            }
            $temp = [];
            foreach ($status as $s) {
                array_push($temp, $s[0]);
            }
            if ($postStatus == 'cancelled') {
                if ($order_item_cancelled == true) {
                    if (!in_array('cancelled', $temp)) {
                        $status[] = array('cancelled', date("d-m-Y h:i:sa"));
                        $data = array(
                            'status' =>  $db->escapeString(json_encode($status)),
                        );
                    }
                    $db->update('order_items', $data, 'id=' . $order_item_id);
                }
            }

            if ($postStatus == 'received') {
                if (!in_array('received', $temp)) {
                    $status[] = array('received', date("d-m-Y h:i:sa"));
                    $data = array(
                        'status' => $db->escapeString(json_encode($status)),
                        'active_status' => 'received'
                    );
                }
                // $db->update('order_items', $data, 'id=' . $order_item_id);
                $db->update('order_items', $data, 'order_id=' . $order_id);
                // $db->update('order_items', "received", 'order_id=' . $id);

                /* get order data */
                $user_id1 = $function->get_data($columns = ['user_id', 'total', 'delivery_charge', 'discount', 'final_total', 'payment_method', 'address', 'otp'], 'id=' . $order_id, 'orders');

                /* get user data */
                $user_email = $function->get_data($columns = ['email', 'name'], 'id=' . $user_id1[0]['user_id'], 'users');
                $subject = "Order received successfully";

                /* get order item by order id */
                $order_item = $function->get_order_item_by_order_id($order_id);
                $item_ids = array_column($order_item, 'product_variant_id');

                /* get product details by varient id */
                $item_details = $function->get_product_by_variant_id(json_encode($item_ids));
                for ($i = 0; $i < count($item_details); $i++) {
                    if (!empty($item_details[$i])) {
                        $seller_id = $item_details[$i]['seller_id'];
                        if (!empty($seller_id)) {
                            $store_details = $function->get_data($columns = ['email', 'store_name'], 'id=' . $seller_id, 'seller');
                        }
                        $item_data1[] = array(
                            'name' => $item_details[$i]['name'], 'store_name' => $store_details[0]['store_name'], 'tax_amount' => $order_item[$i]['tax_amount'], 'tax_percentage' => $order_item[$i]['tax_percentage'], 'tax_title' => $item_details[$i]['tax_title'], 'unit' =>  $item_details[$i]['measurement'] . " " . $item_details[$i]['measurement_unit_name'],
                            'qty' => $order_item[$i]['quantity'], 'subtotal' => $order_item[$i]['sub_total']
                        );
                        if (!empty($seller_id)) {
                            $seller_subject = "New order placed for " . $store_details[0]['store_name'];
                            $seller_message = "New order item ID : #" . $order_item_id . " received please take note of it and proceed further";
                            send_email($store_details[0]['email'], $seller_subject, $seller_message);
                            $function->send_notification_to_seller($seller_id, $seller_subject, $seller_message, 'order', $order_item_id);
                            //  notification to seller test is  remain
                        }
                    }
                }
                $user_wallet_balance = $function->get_wallet_balance($user_id1[0]['user_id'], 'users');
                $user_msg = "Hello, Dear " . $user_email[0]['name'] . ", We have received your order successfully. Your order summaries are as followed:<br><br>";
                $otp_msg = "Here is your OTP. Please, give it to delivery boy only while getting your order.";

                $order_data = array('total_amount' => $user_id1[0]['total'], 'delivery_charge' => $user_id1[0]['delivery_charge'], 'discount' => $user_id1[0]['discount'], 'wallet_used' => $user_wallet_balance, 'final_total' => $user_id1[0]['final_total'], 'payment_method' => $user_id1[0]['payment_method'], 'address' => $user_id1[0]['address'], 'user_msg' => $user_msg, 'otp_msg' => $otp_msg, 'otp' => $user_id1[0]['otp']);
                $function->send_order_update_notification($user_id1[0]['user_id'], "Your order has been " . ucwords($postStatus), $user_msg, 'order', $order_id);
                $subject = "New order placed for $app_name";
                $message = "New order ID : #" . $order_id . " received please take note of it and proceed further";
                send_smtp_mail($user_email[0]['email'], $subject, $item_data1, $order_data);
                $function->send_notification_to_admin("New Order Arrived.", $message, "admin_notification", $order_id);
                send_email($support_email, $subject, $message);
            }

            if ($postStatus == 'processed') {
                if (!in_array('received', $temp)) {
                    $status[] = array('received', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('processed', $temp)) {
                    $status[] = array('processed', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                $db->update('order_items', $data, 'id=' . $order_item_id);
            }

            if ($postStatus == 'shipped') {
                if (!in_array('received', $temp)) {
                    $status[] = array('received', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('processed', $temp)) {
                    $status[] = array('processed', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('shipped', $temp)) {
                    $status[] = array('shipped', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                $db->update('order_items', $data, 'id=' . $order_item_id);
            }
            if ($postStatus == 'delivered') {
                if (!in_array('received', $temp)) {
                    $status[] = array('received', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('processed', $temp)) {
                    $status[] = array('processed', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('shipped', $temp)) {
                    $status[] = array('shipped', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                if (!in_array('delivered', $temp)) {
                    $status[] = array('delivered', date("d-m-Y h:i:sa"));
                    $data = array('status' => $db->escapeString(json_encode($status)));
                }
                $db->update('order_items', $data, 'id=' . $order_item_id);
                $item_data = array(
                    'status' => $db->escapeString(json_encode($status)),
                    'active_status' => 'delivered'
                );
            }
            if ($postStatus == 'returned') {
                $status[] = array('returned', date("d-m-Y h:i:sa"));
                $data = array('status' => $db->escapeString(json_encode($status)));
                $db->update('order_items', $data, 'id=' . $order_item_id);
                $item_data = array(
                    'status' => $db->escapeString(json_encode($status)),
                    'active_status' => 'returned'
                );
            }
            $i = sizeof($status);
            $currentStatus = $status[$i - 1][0];
            $final_status = array(
                'active_status' => $currentStatus
            );
            if ($db->update('order_items', $final_status, 'id=' . $order_item_id)) {

                if ($postStatus != 'received') {
                    $user_data = $function->get_data($columns = ['name', 'email', 'mobile', 'country_code'], 'id=' . $user_id, 'users');
                    $to = $user_data[0]['email'];
                    $mobile = $user_data[0]['mobile'];
                    $country_code = $user_data[0]['country_code'];
                    $subject = "Your order has been " . ucwords($postStatus);
                    $message = "Hello, Dear " . ucwords($user_data[0]['name']) . ", Here is the new update on your order for the order ID : #" . $order_id . ". Your order has been " . ucwords($postStatus) . ". Please take a note of it.";
                    $message .= "Thank you for using our services!You will receive future updates on your order via Email!";
                    $function->send_order_update_notification($user_id, "Your order has been " . ucwords($postStatus), $message, 'order', $order_id);
                    send_email($to, $subject, $message);
                    $message = "Hello, Dear " . ucwords($user_data[0]['name']) . ", Here is the new update on your order for the order ID : #" . $order_id . ". Your order has been " . ucwords($postStatus) . ". Please take a note of it.";
                    $message .= "Thank you for using our services! Contact us for more information";
                    // need to send notification to seller for update order
                }
                $res = $db->getResult();
            }
        }
        $response['error'] = false;
        $response['message'] = '<p class="alert alert-success">Order items updated successfully</p>';
        print_r(json_encode($response));
        return false;
    } else {
        $response['error'] = true;
        $response['message'] = "<p class='alert alert-danger'>No item(s) selected to update</p>";
        print_r(json_encode($response));
        return false;
    }
}

if (isset($_POST['get_orders']) && isset($_POST['user_id'])) {
    /*
        accesskey:90336
        get_orders:1
        order_id:123  // {optional}
        user_id:101
        limit:20    // {optional}
        offset:5    // {optional}
    */
    if (!verify_token()) {
        return false;
    }
    $where = '';
    $user_id = $db->escapeString($function->xss_clean($_POST['user_id']));
    $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id']) && is_numeric($_POST['order_id'])) ? $db->escapeString($function->xss_clean($_POST['order_id'])) : "";
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($function->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($function->xss_clean($_POST['offset'])) : 0;
    $where = !empty($order_id) ? " AND o.id = " . $order_id : "";
    $sql = "select count(o.id) as total from orders o where user_id=" . $user_id . $where;
    $db->sql($sql);
    $res = $db->getResult();
    $total = $res[0]['total'];
    $sql = "select o.*,obt.message as bank_transfer_message,obt.status as bank_transfer_status,(select name from users u where u.id=o.user_id) as user_name from orders o LEFT JOIN order_bank_transfers obt ON obt.order_id=o.id where user_id=" . $user_id . $where . " ORDER BY date_added DESC LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();
    $i = 0;
    $j = 0;


    $res = $function->get_orders($user_id, $order_id, $limit, $offset);

    print_r(json_encode($res));
}

if (isset($_POST['get_reorder_data']) && !empty($_POST['get_reorder_data'])) {
    if (!verify_token()) {
        return false;
    }
    $id = $db->escapeString($function->xss_clean($_POST['id']));
    $sql = "select * from `orders` where id=$id";
    $db->sql($sql);
    $res = $db->getResult();
    if (empty($res)) {
        $response['error'] = true;
        $response['message'] = "Sorry Invalid order ID";
        print_r(json_encode($response));
    } else {
        $sql = "select * from `order_items` where order_id=$id";
        $db->sql($sql);
        $order_items = $db->getResult();

        $items = $temp = [];
        foreach ($order_items as $item) {
            $temp['product_variant_id'] = $item['product_variant_id'];
            $temp['quantity'] = $item['quantity'];
            $items[] = $temp;
        }
        unset($res[0]['status']);
        unset($res[0]['active_status']);

        $res[0]['items'] = $items;
        $response['error'] = true;
        $response['message'] = "Order data retrived successfully";
        $response['data'] = $res[0];
        print_r(json_encode($response));
    }
}

if (isset($_POST['update_order_total_payable']) && isset($_POST['id'])) {

    $id = $db->escapeString($function->xss_clean($_POST['id']));
    $discount = $db->escapeString($function->xss_clean($_POST['discount']));
    // $deliver_by = $db->escapeString($function->xss_clean($_POST['deliver_by']));
    $total_payble = $db->escapeString($function->xss_clean($_POST['total_payble']));
    $total_payble = round($total_payble, 2);
    // $data = array(
    //     'discount' => $discount,
    //     'deliver_by' => $deliver_by,
    // );
    $data1 = array(
        'discount' => $discount,
        'final_total' => $total_payble,
    );


    if ($discount >= 0) {
        // $db->update('order_items', $data, 'order_id=' . $id);
        $db->update('orders', $data1, 'id=' . $id);
        $res = $db->getResult();
        if (!empty($res)) {
            $response['error'] = false;
            $response['message'] = "Discount updated successfully.";
            print_r(json_encode($response));
        } else {
            $response['error'] = true;
            $response['message'] = "Could not update order. Try again!";
            print_r(json_encode($response));
        }
    }
}

if (isset($_POST['add_transaction']) && $_POST['add_transaction'] == true) {
    if (!verify_token()) {
        return false;
    }
    /*add data to transaction table*/
    $user_id = $db->escapeString($function->xss_clean($_POST['user_id']));
    $order_id = $db->escapeString($function->xss_clean($_POST['order_id']));
    $type = $db->escapeString($function->xss_clean($_POST['type']));
    $txn_id = $db->escapeString($function->xss_clean($_POST['txn_id']));
    $amount = $db->escapeString($function->xss_clean($_POST['amount']));
    $status = $db->escapeString($function->xss_clean($_POST['status']));
    $message = $db->escapeString($function->xss_clean($_POST['message']));
    $transaction_date = (isset($_POST['transaction_date']) && !empty($_POST['transaction_date'])) ? $db->escapeString($function->xss_clean($_POST['transaction_date'])) : date('Y-m-d H:i:s');
    $data = array(
        'user_id' => $user_id,
        'order_id' => $order_id,
        'type' => $type,
        'txn_id' => $txn_id,
        'amount' => $amount,
        'status' => $status,
        'message' => $message,
        'transaction_date' => $transaction_date
    );
    $db->insert('transactions', $data);
    $res = $db->getResult();
    $response['error'] = false;
    $response['transaction_id'] = $res[0];
    $response['message'] = "Transaction added successfully!";
    echo json_encode($response);
}

/* 
	accesskey:90336
	delete_order:1 
    order_id:73
*/
if (isset($_POST['delete_order']) && $_POST['delete_order'] == true) {
    if (!verify_token()) {
        return false;
    }
    /*add data to transaction table*/

    $order_id = $db->escapeString($function->xss_clean($_POST['order_id']));

    // delete data from pemesanan table
    $sql_query = "DELETE FROM orders WHERE ID =" . $order_id;
    if ($db->sql($sql_query)) {
        $sql = "DELETE FROM order_items WHERE order_id =" . $order_id;
        $db->sql($sql);

        $response['error'] = false;
        $response['message'] = "Order deleted successfully!";
    } else {
        $response['error'] = true;
        $response['message'] = "Order does not deleted!";
    }
    echo json_encode($response);
}

if (isset($_POST['test']) && $_POST['test'] == true) {
    $res = send_smtp_mail("test", "hello", "admin_notification", 12);
    print_r($res);
}
if (isset($_POST['create_shiprocket_order']) && $_POST['create_shiprocket_order'] == 1) {
    $seller_id = $db->escapeString($function->xss_clean($_POST['seller_id']));
    $item_id = $db->escapeString($function->xss_clean($_POST['item_id']));
    $pickup_postal_code = $db->escapeString($function->xss_clean($_POST['pickup_postal_code']));
    $pickup_location_id = $db->escapeString($function->xss_clean($_POST['pickup_location']));
    $weight = $db->escapeString($function->xss_clean($_POST['weight']));
    $length = $db->escapeString($function->xss_clean($_POST['length']));
    $breadth = $db->escapeString($function->xss_clean($_POST['breadth']));
    $height = $db->escapeString($function->xss_clean($_POST['height']));
    $token = $shiprocket->generate_token();
    $token = $token['token'];

    $sql = "select delivery_boy_id,shipping_method from order_items where id=" . $item_id;
    $db->sql($sql);
    $res = $db->getResult();
    if ($res[0]['delivery_boy_id'] == 0) {
        // if ($res[0]['shipping_method'] == 'regular') {
        $sql = "select date_added,sub_total,product_name,quantity,discounted_price,order_id,user_id from order_items where id=" . $item_id;
        $db->sql($sql);
        $res_oi = $db->getResult();

        $order_id = $res_oi[0]['order_id'];
        $user_id = $res_oi[0]['user_id'];
        $product_name = $res_oi[0]['product_name'];
        $quantity = $res_oi[0]['quantity'];
        $discounted_price = $res_oi[0]['discounted_price'];
        $sub_total = $res_oi[0]['sub_total'];
        $order_date = $res_oi[0]['date_added'];

        $sql = "select payment_method,pincode_id,area_id from orders where id=" . $order_id;
        $db->sql($sql);
        $res = $db->getResult();

        $pincode_id = $res[0]['pincode_id'];
        $area_id = $res[0]['area_id'];
        $payment_method = strtolower($res[0]['payment_method'] == 'cod') ? 'COD' : 'Prepaid';
        $cod = strtolower($res[0]['payment_method'] == 'cod') ? 1 : 0;

        $sql = "select ua.*,c.name as billing_city,p.pincode as billing_pincode,ua.state as billing_state,ua.country as billing_country,u.email as billing_email,ua.mobile as billing_phone,ua.address as billing_address from user_addresses ua left join cities c on ua.city_id=c.id left join pincodes p on ua.pincode_id=p.id left join users u on ua.user_id=u.id where ua.user_id=" . $user_id . " and ua.pincode_id=" . $pincode_id . " and ua.area_id=" . $area_id;
        $db->sql($sql);
        $res = $db->getResult();
        $res = $res[0];

        $sql = "select pickup_location from pickup_locations where id=" . $pickup_location_id . " and seller_id=" . $seller_id;
        $db->sql($sql);
        $pickup_location = $db->getResult();
        if (isset($pickup_location[0]) && !empty($pickup_location[0])) {
            $pickup_location = $pickup_location[0]['pickup_location'];
        } else {
            $response['error'] = true;
            $response['message'] = '<p class="alert alert-danger">Invalid pickup location</p>';
            echo json_encode($response);
            return false;
        }
        $billing_customer_name = $res['name'];
        $billing_city = $res['billing_city'];
        $billing_pincode = $res['billing_pincode'];
        $billing_state = $res['billing_state'];
        $billing_country = $res['billing_country'];
        $billing_email = $res['billing_email'];
        $billing_phone = $res['billing_phone'];
        $billing_address = $res['billing_address'];
        $data = array(
            'order_id' => $item_id,
            'order_date' => $order_date,
            'pickup_location' => $pickup_location,
            'billing_customer_name' => $billing_customer_name,
            'billing_last_name' => $billing_customer_name,
            'billing_address' => $billing_address,
            'billing_phone' => $billing_phone,
            'billing_city' => $billing_city,
            'billing_pincode' => $billing_pincode,
            'billing_state' => $billing_state,
            'billing_country' => $billing_country,
            'billing_email' => $billing_email,
            'shipping_is_billing' => true,
            'order_items' => array(
                [
                    'name' => $product_name,
                    'sku' => '000',
                    'units' => $quantity,
                    'selling_price' => $discounted_price
                ]

            ),
            'payment_method' => $payment_method,
            'sub_total' => $sub_total,
            'length' => $length,
            'breadth' => $breadth,
            'height' => $height,
            'weight' => $weight
        );

        //checking serviceability
        $serviceability_data = array(
            'pickup_postal_code' => $pickup_postal_code,
            'billing_pincode' => $billing_pincode,
            'cod' => $cod,
            'weight' => $weight,
            'length' => $length,
            'breadth' => $breadth,
            'height' => $height

        );
        $serviceability = $shiprocket->check_serviceability($serviceability_data, $token);
        $serviceability = json_decode($serviceability, 1);
        if (!empty($serviceability) && isset($serviceability['status']) && $serviceability['status'] == 200) {
            //create shiprocket order
            $res = $shiprocket->create_order($data, $token);
            $res = json_decode($res, 1);

            if (!empty($res) && isset($res['order_id']) && !empty($res['order_id'])) {
                $data = array(
                    'order_id' => $order_id,
                    'order_item_id' => $item_id,
                    'shiprocket_order_id' => $res['order_id'],
                    'shipment_id' => $res['shipment_id'],
                    'awb_code' => $res['awb_code'],
                    'courier_company_id' => $serviceability['data']['recommended_courier_company_id']
                );
                $db->insert('order_trackings', $data);
                $data = array(
                    'shipping_method' => 'standard',
                    'shipment_id' => $res['shipment_id']

                );
                $db->update("order_items", $data, "id=$item_id");
                $response['error'] = false;
                $response['message'] = '<label class="alert alert-success text-center">Shiprocket order created successfully</label>';
                echo json_encode($response);
                return false;
            } else {
                $error_msg = '';
                if (isset($res['errors']) && !empty($res['errors'])) {
                    foreach ($res['errors'] as $error) {
                        for ($i = 0; $i < count($error); $i++) {

                            $error_msg .= $error[$i] . '<br>';
                        }
                    }
                } else {
                    if (isset($res['message']) && !empty($res['message'])) {
                        $error_msg = $res['message'];
                    } else {
                        $error_msg = "Something went wrong";
                    }
                }
                $response['error'] = true;
                $response['message'] = '<label class="alert alert-danger text-center">' . $error_msg . '</label>';
            }
        } else {
            $error_msg = '';
            if (isset($serviceability['errors']) && !empty($serviceability['errors'])) {
                foreach ($serviceability['errors'] as $error) {
                    for ($i = 0; $i < count($error); $i++) {

                        $error_msg .= $error[$i] . '<br>';
                    }
                }
            } else {
                if (isset($serviceability['message']) && !empty($serviceability['message'])) {
                    $error_msg = $serviceability['message'];
                } else {
                    $error_msg = "Something went wrong";
                }
            }
            $response['error'] = true;
            $response['message'] = '<label class="alert alert-danger text-center">' . $error_msg . '</label>';
        }
        // } else {
        //     $response['error'] = true;
        //     $response['message'] = '<label class="alert alert-danger text-center">Shiprocket order already created for this item</label>';
        // }
    } else {
        $response['error'] = true;
        $response['message'] = '<label class="alert alert-danger text-center">Regular delivery method is already choosen for this item</label>';
    }

    echo json_encode($response);
    return false;
}


if (isset($_POST['add_pickup_location']) && $_POST['add_pickup_location'] == 1) {
    // echo "test";
    // return false;
    $seller_id = $db->escapeString($function->xss_clean($_POST['seller_id']));
    $pickup_location = $db->escapeString($function->xss_clean($_POST['pickup_location']));
    $name = $db->escapeString($function->xss_clean($_POST['name']));
    $email = $db->escapeString($function->xss_clean($_POST['email']));
    $phone = $db->escapeString($function->xss_clean($_POST['phone']));
    $city = $db->escapeString($function->xss_clean($_POST['city']));
    $state = $db->escapeString($function->xss_clean($_POST['state']));
    $country = $db->escapeString($function->xss_clean($_POST['country']));
    $pin_code = $db->escapeString($function->xss_clean($_POST['pin_code']));
    $address = $db->escapeString($function->xss_clean($_POST['address']));
    $address_2 = $db->escapeString($function->xss_clean($_POST['address_2']));
    $latitude = $db->escapeString($function->xss_clean($_POST['latitude']));
    $longitude = $db->escapeString($function->xss_clean($_POST['longitude']));

    $slug = $pickup_location . " " . $seller_id;
    $pickup_location = $fn->slugify($slug);

    // echo $pickup_location;
    $pickup_location_data = array(
        'pickup_location' => $pickup_location,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'pin_code' => $pin_code,
        'address' => $address,
        'address_2' => $address_2,
        'latitude' => $latitude,
        'longitude' => $longitude
    );
    // echo $token;
    // return false;


    $pickup_location = $shiprocket->add_pickup_location($pickup_location_data);

    if (isset($pickup_location['success']) && $pickup_location['success'] == 1) {
        $pickup_location_data['seller_id'] = $seller_id;
        $db->insert('pickup_locations', $pickup_location_data);
        $response['error'] = false;
        $response['message'] = '<label class="alert alert-success">Pickup Location Added Successfully!</label>';
    } else {
        $error_msg = '';
        if (isset($pickup_location['errors']) && !empty($pickup_location['errors'])) {
            foreach ($pickup_location['errors'] as $error) {
                for ($i = 0; $i < count($error); $i++) {
                    $error_msg .= $error[$i] . '<br>';
                }
            }
        } else {
            if (isset($pickup_location['message']) && !empty($pickup_location['message'])) {
                $error_msg = $pickup_location['message'];
            } else {
                $error_msg = "Something went wrong";
            }
        }
        $response['error'] = true;
        $response['message'] = '<label class="alert alert-danger">' . $error_msg . '</label>';
    }
    echo json_encode($response);
    return false;
}

if (isset($_POST['send_pickup_request']) && $_POST['send_pickup_request'] == 1) {
    // echo "test";
    // return false;
    $item_id = $db->escapeString($function->xss_clean($_POST['item_id']));
    $seller_id = $db->escapeString($function->xss_clean($_POST['seller_id']));
    $shipment_id = $db->escapeString($function->xss_clean($_POST['shipment_id']));
    $courier_id = $db->escapeString($function->xss_clean($_POST['courier_company_id']));
    $data = array(
        'shipment_id' => $shipment_id
    );
    $data_awb = array(
        'shipment_id' => $shipment_id,
        'courier_id' => $courier_id
    );
    $sql = "select pickup_request from order_items where id=" . $item_id;
    $db->sql($sql);
    $res = $db->getResult();
    if ($res[0]['pickup_request'] == 0) {
        $token = $shiprocket->generate_token();
        $token = $token['token'];
        // echo $token;
        // return false;
        $response = $shiprocket->send_pickup_request($data, $data_awb, $token);
        $response = json_decode($response, 1);
        print_r($response);


        return false;
    } else {
        $response['error'] = true;
        $response['message'] = 'Pickup request already sent for this item';
    }
    echo json_encode($response);
    return false;
}

function findKey($array, $keySearch)
{
    foreach ($array as $key => $item) {
        if ($key == $keySearch) {
            return true;
        } elseif (is_array($item) && findKey($item, $keySearch)) {
            return true;
        }
    }
    return false;
}

if (isset($_POST['upload_bank_transfers_attachment']) && $_POST['upload_bank_transfers_attachment'] == 1) {
    /*  
    upload_bank_transfers_attachment
        accesskey:90336
        upload_bank_transfers_attachment:1
        order_id:1
        image[]:FILE
    */

    if (empty($_POST['order_id']) || empty($_FILES['image'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $error['image'] = '';
    $order_id = $db->escapeString($function->xss_clean($_POST['order_id']));
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        for ($i = 0; $i < count($_FILES["image"]["name"]); $i++) {
            if ($_FILES["image"]["error"][$i] > 0) {
                $response['error'] = true;
                $response['message'] = "Images not uploaded!";
                print_r(json_encode($response));
                return false;
            } else {
                $result = $function->validate_other_images($_FILES["image"]["tmp_name"][$i], $_FILES["image"]["type"][$i]);
                if ($result) {
                    $response['error'] = true;
                    $response['message'] = "image type must jpg, jpeg, gif, or png!";
                    print_r(json_encode($response));
                    return false;
                }
            }
        }
    }

    if (isset($_FILES['image']) && ($_FILES['image']['size'][0] > 0)) {
        $file_data = array();
        $target_path = '../upload/attachments/';
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $target_path1 = 'upload/attachments/';
        for ($i = 0; $i < count($_FILES["image"]["name"]); $i++) {
            $filename = $_FILES["image"]["name"][$i];
            $temp = explode('.', $filename);
            $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
            $file_data[] = $target_path1 . '' . $filename;
            if (!move_uploaded_file($_FILES["image"]["tmp_name"][$i], $target_path . '' . $filename)) {
                $response['error'] = true;
                $response['message'] = "Images not uploaded!!!";
                print_r(json_encode($response));
                return false;
            }
        }
        for ($i = 0; $i < count($file_data); $i++) {
            $data = array(
                'order_id' => $order_id,
                'attachment' => $file_data[$i],
            );
            $db->insert('order_bank_transfers', $data);
        }
        $result = $db->getResult();
    }

    $sql = "select o.*,(select name from users u where u.id=o.user_id) as user_name,(select email from users u where u.id=o.user_id) as user_email from orders o where o.id=" . $order_id . " ORDER BY date_added DESC";
    $db->sql($sql);
    $res = $db->getResult();
    $i = 0;
    $j = 0;
    foreach ($res as $row) {
        $final_sub_total = 0;
        $sub_total = 0;
        if ($row['discount'] > 0) {
            $discounted_amount = $row['total'] * $row['discount'] / 100;
            $final_total = $row['total'] - $discounted_amount;
            $discount_in_rupees = $row['total'] - $final_total;
        } else {
            $discount_in_rupees = 0;
        }

        $sql_query = "SELECT id,attachment FROM order_bank_transfers WHERE order_id = " . $row['id'];
        $db->sql($sql_query);
        $res_attac = $db->getResult();

        $myData = array();
        foreach ($res_attac as $item) {
            array_push($myData, ['id' => $item['id'], 'image' => DOMAIN_URL . $item['attachment']]);
        }
        $body1 = json_encode($myData);
        $body = json_decode($body1);

        $res[$i]['attachment'] = $body;

        $res[$i]['discount_rupees'] = "$discount_in_rupees";
        $final_total = ceil($res[$i]['final_total']);
        $res[$i]['final_total'] = "$final_total";
        $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));
        $sql = "select oi.*,v.id as variant_id, p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where order_id=" . $row['id'];
        $db->sql($sql);
        $res[$i]['items'] = $db->getResult();
        $res[$i]['status'] = json_decode($res[$i]['status']);
        unset($res[$i]['status']);
        unset($res[$i]['active_status']);
        for ($j = 0; $j < count($res[$i]['items']); $j++) {
            $res[$i]['items'][$j]['status'] = (!empty($res[$i]['items'][$j]['status'])) ? json_decode($res[$i]['items'][$j]['status']) : array();
            if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                $sub_total += $res[$i]['items'][$j]['sub_total'];
            }
            if (!empty($res[$i]['items'][$j]['status'])) {
                if (count($res[$i]['items'][$j]['status']) > 1) {
                    if (in_array("awaiting_payment", $res[$i]['items'][$j]['status'][0]) && in_array("received", $res[$i]['items'][$j]['status'][1])) {
                        unset($res[$i]['items'][$j]['status'][0]);
                    }
                    $res[$i]['items'][$j]['status'] = array_values($res[$i]['items'][$j]['status']);
                }
            } else {
                $res[$i]['items'][$j]['status'] = array();
            }

            $res[$i]['items'][$j]['delivery_boy_id'] = (!empty($res[$i]['items'][$j]['delivery_boy_id'])) ? $res[$i]['items'][$j]['delivery_boy_id'] : "";
            if (!empty($res[$i]['items'][$j]['seller_id'])) {
                $seller_info = $function->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
            } else {
                $res[$i]['items'][$j]['seller_id'] = "";
                $res[$i]['items'][$j]['seller_name'] = "";
                $res[$i]['items'][$j]['seller_store_name'] = "";
            }
            $item_details = $function->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
            $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
            $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
            $sql = "SELECT id from return_requests where product_variant_id = " . $res[$i]['items'][$j]['variant_id'] . " AND user_id = " . $user_id;
            $db->sql($sql);
            $return_request = $db->getResult();
            if (empty($return_request)) {
                $res[$i]['items'][$j]['applied_for_return'] = false;
            } else {
                $res[$i]['items'][$j]['applied_for_return'] = true;
            }
        }
        $res[$i]['final_total'] = strval($row['final_total']);
        $res[$i]['total'] = strval($row['total']);
        $i++;
    }

    $response['error'] = false;
    $response['message'] = "Images uploaded successfully!";
    $response['data'] = $res;
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['delete_bank_transfers_attachment']) && $_POST['delete_bank_transfers_attachment'] == 1) {
    /*  
    delete_bank_transfers_attachment
        accesskey:90336
        delete_bank_transfers_attachment:1
        order_id:1
        id:2
    */

    if (empty($_POST['order_id']) || empty($_POST['id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $order_id = $db->escapeString($function->xss_clean($_POST['order_id']));
    $id = $db->escapeString($function->xss_clean($_POST['id']));

    $sql = "SELECT attachment FROM `order_bank_transfers` WHERE id = $id AND order_id = $order_id";
    $db->sql($sql);
    $image = $db->getResult();
    if (file_exists('../upload/attachments/' . $image[0]['attachment'])) {
        unlink('../upload/attachments/' . $image[0]['attachment']);
    }

    $sql1 = "DELETE FROM `order_bank_transfers` WHERE id = $id AND order_id = $order_id";
    $db->sql($sql1);
    $res = $db->getResult();

    $response['error'] = false;
    $response['message'] = "Image deleted successfully!";
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_order_invoice']) && $_POST['get_order_invoice'] == 1) {
    /*  
    get_order_invoice
        accesskey:90336
        get_order_invoice:1
        order_id:1  OR order_item_id:2
    */

    // if (!verify_token()) {
    //     return false;
    // }
    $where = '';

    if (empty($_POST['order_id']) && empty($_POST['order_item_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass order id or order item id!";
        print_r(json_encode($response));
        return false;
    }

    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($function->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($function->xss_clean($_POST['offset'])) : 0;

    $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id']) && is_numeric($_POST['order_id'])) ? $db->escapeString($function->xss_clean($_POST['order_id'])) : "";
    $order_item_id = (isset($_POST['order_item_id']) && !empty($_POST['order_item_id']) && is_numeric($_POST['order_item_id'])) ? $db->escapeString($function->xss_clean($_POST['order_item_id'])) : "";

    if (!empty($order_id)) {
        $where .= !empty($where) ? " AND o.id = " . $order_id : " WHERE o.id = " . $order_id;
    }
    if (!empty($order_item_id)) {
        $where .= !empty($where) ? " AND oi.id = " . $order_item_id : " WHERE oi.id = " . $order_item_id;
    }

    $sql = "select o.*,oi.id as order_item_id,(select name from users u where u.id=o.user_id) as user_name,(select email from users u where u.id=o.user_id) as email from orders o LEFT JOIN order_items oi ON o.id=oi.order_id" . $where . " group by o.id ORDER BY date_added DESC LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();
    $i = 0;
    $j = 0;

    foreach ($res as $row) {
        $final_sub_total = 0;
        $sub_total = 0;
        if ($row['discount'] > 0) {
            $discounted_amount = $row['total'] * $row['discount'] / 100;
            $final_total = $row['total'] - $discounted_amount;
            $discount_in_rupees = $row['total'] - $final_total;
        } else {
            $discount_in_rupees = 0;
        }

        $res[$i]['discount_rupees'] = "$discount_in_rupees";
        $final_total = ceil($res[$i]['final_total']);
        $res[$i]['final_total'] = "$final_total";
        $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));
        $sql = "select oi.*,v.id as variant_id, p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where order_id=" . $row['id'];
        $db->sql($sql);
        $res[$i]['items'] = $db->getResult();
        $res[$i]['status'] = json_decode($res[$i]['status']);
        unset($res[$i]['status']);
        unset($res[$i]['active_status']);
        for ($j = 0; $j < count($res[$i]['items']); $j++) {
            $res[$i]['items'][$j]['status'] = (!empty($res[$i]['items'][$j]['status'])) ? json_decode($res[$i]['items'][$j]['status']) : array();
            if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                $sub_total += $res[$i]['items'][$j]['sub_total'];
            }
            if (!empty($res[$i]['items'][$j]['status'])) {
                if (count($res[$i]['items'][$j]['status']) > 1) {
                    if (in_array("awaiting_payment", $res[$i]['items'][$j]['status'][0]) && in_array("received", $res[$i]['items'][$j]['status'][1])) {
                        unset($res[$i]['items'][$j]['status'][0]);
                    }
                    $res[$i]['items'][$j]['status'] = array_values($res[$i]['items'][$j]['status']);
                }
            } else {
                $res[$i]['items'][$j]['status'] = array();
            }

            $res[$i]['items'][$j]['delivery_boy_id'] = (!empty($res[$i]['items'][$j]['delivery_boy_id'])) ? $res[$i]['items'][$j]['delivery_boy_id'] : "";
            if (!empty($res[$i]['items'][$j]['seller_id'])) {
                $seller_info = $function->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
            } else {
                $res[$i]['items'][$j]['seller_id'] = "";
                $res[$i]['items'][$j]['seller_name'] = "";
                $res[$i]['items'][$j]['seller_store_name'] = "";
            }
            $item_details = $function->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
            $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
            $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
            $sql = "SELECT id from return_requests where product_variant_id = " . $res[$i]['items'][$j]['variant_id'] . " AND user_id = " . $user_id;
            $db->sql($sql);
            $return_request = $db->getResult();
            if (empty($return_request)) {
                $res[$i]['items'][$j]['applied_for_return'] = false;
            } else {
                $res[$i]['items'][$j]['applied_for_return'] = true;
            }
        }
        $res[$i]['final_total'] = strval($row['final_total']);
        $res[$i]['total'] = strval($row['total']);
        $i++;
    }
    $orders = $order = array();

    if (!empty($res)) {
        $orders['error'] = false;
        $orders['total'] = count($res);
        $orders['data'] = array_values($res);
        print_r(json_encode($orders));
    } else {
        $res['error'] = true;
        $res['message'] = "No orders found!";
        print_r(json_encode($res));
    }
}


if (isset($_POST['track_order']) && !empty($_POST['track_order'])) {
    /*
    accesskey:90336
    user_id:4
    track_order:1
    oder_item_id:15670

    */

    if (!isset($_POST['shipment_id']) && $_POST['order_item_id'] == 0) {
        $res['error'] = true;
        $res['error'] = 'shipment  id missing';
        print_r(json_encode($res));
        return false;
    } else if (!isset($_POST['order_item_id']) && $_POST['shipment_id'] == 0) {
        $res['error'] = true;
        $res['error'] = 'Order item id is missing';
        print_r(json_encode($res));
        return false;
    } elseif (!empty($_POST['shipment_id']) && !empty($_POST['order_item_id'])) {
        $res['error'] = true;
        $res['error'] = 'You cannot pass both ids';
        print_r(json_encode($res));
        return false;
    }
    if (isset($_POST['shipment_id']) && $_POST['order_item_id'] == 0) {
        $shipment_id = $_POST['shipment_id'];
        $tracking_data = $function->track_order($shipment_id);
        if (!empty($tracking_data['tracking_data'])) {
            for ($i = 0; $i < count($tracking_data['tracking_data']['shipment_track_activities']); $i++) {
                unset($tracking_data['tracking_data']['shipment_track_activities'][$i]['id']);
                unset($tracking_data['tracking_data']['shipment_track_activities'][$i]['ship_track_id']);
            }
            $res['error'] = false;
            $res['current_status'] = $tracking_data['tracking_data']['shipment_track'][0]['current_status'];
            $res['activities'] = $tracking_data['tracking_data']['shipment_track_activities'];
        } elseif (isset($tracking_data['messsage'])) {
            $res['error'] = true;
            $res['message'] = $tracking_data['messsage'];
        } else {
            $res['error'] = true;
            $res['message'] = 'No data found';
        }
    } elseif (isset($_POST['order_item_id']) && $_POST['shipment_id'] == 0) {
        $order_item_id = $_POST['order_item_id'];
        $tracking_data = $function->get_data(['status', 'active_status'], 'id=' . $order_item_id, 'order_items');
        if (!empty($tracking_data)) {
            $status = json_decode($tracking_data[0]['status'], 1);
            $key = 0;
            $temp = array();
            for ($i = 0; $i < count($status); $i++) {
                $temp[$key]['date'] = $status[$i][1];
                $temp[$key]['location'] = "";
                $temp[$key]['activity'] = $status[$i][0];
                $key++;
            }
            $res['error'] = false;
            $res['current_status'] = $tracking_data[0]['active_status'];
            $res['activities'] = $temp;
        } else {
            $res['error'] = true;
            $res['message'] = 'No data found';
        }
    }
    print_r(json_encode($res));
    return false;
}
