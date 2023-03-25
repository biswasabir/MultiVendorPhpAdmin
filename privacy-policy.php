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
include "header.php"; ?>
<html>

<head>
    <title>Privacy-Policy | <?= $settings['app_name'] ?> - Dashboard</title>
    <style>
        .asterik {
            font-size: 20px;
            line-height: 0px;
            vertical-align: middle;
        }

        .tox .tox-menubar {
            background-color: #e7e8e7;
            display: flex;
            flex: 0 0 auto;
            flex-shrink: 0;
            flex-wrap: wrap;
            padding: 0 4px 0 4px;
        }

        .tox .tox-notification--warn,
        .tox .tox-notification--warning {
            background-color: #fffaea;
            border-color: #ffe89d;
            color: #222f3e;
            display: none;
        }
    </style>
</head>
</body>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <?php
    include_once('includes/custom-functions.php');
    $fn = new custom_functions;
    $sql = "SELECT * FROM settings";
    $db->sql($sql);
    $res = $db->getResult();
    $message = '';
    if (isset($_POST['btn_update'])) {
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
            return false;
        }
        if ($permissions['settings']['update'] == 1) {
            if (!empty($_POST['privacy_policy']) && !empty($_POST['terms_conditions'])) {

                $privacy_policy = $db->escapeString($fn->xss_clean($_POST['privacy_policy']));
                $terms_conditions = $db->escapeString($fn->xss_clean($_POST['terms_conditions']));
                //Update privacy_policy - id = 9
                $sql = "UPDATE `settings` SET `value`='" . $privacy_policy . "' WHERE `variable` = 'privacy_policy'";
                $db->sql($sql);
                $sql = "UPDATE `settings` SET `value`='" . $terms_conditions . "' WHERE `variable` = 'terms_conditions'";
                $db->sql($sql);
                $sql = "SELECT * FROM settings";
                $db->sql($sql);
                $res = $db->getResult();
                $message .= "<div class='alert alert-success'> Information Updated Successfully!</div>";
            }
        } else {
            $message .= "<label class='alert alert-danger'>You have no permission to update settings</label>";
        }
    }
    ?>
    <section class="content-header">
        <h1>Privacy Policy And Terms & Conditions</h1>
        <h4><?= $message ?></h4>
        <ol class="breadcrumb">
            <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
        </ol>
        <hr />
    </section>
    <section class="content">
        <div class="col-md-offset-1 col-md-6">
        </div>
        <div class="col-md-4" style="margin-bottom:10px;">
            <?php if ($permissions['settings']['read'] == 1) { ?>
                <a href='play-store-privacy-policy.php' target='_blank' class='btn btn-primary btn-sm'>Privacy Policy Page for Play Store</a>
            <?php } ?>
        </div>
        <div class="row">

            <div class="col-md-12">
                <?php if ($permissions['settings']['read'] == 1) {
                    if ($permissions['settings']['update'] == 0) { ?>
                        <div class="alert alert-danger">You have no permission to update settings</div>
                    <?php } ?>
                    <!-- general form elements -->
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Update Privacy Policy</h3>
                        </div>
                        <!-- /.box-header -->
                        <!-- form start -->
                        <form method="post" enctype="multipart/form-data">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="app_name">Privacy Policy: <i class="address_note"></i> </label>
                                    <textarea rows="10" cols="10" class="form-control addr_editor" name="privacy_policy" id="privacy_policy" required><?= $res[1]['value'] ?></textarea>
                                </div>
                                <div class="box-header with-border">
                                    <h3 class="box-title">Update Terms Conditions</h3>
                                </div>
                                <div class="box-body">
                                    <div class="form-group">
                                        <label for="app_name">Terms & Conditions: <i class="address_note"></i> </label>
                                        <textarea rows="10" cols="10" class="form-control addr_editor" name="terms_conditions" id="terms_conditions" required><?= $res[2]['value'] ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <!-- /.box-body -->
                            <div class="box-footer">
                                <input type="submit" class="btn-primary btn" value="Update" name="btn_update" />
                            </div>
                        </form>
                    <?php } else { ?>
                        <div class="alert alert-danger">You have no permission to view settings</div>
                    <?php } ?>
                    </div>
                    <!-- /.box -->
            </div>
        </div>
</div>
</section>
<div class="separator"> </div>
</div><!-- /.content-wrapper -->
</body>

</html>
<?php include "footer.php"; ?>
<script>
    $(document).ready(function() {
        ltr = '<svg width="20" height="20"><path d="M11 5h7a1 1 0 010 2h-1v11a1 1 0 01-2 0V7h-2v11a1 1 0 01-2 0v-6c-.5 0-1 0-1.4-.3A3.4 3.4 0 017.8 10a3.3 3.3 0 010-2.8 3.4 3.4 0 011.8-1.8L11 5zM4.4 16.2L6.2 15l-1.8-1.2a1 1 0 011.2-1.6l3 2a1 1 0 010 1.6l-3 2a1 1 0 11-1.2-1.6z" fill-rule="evenodd"></path></svg>';
        rtl = '<svg width="20" height="20"><path d="M8 5h8v2h-2v12h-2V7h-2v12H8v-7c-.5 0-1 0-1.4-.3A3.4 3.4 0 014.8 10a3.3 3.3 0 010-2.8 3.4 3.4 0 011.8-1.8L8 5zm12 11.2a1 1 0 11-1 1.6l-3-2a1 1 0 010-1.6l3-2a1 1 0 111 1.6L18.4 15l1.8 1.2z" fill-rule="evenodd"></path></svg>';
        html = '( Use ' + ltr + ' for LTR and use ' + rtl + ' for RTL )';
        $('.address_note').append(html);
    });
</script>