<?php
session_start();
include '../includes/crud.php';
include_once('../includes/variables.php');
include_once('../includes/custom-functions.php');
include('../library/shiprocket.php');

header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');

$fn = new custom_functions;
include_once('verify-token.php');
$db = new Database();
$shiprocket = new Shiprocket();
$db->connect();
$response = array();


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

if (!isset($_POST['accesskey'])) {
    $response['error'] = true;
    $response['message'] = "Access key is invalid or not passed!";
    print_r(json_encode($response));
    return false;
}
$accesskey = $db->escapeString($fn->xss_clean_array($_POST['accesskey']));
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
1.add_to_cart
    accesskey:90336
    add_to_cart:1
    user_id:3
    product_id:1
    product_variant_id:4
    qty:2
*/
if ((isset($_POST['add_to_cart'])) && ($_POST['add_to_cart'] == 1)) {
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $product_id = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_id'])) : "";
    $product_variant_id  = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
    $qty = (isset($_POST['qty']) && !empty($_POST['qty'])) ? $db->escapeString($fn->xss_clean_array($_POST['qty'])) : "";
    if (!empty($user_id) && !empty($product_id)) {
        if (!empty($product_variant_id)) {
            if ($fn->is_item_available($product_id, $product_variant_id)) {
                $sql = "select serve_for,stock from product_variant where id = " . $product_variant_id;
                $db->sql($sql);
                $stock = $db->getResult();
                if ($stock[0]['stock'] > 0 && $stock[0]['serve_for'] == 'Available') {
                    // $total_allowed_quantity = $fn->get_data($columns = ['total_allowed_quantity'], "id='" . $product_id . "'", 'products');
                    // if (isset($total_allowed_quantity[0]['total_allowed_quantity']) && !empty($total_allowed_quantity[0]['total_allowed_quantity'])) {
                    // }
                    if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {

                        /* if item found in user's cart update it */
                        if (empty($qty) || $qty == 0) {
                            $sql = "DELETE FROM cart WHERE user_id = $user_id AND product_variant_id = $product_variant_id";
                            if ($db->sql($sql)) {
                                $response['error'] = false;
                                $response['message'] = 'Item removed from users cart due to 0 quantity';
                            } else {
                                $response['error'] = true;
                                $response['message'] = 'Something went wrong please try again!';
                            }
                            print_r(json_encode($response));
                            return false;
                        }
                        // check for total allowed quantity
                        $total_quantity = $fn->get_data($columns = ['sum(qty) as total'], "product_id='" . $product_id . "' and user_id='" . $user_id . "' and save_for_later='0'", 'cart');
                        if (isset($total_quantity[0]['total']) && !empty($total_quantity[0]['total'])) {
                            $total_allowed_quantity = $fn->get_data($columns = ['total_allowed_quantity'], "id='" . $product_id . "'", 'products');
                            if (isset($total_allowed_quantity[0]['total_allowed_quantity']) && !empty($total_allowed_quantity[0]['total_allowed_quantity'])) {
                                $total_quantity = $total_quantity[0]['total'];
                                $temp = $fn->get_data($columns = ['qty'], "product_variant_id='" . $product_variant_id . "' and user_id='" . $user_id . "'", 'cart');
                                $total_quantity = $total_quantity - $temp[0]['qty'];
                                $total_quantity = $total_quantity + $qty;
                                if ($total_quantity > $total_allowed_quantity[0]['total_allowed_quantity']) {
                                    $response['error'] = true;
                                    $response['message'] = 'Total allowed quantity for this product is ' . $total_allowed_quantity[0]['total_allowed_quantity'] . '!';
                                    print_r(json_encode($response));
                                    return false;
                                }
                            }
                        }
                        $data = array(
                            'qty' => $qty,
                            'save_for_later' => 0
                        );
                        if ($db->update('cart', $data, 'user_id=' . $user_id . ' AND product_variant_id=' . $product_variant_id)) {
                            $response['error'] = false;
                            $response['message'] = 'Item added in users cart successfully';
                        } else {
                            $response['error'] = true;
                            $response['message'] = 'Something went wrong please try again!';
                        }
                    } else {

                        /* Check user status */
                        $sql = "select status from users where id = " . $user_id;
                        $db->sql($sql);
                        $result = $db->getResult();
                        if (isset($result[0]['status']) && $result[0]['status'] == 1) {
                            $total_allowed_quantity = $fn->get_data($columns = ['total_allowed_quantity'], "id='" . $product_id . "'", 'products');
                            if (isset($total_allowed_quantity[0]['total_allowed_quantity']) && !empty($total_allowed_quantity[0]['total_allowed_quantity'])) {
                                if ($qty > $total_allowed_quantity[0]['total_allowed_quantity']) {
                                    $response['error'] = true;
                                    $response['message'] = 'Total allowed quantity for this product is ' . $total_allowed_quantity[0]['total_allowed_quantity'] . '!';
                                    print_r(json_encode($response));
                                    return false;
                                }
                            }
                            /* if item not found in user's cart add it */
                            $data = array(
                                'user_id' => $user_id,
                                'product_id' => $product_id,
                                'product_variant_id' => $product_variant_id,
                                'qty' => $qty
                            );
                            if ($db->insert('cart', $data)) {
                                $response['error'] = false;
                                $response['message'] = 'Item added to users cart successfully';
                            } else {
                                $response['error'] = true;
                                $response['message'] = 'Something went wrong please try again!';
                            }
                        } else {
                            $response['error'] = true;
                            $response['message'] = 'Not allowed to add to cart as your account is de-activated!';
                        }
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Opps stock is not available!';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'No such item available!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Please choose atleast one item!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}

/*
    2.add_multiple_items
        accesskey:90336
        add_multiple_items OR save_for_later_items:1
        user_id:3
        product_variant_id:203,198,202
        qty:1,2,1
    */
if (((isset($_POST['add_multiple_items'])) && ($_POST['add_multiple_items'] == 1)) || ((isset($_POST['save_for_later_items'])) && ($_POST['save_for_later_items'] == 1))) {

    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $product_variant_id  = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
    $qty = (isset($_POST['qty']) && !empty($_POST['qty'])) ? $db->escapeString($fn->xss_clean_array($_POST['qty'])) : "";
    $empty_qty = $is_variant =  $is_product = false;
    $empty_qty_1 = false;
    $item_exists = false;
    $item_exists_1 = false;
    $item_exists_2 = false;

    $sql = "SELECT * FROM users where id = $user_id";
    $db->sql($sql);
    $res1 = $db->getResult();
    if ($res1[0]['status'] == 1) {
        if (!empty($user_id)) {
            if (!empty($product_variant_id)) {
                $product_variant_id = explode(",", $product_variant_id);
                $qty = explode(",", $qty);
                for ($i = 0; $i < count($product_variant_id); $i++) {
                    if ((isset($_POST['add_multiple_items'])) && ($_POST['add_multiple_items'] == 1)) {
                        if ($fn->get_product_id_by_variant_id($product_variant_id[$i])) {
                            $product_id = $fn->get_product_id_by_variant_id($product_variant_id[$i]);
                            if ($fn->is_item_available($product_id, $product_variant_id[$i])) {
                                if ($fn->is_item_available_in_save_for_later($user_id, $product_variant_id[$i])) {
                                    $data = array(
                                        'save_for_later' => 0
                                    );
                                    $db->update('cart', $data, 'user_id=' . $user_id . ' AND product_variant_id=' . $product_variant_id[$i]);
                                }
                                if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id[$i])) {
                                    $item_exists = true;
                                    if (empty($qty[$i]) || $qty[$i] == 0) {
                                        $empty_qty = true;
                                        $sql = "DELETE FROM cart WHERE user_id = $user_id AND product_variant_id = $product_variant_id[$i]";
                                        $db->sql($sql);
                                    } else {
                                        $data = array(
                                            'qty' => $qty[$i]
                                        );
                                        $db->update('cart', $data, 'user_id=' . $user_id . ' AND product_variant_id=' . $product_variant_id[$i]);
                                    }
                                } else {
                                    if (!empty($qty[$i]) && $qty[$i] != 0) {
                                        $data = array(
                                            'user_id' => $user_id,
                                            'product_id' => $product_id,
                                            'product_variant_id' => $product_variant_id[$i],
                                            'qty' => $qty[$i]
                                        );
                                        $db->insert('cart', $data);
                                    } else {
                                        $empty_qty_1 = true;
                                    }
                                }
                            } else {
                                $is_variant = true;
                            }
                        } else {
                            $is_product = true;
                        }
                    } else if ((isset($_POST['save_for_later_items'])) && ($_POST['save_for_later_items'] == 1)) {
                        if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id[$i])) {
                            $item_exists_1 = true;
                            $data = array(
                                'save_for_later' => 1
                            );
                            $db->update('cart', $data, 'user_id=' . $user_id . ' AND product_variant_id=' . $product_variant_id[$i]);
                        } else {
                            $item_exists_2 = true;
                        }
                    }
                }
                $response['error'] = false;
                $response['message'] = $item_exists == true ? 'Cart Updated successfully!' : 'Cart Added Successfully';
                $response['message'] .= $item_exists_1 == true ? 'Item add to save for later!' : '';
                $response['message'] .= $item_exists_2 == true ? 'Item not add into cart!' : '';
                $response['message'] .= $empty_qty == true ? 'Some items removed due to 0 quantity' : '';
                $response['message'] .= $empty_qty_1 == true ? 'Some items not added due to 0 quantity' : '';
                $response['message'] .= $is_variant == true ? 'Some items not present in product list now' : '';
                $response['message'] .= $is_product == true ? 'Some items not present in product list now' : '';
            } else {
                $response['error'] = true;
                $response['message'] = 'Please choose atleast one item!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Please pass all the fields!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Your Account is De-active ask on Customer Support!';
    }
    print_r(json_encode($response));
    return false;
}

/*
3.remove_from_cart
    accesskey:90336
    remove_from_cart:1
    user_id:3
    product_variant_id:4 {optional}
*/
if ((isset($_POST['remove_from_cart'])) && ($_POST['remove_from_cart'] == 1)) {
    $user_id  = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $product_variant_id = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
    if (!empty($user_id)) {
        if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {
            /* if item found in user's cart remove it */
            $sql = "DELETE FROM cart WHERE user_id=" . $user_id . " and save_for_later=0";
            $sql .= !empty($product_variant_id) ? " AND product_variant_id=" . $product_variant_id : "";
            if ($db->sql($sql) && !empty($product_variant_id)) {
                $response['error'] = false;
                $response['message'] = 'Item removed from users cart successfully';
            } elseif ($db->sql($sql) && empty($product_variant_id)) {
                $response['error'] = false;
                $response['message'] = 'All items removed from users cart successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something went wrong please try again!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Item not found in users cart!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}

/*
    4.get_user_cart
        accesskey:90336
        get_user_cart:1
        user_id:3
        pincode_id:370100   // {optional}
        address_id:250      // {optional}
        is_code:1           // {optional}
        type:delivery_charge    // {optional}
    */

if ((isset($_POST['get_user_cart'])) && ($_POST['get_user_cart'] == 1)) {

    $ready_to_add = false;
    $pincode_id = "";
    $delivery_charage_by_item = 0;
    $is_code = (isset($_POST['is_code']) && !empty($_POST['is_code'])) ? $db->escapeString($fn->xss_clean_array($_POST['is_code'])) : "";
    $user_id  = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $address_id  = (isset($_POST['address_id']) && !empty($_POST['address_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['address_id'])) : "";
    $type  = (isset($_POST['type']) && !empty($_POST['type'])) ? $db->escapeString($fn->xss_clean_array($_POST['type'])) : "";
    $passed_pincode_id  = (isset($_POST['pincode_id']) && !empty($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['pincode_id'])) : "";

    if ($address_id != "") {
        $pincodes = $fn->get_data($column = ['pincode_id', 'pincode'], "id= $address_id AND user_id=$user_id", "user_addresses");
        if (isset($pincodes[0]['pincode_id']) && $pincodes[0]['pincode_id'] == 0) {
            $user_pincode = $pincodes[0]['pincode'];
        } else {
            if (empty($pincodes)) {
                $response['error'] = true;
                $response['message'] = 'Address not available for delivary check. First set the address.';
                print_r(json_encode($response));
                return false;
            }
            $pincode_id = $pincodes[0]['pincode_id'];
        }
    }

    if ($passed_pincode_id != "") {
        $pincode_id = $passed_pincode_id;
    }

    if (!empty($user_id)) {
        if ($fn->is_item_available_in_user_cart($user_id)) {
            $i = 0;
            $j = 0;
            $x = 0;
            if ($shipping_type == 'standard') {
                $total_amount = 0;
                $save_price = 0;

                $sql = "SELECT count(cart.id) as total from cart left join products p on cart.product_id=p.id where p.standard_shipping=1 and cart.save_for_later = 0 AND user_id=" . $user_id;
                $db->sql($sql);
                $total = $db->getResult();

                $sql = "SELECT cart.*  from cart left join products p on cart.product_id=p.id where p.standard_shipping=1 and cart.save_for_later = 0 AND user_id=" . $user_id . " ORDER BY date_created DESC ";
                $db->sql($sql);
                $res = $db->getResult();

                $sql = "SELECT cart.qty,cart.product_variant_id from cart left join products p on cart.product_id=p.id where p.standard_shipping=1 and cart.save_for_later = 0 and user_id=" . $user_id;
                $db->sql($sql);
                $res_1 = $db->getResult();

                foreach ($res_1 as $row_1) {
                    $sql = "select price,discounted_price from product_variant where id=" . $row_1['product_variant_id'];
                    $db->sql($sql);
                    $result_1 = $db->getResult();
                    $taxed_amout = $fn->get_taxabled_amount($row_1['product_variant_id']);
                    foreach ($result_1 as $result_2) {
                        $price = $taxed_amout[0]['taxable_amount'] * $row_1['qty'];
                    }
                    $total_amount += $price;
                    $save_price += $taxed_amout[0]['taxable_price'] * $row_1['qty'];
                }
                /* looping on cart items */
                foreach ($res as $row) {
                    $sql = "select pv.*,p.seller_id as seller_id,p.name,p.pincodes,p.pickup_location,pv.weight,p.standard_shipping,p.type as d_type,p.cod_allowed,p.slug,p.image,p.other_images,p.total_allowed_quantity,case WHEN t.percentage!=0 then t.percentage ELSE '0' end as tax_percentage,case WHEN t.title!='' then t.title ELSE '' end as tax_title ,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit,(Select short_code from unit su where su.id=pv.stock_unit_id) as stock_unit_name from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id  where pv.id=" . $row['product_variant_id'] . " GROUP BY pv.id";
                    $db->sql($sql);
                    $res[$i]['item'] = $db->getResult();
                    if (isset($res[$i]['item']) && !empty($res[$i]['item'])) {
                        for ($k = 0; $k < count($res[$i]['item']); $k++) {
                            $res[$i]['item'][$k]['is_item_deliverable'] = false;

                            $variant_images = str_replace("'", '"', $res[$i]['item'][$k]['images']);
                            $res[$i]['item'][$k]['images'] = json_decode($variant_images, 1);
                            $res[$i]['item'][$k]['images'] = (empty($res[$i]['item'][$k]['images']) || !isset($res[$i]['item'][$k]['images']) || is_null($res[$i]['item'][$k]['images'])) ? [] : $res[$i]['item'][$k]['images'];
                            for ($j = 0; $j < count($res[$i]['item'][$k]['images']); $j++) {
                                $res[$i]['item'][$k]['images'][$j] = !empty(DOMAIN_URL . $res[$i]['item'][$k]['images'][$j]) ? DOMAIN_URL . $res[$i]['item'][$k]['images'][$j] : [];
                            }

                            $res[$i]['seller_id'] = $res[$i]['item'][$k]['seller_id'];
                            $standard_shipping = $res[$i]['item'][$k]['standard_shipping'];

                            if ($standard_shipping == 1) {
                                $pickup_location = $fn->get_data($column = ['pin_code'], "pickup_location='" . $res[$i]['item'][$k]['pickup_location'] . "'", 'pickup_locations');

                                $res[$i]['item'][$k]['is_item_deliverable'] = false;
                                if (($pincode_id != 0)) {

                                    if (!empty($user_pincode)) {
                                        $data = array('pickup_location' => $pickup_location[0]['pin_code'], 'delivery_pincode' => $user_pincode, 'weight' => $res[$i]['item'][$k]['weight'], 'cod' => $res[$i]['item'][$k]['cod_allowed']);

                                        $shiprocket_data = $shiprocket->check_serviceability($data);
                                        if ($shiprocket_data['status'] == 200) {
                                            $shiprocket_data = $fn->shiprocket_recomended_data($shiprocket_data);
                                            $res[$i]['item'][$k]['is_item_deliverable'] = true;
                                        } else {
                                            $res[$i]['item'][$k]['is_item_deliverable'] = false;
                                        }
                                    } else {
                                        $pincodes_data = $fn->get_data($column = ['*'], "id=" . $pincode_id, "pincodes");
                                        $data = array('pickup_location' => $pickup_location[0]['pin_code'], 'delivery_pincode' =>  $pincodes_data[0]['pincode'], 'weight' => $res[$i]['item'][$k]['weight'], 'cod' => $res[$i]['item'][$k]['cod_allowed']);

                                        $shiprocket_data = $shiprocket->check_serviceability($data);
                                        if ($shiprocket_data['status'] == 200) {
                                            $shiprocket_data = $fn->shiprocket_recomended_data($shiprocket_data);
                                            $res[$i]['item'][$k]['is_item_deliverable'] = true;
                                            $user_pincode = $pincodes_data[0]['pincode'];
                                        } else {
                                            $res[$i]['item'][$k]['is_item_deliverable'] = false;
                                        }
                                    }
                                } else if (isset($_POST['pincode']) && !empty($_POST['pincode'])) {

                                    $pickup_location = $fn->get_data($column = ['pin_code'], "pickup_location='" . $res[$i]['item'][$k]['pickup_location'] . "'", 'pickup_locations');
                                    $data = array('pickup_location' => $pickup_location[0]['pin_code'], 'delivery_pincode' => $_POST['pincode'], 'weight' => $res[$i]['item'][$k]['weight'], 'cod' => $res[$i]['item'][$k]['cod_allowed']);

                                    $shiprocket_data = $shiprocket->check_serviceability($data);
                                    if ($shiprocket_data['status'] == 200) {
                                        $shiprocket_data = $fn->shiprocket_recomended_data($shiprocket_data);
                                        $res[$i]['item'][$k]['is_item_deliverable'] = true;
                                        $user_pincode = $_POST['pincode'];
                                    } else {
                                        $res[$i]['item'][$k]['is_item_deliverable'] = false;
                                    }
                                }
                            }

                            for ($j = 0; $j < count($res[$i]['item']); $j++) {
                                $res[$i]['item'][$j]['image'] = !empty($res[$i]['item'][$j]['image']) ? DOMAIN_URL . $res[$i]['item'][$j]['image'] : "";
                                $res[$i]['item'][$j]['size_chart'] = !empty($res[$i]['item'][$j]['size_chart']) ? DOMAIN_URL . $res[$i]['item'][$j]['size_chart'] : "";
                            }
                        }
                        $i++;
                    }
                }

                // get save for later data
                $sql = "select * from cart where save_for_later = 1 AND user_id=" . $user_id . " ORDER BY date_created DESC ";
                $db->sql($sql);
                $res1 = $result = $db->getResult();

                foreach ($res1 as $row1) {
                    $sql = "select price,discounted_price from product_variant where id=" . $row1['product_variant_id'];
                    $db->sql($sql);
                    $result1 = $db->getResult();
                    foreach ($result1 as $result2) {
                        $price = ($result2['discounted_price'] <= 0 || empty($result2['discounted_price'])) ? $result2['price'] * $row_1['qty'] : $result2['discounted_price'] * $row1['qty'];
                    }
                }

                /* looping on saved for later items */
                foreach ($result as $rows) {
                    $sql = "select pv.*,p.name,p.type as d_type,p.cod_allowed,p.slug,p.image,p.other_images,case WHEN t.percentage!=null then t.percentage ELSE 0 end as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id where pv.id=" . $rows['product_variant_id'] . " GROUP BY pv.id";
                    $db->sql($sql);
                    $result[$x]['item'] = $db->getResult();
                    for ($z = 0; $z < count($result[$x]['item']); $z++) {
                        $result[$x]['item'][$z]['is_item_deliverable'] = '';

                        $variant_images = str_replace("'", '"', $result[$x]['item'][$z]['images']);
                        $result[$x]['item'][$z]['images'] = json_decode($variant_images, 1);
                        $result[$x]['item'][$z]['images'] = (empty($result[$x]['item'][$z]['images'])) ? array() : $result[$x]['item'][$z]['images'];

                        for ($j = 0; $j < count($result[$x]['item'][$z]['images']); $j++) {
                            $result[$x]['item'][$z]['images'][$j] = !empty(DOMAIN_URL . $result[$x]['item'][$z]['images'][$j]) ? DOMAIN_URL . $result[$x]['item'][$z]['images'][$j] : "";
                        }

                        $result[$x]['item'][$z]['other_images'] = json_decode($result[$x]['item'][$z]['other_images']);
                        $result[$x]['item'][$z]['other_images'] = empty($result[$x]['item'][$z]['other_images']) ? array() : $result[$x]['item'][$z]['other_images'];

                        $result[$x]['item'][$z]['tax_percentage'] = (!empty($result[$x]['item'][$z]['tax_percentage']) && $result[$x]['item'][$z]['tax_percentage'] != "") ? $result[$x]['item'][$z]['tax_percentage']  : "0";
                        $result[$x]['item'][$z]['tax_title'] = empty($result[$x]['item'][$z]['tax_title']) ? "" : $result[$x]['item'][$z]['tax_title'];

                        if ($result[$x]['item'][$z]['stock'] <= 0 || $result[$x]['item'][$z]['serve_for'] == 'Sold Out') {
                            $result[$x]['item'][$z]['isAvailable'] = false;
                            $ready_to_add = true;
                        } else {
                            $result[$x]['item'][$z]['isAvailable'] = true;
                        }

                        for ($y = 0; $y < count($result[$x]['item'][$z]['other_images']); $y++) {
                            $other_images = DOMAIN_URL . $result[$x]['item'][$z]['other_images'][$y];
                            $result[$x]['item'][$z]['other_images'][$y] = $other_images;
                        }
                    }
                    for ($j = 0; $j < count($result[$x]['item']); $j++) {
                        $result[$x]['item'][$j]['image'] = !empty($result[$x]['item'][$j]['image']) ? DOMAIN_URL . $result[$x]['item'][$j]['image'] : "";
                    }
                    $x++;
                }
            } else {
                $total_amount = 0;
                $sql = "SELECT count(cart.id) as total from cart left join products p on cart.product_id=p.id where p.standard_shipping=0 and cart.save_for_later = 0 AND user_id=" . $user_id;
                $db->sql($sql);
                $total = $db->getResult();
                $sql = "SELECT cart.*  from cart left join products p on cart.product_id=p.id where p.standard_shipping=0 and cart.save_for_later = 0 AND user_id=" . $user_id . " ORDER BY date_created DESC ";
                $db->sql($sql);
                $res = $db->getResult();

                $sql = "SELECT cart.qty,cart.product_variant_id from cart left join products p on cart.product_id=p.id where p.standard_shipping=0 and cart.save_for_later = 0 and user_id=" . $user_id;
                $db->sql($sql);
                $res_1 = $db->getResult();
                foreach ($res_1 as $row_1) {
                    $sql = "select price,discounted_price from product_variant where id=" . $row_1['product_variant_id'];
                    $db->sql($sql);
                    $result_1 = $db->getResult();
                    $taxed_amout = $fn->get_taxabled_amount($row_1['product_variant_id']);
                    foreach ($result_1 as $result_2) {
                        $price = $taxed_amout[0]['taxable_amount'] * $row_1['qty'];
                    }
                    $total_amount += $price;
                    $save_price += $taxed_amout[0]['taxable_price'] * $row_1['qty'];
                }

                foreach ($res as $row) {
                    $sql = "select pv.*,p.name,p.pincodes,p.type as d_type,p.cod_allowed,p.slug,p.image,p.other_images,p.total_allowed_quantity,case WHEN t.percentage!=0 then t.percentage ELSE '0' end as tax_percentage,case WHEN t.title!='' then t.title ELSE '' end as tax_title ,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit,(Select short_code from unit su where su.id=pv.stock_unit_id) as stock_unit_name from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id  where pv.id=" . $row['product_variant_id'] . " GROUP BY pv.id";
                    $db->sql($sql);
                    $res[$i]['item'] = $db->getResult();
                    for ($k = 0; $k < count($res[$i]['item']); $k++) {
                        $res[$i]['item'][$k]['is_item_deliverable'] = false;
                        if (!empty($pincode_id)) {
                            $pincodes = ($res[$i]['item'][$k]['d_type'] == "all") ? "" : $res[$i]['item'][$k]['pincodes'];
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


                        $res[$i]['item'][$k]['other_images'] = json_decode($res[$i]['item'][$k]['other_images']);
                        $res[$i]['item'][$k]['other_images'] = empty($res[$i]['item'][$k]['other_images']) ? array() : $res[$i]['item'][$k]['other_images'];
                        $result[$x]['item'][$z]['tax_percentage'] = (empty($result[$x]['item'][$z]['tax_percentage']) or is_null($result[$x]['item'][$z]['tax_percentage'])) ? "0" :  $result[$x]['item'][$z]['tax_percentage'];
                        $res[$i]['item'][$k]['tax_title'] = empty($res[$i]['item'][$k]['tax_title']) ? "" : $res[$i]['item'][$k]['tax_title'];
                        if ($res[$i]['item'][$k]['stock'] <= 0 || $res[$i]['item'][$k]['serve_for'] == 'Sold Out') {
                            $res[$i]['item'][$k]['isAvailable'] = false;
                            $ready_to_add = true;
                        } else {
                            $res[$i]['item'][$k]['isAvailable'] = true;
                        }
                        for ($l = 0; $l < count($res[$i]['item'][$k]['other_images']); $l++) {
                            $other_images = DOMAIN_URL . $res[$i]['item'][$k]['other_images'][$l];
                            $res[$i]['item'][$k]['other_images'][$l] = $other_images;
                        }
                    }
                    for ($j = 0; $j < count($res[$i]['item']); $j++) {
                        $res[$i]['item'][$j]['image'] = !empty($res[$i]['item'][$j]['image']) ? DOMAIN_URL . $res[$i]['item'][$j]['image'] : "";
                        $res[$i]['item'][$j]['size_chart'] = !empty($res[$i]['item'][$j]['size_chart']) ? DOMAIN_URL . $res[$i]['item'][$j]['size_chart'] : "";
                    }
                    $i++;
                }

                $sql = "select * from cart where save_for_later = 1 AND user_id=" . $user_id . " ORDER BY date_created DESC ";
                $db->sql($sql);
                $result = $db->getResult();

                $sql = "select qty,product_variant_id from cart where save_for_later = 1 AND user_id=" . $user_id;
                $db->sql($sql);
                $res1 = $db->getResult();

                foreach ($res1 as $row_1) {
                    $sql = "select price,discounted_price from product_variant where id=" . $row_1['product_variant_id'];
                    $db->sql($sql);
                    $result_1 = $db->getResult();
                    $taxed_amout = $fn->get_taxabled_amount($row_1['product_variant_id']);
                    foreach ($result_1 as $result_2) {
                        $price = $taxed_amout[0]['taxable_amount'] * $row_1['qty'];
                    }
                }
                foreach ($result as $rows) {
                    $sql = "select pv.*,p.name,p.type as d_type,p.cod_allowed,p.slug,p.image,p.other_images,t.percentage as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id where pv.id=" . $rows['product_variant_id'] . " GROUP BY pv.id";
                    $db->sql($sql);
                    $result[$x]['item'] = $db->getResult();

                    for ($z = 0; $z < count($result[$x]['item']); $z++) {
                        $variant_images = str_replace("'", '"', $result[$x]['item'][$z]['images']);
                        $result[$x]['item'][$z]['images'] = json_decode($variant_images, 1);
                        $result[$x]['item'][$z]['images'] = (empty($result[$x]['item'][$z]['images'])) ? array() : $result[$x]['item'][$z]['images'];

                        for ($j = 0; $j < count($result[$x]['item'][$z]['images']); $j++) {
                            $result[$x]['item'][$z]['images'][$j] = !empty(DOMAIN_URL . $result[$x]['item'][$z]['images'][$j]) ? DOMAIN_URL . $result[$x]['item'][$z]['images'][$j] : "";
                        }

                        $result[$x]['item'][$z]['is_item_deliverable'] = false;
                        $result[$x]['item'][$z]['other_images'] = json_decode($result[$x]['item'][$z]['other_images']);
                        $result[$x]['item'][$z]['other_images'] = empty($result[$x]['item'][$z]['other_images']) ? array() : $result[$x]['item'][$z]['other_images'];
                        $result[$x]['item'][$z]['tax_percentage'] = (empty($result[$x]['item'][$z]['tax_percentage']) or is_null($result[$x]['item'][$z]['tax_percentage'])) ? "0" :  $result[$x]['item'][$z]['tax_percentage'];
                        $result[$x]['item'][$z]['tax_title'] = empty($result[$x]['item'][$z]['tax_title']) ? "" : $result[$x]['item'][$z]['tax_title'];

                        if ($result[$x]['item'][$z]['stock'] <= 0 || $result[$x]['item'][$z]['serve_for'] == 'Sold Out') {
                            $result[$x]['item'][$z]['isAvailable'] = false;
                            $ready_to_add = true;
                        } else {
                            $result[$x]['item'][$z]['isAvailable'] = true;
                        }

                        for ($y = 0; $y < count($result[$x]['item'][$z]['other_images']); $y++) {
                            $other_images = DOMAIN_URL . $result[$x]['item'][$z]['other_images'][$y];
                            $result[$x]['item'][$z]['other_images'][$y] = $other_images;
                        }
                    }
                    for ($j = 0; $j < count($result[$x]['item']); $j++) {
                        $result[$x]['item'][$j]['image'] = !empty($result[$x]['item'][$j]['image']) ? DOMAIN_URL . $result[$x]['item'][$j]['image'] : "";
                    }
                    $x++;
                }
            }

            if ($shipping_type == 'standard') {
                $parcels = $fn->make_shipping_parcels($res);
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
                $delivery_charge_parcels = $fn->check_parcels_deliveriblity($parcels, $user_pincode, $is_code);

                $delivery_charage_by_item = $delivery_charge_parcels['delivery_charge_with_cod'];
                $delivery_charage_by_item_without_cod = $delivery_charge_parcels['delivery_charge_without_cod'];
            } else {
                $check_address = $fn->get_data(['area_id'], "id=$address_id", 'user_addresses');
                if (isset($_POST['address_id']) && !empty($_POST['address_id'])) {
                    if ($check_address[0]['area_id'] == 0) {
                        $response['error'] = true;
                        $response['message'] = "Sorry , we cannot delivered on this address";
                        print_r(json_encode($response));
                        return false;
                    }
                }

                $delivery_charage_by_item = $fn->get_delivery_charge($address_id, $total_amount);
            }

            if (!empty($res) || !empty($result)) {
                $response['error'] = false;
                if (!empty($type) && $type == 'delivery_charge') {
                    $response['delivery_charge'] = "$delivery_charage_by_item";
                } else {
                    $sub_total = $total_amount;
                    $saved_amount =  $save_price -  $total_amount;

                    $response['total'] = $total[0]['total'];
                    $response['ready_to_cart'] = $ready_to_add;
                    $response['total_amount'] = "$total_amount";
                    $response['sub_total'] = "$sub_total";
                    $response['overall_amount'] = "$save_price";
                    $response['saved_amount'] = ($saved_amount <= 0) ? "0" : "$saved_amount";
                    $response['delivery_charge_with_cod'] = "$delivery_charage_by_item";
                    $response['delivery_charge_without_cod'] = "$delivery_charage_by_item_without_cod";
                    $response['message'] = 'Cart Data Retrived Successfully!';
                    $response['data'] = array_values($res);
                    $response['save_for_later'] = array_values($result);
                }
            } else {
                $response['error'] = true;
                $response['message'] = "No item(s) found in users cart!";
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No item(s) found in user cart!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}


/*
5.add_to_save_for_later
    accesskey:90336
    add_to_save_for_later:1
    user_id:3
    product_id:1
    product_variant_id:4
    qty:2
*/
if ((isset($_POST['add_to_save_for_later'])) && ($_POST['add_to_save_for_later'] == 1)) {
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $product_id = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_id'])) : "";
    $product_variant_id  = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
    $qty = (isset($_POST['qty']) && !empty($_POST['qty'])) ? $db->escapeString($fn->xss_clean_array($_POST['qty'])) : "";
    if (!empty($user_id) && !empty($product_id)) {
        if (!empty($product_variant_id)) {
            if ($fn->is_item_available($product_id, $product_variant_id)) {
                if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {
                    /* if item found in user's cart update it */
                    if (empty($qty) || $qty == 0) {
                        $sql = "DELETE FROM cart WHERE user_id = $user_id AND product_variant_id = $product_variant_id";
                        if ($db->sql($sql)) {
                            $response['error'] = false;
                            $response['message'] = 'Item removed users cart due to 0 quantity';
                        } else {
                            $response['error'] = true;
                            $response['message'] = 'Something went wrong please try again!';
                        }
                        print_r(json_encode($response));
                        return false;
                    }
                    $data = array(
                        'qty' => $qty,
                        'save_for_later' => 1
                    );

                    echo $sql1 = "UPDATE cart SET save_for_later = 1 , qty = $qty WHERE user_id = $user_id AND product_variant_id = $product_variant_id";
                    if ($db->sql($sql1)) {
                        $x = 0;
                        $total_amount = 0;

                        $sql = "select * from cart where save_for_later = 1 AND user_id=" . $user_id . " AND product_variant_id = " . $product_variant_id . "";
                        $db->sql($sql);
                        $result = $db->getResult();

                        $sql = "select qty,product_variant_id from cart where save_for_later = 1 AND user_id=" . $user_id;
                        $db->sql($sql);
                        $res1 = $db->getResult();

                        foreach ($res1 as $row1) {
                            $sql = "select price,discounted_price from product_variant where id=" . $row1['product_variant_id'];
                            $db->sql($sql);
                            $result1 = $db->getResult();
                            foreach ($result1 as $result2) {
                                $price = $result2['discounted_price'] == 0 ? $result2['price'] * $row_1['qty'] : $result2['discounted_price'] * $row1['qty'];
                            }
                            $total_amount += $price;
                        }

                        foreach ($result as $rows) {
                            $sql = "select pv.*,p.name,p.type as d_type,p.cod_allowed,p.slug,p.image,p.other_images,t.percentage as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id where pv.id=" . $rows['product_variant_id'] . " GROUP BY pv.id";
                            $db->sql($sql);
                            $result[$x]['item'] = $db->getResult();

                            for ($z = 0; $z < count($result[$x]['item']); $z++) {

                                $variant_images = str_replace("'", '"', $res[$i]['item'][$k]['images']);
                                $res[$i]['item'][$k]['images'] = json_decode($variant_images, 1);
                                $res[$i]['item'][$k]['images'] = (empty($res[$i]['item'][$k]['images'])) ? array() : $res[$i]['item'][$k]['images'];

                                for ($j = 0; $j < count($res[$i]['item'][$k]['images']); $j++) {
                                    $res[$i]['item'][$k]['images'][$j] = !empty(DOMAIN_URL . $res[$i]['item'][$k]['images'][$j]) ? DOMAIN_URL . $res[$i]['item'][$k]['images'][$j] : "";
                                }

                                $result[$x]['item'][$z]['is_item_deliverable'] = '';
                                $result[$x]['item'][$z]['other_images'] = json_decode($result[$x]['item'][$z]['other_images']);
                                $result[$x]['item'][$z]['other_images'] = empty($result[$x]['item'][$z]['other_images']) ? array() : $result[$x]['item'][$z]['other_images'];
                                $result[$x]['item'][$z]['tax_percentage'] = empty($result[$x]['item'][$z]['tax_percentage']) ? "0" : $result[$x]['item'][$z]['tax_percentage'];
                                $result[$x]['item'][$z]['tax_title'] = empty($result[$x]['item'][$z]['tax_title']) ? "" : $result[$x]['item'][$z]['tax_title'];

                                if ($result[$x]['item'][$z]['stock'] <= 0 || $result[$x]['item'][$z]['serve_for'] == 'Sold Out') {
                                    $result[$x]['item'][$z]['isAvailable'] = false;
                                    $ready_to_add = true;
                                } else {
                                    $result[$x]['item'][$z]['isAvailable'] = true;
                                }

                                for ($y = 0; $y < count($result[$x]['item'][$z]['other_images']); $y++) {
                                    $other_images = DOMAIN_URL . $result[$x]['item'][$z]['other_images'][$y];
                                    $result[$x]['item'][$z]['other_images'][$y] = $other_images;
                                }
                            }
                            for ($j = 0; $j < count($result[$x]['item']); $j++) {
                                $result[$x]['item'][$j]['image'] = !empty($result[$x]['item'][$j]['image']) ? DOMAIN_URL . $result[$x]['item'][$j]['image'] : "";
                            }
                            $x++;
                        }

                        $response['error'] = false;
                        $response['message'] = 'Item added to save for later successfully!!';
                        $response['data'] = $result;
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Something went wrong please try again!';
                    }
                } else {

                    /* if item not found in user's cart add it */
                    $data = array(
                        'user_id' => $user_id,
                        'product_id' => $product_id,
                        'product_variant_id' => $product_variant_id,
                        'qty' => $qty,
                        'save_for_later' => 1
                    );
                    $sql = "INSERT INTO `cart`(`user_id`, `product_id`, `product_variant_id`, `qty`, `save_for_later`) VALUES ('$user_id','$product_id','$product_variant_id','$qty','1')";
                    if ($db->sql($sql)) {

                        $x = 0;
                        $total_amount = 0;

                        $sql = "select * from cart where save_for_later = 1 AND user_id=" . $user_id . " AND product_variant_id = " . $product_variant_id . "";
                        $db->sql($sql);
                        $result = $db->getResult();

                        $sql = "select qty,product_variant_id from cart where save_for_later = 1 AND user_id=" . $user_id;
                        $db->sql($sql);
                        $res1 = $db->getResult();

                        foreach ($res1 as $row1) {
                            $sql = "select price,discounted_price from product_variant where id=" . $row1['product_variant_id'];
                            $db->sql($sql);
                            $result1 = $db->getResult();
                            foreach ($result1 as $result2) {
                                $price = $result2['discounted_price'] == 0 ? $result2['price'] * $row_1['qty'] : $result2['discounted_price'] * $row1['qty'];
                            }
                            $total_amount += $price;
                        }

                        foreach ($result as $rows) {
                            $sql = "select pv.*,p.name,p.type as d_type,p.cod_allowed,p.slug,p.image,p.other_images,t.percentage as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id where pv.id=" . $rows['product_variant_id'] . " GROUP BY pv.id";
                            $db->sql($sql);
                            $result[$x]['item'] = $db->getResult();

                            for ($z = 0; $z < count($result[$x]['item']); $z++) {
                                $result[$x]['item'][$z]['is_item_deliverable'] = '';
                                $result[$x]['item'][$z]['other_images'] = json_decode($result[$x]['item'][$z]['other_images']);
                                $result[$x]['item'][$z]['other_images'] = empty($result[$x]['item'][$z]['other_images']) ? array() : $result[$x]['item'][$z]['other_images'];
                                $result[$x]['item'][$z]['tax_percentage'] = empty($result[$x]['item'][$z]['tax_percentage']) ? "0" : $result[$x]['item'][$z]['tax_percentage'];
                                $result[$x]['item'][$z]['tax_title'] = empty($result[$x]['item'][$z]['tax_title']) ? "" : $result[$x]['item'][$z]['tax_title'];

                                if ($result[$x]['item'][$z]['stock'] <= 0 || $result[$x]['item'][$z]['serve_for'] == 'Sold Out') {
                                    $result[$x]['item'][$z]['isAvailable'] = false;
                                    $ready_to_add = true;
                                } else {
                                    $result[$x]['item'][$z]['isAvailable'] = true;
                                }

                                for ($y = 0; $y < count($result[$x]['item'][$z]['other_images']); $y++) {
                                    $other_images = DOMAIN_URL . $result[$x]['item'][$z]['other_images'][$y];
                                    $result[$x]['item'][$z]['other_images'][$y] = $other_images;
                                }
                            }
                            for ($j = 0; $j < count($result[$x]['item']); $j++) {
                                $result[$x]['item'][$j]['image'] = !empty($result[$x]['item'][$j]['image']) ? DOMAIN_URL . $result[$x]['item'][$j]['image'] : "";
                            }
                            $x++;
                        }

                        $response['error'] = false;
                        $response['message'] = 'Item added to save for later successfully';
                        $response['data'] = $result;
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Something went wrong please try again!';
                    }
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'No such item available!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Please choose atleast one item!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}
