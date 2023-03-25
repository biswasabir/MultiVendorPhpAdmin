<?php

ini_set("display_errors", 0);
error_reporting(E_ALL);


header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');


include('../../includes/crud.php');
include('../../includes/custom-functions.php');
include('verify-token.php');

include('../../library/shiprocket.php');

$fn = new custom_functions();
$function = new functions;
$db = new Database();
$db->connect();
$shiprocket = new shiprocket();



$config = $fn->get_configurations();
$low_stock_limit = isset($config['low-stock-limit']) && (!empty($config['low-stock-limit'])) ? $config['low-stock-limit'] : 0;

$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}
include('../../includes/variables.php');
$currency = $fn->get_settings('currency');
$shipping_type = ($fn->get_settings('local_shipping') == 1) ? 'local' : 'standard';



/* 
-------------------------------------------
APIs for Seller
-------------------------------------------
1. login
2. get_categories
3. get_subcategories
4. get_products
5. get_financial_statistics
6. update_seller_fcm_id
7. get_seller_transactions
8. get_orders
9. update_order_status
10. add_products
11. update_products
12. delete_products
13. get_seller_by_id
14. get_taxes
15. get_units
16. get_pincodes
17. delete_other_images
18. delete_variant
20. send_request
21. get_requests
22. update_seller_profile
23. get_delivery_boys
24. change_status
25.add_pickup_location
26.get_pickup_location

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
    exit();
}
/* 
---------------------------------------------------------------------------------------------------------
*/

