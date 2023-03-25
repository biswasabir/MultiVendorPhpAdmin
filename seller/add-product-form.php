<?php
include_once('../includes/functions.php');
date_default_timezone_set('Asia/Kolkata');
$function = new functions;
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
// session_start();


//get shipping
$shipping_type = ($fn->get_settings('local_shipping') == 1) ? 'local' : 'standard';

// set time for session timeout
$currentTime = time() + 25200;
$expired = 3600;
if (!isset($_SESSION['seller_id']) && !isset($_SESSION['seller_name'])) {
    header("location:index.php");
} else {
    $ID = $_SESSION['seller_id'];
}
// if current time is more than session timeout back to login page
if ($currentTime > $_SESSION['timeout']) {
    session_destroy();
    header("location:index.php");
}

// destroy previous session timeout and create new one
unset($_SESSION['timeout']);
$_SESSION['timeout'] = $currentTime + $expired;

$sql = "SELECT categories,require_products_approval FROM seller WHERE id = " . $ID;
$db->sql($sql);
$res = $db->getResult();
$category_ids = explode(',', $res[0]['categories']);
$category_id = implode(',', $category_ids);
$pr_approval = $res[0]['require_products_approval'];

if (empty($where)) {
    $where = " WHERE id IN($category_id)";
}

$sql = "SELECT * FROM `category` " . $where . " ORDER BY id ASC ";
$db->sql($sql);
$res = $db->getResult();

$sql_query = "SELECT value FROM settings WHERE variable = 'Currency'";
$db->sql($sql_query);

