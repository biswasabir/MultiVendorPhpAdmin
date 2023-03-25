<?php
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');


include('../../includes/crud.php');
include('../../includes/custom-functions.php');
include('verify-token.php');
$fn = new custom_functions();
$db = new Database();
$db->connect();
$config = $fn->get_configurations();
include('../../includes/variables.php');

include('send-email.php');

/* 
-------------------------------------------
APIs for Delivery Boys
-------------------------------------------
1. login
2. get_delivery_boy_by_id  
3. get_orders_by_delivery_boy_id
4. get_fund_transfers 
5. update_delivery_boy_profile
6. update_order_status
7. delivery_boy_forgot_password
8. get_notifications
9. update_delivery_boy_fcm_id
10. check_delivery_boy_by_mobile
11. send_withdrawal_request
12. get_withdrawal_requests
13. change_availability
14. get_orders
15. bulk_status_update
16. update_bulk_order_items
-------------------------------------------

-------------------------------------------

*/

if (!verify_token()) {
    return false;
}

$currency = $fn->get_settings('currency');

if (!isset($_POST['accesskey'])  || trim($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
    exit();
}

if (isset($_POST['login'])) {
    /* 
    1.Login
        accesskey:90336
        mobile:9876543210
        password:12345678
        fcm_id:YOUR_FCM_ID  // {optional}
        login:1
    */

    if (empty(trim($_POST['mobile']))) {
        $response['error'] = true;
        $response['message'] = "Mobile should be filled!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    if (empty($_POST['password'])) {
        $response['error'] = true;
        $response['message'] = "Password should be filled!";
        print_r(json_encode($response));
        return false;
        exit();
    }


    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $password = md5($db->escapeString($fn->xss_clean($_POST['password'])));
    $sql = "SELECT * FROM delivery_boys	WHERE mobile = '" . $mobile . "' AND password = '" . $password . "'";
    $db->sql($sql);
    $res = $db->getResult();
    $num = $db->numRows($res);
    $rows = $tempRow = array();

    if ($num == 1) {
        if ($res[0]['status'] == 0) {
            $response['error'] = true;
            $response['message'] = "It seems your acount is not active please contact admin for more info!";
            $response['data'] = array();
        } else {
            /* update fcm_id in delivery boy table */
            $delivery_boy_id = $res[0]['id'];

            $fcm_id = (isset($_POST['fcm_id']) && !empty($_POST['fcm_id'])) ? $db->escapeString($fn->xss_clean($_POST['fcm_id'])) : "";
            if (!empty($fcm_id)) {
                $sql1 = "update delivery_boys set `fcm_id` ='$fcm_id' where id = '" . $delivery_boy_id . "'";
                $db->sql($sql1);
                $db->sql($sql);
                $res1 = $db->getResult();
                foreach ($res1 as $row) {
                    $tempRow['id'] = $row['id'];
                    $tempRow['name'] = $row['name'];
                    $tempRow['mobile'] = $row['mobile'];
                    $tempRow['password'] = $row['password'];
                    $tempRow['address'] = $row['address'];
                    $tempRow['bonus'] = $row['bonus'];
                    $tempRow['balance'] = $row['balance'];
                    $tempRow['dob'] = $row['dob'];
                    $tempRow['bank_account_number'] = $row['bank_account_number'];
                    $tempRow['account_name'] = $row['account_name'];
                    $tempRow['bank_name'] = $row['bank_name'];
                    $tempRow['ifsc_code'] = $row['ifsc_code'];
                    $tempRow['other_payment_information'] = (!empty($row['other_payment_information'])) ? $row['other_payment_information'] : "";
                    $tempRow['status'] = $row['status'];
                    $tempRow['is_available'] = $row['is_available'];
                    $tempRow['date_created'] = $row['date_created'];
                    $tempRow['fcm_id'] = !empty($row['fcm_id']) ? $row['fcm_id'] : "";
                    $tempRow['driving_license'] = (!empty($row['driving_license'])) ? DOMAIN_URL . 'upload/delivery-boy/' . $row['driving_license'] : "";
                    $tempRow['national_identity_card'] = (!empty($row['national_identity_card'])) ? DOMAIN_URL . 'upload/delivery-boy/' . $row['national_identity_card'] : "";

                    $rows[] = $tempRow;
                }
                $db->disconnect();
            } else {
                foreach ($res as $row) {
                    $tempRow['id'] = $row['id'];
                    $tempRow['name'] = $row['name'];
                    $tempRow['mobile'] = $row['mobile'];
                    $tempRow['password'] = $row['password'];
                    $tempRow['address'] = $row['address'];
                    $tempRow['bonus'] = $row['bonus'];
                    $tempRow['balance'] = $row['balance'];
                    $tempRow['dob'] = $row['dob'];
                    $tempRow['bank_account_number'] = $row['bank_account_number'];
                    $tempRow['account_name'] = $row['account_name'];
                    $tempRow['bank_name'] = $row['bank_name'];
                    $tempRow['ifsc_code'] = $row['ifsc_code'];
                    $tempRow['other_payment_information'] = (!empty($row['other_payment_information'])) ? $row['other_payment_information'] : "";
                    $tempRow['status'] = $row['status'];
                    $tempRow['date_created'] = $row['date_created'];
                    $tempRow['fcm_id'] = !empty($row['fcm_id']) ? $row['fcm_id'] : "";
                    $tempRow['driving_license'] = (!empty($row['driving_license'])) ? DOMAIN_URL . 'upload/delivery-boy/' . $row['driving_license'] : "";
                    $tempRow['national_identity_card'] = (!empty($row['national_identity_card'])) ? DOMAIN_URL . 'upload/delivery-boy/' . $row['national_identity_card'] : "";

                    $rows[] = $tempRow;
                }
            }
            $db->disconnect();

            $response['error'] = false;
            $response['message'] = "Delivery Boy Login Successfully";
            $response['currency'] =  $currency;
            $response['data'] = $rows;
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Invalid number or password, Try again.";
    }
    print_r(json_encode($response));
}


/* 
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['get_delivery_boy_by_id'])) {

    /* 
    2.get_delivery_boy_by_id
        accesskey:90336
        delivery_boy_id:78
        get_delivery_boy_by_id:1
    */
    if (empty($_POST['delivery_boy_id'])) {
        $response['error'] = true;
        $response['message'] = "Delivery boy id should be Passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $id = $db->escapeString($fn->xss_clean($_POST['delivery_boy_id']));
    $sql = "SELECT * FROM delivery_boys	WHERE id = '" . $id . "'";
    $db->sql($sql);
    $res = $db->getResult();
    $num = $db->numRows($res);
    $db->disconnect();
    $rows = $tempRow = array();
    if ($num == 1) {
        foreach ($res as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['name'] = $row['name'];
            $tempRow['mobile'] = $row['mobile'];
            $tempRow['password'] = $row['password'];
            $tempRow['address'] = $row['address'];
            $tempRow['bonus'] = $row['bonus'];
            $tempRow['balance'] = $row['balance'];
            $tempRow['dob'] = $row['dob'];
            $tempRow['bank_account_number'] = $row['bank_account_number'];
            $tempRow['account_name'] = $row['account_name'];
            $tempRow['bank_name'] = $row['bank_name'];
            $tempRow['ifsc_code'] = $row['ifsc_code'];
            $tempRow['other_payment_information'] = (!empty($row['other_payment_information'])) ? $row['other_payment_information'] : "";
            $tempRow['status'] = $row['status'];
            $tempRow['date_created'] = $row['date_created'];
            $tempRow['fcm_id'] = !empty($row['fcm_id']) ? $row['fcm_id'] : "";
            $tempRow['driving_license'] = (!empty($row['driving_license'])) ? DOMAIN_URL . 'upload/delivery-boy/' . $row['driving_license'] : "";
            $tempRow['national_identity_card'] = (!empty($row['national_identity_card'])) ? DOMAIN_URL . 'upload/delivery-boy/' . $row['national_identity_card'] : "";
            $rows[] = $tempRow;
        }
        $response['error'] = false;
        $response['message'] = "Delivery Boy Data Fetched Successfully";

        $response['currency'] =  $currency;
        $response['data'] = $rows;
        $response['data'][0]['balance'] = strval(round($response['data'][0]['balance']));
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
}

/* 
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['get_orders_by_delivery_boy_id']) && !empty($_POST['get_orders_by_delivery_boy_id'])) {
    /*
    3.get_orders_by_delivery_boy_id
        accesskey:90336
        get_orders_by_delivery_boy_id:1
        delivery_boy_id:1
        order_item_id:12         // {optional}
        order_id:12608          // {optional}
        limit:10                // {optional}
        offset:0                // {optional}
        filter_order:received | processed | shipped | delivered | cancelled | returned    // {optional}
    */

    if (empty($_POST['delivery_boy_id'])) {
        $response['error'] = true;
        $response['message'] = "Delivery Boy ID should be filled!";
        print_r(json_encode($response));
        return false;
    }
    $where = '';
    $id = $db->escapeString($fn->xss_clean($_POST['delivery_boy_id']));
    $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id']) && is_numeric($_POST['order_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_id'])) : "";
    $order_item_id = (isset($_POST['order_item_id']) && !empty($_POST['order_item_id']) && is_numeric($_POST['order_item_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_item_id'])) : "";
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'oi.id';
    $order = (isset($_POST['order']) && !empty($fn->xss_clean($_POST['order']))) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'DESC';

    if (isset($_POST['order_id']) && $_POST['order_id'] != '') {
        $where .= " AND oi.order_id= $order_id";
    }
    if (isset($_POST['order_item_id']) && $_POST['order_item_id'] != '') {
        $where .= " AND oi.id= $order_item_id";
    }
    if (isset($_POST['filter_order']) && $_POST['filter_order'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_POST['filter_order']));
        $where .= " AND oi.`active_status`='" . $filter_order . "'";
    }

    // $sql = "select count(oi.id) as total from order_items oi where delivery_boy_id=" . $delivery_boy_id . $where;
    // $db->sql($sql);
    // $res = $db->getResult();
    // $total = $res[0]['total'];

    $sql = "select COUNT(oi.id) as total from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id AND oi.active_status NOT IN ('awaiting_payment') and oi.delivery_boy_id = $id " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    $sql = "select oi.*,o.mobile,o.otp,o.longitude,o.latitude,o.order_note,o.total ,o.delivery_charge,o.discount,o.promo_code,o.promo_discount,o.wallet_balance,o.final_total,o.payment_method,o.address,o.delivery_time,p.name as name,p.image, u.name as uname,v.measurement, (SELECT short_code FROM unit un where un.id=v.measurement_unit_id)as mesurement_unit_name,oi.status as order_status from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id AND oi.active_status NOT IN ('awaiting_payment') and oi.delivery_boy_id = $id  $where ORDER BY $sort $order LIMIT $offset , $limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    $i = 0;
    foreach ($res as $row) {
        $res_dboy = $fn->get_data($columns = ['name'], "id=" . $row['delivery_boy_id'], 'delivery_boys');
        $seller_address = $fn->get_seller_address($row['seller_id']);
        $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id'], "id=" . $row['seller_id'], 'seller');

        $discounted_amount = $row['total'] * $row['discount'] / 100; /*  */
        $final_total = $row['total'] - $discounted_amount;
        $discount_in_rupees = $row['total'] - $final_total;
        $discount_in_rupees = floor($discount_in_rupees);
        $tempRow['id'] = $row['id'];
        $tempRow['order_id'] = $row['order_id'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['name'] = $row['uname'];

        $tempRow['mobile'] = $row['mobile'];

        $tempRow['order_note'] = $row['order_note'];
        $tempRow['product_name'] = (!empty($row['product_name'])) ? $row['product_name'] : "";;
        $tempRow['image'] = DOMAIN_URL . $row['image'];
        $tempRow['variant_name'] = (!empty($row['variant_name'])) ? $row['variant_name'] : "";
        $tempRow['product_variant_id'] = $row['product_variant_id'];
        $tempRow['delivery_charge'] = $row['delivery_charge'];
        $tempRow['total'] = $row['total'];
        $tempRow['tax'] = $row['tax_amount'] . '(' . $row['tax_percentage'] . '%)';
        $tempRow['promo_discount'] = $row['promo_discount'];
        $tempRow['wallet_balance'] = $row['wallet_balance'];
        $tempRow['discount'] = $discount_in_rupees . '(' . $row['discount'] . '%)';
        $tempRow['qty'] = $row['quantity'];
        $tempRow['final_total'] = $row['final_total'];
        $tempRow['promo_code'] = $row['promo_code'];
        $tempRow['deliver_by'] = !empty($res_dboy[0]['name']) ? $res_dboy[0]['name'] : 'Not Assigned';
        $tempRow['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
        $tempRow['seller_mobile'] = $res_seller[0]['mobile'];
        $tempRow['seller_address'] = $seller_address;
        $tempRow['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '';
        $tempRow['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '';
        $tempRow['payment_method'] = $row['payment_method'];
        $tempRow['seller_id'] = !empty($row['seller_id']) ? $row['seller_id'] : '';
        $tempRow['address'] = $row['address'];
        $tempRow['delivery_time'] = $row['delivery_time'];
        $tempRow['active_status'] = $row['active_status'];
        $tempRow['wallet_balance'] = $row['wallet_balance'];
        $tempRow['date_added'] = date('d-m-Y', strtotime($row['date_added']));
        $tempRow['otp'] = !empty($row['otp']) ? $row['otp'] : '0';
        $tempRow['longitude'] = !empty($row['longitude']) ? $row['longitude'] : '';
        $tempRow['latitude'] = !empty($row['latitude']) ? $row['latitude'] : '';
        $tempRow['price'] = $row['price'];
        $tempRow['discounted_price'] = $row['discounted_price'];
        $tempRow['tax_amount'] = $row['tax_amount'];
        $tempRow['tax_percentage'] = $row['tax_percentage'];
        $tempRow['sub_total'] = $row['sub_total'];

        $rows[] = $tempRow;
        $i++;
    }


    if (!empty($res)) {
        $orders['error'] = false;
        $orders['total'] = $total;
        $orders['data'] = $rows;
        print_r(json_encode($orders));
    } else {
        $res['error'] = true;
        $res['message'] = "No orders found!";
        print_r(json_encode($res));
        return false;
    }
}

/*
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['get_fund_transfers'])) {

    /* 
    4. get_fund_transfers
        accesskey:90336
        delivery_boy_id:82
        offset:0        // {optional}
        limit:10        // {optional}
        
        sort:id           // {optional}
        order:DESC / ASC            // {optional}
        
        search:search_value         // {optional}
        get_fund_transfers:1
        
    */

    $json_response = array();
    $id =  $db->escapeString(trim($fn->xss_clean($_POST['delivery_boy_id'])));
    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;

    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where = " Where f.`id` like '%" . $search . "%' OR d.`name` like '%" . $search . "%' OR f.`message` like '%" . $search . "%' OR d.`mobile` like '%" . $search . "%' OR d.`address` like '%" . $search . "%' OR f.`opening_balance` like '%" . $search . "%' OR f.`closing_balance` like '%" . $search . "%' OR d.`balance` like '%" . $search . "%' OR f.`date_created` like '%" . $search . "%'";
    }

    if (empty($where)) {
        $where .= " WHERE delivery_boy_id = " . $id;
    } else {
        $where .= " AND delivery_boy_id = " . $id;
    }

    $sql = "SELECT COUNT(f.id) as total FROM `fund_transfers` f JOIN `delivery_boys` d ON f.delivery_boy_id=d.id" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];
    $sql = "SELECT f.*,d.name,d.mobile,d.address FROM `fund_transfers` f JOIN `delivery_boys` d ON f.delivery_boy_id=d.id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $json_response['total'] = $total;
    $rows = array();
    $tempRow = array();
    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['mobile'] = $row['mobile'];
        $tempRow['address'] = $row['address'];
        $tempRow['delivery_boy_id'] = $row['delivery_boy_id'];
        $tempRow['type'] = $row['type'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['opening_balance'] = $row['opening_balance'];
        $tempRow['closing_balance'] = $row['closing_balance'];
        $tempRow['status'] = $row['status'];
        $tempRow['message'] = $row['message'];
        $tempRow['date_created'] = $row['date_created'];

        $rows[] = $tempRow;
    }
    $json_response['error'] = false;
    $json_response['data'] = $rows;
    print_r(json_encode($json_response));
}
/* 
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['update_delivery_boy_profile'])) {

    /* 
    5.update_delivery_boy_profile
        accesskey:90336
        delivery_boy_id:87
        name:any value       
		address:Jl Komplek Polri 
		dob:1992-07-07
		bank_name:SBI
		account_number: 12345678976543
		account_name: any value
		ifsc_code:ASDFGH45
		new_driving_license: image_file  { jpg, png, gif, jpeg }
		new_national_identity_card: image_file  { jpg, png, gif, jpeg }
		other_payment_info: value   // {optional}
        old_password:        // {optional}
        update_password:        // {optional}
		confirm_password:        // {optional}
        update_delivery_boy_profile:1
    */
    $json_response = array();
    $id =  $db->escapeString(trim($fn->xss_clean($_POST['delivery_boy_id'])));
    $name = $db->escapeString(trim($fn->xss_clean($_POST['name'])));
    $address = $db->escapeString(trim($fn->xss_clean($_POST['address'])));
    $old_password = (isset($_POST['old_password']) && !empty(trim($_POST['old_password']))) ? $db->escapeString(trim($fn->xss_clean($_POST['old_password']))) : "";
    $update_password = (isset($_POST['update_password']) && !empty(trim($_POST['update_password']))) ? $db->escapeString(trim($fn->xss_clean($_POST['update_password']))) : "";
    $confirm_password = (isset($_POST['confirm_password']) && !empty(trim($_POST['confirm_password']))) ? $db->escapeString(trim($fn->xss_clean($_POST['confirm_password']))) : "";
    $change_password = false;
    $update_dob = $db->escapeString($fn->xss_clean($_POST['dob']));
    $update_bank_name = $db->escapeString($fn->xss_clean($_POST['bank_name']));
    $update_account_number = $db->escapeString($fn->xss_clean($_POST['account_number']));
    $update_account_name = $db->escapeString($fn->xss_clean($_POST['account_name']));
    $update_ifsc_code = $db->escapeString($fn->xss_clean($_POST['ifsc_code']));
    $update_other_payment_info = !empty($_POST['other_payment_info']) ? $db->escapeString($fn->xss_clean($_POST['other_payment_info'])) : '';


    /* check if id is not empty and there is valid data in it */
    if (!isset($_POST['delivery_boy_id']) || empty(trim($_POST['delivery_boy_id'])) || !is_numeric($_POST['delivery_boy_id'])) {
        $json_response['error'] = true;
        $json_response['message'] = "Invalid Id of Delivery Boy";
        print_r(json_encode($json_response));
        return false;
        exit();
    }

    $sql = "SELECT * from delivery_boys where id='$id'";
    $db->sql($sql);
    $res_id = $db->getResult();
    // print_r($res_id);
    $num = $db->numRows($res_id);
    if ($num != 1) {
        $json_response['error'] = true;
        $json_response['message'] = "Delivery Boy is not Registered.";
        print_r(json_encode($json_response));
        return false;
        exit();
    }

    /* if any of the password field is set and old password is not set */
    if ((!empty($confirm_password) || !empty($update_password)) && empty($old_password)) {
        $json_response['error'] = true;
        $json_response['message'] = "Please enter old password.";
        print_r(json_encode($json_response));
        return false;
        exit();
    }

    /* either of the password field is not empty and is they don't match */
    if ((!empty($confirm_password) || !empty($update_password)) && ($update_password != $confirm_password)) {
        $json_response['error'] = true;
        $json_response['message'] = "Password and Confirm Password mismatched.";
        print_r(json_encode($json_response));
        return false;
        exit();
    }

    /* when all conditions are met check for old password in database */
    if (!empty($confirm_password) && !empty($update_password) && !empty($old_password)) {
        $old_password = md5($old_password);
        $sql = "Select password from `delivery_boys` where id = '$id' and password = '$old_password' ";
        $db->sql($sql);
        $res = $db->getResult();

        if (empty($res)) {
            $json_response['error'] = true;
            $json_response['message'] = "Old password mismatched.";
            print_r(json_encode($json_response));
            return false;
            exit();
        }
        $change_password = true;
        $confirm_password = md5($confirm_password);
    }

    $sql = "Update delivery_boys set `name`='" . $name . "',`address`='" . $address . "' ,`dob`='$update_dob',`bank_account_number`='$update_account_number',`bank_name`='$update_bank_name',`account_name`='$update_account_name',`ifsc_code`='$update_ifsc_code' ";
    $sql .= ($change_password) ? ", `password`='" . $confirm_password . "' " : "";
    $sql .= ($update_other_payment_info != "") ? ",`other_payment_information`='$update_other_payment_info'" : "";
    $sql .= " where `id` = '$id' ";

    if ($db->sql($sql)) {
        if (isset($_FILES['new_driving_license']) && $_FILES['new_driving_license']['size'] != 0 && $_FILES['new_driving_license']['error'] == 0 && !empty($_FILES['new_driving_license'])) {
            //image isn't empty and update the image
            $dr_image = $res_id[0]['driving_license'];
            // common image file extensions
            $result = $fn->validate_image($_FILES["new_driving_license"]);
            if (!$result) {
                $json_response['error'] = true;
                $json_response['message'] = "driving_license image type must jpg, jpeg, gif, or png!.";;
                return false;
                exit();
            }
            $target_path = '../../upload/delivery-boy/';
            $dr_filename = microtime(true) . '.' . strtolower($extension);
            $dr_full_path = $target_path . "" . $dr_filename;
            if (!move_uploaded_file($_FILES["new_driving_license"]["tmp_name"], $dr_full_path)) {
                $json_response['error'] = true;
                $json_response['message'] = "Can not upload image.";
                return false;
                exit();
            }
            if (!empty($dr_image)) {
                unlink($target_path . $dr_image);
            }
            $sql = "UPDATE delivery_boys SET `driving_license`='" . $dr_filename . "' WHERE `id`=" . $id;
            $db->sql($sql);
        }
        if (isset($_FILES['new_national_identity_card']) && $_FILES['new_national_identity_card']['size'] != 0 && $_FILES['new_national_identity_card']['error'] == 0 && !empty($_FILES['new_national_identity_card'])) {
            //image isn't empty and update the image
            $nic_image = $res_id[0]['national_identity_card'];
            // common image file extensions
            $result = $fn->validate_image($_FILES["new_driving_license"]);
            if (!$result) {
                $json_response['error'] = true;
                $json_response['message'] = "national_identity_card image type must jpg, jpeg, gif, or png!.";;
                return false;
                exit();
            }
            $target_path = '../../upload/delivery-boy/';
            $nic_filename = microtime(true) . '.' . strtolower($extension);
            $nic_full_path = $target_path . "" . $nic_filename;
            if (!move_uploaded_file($_FILES["new_national_identity_card"]["tmp_name"], $nic_full_path)) {
                $json_response['error'] = true;
                $json_response['message'] = "Can not upload image.";
                return false;
                exit();
            }
            if (!empty($nic_image)) {
                unlink($target_path . $nic_image);
            }
            $sql = "UPDATE delivery_boys SET `national_identity_card`='" . $nic_filename . "' WHERE `id`=" . $id;
            $db->sql($sql);
        }
        $json_response['error'] = false;
        $json_response['message'] = "Information Updated Successfully.";
        $json_response['message'] .= ($change_password) ? " and password also updated successfully." : "";
    } else {
        $json_response['error'] = true;
        $json_response['message'] = "Some Error Occurred! Please Try Again.";
    }
    print_r(json_encode($json_response));
}

/* 
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['update_order_status']) && !empty($_POST['update_order_status'])) {

    /* 
    6.update_order_status
        accesskey:90336
		update_order_status:1
		order_id:169
        order_item_id:12577
        status:received | processed | shipped | delivered | cancelled | returned
		delivery_boy_id:40

    */

    if (empty($_POST['order_id']) || empty($_POST['status']) || empty($_POST['delivery_boy_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all mandatory fields!";
        print_r(json_encode($response));
        return false;
    }
    $id = $db->escapeString(trim($fn->xss_clean($_POST['order_id'])));
    $postStatus = $db->escapeString($fn->xss_clean($_POST['status']));
    $delivery_boy_id = $db->escapeString(trim($fn->xss_clean(($_POST['delivery_boy_id']))));
    $order_item_id = $db->escapeString(trim($fn->xss_clean(($_POST['order_item_id']))));

    $sql = "SELECT delivery_boy_id FROM `order_items` WHERE id=" . $order_item_id;
    $db->sql($sql);
    $result = $db->getResult();
    $dboy_id = $result[0]['delivery_boy_id'];
    if ($delivery_boy_id != $dboy_id) {
        $response['error'] = true;
        $response['message'] = 'You are not authorized to update status of this order!';
        print_r(json_encode($response));
        return false;
    }
    $response = $fn->update_order_status($id, $order_item_id, $postStatus, $delivery_boy_id);
    print_r($response);
}

/* 
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['delivery_boy_forgot_password']) && isset($_POST['mobile'])) {

    /* 
    7.delivery_boy_forgot_password
        accesskey:90336
		mobile:8989898989
		password:1234567
		delivery_boy_forgot_password:1
    */
    if (empty($_POST['password'])) {
        $response['error'] = true;
        $response['message'] = "Password should be filled!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    if (empty($_POST['mobile'])) {
        $response['error'] = true;
        $response['message'] = "Mobile Number id not passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $mobile = $db->escapeString(trim($fn->xss_clean($_POST['mobile'])));
    $password = md5($db->escapeString($fn->xss_clean($_POST['password'])));

    $sql = "SELECT mobile from delivery_boys where mobile='$mobile'";
    $db->sql($sql);
    $res_mobile = $db->getResult();

    if ($res_mobile[0]['mobile'] == $mobile) {
        $sql_update = "UPDATE `delivery_boys` SET `password`='$password' WHERE `mobile`='$mobile'";
        $db->sql($sql_update);
        $response["error"]   = false;
        $response["message"] = "Password updated successfully";
    } else {
        $response["error"]   = true;
        $response["message"] = "Mobile number id not Registered!";
    }
    print_r(json_encode($response));
}

/* 
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['get_notifications'])) {

    /* 
    8. get_notifications
        accesskey:90336
        delivery_boy_id:114
        offset:0        // {optional}
        limit:10        // {optional}
        
        sort:id           // {optional}
        order:DESC / ASC            // {optional}
        
        search:search_value         // {optional}
        get_notifications:1
        
    */

    $json_response = array();
    $id =  $db->escapeString(trim($fn->xss_clean($_POST['delivery_boy_id'])));
    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;

    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where = " Where `id` like '%" . $search . "%' OR `title` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR `type` like '%" . $search . "%' OR `date_created` like '%" . $search . "%'  ";
    }

    if (empty($where)) {
        $where .= " WHERE delivery_boy_id = " . $id;
    } else {
        $where .= " AND delivery_boy_id = " . $id;
    }

    $sql = "SELECT COUNT(id) as total FROM `delivery_boy_notifications` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];
    $sql = "SELECT * FROM `delivery_boy_notifications` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $json_response['total'] = $total;
    $rows = array();
    $tempRow = array();
    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['delivery_boy_id'] = $row['delivery_boy_id'];
        $tempRow['order_id'] = $row['order_item_id'];
        $tempRow['title'] = $row['title'];
        $tempRow['message'] = $row['message'];
        $tempRow['type'] = $row['type'];
        $tempRow['date_created'] = $row['date_created'];

        $rows[] = $tempRow;
    }
    $json_response['error'] = false;
    $json_response['data'] = $rows;
    print_r(json_encode($json_response));
}

