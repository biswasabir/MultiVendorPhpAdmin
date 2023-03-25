<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-CSRF-Token, X-Requested-With, Content-Type, Accept, Authorization');
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
include('../library/shiprocket.php');
$fn = new custom_functions();
$db = new Database();
$shiprocket = new Shiprocket();
$db->connect();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$shipping_type = ($fn->get_settings('local_shipping') == 1) ? 'local' : 'standard';
$currency = $fn->get_settings('currency');

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
1. get_all_products
2. get_products_offline
3. get_variants_offline
4. get_similar_products
5. products_search
6. get_all_products_name
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

if (isset($_POST['get_all_products']) && $_POST['get_all_products'] == 1) {
    /* 
    1.get_all_products
        accesskey:90336
        get_all_products:1
        pincode_id:1 // {optional}
        pincode:5           // {optional}
        product_id:219      // {optional}
        user_id:1782        // {optional}
        seller_id:1         // {optional}
        category_id:29      // {optional}
        subcategory_id:132  // {optional}
        limit:5             // {optional}
        offset:1            // {optional}
        search:dhosa        // {optional}
        slug:pizza-1        // {optional}
        seller_slug:ekart-seller-store //{optional}
        sort:new / old / high / low  // {optional}
    */

    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

    $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'row_order';
    $order = '';
    $product_id = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_id'])) : "";
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $pincode = (isset($_POST['pincode']) && !empty($_POST['pincode'])) ? $db->escapeString($fn->xss_clean($_POST['pincode'])) : "";

    $category_id = (isset($_POST['category_id']) && !empty($_POST['category_id'])) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : "";
    $subcategory_id = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : "0";
    $seller_id = (isset($_POST['seller_id']) && !empty($_POST['seller_id'])) ? $db->escapeString($fn->xss_clean($_POST['seller_id'])) : "";

    $where = "";
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
    if ($sort == 'row_order') {
        $order = 'ASC';
    } else {
        if (isset($_POST['order']) && !empty($_POST['order'])) {
            $order = $fn->xss_clean($_POST['order']);
        }
    }

    $is_pincode = $fn->get_data($column = ['pincode', 'id'], "pincode=" . $pincode, "pincodes");
    if (empty($is_pincode)) {
        $response['error'] = true;
        $response['message'] = "Invalid Pincode passed.";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $where = "";
    if (isset($_POST['search']) && $_POST['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where .= " AND (p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR s.`name` like '%" . $search . "%' OR p.`subcategory_id` like '%" . $search . "%' OR p.`category_id` like '%" . $search . "%' OR p.`slug` like '%" . $search . "%' OR p.`description` like '%" . $search . "%') ";
    }

    if (isset($_POST['product_id']) && !empty($_POST['product_id']) && is_numeric($_POST['product_id'])) {
        $where .= " AND p.`id` = " . $product_id;
    }

    if (isset($_POST['seller_slug']) && !empty($_POST['seller_slug'])) {
        $seller_slug = $db->escapeString($fn->xss_clean($_POST['seller_slug']));
        if (isset($_POST['category_id']) && !empty($_POST['category_id']) && is_numeric($_POST['category_id'])) {
            $seller_category = $fn->get_data($columns = ["categories"], "slug='" . $seller_slug . "'", 'seller');
            $category = $seller_category[0]['categories'];
            $data = explode(",", $category);
            $search = (in_array($category_id, $data, TRUE)) ? 1 : 2;
            if ($search == 2) {
                $response['error'] = true;
                $response['message'] = "No Products found!";
                print_r(json_encode($response));
                return false;
            } else {
                $where .=  " AND s.`slug` = '$seller_slug' AND p.`category_id` IN (" . $category_id . ") ";
            }
        } else {
            $seller_category = $fn->get_data($columns = ["categories"], "slug='" . $seller_slug . "'", 'seller');
            $category = $seller_category[0]['categories'];
            $where .= " AND s.`slug` =  '$seller_slug' AND p.category_id IN (" . $category . " )";
        }
    }
    if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $slug = $db->escapeString($fn->xss_clean($_POST['slug']));
        $where .= " AND p.`slug` =  '$slug' ";
    }

    if (isset($_POST['seller_id']) && !empty($_POST['seller_id']) && is_numeric($_POST['seller_id'])) {
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
                $where .=  " AND p.`seller_id` = " . $seller_id . " AND p.`category_id` IN (" . $category_id . ") ";
            }
        } else {
            $seller_category = $fn->get_data($columns = ["categories"], "id=" . $seller_id, 'seller');
            $category = $seller_category[0]['categories'];
            $where .=  " AND p.`seller_id` = " . $seller_id . " AND p.category_id IN (" . $category . " )";
        }
    }

    if (isset($_POST['category_id']) && !empty($_POST['category_id']) && is_numeric($_POST['category_id'])) {
        if (!isset($_POST['seller_id']) && empty($_POST['seller_id']) && !isset($_POST['seller_slug']) && empty($_POST['seller_slug'])) {
            $where .= " AND p.`category_id`=" . $category_id . " and subcategory_id=0";
        }
    }

    if (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != "" && is_numeric($_POST['subcategory_id'])) {
        $where .=  " AND p.`subcategory_id`=" . $subcategory_id;
    }
  

    if($shipping_type=='local'){
        if (isset($_POST['pincode']) && !empty($_POST['pincode'])) {
            $pincode_id = $is_pincode[0]['id'];
            $where .=  " AND ((p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes) OR (p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes)) or p.type='all'))";
        }
        if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") {
            $pincode_id = $_POST['pincode_id'];
            $where .=  " AND ((p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes) OR (p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes)) OR p.type='all'))";
        }
    }


    if ($shipping_type == 'standard') {
        $sql = "SELECT count(p.id) as total FROM `products` p LEFT JOIN `seller` s ON s.id=p.seller_id LEFT JOIN `category` c ON c.id=p.category_id WHERE p.is_approved = 1 AND p.status = 1 AND p.standard_shipping=1 AND s.status = 1 AND c.status = 1 AND (s.categories like CONCAT('%', p.category_id ,'%')) $where ";
    } else {
        $sql = "SELECT count(p.id) as total FROM `products` p LEFT JOIN `seller` s ON s.id=p.seller_id LEFT JOIN `category` c ON c.id=p.category_id WHERE p.is_approved = 1 AND p.status = 1 AND p.standard_shipping=0 AND s.status = 1 AND c.status = 1 AND (s.categories like CONCAT('%', p.category_id ,'%')) $where ";
    }
    $db->sql($sql);
    $total = $db->getResult();

    if ($shipping_type == "standard") {
        $sql = "SELECT p.*,p.type as d_type, s.store_name as seller_name,s.slug as seller_slug,s.status as seller_status,(SELECT " . $price . " FROM product_variant pv WHERE pv.product_id=p.id) as price FROM `products` p JOIN `seller` s ON s.id=p.seller_id LEFT JOIN `category` c ON c.id=p.category_id WHERE p.is_approved = 1 AND p.standard_shipping=1 AND p.status = 1 AND s.status = 1 AND c.status = 1 AND (s.categories like CONCAT('%', p.category_id ,'%')) $where ORDER BY $sort $order LIMIT $offset,$limit ";
    } else {
      $sql = "SELECT p.*,p.type as d_type, s.store_name as seller_name,s.slug as seller_slug,s.status as seller_status,(SELECT " . $price . " FROM product_variant pv WHERE pv.product_id=p.id) as price FROM `products` p JOIN `seller` s ON s.id=p.seller_id LEFT JOIN `category` c ON c.id=p.category_id WHERE p.is_approved = 1 AND p.standard_shipping=0 AND p.status = 1 AND s.status = 1 AND c.status = 1 AND (s.categories like CONCAT('%', p.category_id ,'%')) $where ORDER BY $sort $order LIMIT $offset,$limit ";
    }
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();

    $product = array();
    $i = 0;
    $sql = "SELECT id FROM cart limit 1";
    $db->sql($sql);
    $res_cart = $db->getResult();


    foreach ($res as $row) {
        $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ORDER BY `pv`.`serve_for` ASC";
        $db->sql($sql);
        $variants = $db->getResult();
        if (empty($variants)) {
            continue;
        }

        if (!empty($pincode) || $pincode != "") {
            $pincodes = $row['pincodes'];
            $sql = "SELECT pincode FROM `pincodes` where id IN($pincodes)";
            $db->sql($sql);
            $res_pincodes = $db->getResult();
            $pincodes = implode(",", array_column($res_pincodes, "pincode"));
            $pincodes = explode(",", $pincodes);
            if ($row['d_type'] == "all") {
                $row['is_item_deliverable'] = true;
            } else if ($row['d_type'] == "included") {
                if (in_array($pincode, $pincodes)) {
                    $row['is_item_deliverable']  = true;
                } else {
                    $row['is_item_deliverable']  = false;
                }
            } else if ($row['d_type'] == "excluded") {
                if (in_array($pincode, $pincodes)) {
                    $row['is_item_deliverable']  = false;
                } else {
                    $row['is_item_deliverable']  = true;
                }
            }
        } else {
            $row['is_item_deliverable'] = false;
        }

        unset($row['type']);
        $row['seller_name'] = !empty($row['seller_name']) ? $row['seller_name'] : "";
        $row['pincodes'] = (isset($row['pincodes']) == null)  ? "" : $row['pincodes'];
        $row['is_approved'] = (isset($row['is_approved']) == null)  ? "" : $row['is_approved'];
        $row['seller_id'] = (isset($row['seller_id']) == null)  ? "" : $row['seller_id'];
        $row['pickup_location'] = (isset($row['pickup_location']) == null)  ? "" : $row['pickup_location'];
        $row['pickup_postcode'] = (isset($row['pickup_postcode']) == null)  ? "" : $row['pickup_postcode'];


        $row['other_images'] = json_decode($row['other_images'], 1);
        $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
        for ($j = 0; $j < count($row['other_images']); $j++) {
            $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
        }

        $row['image'] = DOMAIN_URL . $row['image'];
        if ($row['tax_id'] == 0) {
            $row['tax_title'] = "";
            $row['tax_percentage'] = "0";
        } else {
            $t_id = $row['tax_id'];
            $sql_tax = "SELECT * from taxes where id= $t_id";
            $db->sql($sql_tax);
            $res_tax1 = $db->getResult();
            foreach ($res_tax1 as $tax1) {
                $row['tax_title'] = (!empty($tax1['title'])) ? $tax1['title'] : "";
                $row['tax_percentage'] =  (!empty($tax1['percentage'])) ? $tax1['percentage'] : "0";
            }
        }
        if (!empty($user_id)) {
            $sql = "SELECT id from favorites where product_id = " . $row['id'] . " AND user_id = " . $user_id;
            $db->sql($sql);
            $favorite = $db->getResult();
            if (!empty($favorite)) {
                $row['is_favorite'] = true;
            } else {
                $row['is_favorite'] = false;
            }
        } else {
            $row['is_favorite'] = false;
        }

        $product[$i] = $row;

        for ($k = 0; $k < count($variants); $k++) {

            $variants[$k]['images'] = json_decode($variants[$k]['images'], 1);
            $variants[$k]['images'] = (empty($variants[$k]['images'])) ? array() : $variants[$k]['images'];
            for ($j = 0; $j < count($variants[$k]['images']); $j++) {
                $variants[$k]['images'][$j] = !empty(DOMAIN_URL . $variants[$k]['images'][$j]) ? DOMAIN_URL . $variants[$k]['images'][$j] : "";
            }
            // if ($variants[$k]['stock'] <= 0) {
            //     $variants[$k]['serve_for'] = 'Sold Out';
            // } else {
            //     $variants[$k]['serve_for'] = $variants[$k]['serve_for'];
            // }


            if (!empty($user_id)) {
                $sql = "SELECT qty as cart_count FROM cart where product_variant_id= " . $variants[$k]['id'] . " AND user_id=" . $user_id;
                $db->sql($sql);
                $res = $db->getResult();
                if (!empty($res)) {
                    foreach ($res as $row1) {
                        $variants[$k]['cart_count'] = $row1['cart_count'];
                    }
                } else {
                    $variants[$k]['cart_count'] = "0";
                }
            } else {
                $variants[$k]['cart_count'] = "0";
            }
        }

        $product[$i]['variants'] = $variants;
        $i++;
    }

    $product = mb_convert_encoding($product, "UTF-8", "UTF-8");
    if (!empty($product)) {
        $response['error'] = false;
        $response['message'] = "Products retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $product;
    } else {
        $response['error'] = true;
        $response['message'] = "No products available.";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_products_offline']) && $_POST['get_products_offline'] == 1 && isset($_POST['product_ids']) && !empty($_POST['product_ids'])) {
    /* 
    2.get_products_offline
        accesskey:90336
        get_products_offline:1
        product_ids:214,215 
        slug:mixed-fruit-1        // {optional}
    */

    $product_ids = $db->escapeString($fn->xss_clean($_POST['product_ids']));
    $where = "";
    if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $slug = $db->escapeString($fn->xss_clean($_POST['slug']));
        $where = " AND p.`slug` =  '$slug' ";
    }
    $sql = "SELECT  count(p.id) as total FROM `products` p JOIN `seller`s ON s.id=p.seller_id WHERE p.is_approved = 1 AND p.status = 1 AND s.status = 1 AND p.id IN ($product_ids) " . $where;
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT p.*,s.name as seller_name,s.status as seller_status FROM `products` p JOIN `seller`s ON s.id=p.seller_id WHERE p.is_approved = 1 AND p.status = 1 AND s.status = 1 AND p.id IN ($product_ids)" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    $product = array();
    $i = 0;

    foreach ($res as $row) {
        $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ORDER BY serve_for ASC";
        $db->sql($sql);
        $variants = $db->getResult();
        if (empty($variants)) {
            continue;
        }
        $row['type'] = (isset($row['type']) == null)  ? "" : $row['type'];
        $row['pincodes'] = (isset($row['pincodes']) == null)  ? "" : $row['pincodes'];
        $row['is_approved'] = (isset($row['is_approved']) == null)  ? "" : $row['is_approved'];
        $row['seller_id'] = (isset($row['seller_id']) == null)  ? "" : $row['seller_id'];
        $row['seller_name'] = (isset($row['seller_name']) == null)  ? "" : $row['seller_name'];

        $row['other_images'] = json_decode($row['other_images'], 1);
        $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];

        for ($j = 0; $j < count($row['other_images']); $j++) {
            $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
        }

        if ($row['tax_id'] == 0 || $row['tax_id'] == "") {
            $row['tax_title'] = "";
            $row['tax_percentage'] = "0";
        } else {
            $t_id = $row['tax_id'];
            $sql_tax = "SELECT * from taxes where id= $t_id";
            $db->sql($sql_tax);
            $res_tax = $db->getResult();
            foreach ($res_tax as $tax) {
                $row['tax_title'] = $tax['title'];
                $row['tax_percentage'] = $tax['percentage'];
            }
        }

        for ($k = 0; $k < count($variants); $k++) {
            $variants[$k]['images'] = json_decode($variants[$k]['images'], 1);
            $variants[$k]['images'] = (empty($variants[$k]['images'])) ? array() : $variants[$k]['images'];
            for ($j = 0; $j < count($variants[$k]['images']); $j++) {
                $variants[$k]['images'][$j] = !empty(DOMAIN_URL . $variants[$k]['images'][$j]) ? DOMAIN_URL . $variants[$k]['images'][$j] : "";
            }

            // if ($variants[$k]['stock'] <= 0) {
            //     $variants[$k]['serve_for'] = 'Sold Out';
            // } else {
            //     $variants[$k]['serve_for'] = 'Available';
            // }
            $variants[$k]['cart_count'] = "0";
        }
        $row['is_favorite'] = false;

        $row['image'] = DOMAIN_URL . $row['image'];
        $product[$i] = $row;
        $product[$i]['variants'] = $variants;
        $i++;
    }

    $product = mb_convert_encoding($product, "UTF-8", "UTF-8");
    if (!empty($product)) {
        $response['error'] = false;
        $response['message'] = "Products retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $product;
    } else {
        $response['error'] = true;
        $response['message'] = "No products available";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_variants_offline']) && $_POST['get_variants_offline'] == 1 && isset($_POST['variant_ids']) && !empty($_POST['variant_ids'])) {
    /* 
    3.get_variants_offline
        accesskey:90336
        get_variants_offline:1
        variant_ids:55,56
        pincode_id:1    //{optional}
        pincode:1    //{optional}
    */
    $variant_ids = $db->escapeString($fn->xss_clean($_POST['variant_ids']));
    $pincode_id  = (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['pincode_id'])) : "";
    $pincode  = (isset($_POST['pincode']) && !empty($_POST['pincode'])) ? $db->escapeString($fn->xss_clean_array($_POST['pincode'])) : "";

    $where = "";
    if (isset($_POST['pincode']) && !empty($_POST['pincode'])) {
        $pincode = $fn->get_data($column = ['pincode', 'id'], "pincode=" . $pincode, "pincodes");
        if (empty($pincode)) {
            $response['error'] = true;
            $response['message'] = "Invalid Pincode passed.";
            print_r(json_encode($response));
            return false;
            exit();
        }
        $pincode_id = $pincode[0]['id'];
    }
    if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $slug = $db->escapeString($fn->xss_clean($_POST['slug']));
        $where = " AND p.`slug` = '$slug' ";
    }

    $sql = "SELECT  count(pv.id) as total FROM product_variant pv JOIN products p ON p.id=pv.product_id JOIN seller s ON s.id=p.seller_id where pv.id IN ($variant_ids) and p.is_approved = 1 AND p.status = 1 AND s.status = 1 " . $where;
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT pv.id as product_variant_id,pv.*,p.tax_id FROM product_variant pv JOIN products p ON p.id=pv.product_id where pv.id IN ($variant_ids)" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    $i = 0;
    $j = 0;


    foreach ($res as $row) {

        unset($res[$i]['images']);
        $sql = "select pv.*,p.*,s.name as seller_name,p.type as d_type,s.status as seller_status,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit,(Select short_code from unit su where su.id=pv.stock_unit_id) as stock_unit_name from product_variant pv left join products p on p.id=pv.product_id JOIN seller s ON s.id=p.seller_id where pv.id=" . $row['product_variant_id'];
        // $sql = "select pv.*,p.*,s.name as seller_name,p.type as d_type,s.status as seller_status,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id JOIN seller s ON s.id=p.seller_id where pv.id=" . $row['product_variant_id'];
        $db->sql($sql);

        $res[$i]['item'] = $db->getResult();

        for ($k = 0; $k < count($res[$i]['item']); $k++) {
            if (!empty($pincode_id) || $pincode_id != "") {
                $pincodes = ($res[$i]['item'][$k]['d_type'] == "all") ? "" : $res[$i]['item'][$k]['pincodes'];
                // print_r($pincodes);
                $pincodes = explode(',', $pincodes);
                if ($res[$i]['item'][$k]['d_type'] == "all") {
                    $res[$i]['item'][$k]['is_item_deliverable'] = true;
                } else if ($res[$i]['item'][$k]['d_type'] == "included") {
                    if (in_array($pincode_id, $pincodes)) {
                        $res[$i]['item'][$k]['is_item_deliverable']  = true;
                    } else {
                        $res[$i]['item'][$k]['is_item_deliverable']  = false;
                    }
                } else if ($res[$i]['item'][$k]['d_type'] == "excluded") {

                    if (in_array($pincode_id, $pincodes)) {
                        $res[$i]['item'][$k]['is_item_deliverable']  = false;
                    } else {
                        $res[$i]['item'][$k]['is_item_deliverable']  = true;
                    }
                }
            } else {
                $res[$i]['item'][$k]['is_item_deliverable'] = false;
            }
            $variant_images = str_replace("'", '"', $res[$i]['item'][$k]['images']);
            $res[$i]['item'][$k]['images'] = json_decode($variant_images, 1);
            $res[$i]['item'][$k]['images'] = (empty($res[$i]['item'][$k]['images'])) ? array() : $res[$i]['item'][$k]['images'];

            for ($j = 0; $j < count($res[$i]['item'][$k]['images']); $j++) {
                $res[$i]['item'][$k]['images'][$j] = !empty(DOMAIN_URL . $res[$i]['item'][$k]['images'][$j]) ? DOMAIN_URL . $res[$i]['item'][$k]['images'][$j] : "";
            }

            $res[$i]['item'][$k]['cart_count'] = "0";
            $res[$i]['item'][$k]['other_images'] = json_decode($res[$i]['item'][$k]['other_images']);
            $res[$i]['item'][$k]['other_images'] = empty($res[$i]['item'][$k]['other_images']) ? array() : $res[$i]['item'][$k]['other_images'];
            for ($l = 0; $l < count($res[$i]['item'][$k]['other_images']); $l++) {
                $other_images = DOMAIN_URL . $res[$i]['item'][$k]['other_images'][$l];
                $res[$i]['item'][$k]['other_images'][$l] = $other_images;
            }

            if ($row['tax_id'] == 0) {
                $res[$i]['item'][$k]['tax_title'] = "";
                $res[$i]['item'][$k]['tax_percentage'] = "0";
            } else {
                $t_id = $row['tax_id'];
                $sql_tax = "SELECT * from taxes where id= $t_id";
                $db->sql($sql_tax);
                $res_tax = $db->getResult();
                foreach ($res_tax as $tax) {
                    $res[$i]['item'][$k]['tax_title'] = $tax['title'];
                    $res[$i]['item'][$k]['tax_percentage'] = $tax['percentage'];
                }
            }
        }

        for ($j = 0; $j < count($res[$i]['item']); $j++) {
            $res[$i]['item'][$j]['image'] = !empty($res[$i]['item'][$j]['image']) ? DOMAIN_URL . $res[$i]['item'][$j]['image'] : "";
        }
        $i++;
    }
    $res = mb_convert_encoding($res, "UTF-8", "UTF-8");
    if (!empty($res)) {
        $response['error'] = false;
        $response['message'] = "Product Varients retrived successfully!";
        $response['total'] = $total[0]['total'];
        $response['data'] = array_values($res);
    } else {
        $response['error'] = true;
        $response['message'] = "No item(s) found!";
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_similar_products']) && $_POST['get_similar_products'] == 1) {
    /*  
    4. get_similar_products
        accesskey:90336
        get_similar_products:1
        product_id:211
        category_id:28
        limit:6         // {optional}
        user_id:369     // {optional}
        pincode_id:1 {optional}
    */

    if (empty($_POST['product_id']) || empty($_POST['category_id'])) {
        $response['error'] = true;
        $response['message'] = "Missing arguments!";
        print_r(json_encode($response));
        return false;
    }

    $product_id = $db->escapeString($fn->xss_clean($_POST['product_id']));
    $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
    $user_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $row1 = array();

    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 6;
    $offset = 0;
    $order =  "RAND()";
    $where = '';
    if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") {
        $pincode = $_POST['pincode_id'];
        $where .=  " AND (p.type='included' and FIND_IN_SET('$pincode', p.pincodes) OR (p.type='excluded' and NOT FIND_IN_SET('$pincode', p.pincodes)) or p.type='all')";
    }
    if ($shipping_type == "standard") {
        $where .= ' AND p.standard_shipping=1';
    } else {
        $where .= ' AND p.standard_shipping=0';
    }

    $sql = "SELECT count(p.id) as total FROM `products` p JOIN `seller`s ON s.id=p.seller_id where p.id != $product_id AND p.category_id = $category_id AND p.is_approved = 1 AND p.status = 1 and s.status = 1  $where ORDER BY $order LIMIT $offset,$limit";
    $db->sql($sql);
    $total1 = $db->getResult();

    $sql = "SELECT p.*,s.name as seller_name,s.status as seller_status,(SELECT MIN(pv.price) FROM product_variant pv WHERE pv.product_id=p.id) as price FROM products p  JOIN seller s on s.id=p.seller_id where p.id != $product_id and p.status=1  and p.is_approved = 1 and  s.status = 1 and category_id = $category_id $where ORDER BY $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        foreach ($res as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['seller_id'] = $row['seller_id'];
            $tempRow['seller_name'] = $row['seller_name'];
            $tempRow['tax_id'] = $row['tax_id'];
            $tempRow['row_order'] = $row['row_order'];
            $tempRow['name'] = $row['name'];
            $tempRow['slug'] = $row['slug'];
            $tempRow['category_id'] = $row['category_id'];
            $tempRow['subcategory_id'] = $row['subcategory_id'];
            $tempRow['indicator'] = $row['indicator'];
            $tempRow['manufacturer'] = $row['manufacturer'];
            $tempRow['total_allowed_quantity'] = $row['total_allowed_quantity'];
            $tempRow['made_in'] = $row['made_in'];
            $tempRow['return_status'] = $row['return_status'];
            $tempRow['cancelable_status'] = $row['cancelable_status'];
            $tempRow['till_status'] = $row['till_status'];
            $tempRow['seller_status'] = $row['seller_status'];
            $tempRow['date_added'] = $row['date_added'];
            $tempRow['price'] = $row['price'];
            $tempRow['date_added'] = $row['date_added'];
            $tempRow['type'] = $row['type'];
            $tempRow['pincodes'] = $row['pincodes'];
            $tempRow['is_approved'] = $row['is_approved'];
            $tempRow['return_days'] = $row['return_days'];
            $tempRow['image'] = (!empty($row['image'])) ? DOMAIN_URL . '' . $row['image'] : '';

            if (!empty($row['other_images']) && $row['other_images'] != "") {
                $row['other_images'] = json_decode($row['other_images'], 1);
                for ($j = 0; $j < count($row['other_images']); $j++) {
                    $tempRow['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
                }
            } else {
                $tempRow['other_images'] = array();
            }

            if ($row['tax_id'] == 0) {
                $tempRow['tax_title'] = "";
                $tempRow['tax_percentage'] = "0";
            } else {
                $t_id = $row['tax_id'];
                $sql_tax = "SELECT * from taxes where id= $t_id";
                $db->sql($sql_tax);
                $res_tax = $db->getResult();
                foreach ($res_tax as $tax) {
                    $tempRow['tax_title'] = $tax['title'];
                    $tempRow['tax_percentage'] = $tax['percentage'];
                }
            }

            if (!empty($user_id)) {
                $sql = "SELECT id from favorites where product_id = " . $row['id'] . " AND user_id = " . $user_id;
                $db->sql($sql);
                $result = $db->getResult();
                if (!empty($result)) {
                    $tempRow['is_favorite'] = true;
                } else {
                    $tempRow['is_favorite'] = false;
                }
            } else {
                $tempRow['is_favorite'] = false;
            }

            $tempRow['description'] = $row['description'];
            $tempRow['status'] = $row['status'];

            $sql1 = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ORDER BY serve_for ASC";
            $db->sql($sql1);
            $variants = $db->getResult();
            if (empty($variants)) {
                continue;
            }
            for ($k = 0; $k < count($variants); $k++) {
                $variants[$k]['images'] = json_decode($variants[$k]['images'], 1);
                $variants[$k]['images'] = (empty($variants[$k]['images'])) ? array() : $variants[$k]['images'];
                for ($j = 0; $j < count($variants[$k]['images']); $j++) {
                    $variants[$k]['images'][$j] = !empty(DOMAIN_URL . $variants[$k]['images'][$j]) ? DOMAIN_URL . $variants[$k]['images'][$j] : "";
                }

                if (!empty($user_id)) {
                    $sql = "SELECT qty as cart_count FROM cart where product_variant_id= " . $variants[$k]['id'] . " AND user_id=" . $user_id;
                    $db->sql($sql);
                    $res = $db->getResult();
                    if (!empty($res)) {
                        foreach ($res as $row1) {
                            $variants[$k]['cart_count'] = $row1['cart_count'];
                        }
                    } else {
                        $variants[$k]['cart_count'] = "0";
                    }
                } else {
                    $variants[$k]['cart_count'] = "0";
                }
            }
            $tempRow['variants'] = $variants;
            $rows[] = $tempRow;
        }

        $rows = mb_convert_encoding($rows, "UTF-8", "UTF-8");
        $response['error'] = false;
        $response['message'] = 'Product retrived successfully!';
        $response['total'] = $total1[0]['total'];
        $response['data'] = $rows;
    } else {
        $response['error'] = true;
        $response['message'] = 'Data not Found!';
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['type']) && $_POST['type'] == 'products_search') {
    /*  
    5. products_search
        accesskey:90336
	    type:products_search
	    search:Himalaya Baby Powder
        pincode_id:1 {optional}
    */

    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

    $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : "id";
    $order = (isset($_POST['order']) && !empty($_POST['order'])) ? $db->escapeString($fn->xss_clean($_POST['order'])) : "DESC";

    $where = '';
    if (isset($_POST['search']) && $_POST['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $search = str_replace(' ', '%', $search);
        $where = " AND (p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR p.`image` like '%" . $search . "%' OR p.`subcategory_id` like '%" . $search . "%' OR p.`slug` like '%" . $search . "%' OR p.`description` like '%" . $search . "%')";
    }
    if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") {
        $pincode_id = $_POST['pincode_id'];
        $where .=  " AND (p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes) OR (p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes)) or p.type='all')";
    }
    $user_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $sql = "SELECT COUNT(p.id) as total FROM `products`p JOIN `seller` s ON s.id=p.seller_id WHERE p.is_approved = 1 AND p.status = 1 AND s.status = 1 " . $where;
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    $sql = "SELECT p.*,s.name as seller_name,s.status as seller_status FROM `products`p JOIN seller s ON s.id=p.seller_id WHERE p.is_approved = 1 AND p.status = 1 AND s.status = 1 " . $where;
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    $product = array();
    $i = 0;

    foreach ($res as $row) {
        $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ORDER BY serve_for ASC";
        $db->sql($sql);
        $variants = $db->getResult();
        if (empty($variants)) {
            continue;
        }
        if (!empty($user_id)) {
            $sql = "SELECT id from favorites where product_id = " . $row['id'] . " AND user_id = " . $user_id;
            $db->sql($sql);
            $result = $db->getResult();
            if (!empty($result)) {
                $row['is_favorite'] = true;
            } else {
                $row['is_favorite'] = false;
            }
        } else {
            $row['is_favorite'] = false;
        }

        $row['type'] = (isset($row['type']) == null)  ? "" : $row['type'];
        $row['pincodes'] = (isset($row['pincodes']) == null)  ? "" : $row['pincodes'];
        $row['is_approved'] = (isset($row['is_approved']) == null)  ? "" : $row['is_approved'];
        $row['seller_id'] = (isset($row['seller_id']) == null)  ? "" : $row['seller_id'];

        $row['other_images'] = json_decode($row['other_images'], 1);
        $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
        for ($j = 0; $j < count($row['other_images']); $j++) {
            $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
        }
        if ($row['tax_id'] == 0) {
            $row['tax_title'] = "";
            $row['tax_percentage'] = "0";
        } else {
            $t_id = $row['tax_id'];
            $sql_tax = "SELECT * from taxes where id= $t_id";
            $db->sql($sql_tax);
            $res_tax = $db->getResult();
            foreach ($res_tax as $tax) {
                $row['tax_title'] = $tax['title'];
                $row['tax_percentage'] = $tax['percentage'];
            }
        }
        $row['image'] = DOMAIN_URL . $row['image'];
        $product[$i] = $row;
        for ($k = 0; $k < count($variants); $k++) {
            $variants[$k]['images'] = json_decode($variants[$k]['images'], 1);
            $variants[$k]['images'] = (empty($variants[$k]['images'])) ? array() : $variants[$k]['images'];
            for ($j = 0; $j < count($variants[$k]['images']); $j++) {
                $variants[$k]['images'][$j] = !empty(DOMAIN_URL . $variants[$k]['images'][$j]) ? DOMAIN_URL . $variants[$k]['images'][$j] : "";
            }

            if (!empty($user_id)) {
                $sql = "SELECT qty as cart_count FROM cart where product_variant_id= " . $variants[$k]['id'] . " AND user_id=" . $user_id;
                $db->sql($sql);
                $res = $db->getResult();
                if (!empty($res)) {
                    foreach ($res as $row1) {
                        $variants[$k]['cart_count'] = $row1['cart_count'];
                    }
                } else {
                    $variants[$k]['cart_count'] = "0";
                }
            } else {
                $variants[$k]['cart_count'] = "0";
            }
        }
        $product[$i]['variants'] = $variants;
        $i++;
    }

    $product = mb_convert_encoding($product, "UTF-8", "UTF-8");
    if (empty($product)) {
        $response['error'] = true;
        $response['message'] = 'No Products available';
    } else {
        $response['error'] = false;
        $response['message'] = 'Products Retrived successfuly!';
        $response['total'] = $total;
        $response['data'] = array_values($product);
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_all_products_name']) && $_POST['get_all_products_name'] == 1) {
    /*  
    5. get_all_products_name
        accesskey:90336
        get_all_products_name:1
        pincode:1 {optional}
    */


    $where = "";
    if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") {
        $pincode_id = $_POST['pincode_id'];
        $where .=  " AND (p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes) OR (p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes)) or p.type='all')";
    }

    if($shipping_type=='standard'){
        $where.=" AND p.standard_shipping=1";
    }else{
        $where.= " AND p.standard_shipping=0";
    }

    $sql = "SELECT p.name FROM `products` p JOIN seller s on s.id = p.seller_id where p.is_approved = 1 AND p.status = 1 AND s.status = 1" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    $rows = $tempRow = $blog_array = $blog_array1 = array();
    foreach ($res as $row) {
        $tempRow['name'] = $row['name'];
        $rows[] = $tempRow;
    }
    $names = array_column($rows, 'name');

    $pr_names = implode("''", $names);
    $pr_name = explode("''", $pr_names);

    $pr_name = mb_convert_encoding($pr_name, "UTF-8", "UTF-8");

    $response['error'] = false;
    $response['data'] = $pr_name;
    print_r(json_encode($response));
    return false;
}


