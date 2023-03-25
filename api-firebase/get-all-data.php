<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once('send-email.php');
include_once('../includes/crud.php');
include_once('../includes/custom-functions.php');
include_once('../includes/variables.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES utf8");
$fn = new custom_functions();

$shipping_type = ($fn->get_settings('local_shipping') == 1) ? 'local' : 'standard';

$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

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
get-all-data.php
	accesskey:90336
	user_id:413      //{optional}
	pincode_id:413   //{optional}
	pincode:413   //{optional}
*/


$user_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
$pincode_id = (isset($_POST['pincode_id']) && is_numeric($_POST['pincode_id']) && !empty($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "";
$pincode  = (isset($_POST['pincode']) && !empty($_POST['pincode'])) ? $db->escapeString($fn->xss_clean_array($_POST['pincode'])) : "";


if (isset($_POST['pincode']) && !empty($_POST['pincode'])) {
    $pincode = $fn->get_data($column = ['pincode', 'id'], "pincode=" . "'$pincode'", "pincodes");
    if (empty($pincode)) {
        $response['error'] = true;
        $response['message'] = "Invalid Pincode passed.";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $pincode_id = $pincode[0]['id'];
}

$limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 12;
$offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

//categories
$sql_query = "SELECT * FROM category where status = 1 ORDER BY row_order ASC";
$db->sql($sql_query);
$res_categories = $db->getResult();

if (!empty($res_categories)) {
    for ($i = 0; $i < count($res_categories); $i++) {
        $res_categories[$i]['image'] = (!empty($res_categories[$i]['image'])) ? DOMAIN_URL . '' . $res_categories[$i]['image'] : '';
        $res_categories[$i]['web_image'] = (!empty($res_categories[$i]['web_image'])) ? DOMAIN_URL . '' . $res_categories[$i]['web_image'] : '';
    }
    $tmp = [];
    foreach ($res_categories as $r) {
        if (isset($r['product_rating'])) {
            unset($r['product_rating']);
        }
        $r['childs'] = [];

        $db->sql("SELECT * FROM subcategory WHERE category_id = '" . $r['id'] . "' ORDER BY id DESC");
        $childs = $db->getResult();
        if (!empty($childs)) {
            for ($i = 0; $i < count($childs); $i++) {
                $childs[$i]['image'] = (!empty($childs[$i]['image'])) ? DOMAIN_URL . '' . $childs[$i]['image'] : '';
                $r['childs'][$childs[$i]['slug']] = (array)$childs[$i];
            }
        }
        $tmp[] = $r;
    }
    $res_categories = $tmp;
}

// slider images
$sql = 'SELECT * from slider order by id DESC';
$db->sql($sql);
$res_slider_image = $db->getResult();
$temp = $slider_images = array();
if (!empty($res_slider_image)) {
    $response['error'] = false;
    foreach ($res_slider_image as $row) {
        $name = "";
        $slug = "";
        if ($row['type'] == 'category') {
            $sql = 'select `name`,`slug` from category where id = ' . $row['type_id'] . ' order by id desc';
            $db->sql($sql);
            $result1 = $db->getResult();
            $name = (!empty($result1[0]['name'])) ? $result1[0]['name'] : "";
            $slug = (!empty($result1[0]['slug'])) ? $result1[0]['slug'] : "";
        }
        if ($row['type'] == 'product') {
            $sql = 'select `name`,`slug` from products where id = ' . $row['type_id'] . ' order by id desc';
            $db->sql($sql);
            $result1 = $db->getResult();
            $name = (!empty($result1[0]['name'])) ? $result1[0]['name'] : "";
            $slug = (!empty($result1[0]['slug'])) ? $result1[0]['slug'] : "";
        }

        $temp['type'] = $row['type'];
        $temp['type_id'] = $row['type_id'];
        $temp['name'] = $name;
        $temp['slug'] = $slug;
        $temp['slider_url'] = !empty($row['slider_url']) ? $row['slider_url'] : "";
        $temp['image'] = DOMAIN_URL . $row['image'];
        $slider_images[] = $temp;
    }
}

// featured sections
$and = "";
$sql = 'select * from `sections` order by id desc';
$db->sql($sql);
$result = $db->getResult();


$response = $product_ids = $section = $variations = $featured_sections = $temp = array();
foreach ($result as $row) {
    $product_ids = !empty($row['product_ids']) ? explode(',', $row['product_ids']) : array();
    $category_ids = !empty($row['category_ids']) ? explode(',', $row['category_ids']) : array();

    $section['id'] = $row['id'];
    $section['title'] = $row['title'];
    $section['short_description'] = $row['short_description'];
    $section['style'] = $row['style'];
    $section['product_type'] = $row['product_type'];
    $product_ids = array_map('trim', $product_ids);

    $sort = "";
    $where = "";
    $group = "";
    $cate_ids = $row['category_ids'];
    $section_offer_images = [];

    $res_section_images = $fn->get_offers('below_section', $row['title']);
    foreach ($res_section_images as $offer_row) {
        $section_temp['image'] = !empty($offer_row['image']) && $offer_row['image'] != 'null' ? DOMAIN_URL . $offer_row['image'] : "";
        $section_offer_images[] = $section_temp;
    }

    $standard_shipping = ($shipping_type == 'standard') ? " AND standard_shipping = 1" : " AND standard_shipping = 0";
    if ($row['product_type'] == 'all_products') {
        if (empty($row['category_ids'])) {
            $sql = "SELECT id as product_id FROM `products` WHERE  status = 1  $standard_shipping ORDER BY product_id DESC  LIMIT $offset,$limit";
            $sort .= " ORDER BY p.date_added DESC ";
        } else {
            $sql = "SELECT id as product_id FROM `products` WHERE status = 1 $standard_shipping AND category_id IN($cate_ids) ORDER BY product_id DESC LIMIT $offset,$limit";
            $sort .= " ORDER BY p.date_added DESC ";
        }
    } elseif ($row['product_type'] == 'new_added_products') {
        if (empty($row['category_ids'])) {
            $sql = "SELECT id as product_id FROM `products` WHERE status = 1 $standard_shipping ORDER BY product_id DESC LIMIT $offset,$limit ";
            $sort .= " ORDER BY p.date_added DESC ";
        } else {
            $sql = "SELECT id as product_id FROM `products` WHERE status = 1 $standard_shipping AND category_id IN($cate_ids) ORDER BY product_id DESC LIMIT $offset,$limit";
            $sort .= " ORDER BY p.date_added DESC ";
        }
    } elseif ($row['product_type'] == 'products_on_sale') {
        if (empty($row['category_ids'])) {
            $sql = "SELECT p.id as product_id FROM `products` p LEFT JOIN product_variant pv ON p.id=pv.product_id WHERE p.status = 1 $standard_shipping AND pv.discounted_price > 0 AND pv.price > pv.discounted_price ORDER BY p.id DESC LIMIT $offset,$limit";
            $sort .= " ORDER BY p.id DESC ";
            $where .= " AND pv.discounted_price > 0 AND pv.price > pv.discounted_price";
        } else {
            $sql = "SELECT p.id as product_id FROM `products` p LEFT JOIN product_variant pv ON p.id=pv.product_id WHERE p.status = 1 $standard_shipping AND p.category_id IN($cate_ids) AND pv.discounted_price > 0 AND pv.price > pv.discounted_price ORDER BY p.id DESC LIMIT $offset,$limit";
            $sort .= " ORDER BY p.id DESC ";
            $where .= " AND pv.discounted_price > 0 AND pv.price > pv.discounted_price";
        }
    } elseif ($row['product_type'] == 'most_selling_products') {
        if (empty($row['category_ids'])) {
            $sql = "SELECT p.id as product_id,oi.product_variant_id, COUNT(oi.product_variant_id) AS total FROM order_items oi LEFT JOIN product_variant pv ON oi.product_variant_id = pv.id LEFT JOIN products p ON pv.product_id = p.id WHERE oi.product_variant_id != 0 AND p.id != '' $standard_shipping GROUP BY pv.id,p.id ORDER BY total DESC ";
            $sort .= " ORDER BY p.id DESC ";
        } else {
            $sql = "SELECT p.id as product_id,oi.product_variant_id, COUNT(oi.product_variant_id) AS total FROM order_items oi LEFT JOIN product_variant pv ON oi.product_variant_id = pv.id LEFT JOIN products p ON pv.product_id = p.id WHERE oi.product_variant_id != 0 AND p.id != '' $standard_shipping AND p.category_id IN ($cate_ids) GROUP BY pv.id,p.id ORDER BY total DESC ";
            $sort .= " ORDER BY p.id DESC ";
        }
    } else {
        $product_ids = implode(',', $product_ids);
        $sort .= " ORDER BY p.date_added DESC ";
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

    if (!empty($product) || !empty($product_ids)) {

        if (isset($_POST['pincode']) && $_POST['pincode'] != "") {
            $and .=  " AND (((p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes)) OR (p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes))) or p.type='all')";
        }

        $sql = ($shipping_type == 'standard') ? "SELECT p.*,s.name as seller_name,s.status as seller_status FROM `products`p JOIN seller s ON s.id = p.seller_id WHERE (p.is_approved = 1 AND p.standard_shipping=1 AND p.`status` = 1 AND s.status = 1 AND p.id IN ($product_ids))" . $and . " $sort LIMIT $offset,$limit" : "SELECT p.*,s.name as seller_name,s.status as seller_status FROM `products`p JOIN seller s ON s.id = p.seller_id WHERE (p.is_approved = 1 AND p.standard_shipping=0 AND p.`status` = 1 AND s.status = 1 AND p.id IN ($product_ids))" . $and . " $sort LIMIT $offset,$limit";
        $db->sql($sql);
        $result1 = $db->getResult();

        // echo $sql;
        $product_ids = array_column($result1, 'id');
        $product_ids = implode(",", $product_ids);
        $product = array();
        $i = 0;
        foreach ($result1 as $row) {
            $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ORDER BY serve_for ASC";
            $db->sql($sql);
            $variants = $db->getResult();
            if (empty($variants)) {
                continue;
            }
            if (!empty($row['other_images'])) {
                $row['other_images'] = json_decode($row['other_images'], 1);
            }
            $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];

            $row['type'] = (isset($row['type']) == null)  ? "" : $row['type'];
            $row['pincodes'] = (isset($row['pincodes']) == null)  ? "" : $row['pincodes'];
            $row['is_approved'] = (isset($row['is_approved']) == null)  ? "" : $row['is_approved'];
            $row['pickup_location'] = (isset($row['pickup_location']) == null)  ? "" : $row['pickup_location'];
            $row['pickup_postcode'] = (isset($row['pickup_postcode']) == null)  ? "" : $row['pickup_postcode'];
            $row['weight'] = (isset($row['weight']) == null)  ? "" : $row['weight'];
            $row['length'] = (isset($row['length']) == null)  ? "" : $row['length'];
            $row['breadth'] = (isset($row['breadth']) == null)  ? "" : $row['breadth'];
            $row['height'] = (isset($row['height']) == null)  ? "" : $row['height'];

            if (!empty($row['other_images'])) {
                for ($j = 0; $j < count($row['other_images']); $j++) {
                    $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
                }
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
            for ($k = 0; $k < count($variants); $k++) {

                $variants[$k]['images'] = json_decode($variants[$k]['images'], 1);
                $variants[$k]['images'] = (empty($variants[$k]['images'])) ? array() : $variants[$k]['images'];
                for ($j = 0; $j < count($variants[$k]['images']); $j++) {
                    $variants[$k]['images'][$j] = !empty(DOMAIN_URL . $variants[$k]['images'][$j]) ? DOMAIN_URL . $variants[$k]['images'][$j] : "";
                }

                if (!empty($user_id)) {
                    $sql = "SELECT qty as cart_count FROM cart where product_variant_id = " . $variants[$k]['id'] . " AND user_id = " . $user_id;
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
            }
            $row['image'] = DOMAIN_URL . $row['image'];
            $product[$i] = $row;
            $product[$i]['variants'] = $variants;
            $i++;
        }
    } else {
        $product = [];
    }
    $section['offer_images'] = (isset($section_offer_images) && !empty($section_offer_images)) ? $section_offer_images : [];
    $section['products'] = (isset($product) && !empty($product)) ? $product : [];
    $featured_sections[] = $section;

    unset($section['products']);
    $section_offer_images = [];
}




// offer images
$res_offer_images = $fn->get_offers('top');
$response = $temp = $offer_images = array();

foreach ($res_offer_images as $row) {
    $temp['image'] = DOMAIN_URL . $row['image'];
    $offer_images[] = $temp;
}

// category offer images
$res_offer_images = $fn->get_offers('below_category');
foreach ($res_offer_images as $row) {
    $cate_temp['image'] = DOMAIN_URL . $row['image'];
    $cate_offer_images[] = $cate_temp;
}

// slider offer images
$res_slider_images = $fn->get_offers('below_slider');
foreach ($res_slider_images as $row) {
    $temp['image'] = DOMAIN_URL . $row['image'];
    $slider_offer_images[] = $temp;
}

// seller offer images
$res_seller_images = $fn->get_offers('below_seller');
foreach ($res_seller_images as $row) {
    $temp['image'] = DOMAIN_URL . $row['image'];
    $seller_offer_images[] = $temp;
}

// seller
$res_seller = array();
if (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") {
    $pincode_id = $db->escapeString($fn->xss_clean($_POST['pincode_id']));
    $sql_query = "select s.* from products p join seller s on s.id=p.seller_id WHERE s.status = 1 and p.status=1 AND ((p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes)) or p.type = 'all') OR ((p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes))) GROUP by p.seller_id ";
} else {
    $sql_query = "SELECT * FROM `seller` s WHERE s.status = 1";
}
$db->sql($sql_query);
$result = $db->getResult();
$tempRow = array();

foreach ($result as $row) {
    $seller_address = $fn->get_seller_address($row['id']);

    $tempRow['id'] = $row['id'];
    $tempRow['name'] = $row['name'];
    $tempRow['slug'] = $row['slug'];
    $tempRow['store_name'] = $row['store_name'];
    $tempRow['email'] = $row['email'];
    $tempRow['mobile'] = $row['mobile'];
    $tempRow['balance'] = strval(ceil($row['balance']));
    $tempRow['store_url'] = $row['store_url'];
    $tempRow['store_description'] = $row['store_description'];
    $tempRow['street'] = $row['street'];
    $tempRow['pincode_id'] = $row['pincode_id'];
    $tempRow['state'] = $row['state'];
    $tempRow['categories'] = $row['categories'];
    $tempRow['account_number'] = $row['account_number'];
    $tempRow['bank_ifsc_code'] = $row['bank_ifsc_code'];
    $tempRow['bank_name'] = $row['bank_name'];
    $tempRow['account_name'] = $row['account_name'];
    $tempRow['logo'] = DOMAIN_URL . 'upload/seller/' . $row['logo'];
    $tempRow['national_identity_card'] = DOMAIN_URL . 'upload/seller/' . $row['national_identity_card'];
    $tempRow['address_proof'] = DOMAIN_URL . 'upload/seller/' . $row['address_proof'];
    $tempRow['pan_number'] = !empty($row['pan_number']) ? $row['pan_number'] : "";
    $tempRow['tax_name'] = !empty($row['tax_name']) ? $row['tax_name'] : "";
    $tempRow['tax_number'] = !empty($row['tax_number']) ? $row['tax_number'] : "";
    $tempRow['categories'] = !empty($row['categories']) ? $row['categories'] : "";
    $tempRow['longitude'] = (!empty($row['longitude']))  ? $row['longitude'] : "";
    $tempRow['latitude'] = !empty($row['latitude'])  ? $row['latitude'] : "";
    $tempRow['seller_address'] = $seller_address;
    $res_seller[] = $tempRow;
}

// Settings
$sql = "select variable, value from `settings`";
$db->sql($sql);
$res = $db->getResult();

if (!empty($res)) {
    foreach ($res as $k => $v) {
        if ($v['variable'] == "system_timezone") {
            $system_timezones = (array)json_decode($v['value']);
            $system_timezone = $fn->replaceArrayKeys($system_timezones);
            foreach ($system_timezone as $k => $v) {
                $settings[$k] = $v;
            }
            /* Setting deault values if not set */
            $settings['min_refer_earn_order_amount'] = (!isset($settings['min_refer_earn_order_amount'])) ? 1 : $settings['min_refer_earn_order_amount'];
            $settings['refer_earn_bonus'] = (!isset($settings['refer_earn_bonus'])) ? 0 : $settings['refer_earn_bonus'];
            $settings['max_refer_earn_amount'] = (!isset($settings['max_refer_earn_amount'])) ? 0 : $settings['max_refer_earn_amount'];
            $settings['minimum_withdrawal_amount'] = (!isset($settings['minimum_withdrawal_amount'])) ? 1 : $settings['minimum_withdrawal_amount'];
            $settings['max_product_return_days'] = (!isset($settings['max_product_return_days'])) ? 0 : $settings['max_product_return_days'];
            $settings['delivery_boy_bonus_percentage'] = (!isset($settings['delivery_boy_bonus_percentage'])) ? 1 : $settings['delivery_boy_bonus_percentage'];
            $settings['low_stock_limit'] = (!isset($settings['low_stock_limit'])) ? 10 : $settings['low_stock_limit'];
            $settings['user_wallet_refill_limit'] = (!isset($settings['user_wallet_refill_limit'])) ? 100000 : $settings['user_wallet_refill_limit'];
            $settings['delivery_charge'] = (!isset($settings['delivery_charge'])) ? 0 : $settings['delivery_charge'];
            $settings['min_order_amount'] = (!isset($settings['min_order_amount'])) ? 1 : $settings['min_order_amount'];
            $settings['min_amount'] = (!isset($settings['min_amount'])) ? 1 : $settings['min_amount'];
        } else {
            $settings[$v['variable']] = $v['value'];
        }
    }
}

// Social Media
$sql_query = "SELECT * FROM social_media ORDER BY id ASC ";
$db->sql($sql_query);
$social_media = $db->getResult();

$data = $fn->get_settings('categories_settings', true);
$response['error'] = false;
$response['message'] = "Data fetched successfully";
if (!empty($data)) {
    $response['style'] =  $data['cat_style'];
    $response['visible_count'] = $data['max_visible_categories'];
    $response['column_count'] = ($data['cat_style'] == "style_2") ? 0 : $data['max_col_in_single_row'];
} else {
    $response['style'] =  "";
    $response['visible_count'] = 0;
    $response['column_count'] = 0;
}

$response['category_offer_images'] = (!empty($cate_offer_images)) ? $cate_offer_images : [];
$response['slider_offer_images'] = (!empty($slider_offer_images)) ? $slider_offer_images : [];
$response['seller_offer_images'] = (!empty($seller_offer_images)) ? $seller_offer_images : [];
$response['categories'] = (!empty($res_categories)) ? $res_categories : [];
$response['slider_images'] = (!empty($slider_images)) ? $slider_images : [];
$response['sections'] = (!empty($featured_sections)) ? $featured_sections : [];
$response['offer_images'] = (!empty($offer_images)) ? $offer_images : [];
$response['seller'] = (!empty($res_seller)) ? $res_seller : [];
$response['settings'] = (!empty($settings)) ? $settings : [];
$response['social_media'] = (!empty($social_media)) ? $social_media : [];

print_r(json_encode($response));
