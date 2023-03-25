<?php
include_once('../includes/functions.php');
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
// include_once('includes/crud.php');
$function = new Functions;
// $db = new Database();
if (isset($_GET['id'])) {
    $ID = $db->escapeString($fn->xss_clean($_GET['id']));
} else {
    // $ID = "";
    return false;
    exit(0);
}

$shippin_type = ($fn->get_settings('local_shipping') == 1) ? 'local' : 'standard';

$currentTime = time() + 25200;
$expired = 3600;

if (!isset($_SESSION['seller_id']) && !isset($_SESSION['seller_name'])) {
    header("location:index.php");
} else {
    $seller_id = $_SESSION['seller_id'];
}

if ($currentTime > $_SESSION['timeout']) {
    session_destroy();
    header("location:index.php");
}

// destroy previous session timeout and create new one
unset($_SESSION['timeout']);
$_SESSION['timeout'] = $currentTime + $expired;

$sql = "SELECT * FROM `products` WHERE seller_id = $seller_id AND id='$ID'";
$db->sql($sql);
$seller_products = $db->getResult();
if (empty($seller_products)) {
    echo '<label class="alert alert-danger">No products available!.</label>';
    return false;
}

$sql = "SELECT categories FROM seller WHERE id = " . $seller_id;
$db->sql($sql);
$cate_res = $db->getResult();
$category_ids = explode(',', $cate_res[0]['categories']);
$category_id = implode(',', $category_ids);

if (empty($where)) {
    $where = " WHERE id IN($category_id)";
}

$sql = "SELECT * FROM `category` " . $where . " ORDER BY id ASC ";
$db->sql($sql);
$cate_data = $db->getResult();

// create array variable to store category data
$category_data = array();
$product_status = "";

$sql = "select * from subcategory";
$db->sql($sql);
$subcategory = $db->getResult();

$sql = "SELECT image, other_images FROM products WHERE id ='$ID'";
$db->sql($sql);
$res = $db->getResult();
foreach ($res as $row) {
    $previous_menu_image = $row['image'];
    $other_images = $row['other_images'];
}