if (isset($_POST['check_deliverability']) && $_POST['check_deliverability'] == 1) {

    /*  
    6. check_deliverability
        accesskey:90336
        check_deliverability:1
        pincode_id:1 or pincode:370465
        product_variant_id:210
        slug:test //{optional}
        


    */
    /* pincode code commented because new i write new code for shiprocket */
    // if (isset($_POST['pincode']) && !empty($_POST['pincode'])) {
    //     $res = $fn->get_data($columns = ['id'], "pincode='" . $_POST['pincode'] . "'", 'pincodes');
    //     if (empty($res)) {
    //         $response['error'] = true;
    //         $response['message'] = "Invalid pincode passed.";
    //         print_r(json_encode($response));
    //         return false;
    //     }else{
    //         $_POST['pincode_id'] = $res[0]['id'];
    //     }
    //     $pincode=$_POST['pincode'];
    // }


    if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $res = $fn->get_data($x = ['id'], "slug='" . $_POST['slug'] . "'", 'products');
        $_POST['product_id'] = $res[0]['id'];
        if (empty($_POST['product_id'])) {
            $response['error'] = true;
            $response['message'] = "No such product exists.";
            print_r(json_encode($response));
            return false;
        }
    }
    if (isset($_POST['pincode_id']) && !empty($_POST['pincode_id']) or isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) {
        //  select product using product id
        $sql = "SELECT pv.weight,p.* from product_variant pv left join products p on pv.product_id=p.id where pv.id=" . $_POST['product_variant_id'];
        $db->sql($sql);
        $result = $db->getResult();


        if (empty($result)) {
            $response['error'] = true;
            $response['message'] = "Invalid product variant id passed.";
            print_r(json_encode($response));
            return false;
        }
        // if standard shippiing (shiprocket) enable means 1 so will check from shiprocket other wise local
        if ($result[0]['standard_shipping'] == 1) {
            if (isset($_POST['pincode']) && !empty($_POST['pincode'])) {
                $pincode = $_POST['pincode'];
            }
            if ((isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) or (isset($_POST['pincode']) && !empty($_POST['pincode']))) {

                $sql = "SELECT pin_code from pickup_locations where pickup_location='" . $result[0]['pickup_location'] . "'";
                $db->sql($sql);
                $pickup_location = $db->getResult();

                if (empty($pickup_location)) {
                $res['error']=true;
                $res['message']="Sorry pickup location not added in this product";
                print_r(json_encode($res));
                return false;
                }

                $data = array('pickup_location' => $pickup_location[0]['pin_code'], 'weight' => $result[0]['weight']);
                $data_with_cod = $data_without_cod = $data;
                $data_with_cod['cod'] = 1;
                $data_without_cod['cod'] = 0;

                if (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) {
                    $pincode = $fn->get_data($columns = ['id,pincode'], "id='" . $_POST['pincode_id'] . "'", 'pincodes');
                    if (empty($pincode)) {
                        $response['error'] = true;
                        $response['message'] = "Data Not Found on this pincode id";
                        print_r(json_encode($response));
                        return false;
                    }

                    $data_with_cod['delivery_pincode'] = $data_without_cod['delivery_pincode'] = $pincode[0]['pincode'];
                } else {
                    $data_with_cod['delivery_pincode'] = $data_without_cod['delivery_pincode'] = $pincode;
                }

                $shiprocket_data_with_cod = $shiprocket->check_serviceability($data_with_cod);
                $shiprocket_data_without_cod = $shiprocket->check_serviceability($data_without_cod);

                // print_r($shiprocket_data_with_cod);
                // print_r($shiprocket_data_without_cod);


                if ($shiprocket_data_with_cod['status'] == 200) {
                    $delivery_places_with_cod = $fn->shiprocket_recomended_data($shiprocket_data_with_cod);
                    $delivery_places_without_cod = $fn->shiprocket_recomended_data($shiprocket_data_without_cod);

                    // print_r($delivery_places_with_cod);
                    // print_r($delivery_places_without_cod);


                    $response['error'] = false;
                    $response['message'] = 'Shipping Data Retrive Successfully';
                    $response['currency'] = $currency;
                    $response['delivery_charge_with_cod'] = number_format($delivery_places_with_cod['rate']);
                    $response['delivery_charge_without_cod'] = number_format($delivery_places_without_cod['rate']);
                    $response['estimated_date'] = $delivery_places_with_cod['etd'];
                } else {
                    $response['error'] = true;
                    $response['message'] = $shiprocket_data_with_cod['message'];
                }
                print_r(json_encode($response));
                return false;
            } else {
                $response['error'] = true;
                $response['message'] = 'Pincode id or Pincode required';
            }
        } else {
            if (isset($_POST['pincode']) && !empty($_POST['pincode'])) {
                $res = $fn->get_data($columns = ['id'], "pincode='" . $_POST['pincode'] . "'", 'pincodes');
                if (empty($res)) {
                    $response['error'] = true;
                    $response['message'] = "Invalid pincode passed.";
                    print_r(json_encode($response));
                    return false;
                }
                $_POST['pincode_id'] = $res[0]['id'];
            }
            if (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) {
                $pincode = $fn->get_data($columns = ['id'], "id='" . $_POST['pincode_id'] . "'", 'pincodes');
                if (isset($pincode[0]['id']) && !empty($pincode[0]['id'])) {
                    if (isset($result[0]) && !empty($result[0])) {
                        $pincode_ids = explode(',', $result[0]['pincodes']);
                        if ($result[0]['type'] == "all") {
                            $response['error'] = false;
                        } else if ($result[0]['type'] == "included") {
                            if (in_array($_POST['pincode_id'], $pincode_ids)) {
                                $response['error'] = false;
                            } else {
                                $response['error'] = true;
                            }
                        } else if ($result[0]['type'] == "excluded") {
                            if (!in_array($_POST['pincode_id'], $pincode_ids)) {
                                $response['error'] = false;
                            } else {
                                $response['error'] = true;
                            }
                        }
                        $response['error'] = false;
                        $response['message'] = "Deliverability checked successfully";
                        print_r(json_encode($response));
                        return false;
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Invalid product id passed.";
                        print_r(json_encode($response));
                        return false;
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Invalid pincode id passed.";
                    print_r(json_encode($response));
                    return false;
                }
            } else {
                // $pincode = $fn->get_data($columns = ['id'], "id='" . $_POST['pincode_id'] . "'", 'pincodes');
                if (isset($pincode) && !empty($pincode)) {
                    if (isset($result[0]) && !empty($result[0])) {
                        $pincode_ids = explode(',', $result[0]['pincodes']);
                        if ($result[0]['type'] == "all") {
                            $response['error'] = false;
                        } else if ($result[0]['type'] == "included") {
                            if (in_array($_POST['pincode_id'], $pincode_ids)) {
                                $response['error'] = false;
                            } else {
                                $response['error'] = true;
                            }
                        } else if ($result[0]['type'] == "excluded") {
                            if (!in_array($_POST['pincode_id'], $pincode_ids)) {
                                $response['error'] = false;
                            } else {
                                $response['error'] = true;
                            }
                        }
                        $response['message'] = "Deliverability checked successfully";
                        print_r(json_encode($response));
                        return false;
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Invalid product id passed.";
                        print_r(json_encode($response));
                        return false;
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Invalid pincode id passed.";
                    print_r(json_encode($response));
                    return false;
                }
            }
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Please pass Product id and pincode id for deliverability checking.";
        print_r(json_encode($response));
        return false;
    }
}
