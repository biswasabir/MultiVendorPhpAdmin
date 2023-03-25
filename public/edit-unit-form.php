<?php
include_once('includes/functions.php');
include_once('includes/custom-functions.php');
$fn = new custom_functions;
?>
<?php
// $ID = (isset($_GET['id'])) ? $db->escapeString($fn->xss_clean($_GET['id'])) : "";
if (isset($_GET['id'])) {
    $ID = $db->escapeString($fn->xss_clean($_GET['id']));
} else {
    // $ID = "";
    return false;
    exit(0);
}
$category_data = array();

if (isset($_POST['btnEdit'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['categories']['update'] == 1) {
        $name = $db->escapeString($fn->xss_clean($_POST['name']));
        $short_code = $db->escapeString($fn->xss_clean($_POST['short_code']));
        $parent_id = $db->escapeString($fn->xss_clean($_POST['parent_id']));
        $conversion = ($_POST['conversion'] != "") ? $db->escapeString($fn->xss_clean($_POST['conversion'])) : "";

        $error = array();

        if (empty($name)) {
            $error['name'] = " <span class='label label-danger'>Required!</span>";
        }
        if (empty($short_code)) {
            $error['subtitle'] = " <span class='label label-danger'>Required!</span>";
        }
        

        if (!empty($name) && !empty($short_code)) {
            $sql_query = "UPDATE unit SET 	name = ' $name',  short_code = '$short_code',parent_id = '$parent_id' , conversion = '$conversion' WHERE id =  $ID";
            if ($db->sql($sql_query)) {
                $update_result = $db->getResult();
            }
            if (!empty($update_result)) {
                $update_result = 0;
            } else {
                $update_result = 1;
            }

            // check update result
            if ($update_result == 1) {
                $error['update_unit'] = " <section class='content-header'><span class='label label-success'>Unit updated Successfully</span></section>";
            } else {
                $error['update_unit'] = " <span class='label label-danger'>Failed update unit</span>";
            }
        }
    } else {
        $error['check_permission'] = " <section class='content-header'><span class='label label-danger'>You have no permission to update unit</span></section>";
    }
}
// create array variable to store previous data
$data = array();

$sql_query = "SELECT * FROM unit WHERE id =" . $ID;
$db->sql($sql_query);
$res = $db->getResult();

if (isset($_POST['btnCancel'])) { ?>
    <script>
        window.location.href = "units.php";
    </script>
<?php } ?>
<section class="content-header">
    <h1>
        Edit Unit<small><a href='units.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Units</a></small></h1>
    <small><?php echo isset($error['update_unit']) ? $error['update_unit'] : ''; ?></small>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
</section>
<section class="content">
    <!-- Main row -->

    <div class="row">
        <div class="col-md-6">
            <?php if ($permissions['categories']['update'] == 0) { ?>
                <div class="alert alert-danger topmargin-sm">You have no permission to update Units.</div>
            <?php } ?>
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Unit</h3>
                </div><!-- /.box-header -->
                <form id="edit_unit_form" method="post" enctype="multipart/form-data">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Unit Name</label><?php echo isset($error['name']) ? $error['name'] : ''; ?>
                            <input type="text" class="form-control" name="name" value="<?php echo $res[0]['name']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Short Code</label><?php echo isset($error['short_code']) ? $error['short_code'] : ''; ?>
                            <input type="text" class="form-control" name="short_code" value="<?php echo $res[0]['short_code']; ?>">
                        </div>
                        <div class="form-group">
                        
                            <label for="exampleInputEmail1">Parent Id</label>
                            <?php $sql = "Select id,name from unit";
                           $db->sql($sql);
                           $res_query = $db->getResult();
                            ?>
                            
							<select class="form-control" id="parent_id" name="parent_id" >
                            <option value=""></option>
								<?php foreach ($res_query as $row) {
									echo "<option value=" . $row['id'];
									if ($row['id'] == $res[0]['parent_id']) {
										echo " selected";
									}
									echo ">" . $row['name'] . "</option>";
								} ?>
							</select>
							<!--  -->
                        <div class="form-group">
                            <label for="exampleInputEmail1">Conversion</label>
                            <input type="number" class="form-control" name="conversion" value="<?php echo $res[0]['conversion']; ?>">
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" name="btnEdit">Update</button>
                        <button type="submit" class="btn btn-danger" name="btnCancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<div class="separator"> </div>
<?php $db->disconnect(); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
<script>
    $('#edit_unit_form').validate({
        rules: {
            name: "required",
            short_code: "required",
        }
    });
</script>