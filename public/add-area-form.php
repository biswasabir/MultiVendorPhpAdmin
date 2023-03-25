<?php
include_once('includes/functions.php');
include_once('includes/custom-functions.php');
$fn = new custom_functions;
?>
<?php
$sql_query = "SELECT id, pincode FROM pincodes ORDER BY id ASC";
$db->sql($sql_query);
$res_city = $db->getResult();
$sql_query = "SELECT id, name FROM cities ORDER BY id ASC";
$db->sql($sql_query);
$res_cities = $db->getResult();
if (isset($_POST['btnAdd'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['locations']['create'] == 1) {
        $area_name = $db->escapeString($fn->xss_clean($_POST['area_name']));
        $pincode_ID = $db->escapeString($fn->xss_clean($_POST['pincode_ID']));
        $city_id = $db->escapeString($fn->xss_clean($_POST['city_id']));
        $delivery_charges = $db->escapeString($fn->xss_clean($_POST['delivery_charges']));
        $minimum_free_delivery_order_amount = $db->escapeString($fn->xss_clean($_POST['minimum_free_delivery_order_amount']));
        $sql_query = "SELECT * FROM area WHERE pincode_ID=" . $pincode_ID;
        $db->sql($sql_query);
        $res_area = $db->getResult();
        $TOTAL = $db->numRows($res_area);
        $error = array();

        if (empty($area_name)) {
            $error['area_name'] = " <span class='label label-danger'>Required!</span>";
        }
        if (empty($delivery_charges)) {
            $error['delivery_charges'] = " <span class='label label-danger'>Required!</span>";
        }
        if (empty($minimum_free_delivery_order_amount)) {
            $error['minimum_free_delivery_order_amount'] = " <span class='label label-danger'>Required!</span>";
        }

        $check = $fn->get_data(['name'], "city_id=$city_id and pincode_id=$pincode_ID and name='$area_name'", 'area');
        if (!empty($check)) {
            $error['add_area'] = '<label class="alert alert-danger">Area Alreay exist</label>';
        } else {
            if ($TOTAL == 0) {

                if (!empty($area_name) && !empty($pincode_ID) && !empty($city_id) && !empty($delivery_charges) && !empty($minimum_free_delivery_order_amount)) {
                    $sql_query = "INSERT INTO area (name, pincode_id,city_id,delivery_charges,minimum_free_delivery_order_amount)	VALUES('$area_name', '$pincode_ID','$city_id',$delivery_charges,$minimum_free_delivery_order_amount)";
                    $db->sql($sql_query);
                    $result = $db->getResult();
                    if (!empty($result)) {
                        $result = 0;
                    } else {
                        $result = 1;
                    }
                    if ($result == 1) {
                        $error['add_area'] = "<section class='content-header'><span class='label label-success'>Area Added Successfully</span><h4><small><a  href='areas.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Areas</a></small></h4></section>";
                    } else {
                        $error['add_area'] = " <span class='label label-danger'>Failed</span>";
                    }
                }
            } else {
                if (!empty($area_name) && !empty($pincode_ID) && !empty($city_id) && !empty($delivery_charges) && !empty($minimum_free_delivery_order_amount)) {
                    $sql_query = "INSERT INTO area (name, pincode_id,city_id,delivery_charges,minimum_free_delivery_order_amount)	VALUES('$area_name', '$pincode_ID','$city_id',$delivery_charges,$minimum_free_delivery_order_amount)";
                    $db->sql($sql_query);
                    $result = $db->getResult();
                    if (!empty($result)) {
                        $result = 0;
                    } else {
                        $result = 1;
                    }

                    if ($result == 1) {
                        $error['add_area'] = "<section class='content-header'><span class='label label-success'>Area Added Successfully</span><h4><small><a  href='areas.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Areas</a></small></h4></section>";
                    } else {
                        $error['add_area'] = " <span class='label label-danger'>Failed</span>";
                    }
                }
            }
        }
    } else {
        $error['add_area'] = "<section class='content-header'><span class='label label-danger'>You have no permission to create area</span></section>";
    }
}
?>
<section class="content-header">
    <h1>Add Area <small><a href='areas.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back</a></small></h1>

    <?php echo isset($error['add_area']) ? $error['add_area'] : ''; ?>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
    <hr />
</section>
<section class="content">
    <div class="row">
        <div class="col-md-6">
            <?php if ($permissions['locations']['create'] == 0) { ?>
                <div class="alert alert-danger">You have no permission to create area</div>
            <?php } ?>
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Add Area</h3>
                </div><!-- /.box-header -->
                <!-- form start -->
                <form method="post" id="area_form" enctype="multipart/form-data">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Pincode :</label><?php echo isset($error['pincode_ID']) ? $error['pincode_ID'] : ''; ?>
                            <select name="pincode_ID" id="pincode_ID" class="form-control" required>
                                <option value="">Select Your Pincode</option>
                                <?php
                                if ($permissions['locations']['read'] == 1) {
                                    foreach ($res_city as $row) { ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['pincode']; ?></option>
                                <?php }
                                } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">City :</label><?php echo isset($error['city_id']) ? $error['city_id'] : ''; ?>
                            <select name="city_id" id="city_id" class="form-control" required>
                                <option value="">Select Your City</option>
                                <?php
                                if ($permissions['locations']['read'] == 1) {
                                    foreach ($res_cities as $row) { ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                                <?php }
                                } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Area Name</label><?php echo isset($error['area_name']) ? $error['area_name'] : ''; ?>
                            <input type="text" class="form-control" name="area_name" id="area_name" required />
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Minimum Free Delivery Order Amount</label><?php echo isset($error['minimum_free_delivery_order_amount']) ? $error['minimum_free_delivery_order_amount'] : ''; ?>
                            <input type="number" step="any" min="0" class="form-control" name="minimum_free_delivery_order_amount" id="minimum_free_delivery_order_amount" required />
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Delivery Charges</label><?php echo isset($error['delivery_charges']) ? $error['delivery_charges'] : ''; ?>
                            <input type="number" step="any" min="0" class="form-control" name="delivery_charges" id="delivery_charges" required />
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
            pincode_ID: "required",
            city_id: "required",
            area_name: "required",
            minimum_free_delivery_order_amount: "required",
            delivery_charges: "required"
        }
    });
</script>