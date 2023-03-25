<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
include '../includes/crud.php';
require_once '../includes/functions.php';
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
include_once('../includes/variables.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

if (!verify_token()) {
    return false;
}


if (!isset($_POST['accesskey'])  || trim($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_seller_data']) && $_POST['get_seller_data'] == 1) {
    /* 
    1.get_seller_data
        accesskey:90336
        get_seller_data:1
        seller_id:1  // {optional}
        pincode_id:1  // {optional}
        slug:multivendor-store-1 //{optional}
    */
    $where = "";
    $response = array();
    if (isset($_POST['seller_id']) && $_POST['seller_id'] != "" && is_numeric($_POST['seller_id'])) {
        $id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
        $where .=  " AND s.id= $id ";
    }
    if (isset($_POST['slug']) && $_POST['slug'] != "") {
        $slug = $db->escapeString($fn->xss_clean($_POST['slug']));
        $where .=  " AND s.slug= '$slug' ";
    }
    if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") {
        $pincode_id = $db->escapeString($fn->xss_clean($_POST['pincode_id']));
        $sql_query = "select s.* from products p join seller s on s.id=p.seller_id WHERE s.status = 1 and p.status=1 AND (((p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes)) or p.type = 'all') OR ((p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes)))) $where GROUP by p.seller_id ";
    }else{
        $sql_query = "SELECT * FROM `seller` s WHERE s.status = 1 $where";
    }
    // echo $sql_query;
    $db->sql($sql_query);
    $result = $db->getResult();
   
    $rows = array();
    $tempRow = array();

    foreach ($result as $row) {
        $seller_address = $fn->get_seller_address($row['id']);

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['store_name'] = $row['store_name'];
        $tempRow['slug'] = $row['slug'];
        $tempRow['email'] = $row['email'];
        $tempRow['mobile'] = $row['mobile'];
        $tempRow['balance'] = strval(ceil($row['balance']));
        $tempRow['store_url'] = $row['store_url'];
        $tempRow['store_description'] = $row['store_description'];
        $tempRow['street'] = $row['street'];
        $tempRow['pincode_id'] = $row['pincode_id'];
        $tempRow['state'] = $row['state'];
        $tempRow['categories'] = $row['categories'];
        $tempRow['account_number'] = $row['account_number'];
        $tempRow['bank_ifsc_code'] = $row['bank_ifsc_code'];
        $tempRow['bank_name'] = $row['bank_name'];
        $tempRow['account_name'] = $row['account_name'];
        $tempRow['logo'] = DOMAIN_URL . 'upload/seller/' . $row['logo'];
        $tempRow['national_identity_card'] = DOMAIN_URL . 'upload/seller/' . $row['national_identity_card'];
        $tempRow['address_proof'] = DOMAIN_URL . 'upload/seller/' . $row['address_proof'];
        $tempRow['pan_number'] = !empty($row['pan_number']) ? $row['pan_number'] : "";
        $tempRow['tax_name'] = !empty($row['tax_name']) ? $row['tax_name'] : "";
        $tempRow['tax_number'] = !empty($row['tax_number']) ? $row['tax_number'] : "";
        $tempRow['categories'] = !empty($row['categories']) ? $row['categories'] : "";
        $tempRow['longitude'] = (!empty($row['longitude']))  ? $row['longitude'] : "";
        $tempRow['latitude'] = !empty($row['latitude'])  ? $row['latitude'] : "";
        $tempRow['seller_address'] = $seller_address;
        $rows[] = $tempRow;
    }

  

    if ($db->numRows($result) > 0) {
        $response['error']     = false;
        $response['message']   = "Seller retrieved successfully!";
        $response['data'] = $rows;
    } else {
        $response['error']     = true;
        $response['message']   = "Seller data does not exists!";
        $response['data'] = array();
    }

    print_r(json_encode($response));
    return false;
}
