<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once('../includes/crud.php');
$db = new Database();
$db->connect();
include_once('../includes/variables.php');
include_once('verify-token.php');
include_once('../includes/custom-functions.php');
$fn = new custom_functions;

$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

/* 
-------------------------------------------
APIs for Multi Vendor
-------------------------------------------
1. get-subcategories
-------------------------------------------
-------------------------------------------
*/

if (!verify_token()) {
    return false;
}


if (!isset($_POST['accesskey'])  || trim($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
}

/* 
1.get-subcategories
    accesskey:90336
    category_id:29      // {optional}
*/

$category_id = (isset($_POST['category_id']) && !empty($_POST['category_id'])) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : "";

$where = "";
if (isset($_POST['category_id']) && !empty($_POST['category_id']) && is_numeric($_POST['category_id'])) {
    $where .=  !empty($where) ? " AND `category_id`=" . $category_id  : " WHERE `category_id`=" . $category_id;
}

$sql = "SELECT count(id) as total FROM subcategory $where ";
$db->sql($sql);
$total = $db->getResult();

$sql = "SELECT * FROM subcategory $where ORDER BY row_order ASC ";
$db->sql($sql);
$res = $db->getResult();

if (!empty($res)) {
    for ($i = 0; $i < count($res); $i++) {
        $res[$i]['image'] = (!empty($res[$i]['image'])) ? DOMAIN_URL . '' . $res[$i]['image'] : '';
    }
    $response['error'] = false;
    $response['message'] = "Sub Categories retrieved successfully";
    $response['total'] = $total[0]['total'];
    $response['data'] = $res;
} else {
    $response['error'] = true;
    $response['message'] = "No data found!";
    $response['total'] = $total[0]['total'];
    $response['data'] = array();
}
print_r(json_encode($response));
return false;
