<?php
/*
functions
---------------------------------------------
0. xss_clean($data)
1. get_product_by_id($id=null)
2. get_product_by_variant_id($arr)
3. convert_to_parent($measurement,$measurement_unit_id)
4. rows_count($table,$field = '*',$where = '')
5. get_configurations()
6. get_balance($id)
7. get_bonus($id)
8. get_wallet_balance($id)
9. update_wallet_balance($balance,$id)
10. add_wallet_transaction($order_id="",$id,$type,$amount,$message,$status = 1)
11. update_order_item_status($order_item_ids,$order_id,$status)
12. validate_promo_code($user_id,$promo_code,$total)
13. get_settings($variable,$is_json = false)
14. send_order_update_notification($uid,$title,$message,$type)
15. send_notification_to_delivery_boy($uid,$title,$message,$type,$order_id)
16. get_promo_details($promo_code)
17. store_return_request($user_id,$order_id,$order_item_id)
18. get_role($id)
19. get_permissions($id)
20. add_delivery_boy_commission($id,$type,$amount,$message,$status = "SUCCESS")
21. store_delivery_boy_notification($delivery_boy_id,$order_id,$title,$message,$type)
22. is_item_available_in_cart($user_id,$product_variant_id)
23. time_slot_config()
24. is_address_exists($id="",$user_id="")
25. is_user_or_dboy_exists($type,$type_id)
26. get_user_or_delivery_boy_balance($type,$type_id)
27. store_withdrawal_request($type, $type_id, $amount, $message)
28. debit_balance($type, $type_id, $new_balance)
29. is_records_exists($type, $type_id,$offset,$limit)
30. get_product_id_by_variant_id($product_variant_id)
31. update_delivery_boy_wallet_balance($balance, $id)
32. low_stock_count($low_stock_limit)
33. sold_out_count()
34. is_product_available($product_id)
35. is_product_added_as_favorite($user_id, $product_id)
36. validate_email($email)
37. update_forgot_password_code($email,$code)
38. validate_code($code)
39. get_user($code)
40. update_password($code,$password_hash)
41. is_return_request_exists($user_id, $order_item_id)
42. get_last_inserted_id($table)
43. is_product_cancellable($order_item_id)
44. is_default_address_exists($user_id)
44. get_data($fields=[], $where,$table)
45. update_order_status($id,$status,$delivery_boy_id=0)
46. verify_paystack_transaction($reference, $email, $amount)
47. get_variant_id_by_product_id($product_id)
48. get_order_item_by_order_id($id)
49. add_wallet_balance($order_id, $user_id, $amount, $type,$message)
50. send_notification_to_admin($id, $title, $message, $type, $order_id)
51. add_seller_wallet_transaction($order_id = "",$order_item_id, $seller_id, $type, $amount, $message = 'Used against Order Placement', $status = 1)
52. replaceArrayKeys($array)
53. validate_image($file, $is_image = true)
54. validate_other_images($tmp_name, $type)
55. is_order_item_cancelled($order_item_id)
56. is_order_item_returned($active_status, $postStatus)
57. cancel_order_item($id, $order_item_id)
58. get_user_address($address_id)
59. send_notification_to_seller($sid, $title, $message, $type, $id)
60. check_for_return_request($product_id = 0, $order_id = 0)
61. delete_product($product_id)
62. get_seller_permission($seller_id, $permission)
63. get_seller_balance($seller_id)
64. delete_order($order_id)
65. delete_order_item($order_item_id)
66. select_top_sellers()
67. select_top_categories()
68. function set_timezone($config)
69. delete_other_images($pid, $i, $seller_id = "0")
70. delete_variant($v_id)
71. get_seller_address($seller_id)
72. add_transaction($order_id = "", $id = "", $type = '', $amount, $message = '', $date = '', $status = 1)



*/
require_once('crud.php');
require_once('firebase.php');
require_once('push.php');
require_once('functions.php');
// include_once('../library/shiprocket.php');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

$fn = new functions();
class custom_functions
{
    protected $db;
    function __construct()
    {
        $this->db = new Database();
        $this->db->connect();
    }


