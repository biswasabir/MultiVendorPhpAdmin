<?php
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');


include('../includes/crud.php');
include('../includes/custom-functions.php');
include('verify-token.php');
$fn = new custom_functions();
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
include('../includes/variables.php');

/* 
-------------------------------------------
APIs for Multi Vendor
-------------------------------------------
1. login
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

if (isset($_POST['login']) && $_POST['login'] == 1) {
    /* 
    1.login
        accesskey:90336
        mobile:9876543210
        password:12345678
        fcm_id:YOUR_FCM_ID
        login:1
    */

    if (empty($_POST['mobile'])) {
        $response['error'] = true;
        $response['message'] = "Mobile should be filled!";
        print_r(json_encode($response));
        return false;
    }
    if (empty($_POST['password'])) {
        $response['error'] = true;
        $response['message'] = "Password should be filled!";
        print_r(json_encode($response));
        return false;
    }

    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $password = md5($db->escapeString($fn->xss_clean($_POST['password'])));
    $sql = "SELECT * FROM users WHERE mobile = '" . $mobile . "' AND password = '" . $password . "'";
    $db->sql($sql);
    $res = $db->getResult();
    $num = $db->numRows($res);
    $rows = $tempRow = array();

    if ($num == 1) {
        if ($res[0]['status'] == 0) {
            $response['error'] = true;
            $response['message'] = "It seems your acount is not active please contact admin for more info!";
        } else {
            $user_id = $res[0]['id'];

            $fcm_id = (isset($_POST['fcm_id']) && !empty($_POST['fcm_id'])) ? $db->escapeString($fn->xss_clean($_POST['fcm_id'])) : "";
            if (!empty($fcm_id)) {
                $sql1 = "update users set `fcm_id` ='$fcm_id' where id = '" . $user_id . "'";
                $db->sql($sql1);
                $db->sql($sql);
                $res1 = $db->getResult();
                foreach ($res1 as $row) {
                    $tempRow['user_id'] = $row['id'];
                    $tempRow['name'] = $row['name'];
                    $tempRow['email'] = $row['email'];
                    $tempRow['profile'] = (!empty($row['profile'])) ? DOMAIN_URL . 'upload/profile/' . $row['profile'] : "";
                    $tempRow['country_code'] = $row['country_code'];
                    $tempRow['mobile'] = $row['mobile'];
                    $tempRow['balance'] = $row['balance'];
                    $tempRow['referral_code'] = $row['referral_code'];
                    $tempRow['friends_code'] = !empty($row['friends_code']) ? $row['friends_code'] : "";
                    $tempRow['fcm_id'] = $row['fcm_id'];
                    $tempRow['password'] = $row['password'];
                    $tempRow['status'] = $row['status'];
                    $tempRow['created_at'] = $row['created_at'];

                    $rows[] = $tempRow;
                }
                $db->disconnect();
            } else {
                foreach ($res as $row) {
                    $tempRow['user_id'] = $row['id'];
                    $tempRow['name'] = $row['name'];
                    $tempRow['email'] = $row['email'];
                    $tempRow['profile'] = (!empty($row['profile'])) ? DOMAIN_URL . 'upload/profile/' . $row['profile'] : "";
                    $tempRow['country_code'] = $row['country_code'];
                    $tempRow['mobile'] = $row['mobile'];
                    $tempRow['balance'] = $row['balance'];
                    $tempRow['referral_code'] = $row['referral_code'];
                    $tempRow['friends_code'] = !empty($row['friends_code']) ? $row['friends_code'] : "";
                    $tempRow['fcm_id'] = $row['fcm_id'];
                    $tempRow['password'] = $row['password'];
                    $tempRow['status'] = $row['status'];
                    $tempRow['created_at'] = $row['created_at'];

                    $rows[] = $tempRow;
                }
            }
            $db->disconnect();
            $response['error'] = false;
            $response['message'] = "Login Successfully";
            $response['user_id'] = $row['id'];
            $response['name'] = $row['name'];
            $response['email'] = $row['email'];
            $response['profile'] = (!empty($row['profile'])) ? DOMAIN_URL . 'upload/profile/' . $row['profile'] : "";
            $response['country_code'] = $row['country_code'];
            $response['mobile'] = $row['mobile'];
            $response['balance'] = $row['balance'];
            $response['referral_code'] = $row['referral_code'];
            $response['friends_code'] = !empty($row['friends_code']) ? $row['friends_code'] : "";
            $response['fcm_id'] = $row['fcm_id'];
            $response['password'] = $row['password'];
            $response['status'] = $row['status'];
            $response['created_at'] = $row['created_at'];
            $response['data'] = $rows;
        }
    } else {
            $response['error'] = true;
            $response['message'] = "Invalid number or password, Try again.";
    }
    print_r(json_encode($response));
    return false;
}
