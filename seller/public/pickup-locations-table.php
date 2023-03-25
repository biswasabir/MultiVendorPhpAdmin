<?php
include_once('../includes/functions.php');
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
$seller_id = $_SESSION['seller_id'];

$slq = 'SELECT phone from pickup_locations where verified=1 And seller_id='.$_SESSION['seller_id'];
$db->sql($slq);
$res = $db->getResult();
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.16.0/jquery.validate.min.js"></script>
<section class="content-header">
    <h1>Add pickup location</h1>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
    <hr />
</section>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Add pickup location</h3>
                </div><!-- /.box-header -->
                <!-- form start -->
                <form action="../api-firebase/order-process.php" method="post" id="pickup_location_form" class="pickup_locations">
                    <input type="hidden" name="add_pickup_location" value="1">
                    <input type="hidden" id="seller_id" name="seller_id" value="<?= $seller_id ?>">
                    <input type="hidden" name="accesskey" value="90336">
                    <div class="box-body">
                        <div class="col-md-3 form-group">
                            <label for="">Pickup location <small>(Ex. Home)</small></label><i class="text-danger asterik">*</i>
                            <input type="text" id="pickup_location" class="form-control" name="pickup_location" placeholder="Nickname of the new pickup location. Max 36 characters">
                            <label for="" id="location_result" class="text-danger hide">Pickup Locations Already Taken </label>
                            <div class="hide" id='success'>
                                <label for="" id="pSlug" class="text-success"></label> <i class="fa fa-check-circle text-success"></i>
                            </div>

                        </div>
                        <div class="col-md-3 form-group">
                            <label for="">Name <small>(Ex. Deadpool)</small></label><i class="text-danger asterik">*</i>
                            <input type="text" class="form-control" name="name" id="name" placeholder="The shipper's name">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="">email <small>(Ex. test@test.com)</small></label><i class="text-danger asterik">*</i>
                            <input type="text" class="form-control disabled" id="email" name="email" placeholder="The shipper's email address">
                        </div>

                        <div class="col-md-3 form-group">
                            <label for="">Phone <small>(Ex. 9777777779)</small></label><i class="text-danger asterik">*</i>
                            <input type="text" list="suggestphone" class="form-control" id="phone" name="phone" placeholder="Shipper's phone number">

                            <datalist id="suggestphone">
                                <?php
                                if(!empty($res[0])){
                                foreach ($res as $key=> $phone) { ?>
                                    <option value="<?= $res[$key]['phone']?>">
                                    <?php
                                }}
                                    ?>
                            </datalist>
                        </div>

                        <div class="col-md-3 form-group">
                            <label for="">City <small>(Ex. Pune)</small></label><i class="text-danger asterik">*</i>
                            <input type="text" class="form-control" id="city" name="city" placeholder="Pickup location city name">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="">State <small>(Ex. Maharashtra)</small></label><i class="text-danger asterik">*</i>
                            <input type="text" class="form-control" id="state" name="state" placeholder="Pickup location state name">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="">Country <small>(Ex. India)</small></label><i class="text-danger asterik">*</i>
                            <input type="text" class="form-control" id="country" name="country" placeholder="Pickup location country">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="">Pincode <small>(Ex. 110022)</small></label><i class="text-danger asterik">*</i>
                            <input type="text" class="form-control" id="pincode" name="pin_code" placeholder="Pickup location pincode">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="">Address <small>(Ex. Mutant Facility, Sector 3)</small></label><i class="text-danger asterik">*</i>
                            <textarea class="form-control" id="address" name="address" placeholder="Shipper's primary address. Min 10 characters Max 80 characters"></textarea>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="">Address 2 <small>(Ex. House number 34
                                    )</small></label>
                            <textarea class="form-control" id="address2" name="address_2" placeholder="Additional address details"></textarea>
                        </div>
                        <div class="col-md-12 form-group">
                            <label for="">Latitude <small>(Ex. 22.4064)</small></label>
                            <input type="text" class="form-control" id="lat" name="latitude" placeholder="Pickup location latitude">
                        </div>
                        <div class="col-md-12 form-group">
                            <label for="">Longitude <small>(Ex. 69.0747)</small></label>
                            <input type="text" class="form-control" id="long" name="longitude" placeholder="Pickup location longitude">
                        </div>
                        <div class="col-md-12 form-group">
                            <div id="result"></div>
                        </div>

                    </div><!-- /.box-body -->

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" id="add_btn">Add</button>
                        <input type="reset" class="btn-warning btn" value="Clear" />
                    </div>

                </form>
            </div><!-- /.box -->
        </div>
        <div class="col-md-12">
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Pickup locations</h3>
                </div>
                <div class="box-body table-responsive">
                    <div class="alert alert-info" role="alert">
                        Note: After adding pickup location pls contact to you system admin for verify you pickup location, New number in pickup location has to be verified once, Later additions of pickup locations with a same number will not require verification.
                    </div>
                    <table class="table table-hover" data-toggle="table" id="pickup-locations" data-url="get-bootstrap-table-data.php?table=pickup-locations" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-show-export="true" data-export-types='["txt","excel"]' data-export-options='{"fileName": "pickup-locations-list-<?= date('d-m-Y') ?>","ignoreColumn": ["operate"] }'>
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="pickup_location" data-sortable="true">Pickup location</th>
                                <th data-field="name" data-sortable="true">Name</th>
                                <th data-field="email" data-visible="false" data-sortable="true">Email</th>
                                <th data-field="phone" data-sortable="true">Phone</th>
                                <th data-field="address" data-visible="false" data-sortable="true">Address</th>
                                <th data-field="address_2" data-visible="false" data-sortable="true">Address 2</th>
                                <th data-field="city" data-sortable="true">City</th>
                                <th data-field="state" data-sortable="true">State</th>
                                <th data-field="country">Country</th>
                                <th data-field="pin_code">Pin code</th>
                                <th data-field="latitude" data-visible="false">Latitude</th>
                                <th data-field="longitude" data-visible="false">Longitude</th>
                                <!-- <th data-field="verified">Status</th> -->
                                <!-- <th data-field="operate" data-events="actionEvents">Action</th> -->
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    $('#pickup_location_form').validate({
        rules: {
            pickup_location: {
                required: true,
                maxlength: 36
            },
            name: "required",
            email: "required",
            phone: "required",
            address: "required",
            city: "required",
            state: "required",
            country: "required",
            pin_code: "required",

        }
    });
