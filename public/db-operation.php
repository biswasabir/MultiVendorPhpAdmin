<?php
session_start();
include('../includes/crud.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES 'utf8'");
$auth_username = $db->escapeString($_SESSION["user"]);

include_once('../includes/custom-functions.php');
$fn = new custom_functions;
include_once('../includes/functions.php');
include_once('../library/shiprocket.php');
$shiprocket = new Shiprocket();

$function = new functions;
$permissions = $fn->get_permissions($_SESSION['id']);
$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}
function checkadmin($auth_username)
{
    $db = new Database();
    $db->connect();
    $db->sql("SELECT `username` FROM `admin` WHERE `username`='$auth_username' LIMIT 1");
    $res = $db->getResult();
    if (!empty($res)) {

        return true;
    } else {
        return false;
    }
}
if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
    echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
    return false;
}

if (isset($_POST['change_category'])) {
    if ($permissions['subcategories']['read'] == 1) {
        if ($_POST['category_id'] == '') {
            $sql = "SELECT * FROM subcategory";
        } else {
            $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
            $sql = "SELECT * FROM subcategory WHERE category_id=" . $category_id;
        }
    } else {
        echo "<option value=''>--Select Subcategory--</option>";
        return false;
    }

    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
        }
    } else {
        echo "<option value=''>--No Sub Category is added--</option>";
    }
}

if (isset($_POST['category'])) {
    if ($permissions['subcategories']['read'] == 1) {
        if ($_POST['category_id'] == '') {
            $sql = "SELECT * FROM subcategory";
        } else {
            $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
            $sql = "SELECT * FROM subcategory WHERE category_id=" . $category_id;
        }

        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            echo "<option value=''>All</option>";
            foreach ($res as $row) {
                echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
            }
        } else {
            echo "<option value=''>--No Sub Category is added--</option>";
        }
    } else {
        echo "<option value=''>All</option>";
    }
}

if (isset($_POST['find_subcategory'])) {
    $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
    $sql = "SELECT * FROM subcategory WHERE category_id=" . $category_id;
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
        }
    } else {
        echo "<option value=''>--No Sub Category is added--</option>";
    }
}

if (isset($_POST['delete_variant'])) {
    $v_id = $db->escapeString($fn->xss_clean($_POST['id']));
    $result = $fn->delete_variant($v_id);
    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}

if (isset($_POST['system_configurations'])) {
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $date = $db->escapeString(date('Y-m-d'));
    $currency = empty($_POST['currency']) ? '₹' : $db->escapeString($fn->xss_clean($_POST['currency']));
    $sql = "UPDATE `settings` SET `value`='" . $currency . "' WHERE `variable`='currency'";
    $db->sql($sql);
    $message = "<div class='alert alert-success'> Settings updated successfully!</div>";
    $_POST['system_timezone_gmt'] = (trim($_POST['system_timezone_gmt']) == '00:00') ? "+" . trim($db->escapeString($fn->xss_clean($_POST['system_timezone_gmt']))) : $db->escapeString($fn->xss_clean($_POST['system_timezone_gmt']));

    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['current_version'])))) {
        $_POST['current_version'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['minimum_version_required'])))) {
        $_POST['minimum_version_required'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['delivery_charge'])))) {
        $_POST['delivery_charge'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['min-refer-earn-order-amount'])))) {
        $_POST['min-refer-earn-order-amount'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['min_amount'])))) {
        $_POST['min_amount'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['max-refer-earn-amount'])))) {
        $_POST['max-refer-earn-amount'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['minimum-withdrawal-amount'])))) {
        $_POST['minimum-withdrawal-amount'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['refer-earn-bonus'])))) {
        $_POST['refer-earn-bonus'] = 0;
    }

    $_POST['store_address'] = (!empty(trim($_POST['store_address']))) ? preg_replace("/[\r\n]{2,}/", "<br>", $_POST['store_address']) : "";

    $settings_value = json_encode($fn->xss_clean_array($_POST));

    $sql = "UPDATE settings SET value='" . $settings_value . "' WHERE variable='system_timezone'";
    $db->sql($sql);
    $res = $db->getResult();
    $sql_logo = "select value from `settings` where variable='Logo' OR variable='logo'";
    $db->sql($sql_logo);
    $res_logo = $db->getResult();
    $file_name = $_FILES['logo']['name'];

    if (!empty($_FILES["logo"]["tmp_name"]) && $_FILES["logo"]["size"] > 0) {
        $tmp = explode('.', $file_name);
        $ext = end($tmp);

        $result = $fn->validate_image($fn->xss_clean_array($_FILES["logo"]));
        if (!$result) {
            echo " <span class='label label-danger'>Logo Image type must jpg, jpeg, gif, or png!</span>";
            return false;
        } else {
            $old_image = '../dist/img/' . $res_logo[0]['value'];
            if (file_exists($old_image)) {
                unlink($old_image);
            }

            $target_path = '../dist/img/';
            $filename = "logo." . strtolower($ext);
            $full_path = $target_path . '' . $filename;
            if (!move_uploaded_file($_FILES["logo"]["tmp_name"], $full_path)) {
                $message = "Image could not be uploaded<br/>";
            } else {
                //Update Logo - id = 5
                $sql = "UPDATE `settings` SET `value`='" . $filename . "' WHERE `variable` = 'logo'";
                $db->sql($sql);
            }
        }
    }
    echo "<p class='alert alert-success'>Settings Saved!</p>";
}