    function xss_clean_array($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->xss_clean($value);
            }
        } else {
            $array = $this->xss_clean($array);
        }
        return $array;
    }

    function xss_clean($data)
    {
        $data = trim($data);
        // Fix &entity\n;
        $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        } while ($old_data !== $data);

        // we are done...
        return $data;
    }
    function get_pincode_id_by_pincode($pincode)
    {
        $sql = "SELECT id from pincodes where pincode = " . $pincode;
        $this->db->sql($sql);
        $res = $this->db->getResult();

        if (!empty($res)) {
            return $res;
        }
    }
    function get_product_by_id($id = null)
    {
        if (!empty($id)) {
            $sql = "SELECT * FROM products WHERE id=" . $id;
        } else {
            $sql = "SELECT * FROM products";
        }
        $this->db->sql($sql);
        $res = $this->db->getResult();
        $product = array();
        $i = 1;
        foreach ($res as $row) {
            $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'];
            $this->db->sql($sql);
            $product[$i] = $row;
            $product[$i]['variant'] = $this->db->getResult();
            $i++;
        }
        if (!empty($product)) {
            return $product;
        }
    }
    function get_product_by_variant_id($arr)
    {
        $arr = stripslashes($arr);
        if (!empty($arr)) {
            $arr = json_decode($arr, 1);
            $i = 0;
            foreach ($arr as $id) {
                $sql = "SELECT *,pv.id,pv.type as product_type,(SELECT t.title FROM taxes t WHERE t.id=p.tax_id) as tax_title,(SELECT t.percentage FROM taxes t WHERE t.id=p.tax_id) as tax_percentage,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv JOIN products p ON pv.product_id=p.id WHERE pv.id=" . $id;
                $this->db->sql($sql);
                $res[$i] = $this->db->getResult()[0];
                $i++;
            }
            if (!empty($res)) {
                return $res;
            }
        }
    }
    function get_product_by_variant_id2($value)
    {
        // $arr = stripslashes($arr);
        // if (!empty($arr)) {
        // $arr = json_decode($arr, 1);
        // $i = 0;
        // foreach ($arr as $id) {
        $sql = "SELECT *,pv.id,(SELECT t.title FROM taxes t WHERE t.id=p.tax_id) as tax_title,(SELECT t.percentage FROM taxes t WHERE t.id=p.tax_id) as tax_percentage,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv JOIN products p ON pv.product_id=p.id WHERE pv.id=" . $value;
        $this->db->sql($sql);
        $res = $this->db->getResult()[0];

        // }
        if (!empty($res)) {
            return $res;
        }
        // }
    }

    function convert_to_parent($measurement, $measurement_unit_id)
    {
        $sql = "SELECT * FROM unit WHERE id=" . $measurement_unit_id;
        $this->db->sql($sql);
        $unit = $this->db->getResult();
        if (!empty($unit[0]['parent_id'])) {
            $stock = $measurement / $unit[0]['conversion'];
        } else {
            $stock = ($measurement) * $unit[0]['conversion'];
        }
        return $stock;
    }
    function rows_count($table, $field = '*', $where = '')
    {
        // Total count
        if (!empty($where)) $where = "Where " . $where;
        $sql = "SELECT COUNT(" . $field . ") as total FROM " . $table . " " . $where;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        foreach ($res as $row)
            return ($row['total'] != "") ? $row['total'] : 0;
    }

    public function get_configurations()
    {
        $sql = "SELECT value FROM settings WHERE `variable`='system_timezone'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return json_decode($res[0]['value'], true);
        } else {
            return false;
        }
    }
    public function get_balance($id)
    {
        $sql = "select name,bonus from delivery_boys where id=" . $id;
        $this->db->sql($sql);
        $res_bonus = $this->db->getResult();
        // print_r($res_bonus);
        $sql_new = "SELECT balance FROM delivery_boys WHERE id=" . $id;
        $this->db->sql($sql_new);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['balance'];
        } else {
            return false;
        }
    }
    public function get_bonus($id)
    {
        $sql = "SELECT bonus FROM delivery_boys WHERE id=" . $id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['bonus'];
        } else {
            return false;
        }
    }
    public function get_wallet_balance($id, $table_name)
    {
        $sql = "SELECT balance FROM $table_name WHERE id=" . $id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['balance'];
        } else {
            return 0;
        }
    }
    public function update_wallet_balance($balance, $id, $table_name)
    {
        $data = array(
            'balance' => $balance
        );
        $this->db->update($table_name, $data, 'id=' . $id);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }

    public function add_wallet_transaction($order_id = "", $order_item_id = "", $id = "", $type, $amount, $message = 'Used against Order Placement', $table_name, $status = 1)
    {
        if ($table_name == 'seller_wallet_transactions') {
            $data = array(
                'order_id' => $order_id,
                'order_item_id' => $order_item_id,
                'seller_id' => $id,
                'type' => $type,
                'amount' => $amount,
                'message' => $message,
                'status' => $status
            );
        } else if ($table_name == 'wallet_transactions') {
            $data = array(
                'order_id' => $order_id,
                'order_item_id' => $order_item_id,
                'user_id' => $id,
                'type' => $type,
                'amount' => $amount,
                'message' => $message,
                'status' => $status
            );
        } else {
            $data = array(
                'order_id' => $order_id,
                'order_item_id' => $order_item_id,
                'user_id' => $id,
                'type' => $type,
                'amount' => $amount,
                'message' => $message,
                'status' => $status
            );
        }
        if ($this->db->insert($table_name, $data)) {
            if ($table_name == 'users') {
                $result = $this->send_order_update_notification($id, "Wallet Transaction", $message, 'wallet_transaction', 0);
            }
            $data1 = $this->db->getResult();
            return $data1[0];
        } else {
            return false;
        }
        // print_r($result);
    }

    public function update_order_item_status($order_item_id, $order_id, $status)
    {
        $data = array('update_order_item_status' => '1', 'order_item_id' => $order_item_id, 'status' => $status, 'order_id' => $order_id, 'ajaxCall' => 1);
        // print_r($data);

        $jwt_token = generate_token();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $jwt_token"]);
        curl_setopt($ch, CURLOPT_URL, DOMAIN_URL . "api-firebase/order-process.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $response = curl_exec($ch);
        $header_info = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        curl_close($ch);
        return $response;
    }

    public function validate_promo_code($user_id, $promo_code, $total)
    {
        $sql = "select * from promo_codes where promo_code='" . $promo_code . "'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (empty($res)) {
            $response['error'] = true;
            $response['message'] = "Invalid promo code.";
            return $response;
            exit();
        }
        if ($res[0]['status'] == 0) {
            $response['error'] = true;
            $response['message'] = "This promo code is either expired / invalid.";
            return $response;
            exit();
        }

        $sql = "select id from users where id='" . $user_id . "'";
        $this->db->sql($sql);
        $res_user = $this->db->getResult();
        if (empty($res_user)) {
            $response['error'] = true;
            $response['message'] = "Invalid user data.";
            return $response;
            exit();
        }

        $start_date = $res[0]['start_date'];
        $end_date = $res[0]['end_date'];
        $date = date('Y-m-d h:i:s a');

        if ($date < $start_date) {
            $response['error'] = true;
            $response['message'] = "This promo code can't be used before " . date('d-m-Y', strtotime($start_date)) . "";
            return $response;
            exit();
        }
        if ($date > $end_date) {
            $response['error'] = true;
            $response['message'] = "This promo code can't be used after " . date('d-m-Y', strtotime($end_date)) . "";
            return $response;
            exit();
        }
        if ($total < $res[0]['minimum_order_amount']) {
            $response['error'] = true;
            $response['message'] = "This promo code is applicable only for order amount greater than or equal to " . $res[0]['minimum_order_amount'] . "";
            return $response;
            exit();
        }
        //check how many users have used this promo code and no of users used this promo code crossed max users or not
        $sql = "select id from orders where promo_code='" . $promo_code . "' GROUP BY user_id";
        $this->db->sql($sql);
        $res_order = $this->db->numRows();

        if ($res_order >= $res[0]['no_of_users']) {
            $response['error'] = true;
            $response['message'] = "This promo code is applicable only for first " . $res[0]['no_of_users'] . " users.";
            return $response;
            exit();
        }
        //check how many times user have used this promo code and count crossed max limit or not
        if ($res[0]['repeat_usage'] == 1) {
            $sql = "select id from orders where user_id=" . $user_id . " and promo_code='" . $promo_code . "'";
            $this->db->sql($sql);
            $total_usage = $this->db->numRows();
            if ($total_usage >= $res[0]['no_of_repeat_usage']) {
                $response['error'] = true;
                $response['message'] = "This promo code is applicable only for " . $res[0]['no_of_repeat_usage'] . " times.";
                return $response;
                exit();
            }
        }
        //check if repeat usage is not allowed and user have already used this promo code 
        if ($res[0]['repeat_usage'] == 0) {
            $sql = "select id from orders where user_id=" . $user_id . " and promo_code='" . $promo_code . "'";
            $this->db->sql($sql);
            $total_usage = $this->db->numRows();
            if ($total_usage >= 1) {
                $response['error'] = true;
                $response['message'] = "This promo code is applicable only for 1 time.";
                return $response;
                exit();
            }
        }
        if ($res[0]['discount_type'] == 'percentage') {
            $percentage = $res[0]['discount'];
            $discount = $total / 100 * $percentage;
            if ($discount > $res[0]['max_discount_amount']) {
                $discount = $res[0]['max_discount_amount'];
            }
        } else {
            $discount = $res[0]['discount'];
        }
        $discounted_amount = $total - $discount;
        $response['error'] = false;
        $response['message'] = "promo code applied successfully.";
        $response['promo_code'] = $promo_code;
        $response['promo_code_message'] = $res[0]['message'];
        $response['total'] = $total;
        $response['discount'] = "$discount";
        $response['discounted_amount'] = "$discounted_amount";
        return $response;
        exit();
    }
    public function get_settings($variable, $is_json = false)
    {
        if ($variable == 'logo' || $variable == 'Logo') {
            $sql = "select value from `settings` where variable='Logo' OR variable='logo'";
        } else {
            $sql = "SELECT value FROM `settings` WHERE `variable`='$variable'";
        }

        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res) && isset($res[0]['value'])) {
            if ($is_json)
                return json_decode($res[0]['value'], true);
            else
                return $res[0]['value'];
        } else {
            return false;
        }
    }
    public function send_order_update_notification($uid, $title, $message, $type, $id)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //hecking the required params 
            //creating a new push
            /*dynamically getting the domain of the app*/
            $url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $url .= $_SERVER['SERVER_NAME'];
            $url .= $_SERVER['REQUEST_URI'];
            $server_url = dirname($url) . '/';

            $push = null;
            //first check if the push has an image with it
            //if the push don't have an image give null in place of image
            $push = new Push(
                $title,
                $message,
                null,
                $type,
                $id
            );
            //getting the push from push object
            $mPushNotification = $push->getPush();

            //getting the token from database object
            $sql = "SELECT fcm_id FROM users WHERE id = '" . $uid . "'";
            $this->db->sql($sql);
            $res = $this->db->getResult();
            $token = array();
            foreach ($res as $row) {
                array_push($token, $row['fcm_id']);
            }

            //creating firebase class object 
            $firebase = new Firebase();

            //sending push notification and displaying result 
            $res = $firebase->send($token, $mPushNotification);
            // print_r($res);
            $response['error'] = false;
            $response['message'] = "Successfully Send";
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid request';
        }
    }
    public function send_notification_to_delivery_boy($delivery_boy_id, $title, $message, $type, $order_id)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //hecking the required params 
            //creating a new push
            /*dynamically getting the domain of the app*/
            $url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $url .= $_SERVER['SERVER_NAME'];
            $url .= $_SERVER['REQUEST_URI'];
            $server_url = dirname($url) . '/';

            $push = null;
            //first check if the push has an image with it
            //if the push don't have an image give null in place of image
            $push = new Push(
                $title,
                $message,
                null,
                $type,
                $order_id
            );
            //getting the push from push object
            $m_push_notification = $push->getPush();

            //getting the token from database object
            $sql = "SELECT fcm_id FROM delivery_boys WHERE id = '" . $delivery_boy_id . "'";
            $this->db->sql($sql);
            $res = $this->db->getResult();
            $token = array();
            foreach ($res as $row) {
                array_push($token, $row['fcm_id']);
            }

            //creating firebase class object 
            $firebase = new Firebase();

            //sending push notification and displaying result 
            $firebase->send($token, $m_push_notification);
            $response['error'] = false;
            $response['message'] = "Successfully Send";
            //print_r(json_encode($response));
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid request';
            // print_r(json_encode($response));
        }
    }
    public function get_promo_details($promo_code)
    {
        $sql = "SELECT * FROM `promo_codes` WHERE `promo_code`='$promo_code'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res;
        } else {
            return false;
        }
    }
    public function store_return_request($user_id, $order_id, $order_item_id)
    {
        $sql = "select product_variant_id from order_items where id=" . $order_item_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        $pv_id = $res[0]['product_variant_id'];
        $sql = "select product_id from product_variant where id=" . $pv_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();

        $data = array(
            'user_id' => $user_id,
            'order_id' => $order_id,
            'order_item_id' => $order_item_id,
            'product_id' => $res[0]['product_id'],
            'product_variant_id' => $pv_id
        );
        $this->db->insert('return_requests', $data);
        return $this->db->getResult()[0];
    }
    public function get_role($id)
    {
        $sql = "SELECT role FROM admin WHERE id=" . $id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res) && isset($res[0]['role'])) {
            return $res[0]['role'];
        } else {
            return 0;
        }
    }
    public function get_permissions($id)
    {
        $sql = "SELECT permissions FROM admin WHERE id=" . $id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res) && isset($res[0]['permissions'])) {
            return json_decode($res[0]['permissions'], true);
        } else {
            return 0;
        }
    }

    public function add_delivery_boy_commission($id, $type, $amount, $message, $status = "SUCCESS")
    {
        $balance = $this->get_balance($id);
        $data = array(
            'delivery_boy_id' => $id,
            'type' => $type,
            'opening_balance' => $balance,
            'closing_balance' => $balance + $amount,
            'amount' => $amount,
            'message' => $message,
            'status' => $status
        );
        $this->db->insert('fund_transfers', $data);
        $result = $this->db->getResult()[0];
        return (!empty($result)) ? $result : "0";
    }

    public function store_delivery_boy_notification($delivery_boy_id, $order_item_id, $title, $message, $type)
    {
        $data = array(
            'delivery_boy_id' => $delivery_boy_id,
            'order_item_id' => $order_item_id,
            'title' => $title,
            'message' => $message,
            'type' => $type
        );
        $this->db->insert('delivery_boy_notifications', $data);
        return $this->db->getResult()[0];
    }
    public function is_item_available_in_user_cart($user_id, $product_variant_id = "")
    {
        $sql = "SELECT id FROM cart WHERE user_id=" . $user_id;
        $sql .= !empty($product_variant_id) ? " AND product_variant_id=" . $product_variant_id : "";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }
    public function is_item_available($product_id, $product_variant_id)
    {
        $sql = "SELECT id FROM product_variant WHERE product_id=" . $product_id . " AND id=" . $product_variant_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            $sql = "SELECT id FROM products  WHERE status = 1  AND id=$product_id";
            $this->db->sql($sql);
            $res = $this->db->getResult();
            if (!empty($res)) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
    public function time_slot_config()
    {
        $sql = "SELECT value FROM settings WHERE `variable`='time_slot_config'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return json_decode($res[0]['value'], true);
        } else {
            return false;
        }
    }

    public function is_address_exists($id = "", $user_id = "")
    {
        $sql = "SELECT id FROM user_addresses WHERE ";
        $sql .= !empty($id) ? "id=$id" : "user_id=$user_id";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function is_user_or_dboy_exists($type, $type_id)
    {
        // $type1 = $type == 'user' ? 'users' : 'delivery_boys';
        $sql = "SELECT id FROM $type WHERE id=" . $type_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function get_user_or_delivery_boy_balance($type, $type_id)
    {
        // $type1 = $type == 'user' ? 'users' : 'delivery_boys';
        $sql = "SELECT balance FROM $type WHERE id=" . $type_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['balance'];
        } else {
            return false;
        }
    }
    public function store_withdrawal_request($type, $type_id, $amount, $message)
    {

        $data = array(
            'type' => $type,
            'type_id' => $type_id,
            'amount' => $amount,
            'message' => $message,
        );
        if ($this->db->insert('withdrawal_requests', $data)) {
            return true;
        } else {
            return false;
        }
    }

    public function debit_balance($type, $type_id, $new_balance)
    {
        // $type1 = $type == 'user' ? 'users' : 'delivery_boys';
        $sql = "UPDATE $type SET balance=" . $new_balance . " WHERE id=" . $type_id;
        if ($this->db->sql($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function is_records_exists($type, $type_id, $offset, $limit)
    {
        $offset = empty($offset) ? 0 : $offset;
        $sql = "SELECT * FROM withdrawal_requests WHERE `type`= '" . $type . "' AND `type_id` = " . $type_id . " ORDER BY date_created DESC";
        $sql .= !empty($limit) ? " LIMIT $offset,$limit" : "";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        return $res;
    }

    public function get_product_id_by_variant_id($product_variant_id)
    {
        $sql = "SELECT product_id FROM product_variant WHERE `id`= " . $product_variant_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['product_id'];
        } else {
            return false;
        }
    }
    public function get_variant_id_by_product_id($product_id)
    {
        $sql = "SELECT id FROM product_variant WHERE `product_id`= " . $product_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        return $res[0]['id'];
    }

    public function update_delivery_boy_wallet_balance($balance, $id)
    {
        $data = array(
            'balance' => $balance
        );
        if ($this->db->update('delivery_boys', $data, 'id=' . $id))
            return true;
        else
            return false;
    }

    function low_stock_count($low_stock_limit)
    {
        $sql = "SELECT COUNT(id) as total FROM product_variant WHERE stock < $low_stock_limit AND serve_for='Available'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        foreach ($res as $row)
            return $row['total'];
    }
    function low_stock_count1($low_stock_limit, $id)
    {
        // echo $id;
        $sql = "SELECT COUNT(pv.id) as total FROM `product_variant` pv JOIN products p ON p.id=pv.product_id WHERE pv.stock < $low_stock_limit AND pv.serve_for='Available' AND p.seller_id=$id";
        // echo $sql;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        foreach ($res as $row)
            return $row['total'];
    }

    function sold_out_count()
    {
        $sql = "SELECT COUNT(id) as total FROM product_variant WHERE  serve_for='Sold Out' and stock <= 0";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        foreach ($res as $row)
            return $row['total'];
    }
    function sold_out_count1($id)
    {
        $sql1 = "SELECT COUNT(pv.id) as total FROM product_variant pv JOIN products p ON p.id=pv.product_id WHERE pv.serve_for='Sold Out' and pv.stock <= 0 AND p.seller_id=$id";
        $this->db->sql($sql1);
        $res = $this->db->getResult();
        foreach ($res as $row)
            return $row['total'];
    }

    public function is_product_set_as_rating($product_id)
    {
        // $sql = "select product_rating from category "
        $sql = "SELECT p.id,c.name FROM `products` p join category c on c.id=p.category_id where p.id=$product_id and c.product_rating=1";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function is_user_exists($user_id)
    {
        $sql = "SELECT id FROM users WHERE id=" . $user_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function is_product_available($product_id)
    {
        $sql = "SELECT id FROM products WHERE id=" . $product_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function is_product_added_as_favorite($user_id, $product_id)
    {
        $sql = "SELECT id FROM favorites WHERE product_id=" . $product_id . " AND user_id=" . $user_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function validate_email($email, $table = '')
    {
        if ($table == 'seller') {
            $sql = "SELECT email FROM `seller` WHERE email='" . $email . "'";
        } else {

            $sql = "SELECT email FROM `admin` WHERE email='" . $email . "'";
        }
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['email'];
        } else {
            return 0;
        }
    }

    public function update_forgot_password_code($email, $code, $table = '')
    {
        if ($table == 'seller') {
            $sql = "UPDATE seller set forgot_password_code = '" . $code . "' WHERE email='" . $email . "'";
        } else {
            $sql = "UPDATE admin set forgot_password_code = '" . $code . "' WHERE email='" . $email . "'";
        }
        if ($this->db->sql($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function validate_code($code, $table = '')
    {
        if ($table == 'seller') {
            $sql = "SELECT forgot_password_code FROM `seller` WHERE forgot_password_code='" . $code . "'";
        } else {
            $sql = "SELECT forgot_password_code FROM `admin` WHERE forgot_password_code='" . $code . "'";
        }
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function get_user($code, $table = '')
    {
        if ($table == 'seller') {
            $sql = "SELECT name,email FROM `seller` WHERE forgot_password_code='" . $code . "'";
        } else {
            $sql = "SELECT username,email FROM `admin` WHERE forgot_password_code='" . $code . "'";
        }
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res;
        } else {
            return 0;
        }
    }

    public function update_password($code, $password_hash, $table = '')
    {
        if ($table == 'seller') {
            $sql = "UPDATE seller set password = '" . $password_hash . "' WHERE forgot_password_code='" . $code . "'";
        } else {
            $sql = "UPDATE admin set password = '" . $password_hash . "' WHERE forgot_password_code='" . $code . "'";
        }
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res;
        } else {
            return 0;
        }
    }

    public function is_return_request_exists($user_id, $order_item_id)
    {
        $sql = "SELECT id FROM return_requests WHERE user_id = '" . $user_id . "' AND order_item_id = '" . $order_item_id . "'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function get_last_inserted_id($table)
    {
        $sql = "SELECT MAX(id) as id FROM $table";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['id'];
        } else {
            return 0;
        }
    }

    public function is_product_cancellable($order_item_id)
    {
        $sql = "SELECT product_variant_id,active_status FROM order_items WHERE id = " . $order_item_id;
        $this->db->sql($sql);
        $result = $this->db->getResult();
        $sql = "SELECT p.cancelable_status,p.till_status FROM products p JOIN product_variant pv ON p.id=pv.product_id WHERE pv.id=" . $result[0]['product_variant_id'];
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if ($res[0]['cancelable_status'] == 1) {
            if ($res[0]['till_status'] == 'received' && ($result[0]['active_status'] != 'awaiting_payment' &&  $result[0]['active_status'] != 'received')) {
                $response['error'] = true;
                $response['till_status_error'] = true;
                $response['cancellable_status_error'] = false;
                $response['message'] = 'Sorry this item is only cancelable till status ' . $res[0]['till_status'] . '!';
            } elseif ($res[0]['till_status'] == 'processed' && ($result[0]['active_status'] != 'awaiting_payment' &&  $result[0]['active_status'] != 'received' && $result[0]['active_status'] != 'processed')) {
                $response['error'] = true;
                $response['till_status_error'] = true;
                $response['cancellable_status_error'] = false;
                $response['message'] = 'Sorry this item is only cancelable till status ' . $res[0]['till_status'] . '!';
            } elseif ($res[0]['till_status'] == 'shipped' && ($result[0]['active_status'] != 'awaiting_payment' && $result[0]['active_status'] != 'received' && $result[0]['active_status'] != 'processed' && $result[0]['active_status'] != 'shipped')) {
                $response['error'] = true;
                $response['till_status_error'] = true;
                $response['cancellable_status_error'] = false;
                $response['message'] = 'Sorry this item is only cancelable till status ' . $res[0]['till_status'] . '!';
            } else {
                $response['error'] = false;
                $response['till_status_error'] = false;
                $response['cancellable_status_error'] = false;
                $response['message'] = 'Item Cancellation criteria matched!';
            }
        } else {
            $response['error'] = true;
            $response['cancellable_status_error'] = true;
            $response['till_status_error'] = true;
            $response['message'] = 'Sorry this item is not cancelable!';
        }
        return $response;
    }

    public function is_product_returnable($order_item_id)
    {
        $sql = "SELECT product_variant_id FROM order_items WHERE id = " . $order_item_id;
        $this->db->sql($sql);
        $result = $this->db->getResult();

        $sql = "SELECT p.return_status FROM products p JOIN product_variant pv ON p.id=pv.product_id WHERE pv.id=" . $result[0]['product_variant_id'];
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if ($res[0]['return_status'] == 1) {
            $response['error'] = false;
            $response['return_status_error'] = false;
            $response['message'] = 'Item return criteria matched!';
        } else {
            $response['error'] = true;
            $response['return_status_error'] = true;
            $response['message'] = 'Sorry this item is not returnable!';
        }

        return $response;
    }

    public function remove_other_addresses_from_default($user_id)
    {
        $sql = "UPDATE user_addresses SET is_default = 0 WHERE user_id = " . $user_id;
        $this->db->sql($sql);
    }

    public function verifyTransaction($data)
    {
        global $paypalUrl;

        $req = 'cmd=_notify-validate';
        foreach ($data as $key => $value) {
            $value = urlencode(stripslashes($value));
            $value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i', '${1}%0D%0A${3}', $value); // IPN fix
            $req .= "&$key=$value";
        }
        $ch = curl_init($paypalUrl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        $res = curl_exec($ch);

        if (!$res) {
            $errno = curl_errno($ch);
            $errstr = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: [$errno] $errstr");
        }

        $info = curl_getinfo($ch);

        // Check the http response
        $httpCode = $info['http_code'];
        if ($httpCode != 200) {
            throw new Exception("PayPal responded with http code $httpCode");
        }

        curl_close($ch);

        return $res === 'VERIFIED';
    }
    public function checkTxnid($txnid)
    {
        $txnid = $this->db->escapeString($txnid);
        $sql = 'SELECT * FROM `payments` WHERE txnid = \'' . $txnid . '\'';
        $result = $this->db->getResult();
        return !$this->db->numRows();;
    }
    public function get_data($columns = [], $where, $table, $offset = '', $limit = '', $sort = '', $order = '')
    {
        $sql = "select ";
        if (!empty($columns)) {
            $columns = implode(",", $columns);
            $sql .= " $columns from ";
        } else {
            $sql .= " * from ";
        }
        $where1 = !empty($where) ? " WHERE $where" : "";
        $limit1 = $offset != '' && !empty($limit) ? "LIMIT " . $offset . "," . $limit : '';
        $order_by = !empty($sort) && !empty($order) ? "ORDER BY " . $sort . " " . $order  : '';

        $sql .= " `$table` $where1 $order_by $limit1";
        // echo $sql;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        return $res;
    }
    public function update_order_status($id, $order_item_id, $status, $delivery_boy_id = 0)
    {
        $data = array('update_order_status' => '1', 'order_id' => $id, 'status' => $status, 'order_item_id' => $order_item_id, 'delivery_boy_id' => $delivery_boy_id, 'ajaxCall' => 1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, DOMAIN_URL . "api-firebase/order-process.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    public function update_bulk_order_items($order_items, $status, $delivery_boy_id = 0)
    {
        $data = array('update_order_items' => '1', 'status' => $status, 'order_items' => $order_items, 'delivery_boy_id' => $delivery_boy_id, 'ajaxCall' => 1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, DOMAIN_URL . "api-firebase/order-process.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function verify_paystack_transaction($reference, $email, $amount)
    {
        $payment_methods = $this->get_settings('payment_methods', true);
        //The parameter after verify/ is the transaction reference to be verified
        $url = 'https://api.paystack.co/transaction/verify/' . $reference;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Authorization: Bearer ' . $payment_methods['paystack_secret_key']
            ]
        );

        //send request
        $request = curl_exec($ch);
        //close connection
        curl_close($ch);
        //declare an array that will contain the result 
        $result = array();

        if ($request) {
            $result = json_decode($request, true);
        }

        if ($result['status'] == true) {

            if (array_key_exists('data', $result) && array_key_exists('status', $result['data']) && ($result['data']['status'] === 'success')) {
                if ($result['data']['customer']['email'] == $email && $result['data']['amount'] == $amount) {
                    $response['error'] = false;
                    $response['message'] = "Transaction verified successfully.";
                    $response['status'] = $result['data']['status'];
                } else {
                    $response['error'] = true;
                    $response['message'] = "Transaction verified but does not belong to specified customer or invalid amount sent.";
                    $response['status'] = $result['data']['status'];
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Transaction was unsuccessful. try again";
                $response['status'] = $result['data']['status'];
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Could not initiate verification. " . $result['message'];
            $response['status'] = "failed";
        }
        return $response;
    }
    public function get_payment_methods()
    {
        $sql = "SELECT value FROM settings WHERE `variable`='payment_methods'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return json_decode($res[0]['value'], true);
        } else {
            return false;
        }
    }
    public function get_order_item_by_order_id($id)
    {
        $sql = "SELECT * FROM `order_items` where order_id=$id";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res;
        } else {
            return false;
        }
    }
    public function add_wallet_balance($order_id, $user_id, $amount, $type, $message)
    {
        $data = array('add_wallet_balance' => '1', 'user_id' => $user_id, 'order_id' => $order_id, 'amount' => $amount, 'type' => $type, 'message' => $message, 'ajaxCall' => 1);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, DOMAIN_URL . "api-firebase/get-user-transactions.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function send_notification_to_admin($title, $message, $type, $order_id)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            /*dynamically getting the domain of the app*/
            $url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $url .= $_SERVER['SERVER_NAME'];
            $url .= $_SERVER['REQUEST_URI'];
            $server_url = dirname($url) . '/';
            $push = null;
            $push = new Push(
                $title,
                $message,
                "",
                $type,
                $order_id
            );
            $m_push_notification = $push->getPush();
            $sql = "SELECT fcm_id FROM admin";
            $this->db->sql($sql);
            $res = $this->db->getResult();
            $token = array();
            foreach ($res as $row) {
                array_push($token, $row['fcm_id']);
            }
            //creating firebase class object 
            $firebase = new Firebase();
            //sending push notification and displaying result 
            $firebase->send($token, $m_push_notification);
            $response['error'] = false;
            $response['message'] = "Successfully Send";
            //print_r(json_encode($response));
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid request';
        }
    }

    public function add_seller_wallet_transaction($order_id = "", $order_item_id, $seller_id, $type, $amount, $message = 'Used against Order Placement', $status = 1)
    {
        // `order_id`, `order_item_id`, `seller_id`, `type`, `amount`, `message`, `status`
        $data = array(
            'order_id' => $order_id,
            'order_item_id' => $order_item_id,
            'seller_id' => $seller_id,
            'type' => $type,
            'amount' => $amount,
            'message' => $message,
            'status' => $status
        );
        $this->db->insert('seller_wallet_transactions', $data);
        $data1 = $this->db->getResult();
        // $result = $this->send_order_update_notification($seller_id, "Wallet Transaction", $message, 'wallet_transaction', 0);
        // print_r($result);
        return $data1[0];
    }

    function replaceArrayKeys($array)
    {
        $replacedKeys = str_replace('-', '_', array_keys($array));
        return array_combine($replacedKeys, $array);
    }

    public function validate_image($file, $is_image = true)
    {
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $file['tmp_name']);
        } else if (function_exists('mime_content_type')) {
            $type = mime_content_type($file['tmp_name']);
        } else {
            $type = $file['type'];
        }
        $type = strtolower($type);
        if ($is_image == false) {
            if (in_array($type, array('text/plain', 'application/csv', 'application/vnd.ms-excel', 'text/csv'))) {
                return true;
            } else {
                return false;
            }
        } else {
            if (in_array($type, array('image/jpg', 'image/jpeg', 'image/gif', 'image/png', 'application/octet-stream'))) {
                return true;
            } else {
                return false;
            }
        }
    }
    public function validate_other_images($tmp_name, $type)
    {
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type1 = finfo_file($finfo, $tmp_name);
        } else if (function_exists('mime_content_type')) {
            $type1 = mime_content_type($tmp_name);
        } else {
            $type1 = $type;
        }
        if (in_array($type1, array('image/jpg', 'image/jpeg', 'image/gif', 'image/png', 'application/octet-stream'))) {
            return true;
        } else {
            return false;
        }
    }

    public function is_order_item_cancelled($order_item_id)
    {
        $sql = "SELECT COUNT(id) as cancelled FROM `order_items` WHERE id=" . $order_item_id . " && (active_status LIKE '%cancelled%' OR active_status LIKE '%returned%')";
        $this->db->sql($sql);
        $res_cancelled = $this->db->getResult();
        if ($res_cancelled[0]['cancelled'] > 0) {
            return true;
        } else {
            return false;
        }
    }
    public function is_order_item_returned($active_status, $postStatus)
    {
        if ($active_status != 'delivered' && $postStatus == 'returned') {
            return true;
        } else {
            return false;
        }
    }
    public function cancel_order_item($id, $order_item_id)
    {
        $res_order = $this->get_data($columns = ['final_total', 'total', 'user_id', 'payment_method', 'wallet_balance', 'delivery_charge', 'tax_amount', 'status', 'area_id', 'promo_discount'], 'id=' . $id, 'orders');

        $sql = 'SELECT oi.*,oi.`product_variant_id`,oi.`quantity`,oi.`discounted_price`,oi.`price`,pv.`product_id`,pv.`type`,pv.`stock`,pv.`stock_unit_id`,pv.`measurement`,pv.`measurement_unit_id` FROM `order_items` oi join `product_variant` pv on pv.id = oi.product_variant_id WHERE oi.`id`=' . $order_item_id;
        $this->db->sql($sql);
        $res_oi = $this->db->getResult();
        $price = ($res_oi[0]['discounted_price'] == 0) ? ($res_oi[0]['price'] * $res_oi[0]['quantity']) + ($res_oi[0]['tax_amount'] * $res_oi[0]['quantity'])  : ($res_oi[0]['discounted_price'] * $res_oi[0]['quantity'])  + ($res_oi[0]['tax_amount'] * $res_oi[0]['quantity']);
        $total = $res_order[0]['total'];
        $delivery_charge = $res_order[0]['delivery_charge'];
        $final_total = $res_order[0]['final_total'];
        if ($total - $price >= 0) {
            $sql_total = "update orders set total=$total-$price where id=" . $id;
            $this->db->sql($sql_total);
        }

        $config = $this->get_configurations();
        $min_amount = $config['min_amount'];

        if ((isset($config['area-wise-delivery-charge']) && !empty($config['area-wise-delivery-charge']))) {
            $min_amount = $this->get_data($columns = ['minimum_free_delivery_order_amount', 'delivery_charges'], "id=" . $res_order[0]['area_id'], 'area');
            $sql = "select minimum_free_delivery_order_amount,delivery_charges from area where id=" . $res_order[0]['area_id'];
            $this->db->sql($sql);
            $result = $this->db->getResult();
            if (isset($result[0]['minimum_free_delivery_order_amount']) && !empty($result[0]['minimum_free_delivery_order_amount'])) {
                $min_amount = $result[0]['minimum_free_delivery_order_amount'];
                $dchrg = $result[0]['delivery_charges'];
            }
        }
        $res_total = $this->get_data($columns = ['total'], "id=" . $id, 'orders');
        $total = $res_total[0]['total'];

        if ($total < $min_amount) { // $config['min_amount'] = Minimum Amount for Free Delivery
            if ($delivery_charge == 0) {

                $sql_delivery_chrg = "update orders set delivery_charge=$dchrg where id=" . $id;
                $this->db->sql($sql_delivery_chrg);
                $sql_final_total = "update orders set final_total=$final_total-$price+$dchrg where id=" . $id;
            } else {
                $sql_final_total = "update orders set final_total=$final_total-$price where id=" . $id;
            }
            $this->db->sql($sql_final_total);
        } else {
            $sql_final_total = "update orders set final_total=$final_total-$price where id=" . $id;
        }


        if ($res_order[0]['wallet_balance'] != 0  && strtolower($res_order[0]['payment_method']) == 'wallet') {
            $sql_final_total = "update orders set final_total=0 where id=" . $id;
        }
        if ($this->db->sql($sql_final_total)) {
            if (strtolower($res_order[0]['payment_method']) != 'cod') {
                /* update user's wallet */
                $user_id = $res_order[0]['user_id'];
                $total_amount = ($res_oi[0]['sub_total'] + $res_order[0]['delivery_charge']) - $res_order[0]['promo_discount'];
                $user_wallet_balance = $this->get_wallet_balance($user_id, 'users');
                $new_balance = $user_wallet_balance + $total_amount;
                $this->update_wallet_balance($new_balance, $user_id, 'users');
                $wallet_txn_id = $this->add_wallet_transaction($id, $order_item_id, $user_id, 'credit', $total_amount, 'Balance credited against item cancellation...', 'wallet_transactions');
            } else {
                if ($res_order[0]['wallet_balance'] != 0) {
                    if ($res_order[0]['wallet_balance'] >= $res_oi[0]['sub_total']) {
                        $returnable_amount = $res_oi[0]['sub_total'];
                        $amount = $res_order[0]['wallet_balance'] - $returnable_amount;
                        $sql_total = "update orders set wallet_balance=" . $amount . " where id=" . $id;
                        $user_id = $res_order[0]['user_id'];
                        $user_wallet_balance = $this->get_wallet_balance($user_id, 'users');
                        $new_balance = ($user_wallet_balance + $returnable_amount);
                        $this->update_wallet_balance($new_balance, $user_id, 'users');
                        $wallet_txn_id = $this->add_wallet_transaction($id, $order_item_id, $user_id, 'credit', $returnable_amount, 'Balance credited against item cancellation!!', 'wallet_transactions');
                        $this->db->sql($sql_total);
                        $sql_final_total = "update orders set final_total=final_total+$returnable_amount where id=" . $id;
                        $this->db->sql($sql_final_total);
                    } else {
                        $returnable_amount = $res_order[0]['wallet_balance'];
                        $user_id = $res_order[0]['user_id'];
                        $user_wallet_balance = $this->get_wallet_balance($user_id, 'users');
                        $new_balance = ($user_wallet_balance + $returnable_amount);
                        $this->update_wallet_balance($new_balance, $user_id, 'users');
                        $wallet_txn_id = $this->add_wallet_transaction($id, $order_item_id, $user_id, 'credit', $returnable_amount, 'Balance credited against item cancellation!!', 'wallet_transactions');
                        $sql_total = "update orders set wallet_balance=0 where id=" . $id;
                        $this->db->sql($sql_total);
                        $sql_final_total = "update orde rs set final_total=final_total+$returnable_amount where id=" . $id;
                        $this->db->sql($sql_final_total);
                    }
                }
            }

            if ($res_oi[0]['type'] == 'packet') {
                $sql = "UPDATE product_variant SET stock = stock + " . $res_oi[0]['quantity'] . " WHERE id='" . $res_oi[0]['product_variant_id'] . "'";
                $this->db->sql($sql);
                $sql = "select stock from product_variant where id=" . $res_oi[0]['product_variant_id'];
                $this->db->sql($sql);
                $res_stock = $this->db->getResult();
                if ($res_stock[0]['stock'] > 0) {
                    $sql = "UPDATE product_variant set serve_for='Available' WHERE id='" . $res_oi[0]['product_variant_id'] . "'";
                    $this->db->sql($sql);
                }
            } else {
                /* When product type is loose */
                if ($res_oi[0]['measurement_unit_id'] != $res_oi[0]['stock_unit_id']) {
                    $stock = $this->convert_to_parent($res_oi[0]['measurement'], $res_oi[0]['measurement_unit_id']);
                    $stock = $stock * $res_oi[0]['quantity'];
                    $sql = "UPDATE product_variant SET stock = stock + " . $stock . " WHERE product_id='" . $res_oi[0]['product_id'] . "'" .  " AND id='" . $res_oi[0]['product_variant_id'] . "'";
                    $this->db->sql($sql);
                } else {
                    $stock = $res_oi[0]['measurement'] * $res_oi[0]['quantity'];
                    $sql = "UPDATE product_variant SET stock = stock + " . $stock . " WHERE product_id='" . $res_oi[0]['product_id'] . "'" .  " AND id='" . $res_oi[0]['product_variant_id'] . "'";
                    $this->db->sql($sql);
                }
            }
            if ($total == 0) {
                $sql = "update orders set delivery_charge=0,tax_amount=0,tax_percentage=0,final_total=0 where id=" . $id;
                if ($this->db->sql($sql)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function get_user_address($address_id)
    {

        $address_data = $this->get_data($columns = ['area', 'city', 'pincode', 'mobile', 'latitude', 'longitude', 'address', 'pincode_id', 'area_id', 'landmark', 'state', 'country'], "id=" . $address_id, 'user_addresses');

        if (($address_data[0]['pincode_id'] == "" && $address_data[0]['area_id'] == "") && ($address_data[0]['pincode'] == "" && $address_data[0]['area'] == "")) {
            return false;
        }
        if (!empty($address_data)) {
            if (!empty($address_data[0]['pincode_id']) && !empty($address_data[0]['area_id'])) {
                $area = $this->get_data($columns = ['name'], 'id=' . $address_data[0]['area_id'], 'area');
                $sql = "SELECT a.*,c.name as city_name,p.pincode FROM `area` a LEFT JOIN pincodes p on p.id=a.pincode_id LEFT JOIN cities c on c.id=a.city_id where a.id= " . $address_data[0]['area_id'];
                $this->db->sql($sql);
                $res_city = $this->db->getResult();
            }
            $city = ($address_data[0]['city_id'] == 0) ? $address_data[0]['city'] : $res_city[0]['city_name'];
            $area = ($address_data[0]['area_id'] == 0) ? $address_data[0]['area'] : $area[0]['name'];
            $pincode = ($address_data[0]['pincode_id'] == 0) ? $address_data[0]['pincode'] : $res_city[0]['pincode'];
            $user_address = $address_data[0]['address'] . "," . $address_data[0]['landmark'] . "," . $city . "," . $area . "," . $address_data[0]['state'] . "," . $address_data[0]['country'] . "," . "Pincode:" . $pincode;
            $order_data = array('user_address' => $user_address, 'mobile' => $address_data[0]['mobile'], 'latitude' => $address_data[0]['latitude'], 'longitude' => $address_data[0]['longitude'], 'pincode_id' => $address_data[0]['pincode_id'], 'area_id' => $address_data[0]['area_id']);
            return $order_data;
        } else {

            return false;
        }
    }
    public function send_notification_to_seller($sid, $title, $message, $type, $id, $ignore_method = 0)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' || $ignore_method == 1) {
            //hecking the required params 
            //creating a new push
            /*dynamically getting the domain of the app*/
            $url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $url .= $_SERVER['SERVER_NAME'];
            $url .= $_SERVER['REQUEST_URI'];
            $server_url = dirname($url) . '/';

            $push = null;
            //first check if the push has an image with it
            //if the push don't have an image give null in place of image
            $push = new Push(
                $title,
                $message,
                null,
                $type,
                $id
            );
            //getting the push from push object
            $mPushNotification = $push->getPush();

            //getting the token from database object
            $sql = "SELECT fcm_id FROM seller WHERE id = '" . $sid . "'";
            $this->db->sql($sql);
            $res = $this->db->getResult();
            $token = array();
            foreach ($res as $row) {
                array_push($token, $row['fcm_id']);
            }

            //creating firebase class object 
            $firebase = new Firebase();

            //sending push notification and displaying result 
            $res = $firebase->send($token, $mPushNotification);
            $response['error'] = false;
            $response['message'] = "Successfully Send";
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid request';
        }
    }
    public function check_for_return_request($product_id = 0, $order_id = 0)
    {
        if (!empty($product_id)) {
            $sql_i = "SELECT id,status FROM `return_requests` where product_id=" . $product_id;
            $this->db->sql($sql_i);
            $return_res = $this->db->getResult();
            $status = array();
            if (!empty($return_res)) {
                foreach ($return_res as $row) {
                    if ($row['status'] == 0) {
                        array_push($status, "1");
                    } else {
                        array_push($status, "2");
                    }
                }
                if (in_array("1", $status)) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        } else {
            $sql_i = "SELECT id,status FROM `return_requests` where order_id=" . $order_id;
            $this->db->sql($sql_i);
            $return_res = $this->db->getResult();
            $status = array();
            if (!empty($return_res)) {
                foreach ($return_res as $row) {
                    if ($row['status'] == 0) {
                        array_push($status, "1");
                    } else {
                        array_push($status, "2");
                    }
                }
                if (in_array("1", $status)) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }
    }

    public function delete_product($product_id)
    {
        if ($this->check_for_return_request($product_id, 0)) {

            $sql = "SELECT * FROM `sections` where find_in_set($product_id,product_ids)";
            $this->db->sql($sql);
            $section = $this->db->getResult();
            foreach ($section as $row) {
                $product_ids = explode(',', $row['product_ids']);
                if (($key = array_search($product_id, $product_ids)) !== false) {
                    unset($product_ids[$key]);
                }

                if (!empty($product_ids)) {
                    $product_ids = implode(',', $product_ids);

                    $sql = "UPDATE `sections` SET `product_ids` = '$product_ids' WHERE id=" . $row['id'];
                    $this->db->sql($sql);
                } else {
                    $sql = "DELETE FROM sections WHERE id=" . $row['id'];
                    $this->db->sql($sql);
                }
            }

            $sql_query = "DELETE FROM product_variant WHERE product_id=" . $product_id;
            $this->db->sql($sql_query);
            $sql_query = "DELETE FROM cart WHERE product_id = " . $product_id;
            $this->db->sql($sql_query);

            $sql = "SELECT count(id) as total from product_variant where product_id=" . $product_id;
            $this->db->sql($sql);
            $total = $this->db->getResult();
            // get image file from menu table
            if ($total[0]['total'] == 0) {
                $sql_query = "SELECT image FROM products WHERE id =" . $product_id;
                $this->db->sql($sql_query);
                $res = $this->db->getResult();
                unlink("../" . $res[0]['image']);

                $sql_query = "SELECT other_images FROM products WHERE id =" . $product_id;
                $this->db->sql($sql_query);
                $res = $this->db->getResult();
                if (!empty($res[0]['other_images'])) {
                    $other_images = json_decode($res[0]['other_images']);
                    foreach ($other_images as $other_image) {
                        unlink("../" . $other_image);
                    }
                }

                $sql_query = "DELETE FROM products WHERE id =" . $product_id;
                if ($this->db->sql($sql_query)) {
                    $sql_query = "DELETE FROM favorites WHERE product_id = " . $product_id;
                    $this->db->sql($sql_query);
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }
    public function get_seller_permission($seller_id, $permission)
    {
        $sql = "SELECT " . $permission . " from seller where id=$seller_id";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            if ($res[0][$permission] == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public function get_seller_balance($seller_id)
    {
        $sql = "SELECT balance FROM seller WHERE id=" . $seller_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['balance'];
        } else {
            return false;
        }
    }


    public function delete_order($order_id)
    {
        if ($this->check_for_return_request(0, $order_id)) {
            $sql_query = "DELETE FROM orders WHERE id =" . $order_id;
            if ($this->db->sql($sql_query)) {
                $sql = "DELETE FROM order_items WHERE order_id =" . $order_id;
                $this->db->sql($sql);
                $sql_return = "DELETE FROM return_requests WHERE order_id =" . $order_id;
                $this->db->sql($sql_return);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public function delete_order_item($order_item_id)
    {

        $sql_i = "SELECT id,status,order_id FROM `return_requests` where order_item_id=" . $order_item_id;
        $this->db->sql($sql_i);
        $return_res = $this->db->getResult();
        if (!empty($return_res)) {
            if ($return_res[0]['status'] == 1) {
                $sql = "DELETE FROM order_items WHERE id =" . $order_item_id;
                $this->db->sql($sql);
                $sql_return = "DELETE FROM return_requests WHERE order_item_id =" . $order_item_id;
                $this->db->sql($sql_return);
                $sql_i = "SELECT id FROM `order_items` where order_id=" . $return_res[0]['order_id'];
                $this->db->sql($sql_i);
                $res_order = $this->db->getResult();
                if (empty($res_order)) {
                    $this->delete_order($return_res[0]['order_id']);
                }
                return true;
            } else {
                return false;
            }
        } else {
            $sql_i = "SELECT order_id FROM `order_items` where id=" . $order_item_id;
            $this->db->sql($sql_i);
            $res_order_id = $this->db->getResult();
            $sql = "DELETE FROM order_items WHERE id =" . $order_item_id;
            if ($this->db->sql($sql)) {
                $sql_i = "SELECT id FROM `order_items` where order_id=" . $res_order_id[0]['order_id'];
                $this->db->sql($sql_i);
                $res_order = $this->db->getResult();
                if (empty($res_order)) {
                    $this->delete_order($res_order_id[0]['order_id']);
                }
                return true;
            } else {
                return false;
            }
        }
    }

    public function select_top_sellers()
    {
        $sql = "SELECT SUM(oi.sub_total) as total,oi.seller_id,s.name as seller_name,s.store_name FROM `order_items` oi JOIN seller s on s.id=oi.seller_id where oi.active_status='delivered' GROUP BY oi.seller_id ORDER BY `total` DESC LIMIT 0,5";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res;
        } else {
            return false;
        }
    }
    public function select_top_categories()
    {
        $sql = "SELECT pv.product_id,pv.id,p.name as p_name,p.category_id,p.seller_id,c.name as cat_name, pv.measurement,oi.product_name,oi.variant_name,SUM(oi.sub_total) as total FROM `order_items` oi join `product_variant` pv ON oi.product_variant_id=pv.id join products p ON pv.product_id=p.id join unit u on pv.measurement_unit_id=u.id JOIN category c ON p.category_id=c.id WHERE oi.date_added > DATE_SUB(NOW(), INTERVAL 1 MONTH) AND oi.active_status='delivered' GROUP BY p.category_id ORDER BY total desc LIMIT 0, 5";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res;
        } else {
            return false;
        }
    }

    public function set_timezone($config)
    {
        $result = false;
        if (isset($config['system_timezone']) && isset($config['system_timezone_gmt']) && $config['system_timezone_gmt'] != "" && $config['system_timezone'] != "") {
            date_default_timezone_set($config['system_timezone']);
            $this->db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
            $result = true;
        } else {
            date_default_timezone_set('Asia/Kolkata');
            $this->db->sql("SET `time_zone` = '+05:30'");
            $result = true;
        }
        return $result;
    }

    public function delete_other_images($pid, $i, $seller_id = "0")
    {
        if ($seller_id > 0) {
            $sql = "SELECT other_images FROM products WHERE id = $pid and seller_id = $seller_id";
            $this->db->sql($sql);
            $res = $this->db->getResult();
            if (empty($res)) {
                return 2;
            }
        }
        $sql = "SELECT other_images FROM products WHERE id =" . $pid;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        foreach ($res as $row)
            $other_images = $row['other_images']; /*get other images json array*/
        $other_images = json_decode($other_images); /*decode from json to array*/
        if ($seller_id > 0) {
            unlink("../../" . $other_images[$i]); /*remove the image from the folder*/
        } else {
            unlink("../" . $other_images[$i]); /*remove the image from the folder*/
        }
        unset($other_images[$i]); /*remove image from the array*/
        $other_images = json_encode(array_values($other_images)); /*convert back to JSON */

        /*update the table*/
        if (empty($other_images) || $other_images == '[]') {
            $sql = "UPDATE `products` set `other_images`='' where id=" . $pid;
        }
        $sql = "UPDATE `products` set `other_images`='" . $other_images . "' where id=" . $pid;
        if ($this->db->sql($sql))
            return 1;
        else
            return 0;
    }

    public function delete_variant($v_id)
    {
        $sql = "SELECT id FROM product_variant WHERE id=" . $v_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();

        $sql_query = "SELECT images FROM product_variant WHERE id =" . $v_id;
        $this->db->sql($sql_query);
        $result = $this->db->getResult();

        foreach ($result as $row)
            $other_images = $row['images']; /*get other images json array*/

        $variant_images = str_replace("'", '"', $other_images);
        $other_images = json_decode($variant_images); /*decode from json to array*/
        foreach ($other_images as $other_image) {
            $image =  '../../' . $other_image;
            file_exists($image) ? unlink('../../' . $other_image) : unlink("../" . $other_image); /*remove the image from the folder*/
        }

        if (!empty($res)) {
            $sql = "DELETE FROM product_variant WHERE id=" . $v_id;
            if ($this->db->sql($sql)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public function get_seller_address($seller_id)
    {
        $res_seller = $this->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $seller_id, 'seller');

        $res_pincode = $this->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
        $res_city = $this->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
        $city_name = (!empty($res_city[0]['name'])) ? $res_city[0]['name'] . " - " : "";
        $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . ", " : "";
        $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . ", " : "";
        $pincode = (!empty($res_seller[0]['pincode_id']) && !empty($res_pincode)) ? $city_name . $res_pincode[0]['pincode'] : "";
        $seller_address = $state  . $street . $pincode;
        if (!empty($seller_address)) {
            return $seller_address;
        } else {
            return false;
        }
    }
    public function add_transaction($order_id = "", $id = "", $type = '', $amount, $message = '', $date = '', $status = 1)
    {
        $date = !empty($date) ? $date : date('Y-m-d H:i:s');
        $data = array(
            'order_id' => $order_id,
            'user_id' => $id,
            'type' => $type,
            'amount' => $amount,
            'message' => $message,
            'transaction_date' => $date,
            'status' => $status
        );
        $this->db->insert('transactions', $data);
        $result = $this->db->getResult()[0];
        return (!empty($result)) ? $result : "0";
    }
    public function get_delivery_charge($address_id, $total = 0)
    {
        $total = str_replace(',', '', $total);
        $config = $this->get_configurations();
        $address = $this->get_data(['area_id'], 'id=' . $address_id, 'user_addresses');
        $min_amount = $config['min_amount'];
        $delivery_charge = $config['delivery_charge'];
        if ((isset($config['area-wise-delivery-charge']) && !empty($config['area-wise-delivery-charge']))) {
            if (isset($address[0]['area_id']) && !empty($address[0]['area_id'])) {
                $area = $this->get_data(['delivery_charges', 'minimum_free_delivery_order_amount'], 'id=' . $address[0]['area_id'], 'area');
                if (isset($area[0]['minimum_free_delivery_order_amount'])) {
                    $min_amount = $area[0]['minimum_free_delivery_order_amount'];
                    $delivery_charge = $area[0]['delivery_charges'];
                }
            }
        }
        if ($total < $min_amount || $total == 0) {
            $d_charge = $delivery_charge;
        } else {
            $d_charge = 0;
        }
        return $d_charge;
    }
    public function is_item_available_in_save_for_later($user_id, $product_variant_id = "")
    {
        $sql = "SELECT id FROM cart WHERE user_id=" . $user_id;
        $sql .= !empty($product_variant_id) ? " AND product_variant_id=" . $product_variant_id : "";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return 1;
        } else {
            return 0;
        }
    }
    public function get_item_wise_delivery_charge($product_variant_id, $passed_pincode_id)
    {
        $sql = 'select p.standard_shipping,pv.price,pv.discounted_price from product_variant pv left join products p on p.id=pv.product_id where pv.id=' . $product_variant_id;
        $this->db->sql($sql);
        $res = $this->db->getResult();
    }

    public function delete_variant_images($vid, $i)
    {
        $sql = "SELECT images FROM product_variant WHERE id =" . $vid;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        foreach ($res as $row)
            $other_images = $row['images']; /*get images json array*/

        $variant_images = str_replace("'", '"', $other_images);
        $other_images = json_decode($variant_images); /*decode from json to array*/

        $image =  '../../' . $other_images[$i];
        file_exists($image) ? unlink('../../' . $other_images[$i]) : unlink("../" . $other_images[$i]); /*remove the image from the folder*/
        unset($other_images[$i]); /*remove image from the array*/
        $res_other_images = json_encode(array_values($other_images)); /*convert back to JSON */

        /*update the table*/
        $sql = "UPDATE `product_variant` set `images`='" . $res_other_images . "' where id=" . $vid;
        if ($this->db->sql($sql))
            return 1;
        else
            return 0;
    }

    public function get_offers($position, $section_position = '')
    {
        if (!empty($section_position)) {
            $sql = "SELECT * FROM offers WHERE position = '" . $position . "' AND section_position = '" . $section_position . "' ";
        } else {
            $sql = "SELECT * FROM offers WHERE position = '" . $position . "' ";
        }
        $this->db->sql($sql);
        $res = $this->db->getResult();
        return $res;
    }
    public function process_shiprocket($order_id, $seller_id, $pickup_location, $sub_total, $weight, $height, $breadth, $length, $order_items_ids)
    {

        $fn = new functions();
        $shiprocket = new Shiprocket();
        $order_items_arr = $orders = $order_items = $parcels = array();
        $order_items_ids = implode(',', $order_items_ids);

        $sr_subtotal = $sr_weight = $sr_height = $sr_length = $sr_breadth = 0;
        $sql = 'SELECT oi.id as item_id,oi.quantity,p.seller_id,p.id,pv.weight,p.name,p.standard_shipping,pv.price,p.pickup_location  FROM `order_items` oi left JOIN product_variant pv on oi.product_variant_id=pv.id left join products p on p.id=pv.product_id WHERE  oi.id    in(' . $order_items_ids . ') and oi.order_id=' . $order_id;
        $this->db->sql($sql);
        $order_res = $this->db->getResult();

        $sql = 'SELECT o.user_id,o.payment_method,o.pincode_id,o.area_id,CASE WHEN o.pincode_id=0 THEN (Select ua.pincode from user_addresses ua where ua.id=o.address_id) ELSE (Select pin.pincode from pincodes pin where pin.id=o.pincode_id) END AS pincode,CASE WHEN o.area_id=0 THEN (Select ua.area from user_addresses ua where ua.id=o.address_id) ELSE (Select a.name from area a where a.id=o.area_id) END AS area from orders o where id=' . $order_id;
        $this->db->sql($sql);
        $pincode = $this->db->getResult();

        $sql = 'SELECT (Select u.name from users u where u.id=ua.user_id) as user_name,(Select u.email from users u where u.id=ua.user_id) as email,ua.mobile,ua.address,ua.state,ua.country,(select c.name from cities c where c.id=ua.city_id) as city from user_addresses ua where ua.user_id=' . $pincode[0]['user_id'] . ' AND ua.pincode_id=' . $pincode[0]['pincode_id'] . ' AND ua.area_id=' . $pincode[0]['area_id'];
        $this->db->sql($sql);
        $users_details = $this->db->getResult();
        // $payment_method = (isset($pincode[0]['payment_method']) && !empty($pincode[0]['payment_method']) && $pincode[0]['payment_method'] == "COD") ? 0 : 1;
        $payment_method = (isset($pincode[0]['payment_method']) && !empty($pincode[0]['payment_method']) && ($pincode[0]['payment_method'] == "COD" || $pincode[0]['payment_method'] == "cod")) ? 0 : 1;
        $order_id_create_order = $order_id;
        $index = 0;
        foreach ($order_res as  $items) {
            if ($items['standard_shipping']) {
                $slug = $items['item_id'] . " " . $order_id . " " . $index;
                $order_items[] = array('name' => $items['name'], 'sku' => $fn->slugify($slug), 'units' => $items['quantity'], 'selling_price' => $items['price']);
                $order_id_create_order .= '-' . $items['id'];
                $index++;
            }
        }
        $sql = 'SELECT pin_code from pickup_locations where pickup_location="' . $pickup_location . '"';
        $this->db->sql($sql);
        $pickup_location_pincode = $this->db->getResult();
        $data = array('pickup_location' => $pickup_location_pincode[0]['pin_code'], 'delivery_pincode' => $pincode[0]['pincode'], 'weight' => $weight, 'cod' => ($payment_method == 0) ? '1' : '0');
        $check_deliveribility = $shiprocket->check_serviceability($data);
        $get_currier_id = $this->shiprocket_recomended_data($check_deliveribility);
        $data = array(
            'order_id' => $order_id_create_order,
            'order_date' => date('y-m-d'),
            'pickup_location' => $pickup_location,
            'billing_customer_name' => $users_details[0]['user_name'],
            'billing_last_name' => $users_details[0]['user_name'],
            'billing_address' => $pincode[0]['area'],
            'billing_phone' => $users_details[0]['mobile'],
            'billing_city' => $users_details[0]['city'],
            'billing_pincode' => $pincode[0]['pincode'],
            'billing_state' => $users_details[0]['state'],
            'billing_country' => $users_details[0]['country'],
            'billing_email' => $users_details[0]['email'],
            'shipping_is_billing' => true,
            "order_items" => $order_items,
            'payment_method' => ($payment_method == 0) ? 'COD' : 'prepaid', // change as required
            'sub_total' => $sub_total + $get_currier_id['rate'],
            'length' => $length,
            'breadth' => $breadth,
            'height' => $height,
            'weight' => $weight
        );
        $res = $shiprocket->create_order($data);

        if ($res['status_code'] == 1 || !empty($res['order_id'])) {
            $item_id = 0;
            $shiprocket_order_id = $res['order_id'];
            $shipment_id = $res['shipment_id'];
            $courier_company_id = $get_currier_id['courier_company_id'];
            foreach ($order_res as  $items) {
                if ($items['standard_shipping']) {
                    $item_id = $items['item_id'];
                    $sql = "INSERT INTO `order_trackings` (`order_id`,`order_item_id`,`shiprocket_order_id`,`shipment_id`,`courier_company_id`) VALUES ('$order_id','$item_id','$shiprocket_order_id','$shipment_id','$courier_company_id')";
                    $this->db->sql($sql);
                }
            }
            return $res;
        } elseif ($res['status_code'] != 1 || !empty($res['status_code'])) {
            $res['data'] = $res;
            $res['message'] = $res['message'];
            return $res;
        } else {
            return $res;
        }

        print_r($res);
    }



    // public function return_order($order_item_id)
    // {
    //     $fn = new functions();
    //     $shiprocket = new Shiprocket();
    //     $order_item_data=$this->get_data(['*'], 'id='.$item_id,'order_items');
    //     print_r($order_item_data);

    // }

    public function send_request_for_pickup($shipment_id)
    {
        $shiprocket = new Shiprocket();
        $res = $shiprocket->request_for_pickup($shipment_id);
        if ($res['pickup_status'] == 1) {
            $pickup_scheduled_date = $res['response']['pickup_scheduled_date'];
            $pickup_token_number = $res['response']['pickup_token_number'];
            $status = $res['response']['status'];
            $others = $res['response']['others'];
            $pickup_generated_date = json_encode($res['response']['pickup_generated_date']);
            $data = $res['response']['data'];
            $sql = "UPDATE order_trackings SET `pickup_status`=1 ,`pickup_scheduled_date`='$pickup_scheduled_date',`pickup_token_number`='$pickup_token_number',`status`='$status',`others`='$others',`pickup_generated_date`='$pickup_generated_date',`data`='$data' where shipment_id=$shipment_id ";
            $this->db->sql($sql);
            $sql = 'select pickup_status from order_trackings where shipment_id=' . $shipment_id;
            $this->db->sql($sql);
            $result = $this->db->getResult();
            if ($result[0] == 1) {
                return true;
            }
        } else if ($res['pickup_status'] == 0) {
            $sql = "UPDATE order_trackings SET `pickup_status`=1  where shipment_id=$shipment_id ";
            $this->db->sql($sql);
            return true;
        } else if ($res['status_code'] == 400) {
            $sql = "UPDATE order_trackings SET `pickup_status`=1  where shipment_id=$shipment_id ";
            $this->db->sql($sql);
            $res['pickup_status'] == 1;
            return  true;
        } else {
            return $res;
        }
    }


    public function generate_manifests($shipment_id)
    {
        $shiprocket = new Shiprocket();
        $manifest = $shiprocket->generate_manifests($shipment_id);
        if ($manifest['status'] == '1') {
            $manifest = json_encode($manifest);
            $sql = "UPDATE order_trackings SET manifests=$manifest where shipment_id=$shipment_id";
            if ($this->db->sql($sql)) {
                $error['error'] = false;
                $error['message'] = "manifest generated successfully";
            } else {
                $error['error'] = true;
                $error['message'] = "somethings get wrong";
            }
        } else if (!empty($manifest['already_manifested_shipment_ids'])) {

            $order_id = $this->get_data(['shiprocket_order_id'], "shipment_id=$shipment_id", 'order_trackings');
            $manifest = $shiprocket->print_manifests($order_id[0]['shiprocket_order_id']);
            $manifest = [
                'status' => 1,
                'manifest_url' => $manifest['manifest_url']
            ];
            $manifest = json_encode($manifest);
            $sql = "UPDATE order_trackings SET manifests='$manifest' where shipment_id=$shipment_id";
            if ($this->db->sql($sql)) {
                $error['error'] = false;
                $error['message'] = "manifest generated successfully";
            } else {
                $error['error'] = true;
                $error['message'] = "somethings get wrong";
            }
        } else {
            $error['error'] = true;
            $error['message'] = "somethings get wrong";
        }
        return $error;
    }
    public function generate_labels($shipment_id)
    {
        $shiprocket = new Shiprocket();
        $label = $shiprocket->generate_label($shipment_id);
        if ($label['label_created'] == '1') {
            $label = json_encode($label);
            $sql = "UPDATE order_trackings SET labels='$label' where shipment_id=$shipment_id";
            if ($this->db->sql($sql)) {
                $error['error'] = false;
                $error['message'] = "manifest generated successfully";
            } else {
                $error['error'] = true;
                $error['message'] = "somethings get wrong";
            }
        } else {
            $error['error'] = true;
            $error['message'] = "somethings get wrong";
        }
        return $error;
    }



    public function generate_awb($shipment_id)
    {
        $shiprocket = new Shiprocket();
        $where = 'shipment_id=' . $shipment_id;
        $get_currier_id = $this->get_data(['courier_company_id'], $where, 'order_trackings');
        $courier_company_id = $get_currier_id[0]['courier_company_id'];
        $awb = $shiprocket->generate_awb($shipment_id, $courier_company_id);
        $awb = $shiprocket->generate_awb($shipment_id, $courier_company_id);

        if ($awb['awb_assign_status'] == 1) {
            $awb_code = $this->get_order($shipment_id)['data']['shipments']['awb'];
            $sql = "UPDATE order_trackings SET `awb_code`='$awb_code' where shipment_id=$shipment_id ";
            $this->db->sql($sql);
            $res['error'] = false;
            $res['message'] = "AWB generated succssfully";
            return $res;
        } else if ($awb['status_code'] == 350) {
            $res['error'] = true;
            $res['message'] = $awb['message'];
            return $res;
        } else if ($awb['status_code'] == 400) {
            $res['error'] = true;
            $res['message'] = $awb['message'];
            return $res;
        } else if (!empty($this->get_order($shipment_id))) {
            $awb_code = $this->get_order($shipment_id)['data']['shipments']['awb'];
            $sql = "UPDATE order_trackings SET `awb_code`='$awb_code' where shipment_id=$shipment_id ";
            $this->db->sql($sql);
            $res['error'] = false;
            $res['message'] = "AWB generated succssfully";
            return $res;
        } else if ($awb['awb_assign_status'] == 0) {
            $res['error'] = true;
            $res['message'] = $awb['response']['data']['awb_assign_error'];
            return $res;
        } else {
            $res = $awb['message'];
            return $res;
        }
    }

    public function get_order($shipment_id)
    {

        $shiprocket = new Shiprocket;
        $order_id = $this->get_data(['shiprocket_order_id'], 'shipment_id=' . $shipment_id, 'order_trackings')[0]['shiprocket_order_id'];
        $order_details = $shiprocket->get_order($order_id);
        return $order_details;
    }

    public function get_shipment_details($shipment_id)
    {
        $shiprocket = new Shiprocket;
        $shipment_details = $shiprocket->get_shipment_details($shipment_id);
        return $shipment_details;
    }
    public function track_order($shipment_id)
    {
        $shiprocket = new Shiprocket();
        $track_order = $shiprocket->track_order($shipment_id);
        if ($track_order['tracking_data']['track_status']) {
            return $track_order;
        } else {
            $res['message'] = $track_order['tracking_data']['error'];
            return $res;
        }
    }

    public function shiprocket_recomended_data($shiprocket_data)
    {
        $result = array();
        if (isset($shiprocket_data['data']['recommended_courier_company_id'])) {
            foreach ($shiprocket_data['data']['available_courier_companies'] as  $rd) {
                if ($shiprocket_data['data']['recommended_courier_company_id'] == $rd['courier_company_id']) {
                    $result = $rd;
                    break;
                }
            }
        } else {
            foreach ($shiprocket_data['data']['available_courier_companies'] as  $rd) {
                if ($rd['courier_company_id']) {
                    $result = $rd;
                    break;
                }
            }
        }
        return $result;
    }



    // make shipping parcel of product 
    public function make_shipping_parcels($data)
    {
        // find unique seller from given data
        $seller_id = array_unique(array_column($data, 'seller_id'));
        $parcels = $pickup_locations = $test = $temp = array();
        //store standard shipping weight
        $local_shipping_total_item_weight = 0;
        // store local shipping weight
        foreach ($data as $value) {
            for ($i = 0; $i < count($value['item']); $i++)
                $temp[] = $value['item'][$i];
        }
        $unique_pickup_location = array_unique(array_column($temp, 'pickup_location'));
        $shipping_parcels = array();
        foreach ($data as $product) {
            for ($i = 0; $i < count($product['item']); $i++) {
                if ($product['item'][$i]['standard_shipping'] && !empty($product['item'][$i]['pickup_location'])) {

                    if (!isset($parcels[$product['item'][$i]['pickup_location']][$product['seller_id']]['weight'])) {
                        $parcels[$product['item'][$i]['pickup_location']][$product['seller_id']]['weight'] = 0;
                    }
                    $parcels[$product['item'][$i]['pickup_location']][$product['seller_id']]['weight'] = (isset($parcels[$product['item'][$i]['pickup_location']][$product['seller_id']]['weight']) && !empty($product['item'][$i]['weight'])) ? $parcels[$product['item'][$i]['pickup_location']][$product['seller_id']]['weight'] + ($product['item'][$i]['weight'] * $product['qty']) : $product['item'][$i]['weight'] * $product['qty'];
                }
            }
        }
        return $parcels;
    }


    public function check_parcels_deliveriblity($data, $user_pincode, $is_cod = 0)
    {
        $min_days = $max_days = $delivery_charge_with_cod  = $delivery_charge_without_cod = 0;
        $shiprocket = new Shiprocket;
        /**
         * Parcels
         * Array
         * (
         *    pickup-location-slug-seller_id
         *    [rainbow-textiles-26] => Array
         *        (
         *               seller_id
         *                [26] => Array
         *                (
         *                    [weight] => 0.015
         *                )
         *        )
         * )
         */
        foreach ($data as $pickup_location => $parcels) {
            foreach ($parcels as $seller_id => $parcel) {
                $pin_code = $this->get_data(['pin_code'], "pickup_location='" . $pickup_location . "'", 'pickup_locations');

                $shiprocket_data = array('pickup_location' => $pin_code[0]['pin_code'], 'delivery_pincode' => $user_pincode, 'weight' => $parcel['weight'], 'cod' => 0);
                $shiprocket_data = $shiprocket->check_serviceability($shiprocket_data);
                $shiprocket_data = $this->shiprocket_recomended_data($shiprocket_data);

                $shiprocket_data_with_cod = array('pickup_location' => $pin_code[0]['pin_code'], 'delivery_pincode' => $user_pincode, 'weight' => $parcel['weight'], 'cod' => 1);
                $shiprocket_data_with_cod = $shiprocket->check_serviceability($shiprocket_data_with_cod);
                $shiprocket_data_with_cod = $this->shiprocket_recomended_data($shiprocket_data_with_cod);

                $data[$pickup_location][$seller_id]['pickup_availability'] = $shiprocket_data['pickup_availability'];
                $data[$pickup_location][$seller_id]['courier_name'] = $shiprocket_data['courier_name'];
                $data[$pickup_location][$seller_id]['delivery_charge_with_cod'] = $shiprocket_data_with_cod['rate'];
                $data[$pickup_location][$seller_id]['delivery_charge_without_cod'] = $shiprocket_data['rate'];
                $data[$pickup_location][$seller_id]['estimate_date'] = $shiprocket_data['etd'];
                $data[$pickup_location][$seller_id]['estimate_days'] = $shiprocket_data['estimated_delivery_days'];

                $min_days = (empty($min_days) || $shiprocket_data['estimated_delivery_days'] < $min_days) ? $shiprocket_data['estimated_delivery_days'] : $min_days;
                $max_days = (empty($max_days) || $shiprocket_data['estimated_delivery_days'] > $max_days) ? $shiprocket_data['estimated_delivery_days'] : $max_days;

                $delivery_charge_with_cod += $data[$pickup_location][$seller_id]['delivery_charge_with_cod'];
                $delivery_charge_without_cod += $data[$pickup_location][$seller_id]['delivery_charge_without_cod'];
            }
        }

        $delivery_day = ($min_days == $max_days) ? $min_days : $min_days . '-' . $max_days;
        $shipping_parcels = array('error' => false, 'estimated_delivery_days' => $delivery_day,  'delivery_charge' => 0, 'delivery_charge_with_cod' => round($delivery_charge_with_cod), 'delivery_charge_without_cod' => round($delivery_charge_without_cod), 'data' => $data);
        return $shipping_parcels;
    }


    public function get_taxabled_amount($product_varient_id)
    {
        $sql = "SELECT pv.id,pv.discounted_price,t.percentage,pv.price,CASE when t.percentage != 0 THEN (pv.price+(pv.price*t.percentage)/100) ELSE pv.price END AS taxable_price,CASE when pv.discounted_price !=0 THEN pv.discounted_price+(pv.discounted_price*t.percentage)/100 ELSE pv.price+(pv.price*t.percentage)/100 END as taxable_amount from product_variant pv left JOIN products p on pv.product_id=p.id LEFT JOIN taxes t on t.id=p.tax_id where pv.id=$product_varient_id";
        $this->db->sql($sql);
        $result = $this->db->getResult();
        if (empty($result[0]['percentage']) && $result[0]['discounted_price'] != 0) {
            $result[0]['taxable_amount'] = $result[0]['discounted_price'];
        } else if (empty($result[0]['percentage'])) {
            $result[0]['taxable_amount'] = $result[0]['price'];
        }
        return $result;
    }

    public function get_orders($user_id, $order_id, $limit, $offset)
    {
        $where = !empty($order_id) ? " AND o.id = " . $order_id : "";
        $sql = "select count(o.id) as total from orders o where user_id=" . $user_id . $where;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        $total = $res[0]['total'];
        $sql = "select o.*,obt.message as bank_transfer_message,obt.status as bank_transfer_status,(select name from users u where u.id=o.user_id) as user_name from orders o LEFT JOIN order_bank_transfers obt ON obt.order_id=o.id where user_id=" . $user_id . $where . " ORDER BY date_added DESC LIMIT $offset,$limit";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        $i = 0;
        $j = 0;
        // return false;
        foreach ($res as $row) {

            // echo "meri ek tang nakli hain me hoki ka bohot bada khiladi hun";
            $final_sub_total = 0;
            $sub_total = 0;

            if ($row['discount'] > 0) {
                $discounted_amount = $row['total'] * $row['discount'] / 100;
                $final_total = $row['total'] - $discounted_amount;
                $discount_in_rupees = $row['total'] - $final_total;
            } else {
                $discount_in_rupees = 0;
            }

            $sql_query = "SELECT id,attachment FROM order_bank_transfers WHERE order_id = " . $row['id'];
            $this->db->sql($sql_query);
            $res_attac = $this->db->getResult();

            $myData = array();
            foreach ($res_attac as $item) {
                array_push($myData, ['id' => $item['id'], 'image' => DOMAIN_URL . $item['attachment']]);
            }
            $body1 = json_encode($myData);
            $body = json_decode($body1);

            $res[$i]['attachment'] = $body;

            $res[$i]['discount_rupees'] = "$discount_in_rupees";
            $final_total = ceil($res[$i]['final_total']);
            $res[$i]['final_total'] = "$final_total";
            $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));
            $res[$i]['bank_transfer_message'] = !empty($res[$i]['bank_transfer_message']) ? $res[$i]['bank_transfer_message'] : "";
            $res[$i]['bank_transfer_status'] = !empty($res[$i]['bank_transfer_status']) ? $res[$i]['bank_transfer_status'] : "0";
            $sql = "select oi.*,v.id as variant_id, p.name,p.image,p.manufacturer,p.standard_shipping,p.made_in,p.return_status,p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where order_id=" . $row['id'];
            $this->db->sql($sql);
            $res[$i]['items'] = $this->db->getResult();
            $res[$i]['status'] = json_decode($res[$i]['status']);
            for ($j = 0; $j < count($res[$i]['items']); $j++) {

                // unset($res[$i][$j]['status']);
                if ($res[$i]['items'][$j]['standard_shipping'] == 1) {
                    $res[$i]['items'][$j]['shipping_method'] = 'standard';
                    $res[$i]['status'] = "";
                    $res[$i]['active_status'] = "";
                    $res[$i]['items'][$j]['status'] = "";
                    $order_tracking = $this->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');
                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                        $sub_total += $res[$i]['items'][$j]['sub_total'];
                    }
                    if (!empty($res[$i]['items'][$j]['status'])) {
                        if (count($res[$i]['items'][$j]['status']) > 1) {
                            if (in_array("awaiting_payment", $res[$i]['items'][$j]['status'][0]) && in_array("received", $res[$i]['items'][$j]['status'][1])) {
                                unset($res[$i]['items'][$j]['status'][0]);
                            }
                            $res[$i]['items'][$j]['status'] = array_values($res[$i]['items'][$j]['status']);
                        }
                    } else {
                        $res[$i]['items'][$j]['status'] = array();
                    }

                    $res[$i]['items'][$j]['delivery_boy_id'] = (!empty($res[$i]['items'][$j]['delivery_boy_id'])) ? $res[$i]['items'][$j]['delivery_boy_id'] : "";
                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $this->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $item_details = $this->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
                    $sql = "SELECT id from return_requests where product_variant_id = " . $res[$i]['items'][$j]['variant_id'] . " AND user_id = " . $user_id;
                    $this->db->sql($sql);
                    $return_request = $this->db->getResult();

                    $order_tracking_data = $this->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');
                    if (empty($order_tracking_data)) {
                        $res[$i]['items'][$j]['active_status'] = 'Order not created';
                        $res[$i]['items'][$j]['shipment_id'] = "";
                        $res[$i]['items'][$j]['awb_code'] = "";
                        $res[$i]['items'][$j]['pickup_status'] = "";
                        $res[$i]['items'][$j]['is_canceled'] = "";
                    } else if (!empty($order_tracking_data[0]['shipment_id']) && empty($order_tracking_data[0]['awb_code'])) {
                        $res[$i]['items'][$j]['active_status'] = 'AWb not generated';
                        $res[$i]['items'][$j]['shipment_id'] = $order_tracking_data[0]['shipment_id'];
                        $res[$i]['items'][$j]['awb_code'] = "";
                        $res[$i]['items'][$j]['pickup_status'] = "";
                        $res[$i]['items'][$j]['is_canceled'] = "";
                    } else if (!empty($order_tracking_data[0]['shipment_id']) && !empty($order_tracking_data[0]['awb_code']) && $order_tracking_data[0]['pickup_status'] == 0 && $order_tracking_data[0]['is_canceled'] == 0) {
                        $res[$i]['items'][$j]['active_status'] = 'Send request for pickup pending';
                        $res[$i]['items'][$j]['shipment_id'] = $order_tracking_data[0]['shipment_id'];
                        $res[$i]['items'][$j]['awb_code'] = isset($order_tracking_data[0]['awb_code']) ? $order_tracking_data[0]['awb_code'] : "0";
                        $res[$i]['items'][$j]['pickup_status'] = "1";
                        $res[$i]['items'][$j]['is_canceled'] = "";
                    } else if ($order_tracking_data[0]['is_canceled'] == 1 && !empty($order_tracking_data[0]['shipment_id']) && !empty($order_tracking_data[0]['awb_code']) && $order_tracking_data[0]['pickup_status'] == 1) {
                        $res[$i]['items'][$j]['active_status'] = 'Order is canclled';
                        $res[$i]['items'][$j]['shipment_id'] = $order_tracking_data[0]['shipment_id'];
                        $res[$i]['items'][$j]['awb_code'] = isset($order_tracking_data[0]['awb_code']) ? $order_tracking_data[0]['awb_code'] : "0";
                        $res[$i]['items'][$j]['pickup_status'] = "1";
                        $res[$i]['items'][$j]['is_canceled'] = "1";
                    } else {
                        $res[$i]['items'][$j]['active_status'] = 'Order Ready for tracking';
                        $res[$i]['items'][$j]['shipment_id'] = $order_tracking_data[0]['shipment_id'];
                        $res[$i]['items'][$j]['awb_code'] = isset($order_tracking_data[0]['awb_code']) ? $order_tracking_data[0]['awb_code'] : "0";
                        $res[$i]['items'][$j]['pickup_status'] = "1";
                        $res[$i]['items'][$j]['is_canceled'] = "";
                    }
                } else {
                    $res[$i]['items'][$j]['shipping_method'] = 'local';
                    $res[$i]['items'][$j]['status'] = (!empty($res[$i]['items'][$j]['status'])) ? json_decode($res[$i]['items'][$j]['status']) : array();
                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                        $sub_total += $res[$i]['items'][$j]['sub_total'];
                    }
                    if (!empty($res[$i]['items'][$j]['status'])) {
                        if (count($res[$i]['items'][$j]['status']) > 1) {
                            if (in_array("awaiting_payment", $res[$i]['items'][$j]['status'][0]) && in_array("received", $res[$i]['items'][$j]['status'][1])) {
                                unset($res[$i]['items'][$j]['status'][0]);
                            }
                            $res[$i]['items'][$j]['status'] = array_values($res[$i]['items'][$j]['status']);
                        }
                    } else {
                        $res[$i]['items'][$j]['status'] = array();
                    }

                    $res[$i]['items'][$j]['delivery_boy_id'] = (!empty($res[$i]['items'][$j]['delivery_boy_id'])) ? $res[$i]['items'][$j]['delivery_boy_id'] : "";
                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $this->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $item_details = $this->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
                    $sql = "SELECT id from return_requests where product_variant_id = " . $res[$i]['items'][$j]['variant_id'] . " AND user_id = " . $user_id;
                    $this->db->sql($sql);
                    $return_request = $this->db->getResult();
                    if (empty($return_request)) {
                        $res[$i]['items'][$j]['applied_for_return'] = false;
                    } else {
                        $res[$i]['items'][$j]['applied_for_return'] = true;
                    }
                    $res[$i]['items'][$j]['shipment_id'] = "0";
                }
            }

            $res[$i]['final_total'] = strval($row['final_total']);
            $res[$i]['total'] = strval($row['total']);
            $i++;
        }
        $orders = $order = array();

        if (!empty($res)) {
            $orders['error'] = false;
            $orders['total'] = $total;
            $orders['data'] = array_values($res);
            return $orders;
        } else {
            $res['error'] = true;
            $res['message'] = "No orders found!";
            return $res;
        }
    }

    public function get_maintenance_mode($app_name)
    {
        $mode = $this->get_data(['*'], 'variable="maintenance_mode"', 'settings')[0];
        $mode = (array) json_decode($mode['value']);
        foreach ($mode as $app => $value) {
            if ($app_name == $app) {
                $mode = $value;
            }
        }
        if ($mode == [] || $mode == 0)
            $mode = "0";
        return $mode;
    }
}