/*
1.login
    accesskey:90336
    login:1
    mobile:9876543210
    password:12345678
    fcm_id:YOUR_FCM_ID  // {optional}
*/
if (isset($_POST['login']) && !empty($_POST['login']) == 1) {

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

    $sql = "SELECT * FROM seller WHERE mobile = '$mobile' AND password = '$password'";
    $db->sql($sql);
    $res = $db->getResult();
    $num = $db->numRows($res);
    $rows = $tempRow = array();
    if ($num == 1) {
        if ($res[0]['status'] == 7) {
            $response['error'] = true;
            $response['message'] = "It seems your acount was removed by super admin please contact him to restore the account!";
        } else if ($res[0]['status'] == 2) {
            $response['error'] = true;
            $response['message'] = "Your account is not approved by Super Admin. Please wait for approval!";
        } else {
            $user_id = $res[0]['id'];
            $fcm_id = (isset($_POST['fcm_id']) && !empty($_POST['fcm_id'])) ? $db->escapeString($fn->xss_clean($_POST['fcm_id'])) : "";
            if (!empty($fcm_id)) {
                $sql1 = "update seller set `fcm_id` ='$fcm_id' where id = '" . $user_id . "'";
                $db->sql($sql1);
                $db->sql($sql);
                $res = $db->getResult();
            }
            $res[0]['fcm_id'] = !empty($res[0]['fcm_id'])  ? $res[0]['fcm_id'] : "";
            $res[0]['national_identity_card'] = !empty($res[0]['national_identity_card'])  ?  DOMAIN_URL . 'upload/seller/' . $res[0]['national_identity_card'] : "";
            $res[0]['address_proof'] = !empty($res[0]['address_proof']) ?  DOMAIN_URL . 'upload/seller/' . $res[0]['address_proof'] : "";
            $res[0]['logo'] = (!empty($res[0]['logo'])) ? DOMAIN_URL . 'upload/seller/' . $res[0]['logo'] : "";
            $res[0]['currency'] = $currency;
            $response['error'] = false;
            $response['message'] = "Login Successfully";
            $response['data'] = $res;
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Invalid number or password, Try again.";
    }
    print_r(json_encode($response));
    return false;
}

/* 
---------------------------------------------------------------------------------------------------------
*/

/*
2.get_categories
    accesskey:90336
    seller_id:9
    get_categories: 1
    offset:0           // {optional}
    limit:10           // {optional}
    sort:id            // {optional}
    order:asc/desc     // {optional}    
    search:Beverages   // {optional}
*/
if (isset($_POST['get_categories']) && !empty($_POST['get_categories'] == 1)) {

    if (empty($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Seller id can not be empty!";
        print_r(json_encode($response));
        return false;
    }

    $offset = (isset($_POST['offset']) && !empty($fn->xss_clean($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($fn->xss_clean($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'row_order';
    $order = (isset($_POST['order']) && !empty($fn->xss_clean($_POST['order']))) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'ASC';
    $seller_id = (isset($_POST['seller_id']) && !empty($fn->xss_clean($_POST['seller_id'])) && is_numeric($_POST['seller_id'])) ? $db->escapeString($fn->xss_clean($_POST['seller_id'])) : "";

    $where = "";
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where .= "AND `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `subtitle` like '%" . $search . "%'";
    }
    $sql_query = "SELECT categories FROM seller where id=$seller_id ";
    $db->sql($sql_query);
    $res1 = $db->getResult();
    $category_ids = $res1[0]['categories'];

    $sql = "SELECT count(id) as total FROM category where id IN($category_ids)" . $where .  "ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $total = $db->getResult();

    $sql_query = "SELECT * FROM category where id IN($category_ids)" . $where .  "ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql_query);
    $res = $db->getResult();
    foreach ($total as $row) {
        $total = $row['total'];
    }
    if (!empty($res)) {
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['image'] = (!empty($res[$i]['image'])) ? DOMAIN_URL  . $res[$i]['image'] : "";
            $res[$i]['web_image'] = (!empty($res[$i]['web_image'])) ? DOMAIN_URL . $res[$i]['web_image'] : "";
            $tmp = [];
        }
        foreach ($res as $r) {
            $r['childs'] = [];
            $db->sql("SELECT * FROM subcategory WHERE category_id = '" . $r['id'] . "' ORDER BY id DESC");
            $childs = $db->getResult();
            $temp = array('id' => "0", 'category_id' => "0", 'name' => "Select SubCategory", 'slug' => "", 'subtitle' => "", 'image' => "");
            if (!empty($childs)) {
                for ($i = 0; $i < count($childs); $i++) {
                    $childs[$i]['image'] = (!empty($childs[$i]['image'])) ? DOMAIN_URL  . $childs[$i]['image'] : '';
                    $r['childs'][$i] = (array)$childs[$i];
                }
                array_unshift($r['childs'], $temp);
            } else {
                $temp = array('id' => "0", 'category_id' => "0", 'name' => "No SubCategories available", 'slug' => "", 'subtitle' => "", 'image' => "");
                array_unshift($r['childs'], $temp);
            }
            $tmp[] = $r;
        }
        $res = $tmp;
        $response['error'] = false;
        $response['message'] = "Categories retrived successfully";
        $response['total'] = $total;
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}

/* 
---------------------------------------------------------------------------------------------------------
*/

/*
3.get_subcategories
    accesskey:90336
    seller_id:1
    get_subcategories:1
    category_id:29      // {optional}
    subcategory_id:114  // {optional}
    offset:0            // {optional}
    limit:10            // {optional}
    sort:id             // {optional}
    order:asc/desc      // {optional}
*/
if (isset($_POST['get_subcategories']) && !empty($_POST['get_subcategories'] == 1)) {
    if (empty($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Seller ID should be filled!";
        print_r(json_encode($response));
        return false;
    }

    $offset = (isset($_POST['offset']) && !empty($fn->xss_clean($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($fn->xss_clean($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'id';
    $order = (isset($_POST['order']) && !empty($fn->xss_clean($_POST['order']))) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'DESC';
    $seller_id = (isset($_POST['seller_id']) && !empty($fn->xss_clean($_POST['seller_id'])) && is_numeric($_POST['seller_id'])) ? $db->escapeString($fn->xss_clean($_POST['seller_id'])) : "";
    $category_id = (isset($_POST['category_id']) && !empty($_POST['category_id'])) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : "";
    $subcategory_id = (isset($_POST['subcategory_id'])) && !empty($_POST['subcategory_id']) ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : "";

    $where = "";
    if (!empty($_POST['category_id'])) {
        $where .= " AND c.id=" . $category_id;
    }
    if (!empty($_POST['subcategory_id'])) {
        $where .= " AND sc.id=" . $subcategory_id;
    }
    $sql = "SELECT count(sc.id) as total FROM `subcategory` sc left join category c on sc.category_id=c.id  
    left JOIN seller s on FIND_IN_SET(c.id, s.categories) > 0 WHERE s.id = $seller_id " . $where;
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT sc.*,c.name as category_name FROM `subcategory` sc left join category c on sc.category_id=c.id  
    left JOIN seller s on FIND_IN_SET(c.id, s.categories) > 0 WHERE s.id = $seller_id " . $where . " ORDER BY sc.$sort $order LIMIT $offset,$limit ";
    $db->sql($sql);
    $res1 = $db->getResult();

    if (!empty($res1)) {
        for ($i = 0; $i < count($res1); $i++) {
            $res1[$i]['image'] = (!empty($res1[$i]['image'])) ? DOMAIN_URL . '' . $res1[$i]['image'] : '';
        }
        $response['error'] = false;
        $response['message'] = "Sub Categories retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $res1;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}

/* 
---------------------------------------------------------------------------------------------------------
*/

/*
4.get_products
    accesskey:90336
    get_products:1
    seller_id:1
    filter:low_stock | out_stock // {optional}
    product_id:119      // {optional}
    category_id:119     // {optional}
    subcategory_id:119  // {optional}
    limit:10            // {optional}
    offset:0            // {optional}
    search:value        // {optional}
    slug:popcorn-3      // {optional}
    sort:new / old / high / low  // {optional}
    shipping_type:local/standard
*/

if (isset($_POST['get_products']) && !empty($_POST['get_products'] == 1)) {

    if (empty($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Seller ID should be filled!";
        print_r(json_encode($response));
        return false;
    }
    $where = "";
    if (isset($_POST['shipping_type']) && !empty($_POST['shipping_type'])) {
        $standard_shipping = ($_POST['shipping_type'] == 'standard') ? 1 : 0;
        $where .= " AND p.standard_shipping=$standard_shipping";
    }
    $offset = (isset($_POST['offset']) && !empty($fn->xss_clean($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($fn->xss_clean($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;

    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'new';

    $seller_id = (isset($_POST['seller_id']) && !empty($fn->xss_clean($_POST['seller_id'])) && is_numeric($_POST['seller_id'])) ? $db->escapeString($fn->xss_clean($_POST['seller_id'])) : "";
    $category_id = (isset($_POST['category_id']) && !empty($_POST['category_id'])) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : "";
    $filter = (isset($_POST['filter']) && !empty($_POST['filter'])) ? $db->escapeString($fn->xss_clean($_POST['filter'])) : '';
    $product_id = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_id'])) : "";
    $subcategory_id = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : "0";


    if ($sort == 'new') {
        $sort = 'date_added DESC';
        $price = 'MIN(price)';
        $price_sort = ' pv.price ASC';
    } elseif ($sort == 'old') {
        $sort = 'date_added ASC';
        $price = 'MIN(price)';
        $price_sort = ' pv.price ASC';
    } elseif ($sort == 'high') {
        $sort = ' price DESC';
        $price = 'MAX(price)';
        $price_sort = ' pv.price DESC';
    } elseif ($sort == 'low') {
        $sort = ' price ASC';
        $price = 'MIN(price)';
        $price_sort = ' pv.price ASC';
    } else {
        $sort = ' p.row_order ASC';
        $price = 'MIN(price)';
        $price_sort = ' pv.price ASC';
    }

    $join = $out_stock = $low_stock = "";
    if ($filter == "out_stock") {
        $join = " join product_variant pv ON pv.product_id=p.id ";
        $where .= " AND pv.serve_for = 'Sold Out'";
        $out_stock = " AND pv.serve_for = 'Sold Out'";
    }
    if ($filter == "low_stock") {
        $join = " join product_variant pv ON pv.product_id=p.id ";
        $where .=  " AND pv.stock < $low_stock_limit AND pv.serve_for = 'Available'";
        $low_stock .= " AND pv.stock < $low_stock_limit AND pv.serve_for = 'Available'";
    }

    if (isset($_POST['product_id']) && !empty($_POST['product_id']) && is_numeric($_POST['product_id'])) {
        $where .= " AND p.`id` = " . $product_id;
    }

    if (isset($_POST['category_id']) && !empty($_POST['category_id']) && is_numeric($_POST['category_id'])) {
        $seller_category = $fn->get_data($columns = ["categories"], "id=" . $seller_id, 'seller');
        $category = $seller_category[0]['categories'];
        $data = explode(",", $category);
        $search = (in_array($category_id, $data, TRUE)) ? 1 : 2;
        if ($search == 2) {
            $response['error'] = true;
            $response['message'] = "No Products found!";
            print_r(json_encode($response));
            return false;
        } else {
            $where .=  " AND p.`category_id`=" . $category_id;
        }
    } else {
        $categories = $fn->get_data($columns = ["categories"], "id=" . $seller_id, 'seller');
        $category = $categories[0]['categories'];
        if ($category != "") {
            $where .= " AND p.category_id IN ($category)";
        }
    }
    if (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != "" && is_numeric($_POST['subcategory_id'])) {
        $where .= " AND p.`subcategory_id`=" . $subcategory_id;
    }

    if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $slug = $db->escapeString($fn->xss_clean($_POST['slug']));
        $where .= " AND p.`slug` =  '$slug' ";
    }
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where .= " AND (p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR p.`category_id` like '%" . $search . "%' OR p.`subcategory_id` like '%" . $search . "%' OR p.`slug` like '%" . $search . "%' OR p.`description` like '%" . $search . "%')";
    }
    $sql = "SELECT count(p.id) as total FROM products p JOIN seller s ON s.id=p.seller_id $join where p.seller_id = $seller_id" . $where;
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT p.*,s.name as seller_name ,s.status as seller_status,(SELECT " . $price . " FROM product_variant pv WHERE pv.product_id=p.id) as price FROM products p JOIN seller s ON s.id=p.seller_id $join where p.seller_id=$seller_id $where ORDER BY $sort LIMIT $offset,$limit ";
    $db->sql($sql);
    $res = $db->getResult();


    $product = array();
    $i = 0;

    foreach ($res as $row) {
        $row['category_name'] = $fn->get_data(['name'], 'id=' . $res[$i]['category_id'], 'category')[0]['name'];
        $row['subcategory_name'] = !empty($res[$i]['subcategory_id']) ? $fn->get_data(['name'], 'id=' . $res[$i]['subcategory_id'], 'subcategory')[0]['name'] : "";

        $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " $out_stock " . $low_stock;
        // echo $sql;
        $db->sql($sql);
        $variants = $db->getResult();

        if (empty($variants)) {
            continue;
        }
        $row['pickup_location'] = !empty($row['pickup_location']) ? $row['pickup_location'] : "";
        $row['other_images'] = json_decode($row['other_images'], 1);
        $row['other_images'] = empty($row['other_images']) ? array() : $row['other_images'];
        for ($j = 0; $j < count($row['other_images']); $j++) {
            $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
        }
        $row['image'] = DOMAIN_URL . $row['image'];
        if ($row['tax_id'] == 0) {
            $row['tax_title'] = "";
            $row['tax_percentage'] = "0";
        } else {
            $t_id = $row['tax_id'];
            $sql_tax = "select * from taxes where id= $t_id";
            $db->sql($sql_tax);

            $res_tax1 = $db->getresult();
            foreach ($res_tax1 as $tax1) {
                $row['tax_title'] = (!empty($tax1['title'])) ? $tax1['title'] : "";
                $row['tax_percentage'] =  (!empty($tax1['percentage'])) ? $tax1['percentage'] : "0";
            }
        }
        // [ 0=included, 1=excluded, 2=all ]
        if ($row['type'] == 'excluded') {
            $row['delivery_places'] = "1";
        } else  if ($row['type'] == 'included') {
            $row['delivery_places'] = "0";
        } else  if ($row['type'] == 'all') {
            $row['delivery_places'] = "2";
        } else {
            $row['delivery_places'] = "";
        }
        $row['type'] = $variants[0]['type'];

        $product[$i] = $row;
        for ($k = 0; $k < count($variants); $k++) {
            $variant_images = str_replace("'", '"', $variants[$k]['images']);
            $variants[$k]['images'] = json_decode($variant_images, 1);
            $variants[$k]['images'] = (empty($variants[$k]['images'])) ? array() : $variants[$k]['images'];
            for ($j = 0; $j < count($variants[$k]['images']); $j++) {
                $variants[$k]['images'][$j] = !empty(DOMAIN_URL . $variants[$k]['images'][$j]) ? DOMAIN_URL . $variants[$k]['images'][$j] : "";
            }

            $variants[$k]['serve_for'] =  $variants[$k]['stock'] <= 0 ? 'Sold Out' : $variants[$k]['serve_for'];
        }
        $product[$i]['variants'] = $variants;
        $i++;
    }
    if (!empty($product)) {
        $response['error'] = false;
        $response['message'] = "Products retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $product;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}
// if (isset($_POST['get_products']) && !empty($_POST['get_products'] == 1)) {

//     if (empty($_POST['seller_id'])) {
//         $response['error'] = true;
//         $response['message'] = "Seller ID should be filled!";
//         print_r(json_encode($response));
//         return false;
//     }
//     $where = "";
//     if (isset($_POST['shipping_type']) && !empty($_POST['shipping_type'])) {
//         $standard_shipping = ($_POST['shipping_type'] == 'standard') ? 1 : 0;
//         $where .= " AND p.standard_shipping=$standard_shipping";
//     }
//     $offset = (isset($_POST['offset']) && !empty($fn->xss_clean($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
//     $limit = (isset($_POST['limit']) && !empty($fn->xss_clean($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;

//     $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'new';

//     $seller_id = (isset($_POST['seller_id']) && !empty($fn->xss_clean($_POST['seller_id'])) && is_numeric($_POST['seller_id'])) ? $db->escapeString($fn->xss_clean($_POST['seller_id'])) : "";
//     $category_id = (isset($_POST['category_id']) && !empty($_POST['category_id'])) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : "";
//     $filter = (isset($_POST['filter']) && !empty($_POST['filter'])) ? $db->escapeString($fn->xss_clean($_POST['filter'])) : '';
//     $product_id = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_id'])) : "";
//     $subcategory_id = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : "0";


//     if ($sort == 'new') {
//         $sort = 'date_added DESC';
//         $price = 'MIN(price)';
//         $price_sort = ' pv.price ASC';
//     } elseif ($sort == 'old') {
//         $sort = 'date_added ASC';
//         $price = 'MIN(price)';
//         $price_sort = ' pv.price ASC';
//     } elseif ($sort == 'high') {
//         $sort = ' price DESC';
//         $price = 'MAX(price)';
//         $price_sort = ' pv.price DESC';
//     } elseif ($sort == 'low') {
//         $sort = ' price ASC';
//         $price = 'MIN(price)';
//         $price_sort = ' pv.price ASC';
//     } else {
//         $sort = ' p.row_order ASC';
//         $price = 'MIN(price)';
//         $price_sort = ' pv.price ASC';
//     }

//     $join = $out_stock = $low_stock = "";
//     if ($filter == "out_stock") {
//         $join = " join product_variant pv ON pv.product_id=p.id ";
//         $where .= " AND pv.serve_for = 'Sold Out'";
//         $out_stock = " AND pv.serve_for = 'Sold Out'";
//     }
//     if ($filter == "low_stock") {
//         $join = " join product_variant pv ON pv.product_id=p.id ";
//         $where .=  " AND pv.stock < $low_stock_limit AND pv.serve_for = 'Available'";
//         $low_stock .= " AND pv.stock < $low_stock_limit AND pv.serve_for = 'Available'";
//     }

//     if (isset($_POST['product_id']) && !empty($_POST['product_id']) && is_numeric($_POST['product_id'])) {
//         $where .= " AND p.`id` = " . $product_id;
//     }

//     if (isset($_POST['category_id']) && !empty($_POST['category_id']) && is_numeric($_POST['category_id'])) {
//         $seller_category = $fn->get_data($columns = ["categories"], "id=" . $seller_id, 'seller');
//         $category = $seller_category[0]['categories'];
//         $data = explode(",", $category);
//         $search = (in_array($category_id, $data, TRUE)) ? 1 : 2;
//         if ($search == 2) {
//             $response['error'] = true;
//             $response['message'] = "No Products found!";
//             print_r(json_encode($response));
//             return false;
//         } else {
//             $where .=  " AND p.`category_id`=" . $category_id;
//         }
//     } else {
//         $categories = $fn->get_data($columns = ["categories"], "id=" . $seller_id, 'seller');
//         $category = $categories[0]['categories'];
//         $where .= " AND p.category_id IN ($category)";
//     }
//     if (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != "" && is_numeric($_POST['subcategory_id'])) {
//         $where .= " AND p.`subcategory_id`=" . $subcategory_id;
//     }

//     if (isset($_POST['slug']) && !empty($_POST['slug'])) {
//         $slug = $db->escapeString($fn->xss_clean($_POST['slug']));
//         $where .= " AND p.`slug` =  '$slug' ";
//     }
//     if (isset($_POST['search']) && !empty($_POST['search'])) {
//         $search = $db->escapeString($fn->xss_clean($_POST['search']));
//         $where .= " AND (p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR p.`category_id` like '%" . $search . "%' OR p.`subcategory_id` like '%" . $search . "%' OR p.`slug` like '%" . $search . "%' OR p.`description` like '%" . $search . "%')";
//     }
//     $sql = "SELECT count(p.id) as total FROM products p JOIN seller s ON s.id=p.seller_id $join where p.seller_id = $seller_id" . $where;
//     $db->sql($sql);
//     $total = $db->getResult();

//     $sql = "SELECT p.*,s.name as seller_name ,s.status as seller_status,(SELECT " . $price . " FROM product_variant pv WHERE pv.product_id=p.id) as price FROM products p JOIN seller s ON s.id=p.seller_id $join where p.seller_id=$seller_id $where ORDER BY $sort LIMIT $offset,$limit ";
//     $db->sql($sql);
//     $res = $db->getResult();


//     $product = array();
//     $i = 0;

//     foreach ($res as $row) {
//         $row['category_name'] = $fn->get_data(['name'], 'id=' . $res[$i]['category_id'], 'category')[0]['name'];
//         $row['subcategory_name'] = !empty($res[$i]['subcategory_id']) ? $fn->get_data(['name'], 'id=' . $res[$i]['subcategory_id'], 'subcategory')[0]['name'] : "";
//         $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " $out_stock " . $low_stock;
//         // echo $sql;
//         $db->sql($sql);
//         $variants = $db->getResult();

//         if (empty($variants)) {
//             continue;
//         }
//         $row['pickup_location'] = !empty($row['pickup_location']) ? $row['pickup_location'] : "";
//         $row['other_images'] = json_decode($row['other_images'], 1);
//         $row['other_images'] = empty($row['other_images']) ? array() : $row['other_images'];
//         for ($j = 0; $j < count($row['other_images']); $j++) {
//             $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
//         }
//         $row['image'] = DOMAIN_URL . $row['image'];
//         if ($row['tax_id'] == 0) {
//             $row['tax_title'] = "";
//             $row['tax_percentage'] = "0";
//         } else {
//             $t_id = $row['tax_id'];
//             $sql_tax = "select * from taxes where id= $t_id";
//             $db->sql($sql_tax);

//             $res_tax1 = $db->getresult();
//             foreach ($res_tax1 as $tax1) {
//                 $row['tax_title'] = (!empty($tax1['title'])) ? $tax1['title'] : "";
//                 $row['tax_percentage'] =  (!empty($tax1['percentage'])) ? $tax1['percentage'] : "0";
//             }
//         }
//         // [ 0=included, 1=excluded, 2=all ]
//         if ($row['type'] == 'excluded') {
//             $row['delivery_places'] = "1";
//         } else  if ($row['type'] == 'included') {
//             $row['delivery_places'] = "0";
//         } else  if ($row['type'] == 'all') {
//             $row['delivery_places'] = "2";
//         } else {
//             $row['delivery_places'] = "";
//         }
//         $row['type'] = $variants[0]['type'];

//         $product[$i] = $row;
//         for ($k = 0; $k < count($variants); $k++) {
//             $variants[$k]['images'] = json_decode($variants[$k]['images'], 1);
//             $variants[$k]['images'] = (empty($variants[$k]['images'])) ? array() : $variants[$k]['images'];
//             for ($j = 0; $j < count($variants[$k]['images']); $j++) {
//                 $variants[$k]['images'][$j] = !empty(DOMAIN_URL . $variants[$k]['images'][$j]) ? DOMAIN_URL . $variants[$k]['images'][$j] : "";
//             }
//             $variants[$k]['serve_for'] =  $variants[$k]['stock'] <= 0 ? 'Sold Out' : $variants[$k]['serve_for'];
//         }
//         $product[$i]['variants'] = $variants;
//         $i++;
//     }
//     if (!empty($product)) {
//         $response['error'] = false;
//         $response['message'] = "Products retrieved successfully";
//         $response['total'] = $total[0]['total'];
//         $response['data'] = $product;
//     } else {
//         $response['error'] = true;
//         $response['message'] = "No data found!";
//     }
//     print_r(json_encode($response));
//     return false;
// }

/* 
---------------------------------------------------------------------------------------------------------
*/

/*
5.get_financial_statistics
    accesskey:90336
    get_financial_statistics:1
    seller_id:1
*/
if (isset($_POST['get_financial_statistics']) && !empty($_POST['get_financial_statistics'] == 1)) {
    if (empty($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Seller ID should be filled!";
        print_r(json_encode($response));
        return false;
    }

    $seller_id = (isset($_POST['seller_id']) && !empty($fn->xss_clean($_POST['seller_id'])) && is_numeric($_POST['seller_id'])) ? $db->escapeString($fn->xss_clean($_POST['seller_id'])) : "";
    $low_stock_limit = isset($config['low-stock-limit']) && (!empty($config['low-stock-limit'])) ? $config['low-stock-limit'] : 0;
    $total_orders = $total_products = $total_sold_out_products = $total_low_stock_count = 0;

    $sql = "SELECT categories FROM seller WHERE id = " . $seller_id;
    $db->sql($sql);
    $res = $db->getResult();

    $category_id = "";
    if (isset($res[0]['categories'])) {
        $category_ids = explode(',', $res[0]['categories']);
        $category_id = implode(',', $category_ids);

        $total_orders = $fn->rows_count('order_items', 'distinct(order_id)', "seller_id = $seller_id");

        if ($category_id != "") {
            $total_products = $fn->rows_count('products', '*', "seller_id= $seller_id AND category_id IN($category_id)");
        }
    }

    $total_sold_out_products = $fn->sold_out_count1($seller_id);
    $total_low_stock_count = $fn->low_stock_count1($low_stock_limit, $seller_id);

    $year = date("Y");
    $curdate = date('Y-m-d');

    $sql1 = "SELECT SUM(sub_total) AS total_sale,DATE(date_added) AS order_date FROM order_items WHERE YEAR(date_added) = '$year' AND DATE(date_added)<='$curdate' AND seller_id=$seller_id  and active_status = 'delivered' GROUP BY DATE(date_added) ORDER BY DATE(date_added) DESC  LIMIT 0,7";
    // echo $sql1;
    $db->sql($sql1);
    $result_order = $db->getResult();
    $total_sales = array_column($result_order, "total_sale");

    $response['error'] = false;
    $response['total_orders'] = $total_orders;
    $response['total_products'] = $total_products;
    $response['total_sold_out_products'] = $total_sold_out_products;
    $response['total_low_stock_count'] = $total_low_stock_count;
    $response['balance'] = $fn->get_seller_balance($seller_id);
    $response['currency'] = $fn->get_settings('currency');
    $response['total_sale'] = (!empty($result_order)) ? strval(array_sum($total_sales)) : "0";

    print_r(json_encode($response));
}
/* 
---------------------------------------------------------------------------------------------------------
*/

/*
6.update_seller_fcm_id
    accesskey:90336
    update_seller_fcm_id:1
    seller_id:1  
    fcm_id:YOUR_FCM_ID
*/
if (isset($_POST['update_seller_fcm_id']) && !empty($_POST['update_seller_fcm_id'] == 1)) {
    if (empty($_POST['fcm_id']) || empty($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields.";
        print_r(json_encode($response));
        return false;
    }
    $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $fcm_id = $db->escapeString($fn->xss_clean($_POST['fcm_id']));

    if (isset($_POST['fcm_id']) && !empty($_POST['fcm_id'])) {
        $sql = "update seller set `fcm_id` ='$fcm_id' where id = '$seller_id' ";
        if ($db->sql($sql)) {
            $response['error'] = false;
            $response['mesage'] = "Seller fcm_id updated succesfully";
        } else {
            $response['error'] = true;
            $response['message'] = "Can not update fcm_id of Seller";
        }
        print_r(json_encode($response));
    }
}
/* 
---------------------------------------------------------------------------------------------------------
*/

/*
7.get_seller_transactions
    accesskey:90336
    get_seller_transactions:1
    seller_id:1
    offset:0            // {optional}
    limit:10            // {optional}
    sort:id             // {optional}
    order:DESC / ASC    // {optional}
*/
if (isset($_POST['get_seller_transactions']) && !empty($_POST['get_seller_transactions'] == 1)) {
    if (empty($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Seller ID should be filled!";
        print_r(json_encode($response));
        return false;
    }
    $offset = (isset($_POST['offset']) && !empty($fn->xss_clean($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($fn->xss_clean($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'id';
    $order = (isset($_POST['order']) && !empty($fn->xss_clean($_POST['order']))) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'DESC';

    $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));

    if (!empty($seller_id)) {
        $sql = "SELECT count(id) as total from seller_wallet_transactions where seller_id=" . $seller_id;
        $db->sql($sql);
        $res = $db->getResult();
        $total = $res[0]['total'];

        $sql = "SELECT * FROM `seller_wallet_transactions` where seller_id= $seller_id ORDER BY $sort $order LIMIT $offset , $limit";
        $db->sql($sql);
        $res = $db->getResult();

        $response['error'] = false;
        $response['message'] = "Transcations retrived successfully";
        $response['total'] = $total;
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['order_id'] = !empty($res[$i]['order_id']) ? $res[$i]['order_id'] : "";
            $res[$i]['order_item_id'] = !empty($res[$i]['order_item_id']) ? $res[$i]['order_item_id'] : "";
        }
        $response['data'] = array_values($res);
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
}
/* 
---------------------------------------------------------------------------------------------------------
*/

/* 
8. get_orders
    accesskey:90336
    get_orders:1
    seller_id:1
    order_id:12608          // {optional}
    start_date:2020-06-05   // {optional} {YYYY-mm-dd}
    end_date:2020-06-05     // {optional} {YYYY-mm-dd}
    limit:10                // {optional}
    offset:0                // {optional}
    filter_order:received | processed | shipped | delivered | cancelled | returned | awaiting_payment    // {optional}
*/



if (isset($_POST['get_orders']) && !empty($_POST['get_orders'])) {
    if (empty($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Seller ID should be filled!";
        print_r(json_encode($response));
        return false;
    }


    if (isset($_POST['shipping_type']) && !empty($_POST['shipping_type'])) {
        $shipping_type = $_POST['shipping_type'];
        if ($shipping_type == 'standard') {
            $where = '';
            $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
            $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id']) && is_numeric($_POST['order_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_id'])) : "";
            $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
            $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

            if (isset($_POST['order_id']) && $_POST['order_id'] != '') {
                $where .= " AND oi.order_id= $order_id";
            }
            if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
                $start_date = $db->escapeString($fn->xss_clean($_POST['start_date']));
                $end_date = $db->escapeString($fn->xss_clean($_POST['end_date']));
                // $where .= " AND DATE(o.date_added)>=DATE('" . $start_date . "') AND DATE(o.date_added)<=DATE('" . $end_date . "')";
                $where .= " AND DATE(oi.date_added) >= '" . $start_date . "' AND DATE(oi.date_added) <= '" . $end_date . "'";
            }
            if (isset($_POST['filter_order']) && $_POST['filter_order'] != '') {
                $filter_order = $db->escapeString($fn->xss_clean($_POST['filter_order']));
                $where .= " AND oi.`active_status`='" . $filter_order . "'";
            }

            $sql = "select count(DISTINCT(order_id)) as total from order_items oi JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND  oi.seller_id=" . $seller_id . $where;
            $db->sql($sql);
            $res = $db->getResult();
            $total_count = $res[0]['total'];
            $sql = "select DISTINCT o.id,oi.seller_id, o.*,(select name from users u where u.id=o.user_id) as user_name from orders o JOIN order_items oi ON o.id=oi.order_id JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND  oi.seller_id=" . $seller_id . $where . " ORDER BY oi.date_added DESC LIMIT $offset,$limit";
            // echo $sql;
            $db->sql($sql);
            $res = $db->getResult();
            $i = 0;
            $j = 0;

            foreach ($res as $row) {
                $final_sub_total = 0;
                // print_r($row);
                if ($row['discount'] > 0) {
                    $discounted_amount = $row['total'] * $row['discount'] / 100;
                    $final_total = $row['total'] - $discounted_amount;
                    $discount_in_rupees = $row['total'] - $final_total;
                } else {
                    $discount_in_rupees = 0;
                }

                $res[$i]['discounted_price'] = strval($discount_in_rupees);
                $final_total = ceil($res[$i]['final_total']);
                $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $res[$i]['seller_id'], 'seller');
                $res_pincode = $fn->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
                $res_city = $fn->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
                $city = isset($res_city[0]['name']) && !empty($res_city[0]['name']) ? $res_city[0]['name'] . ' - ' : '';
                $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . ", " : "";
                $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . ", " : "";
                $pincode = (!empty($res_seller[0]['pincode_id'])) ? $city . $res_pincode[0]['pincode'] : "";
                $seller_address = $state  . $street . $pincode;
                $res[$i]['final_total'] = strval($final_total + $res[$i]['delivery_charge']);

                $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));
                $sql = "select oi.*,v.id as variant_id,v.weight, p.name,p.image,p.manufacturer,p.made_in,p.standard_shipping,p.pickup_location,p.return_status,p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where p.standard_shipping=1 and oi.order_id=" . $row['id'] . " AND oi.seller_id=$seller_id ";
                $db->sql($sql);
                $res[$i]['items'] = $db->getResult();
                $res[$i]['status'] = "";
                $res[$i]['active_status'] = "";
                for ($j = 0; $j < count($res[$i]['items']); $j++) {
                    // print_r($res[$i]['items']);
                    unset($res[$i]['items'][$j]['status']);
                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                    }

                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];
                    $res[$i]['items'][$j]['shipping_method'] = "standard";
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
                    $res[$i]['items'][$j]['seller_mobile'] = $res_seller[0]['mobile'];
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
                    $res[$i]['items'][$j]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
                    if (!empty($res[$i]['items'][$j]['delivery_boy_id'])) {
                        $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $res[$i]['items'][$j]['delivery_boy_id'], 'delivery_boys');
                        $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
                    } else {
                        $res[$i]['items'][$j]['delivery_boy_name'] = "";
                    }
                    $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
                    $res[$i]['items'][$j]['pickup_location'] = !empty($res[$i]['items'][$j]['pickup_location']) ?    $res[$i]['items'][$j]['pickup_location'] : "";
                    $order_tracking_data = [];
                    $order_tracking_data = $fn->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');
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
                }
                $res[$i]['final_total'] = strval($row['final_total']);
                $res[$i]['total'] = strval($row['total']);
                $i++;
            }



            $orders = $order = array();
            if (!empty($res)) {
                $orders['error'] = false;
                $orders['total'] = $total_count;
                $orders['data'] = array_values($res);
                print_r(json_encode($orders));
            } else {
                $res['error'] = true;
                $res['message'] = "No orders found!";
                print_r(json_encode($res));
                return false;
            }
        } else {
            $where = '';
            $count_local_ors = 0;
            $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
            $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id']) && is_numeric($_POST['order_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_id'])) : "";
            $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
            $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

            if (isset($_POST['order_id']) && $_POST['order_id'] != '') {
                $where .= " AND oi.order_id= $order_id";
            }
            if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
                $start_date = $db->escapeString($fn->xss_clean($_POST['start_date']));
                $end_date = $db->escapeString($fn->xss_clean($_POST['end_date']));
                // $where .= " AND DATE(o.date_added)>=DATE('" . $start_date . "') AND DATE(o.date_added)<=DATE('" . $end_date . "')";
                $where .= " AND DATE(oi.date_added) >= '" . $start_date . "' AND DATE(oi.date_added) <= '" . $end_date . "'";
            }
            if (isset($_POST['filter_order']) && $_POST['filter_order'] != '') {
                $filter_order = $db->escapeString($fn->xss_clean($_POST['filter_order']));
                $where .= " AND oi.`active_status`='" . $filter_order . "'";
            }


            $sql = "select count(DISTINCT(order_id)) as total from order_items oi JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=0 AND  oi.seller_id=" . $seller_id . $where;
            $db->sql($sql);
            $res = $db->getResult();
            $total_count = $res[0]['total'];
            $sql = "select DISTINCT o.id,oi.seller_id, o.*,(select name from users u where u.id=o.user_id) as user_name from orders o JOIN order_items oi ON o.id=oi.order_id JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=0 AND  oi.seller_id=" . $seller_id . $where . " ORDER BY oi.date_added DESC LIMIT $offset,$limit";
            // echo $sql;
            $db->sql($sql);
            $res = $db->getResult();
            $i = 0;
            $j = 0;

            foreach ($res as $row) {
                $final_sub_total = 0;
                // print_r($row);
                if ($row['discount'] > 0) {
                    $discounted_amount = $row['total'] * $row['discount'] / 100;
                    $final_total = $row['total'] - $discounted_amount;
                    $discount_in_rupees = $row['total'] - $final_total;
                } else {
                    $discount_in_rupees = 0;
                }

                $res[$i]['discounted_price'] = strval($discount_in_rupees);
                $final_total = ceil($res[$i]['final_total']);
                $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $res[$i]['seller_id'], 'seller');
                $res_pincode = $fn->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
                $res_city = $fn->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
                $city = isset($res_city[0]['name']) && !empty($res_city[0]['name']) ? $res_city[0]['name'] . ' - ' : '';
                $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . ", " : "";
                $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . ", " : "";
                $pincode = (!empty($res_seller[0]['pincode_id'])) ? $city . $res_pincode[0]['pincode'] : "";
                $seller_address = $state  . $street . $pincode;
                $res[$i]['final_total'] = strval($final_total + $res[$i]['delivery_charge']);
                $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));

                $sql = "select oi.*,v.id as variant_id,v.weight, p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.pickup_location, p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where oi.order_id=" . $row['id'] . " AND oi.seller_id=$seller_id";
                $db->sql($sql);
                $res[$i]['items'] = $db->getResult();


                for ($j = 0; $j < count($res[$i]['items']); $j++) {

                    unset($res[$i]['items'][$j]['status']);
                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                    }
                    $res[$i]['items'][$j]['pickup_location'] = "";
                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];
                    $res[$i]['items'][$j]['shipping_method'] = "local";

                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
                    $res[$i]['items'][$j]['seller_mobile'] = $res_seller[0]['mobile'];
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
                    $res[$i]['items'][$j]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
                    if (!empty($res[$i]['items'][$j]['delivery_boy_id'])) {
                        $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $res[$i]['items'][$j]['delivery_boy_id'], 'delivery_boys');
                        $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
                    } else {
                        $res[$i]['items'][$j]['delivery_boy_name'] = "";
                    }
                    $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';

                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];

                    $res[$i]['items'][$j]['shipment_id'] = "";
                    $res[$i]['items'][$j]['shipment_id'] = "";
                    $res[$i]['items'][$j]['awb_code'] = "";
                    $res[$i]['items'][$j]['pickup_status'] = "";
                    $res[$i]['items'][$j]['is_canceled'] = "";
                }
                $res[$i]['final_total'] = strval($row['final_total']);
                $res[$i]['total'] = strval($row['total']);
                $i++;
            }
            $orders = $order = array();

            if (!empty($res)) {
                $orders['error'] = false;
                $orders['total'] = $total_count;
                $orders['data'] = array_values($res);
                print_r(json_encode($orders));
            } else {
                $res['error'] = true;
                $res['message'] = "No orders found!";
                print_r(json_encode($res));
                return false;
            }
        }
    } else {

        $where = '';

        $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
        $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id']) && is_numeric($_POST['order_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_id'])) : "";
        $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
        $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

        if (isset($_POST['order_id']) && $_POST['order_id'] != '') {
            $where .= " AND oi.order_id= $order_id";
        }
        if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
            $start_date = $db->escapeString($fn->xss_clean($_POST['start_date']));
            $end_date = $db->escapeString($fn->xss_clean($_POST['end_date']));
            // $where .= " AND DATE(o.date_added)>=DATE('" . $start_date . "') AND DATE(o.date_added)<=DATE('" . $end_date . "')";
            $where .= " AND DATE(oi.date_added) >= '" . $start_date . "' AND DATE(oi.date_added) <= '" . $end_date . "'";
        }
        if (isset($_POST['filter_order']) && $_POST['filter_order'] != '') {
            $filter_order = $db->escapeString($fn->xss_clean($_POST['filter_order']));
            $where .= " AND oi.`active_status`='" . $filter_order . "'";
        }

        $sql = "select count(DISTINCT(order_id)) as total from order_items oi where oi.seller_id=" . $seller_id . $where;
        $db->sql($sql);
        $res = $db->getResult();
        $total_count = $res[0]['total'];
        $sql = "select DISTINCT o.id,oi.seller_id, o.*,(select name from users u where u.id=o.user_id) as user_name from orders o JOIN order_items oi ON o.id=oi.order_id where oi.seller_id=" . $seller_id . $where . " ORDER BY oi.date_added DESC LIMIT $offset,$limit";
        // echo $sql;
        $db->sql($sql);
        $res = $db->getResult();
        $i = 0;
        $j = 0;
        foreach ($res as $row) {
            $final_sub_total = 0;
            // print_r($row);
            if ($row['discount'] > 0) {
                $discounted_amount = $row['total'] * $row['discount'] / 100;
                $final_total = $row['total'] - $discounted_amount;
                $discount_in_rupees = $row['total'] - $final_total;
            } else {
                $discount_in_rupees = 0;
            }

            $res[$i]['discounted_price'] = strval($discount_in_rupees);
            $final_total = ceil($res[$i]['final_total']);
            $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $res[$i]['seller_id'], 'seller');
            $res_pincode = $fn->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
            $res_city = $fn->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
            $city = isset($res_city[0]['name']) && !empty($res_city[0]['name']) ? $res_city[0]['name'] . ' - ' : '';
            $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . ", " : "";
            $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . ", " : "";
            $pincode = (!empty($res_seller[0]['pincode_id'])) ? $city . $res_pincode[0]['pincode'] : "";
            $seller_address = $state  . $street . $pincode;
            $res[$i]['final_total'] = strval($final_total + $res[$i]['delivery_charge']);
            $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));

            $sql = "select oi.*,v.id as variant_id, v.weight,p.name,p.image,p.standard_shipping,p.manufacturer,p.made_in,p.return_status,p.pickup_location,p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where oi.order_id=" . $row['id'] . " AND oi.seller_id=$seller_id";
            $db->sql($sql);
            $res[$i]['items'] = $db->getResult();


            for ($j = 0; $j < count($res[$i]['items']); $j++) {
                if ($res[$i]['items'][$j]['standard_shipping'] == 1) {
                    $res[$i]['status'] = "";
                    $res[$i]['active_status'] = "";

                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                    }
                    $res[$i]['items'][$j]['status'] = "";
                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];
                    $res[$i]['items'][$j]['shipping_method'] = "standard";
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
                    $res[$i]['items'][$j]['seller_mobile'] = $res_seller[0]['mobile'];
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
                    $res[$i]['items'][$j]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
                    if (!empty($res[$i]['items'][$j]['delivery_boy_id'])) {
                        $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $res[$i]['items'][$j]['delivery_boy_id'], 'delivery_boys');
                        $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
                    } else {
                        $res[$i]['items'][$j]['delivery_boy_name'] = "";
                    }
                    $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';

                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];

                    $order_tracking_data = $fn->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');
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


                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                    }

                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];
                    $res[$i]['items'][$j]['shipping_method'] = "local";
                    $res[$i]['items'][$j]['pickup_location'] = "";
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
                    $res[$i]['items'][$j]['seller_mobile'] = $res_seller[0]['mobile'];
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
                    $res[$i]['items'][$j]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
                    if (!empty($res[$i]['items'][$j]['delivery_boy_id'])) {
                        $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $res[$i]['items'][$j]['delivery_boy_id'], 'delivery_boys');
                        $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
                    } else {
                        $res[$i]['items'][$j]['delivery_boy_name'] = "";
                    }
                    $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';

                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];

                    $res[$i]['items'][$j]['shipment_id'] = "";
                    $res[$i]['items'][$j]['shipment_id'] = "";
                    $res[$i]['items'][$j]['awb_code'] = "";
                    $res[$i]['items'][$j]['pickup_status'] = "";
                    $res[$i]['items'][$j]['is_canceled'] = "";
                }
            }
            $res[$i]['final_total'] = strval($row['final_total']);
            $res[$i]['total'] = strval($row['total']);
            $i++;
        }
        $orders = $order = array();


        if (!empty($res)) {
            $orders['error'] = false;
            $orders['total'] = $total_count;
            $orders['data'] = array_values($res);
            print_r(json_encode($orders));
        } else {
            $res['error'] = true;
            $res['message'] = "No orders found!";
            print_r(json_encode($res));
            return false;
        }
    }
}