if (isset($_POST['payment_method_settings'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $data = $fn->get_settings('payment_methods', true);
    if (empty($data)) {
        $json_data = json_encode($fn->xss_clean_array($_POST));
        $sql = "INSERT INTO `settings`(`variable`, `value`) VALUES ('payment_methods','$json_data')";
        $db->sql($sql);
        echo "<div class='alert alert-success'> Settings created successfully!</div>";
    } else {
        $json_data = json_encode($fn->xss_clean_array($_POST));
        $sql = "UPDATE `settings` SET `value`='$json_data' WHERE `variable`='payment_methods'";
        $db->sql($sql);
        echo "<div class='alert alert-success'> Settings updated successfully!</div>";
    }
}

if (isset($_POST['customer_app_mode']) || isset($_POST['seller_app_mode']) || isset($_POST['delivery_boy_app_mode'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $data = $fn->get_settings('maintenance_mode', true);
    if (empty($data)) {
        $maintanance = [
            'customer' => $_POST['customer_app_mode'],
            'seller' => $_POST['seller_app_mode'],
            'delivery_boy' => $_POST['delivery_boy_app_mode']
        ];
        $maintanance = json_encode($maintanance);
        $sql = "INSERT INTO `settings`(`variable`, `value`) VALUES ('maintenance_mode','$maintanance')";
        $db->sql($sql);
        echo "<div class='alert alert-success'> Settings created successfully!</div>";
    } else {
        $maintanance = [
            'customer' => $_POST['customer_app_mode'],
            'seller' => $_POST['seller_app_mode'],
            'delivery_boy' => $_POST['delivery_boy_app_mode']
        ];
        $maintanance = json_encode($maintanance);
        $sql = "UPDATE `settings` SET `value`='$maintanance' WHERE `variable`='maintenance_mode'";
        $db->sql($sql);
        echo "<div class='alert alert-success'> Settings updated successfully!</div>";
    }
}


if (isset($_POST['time_slot_config']) && $_POST['time_slot_config'] == 1) {
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $_POST['allowed_days'] = empty($_POST['allowed_days']) ? 1 : $db->escapeString($fn->xss_clean($_POST['allowed_days']));
    if (!$time_slot_config) {
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "INSERT INTO settings (`variable`,`value`) VALUES ('time_slot_config','" . $settings_value . "')";
    } else {
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "UPDATE settings SET value='" . $settings_value . "' WHERE variable='time_slot_config'";
    }
    if ($db->sql($sql)) {
        echo "<p class='alert alert-success'>Saved Successfully!</p>";
    } else {
        echo "<p class='alert alert-danger'>Something went wrong please try again!</p>";
    }
}
if (isset($_POST['add_category_settings']) && $_POST['add_category_settings'] == 1) {
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $sql = "select variable from settings where variable='categories_settings' ";
    $db->sql($sql);
    $res = $db->getResult();
    if (empty($res)) {
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "INSERT INTO settings (`variable`,`value`) VALUES ('categories_settings','" . $settings_value . "')";
    } else {
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "UPDATE settings SET value='" . $settings_value . "' WHERE variable='categories_settings'";
    }
    if ($db->sql($sql)) {
        echo "<p class='alert alert-success'>Saved Successfully!</p>";
    } else {
        echo "<p class='alert alert-danger'>Something went wrong please try again!</p>";
    }
}

if (isset($_POST['add_dr_gold']) && $_POST['add_dr_gold'] == 1) {
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $sql = "select * from settings where variable = 'doctor_brown'";
    $db->sql($sql);
    $res = $db->getResult();
    if (empty($res)) {
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "INSERT INTO settings (`variable`,`value`) VALUES ('doctor_brown','$settings_value ')";
        if ($db->sql($sql)) {
            $response['error'] = false;
            $response['message'] = "Your system is registered and activated successfully!";
        } else {
            $response['error'] = true;
            $response['message'] = "Something went wrong please try again!";
        }
    } else {
        $response['error'] = false;
        $response['message'] = "Your system is already activated!";
    }
    print_r(json_encode($response));
}

if (isset($_POST['front_end_settings']) && $_POST['front_end_settings'] == 1) {
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $res = $res_data = array();
    $loading_old = "";
    $web_logo_old = "";
    $favicon_old = "";
    $screenshots_old = "";
    $google_play_old = "";

    $sql = "select * from settings where variable = 'front_end_settings'";
    $db->sql($sql);
    $res = $db->getResult();
    $res_data = (!empty($res)) ? json_decode($res[0]['value'], true) : array();

    $loading_old = $res_data['loading'];
    $favicon_old = $res_data['favicon'];
    $web_logo_old = $res_data['web_logo'];
    $screenshots_old = $res_data['screenshots'];
    $google_play_old = $res_data['google_play'];

    if (isset($_FILES['favicon']) && !empty($_FILES['favicon']) && $_FILES['favicon']['error'] == 0 && $_FILES['favicon']['size'] > 0) {
        $favicon = $db->escapeString($fn->xss_clean($_FILES['favicon']['name']));
        $extension = pathinfo($_FILES["favicon"]["name"])['extension'];
        $result = $fn->validate_image($_FILES["favicon"]);
        if (!$result) {
            echo "<p class='alert alert-danger'>Image type must jpg, jpeg, gif, or png!</p>";
            return false;
        }

        $target_path = '../dist/img/';
        if (!empty($favicon_old) && $favicon_old != '') {
            unlink($target_path . $favicon_old);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;

        if (!move_uploaded_file($_FILES["favicon"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load favicon!</p>";
            return false;
        }
        $_POST['favicon'] = $filename;
    } else {
        $_POST['favicon'] = $favicon_old;
    }

    if (isset($_FILES['web_logo']) && !empty($_FILES['web_logo']) && $_FILES['web_logo']['error'] == 0 && $_FILES['web_logo']['size'] > 0) {
        $web_logo = $db->escapeString($fn->xss_clean($_FILES['web_logo']['name']));
        $extension = pathinfo($_FILES["web_logo"]["name"])['extension'];
        $result = $fn->validate_image($_FILES["web_logo"]);
        if (!$result) {
            echo "<p class='alert alert-danger'>Image type must jpg, jpeg, gif, or png!</p>";
            return false;
        }

        $target_path = '../dist/img/';
        if (!empty($fweb_logo_old) && $web_logo_old != '') {
            unlink($target_path . $web_logo_old);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;

        if (!move_uploaded_file($_FILES["web_logo"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load Web Logo!</p>";
            return false;
        }
        $_POST['web_logo'] = $filename;
    } else {
        $_POST['web_logo'] = $web_logo_old;
    }

    if (isset($_FILES['loading']) && !empty($_FILES['loading']) && $_FILES['loading']['error'] == 0 && $_FILES['loading']['size'] > 0) {
        $loading = $db->escapeString($fn->xss_clean($_FILES['loading']['name']));
        $extension = pathinfo($_FILES["loading"]["name"])['extension'];
        $result = $fn->validate_image($_FILES["loading"]);
        if (!$result) {
            echo "<p class='alert alert-danger'>Image type must jpg, jpeg, gif, or png!</p>";
            return false;
        }

        $target_path = '../dist/img/';
        if (!empty($loading_old) && $loading_old != '') {
            unlink($target_path . $loading_old);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;

        if (!move_uploaded_file($_FILES["loading"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load loading Image!</p>";
            return false;
        }
        $_POST['loading'] = $filename;
    } else {
        $_POST['loading'] = $loading_old;
    }


    if (isset($_FILES['screenshots']) && !empty($_FILES['screenshots']) && $_FILES['screenshots']['error'] == 0 && $_FILES['screenshots']['size'] > 0) {
        $screenshots = $db->escapeString($fn->xss_clean($_FILES['screenshots']['name']));
        $extension = pathinfo($_FILES["screenshots"]["name"])['extension'];
        $result = $fn->validate_image($_FILES["screenshots"]);
        if (!$result) {
            echo "<p class='alert alert-danger'>Image type must jpg, jpeg, gif, or png!</p>";
            return false;
        }

        $target_path = '../dist/img/';
        if (!empty($screenshots_old) && $screenshots_old != '') {
            unlink($target_path . $screenshots_old);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;

        if (!move_uploaded_file($_FILES["screenshots"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load App Screenshots!</p>";
            return false;
        }
        $_POST['screenshots'] = $filename;
    } else {
        $_POST['screenshots'] = $screenshots_old;
    }


    if (isset($_FILES['google_play']) && !empty($_FILES['google_play']) && $_FILES['google_play']['error'] == 0 && $_FILES['google_play']['size'] > 0) {
        $google_play = $db->escapeString($fn->xss_clean($_FILES['google_play']['name']));
        $extension = pathinfo($_FILES["google_play"]["name"])['extension'];
        $result = $fn->validate_image($_FILES["google_play"]);
        if (!$result) {
            echo "<p class='alert alert-danger'>Image type must jpg, jpeg, gif, or png!</p>";
            return false;
        }

        $target_path = '../dist/img/';
        if (!empty($google_play_old) && $google_play_old != '') {
            unlink($target_path . $google_play_old);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;

        if (!move_uploaded_file($_FILES["google_play"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load Google Play Image!</p>";
            return false;
        }
        $_POST['google_play'] = $filename;
    } else {
        $_POST['google_play'] = $google_play_old;
    }
    $_POST['common_meta_description'] = str_replace("'", "''", $_POST['common_meta_description']);
    $_POST['common_meta_keywords'] = str_replace("'", "''", $_POST['common_meta_keywords']);
    $_POST['show_color_picker_in_website'] = str_replace("'", "''", $_POST['show_color_picker_in_website']);
    if (empty($res)) {
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "INSERT INTO settings (`variable`,`value`) VALUES ('front_end_settings','$settings_value ')";
        if ($db->sql($sql)) {
            echo "<p class='alert alert-success'>Saved Successfully!</p>";
        } else {
            echo "<p class='alert alert-danger'>Something went wrong please try again!</p>";
        }
    } else {
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "UPDATE settings SET value='" . $settings_value . "' WHERE variable='front_end_settings'";
        if ($db->sql($sql)) {
            echo "<p class='alert alert-success'>Saved Successfully!</p>";
        } else {
            echo "<p class='alert alert-danger'>Something went wrong please try again!</p>";
        }
    }
}

if (isset($_POST['add_delivery_boy']) && $_POST['add_delivery_boy'] == 1) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['delivery_boys']['create'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to create delivery boy</label>';
        return false;
    }
    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $address = $db->escapeString($fn->xss_clean($_POST['address']));
    $bonus = $db->escapeString($fn->xss_clean($_POST['bonus']));
    $dob = $db->escapeString($fn->xss_clean($_POST['dob']));
    $bank_name = $db->escapeString($fn->xss_clean($_POST['bank_name']));
    $other_payment_info = (isset($_POST['other_payment_info']) && !empty(trim($_POST['other_payment_info']))) ? $db->escapeString(trim($fn->xss_clean($_POST['other_payment_info']))) : '';
    $account_number = $db->escapeString($fn->xss_clean($_POST['account_number']));
    $account_name = $db->escapeString($fn->xss_clean($_POST['account_name']));
    $ifsc_code = $db->escapeString($fn->xss_clean($_POST['ifsc_code']));
    $password = $db->escapeString($fn->xss_clean($_POST['password']));
    $password = md5($password);
    $sql = 'SELECT id FROM delivery_boys WHERE mobile=' . $mobile;
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);
    // if ($count > 0) {
    //     echo '<label class="alert alert-danger">Mobile Number Already Exists!</label>';
    //     return false;
    // }
    $target_path = '../upload/delivery-boy/';
    if ($_FILES['driving_license']['error'] == 0 && $_FILES['driving_license']['size'] > 0) {
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $extension = pathinfo($_FILES["driving_license"]["name"])['extension'];

        // $mimetype = mime_content_type($_FILES["driving_license"]["tmp_name"]);
        // if (!in_array($mimetype, array('image/jpg', 'image/jpeg', 'image/gif', 'image/png'))) {
        //     echo " <span class='label label-danger'>Driving License image type must jpg, jpeg, gif, or png!</span>";
        //     return false;
        //     exit();
        // }
        $result = $fn->validate_image($_FILES["driving_license"]);
        if (!$result) {
            echo " <span class='label label-danger'>Driving License image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $dr_filename = microtime(true) . '.' . strtolower($extension);
        $dr_full_path = $target_path . "" . $dr_filename;
        if (!move_uploaded_file($_FILES["driving_license"]["tmp_name"], $dr_full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }
    if ($_FILES['national_identity_card']['error'] == 0 && $_FILES['national_identity_card']['size'] > 0) {
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $extension = pathinfo($_FILES["national_identity_card"]["name"])['extension'];

        // $mimetype = mime_content_type($_FILES["national_identity_card"]["tmp_name"]);
        // if (!in_array($mimetype, array('image/jpg', 'image/jpeg', 'image/gif', 'image/png'))) {
        //     echo " <span class='label label-danger'>National Identity Card image type must jpg, jpeg, gif, or png!</span>";
        //     return false;
        //     exit();
        // }
        $result = $fn->validate_image($_FILES["national_identity_card"]);
        if (!$result) {
            echo " <span class='label label-danger'>National Identity Card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $nic_filename = microtime(true) . '.' . strtolower($extension);
        $nic_full_path = $target_path . "" . $nic_filename;
        if (!move_uploaded_file($_FILES["national_identity_card"]["tmp_name"], $nic_full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }
    if (!empty($_POST['pincode_id'])) {
        $pincode_id = $fn->xss_clean_array($_POST['pincode_id']);
        $pincode_id = implode(",", $pincode_id);
    }
    $sql = "INSERT INTO delivery_boys (`name`,`mobile`,`password`,`address`,`bonus`, `driving_license`, `national_identity_card`, `dob`, `bank_account_number`, `bank_name`, `account_name`, `ifsc_code`,`other_payment_information`,`pincode_id`) VALUES ('$name', '$mobile', '$password', '$address','$bonus','$dr_filename', '$nic_filename', '$dob','$account_number','$bank_name','$account_name','$ifsc_code','$other_payment_info','$pincode_id')";
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Delivery Boy Added Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}
if (isset($_POST['update_delivery_boy']) && $_POST['update_delivery_boy'] == 1) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }

    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['delivery_boys']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update delivery boy</label>';
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['delivery_boy_id']));
    if ($id == 104) {
        echo '<label class="alert alert-danger">Sorry you can not update this delivery boy.</label>';
        return false;
    }
    $name = $db->escapeString($fn->xss_clean($_POST['update_name']));
    $password = !empty($_POST['update_password']) ? $db->escapeString($fn->xss_clean($_POST['update_password'])) : '';
    $update_other_payment_info = !empty($_POST['update_other_payment_info']) ? $db->escapeString($fn->xss_clean($_POST['update_other_payment_info'])) : '';
    $address = $db->escapeString($fn->xss_clean($_POST['update_address']));
    $bonus = $db->escapeString($fn->xss_clean($_POST['update_bonus']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $update_dob = $db->escapeString($fn->xss_clean($_POST['update_dob']));
    $update_bank_name = $db->escapeString($fn->xss_clean($_POST['update_bank_name']));
    $update_account_number = $db->escapeString($fn->xss_clean($_POST['update_account_number']));
    $update_account_name = $db->escapeString($fn->xss_clean($_POST['update_account_name']));
    $update_ifsc_code = $db->escapeString($fn->xss_clean($_POST['update_ifsc_code']));
    $password = !empty($password) ? md5($password) : '';
    $dr_image = $nic_image = "";
    if ($_FILES['update_driving_license']['size'] != 0 && $_FILES['update_driving_license']['error'] == 0 && !empty($_FILES['update_driving_license'])) {
        //image isn't empty and update the image
        $dr_image = $db->escapeString($fn->xss_clean($_POST['dr_image1']));
        $extension = pathinfo($_FILES["update_driving_license"]["name"])['extension'];
        // $mimetype = mime_content_type($_FILES["update_driving_license"]["tmp_name"]);
        // if (!in_array($mimetype, array('image/jpg', 'image/jpeg', 'image/gif', 'image/png'))) {
        //     echo " <span class='label label-danger'>Driving License image type must jpg, jpeg, gif, or png!</span>";
        //     return false;
        //     exit();
        // }
        $result = $fn->validate_image($_FILES["update_driving_license"]);
        if (!$result) {
            echo " <span class='label label-danger'>Driving License image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $target_path = '../upload/delivery-boy/';
        $dr_filename = microtime(true) . '.' . strtolower($extension);
        $dr_full_path = $target_path . "" . $dr_filename;
        if (!move_uploaded_file($_FILES["update_driving_license"]["tmp_name"], $dr_full_path)) {
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if (!empty($dr_image)) {
            unlink($target_path . $dr_image);
        }
        $sql = "UPDATE delivery_boys SET `driving_license`='" . $dr_filename . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }
    if ($_FILES['update_national_identity_card']['size'] != 0 && $_FILES['update_national_identity_card']['error'] == 0 && !empty($_FILES['update_national_identity_card'])) {
        //image isn't empty and update the image
        $nic_image = $db->escapeString($fn->xss_clean($_POST['nic_image']));
        $extension = pathinfo($_FILES["update_national_identity_card"]["name"])['extension'];
        // $mimetype = mime_content_type($_FILES["update_national_identity_card"]["tmp_name"]);
        // if (!in_array($mimetype, array('image/jpg', 'image/jpeg', 'image/gif', 'image/png'))) {
        //     echo " <span class='label label-danger'>National Identity Card image type must jpg, jpeg, gif, or png!</span>";
        //     return false;
        //     exit();
        // }
        $result = $fn->validate_image($_FILES["update_national_identity_card"]);
        if (!$result) {
            echo " <span class='label label-danger'>National Identity Card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $target_path = '../upload/delivery-boy/';
        $nic_filename = microtime(true) . '.' . strtolower($extension);
        $nic_full_path = $target_path . "" . $nic_filename;
        if (!move_uploaded_file($_FILES["update_national_identity_card"]["tmp_name"], $nic_full_path)) {
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if (!empty($nic_image)) {
            unlink($target_path . $nic_image);
        }
        $sql = "UPDATE delivery_boys SET `national_identity_card`='" . $nic_filename . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }
    if (!empty($_POST['update_pincode_id'])) {
        $pincode_id = $fn->xss_clean_array($_POST['update_pincode_id']);
        $pincode_id = implode(",", $pincode_id);
        $sql = "UPDATE delivery_boys SET `pincode_id`='" . $pincode_id . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }
    if (!empty($password)) {
        $sql = "Update delivery_boys set `name`='" . $name . "',password='" . $password . "',`address`='" . $address . "',`bonus`='" . $bonus . "',`status`='" . $status . "',`dob`='$update_dob',`bank_account_number`='$update_account_number',`bank_name`='$update_bank_name',`account_name`='$update_account_name',`ifsc_code`='$update_ifsc_code',`other_payment_information`='$update_other_payment_info' where `id`=" . $id;
    } else {
        $sql = "Update delivery_boys set `name`='" . $name . "',`address`='" . $address . "',`bonus`='" . $bonus . "',`status`='" . $status . "',`dob`='$update_dob',`bank_account_number`='$update_account_number',`bank_name`='$update_bank_name',`account_name`='$update_account_name',`ifsc_code`='$update_ifsc_code',`other_payment_information`='$update_other_payment_info'  where `id`=" . $id;
    }
    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Information Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}

if (isset($_GET['delete_delivery_boy']) && $_GET['delete_delivery_boy'] == 1) {
    if ($permissions['delivery_boys']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $target_path = '../upload/delivery-boy/';
    $driving_license = $db->escapeString($fn->xss_clean($_GET['driving_license']));
    $national_identity_card = $db->escapeString($fn->xss_clean($_GET['national_identity_card']));
    if ($id == 104) {
        echo 3;
        return false;
    }
    $sql = "DELETE FROM `delivery_boys` WHERE id=" . $id;
    if ($db->sql($sql)) {
        // delete fund_transfers
        $sql = "DELETE FROM `fund_transfers` WHERE delivery_boy_id=" . $id;
        $db->sql($sql);
        // delete withdrawal requests
        $sql = "DELETE FROM `withdrawal_requests` WHERE `type_id`=" . $id . " AND `type`='delivery_boy'";
        $db->sql($sql);
        // delete delivery boy notification
        $sql = "DELETE FROM `delivery_boy_notifications` WHERE delivery_boy_id=" . $id;
        $db->sql($sql);
        if (!empty($driving_license)) {
            unlink($target_path . $driving_license);
        }
        if (!empty($national_identity_card)) {
            unlink($target_path . $national_identity_card);
        }
        echo 0;
    } else {
        echo 1;
    }
}

if (isset($_POST['update_web_category']) && $_POST['update_web_category'] == 1) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['categories']['update'] == 1) {
        $category_id = $db->escapeString($fn->xss_clean($_POST['web_category_id']));
        $c_image = $db->escapeString($fn->xss_clean($_FILES['c_image']['name']));
        $c_image_temp = $db->escapeString($fn->xss_clean($_FILES['c_image']['tmp_name']));
        $extension = pathinfo($_FILES["c_image"]["name"])['extension'];
        $result = $fn->validate_image($_FILES["c_image"]);
        if (!$result) {
            echo '<p class="alert alert-danger">Image type must jpg, jpeg, gif, or png!</p>';
            return false;
            exit();
        }

        if ($c_image_temp != "") {

            $target_path = '../upload/web-category-image/';
            if (!is_dir($target_path)) {
                mkdir($target_path, 0777, true);
            }
            $nic_filename = microtime(true) . '.' . strtolower($extension);
            $nic_full_path = $target_path . "" . $nic_filename;

            $target_path_db = 'upload/web-category-image/';
            $nic_filename_db = microtime(true) . '.' . strtolower($extension);
            $nic_full_path_db = $target_path_db . "" . $nic_filename_db;
            if (!move_uploaded_file($_FILES["c_image"]["tmp_name"], $nic_full_path)) {
                echo '<p class="alert alert-danger">Can not upload image.</p>';
                return false;
                exit();
            }
            $c_update = "update category set  web_image= '$nic_full_path_db' where id='$category_id'";
        }

        $db->sql($c_update);
        $update_result = $db->getResult();
    }
}

if (isset($_POST['add_social_media']) && $_POST['add_social_media'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to add social media</label>';
        return false;
    }
    $icon = $db->escapeString($fn->xss_clean($_POST['icon']));
    $link = $db->escapeString($fn->xss_clean($_POST['link']));

    $sql = "INSERT INTO social_media (`icon`,`link`) VALUES ('$icon', '$link')";
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Social Media Added Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}
if (isset($_POST['update_social_media']) && $_POST['update_social_media'] == 1) {

    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update social media</label>';
        return false;
    }

    $icon = $db->escapeString($fn->xss_clean($_POST['update_icon']));
    $link = $db->escapeString($fn->xss_clean($_POST['update_link']));
    $id = $db->escapeString($fn->xss_clean($_POST['social_media_id']));
    $sql = "Update social_media set `icon`='" . $icon . "', link='" . $link . "' where `id`=" . $id;

    $db->sql($sql);

    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Information Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_GET['delete_social_media']) && $_GET['delete_social_media'] == 1) {
    if ($permissions['settings']['update'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));


    $sql = "DELETE FROM `social_media` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}

if (isset($_POST['update_payment_request']) && $_POST['update_payment_request'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['payment']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update payment request.</label>";
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['payment_request_id']));
    $remarks = $db->escapeString($fn->xss_clean($_POST['update_remarks']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $sql = "select status from payment_requests where id=" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    if ($res[0]['status'] == 1) {
        echo "<label class='alert alert-danger'>Payment request already approved.</label>";
        return false;
    }
    if ($res[0]['status'] == 2) {
        echo "<label class='alert alert-danger'>Payment request already cancelled.</label>";
        return false;
    }
    if ($status == '2') {
        $sql = "SELECT user_id,amount_requested FROM payment_requests WHERE id=" . $id;
        $db->sql($sql);
        $res = $db->getResult();
        $user_id = $res[0]['user_id'];
        $amount = $res[0]['amount_requested'];

        $sql = "UPDATE users SET balance = balance + $amount WHERE id=" . $user_id;
        $db->sql($sql);
    }
    $sql = "Update payment_requests set `remarks`='" . $remarks . "',`status`='" . $status . "' where `id`=" . $id;
    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_POST['boy_id']) && isset($_POST['transfer_fund']) && !empty($_POST['transfer_fund'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['payment']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update delivery boy.</label>";
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['boy_id']));
    $balance = $db->escapeString($fn->xss_clean($_POST['delivery_boy_balance']));
    if (!is_numeric($_POST['amount'])) {

        echo "<label class='alert alert-danger'>Amount must be number.</label>";
        return false;
    }
    $amount = $db->escapeString($fn->xss_clean($_POST['amount']));

    $message = (!empty($_POST['message'])) ? $db->escapeString($fn->xss_clean($_POST['message'])) : 'Fund Transferred By Admin';
    $bal = $balance - $amount;
    $sql = "Update delivery_boys set `balance`='" . $bal . "' where `id`=" . $id;
    $db->sql($sql);
    $sql = "INSERT INTO `fund_transfers` (`delivery_boy_id`,`amount`,`opening_balance`,`closing_balance`,`status`,`message`) VALUES ('" . $id . "','" . $amount . "','" . $balance . "','" . $bal . "','SUCCESS','" . $message . "')";
    $db->sql($sql);
    echo "<p class='alert alert-success'>Amount Transferred Successfully!</p>";
}

if (isset($_POST['seller_id']) && !empty($_POST['seller_id']) && isset($_POST['transfer_fund_seller']) && !empty($_POST['transfer_fund_seller'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['sellers']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update seller.</label>";
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
    $message = ($_POST['message'] != "") ? $db->escapeString($fn->xss_clean($_POST['message'])) : "Balance $type to seller";

    $balance = $fn->get_wallet_balance($id, 'seller');
    if ($type == 'debit' && $balance <= 0) {
        echo "<label class='alert alert-danger'>Balance should be greater than 0.</label>";
        return false;
    }
    if ($type == 'debit' && $amount > $balance) {
        echo "<label class='alert alert-danger'>Amount should not be greater than balance.</label>";
        return false;
    }
    $new_balance = $type == 'credit' ? $balance + $amount : $balance - $amount;
    $fn->update_wallet_balance($new_balance, $id, 'seller');
    if ($fn->add_wallet_transaction(0, 0, $id, $type, $amount, $message, 'seller_wallet_transactions')) {
        echo "<label class='alert alert-success'>Balance Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}

if (isset($_POST['add_promo_code']) && $_POST['add_promo_code'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['promo_codes']['create'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to create promo code</label>';
        return false;
    }
    $promo_code = $db->escapeString($fn->xss_clean($_POST['promo_code']));
    $message = $db->escapeString($fn->xss_clean($_POST['message']));
    $start_date = $db->escapeString($fn->xss_clean($_POST['start_date']));
    $end_date = $db->escapeString($fn->xss_clean($_POST['end_date']));
    $no_of_users = $db->escapeString($fn->xss_clean($_POST['no_of_users']));
    $minimum_order_amount = $db->escapeString($fn->xss_clean($_POST['minimum_order_amount']));
    $discount = $db->escapeString($fn->xss_clean($_POST['discount']));
    $discount_type = $db->escapeString($fn->xss_clean($_POST['discount_type']));
    $max_discount_amount = $db->escapeString($fn->xss_clean($_POST['max_discount_amount']));
    $repeat_usage = $db->escapeString($fn->xss_clean($_POST['repeat_usage']));
    $no_of_repeat_usage = !empty($_POST['repeat_usage']) ? $db->escapeString($fn->xss_clean($_POST['no_of_repeat_usage'])) : 0;
    $status = $db->escapeString($fn->xss_clean($_POST['status']));

    $sql = "INSERT INTO promo_codes (promo_code,message,start_date,end_date,no_of_users,minimum_order_amount,discount,discount_type,max_discount_amount,repeat_usage,no_of_repeat_usage,status)
                        VALUES('$promo_code', '$message', '$start_date', '$end_date','$no_of_users','$minimum_order_amount','$discount','$discount_type','$max_discount_amount','$repeat_usage','$no_of_repeat_usage','$status')";
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Promo Code Added Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}
if (isset($_POST['update_promo_code']) && $_POST['update_promo_code'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['promo_codes']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update promo code</label>';
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['promo_code_id']));
    $promo_code = $db->escapeString($fn->xss_clean($_POST['update_promo']));
    $message = $db->escapeString($fn->xss_clean($_POST['update_message']));
    $start_date = $db->escapeString($fn->xss_clean($_POST['update_start_date']));
    $end_date = $db->escapeString($fn->xss_clean($_POST['update_end_date']));
    $no_of_users = $db->escapeString($fn->xss_clean($_POST['update_no_of_users']));
    $minimum_order_amount = $db->escapeString($fn->xss_clean($_POST['update_minimum_order_amount']));
    $discount = $db->escapeString($fn->xss_clean($_POST['update_discount']));
    $discount_type = $db->escapeString($fn->xss_clean($_POST['update_discount_type']));
    $max_discount_amount = $db->escapeString($fn->xss_clean($_POST['update_max_discount_amount']));
    $repeat_usage = $db->escapeString($fn->xss_clean($_POST['update_repeat_usage']));
    $no_of_repeat_usage = $repeat_usage == 0 ? '0' : $db->escapeString($fn->xss_clean($_POST['update_no_of_repeat_usage']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));

    $sql = "Update promo_codes set `promo_code`='" . $promo_code . "',`message`='" . $message . "',`start_date`='" . $start_date . "',`end_date`='" . $end_date . "',`no_of_users`='" . $no_of_users . "',`minimum_order_amount`='" . $minimum_order_amount . "',`discount`='" . $discount . "',`discount_type`='" . $discount_type . "',`max_discount_amount`='" . $max_discount_amount . "',`repeat_usage`='" . $repeat_usage . "',`no_of_repeat_usage`='" . $no_of_repeat_usage . "',`status`='" . $status . "' where `id`=" . $id;

    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Promo Code Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_GET['delete_promo_code']) && $_GET['delete_promo_code'] == 1) {
    if ($permissions['promo_codes']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $sql = "DELETE FROM `promo_codes` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}
if (isset($_POST['add_time_slot']) && $_POST['add_time_slot'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['settings']['update'] == 0) {

        echo '<label class="alert alert-danger">You have no permission to add time slot</label>';
        return false;
    }
    $title = $db->escapeString($fn->xss_clean($_POST['title']));
    $from_time = $db->escapeString($fn->xss_clean($_POST['from_time']));
    $to_time = $db->escapeString($fn->xss_clean($_POST['to_time']));
    $last_order_time = $db->escapeString($fn->xss_clean($_POST['last_order_time']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $sql = "INSERT INTO time_slots (title,from_time,to_time,last_order_time,status)
                        VALUES('$title', '$from_time', '$to_time', '$last_order_time','$status')";
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Time Slot Added Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}
if (isset($_POST['update_time_slot']) && $_POST['update_time_slot'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['settings']['update'] == 0) {

        echo '<label class="alert alert-danger">You have no permission to update time slot</label>';
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['time_slot_id']));
    $title = $db->escapeString($fn->xss_clean($_POST['update_title']));
    $from_time = $db->escapeString($fn->xss_clean($_POST['update_from_time']));
    $to_time = $db->escapeString($fn->xss_clean($_POST['update_to_time']));
    $last_order_time = $db->escapeString($fn->xss_clean($_POST['update_last_order_time']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $sql = "Update time_slots set `title`='" . $title . "',`from_time`='" . $from_time . "',`to_time`='" . $to_time . "',`last_order_time`='" . $last_order_time . "',`status`='" . $status . "' where `id`=" . $id;
    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Time Slot Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_GET['delete_time_slot']) && $_GET['delete_time_slot'] == 1) {
    if ($permissions['settings']['update'] == 0) {

        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $sql = "DELETE FROM `time_slots` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}
if (isset($_POST['update_return_request']) && $_POST['update_return_request'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['return_requests']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update return request.</label>";
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['return_request_id']));
    $order_item_id = $db->escapeString($fn->xss_clean($_POST['order_item_id']));
    $order_id = $db->escapeString($fn->xss_clean($_POST['order_id']));
    $remarks = $db->escapeString($fn->xss_clean($_POST['update_remarks']));
    $return_status = $db->escapeString($fn->xss_clean($_POST['status']));
    $shipping_type = $fn->get_settings('local_shipping');

    if ($shipping_type == 1) {
        $res = $fn->get_data($columns = ['status'], 'id=' . $id, 'return_requests');
        if ($res[0]['status'] == 1) {
            echo "<label class='alert alert-danger'>Return request already approved.</label>";
            return false;
        }
        if ($return_status == 1) {
            $res_order = $fn->get_data($columns = ['final_total', 'total', 'user_id', 'payment_method', 'wallet_balance', 'delivery_charge', 'tax_amount', 'status', 'area_id'], 'id=' . $id, 'orders');
            $sql = 'SELECT oi.*,oi.`product_variant_id`,oi.`quantity`,oi.`discounted_price`,oi.`price`,pv.`product_id`,pv.`type`,pv.`stock`,pv.`stock_unit_id`,pv.`measurement`,pv.`measurement_unit_id` FROM `order_items` oi join `product_variant` pv on pv.id = oi.product_variant_id WHERE oi.`id`=' . $order_item_id;
            $db->sql($sql);
            $res_oi = $db->getResult();

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

            $total = ($res_oi[0]['discounted_price'] == 0) ? ($res_oi[0]['price'] * $res_oi[0]['quantity']) + ($res_oi[0]['tax_amount'] * $res_oi[0]['quantity'])  : ($res_oi[0]['discounted_price'] * $res_oi[0]['quantity'])  + ($res_oi[0]['tax_amount'] * $res_oi[0]['quantity']);
            $sql = "select user_id from return_requests where id=" . $id;
            $db->sql($sql);
            $res_user = $db->getResult();
            $user_id = $res_user[0]['user_id'];

            $sql = "select promo_discount,payment_method,wallet_balance from orders where id=" . $order_id;
            $db->sql($sql);
            $res_order = $db->getResult();
            $promo_discount = $res_order[0]['promo_discount'];
            if ($promo_discount > 0) {
                if ($total > $promo_discount) {
                    $total = $total - $promo_discount;
                    $sql = "update orders set promo_discount=0 where id=" . $order_id;
                    $db->sql($sql);
                } else {
                    $new_promo_discount = $promo_discount - $total;
                    $sql = "update orders set promo_discount=" . $new_promo_discount . " where id=" . $order_id;
                    $db->sql($sql);
                    $total = 0;
                }
            }

            // if (strtolower($res_order[0]['payment_method']) != 'cod') {
            /* update user's wallet */
            $total_amount = $res_oi[0]['sub_total'];
            $user_wallet_balance = $fn->get_wallet_balance($user_id, 'users');
            $new_balance = $user_wallet_balance + $total_amount;
            $fn->update_wallet_balance($new_balance, $user_id, 'users');
            $wallet_txn_id = $fn->add_wallet_transaction($id, $order_item_id, $user_id, 'credit', $total_amount, 'Balance credited against item cancellation...', 'wallet_transactions');
            $user_data = $fn->get_data($columns = ['name', 'email', 'mobile', 'country_code'], 'id=' . $user_id, 'users');
            $to = $user_data[0]['email'];
            $mobile = $user_data[0]['mobile'];
            $country_code = $user_data[0]['country_code'];
            $subject = "Your order has been " . ucwords($postStatus);
            $message = "Hello, Dear " . ucwords($user_data[0]['name']) . ", Here is the new update on your order for the order ID : #" . $id . ". Your order has been " . ucwords($postStatus) . ". Please take a note of it.";
            $message .= "Thank you for using our services!You will receive future updates on your order via Email!";
            $fn->send_order_update_notification($user_id, "Your order has been " . ucwords($postStatus), $message, 'order', $id);
            // send_email($to, $subject, $message);
            // } else {
            // if ($res_order[0]['wallet_balance'] != 0) {
            //     if ($res_order[0]['wallet_balance'] >= $res_oi[0]['sub_total']) {
            //         $returnable_amount = $res_oi[0]['sub_total'];
            //         $amount = $res_order[0]['wallet_balance'] - $returnable_amount;
            //         $sql_total = "update orders set wallet_balance=" . $amount . " where id=" . $order_id;
            //         $user_wallet_balance = $fn->get_wallet_balance($user_id, 'users');
            //         $new_balance = ($user_wallet_balance + $returnable_amount);
            //         $fn->update_wallet_balance($new_balance, $user_id, 'users');
            //         $wallet_txn_id = $fn->add_wallet_transaction($id, $order_item_id, $user_id, 'credit', $returnable_amount, 'Balance credited against item cancellation!!', 'wallet_transactions');
            //         $db->sql($sql_total);
            //     } else {
            //         $returnable_amount = $res_order[0]['wallet_balance'];
            //         $user_wallet_balance = $fn->get_wallet_balance($user_id, 'users');
            //         $new_balance = ($user_wallet_balance + $returnable_amount);
            //         $fn->update_wallet_balance($new_balance, $user_id, 'users');
            //         $wallet_txn_id = $fn->add_wallet_transaction($id, $order_item_id, $user_id, 'credit', $returnable_amount, 'Balance credited against item cancellation!!', 'wallet_transactions');
            //         $sql_total = "update orders set wallet_balance=0 where id=" . $order_id;
            //         $db->sql($sql_total);
            //     }
            // }
            // }
            // if ($postStatus == 'returned') {
            $res_order_item = $fn->get_data($columns = ['active_status', 'status'], 'id=' . $order_item_id, 'order_items');
            $status = json_decode($res_order_item[0]['status']);
            $status[] = array('returned', date("d-m-Y h:i:sa"));
            $data = array('status' => $db->escapeString(json_encode($status)), 'active_status' => 'returned');
            $db->update('order_items', $data, 'id=' . $order_item_id);
            // $item_data = array(
            //     'status' => $db->escapeString(json_encode($status)),
            //     'active_status' => 'returned'
            // );
            // }
            /* check for other item status and summery of order */
            $sql = "SELECT id FROM order_items WHERE order_id=" . $order_id;
            $db->sql($sql);
            $total_order = $db->numRows();
            $sql = "SELECT id FROM `order_items` WHERE order_id=" . $order_id . " && (`active_status` LIKE '%cancelled%' OR `active_status` LIKE '%returned%' )";
            $db->sql($sql);
            $returned = $db->numRows();
            if ($returned == $total_order) {
                $sql = "update orders set delivery_charge=0,tax_amount=0,tax_percentage=0,final_total=0,total=0 where id=" . $order_id;
                $db->sql($sql);
            }
        }
        $sql_query = "Update return_requests set `remarks`='" . $remarks . "',`status`='" . $return_status . "' where `id`=" . $id;
        if ($db->sql($sql_query)) {
            echo "<label class='alert alert-success'>Return request updated successfully.</label>";
        } else {
            echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
        }
    }
}
if (isset($_GET['delete_return_request']) && $_GET['delete_return_request'] == 1) {
    if ($permissions['return_requests']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $sql = "DELETE FROM `return_requests` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}
if (isset($_POST['manage_customer_wallet']) && isset($_POST['user_id'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['customers']['read'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to manage wallet balance</label>';
        return false;
    }

    $user_id = $db->escapeString($fn->xss_clean($_POST['user_id']));
    $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $message = !empty(trim($_POST['message'])) ? $db->escapeString(trim($fn->xss_clean($_POST['message']))) : 'Transaction by admin';

    $balance = $fn->get_wallet_balance($user_id, 'users');
    if ($type == 'debit' && $balance <= 0) {
        echo "<label class='alert alert-danger'>Balance should be greater than 0.</label>";
        return false;
    }
    if ($type == 'debit' && $amount > $balance) {
        echo "<label class='alert alert-danger'>Amount should not be greater than balance.</label>";
        return false;
    }
    $new_balance = $type == 'credit' ? $balance + $amount : $balance - $amount;
    $fn->update_wallet_balance($new_balance, $user_id, 'users');
    if ($fn->add_wallet_transaction($order_id = "", 0, $user_id, $type, $amount, $message, 'wallet_transactions')) {
        echo "<label class='alert alert-success'>Balance Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_POST['add_system_user']) && $_POST['add_system_user'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_SESSION['id'];
    $username = $db->escapeString($fn->xss_clean($_POST['username']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    if (empty($email)) {
        echo " <label class='alert alert-danger'>Email required!</label>";
        return false;
    }
    $valid_mail = "/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i";
    if (!preg_match($valid_mail, $email)) {
        echo " <label class='alert alert-danger'>Wrong email format!</label>";
        return false;
    }

    $password = $db->escapeString($fn->xss_clean($_POST['password']));
    $password = md5($password);
    $role = $db->escapeString($fn->xss_clean($_POST['role']));


    $sql = "SELECT id FROM admin WHERE username='" . $username . "'";
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);
    if ($count > 0) {
        echo '<label class="alert alert-danger">Username Already Exists!</label>';
        return false;
    }

    $sql = "SELECT id FROM admin WHERE email='" . $email . "'";
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);
    if ($count > 0) {
        echo '<label class="alert alert-danger">Email Already Exists!</label>';
        return false;
    }
    $permissions['orders'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-order'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-order'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-order'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-order'])));

    $permissions['categories'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-category'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-category'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-category'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-category'])));

    $permissions['sellers'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-seller'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-seller'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-seller'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-seller'])));

    $permissions['subcategories'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-subcategory'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-subcategory'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-subcategory'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-subcategory'])));

    $permissions['products'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-product'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-product'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-product'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-product'])));

    $permissions['products_order'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-products-order'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-products-order'])));

    $permissions['home_sliders'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-home-slider'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-home-slider'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-home-slider'])));

    $permissions['new_offers'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-new-offer'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-new-offer'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-new-offer'])));

    $permissions['promo_codes'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-promo'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-promo'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-promo'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-promo'])));

    $permissions['featured'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-featured'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-featured'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-featured'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-featured'])));

    $permissions['customers'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-customers'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-customers'])));

    $permissions['payment'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-payment'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-payment'])));

    $permissions['return_requests'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-return'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-return'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-return'])));

    $permissions['delivery_boys'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-delivery'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-delivery'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-delivery'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-delivery'])));

    $permissions['notifications'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-notification'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-notification'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-notification'])));

    $permissions['transactions'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-transaction'])));

    $permissions['settings'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-settings'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-settings'])));

    $permissions['locations'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-location'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-location'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-location'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-location'])));

    $permissions['reports'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-report'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-report'])));

    $permissions['faqs'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-faq'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-faq'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-faq'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-faq'])));

    $encoded_permissions = json_encode($permissions);
    $sql = "INSERT INTO admin (username,email,password,role,permissions,created_by)
                        VALUES('$username', '$email', '$password', '$role','$encoded_permissions','$id')";
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">' . $role . ' Added Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}
if (isset($_GET['delete_system_user']) && $_GET['delete_system_user'] == 1) {
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $sql = "DELETE FROM `admin` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}
if (isset($_POST['update_system_user']) && $_POST['update_system_user'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['system_user_id']));
    $permissions['orders'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-order'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-order'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-order'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-order'])));

    $permissions['categories'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-category'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-category'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-category'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-category'])));

    $permissions['sellers'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-seller'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-seller'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-seller'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-seller'])));

    $permissions['subcategories'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-subcategory'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-subcategory'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-subcategory'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-subcategory'])));

    $permissions['products'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-product'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-product'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-product'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-product'])));

    $permissions['products_order'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-products-order'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-products-order'])));

    $permissions['home_sliders'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-home-slider'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-home-slider'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-home-slider'])));

    $permissions['new_offers'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-new-offer'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-new-offer'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-new-offer'])));

    $permissions['promo_codes'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-promo'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-promo'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-promo'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-promo'])));

    $permissions['featured'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-featured'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-featured'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-featured'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-featured'])));

    $permissions['customers'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-customers'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-customers'])));

    $permissions['payment'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-payment'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-payment'])));

    $permissions['return_requests'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-return'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-return'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-return'])));

    $permissions['delivery_boys'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-delivery'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-delivery'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-delivery'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-delivery'])));

    $permissions['notifications'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-notification'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-notification'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-notification'])));

    $permissions['transactions'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-transaction'])));

    $permissions['fund_transfer_delivery_boy'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-fund'])));

    $permissions['settings'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-settings'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-settings'])));

    $permissions['locations'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-location'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-location'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-location'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-location'])));

    $permissions['reports'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-report'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-report'])));

    $permissions['faqs'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-faq'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-faq'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-faq'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-faq'])));

    $permissions = json_encode($permissions);
    $sql = "UPDATE admin SET permissions='" . $permissions . "' WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Updated Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}