</script>
<script>
    $('#pickup_location_form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        if ($("#pickup_location_form").validate().form()) {
            if (confirm('Are you sure?Want to add pickup location')) {
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function() {
                        $('#add_btn').html('Please wait..').attr('disabled', true);
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function(result) {
                        $('#result').html(result.message);
                        $('#result').show().delay(6000).fadeOut();
                        $('#add_btn').html('Add').attr('disabled', false);
                        $('#pickup-locations').bootstrapTable('refresh');
                        if (result.error == false) {
                            $('#pickup_location_form')[0].reset();
                        }
                    }
                });
            }
        }
    });

    $('#pickup_location').on('keyup', function(e) {
        if ($('#pickup_location').val().length === 0) {
            console.log($('#success').addClass('hide'));
            console.log('value empty');
            return false;
        }

        e.preventDefault();
        data={
            check_pickup_location:1,
            pickup_location_seller:$('#pickup_location').val(),
            check_seller_id:$('#seller_id').val()
        }

        $.ajax({

            type: 'POST',
            url: 'public/db-operation.php',
            data:data,
            dataType: "json",
            error: function(request, error) {
                $('#location_result').removeClass('hide')
                $('#name').attr('disabled', true);
                $('#email').attr('disabled', true);
                $('#phone').attr('disabled', true);
                $('#pincode').attr('disabled', true);
                $('#city').attr('disabled', true);
                $('#state').attr('disabled', true);
                $('#country').attr('disabled', true);
                $('#address').attr('disabled', true);
                $('#address2').attr('disabled', true);
                $('#lat').attr('disabled', true);
                $('#long').attr('disabled', true);
                $('#add_btn').html('Add').attr('disabled', true);
                $('#success').addClass('hide')

            },
            success: function(location_result) {
                $('#location_result').html(location_result.message);
                if (location_result.error == false) {
                    $('#location_result').addClass('hide')
                    $('#name').attr('disabled', false);
                    $('#email').attr('disabled', false);
                    $('#phone').attr('disabled', false);
                    $('#pincode').attr('disabled', false);
                    $('#city').attr('disabled', false);
                    $('#state').attr('disabled', false);
                    $('#country').attr('disabled', false);
                    $('#address').attr('disabled', false);
                    $('#address2').attr('disabled', false);
                    $('#lat').attr('disabled', false);
                    $('#long').attr('disabled', false);
                    $('#add_btn').html('Add').attr('disabled', false);
                    $('#success').removeClass('hide');
                    $('#pSlug').html(location_result.slug);
                }

            }
        })


    });
</script>

<div class="separator"> </div>

<?php $db->disconnect(); ?>