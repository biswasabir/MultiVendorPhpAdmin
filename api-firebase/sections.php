<?php
ini_set("display_errors", "1");
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
session_start();
include '../includes/crud.php';
include '../includes/variables.php';
include_once('verify-token.php');
include_once('../includes/custom-functions.php');

$fn = new custom_functions;
$db = new Database();
$db->connect();
date_default_timezone_set('Asia/Kolkata');
$response = array();

/*
sections.php
    accesskey:90336
*/

if (!isset($_POST['accesskey'])) {
    if (!isset($_GET['accesskey'])) {
        $response['error'] = true;
        $response['message'] = "Access key is invalid or not passed!";
        print_r(json_encode($response));
        return false;
    }
}
$shipping_type = ($fn->get_settings('local_shipping') == 1) ? 'local' : 'standard';
if (isset($_POST['accesskey'])) {
    $accesskey = $db->escapeString($fn->xss_clean($_POST['accesskey']));
} else {
    $accesskey = $db->escapeString($fn->xss_clean($_GET['accesskey']));
}

if ($access_key != $accesskey) {
    $response['error'] = true;
    $response['message'] = "invalid accesskey!";
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['add-section'])) && ($_POST['add-section'] == 1)) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response["message"] =  '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        echo json_encode($response);
        return false;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['featured']['create'] == 0) {
        $response["message"] = "<p class='alert alert-danger'>You have no permission to create featured section.</p>";
        echo json_encode($response);
        return false;
    }

    $title = $db->escapeString($fn->xss_clean($_POST['title']));
    $short_description = $db->escapeString($fn->xss_clean($_POST['short_description']));
    $style = $db->escapeString($fn->xss_clean($_POST['style']));
    $product_type = $db->escapeString($fn->xss_clean($_POST['product_type']));

    $product_ids = isset($_POST['product_ids']) ? $fn->xss_clean_array($_POST['product_ids']) : "";
    $product_id = !empty($product_ids) ? implode(',', $product_ids) : "";

    $category_id = isset($_POST['category_ids']) ? $fn->xss_clean_array($_POST['category_ids']) : "";
    $category_ids = !empty($category_id) ? implode(',', $category_id) : "";

    $product_ids = ($product_type == 'custom_products') ?  $product_id : "";

    $sql = "INSERT INTO `sections` (`title`,`style`,`short_description`,`product_ids`,`category_ids`,`product_type`) VALUES ('$title','$style','$short_description','$product_ids','$category_ids','$product_type')";
    $db->sql($sql);
    $res = $db->getResult();
    $response["message"] = "<p class = 'alert alert-success'>Section created Successfully</p>";
    $sql = "SELECT id FROM sections ORDER BY id DESC";
    $db->sql($sql);
    $res = $db->getResult();
    $response["id"] = $res[0]['id'];
    echo json_encode($response);
}
if ((isset($_POST['edit-section'])) && ($_POST['edit-section'] == 1)) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        $response["message"] =  '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        echo json_encode($response);
        return false;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['featured']['update'] == 0) {
        $response["message"] = "<p class='alert alert-danger'>You have no permission to update featured section.</p>";
        echo json_encode($response);
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['section-id']));
    $style = $db->escapeString($fn->xss_clean($_POST['style']));
    $product_type = $db->escapeString($fn->xss_clean($_POST['product_type']));
    $short_description = $db->escapeString($fn->xss_clean($_POST['short_description']));
    $title = $db->escapeString($fn->xss_clean($_POST['title']));

    $product_ids = isset($_POST['product_ids']) ? $fn->xss_clean_array($_POST['product_ids']) : "";
    $product_id = !empty($product_ids) ? implode(',', $product_ids) : "";

    $category_id = isset($_POST['category_ids']) ? $fn->xss_clean_array($_POST['category_ids']) : "";
    $category_ids = !empty($category_id) ? implode(',', $category_id) : "";

    $product_ids = ($product_type == 'custom_products') ? $product_id : "";

    $sql = "UPDATE `sections` SET `title`='$title', `short_description`='$short_description', `style`='$style', `product_ids` = '$product_ids',`category_ids` = '$category_ids',`product_type` = '$product_type' WHERE `sections`.`id` = " . $id;
    $db->sql($sql);
    $res = $db->getResult();
    $response["message"] = "<p class='alert alert-success'>Section updated Successfully</p>";
    $response["id"] = $id;
    echo json_encode($response);
}
if (isset($_GET['type']) && $_GET['type'] != '' && $_GET['type'] == 'delete-section') {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        return 2;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['featured']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));

    $sql = 'DELETE FROM `sections` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}
