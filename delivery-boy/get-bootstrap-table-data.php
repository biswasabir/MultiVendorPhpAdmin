<?php
session_start();

// set time for session timeout
$currentTime = time() + 25200;
$expired = 3600;
if (!isset($_SESSION['delivery_boy_id']) && !isset($_SESSION['name'])) {
	header("location:index.php");
} else {
	$id = $_SESSION['delivery_boy_id'];
}

// if session not set go to login page


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
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
	date_default_timezone_set($config['system_timezone']);
	$db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
	date_default_timezone_set('Asia/Kolkata');
	$db->sql("SET `time_zone` = '+05:30'");
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
        $where .= " AND (p.name like '%" . $search . "%' OR u.name like '%" . $search . "%' OR oi.id like '%" . $search . "%'OR oi.order_id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR o.address like '%" . $search . "%' OR o.`payment_method` like '%" . $search . "%' OR o.`delivery_charge` like '%" . $search . "%' OR o.`delivery_time` like '%" . $search . "%' OR oi.`status` like '%" . $search . "%' OR o.`date_added` like '%" . $search . "%')";
      
    }
    if (isset($_GET['filter_order']) && $_GET['filter_order'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_GET['filter_order']));
        $where .= " and oi.`active_status`='" . $filter_order . "'";

    }
    if (isset($_GET['seller_id']) && $_GET['seller_id'] != '') {
        $seller_id = $db->escapeString($fn->xss_clean($_GET['seller_id']));
        
        $where .= " and oi.seller_id= $seller_id";

    }
    $sql = "select COUNT(oi.id) as total from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id AND oi.active_status NOT IN ('awaiting_payment') and oi.delivery_boy_id = $id " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    $sql = "select oi.*,o.mobile,o.order_note,o.total,p.name as product_name ,o.delivery_charge,o.discount,o.promo_code,o.promo_discount,o.wallet_balance,o.final_total,o.payment_method,o.address,o.delivery_time,p.name as name, u.name as uname,v.measurement, (SELECT short_code FROM unit un where un.id=v.measurement_unit_id)as mesurement_unit_name,oi.status as order_status from `order_items` oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join users u ON u.id=oi.user_id left join orders o ON o.id=oi.order_id where oi.order_id=o.id AND oi.active_status NOT IN ('awaiting_payment') and oi.delivery_boy_id = $id  $where ORDER BY $sort $order LIMIT $offset , $limit";
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

        $discounted_amount = $row['total'] * $row['discount'] / 100; /*  */
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
        $tempRow['product_name'] = $row['product_name'];
        $tempRow['product_variant_id'] = $row['product_variant_id'];
        $tempRow['delivery_charge'] = $row['delivery_charge'];
        $tempRow['total'] = $row['total'];
        $tempRow['tax'] = $row['tax_amount'] . '(' . $row['tax_percentage'] . '%)';
        $tempRow['promo_discount'] = $row['promo_discount'];
        $tempRow['wallet_balance'] = $row['wallet_balance'];
        $tempRow['discount'] = $discount_in_rupees . '(' . $row['discount'] . '%)';
        $tempRow['qty'] = $row['quantity'];
        $tempRow['final_total'] = $row['final_total'];
        $tempRow['promo_code'] = $row['promo_code'];
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
        $tempRow['operate'] = '<a href="order-detail.php?id=' . $row['id'] . '"><i class="fa fa-eye"></i> View</a>
        <br><a href="delete-order.php?id=' . $row['id'] . '"><i class="fa fa-trash"></i> Delete</a>';
        $rows[] = $tempRow;
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// data of 'Fund Transfer' table goes here
if (isset($_GET['table']) && $_GET['table'] == 'fund-transfers') {

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
		$where = " Where f.`id` like '%" . $search . "%' OR f.`delivery_boy_id` like '%" . $search . "%' OR d.`name` like '%" . $search . "%' OR f.`message` like '%" . $search . "%' OR d.`mobile` like '%" . $search . "%' OR d.`address` like '%" . $search . "%' OR f.`opening_balance` like '%" . $search . "%' OR f.`closing_balance` like '%" . $search . "%' OR d.`balance` like '%" . $search . "%' OR f.`date_created` like '%" . $search . "%'";
	}
	if (empty($where)) {
		$where .= " WHERE delivery_boy_id = " . $id;
	} else {
		$where .= " AND delivery_boy_id = " . $id;
	}

	$sql = "SELECT COUNT(*) as total FROM `fund_transfers` f JOIN `delivery_boys` d ON f.delivery_boy_id=d.id" . $where;
	//  		echo $sql;
	$db->sql($sql);
	$res = $db->getResult();
	foreach ($res as $row)
		$total = $row['total'];

	$sql = "SELECT f.*,d.name,d.mobile,d.address FROM `fund_transfers` f JOIN `delivery_boys` d ON f.delivery_boy_id=d.id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
	// 		echo $sql;
	$db->sql($sql);
	$res = $db->getResult();

	$bulkData = array();
	$bulkData['total'] = $total;
	$rows = array();
	$tempRow = array();

	foreach ($res as $row) {
		$tempRow['id'] = $row['id'];
		$tempRow['name'] = $row['name'];
		$tempRow['mobile'] = $row['mobile'];
		$tempRow['address'] = $row['address'];
		$tempRow['delivery_boy_id'] = $row['delivery_boy_id'];
		$tempRow['opening_balance'] = number_format($row['opening_balance'],2);
        $tempRow['closing_balance'] = number_format($row['closing_balance'],2);
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
