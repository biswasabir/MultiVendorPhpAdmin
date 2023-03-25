<?php
include_once('includes/functions.php');
include_once('includes/custom-functions.php');
$fn = new custom_functions;
?>
<?php

if (isset($_POST['btnAdd'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['locations']['create'] == 1) {
        $pincode = $db->escapeString($fn->xss_clean($_POST['pincode']));

        $error = array();

        if (empty($pincode)) {
            $error['pincode'] = " <span class='label label-danger'>Required!</span>";
        }

        $check = $fn->get_data(['pincode'], 'pincode=' . "'$pincode'", 'pincodes');
        if (!empty($check)) {
            $error['add_pincode'] = '<label class="alert alert-danger">Pincode Alreay exist</label>';
        } else {
            if (!empty($pincode)) {
                $sql_query = "INSERT INTO pincodes (pincode, status)	VALUES('$pincode',1)";
                $db->sql($sql_query);
                $result = $db->getResult();
                if (!empty($result)) {
                    $result = 0;
                } else {
                    $result = 1;
                }
                if ($result == 1) {
                    $error['add_pincode'] = "<section class='content-header'><span class='label label-success'>Pincode Added Successfully</span><h4><small><a  href='pincodes.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Pincodes</a></small></h4></section>";
                } else {
                    $error['add_pincode'] = " <span class='label label-danger'>Failed</span>";
                }
            }
        }
    } else {
        $error['add_pincode'] = "<section class='content-header'><span class='label label-danger'>You have no permission to create pincodes</span></section>";
    }
}
?>
<section class="content-header">
    <h1>Add Pincode <small><a href='pincodes.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back</a></small></h1>

    <?= isset($error['add_pincode']) ? $error['add_pincode'] : ''; ?>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
    <hr />
</section>
<section class="content">
    <div class="row">
        <div class="col-md-6">
            <?php if ($permissions['locations']['create'] == 0) { ?>
                <div class="alert alert-danger">You have no permission to create pincode</div>
            <?php } ?>
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Add Pincode</h3>
                </div><!-- /.box-header -->
                <!-- form start -->
                <form method="post" id="area_form" enctype="multipart/form-data">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="pincode">Pincode</label><?= isset($error['pincode']) ? $error['pincode'] : ''; ?>
                            <input type="text" class="form-control" name="pincode" id="pincode" required />
                        </div>

                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <input type="submit" class="btn-primary btn" value="Add" name="btnAdd" />&nbsp;
                        <input type="reset" class="btn-danger btn" value="Clear" />
                    </div>
                </form>
            </div><!-- /.box -->
        </div>
    </div>
</section>
<div class="separator"> </div>

<?php $db->disconnect(); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
<script>
    $('#area_form').validate({
        debug: false,
        rules: {
            pincode: "required"
        }
    });
</script>