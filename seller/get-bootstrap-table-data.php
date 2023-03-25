<?php
session_start();

// set time for session timeout
$currentTime = time() + 25200;
$expired = 3600;
if (!isset($_SESSION['seller_id']) && !isset($_SESSION['seller_name'])) {
    header("location:index.php");
} else {
    $id = $_SESSION['seller_id'];
}

// if current time is more than session timeout back to login page
if ($currentTime > $_SESSION['timeout']) {
    session_destroy();
    header("location:index.php");
}

// destroy previous session timeout and create new one
unset($_SESSION['timeout']);
$_SESSION['timeout'] = $currentTime + $expired;

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
$city_id = $fn->get_data($columns = ['city_id'], "id='" . $id . "'", 'seller');
$city_id = isset($city_id[0]['city_id']) && !empty($city_id[0]['city_id']) ? $city_id[0]['city_id'] : 0;
//data of 'ORDERS' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'orders') {
    $offset = 0;
    $limit = 10;
    $sort = 'o.id';
    $order = 'DESC';
    $where = ' ';
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $where .= " where DATE(date_added)>=DATE('" . $_GET['start_date'] . "') AND DATE(date_added)<=DATE('" . $_GET['end_date'] . "')";
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
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $where .= " AND (name like '%" . $search . "%' OR o.id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR address like '%" . $search . "%' OR `payment_method` like '%" . $search . "%' OR `delivery_charge` like '%" . $search . "%' OR `delivery_time` like '%" . $search . "%' OR o.`status` like '%" . $search . "%' OR `date_added` like '%" . $search . "%')";
        } else {
            $where .= " where (name like '%" . $search . "%' OR o.id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR address like '%" . $search . "%' OR `payment_method` like '%" . $search . "%' OR `delivery_charge` like '%" . $search . "%' OR `delivery_time` like '%" . $search . "%' OR o.`status` like '%" . $search . "%' OR `date_added` like '%" . $search . "%')";
        }
    }
    if (isset($_GET['filter_order']) && $_GET['filter_order'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_GET['filter_order']));
        if (isset($_GET['search']) && $_GET['search'] != '') {
            $where .= " and `active_status`='" . $filter_order . "'";
        } elseif (isset($_GET['start_date']) && $_GET['start_date'] != '') {
            $where .= " and `active_status`='" . $filter_order . "'";
        } else {
            $where .= " where `active_status`='" . $filter_order . "'";
        }
    }
    if (empty($where)) {
        $where .= " WHERE oi.seller_id = " . $id;
    } else {
        $where .= " AND oi.seller_id = " . $id;
    }

    $sql = "SELECT COUNT(oi.id) as total FROM `order_items` oi JOIN users u ON u.id=oi.user_id" . $where;

    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    $sql = "select o.*,oi.id as order_item_id,oi.seller_id,oi.delivery_boy_id,u.name FROM orders o JOIN users u ON u.id=o.user_id JOIN order_items oi ON o.id=oi.order_id" . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    for ($i = 0; $i < count($res); $i++) {
        $sql = "select oi.*,oi.id as order_item_id,p.name as name, u.name as uname,v.measurement, (SELECT short_code FROM unit un where un.id=v.measurement_unit_id)as mesurement_unit_name,(SELECT status FROM orders o where o.id=oi.order_id)as order_status from `order_items` oi 
			    join product_variant v on oi.product_variant_id=v.id 
			    join products p on p.id=v.product_id 
			    JOIN users u ON u.id=oi.user_id 
			    where oi.order_id=" . $res[$i]['id'];
        $db->sql($sql);
        $res[$i]['items'] = $db->getResult();
    }
    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $items = $row['items'];

        $items1 = '';
        $temp = '';
        $total_amt = 0;
        foreach ($items as $item) {
            $temp .= "<b>ID :</b>" . $item['id'] . "<b> Product Variant Id :</b> " . $item['product_variant_id'] . "<b> Name : </b>" . $item['name'] . " <b>Unit : </b>" . $item['measurement'] . $item['mesurement_unit_name'] . " <b>Price : </b>" . $item['price'] . " <b>QTY : </b>" . $item['quantity'] . " <b>Subtotal : </b>" . $item['quantity'] * $item['price'] . "<br>------<br>";
            $total_amt += $item['sub_total'];
        }

        $items1 = $temp;
        $temp = '';
        $status = json_decode($row['items'][0]['order_status']);

        if (!empty($status)) {
            foreach ($status as $st) {
                $temp .= $st[0] . " : " . $st[1] . "<br>------<br>";
            }
        }
        if ($row['active_status'] == 'received') {
            $active_status = '<label class="label label-primary">' . $row['active_status'] . '</label>';
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

        $sql1 = "select name from delivery_boys where id=" . $row['delivery_boy_id'];
        $db->sql($sql1);
        $res_dboy = $db->getResult();

        $total = ($total_amt + $row['delivery_charge']) - $row['wallet_balance'];
        $discounted_amount = $total * $row['items'][0]['discount'] / 100;
        $status = $temp;
        $operate = "<a class='btn btn-sm btn-primary edit-fees' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editFeesModal'>Edit</a>";

        $operate .= "<a onclick='return conf(\"delete\");' class='btn btn-sm btn-danger' href='public/db_operations.php?id=" . $row['id'] . "&delete_order=1' target='_blank'>Delete</a>";
        $discounted_amount = $row['total'] * $row['items'][0]['discount'] / 100; /*  */
        $final_total = $row['total'] - $discounted_amount;
        $discount_in_rupees = $row['total'] - $final_total;
        $discount_in_rupees = floor($discount_in_rupees);
        $tempRow['id'] = $row['id'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['name'] = $row['items'][0]['uname'];
        $tempRow['mobile'] = $row['mobile'];
        $tempRow['delivery_charge'] = $row['delivery_charge'];
        $tempRow['order_note'] = $row['order_note'];
        $tempRow['items'] = $items1;
        $tempRow['total'] = $row['total'];
        $tempRow['tax'] = $row['tax_amount'] . '(' . $row['tax_percentage'] . '%)';
        $tempRow['promo_discount'] = $row['promo_discount'];
        $tempRow['wallet_balance'] = $row['wallet_balance'];
        $tempRow['discount'] = $discount_in_rupees . '(' . $row['items'][0]['discount'] . '%)';
        $tempRow['qty'] = $row['items'][0]['quantity'];
        $tempRow['final_total'] = ceil($row['final_total']);
        $tempRow['deliver_by'] = !empty($res_dboy[0]['name']) ? $res_dboy[0]['name'] : 'Not Assigned';
        $tempRow['promo_code'] = $row['promo_code'];
        $tempRow['payment_method'] = $row['payment_method'];
        $tempRow['address'] = $row['address'];
        $tempRow['delivery_time'] = $row['delivery_time'];
        $tempRow['status'] = $status;
        $tempRow['active_status'] = $active_status;
        $tempRow['wallet_balance'] = $row['wallet_balance'];
        $tempRow['date_added'] = date('d-m-Y', strtotime($row['date_added']));
        $tempRow['operate'] = '<a href="order-detail.php?id=' . $row['id'] . '"><i class="fa fa-eye"></i> View</a>';
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

//data of 'ORDER ITEMS' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'order_items') {
    $offset = 0;
    $limit = 10;
    $sort = 'oi.id';
    $order = 'DESC';
    $where = ' ';
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        if (empty($where)) {
            $where .= " where DATE(oi.date_added)>=DATE('" . $_GET['start_date'] . "') AND DATE(oi.date_added)<=DATE('" . $_GET['end_date'] . "')";
        } else {
            $where .= " AND DATE(oi.date_added)>=DATE('" . $_GET['start_date'] . "') AND DATE(oi.date_added)<=DATE('" . $_GET['end_date'] . "')";
        }
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
        $where .= " AND (p.name like '%" . $search . "%' OR u.name like '%" . $search . "%' OR oi.id like '%" . $search . "%'OR oi.order_id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR o.address like '%" . $search . "%' OR o.`payment_method` like '%" . $search . "%' OR o.`delivery_charge` like '%" . $search . "%' OR o.`delivery_time` like '%" . $search . "%' OR oi.`status` like '%" . $search . "%' OR o.`date_added` like '%" . $search . "%')";
    }

    if (empty($where)) {
        $where .= " WHERE oi.seller_id = $id ";
    } else {
        $where .= " AND oi.seller_id = $id ";
    }

    if (isset($_GET['filter_order']) && $_GET['filter_order'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_GET['filter_order']));
        if (empty($where)) {
            $where .= " WHERE oi.`active_status`='" . $filter_order . "'";
        } else {
            $where .= " AND oi.`active_status`='" . $filter_order . "'";
        }
    }

    $sql = "select COUNT(oi.id) as total from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    // $sql = "select ot.shipment_id,ot.courier_company_id,oi.*,o.mobile,o.order_note,o.total,p.name as product_name ,o.delivery_charge,o.discount,o.promo_code,o.promo_discount,o.wallet_balance,o.final_total,o.payment_method,o.address,o.delivery_time,p.name as name, u.name as uname,v.measurement, (SELECT short_code FROM unit un where un.id=v.measurement_unit_id)as mesurement_unit_name,oi.status as order_status from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id left join order_trackings ot ON ot.order_item_id=oi.id where oi.order_id=o.id and oi.seller_id = $id $where ORDER BY $sort $order LIMIT $offset , $limit";
    $sql = "select oi.*,o.mobile,o.order_note,o.total,p.name as product_name ,o.delivery_charge,o.discount,o.promo_code,o.promo_discount,o.wallet_balance,o.final_total,o.payment_method,o.address,o.delivery_time,p.name as name, u.name as uname,v.measurement, (SELECT short_code FROM unit un where un.id=v.measurement_unit_id)as mesurement_unit_name,oi.status as order_status from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id and oi.seller_id = $id $where ORDER BY $sort $order LIMIT $offset , $limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    $str_to_replace = '*******';
    foreach ($res as $row) {
        $temp = '';
        $total_amt = 0;
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
        $sql = "select name from seller where id=" . $id;
        $db->sql($sql);
        $res_seller = $db->getResult();
        $status = $temp;
        $discounted_amount = $row['sub_total'] * $row['discount'] / 100; /*  */
        $final_total = $row['sub_total'] - $discounted_amount;
        $discount_in_rupees = $row['sub_total'] - $final_total;
        $discount_in_rupees = floor($discount_in_rupees);
        $tempRow['order_id'] = $row['order_id'];
        $tempRow['id'] = $row['id'];
        $tempRow['user_id'] = $row['user_id'];
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0 || !$fn->get_seller_permission($_SESSION['seller_id'], 'customer_privacy')) {
            $tempRow['mobile'] = $str_to_replace . substr($row['mobile'], 7);
            // $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
        }

        $tempRow['name'] = $row['uname'];
        // if () {
        //     $tempRow['mobile'] = $row['mobile'];
        // } else {
        //     $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        // }
        $operate = '<a href="order-detail.php?id=' . $row['order_id'] . '" class="btn btn-xs btn-primary" title="View order"><i class="fa fa-eye"></i>View</a>';
        // $operate = '<a href="order-detail.php?id=' . $row['order_id'] . '"><i class="fa fa-eye"></i> View</a>';
        // if ($row['shipping_method'] == 'standard') {
        //     $operate .= '<a class="btn btn-xs btn-info mt-5 request-pickup" id="shipment_btn" data-item_id=' . $row['id'] . ' data-shipment_id=' . $row['shipment_id'] . ' data-courier_company_id=' . $row['courier_company_id'] . ' title="Request for pickup"><i class="fa fa-check"></i>Request pickup</a>';
        // }
        // $operate = '<a href="order-detail.php?id=' . $row['order_id'] . '"><i class="fa fa-eye"></i> View</a>';
        $tempRow['order_note'] = $row['order_note'];
        $tempRow['is_credited'] = (empty($row['is_credited'])) ? '<label class="label label-danger">Not Credited</label>' : '<label class="label label-success">Credited</label>';
        $tempRow['product_name'] = $row['product_name'];
        $tempRow['product_variant_id'] = $row['product_variant_id'];
        $tempRow['tax'] = $row['tax_amount'] . '(' . $row['tax_percentage'] . '%)';
        $tempRow['discount'] = $discount_in_rupees . '(' . $row['discount'] . '%)';
        $tempRow['qty'] = $row['quantity'];
        $tempRow['total'] = $row['sub_total'];
        $tempRow['deliver_by'] = !empty($res_dboy[0]['name']) ? $res_dboy[0]['name'] : 'Not Assigned';
        $tempRow['payment_method'] = $row['payment_method'];
        $tempRow['address'] = $row['address'];
        $tempRow['delivery_time'] = $row['delivery_time'];
        $tempRow['status'] = $status;
        $tempRow['active_status'] = $active_status;
        $tempRow['date_added'] = date('d-m-Y', strtotime($row['date_added']));
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

