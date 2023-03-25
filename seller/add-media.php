<?php
session_start();

include_once('../includes/functions.php');
include_once('../includes/custom-functions.php');
$fn = new custom_functions;

require_once '../includes/crud.php';
$db = new Database();
$db->connect();

if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
    echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
    return false;
}
$count = count($_FILES['documents']['name']);
for ($i = 0; $i < $count; $i++) {
    if (!empty($_FILES['documents']['name'][$i])) {
        $image_name = $db->escapeString($fn->xss_clean($_FILES['documents']['name'][$i]));
        $image_type =  $db->escapeString($fn->xss_clean($_FILES['documents']['type'][$i]));
        $tmp_name =  $db->escapeString($fn->xss_clean($_FILES['documents']['tmp_name'][$i]));
        $image_error =  $db->escapeString($fn->xss_clean($_FILES['documents']['error'][$i]));
        $size =  $db->escapeString($fn->xss_clean($_FILES['documents']['size'][$i]));
        $data = array();
        $allowedExts = array("gif", "jpeg", "jpg", "png");

        $target_path = DOMAIN_URL . 'upload/media/';
        $result = $fn->validate_other_images($_FILES["documents"]["tmp_name"][$i], $_FILES["documents"]["type"][$i]);
        if (!$result) {
            $response['error'] = true;
            $response['message'] = "Image type must jpg, jpeg, gif, or png!";
            echo json_encode($response);
            return false;
        }
        $error = array();
        $arr = explode(".", $image_name);
        $extension = strtolower(array_pop($arr));
        error_reporting(E_ERROR | E_PARSE);
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $image = time() . rand('1000', '9999') . "." . $extension;
        if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], '../upload/media/' . $image)) {
            $sub_directory = 'upload/media/';
            $id = $_SESSION['seller_id'];

            $sql = "INSERT INTO `media`(`name`,`extension`,`type`,`sub_directory`,`size`,`seller_id`) VALUES ('" . $image . "','" . $extension . "','" . $image_type . "','" . $sub_directory . "','" . $size . "','" . $id . "')";
            $db->sql($sql);
            $res = $db->getResult();

            $response['error'] = false;
            $response['message'] = "Image Uploaded Successfully";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Image could not be Uploaded!Try Again";
    }
    print_r(json_encode($response));
}
