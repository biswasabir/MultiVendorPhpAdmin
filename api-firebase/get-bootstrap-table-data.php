<?php
session_start();

// set time for session timeout
$currentTime = time() + 25200;
$expired = 3600;

// if session not set go to login page
if (!isset($_SESSION['user'])) {
    header("location:index.php");
}

// if current time is more than session timeout back to login page
if ($currentTime > $_SESSION['timeout']) {
    session_destroy();
    header("location:index.php");
}

// destroy previous session timeout and create new one
unset($_SESSION['timeout']);
$_SESSION['timeout'] = $currentTime + $expired;

header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


include_once('../includes/custom-functions.php');
$fn = new custom_functions;
include_once('../includes/crud.php');
include_once('../includes/variables.php');
$db = new Database();
$db->connect();
$config = $fn->get_configurations();
$low_stock_limit = $config['low-stock-limit'];

if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

if (isset($_GET['table']) && $_GET['table'] == 'orders') {
    $offset = 0;
    $limit = 10;
    $sort = 'o.id';
    $order = 'DESC';
    $where = ' ';
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = $db->escapeString($fn->xss_clean($_GET['start_date']));
        $end_date = $db->escapeString($fn->xss_clean($_GET['end_date']));
        if ($start_date == $end_date) {
            $where .= " where DATE(o.date_added)='" . $start_date . "'";
        } else {
            $where .= " where DATE(o.date_added)>=DATE('" . $start_date . "') AND DATE(o.date_added)<=DATE('" . $end_date . "')";
        }
    }
    // echo $where;
    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $where .= " AND (name like '%" . $search . "%' OR o.id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR address like '%" . $search . "%' OR `payment_method` like '%" . $search . "%' OR `delivery_charge` like '%" . $search . "%' OR `delivery_time` like '%" . $search . "%' OR o.`status` like '%" . $search . "%' OR o.`date_added` like '%" . $search . "%')";
        } else {
            $where .= " where (u.name like '%" . $search . "%' OR o.id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR address like '%" . $search . "%' OR `payment_method` like '%" . $search . "%' OR `delivery_charge` like '%" . $search . "%' OR `delivery_time` like '%" . $search . "%' OR o.`status` like '%" . $search . "%' OR o.`date_added` like '%" . $search . "%')";
        }
    }
    if (isset($_GET['filter_order']) && $_GET['filter_order'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_GET['filter_order']));
        if (isset($_GET['search']) && $_GET['search'] != '') {
            $where .= " and oi.`active_status`='" . $filter_order . "'";
        } elseif (isset($_GET['start_date']) && $_GET['start_date'] != '') {
            $where .= " and oi.`active_status`='" . $filter_order . "'";
        } else {
            $where .= " where oi.`active_status`='" . $filter_order . "'";
        }
    }
    if (isset($_GET['seller_id']) && $_GET['seller_id'] != '') {
        $seller_id = $_GET['seller_id'];
        if (empty(trim($where))) {
            $where .= ' where oi.seller_id = ' . $seller_id;
        } else {
            $where .= ' and oi.seller_id = ' . $seller_id;
        }
    }
    if (isset($_GET['shipping_type']) && $_GET['shipping_type'] != '') {
        $shipping_type = $_GET['shipping_type'];
        if (empty(trim($where))) {
            if ($shipping_type == 2) {
            } else {
                $where .= ' where pd.standard_shipping = ' . $shipping_type;
            }
        } else {
            if ($shipping_type == 2) {
            } else {
                $where .= ' and pd.standard_shipping = ' . $shipping_type;
            }
        }
    }
    $sql = "SELECT COUNT(distinct(o.id)) as total FROM `orders` o LEFT JOIN order_items oi ON o.id=oi.order_id JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products pd on pd.id=pv.product_id LEFT JOIN users u ON u.id=o.user_id" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "select o.*,u.name FROM orders o LEFT JOIN users u ON u.id=o.user_id  LEFT JOIN pincodes p ON p.id=o.pincode_id LEFT JOIN order_items oi ON o.id=oi.order_id JOIN product_variant pv on pv.id=oi.product_variant_id JOIN products pd on pd.id=pv.product_id  " . $where . " GROUP BY o.id ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    for ($i = 0; $i < count($res); $i++) {
        $sql = "select oi.*,d.name as d_name,s.name as s_name, u.name as uname,oi.active_status as order_status from `order_items` oi 
			    left join users u ON u.id=oi.user_id
                left join delivery_boys d ON d.id=oi.delivery_boy_id
                left join seller s ON s.id=oi.seller_id
			    where oi.order_id=" . $res[$i]['id'];
        // echo $sql;
        $db->sql($sql);
        $res[$i]['items'] = $db->getResult();
    }
    $bulkData = $rows = $tempRow = array();
    $bulkData['total'] = $total;

    foreach ($res as $row) {
        $items = $row['items'];
        $seller_name = implode(",", array_values(array_unique(array_column($items, "s_name"))));

        $items1 = $temp = $temp1 = '';
        $total_amt = 0;
        $temp = '';
        $status = json_decode($row['status']);
        if (!empty($status)) {
            foreach ($status as $st) {
                $temp .= $st[0] . " : " . $st[1] . "<br>------<br>";
            }
        }
        foreach ($items as $item) {

            if ($item['order_status'] == 'received') {
                $active_status = '<label class="label label-primary">' . $item['order_status'] . '</label>';
            }
            if ($item['order_status'] == 'awaiting_payment') {
                $active_status = '<label class="label label-secondary">Awaiting Payment</label>';
            }
            if ($item['order_status'] == 'processed') {
                $active_status = '<label class="label label-info">' . $item['order_status'] . '</label>';
            }
            if ($item['order_status'] == 'shipped') {
                $active_status = '<label class="label label-warning">' . $item['order_status'] . '</label>';
            }
            if ($item['order_status'] == 'delivered') {
                $active_status = '<label class="label label-success">' . $item['order_status'] . '</label>';
            }
            if ($item['order_status'] == 'returned' || $item['order_status'] == 'cancelled') {
                $active_status = '<label class="label label-danger">' . $item['order_status'] . '</label>';
            }

            $deliver_by = !empty($item['d_name']) ? $item['d_name'] : 'Not Assigned';
            $seller = !empty($item['s_name']) ? $item['s_name'] : '';
            $temp1 .= "<b>Item ID :</b>" . $item['id'] . "<b> Product Variant Id :</b> " . $item['product_variant_id'] . "<b> Seller Name :</b> " . $seller . "<b> Name : </b>" . $item['product_name'] . " <b>Unit : </b>" . $item['variant_name']  . " <b>Price : </b>" . $item['price'] . " <b>QTY : </b>" . $item['quantity'] . " <b>Subtotal : </b>" . $item['quantity'] * $item['price'] . " " . $active_status . " <b>Deliver BY : </b>" . $deliver_by . "<br>------<br>";
            $total_amt += $item['sub_total'];
        }
        $items1 = $temp1;

        $operate = "<a class='btn btn-sm btn-primary edit-fees' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editFeesModal'>Edit</a>";

        $operate .= "<a onclick='return conf(\"delete\");' class='btn btn-sm btn-danger' href='../public/db_operations.php?id=" . $row['id'] . "&delete_order=1' target='_blank'>Delete</a>";
        if (!empty($row['items'][0]['discount'])) {
            $discounted_amount = $row['total'] * $row['items'][0]['discount'] / 100;
            $final_total = $row['total'] - $discounted_amount;
            $discount_in_rupees = $row['total'] - $final_total;
            $discount_in_rupees = floor($discount_in_rupees);
        } else {
            $discount_in_rupees = "0";
        }

        $tempRow['id'] = $row['id'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['name'] = (!empty($row['items'][0]['uname'])) ? $row['items'][0]['uname'] : " ";
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
        }
        $tempRow['delivery_charge'] = $row['delivery_charge'];
        $tempRow['items'] = $items1;
        $tempRow['total'] = $row['total'];
        $tempRow['tax'] = $row['tax_amount'] . '(' . $row['tax_percentage'] . '%)';
        $tempRow['promo_discount'] = $row['promo_discount'];
        $tempRow['wallet_balance'] = $row['wallet_balance'];
        if (!empty($row['items'][0]['discount'])) {
            $tempRow['discount'] = $discount_in_rupees . '(' . $row['items'][0]['discount'] . '%)';
        } else {
            $tempRow['discount'] = "0";
        }
        $tempRow['qty'] = (!empty($row['items'][0]['quantity'])) ? $row['items'][0]['quantity'] : "0";
        $tempRow['seller_name'] = $seller_name;
        $tempRow['final_total'] = $row['final_total'];
        $tempRow['promo_code'] = $row['promo_code'];
        $tempRow['order_note'] = (!empty($row['order_note']) || $row['order_note'] != "") ? $row['order_note'] : '';
        $tempRow['area_id'] = (!empty($row['area_id']) || $row['area_id'] != "") ? $row['area_id'] : '';
        $tempRow['payment_method'] = $row['payment_method'];
        $tempRow['address'] = $row['address'];
        $tempRow['delivery_time'] = $row['delivery_time'];
        $tempRow['active_status'] = $active_status;
        $tempRow['wallet_balance'] = $row['wallet_balance'];
        $tempRow['date_added'] = date('d-m-Y', strtotime($row['date_added']));
        $tempRow['operate'] = '<a href="order-detail.php?id=' . $row['id'] . '"><i class="fa fa-eye"></i> View</a>
				<br><a href="delete-order.php?id=' . $row['id'] . '"><i class="fa fa-trash"></i> Delete</a>';
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

//data of 'ORDERS' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'order_items') {
    $offset = 0;
    $limit = 10;
    $sort = 'o.id';
    $order = 'DESC';
    $where = ' ';
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = $db->escapeString($fn->xss_clean($_GET['start_date']));
        $end_date = $db->escapeString($fn->xss_clean($_GET['end_date']));
        $where .= " and DATE(o.date_added)>=DATE('" . $start_date . "') AND DATE(o.date_added)<=DATE('" . $end_date . "')";
    }
    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " AND (oi.product_name like '%" . $search . "%' OR u.name like '%" . $search . "%' OR oi.id like '%" . $search . "%'OR oi.order_id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR o.address like '%" . $search . "%' OR o.`payment_method` like '%" . $search . "%' OR o.`delivery_charge` like '%" . $search . "%' OR o.`delivery_time` like '%" . $search . "%' OR oi.`status` like '%" . $search . "%' OR o.`date_added` like '%" . $search . "%')";
    }
    if (isset($_GET['filter_order']) && $_GET['filter_order'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_GET['filter_order']));
        $where .= " and oi.`active_status`='" . $filter_order . "'";
    }
    if (isset($_GET['seller_id']) && $_GET['seller_id'] != '') {
        $seller_id = $db->escapeString($fn->xss_clean($_GET['seller_id']));

        $where .= " and oi.seller_id= $seller_id";
    }
    $sql = "select COUNT(oi.id) as total from `order_items` oi  left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id" . $where;
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    $sql = "select oi.*,o.mobile,o.order_note,o.total ,o.delivery_charge,o.discount,o.promo_code,o.promo_discount,o.wallet_balance,o.final_total,o.payment_method,o.address,o.delivery_time, u.name as uname,oi.status as order_status from `order_items` oi  left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id  $where ORDER BY $sort $order LIMIT $offset , $limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    foreach ($res as $row) {
        $temp = '';
        $total_amt = 0;
        $temp = '';
        $status = json_decode($row['order_status']);

        if (!empty($status)) {
            foreach ($status as $st) {
                $temp .= $st[0] . " : " . $st[1] . "<br>------<br>";
            }
        }
        if ($row['active_status'] == 'received') {
            $active_status = '<label class="label label-primary">' . $row['active_status'] . '</label>';
        }
        if ($row['active_status'] == 'awaiting_payment') {
            $active_status = '<label class="label label-secondary">Awaiting Payment</label>';
        }
        if ($row['active_status'] == 'processed') {
            $active_status = '<label class="label label-info">' . $row['active_status'] . '</label>';
        }
        if ($row['active_status'] == 'shipped') {
            $active_status = '<label class="label label-warning">' . $row['active_status'] . '</label>';
        }
        if ($row['active_status'] == 'delivered') {
            $active_status = '<label class="label label-success">' . $row['active_status'] . '</label>';
        }
        if ($row['active_status'] == 'returned' || $row['active_status'] == 'cancelled') {
            $active_status = '<label class="label label-danger">' . $row['active_status'] . '</label>';
        }
        $sql = "select name from delivery_boys where id=" . $row['delivery_boy_id'];
        $db->sql($sql);
        $res_dboy = $db->getResult();
        $sql = "select name from seller where id=" . $row['seller_id'];
        $db->sql($sql);
        $res_seller = $db->getResult();
        $status = $temp;

        $discounted_amount = $row['total'] * $row['discount'] / 100;
        $final_total = $row['total'] - $discounted_amount;
        $discount_in_rupees = $row['total'] - $final_total;
        $discount_in_rupees = floor($discount_in_rupees);
        $tempRow['id'] = $row['id'];
        $tempRow['order_id'] = $row['order_id'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['name'] = $row['uname'];
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
        }
        $tempRow['order_note'] = $row['order_note'];
        $tempRow['is_credited'] = (empty($row['is_credited'])) ? '<label class="label label-danger">Not Credited</label>' : '<label class="label label-success">Credited</label>';
        $tempRow['product_name'] = $row['product_name'] . " (" . $row['variant_name'] . ")";
        $tempRow['product_variant_id'] = $row['product_variant_id'];
        $tempRow['total'] = $row['sub_total'];
        $tempRow['tax'] = $row['tax_amount'] . '(' . $row['tax_percentage'] . '%)';
        $tempRow['qty'] = $row['quantity'];
        $tempRow['deliver_by'] = !empty($res_dboy[0]['name']) ? $res_dboy[0]['name'] : 'Not Assigned';
        $tempRow['seller_name'] = !empty($res_seller[0]['name']) ? $res_seller[0]['name'] : '';
        $tempRow['payment_method'] = $row['payment_method'];
        $tempRow['seller_id'] = !empty($row['seller_id']) ? $row['seller_id'] : '';
        $tempRow['address'] = $row['address'];
        $tempRow['delivery_time'] = $row['delivery_time'];
        $tempRow['status'] = $status;
        $tempRow['active_status'] = $active_status;
        $tempRow['wallet_balance'] = $row['wallet_balance'];
        $tempRow['date_added'] = date('d-m-Y', strtotime($row['date_added']));
        $tempRow['operate'] = '<a href="order-detail.php?id=' . $row['order_id'] . '"><i class="fa fa-eye"></i> View</a>
        <br><a href="delete-order.php?id=' . $row['id'] . '"><i class="fa fa-trash"></i> Delete</a>';

        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'CATEGORY' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'category') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " Where `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `subtitle` like '%" . $search . "%' OR `image` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(`id`) as total FROM `category` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `category` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {

        $operate = '<a href="view-subcategory.php?id=' . $row['id'] . '"><i class="fa fa-folder-open-o"></i>View Subcategories</a>';
        $operate .= ' <a href="edit-category.php?id=' . $row['id'] . '"><i class="fa fa-edit"></i>Edit</a>';
        $operate .= ' <a class="btn-xs btn-danger" href="delete-category.php?id=' . $row['id'] . '"><i class="fa fa-trash-o"></i>Delete</a>';

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['subtitle'] = $row['subtitle'];
        $tempRow['image'] = "<a data-lightbox='category' href='" . $row['image'] . "' data-caption='" . $row['name'] . "'><img src='" . $row['image'] . "' title='" . $row['name'] . "' height='50' /></a>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'SUBCATEGORY' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'subcategory') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where s.`id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `subtitle` like '%" . $search . "%' OR `image` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(*) as total FROM `subcategory` s" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT s.*,(SELECT name FROM category c WHERE c.id=s.category_id) as category_name FROM `subcategory` s" . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {

        $operate = '<a href="view-subcategory-product.php?id=' . $row['id'] . '"><i class="fa fa-folder-open-o"></i>View Products</a>';
        $operate .= ' <a href="edit-subcategory.php?id=' . $row['id'] . '"><i class="fa fa-edit"></i>Edit</a>';
        $operate .= ' <a class="btn-xs btn-danger" href="delete-subcategory.php?id=' . $row['id'] . '"><i class="fa fa-trash-o"></i>Delete</a>';
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['category_name'] = $row['category_name'];
        $tempRow['subtitle'] = $row['subtitle'];
        $tempRow['image'] = "<a data-lightbox='category' href='" . $row['image'] . "' data-caption='" . $row['name'] . "'><img src='" . $row['image'] . "' title='" . $row['name'] . "' height='50' /></a>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'PRODUCTS' table goes here