if (isset($_POST['update_withdrawal_request']) && $_POST['update_withdrawal_request'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['return_requests']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update withdrawal request.</label>";
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['withdrawal_request_id']));
    $type = $type1 = $db->escapeString($fn->xss_clean($_POST['type']));
    $type_id = $db->escapeString($fn->xss_clean($_POST['type_id']));
    $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $message = ($_POST['message'] != "") ? $db->escapeString($fn->xss_clean($_POST['message'])) : "Balance credited on withdrawal request cancellation";
    $sql = "select status from withdrawal_requests where id=" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    if ($res[0]['status'] == 1) {
        echo "<label class='alert alert-danger'>Withdrawal request already approved.</label>";
        return false;
    }
    if ($res[0]['status'] == 2) {
        echo "<label class='alert alert-danger'>Withdrawal request already cancelled.</label>";
        return false;
    }
    if ($status == 2) {
        if ($type1 == 'user') {
            $balance = $fn->get_wallet_balance($type_id, 'users');
            $new_balance = $balance + $amount;
            $fn->update_wallet_balance($new_balance, $type_id, 'users');
            $fn->add_wallet_transaction($order_id = "", 0, $type_id, 'credit', $amount, 'Balance credited on withdrawal request cancellation.', 'wallet_transactions');
        }
        if ($type1 == 'delivery_boys') {
            $balance = $fn->get_balance($type_id);
            $new_balance = $balance + $amount;
            $fn->update_delivery_boy_wallet_balance($new_balance, $type_id);
            $sql = "INSERT INTO `fund_transfers` (`delivery_boy_id`,`type`,`amount`,`opening_balance`,`closing_balance`,`status`,`message`) VALUES ('" . $type_id . "','credit','" . $amount . "','" . $balance . "','" . $new_balance . "','SUCCESS','Balance credited on withdrawal request cancellation.')";
            $db->sql($sql);
        }
        if ($type1 == 'seller') {
            $balance = $fn->get_wallet_balance($type_id, 'seller');
            $new_balance = $balance + $amount;
            $fn->update_wallet_balance($new_balance, $type_id, 'seller');
            $fn->add_wallet_transaction($order_id = "", 0, $type_id, 'credit', $amount, $message, 'seller_wallet_transactions');
        }
    }
    $sql_query = "Update withdrawal_requests set `status`='" . $status . "' where `id`=" . $id;
    if ($db->sql($sql_query)) {
        echo "<label class='alert alert-success'>Withdrawal request updated successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}

if (isset($_GET['delete_withdrawal_request']) && $_GET['delete_withdrawal_request'] == 1) {
    if ($permissions['return_requests']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $sql = "DELETE FROM `withdrawal_requests` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}

// upload bulk product - upload products in bulk using  a CSV file
if (isset($_POST['bulk_upload']) && $_POST['bulk_upload'] == 1) {


    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['create'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to upload products.</label>";
        return false;
    }
    $count = 0;
    $count1 = 0;
    $error = false;
    $filename = $_FILES["upload_file"]["tmp_name"];

    $result = $fn->validate_image($_FILES["upload_file"], false);
    if (!$result) {
        $error = true;
    }
    $allowed_status = array("received", "processed", "shipped");
    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {

                $emapData[0] = trim($db->escapeString($emapData[0])); // product name
                $emapData[1] = trim($db->escapeString($emapData[1])); // category id                
                $emapData[3] = trim($db->escapeString($emapData[3])); // indicator
                $emapData[4] = trim($db->escapeString($emapData[4])); // manufacturer
                $emapData[5] = trim($db->escapeString($emapData[5])); // made in
                $emapData[6] = trim($db->escapeString($emapData[6])); // return status
                $emapData[7] = trim($db->escapeString($emapData[7])); // cancel status
                $emapData[8] = trim($db->escapeString($emapData[8])); // till status
                $emapData[9] = trim($db->escapeString($emapData[9])); // description
                $emapData[10] = trim($db->escapeString($emapData[10])); // image
                $emapData[11] = trim($db->escapeString($emapData[11])); // seller_id
                $emapData[12] = trim($db->escapeString($emapData[12])); // is_approved
                $emapData[13] = trim($db->escapeString($emapData[13])); // deliverable_type
                $emapData[14] = trim($db->escapeString($emapData[14])); // pincodes
                $emapData[15] = trim($db->escapeString($emapData[15])); // return_days
                $emapData[16] = trim($db->escapeString($emapData[16])); // tax_id
                $emapData[17] = trim($db->escapeString($emapData[17])); // standard_shipping
                $emapData[18] = trim($db->escapeString($emapData[18])); // pickup_location

                $emapData[19] = trim($db->escapeString($emapData[19])); // type
                $emapData[20] = trim($db->escapeString($emapData[20])); // Measurement
                $emapData[21] = trim($db->escapeString($emapData[21])); // Measurement Unit ID
                $emapData[22] = trim($db->escapeString($emapData[22])); // Price
                $emapData[23] = trim($db->escapeString($emapData[23])); // Discounted Price
                $emapData[24] = trim($db->escapeString($emapData[24])); // Serve For
                $emapData[25] = trim($db->escapeString($emapData[25])); // Stock
                $emapData[26] = trim($db->escapeString($emapData[26])); // Stock Unit ID
                $emapData[27] = trim($db->escapeString($emapData[27])); // weight
                $emapData[28] = trim($db->escapeString($emapData[28])); // height
                $emapData[29] = trim($db->escapeString($emapData[29])); // breadth
                $emapData[30] = trim($db->escapeString($emapData[30])); // length

                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">Product Name  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[1])) {
                    echo '<p class="alert alert-danger">Category ID  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[11])) {
                    echo '<p class="alert alert-danger">Seller ID  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[1])) {
                    $seller = $fn->get_data($columns = ['name'], "id=" . $emapData[11], 'seller');
                    if (empty($seller)) {
                        echo '<p class="alert alert-danger">Seller is not exist check the seller_id at row - ' . $count . '</div>';
                        return false;
                    }
                    $sql = "SELECT categories FROM seller WHERE id = " . $emapData[11];
                    $db->sql($sql);
                    $res = $db->getResult();

                    if (strpos($res[0]['categories'], $emapData[1]) === false) {
                        echo '<p class="alert alert-danger">Category ID  is not assign to seller at row - ' . $count . '</div>';
                        return false;
                    }
                }

                if (!empty($emapData[6]) && $emapData[6] != 1) {
                    echo '<p class="alert alert-danger">Is Returnable is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[7]) && $emapData[7] != 1) {
                    echo '<p class="alert alert-danger">Is cancel-able is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[7]) && $emapData[7] == 1 && (empty($emapData[8]) || !in_array($emapData[8], $allowed_status))) {
                    echo '<p class="alert alert-danger">Till status is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[7]) && !(empty($emapData[8]))) {
                    echo '<p class="alert alert-danger">Till status is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[9])) {
                    echo '<p class="alert alert-danger">Description  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[10])) {
                    echo '<p class="alert alert-danger">Image  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[16])) {
                    $tax = $fn->get_data(['id'], "id = " . $emapData[16], 'taxes');
                    if (empty($tax)) {
                        echo '<p class="alert alert-danger">Tax ID  is invalid at row - ' . $count . '</div>';
                        return false;
                    }
                }

                $index1 = 19;
                $total_variants = 0;
                for ($j = 0; $j < 50; $j++) {
                    if (!empty($emapData[$index1])) {
                        $total_variants++;
                    }
                    $index1 = $index1 + 12;
                }

                if ($total_variants == 0) {
                    echo '<p class="alert alert-danger">Atleast one variant required at row - ' . $count . '</div>';
                    return false;
                }

                $sql = "SELECT id FROM unit";
                $db->sql($sql);
                $ids = $db->getResult();

                $index1 = 19;
                for ($z = 0; $z < $total_variants; $z++) {
                    if (empty($emapData[$index1]) || (strtolower($emapData[$index1]) != 'packet' && strtolower($emapData[$index1] != 'loose'))) {
                        echo '<p class="alert alert-danger">Type  is empty or invalid at row - ' . $count . ' Index - ' . $index1 . '</div>';
                        return false;
                    }
                    $index1 = $index1 + 1;

                    if (empty($emapData[$index1]) || !is_numeric($emapData[$index1])) {
                        echo '<p class="alert alert-danger">Measurement  is empty or invalid at row - ' . $count . ' Index - ' . $index1 . '</div>';
                        return false;
                    }
                    $index1 = $index1 + 1;

                    $invalid_measurement_unit = 1;
                    foreach ($ids as $id) {
                        if ($emapData[$index1] == $id['id']) {
                            $invalid_measurement_unit = 0;
                        }
                    }

                    $index1 = $index1 + 1;
                    if (empty($emapData[$index1]) || $emapData[$index1] <= $emapData[$index1 + 1]) {
                        echo '<p class="alert alert-danger">Price is empty or invalid at row - ' . $count . ' Index - ' . $index1 . '</div>';
                        return false;
                    }
                    $index1 = $index1 + 2;

                    if (empty($emapData[$index1]) || ($emapData[$index1] != 'Available' && $emapData[$index1] != 'Sold Out')) {
                        echo '<p class="alert alert-danger">Serve For  is empty or invalid at row - ' . $count . ' Index - ' . $index1 . '</div>';
                        return false;
                    }
                    $index1 = $index1 + 1;

                    if ($emapData[$index1] == '' || !is_numeric($emapData[$index1])) {
                        echo '<p class="alert alert-danger">Stock  is empty or invalid at row - ' . $count . ' Index - ' . $index1 . '</div>';
                        return false;
                    }
                    $index1 = $index1 + 1;

                    $invalid_stock_unit = 1;
                    foreach ($ids as $id) {
                        if ($emapData[$index1] == $id['id']) {
                            $invalid_stock_unit = 0;
                        }
                    }

                    if (empty($emapData[$index1]) || $invalid_stock_unit == 1) {
                        echo '<p class="alert alert-danger">Stock Unit ID is empty or invalid at row - ' . $count . ' Index - ' . $index1 . '</div>';
                        return false;
                    }
                    $index1 = $index1 + 5;
                }
            }
            $count++;
        }

        fclose($file);
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $pr_approval = $fn->get_data(['require_products_approval'], "id = " . $emapData[12], 'seller');
                $is_approved = isset($pr_approval[0]['require_products_approval']) && $pr_approval[0]['require_products_approval'] == 0 ? 1 : 0;
                $emapData[0] = trim($db->escapeString($emapData[0])); // product name
                $emapData[1] = trim($db->escapeString($emapData[1])); // category id
                $emapData[2] = !empty($emapData[2]) ? trim($db->escapeString($emapData[2])) : 0; // subcategory id
                $emapData[3] = trim($db->escapeString($emapData[3])); // indicator
                $emapData[4] = trim($db->escapeString($emapData[4])); // manufacturer
                $emapData[5] = trim($db->escapeString($emapData[5])); // made in
                $emapData[6] = !empty($emapData[6]) ? trim($db->escapeString($emapData[6])) : 0; // return status
                $emapData[7] = !empty($emapData[7]) ? trim($db->escapeString($emapData[7])) : 0; // cancel status
                $emapData[8] = trim($db->escapeString($emapData[8])); // till status
                $emapData[9] = trim($db->escapeString($emapData[9])); // description
                $emapData[10] = trim($db->escapeString($emapData[10])); // image
                $emapData[11] = trim($db->escapeString($emapData[11])); // seller_id
                $emapData[12] = (!empty($emapData[12]) && $emapData[12] != "") ? trim($db->escapeString($emapData[12])) : 0; // is_approved
                $emapData[13] = (!empty($emapData[13]) && $emapData[13] != "") ? trim($db->escapeString($emapData[13])) : ""; // deliverable_type
                $emapData[14] =  (!empty($emapData[14]) && $emapData[14] != "") ? trim($db->escapeString($emapData[14])) : ""; // pincodes
                $emapData[15] =  (!empty($emapData[15]) && $emapData[15] != "") ? trim($db->escapeString($emapData[15])) : "0"; // return_days
                $emapData[16] =  (!empty($emapData[16]) && $emapData[16] != "") ? trim($db->escapeString($emapData[16])) : "0"; // tax_id
                $emapData[17] =  (!empty($emapData[17]) && $emapData[17] != "") ? trim($db->escapeString($emapData[17])) : "0"; // standard_shipping 
                $emapData[18] =  (!empty($emapData[18]) && $emapData[18] != "") ? trim($db->escapeString($emapData[18])) : "0"; // pickup_location

                $emapData[19] = trim($db->escapeString($emapData[19])); // type
                $emapData[20] = trim($db->escapeString($emapData[20])); // Measurement
                $emapData[21] = trim($db->escapeString($emapData[21])); // Measurement Unit ID
                $emapData[22] = trim($db->escapeString($emapData[22])); // Price
                $emapData[23] = trim($db->escapeString($emapData[23])); // Discounted Price
                $emapData[24] = trim($db->escapeString($emapData[24])); // Serve For
                $emapData[25] = trim($db->escapeString($emapData[25])); // Stock
                $emapData[26] = trim($db->escapeString($emapData[26])); // Stock Unit ID
                $emapData[27] = trim($db->escapeString($emapData[27])); // weight
                $emapData[28] = trim($db->escapeString($emapData[28])); // height
                $emapData[29] = trim($db->escapeString($emapData[29])); // breadth
                $emapData[30] = trim($db->escapeString($emapData[30])); // length

                $slug = $function->slugify($emapData[0]);
                $data = array(
                    'name' => $emapData[0],
                    'slug' => $slug,
                    'category_id' => $emapData[1],
                    'subcategory_id' => $emapData[2],
                    'indicator' => $emapData[3],
                    'manufacturer' => $emapData[4],
                    'made_in' => $emapData[5],
                    'return_status' => $emapData[6],
                    'cancelable_status' => $emapData[7],
                    'till_status' => $emapData[8],
                    'description' => $emapData[9],
                    'image' => $emapData[10],
                    'seller_id' => $emapData[11],
                    'is_approved' => $is_approved,
                    'type' => $emapData[13],
                    'pincodes' => $emapData[14],
                    'return_days' => $emapData[15],
                    'tax_id' => $emapData[16],
                    'standard_shipping' => $emapData[17],
                    'pickup_location' => $emapData[18],
                );
                print_r($data);
                return false;
                $db->insert('products', $data);
                $res = $db->getResult();

                $index1 = 19;
                $total_variants = 0;

                for ($j = 0; $j < 50; $j++) {
                    if (!empty($emapData[$index1])) {
                        $total_variants++;
                    }
                    $index1 = $index1 + 12;
                }
                $index = 19;

                for ($i = 0; $i < $total_variants; $i++) {
                    $variant_data[$i]['product_id'] = $res[0];
                    $variant_data[$i]['type'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['measurement'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['measurement_unit_id'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['price'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['discounted_price'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['serve_for'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['stock'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['stock_unit_id'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['weight'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['height'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['breadth'] = $emapData[$index];
                    $index++;
                    $variant_data[$i]['length'] = $emapData[$index];
                    $index++;
                    $db->insert('product_variant', $variant_data[$i]);
                    $res1 = $db->getResult();
                }
            }

            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}




// upload bulk product - upload products in bulk using  a CSV file
if (isset($_POST['bulk_update']) && $_POST['bulk_update'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'products')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update products.</label>";
        return false;
    }
    $count = 0;
    $count1 = 0;
    $filename = $_FILES["upload_file"]["tmp_name"];
    $error = false;
    // $mimetype = mime_content_type($_FILES["upload_file"]["tmp_name"]);
    // if (!in_array($mimetype, array('text/plain'))) {
    //     $error = true;
    // }
    $result = $fn->validate_image($_FILES["upload_file"], false);
    if (!$result) {
        $error = true;
    }
    $allowed_status = array("received", "processed", "shipped");
    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // product ID
                $emapData[1] = trim($db->escapeString($emapData[1])); // product name
                $emapData[2] = trim($db->escapeString($emapData[2])); // category id
                $emapData[3] = trim($db->escapeString($emapData[3])); // subcategory id
                $emapData[4] = trim($db->escapeString($emapData[4])); // indicator
                $emapData[5] = trim($db->escapeString($emapData[5])); // manufacturer
                $emapData[6] = trim($db->escapeString($emapData[6])); // made in
                $emapData[7] = trim($db->escapeString($emapData[7])); // return status
                $emapData[8] = trim($db->escapeString($emapData[8])); // cancel status
                $emapData[9] = trim($db->escapeString($emapData[9])); // till status
                $emapData[10] = trim($db->escapeString($emapData[10])); // description
                $emapData[11] = trim($db->escapeString($emapData[11])); // image
                // return false;
                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">Product ID  is empty at row - ' . $count . '</div>';
                    return false;
                }
                $sql = "SELECT * FROM products WHERE id=" . $emapData[0];
                $db->sql($sql);
                $result = $db->getResult();
                if (empty($result)) {
                    echo '<p class="alert alert-danger">Product ID  is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[7]) && $emapData[7] != 1) {
                    echo '<p class="alert alert-danger">Is Returnable is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[8]) && $emapData[8] != 1) {
                    echo '<p class="alert alert-danger">Is cancel-able is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[8]) && $emapData[8] == 1 && (empty($emapData[9]) || !in_array($emapData[9], $allowed_status))) {
                    echo '<p class="alert alert-danger">Till status is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[8]) && !(empty($emapData[9]))) {
                    echo '<p class="alert alert-danger">Till status is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[8]) && (empty($emapData[9]))) {
                    echo '<p class="alert alert-danger">Till status is invalid or empty at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[11])) {
                    echo '<p class="alert alert-danger">Image  is empty at row - ' . $count . '</div>';
                    return false;
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            print_r($emapData);
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // product ID
                $sql = "SELECT * FROM products WHERE id=" . $emapData[0];
                $db->sql($sql);
                $result = $db->getResult();
                $emapData[1] = !empty($emapData[1]) ? trim($db->escapeString($emapData[1])) : $result[0]['name']; // product name
                $emapData[2] = !empty($emapData[2]) ? trim($db->escapeString($emapData[2])) : $result[0]['category_id']; // category id
                $emapData[3] = !empty($emapData[3]) ? trim($db->escapeString($emapData[3])) : $result[0]['subcategory_id']; // subcategory id
                $emapData[4] = !empty($emapData[4]) ? trim($db->escapeString($emapData[4])) : $result[0]['indicator']; // indicator
                $emapData[5] = !empty($emapData[5]) ? trim($db->escapeString($emapData[5])) : $result[0]['manufacturer']; // manufacturer
                $emapData[6] = !empty($emapData[6]) ? trim($db->escapeString($emapData[6])) : $result[0]['made_in']; // made in
                $emapData[7] = !empty($emapData[7]) ? trim($db->escapeString($emapData[7])) : $result[0]['return_status']; // return status
                $emapData[8] = trim($db->escapeString($emapData[8])); // cancel status
                $emapData[9] = !empty($emapData[8]) ? trim($db->escapeString($emapData[9])) : ''; // till status
                $emapData[10] = !empty($emapData[10]) ? trim($db->escapeString($emapData[10])) : $result[0]['description']; // description
                $emapData[11] = !empty($emapData[11]) ? trim($db->escapeString($emapData[11])) : $result[0]['image']; // image

                $slug = !empty($emapData[1]) ? $function->slugify($emapData[1]) : $result[0]['slug'];
                $sql = "UPDATE products SET `name`='" . $emapData[1] . "',`slug`='" . $slug . "',`category_id`='" . $emapData[2] . "',`subcategory_id`='" . $emapData[3] . "',`indicator`='" . $emapData[4] . "',`manufacturer`='" . $emapData[5] . "',`made_in`='" . $emapData[6] . "',`return_status`='" . $emapData[7] . "',`cancelable_status`='" . $emapData[8] . "',`till_status`='" . $emapData[9] . "',`description`='" . $emapData[10] . "',`image`='" . $emapData[11] . "' WHERE id=" . $emapData[0];
                $db->sql($sql);
            }

            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}
// upload bulk product variants- upload product variants in bulk using  a CSV file
if (isset($_POST['bulk_upload']) && $_POST['bulk_upload'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'variants')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['create'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to upload products.</label>";
        return false;
    }
    $count = 0;
    $count1 = 0;
    $filename = $_FILES["upload_file"]["tmp_name"];
    $error = false;
    // $mimetype = mime_content_type($_FILES["upload_file"]["tmp_name"]);
    // if (!in_array($mimetype, array('text/plain'))) {
    //     $error = true;
    // }
    $result = $fn->validate_image($_FILES["upload_file"], false);
    if (!$result) {
        $error = true;
    }
    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        $emptydata = false;
        $invalid_price = false;
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // type
                $emapData[1] = trim($db->escapeString($emapData[1])); // measurement
                $emapData[2] = trim($db->escapeString($emapData[2])); // measurement unit id
                $emapData[3] = trim($db->escapeString($emapData[3])); // price
                $emapData[4] = trim($db->escapeString($emapData[4])); // discounted price
                $emapData[5] = trim($db->escapeString($emapData[5])); // serve for
                $emapData[6] = trim($db->escapeString($emapData[6])); // stock
                $emapData[7] = trim($db->escapeString($emapData[7])); // stock unit id
                $emapData[8] = trim($db->escapeString($emapData[8])); // product id

                if (empty($emapData[0]) || ($emapData[0] != 'packet' && $emapData[0] != 'loose')) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Type  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[1])) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Measurement  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                $sql = "SELECT id FROM unit";
                $db->sql($sql);
                $ids = $db->getResult();
                $invalid_measurement_unit = 1;
                foreach ($ids as $id) {
                    if ($emapData[2] == $id['id']) {
                        $invalid_measurement_unit = 0;
                    }
                }
                if (empty($emapData[2]) || $invalid_measurement_unit == 1) {
                    echo '<p class="alert alert-danger">Measurement Unit ID is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[3]) || $emapData[3] <= $emapData[4]) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Price is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[5]) || ($emapData[5] != 'Available' && $emapData[5] != 'Sold Out')) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Serve For  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                $invalid_stock_unit = 0;
                foreach ($ids as $id) {
                    if ($emapData[7] == $id['id']) {
                        $invalid_stock_unit = 0;
                    }
                }
                if (empty($emapData[7]) || $invalid_stock_unit == 1) {
                    echo '<p class="alert alert-danger">Stock Unit ID is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[8])) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Product ID is empty at row - ' . $count . '</div>';
                    return false;
                }
                $sql = "SELECT id FROM products WHERE id=" . $emapData[8];
                $db->sql($sql);
                $result = $db->getResult();
                if (empty($result)) {
                    echo '<p class="alert alert-danger">Product ID  is invalid at row - ' . $count . '</div>';
                    return false;
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // type
                $emapData[1] = trim($db->escapeString($emapData[1])); // measurement
                $emapData[2] = trim($db->escapeString($emapData[2])); // measurement unit id
                $emapData[3] = trim($db->escapeString($emapData[3])); // price
                $emapData[4] = trim($db->escapeString($emapData[4])); // discounted price
                $emapData[5] = trim($db->escapeString($emapData[5])); // serve for
                $emapData[6] = trim($db->escapeString($emapData[6])); // stock
                $emapData[7] = trim($db->escapeString($emapData[7])); // stock unit id
                $emapData[8] = trim($db->escapeString($emapData[8])); // product id
                $sql = "INSERT INTO product_variant (`product_id`,`type`,`measurement`,`measurement_unit_id`,`price`,`discounted_price`,`serve_for`,`stock`,`stock_unit_id`) VALUES ('" . $emapData[8] . "','" . $emapData[0] . "','" . $emapData[1] . "','" . $emapData[2] . "','" . $emapData[3] . "','" . $emapData[4] . "','" . $emapData[5] . "','" . $emapData[6] . "','" . $emapData[7] . "')";
                $db->sql($sql);
            }

            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}

if (isset($_POST['bulk_update']) && $_POST['bulk_update'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'variants')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update products.</label>";
        return false;
    }
    $count = 0;

    $count1 = 0;
    $filename = $_FILES["upload_file"]["tmp_name"];
    $error = false;
    // $mimetype = mime_content_type($_FILES["upload_file"]["tmp_name"]);
    // if (!in_array($mimetype, array('text/plain'))) {
    //     $error = true;
    // }
    $result = $fn->validate_image($_FILES["upload_file"], false);
    if (!$result) {
        $error = true;
    }
    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        $emptydata = false;
        $invalid_price = false;
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // ID
                $emapData[1] = trim($db->escapeString($emapData[1])); // type
                $emapData[2] = trim($db->escapeString($emapData[2])); // measurement
                $emapData[3] = trim($db->escapeString($emapData[3])); // measurement unit id
                $emapData[4] = trim($db->escapeString($emapData[4])); // price
                $emapData[5] = trim($db->escapeString($emapData[5])); // discounted price
                $emapData[6] = trim($db->escapeString($emapData[6])); // serve for
                $emapData[7] = trim($db->escapeString($emapData[7])); // stock
                $emapData[8] = trim($db->escapeString($emapData[8])); // stock unit id
                $emapData[9] = trim($db->escapeString($emapData[9])); // product id

                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">Variant ID  is empty at row - ' . $count . '</div>';
                    return false;
                }
                $sql = "SELECT * FROM product_variant WHERE id=" . $emapData[0];
                $db->sql($sql);
                $result = $db->getResult();
                if (empty($result)) {
                    echo '<p class="alert alert-danger">Variant ID  is invalid at row - ' . $count . '</div>';
                    return false;
                }


                if (!empty($emapData[1]) && $emapData[1] != 'packet' && $emapData[1] != 'loose') {
                    echo '<p class="alert alert-danger">Type  is invalid at row - ' . $count . '</div>';
                    return false;
                }

                $sql = "SELECT id FROM unit";
                $db->sql($sql);
                $ids = $db->getResult();
                if (!empty($emapData[3])) {
                    $invalid_measurement_unit = 1;
                    foreach ($ids as $id) {
                        if ($emapData[3] == $id['id']) {
                            $invalid_measurement_unit = 0;
                        }
                    }
                    if ($invalid_measurement_unit == 1) {
                        echo '<p class="alert alert-danger">Measurement Unit ID is invalid at row - ' . $count . '</div>';
                        return false;
                    }
                }

                if ($emapData[4] <= $emapData[5]) {
                    echo '<p class="alert alert-danger">Price is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[6]) && $emapData[6] != 'Available' && $emapData[6] != 'Sold Out') {
                    echo '<p class="alert alert-danger">Serve For  is invalid at row - ' . $count . '</div>';
                    return false;
                }

                if (!empty($emapData[8])) {
                    $invalid_stock_unit = 1;
                    foreach ($ids as $id) {
                        if ($emapData[8] == $id['id']) {
                            $invalid_stock_unit = 0;
                        }
                    }
                    if ($invalid_stock_unit == 1) {
                        echo '<p class="alert alert-danger">Stock Unit ID is invalid at row - ' . $count . '</div>';
                        return false;
                    }
                }
                if (!empty($emapData[9])) {
                    $sql = "SELECT id FROM products WHERE id=" . $emapData[9];
                    $db->sql($sql);
                    $result = $db->getResult();
                    if (empty($result)) {
                        echo '<p class="alert alert-danger">Product ID  is invalid at row - ' . $count . '</div>';
                        return false;
                    }
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // ID
                $sql = "SELECT * FROM product_variant WHERE id=" . $emapData[0];
                $db->sql($sql);
                $result = $db->getResult();
                $emapData[1] = !empty($emapData[1]) ? trim($db->escapeString($emapData[1])) : $result[0]['type']; // type
                $emapData[2] = !empty($emapData[2]) ? trim($db->escapeString($emapData[2])) : $result[0]['measurement']; // measurement
                $emapData[3] = !empty($emapData[3]) ? trim($db->escapeString($emapData[3])) : $result[0]['measurement_unit_id']; // measurement unit id
                $emapData[4] = !empty($emapData[4]) ? trim($db->escapeString($emapData[4])) : $result[0]['price']; // price
                $emapData[5] = $result[0]['discounted_price'] == 0 && !empty($emapData[5]) ? trim($db->escapeString($emapData[5])) : trim($db->escapeString($emapData[5])); // discounted price
                $emapData[6] = !empty($emapData[6]) ? trim($db->escapeString($emapData[6])) : $result[0]['serve_for']; // serve for
                $emapData[7] = !empty($emapData[7]) ? trim($db->escapeString($emapData[7])) : $result[0]['stock']; // stock
                $emapData[8] = !empty($emapData[8]) ? trim($db->escapeString($emapData[8])) : $result[0]['stock_unit_id']; // stock unit id
                $emapData[9] = !empty($emapData[9]) ? trim($db->escapeString($emapData[9])) : $result[0]['product_id']; // product id
                $sql = "UPDATE product_variant SET `product_id`='" . $emapData[9] . "',`type`='" . $emapData[1] . "',`measurement`='" . $emapData[2] . "',`measurement_unit_id`='" . $emapData[3] . "',`price`='" . $emapData[4] . "',`discounted_price`='" . $emapData[5] . "',`serve_for`='" . $emapData[6] . "',`stock`='" . $emapData[7] . "',`stock_unit_id`='" . $emapData[8] . "' WHERE id=" . $emapData[0];
                $db->sql($sql);
            }

            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}

if (isset($_GET['product_status']) && !empty($_GET['product_status']) && isset($_GET['type']) && !empty($_GET['type'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update products.</label>";
        return false;
    }
    $type = $db->escapeString($fn->xss_clean($_GET['type']));
    $product_id = $db->escapeString($fn->xss_clean($_GET['id']));

    if ($type == 'deactive') {
        $sql = "UPDATE `products` SET `status`= 0 WHERE id = $product_id";
        if ($db->sql($sql)) {
            echo 1;
        } else {
            echo 0;
        }
    }
    if ($type == 'active') {
        $sql = "UPDATE `products` SET `status`= 1 WHERE id = $product_id";
        if ($db->sql($sql)) {
            echo 1;
        } else {
            echo 0;
        }
    }
}

if (isset($_POST['delete_media']) && !empty($_POST['id']) && $_POST['delete_media'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id     = $db->escapeString($fn->xss_clean($_POST['id']));
    $image  = $db->escapeString($fn->xss_clean($_POST['image']));
    // $id = $db->escapeString($_POST['id']);
    if (!empty($image))
        unlink('../' . $image);

    $sql = "DELETE FROM `media` WHERE `id`='" . $id . "'";

    if ($db->sql($sql)) {
        echo 1;
        echo "<p class='alert alert-success'>Media Deleted successfully!</p><br>";
    } else {
        echo 0;
        echo "<p class='alert alert-success'>Media is not Deleted!</p><br>";
    }
}

if (isset($_POST['add_seller']) && $_POST['add_seller'] == 1) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['sellers']['create'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to create seller</label>';
        return false;
    }

    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $store_name = $db->escapeString($fn->xss_clean($_POST['store_name']));
    $slug = $function->slugify($db->escapeString($fn->xss_clean($_POST['store_name'])), 'seller');
    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    $commission = (isset($_POST['commission']) && $_POST['commission'] != "") ? $db->escapeString($fn->xss_clean($_POST['commission'])) : "0";

    $pan_number = $db->escapeString($fn->xss_clean($_POST['pan_number']));
    $tax_number = $db->escapeString($fn->xss_clean($_POST['tax_number']));
    $tax_name = $db->escapeString($fn->xss_clean($_POST['tax_name']));
    $status = (isset($_POST['status']) && $_POST['status'] != "") ? $db->escapeString($fn->xss_clean($_POST['status'])) : "2";
    $customer_privacy = (isset($_POST['customer_privacy']) && $_POST['customer_privacy'] != "") ? $db->escapeString($fn->xss_clean($_POST['customer_privacy'])) : "0";
    $store_url = (isset($_POST['store_url']) && $_POST['store_url'] != "") ? $db->escapeString($fn->xss_clean($_POST['store_url'])) : "";
    $store_description = (isset($_POST['description']) && $_POST['description'] != "") ? $db->escapeString($fn->xss_clean($_POST['description'])) : "";
    $street = (isset($_POST['street']) && $_POST['street'] != "") ? $db->escapeString($fn->xss_clean($_POST['street'])) : "";
    $pincode_id = (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "0";
    $city_id = (isset($_POST['city_id']) && $_POST['city_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['city_id'])) : "0";
    $state = (isset($_POST['state']) && $_POST['state'] != "") ? $db->escapeString($fn->xss_clean($_POST['state'])) : "";
    $account_number = (isset($_POST['account_number']) && $_POST['account_number'] != "") ? $db->escapeString($fn->xss_clean($_POST['account_number'])) : "";
    $bank_ifsc_code = (isset($_POST['ifsc_code']) && $_POST['ifsc_code'] != "") ? $db->escapeString($fn->xss_clean($_POST['ifsc_code'])) : "";
    $account_name = (isset($_POST['account_name']) && $_POST['account_name'] != "") ? $db->escapeString($fn->xss_clean($_POST['account_name'])) : "";
    $bank_name = (isset($_POST['bank_name']) && $_POST['bank_name'] != "") ? $db->escapeString($fn->xss_clean($_POST['bank_name'])) : "";
    $latitude = (isset($_POST['latitude']) && $_POST['latitude'] != "") ? $db->escapeString($fn->xss_clean($_POST['latitude'])) : "0";
    $longitude = (isset($_POST['longitude']) && $_POST['longitude'] != "") ? $db->escapeString($fn->xss_clean($_POST['longitude'])) : "0";
    $require_products_approval = (isset($_POST['require_products_approval']) && $_POST['require_products_approval'] != "") ? $db->escapeString($fn->xss_clean($_POST['require_products_approval'])) : 0;
    $cat_id = $fn->xss_clean_array($_POST['cat_ids']);
    $cat_ids = implode(",", $cat_id);
    $password = $db->escapeString($fn->xss_clean($_POST['password']));
    $password = md5($password);
    $sql = "SELECT id FROM seller WHERE mobile='$mobile'";
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);
    if ($count > 0) {
        echo '<label class="alert alert-danger">Mobile Number Already Exists!</label>';
        return false;
    }
    $target_path = '../upload/seller/';
    if (!is_dir($target_path)) {
        mkdir($target_path, 0777, true);
    }
    if ($_FILES['store_logo']['error'] == 0 && $_FILES['store_logo']['size'] > 0) {

        $extension = pathinfo($_FILES["store_logo"]["name"])['extension'];
        $result = $fn->validate_image($_FILES["store_logo"]);
        if (!$result) {
            echo " <span class='label label-danger'>Logo image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["store_logo"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }
    // address_proof national_id_card
    if ($_FILES['national_id_card']['error'] == 0 && $_FILES['national_id_card']['size'] > 0) {

        $extension = pathinfo($_FILES["national_id_card"]["name"])['extension'];
        $result = $fn->validate_image($_FILES["national_id_card"]);
        if (!$result) {
            echo " <span class='label label-danger'>National id card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $national_id_card = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $national_id_card;
        if (!move_uploaded_file($_FILES["national_id_card"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }
    if ($_FILES['address_proof']['error'] == 0 && $_FILES['address_proof']['size'] > 0) {

        $extension = pathinfo($_FILES["address_proof"]["name"])['extension'];
        $result = $fn->validate_image($_FILES["address_proof"]);
        if (!$result) {
            echo " <span class='label label-danger'>Address Proof card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $address_proof = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $address_proof;
        if (!move_uploaded_file($_FILES["address_proof"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }

    $sql = "INSERT INTO `seller`(`name`, `store_name`,`slug`,`email`, `mobile`, `password`, `store_url`, `logo`, `store_description`, `street`, `pincode_id`,`city_id`, `state`, `account_number`, `bank_ifsc_code`, `account_name`, `bank_name`, `commission`,`status`,`categories`,`require_products_approval`,`national_identity_card`,`address_proof`,`pan_number`,`tax_name`,`tax_number`,`customer_privacy`,`latitude`,`longitude`) VALUES ('$name','$store_name','$slug','$email', '$mobile', '$password','$store_url' ,'$filename', '$store_description', '$street',$pincode_id,$city_id,'$state','$account_number','$bank_ifsc_code','$account_name','$bank_name','$commission','$status','$cat_ids','$require_products_approval','$national_id_card','$address_proof','$pan_number','$tax_name','$tax_number','$customer_privacy','$latitude','$longitude')";
    if ($db->sql($sql)) {
        echo "<div class='alert alert-success'> Seller Added Successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Some Error Occrred! please try again.</div>";
    }
}

if (isset($_POST['update_seller'])  && !empty($_POST['update_seller'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['sellers']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update Seller</label>';
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['update_id']));
    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $store_name = $db->escapeString($fn->xss_clean($_POST['store_name']));
    $slug = $function->slugify($db->escapeString($fn->xss_clean($_POST['store_name'])), 'seller');
    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    $tax_name = $db->escapeString($fn->xss_clean($_POST['tax_name']));
    $tax_number = $db->escapeString($fn->xss_clean($_POST['tax_number']));
    $pan_number = $db->escapeString($fn->xss_clean($_POST['pan_number']));
    $commission = $db->escapeString($fn->xss_clean($_POST['commission']));
    $store_description = (isset($_POST['hide_description']) && ($_POST['hide_description'] != "")) ? $db->escapeString($fn->xss_clean($_POST['hide_description'])) : "";
    if (strpos($name, "'") !== false) {
        $name = str_replace("'", "''", "$name");
        if (strpos($store_description, "'") !== false)
            $store_description = str_replace("'", "''", "$store_description");
    }
    $status = (isset($_POST['status']) && $_POST['status'] != "") ? $db->escapeString($fn->xss_clean($_POST['status'])) : "2";
    $customer_privacy = (isset($_POST['customer_privacy']) && $_POST['customer_privacy'] != "") ? $db->escapeString($fn->xss_clean($_POST['customer_privacy'])) : "0";
    $view_order_otp = (isset($_POST['view_order_otp']) && $_POST['view_order_otp'] != "") ? $db->escapeString($fn->xss_clean($_POST['view_order_otp'])) : "0";
    $assign_delivery_boy = (isset($_POST['assign_delivery_boy']) && $_POST['assign_delivery_boy'] != "") ? $db->escapeString($fn->xss_clean($_POST['assign_delivery_boy'])) : "0";
    $store_url = (isset($_POST['store_url']) && $_POST['store_url'] != "") ? $db->escapeString($fn->xss_clean($_POST['store_url'])) : "";
    $street = (isset($_POST['street']) && $_POST['street'] != "") ? $db->escapeString($fn->xss_clean($_POST['street'])) : "";
    $pincode_id = (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "0";
    $city_id = (isset($_POST['city_id']) && $_POST['city_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['city_id'])) : "0";
    $state = (isset($_POST['state']) && $_POST['state'] != "") ? $db->escapeString($fn->xss_clean($_POST['state'])) : "";
    $account_number = (isset($_POST['account_number']) && $_POST['account_number'] != "") ? $db->escapeString($fn->xss_clean($_POST['account_number'])) : "";
    $bank_ifsc_code = (isset($_POST['ifsc_code']) && $_POST['ifsc_code'] != "") ? $db->escapeString($fn->xss_clean($_POST['ifsc_code'])) : "";
    $account_name = (isset($_POST['account_name']) && $_POST['account_name'] != "") ? $db->escapeString($fn->xss_clean($_POST['account_name'])) : "";
    $bank_name = (isset($_POST['bank_name']) && $_POST['bank_name'] != "") ? $db->escapeString($fn->xss_clean($_POST['bank_name'])) : "";
    $latitude = (isset($_POST['latitude']) && $_POST['latitude'] != "") ? $db->escapeString($fn->xss_clean($_POST['latitude'])) : "0";
    $longitude = (isset($_POST['longitude']) && $_POST['longitude'] != "") ? $db->escapeString($fn->xss_clean($_POST['longitude'])) : "0";


    $require_products_approval = (isset($_POST['require_products_approval']) && $_POST['require_products_approval'] != "") ? $db->escapeString($fn->xss_clean($_POST['require_products_approval'])) : 0;
    $cat_id = (isset($_POST['cat_ids'])) ? $fn->xss_clean_array($_POST['cat_ids']) : "";
    $cat_ids = "";
    if (!empty($cat_id)) {
        $cat_ids = implode(",", $cat_id);
        $cat_ids = $db->escapeString($cat_ids);
    }

    $password = !empty($_POST['password']) ? $db->escapeString($fn->xss_clean($_POST['password'])) : '';
    $password = !empty($password) ? md5($password) : '';

    if ($_FILES['store_logo']['size'] != 0 && $_FILES['store_logo']['error'] == 0 && !empty($_FILES['store_logo'])) {
        //image isn't empty and update the image
        $old_logo = $db->escapeString($fn->xss_clean($_POST['old_logo']));
        $extension = pathinfo($_FILES["store_logo"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["store_logo"]);
        if (!$result) {
            echo " <span class='label label-danger'>Logo image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $target_path = '../upload/seller/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["store_logo"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if (!empty($old_logo)) {
            unlink($target_path . $old_logo);
        }
        $sql = "UPDATE seller SET `logo`='" . $filename . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }
    if ($_FILES['national_id_card']['size'] != 0 && $_FILES['national_id_card']['error'] == 0 && !empty($_FILES['national_id_card'])) {
        //image isn't empty and update the image
        $old_national_identity_card = $db->escapeString($fn->xss_clean($_POST['old_national_identity_card']));
        $extension = pathinfo($_FILES["national_id_card"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["national_id_card"]);
        if (!$result) {
            echo " <span class='label label-danger'>National id card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $target_path = '../upload/seller/';
        $national_id_card = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $national_id_card;
        if (!move_uploaded_file($_FILES["national_id_card"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if (!empty($old_national_identity_card)) {
            unlink($target_path . $old_national_identity_card);
        }
        $sql = "UPDATE seller SET `national_identity_card`='" . $national_id_card . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }
    if ($_FILES['address_proof']['size'] != 0 && $_FILES['address_proof']['error'] == 0 && !empty($_FILES['address_proof'])) {
        //image isn't empty and update the image
        $old_address_proof = $db->escapeString($fn->xss_clean($_POST['old_address_proof']));
        $extension = pathinfo($_FILES["address_proof"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["address_proof"]);
        if (!$result) {
            echo " <span class='label label-danger'>Address Proof card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $target_path = '../upload/seller/';
        $address_proof = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $address_proof;
        if (!move_uploaded_file($_FILES["address_proof"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if (!empty($old_address_proof)) {
            unlink($target_path . $old_address_proof);
        }
        $sql = "UPDATE seller SET `address_proof`='" . $address_proof . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }

    if (!empty($password)) {
        $sql = "UPDATE `seller` SET `name`='$name',`latitude`='$latitude',`longitude`='$longitude',`customer_privacy`='$customer_privacy',`view_order_otp`='$view_order_otp',`assign_delivery_boy`='$assign_delivery_boy',`store_name`='$store_name',`slug` = '$slug',`email`='$email',`mobile`='$mobile',`password`='$password',`store_url`='$store_url',`store_description`='$store_description',`street`='$street',`pincode_id`='$pincode_id',`city_id`='$city_id',`state`='$state',`account_number`='$account_number',`bank_ifsc_code`='$bank_ifsc_code',`account_name`='$account_name',`bank_name`='$bank_name',`commission`='$commission',`status`=$status,`categories`='$cat_ids',`require_products_approval`='$require_products_approval' ,`pan_number`='$pan_number',`tax_name`='$tax_name',`tax_number`='$tax_number' WHERE id=" . $id;
    } else {
        $sql = "UPDATE `seller` SET `name`='$name',`latitude`='$latitude',`longitude`='$longitude',`customer_privacy`='$customer_privacy',`view_order_otp`='$view_order_otp',`assign_delivery_boy`='$assign_delivery_boy',`store_name`='$store_name',`slug` = '$slug',`email`='$email',`mobile`='$mobile',`store_url`='$store_url',`store_description`='$store_description',`street`='$street',`pincode_id`='$pincode_id',`city_id`='$city_id',`state`='$state',`account_number`='$account_number',`bank_ifsc_code`='$bank_ifsc_code',`account_name`='$account_name',`bank_name`='$bank_name',`commission`='$commission',`status`=$status,`categories`='$cat_ids',`require_products_approval`='$require_products_approval',`pan_number`='$pan_number',`tax_name`='$tax_name',`tax_number`='$tax_number' WHERE id=" . $id;
    }
    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Information Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}

if (isset($_POST['get_categories_by_seller']) && $_POST['get_categories_by_seller'] != '') {
    $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    if (empty($seller_id)) {
        echo '<option value="">Select Categories</option>';
        return false;
    }
    $sql = "SELECT categories FROM seller WHERE id = " . $seller_id;
    $db->sql($sql);
    $res = $db->getResult();

    $sql = "SELECT id, name FROM `category` WHERE id IN(" . $res[0]['categories'] . ") ORDER BY id ASC ";
    $db->sql($sql);
    $res = $db->getResult();

    $options = '<option value="">Select Categories</option>';
    foreach ($res as $option) {
        $options .= "<option value='" . $option['id'] . "'>" . $option['name'] . "</option>";
    }

    echo $options;
}

if (isset($_POST['remove_seller']) && !empty($_POST['remove_seller']) && isset($_POST['seller_id']) && !empty($_POST['seller_id'])) {

    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['sellers']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update seller.</label>";
        return false;
    }
    $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $type = $db->escapeString($fn->xss_clean($_POST['type']));

    if ($type == 'trashed') {
        $sql = "UPDATE `seller` SET `status`= 7 WHERE id = $seller_id";
        if ($db->sql($sql)) {
            echo 1;
        } else {
            echo 0;
        }
    }
    if ($type == 'restore') {
        $sql = "UPDATE `seller` SET `status`= 1 WHERE id = $seller_id";
        if ($db->sql($sql)) {
            echo 1;
        } else {
            echo 0;
        }
    }
}
if (isset($_POST['delete_seller']) && !empty($_POST['delete_seller']) && isset($_POST['seller_id']) && !empty($_POST['seller_id'])) {
    if ($permissions['sellers']['delete'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update seller.</label>";
        return false;
    }
    $value = false;
    $ID = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $sql = "SELECT * FROM products WHERE seller_id=$ID";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $result = $fn->delete_product($row['id']);
        if (!empty($result)) {
            $value[] = 1;
        } else {
            return false;
        }
    }

    // delete seller
    $target_path = '../upload/seller/';
    $sql_query = "SELECT logo,national_identity_card,address_proof FROM `seller` WHERE id =" . $ID;
    $db->sql($sql_query);
    $res = $db->getResult();
    unlink($target_path . $res[0]['logo']);
    unlink($target_path . $res[0]['national_identity_card']);
    unlink($target_path . $res[0]['address_proof']);

    $sql_query = "DELETE FROM seller WHERE id =" . $ID;
    $db->sql($sql_query);

    // delete seller tansactions
    $sql_query = "DELETE FROM seller_transactions WHERE seller_id =" . $ID;
    $db->sql($sql_query);

    // delete seller wallet transactions
    $sql_query = "DELETE FROM seller_wallet_transactions WHERE seller_id =" . $ID;
    $db->sql($sql_query);

    $sql_query = "SELECT DISTINCT oi.id,oi.order_id,oi.product_variant_id,oi.seller_id FROM order_items oi  INNER JOIN  orders O on o.id-oi.order_id where oi.seller_id=" . $ID;
    $db->sql($sql_query);
    $res = $db->getResult();
    foreach ($res as $row) {
        $order_item_id = $row['id'];
        // delete order items
        $sql_query = "DELETE FROM order_items WHERE id =" . $order_item_id;
        if ($db->sql($sql_query)) {
            $value[] = 1;
        } else {
            $value = array();
        }
    }

    $res_order_id = array_values(array_unique(array_column($res, "order_id")));

    for ($i = 0; $i < count($res_order_id); $i++) {
        $order_id = $res_order_id[$i];
        $sql_query = "SELECT DISTINCT oi.id,oi.order_id,oi.product_variant_id,oi.seller_id FROM order_items oi INNER JOIN orders O on o.id-oi.order_id where oi.seller_id!=$ID and oi.order_id=$order_id";
        $db->sql($sql_query);
        $res = $db->getResult();
        if (empty($res)) {
            // delete orders
            $sql_query = "DELETE FROM orders WHERE id =" . $order_id;
            $db->sql($sql_query);

            $value[] = 1;
        } else {
            $value = array();
        }
    }
    if (!empty($value)) {
        echo 0;
    } else {
        echo 1;
    }
}

if (isset($_POST['type']) && $_POST['type'] != '' && $_POST['type'] == 'delete-notification') {
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['notifications']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id        = $db->escapeString($fn->xss_clean($_POST['id']));
    $image     = $db->escapeString($fn->xss_clean($_POST['image']));

    if (!empty($image))
        unlink('../' . $image);

    $sql = 'DELETE FROM `notifications` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}
if (isset($_GET['update_user_status']) && $_GET['update_user_status'] == 1) {
    if ($permissions['customers']['update'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $status = $db->escapeString($fn->xss_clean($_GET['status']));
    $sql = "UPDATE users set `status` = " . $status . " WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}

if (isset($_POST['boy_id']) && isset($_POST['cash_collection']) && !empty($_POST['cash_collection'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['delivery_boys']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update delivery boy.</label>";
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['boy_id']));
    $cash = $fn->get_data($columns = ['cash_received'], "id='" . $id . "'", 'delivery_boys');
    if (isset($cash[0]['cash_received'])) {
        $cash = $cash[0]['cash_received'];
        if (!is_numeric($_POST['amount'])) {
            echo "<label class='alert alert-danger'>Amount must be number.</label>";
            return false;
        }
        if ($_POST['amount'] > $cash) {
            echo "<label class='alert alert-danger'>Amount must be not be greater than cash.</label>";
            return false;
        }
        $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
        $date = $db->escapeString($fn->xss_clean($_POST['date']));

        $message = (!empty($_POST['message'])) ? $db->escapeString($fn->xss_clean($_POST['message'])) : 'Delivery boy cash collection by admin';
        $cash = $cash - $amount;
        $sql = "Update delivery_boys set `cash_received`='" . $cash . "' where `id`=" . $id;
        $db->sql($sql);
        $fn->add_transaction("", $id, 'delivery_boy_cash_collection', $amount, $message, $date);
        echo "<p class='alert alert-success'>Cash collected Successfully!</p>";
    } else {
        echo "<label class='alert alert-danger'>Something went wrong!.</label>";
        return false;
    }
}
if (isset($_POST['seller_id']) && isset($_POST['get_category_wise_commission']) && !empty($_POST['get_category_wise_commission'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['sellers']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update seller.</label>";
        return false;
    }
    $html = '<table class="table table-hover"><tr><th>ID</th><th>Category</th><th>Commission(%) <small>[keep blank if want to apply global commission for particular category]</small></th></tr>';
    $seller_id = $_POST['seller_id'];
    $categories = $fn->get_data($columns = ['categories'], "id='" . $seller_id . "'", 'seller');
    if (isset($categories[0]['categories']) && !empty($categories[0]['categories'])) {
        $categories = explode(',', $categories[0]['categories']);
        for ($i = 0; $i < count($categories); $i++) {
            $category = $fn->get_data($columns = ['id', 'name'], "id='" . $categories[$i] . "'", 'category');
            if (isset($category[0]['id'])) {
                $commission = $fn->get_data($columns = ['commission'], "seller_id='" . $seller_id . "' and category_id='" . $category[0]['id'] . "'", 'seller_commission');
                $commission = isset($commission[0]['commission']) && !empty($commission[0]['commission']) ? $commission[0]['commission'] : '';
                $html .= '<tr><td>' . $category[0]['id'] . '</td><td>' . $category[0]['name'] . '</td><td><input type="hidden" name="category_id[]" value=' . $category[0]['id'] . '><input type="number" min="0" name="commission[]" value=' . $commission . '></td></tr>';
            }
        }
        $html .= '</table>';
        $html .= '<div class="form-group">
        
            <button type="submit" id="update_btn" class="btn btn-success">Save</button><hr>
            <div id="save_result">
            </div>
        
    </div>';
        $response['error'] = false;
        $response['html'] = $html;
        $response['seller_id'] = $seller_id;
        print_r(json_encode($response));
    }
}
if (isset($_POST['seller_id']) && isset($_POST['save_seller_commission']) && !empty($_POST['save_seller_commission'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['sellers']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update seller.</label>";
        return false;
    }
    $seller_id = $db->escapeString($_POST['seller_id']);
    $category_ids = $fn->xss_clean_array($_POST['category_id']);
    $commission = $fn->xss_clean_array($_POST['commission']);
    for ($i = 0; $i < count($category_ids); $i++) {
        $result = $fn->get_data($columns = ['id'], "seller_id='" . $seller_id . "' and category_id='" . $category_ids[$i] . "'", 'seller_commission');
        if (isset($result[0]['id']) && !empty($result[0]['id'])) {
            $commission[$i] = isset($commission[$i]) && $commission[$i] > 0 ? $commission[$i] : 0;
            if ($commission[$i] > 0) {

                $sql = "update seller_commission set commission = " . $commission[$i] . " where id=" . $result[0]['id'];
            } else {
                $sql = "DELETE from seller_commission where id=" . $result[0]['id'];
            }
            $db->sql($sql);
        } else {
            if (!empty($commission[$i])) {
                $sql = "INSERT into seller_commission (seller_id,category_id,commission) VALUES ('" . $seller_id . "','" . $category_ids[$i] . "','" . $commission[$i] . "')";
                $db->sql($sql);
            }
        }
    }
    echo "<label class='alert alert-success'>Commission saved successfully.</label>";
    return false;
}

if (isset($_POST['update_bank_transfer']) && $_POST['update_bank_transfer'] == 1) {
    $message = isset($_POST['message']) && !empty($_POST['message']) ? $db->escapeString($fn->xss_clean($_POST['message'])) : "";
    $status = isset($_POST['status']) && !empty($_POST['status']) ? $db->escapeString($fn->xss_clean($_POST['status'])) : "";
    $order_id = $db->escapeString($fn->xss_clean($_POST['order_id']));

    $sql = "SELECT * FROM `order_bank_transfers` WHERE order_id=" . $order_id;
    $db->sql($sql);
    $res = $db->getResult();
    // print_r($res);
    if ($res[0]['status'] == 0) {
        $atta_status = 'Pending';
    } elseif ($res[0]['status'] == 1) {
        $atta_status = 'Accepted';
    } elseif ($res[0]['status'] == 2) {
        $atta_status = 'Rejected';
    }

    if (!empty($_POST['status']) && $status == 0 && $status != '') {
        echo "<label class='alert alert-danger'>status already Accepted.</label>";
        return false;
    }

    if (($res[0]['status'] == 0 && $status == 0) || ($res[0]['status'] == 1 && $status == 1) || ($res[0]['status'] == 2 && $status == 2)) {
        echo  "<label class='alert alert-danger'>status already $atta_status. </label>";
        return false;
    }

    if ($res[0]['status'] < $status) {
        if (!empty($message)) {
            $sql_query = "update order_bank_transfers set `message`='" . $message . "',`status`='" . $status . "' where `order_id`=" . $order_id;
        } else {
            $sql_query = "update order_bank_transfers set `status`='" . $status . "' where `order_id`=" . $order_id;
        }

        if ($db->sql($sql_query)) {
            echo "<label class='alert alert-success'>Bank Transfer Details Updated successfully.</label>";
        } else {
            echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
        }
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}

if (isset($_POST['get_category_id_by_product_id']) && $_POST['get_category_id_by_product_id'] == 1) {
    if ($_POST['category_id'] == '') {
        $sql = "SELECT id,name from `products` where `status` = 1 order by id desc";
    } else {
        $category_ids = $db->escapeString($fn->xss_clean($_POST['category_id']));
        $sql = "SELECT id,name from `products` where `status` = 1 AND category_id IN( $category_ids ) order by id desc";
    }
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
        }
    }
}


if (isset($_POST['get_seller_pickup_location']) && !empty($_POST['get_seller_pickup_location'])) {
    $sql_query = 'SELECT pickup_location,pin_code FROM pickup_locations WHERE  verified=1 and  seller_id="' . $_POST['get_seller_pickup_location'] . '"';
    $db->sql($sql_query);
    $seller_pickup_locations = $db->getResult();
    if (!empty($seller_pickup_locations)) {

        $result['error'] = false;
        $result['message'] = 'Pickup Location Fetched Successfully';
        $result['data'] = $seller_pickup_locations;
        print_r(json_encode($result));
    } else {
        $result['error'] = true;
        $result['message'] = 'This Sellers have Not Added Pickup Locations';
        print_r(json_encode($result));
    }
}


if (isset($_POST['create_order_btn']) && !empty($_POST['create_order_btn'])) {

    $order_id = $_POST['order_id'];
    $seller_id = $_POST['select_seller_id'];
    $pickup_location = $_POST['seller_pickup_location'];
    $weight = $_POST['weight'];
    $hieght = $_POST['hieght'];
    $length = $_POST['length'];
    $breadth = $_POST['breadth'];
    $sub_total = $_POST['subtotal'];
    $order_item_ids = $_POST['order_item_ids'];


    $res = $fn->process_shiprocket($order_id, $seller_id, $pickup_location, $sub_total, $weight, $hieght, $breadth, $length, $order_item_ids);


    if ($res['status_code'] == 1) {
        $result['error'] = false;
        $result['message'] = 'Order Created Successfully';
        print_r(json_encode($result));
    } else {
        $result['error'] = true;
        $result['message'] = $res['message'];
        $result['data'] = (isset($res['errors']) && !empty($res['errors'])) ? $res['errors'] : '';
        print_r(json_encode($result));
    }
}


if (isset($_POST['request_pickup']) && !empty($_POST['request_pickup'])) {

    $shipment_id = (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) ? $_POST['shipment_id'] : '';
    $courier_company_id = (isset($_POST['courier_company_id']) && !empty($_POST['courier_company_id'])) ? $_POST['courier_company_id'] : '';
    $res = $fn->send_request_for_pickup($shipment_id);
    if ($res['error'] == false) {
        $result['error'] = false;
        $result['message'] = "Request For Pickup Sended Successfully";
    } else {
        $result['error'] = true;
        $result['message'] = $res['message'];
    }
    print_r(json_encode($result));
}

if (isset($_POST['generate_awb']) && !empty($_POST['generate_awb'])) {

    $shipment_id = (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) ? $_POST['shipment_id'] : '';
    $courier_company_id = (isset($_POST['courier_company_id']) && !empty($_POST['courier_company_id'])) ? $_POST['courier_company_id'] : '';
    $res = $fn->generate_awb($shipment_id);

    print_r(json_encode($res));
}



if (isset($_POST['track_order']) && !empty($_POST['track_order'])) {
    if (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) {
        $res = $fn->track_order($_POST['shipment_id']);
        if ($res['tracking_data']) {
            $result['error'] = false;
            $result['message'] = "Your Order tracking successfully";
            $result['current_status'] = $res['tracking_data']['shipment_track'][0]['current_status'];
            $result['data']['track_activities'] = $res['tracking_data']['shipment_track_activities'];
        } else {
            $result['error'] = true;
            $result['message'] = $res['message'];
        }
        print_r(json_encode($result));
    }
}
if (isset($_POST['generate_manifests']) && !empty($_POST['generate_manifests'])) {
    if (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) {
        $res = $fn->generate_manifests($_POST['shipment_id']);
        print_r(json_encode($res));
    }
}
if (isset($_POST['generate_labels']) && !empty($_POST['generate_labels'])) {
    if (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) {
        $res = $fn->generate_labels($_POST['shipment_id']);
        print_r(json_encode($res));
    }
}

if (isset($_POST['cancel_order']) && !empty($_POST['cancel_order'])) {
    if (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) {
        $oders_data = $fn->get_data(['shiprocket_order_id'], 'shipment_id=' . $_POST['shipment_id'], 'order_trackings');
        $shiprocket_order_id = $oders_data[0]['shiprocket_order_id'];
        $order_id['ids'] = [$shiprocket_order_id];
        $res = $shiprocket->cancel_order($order_id);


        if ($res['status'] == 200) {
            $sql = "UPDATE order_trackings  SET is_canceled=1 where shiprocket_order_id=" . $shiprocket_order_id;
            $db->sql($sql);
            $result['error'] = false;
            $result['message'] = "Your Order cancel successfully";
        } else {
            $result['error'] = true;
            $result['message'] = $res['message'];
        }
        print_r(json_encode($result));
    }
}






if (isset($_POST['shiprocket']) && isset($_POST['shiprocket_email']) && isset($_POST['shiprocket_password'])) {


    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $data_shiprocket = $fn->get_settings('shiprocket', true);

    if (empty($data_shiprocket)) {
        $shiprocket = $_POST['shiprocket'];
        $shiprocket_email = $_POST['shiprocket_email'];
        $shiprocket_email = $_POST['shiprocket_email'];
        $webhook_token = $_POST['webhook_token'];
        $json_data = array('shiprocket' => $shiprocket, 'shiprocket_email' => $shiprocket_email, 'shiprocket_password' => $shiprocket_password, 'webhook_token' => $webhook_token);
        $encode_data_shiprocket = json_encode($json_data);
        $sql = "INSERT INTO `settings`(`variable`, `value`) VALUES ('shiprocket','$encode_data_shiprocket')";
        $db->sql($sql);
        echo "<div class='alert alert-success'> shiprocket created successfully!</div>";
    } else {
        $shiprocket = $_POST['shiprocket'];
        $shiprocket_email = $_POST['shiprocket_email'];
        $shiprocket_password = $_POST['shiprocket_password'];
        $webhook_token = $_POST['webhook_token'];
        $json_data = array('shiprocket' => $shiprocket, 'shiprocket_email' => $shiprocket_email, 'shiprocket_password' => $shiprocket_password, 'webhook_token' => $webhook_token);
        $encode_data_shiprocket = json_encode($json_data);
        // $local_shipping = $_POST['local_shipping'];
        $sql = "UPDATE `settings` SET `value`='$encode_data_shiprocket' WHERE `variable`='shiprocket'";
        $db->sql($sql);
        echo "<div class='alert alert-success'> shiprocket updated successfully!</div>";
    }
}
if (isset($_POST['local_shipping'])) {
    $sql = "SELECT variable,value FROM settings  where variable='local_shipping'";
    $db->sql($sql);
    $data_local_shipping = $db->getResult();
    $local_shipping = $_POST['local_shipping'];
    if (empty($data_local_shipping)) {
        $sql = "INSERT INTO `settings`(`variable`, `value`) VALUES ('local_shipping','$local_shipping')";
        $db->sql($sql);
        echo "<div class='alert alert-success'> local shipping created successfully!</div>";
    } else {
        $sql = "UPDATE `settings` SET `value`='$local_shipping' WHERE `variable`='local_shipping'"  or die();
        $db->sql($sql);
        echo "<div class='alert alert-success'> local shipping updated successfully!</div>";
    }
}


if (isset($_POST['selected_pickup_location_seller_id']) &&  !empty($_POST['selected_pickup_location_seller_id'])) {

    $pickup_location = $_POST['pickup_location_'];
    $seller_id = $_POST['selected_pickup_location_seller_id'];
    $varify_slug = preg_match('/-/i', $pickup_location);
    $url = $pickup_location . " " . $seller_id;
    $url = $function->slugify($url);
    $creat_slug = ($varify_slug == 1) ? $pickup_location : $url;
    $sql_query = "SELECT * FROM  pickup_locations WHERE pickup_location='$creat_slug'";
    $db->sql($sql_query);
    $data = $db->getresult();
    if (!empty($data)) {
        $result['error'] = false;
        $result['message'] = 'Pickup Locations Already Taken';
        $result['data'] = $data;
    } else {
        $result['error'] = false;
        $result['slug'] = $creat_slug;
    }
    print_r(json_encode($result));
    return false;
}

if (isset($_POST['update_pickup_location']) & !empty($_POST['update_pickup_location'])) {
    $update_pickup_location = $_POST['update_pickup_location'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if (isset($_POST['verified'])) {
        $verified = ($_POST['verified'] == 1) ? '1' : '0';
        $sql = "UPDATE pickup_locations SET  verified='$verified' where id='$update_pickup_location'";
    } else {

        $sql = "UPDATE pickup_locations SET  `name`='$name',`email`='$email',`phone`=$phone where pickup_location='$update_pickup_location'";
    }
    $db->sql($sql);

    if ($db->sql($sql)) {
        if (isset($_POST['verified'])) {
            $status = ($res[0]['verified'] == 1) ? 'verified' : 'unverified';
            $result['error'] = false;
            $result['Message'] = $_POST['update_pickup_location'] . ' Pickup location ' . $status . ' successfuly';
        } elseif (!isset($_POST['verified'])) {
            $result['Message'] = 'Pickup Location update successfully';
        } else {
            $result['error'] = true;
            $result['Message'] = 'Sorry something wrong try some time later... ';
        }
    } else {
        $result['error'] = true;
        $result['Message'] = 'Sorry something wrong try some time later... ';
    }
    print_r(json_encode($result));
}

if (isset($_POST['delete_pickup_location']) & !empty($_POST['delete_pickup_location'])) {


    $delete_pickup_location = $_POST['delete_pickup_location'];


    $sql = 'DELETE FROM pickup_locations where id="' . $delete_pickup_location . '"';
    $db->sql($sql);
    $sql = 'Select verified from pickup_locations where pickup_location="' . $delete_pickup_location . '"';
    $db->sql($sql);
    $res = $db->getResult();


    if (empty($res)) {
        $status = ($res[0]['verified'] == 1) ? 'verified' : 'unverified';
        $result['error'] = false;
        $result['Message'] = $delete_pickup_location . ' Pickup location deleted  successfuly';
    } else {
        $result['error'] = true;
        $result['Message'] = 'Sorry something wrong try some time later... ';
    }
    print_r(json_encode($result));
}

if (!empty($_GET['type']) && $_GET['type'] == "search_pincode") {

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " AND (id like '%" . $search . "%' OR pincode like '%" . $search . "%')";
        $sql = "SELECT * FROM `pincodes` WHERE status = 1 $where";
        $db->sql($sql);
        $res = $db->getResult();
        print_r(json_encode($res));
    }
}

if (isset($_POST['delete_variant_images']) && $_POST['delete_variant_images'] == 1) {
    $vid = $db->escapeString($fn->xss_clean($_POST['vid']));
    $i = $db->escapeString($fn->xss_clean($_POST['i']));

    $res = $fn->delete_variant_images($vid, $i);
    print_r($res);
}
if (isset($_POST['location_bulk_uploads']) && $_POST['location_bulk_uploads'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'cities')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['locations']['create'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to upload Cities.</label>";
        return false;
    }

    $count = 0;
    $count1 = 0;
    $error = false;
    $filename = $_FILES["upload_file"]["tmp_name"];

    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($fn->xss_clean($emapData[0]))); // city name

                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">City Name  is empty at row - ' . $count . '</div>';
                    return false;
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");

        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($fn->xss_clean($emapData[0]))); // city name

                $sql = "INSERT INTO cities (`name`) VALUES ('" . $emapData[0] . "')";
                $db->sql($sql);
            }
            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}
if (isset($_POST['location_bulk_uploads']) && $_POST['location_bulk_uploads'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'pincodes')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['locations']['create'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to upload Pincodes.</label>";
        return false;
    }

    $count = 0;
    $count1 = 0;
    $error = false;
    $filename = $_FILES["upload_file"]["tmp_name"];

    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($fn->xss_clean($emapData[0]))); // pincode
                $emapData[1] = trim($db->escapeString($fn->xss_clean($emapData[1]))); // status

                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">Pincode  is empty at row - ' . $count . '</div>';
                    return false;
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");

        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($fn->xss_clean($emapData[0]))); // pincode
                $emapData[1] = ($emapData[1] != '') ? trim($db->escapeString($fn->xss_clean($emapData[1]))) : 0; // status

                $sql = "INSERT INTO pincodes (`name`,`status`) VALUES ('" . $emapData[0] . "' , '" . $emapData[1] . "')";
                $db->sql($sql);
            }
            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}

if (isset($_POST['location_bulk_uploads']) && $_POST['location_bulk_uploads'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'areas')) {

    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['locations']['create'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to upload areas.</label>";
        return false;
    }

    $count = 0;
    $count1 = 0;
    $error = false;
    $filename = $_FILES["upload_file"]["tmp_name"];

    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($fn->xss_clean($emapData[0]))); // area name
                $emapData[1] = trim($db->escapeString($fn->xss_clean($emapData[1]))); // city id
                $emapData[2] = trim($db->escapeString($fn->xss_clean($emapData[2]))); // Pincode id
                $emapData[3] = trim($db->escapeString($fn->xss_clean($emapData[3]))); // minimum_free_delivery_order_amount
                $emapData[4] = trim($db->escapeString($fn->xss_clean($emapData[4]))); //delivery_charges

                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">Area Name  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[1])) {
                    echo '<p class="alert alert-danger">City Id  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[1])) {
                    $city = $fn->get_data($columns = ['name'], "id=" . $emapData[1], 'cities');
                    if (empty($city)) {
                        echo '<p class="alert alert-danger">City is not exist check the city_id at row - ' . $count . '</div>';
                        return false;
                    }
                }
                if (empty($emapData[2])) {
                    echo '<p class="alert alert-danger">Pincode Id  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[2])) {
                    $pincode = $fn->get_data($columns = ['pincode'], "id=" . $emapData[2], 'pincodes');
                    if (empty($pincode)) {
                        echo '<p class="alert alert-danger">pincode is not exist check the pincode_id at row - ' . $count . '</div>';
                        return false;
                    }
                }
                if (empty($emapData[3])) {
                    echo '<p class="alert alert-danger">Minimum Free Delivery Order Amount  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[4])) {
                    echo '<p class="alert alert-danger">Delivery Charges  is empty at row - ' . $count . '</div>';
                    return false;
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");

        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($fn->xss_clean($emapData[0]))); // Area Name
                $emapData[1] = trim($db->escapeString($fn->xss_clean($emapData[1]))); // City Id
                $emapData[2] = trim($db->escapeString($fn->xss_clean($emapData[2]))); // Pincode Id
                $emapData[3] = trim($db->escapeString($fn->xss_clean($emapData[3]))); // minimum_free_delivery_order_amount
                $emapData[4] = trim($db->escapeString($fn->xss_clean($emapData[4]))); // delivery_charges

                $sql = "INSERT INTO area (`name`,`city_id`,`pincode_id`,`minimum_free_delivery_order_amount`,`delivery_charges`) VALUES ('" . $emapData[0] . "','" . $emapData[1] . "','" . $emapData[2] . "','" . $emapData[3] . "','" . $emapData[4] . "')";
                $db->sql($sql);
            }
            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}
