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

// create array variable to store previous data
$data = array();

$sql_query = "SELECT * FROM seller WHERE id =" . $ID;
$db->sql($sql_query);
$res = $db->getResult();
?>
<section class="content-header">
    <h1>
        Edit Seller<small><a href='sellers.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Sellers</a></small></h1>

    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
</section>
<section class="content">
    <!-- Main row -->

    <div class="row">
        <div class="col-md-12">
            <?php if ($permissions['sellers']['update'] == 1) { ?>

                <!-- general form elements -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit Seller</h3>
                    </div><!-- /.box-header -->
                    <!-- form start -->
                    <form id="edit_form" method="post" action="public/db-operation.php" enctype="multipart/form-data">
                        <div class="box-body">
                            <input type="hidden" id="update_seller" name="update_seller" required="" value="1" aria-required="true">
                            <input type="hidden" id="update_id" name="update_id" required value="<?= $ID; ?>">
                            <input type="hidden" id="hide_description" name="hide_description">
                            <input type="hidden" id="old_logo" name="old_logo" required value="<?= $res[0]['logo']; ?>">
                            <input type="hidden" id="old_national_identity_card" name="old_national_identity_card" required value="<?= $res[0]['national_identity_card']; ?>">
                            <input type="hidden" id="old_address_proof" name="old_address_proof" required value="<?= $res[0]['address_proof']; ?>">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Name</label>
                                        <input type="text" class="form-control" name="name" id="name" value="<?= $res[0]['name']; ?>" required>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Email</label>
                                        <input type="email" class="form-control" name="email" id="email" value="<?= $res[0]['email']; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Mobile</label>
                                        <input type="number" class="form-control" name="mobile" id="mobile" value="<?= $res[0]['mobile']; ?>" required readonly>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Store URL</label>
                                        <input type="text" class="form-control" name="store_url" id="store_url" value="<?= (!empty($res[0]['store_url'])) ? $res[0]['store_url'] : ""; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row d-none">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Old Password :</label><small>( Leave it blank for no change )</small>
                                        <input type="password" class="form-control" name="old_password" id="old_password" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Password</label><small>( Leave it blank for no change )</small>
                                        <input type="password" class="form-control" name="password" id="password">
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Confirm Password</label>
                                        <input type="password" class="form-control" name="confirm_password" id="confirm_password">
                                    </div>
                                </div>
                            </div>
                            <div class="row">

                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label for="exampleInputFile">Logo</label>
                                        <input type="file" name="store_logo" id="store_logo">
                                        <p class="help-block"><img src="<?php echo DOMAIN_URL . 'upload/seller/' . $res[0]['logo']; ?>" style="max-width:100%" /></p>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label for="exampleInputFile">National Identity Card</label>
                                        <input type="file" name="national_id_card" id="national_id_card">
                                        <p class="help-block"><img src="<?php echo DOMAIN_URL . 'upload/seller/' . $res[0]['national_identity_card']; ?>" style="max-width:100%" /></p>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label for="exampleInputFile">Address Proof</label>
                                        <input type="file" name="address_proof" id="address_proof">
                                        <p class="help-block"><img src="<?php echo DOMAIN_URL . 'upload/seller/' . $res[0]['address_proof']; ?>" style="max-width:100%" /></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Store Name</label>
                                        <input type="text" class="form-control" name="store_name" id="store_name" value="<?= $res[0]['store_name']; ?>" required>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Street</label>
                                        <input type="text" class="form-control" name="street" id="street" value="<?= (!empty($res[0]['street'])) ? $res[0]['street'] : ""; ?>">
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="pincode_id">Pincode</label>
                                        <?php $db->sql("SET NAMES 'utf8'");
                                        $sql = "SELECT * FROM pincodes ORDER BY id + 0 ASC";
                                        $db->sql($sql);
                                        $pincodes = $db->getResult();
                                        ?>
                                        <select id='pincode_id' name="pincode_id" class='form-control'>
                                            <option value=''>Select Pincode</option>
                                            <?php foreach ($pincodes as $row) { ?>
                                                <option value='<?= $row['id'] ?>' <?= ($res[0]['pincode_id'] == $row['id']) ? 'selected' : ''; ?>><?= $row['pincode'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">State</label>
                                        <input type="text" class="form-control" name="state" id="state" value="<?= (!empty($res[0]['state'])) ? $res[0]['state'] : ""; ?>">
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="pincode_id">City</label>
                                        <?php $db->sql("SET NAMES 'utf8'");
                                        $sql = "SELECT * FROM cities ORDER BY id + 0 ASC";
                                        $db->sql($sql);
                                        $cities = $db->getResult();
                                        ?>
                                        <select id='city_id' name="city_id" class='form-control'>
                                            <option value=''>Select City</option>
                                            <?php foreach ($cities as $row) { ?>
                                                <option value='<?= $row['id'] ?>' <?= ($res[0]['city_id'] == $row['id']) ? 'selected' : ''; ?>><?= $row['name'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">PAN Number</label>
                                        <input type="text" class="form-control" name="pan_number" value="<?= $res[0]['pan_number']; ?>" required>
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Bank's IFSC Code</label>
                                        <input type="text" class="form-control" name="ifsc_code" id="ifsc_code" value="<?= (!empty($res[0]['bank_ifsc_code'])) ? $res[0]['bank_ifsc_code'] : "";  ?>">
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Bank Name</label>
                                        <input type="text" class="form-control" name="bank_name" id="bank_name" value="<?= (!empty($res[0]['bank_name'])) ? $res[0]['bank_name'] : "";  ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Bank Account Name</label>
                                        <input type="text" class="form-control" name="account_name" id="account_name" value="<?= (!empty($res[0]['account_name'])) ? $res[0]['account_name'] : ""; ?>">
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Account Number</label>
                                        <input type="number" class="form-control" name="account_number" id="account_number" value="<?= (!empty($res[0]['account_number'])) ? $res[0]['account_number'] : ""; ?>">
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Commission (%) <small>[Will be used if category wise commission not set] <a href="#" data-toggle='modal' data-target='#howItWorksModal' title='How it works'>How seller commission works?</a></label></small>
                                        <input type="number" class="form-control" name="commission" id="commission" value="<?= $res[0]['commission']; ?>" required><br>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for='catogories_ids'>Category IDs <small>( Ex : 100,205, 360)</small></label>
                                        <select name='cat_ids[]' id='cat_ids' class='form-control' placeholder='Enter the category IDs you want to assign Seller' required multiple="multiple">
                                            <?php $sql = 'select id,name from `category`  order by id desc';
                                            $db->sql($sql);
                                            $result = $db->getResult();
                                            foreach ($result as $value) {
                                                $categories = explode(',', $res[0]['categories']);
                                                $selected = in_array($value['id'], $categories) ? 'selected' : '';
                                            ?>
                                                <option value='<?= $value['id'] ?>' <?= $selected ?>><?= $value['name'] ?></option>
                                            <?php } ?>

                                        </select>
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Tax Name</label>
                                        <input type="text" class="form-control" name="tax_name" value="<?= $res[0]['tax_name']; ?>" required>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Tax Number</label>
                                        <input type="text" class="form-control" name="tax_number" value="<?= $res[0]['tax_number']; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Latitude</label>
                                        <input type="number" class="form-control" name="latitude" id="latitude" value="<?= $res[0]['latitude']; ?>">
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-group">
                                        <label for="">Longitude</label>
                                        <input type="text" class="form-control" name="longitude" id="longitude" value="<?= $res[0]['longitude']; ?>">
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="form-group col-md-8">
                                    <div class="form-group">
                                        <label for="description">Store Description : </label><i id="address_note"></i>
                                        <textarea name="description123456" id="description123456" class="form-control addr_editor" rows="16"><?= $res[0]['store_description']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Require Product's Approval</label>
                                        <div id="status" class="btn-group">
                                            <label class="btn btn-default" data-toggle-class="btn-default" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="require_products_approval" value="1" <?= ($res[0]['require_products_approval'] == 1) ? 'checked' : ''; ?>> Yes
                                            </label>
                                            <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="require_products_approval" value="0" <?= ($res[0]['require_products_approval'] == 0) ? 'checked' : ''; ?>> No
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="row"> -->
                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">View Customer's Details? </label>
                                        <div id="customer_privacy" class="btn-group">
                                            <label class="btn btn-default" data-toggle-class="btn-default" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="customer_privacy" value="1" <?= ($res[0]['customer_privacy'] == 1) ? 'checked' : ''; ?>> Yes
                                            </label>
                                            <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="customer_privacy" value="0" <?= ($res[0]['customer_privacy'] == 0) ? 'checked' : ''; ?>> No
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">View Order's OTP? </label>
                                        <div id="view_order_otp" class="btn-group">
                                            <label class="btn btn-default" data-toggle-class="btn-default" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="view_order_otp" value="1" <?= ($res[0]['view_order_otp'] == 1) ? 'checked' : ''; ?>> Yes
                                            </label>
                                            <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="view_order_otp" value="0" <?= ($res[0]['view_order_otp'] == 0) ? 'checked' : ''; ?>> No
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Can assign delivery boy? </label>
                                        <div id="assign_delivery_boy" class="btn-group">
                                            <label class="btn btn-default" data-toggle-class="btn-default" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="assign_delivery_boy" value="1" <?= ($res[0]['assign_delivery_boy'] == 1) ? 'checked' : ''; ?>> Yes
                                            </label>
                                            <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="assign_delivery_boy" value="0" <?= ($res[0]['assign_delivery_boy'] == 0) ? 'checked' : ''; ?>> No
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-5">
                                    <div class="form-group">
                                        <label class="control-label">Status</label>
                                        <div id="status" class="btn-group">
                                            <label class="btn btn-default" data-toggle-class="btn-default" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="status" value="0" <?= ($res[0]['status'] == 0) ? 'checked' : ''; ?>> Deactive
                                            </label>
                                            <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="status" value="1" <?= ($res[0]['status'] == 1) ? 'checked' : ''; ?>> Approved
                                            </label>
                                            <label class="btn btn-danger" data-toggle-class="btn-danger" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="status" value="2" <?= ($res[0]['status'] == 2) ? 'checked' : ''; ?>> Not-Approved
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <!-- </div> -->

                                <!-- </div> -->

                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-primary" id="submit_btn">Update</button><br>
                                <div style="display:none;" id="result"></div>

                            </div>
                        </div><!-- /.box-body -->
                    </form>
                </div><!-- /.box -->
            <?php } else { ?>
                <div class="alert alert-danger">You have no permission to update sellers </div>
            <?php } ?>
        </div>
    </div>
    <div class="modal fade" id='howItWorksModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">How seller commission will get credited?</h4>
                    <hr>
                    <ol>
                        <li>
                            Cron job must be set (For once in a day) on your server for seller commission to be work.
                        </li>
                        <li>
                            Cron job will run every mid night at 12:00 AM.
                        </li>
                        <li>
                            Formula for seller commision is <b>Sub total (Excluding delivery charge) / 100 * seller commission percentage</b>
                        </li>
                        <li>
                            For example sub total is 1378 and seller commission is 20% then 1378 / 100 X 20 = 275.6 so 1378 - 275.6 = 1102.4 will get credited into seller's wallet</b>
                        </li>
                        <li>
                            If Order status is delivered then only seller will get commisison.
                        </li>
                        <li>
                            Ex - 1. Order placed on 11-Aug-21 and product return days are set to 0 so 11-Aug + 0 days = 11-Aug seller commission will get credited on 12-Aug-21 at 12:00 AM (Mid night)
                        </li>
                        <li>
                            Ex - 2. Order placed on 11-Aug-21 and product return days are set to 7 so 11-Aug + 7 days = 18-Aug seller commission will get credited on 19-Aug-21 at 12:00 AM (Mid night)
                        </li>
                        <li>
                            If seller commission doesn't works make sure cron job is set properly and it is working. If you don't know how to set cron job for once in a day please take help of server support or do search for it.
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="separator"> </div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
<script>
    $(document).ready(function() {
        ltr = '<svg width="20" height="20"><path d="M11 5h7a1 1 0 010 2h-1v11a1 1 0 01-2 0V7h-2v11a1 1 0 01-2 0v-6c-.5 0-1 0-1.4-.3A3.4 3.4 0 017.8 10a3.3 3.3 0 010-2.8 3.4 3.4 0 011.8-1.8L11 5zM4.4 16.2L6.2 15l-1.8-1.2a1 1 0 011.2-1.6l3 2a1 1 0 010 1.6l-3 2a1 1 0 11-1.2-1.6z" fill-rule="evenodd"></path></svg>';
        rtl = '<svg width="20" height="20"><path d="M8 5h8v2h-2v12h-2V7h-2v12H8v-7c-.5 0-1 0-1.4-.3A3.4 3.4 0 014.8 10a3.3 3.3 0 010-2.8 3.4 3.4 0 011.8-1.8L8 5zm12 11.2a1 1 0 11-1 1.6l-3-2a1 1 0 010-1.6l3-2a1 1 0 111 1.6L18.4 15l1.8 1.2z" fill-rule="evenodd"></path></svg>';
        html = '( Use ' + ltr + ' for LTR and use ' + rtl + ' for RTL )';
        $('#address_note').append(html);
    });
</script>
<script>
    $('#edit_form').validate({
        rules: {
            name: "required",
            mobile: "required",
            address: "required",
            confirm_password: {
                equalTo: "#password"
            }
        }
    });
    $('#cat_ids').select2({
        width: 'element',
        placeholder: 'type in category name to search',

    });
    $('#edit_form').on('submit', function(e) {
        e.preventDefault();
        var content = tinyMCE.activeEditor.getContent();
        $('#hide_description').val(content);
        var formData = new FormData(this);
        if ($("#edit_form").validate().form()) {
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                beforeSend: function() {
                    $('#submit_btn').html('Please wait..');
                },
                cache: false,
                contentType: false,
                processData: false,
                success: function(result) {
                    $('#result').html(result);
                    $('#result').show().delay(6000).fadeOut();
                    $('#cat_ids').select2({
                        placeholder: "type in category name to search"
                    });
                    $('#submit_btn').html('Update');
                    location.reload(true);
                }
            });
        }
    });
</script>
<?php $db->disconnect(); ?>