<?php session_start();
header('Access-Control-Allow-Origin: *');
include '../includes/crud.php';
include '../includes/variables.php';
include_once('verify-token.php');
$db = new Database();
$db->connect();
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
$config = $fn->get_configurations();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

$response = array();
$accesskey = isset($_POST['accesskey']) && $_POST['accesskey'] != '' ? $db->escapeString($fn->xss_clean($_POST['accesskey'])) : '';
if (empty($accesskey)) {
    $response['error'] = true;
    $response['message'] = "accesskey required";
    print_r(json_encode($response));
    return false;
}
if ($access_key != $accesskey) {
    $response['error'] = true;
    $response['message'] = "invalid accesskey";
    print_r(json_encode($response));
    return false;
}
// if (!verify_token()) {
//     return false;
// }

if (isset($_POST['validate_promo_code']) && $_POST['validate_promo_code'] == 1) {

    if ((isset($_POST['user_id']) && $_POST['user_id'] != '') && (isset($_POST['promo_code']) && $_POST['promo_code'] != '') && (isset($_POST['total']) && $_POST['total'] != '')) {
        $user_id = $db->escapeString($fn->xss_clean($_POST['user_id']));
        $promo_code = $db->escapeString($fn->xss_clean($_POST['promo_code']));
        $total = $db->escapeString($fn->xss_clean($_POST['total']));
        $response = $fn->validate_promo_code($user_id, $promo_code, $total);
        print_r(json_encode($response));
        return false;
    } else {
        $response['error'] = true;
        $response['message'] = "Please enter user id,promo code and total.";
        echo json_encode($response);
        return false;
    }
}

if (isset($_POST['get_promo_codes']) && $_POST['get_promo_codes'] == 1) {
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;

    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';


    if (!isset($_POST['user_id']) || empty($_POST['user_id']) || !isset($_POST['amount']) || empty($_POST['amount'])) {
        $response["error"]   = true;
        $response["message"] = "Please Pass User id and amount";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $user_id = $db->escapeString(trim($fn->xss_clean($_POST['user_id'])));
    $amount = $db->escapeString(trim($fn->xss_clean($_POST['amount'])));

    $sql = "SELECT count(id) as total FROM `promo_codes` WHERE status=1 AND CURDATE() between start_date and end_date";
    $db->sql($sql);
    $total = $db->getResult();

    $i = 0;

    $sql = "SELECT * FROM promo_codes WHERE status=1 AND CURDATE() between start_date and end_date ORDER BY `$sort` $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            for ($k = 0; $k < count($total); $k++) {
                $promo_code = $row['promo_code'];
                $res[$i]['is_validate'][$k] = $fn->validate_promo_code($user_id, $promo_code, $amount);
                $res[$i]['is_validate'][$k]['promo_code'] = !empty($res[$i]['is_validate'][$k]['promo_code']) ? $res[$i]['is_validate'][$k]['promo_code'] : "";
                $res[$i]['is_validate'][$k]['promo_code_message'] = !empty($res[$i]['is_validate'][$k]['promo_code_message']) ? $res[$i]['is_validate'][$k]['promo_code_message'] : "";
                $res[$i]['is_validate'][$k]['total'] = ($res[$i]['is_validate'][$k]['total'] > 0) ? $res[$i]['is_validate'][$k]['total'] : 0;
                $res[$i]['is_validate'][$k]['discount'] = ($res[$i]['is_validate'][$k]['discount'] > 0) ? $res[$i]['is_validate'][$k]['discount'] : 0;
                $res[$i]['is_validate'][$k]['discounted_amount'] = ($res[$i]['is_validate'][$k]['discounted_amount'] > 0) ? $res[$i]['is_validate'][$k]['discounted_amount'] : 0;
            }
            $i++;
        }
        $response['error'] = false;
        $response['total'] = $total[0]['total'];
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = 'Data not Found!';
    }
    print_r(json_encode($response));
}
