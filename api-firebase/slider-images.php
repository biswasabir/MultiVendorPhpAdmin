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
include_once('../includes/functions.php');
$function = new functions;
include_once('verify-token.php');
$db = new Database();
$db->connect();
$response = array();

if (!isset($_POST['accesskey'])) {
    if (!isset($_GET['accesskey'])) {
        $response['error'] = true;
        $response['message'] = "Access key is invalid or not passed!";
        print_r(json_encode($response));
        return false;
    }
}

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

if ((isset($_POST['add-image'])) && ($_POST['add-image'] == 1)) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['home_sliders']['create'] == 0) {
        $response["message"] = "<p class='alert alert-danger'>You have no permission to create home slider.</p>";
        echo json_encode($response);
        return false;
    }
    $image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
    $image_error = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
    $image_type = $db->escapeString($fn->xss_clean($_FILES['image']['type']));
    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $slider_url = $db->escapeString($fn->xss_clean($_POST['slider_url']));
    $id = ($type != 'default') && ($type != 'slider_url')  ? $db->escapeString($fn->xss_clean($_POST[$type])) : "0";

    // create array variable to handle error
    $error = array();
    // common image file extensions
    $allowedExts = array("gif", "jpeg", "jpg", "png");

    // get image file extension
    error_reporting(E_ERROR | E_PARSE);
    $extension = end(explode(".", $_FILES["image"]["name"]));
    if ($image_error > 0) {
        $error['image'] = " <span class='label label-danger'>Not uploaded!</span>";
    } else {
        $result = $fn->validate_image($_FILES['image']);
        if (!$result) {
            $response["message"] = "<span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
            echo json_encode($response);
            $error['image'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
            return false;
        }
    }
    if (empty($error['image'])) {
        // create random image file name
        $mt = explode(' ', microtime());
        $microtime = ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
        $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);

        $image = $microtime . "." . $extension;
        // upload new image
        $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../upload/slider/' . $image);

        // insert new data to menu table
        $upload_image = 'upload/slider/' . $image;
        $sql = "INSERT INTO `slider`(`image`,`type`, `type_id`,`slider_url`) VALUES ('$upload_image','" . $type . "','" . $id . "','" . $slider_url . "')";
        $db->sql($sql);
        $res = $db->getResult();
        $sql = "SELECT id FROM `slider` ORDER BY id DESC";
        $db->sql($sql);
        $res = $db->getResult();
        $response["message"] = "<span class='label label-success'>Image Uploaded Successfully!</span>";
        $response["id"] = $res[0]['id'];
    } else {
        $response["message"] = "<span class='label label-daner'>Image could not be Uploaded!Try Again!</span>";
    }
    echo json_encode($response);
}
if (isset($_GET['type']) && $_GET['type'] != '' && $_GET['type'] == 'delete-slider') {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['home_sliders']['delete'] == 0) {
        echo 2;
        return false;
    }

    $id        = $_GET['id'];
    $image     = $_GET['image'];

    if (!empty($image))
        unlink('../' . $image);

    $sql = 'DELETE FROM `slider` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}
if (isset($_POST['get-slider-images'])) {
    // if (!verify_token()) {
    //     return false;
    // }
    $sql = 'select * from slider order by id desc';
    $db->sql($sql);
    $result = $db->getResult();
    $response = $temp = $temp1 = array();
    if (!empty($result)) {
        $response['error'] = false;
        foreach ($result as $row) {
            $name = "";
            if ($row['type'] == 'category') {
                $sql = 'select `name` from category where id = ' . $row['type_id'] . ' order by id desc';
                $db->sql($sql);
                $result1 = $db->getResult();
                $name = (!empty($result1[0]['name'])) ? $result1[0]['name'] : "";
                $slug = $function->slugify($db->escapeString($fn->xss_clean($name)));
            }
            if ($row['type'] == 'product') {
                $sql = 'select `name` from products where id = ' . $row['type_id'] . ' order by id desc';
                $db->sql($sql);
                $result1 = $db->getResult();
                $name = (!empty($result1[0]['name'])) ? $result1[0]['name'] : "";
                $slug = $function->slugify($db->escapeString($fn->xss_clean($name)));
            }

            $temp['type'] = $row['type'];
            $temp['type_id'] = $row['type_id'];
            $temp['name'] = $name;
            $temp['slug'] = $row['type_id'] == 0 ? "" : $slug;
            $temp['slider_url'] = !empty($row['slider_url']) ? $row['slider_url'] : "";
            $temp['image'] = DOMAIN_URL . $row['image'];
            $temp1[] = $temp;
        }
        $response['data'] = $temp1;
    } else {
        $response['error'] = true;
        $response['message'] = "No slider images uploaded yet!";
    }
    print_r(json_encode($response));
}
