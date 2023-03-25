<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('../includes/crud.php');
include('../includes/variables.php');
include_once('verify-token.php');
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
$db = new Database();
$db->connect();
$response = array();

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
1. settings
    1. get_payment_methods
    2. get_privacy
    3. get_terms
    4. get_logo
    5. get_contact
    6. get_about_us
    7. get_timezone
    8. get_fcm_key
    9. get_time_slot_config
    10.get_front_end_settings
2. get_time_slots
3. all
4. get_shipping_type
-------------------------------------------
-------------------------------------------
*/

// if (!verify_token()) {
//     return false;
// }

if (!isset($_POST['accesskey'])  || trim($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
}

$settings = $setting = array();

if (isset($_POST['settings']) && $_POST['settings'] == 1) {
    if (isset($_POST['get_payment_methods']) && $_POST['get_payment_methods'] == 1) {
        /*
        1.get_payment_methods
            accesskey:90336
            settings:1
            get_payment_methods:1
        */
        $sql = "select value from `settings` where `variable`='payment_methods'";
        $db->sql($sql);
        $res = $db->getResult();

        if (!empty($res)) {
            $payment_methods = json_decode($res[0]['value']);
            if (!isset($payment_methods->paytm_payment_method)) {
                $payment_methods->paytm_payment_method = 0;
                $payment_methods->paytm_mode = "sandbox";
                $payment_methods->paytm_merchant_key = "";
                $payment_methods->paytm_merchant_id = "";
            }
            $payment_methods->cod_mode = !isset($payment_methods->cod_mode) || empty($payment_methods->cod_mode) ? 'global' : $payment_methods->cod_mode;
            $settings['error'] = false;
            $settings['message'] = "Payment Method Retrived Successfully!";
            $settings['payment_methods'] = $payment_methods;
        } else {
            $settings['error'] = true;
            $settings['settings'] = "No settings found!";
            $settings['message'] = "Something went wrong!";
        }
        print_r(json_encode($settings));
        return false;
    }

    if (isset($_POST['get_privacy']) && $_POST['get_privacy'] == 1) {
        /*
        2.get_privacy
            accesskey:90336
            settings:1
            get_privacy:1
        */
        $sql = "select value from `settings` where variable='privacy_policy'";
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            $settings['error'] = false;
            $settings['message'] = "Privacy Retrived Successfully!";
            $settings['privacy'] = $res[0]['value'];
        } else {
            $settings['error'] = true;
            $settings['settings'] = "No settings found!";
            $settings['message'] = "Something went wrong!";
        }
        print_r(json_encode($settings));
        return false;
    }

    if (isset($_POST['get_terms']) && $_POST['get_terms'] == 1) {
        /*
        3.get_terms
            accesskey:90336
            settings:1
            get_payment_methods:1
        */
        $sql = "select value from `settings` where variable='terms_conditions'";
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            $settings['error'] = false;
            $settings['message'] = "Terms Retrived Successfully!";
            $settings['terms'] = $res[0]['value'];
        } else {
            $settings['error'] = true;
            $settings['settings'] = "No settings found!";
            $settings['message'] = "Something went wrong!";
        }
        print_r(json_encode($settings));
        return false;
    }

    if (isset($_POST['get_logo']) && $_POST['get_logo'] == 1) {
        /*
        4.get_logo
            accesskey:90336
            settings:1
            get_logo:1
        */
        $sql = "select value from `settings` where variable='Logo' OR variable='logo'";
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            $settings['error'] = false;
            $settings['message'] = "Logo Retrived Successfully!";
            $settings['logo'] = DOMAIN_URL . 'dist/img/' . $res[0]['value'];
        } else {
            $settings['error'] = true;
            $settings['settings'] = "No settings found!";
            $settings['message'] = "Something went wrong!";
        }
        print_r(json_encode($settings));
        return false;
    }

    if (isset($_POST['get_contact']) && $_POST['get_contact'] == 1) {
        /*
        5.get_contact
            accesskey:90336
            settings:1
            get_contact:1
        */
        $sql = "select value from `settings` where variable='contact_us'";
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            $settings['error'] = false;
            $settings['message'] = "Contact Retrived Successfully!";
            $settings['contact'] = $res[0]['value'];
        } else {
            $settings['error'] = true;
            $settings['settings'] = "No settings found!";
            $settings['message'] = "Something went wrong!";
        }
        print_r(json_encode($settings));
        return false;
    }

    if (isset($_POST['get_about_us']) && $_POST['get_about_us'] == 1) {
        /*
        6.get_about_us
            accesskey:90336
            settings:1
            get_about_us:1
        */
        $sql = "select value from `settings` where variable='about_us'";
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            $settings['error'] = false;
            $settings['message'] = "About Us Retrived Successfully!";
            $settings['about'] = $res[0]['value'];
        } else {
            $settings['error'] = true;
            $settings['settings'] = "No settings found!";
            $settings['message'] = "Something went wrong!";
        }
        print_r(json_encode($settings));
        return false;
    }

    if (isset($_POST['get_timezone']) && $_POST['get_timezone'] == 1) {
        /*
        7.get_timezone
            accesskey:90336
            settings:1
            get_timezone:1
        */
        $sql = "select value from `settings` where variable='system_timezone'";
        $db->sql($sql);
        $res = $db->getResult();
        $array = json_decode($res[0]['value'], true);

        $array['tax_name'] = isset($array['tax_name']) && !empty($array['tax_name']) ? $array['tax_name'] : "";
        $array['tax_number'] = isset($array['tax_number']) && !empty($array['tax_number']) ? $array['tax_number'] : "0";

        $currency = $fn->get_settings('currency');
        if (!empty($res)) {
            $settings['error'] = false;
            $settings['message'] = "Timezone Retrived Successfully!";
            $settings['settings'] = $fn->replaceArrayKeys($array);
            $settings['settings']['currency'] = $currency;
        } else {
            $settings['error'] = true;
            $settings['settings'] = "No settings found!";
            $settings['message'] = "Something went wrong!";
        }
        print_r(json_encode($settings));
        return false;
    }

    if (isset($_POST['get_fcm_key']) && $_POST['get_fcm_key'] == 1) {
        /*
        8.get_fcm_key
            accesskey:90336
            settings:1
            get_fcm_key:1
        */
        $sql = "select value from `settings` where variable='fcm_server_key'";
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            $settings['error'] = false;
            $settings['message'] = "FCM Key Retrived Successfully!";
            $settings['fcm'] = $res[0]['value'];
        } else {
            $settings['error'] = true;
            $settings['settings'] = "No settings found!";
            $settings['message'] = "Something went wrong!";
        }
        print_r(json_encode($settings));
        return false;
    }

    if (isset($_POST['get_time_slot_config']) && $_POST['get_time_slot_config'] == 1) {
        /*
        9.get_time_slot_config
            accesskey:90336
            settings:1
            get_time_slot_config:1
        */
        $sql = "select value from `settings` where variable='time_slot_config'";
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            $settings['error'] = false;
            $settings['message'] = "Time slot config retrived successfully!";
            $settings['time_slot_config'] = json_decode($res[0]['value']);
        } else {
            $settings['error'] = true;
            $settings['settings'] = "No settings found!";
            $settings['message'] = "Something went wrong!";
        }
        print_r(json_encode($settings));
        return false;
    }

    if (isset($_POST['get_front_end_settings']) && $_POST['get_front_end_settings'] == 1) {
        /*
        10.get_front_end_settings
            accesskey:90336
            settings:1
            get_front_end_settings:1
        */
        $sql = "select * from `settings` where variable='front_end_settings'";
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            $res[0]['value'] = json_decode($res[0]['value'], true);
            $res[0]['value']['favicon'] = DOMAIN_URL . 'dist/img/' . $res[0]['value']['favicon'];
            $res[0]['value']['screenshots'] = DOMAIN_URL . 'dist/img/' . $res[0]['value']['screenshots'];
            $res[0]['value']['google_play'] = DOMAIN_URL . 'dist/img/' . $res[0]['value']['google_play'];
            $res[0]['value']['show_color_picker_in_website'] = !isset($res[0]['value']['show_color_picker_in_website']) && empty($res[0]['value']['show_color_picker_in_website']) ? 0 : $res[0]['value']['show_color_picker_in_website'];

            $settings['error'] = false;
            $settings['message'] = "Front end settings retrived successfully!";
            $settings['front_end_settings'] = $res;
        } else {
            $settings['error'] = true;
            $settings['message'] = "No active time slots found!";
        }
        print_r(json_encode($settings));
        return false;
    }
    $settings['error'] = true;
    $settings['message'] = "Pass All Field!";
    print_r(json_encode($settings));
    return false;
} else if (isset($_POST['get_time_slots']) && $_POST['get_time_slots'] == 1) {
    /*
    2.get_time_slots
        accesskey:90336
        get_time_slots:1  
    */

    $sql = "select * from `time_slots` where status=1 ORDER BY `last_order_time` ASC";
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        $settings['error'] = false;
        $settings['message'] = "Time slot retrived successfully!";
        $settings['time_slots'] = $res;
    } else {
        $settings['error'] = true;
        $settings['message'] = "No active time slots found!";
        $settings['time_slots'] = null;
    }
    print_r(json_encode($settings));
    return false;
} else if (isset($_POST['get_shipping_type']) && !empty($_POST['get_shipping_type'])) {
    /*
    11 get_shipping_type
    accesskey:90336
    get_shipping_type:1
    */

    $shipping_type = ($fn->get_settings('local_shipping') == 1) ? 'local' : 'standard';
    $res['error'] = false;
    $res['message'] = "Shipping type fetched successfully";
    $res['maintenance'] = $fn->get_maintenance_mode('customer');
    $res['shipping_type'] = $shipping_type;

    print_r(json_encode($res));
    return false;
} else if (isset($_POST['all']) && $_POST['all'] == 1) {

    /*
    3.all
        accesskey:90336
        all:1
    */

    $sql = "select variable, value from `settings`";
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        $settings['error'] = false;
        $settings['message'] = "Settings data retrived successfully!";
        $settings['data'] = array();
        foreach ($res as $k => $v) {
            
            if ($v['variable'] == "system_timezone") {
                $system_timezones = (array)json_decode($v['value']);
                $system_timezone = $fn->replaceArrayKeys($system_timezones);
                foreach ($system_timezone as $k => $v) {
                    $settings['data'][$k] = $v;
                }
                /* Setting deault values if not set */
                $settings['data']['min_refer_earn_order_amount'] = (!isset($settings['data']['min_refer_earn_order_amount'])) ? 1 : $settings['data']['min_refer_earn_order_amount'];
                $settings['data']['refer_earn_bonus'] = (!isset($settings['data']['refer_earn_bonus'])) ? 0 : $settings['data']['refer_earn_bonus'];
                $settings['data']['max_refer_earn_amount'] = (!isset($settings['data']['max_refer_earn_amount'])) ? 0 : $settings['data']['max_refer_earn_amount'];
                $settings['data']['minimum_withdrawal_amount'] = (!isset($settings['data']['minimum_withdrawal_amount'])) ? 1 : $settings['data']['minimum_withdrawal_amount'];
                $settings['data']['max_product_return_days'] = (!isset($settings['data']['max_product_return_days'])) ? 0 : $settings['data']['max_product_return_days'];
                $settings['data']['delivery_boy_bonus_percentage'] = (!isset($settings['data']['delivery_boy_bonus_percentage'])) ? 1 : $settings['data']['delivery_boy_bonus_percentage'];
                $settings['data']['low_stock_limit'] = (!isset($settings['data']['low_stock_limit'])) ? 10 : $settings['data']['low_stock_limit'];
                $settings['data']['user_wallet_refill_limit'] = (!isset($settings['data']['user_wallet_refill_limit'])) ? 100000 : $settings['data']['user_wallet_refill_limit'];
                $settings['data']['delivery_charge'] = (!isset($settings['data']['delivery_charge'])) ? 0 : $settings['data']['delivery_charge'];
                $settings['data']['min_order_amount'] = (!isset($settings['data']['min_order_amount'])) ? 1 : $settings['data']['min_order_amount'];
                $settings['data']['min_amount'] = (!isset($settings['data']['min_amount'])) ? 1 : $settings['data']['min_amount'];
                $settings['data']['max_cart_items_count'] = (!isset($settings['data']['max_cart_items_count'])) ? "" : $settings['data']['max_cart_items_count'];
                $settings['data']['currency'] = (!isset($settings['data']['currency'])) ? "" : $settings['data']['currency'];
            } else {
                $settings['data'][$v['variable']] = $v['value'];
            }
        }
    } else {
        $settings['error'] = true;
        $settings['settings'] = "No settings found!";
        $settings['message'] = "Something went wrong!";
    }
    $settings['data']['currency'] = ($settings['data']['currency'] != false) ? $settings['data']['currency'] : $settings['data']['currency'];
    $settings = mb_convert_encoding($settings, 'UTF-8', 'UTF-8');
    print_r(json_encode($settings));
    return false;
} else {
    $settings['error'] = true;
    $settings['message'] = "Pass Settings Field!";
    print_r(json_encode($settings));
    return false;
}
