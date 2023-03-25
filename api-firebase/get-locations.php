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
// date_default_timezone_set('Asia/Kolkata');
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
1. get_areas
2. get_pincodes
3. get_cities
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

if (isset($_POST['get_areas']) && $_POST['get_areas'] == 1) {
    /*  
    1. get_areas
        accesskey:90336
        get_areas:1
        id:229              // {optional}
        pincode_id:1        // {optional}
        city_id:1        // {optional}
       
        sort:id             // {optional}
        order:DESC / ASC    // {optional}

    */

    $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'id';
    $order = (isset($_POST['order']) && !empty($_POST['order'])) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'DESC';

    $id = (isset($_POST['id'])) ? $db->escapeString($fn->xss_clean($_POST['id'])) : "";
    $pincode_id = (isset($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "";
    $city_id = (isset($_POST['city_id'])) ? $db->escapeString($fn->xss_clean($_POST['city_id'])) : "";

    $where = "";
    if (isset($_POST['id']) && !empty($_POST['id']) && is_numeric($_POST['id'])) {
        $where .=  !empty($where) ? " AND a.`id`=" . $id : " WHERE a.`id`=" . $id;
    }
    if (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) {
        $where .=  !empty($where) ? " AND a.`pincode_id`=" . $pincode_id : " WHERE a.`pincode_id`=" . $pincode_id;
    }
    if (isset($_POST['search']) && $_POST['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where .= " AND c.`id` like '%" . $search . "%' || c.`name` like '%" . $search . "%' || a.id like '%" . $search . "%' || a.name like '%" . $search . "%' || p.id like '%" . $search . "%' || p.pincode like '%" . $search . "%'";
    }
    if (isset($_POST['city_id']) && !empty($_POST['city_id']) && is_numeric($_POST['city_id'])) {
        $where .=  !empty($where) ? " AND a.`city_id`=" . $city_id : " WHERE a.`city_id`=" . $city_id;
    }

    $sql = "SELECT count(DISTINCT(a.id)) as total FROM area a JOIN cities c ON c.id = a.city_id  JOIN pincodes p on p.id = a.pincode_id $where";
    // echo $sql;
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT a.*,c.name as city_name, p.pincode as pincode FROM `area` a JOIN cities c ON c.id = a.city_id  JOIN pincodes p on p.id = a.pincode_id $where GROUP BY a.id ORDER BY $sort $order ";
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        $response['error'] = false;
        $response['message'] = "Areas retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
        $response['total'] = 0;
        $response['data'] = array();
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_pincodes']) && $_POST['get_pincodes'] == 1) {
    /*
    2. get_pincodes
        accesskey:90336
        get_pincodes:1
        id:1                // {optional}
        offset:0            // {optional}
        limit:10            // {optional}
        sort:id             // {optional}
        order:DESC / ASC    // {optional}
    */
    $offset = (isset($_POST['offset']) && !empty($fn->xss_clean($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($fn->xss_clean($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'id';
    $order = (isset($_POST['order']) && !empty($fn->xss_clean($_POST['order']))) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'DESC';
    $id = (isset($_POST['id'])) ? $db->escapeString($fn->xss_clean($_POST['id'])) : "";
    $area_id = (isset($_POST['area_id'])) ? $db->escapeString($fn->xss_clean($_POST['area_id'])) : "";
    $where = "";
    $where1 = "";
    if (isset($_POST['id']) && !empty($_POST['id']) && is_numeric($_POST['id'])) {
        $where .=  " AND p.`id`=" . $id;
        $where1 .=  " AND p.`id`=" . $id;
    }
    if (isset($_POST['area_id']) && !empty($_POST['area_id']) && is_numeric($_POST['area_id'])) {
        $where .=  " AND a.`id`=" . $area_id;
    }
    if (isset($_POST['search']) && $_POST['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where .= " AND p.`pincode` like '%" . $search . "%'";
        $where1 .= " AND p.`pincode` like '%" . $search . "%'";
    }
    if (!isset($_POST['area_id'])) {
        $sql = "SELECT count(p.id) as total FROM pincodes p WHERE p.status = 1 $where1";
    } else {
        $sql = "SELECT count(p.id) as total FROM pincodes p LEFT JOIN area a ON p.id=a.pincode_id WHERE p.status = 1 $where";
    }
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT p.* FROM pincodes p LEFT JOIN area a ON p.id=a.pincode_id where p.status = 1 $where  GROUP BY p.id ORDER BY $sort $order  LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();
    $product = array();

    $i = 0;
    foreach ($res as $row) {
        $sql = "SELECT a.* FROM area a JOIN pincodes p ON p.id=a.pincode_id WHERE a.pincode_id = '" . $row['id'] . "' $where ORDER BY a.id DESC";
        $db->sql($sql);
        $product[$i] = $row;
        $variants = $db->getResult();

        $product[$i]['area'] = $variants;
        $i++;
    }
    if (!empty($product)) {
        $response['error'] = false;
        $response['message'] = "Pincodes retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $product;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_cities']) && $_POST['get_cities'] == 1) {
    /*  
    3. get_cities
        accesskey:90336
        get_cities:1
        id:1                // {optional}
        sort:id             // {optional}
        order:DESC / ASC    // {optional}
    */

    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'a.id';
    $order = (isset($_POST['order']) && !empty($fn->xss_clean($_POST['order']))) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'DESC';
    $area_id = (isset($_POST['area_id']) && !empty($fn->xss_clean($_POST['area_id']))) ? $db->escapeString($fn->xss_clean($_POST['area_id'])) : '';


    $where = "";
    if (isset($_POST['id']) && !empty($_POST['id']) && is_numeric($_POST['id'])) {
        $id = (isset($_POST['id'])) ? $db->escapeString($fn->xss_clean($_POST['id'])) : "";
        $where .=   " AND a.id=" . $id;
    }
    if (isset($_POST['area_id']) && !empty($_POST['area_id']) && is_numeric($_POST['area_id'])) {
        $area_id = (isset($_POST['area_id'])) ? $db->escapeString($fn->xss_clean($_POST['area_id'])) : "";
        $where .=  " AND a.id=" . $area_id;
    }
    if (isset($_POST['search']) && $_POST['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where .= " AND c.`id` like '%" . $search . "%' || c.`name` like '%" . $search . "%' || a.id like '%" . $search . "%' || a.name like '%" . $search . "%'";
    }

    $sql = "SELECT count(DISTINCT(c.id)) as total FROM cities c JOIN area a on a.city_id=c.id WHERE c.status = 1 $where";
    $db->sql($sql);
    $total = $db->getResult();
    $sql = "SELECT c.*,c.name as city_name,a.*,c.id as city_id FROM `cities` c JOIN area a on a.city_id=c.id WHERE c.status = 1  $where group by c.id";
    $db->sql($sql);
    $res = $db->getResult();
    $tempRow = $rows = array();
    foreach ($res as $row => $value) {
        $tempRow['id'] = $value['city_id'];
        $sql = "SELECT p.*,c.name as city_name,a.name as area_name,a.* FROM `pincodes` p left JOIN area a on a.pincode_id=p.id left join cities c on c.id=a.city_id where a.city_id=" . $value['city_id'];
        $db->sql($sql);
        $areas = $db->getResult();
        unset($areas[0]['name']);
        $tempRow['city_name'] = $value['city_name'];
        $tempRow['areas'] = $areas;
        $tempRow['status'] = $value['status'];
        $rows[] = $tempRow;
    }

    if (!empty($res)) {
        $response['error'] = false;
        $response['message'] = "Cities retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $rows;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}
