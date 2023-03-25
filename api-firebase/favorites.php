<?php
session_start();
include '../includes/crud.php';
include_once('../includes/variables.php');
include_once('../includes/custom-functions.php');


header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');


$fn = new custom_functions;
include_once('verify-token.php');
$db = new Database();
$db->connect();
$response = array();

if (!isset($_POST['accesskey'])) {
    $response['error'] = true;
    $response['message'] = "Access key is invalid or not passed!";
    print_r(json_encode($response));
    return false;
}
$accesskey = $db->escapeString($fn->xss_clean($_POST['accesskey']));
if ($access_key != $accesskey) {
    $response['error'] = true;
    $response['message'] = "invalid accesskey!";
    print_r(json_encode($response));
    return false;
}

if (!verify_token()) {
    return false;
}
/*
1.add_to_favorites
    accesskey:90336
    add_to_favorites:1
    user_id:3
    product_id:1
*/
if ((isset($_POST['add_to_favorites'])) && ($_POST['add_to_favorites'] == 1)) {
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $product_id = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_id'])) : "";
    if (!empty($user_id) && !empty($product_id)) {
        if ($fn->is_product_available($product_id)) {
            if (!$fn->is_product_added_as_favorite($user_id, $product_id)) {
                $data = array(
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                );
                if ($db->insert('favorites', $data)) {
                    $response['error'] = false;
                    $response['message'] = 'Item added in user\'s favorite list successfully';
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Something went wrong please try again!';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Product already added as favorite!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No such product available!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}

/*
2.remove_from_favorites
    accesskey:90336
    remove_from_favorites:1
    id:3 OR user_id : 413 OR (user_id and product_id)
*/
if ((isset($_POST['remove_from_favorites'])) && ($_POST['remove_from_favorites'] == 1)) {
    $id  = (isset($_POST['id']) && !empty($_POST['id'])) ? $db->escapeString($fn->xss_clean($_POST['id'])) : "";
    $user_id  = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $product_id  = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_id'])) : "";
    if (!empty($id) || !empty($user_id)) {
        $where = '';
        $where .= !empty($id) ? ' WHERE id = ' . $id : '';
        $where .= !empty($user_id) && empty($product_id) ? ' WHERE user_id = ' . $user_id : '';
        $where .= !empty($user_id) && !empty($product_id) ? ' WHERE user_id = ' . $user_id . ' AND product_id = ' . $product_id : '';
        $sql = "DELETE FROM favorites" . $where;
        if ($db->sql($sql) && empty($user_id)) {
            $response['error'] = false;
            $response['message'] = 'Item removed from user\'s favorite list successfully';
        } elseif ($db->sql($sql) && !empty($user_id) && empty($product_id)) {
            $response['error'] = false;
            $response['message'] = 'All items removed from user\'s favorite list successfully';
        } elseif ($db->sql($sql) && !empty($user_id) && !empty($product_id)) {
            $response['error'] = false;
            $response['message'] = 'Item removed from user\'s favorite list successfully';
        } else {
            $response['error'] = true;
            $response['message'] = 'Something went wrong please try again!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass id or user id!';
    }

    print_r(json_encode($response));
    return false;
}

/*
3.get_favorites
    accesskey:90336
    get_favorites:1
    user_id:3
    offset:0 {optional}
    limit:5 {optional}
*/
if ((isset($_POST['get_favorites'])) && ($_POST['get_favorites'] == 1)) {
    $user_id  = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    if (!empty($user_id)) {
        $sql = "SELECT count(id) as total from favorites where user_id=" . $user_id;
        $db->sql($sql);
        $total = $db->getResult();
        $sql = "select f.id,f.user_id,f.product_id,p.tax_id,p.row_order,p.name,p.slug,p.category_id,p.subcategory_id,p.indicator,p.total_allowed_quantity,p.manufacturer,p.made_in,p.return_status,p.cancelable_status,p.till_status,p.image,t.percentage as tax_percentage,t.title as tax_title,p.other_images,p.description,p.status,p.date_added from favorites f LEFT JOIN products p ON f.product_id=p.id left join taxes t on t.id=p.tax_id where f.user_id=" . $user_id . " ORDER BY f.date_created DESC LIMIT $offset,$limit";
        $db->sql($sql);
        $res = $db->getResult();

        $i = 0;
        $j = 0;
        $product = [];
        if (!empty($res)) {
            foreach ($res as $row) {
                if ($fn->is_product_available($row['product_id'])) {
                    $sql = "SELECT id from favorites where product_id = " . $row['product_id'] . " AND user_id = " . $user_id;
                    $db->sql($sql);
                    $result = $db->getResult();
                    if (!empty($result)) {
                        $row['is_favorite'] = true;
                    } else {
                        $row['is_favorite'] = false;
                    }
                    $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['product_id'] . " ";
                    $db->sql($sql);
                    $variants = $db->getResult();
                    if (empty($variants)) {
                        continue;
                    }
                    $row['other_images'] = json_decode($row['other_images'], 1);
                    $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
                    $row['tax_percentage'] = empty($row['tax_percentage']) ? "0" : $row['tax_percentage'];
                    $row['tax_title'] = empty($row['tax_title']) ? "" : $row['tax_title'];
                    $row['total_allowed_quantity'] = empty($row['total_allowed_quantity']) ? "0" : $row['total_allowed_quantity'];

                    for ($j = 0; $j < count($row['other_images']); $j++) {
                        $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
                    }
                    $row['image'] = !empty($row['image']) ? DOMAIN_URL . $row['image'] : "";

                    for ($k = 0; $k < count($variants); $k++) {

                        $variant_images = str_replace("'", '"', $variants[$k]['images']);
                        $variants[$k]['images'] = json_decode($variant_images, 1);
                        $variants[$k]['images'] = (empty($variants[$k]['images'])) ? array() : $variants[$k]['images'];

                        for ($j = 0; $j < count($variants[$k]['images']); $j++) {
                            $variants[$k]['images'][$j] = !empty(DOMAIN_URL . $variants[$k]['images'][$j]) ? DOMAIN_URL . $variants[$k]['images'][$j] : "";
                        }

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

                        $product[$i] = $row;
                        $product[$i]['variants'] = $variants;
                    }
                    $i++;

                    $response['error'] = false;
                    $response['total'] = $total[0]['total'];
                    $response['data'] = $product;
                }
            }
        } else {
            $response['error'] = true;
            $response['data'] = 'No item(s) found in user\'s favorite list!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}

/*
4.add_multiple_products_to_favorites
    accesskey:90336
    add_multiple_products_to_favorites:1
    user_id:3
    product_ids:1,2
*/
if ((isset($_POST['add_multiple_products_to_favorites'])) && ($_POST['add_multiple_products_to_favorites'] == 1)) {
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $product_ids = (isset($_POST['product_ids']) && !empty($_POST['product_ids'])) ? $db->escapeString($fn->xss_clean($_POST['product_ids'])) : "";
    if (!empty($user_id) && !empty($product_ids)) {
        $product_ids = explode(',', $product_ids);
        for ($i = 0; $i < count($product_ids); $i++) {
            if ($fn->is_product_available($product_ids[$i])) {
                if (!$fn->is_product_added_as_favorite($user_id, $product_ids[$i])) {
                    $data = array(
                        'user_id' => $user_id,
                        'product_id' => $product_ids[$i],
                    );
                    $db->insert('favorites', $data);
                }
            }
        }
        $response['error'] = false;
        $response['message'] = 'Items added in user\'s favorite list successfully';
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}