if (isset($_GET['table']) && $_GET['table'] == 'products') {
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'ASC';
    $where = '';

    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        if ($_GET['sort'] == 'id') {
            $sort = "id";
        } else {
            $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
        }
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) and $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " where (p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR pv.`measurement` like '%" . $search . "%' OR u.`short_code` like '%" . $search . "%' )";
    }

    if (isset($_GET['category_id']) && $_GET['category_id'] != '') {
        $category_id = $db->escapeString($fn->xss_clean($_GET['category_id']));
        if (isset($_GET['search']) and $_GET['search'] != '')
            $where .= ' and p.`category_id`=' . $category_id;
        else
            $where = ' where p.`category_id`=' . $category_id;
    }
    if (isset($_GET['subcategory_id']) && $_GET['subcategory_id'] != '') {
        $subcategory_id = $db->escapeString($fn->xss_clean($_GET['subcategory_id']));
        if (isset($_GET['search']) and $_GET['search'] != '')
            $where .= ' and p.`subcategory_id`=' . $subcategory_id;
        else
            $where = ' where p.`subcategory_id`=' . $subcategory_id;
    }
    if (isset($_GET['seller_id']) && $_GET['seller_id'] != '') {
        $seller_id = $db->escapeString($fn->xss_clean($_GET['seller_id']));

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $where .= ' and p.`seller_id`=' . $seller_id;
        } else if (isset($_GET['category_id']) and $_GET['category_id'] != '') {
            $where .= ' and p.`seller_id`=' . $seller_id;
        } else {
            $where = ' where p.`seller_id`=' . $seller_id;
        }
    }
    if (isset($_GET['is_approved']) && $_GET['is_approved'] != '') {
        $is_approved = $db->escapeString($fn->xss_clean($_GET['is_approved']));
        $where .= empty($where) ? " WHERE p.is_approved = $is_approved " : " AND p.is_approved = $is_approved ";
    }
    if (isset($_GET['shipping_method']) && $_GET['shipping_method'] != '') {
        $shipping_method = $db->escapeString($fn->xss_clean($_GET['shipping_method']));
        $where .= empty($where) ? " WHERE p.standard_shipping = $shipping_method " : " AND p.standard_shipping = $shipping_method ";
    }
    if (isset($_GET['sold_out']) && $_GET['sold_out'] == 1) {
        $where .= empty($where) ? " WHERE pv.stock <=0 AND pv.serve_for = 'Sold Out'" : " AND stock <=0 AND serve_for = 'Sold Out'";
    }
    if (isset($_GET['low_stock']) && $_GET['low_stock'] == 1) {
        $where .= empty($where) ? " WHERE pv.stock < $low_stock_limit AND pv.serve_for = 'Available'" : " AND stock < $low_stock_limit AND serve_for = 'Available'";
    }


    $join = "LEFT JOIN `product_variant` pv ON pv.product_id = p.id
            LEFT JOIN `unit` u ON u.id = pv.measurement_unit_id LEFT JOIN `seller` s ON s.id = p.seller_id";

    $sql = "SELECT COUNT(p.id) as `total` FROM `products` p $join " . $where . "";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];
    $sql = "SELECT p.id AS id,p.*, p.name,p.seller_id,p.status,p.tax_id, p.image,s.name as seller_name, p.indicator, p.manufacturer, p.made_in, p.return_status, p.cancelable_status, p.till_status,p.description, pv.id as product_variant_id, pv.price, pv.discounted_price, pv.measurement, pv.serve_for, pv.stock,pv.stock_unit_id, u.short_code 
            FROM `products` p
            $join 
            $where ORDER BY $sort $order LIMIT $offset, $limit";
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    $currency = $fn->get_settings('currency', false);

    foreach ($res as $row) {
        if ($row['indicator'] == 0) {
            $indicator = "<span class='label label-info'>None</span>";
        }
        if ($row['indicator'] == 1) {
            $indicator = "<span class='label label-success'>Veg</span>";
        }
        if ($row['indicator'] == 2) {
            $indicator = "<span class='label label-danger'>Non-Veg</span>";
        }
        if ($row['till_status'] == 'received') {
            $till_status = '<label class="label label-primary">Received</label>';
        }
        if ($row['till_status'] == 'processed') {
            $till_status = '<label class="label label-info">Processed</label>';
        }
        if ($row['till_status'] == 'shipped') {
            $till_status = '<label class="label label-warning">Shipped</label>';
        }
        if ($row['till_status'] == 'delivered') {
            $till_status = '<label class="label label-success">Delivered</label>';
        }

        if (!empty($row['stock_unit_id'])) {
            $sql = "select short_code as stock_unit from unit where id = " . $row['stock_unit_id'];
            $db->sql($sql);
            $stock_unit = $db->getResult();
            $tempRow['stock'] = $row['stock'] . ' ' . $stock_unit[0]['stock_unit'];
        }

        $operate = '<a href="view-product-variants.php?id=' . $row['id'] . '" title="View"><i class="fa fa-folder-open"></i></a>';
        $operate .= ' <a href="edit-product.php?id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit"></i></a>';
        $operate .= ' <a class="btn btn-xs btn-danger" href="delete-product.php?id=' . $row['product_variant_id'] . '" title="Delete"><i class="fa fa-trash-o"></i></a>&nbsp;';
        if ($row['status'] == 1) {
            $operate .= "<a class='btn btn-xs btn-warning set-product-deactive' data-id='" . $row['id'] . "' title='Hide'>  <i class='fa fa-eye'></i> </a>";
        } elseif ($row['status'] == 0) {
            $operate .= "<a class='btn btn-xs btn-success set-product-active' data-id='" . $row['id'] . "' title='Show'>  <i class='fa fa-eye-slash'></i> </a>";
        }

        $tempRow['id'] = $row['product_variant_id'];
        $tempRow['product_id'] = $row['id'];
        $tempRow['tax_id'] = $row['tax_id'];
        $tempRow['seller_id'] = (!empty($row['seller_id'])) ? $row['seller_id'] : "";
        $tempRow['seller_name'] = (!empty($row['seller_name'])) ? $row['seller_name'] : "";
        $tempRow['name'] = $row['name'];
        $tempRow['return_days'] = $row['return_days'];
        $tempRow['type'] = $row['type'];
        $tempRow['pincodes'] = $row['pincodes'];
        $tempRow['measurement'] = $row['measurement'] . " " . $row['short_code'];
        $tempRow['price'] = $currency . " " . $row['price'];
        $tempRow['indicator'] = $indicator;
        $tempRow['manufacturer'] = $row['manufacturer'];
        $tempRow['made_in'] = $row['made_in'];

        $tempRow['is_approved'] = ($row['is_approved'] == 1)
            ? "<label class='label label-success'>Approved</label>"
            : (($row['is_approved'] == 2)
                ? "<label class='label label-danger'>Not-Approved</label>"
                : "<label class='label label-warning'>Not-Processed</label>");
        $tempRow['description'] = $row['description'];
        $tempRow['return_status'] = $row['return_status'] == 1 ? "<span class='label label-success'>Allowed</span>" : "<span class='label label-danger'>Not Allowed</span>";
        $tempRow['cancelable_status'] = $row['cancelable_status'] == 1 ? "<span class='label label-success'>Allowed</span>" : "<span class='label label-danger'>Not Allowed</span>";
        $tempRow['till_status'] = $row['cancelable_status'] == 1 ? $till_status : "<label class='label label-info'>Not Applicable</label>";
        $tempRow['discounted_price'] = $currency . " " . $row['discounted_price'];
        $tempRow['serve_for'] = $row['serve_for'] == 'Sold Out' ? "<span class='label label-danger'>Sold Out</label>" : "<span class='label label-success'>Available</label>";
        $tempRow['image'] = "<a data-lightbox='product' href='" . $row['image'] . "' data-caption='" . $row['name'] . "'><img src='" . $row['image'] . "' title='" . $row['name'] . "' height='50' /></a>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'USERS' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'users') {

    $offset = 0;
    $limit = 10;
    $sort = 'u.id';
    $order = 'DESC';
    $where = $group_by = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['filter_user']) && $_GET['filter_user'] != '') {
        $filter_user = $db->escapeString($fn->xss_clean($_GET['filter_user']));
        $where .= ' where ua.pincode_id=' . $filter_user;
    }
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        if (isset($_GET['filter_user']) && $_GET['filter_user'] != '') {
            $where .= " and u.`id` like '%" . $search . "%' OR u.`name` like '%" . $search . "%' OR u.`email` like '%" . $search . "%' OR u.`mobile` like '%" . $search . "%' ";
        } else {
            $where .= " Where u.`id` like '%" . $search . "%' OR u.`name` like '%" . $search . "%' OR u.`email` like '%" . $search . "%' OR u.`mobile` like '%" . $search . "%'";
        }
    }
    // }else{
    $group_by = ' GROUP by u.id';
    // }
    if (isset($_GET['filter_order_status']) && $_GET['filter_order_status'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_GET['filter_order']));
        if (isset($_GET['search']) and $_GET['search'] != '')
            $where .= ' and active_status=' . $filter_order;
        else
            $where = ' where active_status=' . $filter_order;
    }


    $sql = "SELECT COUNT(DISTINCT(u.id)) as total FROM `users` u LEFT JOIN user_addresses ua on u.id=ua.user_id LEFT JOIN pincodes p on p.id=ua.pincode_id LEFT JOIN area a on a.id=ua.area_id LEFT JOIN cities c on c.id=a.city_id  " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT u.*,a.name as area_name,p.pincode as pincode,c.name as city,ua.pincode_id FROM `users` u LEFT JOIN user_addresses ua on u.id=ua.user_id LEFT JOIN pincodes p on p.id=ua.pincode_id LEFT JOIN area a on a.id=ua.area_id LEFT JOIN cities c on c.id=a.city_id " . $where . " $group_by ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    //  echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = $rows = $tempRow = array();
    $bulkData['total'] = $total;
    $operate = '';
    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $path = DOMAIN_URL . 'upload/profile/';
        if (!empty($row['profile'])) {
            $tempRow['profile'] = "<a data-lightbox='product' href='" . $path . $row['profile'] . "' data-caption='" . $row['name'] . "'><img src='" . $path . $row['profile'] . "' title='" . $row['name'] . "' height='50' /></a>";
        } else {
            $tempRow['profile'] = "<a data-lightbox='product' href='" . $path . "default_user_profile.png' data-caption='" . $row['name'] . "'><img src='" . $path . "default_user_profile.png' title='" . $row['name'] . "' height='50' /></a>";
        }
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $tempRow['email'] = str_repeat("*", strlen($row['email']) - 13) . substr($row['email'], -13);
            $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
            $tempRow['email'] = $row['email'];
        }
        $operate = '<a class="btn btn-xs btn-info view-address" data-id="' . $row['id'] . '" data-toggle="modal" data-target="#ViewAddressModel" title="View Address"><i class="fa fa-credit-card"></i></a>&nbsp;';
        if ($row['status'] == 0) {
            $operate .= " <a class='btn btn-lg btn-default change-status' data-id='" . $row['id'] . "' data-status='1' title='Active'><i class='fa fa-toggle-off'></i></a>";
        } else {
            $operate .= " <a class='btn btn-lg btn-default change-status' data-id='" . $row['id'] . "' data-status='0' title='De-Active'><i class='fa fa-toggle-on'></i></a>";
        }
        $tempRow['balance'] = $row['balance'];
        $tempRow['referral_code'] = $row['referral_code'];
        $tempRow['friends_code'] = !empty($row['friends_code']) ? $row['friends_code'] : '-';
        $tempRow['city'] = $row['city'];
        $tempRow['pincode_id'] = $row['pincode_id'];
        $tempRow['pincode'] = $row['pincode'];
        $tempRow['area'] = $row['area_name'];
        $tempRow['status'] = $row['status'] == 1 ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>De-Active</label>";
        $tempRow['created_at'] = $row['created_at'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $rows = mb_convert_encoding($rows, "UTF-8", "UTF-8");
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'area' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'area') {
    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString($fn->xss_clean($_GET['offset'])) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString($fn->xss_clean($_GET['limit'])) : 10;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString($fn->xss_clean($_GET['sort'])) : 'id';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString($fn->xss_clean($_GET['order'])) : 'DESC';

    if (isset($_GET['filter_area']) && !empty($_GET['filter_area'])) {
        $filter_area = $db->escapeString($fn->xss_clean($_GET['filter_area']));
        $where .= ' and  a.pincode_id=' . $filter_area;
    }
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        if (isset($_GET['filter_area']) && !empty($_GET['filter_area'])) {
            $where .= " and a.`id` like '%" . $search . "%' OR a.`name` like '%" . $search . "%' OR `pincode_id` like '%" . $search . "%' OR p.`pincode` like '%" . $search . "%' OR c.`name` like '%" . $search . "%'";
        } else {
            $where .= " and a.`id` like '%" . $search . "%' OR a.`name` like '%" . $search . "%' OR `pincode_id` like '%" . $search . "%' OR p.`pincode` like '%" . $search . "%' OR c.`name` like '%" . $search . "%'";
        }
    }

    $sql = "SELECT COUNT(a.id) as total FROM `area` a join pincodes p ON a.pincode_id=p.id join cities c on c.id=a.city_id where c.status= 1 and p.status=1 " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT a.*,p.pincode,c.name as city_name FROM `area` a join pincodes p ON a.pincode_id=p.id join cities c on c.id=a.city_id where c.status= 1 and p.status=1 $where ORDER BY $sort $order LIMIT $offset,$limit";
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = ' <a href="edit-area.php?id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit"></i>Edit</a>&nbsp;';
        $operate .= ' <a class="btn btn-xs btn-danger" href="delete-area.php?id=' . $row['id'] . '" title="Delete"><i class="fa fa-trash-o"></i> Delete</a>';

        $tempRow['id'] = $row['id'];
        $tempRow['city_id'] = $row['city_id'];
        $tempRow['pincode_id'] = $row['pincode_id'];
        $tempRow['name'] = $row['name'];
        $tempRow['delivery_charges'] = $row['delivery_charges'];
        $tempRow['minimum_free_delivery_order_amount'] = $row['minimum_free_delivery_order_amount'];
        $tempRow['city_name'] = $row['city_name'];
        $tempRow['pincode'] = $row['pincode'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'notification' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'notifications') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `title` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR `image` like '%" . $search . "%' OR `date_sent` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(*) as total FROM `notifications` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `notifications` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {


        $operate = " <a class='btn btn-xs btn-danger delete-notification' data-id='" . $row['id'] . "' data-image='" . $row['image'] . "' title='Delete'><i class='fa fa-trash-o'></i>Delete</a>";

        $tempRow['id'] = $row['id'];
        $tempRow['title'] = $row['title'];
        $tempRow['message'] = $row['message'];
        $tempRow['type'] = $row['type'];
        $tempRow['type_id'] = $row['type_id'];
        $tempRow['image'] = (!empty($row['image'])) ? "<a data-lightbox='slider' href='" . $row['image'] . "' data-caption='" . $row['title'] . "'><img src='" . $row['image'] . "' title='" . $row['title'] . "' width='50' /></a>" : "No Image";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

if (isset($_GET['table']) && $_GET['table'] == 'slider') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `image` like '%" . $search . "%' OR `date_added` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(*) as total FROM `slider` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `slider` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = " <a class='btn btn-xs btn-danger delete-slider' data-id='" . $row['id'] . "' data-image='" . $row['image'] . "' title='Delete'><i class='fa fa-trash-o'></i>Delete</a>";


        $tempRow['id'] = $row['id'];
        $tempRow['type'] = $row['type'];
        $tempRow['type_id'] = $row['type_id'];
        $tempRow['slider_url'] = $row['slider_url'];
        $tempRow['image'] = (!empty($row['image'])) ? "<a data-lightbox='slider' href='" . $row['image'] . "'><img src='" . $row['image'] . "' width='40'/></a>" : "No Image";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}
