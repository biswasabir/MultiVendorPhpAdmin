<?php
include_once('includes/crud.php');
include_once('includes/custom-functions.php');
$db = new Database();
$fn = new custom_functions();
$data = file_get_contents('php://input');
$data = json_decode($data, 1);



if (isset($_SERVER['HTTP_X_API_KEY']) && !empty($_SERVER['HTTP_X_API_KEY'])) {
    $get_token = json_decode($fn->get_settings('shiprocket'), 1)['webhook_token'];
    if ($get_token == $_SERVER['HTTP_X_API_KEY']) {
        if (isset($data) && !empty($data)) {
            if (isset($data['awb']) && !empty($data['awb'])) {
                if ($data['current_status'] == "Delivered") {
                    $order_details = $fn->get_data(['order_item_id', 'order_id'], 'awb_code=' . $data['awb'], "order_trackings");
                    if (!empty($order_details)) {

                        $item_ids = [];
                        foreach ($order_details as $items) {
                            $items_ids[] = $items['order_item_id'];
                        }
                        $order_id = $order_details[0]['order_id'];
                        $order_item_ids = implode(',', array_unique($items_ids));

                        $sql = "UPDATE order_items SET active_status='delivered' where id in ($order_item_ids) and order_id=$order_id";
                        $db->connect();
                        if ($db->sql($sql)) {
                            $res['error'] = false;
                            $res['message'] = "order updated";
                            $res['date'] = date('d-m-y') . " " . date("h:i:sa");
                        } else {
                            $res['error'] = true;
                            $res['message'] = "order not updated";
                            $res['date'] = date('d-m-y') . " " . date("h:i:sa");
                        }
                    } else {
                        $res['error'] = true;
                        $res['message'] = "order not found or may be AWB miss matched";
                        $res['date'] = date('d-m-y') . " " . date("h:i:sa");
                    }
                } else {
                    $res['error'] = true;
                    $res['message'] = "order is not delivered but its is in " . $data['current_status'];
                    $res['current_status'] = $data['current_status'];
                    $res['date'] = date('d-m-y') . " " . date("h:i:sa");
                }
            } else {
                $res['error'] = true;
                $res['message'] = "awb not found";
                $res['date'] = date('d-m-y') . " " . date("h:i:sa");
            }
        } else {
            $res['error'] = true;
            $res['message'] = "data not found";
            $res['date'] = date('d-m-y') . " " . date("h:i:sa");
        }
    } else {
        $res['error'] = true;
        $res['message'] = "token not veified";
        $res['date'] = date('d-m-y') . " " . date("h:i:sa");
    }
} else {
    $res['error'] = true;
    $res['message'] = "token required";
    $res['date'] = date('d-m-y') . " " . date("h:i:sa");
}

$file = fopen('shiprocket-webhook.txt', 'a');

//store response in file
fwrite($file, PHP_EOL);
fwrite($file, var_export($res, true));
fwrite($file, PHP_EOL);
