<?php
header('Access-Control-Allow-Origin: *');
include_once('../includes/crud.php');
include_once('../includes/custom-functions.php');
include_once('../includes/variables.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES utf8");
$function = new custom_functions();
$config = $function->get_configurations();

$config = $function->get_configurations();
$time_slot_config = $function->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}
/*
	accesskey:90336
	get_wallet_transactions:1
	user_id:5
	offset:0 // {optional}
    limit:10 // {optional}

 */

$response = array();
$accesskey = $db->escapeString($function->xss_clean($_POST['accesskey']));
if (!isset($_POST['accesskey']) || $access_key != $accesskey) {
    $response['error'] = true;
    $response['message'] = "invalid accesskey";
    print_r(json_encode($response));
    return false;
}
if (!verify_token()) {
    return false;
}

if (isset($_POST['get_wallet_transactions']) && isset($_POST['user_id'])) {
    $user_id = $db->escapeString($function->xss_clean($_POST['user_id']));
    $offset = (isset($_POST['offset']) && !empty($_POST['offset'])) ? $db->escapeString($function->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($_POST['limit'])) ? $db->escapeString($function->xss_clean($_POST['limit'])) : 20;
    $sql = "SELECT w.*,u.name,u.email FROM wallet_transactions w JOIN users u ON u.id=w.user_id where w.user_id=" . $user_id . " order by id DESC LIMIT $offset,$limit ";
    $db->sql($sql);
    $res = $db->getResult();
    $wallet_transaction = $response = array();
    if (!empty($res)) {
        foreach ($res as $row) {
            $wallet_transaction['id'] = $row['id'];
            $wallet_transaction['user_id'] = $row['user_id'];
            $wallet_transaction['name'] = $row['name'];
            $wallet_transaction['email'] = $row['email'];
            $wallet_transaction['type'] = $row['type'];
            $wallet_transaction['amount'] = $row['amount'];
            $wallet_transaction['message'] = $row['message'];
            $wallet_transaction['status'] = $row['status'];
            $wallet_transactions[] = $wallet_transaction;
        }
        $response['error'] = false;
        $response['data'] = $wallet_transactions;
        print_r(json_encode($response));
    } else {
        $payment_request['error'] = true;
        $payment_request['message'] = "No wallet transactions found!";
        print_r(json_encode($payment_request));
    }
}

if (isset($_POST['verify_paystack_transaction']) && isset($_POST['reference'])) {
    $reference = $db->escapeString($function->xss_clean($_POST['reference']));
    $email = $db->escapeString($function->xss_clean($_POST['email']));
    $amount = $db->escapeString($function->xss_clean($_POST['amount']));
    if (empty($reference) || empty($email) || empty($amount) || !is_numeric($amount)) {
        $response['error'] = true;
        $response['message'] = "Invalid data supplied please pass all the fields.";
        $response['status'] = "failed";
        print_r(json_encode($response));
        return false;
    }
    $response = $function->verify_paystack_transaction($reference, $email, $amount);
    print_r(json_encode($response));
}