if (isset($_POST['btnEdit'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($_POST['type'] == "loose") {
        for ($i = 0; $i < count($_POST['weight_loose']); $i++) {
            if ($_POST['weight_loose'][$i] == 0) {
                $error['weight_loose'] = "<label class='alert alert-danger'>Weight should me greater then 0.</label>";
            }
            break;
        }
        for ($i = 0; $i < count($_POST['height_loose']); $i++) {
            if ($_POST['height_loose'][$i] == 0) {
                $error['height_loose'] = "<label class='alert alert-danger'>height should me greater then 0.</label>";
            }
            break;
        }
        for ($i = 0; $i < count($_POST['breadth_loose']); $i++) {
            if ($_POST['breadth_loose'][$i] == 0) {
                $error['breadth_loose'] = "<label class='alert alert-danger'>breadth should me greater then 0.</label>";
            }
            break;
        }
        for ($i = 0; $i < count($_POST['lenght_loose']); $i++) {
            if ($_POST['lenght_loose'][$i] == 0) {
                $error['lenght_loose'] = "<label class='alert alert-danger'>length should me greater then 0.</label>";
            }
            break;
        }
    } else {
        for ($i = 0; $i < count($_POST['weight']); $i++) {
            if ($_POST['weight'][$i] == 0) {
                $error['weight'] = "<label class='alert alert-danger'>Weight should me greater then 0.</label>";
            }
            break;
        }
        for ($i = 0; $i < count($_POST['height']); $i++) {
            if ($_POST['height'][$i] == 0) {
                $error['height'] = "<label class='alert alert-danger'>height should me greater then 0.</label>";
            }
            break;
        }
        for ($i = 0; $i < count($_POST['breadth']); $i++) {
            if ($_POST['breadth'][$i] == 0) {
                $error['breadth'] = "<label class='alert alert-danger'>breadth should me greater then 0.</label>";
            }
            break;
        }
        for ($i = 0; $i < count($_POST['lenght']); $i++) {
            if ($_POST['length'][$i] == 0) {
                $error['length'] = "<label class='alert alert-danger'>length should me greater then 0.</label>";
            }
            break;
        }
    }


    // if ($permissions['products']['update'] == 1) {
    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    if (strpos($name, '-') !== false) {
        $temp = (explode("-", $name)[1]);
    } else {
        $temp = $name;
    }

    $slug = $function->slugify($temp);
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $sql = "SELECT slug FROM products where id!=" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    $i = 1;
    foreach ($res as $row) {
        if ($slug == $row['slug']) {
            $slug = $slug . '-' . $i;
            $i++;
        }
    }
    $error = array();
    if ($_POST['shipping_type'] == 'standard') {
        $pincodes = '';
        $standard_shipping = 1;
        $pickup_location = (isset($_POST['pickup_location']) && !empty($_POST['pickup_location'])) ? $db->escapeString($fn->xss_clean($_POST['pickup_location'])) : 0;
    } else {
        $standard_shipping = 0;
        $pickup_location = 0;
        if ($pincode_type == "all") {
            $pincode_ids = NULL;
            $pickup_location = "";
        } else {
            $standard_shipping = 0;
            $pickup_location = NULL;
            $pincode_type = (isset($_POST['product_pincodes']) && $_POST['product_pincodes'] != '') ? $db->escapeString($fn->xss_clean($_POST['product_pincodes'])) : "";
            if ($pincode_type == "all") {
                $pincode_ids = NULL;
            } else {
                if (empty($_POST['pincode_ids_exc'])) {
                    $error['pincode_ids_exc'] = "<label class='alert alert-danger'>Select pincodes!.</label>";
                } else {
                    $pincode_ids = $fn->xss_clean_array($_POST['pincode_ids_exc']);
                    $pincode_ids = implode(",", $pincode_ids);
                }
            }
        }
    }

    $weight = (isset($_POST['weight']) && !empty($_POST['weight'])) ? $db->escapeString($fn->xss_clean($_POST['weight'])) : 0;
    $length = (isset($_POST['length']) && !empty($_POST['length'])) ? $db->escapeString($fn->xss_clean($_POST['length'])) : 0;
    $breadth = (isset($_POST['breadth']) && !empty($_POST['breadth'])) ? $db->escapeString($fn->xss_clean($_POST['breadth'])) : 0;
    $height = (isset($_POST['height']) && !empty($_POST['height'])) ? $db->escapeString($fn->xss_clean($_POST['height'])) : 0;


    $subcategory_id = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : 0;
    $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
    $serve_for = $db->escapeString($fn->xss_clean($_POST['serve_for']));
    $description = $db->escapeString($fn->xss_clean($_POST['description']));
    $pr_status = $db->escapeString($fn->xss_clean($_POST['pr_status']));
    $manufacturer = (isset($_POST['manufacturer']) && $_POST['manufacturer'] != '') ? $db->escapeString($fn->xss_clean($_POST['manufacturer'])) : '';
    $made_in = (isset($_POST['made_in']) && $_POST['made_in'] != '') ? $db->escapeString($fn->xss_clean($_POST['made_in'])) : '';
    $indicator = (isset($_POST['indicator']) && $_POST['indicator'] != '') ? $db->escapeString($fn->xss_clean($_POST['indicator'])) : '0';
    $return_status = (isset($_POST['return_status']) && $_POST['return_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['return_status'])) : '0';
    $return_days = (isset($_POST['return_days']) && $_POST['return_days'] != '') ? $db->escapeString($fn->xss_clean($_POST['return_days'])) : 0;
    $cancelable_status = (isset($_POST['cancelable_status']) && $_POST['cancelable_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['cancelable_status'])) : '0';
    $till_status = (isset($_POST['till_status']) && $_POST['till_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['till_status'])) : '';

    $tax_id = (isset($_POST['tax_id']) && $_POST['tax_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['tax_id'])) : 0;
    $sql = "SELECT require_products_approval FROM seller WHERE id = " . $seller_id;
    $db->sql($sql);
    $res_approval = $db->getResult();
    $pr_approval = $res_approval[0]['require_products_approval'];
    $is_approved = $fn->get_data($columns = ['is_approved'], 'id=' . $id, 'products');
    $is_approved = $is_approved[0]['is_approved'];
    $is_approved = ($pr_approval == 0) ? 1 : $is_approved;
    $is_cod_allowed = (isset($_POST['is_cod_allowed']) && $_POST['is_cod_allowed'] != '') ? $db->escapeString($fn->xss_clean($_POST['is_cod_allowed'])) : 1;
    $total_allowed_quantity = (isset($_POST['max_allowed_quantity']) && $_POST['max_allowed_quantity'] != '') ? $db->escapeString($fn->xss_clean($_POST['max_allowed_quantity'])) : '';
    // get image info
    $image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
    $image_error = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
    $image_type = $db->escapeString($fn->xss_clean($_FILES['image']['type']));
    $error = array();

    if (empty($name)) {
        $error['name'] = " <span class='label label-danger'>!</span>";
    }
    if ($cancelable_status == 1 && $till_status == '') {
        $error['cancelable'] = " <span class='label label-danger'>!</span>";
    }

    if (empty($category_id)) {
        $error['category_id'] = " <span class='label label-danger'>!</span>";
    }
    if (empty($serve_for)) {
        $error['serve_for'] = " <span class='label label-danger'>Not choosen</span>";
    }

    if (empty($description)) {
        $error['description'] = " <span class='label label-danger'>!</span>";
    }

    // common image file extensions
    $allowedExts = array("gif", "jpeg", "jpg", "png");

    // get image file extension
    error_reporting(E_ERROR | E_PARSE);
    $extension = end(explode(".", $_FILES["image"]["name"]));

    if (!empty($image)) {
        $result = $fn->validate_image($_FILES["image"]);
        if (!$result) {
            $error['image'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
        }
    }
    /*updating other_images if any*/

    if (isset($_FILES['other_images']) && ($_FILES['other_images']['size'][0] > 0)) {
        $file_data = array();
        $target_path = '../upload/other_images/';
        $target_path1 = 'upload/other_images/';
        for ($i = 0; $i < count($_FILES["other_images"]["name"]); $i++) {
            if ($_FILES["other_images"]["error"][$i] > 0) {
                $error['other_images'] = " <span class='label label-danger'>Images not uploaded!</span>";
            } else {
                $result = $fn->validate_other_images($_FILES["other_images"]["tmp_name"][$i], $_FILES["other_images"]["type"][$i]);
                if (!$result) {
                    $error['other_images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                }
            }
            $filename = $_FILES["other_images"]["name"][$i];
            $temp = explode('.', $filename);
            $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
            $file_data[] = $target_path1 . '' . $filename;
            if (!move_uploaded_file($_FILES["other_images"]["tmp_name"][$i], $target_path . '' . $filename))
                echo "{$_FILES['image']['name'][$i]} not uploaded<br/>";
        }
        if (!empty($other_images) && $other_images != 'null') {
            $arr_old_images = json_decode($other_images);
            $all_images = array_merge($arr_old_images, $file_data);
            $all_images = json_encode(array_values($all_images));
        } else {
            $all_images = json_encode($file_data);
        }
        if (empty($error)) {
            $sql = "update `products` set `other_images`='" . $all_images . "' where `id`= '$ID' ";
            $db->sql($sql);
        }
    }
    if (!empty($name) && !empty($category_id) &&  !empty($serve_for) && !empty($description) && empty($error['cancelable']) && empty($error)) {
        if (strpos($name, "'") !== false) {
            $name = str_replace("'", "''", "$name");
            if (strpos($description, "'") !== false)
                $description = str_replace("'", "''", "$description");
        }
        if (!empty($image)) {
            // create random image file name
            $string = '0123456789';
            $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);
            $function = new functions;
            $image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;
            // delete previous image
            $delete = unlink("$previous_menu_image");
            // upload new image
            $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../upload/images/' . $image);

            $upload_image = 'upload/images/' . $image;



            $sql_query = "UPDATE products SET name = '$name' ,is_approved= '$is_approved',type= '$pincode_type',pincodes = '$pincode_ids',tax_id = '$tax_id' ,seller_id = '$seller_id' ,slug = '$slug' , subcategory_id = '$subcategory_id', image = '$upload_image', description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status', return_days = '$return_days', cancelable_status = '$cancelable_status', till_status = '$till_status',`status` = $pr_status,`cod_allowed` = $is_cod_allowed,`total_allowed_quantity`= $total_allowed_quantity,`standard_shipping`=$standard_shipping,`pickup_location`='$pickup_location' WHERE id = $ID";
            $db->sql($sql_query);
        } else if ($pincode_type != "") {
            $sql_query = "UPDATE products SET name = '$name' ,is_approved= '$is_approved',type= '$pincode_type',pincodes = '$pincode_ids',tax_id = '$tax_id' ,seller_id = '$seller_id' ,slug = '$slug' ,category_id = '$category_id' ,subcategory_id = '$subcategory_id' ,description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status',return_days = '$return_days', cancelable_status = '$cancelable_status', till_status = '$till_status' ,`status` = $pr_status,`cod_allowed` = $is_cod_allowed,`total_allowed_quantity`= $total_allowed_quantity,`standard_shipping`=$standard_shipping,`pickup_location`='$pickup_location' WHERE id = $ID";
            $db->sql($sql_query);
        } else {
            $sql_query = "UPDATE products SET name = '$name' ,is_approved= '$is_approved',tax_id = '$tax_id' ,seller_id = '$seller_id' ,slug = '$slug' ,category_id = '$category_id' ,subcategory_id = '$subcategory_id', description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status',return_days = '$return_days', cancelable_status = '$cancelable_status', till_status = '$till_status' ,`status` = $pr_status,`cod_allowed` = $is_cod_allowed,`total_allowed_quantity`= $total_allowed_quantity,`standard_shipping`=$standard_shipping,`pickup_location`='$pickup_location' WHERE id = $ID";
            $db->sql($sql_query);
        }


        // echo $sql_query; return false;
        $db->sql($sql_query);
        $res = $db->getResult();
        $product_variant_id = $db->escapeString($fn->xss_clean($_POST['product_variant_id']));
        if (isset($_POST['loose_measurement']) && isset($_POST['packate_measurement']) && $_POST['loose_measurement'] != 0 && $_POST['packate_measurement'] != 0 && $_POST['packate_measurement'] < $_POST['loose_measurement']) {
            $count = count($_POST['loose_measurement']);
        } else {
            $count = count($_POST['packate_measurement']);
        }


        for ($i = 0; $i < $count; $i++) {

            $vari_image = $fn->get_data($columns = ['id', 'images'], 'id=' . $fn->xss_clean($_POST['product_variant_id'][$i]), 'product_variant');
            $previous_variant_other_image = $vari_image[0]['images'];

            if ($_POST['type'] == "packet") {
                $stock = $db->escapeString($fn->xss_clean($_POST['packate_stock'][$i]));
                $serve_for = ($stock == 0 || $stock <= 0) ? 'Sold Out' : $db->escapeString($fn->xss_clean($_POST['packate_serve_for'][$i]));
                $all_images = '';
                /*updating variant images if any*/
                if (isset($_FILES['packet_variant_images']) && ($_FILES['packet_variant_images']['size'][$i][0] > 0)) {

                    $vari_id = $fn->xss_clean($_POST['product_variant_id'][$i]);

                    $file_data = array();
                    $target_path1 = 'upload/variant_images/';
                    if (!is_dir($target_path1)) {
                        mkdir($target_path1, 0777, true);
                    }

                    for ($k = 0; $k < count($_FILES["packet_variant_images"]["name"][$i]); $k++) {
                        if ($_FILES["packet_variant_images"]["error"][$i][$k] > 0) {
                            $error['packet_variant_images'] = " <span class='label label-danger'>Images not uploaded!</span>";
                        } else {
                            $result = $fn->validate_other_images($_FILES["packet_variant_images"]["tmp_name"][$i][$k], $_FILES["packet_variant_images"]["type"][$i][$k]);
                            if ($result) {
                                $error['packet_variant_images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                            }
                        }
                        $filename = $_FILES["packet_variant_images"]["name"][$i][$k];
                        $temp = explode('.', $filename);
                        $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                        $file_data[] = $target_path1 . '' . $filename;

                        if (!move_uploaded_file($_FILES["packet_variant_images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                            echo "{$_FILES['packet_variant_images']['name'][$i][$k]} not uploaded<br/>";
                    }

                    if (!empty($previous_variant_other_image) && $previous_variant_other_image != 'null') {
                        $variant_images = str_replace("'", '"', $previous_variant_other_image);
                        $arr_old_images = json_decode($variant_images);
                        $all_images = array_merge($arr_old_images, $file_data);
                        $all_images = json_encode(array_values($all_images));
                    } else {
                        $all_images = $db->escapeString(json_encode($file_data));
                    }

                    $sql = "update `product_variant` set `images`='" . $all_images . "' where `id`=" . $vari_id;
                    $db->sql($sql);
                }

                $weight = $db->escapeString($fn->xss_clean($_POST['weight'][$i]));
                $height = $db->escapeString($fn->xss_clean($_POST['height'][$i]));
                $length = $db->escapeString($fn->xss_clean($_POST['length'][$i]));
                $breadth = $db->escapeString($fn->xss_clean($_POST['breadth'][$i]));

                $data = array(
                    'type' => $db->escapeString($fn->xss_clean($_POST['type'])),
                    'measurement' => $db->escapeString($fn->xss_clean($_POST['packate_measurement'][$i])),
                    'measurement_unit_id' => $db->escapeString($fn->xss_clean($_POST['packate_measurement_unit_id'][$i])),
                    'price' => $db->escapeString($fn->xss_clean($_POST['packate_price'][$i])),
                    'discounted_price' => $db->escapeString($fn->xss_clean($_POST['packate_discounted_price'][$i])),
                    'stock' => $stock,
                    'weight' => $weight,
                    'height' => $height,
                    'length' => $length,
                    'breadth' => $breadth,
                    'stock_unit_id' => $db->escapeString($fn->xss_clean($_POST['packate_stock_unit_id'][$i])),
                    'serve_for' => $serve_for,
                );



                $db->update('product_variant', $data, 'id=' . $fn->xss_clean($_POST['product_variant_id'][$i]));
                $res = $db->getResult();
            } else if ($_POST['type'] == "loose") {



                $stock = $db->escapeString($fn->xss_clean($_POST['loose_stock']));
                $serve_for = ($stock == 0 || $stock <= 0) ? 'Sold Out' : $db->escapeString($fn->xss_clean($_POST['serve_for']));
                $all_images = '';

                /*updating variant images if any*/
                if (isset($_FILES['loose_variant_images']) && ($_FILES['loose_variant_images']['size'][$i][0] > 0)) {
                    $vari_ids = $fn->xss_clean($_POST['product_variant_id'][$i]);
                    $file_data = array();
                    $target_path1 = 'upload/variant_images/';
                    if (!is_dir($target_path1)) {
                        mkdir($target_path1, 0777, true);
                    }

                    for ($k = 0; $k < count($_FILES["loose_variant_images"]["name"][$i]); $k++) {

                        if ($_FILES["loose_variant_images"]["error"][$i][$k] > 0) {
                            $error['loose_variant_images'] = " <span class='label label-danger'>Images not uploaded!</span>";
                        } else {
                            $result = $fn->validate_other_images($_FILES["loose_variant_images"]["tmp_name"][$i][$k], $_FILES["loose_variant_images"]["type"][$i][$k]);
                            if ($result) {
                                $error['loose_variant_images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                            }
                        }
                        $filename = $_FILES["loose_variant_images"]["name"][$i][$k];
                        $temp = explode('.', $filename);

                        $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                        $file_data[] = $target_path1 . '' . $filename;

                        if (!move_uploaded_file($_FILES["loose_variant_images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                            echo "{$_FILES['loose_variant_images']['name'][$i][$k]} not uploaded<br/>";
                    }
                    if (!empty($previous_variant_other_image)) {
                        $variant_images = str_replace("'", '"', $previous_variant_other_image);
                        $arr_old_images = json_decode($variant_images);
                        $all_images = array_merge($arr_old_images, $file_data);
                        $all_images = json_encode(array_values($all_images));
                    } else {
                        $all_images = $db->escapeString(json_encode($file_data));
                    }

                    $sql = "update `product_variant` set `images`='" . $all_images . "' where `id`=" . $vari_ids;
                    $db->sql($sql);
                }
                $weight = $db->escapeString($fn->xss_clean($_POST['weight_loose'][$i]));
                $height = $db->escapeString($fn->xss_clean($_POST['height_loose'][$i]));
                $length = $db->escapeString($fn->xss_clean($_POST['length_loose'][$i]));
                $breadth = $db->escapeString($fn->xss_clean($_POST['breadth_loose'][$i]));



                $data = array(
                    'type' => $db->escapeString($fn->xss_clean($_POST['type'])),
                    'measurement' => $db->escapeString($fn->xss_clean($_POST['loose_measurement'][$i])),
                    'measurement_unit_id' => $db->escapeString($fn->xss_clean($_POST['loose_measurement_unit_id'][$i])),
                    'price' => $db->escapeString($fn->xss_clean($_POST['loose_price'][$i])),
                    'discounted_price' => $db->escapeString($fn->xss_clean($_POST['loose_discounted_price'][$i])),
                    'stock' => $stock,
                    'weight' => $weight,
                    'height' => $height,
                    'length' => $length,
                    'breadth' => $breadth,
                    'stock_unit_id' => $db->escapeString($fn->xss_clean($_POST['loose_stock_unit_id'])),
                    'serve_for' => $serve_for,
                );
                // print_r($data);

                $db->update('product_variant', $data, 'id=' . $fn->xss_clean($_POST['product_variant_id'][$i]));
                $res = $db->getResult();
            }
        }
        if (
            isset($_POST['insert_packate_measurement']) && isset($_POST['insert_packate_measurement_unit_id'])
            && isset($_POST['insert_packate_price']) && isset($_POST['insert_packate_discounted_price'])
            && isset($_POST['insert_packate_stock']) && isset($_POST['insert_packate_stock_unit_id'])
        ) {
            $insert_packate_measurement = $db->escapeString($fn->xss_clean($_POST['insert_packate_measurement']));
            for ($i = 0; $i < count($_POST['insert_packate_measurement']); $i++) {
                $stock = $db->escapeString($fn->xss_clean($_POST['insert_packate_stock'][$i]));
                $serve_for = ($stock == 0 || $stock <= 0) ? 'Sold Out' : $db->escapeString($fn->xss_clean($_POST['insert_packate_serve_for'][$i]));
                $variant_images = '';

                if ($_FILES["insert_packet_variant_images"]["error"][$i][0] == 0) {
                    for ($j = 0; $j < count($_FILES["insert_packet_variant_images"]["name"][$i]); $j++) {
                        if ($_FILES["insert_packet_variant_images"]["error"][$i][$j] > 0) {
                            $error['insert_packet_variant_images'] = " <span class='label label-danger'>Images not uploaded!</span>";
                        } else {
                            $result = $fn->validate_other_images($_FILES["insert_packet_variant_images"]["tmp_name"][$i][$j], $_FILES["insert_packet_variant_images"]["type"][$i][$j]);
                            if ($result) {
                                $error['insert_packet_variant_images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                            }
                        }
                    }
                }

                if ((isset($_FILES['insert_packet_variant_images'])) && (!empty($_FILES['insert_packet_variant_images']['name'][$i][0])) && ($_FILES['insert_packet_variant_images']['size'][$i][0] > 0)) {
                    $file_data = array();
                    $target_path1 = 'upload/variant_images/';
                    if (!is_dir($target_path1)) {
                        mkdir($target_path1, 0777, true);
                    }

                    for ($k = 0; $k < count($_FILES["insert_packet_variant_images"]["name"][$i]); $k++) {
                        $filename = $_FILES["insert_packet_variant_images"]["name"][$i][$k];
                        $temp = explode('.', $filename);
                        $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                        $file_data[] = $target_path1 . '' . $filename;
                        if (!move_uploaded_file($_FILES["insert_packet_variant_images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                            echo "{$_FILES['insert_packet_variant_images']['name'][$i][$k]} not uploaded<br/>";
                    }
                    $variant_images = json_encode($file_data);
                }


                $product_id = $db->escapeString($ID);
                $type = $db->escapeString($fn->xss_clean($_POST['type']));
                $measurement = $db->escapeString($fn->xss_clean($_POST['insert_packate_measurement'][$i]));
                $measurement_unit_id = $db->escapeString($fn->xss_clean($_POST['insert_packate_measurement_unit_id'][$i]));
                $price = $db->escapeString($fn->xss_clean($_POST['insert_packate_price'][$i]));
                $discounted_price = $db->escapeString($fn->xss_clean($_POST['insert_packate_discounted_price'][$i]));
                $stock = $stock;
                $stock_unit_id = $db->escapeString($fn->xss_clean($_POST['insert_packate_stock_unit_id'][$i]));
                $serve_for = $serve_for;
                $weight = $db->escapeString($fn->xss_clean($_POST['weight'][$i]));
                $height = $db->escapeString($fn->xss_clean($_POST['height'][$i]));
                $length = $db->escapeString($fn->xss_clean($_POST['length'][$i]));
                $breadth = $db->escapeString($fn->xss_clean($_POST['breadth'][$i]));
                $sql = "INSERT INTO product_variant (product_id,type,measurement,measurement_unit_id,price,discounted_price,serve_for,stock,stock_unit_id,images,weight,length,breadth,height) VALUES('$product_id','$type','$measurement','$measurement_unit_id','$price','$discounted_price','$serve_for','$stock','$stock_unit_id','$variant_other_images','$weight','$length','$breadth','$height')";
                $db->sql($sql);
            }
        }

        if (
            isset($_POST['insert_loose_measurement']) && isset($_POST['insert_loose_measurement_unit_id'])
            && isset($_POST['insert_loose_price']) && isset($_POST['insert_loose_discounted_price'])
        ) {
            $insert_loose_measurement = $db->escapeString($fn->xss_clean($_POST['insert_loose_measurement']));
            for ($i = 0; $i < count($_POST['insert_loose_measurement_unit_id']); $i++) {

                $file_data = '';
                $variant_images = '';

                if ($_FILES["insert_loose_variant_images"]["error"][$i][0] == 0) {
                    for ($j = 0; $j < count($_FILES["insert_loose_variant_images"]["name"][$i]); $j++) {
                        if ($_FILES["insert_loose_variant_images"]["error"][$i][$j] > 0) {
                            $error['insert_loose_variant_images'] = " <span class='label label-danger'>Images not uploaded!</span>";
                        } else {
                            $result = $fn->validate_other_images($_FILES["insert_loose_variant_images"]["tmp_name"][$i][$j], $_FILES["insert_loose_variant_images"]["type"][$i][$j]);
                            if ($result) {
                                $error['insert_loose_variant_images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                            }
                        }
                    }
                }
                if (isset($_FILES['insert_loose_variant_images']) && (!empty($_FILES['insert_loose_variant_images']['name'][$i][0])) && ($_FILES['insert_loose_variant_images']['size'][$i][0] > 0)) {
                    $file_data = array();
                    $target_path1 = 'upload/variant_images/';
                    if (!is_dir($target_path1)) {
                        mkdir($target_path1, 0777, true);
                    }
                    for ($k = 0; $k < count($_FILES["insert_loose_variant_images"]["name"][$i]); $k++) {
                        $filename = $_FILES["insert_loose_variant_images"]["name"][$i][$k];
                        $temp = explode('.', $filename);
                        $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                        $file_data[] = $target_path1 . '' . $filename;
                        if (!move_uploaded_file($_FILES["insert_loose_variant_images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                            echo "{$_FILES['insert_loose_variant_images']['name'][$i][$k]} not uploaded<br/>";
                    }
                    $variant_images = json_encode($file_data);
                }

                $product_id = $db->escapeString($ID);
                $type = $db->escapeString($fn->xss_clean($_POST['type']));
                $measurement = $db->escapeString($fn->xss_clean($_POST['insert_loose_measurement'][$i]));
                $measurement_unit_id = $db->escapeString($fn->xss_clean($_POST['insert_loose_measurement_unit_id'][$i]));
                $price = $db->escapeString($fn->xss_clean($_POST['insert_loose_price'][$i]));
                $discounted_price = $db->escapeString($fn->xss_clean($_POST['insert_loose_discounted_price'][$i]));
                $stock = $db->escapeString($fn->xss_clean($_POST['loose_stock']));
                $stock_unit_id = $db->escapeString($fn->xss_clean($_POST['loose_stock_unit_id']));
                $serve_for = $db->escapeString($fn->xss_clean($_POST['serve_for']));
                $weight = $db->escapeString($fn->xss_clean($_POST['weight_loose'][$i]));
                $height = $db->escapeString($fn->xss_clean($_POST['height_loose'][$i]));
                $length = $db->escapeString($fn->xss_clean($_POST['length_loose'][$i]));
                $breadth = $db->escapeString($fn->xss_clean($_POST['breadth_loose'][$i]));

                $sql = "INSERT INTO product_variant (product_id,type,measurement,measurement_unit_id,price,discounted_price,serve_for,stock,stock_unit_id,images,weight,length,breadth,height) VALUES('$product_id','$type','$measurement','$measurement_unit_id','$price','$discounted_price','$serve_for','$stock','$stock_unit_id','$variant_other_images','$weight','$length','$breadth','$height')";
                $db->sql($sql);
            }
        }
        $error['update_data'] = "<span class='label label-success'>Product updated Successfully</span>";
    }
} else {
    $error['check_permission'] = " <section class='content-header'><span class='alert alert-danger'>You have no permission to update product</span></section>";
}
// create array variable to store previous data
$data = array();
$sql_query = "SELECT p.*,p.type as d_type,v.*,v.id as product_variant_id,v.images AS variant_images FROM product_variant v JOIN products p ON p.id=v.product_id WHERE p.id=" . $ID;
$db->sql($sql_query);
$res = $db->getResult();
$product_status = $res[0]['status'];
foreach ($res as $row)
    $data = $row;
function isJSON($string)
{
    return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
}
?>
<section class="content-header">
    <h1>Edit Product <small><a href='products.php'> <i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Products</a></small></h1>
    <small><?php echo isset($error['lenght']) ? $error['lenght'] : ''; ?></small>
    <small><?php echo isset($error['height']) ? $error['height'] : ''; ?></small>
    <small><?php echo isset($error['breadth']) ? $error['breadth'] : ''; ?></small>
    <small><?php echo isset($error['weight']) ? $error['weight'] : ''; ?></small>
    <small><?php echo isset($error['lenght_loose']) ? $error['lenght_loose'] : ''; ?></small>
    <small><?php echo isset($error['height_loose']) ? $error['height_loose'] : ''; ?></small>
    <small><?php echo isset($error['breadth_loose']) ? $error['breadth_loose'] : ''; ?></small>
    <small><?php echo isset($error['weight_loose']) ? $error['weight_loose'] : ''; ?></small>
    <small><?php echo isset($error['update_data']) ? $error['update_data'] : ''; ?></small>
    <small><?php echo isset($error['update_data']) ? $error['update_data'] : ''; ?></small>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
    <br>
</section>
<section class="content">
    <!-- Main row -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Product</h3>
                </div>
                <div class="box-header">
                    <?php echo isset($error['cancelable']) ? '<span class="label label-danger">Till status is .</span>' : ''; ?>
                </div>
                <!-- form start -->
                <form id='edit_product_form' method="post" enctype="multipart/form-data">
                    <?php
                    $db->select('unit', '*');
                    $unit_data = $db->getResult();
                    $k = 0;
                    ?>
                    <div class="box-body">
                        <div class="form-group">
                            <div class='col-md-6'>
                                <label for="exampleInputEmail1">Product Name</label> <i class="text-danger asterik">*</i> <?php echo isset($error['name']) ? $error['name'] : ''; ?>
                                <input type="text" name="name" class="form-control" value="<?php echo $data['name']; ?>" />
                            </div>
                            <?php $db->sql("SET NAMES 'utf8'");
                            $sql = "SELECT * FROM `taxes` ORDER BY id DESC";
                            $db->sql($sql);
                            $taxes = $db->getResult();
                            ?>
                            <div class='col-md-6'>
                                <label class="control-label " for="taxes">Tax</label>
                                <select id='tax_id' name="tax_id" class='form-control'>
                                    <option value="">Select Tax</option>
                                    <?php foreach ($taxes as $tax) { ?>
                                        <option value='<?= $tax['id'] ?>' <?= ($data['tax_id'] == $tax['id']) ? 'selected' : ''; ?>><?= $tax['title'] . " - " . $tax['percentage'] . " %" ?></option>
                                    <?php } ?>
                                </select><br>
                            </div>
                        </div>
                        <div class='col-md-12'>
                            <div class="form-group">
                                <label for="type">Type</label><?php echo isset($error['type']) ? $error['type'] : ''; ?>
                                <label class="radio-inline"><input type="radio" name="type" id="packate" value="packet" <?= ($res[0]['type'] == "packet") ? "checked" : ""; ?>>Packet</label>
                                <label class="radio-inline"><input type="radio" name="type" id="loose" value="loose" <?= ($res[0]['type'] == "loose") ? "checked" : ""; ?>>Loose</label>
                            </div>
                        </div>
                        <!-- <br> -->
                        <div id="variations">
                            <?php
                            if (isJSON($data['price'])) {
                                $price = json_decode($data['price'], 1);
                                $measurement = json_decode($data['measurement'], 1);
                                $discounted_price = json_decode($data['discounted_price'], 1);
                            } else {
                                $price = array('0' => $data['price']);
                                $measurement = array('0' => $data['measurement']);
                                $discounted_price = array('0' => $data['discounted_price']);
                            }
                            $i = 0;
                            if ($res[0]['type'] == "packet") {
                                foreach ($res as $row) {
                            ?>
                                    <div class="row packate_div">
                                        <input type="hidden" class="form-control" name="product_variant_id[]" id="product_variant_id" value='<?= $row['product_variant_id']; ?>' />
                                        <div class="col-md-2">
                                            <div class="form-group packate_div">
                                                <label for="exampleInputEmail1">Measurement</label> <i class="text-danger asterik">*</i> <input type="number" step="any" min="0" class="form-control" name="packate_measurement[]" value='<?= $row['measurement']; ?>' />
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group packate_div">
                                                <label for="unit">Unit:</label>
                                                <select class="form-control" name="packate_measurement_unit_id[]">
                                                    <?php
                                                    foreach ($unit_data as  $unit) {
                                                        echo "<option";
                                                        if ($unit['id'] == $row['measurement_unit_id']) {
                                                            echo " selected ";
                                                        }
                                                        echo " value='" . $unit['id'] . "'>" . $unit['short_code'] . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group packate_div">
                                                <label for="price">Price (<?= $settings['currency'] ?>):</label> <i class="text-danger asterik">*</i> <input type="number" step="any" min="0" class="form-control" name="packate_price[]" id="packate_price" value='<?= $row['price']; ?>' />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group packate_div">
                                                <label for="discounted_price">Discounted Price(<?= $settings['currency'] ?>):</label>
                                                <input type="number" step="any" min="0" class="form-control" name="packate_discounted_price[]" id="discounted_price" value='<?= $row['discounted_price']; ?>' />
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group packate_div">
                                                <label for="qty">Stock:</label> <i class="text-danger asterik">*</i>
                                                <input type="number" step="any" min="0" class="form-control" name="packate_stock[]" value='<?= $row['stock']; ?>' />
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group packate_div">
                                                <label for="unit">Unit:</label>
                                                <select class="form-control" name="packate_stock_unit_id[]">
                                                    <?php
                                                    foreach ($unit_data as  $unit) {
                                                        echo "<option";
                                                        if ($unit['id'] == $row['stock_unit_id']) {
                                                            echo " selected ";
                                                        }
                                                        echo " value='" . $unit['id'] . "'>" . $unit['short_code'] . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group packate_div">

                                                <label for="qty">Status:</label>
                                                <select name="packate_serve_for[]" class="form-control">
                                                    <option value="Available" <?php if (strtolower($row['serve_for']) == "availabel") {
                                                                                    echo "selected";
                                                                                } ?>>Available</option>
                                                    <option value="Sold Out" <?php if (strtolower($row['serve_for']) == "sold out") {
                                                                                    echo "selected";
                                                                                } ?>>Sold Out</option>
                                                </select>
                                            </div>
                                        </div>
                                        <?php if ($i == 0) { ?>
                                            <div class='col-md-1'>
                                                <label>Variation</label>
                                                <a id='add_packate_variation' title='Add variation of product' style='cursor: pointer;'><i class="fa fa-plus-square-o fa-2x"></i></a>
                                            </div>
                                        <?php } else { ?>
                                            <div class="col-md-1" style="display: grid;">
                                                <label>Remove</label>
                                                <a class="remove_variation text-danger" data-id="data_delete" title="Remove variation of product" style="cursor: pointer;"><i class="fa fa-times fa-2x"></i></a>
                                            </div>
                                        <?php } ?>

                                        <div class="col-md-4">
                                            <div class="form-group packate_div">
                                                <label for="exampleInputFile">Variant Images &nbsp;&nbsp;&nbsp;(Please choose square image of larger than 350px*350px & smaller than 550px*550px.)</label><?= isset($error['variant_images']) ? $error['variant_images'] : ''; ?>
                                                <input type="file" name="packet_variant_images[<?= $k++; ?>][]" id="packet_variant_images" multiple title="Please choose square image of larger than 350px*350px & smaller than 550px*550px." /><br />
                                                <?php
                                                if (!empty($row['variant_images']) && $row['variant_images'] != 'NULL' && $row['variant_images'] != 'null') {
                                                    $variant_images = str_replace("'", '"', $row['variant_images']);
                                                    $variant_images = json_decode($variant_images);
                                                    for ($i = 0; $i < count($variant_images); $i++) { ?>
                                                        <img src="<?= '../' . $variant_images[$i]; ?>" height="100" />
                                                        <a class='btn btn-xs btn-danger delete-variant-image' data-i='<?= $i; ?>'>Delete</a>
                                                <?php }
                                                } ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Weight<i class="text-danger asterik">*</i></label><input type="text" name="weight[]" value='<?= $row['weight']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Lenght</label><input type="text" name="length[]" value='<?= $row['length']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Breadth</label><input type="text" name="breadth[]" value='<?= $row['breadth']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group"><label>Height</label><input type="text" name="height[]" value='<?= $row['height']; ?>' class="form-control min_value"></div>
                                        </div>

                                    </div>
                                <?php $i++;
                                }
                            } else {
                                $db->select('unit', '*');
                                $resedit = $db->getResult();
                                ?>
                                <div id="packate_div" style="display:none">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group packate_div">
                                                <label for="exampleInputEmail1">Measurement</label> <i class="text-danger asterik">*</i> <input type="number" step="any" min="0" class="form-control" name="packate_measurement[]" />
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group packate_div">
                                                <label for="unit">Unit:</label>
                                                <select class="form-control" name="packate_measurement_unit_id[]">
                                                    <?php
                                                    foreach ($resedit as  $row) {
                                                        echo "<option value='" . $row['id'] . "'>" . $row['short_code'] . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group packate_div">
                                                <label for="price">Price (<?= $settings['currency'] ?>):</label> <i class="text-danger asterik">*</i> <input type="number" step="any" min="0" class="form-control" name="packate_price[]" id="packate_price" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group packate_div">
                                                <label for="discounted_price">Discounted Price(<?= $settings['currency'] ?>):</label>
                                                <input type="number" step="any" min="0" class="form-control" name="packate_discounted_price[]" id="discounted_price" />
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group packate_div">
                                                <label for="qty">Stock:</label> <i class="text-danger asterik">*</i>
                                                <input type="number" step="any" min="0" class="form-control" name="packate_stock[]" />
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group packate_div">
                                                <label for="unit">Unit:</label>
                                                <select class="form-control" name="packate_stock_unit_id[]">
                                                    <?php
                                                    foreach ($resedit as  $row) {
                                                        echo "<option value='" . $row['id'] . "'>" . $row['short_code'] . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group packate_div">
                                                <label for="qty">Status:</label>
                                                <select name="packate_serve_for[]" class="form-control">
                                                    <option value="Available">Available</option>
                                                    <option value="Sold Out">Sold Out</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <label>Variation</label>
                                            <a id="add_packate_variation" title="Add variation of product" style="cursor: pointer;"><i class="fa fa-plus-square-o fa-2x"></i></a>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group packate_div">
                                                <label for="exampleInputFile">Variant Other Images <i class="text-danger asterik">*</i> &nbsp;&nbsp;&nbsp;*Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?= isset($error['variant_images']) ? $error['variant_images'] : ''; ?>
                                                <input type="file" name="packet_variant_images[<?= $k++; ?>][]" id="packet_variant_images" multiple /><br />
                                                <?php if (!empty($row['variant_images'])) {
                                                    $variant_images = str_replace("'", '"', $row['variant_images']);
                                                    $variant_images = json_decode($variant_images);
                                                    for ($i = 0; $i < count($variant_images); $i++) { ?>
                                                        <img src="<?= '../' . $variant_images[$i]; ?>" height="160" />
                                                        <a class='btn btn-xs btn-danger delete-variant-image' data-i='<?= $i; ?>' data-pid='<?= $_GET['id']; ?>'>Delete</a>
                                                <?php }
                                                } ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Weight<i class="text-danger asterik">*</i></label><input type="text" name="weight[]" value='<?= $row['weight']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Lenght</label><input type="text" name="length[]" value='<?= $row['length']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Breadth</label><input type="text" name="breadth[]" value='<?= $row['breadth']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group"><label>Height</label><input type="text" name="height[]" value='<?= $row['height']; ?>' class="form-control min_value"></div>
                                        </div>

                                    </div>
                                </div>
                            <?php } ?>
                            <div id="packate_variations"></div>
                            <?php
                            $i = 0;
                            $j = 0;
                            if ($res[0]['type'] == "loose") {
                                foreach ($res as $row) {
                            ?>
                                    <div class="row loose_div">
                                        <input type="hidden" class="form-control" name="product_variant_id[]" id="product_variant_id" value='<?= $row['product_variant_id']; ?>' />
                                        <div class="col-md-4">
                                            <div class="form-group loose_div">
                                                <label for="exampleInputEmail1">Measurement</label> <i class="text-danger asterik">*</i>
                                                <input type="number" step="any" min="0" class="form-control" name="loose_measurement[]"="" value='<?= $row['measurement']; ?>'>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group loose_div">
                                                <label for="unit">Unit:</label>
                                                <select class="form-control" name="loose_measurement_unit_id[]">
                                                    <?php
                                                    foreach ($unit_data as  $unit) {
                                                        echo "<option";
                                                        if ($unit['id'] == $row['measurement_unit_id']) {
                                                            echo " selected ";
                                                        }
                                                        echo " value='" . $unit['id'] . "'>" . $unit['short_code'] . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group loose_div">
                                                <label for="price">Price (<?= $settings['currency'] ?>):</label> <i class="text-danger asterik">*</i>
                                                <input type="number" step="any" min="0" class="form-control" name="loose_price[]" id="loose_price"="" value='<?= $row['price']; ?>'>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group loose_div">
                                                <label for="discounted_price">Discounted Price(<?= $settings['currency'] ?>):</label>
                                                <input type="number" step="any" min="0" class="form-control" name="loose_discounted_price[]" id="discounted_price" value='<?= $row['discounted_price']; ?>' />
                                            </div>
                                        </div>
                                        <?php if ($i == 0) { ?>
                                            <div class='col-md-1'>
                                                <label>Variation</label>
                                                <a id='add_loose_variation' title='Add variation of product' style='cursor: pointer;'><i class="fa fa-plus-square-o fa-2x"></i></a>
                                            </div>
                                        <?php } else { ?>
                                            <div class="col-md-1" style="display: grid;">
                                                <label>Remove</label>
                                                <a class="remove_variation text-danger" data-id="data_delete" title="Remove variation of product" style="cursor: pointer;"><i class="fa fa-times fa-2x"></i></a>
                                            </div>
                                        <?php }
                                        $i++; ?>

                                        <div class="col-md-4">
                                            <div class="form-group packate_div">
                                                <label for="exampleInputFile">Variant Other Images <i class="text-danger asterik">*</i> &nbsp;&nbsp;&nbsp;*Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?= isset($error['variant_images']) ? $error['variant_images'] : ''; ?>
                                                <input type="file" name="packet_variant_images[<?= $k++; ?>][]" id="packet_variant_images" multiple /><br />
                                                <?php if (!empty($row['variant_images'])) {
                                                    $variant_images = str_replace("'", '"', $row['variant_images']);
                                                    $variant_images = json_decode($variant_images);
                                                    for ($i = 0; $i < count($variant_images); $i++) { ?>
                                                        <img src="<?= '../' . $variant_images[$i]; ?>" height="160" />
                                                        <a class='btn btn-xs btn-danger delete-variant-image' data-i='<?= $i; ?>' data-pid='<?= $_GET['id']; ?>'>Delete</a>
                                                <?php }
                                                } ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Weight<i class="text-danger asterik">*</i></label><input type="text" name="weight_loose[]" value='<?= $row['weight']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Lenght</label><input type="text" name="length_loose[]" value='<?= $row['length']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Breadth</label><input type="text" name="breadth_loose[]" value='<?= $row['breadth']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group"><label>Height</label><input type="text" name="height_loose[]" value='<?= $row['height']; ?>' class="form-control min_value"></div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div id="loose_variations"></div>

                                <hr>
                                <div class="form-group" id="loose_stock_div" style="display:block;">
                                    <label for="quantity">Stock :</label> <i class="text-danger asterik">*</i> <?php echo isset($error['quantity']) ? $error['quantity'] : ''; ?>
                                    <input type="number" step="any" min="0" class="form-control" name="loose_stock" value='<?= $row['stock']; ?>'>
                                </div>
                                <div class="form-group">
                                    <label for="stock_unit">Unit :</label><?php echo isset($error['stock_unit']) ? $error['stock_unit'] : ''; ?>
                                    <select class="form-control" name="loose_stock_unit_id" id="loose_stock_unit_id">
                                        <?php
                                        foreach ($unit_data as  $unit) {
                                            echo "<option";
                                            if ($unit['id'] == $row['stock_unit_id']) {
                                                echo " selected ";
                                            }
                                            echo " value='" . $unit['id'] . "'>" . $unit['short_code'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php } else {
                                $db->select('unit', '*');
                                $resedit = $db->getResult();
                            ?>
                                <div id="loose_div" style="display:none;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group loose_div">
                                                <label for="exampleInputEmail1">Measurement</label> <i class="text-danger asterik">*</i>
                                                <input type="number" step="any" min="0" class="form-control" name="loose_measurement[]"="">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group loose_div">
                                                <label for="unit">Unit:</label>
                                                <select class="form-control" name="loose_measurement_unit_id[]">
                                                    <?php
                                                    foreach ($resedit as  $row) {
                                                        echo "<option value='" . $row['id'] . "'>" . $row['short_code'] . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group loose_div">
                                                <label for="price">Price (INR):</label> <i class="text-danger asterik">*</i>
                                                <input type="number" step="any" min="0" class="form-control" name="loose_price[]" id="loose_price"="">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group loose_div">
                                                <label for="discounted_price">Discounted Price:</label>
                                                <input type="number" step="any" min="0" class="form-control" name="loose_discounted_price[]" id="discounted_price" />
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <label>Variation</label>
                                            <a id="add_loose_variation" title="Add variation of product" style="cursor: pointer;"><i class="fa fa-plus-square-o fa-2x"></i></a>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group loose_div">
                                                <label for="exampleInputFile">Variant Other Images <i class="text-danger asterik">*</i> &nbsp;&nbsp;&nbsp;*Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?= isset($error['variant_images']) ? $error['variant_images'] : ''; ?>
                                                <input type="file" name="loose_variant_images[<?= $j++; ?>][]" id="loose_variant_images" multiple title="Please choose square image of larger than 350px*350px & smaller than 550px*550px." /><br />
                                                <?php if (!empty($row['variant_images'])) {
                                                    $variant_images = str_replace("'", '"', $row['variant_images']);
                                                    $variant_images = json_decode($variant_images);
                                                    for ($i = 0; $i < count($variant_images); $i++) { ?>
                                                        <img src="<?= '../' . $variant_images[$i]; ?>" height="160" />
                                                        <a class='btn btn-xs btn-danger delete-variant-image' data-i='<?= $i; ?>' data-pid='<?= $_GET['id']; ?>'>Delete</a>
                                                <?php }
                                                } ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Weight<i class="text-danger asterik">*</i></label><input type="text" name="weight_loose[]" value='<?= $row['weight']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Lenght</label><input type="text" name="length_loose[]" value='<?= $row['length']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group"><label>Breadth</label><input type="text" name="breadth_loose[]" value='<?= $row['breadth']; ?>' class="form-control min_value"></div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group"><label>Height</label><input type="text" name="height_loose[]" value='<?= $row['height']; ?>' class="form-control min_value"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="variations">
                                </div>
                                <hr>
                                <div class="form-group" id="loose_stock_div" style="display:none;">
                                    <label for="quantity">Stock :</label> <i class="text-danger asterik">*</i> <?php echo isset($error['quantity']) ? $error['quantity'] : ''; ?>
                                    <input type="number" step="any" min="0" class="form-control" name="loose_stock">

                                    <label for="stock_unit">Unit :</label><?php echo isset($error['stock_unit']) ? $error['stock_unit'] : ''; ?>
                                    <select class="form-control" name="loose_stock_unit_id" id="loose_stock_unit_id">
                                        <?php
                                        foreach ($resedit as $row) {
                                            echo "<option value='" . $row['id'] . "'>" . $row['short_code'] . "</option>";
                                        }
                                        ?>
                                    </select>

                                </div>
                            <?php } ?>
                            <hr>

                            <div class="form-group">
                                <div class="form-group" id="status_div" <?php if ($res[0]['type'] == "packet") {
                                                                            echo "style='display:none'";
                                                                        } ?>>
                                    <label for="exampleInputEmail1">Status :</label><?php echo isset($error['serve_for']) ? $error['serve_for'] : ''; ?>
                                    <select name="serve_for" class="form-control">
                                        <option value="Available" <?php if (strtolower($res[0]['serve_for']) == "available") {
                                                                        echo "selected";
                                                                    } ?>>Available</option>
                                        <option value="Sold Out" <?php if (strtolower($res[0]['serve_for']) == "sold out") {
                                                                        echo "selected";
                                                                    } ?>>Sold Out</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Category :</label> <i class="text-danger asterik">*</i> <?php echo isset($error['category_id']) ? $error['category_id'] : ''; ?>
                                        <select name="category_id" id="category_id" class="form-control">
                                            <?php
                                            foreach ($cate_data as $row) { ?>
                                                <option value="<?php echo $row['id']; ?>" <?= ($row['id'] == $data['category_id']) ? "selected" : ""; ?>><?php echo $row['name']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Sub Category :</label>
                                        <select name="subcategory_id" id="subcategory_id" class="form-control">
                                            <option value="">---Select Subcategory---</option>
                                            <?php foreach ($subcategory as $subcategories) { ?>
                                                <option value="<?= $subcategories['id']; ?>" <?= $res[0]['subcategory_id'] == $subcategories['id'] ? 'selected' : '' ?>><?= $subcategories['name']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Product Type :</label>
                                        <select name="indicator" id="indicator" class="form-control">
                                            <option value="">--Select Type--</option>
                                            <option value="1" <?php if ($res[0]['indicator'] == 1) {
                                                                    echo 'selected';
                                                                } ?>>Veg</option>
                                            <option value="2" <?php if ($res[0]['indicator'] == 2) {
                                                                    echo 'selected';
                                                                } ?>>Non Veg</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Manufacturer :</label>
                                        <input type="text" name="manufacturer" value="<?= $res[0]['manufacturer'] ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Made In :</label>
                                        <input type="text" name="made_in" value="<?= $res[0]['made_in'] ?>" class="form-control">
                                    </div>
                                    <hr>
                                    <div class="row offset-col-2">
                                        <div class="col-md-4">
                                            <label for="">Select Shipping Type :</label><i class="text-danger asterik">*</i>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="col-md-06">
                                                <div class="form-group">
                                                    <select name="shipping_type" id="shipping_type" class="form-control">
                                                        <option value="">Select Option</option>
                                                        <option value="local" <?= ($res[0]['standard_shipping'] == 0) ? 'selected' : '' ?>>local Shipping</option>
                                                        <option value="standard" <?= ($res[0]['standard_shipping'] == 1) ? 'selected' : '' ?>>Standard Shipping</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row local">
                                        <div class="">
                                            <div class="col-md-4">
                                                <div class="form-group" style="margin-top:18px">
                                                    <label for="product_pincodes">Delivery Places <small><a href="#" class="local_shipping_info" data-toggle="modal" data-target="#exampleModal">What is delivery places?</a></small>
                                                        :</label><i class="text-danger asterik">*</i>
                                                    <select name="product_pincodes" id="product_pincodes" class="form-control">
                                                        <option value="">Select Option</option>
                                                        <option value="included" <?= (!empty($res[0]['d_type']) && $res[0]['d_type'] == "included") ? 'selected' : ''; ?>>Pincode Included</option>
                                                        <option value="excluded" <?= (!empty($res[0]['d_type']) && $res[0]['d_type'] == "excluded") ? 'selected' : ''; ?>>Pincode Excluded</option>
                                                        <option value="all" <?= (!empty($res[0]['d_type']) && $res[0]['d_type'] == "all") ? 'selected' : ''; ?>>Includes All</option>
                                                    </select>
                                                    <br />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group" style="margin-top:18px">
                                                    <label for='pincode_ids_exc'>Selected Pincodes <small>( Ex : 100,205, 360 <comma separated>)</small></label>
                                                    <select name='pincode_ids_exc[]' id='pincode_ids_exc' style="width:520px" class='form-control' placeholder='Enter the pincode you want to allow delivery this product' multiple="multiple">
                                                        <?php $sql = 'select id,pincode from `pincodes` where `status` = 1 order by id desc';
                                                        $db->sql($sql);
                                                        $result = $db->getResult();
                                                        if ($res[0]['pincodes'] != "") {
                                                            foreach ($result as $value) {
                                                        ?>
                                                                <option value='<?= $value['id'] ?>' <?= (strpos(" " . $res[0]['pincodes'], $value['id'])) ? 'selected' : ''; ?>><?= $value['pincode']  ?></option>
                                                            <?php }
                                                        } else {
                                                            foreach ($result as $value) { ?>
                                                                <option value='<?= $value['id'] ?>'><?= $value['pincode']  ?></option>

                                                        <?php }
                                                        } ?>

                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row standard">
                                        <div class="">
                                            <div class="col-md-4 ">
                                                <small><a href="#" class="standard_shipping_info" data-toggle="modal" data-target="#exampleModal">What is pickup places?</a></small>

                                                <div class="form-group">

                                                    <label for="product_pincodes">Select Pickup Places :</label><i class="text-danger asterik">*</i>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label id='shipping_error' class="text-danger"></label>
                                                <select name="pickup_location" id='sellers_pickup_locations' class="form-control">
                                                    <option class="defualt_select" value="">-- Select --</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="">Is Returnable? :</label><br>
                                            <input type="checkbox" id="return_status_button" class="js-switch" <?= $res[0]['return_status'] == 1 ? 'checked' : '' ?>>
                                            <input type="hidden" id="return_status" name="return_status" value="<?= $res[0]['return_status'] == 1 ? 1 : 0 ?>">
                                        </div>
                                    </div>
                                    <?php
                                    $style1 = (!empty($res[0]['return_days'])) ? "" : "display:none;";
                                    ?>
                                    <div class="col-md-3" id="return_day" style="<?= $style1; ?>">
                                        <div class="form-group">
                                            <label for="return_day">Max Return Days :</label>
                                            <input type="number" step="any" min="0" class="form-control" placeholder="Number of days to Return" value="<?= $res[0]['return_days'] ?>" name="return_days" id="return_days" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="">Is cancel-able? :</label><br>
                                            <input type="checkbox" id="cancelable_button" class="js-switch" <?= $res[0]['cancelable_status'] == 1 ? 'checked' : '' ?>>
                                            <input type="hidden" id="cancelable_status" name="cancelable_status" value="<?= $res[0]['cancelable_status'] == 1 ? 1 : 0 ?>">
                                        </div>
                                    </div>
                                    <?php
                                    $style = $res[0]['cancelable_status'] == 1 ? "" : "display:none;";
                                    ?>
                                    <div class="col-md-3" id="till-status" style="<?= $style; ?>">
                                        <div class="form-group">
                                            <label for="">Till which status? :</label> <i class="text-danger asterik">*</i> <?php echo isset($error['cancelable']) ? $error['cancelable'] : ''; ?><br>
                                            <select id="till_status" name="till_status" class="form-control">
                                                <option value="">Select</option>
                                                <option value="received" <?= $res[0]['till_status'] == 'received' ? 'selected' : '' ?>>Received</option>
                                                <option value="processed" <?= $res[0]['till_status'] == 'processed' ? 'selected' : '' ?>>Processed</option>
                                                <option value="shipped" <?= $res[0]['till_status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 d-none">
                                        <div class="form-group">
                                            <label for="">Is COD allowed? :</label><br>
                                            <input type="checkbox" id="cod_allowed_button" class="js-switch" <?= isset($res[0]['cod_allowed']) && $res[0]['cod_allowed'] == 1 ? 'checked' : '' ?>>
                                            <input type="hidden" id="cod_allowed_status" name="is_cod_allowed" value="<?= isset($res[0]['cod_allowed']) && $res[0]['cod_allowed'] == 1 ? 1 : 0 ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-none">
                                        <div class="form-group">
                                            <label for="">Total allowed quantity : <small>[Keep blank if no such limit]</small></label>
                                            <input type="number" min="0" class="form-control" name="max_allowed_quantity" value="<?= isset($res[0]['total_allowed_quantity']) && $res[0]['total_allowed_quantity'] != '' ? $res[0]['total_allowed_quantity'] : 0 ?>" />
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputFile">Image <i class="text-danger asterik">*</i> &nbsp;&nbsp;&nbsp;*Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?php echo isset($error['image']) ? $error['image'] : ''; ?>
                                    <input type="file" name="image" id="image" title="Please choose square image of larger than 350px*350px & smaller than 550px*550px." /><br />
                                    <img src="<?= DOMAIN_URL . $data['image']; ?>" width="210" height="160" />
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputFile">Other Images *Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?php echo isset($error['other_images']) ? $error['other_images'] : ''; ?>
                                    <input type="file" name="other_images[]" id="other_images" multiple title="Please choose square image of larger than 350px*350px & smaller than 550px*550px." /><br />
                                    <?php
                                    if (!empty($data['other_images'])) {
                                        $other_images = json_decode($data['other_images']);
                                        for ($i = 0; $i < count($other_images); $i++) { ?>
                                            <img src="<?= DOMAIN_URL .  $other_images[$i]; ?>" height="160" />
                                            <a class='btn btn-xs btn-danger delete-image' data-seller_id='<?= $seller_id; ?>' data-i='<?= $i; ?>' data-pid='<?= $_GET['id']; ?>'>Delete</a>
                                    <?php }
                                    } ?>
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputEmail1">Description :</label> <i class="text-danger asterik">*</i><i id="address_note"></i> <?= isset($error['description']) ? $error['description'] : ''; ?>
                                    <textarea name="description" id="description" class="form-control addr_editor" rows="16"><?= $data['description']; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="control-label ">Status :</label>
                                    <div id="product_status" class="btn-group">
                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="pr_status" value="0"> Deactive
                                        </label>
                                        <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="pr_status" value="1"> Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.box-body -->
                        <div class="box-footer">
                            <input type="submit" class="btn-primary btn" value="Update" name="btnEdit" />
                        </div>
                </form>
            </div><!-- /.box -->
        </div>
    </div>
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title info-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="standard_shiping_info_modal">
                        <b>What is Pickup Location ?</b><br>
                        Get your products delivered faster at your customers' doorstep by selecting the pickup location nearest to your buyer's address. It helps in faster delivery by eliminating the extra transit time
                        <a href="https://www.shiprocket.in/features/multiple-pick-up-locations/" target="_blank">know more.</a>
                        <br>
                        <br>
                        <b>How to get pickup location ?</b><br>
                        <a href="<?= DOMAIN_URL ?>seller/pickup-locations.php" target="_blank">click</a> here to get detais of pickup locations

                        <br>
                        <br>
                        <b>Note:</b>Currently only seller can add pickup location in this system
                    </p>
                    <p class="local_shiping_info_modal">
                        <b>What is Delivery Places ?</b><br>
                        deliver places is were admin can allow to delivered orders to customers
                        <br>
                        <br>
                        <b>Type:</b>
                        <br>
                        <b>1. Included:</b>in this type you can select where spesific pincode you will deliver orders to customers <br>
                        <b>2. Excluded:</b>in this type you can select where spesific pincode you will not deliver orders to customers <br>
                        <b>3. All:</b>in this type you can deliver product to all pincodes <br>
                        <br>
                        <b>Note:</b>You can delivered only pincodes that are uploaded in you system
                        <br>
                        <br>
                        <b>How to get Pincodes ?</b><br>
                        <a href="<?= DOMAIN_URL ?>seller/areas.php" target="_blank">click</a> here to get detais of Pincodes
                        <br>
                        <br>

                    </p>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</section>
<div class="separator"> </div>

<script>
    var pincode_type = $('#product_pincodes').find(":selected").val();

    function searchable_zipcodes() {
        var search_zipcodes = $(".search_zipcode").select2({
            ajax: {
                url: 'public/db-operation.php',
                dataType: 'json',
                data: function(params) {
                    return {
                        search: params.term,
                        type: 'search_pincode',
                        page: params.page,
                    }
                },
                processResults: function(response, params) {
                    params.page = params.page || 1;
                    return {
                        results: response,
                        pagination: {
                            more: (params.page * 30) < response.total
                        }
                    };
                },
                cache: false,
            },
            escapeMarkup: function(markup) {
                return markup;
            },
            placeholder: 'Search for Pincodes',
            templateResult: formatRepo,
            templateSelection: formatRepoSelection,
        });
        return search_zipcodes;
    }

    function formatRepo(repo) {
        if (repo.loading) {
            return repo.pincode;
        }

        var markup = "<div class='select2-result-repository clearfix'>" +
            "<div class='select2-result-repository__meta'>" +
            "<strong>" + repo.pincode + "</strong>";

        return markup;
    }

    function formatRepoSelection(repo) {
        return repo.pincode;
    }
    $(document).on('click', '.delete-image', function() {
        var pid = $(this).data('pid');
        var i = $(this).data('i');
        var seller_id = $(this).data('seller_id');
        if (confirm('Are you sure want to delete the image?')) {
            $.ajax({
                type: 'POST',
                url: 'public/delete-other-images.php',
                data: 'i=' + i + '&pid=' + pid + '&seller_id=' + seller_id,
                success: function(result) {
                    if (result == '1') {
                        alert('Image deleted successfully');
                        window.location.replace("view-product-variants.php?id=" + pid);
                    } else
                        alert('Image could not be deleted!');

                }
            });
        }
    });

    $(document).on('click', '.delete-variant-image', function() {
        var pid = $(this).data('pid');
        var i = $(this).data('i');
        var vid = $(this).closest('div.row').find("input[id='product_variant_id']").val();
        if (confirm('Are you sure want to delete the image?')) {
            $.ajax({
                type: 'POST',
                url: 'public/db-operation.php',
                data: 'i=' + i + '&vid=' + vid + '&delete_variant_images=1',
                success: function(result) {
                    if (result == 1) {
                        alert('Image deleted successfully');
                        location.reload();
                    } else {
                        alert('Image could not be deleted!');
                        location.reload();
                    }
                }
            });
        }
    });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
<script>
    $(document).ready(function() {
        ltr = '<svg width="20" height="20"><path d="M11 5h7a1 1 0 010 2h-1v11a1 1 0 01-2 0V7h-2v11a1 1 0 01-2 0v-6c-.5 0-1 0-1.4-.3A3.4 3.4 0 017.8 10a3.3 3.3 0 010-2.8 3.4 3.4 0 011.8-1.8L11 5zM4.4 16.2L6.2 15l-1.8-1.2a1 1 0 011.2-1.6l3 2a1 1 0 010 1.6l-3 2a1 1 0 11-1.2-1.6z" fill-rule="evenodd"></path></svg>';
        rtl = '<svg width="20" height="20"><path d="M8 5h8v2h-2v12h-2V7h-2v12H8v-7c-.5 0-1 0-1.4-.3A3.4 3.4 0 014.8 10a3.3 3.3 0 010-2.8 3.4 3.4 0 011.8-1.8L8 5zm12 11.2a1 1 0 11-1 1.6l-3-2a1 1 0 010-1.6l3-2a1 1 0 111 1.6L18.4 15l1.8 1.2z" fill-rule="evenodd"></path></svg>';
        html = '( Use ' + ltr + ' for LTR and use ' + rtl + ' for RTL )';
        $('#address_note').append(html);
    });
</script>
<script>
    var changeCheckbox = document.querySelector('#return_status_button');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#return_status').val(1);
            $('#return_day').show();
        } else {
            $('#return_status').val(0);
            $('#return_day').hide();
            $('#return_day').val('');
        }
    };
</script>
<script>
    var changeCheckbox = document.querySelector('#cancelable_button');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#cancelable_status').val(1);
            $('#till-status').show();

        } else {
            $('#cancelable_status').val(0);
            $('#till-status').hide();
            $('#till_status').val('');
        }
    };
    var changeCheckbox = document.querySelector('#cod_allowed_button');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#cod_allowed_status').val(1);

        } else {
            $('#cod_allowed_status').val(0);
        }
    };
    $('#pincode_ids_inc').select2({
        width: 'element',
        placeholder: 'type in category name to search',

    });
    $(document).ready(function() {
        if ($('#shipping_type').val() == 'local') {
            var val = $('#product_pincodes').val();
            if (val == "all") {
                $('#pincode_ids_exc').prop('disabled', true);
            } else {
                $('#pincode_ids_exc').prop('disabled', false);
            }
        }
    });

    $('#product_pincodes').on('change', function() {
        var val = $('#product_pincodes').val();
        if (val == "included" || val == "excluded") {
            $('#pincode_ids_exc').prop('disabled', false);
        } else {
            $('#pincode_ids_exc').val("");
            $('#pincode_ids_exc').prop('disabled', true);
        }
    });
    $('#pincode_ids_exc').select2({
        width: 'element',
        placeholder: 'type in category name to search',

    });
</script>
<script>
    $.validator.addMethod('lessThanEqual', function(value, element, param) {
        return this.optional(element) || parseInt(value) < parseInt($(param).val());
    }, "Discounted Price should be lesser than Price");

    $('#edit_product_form').validate({
        rules: {
            name: "",
            measurement: "",
            price: "",
            quantity: "",
            discounted_price: "",
            stock: "",
            discounted_price: {
                lessThanEqual: "#price"
            },
            description: {
                required: function(textarea) {
                    CKEDITOR.instances[textarea.id].updateElement();
                    var editorcontent = textarea.value.replace(/<[^>]*>/gi, '');
                    return editorcontent.length === 0;
                }
            }
        }
    });
</script>
<input type="hidden" name="" id="old_pickup_location" value="<?= $res[0]['pickup_location'] ?>">
<script>
    function fetchPickup_locations(standard_shipping, seller_id) {
        if ($('#shipping_type').val() == 'standard') {
            if (seller_id != 0) {
                var old_pickup_location = $('#old_pickup_location').val()
                $('#sellers_pickup_locations').removeClass('hide');
                if (standard_shipping == 1) {
                    $.ajax({

                        type: 'POST',
                        url: "../public/db-operation.php",
                        data: "get_seller_pickup_location=" + seller_id,
                        dataType: "json",
                        error: function(request, error) {
                            console.log(request)
                        },
                        success: function(data) {
                            if (data.error == false) {
                                var pickup_locations = data.data;
                                var seller_pickup_locations;
                                $('.appended-options').remove();
                                if (pickup_locations.length == 1) {
                                    pickup_locations.forEach(location => {
                                        if (old_pickup_location == location.pickup_location) {
                                            seller_pickup_locations = '<option class="appended-options" selected  value="' + location.pickup_location + '">' + location.pickup_location + ' ' + location.pin_code + '</option>';
                                            $('#sellers_pickup_locations').append(seller_pickup_locations)
                                        } else {
                                            seller_pickup_locations = '<option class="appended-options"  value="' + location.pickup_location + '">' + location.pickup_location + ' ' + location.pin_code + '</option>';
                                            $('#sellers_pickup_locations').append(seller_pickup_locations)
                                            $('.defualt_select').attr("disabled", true)
                                        }
                                    })
                                } else {
                                    pickup_locations.forEach(location => {
                                        if (old_pickup_location == location.pickup_location) {
                                            seller_pickup_locations = '<option class="appended-options" selected value="' + location.pickup_location + '">' + location.pickup_location + ' ' + location.pin_code + '</option>';
                                            $('#sellers_pickup_locations').append(seller_pickup_locations)
                                        } else {
                                            seller_pickup_locations = '<option class="appended-options" value="' + location.pickup_location + '">' + location.pickup_location + ' ' + location.pin_code + '</option>';
                                            $('#sellers_pickup_locations').append(seller_pickup_locations)
                                        }

                                    })
                                }
                                $('#sellers_pickup_locations').removeClass('hide');
                                $('#seller_pickup_locations_error').addClass('hide');
                                $('#btnADD').attr('disabled', false)
                                $('#btnADD').attr('disabled', false)
                                $('#seller_id').attr('disabled', false)
                                $('#standar_shipping').attr('disabled', false)
                                $("input").prop("disabled", false);
                                $("select").prop("disabled", false);
                                $("radio").prop("disabled", false);
                                $('#shipping_error').addClass('hide');
                            } else {
                                $('#sellers_pickup_locations').addClass('hide');
                                $('#shipping_error').removeClass('hide');
                                $('#shipping_error').html('Sorry this Seller have not any pickup locations');
                                $('#btnADD').attr('disabled', true)
                                $("input").prop("disabled", true);
                                $("select").prop("disabled", true);
                                $("radio").prop("disabled", true);
                                $('#seller_id').attr('disabled', false)
                                $('#standard_shipping').attr('disabled', false)
                            }
                        }
                    });

                } else {
                    $('#btnADD').attr('disabled', false)
                    $('#btnADD').attr('disabled', false)
                }

            } else {
                $('#shipping_error').html('Select Seller name');
                $('#sellers_pickup_locations').addClass('hide');
                $('#btnADD').attr('disabled', true)
                $("input").prop("disabled", true);
                $("select").prop("disabled", true);
                $("radio").prop("disabled", true);
                $('#seller_id').attr('disabled', false)
                $('#standard_shipping').attr('disabled', false)
            }
        }
    }
    $('.standard_shipping_info').on('click', function() {
        $('.standard_shiping_info_modal').show();
        $('.info-title').html('What is pickup places');
        $('.local_shiping_info_modal').hide();
    });
    $('.local_shipping_info').on('click', function() {
        $('.standard_shiping_info_modal').hide();
        $('.info-title').html('what is a deliver places');
        $('.local_shiping_info_modal').show();
    });
    $('#shipping_type').on('change', function() {
        let shipping_type = $(this).val();
        if (shipping_type == 'standard') {
            $('.standard').show();
            $('.local').hide();

            var seller_id = "<?= $seller_id ?>";
            var standard_shipping = 1;
            fetchPickup_locations(standard_shipping, seller_id)
        } else {
            $('.standard').hide();
            $('.local').show();
        }
    })
    var shipping_type = "<?= $res[0]['standard_shipping'] ?>"
    if (shipping_type == 1) {
        $('.standard').show();
        $('.local').hide();
        var standard_shipping = 1;
        var seller_id = "<?= $seller_id ?>";
        fetchPickup_locations(standard_shipping, seller_id)
    } else {
        $('.standard').hide();
        $('.local').show();
    }
</script>

<script>
    var x = 0;
    $('#add_loose_variation').on('click', function() {
        html = '<div class="row"><div class="col-md-4"><div class="form-group loose_div">' +
            '<label for="exampleInputEmail1">Measurement</label> <i class="text-danger asterik">*</i> <input type="number" step="any" min="0" class="form-control" name="insert_loose_measurement[]" ="">' +
            '</div></div>' +
            '<div class="col-md-2"><div class="form-group loose_div">' +
            '<label for="unit">Unit:</label>' +
            '<select class="form-control" name="insert_loose_measurement_unit_id[]">' +
            '<?php foreach ($unit_data as  $unit) {
                    echo "<option value=" . $unit['id'] . ">" . $unit['short_code'] . "</option>";
                } ?>' +
            '</select></div></div>' +
            '<div class="col-md-3"><div class="form-group loose_div">' +
            '<label for="price">Price  (<?= $settings['currency'] ?>):</label> <i class="text-danger asterik">*</i> ' +
            '<input type="number" step="any" min="0" class="form-control" name="insert_loose_price[]" id="loose_price" ="">' +
            '</div></div>' +
            '<div class="col-md-2"><div class="form-group loose_div">' +
            '<label for="discounted_price">Discounted Price(<?= $settings['currency'] ?>):</label>' +
            '<input type="number" step="any" min="0" class="form-control" name="insert_loose_discounted_price[]" id="discounted_price"/>' +
            '</div></div>' +
            '<div class="col-md-1" style="display: grid;">' +
            '<label>Remove</label><a class="remove_variation text-danger" data-id="remove" title="Remove variation of product" style="cursor: pointer;"><i class="fa fa-times fa-2x"></i></a>' +
            '</div>' +

            '<div class="col-md-6"><div class="form-group loose_div">' +
            '<label for="exampleInputFile">Variant Other Images <i class="text-danger asterik">*</i> &nbsp;&nbsp;&nbsp;*Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?= isset($error['variant_images']) ? $error['variant_images'] : ''; ?>' +
            '<input type="file" name="insert_loose_variant_images[' + x++ + '][]" id="insert_loose_variant_images" multiple title="Please choose square image of larger than 350px*350px & smaller than 550px*550px." /><br />' +
            '</div></div>' +

            '<div class="col-md-2"><div class="form-group"><label>Weight <i class="text-danger">*</i></label><input type="text" name="weight_loose[]" class="form-control"></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Length</label><input type="text"  name="length_loose[]" class="form-control"></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Breadth</label><input type="text"  name="breadth_loose[]" class="form-control"></div></div>' +
            '<div class="col-md-1"><div class="form-group"><label>Height</label><input type="text" name="height_loose[]"  class="form-control"></div></div>' +



            '</div>';
        $('#loose_variations').append(html);
    });

    $('#add_packate_variation').on('click', function() {
        html = '<div class="row"><div class="col-md-2"><div class="form-group packate_div">' +
            '<label for="exampleInputEmail1">Measurement</label> <i class="text-danger asterik">*</i> <input type="number" step="any" min="0" class="form-control" name="insert_packate_measurement[]"  />' +
            '</div></div>' +
            '<div class="col-md-1"><div class="form-group packate_div">' +
            '<label for="unit">Unit:</label>' +
            '<select class="form-control" name="insert_packate_measurement_unit_id[]">' +
            '<?php foreach ($unit_data as  $unit) {
                    echo "<option value=" . $unit['id'] . ">" . $unit['short_code'] . "</option>";
                } ?>' +
            '</select></div></div>' +
            '<div class="col-md-2"><div class="form-group packate_div">' +
            '<label for="price">Price  (<?= $settings['currency'] ?>):</label> <i class="text-danger asterik">*</i> <input type="number" step="any" min="0" class="form-control" name="insert_packate_price[]" id="packate_price"  />' +
            '</div></div>' +
            '<div class="col-md-2"><div class="form-group packate_div">' +
            '<label for="discounted_price">Discounted Price(<?= $settings['currency'] ?>):</label>' +
            '<input type="number" step="any" min="0" class="form-control" name="insert_packate_discounted_price[]" id="discounted_price"/>' +
            '</div></div>' +
            '<div class="col-md-1"><div class="form-group packate_div">' +
            '<label for="qty">Stock:</label> <i class="text-danger asterik">*</i> ' +
            '<input type="number" step="any" min="0" class="form-control" name="insert_packate_stock[]"/>' +
            '</div></div>' +
            '<div class="col-md-1"><div class="form-group packate_div">' +
            '<label for="unit">Unit:</label><select class="form-control" name="insert_packate_stock_unit_id[]">' +
            '<?php foreach ($unit_data as  $unit) {
                    echo "<option value=" . $unit['id'] . ">" . $unit['short_code'] . "</option>";
                } ?>' +
            '</select></div></div>' +
            '<div class="col-md-2"><div class="form-group packate_div"><label for="insert_packate_serve_for">Status:</label>' +
            '<select name="insert_packate_serve_for[]" class="form-control valid" ="" aria-invalid="false"><option value="Available">Available</option><option value="Sold Out">Sold Out</option></select></div></div>' +
            '<div class="col-md-1" style="display: grid;">' +
            '<label>Remove</label><a class="remove_variation text-danger" data-id="remove" title="Remove variation of product" style="cursor: pointer;"><i class="fa fa-times fa-2x"></i></a>' +
            '</div>' +

            '<div class="col-md-6"><div class="form-group packate_div">' +
            '<label for="exampleInputFile">Variant Other Images <i class="text-danger asterik">*</i> &nbsp;&nbsp;&nbsp;*Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?= isset($error['variant_images']) ? $error['variant_images'] : ''; ?>' +
            '<input type="file" name="insert_packet_variant_images[' + x++ + '][]" id="insert_packet_variant_images" multiple title="Please choose square image of larger than 350px*350px & smaller than 550px*550px." /><br />' +
            '</div></div>' +


            '<div class="col-md-2"><div class="form-group"><label>Weight <i class="text-danger">*</i></label><input type="text" name="weight[]" class="form-control"></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Length</label><input type="text"  name="length[]" class="form-control"></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Breadth</label><input type="text"  name="breadth[]" class="form-control"></div></div>' +
            '<div class="col-md-1"><div class="form-group"><label>Height</label><input type="text" name="height[]"  class="form-control"></div></div>' +


            '</div>';
        $('#packate_variations').append(html);
    });
</script>
<script>
    $(document).on('click', '.remove_variation', function() {
        if ($(this).data('id') == 'data_delete') {
            if (confirm('Are you sure? Want to delete this row')) {
                var id = $(this).closest('div.row').find("input[id='product_variant_id']").val();
                $.ajax({
                    url: 'public/db-operation.php',
                    type: "post",
                    data: 'id=' + id + '&delete_variant=1',
                    success: function(result) {
                        if (result) {
                            location.reload();
                        } else {
                            alert("Variant not deleted!");
                        }
                    }
                });
            }
        } else {
            $(this).closest('.row').remove();
        }
    });

    $(document).on('change', '#category_id', function() {
        $.ajax({
            url: 'public/db-operation.php',
            method: 'POST',
            data: 'category_id=' + $('#category_id').val() + '&find_subcategory=1',
            success: function(data) {
                $('#subcategory_id').html("<option value=''>---Select Subcategory---</option>" + data);
            }
        });
    });
    $(document).on('change', '#packate', function() {
        $('#packate_div').show();
        $('.packate_div').show();
        $('#loose_div').hide();
        $('.loose_div').hide();
        $('#status_div').hide();
        $('#loose_stock_div').hide();
    });
    $(document).on('change', '#loose', function() {
        $('#loose_div').show();
        $('.loose_div').show();
        $('#loose_stock_div').show();
        $('#status_div').show();
        $('#packate_div').hide();
        $('.packate_div').hide();
    });
    $(document).ready(function() {
        var product_status = '<?= $product_status ?>';
        $("input[name=pr_status][value=1]").prop('checked', true);
        if (product_status == 0)
            $("input[name=pr_status][value=0]").prop('checked', true);
    });
</script>