/* 
---------------------------------------------------------------------------------------------------------
*/

/*
9.  update_order_status
    accesskey:90336
    <update_order_status:1></update_order_status:1>
    order_id:169
    seller_id:1
    order_item_id:12577
    delivery_boy_id:12577
    status:received | processed | shipped | delivered | cancelled | returned
*/
if (isset($_POST['update_order_status']) && !empty($_POST['update_order_status'] == 1)) {

    if (empty($_POST['order_id']) || empty($_POST['seller_id']) || empty($_POST['order_item_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all mandatory fields!";
        print_r(json_encode($response));
        return false;
    }

    $id = $db->escapeString(trim($fn->xss_clean($_POST['order_id'])));
    $postStatus = isset($_POST['status']) && !empty($_POST['status']) ? $db->escapeString(trim($fn->xss_clean(($_POST['status'])))) : '';
    $seller_id = $db->escapeString(trim($fn->xss_clean(($_POST['seller_id']))));
    $order_item_ids = $db->escapeString(trim($fn->xss_clean(($_POST['order_item_id']))));
    $delivery_boy_id = isset($_POST['delivery_boy_id']) && !empty($_POST['delivery_boy_id']) ? $db->escapeString(trim($fn->xss_clean(($_POST['delivery_boy_id'])))) : 0;
    $sql = "SELECT * from order_items where id in ($order_item_ids) AND order_id=$id";
    $db->sql($sql);
    $res = $db->getResult();
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

    if (isset($_POST['order_id']) && $_POST['order_id'] != '') {
        $where .= " AND oi.order_id= $order_id";
    }
    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $start_date = $db->escapeString($fn->xss_clean($_POST['start_date']));
        $end_date = $db->escapeString($fn->xss_clean($_POST['end_date']));
        // $where .= " AND DATE(o.date_added)>=DATE('" . $start_date . "') AND DATE(o.date_added)<=DATE('" . $end_date . "')";
        $where .= " AND DATE(oi.date_added) >= '" . $start_date . "' AND DATE(oi.date_added) <= '" . $end_date . "'";
    }
    if (isset($_POST['filter_order']) && $_POST['filter_order'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_POST['filter_order']));
        $where .= " AND oi.`active_status`='" . $filter_order . "'";
    }


    $order_item_ids = explode(',', $order_item_ids);
    $response = $fn->update_bulk_order_items($order_item_ids, $postStatus, $delivery_boy_id);
    $response = json_decode($response, 1);
    $order_item_ids = $db->escapeString(trim($fn->xss_clean(($_POST['order_item_id']))));




    $sql = "select count(DISTINCT(order_id)) as total from order_items oi JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=0 AND o.id=$id and oi.id in ($order_item_ids) and and  oi.seller_id=" . $seller_id;
    $db->sql($sql);
    $res = $db->getResult();
    $total_count = $res[0]['total'];
    $sql = "select DISTINCT o.id,oi.seller_id, o.*,(select name from users u where u.id=o.user_id) as user_name from orders o JOIN order_items oi ON o.id=oi.order_id JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=0 AND  o.id=$id  and oi.seller_id=" . $seller_id . " ORDER BY oi.date_added DESC LIMIT $offset,$limit";
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    $i = 0;
    $j = 0;


    foreach ($res as $row) {
        $final_sub_total = 0;
        // print_r($row);
        if ($row['discount'] > 0) {
            $discounted_amount = $row['total'] * $row['discount'] / 100;
            $final_total = $row['total'] - $discounted_amount;
            $discount_in_rupees = $row['total'] - $final_total;
        } else {
            $discount_in_rupees = 0;
        }

        $res[$i]['discounted_price'] = strval($discount_in_rupees);
        $final_total = ceil($res[$i]['final_total']);
        $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $res[$i]['seller_id'], 'seller');
        $res_pincode = $fn->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
        $res_city = $fn->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
        $city = isset($res_city[0]['name']) && !empty($res_city[0]['name']) ? $res_city[0]['name'] . ' - ' : '';
        $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . ", " : "";
        $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . ", " : "";
        $pincode = (!empty($res_seller[0]['pincode_id'])) ? $city . $res_pincode[0]['pincode'] : "";
        $seller_address = $state  . $street . $pincode;
        $res[$i]['final_total'] = strval($final_total + $res[$i]['delivery_charge']);
        $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));

        $sql = "select oi.*,v.id as variant_id,v.weight, p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.pickup_location, p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where  oi.id in ($order_item_ids) AND oi.seller_id=$seller_id";
        $db->sql($sql);
        $res[$i]['items'] = $db->getResult();


        for ($j = 0; $j < count($res[$i]['items']); $j++) {


            if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                $final_sub_total += $res[$i]['items'][$j]['sub_total'];
            }
            $res[$i]['items'][$j]['pickup_location'] = "";
            if (!empty($res[$i]['items'][$j]['seller_id'])) {
                $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
            } else {
                $res[$i]['items'][$j]['seller_id'] = "";
                $res[$i]['items'][$j]['seller_name'] = "";
                $res[$i]['items'][$j]['seller_store_name'] = "";
            }
            $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];
            $res[$i]['items'][$j]['shipping_method'] = "local";

            $res[$i]['items'][$j]['seller_address'] = $seller_address;
            $res[$i]['items'][$j]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
            $res[$i]['items'][$j]['seller_mobile'] = $res_seller[0]['mobile'];
            $res[$i]['items'][$j]['seller_address'] = $seller_address;
            $res[$i]['items'][$j]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
            $res[$i]['items'][$j]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
            if (!empty($delivery_boy_id)) {
                $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $delivery_boy_id, 'delivery_boys');
                $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
            } else {
                $res[$i]['items'][$j]['delivery_boy_name'] = "";
            }
            $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
            $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';

            $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];

            $res[$i]['items'][$j]['shipment_id'] = "";
        }
        $res[$i]['final_total'] = strval($row['final_total']);
        $res[$i]['total'] = strval($row['total']);
        $i++;
    }

    $orders = $order = array();
    if (empty($res)) {

        $response['error'] = true;
        $response['message'] = "No items found on this order";
        print_r(json_encode($response));
        return;
    }

    if (!$response['error']) {

        $message = $response["message"];
        $response['error'] = false;
        $response['message'] = strip_tags($message);
        $response['data'] = $res;
    } else {
        $message = $response["message"];
        $response['error'] = true;
        $response['message'] = strip_tags($message);
    }
    print_r(json_encode($response));
}

/* 
---------------------------------------------------------------------------------------------------------
*/

/* 10.add_products
     
    accesskey:90336
    add_products:1
    seller_id:1
    name:chocolate-boxes            
    category_id:31 
    description:chocolates
    till_status: received 
   
    shipping_type=standard or local 

    pickup_location:mirzapur-bhuj-sector-1

    delivery_places:0 OR 1 OR 2 [ 0=included, 1=excluded, 2=all ]
    pincodes:1,4,5                 //{must blank when delivery_places=2}

    indicator:0 
    subcategory_id:115          // {optional}
    return_days:7 {optional}
    tax_id:4                    // {optional}
    manufacturer:india          // {optional}
    made_in:india               // {optional}
    return_status:0 / 1         // {optional}
    cancelable_status:0 / 1     // {optional}
    till_status:received / processed / shipped           // {optional}
    indicator:0 - none / 1 - veg / 2 - non-veg          // {optional}
    image:FILE          
    other_images[]:FILE

    type:packet
    measurement:500,400
    measurement_unit_id:4,1
    price:175,145
    discounted_price:60,30    // {optional} 
    serve_for:Available,sold out
    stock:992,225
    stock_unit_id:4,1   
    weight:1,1.5
    height:10,10
    breadth:10,10
    length:10,10
    images[0][] : FILE           // {optional}
    images[1][] : FILE           // {optional}         
    
    type:loose
    measurement:1,1
    measurement_unit_id:1,5
    price:100,400
    discounted_price:20,15       // {optional}
    serve_for:Available/Sold Out
    stock:992,225
    stock_unit_id:4,1   
    weight:1,1.5
    height:10,10
    breadth:10,10
    length:10,10
    images[0][] : FILE           // {optional}
    images[1][] : FILE           // {optional}
*/

