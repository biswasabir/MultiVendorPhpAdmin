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

$shipping_type = ($fn->get_settings('local_shipping') == 1) ? 'local' : 'standard';

/* 
-------------------------------------------
APIs for Multi Vendor
-------------------------------------------
1. add_address
2. update_address
3. delete_address
4. get_addresses
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

if ((isset($_POST['add_address'])) && ($_POST['add_address'] == 1)) {
    /*
    1.add_address
        accesskey:90336
        add_address:1
        user_id:3
        name:abc
        mobile:1234567890
        type:Home/Office
        address:Time Square Empire
        landmark:Bhuj-Mirzapar Highway
        area_id:1    
        area_name:mirzapar 
        pincode_id:2 
        pincode:
        city_id:2    
        city_name:
        state:Gujarat
        country:India
        alternate_mobile:9876543210 // {optional}
        country_code:+91            // {optional}
        latitude:value              // {optional}
        longitude:value             // {optional}
        is_default:0/1              // {optional}
    */

    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $type = (isset($_POST['type']) && !empty($_POST['type'])) ? $db->escapeString($fn->xss_clean($_POST['type'])) : "";
    $name = (isset($_POST['name']) && !empty($_POST['name'])) ? $db->escapeString($fn->xss_clean($_POST['name'])) : "";
    $mobile = (isset($_POST['mobile']) && !empty($_POST['mobile'])) ? $db->escapeString($fn->xss_clean($_POST['mobile'])) : "";
    $country_code  = (isset($_POST['country_code']) && !empty($_POST['country_code'])) ? $db->escapeString($fn->xss_clean($_POST['country_code'])) : "";
    $alternate_mobile = (isset($_POST['alternate_mobile']) && !empty($_POST['alternate_mobile'])) ? $db->escapeString($fn->xss_clean($_POST['alternate_mobile'])) : "";
    $address =  (isset($_POST['address']) && !empty($_POST['address'])) ? $db->escapeString($fn->xss_clean($_POST['address'])) : "";
    $landmark = (isset($_POST['landmark']) && !empty($_POST['landmark'])) ? $db->escapeString($fn->xss_clean($_POST['landmark'])) : "";
    $area_id = (isset($_POST['area_id']) && !empty($_POST['area_id'])) ? $db->escapeString($fn->xss_clean($_POST['area_id'])) : "";
    $area_name = (isset($_POST['area_name']) && !empty($_POST['area_name'])) ? $db->escapeString($fn->xss_clean($_POST['area_name'])) : "";
    $pincode_id = (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "";
    $pincode = (isset($_POST['pincode']) && !empty($_POST['pincode'])) ? $db->escapeString($fn->xss_clean($_POST['pincode'])) : "";
    $city_id = (isset($_POST['city_id']) && !empty($_POST['city_id'])) ? $db->escapeString($fn->xss_clean($_POST['city_id'])) : "";
    $city_name = (isset($_POST['city_name']) && !empty($_POST['city_name'])) ? $db->escapeString($fn->xss_clean($_POST['city_name'])) : "";
    $state = (isset($_POST['state']) && !empty($_POST['state'])) ? $db->escapeString($fn->xss_clean($_POST['state'])) : "";
    $country = (isset($_POST['country']) && !empty($_POST['country'])) ? $db->escapeString($fn->xss_clean($_POST['country'])) : "";
    $latitude = (isset($_POST['latitude']) && !empty($_POST['latitude'])) ? $db->escapeString($fn->xss_clean($_POST['latitude'])) : "0";
    $longitude = (isset($_POST['longitude']) && !empty($_POST['longitude'])) ? $db->escapeString($fn->xss_clean($_POST['longitude'])) : "0";
    $is_default = (isset($_POST['is_default']) && !empty($_POST['is_default'])) ? $db->escapeString($fn->xss_clean($_POST['is_default'])) : "0";

    if (!empty($user_id) && !empty($type) && !empty($name) && !empty($mobile) && !empty($address) && !empty($landmark) && (!empty($area_id) || !empty($area_name))  && (!empty($pincode_id) || !empty($pincode)) && (!empty($city_id) || !empty($city_name)) && !empty($state) && !empty($country)) {
        if ($is_default == 1) {
            $fn->remove_other_addresses_from_default($user_id);
        }

        if (!empty($city_id) && !empty($city_name)) {
            $res['error'] = false;
            $res['message'] = 'Sorry you cannot use city_id and city name same time ';
            print_r(json_encode($res));
            return false;
        }

        if (!empty($pincode_id) && !empty($pincode)) {
            $res['error'] = false;
            $res['message'] = 'Sorry you cannot use pincode_id and pincode name same time ';
            print_r(json_encode($res));
            return false;
        }

        if (!empty($area_id) && !empty($area_name)) {
            $res['error'] = false;
            $res['message'] = 'Sorry you cannot use area_id and area_name name same time ';
            print_r(json_encode($res));
            return false;
        }


        $data = array(
            'user_id' => $user_id,
            'type' => $type,
            'name' => $name,
            'mobile' => $mobile,
            'alternate_mobile' => $alternate_mobile,
            'address' => $address,
            'landmark' => $landmark,
            'area_id' => (!empty($area_id)) ? $area_id : 0,
            'area' => $area_name,
            'pincode_id' => (!empty($pincode_id)) ? $pincode_id : 0,
            'pincode' => $pincode,
            'city_id' => (!empty($city_id)) ? $city_id : 0,
            'city' => $city_name,
            'state' => $state,
            'country' => $country,
            'latitude' => $latitude == "" ? "0" : $latitude,
            'longitude' => $longitude == "" ? "0" : $longitude,
            'is_default' => $is_default,
        );

        $db->insert('user_addresses', $data);
        $res_insert = $db->getResult();

        if ($res_insert) {
            $d_charges = $fn->get_data($columns = ['minimum_free_delivery_order_amount', 'delivery_charges'], 'id=' . $area_id, 'area');
            $res = $db->getResult();

            $sql = "SELECT ua.*,CASE WHEN ua.pincode_id!=0 THEN (SELECT pincode from pincodes p WHERE p.id=ua.pincode_id ) ELSE  ua.pincode END as pincode,CASE WHEN ua.city_id!=0 THEN (SELECT c.name from cities c WHERE c.id=ua.city_id ) ELSE  ua.city END as city,CASE WHEN ua.area_id!=0 THEN (SELECT a.name from area a WHERE a.id=ua.area_id ) ELSE  ua.area END as `area` FROM user_addresses ua where ua.user_id= " . $user_id . " And  ua.id=" . $res_insert[0] . " ORDER BY is_default DESC";
            $db->sql($sql);
            $res1 = $db->getResult();

            $response['error'] = false;
            $response['message'] = 'Address added successfully';
            $response['data']["id"] = strval($res_insert[0]);
            $response['data']['user_id'] = $user_id;
            $response['data']['type'] = $type;
            $response['data']['name'] = $name;
            $response['data']['mobile'] = $mobile;
            $response['data']['country_code'] = $country_code;
            $response['data']['alternate_mobile'] = $alternate_mobile;
            $response['data']['address'] = $address;
            $response['data']['landmark'] = $landmark;
            $response['data']['area_id'] = $area_id;
            $response['data']['area'] = (!empty($res1[0]['area'])) ? $res1[0]['area'] : "";
            $response['data']['pincode_id'] = $pincode_id;
            $response['data']['pincode'] =  (!empty($res1[0]['pincode'])) ? $res1[0]['pincode'] : "";
            $response['data']['city_id'] = $city_id;
            $response['data']['city'] = (!empty($res1[0]['city'])) ? $res1[0]['city'] : "";
            $response['data']['state'] = $state;
            $response['data']['country'] = $country;
            $response['data']['latitude'] = $latitude == "" ? "0" : $latitude;
            $response['data']['longitude'] = $longitude == "" ? "0" : $longitude;
            $response['data']['is_default'] = $is_default == "" ? "0" : $is_default;
            $response['data']['minimum_free_delivery_order_amount'] = (!empty($d_charges[0]['minimum_free_delivery_order_amount'])) ? $d_charges[0]['minimum_free_delivery_order_amount'] : "0";
            $response['data']['delivery_charges'] = (!empty($d_charges[0]['delivery_charges'])) ? $d_charges[0]['delivery_charges'] : "0";
            $response['data']['selected'] = false;
        } else {
            $response['error'] = true;
            $response['message'] = 'Something went wrong please try again!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));

    return false;
}

if ((isset($_POST['update_address'])) && ($_POST['update_address'] == 1)) {
    /*
    2.update_address
        accesskey:90336
        update_address:1
        id:1
        user_id:1
        is_default:0/1
        name:1                          // {optional}
        type:Home/Office                // {optional}
        mobile:9876543210                // {optional}
        alternate_mobile:9876543210     // {optional}
        address:Time Square Empire      // {optional}
        landmark:Bhuj-Mirzapar Highway  // {optional}
        area_id:1    or  area_name:mirzapar bhuj // {optional}
        pincode_id:2 or  pincode: 370465         // {optional}
        city_id:2    or  city_name:bhuj // {optional}
        state:Gujarat                   // {optional}
        country:India                   // {optional}
        latitude:value                  // {optional}
        longitude:value                 // {optional}
        
    */

    $id = (isset($_POST['id']) && !empty($_POST['id'])) ? trim($db->escapeString($fn->xss_clean($_POST['id']))) : "";
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? trim($db->escapeString($fn->xss_clean($_POST['user_id']))) : "";
    $name = (isset($_POST['name']) && !empty($_POST['name'])) ? trim($db->escapeString($fn->xss_clean($_POST['name']))) : "";
    $mobile = (isset($_POST['mobile']) && !empty($_POST['mobile'])) ? trim($db->escapeString($fn->xss_clean($_POST['mobile']))) : "";
    $type = (isset($_POST['type']) && !empty($_POST['type'])) ? trim($db->escapeString($fn->xss_clean($_POST['type']))) : "";
    $alternate_mobile = (isset($_POST['alternate_mobile']) && !empty($_POST['alternate_mobile'])) ? trim($db->escapeString($fn->xss_clean($_POST['alternate_mobile']))) : "";
    $address = (isset($_POST['address']) && !empty($_POST['address'])) ? trim($db->escapeString($fn->xss_clean($_POST['address']))) : "";
    $landmark = (isset($_POST['landmark']) && !empty($_POST['landmark'])) ? trim($db->escapeString($fn->xss_clean($_POST['landmark']))) : "";
    $area_id = (isset($_POST['area_id']) && !empty($_POST['area_id'])) ? $db->escapeString($fn->xss_clean($_POST['area_id'])) : "0";
    $area_name = (isset($_POST['area_name']) && !empty($_POST['area_name'])) ? $db->escapeString($fn->xss_clean($_POST['area_name'])) : "";
    $pincode_id = (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "0";
    $pincode = (isset($_POST['pincode']) && !empty($_POST['pincode'])) ? $db->escapeString($fn->xss_clean($_POST['pincode'])) : "";
    $city_id = (isset($_POST['city_id']) && !empty($_POST['city_id'])) ? $db->escapeString($fn->xss_clean($_POST['city_id'])) : "0";
    $city_name = (isset($_POST['city_name']) && !empty($_POST['city_name'])) ? $db->escapeString($fn->xss_clean($_POST['city_name'])) : "";
    $state = (isset($_POST['state']) && !empty($_POST['state'])) ? trim($db->escapeString($fn->xss_clean($_POST['state']))) : "";
    $country = (isset($_POST['country']) && !empty($_POST['country'])) ? trim($db->escapeString($fn->xss_clean($_POST['country']))) : "";
    $latitude = (isset($_POST['latitude']) && !empty($_POST['latitude'])) ? trim($db->escapeString($fn->xss_clean($_POST['latitude']))) : "0";
    $longitude = (isset($_POST['longitude']) && !empty($_POST['longitude'])) ? trim($db->escapeString($fn->xss_clean($_POST['longitude']))) : "0";
    $is_default = (isset($_POST['is_default']) && !empty($_POST['is_default'])) ? trim($db->escapeString($fn->xss_clean($_POST['is_default']))) : "0";

    if ((!isset($_POST['city_id']) || $_POST['city_id'] == '') && (!isset($_POST['city_name']) || $_POST['city_name'] == '')) {
        $res['error'] = true;
        $res['message'] = 'Please pass City Id or City Name';
        print_r(json_encode($res));
        return false;
    }

    if ((!isset($_POST['pincode_id']) || $_POST['pincode_id'] == '') && (!isset($_POST['pincode']) || $_POST['pincode'] == '')) {
        $res['error'] = true;
        $res['message'] = 'Please pass pincode id or Pincode';
        print_r(json_encode($res));
        return false;
    }

    if ((!isset($_POST['area_id']) || $_POST['area_id'] == '') && (!isset($_POST['area_name']) || $_POST['area_name'] == '')) {
        $res['error'] = true;
        $res['message'] = 'Please pass Area Id or Area name';
        print_r(json_encode($res));
        return false;
    }

    if (!empty($city_id) && !empty($city_name)) {
        $res['error'] = false;
        $res['message'] = 'Sorry you cannot use city_id and city name same time ';
        print_r(json_encode($res));
        return false;
    }

    if (!empty($pincode_id) && !empty($pincode)) {
        $res['error'] = false;
        $res['message'] = 'Sorry you cannot use pincode_id and pincode name same time ';
        print_r(json_encode($res));
        return false;
    }

    if (!empty($area_id) && !empty($area_name)) {
        $res['error'] = false;
        $res['message'] = 'Sorry you cannot use area_id and area_name name same time ';
        print_r(json_encode($res));
        return false;
    }
    if (!empty($id) && !empty($user_id)) {
        if ($is_default == 1) {
            $fn->remove_other_addresses_from_default($user_id);
        }

        if ($fn->is_address_exists($id)) {
            $data = array(
                'type' => $type,
                'alternate_mobile' => $alternate_mobile,
                'mobile' => $mobile,
                'name' => $name,
                'address' => $address,
                'landmark' => $landmark,
                'area_id' => $area_id,
                'area' => $area_name,
                'pincode_id' => $pincode_id,
                'pincode' => $pincode,
                'city_id' => $city_id,
                'city' => $city_name,
                'state' => $state,
                'country' => $country,
                'latitude' => $latitude == "" ? "0" : $latitude,
                'longitude' => $longitude == "" ? "0" : $longitude,
                'is_default' => $is_default
            );
            $update = $db->update('user_addresses', $data, 'id=' . $id);
            $updates = $db->getResult();

            if ($updates[0] == 1) {
                $d_charges = $fn->get_data($columns = ['minimum_free_delivery_order_amount', 'delivery_charges', 'name', 'pincode_id', 'city_id'], 'id=' . $area_id, 'area');

                $sql = "SELECT ua.*,CASE WHEN ua.pincode_id!=0 THEN (SELECT pincode from pincodes p WHERE p.id=ua.pincode_id ) ELSE  ua.pincode END as pincode,CASE WHEN ua.city_id!=0 THEN (SELECT c.name from cities c WHERE c.id=ua.city_id ) ELSE  ua.city END as city,CASE WHEN ua.area_id!=0 THEN (SELECT a.name from area a WHERE a.id=ua.area_id ) ELSE  ua.area END as `area` FROM user_addresses ua where ua.user_id=$user_id And ua.id= " . $id;
                $db->sql($sql);
                $res1 = $db->getResult();

                $response['error'] = false;
                $response['message'] = 'Address updated successfully';
                $response['data']["id"] = strval($id);
                $response['data']['user_id'] = $user_id;
                $response['data']['name'] = $name;
                $response['data']['type'] = $type;
                $response['data']['mobile'] = $mobile;
                $response['data']['alternate_mobile'] = $alternate_mobile;
                $response['data']['address'] = $address;
                $response['data']['landmark'] = $landmark;
                $response['data']['area_id'] = $area_id;
                $response['data']['area'] = (!empty($res1[0]['area'])) ? $res1[0]['area'] : "";
                $response['data']['pincode_id'] = $pincode_id;
                $response['data']['pincode'] =  (!empty($res1[0]['pincode'])) ? $res1[0]['pincode'] : "";
                $response['data']['city_id'] = $city_id;
                $response['data']['city'] = (!empty($res1[0]['city'])) ? $res1[0]['city'] : "";
                $response['data']['state'] = $state;
                $response['data']['country'] = $country;
                $response['data']['latitude'] = $latitude == "" ? "0" : $latitude;
                $response['data']['longitude'] = $longitude == "" ? "0" : $longitude;
                $response['data']['is_default'] = $is_default == "" ? "0" : $is_default;
                $response['data']['selected'] = false;
            } else {
                $response['error'] = true;
                $response['message'] = 'Something went wrong please try again!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No such address exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['delete_address'])) && ($_POST['delete_address'] == 1)) {
    /*
    3.delete_address
        accesskey:90336
        delete_address:1
        id:3
    */
    $id  = (isset($_POST['id']) && !empty($_POST['id'])) ? trim($db->escapeString($fn->xss_clean($_POST['id']))) : "";
    if (!empty($id)) {
        if ($fn->is_address_exists($id)) {
            if ($db->delete('user_addresses', 'id=' . $id)) {
                $response['error'] = false;
                $response['message'] = 'Address deleted successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something went wrong please try again!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No such address exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['get_addresses'])) && ($_POST['get_addresses'] == 1)) {
    /*
    4.get_addresses
        accesskey:90336
        get_addresses:1
        user_id:3
        address_id: 695 //{optional}
        type:checkout //{optional}
        offset:0    // {optional}
        limit:5     // {optional}
    */
    $type = (isset($_POST['type']) && !empty(($_POST['type']))) ? $_POST['type'] : "";

    $user_id  = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? trim($db->escapeString($fn->xss_clean($_POST['user_id']))) : "";
    if (!empty($user_id)) {
        if ($fn->is_address_exists($id = "", $user_id)) {
            $where = "";
            if (isset($_POST['address_id']) && !empty($_POST['address_id'])) {
                $address_id = $_POST['address_id'];
                $where = " AND ua.id=$address_id";
            }
            if ($type == 'checkout') {

                if ($shipping_type == 'standard') {
                    $sql = "SELECT count(id) as total from user_addresses where user_id=" . $user_id;
                    $db->sql($sql);
                    $total = $db->getResult();

                    $sql = "SELECT ua.*,CASE WHEN ua.pincode_id!=0 THEN (SELECT pincode from pincodes p WHERE p.id=ua.pincode_id ) ELSE  ua.pincode END as pincode,CASE WHEN ua.city_id!=0 THEN (SELECT c.name from cities c WHERE c.id=ua.city_id ) ELSE  ua.city END as city,CASE WHEN ua.area_id!=0 THEN (SELECT a.name from area a WHERE a.id=ua.area_id ) ELSE  ua.area END as area FROM user_addresses ua where ua.user_id= " . $user_id . " $where ORDER BY is_default DESC";
                    $db->sql($sql);
                    $res = $db->getResult();
                    $res[$i]['minimum_free_delivery_order_amount'] = (!empty($res[$i]['minimum_free_delivery_order_amount'])) ? $res[$i]['minimum_free_delivery_order_amount'] : "0";
                    $res[$i]['delivery_charges'] = (!empty($res[$i]['delivery_charges'])) ? $res[$i]['delivery_charges'] : "0";

                    if ($res['pincode_id'] != 0) {
                        $sql = "select ua.*,u.name as user_name,a.name as area_name,p.pincode as pincode,c.name as city,a.minimum_free_delivery_order_amount as minimum_free_delivery_order_amount,a.delivery_charges as delivery_charges from user_addresses ua LEFT JOIN area a ON a.id=ua.area_id LEFT JOIN pincodes p ON p.id=ua.pincode_id LEFT JOIN users u ON u.id=ua.user_id LEFT JOIN cities c ON c.id=a.city_id where ua.user_id= " . $user_id . " $where  ORDER BY is_default DESC";
                        $db->sql($sql);
                        $res = $db->getResult();
                    } else {
                        foreach ($res as $key => $address) {

                            if (is_null($res[$key])) {
                                $res[$key] = "";
                            } else {


                                $response['error'] = false;
                                $response['message'] = 'Address retrived successfully!';
                                $response['total'] = "1";


                                $response['data'] = $res;
                            }
                        }
                        unset($res['city']);
                    }
                } else {
                    $sql = "SELECT count(id) as total from user_addresses where user_id=" . $user_id;
                    $db->sql($sql);
                    $total = $db->getResult();

                    $sql = "SELECT ua.*,CASE WHEN ua.pincode_id!=0 THEN (SELECT pincode from pincodes p WHERE p.id=ua.pincode_id ) ELSE  ua.pincode END as pincode,CASE WHEN ua.city_id!=0 THEN (SELECT c.name from cities c WHERE c.id=ua.city_id ) ELSE  ua.city END as city,CASE WHEN ua.area_id!=0 THEN (SELECT a.name from area a WHERE a.id=ua.area_id ) ELSE  ua.area END as area FROM user_addresses ua where ua.user_id= " . $user_id . " $where  ORDER BY is_default DESC";
                    $db->sql($sql);
                    $res = $db->getResult();
                }
                if (!empty($res)) {
                    $res = $res[0];
                } else {
                    $sql = "SELECT ua.*,CASE WHEN ua.pincode_id!=0 THEN (SELECT pincode from pincodes p WHERE p.id=ua.pincode_id ) ELSE  ua.pincode END as pincode,CASE WHEN ua.city_id!=0 THEN (SELECT c.name from cities c WHERE c.id=ua.city_id ) ELSE  ua.city END as city,CASE WHEN ua.area_id!=0 THEN (SELECT a.name from area a WHERE a.id=ua.area_id ) ELSE  ua.area END as area FROM user_addresses ua where ua.user_id= " . $user_id . " $where  ORDER BY is_default DESC";
                    $db->sql($sql);
                    $res = $db->getResult();
                    $res = $res[0];

                    $res['selected'] = true;
                }


                $response['error'] = false;
                $response['message'] = 'Address retrived successfully!';
                $response['total'] = "1";

                $response['data'] = $res;
            } else {


                if ($shipping_type == 'standard') {
                    $sql = "SELECT count(id) as total from user_addresses where user_id=" . $user_id;
                    $db->sql($sql);
                    $total = $db->getResult();

                    $sql = "SELECT ua.*,CASE WHEN ua.pincode_id!=0 THEN (SELECT pincode from pincodes p WHERE p.id=ua.pincode_id ) ELSE  ua.pincode END as pincode,CASE WHEN ua.city_id!=0 THEN (SELECT c.name from cities c WHERE c.id=ua.city_id ) ELSE  ua.city END as city,CASE WHEN ua.area_id!=0 THEN (SELECT a.name from area a WHERE a.id=ua.area_id ) ELSE  ua.area END as area FROM user_addresses ua where ua.user_id= " . $user_id . " $where  ORDER BY is_default DESC";
                    $db->sql($sql);
                    $res = $db->getResult();
                } else {
                    $sql = "SELECT count(id) as total from user_addresses where user_id=" . $user_id;
                    $db->sql($sql);
                    $total = $db->getResult();

                    $sql = "SELECT ua.*,CASE WHEN ua.pincode_id!=0 THEN (SELECT pincode from pincodes p WHERE p.id=ua.pincode_id ) ELSE  ua.pincode END as pincode,CASE WHEN ua.city_id!=0 THEN (SELECT c.name from cities c WHERE c.id=ua.city_id ) ELSE  ua.city END as city,CASE WHEN ua.area_id!=0 THEN (SELECT a.name from area a WHERE a.id=ua.area_id ) ELSE  ua.area END as area FROM user_addresses ua where ua.user_id= " . $user_id . " $where  ORDER BY is_default DESC";
                    $db->sql($sql);
                    $res = $db->getResult();
                }



                if (!empty($res)) {
                    $response['error'] = false;
                    $response['message'] = 'Address retrived successfully!';

                    $response['total'] = $total[0]['total'];

                    for ($i = 0; $i < count($res); $i++) {
                        foreach ($res[$i] as $key => $address) {
                            if (is_null($address)) {
                                $address = "";
                            }
                        }
                    }
                }

                $response['data'] = array_values($res);
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'User addresse(s) doesn\'t exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}
