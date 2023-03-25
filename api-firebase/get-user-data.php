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

/* 
-------------------------------------------
APIs for Multi Vendor
-------------------------------------------
1. get_user_data
2. remove_fcm_id
3. store_fcm_id
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

if (isset($_POST['get_user_data']) && $_POST['get_user_data'] == 1) {
    /* 
    1.get_user_data
        accesskey:90336
        get_user_data:1
        user_id:1748
    */
    if (isset($_POST['user_id']) && $_POST['user_id'] != '') {
        $id    = $db->escapeString($fn->xss_clean($_POST['user_id']));
        $response = array();
        $sql_query = "SELECT * FROM `users`  WHERE id=" . $id;
        $db->sql($sql_query);
        $result = $db->getResult();

        if ($db->numRows($result) > 0) {
            $response['error']     = false;
            $response['message']   = "User retrieved successfully!";
            foreach ($result as $row) {
                $tempRow['user_id'] = $_SESSION['user_id']    = $row['id'];
                $tempRow['name'] = $row['name'];
                $tempRow['email'] = $row['email'];
                $tempRow['country_code'] = $row['country_code'];
                $tempRow['profile'] = DOMAIN_URL . 'upload/profile/' . "" . $row['profile'];
                $tempRow['mobile'] = $row['mobile'];
                $tempRow['balance'] = $row['balance'];
                $tempRow['referral_code'] = $row['referral_code'];
                $tempRow['friends_code'] = !empty($row['friends_code']) ? $row['friends_code'] : "";
                $tempRow['fcm_id'] = $row['fcm_id'];
                $tempRow['status']     = $row['status'];
                $tempRow['created_at']     = $row['created_at'];

                $rows[] = $tempRow;
            }
            $response['data'] = $rows;
        } else {
            $response['error']     = true;
            $response['message']   = "User data does not exists!";
        }
    } else {
        $response["error"]   = true;
        $response["message"] = "User id is required";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['remove_fcm_id']) && $_POST['remove_fcm_id'] == 1) {
    /* 
    2.remove_fcm_id
        accesskey:90336
        remove_fcm_id:1
        user_id:1748
    */

    if (isset($_POST['user_id']) && $_POST['user_id'] != '') {

        $user_id  = $db->escapeString($fn->xss_clean($_POST['user_id']));

        $sql = "select `id` from `users` where `id`='" . $user_id . "'";
        $db->sql($sql);
        $result = $db->getResult();
        if ($db->numRows($result) > 0) {
            $sql = 'UPDATE `users` SET `fcm_id`="" WHERE `id`="' . $user_id . '"';
            if ($db->sql($sql)) {
                $response["error"]   = false;
                $response["message"] = "FCM ID removed successfully";
            }
        } else {
            $response["error"]   = true;
            $response["message"] = "User does't exists.";
        }
    } else {
        $response["error"]   = true;
        $response["message"] = "User id is required";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['store_fcm_id']) && $_POST['store_fcm_id'] == 1) {
    /* 
    3.store_fcm_id
        accesskey:90336
        store_fcm_id:1
        fcm_id:12345678jhfyjsdgfikt
        user_id:1748    // {optional}
    */
    if (isset($_POST['fcm_id']) && !empty($_POST['fcm_id'])) {
        $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : 0;
        $fcm_id = $db->escapeString($fn->xss_clean($_POST['fcm_id']));
        $function = new functions;
        $result = $function->registerDevice($fcm_id, $user_id);
        if ($result == 1) {
            $response['error'] = false;
            $response['fcm_id'] = $fcm_id;
            $response['message'] = 'FCM token updated successfully';
        } else {
            $response['error'] = true;
            $response['message'] = 'FCM token couldn\'t updated';
        }
    } else {
        $response["error"]   = true;
        $response["message"] = "fcm id is required";
    }
    print_r(json_encode($response));
    return false;
}
