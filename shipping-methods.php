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

$_SESSION['shipping_type'] = ($function->get_settings('local_shipping') == 1) ? 'local' : 'standard';

// $store_settings = $function->get_settings('local_shipping', true);
include "header.php"; ?>
<html>

<head>
    <title>Shipping Methods | <?= $settings['app_name'] ?> - Dashboard</title>
</head>
<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked+.slider {
        background-color: #2196F3;
    }

    input:focus+.slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>
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
                                    <input type="hidden" id="ccc" name="shiprocket_new" required="" value="1" aria-required="true">
                                    <div class="row local">
                                        <div class="form-group col-md-12">
                                            <label for="">Enable Local Shipping <small>( Use Local Delivery Boy For Shipping)</small></label><br>
                                            <label class="switch">
                                                <input type="checkbox" id="local_shipping" class="local_shippment" <?= isset($local_shipping) && $local_shipping == '1' ? "checked" : "" ?>>
                                                <span class="slider round"></span>
                                                <input type="hidden" id="local_shipping_id" data-shipping-type="standard" name="local_shipping" value="<?= isset($local_shipping) && $local_shipping == '1' ? $local_shipping : 0; ?>">
                                            </label>
                                            <!-- <input type="checkbox" id="local_shipping" data-shipping-type="local" class="js-switch local_method" <?= isset($local_shipping) && $local_shipping == '1' ? "checked" : "" ?>> -->
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="">Standard delivery method (Shiprocket) <small>( Enable/Disable ) <a href="https://app.shiprocket.in/api-user" target="_blank">Click here</a> to get credentials. <a href="https://www.shiprocket.in/" target="_blank">What is shiprocket?</a></small></label><br>
                                            <label class="switch">
                                                <input type="checkbox" class="ship_method_standard" id="standard_shipping" <?= isset($shiprocket['shiprocket']) && $shiprocket['shiprocket'] == '1' ? "checked" : "" ?>>
                                                <span class="slider round"></span>
                                                <input type="hidden" id="shiprocket" name="shiprocket" value="<?= isset($shiprocket['shiprocket']) && $shiprocket['shiprocket'] == '1' ? $shiprocket['shiprocket'] : 0; ?>">
                                            </label>
                                            <!-- <input type="checkbox" id="shiprocket_btn" class="js-switch ship_method_standard" <?= isset($shiprocket['shiprocket']) && $shiprocket['shiprocket'] == '1' ? "checked" : "" ?>> -->
                                        </div>

                                        <div class="shiprocket">

                                            <?php $dnone = isset($store_settings['shiprocket']) && $shiprocket['shiprocket'] == '1' ? '' : 'd-none' ?>
                                            <div class="form-group col-md-4">
                                                <label for="">Email</label>
                                                <input type="text" class="form-control shiprocket_email" name="shiprocket_email" id="shiprocket_email" value="<?= $shiprocket['shiprocket_email'] ?>" placeholder='Shiprocket account email' />
                                            </div>
                                            <div class="form-group col-md-4">

                                                <label for="">Password</label>
                                                <div class="input-group">
                                                    <input type="password" id="shiprocket_password" name="shiprocket_password" value="<?= $shiprocket['shiprocket_password'] ?>" placeholder='Shiprocket account password' class="form-control shiprocket_password" aria-describedby="basic-addon2">
                                                    <span class="input-group-addon" id="basic-addon2"><a href="#/" style="color:black" id="psw"><i id="eye_icon" class="fa fa-eye"></i></a></span>
                                                </div>
                                            </div>


                                        </div>

                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="">Shiprocket Webhoook Url</label>
                                            <input type="text" class="form-control shiprocket_email" disabled name="shiprocket_email" id="shiprocket_email" value="<?= DOMAIN_URL . 'webhook.php' ?>" placeholder='Shiprocket Webhook url' />
                                        </div>
                                        <div class="form-group col-md-4">

                                            <label for="">Shiprocket webhook token</label>
                                            <div class="input-group">
                                                <input type="text" disabled id="shiprocket_token" name="" value="<?= $shiprocket['webhook_token'] ?>" class="form-control shiprocket_token" placeholder="Generating new token...." aria-describedby="basic-addon2">
                                                <input type="hidden" id="shiprocket_token_hidden" name="webhook_token" value="<?= $shiprocket['webhook_token'] ?>" class="form-control shiprocket_token" placeholder="Generating new token...." aria-describedby="basic-addon2">
                                                <span class="input-group-addon" id="basic-addon2"><a href="#/" style="color:black" class="generate_token" title="Generate new token"><i id="eye_icon" class="fa fa-refresh"></i></a></span>
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
                    <!-- /.box -->
                </div>
            </div>
        </section>


        <div class="modal fade bd-example-modal-lg" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog " role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">What is Shipping methods</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <b>Note:</b> In shipping methods Admin can select one shipping method at a time.

                        <br>
                        <br>
                        <b>Available Shipping Methods:</b><br>
                        <br>
                        <b>1.Local Shipping:</b>in local shipping admin can use local delivery boys for delivered orders to customers .

                        <br><br>
                        <b>2.Standard Shipping Methods:</b> in standard shipping method admin can use other corrirer servic like shiporkcet for delivered orders to customers.
                        <br><br>
                        <b>Availabl Standard shipping:</b>
                        <br><br>
                        <b>1 Shiprocket:</b> Shiprocket, a product of Delhi based BigFoot Retail solution, is India's first automated shipping software that aims reduce ecommerce shipping to its bare bones. ... You can print bulk shipping labels and ship your products to in and around the world using a single platform.<a href="https://www.shiprocket.in/" target="blank"> Know more about shiprocket</a>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
    $('.generate_token').on('click', function() {
        $(this).html('<i class="fa fa-refresh fa-spin"></i>');
        $('#shiprocket_token').val('Generating...')
        $('#btn_update').attr('disabled', true)
        setTimeout(function() {
            $('#shiprocket_token').val(Math.random().toString(36).substr(2) + '-' + Math.random().toString(36).substr(2) + '-' + Math.random().toString(36).substr(2))
            $('#shiprocket_token_hidden').val($('#shiprocket_token').val())
            $(".generate_token").html('<i class="fa fa-refresh"></i>');
            $('#btn_update').attr('disabled', false)
        }, 2000);
    })
    $('.local_shippment').on('change', function() {
        if ($(this).is(':checked')) {
            $('.ship_method_standard').removeAttr('checked');
            $('.ship_method_standard').attr('disabled', true);
            $('#local_shipping_id').val(1)
            $('#shiprocket').val(0)
        } else {
            // $('.ship_method_standard').removeAttr('checked');
            $('#local_shipping_id').val(0)
            $('#shiprocket').val(1)
            $('.ship_method_standard').attr('disabled', false);
            // $('./ship_method_standard').attr('disabled', false);
        }
    });
    $('.ship_method_standard').on('change', function() {
        if ($(this).is(':checked')) {
            $('.local_shippment').removeAttr('checked');
            $('.local_shippment').attr('disabled', true);
            $('#shiprocket').val(1)
            $('#local_shipping_id').val(0)
        } else {
            // $('.ship_method_standard').removeAttr('checked');
            $('.local_shippment').attr('disabled', false);
            $('#shiprocket').val(0)
            $('#local_shipping_id').val(1)
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
        if (!$('#local_shipping').is(":checked") && !$('#standard_shipping').is(":checked")) {
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

                    $('#btn_update').html('save')
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