$res_cur = $db->getResult();
if (isset($_POST['btnAdd'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    $error = array();
    if ($_POST['shipping_type'] == 'standard') {

        $pincodes = '';
        $standard_shipping = 1;
        $pickup_location = (isset($_POST['pickup_location']) && !empty($_POST['pickup_location'])) ? $db->escapeString($fn->xss_clean($_POST['pickup_location'])) : 0;
    } else {
        if ($pincode_type == "all") {
            $pincode_ids = NULL;
        } else {
            $pincode_type = (isset($_POST['product_pincodes']) && $_POST['product_pincodes'] != '') ? $db->escapeString($fn->xss_clean($_POST['product_pincodes'])) : "";
            if ($pincode_type == "all") {
                $pincode_ids = NULL;
            } else {
                if (empty($_POST['pincode_ids_exc'])) {
                    $error['pincode_ids_exc'] = "<label class='label label-danger'>Select pincodes!.</label>";
                } else {
                    $pincode_ids = $fn->xss_clean_array($_POST['pincode_ids_exc']);
                    $pincode_ids = implode(",", $pincode_ids);
                }
            }
        }
    }
    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $seller_id = $ID;
    $tax_id = (isset($_POST['tax_id']) && $_POST['tax_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['tax_id'])) : 0;
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
    $weight = (isset($_POST['weight']) && !empty($_POST['weight'])) ? $db->escapeString($fn->xss_clean($_POST['weight'])) : 0;
    $length = (isset($_POST['length']) && !empty($_POST['length'])) ? $db->escapeString($fn->xss_clean($_POST['length'])) : 0;
    $breadth = (isset($_POST['breadth']) && !empty($_POST['breadth'])) ? $db->escapeString($fn->xss_clean($_POST['breadth'])) : 0;
    $height = (isset($_POST['height']) && !empty($_POST['height'])) ? $db->escapeString($fn->xss_clean($_POST['height'])) : 0;

    $is_approved = ($pr_approval == 0) ? 1 : 0;
    $is_cod_allowed = (isset($_POST['is_cod_allowed']) && $_POST['is_cod_allowed'] != '') ? $db->escapeString($fn->xss_clean($_POST['is_cod_allowed'])) : 1;
    $total_allowed_quantity = (isset($_POST['max_allowed_quantity']) && !empty($_POST['max_allowed_quantity'])) ? $db->escapeString($fn->xss_clean($_POST['max_allowed_quantity'])) : 0;
    $image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
    $image_error = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
    $image_type = $db->escapeString($fn->xss_clean($_FILES['image']['type']));

    $error = array();

    if (empty($name)) {
        $error['name'] = " <span class='label label-danger'>!</span>";
    }

    if (empty($tax_id)) {
        $error['tax_id'] = " <span class='label label-danger'>!</span>";
    }

    if ($cancelable_status == 1 && $till_status == '') {
        $error['cancelable'] = " <span class='label label-danger'>!</span>";
    }

    if (empty($category_id)) {
        $error['category_id'] = " <span class='label label-danger'>!</span>";
    }

    if (empty($description)) {
        $error['description'] = " <span class='label label-danger'>!</span>";
    }

    // common image file extensions
    $allowedExts = array("gif", "jpeg", "jpg", "png");

    // get image file extension
    error_reporting(E_ERROR | E_PARSE);
    $extension = end(explode(".", $_FILES["image"]["name"]));

    if ($image_error > 0) {
        $error['image'] = " <span class='label label-danger'>Not uploaded!</span>";
    } else {
        $result = $fn->validate_image($_FILES["image"]);
        if (!$result) {
            $error['image'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
        }
    }
    $error['other_images'] = '';
    if ($_FILES["other_images"]["error"][0] == 0) {
        for ($i = 0; $i < count($_FILES["other_images"]["name"]); $i++) {
            $_FILES["other_images"]["type"][$i];
            if ($_FILES["other_images"]["error"][$i] > 0) {
                $error['other_images'] = " <span class='label label-danger'>Images not uploaded!</span>";
            } else {
                $result = $fn->validate_other_images($_FILES["other_images"]["tmp_name"][$i], $_FILES["other_images"]["type"][$i]);
                if (!$result) {
                    $error['other_images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                }
            }
        }
    }

    if (!empty($name) && !empty($category_id)  && empty($error['other_images']) && empty($error['image']) && empty($error['cancelable']) && !empty($description)) {

        // create random image file name
        $string = '0123456789';
        $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);

        $image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;

        // upload new image
        $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../upload/images/' . $image);
        $other_images = '';
        if (isset($_FILES['other_images']) && ($_FILES['other_images']['size'][0] > 0)) {
            //Upload other images
            $file_data = array();
            $target_path = '../upload/other_images/';
            $target_path1 = 'upload/other_images/';
            for ($i = 0; $i < count($_FILES["other_images"]["name"]); $i++) {

                $filename = $_FILES["other_images"]["name"][$i];
                $temp = explode('.', $filename);
                $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                $file_data[] = $target_path1 . '' . $filename;
                if (!move_uploaded_file($_FILES["other_images"]["tmp_name"][$i], $target_path . '' . $filename))
                    echo "{$_FILES['image']['name'][$i]} not uploaded<br/>";
            }
            $other_images = json_encode($file_data);
        }

        $upload_image = 'upload/images/' . $image;

        // insert new data to product table
        $sql = "INSERT INTO products (name,tax_id,seller_id,slug,category_id,subcategory_id,image,other_images,description,indicator,manufacturer,made_in,return_status,cancelable_status, till_status,type,pincodes,is_approved,return_days,cod_allowed,total_allowed_quantity,standard_shipping,pickup_location) VALUES('$name','$tax_id','$seller_id','$slug','$category_id','$subcategory_id','$upload_image','$other_images','$description','$indicator','$manufacturer','$made_in','$return_status','$cancelable_status','$till_status','$pincode_type','$pincode_ids','$is_approved','$return_days','$is_cod_allowed','$total_allowed_quantity','$standard_shipping','$pickup_location')";
        // echo $sql;
        $db->sql($sql);
        $product_result = $db->getResult();

        if (!empty($product_result)) {
            $product_result = 0;
        } else {
            $product_result = 1;
        }

        $sql = "SELECT id from products ORDER BY id DESC";
        $db->sql($sql);
        $res_inner = $db->getResult();
        if ($product_result == 1) {
            if ($_POST['type'] == 'packet') {
                // $packate_measurement = $db->escapeString($fn->xss_clean($_POST['packate_measurement']));
                for ($i = 0; $i < count($_POST['packate_measurement']); $i++) {
                    $variant_other_images = '';
                    if ($_FILES["packet_variant_images"]["error"][$i][0] == 0) {
                        for ($j = 0; $j < count($_FILES["packet_variant_images"]["name"][$i]); $j++) {
                            if ($_FILES["packet_variant_images"]["error"][$i][$j] > 0) {
                                $error['packet_variant_images'] = " <span class='label label-danger'>Images not uploaded!</span>";
                            } else {
                                $result = $fn->validate_other_images($_FILES["packet_variant_images"]["tmp_name"][$i][$j], $_FILES["packet_variant_images"]["type"][$i][$j]);
                                if ($result) {
                                    $error['packet_variant_images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                                }
                            }
                        }
                    }

                    if (isset($_FILES['packet_variant_images']) && (!empty($_FILES['packet_variant_images']['name'][$i][0])) && ($_FILES['packet_variant_images']['size'][$i][0] > 0)) {
                        $file_data = array();
                        $target_path1 = '../upload/variant_images/';
                        $target_path = 'upload/variant_images/';
                        if (!is_dir($target_path1)) {
                            mkdir($target_path1, 0777, true);
                        }

                        for ($k = 0; $k < count($_FILES["packet_variant_images"]["name"][$i]); $k++) {
                            $filename = $_FILES["packet_variant_images"]["name"][$i][$k];
                            $temp = explode('.', $filename);
                            $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                            $file_data[] = $target_path . '' . $filename;
                            if (!move_uploaded_file($_FILES["packet_variant_images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                                echo "{$_FILES['packet_variant_images']['name'][$i][$k]} not uploaded<br/>";
                        }
                        $variant_other_images = json_encode($file_data);
                    }

                    $product_id = $db->escapeString($res_inner[0]['id']);
                    $type = $db->escapeString($fn->xss_clean($_POST['type']));
                    $measurement = $db->escapeString($fn->xss_clean($_POST['packate_measurement'][$i]));
                    $measurement_unit_id = $db->escapeString($fn->xss_clean($_POST['packate_measurement_unit_id'][$i]));

                    $price = $db->escapeString($fn->xss_clean($_POST['packate_price'][$i]));
                    $discounted_price = !empty($_POST['packate_discounted_price'][$i]) ? $db->escapeString($fn->xss_clean($_POST['packate_discounted_price'][$i])) : 0;
                    $serve_for = $db->escapeString($fn->xss_clean($_POST['packate_serve_for'][$i]));
                    $stock = $db->escapeString($fn->xss_clean($_POST['packate_stock'][$i]));
                    $serve_for = ($stock == 0 || $stock <= 0) ? 'Sold Out' : $serve_for;
                    $stock_unit_id = $db->escapeString($fn->xss_clean($_POST['packate_stock_unit_id'][$i]));
                    $weight = $db->escapeString($fn->xss_clean($_POST['weight'][$i]));
                    $height = $db->escapeString($fn->xss_clean($_POST['height'][$i]));
                    $length = $db->escapeString($fn->xss_clean($_POST['length'][$i]));
                    $breadth = $db->escapeString($fn->xss_clean($_POST['breadth'][$i]));

                    $sql = "INSERT INTO product_variant (product_id,type,measurement,measurement_unit_id,price,discounted_price,serve_for,stock,stock_unit_id,images,weight,height,length,breadth) VALUES('$product_id','$type','$measurement','$measurement_unit_id','$price','$discounted_price','$serve_for','$stock','$stock_unit_id','$variant_other_images','$weight','$height','$length','$breadth')";
                    $db->sql($sql);
                    $product_variant = $db->getResult();
                }
                if (!empty($product_variant)) {
                    $product_variant = 0;
                } else {
                    $product_variant = 1;
                }
            } elseif ($_POST['type'] == "loose") {
                for ($i = 0; $i < count($_POST['loose_measurement']); $i++) {
                    $variant_other_images = '';

                    if ($_FILES["loose_variant_images"]["error"][$i][0] == 0) {
                        for ($j = 0; $j < count($_FILES["loose_variant_images"]["name"][$i]); $j++) {
                            if ($_FILES["loose_variant_images"]["error"][$i][$j] > 0) {
                                $error['loose_variant_images'] = " <span class='label label-danger'>Images not uploaded!</span>";
                            } else {
                                $result = $fn->validate_other_images($_FILES["loose_variant_images"]["tmp_name"][$i][$j], $_FILES["loose_variant_images"]["type"][$i][$j]);
                                if ($result) {
                                    $error['loose_variant_images'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                                }
                            }
                        }
                    }

                    if (isset($_FILES['loose_variant_images']) && (!empty($_FILES['loose_variant_images']['name'][$i][0])) && ($_FILES['loose_variant_images']['size'][$i][0] > 0)) {
                        $file_data = array();
                        $target_path1 = '../upload/variant_images/';
                        $target_path = 'upload/variant_images/';
                        if (!is_dir($target_path1)) {
                            mkdir($target_path1, 0777, true);
                        }

                        for ($k = 0; $k < count($_FILES["loose_variant_images"]["name"][$i]); $k++) {
                            $filename = $_FILES["loose_variant_images"]["name"][$i][$k];
                            $temp = explode('.', $filename);
                            $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                            $file_data[] = $target_path . '' . $filename;
                            if (!move_uploaded_file($_FILES["loose_variant_images"]["tmp_name"][$i][$k], $target_path1 . '' . $filename))
                                echo "{$_FILES['loose_variant_images']['name'][$i][$k]} not uploaded<br/>";
                        }
                        $variant_other_images = json_encode($file_data);
                    }

                    $product_id = $db->escapeString($res_inner[0]['id']);
                    $type = $db->escapeString($fn->xss_clean($_POST['type']));
                    $measurement = $db->escapeString($fn->xss_clean($_POST['loose_measurement'][$i]));
                    $measurement_unit_id = $db->escapeString($fn->xss_clean($_POST['loose_measurement_unit_id'][$i]));
                    $price = $db->escapeString($fn->xss_clean($_POST['loose_price'][$i]));
                    $discounted_price = !empty($_POST['loose_discounted_price'][$i]) ? $db->escapeString($fn->xss_clean($_POST['loose_discounted_price'][$i])) : 0;
                    $serve_for = $db->escapeString($fn->xss_clean($_POST['serve_for']));
                    $stock = $db->escapeString($fn->xss_clean($_POST['loose_stock']));
                    $serve_for = ($stock == 0 || $stock <= 0) ? 'Sold Out' : $serve_for;
                    $stock_unit_id = $db->escapeString($fn->xss_clean($_POST['loose_stock_unit_id']));
                    $weight = $db->escapeString($fn->xss_clean($_POST['weight_loose'][$i]));
                    $height = $db->escapeString($fn->xss_clean($_POST['height_loose'][$i]));
                    $length = $db->escapeString($fn->xss_clean($_POST['length_loose'][$i]));
                    $breadth = $db->escapeString($fn->xss_clean($_POST['breadth_loose'][$i]));

                    $sql = "INSERT INTO product_variant (product_id,type,measurement,measurement_unit_id,price,discounted_price,serve_for,stock,stock_unit_id,images,weight,height,length,breadth) VALUES('$product_id','$type','$measurement','$measurement_unit_id','$price','$discounted_price','$serve_for','$stock','$stock_unit_id','$variant_other_images','$weight','$height','$length','$breadth')";
                    $db->sql($sql);
                    $product_variant = $db->getResult();
                }
                if (!empty($product_variant)) {
                    $product_variant = 0;
                } else {
                    $product_variant = 1;
                }
            }
        }
        if ($product_result == 1 && $product_variant == 1) {
            $error['add_menu'] = "<section class='content-header'>
                                                <span class='label label-success'>Product Added Successfully</span>
                                                <h4><small><a  href='products.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Products</a></small></h4>
                                                 </section>";
        } else {
            $error['add_menu'] = " <span class='label label-danger'>Failed</span>";
        }
    }
    // } else {
    //     $error['check_permission'] = " <section class='content-header'> <span class='label label-danger'>You have no permission to create product</span></section>";
    // }


}
?>
<section class="content-header">
    <h1>Add Product</h1>
    <?php echo isset($error['add_menu']) ? $error['add_menu'] : ''; ?>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>

</section>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Add Product</h3>
                </div>
                <div class="box-header">
                    <?php echo isset($error['cancelable']) ? '<span class="label label-danger">Till status is .</span>' : ''; ?>
                </div>

                <!-- /.box-header -->
                <!-- form start -->
                <form id='add_product_form' method="post" enctype="multipart/form-data">
                    <?php
                    $sql = "SELECT * FROM unit";
                    $db->sql($sql);
                    $res_unit = $db->getResult();
                    ?>
                    <div class="box-body">
                        <div class="form-group">
                            <div class='col-md-6'>
                                <label for="exampleInputEmail1">Product Name</label> <i class="text-danger asterik">*</i><?php echo isset($error['name']) ? $error['name'] : ''; ?>
                                <input type="text" class="form-control" name="name">
                            </div>

                            <?php $db->sql("SET NAMES 'utf8'");
                            $sql = "SELECT * FROM `taxes` ORDER BY id DESC";
                            $db->sql($sql);
                            $taxes = $db->getResult();
                            ?>
                            <div class='col-md-6'>
                                <label class="control-label " for="taxes">Tax</label>
                                <select id='tax_id' name="tax_id" class='form-control'>
                                    <option value=''>Select Tax</option>
                                    <?php foreach ($taxes as $tax) { ?>
                                        <option value='<?= $tax['id'] ?>'><?= $tax['title'] . " - " . $tax['percentage'] . " %" ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class='col-md-12'>
                            <div class="form-group">
                                <label for="type"><br>Type</label><?php echo isset($error['type']) ? $error['type'] : ''; ?>
                                <label class="radio-inline"><input type="radio" name="type" id="packate" value="packet" checked>Packet</label>
                                <label class="radio-inline"><input type="radio" name="type" id="loose" value="loose">Loose</label>
                            </div>
                        </div>
                        <hr>
                        <div id="packate_div" style="display:none">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group packate_div">
                                        <label for="exampleInputEmail1">Measurement</label> <i class="text-danger asterik">*</i><input type="number" step="any" min="0" class="form-control" name="packate_measurement[]" />
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group packate_div">
                                        <label for="unit">Unit:</label>
                                        <select class="form-control" name="packate_measurement_unit_id[]">
                                            <?php
                                            foreach ($res_unit as  $row) {
                                                echo "<option value='" . $row['id'] . "'>" . $row['short_code'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group packate_div">
                                        <label for="price">Price (<?= $settings['currency'] ?>):</label> <i class="text-danger asterik">*</i><input type="number" step="any" min='0' class="form-control" name="packate_price[]" id="packate_price" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group packate_div">
                                        <label for="discounted_price">Discounted Price(<?= $settings['currency'] ?>):</label>
                                        <input type="number" step="any" min='0' class="form-control" name="packate_discounted_price[]" id="discounted_price" />
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group packate_div">
                                        <label for="qty">Stock:</label> <i class="text-danger asterik">*</i>
                                        <input type="number" step="any" min="0" class="form-control" name="packate_stock[]"="" />
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group packate_div">
                                        <label for="unit">Unit:</label>
                                        <select class="form-control" name="packate_stock_unit_id[]">
                                            <?php
                                            foreach ($res_unit as  $row) {
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
                                        <label for="exampleInputFile">Variant Images &nbsp;&nbsp;&nbsp;(Please choose square image of larger than 350px*350px & smaller than 550px*550px.)</label>
                                        <input type="file" name="packet_variant_images[0][]" id="packet_variant_images" multiple /><br />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group"><label>Weight<i class="text-danger asterik">*</i></label><input type="text" name="weight[]" class="form-control min_value"></div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group"><label>Length</label><input type="text" name="length[]" class="form-control min_value"></div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group"><label>Breadth</label><input type="text" name="breadth[]" class="form-control min_value"></div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group"><label>Height</label><input type="text" name="height[]" class="form-control min_value"></div>
                                </div>
                            </div>
                        </div>


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
                                            foreach ($res_unit as  $row) {
                                                echo "<option value='" . $row['id'] . "'>" . $row['short_code'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group loose_div">
                                        <label for="price">Price (<?= $settings['currency'] ?>):</label> <i class="text-danger asterik">*</i>
                                        <input type="number" step="any" min="0" class="form-control" name="loose_price[]" id="loose_price"="">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group loose_div">
                                        <label for="discounted_price">Discounted Price(<?= $settings['currency'] ?>):</label>
                                        <input type="number" step="any" min="0" class="form-control" name="loose_discounted_price[]" id="discounted_price" />
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <label>Variation</label>
                                    <a id="add_loose_variation" title="Add variation of product" style="cursor: pointer;"><i class="fa fa-plus-square-o fa-2x"></i></a>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group loose_div">
                                        <label for="exampleInputFile">Variant Images &nbsp;&nbsp;&nbsp;(Please choose square image of larger than 350px*350px & smaller than 550px*550px.)</label>
                                        <input type="file" name="loose_variant_images[0][]" id="loose_variant_images" class="loose_vari_image" multiple /><br />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group"><label>Weight<i class="text-danger asterik">*</i></label><input type="text" name="weight_loose[]" class="form-control min_value"></div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group"><label>Length</label><input type="text" name="length_loose[]" class="form-control min_value"></div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group"><label>Breadth</label><input type="text" name="breadth_loose[]" class="form-control min_value"></div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group"><label>Height</label><input type="text" name="height_loose[]" class="form-control min_value"></div>
                                </div>
                            </div>
                        </div>
                        <div id="variations">
                        </div>
                        <hr>
                        <div class="form-group" id="loose_stock_div" style="display:none;">
                            <label for="quantity">Stock :</label> <i class="text-danger asterik">*</i><?php echo isset($error['quantity']) ? $error['quantity'] : ''; ?>
                            <input type="number" step="any" min="0" class="form-control" name="loose_stock"><br>
                            <div class="form-group">
                                <label for="stock_unit"><br>Unit :</label><?php echo isset($error['stock_unit']) ? $error['stock_unit'] : ''; ?>
                                <select class="form-control" name="loose_stock_unit_id" id="loose_stock_unit_id">
                                    <?php
                                    foreach ($res_unit as $row) {
                                        echo "<option value='" . $row['id'] . "'>" . $row['short_code'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="packate_server_hide">
                            <label for="serve_for">Status :</label><?php echo isset($error['serve_for']) ? $error['serve_for'] : ''; ?>
                            <select name="serve_for" class="form-control">
                                <option value="Available">Available</option>
                                <option value="Sold Out">Sold Out</option>
                            </select>
                            <br />
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category :</label> <i class="text-danger asterik">*</i><?php echo isset($error['category_id']) ? $error['category_id'] : ''; ?>
                            <select name="category_id" id="category_id" class="form-control">
                                <option value="">--Select Category--</option>

                                <?php foreach ($res as $row) {
                                    if (!empty($row['id'])) { ?>
                                        <option value="<?= $row['id']; ?>"><?= $row['name']; ?></option>
                                    <?php  } else { ?>
                                        <option>No category</option>
                                <?php  }
                                } ?>
                            </select>
                            <br />
                        </div>
                        <div class="form-group">
                            <label for="subcategory_id">Sub Category :</label>
                            <select name="subcategory_id" id="subcategory_id" class="form-control">
                                <option value="">--Select Sub Category--</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="">Product Type :</label>
                            <select name="indicator" id="indicator" class="form-control">
                                <option value="0">--Select Type--</option>
                                <option value="1">Veg</option>
                                <option value="2">Non Veg</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="">Manufacturer :</label>
                            <input type="text" name="manufacturer" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Made In :</label>
                            <input type="text" name="made_in" class="form-control">
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
                                            <option value="local" <?= ($shipping_type == 'local') ? 'selected' : '' ?>>local Shipping</option>
                                            <option value="standard" <?= ($shipping_type == 'standard') ? 'selected' : '' ?>>Standard Shipping</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div class="row local">
                            <div class="">
                                <div class="col-md-4">
                                    <small><a href="#" class="local_shipping_info" data-toggle="modal" data-target="#exampleModal">What is delivery places?</a></small>
                                    <div class="form-group">
                                        <label for="product_pincodes">Delivery Places :</label><i class="text-danger asterik">*</i>
                                        <select name="product_pincodes" id="product_pincodes" class="form-control">
                                            <option value="">Select Option</option>
                                            <option value="included">Pincode Included</option>
                                            <option value="excluded">Pincode Excluded</option>
                                            <option value="all">Includes All</option>
                                        </select>
                                        <br />
                                    </div>
                                </div>

                                <div class="col-md-4 pincodes">
                                    <div class="form-group " style="margin-top: 18px;">
                                        <label for='pincode_ids_exc'>Select Pincodes <small>( Ex : 100,205, 360 <comma separated>)</small></label><?php echo isset($error['pincode_ids_exc']) ? $error['pincode_ids_exc'] : ''; ?>
                                        <select name='pincode_ids_exc[]' style="width: 520px;" id='pincode_ids_exc' class='form-control' placeholder='Enter the pincode you want to allow delivery this product' multiple="multiple">
                                            <?php $sql = 'select id,pincode from `pincodes` where `status` = 1 order by id desc';
                                            $db->sql($sql);
                                            $result = $db->getResult();
                                            foreach ($result as $value) {
                                            ?>
                                                <option value='<?= $value['id'] ?>'><?= $value['pincode'] ?></option>
                                            <?php } ?>

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
                        <hr>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="">Is Returnable? :</label><br>
                                    <input type="checkbox" id="return_status_button" class="js-switch">
                                    <input type="hidden" id="return_status" name="return_status">
                                </div>
                            </div>
                            <div class="col-md-3" id="return_day" style="display:none">
                                <div class="form-group">
                                    <label for="return_day">Max Return Days :</label>
                                    <input type="number" step="any" min="0" class="form-control" placeholder="Number of days to Return" name="return_days" id="return_days" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="">Is cancel-able? :</label><br>
                                    <input type="checkbox" id="cancelable_button" class="js-switch">
                                    <input type="hidden" id="cancelable_status" name="cancelable_status">
                                </div>
                            </div>
                            <div class="col-md-3" id="till-status" style="display:none">
                                <div class="form-group">
                                    <label for="">Till which status? :</label> <i class="text-danger asterik">*</i> <?php echo isset($error['cancelable']) ? $error['cancelable'] : ''; ?><br>
                                    <select id="till_status" name="till_status" class="form-control">
                                        <option value="">Select</option>
                                        <option value="received">Received</option>
                                        <option value="processed">Processed</option>
                                        <option value="shipped">Shipped</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="">Is COD allowed? :</label><br>
                                    <input type="checkbox" id="cod_allowed_button" class="js-switch">
                                    <input type="hidden" id="cod_allowed_status" name="is_cod_allowed">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="">Total allowed quantity : <small>[Keep blank if no such limit]</small></label>
                                    <input type="number" min="1" class="form-control" name="max_allowed_quantity" />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="image">Main Image : <i class="text-danger asterik">*</i>&nbsp;&nbsp;&nbsp;*Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?php echo isset($error['image']) ? $error['image'] : ''; ?>
                            <input type="file" name="image" id="image">
                        </div>
                        <div class="form-group">
                            <label for="other_images">Other Images of the Product: *Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?php echo isset($error['other_images']) ? $error['other_images'] : ''; ?>
                            <input type="file" name="other_images[]" id="other_images" multiple>
                        </div>
                        <div class="form-group">
                            <label for="description">Description :</label> <i class="text-danger asterik">*</i><?php echo isset($error['description']) ? $error['description'] : ''; ?>
                            <textarea name="description" id="description" class="form-control" rows="8"></textarea>
                            <script type="text/javascript" src="../css/js/ckeditor/ckeditor.js"></script>
                            <script type="text/javascript">
                                CKEDITOR.replace('description');
                            </script>
                        </div>
                    </div>
                    <!-- /.box-body -->
                    <div class="box-footer">
                        <input type="submit" class="btn-primary btn" value="Add" id="btnAdd" name="btnAdd" />&nbsp;
                        <input type="reset" class="btn-danger btn" value="Clear" id="btnClear" />
                        <!--<div  id="res"></div>-->
                    </div>
                </form>
            </div>
            <!-- /.box -->
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

<input type="hidden" name="" id="seller_id" value="<?php print_r($_SESSION['seller_id']) ?>">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
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

    function fetchPickup_locations(standard_shipping, seller_id) {
        if (seller_id != 0) {
            $('#sellers_pickup_locations').removeClass('hide');

            if (standard_shipping == 1) {
                $.ajax({

                    type: 'POST',
                    url: "public/db-operation.php",
                    data: {
                        get_pickup_location: 1,
                        current_seller_id: seller_id
                    },
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
                                    seller_pickup_locations = '<option class="appended-options" selected  value="' + location.pickup_location + '">' + location.pickup_location + ' ' + location.pin_code + '</option>';
                                    $('#sellers_pickup_locations').append(seller_pickup_locations)
                                    $('.defualt_select').attr("disabled", true)
                                    console.log(seller_pickup_locations);
                                })
                            } else {
                                pickup_locations.forEach(location => {
                                    seller_pickup_locations = '<option class="appended-options" value="' + location.pickup_location + '">' + location.pickup_location + ' ' + location.pin_code + '</option>';
                                    $('#sellers_pickup_locations').append(seller_pickup_locations)
                                    console.log(seller_pickup_locations);
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
</script>



<?php
if ($shipping_type == 'standard') { ?>
    <script>
        var seller_id = $('#seller_id').val();
        var standard_shipping = 1;
        fetchPickup_locations(standard_shipping, seller_id);
    </script>

<?php
}

?>
<script>
    $('#shipping_type').on('change', function() {
        let shipping_type = $(this).val();
        if (shipping_type == 'standard') {
            $('.standard').show();
            $('.local').hide();
        } else {
            $('.standard').hide();
            $('.local').show();
        }
        var seller_id = $('#seller_id').val();
        var standard_shipping = 1
        fetchPickup_locations(standard_shipping, seller_id)
    })
    var shipping_type = "<?= $shipping_type ?>"
    if (shipping_type == "standard") {
        $('.standard').show();
        $('.local').hide();
        var seller_id = $('#seller_id').val();
        var standard_shipping = 1
        fetchPickup_locations(standard_shipping, seller_id)
    } else {
        $('.standard').hide();
        $('.local').show();
    }

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
</script>
<script>
    var changeCheckbox = document.querySelector('#cod_allowed_button');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#cod_allowed_status').val(1);
        } else {
            $('#cod_allowed_status').val(0);
        }
    };
</script>
<script>
    $('#pincode_ids_inc').select2({
        width: 'element',
        placeholder: 'type in category name to search',

    });
    $('#pincode_ids_exc').prop('disabled', true);

    $('#product_pincodes').on('change', function() {
        var val = $('#product_pincodes').val();
        if (val == "included" || val == "excluded") {
            $('#pincode_ids_exc').prop('disabled', false);
        } else {
            $('#pincode_ids_exc').prop('disabled', true);
        }
    });
    $('#pincode_ids_exc').select2({
        width: 'element',
        placeholder: 'type in category name to search',

    });
    if ($('#packate').prop('checked')) {
        $('#packate_div').show();
        $('#packate_server_hide').hide();
        $('.loose_div').children(":input").prop('disabled', true);
        $('#loose_stock_div').children(":input").prop('disabled', true);
    }

    $.validator.addMethod('lessThanEqual', function(value, element, param) {
        return this.optional(element) || parseInt(value) < parseInt($(param).val());
    }, "Discounted Price should be lesser than Price");
</script>

<script>
    function min_value(element) {
        if ($(element).val() <= 0) {
            $('.error_status').remove();
            $(element).parent().append('<span class="text-danger error_status">value shoud be not less then or equal to zero</span>');
            $(element).attr('disabled', false);
            $('#btnAdd').attr('disabled', true)
        } else {
            $('.error_status').remove();
            $('#btnAdd').attr('disabled', false)
        }
    }

    var num = 2;
    var i = 1;
    $('.min_value').on('keyup', function() {

        console.log($(this).val())
        if ($(this).val() <= 0) {
            $('.error_status').remove();
            $(this).parent().append('<span class="text-danger error_status">value shoud be not less then or equal to zero</span>');
            $(this).attr('disabled', false);
            $('#btnAdd').attr('disabled', true)
        } else {
            $('.error_status').remove();
            $('#btnAdd').attr('disabled', false)
        }
    });

    $('#add_packate_variation').on('click', function() {
        html = '<div class="row"><div class="col-md-2"><div class="form-group"><label for="measurement">Measurement</label> <i class="text-danger asterik">*</i>' +
            '<input type="number" class="form-control" name="packate_measurement[]" ="" step="any" min="0"></div></div>' +
            '<div class="col-md-1"><div class="form-group">' +
            '<label for="measurement_unit">Unit</label><select class="form-control" name="packate_measurement_unit_id[]">' +
            '<?php
                foreach ($res_unit as $row) {
                    echo "<option value=" . $row['id'] . ">" . $row['short_code'] . "</option>";
                }
                ?>' +
            '</select></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label for="price">Price(<?= $settings['currency'] ?>):</label> <i class="text-danger asterik">*</i>' +
            '<input type="number" step="any" min="0" class="form-control" name="packate_price[]" =""></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label for="discounted_price">Discounted Price(<?= $settings['currency'] ?>):</label>' +
            '<input type="number" step="any" min="0" class="form-control" name="packate_discounted_price[]" /></div></div>' +
            '<div class="col-md-1"><div class="form-group"><label for="stock">Stock:</label> <i class="text-danger asterik">*</i>' +
            '<input type="number" step="any" min="0" class="form-control" name="packate_stock[]" /></div></div>' +
            '<div class="col-md-1"><div class="form-group"><label for="unit">Unit:</label>' +
            '<select class="form-control" name="packate_stock_unit_id[]">' +
            '<?php
                foreach ($res_unit as  $row) {
                    echo "<option value=" . $row['id'] . ">" . $row['short_code'] . "</option>";
                }
                ?>' +
            '</select>' +
            '</div></div>' +
            '<div class="col-md-2"><div class="form-group packate_div"><label for="qty">Status:</label><select name="packate_serve_for[]" class="form-control" ><option value="Available">Available</option><option value="Sold Out">Sold Out</option></select></div></div>' +
            '<div class="col-md-1" style="display: grid;"><label>Remove</label><a class="remove_variation text-danger" title="Remove variation of product" style="cursor: pointer;"><i class="fa fa-times fa-2x"></i></a></div>' +

            '<div class="col-md-4"><div class="form-group packate_div">' +
            '<label for="exampleInputFile">Variant Images &nbsp;&nbsp;&nbsp;(Please choose square image of larger than 350px*350px & smaller than 550px*550px.)</label>' +
            '<input type="file" name="packet_variant_images[' + i++ + '][]" id="packet_variant_images" multiple /><br />' +
            '</div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Weight <i class="text-danger">*</i></label><input type="text" name="weight[]" onkeyup="min_value(this)" class="form-control min_value"></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Length</label><input type="text"  name="length[]" class="form-control min_value" onkeyup="min_value(this)"></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Breadth</label><input type="text"  name="breadth[]" class="form-control min_value" onkeyup="min_value(this)"></div></div>' +
            '<div class="col-md-1"><div class="form-group"><label>Height</label><input type="text" name="height[]"  class="form-control min_value" onkeyup="min_value(this)"></div></div>' +

            '</div>';

        $('#variations').append(html);
        $('#add_product_form').validate();
    });

    $('#add_loose_variation').on('click', function() {
        html = '<div class="row"><div class="col-md-4"><div class="form-group"><label for="measurement">Measurement</label> <i class="text-danger asterik">*</i>' +
            '<input type="number" step="any" min="0" class="form-control" name="loose_measurement[]" =""></div></div>' +
            '<div class="col-md-2"><div class="form-group loose_div">' +
            '<label for="unit">Unit:</label><select class="form-control" name="loose_measurement_unit_id[]">' +
            '<?php
                foreach ($res_unit as  $row) {
                    echo "<option value=" . $row['id'] . ">" . $row['short_code'] . "</option>";
                }
                ?>' +
            '</select></div></div>' +
            '<div class="col-md-3"><div class="form-group"><label for="price">Price  (<?= $settings['currency'] ?>):</label> <i class="text-danger asterik">*</i>' +
            '<input type="number" step="any" min="0" class="form-control" name="loose_price[]" =""></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label for="discounted_price">Discounted Price(<?= $settings['currency'] ?>):</label>' +
            '<input type="number" step="any"  min="0" class="form-control" name="loose_discounted_price[]" /></div></div>' +
            '<div class="col-md-1" style="display: grid;"><label>Remove</label><a class="remove_variation text-danger" title="Remove variation of product" style="cursor: pointer;"><i class="fa fa-times fa-2x"></i></a></div>' +

            '<div class="col-md-4">' +
            '<div class="form-group packate_div">' +
            '<label for="exampleInputFile">Variant Images &nbsp;&nbsp;&nbsp;(Please choose square image of larger than 350px*350px & smaller than 550px*550px.)</label>' +
            '<input type="file" name="loose_variant_images[' + i++ + '][]" id="loose_variant_images" class="loose_vari_image" multiple /><br />' +
            '</div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Weight<i class="text-danger">*</i></label><input type="text" name="weight_loose[]" onkeyup="min_value(this)" class="form-control min_value"></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Length</label><input type="text"  name="length_loose[]" class="form-control min_value" onkeyup="min_value(this)"></div></div>' +
            '<div class="col-md-2"><div class="form-group"><label>Breadth</label><input type="text"  name="breadth_loose[]" class="form-control min_value" onkeyup="min_value(this)"></div></div>' +
            '<div class="col-md-1"><div class="form-group"><label>Height</label><input type="text" name="height_loose[]"  class="form-control min_value" onkeyup="min_value(this)"  ></div></div>' +
            '</div>';
        $('#variations').append(html);
    });
</script>
<script>
    $('#add_product_form').validate({

        ignore: [],
        debug: false,
        rules: {
            name: "",
            measurement: "",
            price: "",
            quantity: "",
            image: "",
            category_id: "",
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
    $('#btnClear').on('click', function() {
        for (instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].setData('');
        }
    });
</script>
<script>
    $(document).on('click', '.remove_variation', function() {
        $(this).closest('.row').remove();
    });


    $(document).on('change', '#category_id', function() {
        $.ajax({
            url: "public/db-operation.php",
            data: "category_id=" + $('#category_id').val() + "&change_category=1",
            method: "POST",
            success: function(data) {
                $('#subcategory_id').html("<option value=''>---Select Subcategory---</option>" + data);
            }
        });
    });

    $(document).on('change', '#packate', function() {
        $('#variations').html("");
        $('#packate_div').show();
        $('#packate_server_hide').hide();
        $('.packate_div').children(":input").prop('disabled', false);
        $('#loose_div').hide();
        $('.loose_div').children(":input").prop('disabled', true);
        $('#loose_stock_div').hide();
        $('#loose_stock_unit_id').hide();
        $('#loose_stock_div').children(":input").prop('disabled', true);

    });
    $(document).on('change', '#loose', function() {
        $('#variations').html("");
        $('#loose_div').show();
        $('.loose_div').children(":input").prop('disabled', false);
        $('#loose_stock_div').show();
        $('#loose_stock_div').children(":input").prop('disabled', false);
        $('#packate_server_hide').show();
        $('#packate_div').hide();
        $('.packate_div').children(":input").prop('disabled', true);

    });
</script>