//data of 'CATEGORY' table goes here
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

    $sql = "SELECT categories FROM seller WHERE id = " . $id;
    $db->sql($sql);
    $res = $db->getResult();
    $category_ids = explode(',', $res[0]['categories']);
    $category_id = implode(',', $category_ids);

    if (empty($where)) {
        $where .= " WHERE id IN($category_id)";
    } else {
        $where .= " AND id IN($category_id)";
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

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['subtitle'] = $row['subtitle'];
        $tempRow['image'] = "<a data-lightbox='category' href='" . DOMAIN_URL . $row['image'] . "' data-caption='" . $row['name'] . "'><img src='" . DOMAIN_URL . $row['image'] . "' title='" . $row['name'] . "' height='50' /></a>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

//data of 'PRODUCTS' table goes here
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
        $where = " where (pv.`id` like '%" . $search . "%' OR p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR pv.`measurement` like '%" . $search . "%' OR u.`short_code` like '%" . $search . "%' )";
    }

    if (isset($_GET['category_id']) && $_GET['category_id'] != '') {
        $category_id = $db->escapeString($fn->xss_clean($_GET['category_id']));
        if (isset($_GET['search']) and $_GET['search'] != '')
            $where .= ' and p.`category_id`=' . $category_id;
        else
            $where .= ' where p.`category_id`=' . $category_id;
    }
    if (isset($_GET['subcategory_id']) && $_GET['subcategory_id'] != '') {
        $subcategory_id = $db->escapeString($fn->xss_clean($_GET['subcategory_id']));
        $where .= empty($where) ? " WHERE seller_id = $id AND subcategory_id = $subcategory_id " : " AND seller_id = $id AND subcategory_id = $subcategory_id ";
    }
    if (isset($_GET['product_id']) && $_GET['product_id'] != '') {
        $product_id = $db->escapeString($fn->xss_clean($_GET['product_id']));
        $where .= empty($where) ? " WHERE seller_id = $id AND p.id = $product_id " : " AND seller_id = $id AND p.id = $product_id ";
    }
    if (isset($_GET['is_approved']) && $_GET['is_approved'] != '') {
        $is_approved = $db->escapeString($fn->xss_clean($_GET['is_approved']));
        $where .= empty($where) ? " WHERE seller_id = $id AND p.is_approved = $is_approved " : " AND seller_id = $id AND p.is_approved = $is_approved ";
    }
    if (isset($_GET['sold_out']) && $_GET['sold_out'] == 1) {
        $where .= empty($where) ? " WHERE  pv.serve_for = 'Sold Out' AND p.seller_id=$id" : " AND  serve_for = 'Sold Out' AND p.seller_id=$id";
    }
    if (isset($_GET['low_stock']) && $_GET['low_stock'] == 1) {
        $where .= empty($where) ? " WHERE pv.stock < $low_stock_limit AND pv.serve_for = 'Available' AND seller_id=$id" : " AND stock < $low_stock_limit AND serve_for = 'Available' AND seller_id=$id";
    }


    $join = "LEFT JOIN `product_variant` pv ON pv.product_id = p.id
    LEFT JOIN `unit` u ON u.id = pv.measurement_unit_id LEFT JOIN `seller` s ON s.id = p.seller_id";

    $sql = "SELECT categories FROM seller WHERE id = " . $id;
    $db->sql($sql);
    $res = $db->getResult();
    $category_ids = explode(',', $res[0]['categories']);
    $category_id = implode(',', $category_ids);

    if (empty($where)) {
        $where .= " WHERE p.category_id IN($category_id) AND p.seller_id =" . $id;
    } else {
        $where .= " AND p.category_id IN($category_id) AND p.seller_id =" . $id;
    }

    $sql = "SELECT COUNT(p.id) as `total` FROM `products` p $join " . $where . "";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT p.id AS id,p.*, p.name,p.seller_id,p.status,p.tax_id, p.image,s.name as seller_name, p.indicator, p.manufacturer, p.made_in, p.return_status, p.cancelable_status, p.till_status,p.description, pv.id as product_variant_id, pv.price, pv.discounted_price, pv.measurement, pv.serve_for, pv.stock,pv.stock_unit_id, u.short_code 
            FROM `products` p
            $join 
            $where ORDER BY $sort $order LIMIT $offset, $limit";
    // echo $sql;
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
        $tempRow['image'] = "<a data-lightbox='product' href='" . DOMAIN_URL . $row['image'] . "' data-caption='" . $row['name'] . "'><img src='" . DOMAIN_URL . $row['image'] . "' title='" . $row['name'] . "' height='50' /></a>";
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

    if (isset($_GET['search']) and $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " where (s.`id` like '%" . $search . "%' OR s.`name` like '%" . $search . "%')";
    }

    if (isset($_GET['category_id']) && $_GET['category_id'] != '') {
        $category_id = $db->escapeString($fn->xss_clean($_GET['category_id']));
        $where .= empty($where) ? " WHERE s.category_id = $category_id " : "AND s.category_id = $category_id ";
    }

    $sql1 = "SELECT categories FROM seller WHERE id = " . $id;
    $db->sql($sql1);
    $res1 = $db->getResult();

    $category_ids = explode(',', $res1[0]['categories']);
    $category_id = implode(',', $category_ids);

    if (empty($where)) {
        $where .= " WHERE s.category_id IN($category_id) ";
    } else {
        $where .= " ANd s.category_id IN($category_id) ";
    }

    $sql = "SELECT COUNT(s.id) as total FROM `subcategory` s" . $where;
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

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['category_name'] = $row['category_name'];
        $tempRow['subtitle'] = $row['subtitle'];
        $tempRow['image'] = "<a data-lightbox='category' href='" . DOMAIN_URL . $row['image'] . "' data-caption='" . $row['name'] . "'><img src='" . DOMAIN_URL . $row['image'] . "' title='" . $row['name'] . "' height='50' /></a>";
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
// data of 'MEDIA' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'media') {

    $offset = (isset($_GET['offset']) && !empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $db->escapeString($fn->xss_clean($_GET['offset'])) : 0;
    $limit = (isset($_GET['limit']) && !empty($_GET['limit']) && is_numeric($_GET['limit'])) ? $db->escapeString($fn->xss_clean($_GET['limit'])) : 10;
    $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $db->escapeString($fn->xss_clean($_GET['sort'])) : 'id';
    $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $db->escapeString($fn->xss_clean($_GET['order'])) : 'DESC';
    $where = '';
    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where = " AND (`id` like '%" . $search . "%' OR `extension` like '%" . $search . "%' OR `type` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `date_created` like '%" . $search . "%')";
    }

    $sql = "SELECT COUNT(id) as total FROM `media` WHERE seller_id = $id $where";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT * FROM `media` WHERE seller_id = $id $where ORDER BY $sort $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    foreach ($res as $row) {
        $operate = " <a class='btn btn-xs btn-danger delete_media' data-id='" . $row['id'] . "' data-image='" . $row['sub_directory'] . '/' . $row['name'] . "'title='Delete'><i class='fa fa-trash-o'></i>Delete</a>";
        $operate .= " <a class='btn btn-xs btn-primary copy_to_clipboard' title='Copy'><i class='fa fa-copy'></i>Copy</a> ";

        $tempRow['id'] = $row['id'];
        $tempRow['image'] = "<img src='" . DOMAIN_URL . $row['sub_directory'] . '/' . $row['name'] . "' width='60' height: 60px; />";
        $full_path = $row['sub_directory']  . $row['name'];
        $tempRow['image'] .= "<span class='copy-path hide'>$full_path</span>";
        $tempRow['name'] = $row['name'];
        $tempRow['extension'] = $row['extension'];
        $tempRow['type'] = $row['type'];
        $tempRow['sub_directory'] = $row['sub_directory'];
        $tempRow['size'] = ($row['size'] > 1) ? formatBytes($row['size']) : $row['size'];
        $tempRow['date_created'] = date('d-m-Y h:i:sa', strtotime($row['date_created']));
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
    $where = '';
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
    $group_by = ' GROUP by u.id';
    if (isset($_GET['filter_order_status']) && $_GET['filter_order_status'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_GET['filter_order']));
        if (isset($_GET['search']) and $_GET['search'] != '')
            $where .= ' and active_status=' . $filter_order;
        else
            $where = ' where active_status=' . $filter_order;
    }
    $where .= empty($where) ? ' where oi.seller_id=' . $_SESSION['seller_id'] : ' and oi.seller_id=' . $_SESSION['seller_id'];

    $sql = "SELECT COUNT(DISTINCT(u.id)) as total FROM `users` u LEFT JOIN user_addresses ua on u.id=ua.user_id LEFT JOIN pincodes p on p.id=ua.pincode_id LEFT JOIN area a on a.id=ua.area_id LEFT JOIN order_items oi on u.id=oi.user_id" . $where;

    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT DISTINCT u.*,a.name as area_name,p.pincode as pincode,c.name as city,ua.pincode_id FROM `users` u LEFT JOIN user_addresses ua on u.id=ua.user_id LEFT JOIN pincodes p on p.id=ua.pincode_id LEFT JOIN area a on a.id=ua.area_id LEFT JOIN cities c on c.id=a.city_id LEFT JOIN order_items oi on u.id=oi.user_id" . $where . " $group_by ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    $bulkData = $rows = $tempRow = array();
    $bulkData['total'] = $total;
    $str_to_replace = '*******';
    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $path = DOMAIN_URL . 'upload/profile/';
        if (!empty($row['profile'])) {
            $tempRow['profile'] = "<a data-lightbox='product' href='" . $path . $row['profile'] . "' data-caption='" . $row['name'] . "'><img src='" . $path . $row['profile'] . "' title='" . $row['name'] . "' height='50' /></a>";
        } else {
            $tempRow['profile'] = "<a data-lightbox='product' href='" . $path . "default_user_profile.png' data-caption='" . $row['name'] . "'><img src='" . $path . "default_user_profile.png' title='" . $row['name'] . "' height='50' /></a>";
        }

        $operate = '<a class="btn btn-xs btn-info view-address" data-id="' . $row['id'] . '" data-toggle="modal" data-target="#ViewAddressModel" title="View Address"><i class="fa fa-credit-card"></i></a>&nbsp;';

        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0 || !$fn->get_seller_permission($_SESSION['seller_id'], 'customer_privacy')) {
            $tempRow['email'] = $str_to_replace . substr($row['email'], 7);
            $tempRow['mobile'] = $str_to_replace . substr($row['mobile'], 7);
            // $tempRow['mobile'] = str_repeat("*", strlen($row['mobile']) - 3) . substr($row['mobile'], -3);
        } else {
            $tempRow['mobile'] = $row['mobile'];
            $tempRow['email'] = $row['email'];
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
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'pincodes' table goes here
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

        $tempRow['id'] = $row['id'];
        $tempRow['pincode'] = $row['pincode'];
        $tempRow['status'] = ($row['status'] == 1) ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>Deactive</label>";
        $rows[] = $tempRow;
    }
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
    if ($city_id != 0) {
        $where .= ' and a.city_id = ' . $city_id;
    }
    if (isset($_GET['filter_area']) && !empty($_GET['filter_area'])) {
        $filter_area = $db->escapeString($fn->xss_clean($_GET['filter_area']));
        $where .= ' and  a.pincode_id=' . $filter_area;
    }
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        if (isset($_GET['filter_area']) && !empty($_GET['filter_area'])) {
            $where .= " and a.`id` like '%" . $search . "%' OR a.`name` like '%" . $search . "%' OR `pincode_id` like '%" . $search . "%' OR p.`pincode` like '%" . $search . "%'";
        } else {
            $where .= " and a.`id` like '%" . $search . "%' OR a.`name` like '%" . $search . "%' OR `pincode_id` like '%" . $search . "%' OR p.`pincode` like '%" . $search . "%'";
        }
    }

    $sql = "SELECT COUNT(a.id) as total FROM `area` a join pincodes p ON a.pincode_id=p.id join cities c on c.id=a.city_id where c.status= 1 and p.status=1 " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT a.*,p.pincode,c.name as city_name FROM `area` a join pincodes p ON a.pincode_id=p.id join cities c on c.id=a.city_id where c.status= 1 and p.status=1 $where ORDER BY $sort $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {


        $tempRow['id'] = $row['id'];
        $tempRow['city_id'] = $row['city_id'];
        $tempRow['pincode_id'] = $row['pincode_id'];
        $tempRow['name'] = $row['name'];
        $tempRow['delivery_charges'] = $row['delivery_charges'];
        $tempRow['minimum_free_delivery_order_amount'] = $row['minimum_free_delivery_order_amount'];
        $tempRow['city_name'] = $row['city_name'];
        $tempRow['pincode'] = $row['pincode'];
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

//data of 'PRODUCT_SALES_REPORT' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'product_sales_report') {
    $where = ' ';
    $offset = (isset($_GET['offset']) && !empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $db->escapeString($_GET['offset']) : 0;
    $limit = (isset($_GET['limit']) && !empty($_GET['limit']) && is_numeric($_GET['limit'])) ? $db->escapeString($_GET['limit']) : 10;
    $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $db->escapeString($_GET['sort']) : 'id';
    $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $db->escapeString($_GET['order']) : 'DESC';
    $currency = $fn->get_settings('currency', false);
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = $db->escapeString($fn->xss_clean($_GET['start_date']));
        $end_date = $db->escapeString($fn->xss_clean($_GET['end_date']));
        $where .= " AND p.seller_id=$id AND DATE(oi.date_added)>=DATE('" . $start_date . "') AND DATE(oi.date_added)<=DATE('" . $end_date . "')";
    } else {
        $where .= " AND p.seller_id=$id AND oi.date_added > DATE_SUB(NOW(), INTERVAL 1 MONTH) ";
    }
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $db->escapeString($fn->xss_clean($_GET['search']));
        $where .= " AND (oi.id like '%" . $search . "%' OR p.name like '%" . $search . "%' OR u.name like '%" . $search . "%' )";
    }
    $sql = "SELECT pv.product_id, pv.measurement,u.short_code as u_name,oi.*, 
        (SELECT count(oi.product_variant_id) FROM `order_items` oi where pv.id = oi.product_variant_id $where ) as total_sales, 
        (SELECT SUM(oi.sub_total) FROM `order_items` oi where pv.id = oi.product_variant_id $where ) as total_price
        FROM `order_items` oi join `product_variant` pv ON oi.product_variant_id=pv.id join products p ON pv.product_id=p.id join unit u on pv.measurement_unit_id=u.id WHERE oi.active_status='delivered'  $where GROUP by (pv.id) ";
    $db->sql($sql);
    $res1 = $db->getResult();
    $total = $db->numRows($res1);
    $sql .= " ORDER BY $sort $order LIMIT $offset, $limit";
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();

    $tempRow = $bulkData = $rows = array();
    $bulkData['total'] = $total;
    foreach ($res as $row) {
        $tempRow['product_name'] = $row['product_name'];
        $tempRow['product_varient_id'] = $row['product_variant_id'];
        $tempRow['unit_name'] = $row['measurement'] . ' ' . $row['u_name'];
        $tempRow['total_sales'] = $row['total_sales'];
        $tempRow['date_added'] = $row['date_added'];
        $tempRow['total_price'] = $currency . ' ' . number_format($row['total_price']);
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

//data of 'SALES_REPORTS' table goes here
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

    if (isset($_GET['cat_id']) && $_GET['cat_id'] != '') {
        $cat_id = $db->escapeString($fn->xss_clean($_GET['cat_id']));
        $where .= " and p.category_id= $cat_id";
    }
    $sql = "select COUNT(oi.id) as total from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id and oi.active_status='delivered' and oi.seller_id= $id $where ";

    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "select oi.id,o.total,oi.seller_id,o.user_id,o.mobile,oi.sub_total,p.name as product_name,o.final_total,o.address, u.name as uname, oi.status as order_status,DATE(oi.date_added) as date_added from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id and oi.active_status='delivered' and oi.seller_id= $id $where ORDER BY $sort $order LIMIT $offset , $limit";
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



//data of 'TAXES' table goes here
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

        $tempRow['id'] = $row['id'];
        $tempRow['title'] = $row['title'];
        $tempRow['percentage'] = $row['percentage'];
        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-danger'>Deactive</label>";
        else
            $tempRow['status'] = "<label class='label label-success'>Active</label>";;
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'SELLER_TANSACTIONS' table goes here
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
    $where .= (empty($where)) ? " WHERE st.seller_id = $id" : "AND st.seller_id = $id";
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

// data of 'SELLER_WALLET_TRANSACTIONS' table goes here
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
    $where .= (empty($where)) ? " WHERE w.seller_id = $id" : "AND w.seller_id = $id";
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

    $sql = "SELECT COUNT(w.id) as total FROM `withdrawal_requests` w JOIN `seller` s ON s.id=w.type_id WHERE type = 'seller' and type_id=$id";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT w.*,s.name as seller_name,s.balance as s_balance FROM `withdrawal_requests` w  JOIN `seller` s ON s.id=w.type_id WHERE type = 'seller' and type_id=$id ORDER BY $sort $order LIMIT $offset ,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['type'] = $row['type'];
        $tempRow['type_id'] = $row['type_id'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['balance'] = $row['s_balance'];
        $tempRow['message'] = empty($row['message']) ? '-' : $row['message'];
        $tempRow['name'] = !empty($row['seller_name']) ? $row['seller_name'] : "";

        if ($row['status'] == 0)
            $tempRow['status'] = "<label class='label label-warning'>Pending</label>";
        if ($row['status'] == 1)
            $tempRow['status'] = "<label class='label label-primary'>Approved</label>";
        if ($row['status'] == 2)
            $tempRow['status'] = "<label class='label label-danger'>Cancelled</label>";
        $tempRow['date_created'] = $row['date_created'];
        $tempRow['last_updated'] = $row['last_updated'];
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
        $where .= " Where `id` like '%" . $search . "%' OR `cities` like '%" . $search . "%' ";
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


        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['status'] = ($row['status'] == 0) ? "<label class='label label-danger'>Deactive</label>" : "<label class='label label-success'>Active</label>";
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

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

if (isset($_GET['table']) && $_GET['table'] == 'pickup-locations') {

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
        $where = " and `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `pickup_location` like '%" . $search . "%' OR `email` like '%" . $search . "%' OR `phone` like '%" . $search . "%' OR `address` like '%" . $search . "%' OR `address_2` like '%" . $search . "%' OR `city` like '%" . $search . "%' OR `state` like '%" . $search . "%' OR `country` like '%" . $search . "%' OR `pin_code` like '%" . $search . "%' OR `date_created` like '%" . $search . "%'";
    }

    $sql = "SELECT COUNT(p.id) as total FROM `pickup_locations` p WHERE seller_id=$id" . $where;
    // echo $sql;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row)
        $total = $row['total'];

    $sql = "SELECT p.* FROM `pickup_locations` p  WHERE seller_id = $id $where ORDER BY $sort $order LIMIT $offset ,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['seller_id'] = $row['seller_id'];
        $tempRow['pickup_location'] = $row['pickup_location'];
        $tempRow['name'] = $row['name'];
        $tempRow['email'] = $row['email'];
        $tempRow['phone'] = $row['phone'];
        $tempRow['address'] = $row['address'];
        $tempRow['address_2'] = $row['address_2'];
        $tempRow['city'] = $row['city'];
        $tempRow['state'] = $row['state'];
        $tempRow['country'] = $row['country'];
        $tempRow['pin_code'] = $row['pin_code'];
        $tempRow['latitude'] = $row['latitude'];
        $tempRow['longitude'] = $row['longitude'];
        $tempRow['latitude'] = $row['latitude'];
        if ($row['verified'] == 0)
            $tempRow['verified'] = "<label class='label label-warning'>Not verified</label>";
        if ($row['verified'] == 1)
            $tempRow['verified'] = "<label class='label label-primary'>Verified</label>";
        $tempRow['date_created'] = $row['date_created'];
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
