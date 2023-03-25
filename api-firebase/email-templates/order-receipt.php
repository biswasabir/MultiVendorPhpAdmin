<?php
include_once '../../includes/crud.php';
$db = new Database();
$db->connect();
include_once '../../includes/functions.php';
$fn = new functions();
$system_configs = $fn->get_system_configs();
$app_name = $system_configs['app_name'];
$support_email = $system_configs['support_email'];
$support_number = $system_configs['support_number'];
include_once '../../includes/custom-functions.php';
$fun = new custom_functions();
$logo = $fun->get_settings('logo');

$currency = $fun->get_settings('currency');
if ($currency == '৳') {
    $currency = '&#2547;';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/ico" href="<?= DOMAIN_URL . 'dist/img/' . $logo ?>">
    <title>Email - <?= $app_name ?></title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <style>
        .borderless td,
        th {
            border: none !important;
            padding: 0px !important;
        }

        .email-header {
            background-color: #1D92EE;
            padding: 13px;
        }


        .title {
            color: #FFD966;
            font-weight: bold;
        }

        .logo {
            float: right;
        }

        .table-title {
            float: right;
            color: #8c8d8e;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .content-wrapper {
            text-align: center;
            padding: 10px;
        }


        .header-wrapper {
            max-width: 800px;
            margin: auto;
        }

        .body {
            background-color: #CFE2F3;
        }

        .main-section {
            background-color: #FFFFFF;
        }

        .table-data {
            background-color: #62626229;
        }

        .table-row {
            text-align: center;
            font-size: 15px;
        }

        .invoice-info {
            margin: 10px;
        }

        .otp {
            background-color: lightgrey;
            text-align: center;
            float: right;
            letter-spacing: 2px;
        }

        .desc-wrapper {
            float: right;
            padding: 25px;
        }

        .mess {
            padding-left: 50px;
            padding-right: 50px;
        }

        @media only screen and (max-width: 600px) {
            .header {
                text-align: center;
            }

            .logo {
                float: none;
            }


        }
    </style>
</head>

<body>
    <div class="body">
        <div class="container">
            <div class="header-wrapper">
                <!-- header section -->
                <section class="email-header">
                    <div class="header">
                        <div class="row">
                            <div class="col-sm-6">
                                <h3 class="title"><?= $app_name ?></h3>
                            </div>
                            <div class="col-sm-6">
                                <div class="logo">
                                    <img src="<?= DOMAIN_URL . 'dist/img/' . $logo ?>" alt="" width="100px">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>


                <!-- main section  -->
                <div class="main-section">
                    <section class="main-wrapper">
                        <div class="content-wrapper">
                            <div class="img">
                                <img src="<?= DOMAIN_URL . 'images/order_received_img.png' ?>" alt="">
                            </div>
                            <h2><b>Thank You For Your Order!</b></h2>
                        </div>

                        <div class="mess">
                            <h5><?= $order_data['user_msg'] ?></h5>
                            <h5><?= $order_data['otp_msg'] ?></h5>
                        </div>
                    </section>

                    <!-- table section -->

                    <div class="desc-wrapper">
                        <?php
                        if ($order_data['otp'] == 0 || $order_data['otp'] == '') {
                            $display = "none";
                            $style = " style='width:0px;' ";
                        }
                        ?>
                        <div class="otp" <?= $style ?> <?= $display ?>>
                            <h4><?= $order_data['otp'] ?></h4>
                        </div>
                        <div><small class="table-title"><i>* prices are shown including taxes.</i></small></div>
                    </div>

                    <section class="table-wrapper">
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr style="background-color: #a0999980;">
                                            <th class="table-row">Name</th>
                                            <th class="table-row">Sold By</th>
                                            <th class="table-row">Unit</th>
                                            <th class="table-row">QTY</th>
                                            <th class="table-row">Sub Total (<?= $currency; ?>)</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($item_data1 as $rows) {
                                        ?>
                                            <tr>
                                                <td class="table-row"><?= $rows['name'] ?></td>
                                                <td class="table-row"><?= $rows['store_name'] ?></td>
                                                <td class="table-row"><?= $rows['unit'] ?></td>
                                                <td class="table-row"><?= $rows['qty'] ?></td>
                                                <td class="table-row"><?= $rows['subtotal'] ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div><!-- /.col -->
                        </div>

                        <div class="row">
                            <div class="col-md-10" style="margin-left: 30px;">
                                <table class="table borderless">
                                    <tr>
                                        <th>
                                            <h4><b>Total (<?= $currency; ?>)</b></h4>
                                        </th>
                                        <td>
                                            <h4 style="float: right;"><?= $order_data['total_amount'] ?></h4>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <h4><b>Delivery Charge (<?= $currency; ?>)</b></h4>
                                        </th>
                                        <td>
                                            <h4 style="float: right;"><?= $order_data['delivery_charge'] ?></h4>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <h4><b>Wallet Amount (<?= $currency; ?>)</b></h4>
                                        </th>
                                        <td>
                                            <h4 style="float: right;"><?= $order_data['wallet_used'] ?></h4>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <h4><b>Final Total (<?= $currency; ?>)</b></h4>
                                        </th>
                                        <td>
                                            <h4 style="float: right;"><?= $order_data['final_total'] ?></h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- info row -->
                        <div class="row" style="margin-left: 12px;">
                            <div class="col-md-4 invoice-col">
                                <h4><b>From</b></h4>
                                <h4><b>Email : </b></h4>
                                <h4><?= $support_email; ?></h4>
                                <h4><b>Customer Care : </b></h4>
                                <h4><?= $support_number; ?></h4>
                            </div><br><!-- /.col -->

                            <div class="col-md-4 invoice-col">
                                <h4><b>Delivery Address</b></h4>
                                <h4><?= $order_data['address']; ?></h4>
                            </div><br><!-- /.col -->

                            <div class="col-md-4 invoice-col">
                                <h4><b>Payment Method</b></h4>
                                <h4><?= $order_data['payment_method']; ?></h5>
                            </div><br>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</body>

</html>