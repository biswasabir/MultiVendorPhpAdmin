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


?>


<?php include "header.php"; ?>
<html>

<head>
    <title>Pickup location | <?= $settings['app_name'] ?> - Dashboard</title>
    <style>
        .asterik {
            font-size: 20px;
            line-height: 0px;
            vertical-align: middle;
        }

        .pointer:hover {
            cursor: pointer;
        }
    </style>
</head>
</body>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <?php
    include_once('includes/functions.php');
    ?>
    <section class="content-header">
        <div class="alert alert-warning	" role="alert">
            Note: You can verify unverified pickup locations of sellers from <a href="https://app.shiprocket.in/company-pickup-location?redirect_url=" target="_blank">shiprocket dashboard</a>. New number in pickup location has to be verified once, Later additions of pickup locations with a same number will not require verification.
        </div>
        <h1>Pickup Location /<small><a href="home.php"><i class="fa fa-home"></i> Home</a></small></h1>

    </section>
    <?php
    if ($permissions['locations']['read'] == 1) {
    ?>
        <!-- Main content -->
        <section class="content">
            <!-- Main row -->
            <div class="row">
                <!-- Left col -->
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                        </div>
                        <div class="box-header">
                            <h3 class="box-title">Pickup Location</h3>
                        </div>
                        <div class="box-body table-responsive">

                            <div id="result"></div>
                            <table class="table table-hover" data-toggle="table" id="pickup_location_list" data-url="api-firebase/get-bootstrap-table-data.php?table=pickup_locations" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-query-params="queryParams_1">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="pickup_location" data-sortable="true">pickup locations</th>
                                        <th data-field="seller_name" data-sortable="true">Seller Name</th>
                                        <th data-field="name" data-sortable="true">Name</th>
                                        <th data-field="email" data-sortable="true">Email</th>
                                        <th data-field="phone" data-sortable="true">Phone</th>
                                        <th data-field="address" data-sortable="true">Address</th>
                                        <th data-field="address_2" data-sortable="true">Address 2</th>
                                        <th data-field="city" data-sortable="true">city</th>
                                        <th data-field="pin_code" data-sortable="true">Pincode</th>
                                        <th data-field="verified" data-sortable="true">Status</th>
                                        <th data-field="operate">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="separator"> </div>
            </div>
        </section>

        <div class="modal fade " id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Etid Pickup Location</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="" method="post" id="pickup_location_form" class="pickup_locations">
                        <div class="modal-body">
                            <input type="hidden" name="update_pickup_location" value="1">
                            <input type="hidden" name="seller_id" id="pickup_location_seller_id" value="">
                            <input type="hidden" name="accesskey" value="90336">

                            <div class="alert alert-warning" role="alert">
                                <b>Note:</b> After edit pickup location also edit in <a href="">Shiprocket Panel</a>, Otherwise details will missmatch.
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="">Pickup location <small>(Ex. Home)</small></label>
                                <input type="text" id="pickup_location" class="form-control" name="pickup_location" placeholder="Nickname of the new pickup location. Max 36 characters">
                                <label for="" id="location_result" disabled class="text-danger hide">Pickup Locations Already Taken </label>
                                <div class="hide" id='success'>
                                    <label for="" id="pSlug" class="text-success"></label> <i class="fa fa-check-circle text-success"></i>
                                </div>

                            </div>
                            <div class="col-md-3 form-group">
                                <label for="">Name <small>(Ex. Deadpool)</small></label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="The shipper's name">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="">email <small>(Ex. test@test.com)</small></label>
                                <input type="text" class="form-control disabled" id="email" name="email" placeholder="The shipper's email address">
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="">Phone <small>(Ex. 9777777779)</small></label>
                                <input type="text" list="suggestphone" class="form-control" id="phone" name="phone" placeholder="Shipper's phone number">

                            </div>

                            <div class="col-md-3 form-group">
                                <label for="">City <small>(Ex. Pune)</small></label>
                                <input type="text" class="form-control" id="city" disabled name="city" placeholder="Pickup location city name">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="">State <small>(Ex. Maharashtra)</small></label>
                                <input type="text" class="form-control" id="state" disabled name="state" placeholder="Pickup location state name">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="">Country <small>(Ex. India)</small></label>
                                <input type="text" class="form-control" id="country" disabled name="country" placeholder="Pickup location country">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="">Pincode <small>(Ex. 110022)</small></label>
                                <input type="text" class="form-control" id="pincode" disabled name="pin_code" placeholder="Pickup location pincode">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="">Address <small>(Ex. Mutant Facility, Sector 3)</small></label>
                                <textarea class="form-control" id="address" name="address" disabled placeholder="Shipper's primary address. Min 10 characters Max 80 characters"></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="">Address 2 <small>(Ex. House number 34
                                        )</small></label>
                                <textarea class="form-control" id="address2" name="address_2" disabled placeholder="Additional address details"></textarea>
                            </div>
                            <div class="col-md-12 form-group">
                                <div id="result"></div>
                            </div>


                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="close" data-dismiss="modal">Close</button>
                            <button type="button" id='update_btn' class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>



    <?php } else { ?>
        <div class="alert alert-danger topmargin-sm" style="margin-top: 20px;">You have no permission to view areas.</div>
    <?php } ?>
    <script>
        function verified(element) {
            $.ajax({
                type: 'POST',
                url: 'public/db-operation.php',
                data: 'update_pickup_location=' + $(element).data('id') + '&verified=' + $(element).data('verified'),
                dataType: "json",
                success: function(result) {
                    if (result.error == false) {
                        $('#result').html('<h4><label class="label label-success" >' + result.Message + '</label></h4>')
                        $('#pickup_location_list').bootstrapTable('refresh');
                        setTimeout(function() {
                            $('#result').html("");
                        }, 3000);
                    } else {
                        $('#result').html('<h4><label class="label bg-danger label-danger text-white" >' + result.Message + '</label></h4>')
                        $('#pickup_location_list').bootstrapTable('refresh');
                        setTimeout(function() {
                            $('#result').html("");
                        }, 3000);
                    }
                }
            });
        }

        function delete_pickup_location(element) {
            var message = "Are you sure to delete  " + $(element).data('pickup-location') + ' ?'
            if (confirm(message) == true) {
                $.ajax({
                    type: 'POST',
                    url: 'public/db-operation.php',
                    data: 'delete_pickup_location=' + $(element).data('id'),
                    dataType: "json",
                    success: function(result) {
                        if (result.error == false) {
                            $('#result').html('<h4><label class="label label-success" >' + result.Message + '</label></h4>')
                            $('#pickup_location_list').bootstrapTable('refresh');
                            setTimeout(function() {
                                $('#result').html("");
                            }, 3000);
                        } else {
                            $('#result').html('<h4><label class="label bg-danger label-danger text-white" >' + result.Message + '</label></h4>')
                            $('#pickup_location_list').bootstrapTable('refresh');
                            setTimeout(function() {
                                $('#result').html("");
                            }, 3000);
                        }
                    }
                });
            } else {

            }

        }


        async function fetch_pickup_location(pickup_location, seller_id) {
            await $.ajax({
                type: 'POST',
                url: 'public/db-operation.php',
                data: 'pickup_location =' + pickup_location + ' &selected_pickup_location_seller_id=' + seller_id,
                dataType: "json",
                error: function(request) {
                    response = request;
                },
                success: function(request) {
                    response = request;

                }
            });
            return response;

        }

        async function get_pickup_location(element) {
            var pickup_location = $(element).data('pickup-location');
            var seller_id = $(element).data('seller-id');
            $('#pickup_location_seller_id').attr('value', seller_id);
            result = await fetch_pickup_location(pickup_location, seller_id);
            console.log(result);
            if (result.error == false) {
                var data = result.data;
                $('#pickup_location').attr('value', data[0]['pickup_location']);
                $('#name').attr('value', data[0]['name']);
                $('#email').attr('value', data[0]['email']);
                $('#phone').attr('value', data[0]['phone']);
                $('#pincode').attr('value', data[0]['pin_code']);
                $('#city').attr('value', data[0]['city']);
                $('#state').attr('value', data[0]['state']);
                $('#country').attr('value', data[0]['country']);
                $('#address').html(data[0]['address']);
                $('#address2').html(data[0]['address2']);
                $('#lat').attr('value', data[0]['latitude']);
                $('#long').attr('value', data[0]['longitude']);
                $('#update_btn').html('Update').attr('disabled', false);
                $('#success').addClass('hide')
            }


        }
        $('#update_btn').on('click', function(e) {
            // var form_data = document.getElementById('#pickup_location_form');
            var formData = {
                update_pickup_location: $('#pickup_location').val(),
                name: $('#name').val(),
                phone: $('#phone').val(),
                email: $('#pincode').val(),


            };

            if (confirm('Are you sure?Want to edit pickup location')) {
                $.ajax({
                    type: 'POST',
                    url: 'public/db-operation.php',
                    data: formData,
                    beforeSend: function() {
                        $('#update_btn').html('Please wait..').attr('disabled', true);
                    },
                    cache: false,

                    dataType: "json",
                    success: function(result) {
                        $('#result').html(result.message);
                        $('#result').show().delay(6000).fadeOut();
                        $('#update_btn').html('Update').attr('disabled', false);
                        $('#pickup_location_list').bootstrapTable('refresh');
                        if (result.error == false) {
                            $('#pickup_location_form')[0].reset();
                        }
                        $('#close').click();
                    }
                });
            }

        });

        $('#pickup_location').on('keyup', function(e) {
            if ($('#pickup_location').val().length === 0) {
                $('#success').addClass('hide');
                return false;
            }

            e.preventDefault();
            var pickup_location = $(this).val();
            var seller_id = $('#pickup_location_seller_id').val();
            fetch_pickup_location(pickup_location, seller_id);
            if (result.error == false) {
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
                $('#update_btn').html('Update').attr('disabled', false);
                $('#success').removeClass('hide');
                $('#pSlug').html(result.slug);
            } else {
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
                $('#update_btn').html('Update').attr('disabled', true);
                $('#success').addClass('hide')
            }
        });

        function queryParams_1(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }
    </script>
</div><!-- /.content-wrapper -->
</body>

</html>
<?php include "footer.php"; ?>