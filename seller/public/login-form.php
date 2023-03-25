<?php
if (isset($_SESSION['seller_id']) && isset($_SESSION['seller_name'])) {
    header("location:home.php");
}

if (isset($_POST['btnLogin'])) {

    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $password = $db->escapeString($fn->xss_clean($_POST['password']));

    $currentTime = time() + 25200;
    $expired = 3600;

    $error = array();

    if (empty($mobile)) {
        $error['mobile'] = " <span class='label label-danger'>Mobile should be filled!</span>";
    }

    if (empty($password)) {
        $error['password'] = " <span class='label label-danger'>Password should be filled!</span>";
    }

    if (!empty($mobile) && !empty($password)) {
        $password = md5($password);

        $sql_query = "SELECT * FROM seller WHERE mobile = '" . $mobile . "' AND password = '" . $password . "'";

        $db->sql($sql_query);

        $res = $db->getResult();
        $num = $db->numRows($res);

        if ($num == 1) {
            $sql = "SELECT status FROM seller WHERE mobile=" . $mobile;
            $db->sql($sql);
            $result = $db->getResult();

            // approoved: 1 | not-approoved: 2 | deactive:0 |  removed :7
            if ($result[0]['status'] == 7) {
                $error['failed_status'] = "<span class='btn btn-danger'>It seems your acount was removed by super admin please contact him to restore the account!</span>";
            } else if ($result[0]['status'] == 2) {
                $error['failed_status'] = "<span class='btn btn-danger'>Your account is not approved by Super Admin. Please wait for approval!</span>";
            } else {
                $_SESSION['seller_name'] = $res[0]['name'];
                $_SESSION['seller_id'] = $res[0]['id'];
                $_SESSION['timeout'] = $currentTime + $expired;
                header("location: home.php");
            }
        } else {
            $error['failed'] = "<span class='btn btn-danger'>Invalid Mobile or Password!</span>";
        }
    }
}
?>
<div class="col-md-4 col-md-offset-4 " style="margin-top:80px;">
    <div class='row'>
        <div class="col-md-12 text-center">
            <img src="<?= '../dist/img/' . $logo; ?>" height="110">
            <h3>Seller Dashboard</h3>
        </div>
        <div class="box box-info col-md-12">
            <div class="box-header with-border">
                <h3 class="box-title">Seller Login</h3>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="box-body">
                    <div class="form-group">
                        <label for="exampleInputEmail1">Mobile :</label><?php echo isset($error['mobile']) ? $error['mobile'] : ''; ?>
                        <input type="text" name="mobile" class="form-control" value="">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Password :</label><?php echo isset($error['password']) ? $error['password'] : ''; ?>
                        <input type="password" class="form-control" name="password" value=""><br>
                    </div>
                    <center><?php echo isset($error['failed']) ? $error['failed'] : ''; ?></center>
                    <center><?php echo isset($error['failed_status']) ? $error['failed_status'] : ''; ?></center>
                    <div class="box-footer">
                        <button type="submit" name="btnLogin" class="btn btn-info pull-left">Login</button>
                    </div>
                    <div class="box-footer">
                        <a href="sign-up.php" class="btn pull-left">Create Seller Account?</a>
                        <a href="forgot-password.php" class="btn pull-right">Forgot password?</a>

                    </div>
            </form>
        </div>
    </div>
</div>
</div>