if (isset($_GET['table']) && $_GET['table'] == 'offers') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `date_added` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(id) as total FROM `offers` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `offers` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    foreach ($res as $row) {
        $operate = " <a class='btn btn-xs btn-danger delete-offer' data-id='" . $row['id'] . "' data-image='" . $row['image'] . "' title='Delete'><i class='fa fa-trash-o'></i>Delete</a>";

        $tempRow['id'] = $row['id'];
        $tempRow['position'] = $row['position'];
        $tempRow['section_position'] = $row['section_position'];
        $tempRow['image'] = (!empty($row['image'])) ? "<a data-lightbox='offer' href='" . $row['image'] . "'><img src='" . $row['image'] . "' width='40'/></a>" : "No Image";
        $tempRow['date_created'] = date('d-m-Y h:i:sa', strtotime($row['date_added']));
        $tempRow['operate'] = $operate;

        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

function formatBytes($size, $precision = 2)
{
    $base = log($size, 1024);
    $suffixes = array('', 'KB', 'MB', 'GB', 'TB');

    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
if (isset($_GET['table']) && $_GET['table'] == 'media') {

    $offset = (isset($_GET['offset']) && !empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $db->escapeString($fn->xss_clean($_GET['offset'])) : 0;
    $limit = (isset($_GET['limit']) && !empty($_GET['limit']) && is_numeric($_GET['limit'])) ? $db->escapeString($fn->xss_clean($_GET['limit'])) : 10;
    $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $db->escapeString($fn->xss_clean($_GET['sort'])) : 'id';
    $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $db->escapeString($fn->xss_clean($_GET['order'])) : 'DESC';
    if (isset($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " m.`id` like '%" . $search . "%' OR m.`extension` like '%" . $search . "%' OR m.`type` like '%" . $search . "%' OR m.`name` like '%" . $search . "%' OR s.`name` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(m.id) as total FROM `media` m LEFT JOIN seller s On m.seller_id=s.id WHERE " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT m.*,s.name as seller_name FROM `media` m LEFT JOIN seller s ON m.seller_id=s.id WHERE $where ORDER BY $sort $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    foreach ($res as $row) {
        $operate = " <a class='btn btn-xs btn-primary copy_to_clipboard' title='Copy'><i class='fa fa-copy'></i>Copy</a> ";
        $operate .= " <a class='btn btn-xs btn-danger delete_media' data-id='" . $row['id'] . "' data-image='" . $row['sub_directory'] . '/' . $row['name'] . "'title='Delete'><i class='fa fa-trash-o'></i>Delete</a>";

        $tempRow['id'] = $row['id'];
        $tempRow['image'] = "<img src='" . DOMAIN_URL . $row['sub_directory'] . '/' . $row['name'] . "' width='60' height: 60px; />";
        $full_path = $row['sub_directory']  . $row['name'];
        $tempRow['image'] .= "<span class='copy-path hide'>$full_path</span>";
        $tempRow['name'] = $row['name'];
        $tempRow['extension'] = $row['extension'];
        $tempRow['type'] = $row['type'];
        $tempRow['sub_directory'] = $row['sub_directory'];
        $tempRow['size'] = ($row['size'] > 1) ? formatBytes($row['size']) : $row['size'];
        $tempRow['seller_name'] = !empty($row['seller_name']) ? $row['seller_name'] : "-";

        $tempRow['date_created'] = date('d-m-Y h:i:sa', strtotime($row['date_created']));
        $tempRow['operate'] = $operate;

        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

if (isset($_GET['table']) && $_GET['table'] == 'sections') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `title` like '%" . $search . "%' OR `date_added` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(*) as total FROM `sections` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `sections` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-primary edit-section' data-id='" . $row['id'] . "' title='Edit'><i class='fa fa-pencil-square-o'></i></a>";

        $operate .= " <a class='btn btn-xs btn-danger delete-section' data-id='" . $row['id'] . "' title='Delete'><i class='fa fa-trash-o'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['title'] = $row['title'];
        $tempRow['short_description'] = $row['short_description'];
        $tempRow['style'] = $row['style'];
        $tempRow['product_type'] = $row['product_type'];
        $tempRow['product_ids'] = $row['product_ids'];
        $tempRow['category_ids'] = $row['category_ids'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}


if (isset($_GET['table']) && $_GET['table'] == 'seller_request') {
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $status = $db->escapeString($fn->xss_clean($_GET['status']));
    $where = ' where status=' . $status;
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= "  and (`id` like '%" . $search . "%' OR `name` like '%" . $search . "%')";
    }

    $sql = "SELECT COUNT(*) as total FROM `seller` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `seller` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = ' <a href="edit-request.php?id=' . $row['id'] . '"><i class="fa fa-edit"></i>Edit</a>';
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['mobile'] = $row['mobile'];
        $tempRow['email'] = $row['email'];
        $tempRow['company'] = $row['company_name'];
        $tempRow['address'] = $row['company_address'];
        $tempRow['gst_no'] = $row['gst_no'];
        $tempRow['pan_no'] = $row['pan_no'];
        if ($row['status'] == 0) {
            $tempRow['status'] = "<span class='label label-warning'>Pending</span>";
        } elseif ($row['status'] == 1) {
            $tempRow['status'] =  "<span class='label label-success'>Accepted</span>";
        } else {
            $tempRow['status'] =  "<span class='label label-danger'>Denied</span>";
        }
        $tempRow['date_created'] = $row['date_created'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}
// data of 'Delivery Boy' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'delivery-boys') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `address` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(*) as total FROM `delivery_boys` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `delivery_boys` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;

    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    $path = 'upload/delivery-boy/';
    foreach ($res as $row) {

        $operate = "<a class='btn btn-xs btn-primary edit-delivery-boy' data-id='" . $row['id'] . "' data-driving_license='" . $row['driving_license'] . "' data-national_identity_card='" . $row['national_identity_card'] . "' data-toggle='modal' data-target='#editDeliveryBoyModal' title='Edit'><i class='fa fa-pencil-square-o'></i></a>";

        $operate .= " <a class='btn btn-xs btn-danger delete-delivery-boy' data-id='" . $row['id'] . "' data-driving_license='" . $row['driving_license'] . "' data-national_identity_card='" . $row['national_identity_card'] . "' title='Delete'><i class='fa fa-trash-o'></i></a>";

        // $operate .= " <a class='btn btn-xs btn-primary transfer-fund' data-id='" . $row['id'] . "' data-name='" . $row['name'] . "' data-mobile='" . $row['mobile'] . "' data-address='" . $row['address'] . "' data-balance='" . $row['balance'] . "' data-toggle='modal' data-target='#fundTransferModal' title='Fund Transfer'><i class='fa fa-chevron-circle-right'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
        }
        $tempRow['address'] = $row['address'];
        $tempRow['bonus'] = $row['bonus'];
        $tempRow['balance'] = number_format($row['balance'], 2);
        if (!empty($row['driving_license'])) {
            $tempRow['driving_license'] = "<a data-lightbox='product' href='" . DOMAIN_URL . $path . $row['driving_license'] . "'><img src='" . DOMAIN_URL . $path . $row['driving_license'] . "' height='50' /></a>";
            $tempRow['national_identity_card'] = "<a data-lightbox='product' href='" . $path . $row['national_identity_card'] . "'><img src='" . $path . $row['national_identity_card'] . "' height='50' /></a>";
        } else {
            $tempRow['national_identity_card'] = "<p>No National Identity Card</p>";
            $tempRow['driving_license'] = "<p>No Driving License</p>";
        }
        $tempRow['dob'] = $row['dob'];
        $tempRow['bank_account_number'] = $row['bank_account_number'];
        $tempRow['bank_name'] = $row['bank_name'];
        $tempRow['account_name'] = $row['account_name'];
        $tempRow['other_payment_information'] = (!empty($row['other_payment_information'])) ? $row['other_payment_information'] : "";
        $tempRow['pincode_id'] = (!empty($row['pincode_id'])) ? $row['pincode_id'] : "";
        $tempRow['ifsc_code'] = $row['ifsc_code'];
        $tempRow['cash_received'] = $row['cash_received'];
        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-danger'>Deactive</label>";
        else
            $tempRow['status'] = "<label class='label label-success'>Active</label>";
        if ($row['is_available'] == 0)
            $tempRow['is_available'] = "<label class='label label-danger'>No</label>";
        else
            $tempRow['is_available'] = "<label class='label label-success'>Yes</label>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'SOCIAL MEDIA' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'social_media') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'ASC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " Where `id` like '%" . $search . "%' OR `icon` like '%" . $search . "%' OR `link` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(`id`) as total FROM `social_media` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `social_media` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {


        $operate = "<a class='btn btn-xs btn-primary edit-social-media' data-id='" . $row['id'] . "'  data-toggle='modal' data-target='#editSocialMediaModal' title='Edit'><i class='fa fa-pencil-square-o'></i></a>";

        $operate .= " <a class='btn btn-xs btn-danger delete-social-media' data-id='" . $row['id'] . "'   title='Delete'><i class='fa fa-trash-o'></i></a>";

        $tempRow['id'] = $row['id'];
        //$tempRow['name'] = $row['name'];

        $tempRow['id'] = $row['id'];
        $icon = "<i class='fa " . $row['icon'] . "'></i>";
        $tempRow['social_icon'] = $icon;
        $tempRow['icon'] = $row['icon'];
        $tempRow['link'] = $row['link'];

        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}
// data of 'Payment Request' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'payment-requests') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where p.`id` like '%" . $search . "%' OR `user_id` like '%" . $search . "%' OR `payment_type` like '%" . $search . "%' OR `amount_requested` like '%" . $search . "%' OR `remarks` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `email` like '%" . $search . "%' OR `date_created` like '%" . $search . "%' OR `payment_address` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(*) as total FROM `payment_requests` p JOIN users u ON p.user_id=u.id" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT p.*,u.name,u.email FROM payment_requests p JOIN users u ON u.id=p.user_id" . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;

    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-primary edit-payment-request' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editPaymentRequestModal' title='Edit'><i class='fa fa-pencil-square-o'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['payment_type'] = $row['payment_type'];
        if ($row['payment_type'] == 'bank') {
            $payment_address = json_decode($row['payment_address'], true);
            $tempRow['payment_address'] = '<b>A/C Holder</b><br>' . $payment_address[0][1] . '<br>' . '<b>A/C Number</b><br>' . $payment_address[1][1] . '<br>' . '<b>IFSC Code</b><br>' . $payment_address[2][1] . '<br>' . '<b>Bank Name</b><br>' . $payment_address[3][1];
        } else {
            $tempRow['payment_address'] = $row['payment_address'];
        }
        $tempRow['amount_requested'] = $row['amount_requested'];
        $tempRow['remarks'] = $row['remarks'];
        $tempRow['name'] = $row['name'];
        $tempRow['email'] = $row['email'];
        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-warning'>Pending</label>";
        if ($row['status'] == 1)
            $tempRow['status'] = "<label class='label label-primary'>Success</label>";
        if ($row['status'] == 2)
            $tempRow['status'] = "<label class='label label-danger'>Cancelled</label>";
        $tempRow['operate'] = $operate;
        $tempRow['date_created'] = $row['date_created'];
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'Fund Transfer' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'fund-transfers') {

    $offset = 0;
    $limit = 10;
    $sort = 'f.id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where f.`id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `address` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR f.`date_created` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(f.`id`) as total FROM `fund_transfers` f LEFT JOIN `delivery_boys` d ON f.delivery_boy_id=d.id" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT f.*,d.name,d.mobile,d.address FROM `fund_transfers` f LEFT JOIN `delivery_boys` d ON f.delivery_boy_id=d.id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;

    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
        }
        $tempRow['address'] = $row['address'];
        $tempRow['delivery_boy_id'] = $row['delivery_boy_id'];
        $tempRow['opening_balance'] = number_format($row['opening_balance'], 2);
        $tempRow['closing_balance'] = number_format($row['closing_balance'], 2);
        $tempRow['amount'] = $row['amount'];
        $tempRow['type'] = $row['type'] == 'credit' ? '<span class="label label-success">Credit</span>' : '<span class="label label-danger">Debit</span>';
        $tempRow['status'] = $row['status'] == 'SUCCESS' ? '<span class="label label-success">Success</span>' : '<span class="label label-danger">Failed</span>';
        $tempRow['message'] = $row['message'];
        $tempRow['date_created'] = $row['date_created'];


        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'Fund Transfer' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'unit') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `short_code` like '%" . $search . "%' OR `conversion` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(`id`) as total FROM `unit` $where";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `unit` $where ORDER BY $sort $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = ' <a href="edit-unit.php?id=' . $row['id'] . '"><i class="fa fa-edit"></i>Edit</a>';
        $tempRow['operate'] = $operate;
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['short_code'] = $row['short_code'];
        $tempRow['parent_id'] = $row['parent_id'];
        $tempRow['conversion'] = $row['conversion'];
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'Promo Codes' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'promo-codes') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `promo_code` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR `start_date` like '%" . $search . "%' OR `end_date` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(id) as total FROM `promo_codes`" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `promo_codes`" . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {

        $operate = "<a class='btn btn-xs btn-primary edit-promo-code' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editPromoCodeModal' title='Edit'><i class='fa fa-pencil-square-o'></i></a>";
        $operate .= " <a class='btn btn-xs btn-danger delete-promo-code' data-id='" . $row['id'] . "' title='Delete'><i class='fa fa-trash-o'></i></a>";


        $tempRow['id'] = $row['id'];
        $tempRow['promo_code'] = $row['promo_code'];
        $tempRow['message'] = $row['message'];
        $tempRow['start_date'] = $row['start_date'];
        $tempRow['end_date'] = $row['end_date'];
        $tempRow['no_of_users'] = $row['no_of_users'];
        $tempRow['minimum_order_amount'] = $row['minimum_order_amount'];
        $tempRow['discount'] = $row['discount'];
        $tempRow['discount_type'] = $row['discount_type'];
        $tempRow['max_discount_amount'] = $row['max_discount_amount'];
        $tempRow['repeat_usage'] = $row['repeat_usage'] == 1 ? 'Allowed' : 'Not Allowed';
        $tempRow['no_of_repeat_usage'] = $row['no_of_repeat_usage'];
        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-danger'>Deactive</label>";
        else
            $tempRow['status'] = "<label class='label label-success'>Active</label>";
        $tempRow['date_created'] = date('d-m-Y h:i:sa', strtotime($row['date_created']));
        $tempRow['operate'] = $operate;

        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}
if (isset($_GET['table']) && $_GET['table'] == 'time-slots') {

    $offset = 0;
    $limit = 10;
    $sort = 'last_order_time';
    $order = 'ASC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `title` like '%" . $search . "%' OR `from_time` like '%" . $search . "%' OR `to_time` like '%" . $search . "%' OR `last_order_time` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(*) as total FROM `time_slots` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `time_slots` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {

        $operate = "<a class='btn btn-xs btn-primary edit-time-slot' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editTimeSlotModal' title='Edit'><i class='fa fa-pencil-square-o'></i></a>";
        $operate .= " <a class='btn btn-xs btn-danger delete-time-slot' data-id='" . $row['id'] . "' title='Delete'><i class='fa fa-trash-o'></i></a>";
        $tempRow['id'] = $row['id'];
        $tempRow['title'] = $row['title'];
        $tempRow['from_time'] = $row['from_time'];
        $tempRow['to_time'] = $row['to_time'];
        $tempRow['last_order_time'] = $row['last_order_time'];
        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-danger'>Deactive</label>";
        else
            $tempRow['status'] = "<label class='label label-success'>Active</label>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}
// data of 'Return Request' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'return-requests') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where r.`id` like '%" . $search . "%' OR r.`user_id` like '%" . $search . "%' OR r.`order_id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR u.`name` like '%" . $search . "%' OR r.`status` like '%" . $search . "%' OR r.`date_created` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(r.id) as total FROM return_requests r LEFT JOIN users u ON u.id=r.user_id LEFT JOIN order_items oi ON oi.id=r.order_item_id LEFT JOIN products p ON p.id = r.product_id LEFT JOIN product_variant pv ON pv.id=r.product_variant_id" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT r.*,u.name,oi.product_variant_id,oi.quantity,oi.price,oi.discounted_price,oi.product_name,oi.variant_name FROM return_requests r LEFT JOIN users u ON u.id=r.user_id LEFT JOIN order_items oi ON oi.id=r.order_item_id" . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-primary edit-return-request' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editReturnRequestModal' title='Edit'><i class='fa fa-pencil-square-o'></i></a>";
        $operate .= " <a class='btn btn-xs btn-danger delete-return-request' data-id='" . $row['id'] . "' title='Delete'><i class='fa fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['order_id'] = $row['order_id'];
        $tempRow['order_item_id'] = $row['order_item_id'];
        // $tempRow['product_id'] = $row['product_id'];
        $tempRow['price'] = $row['price'];
        $tempRow['discounted_price'] = $row['discounted_price'];
        $tempRow['remarks'] = $row['remarks'];
        $tempRow['name'] = $row['name'];
        $tempRow['product_name'] = $row['product_name'] . "(" . $row['variant_name'] . ")";
        $tempRow['product_variant_id'] = $row['product_variant_id'];
        $tempRow['quantity'] = $row['quantity'];
        $tempRow['total'] = $row['discounted_price'] == 0 ? $row['price'] * $row['quantity'] : $row['discounted_price'] * $row['quantity'];
        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-warning'>Pending</label>";
        if ($row['status'] == 1)
            $tempRow['status'] = "<label class='label label-primary'>Approved</label>";
        if ($row['status'] == 2)
            $tempRow['status'] = "<label class='label label-danger'>Cancelled</label>";
        $tempRow['operate'] = $operate;
        $tempRow['date_created'] = $row['date_created'];
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}
// data of 'Promo Codes' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'system-users') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'ASC';
    $where = '';
    $condition = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `username` like '%" . $search . "%' OR `email` like '%" . $search . "%' OR `role` like '%" . $search . "%' OR `date_created` like '%" . $search . "%'";
    }
    if ($_SESSION['role'] != 'super admin') {
        if (empty($where)) {
            $condition .= ' where created_by=' . $_SESSION['id'];
        } else {
            $condition .= ' and created_by=' . $_SESSION['id'];
        }
    }

    $sql = "SELECT COUNT(id) as total FROM `admin`" . $where . "" . $condition;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `admin`" . $where . "" . $condition . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        if ($row['created_by'] != 0) {
            $sql = "SELECT username FROM admin WHERE id=" . $row['created_by'];
            $db->sql($sql);
            $created_by = $db->getResult();
        }

        if ($row['role'] != 'super admin') {
            $operate = "<a class='btn btn-xs btn-primary edit-system-user' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editSystemUserModal' title='Edit'><i class='fa fa-pencil-square-o'></i></a>";
            $operate .= " <a class='btn btn-xs btn-danger delete-system-user' data-id='" . $row['id'] . "' title='Delete'><i class='fa fa-trash-o'></i></a>";
        } else {
            $operate = '';
        }
        if ($row['role'] == 'super admin') {
            $role = '<span class="label label-success">Super Admin</span>';
        }
        if ($row['role'] == 'admin') {
            $role = '<span class="label label-primary">Admin</span>';
        }
        if ($row['role'] == 'editor') {
            $role = '<span class="label label-warning">Editor</span>';
        }
        $tempRow['id'] = $row['id'];
        $tempRow['username'] = $row['username'];
        $tempRow['email'] = $row['email'];
        $tempRow['permissions'] = $row['permissions'];
        $tempRow['role'] = $role;
        $tempRow['created_by_id'] = $row['created_by'] != 0 ? $row['created_by'] : '-';
        $tempRow['created_by'] = $row['created_by'] != 0 ? $created_by[0]['username'] : '-';
        $tempRow['date_created'] = date('d-m-Y h:i:sa', strtotime($row['date_created']));
        $tempRow['operate'] = $operate;

        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'Wallet Transactions' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'wallet-transactions') {

    $offset = 0;
    $limit = 10;
    $sort = 'w.id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where w.`id` like '%" . $search . "%' OR `user_id` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `date_created` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(*) as total FROM `wallet_transactions` w JOIN `users` u ON u.id=w.user_id " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT w.*,u.name FROM `wallet_transactions` w JOIN `users` u ON u.id=w.user_id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['name'] = $row['name'];
        $tempRow['type'] = $row['type'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['message'] = $row['message'];
        $tempRow['date_created'] = $row['date_created'];
        $tempRow['las_updated'] = $row['last_updated'];
        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-danger'>Deactive</label>";
        else
            $tempRow['status'] = "<label class='label label-success'>Active</label>";
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'Withdrawal Request' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'withdrawal-requests') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['type']) && $_GET['type'] != '') {
        $type = $db->escapeString($fn->xss_clean($_GET['type']));
        $where .= empty($where) ? " WHERE type = '" . $type . "'" : " and type = '" . $type . "'";
    }

    if (isset($_GET['type']) && $_GET['type'] != '' && $_GET['type'] == 'user') {
        $sql = "SELECT COUNT(w.id) as total FROM `withdrawal_requests` w LEFT JOIN users u ON w.type_id=u.id" . $where;
    } elseif (isset($_GET['type']) && $_GET['type'] != '' && $_GET['type'] == 'delivery_boy') {
        $sql = "SELECT COUNT(w.id) as total FROM `withdrawal_requests` w LEFT JOIN delivery_boys d ON w.type_id=d.id" . $where;
    } else {
        $sql = "SELECT COUNT(id) as total FROM `withdrawal_requests`" . $where;
    }
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];
    if (isset($_GET['type']) && $_GET['type'] != '' && $_GET['type'] == 'user') {
        $sql = "SELECT * FROM withdrawal_requests" . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    } elseif (isset($_GET['type']) && $_GET['type'] != '' && $_GET['type'] == 'delivery_boy') {
        $sql = "SELECT * FROM withdrawal_requests" . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    } else {
        $sql = "SELECT * FROM `withdrawal_requests`" . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    }
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {

        if ($row['type'] == 'user') {
            $sql = "select name,balance from users where id=" . $row['type_id'];
        } else  if ($row['type'] == 'seller') {
            $sql = "select name,balance from seller where id=" . $row['type_id'];
        } else {
            $sql = "select name,balance from delivery_boys where id=" . $row['type_id'];
        }
        $db->sql($sql);
        $res1 = $db->getResult();
        $operate = "<a class='btn btn-xs btn-primary edit-withdrawal-request' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editWithdrawalRequestModal' title='Edit'><i class='fa fa-pencil-square-o'></i></a>";
        $operate .= " <a class='btn btn-xs btn-danger delete-withdrawal-request' data-id='" . $row['id'] . "' title='Delete'><i class='fa fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['type'] = $row['type'];
        $tempRow['type_id'] = $row['type_id'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['balance'] = $res1[0]['balance'];
        $tempRow['message'] = empty($row['message']) ? '-' : $row['message'];
        $tempRow['name'] = !empty($res1[0]['name']) ? $res1[0]['name'] : "";

        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-warning'>Pending</label>";
        if ($row['status'] == 1)
            $tempRow['status'] = "<label class='label label-primary'>Approved</label>";
        if ($row['status'] == 2)
            $tempRow['status'] = "<label class='label label-danger'>Cancelled</label>";
        $tempRow['operate'] = $operate;
        $tempRow['date_created'] = $row['date_created'];
        $tempRow['last_updated'] = $row['last_updated'];
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

if (isset($_GET['table']) && $_GET['table'] == 'sales_reports') {
    $offset = 0;
    $limit = 10;
    $sort = 'oi.date_added';
    $order = 'DESC';
    $where = '';
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = $db->escapeString($fn->xss_clean($_GET['start_date']));
        $end_date = $db->escapeString($fn->xss_clean($_GET['end_date']));
        $where .= " and  DATE(oi.date_added)>=DATE('" . $start_date . "') AND DATE(oi.date_added)<=DATE('" . $end_date . "')";
    } else {
        $where .= " and oi.date_added > DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    }

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " AND (o.id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR u.name like '%" . $search . "%' OR address like '%" . $search . "%' OR date_added like '%" . $search . "%' OR `final_total` like '%" . $search . "%')";
    }

    if (isset($_GET['seller_id']) && $_GET['seller_id'] != '') {
        $seller_id = $db->escapeString($fn->xss_clean($_GET['seller_id']));
        $where .= " and oi.seller_id= $seller_id";
    }
    if (isset($_GET['cat_id']) && $_GET['cat_id'] != '') {
        $cat_id = $db->escapeString($fn->xss_clean($_GET['cat_id']));
        $where .= " and p.category_id= $cat_id";
    }

    $sql = "select COUNT(oi.id) as total from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id and oi.active_status='delivered'  $where ";
    // echo $sql;

    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    $sql = "select oi.id,o.total,oi.seller_id,oi.sub_total,o.user_id,o.mobile,p.name as product_name,o.final_total,o.address, u.name as uname, oi.status as order_status,DATE(oi.date_added) as date_added from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id and oi.active_status='delivered'  $where ORDER BY $sort $order LIMIT $offset , $limit";
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    foreach ($res as $row) {

        $tempRow['id'] = $row['id'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['uname'] = $row['uname'];
        $tempRow['product_name'] = $row['product_name'];
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
        }
        $tempRow['address'] = $row['address'];
        $tempRow['final_total'] = $row['sub_total'];
        $tempRow['date_added'] = date('d-m-Y', strtotime($row['date_added']));
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

if (isset($_GET['table']) && $_GET['table'] == 'taxes') {

    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));

    if (isset($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `title` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(`id`) as total FROM `taxes` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `taxes` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = $tempRow = array();

    foreach ($res as $row) {

        $operate = ' <a href="edit-tax.php?id=' . $row['id'] . '"><i class="fa fa-edit"></i>Edit</a>';
        $operate .= ' <a class="btn-xs btn-danger" href="delete-tax.php?id=' . $row['id'] . '"><i class="fa fa-trash-o"></i>Delete</a>';

        $tempRow['id'] = $row['id'];
        $tempRow['title'] = $row['title'];
        $tempRow['percentage'] = $row['percentage'];
        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-danger'>Deactive</label>";
        else
            $tempRow['status'] = "<label class='label label-success'>Active</label>";;
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

if (isset($_GET['table']) && $_GET['table'] == 'product_sales_report') {
    $where = ' ';
    $offset = (isset($_GET['offset']) && !empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $db->escapeString($fn->xss_clean($_GET['offset'])) : 0;
    $limit = (isset($_GET['limit']) && !empty($_GET['limit']) && is_numeric($_GET['limit'])) ? $db->escapeString($fn->xss_clean($_GET['limit'])) : 10;
    $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $db->escapeString($fn->xss_clean($_GET['sort'])) : 'id';
    $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $db->escapeString($fn->xss_clean($_GET['order'])) : 'DESC';
    $currency = $fn->get_settings('currency', false);
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = $db->escapeString($fn->xss_clean($_GET['start_date']));
        $end_date = $db->escapeString($fn->xss_clean($_GET['end_date']));
        $where .= " AND DATE(oi.date_added)>=DATE('" . $start_date . "') AND DATE(oi.date_added)<=DATE('" . $end_date . "')";
    } else {
        $where .= " AND oi.date_added > DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    }
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " AND (oi.id like '%" . $search . "%' OR p.name like '%" . $search . "%' OR u.name like '%" . $search . "%' )";
    }
    $sql = "SELECT pv.product_id,p.name as p_name,p.seller_id, pv.measurement,u.short_code as u_name,oi.*, 
    (SELECT count(oi.product_variant_id) FROM `order_items` oi where pv.id = oi.product_variant_id $where) as total_sales, 
    (SELECT SUM(oi.sub_total) FROM `order_items` oi where pv.id = oi.product_variant_id $where) as total_price
    FROM `order_items` oi join `product_variant` pv ON oi.product_variant_id=pv.id join products p ON pv.product_id=p.id join unit u on pv.measurement_unit_id=u.id where oi.active_status='delivered' $where GROUP by (pv.id) ";
    $db->sql($sql);
    $res1 = $db->getResult();
    $total = $db->numRows($res1);

    $sql .= "ORDER BY $sort $order LIMIT $offset, $limit ";
    $db->sql($sql);
    $res = $db->getResult();

    $tempRow = $bulkData = $rows = array();
    $bulkData['total'] = $total;
    foreach ($res as $row) {
        $tempRow['product_name'] = $row['product_name'];
        $tempRow['product_varient_id'] = $row['product_variant_id'];
        $tempRow['unit_name'] = $row['measurement'] . ' ' . $row['u_name'];
        $tempRow['total_sales'] = $row['total_sales'];
        $tempRow['seller_id'] = $row['seller_id'];
        $seller_info = $fn->get_data($column = ['name', 'store_name'], "id=" . $row['seller_id'], 'seller');
        $tempRow['seller_name'] = $seller_info[0]['name'] . "(" . $seller_info[0]['store_name'] . ")";
        $tempRow['total_price'] = $currency . ' ' . number_format($row['total_price']);
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'Delivery Boy' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'seller') {
    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString($fn->xss_clean($_GET['offset'])) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString($fn->xss_clean($_GET['limit'])) : 10;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString($fn->xss_clean($_GET['sort'])) : 'id';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString($fn->xss_clean($_GET['order'])) : 'DESC';

    if (isset($_GET['filter_seller']) && $_GET['filter_seller'] != '') {
        $filter_seller = $db->escapeString($fn->xss_clean($_GET['filter_seller']));
        $where .= " WHERE status = '$filter_seller' ";
    }

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        if (isset($_GET['filter_seller']) && $_GET['filter_seller'] != '') {
            $where .= " and `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `store_name` like '%" . $search . "%' OR `store_url` like '%" . $search . "%' OR `store_description` like '%" . $search . "%' OR `state` like '%" . $search . "%' OR `bank_name` like '%" . $search . "%' OR `account_name` like '%" . $search . "%'";
        } else {
            $where .= " Where `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `store_name` like '%" . $search . "%' OR `store_url` like '%" . $search . "%' OR `store_description` like '%" . $search . "%' OR `state` like '%" . $search . "%' OR `bank_name` like '%" . $search . "%' OR `account_name` like '%" . $search . "%'";
        }
    }

    $sql = "SELECT COUNT(id) as total FROM `seller` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `seller` $where ORDER BY $sort $order LIMIT $offset , $limit";

    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    $path = 'upload/seller/';
    foreach ($res as $row) {
        $operate = ' <a href="edit-seller.php?id=' . $row['id'] . '"><i class="fa fa-edit"></i>Edit </a>';
        $operate .= ' <a class="btn-xs btn-danger" href="remove-seller.php?id=' . $row['id'] . '"><i class="fa fa-trash-o"></i>Remove</a>';
        $operate .= ' <a  href="view-seller-products.php?id=' . $row['id'] . '"><i class="fa fa-eye"></i>Products </a>';
        $operate .= ' <a  href="view-seller-orders.php?id=' . $row['id'] . '"><i class="fa fa-eye"></i>Orders </a>';

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['store_name'] = $row['store_name'];
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
            $tempRow['email'] = str_repeat("*", strlen($row['email']) - 13) . substr($row['email'], -13);
        } else {
            $tempRow['email'] = $row['email'];
            $tempRow['mobile'] = $row['mobile'];
        }

        $tempRow['balance'] = ceil($row['balance']);
        $tempRow['store_url'] = $row['store_url'];
        $tempRow['logo'] = "<a data-lightbox='product' href='" . DOMAIN_URL . $path . $row['logo'] . "'><img src='" . DOMAIN_URL . $path . $row['logo'] . "' height='50' /></a>";
        $tempRow['address_proof'] = "<a data-lightbox='product' href='" . DOMAIN_URL . $path . $row['address_proof'] . "'><img src='" . DOMAIN_URL . $path . $row['address_proof'] . "' height='50' /></a>";
        $tempRow['national_identity_card'] = "<a data-lightbox='product' href='" . DOMAIN_URL . $path . $row['national_identity_card'] . "'><img src='" . DOMAIN_URL . $path . $row['national_identity_card'] . "' height='50' /></a>";
        $tempRow['store_description'] = $row['store_description'];
        $tempRow['street'] = $row['street'];
        $tempRow['pincode_id'] = $row['pincode_id'];
        $tempRow['city_id'] = $row['city_id'];
        $tempRow['state'] = $row['state'];
        $tempRow['categories'] = $row['categories'];
        $tempRow['account_number'] = $row['account_number'];
        $tempRow['bank_ifsc_code'] = $row['bank_ifsc_code'];
        $tempRow['bank_name'] = $row['bank_name'];
        $tempRow['account_name'] = $row['account_name'];
        $tempRow['require_products_approval'] = ($row['require_products_approval'] == 1) ? "<span class='label label-primary'>Yes</span>" : "<span class='label label-info'>No</span>";
        $tempRow['commission'] = (!empty($row['commission'])) ? $row['commission'] : "";
        if ($row['status'] == 2)
            $tempRow['status'] = "<label class='label label-warning'>Not-Approved</label>";
        else if ($row['status'] == 1)
            $tempRow['status'] = "<label class='label label-success'>Approved</label>";
        else if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-danger'>Deactive</label>";
        else if ($row['status'] == 7)
            $tempRow['status'] = "<label class='label label-danger'>Removed</label>";
        $tempRow['operate'] = $operate;
        $tempRow['edit_commission'] = $operate = "<a class='btn btn-xs btn-primary category-wise-commission' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#category-wise-commission-modal' title='Category wise seller commission'><i class='fa fa-pencil-square-o'></i></a>";
        $rows[] = $tempRow;
    }
    $rows = mb_convert_encoding($rows, "UTF-8", "UTF-8");
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'USERS' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'pincodes') {
    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString(trim($fn->xss_clean($_GET['offset']))) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString(trim($fn->xss_clean($_GET['limit']))) : 10;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString(trim($fn->xss_clean($_GET['sort']))) : 'id';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString(trim($fn->xss_clean($_GET['order']))) : 'DESC';

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " Where `id` like '%" . $search . "%' OR `pincode` like '%" . $search . "%'  ";
    }

    $sql = "SELECT COUNT(id) as total FROM `pincodes` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `pincodes` $where ORDER BY $sort $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = ' <a href="edit-pincode.php?id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit"></i>Edit</a>&nbsp;';
        $operate .= ' <a class="btn btn-xs btn-danger" href="delete-pincode.php?id=' . $row['id'] . '" title="Delete"><i class="fa fa-trash-o"></i> Delete</a>';

        $tempRow['id'] = $row['id'];
        $tempRow['pincode'] = $row['pincode'];
        $tempRow['status'] = ($row['status'] == 1) ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>Deactive</label>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}


// data of 'USERS' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'seller_transactions') {
    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString(trim($fn->xss_clean($_GET['offset']))) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString(trim($fn->xss_clean($_GET['limit']))) : 10;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString(trim($fn->xss_clean($_GET['sort']))) : 'id';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString(trim($fn->xss_clean($_GET['order']))) : 'DESC';

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " Where st.`id` like '%" . $search . "%' OR st.`type` like '%" . $search . "%' OR st.`txn_id` like '%" . $search . "%' OR st.`status` like '%" . $search . "%' OR s.`name` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(st.id) as total FROM `seller_transactions` st join seller s on s.id=st.seller_id  " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT st.*,s.name as seller_name FROM `seller_transactions` st join seller s on s.id=st.seller_id $where ORDER BY $sort $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {

        $tempRow['id'] = $row['id'];
        $tempRow['seller_id'] = $row['seller_id'];
        $tempRow['seller_name'] = $row['seller_name'];
        $tempRow['order_id'] = $row['order_id'];
        $tempRow['order_item_id'] = $row['order_item_id'];
        $tempRow['type'] = $row['type'];
        $tempRow['seller_name'] = $row['seller_name'];
        $tempRow['txn_id'] = $row['txn_id'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['status'] = $row['status'];
        $tempRow['message'] = $row['message'];
        $tempRow['transaction_date'] = $row['transaction_date'];
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'USERS' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'transactions') {
    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString(trim($fn->xss_clean($_GET['offset']))) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString(trim($fn->xss_clean($_GET['limit']))) : 10;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString(trim($fn->xss_clean($_GET['sort']))) : 'id';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString(trim($fn->xss_clean($_GET['order']))) : 'DESC';

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " Where st.`id` like '%" . $search . "%' OR st.`type` like '%" . $search . "%' OR st.`txn_id` like '%" . $search . "%' OR st.`status` like '%" . $search . "%' OR s.`name` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(t.id) as total FROM `transactions` t join users u on u.id=t.user_id  " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT t.*,u.name as user_name FROM `transactions` t join users u on u.id=t.user_id $where ORDER BY $sort $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {

        $tempRow['id'] = $row['id'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['user_name'] = $row['user_name'];
        $tempRow['order_id'] = $row['order_id'];

        $tempRow['type'] = $row['type'];

        $tempRow['txn_id'] = $row['txn_id'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['status'] = $row['status'];
        $tempRow['message'] = $row['message'];
        $tempRow['transaction_date'] = $row['transaction_date'];
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'Wallet Transactions' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'seller_wallet_transactions') {

    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString(trim($fn->xss_clean($_GET['offset']))) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString(trim($fn->xss_clean($_GET['limit']))) : 10;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString(trim($fn->xss_clean($_GET['sort']))) : 'id';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString(trim($fn->xss_clean($_GET['order']))) : 'DESC';

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where w.`id` like '%" . $search . "%' OR `user_id` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `date_created` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(w.id) as total FROM `seller_wallet_transactions` w JOIN `seller` u ON u.id=w.seller_id " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT w.*,u.name FROM `seller_wallet_transactions` w JOIN `seller` u ON u.id=w.seller_id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['seller_id'] = $row['seller_id'];
        $tempRow['name'] = $row['name'];
        $tempRow['type'] = $row['type'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['message'] = $row['message'];
        $tempRow['date_created'] = $row['date_created'];
        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-danger'>Deactive</label>";
        else
            $tempRow['status'] = "<label class='label label-success'>Active</label>";
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'CATEGORY' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'top_sellers') {

    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString(trim($fn->xss_clean($_GET['offset']))) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString(trim($fn->xss_clean($_GET['limit']))) : 5;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString(trim($fn->xss_clean($_GET['sort']))) : 'total_revenue';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString(trim($fn->xss_clean($_GET['order']))) : 'DESC';


    $sql = "SELECT SUM(oi.sub_total) as total_revenue FROM `order_items` oi JOIN seller s on s.id=oi.seller_id where oi.date_added > DATE_SUB(NOW(), INTERVAL 1 MONTH) AND oi.active_status='delivered' GROUP BY oi.seller_id ";
    // $sql = "SELECT oi.sub_total as total_revenue,oi.seller_id,s.name as seller_name,s.store_name FROM `order_items` oi JOIN seller s on s.id=oi.seller_id where oi.active_status='delivered' ";

    $db->sql($sql);
    $res = $db->getResult();
    $total = $db->numRows($res);

    $sql = "SELECT SUM(oi.sub_total) as total_revenue,oi.seller_id,s.name as seller_name,s.store_name FROM `order_items` oi JOIN seller s on s.id=oi.seller_id where oi.date_added > DATE_SUB(NOW(), INTERVAL 1 MONTH) AND oi.active_status='delivered' GROUP BY oi.seller_id ORDER BY $sort $order LIMIT $offset, $limit";
    // $sql = "SELECT oi.sub_total as total_revenue,oi.seller_id,s.name as seller_name,s.store_name FROM `order_items` oi JOIN seller s on s.id=oi.seller_id where oi.active_status='delivered'  LIMIT $offset, $limit";
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    $i = 1;
    foreach ($res as $row) {

        $operate = '<a href="sellers.php"><i class="fa fa-eye"></i>View </a>';

        $tempRow['id'] = $i;
        $tempRow['seller_name'] = $row['seller_name'];
        $tempRow['store_name'] = $row['store_name'];
        $tempRow['total_revenue'] = number_format($row['total_revenue']);
        $tempRow['operate'] = $operate;
        $i++;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'CATEGORY' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'top_categories') {

    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString(trim($fn->xss_clean($_GET['offset']))) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString(trim($fn->xss_clean($_GET['limit']))) : 5;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString(trim($fn->xss_clean($_GET['sort']))) : 'total_revenues';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString(trim($fn->xss_clean($_GET['order']))) : 'DESC';


    $sql = "SELECT SUM(oi.sub_total) as total_revenues FROM `order_items` oi join `product_variant` pv ON oi.product_variant_id=pv.id join products p ON pv.product_id=p.id join unit u on pv.measurement_unit_id=u.id JOIN category c ON p.category_id=c.id WHERE oi.date_added > DATE_SUB(NOW(), INTERVAL 1 MONTH) AND oi.active_status='delivered' GROUP BY p.category_id ";
    $db->sql($sql);
    $res = $db->getResult();
    $total = $db->numRows($res);

    $sql = "SELECT pv.product_id,pv.id,p.name as p_name,p.category_id,p.seller_id,c.name as cat_name, pv.measurement,oi.product_name,oi.variant_name,SUM(oi.sub_total) as total_revenues FROM `order_items` oi join `product_variant` pv ON oi.product_variant_id=pv.id join products p ON pv.product_id=p.id join unit u on pv.measurement_unit_id=u.id JOIN category c ON p.category_id=c.id WHERE oi.date_added > DATE_SUB(NOW(), INTERVAL 1 MONTH) AND oi.active_status='delivered' GROUP BY p.category_id ORDER BY $sort $order LIMIT $offset, $limit";
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    $i = 1;
    foreach ($res as $row) {

        $operate = '<a href="categories.php"><i class="fa fa-eye"></i>View </a>';

        $tempRow['id'] = $i;
        $tempRow['cat_name'] = $row['cat_name'];
        $tempRow['p_name'] = $row['p_name'];
        $tempRow['total_revenues'] = number_format($row['total_revenues']);
        $tempRow['operate'] = $operate;
        $i++;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}


// Pickup Locations
if (isset($_GET['table']) && $_GET['table'] == 'pickup_locations') {
    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString(trim($fn->xss_clean($_GET['offset']))) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString(trim($fn->xss_clean($_GET['limit']))) : 10;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString(trim($fn->xss_clean($_GET['sort']))) : 'id';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString(trim($fn->xss_clean($_GET['order']))) : 'DESC';
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " Where s.name like '%" . $search . "%' OR pl.pickup_location like '%" . $search . "%'  OR pl.pin_code like '%" . $search . "%' OR pl.name like '%" . $search . "%' OR pl.city like '%" . $search . "%' OR pl.address like '%" . $search . "%'";
    }
    $sql = "SELECT COUNT(id) as total FROM `pickup_locations` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];
    $sql = "SELECT pl.*,s.name as seller_name,s.id as seller_id FROM `pickup_locations` pl left join seller s on pl.seller_id=s.id   $where ORDER BY $sort $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();
    // print_r($res);
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    foreach ($res as $row) {
        $operate = " <button class='btn btn-xs btn-info '   data-toggle='modal' data-target='#editModal' onclick='get_pickup_location(this)' data-seller-id=" . $row['seller_id'] . "  data-pickup-location=" . $row['pickup_location'] . " ><i class='fa fa-pencil-square-o' aria-hidden='true'></i></button> ";
        $operate .= "<button class='btn btn-xs btn-danger'  onclick='delete_pickup_location(this)' data-id=" . $row['id'] . "   data-pickup-location=" . $row['pickup_location'] . "><i class='fa fa-trash'></i></a> ";
        $tempRow['id'] = $row['id'];
        $tempRow['seller_id'] = $row['seller_id'];
        $tempRow['seller_name'] = $row['seller_name'];
        $tempRow['pickup_location'] = $row['pickup_location'];
        $tempRow['name'] = $row['name'];
        $tempRow['email'] = $row['email'];
        $tempRow['phone'] = $row['phone'];
        $tempRow['address'] = $row['address'];
        $tempRow['address_2'] = $row['address_2'];
        $tempRow['city'] = $row['city'];
        $tempRow['pin_code'] = $row['pin_code'];
        $tempRow['verified'] = ($row['verified'] == 1) ? "<label class='label label-success pointer' data-verified='0' data-id=" . $row['id'] . "  data-pickup-location=" . $row['pickup_location'] . "  onclick='verified(this)' title='deactive pickup location'><i class='fa fa-check'></i></label>" : "<a href='#/' class='verified text-light pointer'  onclick='verified(this)' data-id=" . $row['id'] . " data-verified='1' data-pickup-location=" . $row['pickup_location'] . "><label class='label label-danger' title='active pickup location'><i class='fa fa-times'></i></label></a>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}



// data of 'CATEGORY' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'wishlists') {

    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString(trim($fn->xss_clean($_GET['offset']))) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString(trim($fn->xss_clean($_GET['limit']))) : 5;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString(trim($fn->xss_clean($_GET['sort']))) : 'total_qty';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString(trim($fn->xss_clean($_GET['order']))) : 'DESC';

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " AND (f.id like '%" . $search . "%' OR f.product_id like '%" . $search . "%' OR p.name like '%" . $search . "%' )";
    }

    $sql = "SELECT f.product_id,p.seller_id, count(f.id) as total_qty,s.name as seller_name,p.name as product_name FROM favorites f join products p on p.id=f.product_id JOIN seller s ON s.id=p.seller_id where f.date_created > DATE_SUB(NOW(), INTERVAL 1 MONTH)  GROUP BY f.product_id $where ORDER by total_qty DESC  ";
    $db->sql($sql);
    $res = $db->getResult();
    $total = $db->numRows($res);

    $sql = "SELECT f.product_id,p.seller_id, count(f.id) as total_qty,s.name as seller_name,p.name as product_name FROM favorites f join products p on p.id=f.product_id JOIN seller s ON s.id=p.seller_id where f.date_created > DATE_SUB(NOW(), INTERVAL 1 MONTH)  GROUP BY f.product_id $where ORDER BY $sort $order LIMIT $offset, $limit";
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    $i = 1;
    foreach ($res as $row) {

        $operate = '<a href="products.php"><i class="fa fa-eye"></i>View </a>';

        $tempRow['id'] = $i;
        $tempRow['product_id'] = $row['product_id'];
        $tempRow['seller_id'] = $row['seller_id'];
        $tempRow['product_name'] = $row['product_name'];
        $tempRow['seller_name'] = $row['seller_name'];
        $tempRow['total_qty'] = $row['total_qty'];
        $tempRow['operate'] = $operate;
        $i++;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'USERS' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'cities') {
    $where = '';
    $offset = (isset($_GET['offset']) && !empty(trim($_GET['offset'])) && is_numeric($_GET['offset'])) ? $db->escapeString($fn->xss_clean($_GET['offset'])) : 0;
    $limit = (isset($_GET['limit']) && !empty(trim($_GET['limit'])) && is_numeric($_GET['limit'])) ? $db->escapeString($fn->xss_clean($_GET['limit'])) : 10;
    $sort = (isset($_GET['sort']) && !empty(trim($_GET['sort']))) ? $db->escapeString($fn->xss_clean($_GET['sort'])) : 'id';
    $order = (isset($_GET['order']) && !empty(trim($_GET['order']))) ? $db->escapeString($fn->xss_clean($_GET['order'])) : 'DESC';


    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " Where `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' ";
    }

    $sql = "SELECT COUNT(id) as total FROM `cities` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `cities` $where ORDER BY $sort $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = ' <a href="edit-city.php?id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit"></i>Edit</a>&nbsp;';
        $operate .= ' <a class="btn btn-xs btn-danger" href="delete-city.php?id=' . $row['id'] . '" title="Delete"><i class="fa fa-trash-o"></i> Delete</a>';

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['status'] = ($row['status'] == 0) ? "<label class='label label-danger'>Deactive</label>" : "<label class='label label-success'>Active</label>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'Delivery boy cash' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'delivery-boy-cash') {

    $offset = 0;
    $limit = 10;
    $sort = 't.id';
    $order = 'DESC';
    $where = " where (t.type='delivery_boy_cash'||t.type='delivery_boy_cash_collection')";
    if (isset($_GET['offset']))
        $offset = $db->escapeString($fn->xss_clean($_GET['offset']));
    if (isset($_GET['limit']))
        $limit = $db->escapeString($fn->xss_clean($_GET['limit']));

    if (isset($_GET['sort']))
        $sort = $db->escapeString($fn->xss_clean($_GET['sort']));
    if (isset($_GET['order']))
        $order = $db->escapeString($fn->xss_clean($_GET['order']));


    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $where = " where (t.type = '" . $_GET['type'] . "')";
    }
    if (isset($_GET['delivery_boy_id']) && !empty($_GET['delivery_boy_id'])) {
        $where .= ' and t.user_id = ' . $_GET['delivery_boy_id'];
    }
    if (isset($_GET['date']) && !empty($_GET['date'])) {
        $where .= " and DATE_FORMAT(t.transaction_date, '%Y-%m-%d')='" . $_GET['date'] . "'";
    }
    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " and (t.`id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `address` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR t.`transaction_date` like '%" . $search . "%')";
    }
    $sql = "SELECT COUNT(t.`id`) as total FROM `transactions` t LEFT JOIN `delivery_boys` d ON t.user_id=d.id" . $where;
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT t.*,d.name,d.mobile,d.address,d.cash_received FROM `transactions` t LEFT JOIN `delivery_boys` d ON t.user_id=d.id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
        }
        $opening_cash = $row['type'] == 'delivery_boy_cash' ? $row['amount'] : $row['cash'] - $row['amount'];
        $type = $row['type'] == 'delivery_boy_cash' ? '<span class="label label-danger">Received</span>' : '<span class="label label-success">Collected</span>';
        $tempRow['address'] = $row['address'];
        $tempRow['delivery_boy_id'] = $row['delivery_boy_id'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['type'] = $type;
        $tempRow['status'] = $row['status'] == '1' ? '<span class="label label-success">Success</span>' : '<span class="label label-danger">Failed</span>';
        $tempRow['message'] = $row['message'];
        $tempRow['date_created'] = $row['transaction_date'];


        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}


// data of 'NEWSLETTER' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'newsletter') {
    $where = '';
    $offset = (isset($_GET['offset']) && !empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $db->escapeString($_GET['offset']) : 0;
    $limit = (isset($_GET['limit']) && !empty($_GET['limit']) && is_numeric($_GET['limit'])) ? $db->escapeString($_GET['limit']) : 10;
    $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $db->escapeString($_GET['sort']) : 'id';
    $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $db->escapeString($_GET['order']) : 'DESC';

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " Where `id` like '%" . $search . "%' OR `email` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(`id`) as total FROM `newsletter` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `newsletter` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['email'] = $row['email'];
        $tempRow['created_at'] = $row['created_at'];

        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

if (isset($_GET['table']) && $_GET['table'] == 'user_address') {
    $where = '';
    $offset = (isset($_GET['offset']) && !empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $db->escapeString($_GET['offset']) : 0;
    $limit = (isset($_GET['limit']) && !empty($_GET['limit']) && is_numeric($_GET['limit'])) ? $db->escapeString($_GET['limit']) : 10;
    $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $db->escapeString($_GET['sort']) : 'id';
    $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $db->escapeString($_GET['order']) : 'DESC';

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " Where ua.`id` like '%" . $search . "%' OR ua.`pincode` like '%" . $search . "%'";
    }

    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $user_id = $db->escapeString($fn->xss_clean($_GET['user_id']));
        $where .= !empty($where) ? ' AND ua.user_id = ' . $user_id : ' WHERE ua.user_id = ' . $user_id;
    }
    $sql = "SELECT COUNT(user_id) as total FROM `user_addresses` ua " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT ua.*,u.email,u.balance,u.referral_code,u.status,(SELECT name FROM cities c WHERE c.id=ua.city_id) as city_name,ua.landmark as street,(SELECT name FROM area a WHERE a.id=ua.area_id) as area_name FROM `users` u LEFT JOIN user_addresses ua on ua.user_id=u.id " . $where . " ORDER BY `" . $sort . "` " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $path = DOMAIN_URL . 'upload/profile/';
        if (!empty($row['profile'])) {
            $tempRow['profile'] = "<a data-lightbox='product' href='" . $path . $row['profile'] . "' data-caption='" . $row['name'] . "'><img src='" . $path . $row['profile'] . "' title='" . $row['name'] . "' height='50' /></a>";
        } else {
            $tempRow['profile'] = "<a data-lightbox='product' href='" . $path . "default_user_profile.png' data-caption='" . $row['name'] . "'><img src='" . $path . "default_user_profile.png' title='" . $row['name'] . "' height='50' /></a>";
        }
        if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
            $tempRow['email'] = str_repeat("*", strlen($row['email']) - 13) . substr($row['email'], -13);
            $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
            $tempRow['email'] = $row['email'];
        }
        $tempRow['balance'] = $row['balance'];
        $tempRow['referral_code'] = $row['referral_code'];
        $tempRow['friends_code'] = !empty($row['friends_code']) ? $row['friends_code'] : '-';
        $tempRow['city_id'] = $row['city_id'];
        $tempRow['city'] = $row['city_name'];
        $tempRow['area_id'] = $row['area_id'];
        $tempRow['area'] = $row['area_name'];
        $tempRow['street'] = $row['street'];
        $tempRow['apikey'] = $row['apikey'];

        $tempRow['status'] = $row['status'] == 1 ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>De-Active</label>";
        $tempRow['date_created'] = $row['date_created'];
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}


$db->disconnect();
