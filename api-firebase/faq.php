<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include_once('../includes/crud.php');
include_once('../includes/variables.php');
include_once('verify-token.php');
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
$db = new Database();
$db->connect();
$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
$time_zone = $fn->set_timezone($config);
if (!$time_zone) {
    $response['error'] = true;
    $response['message'] = "Time Zone is not set.";
    print_r(json_encode($response));
    return false;
}

if (!verify_token()) {
    return false;
}

if (isset($_POST['accesskey'])) {
    /*
    1. get_faq
        accesskey:90336
        keyword:Why should I buy online?
        page:0
        limit:3
    */
    $access_key_received = $db->escapeString($fn->xss_clean($_POST['accesskey']));

    if ($access_key_received == $access_key) {
        $function = new functions;
        $data = array();
        if (isset($_POST['keyword'])) {
            $sql_query = "SELECT id, question, answer FROM faq where answer != '' and question LIKE '" . $function->sanitize($_POST['keyword']) . "' ORDER BY id DESC";
        } else {
            $sql_query = "SELECT id, question, answer FROM faq where answer != '' ORDER BY id DESC";
        }
        $db->sql($sql_query);
        $res = $db->getResult();
        $total_records = $db->numRows($res);
        if (isset($_POST['page'])) {
            $page = $_POST['page'];
        } else {
            $page = 1;
        }
        $limit = 10;
        if (isset($_POST['limit'])) {
            $limit = $_POST['limit'];
        }
        if ($page) {
            $from     = ($page * $limit) - $limit;
        } else {
            $from = 0;
        }
        if (empty($keyword)) {
            $sql_query = "SELECT id, question, answer FROM faq WHERE answer != '' ORDER BY id DESC LIMIT " . $from . "," . $limit . "";
        } else {
            $sql_query = "SELECT id, question, answer FROM faq WHERE answer != '' and question LIKE " . $keyword . "  ORDER BY id DESC LIMIT " . $from . "," . $limit . "";
        }
        $db->sql($sql_query);
        $res = $db->getResult();
        if (!empty($res)) {
            $response['error'] = false;
            $response['total'] = $total_records;
            $response['message'] = "FAQ Data Retrived successfully!";
            $response['data'] = $res;
        } else {
            $response['data'] = [];
            $response['total'] = 0;
            $response['error'] = false;
            $response['message'] = "No data found!";
        }
        $output = json_encode($response);
    } else {
        die('accesskey is incorrect.');
    }
} else {
    die('accesskey is required.');
}
echo $output;

$db->disconnect();