if (isset($_POST['add_products']) && !empty($_POST['add_products'])) {

    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response['error'] = true;
        $response['message'] = "This operation is not allowed in demo panel!.!";
        print_r(json_encode($response));
        return false;
    }

    $res_msg = "";
    $res_msg .= (empty($_POST['name']) || $_POST['name'] == "") ? "name," : "";
    $res_msg .= (empty($_POST['seller_id']) || $_POST['seller_id'] == "") ? "seller_id," : "";
    $res_msg .= (empty($_POST['category_id']) || $_POST['category_id'] == "") ? "category_id," : "";
    $res_msg .= (empty($_FILES['image']) || $_FILES['image'] == "") ? "image," : "";
    $res_msg .= (empty($_POST['description']) || $_POST['description'] == "") ? "description," : "";
    $res_msg .= (empty($_POST['type']) || $_POST['type'] == "") ? "type," : "";

    if (!empty($_POST['type']) || $_POST['type'] != "") {
        if ($_POST['type'] == "packet") {
            $res_msg .= (empty($_POST['measurement']) || $_POST['measurement'] == "") ? "measurement," : "";
            $res_msg .= (empty($_POST['measurement_unit_id']) || $_POST['measurement_unit_id'] == "") ? "measurement_unit_id," : "";
            $res_msg .= (empty($_POST['price']) || $_POST['price'] == "") ? "price," : "";
            $res_msg .= (empty($_POST['serve_for']) || $_POST['serve_for'] == "") ? "serve_for," : "";
            $res_msg .= (empty($_POST['stock']) || $_POST['stock'] == "") ? "stock," : "";
            $res_msg .= (empty($_POST['stock_unit_id']) || $_POST['stock_unit_id'] == "") ? "stock_unit_id," : "";
        } else if ($_POST['type'] == "loose") {
            $res_msg .= (empty($_POST['measurement']) || $_POST['measurement'] == "") ? "measurement," : "";
            $res_msg .= (empty($_POST['measurement_unit_id']) || $_POST['measurement_unit_id'] == "") ? "measurement_unit_id," : "";
            $res_msg .= (empty($_POST['price']) || $_POST['price'] == "") ? "price," : "";
            $res_msg .= (empty($_POST['serve_for']) || $_POST['serve_for'] == "") ? "serve_for," : "";
            $res_msg .= (empty($_POST['stock']) || $_POST['stock'] == "") ? "stock," : "";
            $res_msg .= (empty($_POST['stock_unit_id']) || $_POST['stock_unit_id'] == "") ? "stock_unit_id," : "";
        }
    }

    if (isset($_POST['shipping_type']) && $_POST['shipping_type'] == 'standard') {
        $res_msg .= empty($_POST['pickup_location']) ? 'pickup_location' : "";
    } else {
        $res_msg .= ($_POST['delivery_places'] == "") ? "delivery_places," : "";
        $res_msg .= ((empty($_POST['pincodes']) || $_POST['pincodes'] == "") && $_POST['delivery_places'] != "2") ? "pincodes," : "";
    }

    if ($res_msg != "") {
        $response['error'] = true;
        $response['message'] = "This fields " . trim($res_msg, ",") . " should be Passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $tax_id = (isset($_POST['tax_id']) && $_POST['tax_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['tax_id'])) : '0';
    $return_days = (isset($_POST['return_days']) && $_POST['return_days'] != '') ? $db->escapeString($fn->xss_clean($_POST['return_days'])) : 0;
    $slug = $function->slugify($db->escapeString($fn->xss_clean($_POST['name'])));
    $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
    $subcategory_id = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : 0;
    $description = $db->escapeString($fn->xss_clean($_POST['description']));
    $manufacturer = (isset($_POST['manufacturer']) && $_POST['manufacturer'] != '') ? $db->escapeString($fn->xss_clean($_POST['manufacturer'])) : '';
    $made_in = (isset($_POST['made_in']) && $_POST['made_in'] != '') ? $db->escapeString($fn->xss_clean($_POST['made_in'])) : '';
    $indicator = (isset($_POST['indicator']) && $_POST['indicator'] != '') ? $db->escapeString($fn->xss_clean($_POST['indicator'])) : '0';
    $return_status = (isset($_POST['return_status']) && $_POST['return_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['return_status'])) : '0';
    $cancelable_status = (isset($_POST['cancelable_status']) && $_POST['cancelable_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['cancelable_status'])) : '0';
    $till_status = (isset($_POST['till_status']) && $_POST['till_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['till_status'])) : '';
    $loose_stock = (isset($_POST['stock']) && $_POST['stock'] != '') ? $db->escapeString($fn->xss_clean($_POST['stock'])) : '';
    $loose_stock_unit_id = (isset($_POST['stock_unit_id']) && $_POST['stock_unit_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['stock_unit_id'])) : '';
    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $cod_allowed = (isset($_POST['cod_allowed']) && $_POST['cod_allowed'] != '') ? $db->escapeString($fn->xss_clean($_POST['cod_allowed'])) : '1';

    $seller_data = $fn->get_data($columns = ['require_products_approval'], 'id=' . $seller_id, 'seller');
    $pr_approval = $seller_data[0]['require_products_approval'];
    $is_approved = ($pr_approval == 0) ? 1 : 0;

    $image = (isset($_POST['image'])) ? $db->escapeString($fn->xss_clean($_FILES['image']['name'])) : '';
    $image_error = (isset($_POST['image_error'])) ? $db->escapeString($fn->xss_clean($_FILES['image']['error'])) : '';
    $image_type = (isset($_POST['image_type'])) ? $db->escapeString($fn->xss_clean($_FILES['image']['type'])) : '';

    $allowedExts = array("gif", "jpeg", "jpg", "png");

    error_reporting(E_ERROR | E_PARSE);
    $extension = end(explode(".", $_FILES["image"]["name"]));
    $error['other_images'] = $error['image'] = '';


    // if standard shipping so add product in start shipping 
    if ($_POST['shipping_type'] == 'standard') {
        $standard_shipping = 1;
        $pickup_location = (isset($_POST['pickup_location']) && !empty($_POST['pickup_location'])) ? $db->escapeString($fn->xss_clean($_POST['pickup_location'])) : 0;
        $check = $fn->get_data(['*'], "pickup_location='$pickup_location'", 'pickup_locations');
        if (empty($check)) {
            $response['error'] = true;
            $response['message'] = "Pickup location not found";
            print_r(json_encode($response));
            return false;
            exit();
        }
        $pincodes = 0;
    } else {
        $standard_shipping = 0;
        $pickup_location = "";
        $d_type = "";
        $pincode_type = (isset($_POST['delivery_places']) && $_POST['delivery_places'] != '') ? $db->escapeString($fn->xss_clean($_POST['delivery_places'])) : '';
        if ($pincode_type == "2") {
            $d_type = "all";
            $pincodes = "";
        } else {
            if ($pincode_type == "0") {
                $d_type = "included";
            } else if ($pincode_type == "1") {
                $d_type = "excluded";
            }
            $pincodes = $db->escapeString($fn->xss_clean($_POST['pincodes']));
        }
    }


    $discounted_price1 = (!empty($_POST['discounted_price'])) ? $db->escapeString($fn->xss_clean($_POST['discounted_price'])) : '0';
    $price1 = $db->escapeString($fn->xss_clean($_POST['price']));
    $discounted_price = explode(",", $discounted_price1);
    $price = explode(",", $price1);

    for ($i = 0; $i < count($discounted_price); $i++) {
        $discounted_price1 = (!empty($discounted_price)) ? $discounted_price[$i] : '0';
        if ($discounted_price[$i] > $price[$i]) {
            $response['error'] = true;
            $response['message'] = "Discounted price can not be greater than price";
            print_r(json_encode($response));
            return false;
        }
    }

    if ($image_error > 0) {
        $response['error'] = true;
        $response['message'] = "Image Not uploaded!";
        print_r(json_encode($response));
        return false;
    } else {
        $result = $fn->validate_image($_FILES["image"]);
        if (!$result) {
            $response['error'] = true;
            $response['message'] = "image type must jpg, jpeg, gif, or png!";
            print_r(json_encode($response));
            return false;
        }
    }

    if ($_FILES["other_images"]["error"] == 0) {
        for ($i = 0; $i < count($_FILES["other_images"]["name"]); $i++) {
            if ($_FILES["other_images"]["error"][$i] > 0) {
                $response['error'] = true;
                $response['message'] = "Other Images not uploaded!";
                print_r(json_encode($response));
                return false;
            } else {
                $result = $fn->validate_other_images($_FILES["other_images"]["tmp_name"][$i], $_FILES["other_images"]["type"][$i]);
                if (!$result) {
                    $response['error'] = true;
                    $response['message'] = "other image type must jpg, jpeg, gif, or png!";
                    print_r(json_encode($response));
                    return false;
                }
            }
        }
    }

    $string = '0123456789';
    $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);

    $image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;
    $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../../upload/images/' . $image);
    $other_images = '';

    if (isset($_FILES['other_images']) && ($_FILES['other_images']['size'][0] > 0)) {
        $file_data = array();
        $target_path = '../../upload/other_images/';
        $target_path1 = 'upload/other_images/';

        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        for ($j = 0; $j < count($_FILES["other_images"]["name"]); $j++) {
            $filename = $_FILES["other_images"]["name"][$j];
            $temp = explode('.', $filename);
            $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
            $file_data[] = $target_path1 . '' . $filename;
            if (!move_uploaded_file($_FILES["other_images"]["tmp_name"][$j], $target_path . '' . $filename)) {
                $response['error'] = true;
                $response['message'] = "Other Images not uploaded!";
                print_r(json_encode($response));
                return false;
            }
        }
        $other_images = json_encode($file_data);
    }
    $upload_image = 'upload/images/' . $image;

    $sql = "INSERT INTO products (name,tax_id,seller_id,slug,category_id,subcategory_id,image,other_images,description,indicator,manufacturer,made_in,return_status,cancelable_status, till_status,type,pincodes,is_approved,return_days,pickup_location,standard_shipping,cod_allowed) VALUES('$name','$tax_id','$seller_id','$slug','$category_id','$subcategory_id','$upload_image','$other_images','$description','$indicator','$manufacturer','$made_in','$return_status','$cancelable_status','$till_status','$d_type','$pincodes','$is_approved','$return_days','$pickup_location','$standard_shipping','$cod_allowed')";

    if ($db->sql($sql)) {
        $res_inner = $fn->get_data($columns = ['id'], 'seller_id=' . $seller_id, 'products', '0', '1', 'id', 'DESC');

        $product_id = $db->escapeString($res_inner[0]['id']);
        $type = $db->escapeString($fn->xss_clean($_POST['type']));

        $measurement1 = $db->escapeString($fn->xss_clean($_POST['measurement']));
        $measurement_unit_id1 = $db->escapeString($fn->xss_clean($_POST['measurement_unit_id']));
        $price1 = $db->escapeString($fn->xss_clean($_POST['price']));
        $discounted_price1 = (!empty($_POST['discounted_price'])) ? $db->escapeString($fn->xss_clean($_POST['discounted_price'])) : '0';
        $stock1 = $db->escapeString($fn->xss_clean($_POST['stock']));
        $serve_for = $db->escapeString($fn->xss_clean($_POST['serve_for']));
        $stock_unit_id1 = $db->escapeString($fn->xss_clean($_POST['stock_unit_id']));
        $weight1 = $db->escapeString($fn->xss_clean($_POST['weight']));
        $height1 = $db->escapeString($fn->xss_clean($_POST['height']));
        $breadth1 = $db->escapeString($fn->xss_clean($_POST['breadth']));
        $length1 = $db->escapeString($fn->xss_clean($_POST['length']));

        $measurement = explode(",", $measurement1);
        $measurement_unit_id = explode(",", $measurement_unit_id1);
        $price = explode(",", $price1);
        $discounted_price = explode(",", $discounted_price1);
        $stock = explode(",", $stock1);
        $serve_for = explode(",", $serve_for);
        $stock_unit_id = explode(",", $stock_unit_id1);
        $weight = explode(",", $weight1);
        $height = explode(",", $height1);
        $breadth = explode(",", $breadth1);
        $length = explode(",", $length1);

        if ($type == 'packet') {
            if (!(count($measurement) == count($measurement_unit_id) && count($measurement_unit_id) == count($price) && count($price) == count($stock) && count($stock) == count($serve_for) && count($serve_for) == count($stock_unit_id))) {
                $response['error'] = true;
                $response['message'] = "Pass correct count for variants";
                print_r(json_encode($response));
                return false;
                exit();
            }

            $v_ids = array();
            for ($i = 0; $i < count($measurement); $i++) {
                $variant_other_images = '';
                if ($_FILES["images"]["error"][$i][0] == 0) {
                    for ($j = 0; $j < count($_FILES["images"]["name"][$i]); $j++) {
                        if ($_FILES["images"]["error"][$i][$j] > 0) {
                            $error['images'] = "Variant Images not uploaded!";
                        } else {
                            $result = $fn->validate_other_images($_FILES["images"]["tmp_name"][$i][$j], $_FILES["images"]["type"][$i][$j]);
                            if ($result) {
                                $error['images'] = "Variant Image type must jpg, jpeg, gif, or png!";
                            }
                        }
                    }
                }

                if (isset($_FILES['images']) && (!empty($_FILES['images']['name'][$i][0])) && ($_FILES['images']['size'][$i][0] > 0)) {
                    $file_data = array();
                    $target_path1 = '../../upload/variant_images/';
                    $target_path = 'upload/variant_images/';
                    if (!is_dir($target_path1)) {
                        mkdir($target_path1, 0777, true);
                    }

                    for ($k = 0; $k < count($_FILES["images"]["name"][$i]); $k++) {
                        $filename = $_FILES["images"]["name"][$i][$k];
                        $temp = explode('.', $filename);
                        $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                        $file_data[] = $target_path . '' . $filename;
                        if (!move_uploaded_file($_FILES["images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                            echo "{$_FILES['images']['name'][$i][$k]} not uploaded<br/>";
                    }
                    $variant_other_images = json_encode($file_data);
                    $variant_other_images  = str_replace('"', "'", $variant_other_images);
                }

                $serve_for_lbl = ($stock[$i] == 0) ? 'Sold Out' : $serve_for[$i];

                $data = array(
                    'type' => $type,
                    'product_id' => $product_id,
                    'measurement' => $measurement[$i],
                    'measurement_unit_id' => $measurement_unit_id[$i],
                    'price' => $price[$i],
                    'discounted_price' => (!empty($discounted_price[$i])) ? $discounted_price[$i] : "0",
                    'serve_for' => $serve_for_lbl,
                    'stock' => $stock[$i],
                    'weight' => $weight[$i],
                    'height' => $height[$i],
                    'length' => $length[$i],
                    'breadth' => $breadth[$i],
                    'stock_unit_id' => $stock_unit_id[$i],
                    'images' => $variant_other_images,
                );
                $db->insert('product_variant', $data);
                $res4 = $db->getResult();
                $v_ids[] = $res4[0];
            }
            if (!empty($res4)) {
                $response['error'] = false;
                $response['message'] = "Product of packet variant Added";
            } else {
                $response['error'] = true;
                $response['message'] = "Product of packet variant Not Added";
            }
        } elseif ($type == 'loose') {

            $serve_for_loose = ($loose_stock == 0) ? 'Sold Out' : $db->escapeString($fn->xss_clean($_POST['serve_for']));

            for ($i = 0; $i < count($measurement); $i++) {
                $variant_other_images = '';

                if ($_FILES["images"]["error"][$i][0] == 0) {
                    for ($j = 0; $j < count($_FILES["images"]["name"][$i]); $j++) {
                        if ($_FILES["images"]["error"][$i][$j] > 0) {
                            $error['images'] = "Variant Images not uploaded!";
                        } else {
                            $result = $fn->validate_other_images($_FILES["images"]["tmp_name"][$i][$j], $_FILES["images"]["type"][$i][$j]);
                            if ($result) {
                                $error['images'] = "Variant Image type must jpg, jpeg, gif, or png!";
                            }
                        }
                    }
                }

                if (isset($_FILES['images']) && (!empty($_FILES['images']['name'][$i][0])) && ($_FILES['images']['size'][$i][0] > 0)) {
                    $file_data = array();
                    $target_path1 = '../../upload/variant_images/';
                    $target_path = 'upload/variant_images/';
                    if (!is_dir($target_path1)) {
                        mkdir($target_path1, 0777, true);
                    }

                    for ($k = 0; $k < count($_FILES["images"]["name"][$i]); $k++) {
                        $filename = $_FILES["images"]["name"][$i][$k];
                        $temp = explode('.', $filename);
                        $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                        $file_data[] = $target_path . '' . $filename;
                        if (!move_uploaded_file($_FILES["images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                            echo "{$_FILES['images']['name'][$i][$k]} not uploaded<br/>";
                    }
                    $variant_other_images = json_encode($file_data);
                    $variant_other_images  = str_replace('"', "'", $variant_other_images);
                }

                $data = array(
                    'type' => $type,
                    'product_id' => $product_id,
                    'measurement' => $measurement[$i],
                    'measurement_unit_id' => $measurement_unit_id[$i],
                    'price' => $price[$i],
                    'discounted_price' => $discounted_price[$i],
                    'serve_for' => $serve_for_loose,
                    'stock' => $loose_stock,
                    'weight' => $weight[$i],
                    'height' => $height[$i],
                    'length' => $length[$i],
                    'breadth' => $breadth[$i],
                    'stock_unit_id' => $loose_stock_unit_id,
                    'images' => $variant_other_images,
                );
                $db->insert('product_variant', $data);
                $res4 = $db->getResult();
                $v_ids[] = $res4[0];
            }
            if (!empty($res4)) {
                $response['error'] = false;
                $response['message'] = "Product of loose variant Added";
            } else {
                $response['error'] = true;
                $response['message'] = "Product of loose variant Not Added";
            }
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Product Not Added";
    }

    $tax_data = array();
    $rows = array();

    $product_data = $fn->get_data('', "slug='" . $slug . "'", 'products');
    $sql = "SELECT (SELECT MIN(pv.price) FROM product_variant pv WHERE pv.product_id=p.id) as price FROM products p   where p.id = " . $product_data[0]['id'];
    $db->sql($sql);
    $pr_price = $db->getResult();

    $images = $fn->get_data(['images'], 'product_id=' . $product_data[0]['id'], 'product_variant');

    $seller_data = $fn->get_data($columns = ['name', 'status'], "id=" . $product_data[0]['seller_id'], 'seller');

    if (!empty($product_data[0]['tax_id'])) {
        $tax_data = $fn->get_data($columns = ['title', 'percentage'], "id=" . $product_data[0]['tax_id'], 'taxes');
    }


    $variant_data = $fn->get_data('', 'product_id =' . $product_id, 'product_variant', '', '', 'id', 'ASC');
    foreach ($variant_data as $vari_data) {
        for ($i = 0; $i < count($vari_data['id']); $i++) {
            $variant_img = $fn->get_data($columns = ['images'], "id=" . $vari_data['id'], 'product_variant');

            foreach ($variant_img as $row) {
                $variant_images = str_replace("'", '"', $row['images']);
                $variant_images = json_decode($variant_images);

                $variant_images = (empty($variant_images)) ? array() : $variant_images;
                for ($j = 0; $j < count($variant_images); $j++) {
                    $variant_images[$j] = !empty(DOMAIN_URL . $variant_images[$j]) ? DOMAIN_URL . $variant_images[$j] : "";
                }
            }

            $ms_unit_name = $fn->get_data($columns = ['id,short_code'], "id=" . $vari_data['measurement_unit_id'], 'unit');
            $stock_unit_name = $fn->get_data($columns = ['id,short_code'], "id=" . $vari_data['stock_unit_id'], 'unit');

            $tempRow = array(
                'id' => $vari_data['id'],
                'type' => $vari_data['type'],
                'product_id' => $vari_data['product_id'],
                'measurement' => $vari_data['measurement'],
                'measurement_unit_id' => $vari_data['measurement_unit_id'],
                'measurement_unit_name' => $ms_unit_name[0]['short_code'],
                'price' => $vari_data['price'],
                'weight' => (!empty($vari_data['weight'])) ? $vari_data['weight'] : "",
                'height' => (!empty($vari_data['height'])) ? $vari_data['height'] : "",
                'length' => (!empty($vari_data['length'])) ? $vari_data['length'] : "",
                'breadth' => (!empty($vari_data['breadth'])) ? $vari_data['breadth'] : "",
                'discounted_price' => (!empty($vari_data['discounted_price'])) ? $vari_data['discounted_price'] : "0",
                'serve_for' => $vari_data['serve_for'],
                'images' => $variant_images,
                'stock' => $vari_data['stock'],
                'stock_unit_id' => $vari_data['stock_unit_id'],
                'stock_unit_name' => $stock_unit_name[0]['short_code'],
            );
            $rows[] = $tempRow;
        }
    }
    if (!empty($product_data[0]['other_images'])) {
        $other_i = json_decode($product_data[0]['other_images'], true);
        for ($j = 0; $j < count($other_i); $j++) {
            $other_i[$j] = DOMAIN_URL . $other_i[$j];
        }
    }

    $res_data = array(
        "id" => $product_data[0]['id'],
        "name" => $name,
        "seller_id" => $seller_id,
        "subcategory_id" => $subcategory_id,
        "subcategory_name" => !empty($subcategory_id) ? $fn->get_data(['name'], 'id=' . $subcategory_id, 'subcategory')[0]['name'] : "",
        "tax_id" => $tax_id,
        "category_id" => $category_id,
        "category_name" => !empty($category_id) ? $fn->get_data(['name'], 'id=' . $category_id, 'category')[0]['name'] : "",
        "description" => $description,
        "manufacturer" => $manufacturer,
        "made_in" => $made_in,
        "indicator" => $indicator,
        "return_status" => $return_status,
        "return_days" => $return_days,
        "cancelable_status" => $cancelable_status,
        "till_status" => $till_status,
        "standard_shipping" => $standard_shipping,
        "pickup_location" => !empty($pickup_location) ? $pickup_location : "",
        "delivery_places" => is_null($pincode_type) ? "0" : $pincode_type,
        "pincodes" => (string)$pincodes,
        "cod_allowed" => $cod_allowed,
        "type" => $type,
        "row_order" => $product_data[0]['row_order'],
        "slug" => $product_data[0]['slug'],
        "status" => $product_data[0]['status'],
        "date_added" => $product_data[0]['date_added'],
        "is_approved" => $product_data[0]['is_approved'],
        "seller_name" => $seller_data[0]['name'],
        "seller_status" => $seller_data[0]['status'],
        "price" => $pr_price[0]['price'],
        "tax_title" => (!empty($tax_data)) ? $tax_data[0]['title'] : "",
        "tax_percentage" => (!empty($tax_data)) ? $tax_data[0]['percentage'] : "0",
        "image" => DOMAIN_URL . $product_data[0]['image'],
        "other_images" => (!empty($other_i)) ? $other_i : [],
        "variants" => $rows,
    );
    $response['data'] = $res_data;
    print_r(json_encode($response));
    return false;
}

/* 
---------------------------------------------------------------------------------------------------------
*/

/*
11.update_products
    accesskey:90336
    update_products:1
    seller_id:1
    id:833
    name:chocolate-popcorn           
    description:chocolates
    category_id:31 
    subcategory_id:115          // {optional}
    return_days:7 {optional}
    tax_id:4                    // {optional}
    manufacturer:india          // {optional}
    made_in:india               // {optional}
    return_status:0 / 1         // {optional}
    cancelable_status:0 / 1     // {optional}
    till_status:received / processed / shipped           // {optional}
    indicator:0 - none / 1 - veg / 2 - non-veg          // {optional}
    product_variant_id:510,209
    image:FILE           //{optional}
    other_images[]:FILE    //{optional}
    shipping_type:local or standard


    delivery_places:0 OR 1 OR 2 [ 0=included, 1=excluded, 2=all ]

    pincodes:1,4,5                 //{must blank when delivery_places=2}
        OR
    pickup_locations :MIRZAPAR-BHUJ  //seller select standard_shipping 
    
    type:packet
    measurement:500,400
    measurement_unit_id:4,1
    price:175,145
    discounted_price:60,30    // {optional} 
    serve_for:Available,sold out
    stock:992,225
    stock_unit_id:4,1
    weight:1,1.5
    height:10,10
    breadth:10,10
    length:10,10       
    images[0][] : FILE           // {optional}
    images[1][] : FILE           // {optional}     

    type:loose
    measurement:1,1
    measurement_unit_id:1,5
    price:100,400
    discounted_price:20,15       // {optional}
    serve_for:Available/Sold Out
    stock:997
    stock_unit_id:1
    weight:1,1.5
    height:10,10
    breadth:10,10
    length:10,10
    images[0][] : FILE           // {optional}
    images[1][] : FILE           // {optional}
*/

if (isset($_POST['update_products']) && !empty($_POST['update_products'])) {

    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response['error'] = true;
        $response['message'] = "This operation is not allowed in demo panel!.!";
        print_r(json_encode($response));
        return false;
    }

    if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['category_id'])  || empty($_POST['description']) || empty($_POST['type'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }

    $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $id = $db->escapeString($fn->xss_clean($_POST['id']));
    $name = $db->escapeString($fn->xss_clean($_POST['name']));

    $temp = (strpos($name, '-') !== false) ? (explode("-", $name)[1]) : $name;
    $slug = $function->slugify($temp);
    $res = $fn->get_data($columns = ['slug'], 'id = ' . $id, 'products');
    $i = 1;
    foreach ($res as $row) {
        if ($slug == $row['slug']) {
            $slug = $slug . '-' . $i;
            $i++;
        }
    }

    $category_data = array();
    $product_status = "";

    $res1 = $fn->get_data($columns = ['categories'], 'id = ' . $seller_id, 'seller');
    $category_ids = $res1[0]['categories'];

    $category_data = $fn->get_data($columns = ['id', 'name'], 'id IN (' . $category_ids . ')', 'category', '', '', 'id', 'asc');

    $subcategory = $fn->get_data('', 'category_id IN (' . $category_ids . ')', 'subcategory');

    $res = $fn->get_data($columns = ['image', 'other_images'], 'id = ' . $id, 'products');
    foreach ($res as $row) {
        $previous_menu_image = $row['image'];
        $other_images = $row['other_images'];
    }

    $subcategory_id = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : 0;
    $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
    $description = $db->escapeString($fn->xss_clean($_POST['description']));
    $manufacturer = (isset($_POST['manufacturer']) && $_POST['manufacturer'] != '') ? $db->escapeString($fn->xss_clean($_POST['manufacturer'])) : '';
    $made_in = (isset($_POST['made_in']) && $_POST['made_in'] != '') ? $db->escapeString($fn->xss_clean($_POST['made_in'])) : '';
    $indicator = (isset($_POST['indicator']) && $_POST['indicator'] != '') ? $db->escapeString($fn->xss_clean($_POST['indicator'])) : '0';
    $cod_allowed = (isset($_POST['cod_allowed']) && $_POST['cod_allowed'] != '') ? $db->escapeString($fn->xss_clean($_POST['cod_allowed'])) : '1';
    $return_status = (isset($_POST['return_status']) && $_POST['return_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['return_status'])) : '0';
    $return_days = (isset($_POST['return_days']) && $_POST['return_days'] != '') ? (int)$db->escapeString($fn->xss_clean($_POST['return_days'])) : 0;
    $cancelable_status = (isset($_POST['cancelable_status']) && $_POST['cancelable_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['cancelable_status'])) : '0';
    $till_status = (isset($_POST['till_status']) && $_POST['till_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['till_status'])) : '';
    $tax_id = (isset($_POST['tax_id']) && $_POST['tax_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['tax_id'])) : 0;

    $serve_for = $db->escapeString($fn->xss_clean($_POST['serve_for']));
    $loose_stock = (isset($_POST['stock']) && $_POST['stock'] != '') ? $db->escapeString($fn->xss_clean($_POST['stock'])) : '';
    $loose_stock_unit_id = (isset($_POST['stock_unit_id']) && $_POST['stock_unit_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['stock_unit_id'])) : '';

    $seller_data = $fn->get_data($columns = ['require_products_approval'], 'id=' . $seller_id, 'seller');
    $pr_approval = $seller_data[0]['require_products_approval'];

    $is_approved = $fn->get_data($columns = ['is_approved'], 'id=' . $_POST['id'], 'products');
    $is_approved = $is_approved[0]['is_approved'];
    $is_approved = ($pr_approval == 0) ? 1 : $is_approved;

    $sub_category_name = $fn->get_data(['name'], "id= $subcategory_id AND category_id=$category_id", 'subcategory');
    if (empty($sub_category_name) && !empty($subcategory_id)) {
        $response['error'] = true;
        $response['message'] = "Sub category not found";
        print_r(json_encode($response));
        return false;
    }

    if ($_POST['shipping_type'] == 'standard') {
        $standard_shipping = 1;
        $pickup_location = (isset($_POST['pickup_location']) && !empty($_POST['pickup_location'])) ? $db->escapeString($fn->xss_clean($_POST['pickup_location'])) : 0;
        $pincodes = 0;
    } else {
        $d_type = "";
        $pincode_type = (isset($_POST['delivery_places']) && $_POST['delivery_places'] != '') ? $db->escapeString($fn->xss_clean($_POST['delivery_places'])) : 0;
        if ($pincode_type == "2") {
            $d_type = "all";
            $pincodes = '';
        } else {
            $d_type = $pincode_type = "";
            $pincode_type = (isset($_POST['delivery_places']) && $_POST['delivery_places'] != '') ? $db->escapeString($fn->xss_clean($_POST['delivery_places'])) : 0;
            if ($pincode_type == "2") {
                $d_type = "all";
                $pincodes = '';
            } else {
                if ($pincode_type == "0") {
                    $d_type = "included";
                } else if ($pincode_type == "1") {
                    $d_type = "excluded";
                }
                $pincodes = $db->escapeString($fn->xss_clean($_POST['pincodes']));
            }
        }
    }

    /* update other images */
    if (isset($_FILES['other_images']) && ($_FILES['other_images']['size'][0] > 0)) {
        $file_data = array();
        $target_path = '../../upload/other_images/';
        $target_path1 = 'upload/other_images/';

        for ($i = 0; $i < count($_FILES["other_images"]["name"]); $i++) {
            if ($_FILES["other_images"]["error"][$i] > 0) {
                $response['error'] = true;
                $response['message'] = "Other Images not uploaded!";
                print_r(json_encode($response));
                return false;
            } else {
                $result = $fn->validate_other_images($_FILES["other_images"]["tmp_name"][$i], $_FILES["other_images"]["type"][$i]);
                if (!$result) {
                    $response['error'] = true;
                    $response['message'] = "Other image type must jpg, jpeg, gif, or png!";
                    print_r(json_encode($response));
                    return false;
                }
            }
            $filename = $_FILES["other_images"]["name"][$i];
            $temp = explode('.', $filename);
            $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
            $file_data[] = 'upload/other_images/' . $filename;

            if (!move_uploaded_file($_FILES["other_images"]["tmp_name"][$i], $target_path . $filename)) {
                $response['error'] = true;
                $response['message'] = "Other Images not uploaded!";
                print_r(json_encode($response));
                return false;
            }
        }
        if (!empty($other_images)) {
            $arr_old_images = json_decode($other_images);
            $all_images = array_merge($arr_old_images, $file_data);
            $all_images = $db->escapeString(json_encode(array_values($all_images)));
        } else {
            $all_images = $db->escapeString(json_encode($file_data));
        }
        if (empty($error)) {
            $sql = "update `products` set `other_images`='" . $all_images . "' where `id`= $id  and seller_id=$seller_id";
            $db->sql($sql);
        }
    }

    if (strpos($name, "'") !== false) {
        $name = str_replace("'", "''", "$name");
        if (strpos($description, "'") !== false) {
            $description = str_replace("'", "''", "$description");
        }
    }

    if (isset($_FILES['image']) && ($_FILES['image']['size'] > 0)) {
        $image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
        $image_error = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
        $image_type = $db->escapeString($fn->xss_clean($_FILES['image']['type']));
        $error = array();
        $allowedExts = array("gif", "jpeg", "jpg", "png");

        error_reporting(E_ERROR | E_PARSE);
        $extension = end(explode(".", $_FILES["image"]["name"]));

        if (!empty($image)) {
            $result = $fn->validate_image($_FILES["image"]);
            if (!$result) {
                $response['error'] = true;
                $response['message'] = "image type must jpg, jpeg, gif, or png!";
                print_r(json_encode($response));
                return false;
            }
        }
        $string = '0123456789';
        $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);
        $function = new functions;
        $image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;
        $delete = unlink("$previous_menu_image");

        $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../../upload/images/' . $image);
        $upload_image = 'upload/images/' . $image;

        $sql_query = "UPDATE products SET name = '$name' ,is_approved= '$is_approved',type= '$pincode_type',pincodes = '$pincode_ids',tax_id = '$tax_id' ,seller_id = '$seller_id' ,slug = '$slug' , subcategory_id = '$subcategory_id', image = '$upload_image', description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status', return_days = '$return_days', cancelable_status = '$cancelable_status', till_status = '$till_status',`pickup_location`='$pickup_location' WHERE id = $id";
    } else if ($pincode_type != "") {
        $sql_query = "UPDATE products SET name = '$name' ,is_approved= '$is_approved',type= '$pincode_type',pincodes = '$pincode_ids',tax_id = '$tax_id' ,seller_id = '$seller_id' ,slug = '$slug' ,category_id = '$category_id' ,subcategory_id = '$subcategory_id' ,description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status',return_days = '$return_days', cancelable_status = '$cancelable_status', till_status = '$till_status' ,`pickup_location`='$pickup_location' WHERE id = $id";
    } else {
        $sql_query = "UPDATE products SET name = '$name' ,is_approved= '$is_approved',tax_id = '$tax_id' ,seller_id = '$seller_id' ,slug = '$slug' ,category_id = '$category_id' ,subcategory_id = '$subcategory_id', description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status',return_days = '$return_days', cancelable_status = '$cancelable_status', till_status = '$till_status',`pickup_location`='$pickup_location'  WHERE id = $id";
    }
    $db->sql($sql_query);
    $res = $db->getResult();

    $type = $db->escapeString($fn->xss_clean($_POST['type']));

    $product_variant_id1 = $db->escapeString($fn->xss_clean($_POST['product_variant_id']));
    $measurement1 = $db->escapeString($fn->xss_clean($_POST['measurement']));
    $measurement_unit_id1 = $db->escapeString($fn->xss_clean($_POST['measurement_unit_id']));
    $price1 = $db->escapeString($fn->xss_clean($_POST['price']));
    $discounted_price1 = !empty($_POST['discounted_price']) ? $db->escapeString($fn->xss_clean($_POST['discounted_price'])) : 0;
    $serve_for2 =  $db->escapeString($fn->xss_clean($_POST['serve_for']));
    $stock1 = $db->escapeString($fn->xss_clean($_POST['stock']));
    $stock_unit_id1 = $db->escapeString($fn->xss_clean($_POST['stock_unit_id']));
    $weight = (isset($_POST['weight']) && ($_POST['weight'] != '')) ? $db->escapeString($fn->xss_clean($_POST['weight'])) : 0;
    $height = (isset($_POST['height']) && ($_POST['height'] != '')) ? $db->escapeString($fn->xss_clean($_POST['height'])) : 0;
    $breadth = (isset($_POST['breadth']) && ($_POST['breadth'] != '')) ? $db->escapeString($fn->xss_clean($_POST['breadth'])) : 0;
    $length = (isset($_POST['length']) && ($_POST['length'] != '')) ? $db->escapeString($fn->xss_clean($_POST['length'])) : 0;

    $product_variant_id = explode(",", $product_variant_id1);
    $measurement = explode(",", $measurement1);
    $measurement_unit_id = explode(",", $measurement_unit_id1);
    $price = explode(",", $price1);
    $discounted_price = explode(",", $discounted_price1);
    $serve_for = explode(",", $serve_for2);
    $stock = explode(",", $stock1);
    $stock_unit_id = explode(",", $stock_unit_id1);
    $weight = explode(",", $weight);
    $height = explode(",", $height);
    $breadth = explode(",", $breadth);
    $length = explode(",", $length);

    $tax_data = array();

    /* get products data */
    $product_data = $fn->get_data('', 'id=' . $id, 'products');

    /*get seller data */
    $seller_data = $fn->get_data($columns = ['name', 'status'], "id=" . $product_data[0]['seller_id'], 'seller');

    /*get product variants data */
    $pr_variant_test = $fn->get_data($columns = ['id'], "product_id=" . $id, "product_variant");

    if (!empty($product_data[0]['tax_id'])) {
        $tax_data = $fn->get_data($columns = ['title', 'percentage'], "id=" . $product_data[0]['tax_id'], 'taxes');
    }

    $sql = "SELECT (SELECT MIN(pv.price) FROM product_variant pv WHERE pv.product_id=p.id) as price FROM products p   where p.id = " . $id;
    $db->sql($sql);
    $pr_price = $db->getResult();

    for ($i = 0; $i < count($measurement); $i++) {

        if (in_array($product_variant_id[$i], $pr_variant_test)) {
            $response['error'] = true;
            $response['message'] = "Invalid product variant id.";
            print_r(json_encode($response));
            return false;
        }

        $previous_variant_image = '';

        $vari_image = $fn->get_data($columns = ['id', 'images'], 'id=' . $product_variant_id[$i], 'product_variant');
        $previous_variant_image = $vari_image[0]['images'];

        if ($_POST['type'] == "packet") {

            if (!(count($measurement) == count($measurement_unit_id) && count($measurement_unit_id) == count($price) && count($price) == count($stock) && count($stock) == count($serve_for) && count($serve_for) == count($stock_unit_id))) {
                $response['error'] = true;
                $response['message'] = "Pass correct count for variants";
                print_r(json_encode($response));
                return false;
            }

            $serve_for_lbl = ($stock[$i] == 0) ? 'Sold Out' : $serve_for[$i];
            $file_data = array();
            $all_vari_images = array();

            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][$i][0]) && ($_FILES['images']['size'][$i][0] > 0)) {

                $target_path1 = '../../upload/variant_images/';
                if (!is_dir($target_path1)) {
                    mkdir($target_path1, 0777, true);
                }
                for ($k = 0; $k < count($_FILES["images"]["name"][$i]); $k++) {
                    if ($_FILES["images"]["error"][$i][$k] > 0) {
                        $error['images'] = " <span class='label label-danger'>Images not uploaded!</span>";
                    } else {
                        $result = $fn->validate_other_images($_FILES["images"]["tmp_name"][$i][$k], $_FILES["images"]["type"][$i][$k]);
                        if ($result) {
                            $error['images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                        }
                    }
                    $filename = $_FILES["images"]["name"][$i][$k];
                    $temp = explode('.', $filename);
                    $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                    $file_data[] = 'upload/variant_images/' . '' . $filename;

                    if (!move_uploaded_file($_FILES["images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                        echo "{$_FILES['images']['name'][$i][$k]} not uploaded<br/>";
                }
            }

            if (isset($previous_variant_image) && !empty($previous_variant_image && $product_variant_id[$i] != 0)) {
                $variant_images = str_replace("'", '"', $previous_variant_image);
                $arr_old_images = json_decode($variant_images);
                $all_vari_images = !empty($file_data) ? array_merge($arr_old_images, $file_data) : $arr_old_images;
                $all_vari_images = json_encode(array_values($all_vari_images));
                $all_vari_images = str_replace('"', "'", $all_vari_images);
            } else {
                $all_vari_images = $db->escapeString(json_encode($file_data));
                $all_vari_images = str_replace('"', "'", $all_vari_images);
            }
            $data = array(
                'type' => $type,
                'id' => $product_variant_id[$i],
                'measurement' => $measurement[$i],
                'measurement_unit_id' => $measurement_unit_id[$i],
                'price' => $price[$i],
                'discounted_price' => $discounted_price[$i],
                'serve_for' => $serve_for_lbl,
                'stock' => $stock[$i],
                'weight' => $weight[$i],
                'height' => $height[$i],
                'length' => $length[$i],
                'breadth' => $breadth[$i],
                'stock_unit_id' => $stock_unit_id[$i],
                'images' => !empty($all_vari_images) ? $all_vari_images : []
            );
            if ($data['id'] == 0) {
                $data['product_id'] = $id;
                $db->insert('product_variant', $data);
            } else {
                $db->update('product_variant', $data, 'id=' . $data['id']);
            }
            $res = $db->getResult();
        } elseif ($_POST['type'] == "loose") {

            $file_data = array();
            $all_vari_images = array();

            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][$i][0]) && ($_FILES['images']['size'][$i][0] > 0)) {
                $target_path1 = '../../upload/variant_images/';
                if (!is_dir($target_path1)) {
                    mkdir($target_path1, 0777, true);
                }
                for ($k = 0; $k < count($_FILES["images"]["name"][$i]); $k++) {
                    if ($_FILES["images"]["error"][$i][$k] > 0) {
                        $error['images'] = " <span class='label label-danger'>Images not uploaded!</span>";
                    } else {
                        $result = $fn->validate_other_images($_FILES["images"]["tmp_name"][$i][$k], $_FILES["images"]["type"][$i][$k]);
                        if ($result) {
                            $error['images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                        }
                    }
                    $filename = $_FILES["images"]["name"][$i][$k];
                    $temp = explode('.', $filename);
                    $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                    $file_data[] = 'upload/variant_images/' . '' . $filename;

                    if (!move_uploaded_file($_FILES["images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                        echo "{$_FILES['images']['name'][$i][$k]} not uploaded<br/>";
                }
            }

            if (isset($previous_variant_image) && !empty($previous_variant_image && $product_variant_id[$i] != 0)) {
                $variant_images = str_replace("'", '"', $previous_variant_image);
                $arr_old_images = json_decode($variant_images);
                $all_vari_images = !empty($file_data) ? array_merge($arr_old_images, $file_data) : $arr_old_images;
                $all_vari_images = json_encode(array_values($all_vari_images));
                $all_vari_images = str_replace('"', "'", $all_vari_images);
            } else {
                $all_vari_images = $db->escapeString(json_encode($file_data));
                $all_vari_images = str_replace('"', "'", $all_vari_images);
            }


            $data = array(
                'type' => $type,
                'id' => $product_variant_id[$i],
                'measurement' => $measurement[$i],
                'measurement_unit_id' => $measurement_unit_id[$i],
                'price' => $price[$i],
                'discounted_price' => $discounted_price[$i],
                'serve_for' => $serve_for2,
                'stock' => $loose_stock,
                'weight' => $weight[$i],
                'height' => $height[$i],
                'length' => $length[$i],
                'breadth' => $breadth[$i],
                'stock_unit_id' => $loose_stock_unit_id,
                'images' =>  $all_vari_images,
            );
            if ($data['id'] == 0) {
                $data['product_id'] = $id;
                $db->insert('product_variant', $data);
            } else {
                $db->update('product_variant', $data, 'id=' . $data['id']);
            }
            $res = $db->getResult();
        }
    }

    $variant_data = $fn->get_data('', 'product_id =' . $id, 'product_variant');
    foreach ($variant_data as $vari_data) {
        for ($i = 0; $i < count($vari_data['id']); $i++) {
            $variant_img = $fn->get_data($columns = ['images'], "id=" . $vari_data['id'], 'product_variant');

            foreach ($variant_img as $row) {
                $variant_images = str_replace("'", '"', $row['images']);
                $variant_images = json_decode($variant_images);

                $variant_images = (empty($variant_images)) ? array() : $variant_images;
                for ($j = 0; $j < count($variant_images); $j++) {
                    $variant_images[$j] = !empty(DOMAIN_URL . $variant_images[$j]) ? DOMAIN_URL . $variant_images[$j] : "";
                }
            }

            $ms_unit_name = $fn->get_data($columns = ['id,short_code'], "id=" . $vari_data['measurement_unit_id'], 'unit');
            $stock_unit_name = $fn->get_data($columns = ['id,short_code'], "id=" . $vari_data['stock_unit_id'], 'unit');

            $tempRow = array(
                'id' =>  $vari_data['id'],
                'type' => $vari_data['type'],
                'product_id' => $vari_data['product_id'],
                'measurement' => $vari_data['measurement'],
                'measurement_unit_id' => $vari_data['measurement_unit_id'],
                'measurement_unit_name' => $ms_unit_name[$i]['short_code'],
                'price' => $vari_data['price'],
                'weight' => (!empty($vari_data['weight'])) ? $vari_data['weight'] : "",
                'height' => (!empty($vari_data['height'])) ? $vari_data['height'] : "",
                'length' => (!empty($vari_data['length'])) ? $vari_data['length'] : "",
                'breadth' => (!empty($vari_data['breadth'])) ? $vari_data['breadth'] : "",
                'discounted_price' => (!empty($vari_data['discounted_price'])) ? $vari_data['discounted_price'] : "0",
                'serve_for' => $vari_data['serve_for'],
                'images' => $variant_images,
                'stock' => $vari_data['stock'],
                'stock_unit_id' => $vari_data['stock_unit_id'],
                'stock_unit_name' => $stock_unit_name[$i]['short_code'],
            );

            $rows[] = $tempRow;
        }
    }

    if ($product_data[0]['type'] == 'excluded') {
        $delivery_places = "1";
    } else  if ($product_data[0]['type'] == 'included') {
        $delivery_places = "0";
    } else  if ($product_data[0]['type'] == 'all') {
        $delivery_places = "2";
    } else {
        $delivery_places = "";
    }

    if (!empty($product_data[0]['other_images'])) {
        $other_i = json_decode($product_data[0]['other_images'], true);
        for ($j = 0; $j < count($other_i); $j++) {
            $other_i[$j] = DOMAIN_URL . $other_i[$j];
        }
    }

    if ($_POST['shipping_type'] == 'standard') {
        $res_data = array(
            "id" => $id,
            "name" => $name,
            "seller_id" => $seller_id,
            "subcategory_id" => $subcategory_id,
            "subcategory_name" => !empty($sub_category_name) ? $sub_category_name[0]['name'] : "",
            "tax_id" => $tax_id,
            "category_id" => $category_id,
            "category_name" => !empty($category_id) ? $fn->get_data(['name'], 'id=' . $category_id, 'category')[0]['name'] : "",
            "description" => $description,
            "manufacturer" => $manufacturer,
            "made_in" => $made_in,
            "indicator" => $indicator,
            "cod_allowed" => $cod_allowed,
            "return_status" => $return_status,
            "return_days" => "" . $return_days . "",
            "cancelable_status" => $cancelable_status,
            "standard_shipping" => "1",
            "pickup_location" => !empty($pickup_location) ? $pickup_location : "",
            "till_status" => $till_status,
            "delivery_places" => $delivery_places,
            "pincodes" => "",
            "type" => $type,
            "row_order" => $product_data[0]['row_order'],
            "slug" => $product_data[0]['slug'],
            "status" => $product_data[0]['status'],
            "date_added" => $product_data[0]['date_added'],
            "is_approved" => $product_data[0]['is_approved'],
            "seller_name" => $seller_data[0]['name'],
            "seller_status" => $seller_data[0]['status'],
            "price" => $pr_price[0]['price'],
            "tax_title" => (!empty($tax_data)) ? $tax_data[0]['title'] : "",
            "tax_percentage" => (!empty($tax_data)) ? $tax_data[0]['percentage'] : "0",
            "image" => DOMAIN_URL . $product_data[0]['image'],
            "other_images" => !is_null($other_i) ? $other_i : [],
            "variants" => $rows,
        );
    } else {

        $res_data = array(
            "id" => $id,
            "name" => $name,
            "seller_id" => $seller_id,
            "subcategory_id" => $subcategory_id,
            "subcategory_name" => !empty($subcategory_id) ? $fn->get_data(['name'], 'id=' . $subcategory_id, 'subcategory')[0]['name'] : "",
            "tax_id" => $tax_id,
            "category_id" => $category_id,
            "category_name" => !empty($category_id) ? $fn->get_data(['name'], 'id=' . $category_id, 'category')[0]['name'] : "",
            "description" => $description,
            "manufacturer" => $manufacturer,
            "made_in" => $made_in,
            "indicator" => $indicator,
            "return_status" => $return_status,
            "return_days" => "" . $return_days . "",
            "cancelable_status" => $cancelable_status,
            "till_status" => $till_status,
            "delivery_places" => $delivery_places,
            "pincodes" => $pincodes,
            "type" => $type,
            "standard_shipping" => "0",
            "pickup_location" => "0",
            "row_order" => $product_data[0]['row_order'],
            "slug" => $product_data[0]['slug'],
            "status" => $product_data[0]['status'],
            "date_added" => $product_data[0]['date_added'],
            "is_approved" => $product_data[0]['is_approved'],
            "seller_name" => $seller_data[0]['name'],
            "seller_status" => $seller_data[0]['status'],
            "price" => $pr_price[0]['price'],
            "tax_title" => (!empty($tax_data)) ? $tax_data[0]['title'] : "",
            "tax_percentage" => (!empty($tax_data)) ? $tax_data[0]['percentage'] : "0",
            "image" => DOMAIN_URL . $product_data[0]['image'],
            "other_images" => (!empty($other_i)) ? $other_i : [],
            "variants" => $rows,
        );
    }

    $response['error'] = false;
    $response['message'] = "Product updated successfully";
    $response['data'] = $res_data;

    print_r(json_encode($response));
    return false;
}

/* 
---------------------------------------------------------------------------------------------------------
*/

/*
12.delete_products
    accesskey:90336
    delete_products:1
    product_variants_id:668
    product_id:879
*/
if (isset($_POST['delete_products']) && !empty($_POST['delete_products']) && ($_POST['delete_products'] == 1)) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response['error'] = true;
        $response['message'] = "This operation is not allowed in demo panel!.!";
        print_r(json_encode($response));
        return false;
    }
    if (empty($_POST['product_variants_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass product variants id fields!";
        print_r(json_encode($response));
        return false;
    }
    $product_variants_id = (isset($_POST['product_variants_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_variants_id'])) : "";

    $product_id = $fn->get_product_id_by_variant_id($product_variants_id);
    $sql = "SELECT images FROM product_variant WHERE product_id =" . $product_id;
    $db->sql($sql);
    $res = $db->getResult();
    for ($i = 0; $i < count($res); $i++) {
        if (isset($res[$i]['images']) && !empty($res[$i]['images'])) {
            $other_images = $res[$i]['images']; /*get images json array*/
            $variant_images = str_replace("'", '"', $other_images);
            $other_images = json_decode($variant_images); /*decode from json to array*/
            foreach ($other_images as $other_image) {
                unlink('../../' . $other_image);
            }
        }
    }

    $sql_query = "DELETE FROM cart WHERE product_id = $product_id  AND product_variant_id = $product_variants_id";
    $db->sql($sql_query);
    $sql_query = "DELETE FROM product_variant WHERE product_id=" . $product_id;
    $db->sql($sql_query);

    $sql = "SELECT count(id) as total from product_variant WHERE product_id=" . $product_id;
    $db->sql($sql);
    $total = $db->getResult();

    if ($total[0]['total'] == 0) {
        $sql_query = "SELECT image FROM products WHERE id =" . $product_id;
        $db->sql($sql_query);
        $res = $db->getResult();
        unlink('../../' . $res[0]['image']);

        $sql_query = "SELECT other_images FROM products WHERE id =" . $product_id;
        $db->sql($sql_query);
        $res = $db->getResult();
        if (!empty($res[0]['other_images'])) {
            $other_images = json_decode($res[0]['other_images']);
            foreach ($other_images as $other_image) {
                unlink('../../' . $other_image);
            }
        }

        $sql_query = "DELETE FROM products WHERE id =" . $product_id;
        $db->sql($sql_query);

        $sql_query = "DELETE FROM favorites WHERE product_id = " . $product_id;
        $db->sql($sql_query);
    }
    $response['error'] = false;
    $response['message'] = "product delete successfully!";
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_seller_by_id']) && !empty($_POST['get_seller_by_id'])) {

    /* 
    13. get_seller_by_id
        accesskey:90336
        seller_id:78
        get_seller_by_id:1
    */
    if (empty($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Seller id should be Passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $where = '';
    if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $slug = $db->escapeString($fn->xss_clean($_POST['slug']));
        $where = " AND `slug` = '$slug' ";
    }
    $sql = "SELECT * FROM seller	WHERE id = '" . $id . "'" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    $num = $db->numRows($res);
    $db->disconnect();
    $rows = $tempRow = array();
    if ($num == 1) {
        $res[0]['fcm_id'] = !empty($res[0]['fcm_id'])  ? $res[0]['fcm_id'] : "";
        $res[0]['longitude'] = !empty($res[0]['longitude'])  ? $res[0]['longitude'] : "0";
        $res[0]['latitude'] = !empty($res[0]['latitude'])  ? $res[0]['latitude'] : "0";
        $res[0]['national_identity_card'] = !empty($res[0]['national_identity_card'])  ?  DOMAIN_URL . 'upload/seller/' . $res[0]['national_identity_card'] : "";
        $res[0]['address_proof'] = !empty($res[0]['address_proof']) ?  DOMAIN_URL . 'upload/seller/' . $res[0]['address_proof'] : "";
        $res[0]['logo'] = (!empty($res[0]['logo'])) ? DOMAIN_URL . 'upload/seller/' . $res[0]['logo'] : "";
        $state = (!empty($row['state'])) ? $row['state'] . ", " : "";
        $street = (!empty($row['street'])) ? $row['street'] . ", " : "";
        $pincode = (!empty($row['pincode_id'])) ? $res_pincode[0]['city'] . " - " . $res_pincode[0]['pincode'] : "";
        $seller_address = $state  . $street . $pincode;
        $res[0]['seller_address'] = (!empty($seller_address))  ? $seller_address : "";
        $response['error'] = false;
        $response['message'] = "Seller Data Fetched Successfully";
        $response['currency'] =  $fn->get_settings('currency');
        $response['data'] = $res;
        $response['data'][0]['balance'] = ceil($response['data'][0]['balance']);
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
}

if (isset($_POST['get_taxes']) && $_POST['get_taxes'] == 1) {
    /*  
    14. get_taxes
        accesskey:90336
        get_taxes:1
        limit:10
        offset:10
        search:test
    */

    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $where = "";
    if (isset($_POST['search'])  && !empty($_POST['search'])) {
        $search = $_POST['search'];
        $where = "where t.title like '%$search%' or t.percentage like '%$search%'";
    }

    $sql = "SELECT COUNT(t.id) as total FROM taxes t";
    $db->sql($sql);
    $total = $db->getResult();
    $sql = "SELECT t.* FROM taxes t $where limit $limit offset $offset";
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        $response['error'] = false;
        $response['message'] = "Taxes retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}


if (isset($_POST['get_units']) && $_POST['get_units'] == 1) {
    /*  
    15. get_units
        accesskey:90336
        get_units:1
    */

    $sql = "SELECT * FROM unit ";
    $db->sql($sql);
    $res = $db->getResult();

    for ($i = 0; $i < count($res); $i++) {
        $res[$i]['parent_id'] = (!empty($res[$i]['parent_id'])) ? $res[$i]['parent_id'] : "0";
        $res[$i]['conversion'] = (!empty($res[$i]['conversion'])) ? $res[$i]['conversion'] : "0";
    }

    if (!empty($res)) {
        $response['error'] = false;
        $response['message'] = "Units retrieved successfully";
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_pincodes']) && $_POST['get_pincodes'] == 1) {
    /*  
    16. get_pincodes
        accesskey:90336
        get_pincodes:1
    */

    $sql = "SELECT * FROM pincodes ";
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        $response['error'] = false;
        $response['message'] = "Pincodes retrieved successfully";
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['delete_other_images']) && $_POST['delete_other_images'] == 1) {

    /*  
    17. delete_other_images
        accesskey:90336
        delete_other_images:1
        seller_id:1
        product_id:1
        image:1    // {index of other image array}
    */

    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response['error'] = true;
        $response['message'] = "This operation is not allowed in demo panel!.!";
        print_r(json_encode($response));
        return false;
    }
    if (empty($_POST['seller_id']) || empty($_POST['product_id'])) {
        $response['error'] = true;
        $response['message'] = "All fields should be Passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $pid = $db->escapeString($fn->xss_clean($_POST['product_id']));
    $i = $db->escapeString($fn->xss_clean($_POST['image']));

    $result = $fn->delete_other_images($pid, $i, $seller_id);
    if ($result == 1) {
        $response['error'] = false;
        $response['message'] = "Image deleted successfully";
    } else if ($result == 2) {
        $response['error'] = true;
        $response['message'] = "Seller have not this product";
    } else {
        $response['error'] = true;
        $response['message'] = "Image is not deleted. try agian later";
    }
    print_r(json_encode($response));
    return false;
    exit();
}


if (isset($_POST['delete_variant']) && $_POST['delete_variant'] == 1) {

    /*  
    18. delete_variant
        accesskey:90336
        delete_variant:1
        variant_id:1
    */
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response['error'] = true;
        $response['message'] = "This operation is not allowed in demo panel!.!";
        print_r(json_encode($response));
        return false;
    }
    if (empty($_POST['variant_id'])) {
        $response['error'] = true;
        $response['message'] = "All fields should be Passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $v_id = $db->escapeString($fn->xss_clean($_POST['variant_id']));

    $result = $fn->delete_variant($v_id);
    if ($result) {
        $response['error'] = false;
        $response['message'] = "Product variant deleted successfully!";
    } else {
        $response['error'] = true;
        $response['message'] = "Product variant not exist or some error occured!";
    }
    print_r(json_encode($response));
    return false;
    exit();
}

if (isset($_POST['get_customers']) && !empty($_POST['get_customers'])) {
    /* 
   19.get_customers
	   accesskey:90336
	   get_customers:1
	   pincode_id:119  {optional}
	   limit:10  {optional}
	   offset:0    {optional}
	   sort:id      {optional}
	   order:ASC/DESC {optional}
	   search:value {optional}
   */
    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;

    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'u.id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';

    if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != '') {
        $pincode_id = $db->escapeString($fn->xss_clean($_POST['pincode_id']));
        $where .= ' where ua.pincode_id=' . $pincode_id;
    }
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != '') {
            $where .= " and u.`id` like '%" . $search . "%' OR u.`name` like '%" . $search . "%' OR u.`email` like '%" . $search . "%' OR u.`mobile` like '%" . $search . "%' ";
        } else {
            $where .= " Where u.`id` like '%" . $search . "%' OR u.`name` like '%" . $search . "%' OR u.`email` like '%" . $search . "%' OR u.`mobile` like '%" . $search . "%'";
        }
    }
    $sql = "SELECT COUNT(DISTINCT(u.id)) as total FROM `users` u LEFT JOIN user_addresses ua on u.id=ua.user_id LEFT JOIN pincodes p on p.id=ua.pincode_id LEFT JOIN area a on a.id=ua.area_id " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row)
            $total = $row['total'];

        $sql = "SELECT DISTINCT u.*,a.name as area_name,p.pincode as pincode,c.name as city,ua.pincode_id FROM `users` u LEFT JOIN user_addresses ua on u.id=ua.user_id LEFT JOIN pincodes p on p.id=ua.pincode_id LEFT JOIN area a on a.id=ua.area_id LEFT JOIN cities c on c.id=a.city_id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
        $db->sql($sql);
        $res = $db->getResult();
        $rows = array();
        $tempRow = array();

        foreach ($res as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['name'] = $row['name'];
            $path = DOMAIN_URL . 'upload/profile/';
            if (!empty($row['profile'])) {
                $tempRow['profile'] = $path . $row['profile'];
            } else {
                $tempRow['profile'] = $path . "default_user_profile.png";
            }
            $tempRow['email'] = $row['email'];
            $tempRow['mobile'] = $row['mobile'];
            $tempRow['balance'] = $row['balance'];
            $tempRow['referral_code'] = $row['referral_code'];
            $tempRow['friends_code'] = !empty($row['friends_code']) ? $row['friends_code'] : '-';
            $tempRow['city'] = !empty($row['city']) ? $row['city'] : '';
            $tempRow['pincode_id'] = !empty($row['pincode_id']) ? $row['pincode_id'] : '';
            $tempRow['pincode'] = !empty($row['pincode']) ? $row['pincode'] : '';
            $tempRow['area'] = !empty($row['area_name']) ? $row['area_name'] : '';
            $tempRow['status'] = $row['status'];
            $tempRow['created_at'] = $row['created_at'];
            $rows[] = $tempRow;
        }
        $response['error'] = false;
        $response['message'] = "Customers fatched successfully.";
        $response['total'] = $total;
        $response['data'] = $rows;
    } else {
        $response['error'] = true;
        $response['message'] = "Something went wrong, please try again leter.";
    }
    print_r(json_encode($response));
}

if (isset($_POST['send_request']) && $_POST['send_request'] == 1) {
    /*
    20.send_request
        accesskey:90336
        send_request:1
        type:seller
        type_id:3
        amount:1000
        message:Message {optional}
    */
    $res_msg = "";
    $res_msg .= (empty($_POST['type']) || $_POST['type'] == "") ? "type," : "";
    $res_msg .= (empty($_POST['type_id']) || $_POST['type_id'] == "") ? "type_id," : "";
    if ($res_msg != "") {
        $response['error'] = true;
        $response['message'] = "this fields " . trim($res_msg, ",") . " should be Passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    // if (empty($_POST['type']) || empty($_POST['type_id']) || empty($_POST['amount']) ) {
    //     $response['error'] = true;
    //     $response['message'] = "All fields should be Passed!";
    //     print_r(json_encode($response));
    //     return false;
    //     exit();
    // }
    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $type_id = $db->escapeString($fn->xss_clean($_POST['type_id']));
    $amount  = $db->escapeString($fn->xss_clean($_POST['amount']));
    $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_id'])) : "";
    $order_item_id = (isset($_POST['order_item_id']) && !empty($_POST['order_item_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_item_id'])) : "";
    $message = (isset($_POST['message']) && !empty($_POST['message'])) ? $db->escapeString($fn->xss_clean($_POST['message'])) : "";
    // $type1 = $type == 'user' ? 'user' : 'delivery boy';
    if (!empty($type) && !empty($type_id) && !empty($amount)) {
        // check if such user or delivery boy exists or not
        if ($fn->is_user_or_dboy_exists($type, $type_id)) {
            // checking if balance is greater than amount requested or not 
            $balance = $fn->get_user_or_delivery_boy_balance($type, $type_id);
            if ($balance >= $amount) {
                // Debit amount requeted
                $new_balance =  $balance - $amount;
                if ($fn->debit_balance($type, $type_id, $new_balance)) {
                    // store wallet transaction
                    if ($type == 'seller') {
                        $fn->add_wallet_transaction($order_id, $order_item_id, $type_id, $type, $amount, $message, 'seller_wallet_transactions');
                    }
                    // store withdrawal request
                    if ($fn->store_withdrawal_request($type, $type_id, $amount, $message)) {
                        $sql = "select balance from seller where id=$type_id";
                        $db->sql($sql);
                        $res = $db->getResult();
                        $response['error'] = false;
                        $response['message'] = 'Withdrawal request accepted successfully!please wait for confirmation.';
                        $response['updated_balance'] = $res[0]['balance'];
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Something went wrong please try again later!';
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Something went wrong please try again later!';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Insufficient balance';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No such ' . $type . ' exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_requests']) && $_POST['get_requests'] == 1) {
    /*
    21.get_requests
        accesskey:90336
        get_requests:1
        type:seller
        type_id:3
        offset:0    // {optional}
        limit:5     // {optional}
    */

    $type  = $db->escapeString($fn->xss_clean($_POST['type']));
    $type_id = $db->escapeString($fn->xss_clean($_POST['type_id']));
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    if (!empty($type) && !empty($type_id)) {
        $result = $fn->is_records_exists($type, $type_id, $offset, $limit);
        if (!empty($result)) {
            /* if records found return data */
            $sql = "SELECT count(id) as total from withdrawal_requests where `type` = '" . $type . "' AND `type_id` = " . $type_id;
            $db->sql($sql);
            $total = $db->getResult();
            $response['error'] = false;
            $response['total'] = $total[0]['total'];
            $response['data'] = array_values($result);
        } else {
            $response['error'] = true;
            $response['message'] = "Data does't exists!";
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}

if (isset($_POST['update_seller_profile']) && $_POST['update_seller_profile'] == 1) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response['error'] = true;
        $response['message'] = "This operation is not allowed in demo panel!.!";
        print_r(json_encode($response));
        return false;
    }
    /* 
    {optional -> if not added }
    22.update_seller_profile   
        accesskey:90336
        update_seller_profile:1
        seller_id:1
        name:ekart seller  
        store_name:ekart seller store  
        email:infinitietechnologies03@gmail.com
        tax_name:GST
        tax_number:GST6754321
        pan_number:GNU12345
        status: 0 -> Deactivated, 1-> Activated/Approved  // {optional}
        store_url:https://www.store.com            // {optional}
        description:values                        // {optional}
        street:street1                         // {optional}
        pincode_id:1                              // {optional}
        state:gujarat                             // {optional}
        account_number:123456789265421                   // {optional}
        ifsc_code:DFG34557WD                      // {optional}
        account_name:ekart seller                       // {optional}
        bank_name:SBI                             // {optional}
        old_password:                             // {optional}
        update_password:                          // {optional}
		confirm_password:                         // {optional}
		store_logo: image_file  { jpg, png, gif, jpeg } // {optional -> do not set if no change}
		national_id_card: image_file  { jpg, png, gif, jpeg } // {optional -> do not set if no change}
		address_proof: image_file  { jpg, png, gif, jpeg }  // {optional -> do not set if no change}
		latitude:value                       // {optional}
		longitude:value                         // {optional}
       
    */
    $res_msg = "";
    $res_msg .= (empty($_POST['seller_id']) || $_POST['seller_id'] == "") ? "seller_id," : "";
    $res_msg .= (empty($_POST['name']) || $_POST['name'] == "") ? "name," : "";
    $res_msg .= (empty($_POST['store_name']) || $_POST['store_name'] == "") ? "store_name," : "";
    $res_msg .= (empty($_POST['email']) || $_POST['email'] == "") ? "email," : "";
    $res_msg .= (empty($_POST['tax_name']) || $_POST['tax_name'] == "") ? "tax_name," : "";
    $res_msg .= (empty($_POST['tax_number']) || $_POST['name'] == "") ? "tax_number," : "";
    $res_msg .= (empty($_POST['pan_number']) || $_POST['name'] == "") ? "pan_number," : "";
    if ($res_msg != "") {
        $response['error'] = true;
        $response['message'] = "This fields " . trim($res_msg, ",") . " should be Passed!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $store_name = $db->escapeString($fn->xss_clean($_POST['store_name']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    $tax_name = $db->escapeString($fn->xss_clean($_POST['tax_name']));
    $tax_number = $db->escapeString($fn->xss_clean($_POST['tax_number']));
    $pan_number = $db->escapeString($fn->xss_clean($_POST['pan_number']));

    $status = (isset($_POST['status']) && $_POST['status'] != "") ? $db->escapeString($fn->xss_clean($_POST['status'])) : "2";
    $store_url = (isset($_POST['store_url']) && $_POST['store_url'] != "") ? $db->escapeString($fn->xss_clean($_POST['store_url'])) : "";
    $store_description = (isset($_POST['description']) && $_POST['description'] != "") ? $db->escapeString($fn->xss_clean($_POST['description'])) : "";
    $street = (isset($_POST['street']) && $_POST['street'] != "") ? $db->escapeString($fn->xss_clean($_POST['street'])) : "";
    $pincode_id = (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "0";
    $state = (isset($_POST['state']) && $_POST['state'] != "") ? $db->escapeString($fn->xss_clean($_POST['state'])) : "";
    $account_number = (isset($_POST['account_number']) && $_POST['account_number'] != "") ? $db->escapeString($fn->xss_clean($_POST['account_number'])) : "";
    $bank_ifsc_code = (isset($_POST['ifsc_code']) && $_POST['ifsc_code'] != "") ? $db->escapeString($fn->xss_clean($_POST['ifsc_code'])) : "";
    $account_name = (isset($_POST['account_name']) && $_POST['account_name'] != "") ? $db->escapeString($fn->xss_clean($_POST['account_name'])) : "";
    $bank_name = (isset($_POST['bank_name']) && $_POST['bank_name'] != "") ? $db->escapeString($fn->xss_clean($_POST['bank_name'])) : "";
    $latitude = (isset($_POST['latitude']) && $_POST['latitude'] != "") ? $db->escapeString($fn->xss_clean($_POST['latitude'])) : "0";
    $longitude = (isset($_POST['longitude']) && $_POST['longitude'] != "") ? $db->escapeString($fn->xss_clean($_POST['longitude'])) : "0";


    $old_password = (isset($_POST['old_password']) && !empty(trim($_POST['old_password']))) ? $db->escapeString(trim($fn->xss_clean($_POST['old_password']))) : "";
    $update_password = (isset($_POST['update_password']) && !empty(trim($_POST['update_password']))) ? $db->escapeString(trim($fn->xss_clean($_POST['update_password']))) : "";
    $confirm_password = (isset($_POST['confirm_password']) && !empty(trim($_POST['confirm_password']))) ? $db->escapeString(trim($fn->xss_clean($_POST['confirm_password']))) : "";
    $change_password = false;


    /* check if id is not empty and there is valid data in it */
    if (!isset($_POST['seller_id']) || empty(trim($_POST['seller_id'])) || !is_numeric($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Invalid Id of Seller";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $sql = "SELECT * from seller where id='$id'";
    $db->sql($sql);
    $res_id = $db->getResult();
    $num = $db->numRows($res_id);
    if ($num != 1) {
        $response['error'] = true;
        $response['message'] = "Seller is not Registered.";
        print_r(json_encode($response));
        return false;
        exit();
    }
    if (!empty($res_id) && ($res_id[0]['status'] == 2 || $res_id[0]['status'] == 7)) {
        $response['error'] = true;
        $response['message'] = "Seller can not update becasue you have not-approoved or removed.";
        print_r(json_encode($response));
        return false;
        exit();
    }

    /* if any of the password field is set and old password is not set */
    if ((!empty($confirm_password) || !empty($update_password)) && empty($old_password)) {
        $response['error'] = true;
        $response['message'] = "Please enter old password.";
        print_r(json_encode($response));
        return false;
        exit();
    }

    /* either of the password field is not empty and is they don't match */
    if ((!empty($confirm_password) || !empty($update_password)) && ($update_password != $confirm_password)) {
        $response['error'] = true;
        $response['message'] = "Password and Confirm Password mismatched.";
        print_r(json_encode($response));
        return false;
        exit();
    }

    /* when all conditions are met check for old password in database */
    if (!empty($confirm_password) && !empty($update_password) && !empty($old_password)) {
        $old_password = md5($old_password);
        $sql = "Select password from `seller` where id = '$id' and password = '$old_password' ";
        $db->sql($sql);
        $res = $db->getResult();

        if (empty($res)) {
            $response['error'] = true;
            $response['message'] = "Old password mismatched.";
            print_r(json_encode($response));
            return false;
            exit();
        }
        $change_password = true;
        $confirm_password = md5($confirm_password);
    }


    if (!empty($change_password)) {
        $sql = "UPDATE `seller` SET `name`='$name',`store_name`='$store_name',`email`='$email',`password`='$confirm_password',`store_url`='$store_url',`store_description`='$store_description',`street`='$street',`pincode_id`='$pincode_id',`state`='$state',`account_number`='$account_number',`bank_ifsc_code`='$bank_ifsc_code',`account_name`='$account_name',`bank_name`='$bank_name',`latitude`='$latitude',`longitude`='$longitude',`status`=$status,`pan_number`='$pan_number',`tax_name`='$tax_name',`tax_number`='$tax_number' WHERE id=" . $id;
    } else {
        $sql = "UPDATE `seller` SET `name`='$name',`store_name`='$store_name',`email`='$email',`store_url`='$store_url',`store_description`='$store_description',`street`='$street',`pincode_id`='$pincode_id',`state`='$state',`account_number`='$account_number',`bank_ifsc_code`='$bank_ifsc_code',`account_name`='$account_name',`bank_name`='$bank_name',`latitude`='$latitude',`longitude`='$longitude',`status`=$status,`pan_number`='$pan_number',`tax_name`='$tax_name',`tax_number`='$tax_number' WHERE id=" . $id;
    }

    if ($db->sql($sql)) {

        if (isset($_FILES['store_logo']) && $_FILES['store_logo']['size'] != 0 && $_FILES['store_logo']['error'] == 0 && !empty($_FILES['store_logo'])) {
            //image isn't empty and update the image
            $old_logo = $res_id[0]['logo'];
            $extension = pathinfo($_FILES["store_logo"]["name"])['extension'];

            $result = $fn->validate_image($_FILES["store_logo"]);
            if (!$result) {
                $response['error'] = true;
                $response['message'] = "Store logo image type must jpg, jpeg, gif, or png!.";
                return false;
                exit();
            }
            $target_path = '../../upload/seller/';
            $filename = microtime(true) . '.' . strtolower($extension);
            $full_path = $target_path . "" . $filename;
            if (!move_uploaded_file($_FILES["store_logo"]["tmp_name"], $full_path)) {
                $response['error'] = true;
                $response['message'] = "Can not upload image.";
                return false;
                exit();
            }
            if (!empty($old_logo)) {
                unlink($target_path . $old_logo);
            }
            $sql = "UPDATE seller SET `logo`='" . $filename . "' WHERE `id`=" . $id;
            $db->sql($sql);
        }
        if (isset($_FILES['national_id_card']) && $_FILES['national_id_card']['size'] != 0 && $_FILES['national_id_card']['error'] == 0 && !empty($_FILES['national_id_card'])) {
            //image isn't empty and update the image
            $old_national_identity_card = $res_id[0]['national_identity_card'];
            $extension = pathinfo($_FILES["national_id_card"]["name"])['extension'];

            $result = $fn->validate_image($_FILES["national_id_card"]);
            if (!$result) {
                $response['error'] = true;
                $response['message'] = "National id card image type must jpg, jpeg, gif, or png!.";
                return false;
                exit();
            }
            $target_path = '../../upload/seller/';
            $national_id_card = microtime(true) . '.' . strtolower($extension);
            $full_path = $target_path . "" . $national_id_card;
            if (!move_uploaded_file($_FILES["national_id_card"]["tmp_name"], $full_path)) {
                $response['error'] = true;
                $response['message'] = "Can not upload image.";
                return false;
                exit();
            }
            if (!empty($old_national_identity_card)) {
                unlink($target_path . $old_national_identity_card);
            }
            $sql = "UPDATE seller SET `national_identity_card`='" . $national_id_card . "' WHERE `id`=" . $id;
            $db->sql($sql);
        }
        if (isset($_FILES['address_proof']) && $_FILES['address_proof']['size'] != 0 && $_FILES['address_proof']['error'] == 0 && !empty($_FILES['address_proof'])) {
            //image isn't empty and update the image
            $old_address_proof = $res_id[0]['address_proof'];;
            $extension = pathinfo($_FILES["address_proof"]["name"])['extension'];

            $result = $fn->validate_image($_FILES["address_proof"]);
            if (!$result) {
                $response['error'] = true;
                $response['message'] = "Address proof card image type must jpg, jpeg, gif, or png!.";;
                return false;
                exit();
            }
            $target_path = '../../upload/seller/';
            $address_proof = microtime(true) . '.' . strtolower($extension);
            $full_path = $target_path . "" . $address_proof;
            if (!move_uploaded_file($_FILES["address_proof"]["tmp_name"], $full_path)) {
                $response['error'] = true;
                $response['message'] = "Can not upload image.";
                return false;
                exit();
            }
            if (!empty($old_address_proof)) {
                unlink($target_path . $old_address_proof);
            }
            $sql = "UPDATE seller SET `address_proof`='" . $address_proof . "' WHERE `id`=" . $id;
            $db->sql($sql);
        }
        $response['error'] = false;
        $response['message'] = "Information Updated Successfully.";
        $response['message'] .= ($change_password) ? " and password also updated successfully." : "";
    } else {
        $response['error'] = true;
        $response['message'] = "Some Error Occurred! Please Try Again.";
    }
    print_r(json_encode($response));
}

/*
23.get_delivery_boys
    accesskey:90336
    get_delivery_boys:1
    pincode_id:1
*/
if (isset($_POST['get_delivery_boys']) && !empty($_POST['get_delivery_boys'] == 1)) {


    $limit = (isset($_POST['limit']) && !empty($_POST['limit'])) ? $_POST['limit'] : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset'])) ? $_POST['limit'] : 0;
    $where = '';
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $fn->xss_clean($_POST['search']);
        $where .= " AND name like '%$search%' or name like '%$search%' or mobile like '%$search%' or address like '%$search%'";
    }

    if (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) {
        $pincode_id = $_POST['pincode_id'];
        $where .= " AND  FIND_IN_SET($pincode_id, pincode_id)";
    }

    $sql = "SELECT count(id) as total FROM `delivery_boys` WHERE status=1 And is_available=1" . $where . "  limit $limit offset $offset";
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT * FROM delivery_boys WHERE status=1 And is_available=1 " . $where . "  ORDER BY id DESC limit $limit offset $offset";
    $db->sql($sql);
    $res1 = $db->getResult();
    if (!empty($res1)) {
        for ($i = 0; $i < count($res1); $i++) {
            $pending_orders = $fn->rows_count('order_items', 'distinct(order_id)', 'delivery_boy_id=' . $res1[$i]['id'] . ' and active_status != "cancelled" and active_status != "returned" and active_status != "delivered"');
            $res1[$i]['pending_orders'] = $pending_orders;
            $res1[$i]['driving_license'] = !empty($res1[$i]['driving_license'])  ?  DOMAIN_URL . 'upload/delivery-boy/' . $res1[$i]['driving_license'] : "";
            $res1[$i]['national_identity_card'] = !empty($res1[$i]['national_identity_card'])  ?  DOMAIN_URL . 'upload/delivery-boy/' . $res1[$i]['national_identity_card'] : "";
        }
        $response['error'] = false;
        $response['message'] = "Delivery boys retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $res1;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['change_status']) && $_POST['change_status'] != '') {
    /* 
    24.change_status
        accesskey:90336
        seller_id:114
        status:1/0
        change_status:1
    */
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response['error'] = true;
        $response['message'] = "This operation is not allowed in demo panel!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    if (!isset($_POST['seller_id']) || empty($_POST['seller_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass the seller id!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $id = $db->escapeString(trim($_POST['seller_id']));
    $is_exist = $fn->rows_count('seller', 'id', 'id=' . $id);
    if ($is_exist == 1) {
        if (isset($_POST['change_status']) && $_POST['change_status'] == 1) {
            $status = $db->escapeString($fn->xss_clean($_POST['status']));
            if (is_numeric($status) && ($status == '1' || $status == '0')) {
                $sql1 = "update seller set `status` ='$status' where id = '" . $id . "'";
                if ($db->sql($sql1)) {
                    $response['error'] = false;
                    $response['message'] = "Status updated successfully.";
                    print_r(json_encode($response));
                } else {
                    $response['error'] = true;
                    $response['message'] = "Can not update status.";
                    print_r(json_encode($response));
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Invalid status passed.";
                print_r(json_encode($response));
            }
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Seller not exists.";
        print_r(json_encode($response));
    }
}

// 24.add_pickup_location

/*   
    accesskey:90336
    add_pickup_location:1
    seller_id:1     
    pickup_location:madhar kutch
    name:madhar bhuj
    email:test@test.com
    phone:1234567890
    city:bhuj
    state:gujarat,
    country:india
    pin_code:370465
    address:#270,madhar par highway
    address_2:office number 5 60  //{optional}
    latitude:67.489797979    //{optional}
    longitude:68.49789797   //{optional}

*/




if (isset($_POST['add_pickup_location']) && !empty($_POST['add_pickup_location'])) {

    $seller_id = $db->escapeString($fn->xss_clean($_POST['seller_id']));
    $pickup_location = $db->escapeString($fn->xss_clean($_POST['pickup_location']));
    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    $phone = $db->escapeString($fn->xss_clean($_POST['phone']));
    $city = $db->escapeString($fn->xss_clean($_POST['city']));
    $state = $db->escapeString($fn->xss_clean($_POST['state']));
    $country = $db->escapeString($fn->xss_clean($_POST['country']));
    $pin_code = $db->escapeString($fn->xss_clean($_POST['pin_code']));
    $address = $db->escapeString($fn->xss_clean($_POST['address']));
    $address_2 = (isset($_POST['address_2']) && !empty($_POST['address_2'])) ? $db->escapeString($fn->xss_clean($_POST['address_2'])) : "";
    $latitude = (isset($_POST['latitude']) && !empty($_POST['latitude'])) ? $db->escapeString($fn->xss_clean($_POST['latitude'])) : "";
    $longitude = (isset($_POST['longitude']) && !empty($_POST['longitude'])) ? $db->escapeString($fn->xss_clean($_POST['longitude'])) : "";
    // echo $pickup_location;


    $url = $pickup_location . " " . $seller_id;
    $pickup_location = $function->slugify($url);
    $pickup_location_data = array(
        'pickup_location' => $pickup_location,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'pin_code' => $pin_code,
        'address' => $address,
        'address_2' => $address_2,
        'latitude' => $latitude,
        'longitude' => $longitude
    );

    /* first try to add pickup location in shiprocket  */
    $pickup_location = $shiprocket->add_pickup_location($pickup_location_data);
    $pickup_location = $pickup_location;
    if (isset($pickup_location['success']) && $pickup_location['success'] == 1) {
        $pickup_location_data['seller_id'] = $seller_id;
        $db->insert('pickup_locations', $pickup_location_data);
        $response['error'] = false;
        $response['message'] = 'Pickup Location Added Successfully';
        $response['data'] = $pickup_location_data;
    } else {
        $error_msg = '';
        if (isset($pickup_location['errors']) && !empty($pickup_location['errors'])) {
            foreach ($pickup_location['errors'] as $error) {
                for ($i = 0; $i < count($error); $i++) {

                    $error_msg .= $error[$i];
                }
            }
        } else {
            if (isset($pickup_location['message']) && !empty($pickup_location['message'])) {
                $error_msg = $pickup_location['message'];
            } else {
                $error_msg = "Something went wrong" . $pickup_location['message'];
            }
        }
        $response['error'] = true;
        $response['message'] = $pickup_location['message'];
        $response['error_data'] = $error_msg;
    }
    echo json_encode($response);
    return false;
}
/* 25.get_pickup_location

get_pickup_location:1
accesskey:90336
seller_id:2
search:test {optioanal}
limit:10  {optioanal}
offset:1 {optioanal}

*/

if (isset($_POST['get_pickup_location'])  ||  !empty($_POST['get_pickup_location'])) {
    if (!isset($_POST['seller_id']) && empty($_POST['seller_id'])) {
        $result['error'] = true;
        $result['message'] = 'Seller id Should Be Pass';
        print_r(json_encode($result));
        return false;
    }
    $search = (isset($_POST['search']) && !empty($_POST['search'])) ? $_POST['search'] : " ";
    $limit = (isset($_POST['limit']) && !empty($_POST['limit'])) ? $_POST['limit'] : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset'])) ? $_POST['offset'] : 0;

    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $where = "AND (pickup_location like '%$search%' or pin_code like '%$search%' or name like '%$search%' or phone like '%$search%' or address like '%$search%' )";
    }

    $sql_query = "SELECT COUNT(id) as total FROM  pickup_locations WHERE   seller_id=" . $_POST['seller_id'] . " $where  LIMIT $limit OFFSET $offset";
    $db->sql($sql_query);
    $total = $db->getresult();


    $sql_query = "SELECT * FROM  pickup_locations WHERE  seller_id=" . $_POST['seller_id'] . " $where  LIMIT $limit OFFSET $offset";
    // echo $sql_query;
    $db->sql($sql_query);
    $data = $db->getresult();

    if (!empty($data)) {
        $result['error'] = false;
        $result['message'] = 'Pickup Locations Geted Successfully';
        $result['total'] = "" . count($data) . "";
        $result['data'] = $data;
    } else {
        $result['error'] = true;
        $result['message'] = 'You have not Added any pickuplocations';
    }
    print_r(json_encode($result));
}

if (isset($_POST['get_slug'])  ||  !empty($_POST['get_slug'])) {
    if (!isset($_POST['seller_id']) && empty($_POST['seller_id'])) {
        $result['error'] = true;
        $result['message'] = 'Seller id Should Be Pass';
        print_r(json_encode($result));
        return false;
    }
    $sql_query = "SELECT pickup_location FROM  pickup_locations ";
    // echo $sql_query;
    $db->sql($sql_query);
    $data = $db->getresult();

    $pickup_location = array();

    foreach ($data as $pikcuplocation) {
        $pickup_location[] = $pikcuplocation['pickup_location'];
    }
    if (!empty($data)) {
        $result['error'] = false;
        $result['message'] = 'Pickup Locations Geted Successfully';
        $result['data'] = $pickup_location;
    } else {
        $result['error'] = true;
        $result['message'] = 'You have not Added any pickup locations';
    }
    print_r(json_encode($result));
}




/* 
27. create_shiprocket_order
    accesskey:90336
    create_shiprocket_order:1
    order_item_ids:288,286
    order_id:1457
    seller_id:2
    pickup_locations:mirzapar-bhuj-1   //pickup location 
    weight:2                 // wieght of parcel in kg
    height:2                 // height of parcel in cms
    length:2                // length of parcel in cms
    breadth:2                // breadth of parcel in cms
    subtotal:36.9            //total of selected items total


*/

if (isset($_POST['create_shiprocket_order']) && !empty($_POST['create_shiprocket_order'])) {
    $order_item_ids = explode(",", $_POST['order_item_ids']);
    $order_id = $_POST['order_id'];
    $seller_id = $_POST['seller_id'];
    $pickup_location = $_POST['pickup_locations'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    $length = $_POST['length'];
    $breadth = $_POST['breadth'];
    $sub_total = $_POST['subtotal'];


    $order_details = $fn->get_data(['*'], "order_id=$order_id AND order_item_id in (" . $_POST['order_item_ids'] . ")", 'order_trackings');
    // print_r($order_details);
    if (!empty($order_details)) {
        $res['error'] = true;
        $res['message'] = "Order already created";
        print_r(json_encode($res));
        return false;
    }
    $res = $fn->process_shiprocket($order_id, $seller_id, $pickup_location, $sub_total, $weight, $height, $breadth, $length, $order_item_ids);
    if ($res['status_code'] == 1) {
        $order_item_ids = $_POST['order_item_ids'];
        $sql = "select count(DISTINCT(order_id)) as total from order_items oi JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND o.id=$order_id and oi.id in ($order_item_ids) and and  oi.seller_id=" . $seller_id;
        $db->sql($sql);
        $res = $db->getResult();
        $total_count = $res[0]['total'];
        $sql = "select DISTINCT o.id,oi.seller_id, o.*,(select name from users u where u.id=o.user_id) as user_name from orders o JOIN order_items oi ON o.id=oi.order_id JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND  o.id=$order_id  and oi.seller_id=$seller_id";
        // echo $sql;
        $db->sql($sql);
        $res = $db->getResult();
        $i = 0;
        $j = 0;


        foreach ($res as $row) {
            $final_sub_total = 0;
            // print_r($row);
            if ($row['discount'] > 0) {
                $discounted_amount = $row['total'] * $row['discount'] / 100;
                $final_total = $row['total'] - $discounted_amount;
                $discount_in_rupees = $row['total'] - $final_total;
            } else {
                $discount_in_rupees = 0;
            }

            $res[$i]['discounted_price'] = strval($discount_in_rupees);
            $final_total = ceil($res[$i]['final_total']);
            $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $res[$i]['seller_id'], 'seller');
            $res_pincode = $fn->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
            $res_city = $fn->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
            $city = isset($res_city[0]['name']) && !empty($res_city[0]['name']) ? $res_city[0]['name'] . ' - ' : '';
            $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . ", " : "";
            $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . ", " : "";
            $pincode = (!empty($res_seller[0]['pincode_id'])) ? $city . $res_pincode[0]['pincode'] : "";
            $seller_address = $state  . $street . $pincode;
            $res[$i]['final_total'] = strval($final_total + $res[$i]['delivery_charge']);
            $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));

            $sql = "select oi.*,v.id as variant_id,v.weight, p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.pickup_location, p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where  oi.id in ($order_item_ids) AND oi.seller_id=$seller_id";
            $db->sql($sql);
            $res[$i]['items'] = $db->getResult();


            for ($j = 0; $j < count($res[$i]['items']); $j++) {


                $res[$i]['items'][$j]['status'] = "";
                if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                    $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                }

                if (!empty($res[$i]['items'][$j]['seller_id'])) {
                    $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                    $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                    $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                } else {
                    $res[$i]['items'][$j]['seller_id'] = "";
                    $res[$i]['items'][$j]['seller_name'] = "";
                    $res[$i]['items'][$j]['seller_store_name'] = "";
                }
                $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];
                $res[$i]['items'][$j]['shipping_method'] = "standard";
                $res[$i]['items'][$j]['seller_address'] = $seller_address;
                $res[$i]['items'][$j]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
                $res[$i]['items'][$j]['seller_mobile'] = $res_seller[0]['mobile'];
                $res[$i]['items'][$j]['seller_address'] = $seller_address;
                $res[$i]['items'][$j]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
                $res[$i]['items'][$j]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
                if (!empty($res[$i]['items'][$j]['delivery_boy_id'])) {
                    $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $res[$i]['items'][$j]['delivery_boy_id'], 'delivery_boys');
                    $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
                } else {
                    $res[$i]['items'][$j]['delivery_boy_name'] = "";
                }
                $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
                $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
                $res[$i]['items'][$j]['pickup_location'] = !empty($res[$i]['items'][$j]['pickup_location']) ?    $res[$i]['items'][$j]['pickup_location'] : "";
                $order_tracking_data = [];
                $order_tracking_data = $fn->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');
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
            }
            $res[$i]['final_total'] = strval($row['final_total']);
            $res[$i]['total'] = strval($row['total']);
            $i++;
        }

        $orders = $order = array();
        if (empty($res)) {

            $response['error'] = true;
            $response['message'] = "No items found on this order";
            print_r(json_encode($response));
            return;
        }
        $result['error'] = false;
        $result['message'] = 'Order Created Successfully';
        $result['data'] = $res;
        print_r(json_encode($result));
    } else {
        $result['error'] = true;
        $result['message'] = $res['message'];
        $result['data'] = (isset($res['data']) && !empty($res['data'])) ? $res['data'] : '';
        print_r(json_encode($result));
    }
}





/*
28 Send Pickup Request
    accesskey:90336
    send_pickup_request:1
    shipment_id:123456789
    seller_id:2
*/

if (isset($_POST['send_pickup_request']) && !empty($_POST['send_pickup_request'])) {
    if (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) {

        $shipment_id = $_POST['shipment_id'];
        $seller_id = $_POST['seller_id'];
        $get_order_id = $fn->get_data(['shiprocket_order_id'], 'pickup_status=0 And shipment_id=' . $shipment_id, 'order_trackings');
        if (empty($get_order_id)) {
            $res['error'] = true;
            $res['message'] = "Order not found Or already requested for pickup";
            print_r(json_encode($res));
            return false;
        }
        $res = $fn->send_request_for_pickup($shipment_id);
        $order_details = $fn->get_data(['order_id', 'order_item_id'], "shipment_id=$shipment_id", 'order_trackings');

        $item_ids = [];
        foreach ($order_details as $items) {
            $items_ids[] = $items['order_item_id'];
        }
        $order_id = $order_details[0]['order_id'];
        $order_item_ids = implode(',', array_unique($items_ids));

        if ($res) {

            $sql = "select count(DISTINCT(order_id)) as total from order_items oi JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND o.id=$order_id and oi.id in ($order_item_ids) and and  oi.seller_id=" . $seller_id;
            $db->sql($sql);
            $res = $db->getResult();
            $total_count = $res[0]['total'];
            $sql = "select DISTINCT o.id,oi.seller_id, o.*,(select name from users u where u.id=o.user_id) as user_name from orders o JOIN order_items oi ON o.id=oi.order_id JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND  o.id=$order_id  and oi.seller_id=$seller_id";
            // echo $sql;
            $db->sql($sql);
            $res = $db->getResult();
            $i = 0;
            $j = 0;


            foreach ($res as $row) {
                $final_sub_total = 0;
                // print_r($row);
                if ($row['discount'] > 0) {
                    $discounted_amount = $row['total'] * $row['discount'] / 100;
                    $final_total = $row['total'] - $discounted_amount;
                    $discount_in_rupees = $row['total'] - $final_total;
                } else {
                    $discount_in_rupees = 0;
                }

                $res[$i]['discounted_price'] = strval($discount_in_rupees);
                $final_total = ceil($res[$i]['final_total']);
                $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $res[$i]['seller_id'], 'seller');
                $res_pincode = $fn->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
                $res_city = $fn->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
                $city = isset($res_city[0]['name']) && !empty($res_city[0]['name']) ? $res_city[0]['name'] . ' - ' : '';
                $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . ", " : "";
                $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . ", " : "";
                $pincode = (!empty($res_seller[0]['pincode_id'])) ? $city . $res_pincode[0]['pincode'] : "";
                $seller_address = $state  . $street . $pincode;
                $res[$i]['final_total'] = strval($final_total + $res[$i]['delivery_charge']);
                $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));

                $sql = "select oi.*,v.id as variant_id,v.weight, p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.pickup_location, p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where  oi.id in ($order_item_ids) AND oi.seller_id=$seller_id";
                $db->sql($sql);
                $res[$i]['items'] = $db->getResult();


                $res[$i]['status'] = "";
                $res[$i]['active_status'] = "";
                for ($j = 0; $j < count($res[$i]['items']); $j++) {
                    $res[$i]['items'][$j]['status'] = "";
                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                    }

                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];
                    $res[$i]['items'][$j]['shipping_method'] = "standard";
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
                    $res[$i]['items'][$j]['seller_mobile'] = $res_seller[0]['mobile'];
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
                    $res[$i]['items'][$j]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
                    if (!empty($res[$i]['items'][$j]['delivery_boy_id'])) {
                        $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $res[$i]['items'][$j]['delivery_boy_id'], 'delivery_boys');
                        $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
                    } else {
                        $res[$i]['items'][$j]['delivery_boy_name'] = "";
                    }
                    $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
                    $res[$i]['items'][$j]['pickup_location'] = !empty($res[$i]['items'][$j]['pickup_location']) ?    $res[$i]['items'][$j]['pickup_location'] : "";
                    $order_tracking_data = [];
                    $order_tracking_data = $fn->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');
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
                }
                $res[$i]['final_total'] = strval($row['final_total']);
                $res[$i]['total'] = strval($row['total']);
                $i++;
            }

            $orders = $order = array();
            if (empty($res)) {

                $response['error'] = true;
                $response['message'] = "No items found on this order";
                print_r(json_encode($response));
                return;
            }
            $result['error'] = false;
            $result['message'] = "Request For Pickup Sended Successfully";
            $result['data'] = $res;
        } else {
            $result['error'] = true;
            $result['message'] = "Some thing get wrong";
        }
    }
    print_r(json_encode($result));
}
/*
29  track order
    accesskey:90336
    track_order:1
    shipment_id:123456789
*/


if (isset($_POST['track_order']) && !empty($_POST['track_order'])) {
    /*
    accesskey:90336
    user_id:4
    track_order:1
    oder_item_id:15670
    */

    if (!isset($_POST['shipment_id']) && $_POST['order_item_id'] == 0) {
        $res['error'] = true;
        $res['error'] = 'shipment  id missing';
        print_r(json_encode($res));
        return false;
    } else if (!isset($_POST['order_item_id']) && $_POST['shipment_id'] == 0) {
        $res['error'] = true;
        $res['error'] = 'Order item id is missing';
        print_r(json_encode($res));
        return false;
    } elseif (!empty($_POST['shipment_id']) && !empty($_POST['order_item_id'])) {
        $res['error'] = true;
        $res['error'] = 'You cannot pass both ids';
        print_r(json_encode($res));
        return false;
    }
    if (isset($_POST['shipment_id']) && $_POST['order_item_id'] == 0) {
        $shipment_id = $_POST['shipment_id'];
        $tracking_data = $fn->track_order($shipment_id);
        if (!empty($tracking_data['tracking_data'])) {
            for ($i = 0; $i < count($tracking_data['tracking_data']['shipment_track_activities']); $i++) {
                unset($tracking_data['tracking_data']['shipment_track_activities'][$i]['id']);
                unset($tracking_data['tracking_data']['shipment_track_activities'][$i]['ship_track_id']);
            }
            $res['error'] = false;
            $res['current_status'] = $tracking_data['tracking_data']['shipment_track'][0]['current_status'];
            $res['activities'] = $tracking_data['tracking_data']['shipment_track_activities'];
        } elseif (isset($tracking_data['messsage'])) {
            $res['error'] = true;
            $res['message'] = $tracking_data['messsage'];
        } else {
            $res['error'] = true;
            $res['message'] = 'No data found';
        }
    } elseif (isset($_POST['order_item_id']) && $_POST['shipment_id'] == 0) {
        $order_item_id = $_POST['order_item_id'];
        $tracking_data = $fn->get_data(['status', 'active_status'], 'id=' . $order_item_id, 'order_items');
        if (!empty($tracking_data)) {
            $status = json_decode($tracking_data[0]['status'], 1);
            $key = 0;
            $temp = array();
            for ($i = 0; $i < count($status); $i++) {
                $temp[$key]['date'] = $status[$i][1];
                $temp[$key]['location'] = "";
                $temp[$key]['activity'] = $status[$i][0];
                $key++;
            }
            $res['error'] = false;
            $res['current_status'] = $tracking_data[0]['active_status'];
            $res['activities'] = $temp;
        } else {
            $res['error'] = true;
            $res['message'] = 'No data found';
        }
    }
    print_r(json_encode($res));
    return false;
}


/*
30. cancel_order 
    accesskey:90336
    cancel_order:1
    shiprocket_order_id:12345678910
    seller_id:1

*/

if (isset($_POST['cancel_order']) && !empty($_POST['cancel_order'])) {
    if (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) {
        $shipment_id = $_POST['shipment_id'];
        $get_order_id = $fn->get_data(['shiprocket_order_id'], 'is_canceled=0 And shipment_id=' . $shipment_id, 'order_trackings');
        // print_r($get_order_id);
        if (empty($get_order_id)) {
            $res['error'] = true;
            $res['message'] = "Order not found Or already canceled";
            print_r(json_encode($res));
            return false;
        }

        $shiprocket_order_id = $oders_data[0]['shiprocket_order_id'];
        $order_id['ids'] = [$shiprocket_order_id];
        $res = $shiprocket->cancel_order($order_id);
        if ($res == 200) {
            $sql = "UPDATE order_trackings  SET is_canceled=1 where shiprocket_order_id=" . $shiprocket_order_id;
            $db->sql($sql);
            $seller_id = $_POST['seller_id'];
            $order_details = $fn->get_data(['order_id', 'order_item_id'], "shipment_id=$shipment_id", 'order_trackings');

            $item_ids = [];
            foreach ($order_details as $items) {
                $items_ids[] = $items['order_item_id'];
            }
            $order_id = $order_details[0]['order_id'];
            $order_item_ids = implode(',', array_unique($items_ids));

            $sql = "select count(DISTINCT(order_id)) as total from order_items oi JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND o.id=$order_id and oi.id in ($order_item_ids) and and  oi.seller_id=" . $seller_id;
            $db->sql($sql);
            $res = $db->getResult();
            $total_count = $res[0]['total'];
            $sql = "select DISTINCT o.id,oi.seller_id, o.*,(select name from users u where u.id=o.user_id) as user_name from orders o JOIN order_items oi ON o.id=oi.order_id JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND  o.id=$order_id  and oi.seller_id=$seller_id";
            $db->sql($sql);
            $res = $db->getResult();
            $i = 0;
            $j = 0;


            foreach ($res as $row) {
                $final_sub_total = 0;
                // print_r($row);
                if ($row['discount'] > 0) {
                    $discounted_amount = $row['total'] * $row['discount'] / 100;
                    $final_total = $row['total'] - $discounted_amount;
                    $discount_in_rupees = $row['total'] - $final_total;
                } else {
                    $discount_in_rupees = 0;
                }

                $res[$i]['discounted_price'] = strval($discount_in_rupees);
                $final_total = ceil($res[$i]['final_total']);
                $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $res[$i]['seller_id'], 'seller');
                $res_pincode = $fn->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
                $res_city = $fn->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
                $city = isset($res_city[0]['name']) && !empty($res_city[0]['name']) ? $res_city[0]['name'] . ' - ' : '';
                $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . ", " : "";
                $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . ", " : "";
                $pincode = (!empty($res_seller[0]['pincode_id'])) ? $city . $res_pincode[0]['pincode'] : "";
                $seller_address = $state  . $street . $pincode;
                $res[$i]['final_total'] = strval($final_total + $res[$i]['delivery_charge']);
                $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));

                $sql = "select oi.*,v.id as variant_id,v.weight, p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.pickup_location, p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where  oi.id in ($order_item_ids) AND oi.seller_id=$seller_id";
                $db->sql($sql);
                $res[$i]['items'] = $db->getResult();


                $res[$i]['status'] = "";
                $res[$i]['active_status'] = "";
                for ($j = 0; $j < count($res[$i]['items']); $j++) {
                    $res[$i]['items'][$j]['status'] = "";
                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                    }

                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];
                    $res[$i]['items'][$j]['shipping_method'] = "standard";
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
                    $res[$i]['items'][$j]['seller_mobile'] = $res_seller[0]['mobile'];
                    $res[$i]['items'][$j]['seller_address'] = $seller_address;
                    $res[$i]['items'][$j]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
                    $res[$i]['items'][$j]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
                    if (!empty($res[$i]['items'][$j]['delivery_boy_id'])) {
                        $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $res[$i]['items'][$j]['delivery_boy_id'], 'delivery_boys');
                        $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
                    } else {
                        $res[$i]['items'][$j]['delivery_boy_name'] = "";
                    }
                    $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
                    $res[$i]['items'][$j]['pickup_location'] = !empty($res[$i]['items'][$j]['pickup_location']) ?    $res[$i]['items'][$j]['pickup_location'] : "";
                    $order_tracking_data = [];
                    $order_tracking_data = $fn->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');
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
                }
                $res[$i]['final_total'] = strval($row['final_total']);
                $res[$i]['total'] = strval($row['total']);
                $i++;
            }

            $orders = $order = array();
            if (empty($res)) {

                $response['error'] = true;
                $response['message'] = "No items found on this order";
                print_r(json_encode($response));
                return;
            }

            $result['error'] = false;
            $result['message'] = "Your Order Cancelled successfully";
            $result['data'] = $res;
        } else {
            $result['error'] = true;
            $result['message'] = $res['message'];
        }
        print_r(json_encode($result));
    }
}


/*
31. get_shipping_type:
    accesskey:90336
    get_shipping_type:1

*/

if (isset($_POST['get_shipping_type']) && !empty($_POST['get_shipping_type'])) {
    if (!empty($shipping_type)) {
        $result['error'] = false;
        $result['message'] = "shipping type fetched successfully";
        $result['maintenance'] = $fn->get_maintenance_mode('seller');
        $result['shipping_type'] = $shipping_type;
    } else {
        $result['error'] = true;
        $result['message'] = 'some things wrong';
    }
    print_r(json_encode($result));
}


/*
33. delete_variant_images
    accesskey:90336
    delete_variant_images:1
    variant_id:1
    image:1     // {index of image array}
*/

if (isset($_POST['delete_variant_images']) && $_POST['delete_variant_images'] == 1) {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        $response['error'] = true;
        $response['message'] = "This operation is not allowed in demo panel!.!";
        print_r(json_encode($response));
        return false;
    }
    if (empty($_POST['variant_id']) || $_POST['image'] == "") {
        $response['error'] = true;
        $response['message'] = "All fields should be Passed!";
        print_r(json_encode($response));
        return false;
    }
    $vid = $db->escapeString($fn->xss_clean($_POST['variant_id']));
    $i = $db->escapeString($fn->xss_clean($_POST['image']));
    $result = $fn->delete_variant_images($vid, $i);
    if ($result == 1) {
        $response['error'] = false;
        $response['message'] = "Image deleted successfully";
    } else {
        $response['error'] = true;
        $response['message'] = "Image is not deleted. try agian later";
    }
    print_r(json_encode($response));
    return false;
}



/*
34 .generate_awb
    accesskey:90336
    shipment_id:789456123
    seller_id:1
*/

if (isset($_POST['generate_awb']) && !empty($_POST['generate_awb'])) {

    $shipment_id = (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) ? $_POST['shipment_id'] : '';
    $get_order_id = $fn->get_data(['shiprocket_order_id'], 'awb_code!="" And shipment_id=' . $shipment_id, 'order_trackings');
    if (empty($get_order_id)) {
        $res['error'] = true;
        $res['message'] = "Order not found Or already generated";
        print_r(json_encode($res));
        return false;
    }
    $res = $fn->generate_awb($shipment_id);
    $seller_id = $_POST['seller_id'];
    if ($res['error'] == false) {
        $order_details = $fn->get_data(['order_id', 'order_item_id'], "shipment_id=$shipment_id", 'order_trackings');

        $item_ids = [];
        foreach ($order_details as $items) {
            $items_ids[] = $items['order_item_id'];
        }
        $order_id = $order_details[0]['order_id'];
        $order_item_ids = implode(',', array_unique($items_ids));

        $sql = "select count(DISTINCT(order_id)) as total from order_items oi JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND o.id=$order_id and oi.id in ($order_item_ids) and and  oi.seller_id=" . $seller_id;
        $db->sql($sql);
        $res = $db->getResult();
        $total_count = $res[0]['total'];
        $sql = "select DISTINCT o.id,oi.seller_id, o.*,(select name from users u where u.id=o.user_id) as user_name from orders o JOIN order_items oi ON o.id=oi.order_id JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where p.standard_shipping=1 AND  o.id=$order_id  and oi.seller_id=$seller_id";
        // echo $sql;
        $db->sql($sql);
        $res = $db->getResult();
        $i = 0;
        $j = 0;


        foreach ($res as $row) {
            $final_sub_total = 0;
            // print_r($row);
            if ($row['discount'] > 0) {
                $discounted_amount = $row['total'] * $row['discount'] / 100;
                $final_total = $row['total'] - $discounted_amount;
                $discount_in_rupees = $row['total'] - $final_total;
            } else {
                $discount_in_rupees = 0;
            }

            $res[$i]['discounted_price'] = strval($discount_in_rupees);
            $final_total = ceil($res[$i]['final_total']);
            $res_seller = $fn->get_data($columns = ['name', 'mobile', 'latitude', 'longitude', 'state', 'street', 'pincode_id', 'city_id'], "id=" . $res[$i]['seller_id'], 'seller');
            $res_pincode = $fn->get_data($columns = ['pincode'], "id=" . $res_seller[0]['pincode_id'], 'pincodes');
            $res_city = $fn->get_data($columns = ['name'], "id=" . $res_seller[0]['city_id'], 'cities');
            $city = isset($res_city[0]['name']) && !empty($res_city[0]['name']) ? $res_city[0]['name'] . ' - ' : '';
            $state = (!empty($res_seller[0]['state'])) ? $res_seller[0]['state'] . ", " : "";
            $street = (!empty($res_seller[0]['street'])) ? $res_seller[0]['street'] . ", " : "";
            $pincode = (!empty($res_seller[0]['pincode_id'])) ? $city . $res_pincode[0]['pincode'] : "";
            $seller_address = $state  . $street . $pincode;
            $res[$i]['final_total'] = strval($final_total + $res[$i]['delivery_charge']);
            $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));

            $sql = "select oi.*,v.id as variant_id,v.weight, p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.pickup_location, p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi join product_variant v on oi.product_variant_id=v.id join products p on p.id=v.product_id where  oi.id in ($order_item_ids) AND oi.seller_id=$seller_id";
            $db->sql($sql);
            $res[$i]['items'] = $db->getResult();


            $res[$i]['status'] = "";
            $res[$i]['active_status'] = "";
            for ($j = 0; $j < count($res[$i]['items']); $j++) {
                $res[$i]['items'][$j]['status'] = "";
                if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                    $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                }

                if (!empty($res[$i]['items'][$j]['seller_id'])) {
                    $seller_info = $fn->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                    $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                    $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                } else {
                    $res[$i]['items'][$j]['seller_id'] = "";
                    $res[$i]['items'][$j]['seller_name'] = "";
                    $res[$i]['items'][$j]['seller_store_name'] = "";
                }
                $res[$i]['items'][$j]['seller_id'] = $res[$i]['seller_id'];
                $res[$i]['items'][$j]['shipping_method'] = "standard";
                $res[$i]['items'][$j]['seller_address'] = $seller_address;
                $res[$i]['items'][$j]['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
                $res[$i]['items'][$j]['seller_mobile'] = $res_seller[0]['mobile'];
                $res[$i]['items'][$j]['seller_address'] = $seller_address;
                $res[$i]['items'][$j]['seller_latitude'] = !empty($res_seller[0]['latitude']) ? $res_seller[0]['latitude'] : '0';
                $res[$i]['items'][$j]['seller_longitude'] = !empty($res_seller[0]['longitude']) ? $res_seller[0]['longitude'] : '0';
                if (!empty($res[$i]['items'][$j]['delivery_boy_id'])) {
                    $delvery_boy_info = $fn->get_data($columns = ['name'], "id=" . $res[$i]['items'][$j]['delivery_boy_id'], 'delivery_boys');
                    $res[$i]['items'][$j]['delivery_boy_name'] = $delvery_boy_info[0]['name'];
                } else {
                    $res[$i]['items'][$j]['delivery_boy_name'] = "";
                }
                $item_details = $fn->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
                $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
                $res[$i]['items'][$j]['pickup_location'] = !empty($res[$i]['items'][$j]['pickup_location']) ?    $res[$i]['items'][$j]['pickup_location'] : "";
                $order_tracking_data = [];
                $order_tracking_data = $fn->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');
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
            }
            $res[$i]['final_total'] = strval($row['final_total']);
            $res[$i]['total'] = strval($row['total']);
            $i++;
        }

        $orders = $order = array();
        if (empty($res)) {

            $response['error'] = true;
            $response['message'] = "No items found on this order";
            print_r(json_encode($response));
            return;
        }

        $response['error'] = false;
        $response['message'] = "AWB generated successfully";
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "awb not generated";
    }
    print_r(json_encode($response));
}
