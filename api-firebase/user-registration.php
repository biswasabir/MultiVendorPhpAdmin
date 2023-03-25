<?php
header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-CSRF-Token');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('../includes/crud.php');
include('../includes/custom-functions.php');
include('verify-token.php');
$fn = new custom_functions();
$db = new Database();
$db->connect();
$settings = $fn->get_settings('system_timezone', true);
$app_name = $settings['app_name'];
include 'send-email.php';

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
1. verify_user
2. edit_profile
3. change_password
4. forgot_password_mobile
5. register_device
6. register
7. upload_profile

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

if ((isset($_POST['type'])) && ($_POST['type'] == 'verify-user')) {
    /*
    1. verify_user
        accesskey:90336
        type:verify-user
        mobile:1234567890
        web:1 {optional}
    */

    if (empty($_POST['mobile'])) {
        $response['error'] = true;
        $response['message'] = "Mobile should be filled!";
        print_r(json_encode($response));
        return false;
    }

    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $web = (isset($_POST['web']) && !empty($_POST['web'])) ? $db->escapeString($fn->xss_clean($_POST['web'])) : "";


    $sql = 'select id from users where mobile =' . $mobile;
    $db->sql($sql);
    $res = $db->getResult();
    $num_rows = $db->numRows($res);
    if ($num_rows > 0) {
        if ($web == "1") {
            $response["error"]   = true;
            $response["message"] = "This mobile is already registered. Please login!";
            $response["id"]   = $res[0]['id'];
        } else {
            $response["error"]   = false;
            $response["message"] = "This mobile is already registered. Please login!";
            $response["id"]   = $res[0]['id'];
        }
    } else if ($num_rows == 0) {
        if ($web == "1") {
            $response["error"]   = false;
            $response["message"] = "Ready to sent firebase OTP request!";
        } else {
            $response["error"]   = true;
            $response["message"] = "Ready to sent firebase OTP request!";
        }
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['type']) && $_POST['type'] != '' && $_POST['type'] == 'edit-profile') {
    /*
    2. edit_profile
        accesskey:90336
        type:edit-profile
        user_id:178
        name:Jaydeep
        email:admin@gmail.com
        mobile:1234567890
        profile:file        // {optional}
    */

    if (empty($_POST['user_id']) || empty($_POST['name']) || empty($_POST['email'])) {
        $response['error'] = true;
        $response['message'] = "pass all field!";
        print_r(json_encode($response));
        return false;
    }

    $id     = $db->escapeString($fn->xss_clean($_POST['user_id']));
    $name   = $db->escapeString($fn->xss_clean($_POST['name']));
    $email  = $db->escapeString($fn->xss_clean($_POST['email']));
    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));

    $sql = 'select * from users where id =' . $id;
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {

        if (isset($_FILES['profile']) && !empty($_FILES['profile']) && $_FILES['profile']['error'] == 0 && $_FILES['profile']['size'] > 0) {
            if (!empty($res[0]['profile'])) {
                $old_image = $res[0]['profile'];
                if ($old_image != 'default_user_profile.png' && !empty($old_image)) {
                    unlink('../upload/profile/' . $old_image);
                }
            }

            $profile = $db->escapeString($fn->xss_clean($_FILES['profile']['name']));
            $extension = pathinfo($_FILES["profile"]["name"])['extension'];
            $result = $fn->validate_image($_FILES["profile"]);
            if (!$result) {
                $response["error"]   = true;
                $response["message"] = "Image type must jpg, jpeg, gif, or png!";
                print_r(json_encode($response));
                return false;
            }
            $filename = microtime(true) . '.' . strtolower($extension);
            $full_path = '../upload/profile/' . "" . $filename;
            if (!move_uploaded_file($_FILES["profile"]["tmp_name"], $full_path)) {
                $response["error"]   = true;
                $response["message"] = "Invalid directory to load profile!";
                print_r(json_encode($response));
                return false;
            }
            $sql = "UPDATE users SET `profile`='" . $filename . "' WHERE `id`=" . $id;
            $db->sql($sql);
        }

        $mobile = (isset($mobile) && $mobile != '') ? $mobile : $res[0]['mobile'];

        $sql = 'UPDATE `users` SET `name`="' . $name . '",`email`="' . $email . '" ,`mobile`="' . $mobile . '" WHERE `id`=' . $id;
        $db->sql($sql);

        $response["error"]   = false;
        $response["message"] = "Profile has been updated successfully.";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['type']) && $_POST['type'] != '' && $_POST['type'] == 'change-password') {
    /* 
    3.change_password
        accesskey:90336
        type:change-password
        user_id:5
        password:12345678
    */

    if (empty($_POST['user_id']) || empty($_POST['password'])) {
        $response['error'] = true;
        $response['message'] = "pass all field!";
        print_r(json_encode($response));
        return false;
    }
    $id       = $db->escapeString($fn->xss_clean($_POST['user_id']));
    $password = $db->escapeString($fn->xss_clean($_POST['password']));
    $password = md5($password);

    $mobile = $fn->get_data($columns = ['mobile'], 'id = "' . $id . '"', 'users');
    $mobile = $mobile[0]['mobile'];
    if ($mobile == '9876543210' && defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response["error"]   = true;
        $response["message"] = "Demo account password couldn't be changed!";
        print_r(json_encode($response));
        return false;
    }

    $sql = 'UPDATE `users` SET `password`="' . $password . '" WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        $response["error"]   = false;
        $response["message"] = "Password updated successfully";
    } else {
        $response["error"]   = true;
        $response["message"] = "Something went wrong! Try Again!";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['type']) && $_POST['type'] != '' && $_POST['type'] == 'forgot-password-mobile') {
    /* 
    4.forgot_password_mobile
        accesskey:90336
        type:forgot-password-mobile
        mobile:1234567890
        password:12345678
    */

    if (empty($_POST['mobile']) || empty($_POST['password'])) {
        $response['error'] = true;
        $response['message'] = "pass all field!";
        print_r(json_encode($response));
        return false;
    }

    $mobile  = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $password = $db->escapeString($fn->xss_clean($_POST['password']));

    $encrypted_password = md5($password);
    $sql = "select `id`,`name`,`country_code` from `users` where `mobile`='" . $mobile . "'";
    $db->sql($sql);
    $result = $db->getResult();

    if ($db->numRows($result) > 0) {
        $country_code = $result[0]['country_code'];
        $message = 'Your Password for ' . $app_name . ' is Reset. Please login using new Password : ' . $password . '.';
        $sql = 'UPDATE `users` SET `password`="' . $encrypted_password . '" WHERE `mobile`="' . $mobile . '"';
        if ($db->sql($sql)) {
            $response["error"]   = false;
            $response["message"] = "Password is sent successfully! Please login via the OTP sent to your mobile number!";
        }
    } else {
        $response["error"]   = true;
        $response["message"] = "Mobile number does not exist! Please Register";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['type']) && $_POST['type'] != '' && $_POST['type'] == 'register-device') {
    /* 
    5.register_device
        accesskey:90336
        type:register-device
        user_id:122
        token:123fghjf687657fre78fg57gf8re7
    */

    if (empty($_POST['user_id']) || empty($_POST['token'])) {
        $response['error'] = true;
        $response['message'] = "pass all field!";
        print_r(json_encode($response));
        return false;
    }

    $user_id  = $db->escapeString($fn->xss_clean($_POST['user_id']));
    $token  = $db->escapeString($fn->xss_clean($_POST['token']));

    $sql = "select `id` from `users` where `id`='" . $user_id . "'";
    $db->sql($sql);
    $result = $db->getResult();
    if ($db->numRows($result) > 0) {
        $sql = 'UPDATE `users` SET `fcm_id`="' . $token . '" WHERE `id`="' . $user_id . '"';
        if ($db->sql($sql)) {
            $response["error"]   = false;
            $response["message"] = "Device updated successfully";
        }
    } else {
        $response["error"]   = true;
        $response["message"] = "User does't exists.";
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['type'])) && ($_POST['type'] == 'register')) {
    /* 
    6.register
        accesskey:90336
        type:register
        name:Jaydeep Goswami
        email:admin@gmail.com
        mobile:9876543210
        password:12345678
        friends_code:value //{optional}
        profile:FILE        // {optional}
        country_code:91  // {optional}
    */

    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $password = md5($db->escapeString($fn->xss_clean($_POST['password'])));
    $fcm_id = (isset($_POST['fcm_id'])) ? $db->escapeString($fn->xss_clean($_POST['fcm_id'])) : "";
    $country_code = (isset($_POST['country_code'])) ? $db->escapeString($fn->xss_clean($_POST['country_code'])) : "91";
    $status     = 1;
    $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $referral_code  = "";
    if (!empty($name) && !empty($email) && !empty($mobile) && !empty($password)) {
        for ($i = 0; $i < 10; $i++) {
            $referral_code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        if (isset($_POST['friends_code']) && $_POST['friends_code'] != '') {
            $friend_code = $db->escapeString($fn->xss_clean($_POST['friends_code']));
            $sql = "SELECT id FROM users WHERE referral_code='" . $friend_code . "'";
            $db->sql($sql);
            $result = $db->getResult();
            $num_rows = $db->numRows($result);
            if ($num_rows > 0) {
                $friends_code = $db->escapeString($fn->xss_clean($_POST['friends_code']));
            } else {
                $response["error"]   = true;
                $response["message"] = "Invalid friends code!";
                echo json_encode($response);
                return false;
            }
        } else {
            $friends_code = '';
        }


        if (!empty($mobile)) {
            $sql = "select mobile from users where mobile='" . $mobile . "'";
            $db->sql($sql);
            $res = $db->getResult();
            $num_rows = $db->numRows($res);
            if ($num_rows > 0) {
                $response["error"]   = true;
                $response["message"] = "This mobile $mobile is already registered. Please login!";
                print_r(json_encode($response));
                return false;
            } else if ($num_rows == 0) {
                if (isset($_FILES['profile']) && !empty($_FILES['profile']) && $_FILES['profile']['error'] == 0 && $_FILES['profile']['size'] > 0) {
                    $profile = $db->escapeString($fn->xss_clean($_FILES['profile']['name']));
                    if (!is_dir('../../upload/profile/')) {
                        mkdir('../../upload/profile/', 0777, true);
                    }
                    $extension = pathinfo($_FILES["profile"]["name"])['extension'];
                    $result = $fn->validate_image($_FILES["profile"]);

                    if (!$result) {
                        $response["error"]   = true;
                        $response["message"] = "Image type must jpg, jpeg, gif, or png!";
                        print_r(json_encode($response));
                        return false;
                    }

                    $filename = microtime(true) . '.' . strtolower($extension);
                    $full_path = '../upload/profile/' . "" . $filename;
                    if (!move_uploaded_file($_FILES["profile"]["tmp_name"], $full_path)) {
                        $response["error"]   = true;
                        $response["message"] = "Invalid directory to load profile!";
                        print_r(json_encode($response));
                        return false;
                    }
                } else {
                    $filename = 'default_user_profile.png';
                    $full_path = 'upload/profile/' . "" . $filename;
                }
                //user is not registered, insert the data to the database  
                $sql = "INSERT INTO users(`name`,`email`, `mobile`,`password`,`fcm_id`,`profile`,`referral_code`,`friends_code`,`status`,`country_code`)VALUES('$name','$email','$mobile','$password','$fcm_id','$filename','$referral_code','$friends_code','1','$country_code')";
                $db->sql($sql);
                $res = $db->getResult();
                $usr_id = $fn->get_data($columns = ['id'], 'mobile = "' . $mobile . '"', 'users');

                $sql = "DELETE FROM devices where fcm_id = '$fcm_id' ";
                $db->sql($sql);
                $res = $db->getResult();

                $sql_query = "SELECT * FROM `users` WHERE `mobile` = '" . $mobile . "' AND `password` ='" . $password . "'";
                $db->sql($sql_query);
                $result = $db->getResult();
                if ($db->numRows($result) > 0) {
                    $response["error"]   = false;
                    $response["message"] = "User registered successfully";
                    $response['password']  = $result[0]['password'];
                    foreach ($result as $row) {
                        $response['error']     = false;
                        $response['user_id'] = $row['id'];
                        $response['name'] = $row['name'];
                        $response['email'] = $row['email'];
                        $response['profile'] = DOMAIN_URL . 'upload/profile/' . "" . $row['profile'];
                        $response['mobile'] = $row['mobile'];
                        $response['balance'] = $row['balance'];
                        $response['country_code'] = $row['country_code'];
                        $response['referral_code'] = $row['referral_code'];
                        $response['friends_code'] = $row['friends_code'];
                        $response['fcm_id'] = $row['fcm_id'];
                        $response['status'] = $row['status'];
                        $response['created_at'] = $row['created_at'];
                    }
                }
            }
        }
    } else {
        $response["error"]   = true;
        $response["message"] = "Please pass all field";
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['type'])) && ($_POST['type'] == 'upload_profile')) {
    /* 
    7.upload_profile
        accesskey:90336
        type:upload_profile
        user_id:4
        profile:FILE        // {optional}
    */

    if (!isset($_POST['user_id']) && empty($_POST['user_id'])) {
        $response["error"]   = true;
        $response["message"] = "User id is missing.";
        print_r(json_encode($response));
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['user_id']));
    $sql = 'select * from users where id =' . $id;
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        if (isset($_FILES['profile']) && !empty($_FILES['profile']) && $_FILES['profile']['error'] == 0 && $_FILES['profile']['size'] > 0) {

            if (!is_dir('../upload/profile/')) {
                mkdir('../upload/profile/', 0777, true);
            }
            if (!empty($res[0]['profile'])) {
                $old_image = $res[0]['profile'];
                if ($old_image != 'default_user_profile.png' && !empty($old_image)) {
                    unlink('../upload/profile/' . $old_image);
                }
            }
            $profile = $db->escapeString($fn->xss_clean($_FILES['profile']['name']));
            $extension = pathinfo($_FILES["profile"]["name"])['extension'];
            $result = $fn->validate_image($_FILES["profile"]);
            if (!$result) {
                $response["error"]   = true;
                $response["message"] = "Image type must jpg, jpeg, gif, or png!";
                print_r(json_encode($response));
                return false;
            }
            $filename = microtime(true) . '.' . strtolower($extension);
            $full_path = '../upload/profile/' . "" . $filename;
            if (!move_uploaded_file($_FILES["profile"]["tmp_name"], $full_path)) {
                $response["error"]   = true;
                $response["message"] = "Invalid directory to load profile!";
                print_r(json_encode($response));
                return false;
            }
            $sql = "UPDATE users SET `profile`='" . $filename . "' WHERE `id`=" . $id;
            if ($db->sql($sql)) {
                $profile = $fn->get_data($columns = ['profile'], 'id = "' . $id . '"', 'users');
                $profile_url = DOMAIN_URL . 'upload/profile/' . "" . $profile[0]['profile'];
                $response["error"]   = false;
                $response["profile"]   = $profile_url;
                $response["message"] = "Profile has been updated successfully.";
            } else {
                $response["error"]   = true;
                $response["message"] = "Profile is not updated.";
            }
        } else {
            $response["error"]   = true;
            $response["message"] = "Profile parameter is missing.";
        }
    } else {
        $response["error"]   = true;
        $response["message"] = "User does not exist.";
    }
    print_r(json_encode($response));
    return false;
}
