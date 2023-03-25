<?php
include('../includes/crud.php');
include('../includes/custom-functions.php');

$db = new Database();
$db->connect();
$fn = new custom_functions();
// For test payments we want to enable the sandbox mode. If you want to put live
// payments through then this setting needs changing to `false`.
$enableSandbox = true;

// Database settings. Change these for your database configuration.

// PayPal settings. Change these to your account details and the relevant URLs
// for your site.
$paypalConfig = [
	'email' => 'seller@somedomain.com',
	'return_url' => DOMAIN_URL.'paypal/payment_status.php',
	'cancel_url' => DOMAIN_URL.'paypal/payment_status.php?tx=failure',
	'notify_url' => DOMAIN_URL.'paypal/handler.php'
];

$paypalUrl = $enableSandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

// Product being purchased.
$itemName = 'Test Item';
$itemAmount = 5.00;

// Include Functions

// Check if paypal request or response
if (!isset($_POST["txn_id"]) && !isset($_POST["txn_type"])) {

	// Grab the post data so that we can set up the query string for PayPal.
	// Ideally we'd use a whitelist here to check nothing is being injected into
	// our post data.
	$data = [];
	foreach ($_POST as $key => $value) {
		$data[$key] = stripslashes($value);
	}

	// Set the PayPal account.
	$data['business'] = $paypalConfig['email'];

	// Set the PayPal return addresses.
	$data['return'] = stripslashes($paypalConfig['return_url']);
	$data['cancel_return'] = stripslashes($paypalConfig['cancel_url']);
	$data['notify_url'] = stripslashes($paypalConfig['notify_url']);

	// Set the details about the product being purchased, including the amount
	// and currency so that these aren't overridden by the form data.
	$data['item_name'] = $itemName;
	$data['amount'] = $itemAmount;
	$data['currency_code'] = 'GBP';

	// Add any custom fields for the query string.

	// Build the query string from the data.
	$queryString = http_build_query($data);
    
	// Redirect to paypal IPN
	header('location:' . $paypalUrl . '?' . $queryString);
	exit();

} else {
	// Handle the PayPal response.

	// Create a connection to the database.

	// Assign posted variables to local data array.
	$data = [
		'item_name' => $db->escapeString($fn->xss_clean($_POST['item_name'])),
		'item_number' => $db->escapeString($fn->xss_clean($_POST['item_number'])),
		'payment_status' => $db->escapeString($fn->xss_clean($_POST['payment_status'])),
		'payment_amount' => $db->escapeString($fn->xss_clean($_POST['mc_gross'])),
		'payment_currency' => $db->escapeString($fn->xss_clean($_POST['mc_currency'])),
		'txn_id' => $db->escapeString($fn->xss_clean($_POST['txn_id'])),
		'receiver_email' => $db->escapeString($fn->xss_clean($_POST['receiver_email'])),
		'payer_email' => $db->escapeString($fn->xss_clean($_POST['payer_email'])),
		'custom' => $db->escapeString($fn->xss_clean($_POST['custom'])),
	];

	// We need to verify the transaction comes from PayPal and check we've not
	// already processed the transaction before adding the payment to our
	// database.
	if ($fn->verifyTransaction($_POST) && $fn->checkTxnid($data['txn_id'])) {
	   // file_put_contents('data.txt', "Test");
		if ($fn->addPayment($data) !== false) {
			// Payment successfully added.
		}
	}else{
	    file_put_contents('data.txt', "Test fail");
	}
}