if (isset($_POST['get-all-sections']) && $_POST['get-all-sections'] == 1) {
    /* 
    1.get-all-sections
        accesskey:90336
        get-all-sections:1
        section_id:1
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
    $sort1 = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'row_order';

    $sort = '';
    $order = '';

    $product_id = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_id'])) : "";
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $pincode_id = (isset($_POST['pincode']) && !empty($_POST['pincode'])) ? $db->escapeString($fn->xss_clean($_POST['pincode'])) : "";
    $pincode = (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "";

    $category_id = (isset($_POST['category_id']) && !empty($_POST['category_id'])) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : "";
    $subcategory_id = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : "0";
    $seller_id = (isset($_POST['seller_id']) && !empty($_POST['seller_id'])) ? $db->escapeString($fn->xss_clean($_POST['seller_id'])) : "";
    $section_id = (isset($_POST['section_id']) && !empty($_POST['section_id'])) ? $db->escapeString($fn->xss_clean($_POST['section_id'])) : "";

    $where = "";
    $price = 'MIN(price)';
    if (empty($section_id) && $section_id == "") {
        if ($sort1 == 'new') {
            $sort = 'date_added DESC';
            $price = 'MIN(price)';
            $price_sort = ' pv.price ASC';
        } elseif ($sort1 == 'old') {
            $sort = 'date_added ASC';
            $price = 'MIN(price)';
            $price_sort = ' pv.price ASC';
        } elseif ($sort1 == 'high') {
            $sort = ' price DESC';
            $price = 'MAX(price)';
            $price_sort = ' pv.price DESC';
        } elseif ($sort1 == 'low') {
            $sort = ' price ASC';
            $price = 'MIN(price)';
            $price_sort = ' pv.price ASC';
        } else {
            $sort = ' p.row_order ASC';
            $price = 'MIN(price)';
            $price_sort = ' pv.price ASC';
        }
    }
    if ($sort == 'row_order') {
        $order = 'ASC';
    } else {
        if (isset($_POST['order']) && !empty($_POST['order'])) {
            $order = $fn->xss_clean($_POST['order']);
        }
    }

    $is_pincode = $fn->get_data($column = ['pincode', 'id'], "pincode=" . $pincode_id, "pincodes");
    if (empty($is_pincode)) {
        $response['error'] = true;
        $response['message'] = "Invalid Pincode passed.";
        print_r(json_encode($response));
        return false;
        exit();
    }
    if (isset($_POST['pincode']) && !empty($_POST['pincode'])) {
        if (!empty($is_pincode)) {
            $pincode_id = $is_pincode[0]['id'];
        }
    }


    if (isset($_POST['search']) && $_POST['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where .= " AND (p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR s.`name` like '%" . $search . "%' OR p.`subcategory_id` like '%" . $search . "%' OR p.`category_id` like '%" . $search . "%' OR p.`slug` like '%" . $search . "%' OR p.`description` like '%" . $search . "%') ";
    }

    if (isset($_POST['product_id']) && !empty($_POST['product_id']) && is_numeric($_POST['product_id'])) {
        $where .= " AND p.`id` = " . $product_id;
    }

    if (isset($_POST['seller_slug']) && !empty($_POST['seller_slug'])) {
        $seller_slug = $db->escapeString($fn->xss_clean($_POST['seller_slug']));
        $where .= " AND s.`slug` =  '$seller_slug' ";
    }
    if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $slug = $db->escapeString($fn->xss_clean($_POST['slug']));
        $where .= " AND p.`slug` =  '$slug' ";
    }

    if (isset($_POST['seller_id']) && !empty($_POST['seller_id']) && is_numeric($_POST['seller_id'])) {
        $where .= " AND p.`seller_id` = " . $seller_id;
    }

    if (isset($_POST['category_id']) && !empty($_POST['category_id']) && is_numeric($_POST['category_id'])) {
        $where .= " AND p.`category_id`=" . $category_id;
    }
    if (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != "" && is_numeric($_POST['subcategory_id'])) {
        $where .=  " AND p.`subcategory_id`=" . $subcategory_id;
    }
    if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") {
        $pincode_id = $_POST['pincode_id'];
        $where .=  " AND (p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes) OR (p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes)) or p.type='all')";
    }
    if (isset($_POST['pincode']) && $_POST['pincode'] != "") {
        $where .=  " AND (((p.type='included' and FIND_IN_SET($pincode_id, p.pincodes)) OR (p.type='excluded' and NOT FIND_IN_SET($pincode_id, p.pincodes))) or p.type='all')";
    }

    if (isset($_POST['section_id']) && $_POST['section_id'] != "") {
        $section_id = $_POST['section_id'];
        $sql = "select * from `sections` where id = " . $section_id;
        $db->sql($sql);
        $res = $db->getResult();

        $cate_ids = $res[0]['category_ids'];
        $product_ids = $res[0]['product_ids'];

        if ($res[0]['product_type'] == 'all_products') {
            if (empty($res[0]['category_ids'])) {
                $sql = "SELECT id as product_id FROM `products` WHERE status = 1 ORDER BY product_id DESC";
                $sort .= " p.date_added ";
                $order .= " DESC ";
            } else {
                $sql = "SELECT id as product_id FROM `products` WHERE status = 1 AND category_id IN($cate_ids) ORDER BY product_id DESC";
                $sort .= " p.date_added ";
                $order .= " DESC ";
            }
        } elseif ($res[0]['product_type'] == 'new_added_products') {
            if (empty($res[0]['category_ids'])) {
                $sql = "SELECT id as product_id FROM `products` WHERE status = 1 ORDER BY date_added DESC";
                $sort .= " p.id";
                $order .= " DESC ";
            } else {
                $sql = "SELECT id as product_id FROM `products` WHERE status = 1 AND category_id IN($cate_ids) ORDER BY id DESC";
                $sort .= "p.date_added";
                $order .= " DESC ";
            }
        } elseif ($res[0]['product_type'] == 'products_on_sale') {
            if (empty($res[0]['category_ids'])) {
                $sql = "SELECT p.id as product_id FROM `products` p LEFT JOIN product_variant pv ON p.id=pv.product_id WHERE p.status = 1 AND pv.discounted_price > 0 AND pv.price > pv.discounted_price ORDER BY p.id DESC";
                $sort .= " p.id";
                $order .= " DESC ";
            } else {
                $sql = "SELECT p.id as product_id FROM `products` p LEFT JOIN product_variant pv ON p.id=pv.product_id WHERE p.status = 1 AND p.category_id IN($cate_ids) AND pv.discounted_price > 0 AND pv.price > pv.discounted_price ORDER BY p.id DESC";
                $sort .= " p.id";
                $order .= " DESC ";
            }
        } elseif ($res[0]['product_type'] == 'most_selling_products') {
            if (empty($res[0]['category_ids'])) {
                $sql = "SELECT p.id as product_id,oi.product_variant_id, COUNT(oi.product_variant_id) AS total FROM order_items oi LEFT JOIN product_variant pv ON oi.product_variant_id = pv.id LEFT JOIN products p ON pv.product_id = p.id WHERE oi.product_variant_id != 0 AND p.id != '' GROUP BY pv.id,p.id ORDER BY total DESC ";
                $sort .= " p.id";
                $order .= " DESC";
            } else {
                $sql = "SELECT p.id as product_id,oi.product_variant_id, COUNT(oi.product_variant_id) AS total FROM order_items oi LEFT JOIN product_variant pv ON oi.product_variant_id = pv.id LEFT JOIN products p ON pv.product_id = p.id WHERE oi.product_variant_id != 0 AND p.id != '' AND p.category_id IN ($cate_ids) GROUP BY pv.id,p.id ORDER BY total DESC";
                $sort .= " p.id";
                $order .= " DESC";
            }
        } else {
            $product_ids = $res[0]['product_ids'];
            $sort .= " p.id";
            $order .= " DESC";
        }

        if ($res[0]['product_type'] != 'custom_products' && !empty($res[0]['product_type'])) {
            $db->sql($sql);
            $product = $db->getResult();
            $rows = $tempRow = array();
            foreach ($product as $row1) {
                $tempRow['product_id'] = $row1['product_id'];
                $rows[] = $tempRow;
            }
            $pro_id = array_column($rows, 'product_id');
            $product_ids = implode(",", $pro_id);
        }

        if ($shipping_type == "standard") {
            $where .=  " AND p.standard_shipping=1 AND p.id IN  ($product_ids)";
        } else {
            $where .=  " AND p.standard_shipping=0 AND p.id IN  ($product_ids)";
        }
    }
    $sql = "SELECT count(p.id) as total FROM `products` p LEFT JOIN `seller` s ON s.id=p.seller_id WHERE p.is_approved = 1 AND p.status = 1 AND s.status = 1 $where ";

    $db->sql($sql);
    $total = $db->getResult();


    if (empty($section_id) && $section_id == "") {
        if ($shipping_type == "standard") {
            $sql = "SELECT p.*,p.type as d_type, s.store_name as seller_name,s.slug as seller_slug,s.status as seller_status,(SELECT " . $price . " FROM product_variant pv WHERE pv.product_id=p.id) as price FROM `products` p JOIN `seller` s ON s.id=p.seller_id WHERE p.is_approved = 1 AND p.status = 1 AND p.standard_shipping = 1 AND s.status = 1 $where ORDER BY $sort $order LIMIT $offset,$limit ";
        } else {
            $sql = "SELECT p.*,p.type as d_type, s.store_name as seller_name,s.slug as seller_slug,s.status as seller_status,(SELECT " . $price . " FROM product_variant pv WHERE pv.product_id=p.id) as price FROM `products` p JOIN `seller` s ON s.id=p.seller_id WHERE p.is_approved = 1 AND p.status = 1 AND p.standard_shipping=0 AND s.status = 1 $where ORDER BY $sort $order LIMIT $offset,$limit ";
        }
    } else {
        if ($shipping_type == "standard") {
            $sql = "SELECT p.*,p.type as d_type, s.store_name as seller_name,s.slug as seller_slug,s.status as seller_status,(SELECT " . $price . " FROM product_variant pv WHERE pv.product_id=p.id) as price FROM `products` p JOIN `seller` s ON s.id=p.seller_id WHERE p.is_approved = 1 AND p.status = 1 AND p.standard_shipping=1 AND s.status = 1 $where ORDER BY $sort $order LIMIT $offset,$limit ";
        } else {
            $sql = "SELECT p.*,p.type as d_type, s.store_name as seller_name,s.slug as seller_slug,s.status as seller_status,(SELECT " . $price . " FROM product_variant pv WHERE pv.product_id=p.id) as price FROM `products` p JOIN `seller` s ON s.id=p.seller_id WHERE p.is_approved = 1 AND p.status = 1 AND p.standard_shipping=0 AND s.status = 1 $where ORDER BY $sort $order LIMIT $offset,$limit ";
        }
    }
    $db->sql($sql);
    $res = $db->getResult();
    // echo $sql;
    $product = array();
    $i = 0;
    $sql = "SELECT id FROM cart limit 1";
    $db->sql($sql);
    $res_cart = $db->getResult();

    if (!empty($res)) {
        foreach ($res as $row) {
            $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ORDER BY `pv`.`serve_for` ASC";
            $db->sql($sql);
            $variants = $db->getResult();
            if (empty($variants)) {
                continue;
            }
            if (!empty($pincode)) {
                $res_pincode = $fn->get_data($column = ['pincode'], "id=" . $pincode, 'pincodes');
                $row['deliverable_area'] = $res_pincode[0]['pincode'];
            } else {
                $row['deliverable_area'] = "";
            }

            if (!empty($pincode_id) || $pincode_id != "") {
                $pincodes = ($row['d_type'] == "all") ? "" : $row['pincodes'];
                if ($pincodes != "") {
                    $sql = "SELECT pincode FROM `pincodes` where id IN($pincodes)";
                    $db->sql($sql);
                    $res_pincodes = $db->getResult();
                    $pincodes = implode(",", array_column($res_pincodes, "pincode"));
                    $pincodes = explode(",", $pincodes);
                }
                // print_r($pincodes);
                if ($row['d_type'] == "all") {
                    $row['is_item_deliverable'] = true;
                } else if ($row['d_type'] == "included") {
                    if (in_array($pincode_id, $pincodes)) {
                        $row['is_item_deliverable']  = true;
                    } else {
                        $row['is_item_deliverable']  = false;
                    }
                } else if ($row['d_type'] == "excluded") {
                    if (in_array($pincode_id, $pincodes)) {
                        $row['is_item_deliverable']  = true;
                    } else {
                        $row['is_item_deliverable']  = false;
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
            $row['weight'] = (isset($row['weight']) == null)  ? "" : $row['weight'];
            $row['length'] = (isset($row['length']) == null)  ? "" : $row['length'];
            $row['breadth'] = (isset($row['breadth']) == null)  ? "" : $row['breadth'];
            $row['height'] = (isset($row['height']) == null)  ? "" : $row['height'];

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
                if ($variants[$k]['stock'] <= 0) {
                    $variants[$k]['serve_for'] = 'Sold Out';
                } else {
                    $variants[$k]['serve_for'] = $variants[$k]['serve_for'];
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
    }

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

if (isset($_POST['get-all-section'])) {

    /*
    1. get-all-sections 
	    accesskey:90336
        get-all-sections:1
        section_id:99
        user_id : 369   // {optional} 
        pincode:370001  // {optional}
        pincode_id:413   //{optional}
    */
    if (!verify_token()) {
        return false;
    }
    $section_id = (isset($_POST['section_id']) && is_numeric($_POST['section_id'])) ? $db->escapeString($fn->xss_clean($_POST['section_id'])) : "";
    $user_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $pincode = (isset($_POST['pincode']) && is_numeric($_POST['pincode']) && !empty($_POST['pincode'])) ? $db->escapeString($fn->xss_clean($_POST['pincode'])) : "";
    $pincode_id = (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "";
    $and = "";
    $sql = "select * from `sections` ";
    $sql .= (!empty($section_id)) ? " where `id` = $section_id " : "";
    $sql .= " order by `id` desc";
    $db->sql($sql);
    $result = $db->getResult();

    $response = $product_ids = $section = $variations = $temp = array();

    foreach ($result as $row) {
        $product_ids = !empty($row['product_ids']) ? explode(',', $row['product_ids']) : array();
        $category_ids = !empty($row['category_ids']) ? explode(',', $row['category_ids']) : array();

        $section['id'] = $row['id'];
        $section['title'] = $row['title'];
        $section['short_description'] = $row['short_description'];
        $section['style'] = $row['style'];
        $section['product_type'] = $row['product_type'];
        $section['product_ids'] = array_map('trim', $product_ids);
        $product_ids = $section['product_ids'];

        $sort = "";
        $where = "";
        $group = "";
        $cate_ids = $row['category_ids'];

        if ($row['product_type'] == 'all_products') {
            if (empty($row['category_ids'])) {
                $sql = "SELECT id as product_id FROM `products` WHERE status = 1 ORDER BY product_id DESC";
                $sort .= " ORDER BY p.date_added DESC ";
            } else {
                $sql = "SELECT id as product_id FROM `products` WHERE status = 1 AND category_id IN($cate_ids) ORDER BY product_id DESC";
                $sort .= " ORDER BY p.date_added DESC ";
            }
        } elseif ($row['product_type'] == 'new_added_products') {
            if (empty($row['category_ids'])) {
                $sql = "SELECT id as product_id FROM `products` WHERE status = 1 ORDER BY product_id DESC";
                $sort .= " ORDER BY p.date_added DESC ";
            } else {
                $sql = "SELECT id as product_id FROM `products` WHERE status = 1 AND category_id IN($cate_ids) ORDER BY product_id DESC";
                $sort .= " ORDER BY p.date_added DESC ";
            }
        } elseif ($row['product_type'] == 'products_on_sale') {
            if (empty($row['category_ids'])) {
                $sql = "SELECT p.id as product_id FROM `products` p LEFT JOIN product_variant pv ON p.id=pv.product_id WHERE p.status = 1 AND pv.discounted_price > 0 AND pv.price > pv.discounted_price ORDER BY p.id DESC";
                $sort .= " ORDER BY p.id DESC ";
                $where .= " AND pv.discounted_price > 0 AND pv.price > pv.discounted_price";
            } else {
                $sql = "SELECT p.id as product_id FROM `products` p LEFT JOIN product_variant pv ON p.id=pv.product_id WHERE p.status = 1 AND p.category_id IN($cate_ids) AND pv.discounted_price > 0 AND pv.price > pv.discounted_price ORDER BY p.id DESC";
                $sort .= " ORDER BY p.id DESC ";
                $where .= " AND pv.discounted_price > 0 AND pv.price > pv.discounted_price";
            }
        } elseif ($row['product_type'] == 'most_selling_products') {
            if (empty($row['category_ids'])) {
                $sql = "SELECT p.id as product_id,oi.product_variant_id, COUNT(oi.product_variant_id) AS total FROM order_items oi LEFT JOIN product_variant pv ON oi.product_variant_id = pv.id LEFT JOIN products p ON pv.product_id = p.id WHERE oi.product_variant_id != 0 AND p.id != '' GROUP BY pv.id,p.id ORDER BY total DESC";
                $sort .= " ORDER BY COUNT(oi.product_variant_id) DESC ";
                $where .= " AND oi.product_variant_id != 0 AND p.id != ''";
            } else {
                $sql = "SELECT p.id as product_id,oi.product_variant_id, COUNT(oi.product_variant_id) AS total FROM order_items oi LEFT JOIN product_variant pv ON oi.product_variant_id = pv.id LEFT JOIN products p ON pv.product_id = p.id WHERE oi.product_variant_id != 0 AND p.id != '' AND p.category_id IN ($cate_ids) GROUP BY pv.id,p.id ORDER BY total DESC";
                $sort .= " ORDER BY COUNT(oi.product_variant_id) DESC ";
                $where .= " AND oi.product_variant_id != 0 AND p.id != ''";
            }
        } else {
            $product_ids = implode(',', $product_ids);
        }

        if ($row['product_type'] != 'custom_products' && empty($row['product_type'] == '')) {
            $db->sql($sql);
            $product = $db->getResult();
            $rows = $tempRow = array();
            foreach ($product as $row1) {
                $tempRow['product_id'] = $row1['product_id'];
                $rows[] = $tempRow;
            }
            $pro_id = array_column($rows, 'product_id');
            $product_ids = implode(",", $pro_id);
        }

        // $section['product_ids'] = array_map('trim', $product_ids);
        // $product_ids = $section['product_ids'];
        // $product_ids = implode(',', $product_ids);

        $group .= $row['product_type'] == 'most_selling_products' ? " GROUP BY pv.id" : " GROUP BY p.id";

        if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") {
            $and .=  " AND ((type='included' and FIND_IN_SET('$pincode_id', pincodes)) or type = 'all') OR ((type='excluded' and NOT FIND_IN_SET('$pincode_id', pincodes))) ";
        }

        if ($pincode != "") {
            $pincode_id = $fn->get_pincode_id_by_pincode($pincode);
            $pincode_id = $pincode_id[0]['id'];
            $and .=  " AND ((type='included' and FIND_IN_SET('$pincode_id', pincodes)) or type = 'all') OR ((type='excluded' and NOT FIND_IN_SET('$pincode_id', pincodes))) ";
        }
        if ($shipping_type == "standard") {
            $and .=  " AND p.standard_shipping=1";
        } else {
            $and .=  " AND p.standard_shipping=0";
        }
        if (!empty($product_ids)) {

            $sql = 'SELECT * FROM `products` WHERE `status` = 1  AND is_approved = 1 AND id IN (' . $product_ids . ')' . $and;
            $db->sql($sql);
            $result1 = $db->getResult();
            $product = array();
            $i = 0;
            foreach ($result1 as $row) {
                $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ORDER BY serve_for ASC";
                $db->sql($sql);
                $variants = $db->getResult();
                if (empty($variants)) {
                    continue;
                }
                $row['other_images'] = json_decode($row['other_images'], 1);
                $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
                for ($j = 0; $j < count($row['other_images']); $j++) {
                    $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
                }

                $row['type'] = (isset($row['type']) == null)  ? "" : $row['type'];
                $row['pincodes'] = (isset($row['pincodes']) == null)  ? "" : $row['pincodes'];
                $row['is_approved'] = (isset($row['is_approved']) == null)  ? "" : $row['is_approved'];
                $row['pickup_location'] = (isset($row['pickup_location']) == null)  ? "" : $row['pickup_location'];
                $row['pickup_postcode'] = (isset($row['pickup_postcode']) == null)  ? "" : $row['pickup_postcode'];
                $row['weight'] = (isset($row['weight']) == null)  ? "" : $row['weight'];
                $row['length'] = (isset($row['length']) == null)  ? "" : $row['length'];
                $row['breadth'] = (isset($row['breadth']) == null)  ? "" : $row['breadth'];
                $row['height'] = (isset($row['height']) == null)  ? "" : $row['height'];


                if (isset($row['seller_id']) != null) {
                    $seller_info = $fn->get_data($column = ['store_name'], "id=" . $row['seller_id'], "seller");
                    $row['seller_name'] = $seller_info[0]['store_name'];
                } else {
                    $row['seller_name'] = "";
                    $row['seller_id'] = "";
                }

                if ($row['tax_id'] == 0) {
                    $row['tax_title'] = "";
                    $row['tax_percentage'] = "";
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

                    if ($variants[$k]['stock'] <= 0 || $variants[$k]['serve_for'] == 'Sold Out') {

                        $variants[$k]['serve_for'] = 'Sold Out';
                    } else {
                        $variants[$k]['serve_for'] = 'Available';
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

                $row['image'] = DOMAIN_URL . $row['image'];
                $product[$i] = $row;
                $product[$i]['variants'] = $variants;
                $i++;
            }
            $section['products'] = $product;
            if (!empty($section['products'])) {
                $temp[] = $section;
            }
            unset($section['products']);
        }
    }
    if (!empty($result)) {
        $response['error'] = false;
        $response['sections'] = $temp;
    } else {
        $response['error'] = true;
        $response['message'] = "No section has been created yet";
    }
    print_r(json_encode($response));
}


if (isset($_POST['get-notifications'])) {
    /*
    2. get notifications pagination wise
        accesskey:90336
        get-notifications:1
        limit:10            // {optional }
        offset:0            // {optional }
        sort:id / type      // {optional }
        order:DESC / ASC    // {optional }
    */

    if (!verify_token()) {
        return false;
    }

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_POST['offset']))
        $offset = $db->escapeString($fn->xss_clean($_POST['offset']));
    if (isset($_POST['limit']))
        $limit = $db->escapeString($fn->xss_clean($_POST['limit']));

    if (isset($_POST['sort']))
        $sort = $db->escapeString($fn->xss_clean($_POST['sort']));
    if (isset($_POST['order']))
        $order = $db->escapeString($fn->xss_clean($_POST['order']));

    if (isset($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `title` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR `image` like '%" . $search . "%' OR `date_sent` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(`id`) as total FROM `notifications` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `notifications` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();
    if (empty($res)) {
        $response['error'] = true;
        $response['message'] = "Data not found!";
        print_r(json_encode($response));
        return false;
    }
    $bulkData = array();
    $bulkData['error'] = false;
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['title'];
        $tempRow['subtitle'] = $row['message'];
        $tempRow['type'] = $row['type'];
        $tempRow['type_id'] = $row['type_id'];
        $tempRow['image'] = (!empty($row['image'])) ? DOMAIN_URL . $row['image'] : "";
        $rows[] = $tempRow;
    }
    $bulkData['data'] = $rows;
    print_r(json_encode($bulkData));
}

if (isset($_POST['get-delivery-boy-notifications'])) {
    /* 
    3. get-delivery-boy-notifications [ pagination wise ]
        accesskey:90336
	    get-delivery-boy-notifications:1
        delivery_boy_id:10      // {optional }
        limit:10                // {optional }
        offset:0                // {optional }
        sort:id / type          // {optional }
        order:DESC / ASC        // {optional }
        type:order_status/order_reward  // {optional }
    */
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_POST['offset']))
        $offset = $db->escapeString($fn->xss_clean($_POST['offset']));
    if (isset($_POST['limit']))
        $limit = $db->escapeString($fn->xss_clean($_POST['limit']));

    if (isset($_POST['sort']))
        $sort = $db->escapeString($fn->xss_clean($_POST['sort']));
    if (isset($_POST['order']))
        $order = $db->escapeString($fn->xss_clean($_POST['order']));

    if (isset($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where = " Where `id` like '%" . $search . "%' OR `title` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR `date_created` like '%" . $search . "%' ";
    }
    if (isset($_POST['delivery_boy_id']) && !empty($_POST['delivery_boy_id'])) {
        $delivery_boy_id = $db->escapeString($fn->xss_clean($_POST['delivery_boy_id']));
        $where .= empty($where) ? ' where delivery_boy_id=' . $delivery_boy_id : 'and delivery_boy_id=' . $delivery_boy_id;
    }
    if (isset($_POST['type']) && !empty($_POST['type'])) {
        $type = $db->escapeString($fn->xss_clean($_POST['type']));
        $where .= empty($where) ? " where type='" . $type . "'" : " and type='" . $type . "'";
    }
    $sql = "SELECT COUNT(`id`) as total FROM `delivery_boy_notifications` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `delivery_boy_notifications` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();
    if (empty($res)) {
        $response['error'] = true;
        $response['message'] = "Data not found!";
        print_r(json_encode($response));
        return false;
    }
    $bulkData = $rows = $tempRow = array();
    $bulkData['error'] = false;
    $bulkData['total'] = $total;

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['delivery_boy_id'] = $row['delivery_boy_id'];
        $tempRow['title'] = $row['title'];
        $tempRow['message'] = $row['message'];
        $tempRow['type'] = $row['type'];
        $tempRow['date_sent'] = $row['date_created'];
        $rows[] = $tempRow;
    }
    $bulkData['data'] = $rows;
    print_r(json_encode($bulkData));
}

function isJSON($string)
{
    return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
}
