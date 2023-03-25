<?php
include('../includes/crud.php');
$db = new Database();
$db->connect();
include_once('../includes/custom-functions.php');
$fun = new custom_functions;
include_once('../api-firebase/send-email.php');

if (isset($_POST['send_forgot_password_mail']) && $_POST['send_forgot_password_mail'] == 1) {
    $email = $db->escapeString($fun->xss_clean($_POST['email']));
    $seller = isset($_POST['is_seller']) && $_POST['is_seller'] == 1 ? 'seller' : '';
    if (!$fun->validate_email($email, $seller)) {
        echo "<div class='alert alert-danger'>Email doesn't exists.</div>";
        return false;
    } else {
        $is_seller = isset($_POST['is_seller']) && $_POST['is_seller'] == 1 ? 'seller/' : '';
        $temp = mt_rand(100000, 999999);
        $link = md5($temp);
        $full_link = DOMAIN_URL . $is_seller . 'reset-password.php?code=' . $link;
        $message = "Dear user your password reset link is <a href=" . $full_link . " target='_blank'>$full_link</a> click it to proceed further.<br><b>Thank You.</b>";
        if (send_email($email, 'Forgot Password', $message)) {
            $fun->update_forgot_password_code($email, $link, $seller);
            echo "<div class='alert alert-success'>Email Sent Successfully.</div>";
            return false;
        } else {
            echo "<div class='alert alert-danger'>Couldn't send mail.</div>";
        }
    }
}