/* 
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['update_delivery_boy_fcm_id'])) {
    /* 
    9.update_delivery_boy_fcm_id
        accesskey:90336
        delivery_boy_id:114
        fcm_id:YOUR_FCM_ID
        update_delivery_boy_fcm_id:1
    */

    if (empty($_POST['fcm_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass the fcm_id!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $id = $db->escapeString(trim($_POST['delivery_boy_id']));
    if (isset($_POST['fcm_id']) && !empty($_POST['fcm_id'])) {
        $fcm_id = $db->escapeString($fn->xss_clean($_POST['fcm_id']));
        $sql1 = "update delivery_boys set `fcm_id` ='$fcm_id' where id = '" . $id . "'";
        if ($db->sql($sql1)) {
            $response['error'] = false;
            $response['message'] = "Delivery Boy fcm_id Updeted successfully.";
            print_r(json_encode($response));
        } else {
            $response['error'] = true;
            $response['message'] = "Can not update fcm_id of delivery boy.";
            print_r(json_encode($response));
        }
    }
}

/* 
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['check_delivery_boy_by_mobile']) && isset($_POST['mobile'])) {

    /* 
    10.check_delivery_boy_by_mobile
        accesskey:90336
		mobile:8989898989
		check_delivery_boy_by_mobile:1
    */

    if (empty($_POST['mobile'])) {
        $response['error'] = true;
        $response['message'] = "Mobile Number id not passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $mobile = $db->escapeString(trim($fn->xss_clean($_POST['mobile'])));

    $sql = "SELECT mobile from delivery_boys where mobile='$mobile'";
    $db->sql($sql);
    $res_mobile = $db->getResult();

    if ($res_mobile[0]['mobile'] == $mobile) {
        $response["error"]   = false;
        $response["message"] = "Mobile number is Registered.";
    } else {
        $response["error"]   = true;
        $response["message"] = "Mobile number is not Registered!";
    }
    print_r(json_encode($response));
}

/* 
---------------------------------------------------------------------------------------------------------
*/

if ((isset($_POST['send_withdrawal_request'])) && ($_POST['send_withdrawal_request'] == 1)) {

    /* 
	11.send_withdrawal_request
		accesskey:90336
		send_withdrawal_request:1
		type:user/delivery_boy
		type_id:3
		amount:1000
		message:Message {optional}
*/

    $type = (isset($_POST['type']) && !empty($_POST['type'])) ? $db->escapeString($fn->xss_clean($_POST['type'])) : "";
    $type_id = (isset($_POST['type_id']) && !empty($_POST['type_id'])) ? $db->escapeString($fn->xss_clean($_POST['type_id'])) : "";
    $amount  = (isset($_POST['amount']) && !empty($_POST['amount'])) ? $db->escapeString($fn->xss_clean($_POST['amount'])) : "";
    $message = (isset($_POST['message']) && !empty($_POST['message'])) ? $db->escapeString($fn->xss_clean($_POST['message'])) : "";
    $type1 = $type =  ($type == 'user') ? 'users' : 'delivery_boys';
    if (!empty($type) && !empty($type_id) && !empty($amount)) {
        // check if such user or delivery boy exists or not
        if ($fn->is_user_or_dboy_exists($type, $type_id)) {
            // checking if balance is greater than amount requested or not 
            $balance = $fn->get_user_or_delivery_boy_balance($type, $type_id);
            if ($balance >= $amount) {
                // Debit amount requeted
                $new_balance =  $balance - $amount;
                if ($fn->debit_balance($type, $type_id, $new_balance)) {
                    // store wallet transaction
                    if ($type == 'delivery_boys') {
                        $sql = "INSERT INTO `fund_transfers` (`delivery_boy_id`,`type`,`amount`,`opening_balance`,`closing_balance`,`status`,`message`) VALUES ('" . $type_id . "','debit','" . $amount . "','" . $balance . "','" . $new_balance . "','SUCCESS','Balance debited against withdrawal request.')";
                        $db->sql($sql);
                    }
                    if ($type == 'users') {
                        $fn->add_wallet_transaction($order_id = "", $type_id, 'debit', $amount, 'Balance debited against withdrawal request.', 'wallet_transactions', '1');
                    }
                    $new_balance = 0;
                    // store withdrawal request
                    if ($fn->store_withdrawal_request($type, $type_id, $amount, $message)) {
                        if ($type == "users") {
                            $sql = "select  balance from  `users` WHERE id = $type_id";
                            $db->sql($sql);
                            $new_balance = $db->getResult();
                        } else if ($type == "delivery_boys") {
                            $sql = "select  balance from  `delivery_boys` WHERE id = $type_id";
                            $db->sql($sql);
                            $new_balance = $db->getResult();
                        }

                        $response['error'] = false;
                        $response['message'] = 'Withdrawal request accepted successfully!please wait for confirmation.';
                        $response['updated_balance'] = $new_balance[0]['balance'];
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Something went wrong please try again later!';
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Something went wrong please try again later!';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Insufficient balance';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No such ' . $type1 . ' exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}

/* 
---------------------------------------------------------------------------------------------------------
*/

if ((isset($_POST['get_withdrawal_requests'])) && ($_POST['get_withdrawal_requests'] == 1)) {

    /*
12.get_withdrawal_requests
    accesskey:90336
    get_withdrawal_requests:1
	type:user/delivery_boy
	data_type:withdrawal_requests / fund_transfers  {optional}
    type_id:3
    offset:0 {optional}
    limit:5 {optional}
    sort:id          {optional}
    order:DESC / ASC           {optional}

*/

    $type  = (isset($_POST['type']) && !empty($_POST['type'])) ? $db->escapeString($fn->xss_clean($_POST['type'])) : "";
    $data_type  = (isset($_POST['data_type']) && !empty($_POST['data_type'])) ? $db->escapeString($fn->xss_clean($_POST['data_type'])) : "";
    $type_id = (isset($_POST['type_id']) && !empty($_POST['type_id'])) ? $db->escapeString($fn->xss_clean($_POST['type_id'])) : "";
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';
    if (!empty($type) && !empty($type_id)) {


        /* if records found return data */
        if ($data_type == "withdrawal_requests") {
            $result = $fn->is_records_exists($type, $type_id, $offset, $limit);
            if (!empty($result)) {
                $sql = "SELECT count(id) as total from withdrawal_requests where `type` = '" . $type . "' AND `type_id` = " . $type_id;
                $db->sql($sql);
                $total = $db->getResult();
                $response['error'] = false;
                $response['total'] = $total[0]['total'];
                $response['data'] = array_values($result);
            } else {
                $response['error'] = true;
                $response['message'] = "Data does't exists!";
            }
        } elseif ($data_type == "fund_transfers") {

            $sql = "SELECT COUNT(f.id) as total FROM `fund_transfers` f JOIN `delivery_boys` d ON f.delivery_boy_id=d.id where f.delivery_boy_id = $type_id ";
            $db->sql($sql);
            $res = $db->getResult();
            foreach ($res as $row)
                $total = $row['total'];
            $sql = "SELECT f.*,d.name,d.mobile,d.address FROM `fund_transfers` f JOIN `delivery_boys` d ON f.delivery_boy_id=d.id where f.delivery_boy_id = $type_id ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
            $db->sql($sql);
            $res = $db->getResult();


            $rows = array();
            $tempRow = array();
            foreach ($res as $row) {
                $tempRow['id'] = $row['id'];
                $tempRow['name'] = $row['name'];
                $tempRow['mobile'] = $row['mobile'];
                $tempRow['address'] = $row['address'];
                $tempRow['delivery_boy_id'] = $row['delivery_boy_id'];
                $tempRow['type'] = $row['type'];
                $tempRow['amount'] = $row['amount'];
                $tempRow['opening_balance'] = $row['opening_balance'];
                $tempRow['closing_balance'] = $row['closing_balance'];
                $tempRow['status'] = $row['status'];
                $tempRow['message'] = $row['message'];
                $tempRow['date_created'] = $row['date_created'];

                $rows[] = $tempRow;
            }
            $response['error'] = false;
            $response['total'] = $total;
            $response['data'] = $rows;
        } else {
            $sql = "SELECT count(id) as total from withdrawal_requests where `type` = '" . $type . "' AND `type_id` = " . $type_id;
            $db->sql($sql);
            $total = $db->getResult();
            $response['error'] = false;
            $response['total'] = $total[0]['total'];
            $response['data'] = array_values($result);
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}

/* 
---------------------------------------------------------------------------------------------------------
*/

if (isset($_POST['change_availability']) && $_POST['change_availability'] != '') {
    /* 
    13.change_availability
        accesskey:90336
        delivery_boy_id:114
        is_available:1/0
        change_availability:1
    */
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response['error'] = true;
        $response['message'] = "This operation is not allowed in demo panel!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    if (!isset($_POST['delivery_boy_id']) || empty($_POST['delivery_boy_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass the delivery boy id!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $id = $db->escapeString(trim($_POST['delivery_boy_id']));
    $is_exist = $fn->rows_count('delivery_boys', 'id', 'id=' . $id);
    if ($is_exist == 1) {
        if (isset($_POST['change_availability']) && $_POST['change_availability'] != '') {
            $is_available = $db->escapeString($fn->xss_clean($_POST['is_available']));
            $sql1 = "update delivery_boys set `is_available` ='$is_available' where id = '" . $id . "'";
            if ($db->sql($sql1)) {
                $response['error'] = false;
                $response['message'] = "Availability updated successfully.";
                print_r(json_encode($response));
            } else {
                $response['error'] = true;
                $response['message'] = "Can not update availability.";
                print_r(json_encode($response));
            }
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Delivery boy not exists.";
        print_r(json_encode($response));
    }
}

/* 
14. get_orders
    accesskey:90336
    get_orders:1
    delivery_boy_id:1
    order_id:12608          // {optional}
    start_date:2020-06-05   // {optional} {YYYY-mm-dd}
    end_date:2020-06-05     // {optional} {YYYY-mm-dd}
    limit:10                // {optional}
    offset:0                // {optional}
    filter_order:received | processed | shipped | delivered | cancelled | returned | awaiting_payment    // {optional}
*/

if (isset($_POST['get_orders']) && !empty($_POST['get_orders'])) {
    if (empty($_POST['delivery_boy_id'])) {
        $response['error'] = true;
        $response['message'] = "Delivery boy ID should be filled!";
        print_r(json_encode($response));
        return false;
    }
    $where = '';
    $delivery_boy_id = $db->escapeString($fn->xss_clean($_POST['delivery_boy_id']));
    $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id']) && is_numeric($_POST['order_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_id'])) : "";
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

    if (isset($_POST['order_id']) && $_POST['order_id'] != '') {
        $where .= " AND oi.order_id= $order_id";
    }
    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $start_date = $db->escapeString($fn->xss_clean($_POST['start_date']));
        $end_date = $db->escapeString($fn->xss_clean($_POST['end_date']));
        $where .= " AND DATE(oi.date_added) >= '" . $start_date . "' AND DATE(oi.date_added) <= '" . $end_date . "'";
    }
    if (isset($_POST['filter_order']) && $_POST['filter_order'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_POST['filter_order']));
        $where .= " AND oi.`active_status`='" . $filter_order . "'";
    }
    $sql = "select count(DISTINCT(order_id)) as total, COUNT(DISTINCT(seller_id)) as total_seller from order_items oi where oi.delivery_boy_id=" . $delivery_boy_id . $where;

    $db->sql($sql);
    $res = $db->getResult();
    $total_seller = $res[0]['total_seller'];
    $total_count = $res[0]['total'];
    $sql = "select DISTINCT o.id,oi.seller_id,o.*,u.name as user_name from orders o JOIN order_items oi ON o.id=oi.order_id JOIN users u ON u.id=oi.user_id where oi.delivery_boy_id=" . $delivery_boy_id . $where . " ORDER BY oi.date_added DESC LIMIT $offset,$limit";

    $db->sql($sql);
    $res = $db->getResult();
    $i = 0;
    $j = 0;
    foreach ($res as $row) {
        $final_sub_total = 0;
        if ($row['discount'] > 0) {
            $discounted_amount = $row['total'] * $row['discount'] / 100;
            $final_total = $row['total'] - $discounted_amount;
            $discount_in_rupees = $row['total'] - $final_total;
        } else {
            $discount_in_rupees = 0;
        }

        $res[$i]['discounted_price'] = strval($discount_in_rupees);
        $final_total = ceil($res[$i]['final_total']);
        $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $row['seller_id'], 'seller');
        $res_pincode = $fn->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
        $res_city = $fn->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
        $city = isset($res_city[0]['name']) && !empty($res_city[0]['name']) ? $res_city[0]['name'] . ',' : '';
        $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . "," : "";
        $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . "," : "";
        $pincode = (!empty($res_seller[0]['pincode_id'])) ? $res_pincode[0]['pincode'] : "";
        $seller_address = $city . $state  . $street . $pincode;
        $res[$i]['final_total'] = strval($final_total + $res[$i]['delivery_charge']);

        $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));
        $res[$i]['seller_address'] = $seller_address;
        $res[$i]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
        $res[$i]['seller_mobile'] = $res_seller[0]['mobile'];
        $res[$i]['seller_address'] = $seller_address;
        $res[$i]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
        $res[$i]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
        $res[$i]['otp'] = (isset($config['generate-otp']) && $config['generate-otp'] != 0) ? $row['otp'] : '0';
        if ($total_seller > 1) {
            $res[$i]['delivery_charge'] = round($res[$i]['delivery_charge'] / $total_seller);
        } else {
            $res[$i]['delivery_charge'] = $res[$i]['delivery_charge'];
        }

        $sql = "select oi.*,v.id as variant_id, p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where oi.order_id=" . $row['id'] . " AND oi.delivery_boy_id=$delivery_boy_id AND oi.seller_id=" . $row['seller_id'];
        $db->sql($sql);
        $res[$i]['items'] = $db->getResult();
        unset($res[$i]['status']);
        unset($res[$i]['active_status']);


        for ($j = 0; $j < count($res[$i]['items']); $j++) {
            unset($res[$i]['items'][$j]['status']);
            if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                $final_sub_total += $res[$i]['items'][$j]['sub_total'];
            }
            if ($res[$i]['items'][$j]['active_status'] == 'cancelled' or $res[$i]['items'][$j]['active_status'] == 'returned') {
                $res[$i]['delivery_charge'] = 0;
            }
            if (!empty($res[$i]['items'][$j]['seller_id'])) {
                $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
            } else {
                $res[$i]['items'][$j]['seller_id'] = "";
                $res[$i]['items'][$j]['seller_name'] = "";
                $res[$i]['items'][$j]['seller_store_name'] = "";
            }
            $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];

            if (!empty($res[$i]['items'][$j]['delivery_boy_id'])) {
                $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $res[$i]['items'][$j]['delivery_boy_id'], 'delivery_boys');
                $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
            } else {
                $res[$i]['items'][$j]['delivery_boy_name'] = "";
            }
            $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
            $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';

            $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
        }
        $res[$i]['final_total'] = strval($final_sub_total + $res[$i]['delivery_charge'] - $res[$i]['promo_discount'] - $res[$i]['wallet_balance']);
        $res[$i]['total'] = $res[$i]['final_total'];

        $i++;
    }
    $orders = $order = array();

    if (!empty($res)) {
        $orders['error'] = false;
        $orders['total'] = $total_count;
        $orders['data'] = array_values($res);
        print_r(json_encode($orders));
    } else {
        $res['error'] = true;
        $res['message'] = "No orders found!";
        print_r(json_encode($res));
        return false;
    }
}


/*
15.bulk_status_update
    accesskey:90336
    bulk_status_update:1
    item_ids:3,4
    status:processed
*/
if ((isset($_POST['bulk_status_update'])) && ($_POST['bulk_status_update'] == 1)) {
    $item_ids = (isset($_POST['item_ids']) && !empty($_POST['item_ids'])) ? $db->escapeString($fn->xss_clean($_POST['item_ids'])) : "";
    $postStatus = (isset($_POST['status']) && !empty($_POST['status'])) ? $db->escapeString($fn->xss_clean($_POST['status'])) : "";

    if (!empty($item_ids) && !empty($postStatus)) {
        $item_ids = explode(',', $item_ids);

        for ($i = 0; $i < count($item_ids); $i++) {

            $sql = 'select user_id,order_id,active_status,status from order_items where id = ' . $item_ids[$i];
            $db->sql($sql);
            $res_order_item = $db->getResult();

            $status = json_decode($res_order_item[0]['status']);

            if (!empty($_POST['status'])) {
                foreach ($status as $each) {
                    if (in_array($postStatus, $each)) {
                        $response['error'] = true;
                        $response['message'] = 'Order already ' . $postStatus . ". Order item ID " . $item_ids[$i];
                        print_r(json_encode($response));
                        return false;
                    }
                }
                /* Cannot return order unless it is delivered */
                if ($fn->is_order_item_returned($res_order_item[0]['active_status'], $postStatus)) {
                    $response['error'] = true;
                    $response['message'] = 'Cannot return order unless it is delivered! please check order item ID ' . $item_ids[$i];
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
                $response['message'] = "Order can not be awaiting status. Because it is on " . $res_order_item[0]['active_status'] . " Order item ID " . $item_ids[$i];
                print_r(json_encode($response));
                return false;
            }

            if ($res_order_item[0]['active_status'] == 'delivered' && $postStatus == 'cancelled') {
                $response['error'] = true;
                $response['message'] = '';
                $response['message'] = 'Could not cancel delivered item. please check order item ID ' . $item_ids[$i];
                print_r(json_encode($response));
                return false;
            }

            /* check for awaiting status */
            if ($res_order_item[0]['active_status'] == 'awaiting_payment' && ($postStatus == 'returned' || $postStatus == 'delivered' || $postStatus == 'shipped' || $postStatus == 'processed')) {
                $response['error'] = true;
                $response['message'] = "Order can not be $postStatus. Because it is on awaiting status. Order item ID " . $item_ids[$i];
                print_r(json_encode($response));
                return false;
            }

            /* Could not update order status once cancelled or returned! */
            if ($fn->is_order_item_cancelled($item_ids[$i])) {
                $response['error'] = true;
                $response['message'] = 'Could not update order status  cancelled or returned! please check order item ID ' . $item_ids[$i];
                print_r(json_encode($response));
                return false;
            }

            /* if given status is cancel or return */
            if ($postStatus == 'cancelled' || $postStatus == 'returned') {

                /* fetch order items details */
                $sql = 'SELECT oi.`id` as order_item_id,oi.`product_variant_id`,oi.`quantity`,oi.`user_id`,oi.`order_id`,pv.`product_id`,pv.`type`,pv.`stock`,pv.`stock_unit_id`,pv.`measurement`,pv.`measurement_unit_id` FROM `order_items` oi join `product_variant` pv on pv.id = oi.product_variant_id WHERE oi.`id`=' . $item_ids[$i];
                $db->sql($sql);
                $res_oi = $db->getResult();

                if ($postStatus == 'cancelled') {
                    /* check for item cancellable or not */
                    if ($cancel_order_from == "") {
                        $cancelation_error = 0;
                        $resp = $fn->is_product_cancellable($res_oi[0]['order_item_id']);
                        if ($resp['till_status_error'] == 1 || $resp['cancellable_status_error'] == 1) {
                            $cancelation_error = 1;
                        }
                        if ($cancelation_error == 1) {
                            $resp['error'] = true;
                            $resp['message'] = "Order item ID " . $item_ids[$i] . " is not cancelable or not matching cancelation criteria!";
                            print_r(json_encode($resp));
                            return false;
                        }
                    }

                    if ($fn->cancel_order_item($order_id, $item_ids[$i])) {
                        $order_item_cancelled = true;
                    } else {
                        $order_item_cancelled = false;
                    }
                } else if ($postStatus == 'returned') {
                    /* check for item returnable or not */
                    $return_error = 0;
                    $resp = $fn->is_product_returnable($res_oi[0]['order_item_id']);
                    if ($resp['return_status_error'] == 1) {
                        $return_error = 1;
                    }

                    if ($return_error == 1) {
                        $resp['error'] = true;
                        $resp['message'] = "Order item ID " . $item_ids[$i] . " is not returnable";
                        print_r(json_encode($resp));
                        return false;
                    }

                    if ($fn->is_return_request_exists($res_oi[0]['user_id'], $item_ids[$i])) {
                        $response['error'] = true;
                        $response['message'] = 'Order item ID ' . $item_ids[$i] . ' Already applied for return.';
                        print_r(json_encode($response));
                        return false;
                    }

                    $is_item_delivered = 0;
                    $product_details = $fn->get_product_by_variant_id2($res_oi[0]['product_variant_id']);

                    /* check for item return days */
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

                            if ($today > $status_date) {
                                $response['error'] = true;
                                $response['message'] = 'Oops! Sorry you cannot return the item now. You have crossed product\'s maximum return period for order item ID ' . $item_ids[$i];
                                print_r(json_encode($response));
                                return false;
                            }
                        }
                    }

                    /* store return request */
                    $fn->store_return_request($res_oi[0]['user_id'], $res_oi[0]['order_id'], $item_ids[$i]);

                    if ($res_oi[0]['type'] == 'packet') {
                        /* When product type is packet */
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
                            $stock = $fn->convert_to_parent($res_oi[0]['measurement'], $res_oi[0]['measurement_unit_id']);
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

            $sql = 'select active_status,status,delivery_boy_id from order_items where id = ' . $item_ids[$i];
            $db->sql($sql);
            $res_delivery_boy_id = $db->getResult();

            if (isset($res_delivery_boy_id[0]['delivery_boy_id']) && !empty($res_delivery_boy_id[0]['delivery_boy_id'])) {
                if ($postStatus != '') {

                    $delivery_boy_name = $fn->get_data($columns = ['name'], 'id=' . $res_delivery_boy_id[0]['delivery_boy_id'], 'delivery_boys');

                    if ($postStatus == 'delivered') {
                        $title_delivery_boy = "Order item with ID : #" . $item_ids[$i] . "  has been delivered";
                        $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", your order has been delivered. ID : #" . $item_ids[$i] . ". Please take a note of it.";
                    } else {
                        if ($postStatus == 'received') {
                            $title_delivery_boy = "New Order item with ID : #" . $item_ids[$i] . "  has been received";
                            $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", You have new order to deliver. Here is your ID : #" . $item_ids[$i] . ". Please take a note of it.";
                        } else {
                            $title_delivery_boy = "Order item with ID : #" . $item_ids[$i] . "  has been " . ucwords($postStatus);
                            $message_delivery_boy = "Hello, Dear " . ucwords($delivery_boy_name[0]['name']) . ", Your order item ID #" . $item_ids[$i] . ". has been " . $postStatus . " Please take a note of it.";
                        }
                    }
                    $fn->send_notification_to_delivery_boy($res_delivery_boy_id[0]['delivery_boy_id'], $title_delivery_boy, $message_delivery_boy, 'delivery_boys', $item_ids[$i]);
                    $fn->store_delivery_boy_notification($res_delivery_boy_id[0]['delivery_boy_id'], $item_ids[$i], $title_delivery_boy, $message_delivery_boy, 'order_reward');
                }
            }

            if ($postStatus == 'delivered') {
                $sql = "SELECT oi.order_id,oi.user_id,oi.delivery_boy_id,oi.sub_total,o.final_total,o.total,o.payment_method,o.delivery_charge FROM orders o join order_items oi on oi.order_id=o.id WHERE oi.id=" . $item_ids[$i];
                $db->sql($sql);
                $res_boy = $db->getResult();

                if ($res_boy[0]['delivery_boy_id'] != 0) {

                    if (strtolower($res_boy[0]['payment_method']) == 'cod') {
                        $cash_received = $res_boy[0]['sub_total'] + $res_boy[0]['delivery_charge'];

                        $sql = "UPDATE delivery_boys SET cash_received = cash_received + $cash_received WHERE id=" . $res_boy[0]['delivery_boy_id'];
                        $db->sql($sql);

                        /* add delivery boy cash */
                        $delivery_cash = $fn->add_transaction($item_ids[$i], $res_boy[0]['delivery_boy_id'], 'delivery_boy_cash', $cash_received, 'Delivery boy collected COD');
                    }

                    $res_bonus = $fn->get_data($columns = ['name', 'bonus'], 'id=' . $res_boy[0]['delivery_boy_id'], 'delivery_boys');

                    $reward = $res_boy[0]['sub_total'] / 100 * $res_bonus[0]['bonus'];

                    if ($reward > 0) {
                        $sql = "UPDATE delivery_boys SET balance = balance + $reward WHERE id=" . $res_boy[0]['delivery_boy_id'];
                        $db->sql($sql);

                        $delivery_boy_id = $res_boy[0]['delivery_boy_id'];

                        /* add delivery boy commission */
                        $comission = $fn->add_delivery_boy_commission($delivery_boy_id, 'credit', $reward, 'Order Delivery Commission.');

                        $currency = $currency;
                        $message_delivery_boy = "Hello, Dear " . ucwords($res_bonus[0]['name']) . ", Here is the new update on your order for the order item ID : #" . $item_ids[$i] . ". Your Commission of" . $reward . " is credited. Please take a note of it.";

                        $fn->send_notification_to_delivery_boy($delivery_boy_id, "Your commission " . $reward . " " . $currency . " has been credited", "$message_delivery_boy", 'delivery_boys', $item_ids[$i]);
                        $fn->store_delivery_boy_notification($delivery_boy_id, $item_ids[$i], "Your commission " . $reward . " " . $currency . " has been credited", $message_delivery_boy, 'order_reward');
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
                                $res_data = $fn->get_data($columns = ['friends_code', 'name'], "referral_code='" . $res_user[0]['referral_code'] . "'", 'users');
                                $friend_user = $fn->get_data($columns = ['id'], "referral_code='" . $res_data[0]['friends_code'] . "'", 'users');

                                if (!empty($friend_user)) {
                                    $fn->add_wallet_transaction($res_boy[0]['order_id'], $item_ids[$i], $friend_user[0]['id'], 'credit', floor($bonus_amount), 'Refer & Earn Bonus on first order by ' . ucwords($res_data[0]['name']), 'wallet_transactions');
                                }

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
                    $db->update('order_items', $data, 'id=' . $item_ids[$i]);
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

                $db->update('order_items', $data, 'order_id=' . $order_id);

                /* get order data */
                $user_id1 = $fn->get_data($columns = ['user_id', 'total', 'delivery_charge', 'discount', 'final_total', 'payment_method', 'address', 'otp'], 'id=' . $order_id, 'orders');

                /* get user data */
                $user_email = $fn->get_data($columns = ['email', 'name'], 'id=' . $user_id1[0]['user_id'], 'users');
                $subject = "Order received successfully";

                /* get order item by order id */
                $order_item = $fn->get_order_item_by_order_id($order_id);
                $item_ids = array_column($order_item, 'product_variant_id');

                /* get product details by varient id */
                $item_details = $fn->get_product_by_variant_id(json_encode($item_ids));

                for ($i = 0; $i < count($item_details); $i++) {
                    $seller_id = $item_details[$i]['seller_id'];

                    if (!empty($seller_id)) {
                        $store_details = $fn->get_data($columns = ['email', 'store_name'], 'id=' . $seller_id, 'seller');
                    }

                    $item_data1[] = array(
                        'name' => $item_details[$i]['name'], 'store_name' => $store_details[0]['store_name'], 'tax_amount' => $order_item[$i]['tax_amount'], 'tax_percentage' => $order_item[$i]['tax_percentage'], 'tax_title' => $item_details[$i]['tax_title'], 'unit' =>  $item_details[$i]['measurement'] . " " . $item_details[$i]['measurement_unit_name'],
                        'qty' => $order_item[$i]['quantity'], 'subtotal' => $order_item[$i]['sub_total']
                    );

                    if (!empty($seller_id)) {
                        /* send seller notification */
                        $seller_subject = "New order placed for " . $store_details[0]['store_name'];
                        $seller_message = "New order item ID : #" . $item_ids[$i] . " received please take note of it and proceed further";
                        send_email($store_details[0]['email'], $seller_subject, $seller_message);
                        $fn->send_notification_to_seller($seller_id, $seller_subject, $seller_message, 'order', $item_ids[$i]);
                    }
                }

                /* get user wallet balance */
                $user_wallet_balance = $fn->get_wallet_balance($user_id1[0]['user_id'], 'users');

                $user_msg = "Hello, Dear " . $user_email[0]['name'] . ", We have received your order successfully. Your order summaries are as followed:<br><br>";
                $otp_msg = "Here is your OTP. Please, give it to delivery boy only while getting your order.";
                $order_data = array('total_amount' => $user_id1[0]['total'], 'delivery_charge' => $user_id1[0]['delivery_charge'], 'discount' => $user_id1[0]['discount'], 'wallet_used' => $user_wallet_balance, 'final_total' => $user_id1[0]['final_total'], 'payment_method' => $user_id1[0]['payment_method'], 'address' => $user_id1[0]['address'], 'user_msg' => $user_msg, 'otp_msg' => $otp_msg, 'otp' => $user_id1[0]['otp']);

                send_smtp_mail($user_email[0]['email'], $subject, $item_data1, $order_data);

                $fn->send_order_update_notification($user_id1[0]['user_id'], "Your order has been " . ucwords($postStatus), $user_msg, 'order', $id);

                $subject = "New order placed for $app_name";
                $message = "New order ID : #" . $id . " received please take note of it and proceed further";
                $fn->send_notification_to_admin("New Order Arrived.", $message, "admin_notification", $id);
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
                $db->update('order_items', $data, 'id=' . $item_ids[$i]);
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
                $db->update('order_items', $data, 'id=' . $item_ids[$i]);
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
                $db->update('order_items', $data, 'id=' . $item_ids[$i]);
            }

            if ($postStatus == 'returned') {
                $status[] = array('returned', date("d-m-Y h:i:sa"));
                $data = array('status' => $db->escapeString(json_encode($status)));
                $db->update('order_items', $data, 'id=' . $item_ids[$i]);
                $item_data = array(
                    'status' => $db->escapeString(json_encode($status)),
                    'active_status' => 'returned'
                );
            }

            $j = sizeof($status);
            $currentStatus = $status[$j - 1][0];
            $final_status = array(
                'active_status' => $currentStatus,
            );

            if ($db->update('order_items', $final_status, 'id=' . $item_ids[$i])) {

                if ($postStatus != 'received') {
                    $user_data = $fn->get_data($columns = ['name', 'email', 'mobile', 'country_code'], 'id=' . $user_id, 'users');

                    $to = $user_data[0]['email'];
                    $mobile = $user_data[0]['mobile'];
                    $country_code = $user_data[0]['country_code'];

                    $subject = "Your order has been " . ucwords($postStatus);
                    $message = "Hello, Dear " . ucwords($user_data[0]['name']) . ", Here is the new update on your order for the order ID : #" . $order_id . ". Your order has been " . ucwords($postStatus) . ". Please take a note of it.";
                    $message .= "Thank you for using our services!You will receive future updates on your order via Email!";

                    $fn->send_order_update_notification($user_id, "Your order has been " . ucwords($postStatus), $message, 'order', $order_id);

                    send_email($to, $subject, $message);
                }
                $res = $db->getResult();
            }
        }
        $response['error'] = false;
        $response['message'] = 'Status update successfully';
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}


if (isset($_POST['update_bulk_order_items']) && !empty($_POST['update_bulk_order_items'])) {

    /* 
    16.update_bulk_order_items
        accesskey:90336
        update_bulk_order_items:1
        order_items:12,13
        delivery_boy_id:40
        status:received | processed | shipped | delivered | cancelled | returned

    */

    if (empty($_POST['order_items']) || empty($_POST['status']) || empty($_POST['delivery_boy_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all mandatory fields!";
        print_r(json_encode($response));
        return false;
    }

    $postStatus = $db->escapeString($fn->xss_clean($_POST['status']));
    $delivery_boy_id = $db->escapeString(trim($fn->xss_clean(($_POST['delivery_boy_id']))));
    $order_items = $db->escapeString(trim($fn->xss_clean(($_POST['order_items']))));

    $order_items = explode(',', $order_items);
    for ($i = 0; $i < count($order_items); $i++) {
        $sql = "SELECT delivery_boy_id FROM `order_items` WHERE id =" . $order_items[$i];
        $db->sql($sql);
        $result = $db->getResult();
        $dboy_id = $result[0]['delivery_boy_id'];

        if ($delivery_boy_id != $dboy_id) {
            $response['error'] = true;
            $response['message'] = 'You are not authorized to update status of this ' . $order_items[$i] . ' order!';
            print_r(json_encode($response));
            return false;
        }
    }
    $response = $fn->update_bulk_order_items($order_items, $postStatus, $delivery_boy_id);
    print_r(strip_tags($response));
}
