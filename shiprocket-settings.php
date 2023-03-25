<?php

session_start();
include_once('includes/custom-functions.php');
$function = new custom_functions;
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
$shiprocket = $function->get_settings('shiprocket', true);
$local_shipping = $function->get_settings('local_shipping', true);



// $store_settings = $function->get_settings('local_shipping', true);
include "header.php"; ?>
<html>

<head>
    <title>Shipping Methods | <?= $settings['app_name'] ?> - Dashboard</title>
</head>
</body>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">

        <h2>Shipping Methods</h2>
        <ol class="breadcrumb">
            <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
        </ol>
        <hr />
    </section>
    <?php if ($permissions['settings']['read'] == 1) { ?>
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <!-- general form elements -->
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Shipping Methods <small><a href="" data-toggle="modal" data-target="#exampleModal">What is shipping method?</a></small></h3>
                        </div>
                        </script>
                        <!-- /.box-header -->
                        <!-- form start -->
                        <div class="box-body">
                            <div class="col-md-12">
                                <form method="post" id="shiprocket_settings_form">
                                    <input type="hidden" id="ccc" name="shiprocket_settings" required="" value="1" aria-required="true">
                                    <div class="row local">
                                        <div class="form-group col-md-12">
                                            <label for="">Enable Local Shipping <small>( Use Local Delivery Boy For Shipping)</small></label><br>
                                            <input type="checkbox" id="local_shipping" data-shipping-type="local" class="js-switch ship_method" <?= isset($local_shipping) && $local_shipping == '1' ? "checked" : "" ?>>
                                            <input type="hidden" id="local_shipping_id" data-shipping-type="standard" name="local_shipping" value="<?= isset($local_shipping) && $local_shipping == '1' ? $local_shipping : 0; ?>">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="shiprocket">
                                            <div class="form-group col-md-12">
                                                <label for="">Standard delivery method (Shiprocket) <small>( Enable/Disable ) <a href="https://app.shiprocket.in/api-user" target="_blank">Click here</a> to get credentials. <a href="https://www.shiprocket.in/" target="_blank">What is shiprocket?</a></small></label><br>
                                                <input type="checkbox" id="shiprocket_btn" class="js-switch ship_method" <?= isset($shiprocket['shiprocket']) && $shiprocket['shiprocket'] == '1' ? "checked" : "" ?>>
                                                <input type="hidden" id="shiprocket" name="shiprocket" value="<?= isset($shiprocket['shiprocket']) && $shiprocket['shiprocket'] == '1' ? $shiprocket['shiprocket'] : 0; ?>">
                                            </div>
                                            <?php $dnone = isset($store_settings['shiprocket']) && $shiprocket['shiprocket'] == '1' ? '' : 'd-none' ?>
                                            <div class="form-group col-md-3">
                                                <label for="">Email</label>
                                                <input type="text" class="form-control shiprocket_email" name="shiprocket_email" id="shiprocket_email" value="<?= $shiprocket['shiprocket_email'] ?>" placeholder='Shiprocket account email' />
                                            </div>
                                            <div class="form-group col-md-3">

                                                <label for="">Password</label>
                                                <div class="input-group">
                                                    <input type="password" id="shiprocket_password" name="shiprocket_password" value="<?= $shiprocket['shiprocket_password'] ?>" placeholder='Shiprocket account password' class="form-control shiprocket_password" placeholder="Recipient's username" aria-describedby="basic-addon2">
                                                    <span class="input-group-addon" id="basic-addon2"><a href="#/" style="color:black" id="psw"><i id="eye_icon" class="fa fa-eye"></i></a></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-12">

                                            <button type="submit" id="btn_update" class="btn-primary btn" value="" name="btn_update">Save</button>
                                        </div>
                                        <div class="form-group col-md-12">
                                            <div id="result"></div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- /.box -->
                </div>
            </div>
        </section>


        <div class="modal fade bd-example-modal-lg" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog " role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        ...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="alert alert-danger">You have no permission to view settings</div>
    <?php } ?>
    <div class="separator"> </div>
</div><!-- /.content-wrapper -->
</body>



<script>
    var current_type = <?= $shippint_type == 1 ? "'local'" : "'standard'" ?>;
    if (current_type == 'local') {
        $('.shiprocket').hide();
        $('#shiprocket').val('0');
    } else {
        $('.local').hide();
        $('#local').val('0');
    }

    $('.ship_method').on('change', function() {
        if ($(this).is(':checked')) {
            if ($(this).data('shipping-type') == 'local') {
                $('.shiprocket').hide();
                $('#shiprocket').val('0');
            } else {
                $('.local').hide();
                $('.delivery_boy').hide();
                $('#local').val('0');
            }
        } else {
            if ($(this).data('shipping-type') == 'local') {
                $('.shiprocket').show();
                $('#shiprocket').val('0');


            } else {
                $('.local').show();
                $('.delivery_boy').show();
                $('#local').val('0');
            }
        }
    });





    $('#psw').on('click', function() {
        if ($('#shiprocket_password').is(':password')) {
            $('#shiprocket_password').attr('type', 'text');
            $('#eye_icon').addClass('fa-eye-slash');
            $('#eye_icon').removeClass('fa-eye');

        } else {
            $('#shiprocket_password').attr('type', 'password');
            $('#eye_icon').removeClass('fa-eye-slash');
            $('#eye_icon').addClass('fa-eye');
        }
    });
</script>
<script>
    $('#shiprocket_settings_form').on('submit', function(e) {
        if (!$('#local_shipping').is(":checked") && !$('#shiprocket_btn').is(":checked")) {
            alert("please select one method");
        } else {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: 'public/db-operation.php',
                data: formData,
                beforeSend: function() {
                    $('#btn_update').html('<i class="fa fa-refresh fa-spin fa-2x fa-fw"></i>')
                    $('#btn_update').attr('disabled', true);
                },
                cache: false,
                contentType: false,
                processData: false,
                success: function(result) {
                    $('#btn_update').html('save8')
                    $('#result').html(result);
                    $('#result').show().delay(5000).fadeOut();
                    $('#btn_update').val('Save').attr('disabled', false);
                }
            });
        }
    });
</script>

</html>
<?php include "footer.php"; ?>