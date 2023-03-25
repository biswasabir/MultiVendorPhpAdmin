<section class="content-header">
    <h1>Store Settings</h1>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
    <hr />
</section>
<section class="content">

    <div class="row">
        <div class="col-md-12">
            <!-- general form elements -->
            <?php
            if ($permissions['settings']['read'] == 1) {

                if ($permissions['settings']['update'] == 0) { ?>
                    <div class="alert alert-danger">You have no permission to update settings</div>
                <?php } ?>
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Update System Settings</h3>
                    </div>
                    <!-- /.box-header -->
                    <?php
                    $db->sql("SET NAMES 'utf8'");
                    $sql = "SELECT * FROM settings WHERE  variable='system_timezone'";
                    $db->sql($sql);

                    $res_time = $db->getResult();
                    if (!empty($res_time)) {
                        foreach ($res_time as $row) {
                            $id = $row['id'];
                            $data = json_decode($row['value'], true);
                        }
                    }

                    $sql = "select value from `settings` where variable='Logo' OR variable='logo'";
                    $db->sql($sql);
                    $res_logo = $db->getResult();
                    $sql = "SELECT * FROM settings WHERE variable='currency'";
                    $db->sql($sql);
                    $res_currency = $db->getResult();
                    ?>
                    <!-- form start -->
                    <form id="system_configurations_form" method="post" enctype="multipart/form-data">
                        <input type="hidden" id="system_configurations" name="system_configurations" required="" value="1" aria-required="true">
                        <input type="hidden" id="system_timezone_gmt" name="system_timezone_gmt" value="<?php if (!empty($data['system_timezone_gmt'])) {
                                                                                                            echo $data['system_timezone_gmt'];
                                                                                                        } ?>" aria-required="true">
                        <input type="hidden" id="system_configurations_id" name="system_configurations_id" value="<?php if (!empty($id)) {
                                                                                                                        echo $id;
                                                                                                                    } ?>" aria-required="true">
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-2">
                                    <label for="app_name">App Name:</label>
                                    <input type="text" class="form-control" required name="app_name" value="<?= (isset($data['app_name'])) ? $data['app_name'] : ''; ?>" placeholder="Name of the App - used in whole system" />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="">Support Number:</label>
                                    <input type="text" class="form-control" required name="support_number" value="<?= (isset($data['support_number'])) ? $data['support_number'] : "" ?>" placeholder="Customer support mobile number - used in whole system +91 9876543210" />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="">Support Email:</label>
                                    <input type="text" class="form-control" required name="support_email" value="<?= (isset($data['support_email'])) ? $data['support_email'] : "" ?>" placeholder="Customer support email - used in whole system" />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="">Current Version Of App:</label>
                                    <input type="text" class="form-control" required name="current_version" value="<?= isset($data['current_version']) ? $data['current_version'] : '' ?>" placeholder='Current Version' />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="">Minimum Version Required: </label>
                                    <input type="text" class="form-control" required name="minimum_version_required" value="<?= isset($data['minimum_version_required']) ? $data['minimum_version_required'] : '' ?>" placeholder='Minimum Required Version' />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="">Version System Status</label><br>
                                    <input type="checkbox" id="version-system-button" class="js-switch" <?php if (!empty($data['is-version-system-on']) && $data['is-version-system-on'] == '1') {
                                                                                                            echo 'checked';
                                                                                                        } ?>>
                                    <input type="hidden" id="is-version-system-on" name="is-version-system-on" value="<?= (!empty($data['is-version-system-on'])) ? $data['is-version-system-on'] : 0; ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-2">
                                    <label for="app_name">Logo:</label>
                                    <img src="<?= DOMAIN_URL . 'dist/img/' . $res_logo[0]['value'] ?>" title='<?= $data['app_name'] ?> - Logo' alt='<?= (isset($data['app_name'])) ? $data['app_name'] : ""; ?> - Logo' style="max-width:100%" />
                                    <input type='file' name='logo' id='logo' accept="image/*" />
                                </div>
                                <h4>Store Address Settings</h4>
                                <hr>
                                <div class="form-group col-md-4">
                                    <label for="">Address: </label>
                                    <textarea class="form-control" required name="store_address" rows="5" /><?= (isset($data['store_address'])) ? $data['store_address'] : "" ?></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="">Latitude: </label>
                                    <input type="text" class="form-control" required name="map_latitude" value="<?= isset($data['map_latitude']) ? $data['map_latitude'] : '' ?>" placeholder='Minimum Required Version' />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="">Longitude: </label>
                                    <input type="text" class="form-control" required name="map_longitude" value="<?= isset($data['map_longitude']) ? $data['map_longitude'] : '' ?>" placeholder='Minimum Required Version' />
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="currency">Store Currency (Symbol or Code - $ or USD):</label>
                                    <input type="text" class="form-control" required name="currency" value="<?= !empty($res_currency) ? $res_currency[0]['value'] : ''; ?>" placeholder="Either Symbol or Code - For Example $ or USD" />
                                </div>
                                <!-- <div class="form-group col-md-3">
                                    <label for="tax">Tax ( in percentage % ):</label>
                                    <input type="number" class="form-control" name="tax" value="<?= $data['tax'] ?>" placeholder="Enter only number" min="0" />
                                </div> -->

                                <div class="form-group col-md-6">
                                    <label class="system_timezone" for="system_timezone">System Timezone</label>
                                    <select id="system_timezone" name="system_timezone" required class="form-control col-md-12">
                                        <?php $options = getTimezoneOptions();
                                        foreach ($options as $option) { ?>
                                            <option value="<?= $option[2] ?>" data-gmt="<?= $option['1']; ?>" <?= (isset($data['system_timezone']) && $data['system_timezone'] == $option[2]) ? 'selected' : ''; ?>><?= $option[2] ?> - GMT <?= $option[1] ?> - <?= $option[0] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                            </div>
                            <div class="row">

                                <div class="form-group col-md-6">
                                    <label for="">Maximum Items Allowed In Cart <small>( Maximum items user can add to cart at once )</small></label>
                                    <input type="number" required class="form-control" name="max_cart_items_count" value="<?= $data['max_cart_items_count'] ?>" placeholder='Maximum Items Allowed In Cart' min='1' required />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="">Minimum Order Amount <small>( Below this user will not allowed to place order )</small></label>
                                    <input type="number" required class="form-control" name="min_order_amount" value="<?= $data['min_order_amount'] ?>" placeholder='Minimum total amount to place order' min='1' />
                                </div>
                            </div>
                            <div class="row">

                                <div class="form-group col-md-2" id="charge">
                                    <label for="">Area wise delivery charge <small>( Enable/Disable )</small></label><br>
                                    <input type="checkbox" id="area_wise_delivery_charge_btn" class="js-switch" <?= (!empty($data['area-wise-delivery-charge']) && $data['area-wise-delivery-charge'] == '1') ? "checked" : "" ?>>
                                    <input type="hidden" id="area-wise-delivery-charge" name="area-wise-delivery-charge" value="<?= (!empty($data['area-wise-delivery-charge'])) ? $data['area-wise-delivery-charge'] : 0; ?>">
                                </div>
                                <?php $dnone = isset($data['area-wise-delivery-charge']) && $data['area-wise-delivery-charge'] == '1' ? 'd-none' : '' ?>

                                <div class="form-group col-md-5 min_amount <?= $dnone ?>">
                                    <label for="delivery_charge">Minimum Amount for Free Delivery (<?= $settings['currency'] ?>) <small>( Below this user will be charged based on Delivery Charge)</small></label>
                                    <input type="number" class="form-control" name="min_amount" value="<?= $data['min_amount'] ?>" placeholder='Minimum Order Amount for Free Delivery' min='0' />
                                </div>
                                <div class="form-group col-md-5 delivery_charge <?= $dnone ?>">
                                    <label for="delivery_charge">Delivery Charge Amount (<?= $settings['currency'] ?>)</label>
                                    <input type="number" class="form-control" name="delivery_charge" value="<?= $data['delivery_charge'] ?>" placeholder='Delivery Charge on Shopping' min='0' />
                                </div>


                            </div>

                            <hr>
                            <h4>Refer & Earn System</h4>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-1">
                                    <label for="refer-earn-system">Status</label><br>
                                    <input type="checkbox" id="refer-earn-system-button" class="js-switch" <?php if (!empty($data['is-refer-earn-on']) && $data['is-refer-earn-on'] == '1') {
                                                                                                                echo 'checked';
                                                                                                            } ?>>
                                    <input type="hidden" id="is-refer-earn-on" name="is-refer-earn-on" value="<?= (!empty($data['is-refer-earn-on'])) ? $data['is-refer-earn-on'] : 0; ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="">Minimum Refer & Earn Order Amount (<?= $settings['currency'] ?>)</label>
                                    <input type="number" required class="form-control" name="min-refer-earn-order-amount" value="<?= $data['min-refer-earn-order-amount'] ?>" placeholder='Minimum Order Amount' />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="">Refer & Earn Bonus (<?= $settings['currency'] ?> OR %)</label>
                                    <input type="number" required class="form-control" name="refer-earn-bonus" value="<?= $data['refer-earn-bonus'] ?>" placeholder='Bonus' />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="">Refer & Earn Method</label>
                                    <select name="refer-earn-method" class="form-control">
                                        <option value="">Select</option>
                                        <option value="percentage" <?= (isset($data['refer-earn-method']) && $data['refer-earn-method'] == 'percentage') ? "selected" : "" ?>>Percentage</option>
                                        <option value="rupees" <?= (isset($data['refer-earn-method']) && $data['refer-earn-method'] == 'rupees') ? "selected" : "" ?>>Rupees</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="">Maximum Refer & Earn Amount (<?= $settings['currency'] ?>)</label>
                                    <input type="number" required class="form-control" name="max-refer-earn-amount" value="<?= $data['max-refer-earn-amount'] ?>" placeholder='Maximum Refer & Earn Amount' />
                                </div>

                            </div>
                            <h4>Other Settings</h4>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="">Maximum Withdrawal Amount</label>
                                    <input type="number" class="form-control" required name="minimum-withdrawal-amount" value="<?= $data['minimum-withdrawal-amount'] ?>" placeholder='Minimum Withdrawal Amount' />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="">Max days to return item</label>
                                    <input type="number" class="form-control" required name="max-product-return-days" value="<?= (isset($data['max-product-return-days'])) ? $data['max-product-return-days'] : ''; ?>" placeholder='Max days to return item' />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="">Delivery Boy Bonus (%)</label>
                                    <input type="number" class="form-control" required name="delivery-boy-bonus-percentage" value="<?= $data['delivery-boy-bonus-percentage'] ?>" placeholder='Delivery Boy Bonus' />
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="">User Wallet Refill Limit </label>
                                    <input type="number" class="form-control" required name="user-wallet-refill-limit" value="<?= (isset($data['user-wallet-refill-limit'])) ? $data['user-wallet-refill-limit'] : ''; ?>" placeholder='User Wallet Refill Limit' />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="">Tax Name ( This will be visible on your invoice )</label>
                                    <input type="text" class="form-control" required name="tax_name" value="<?= (isset($data['tax_name'])) ? $data['tax_name'] : ''; ?>" placeholder='Tax Name' />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="">Tax Number</label>
                                    <input type="text" class="form-control" required name="tax_number" value="<?= (isset($data['tax_number'])) ? $data['tax_number'] : ''; ?>" placeholder='Tax Number' />
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="">Low stock limit <small>(Product will be considered as low stock if stock goes below this limit)</small></label>
                                    <input type="number" class="form-control" required name="low-stock-limit" value="<?= (isset($data['low-stock-limit'])) ? $data['low-stock-limit'] : ''; ?>" placeholder='Product low stock limit' />
                                </div>
                            </div>
                            <h4>Mail Settings</h4>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="from_mail">From eMail ID: <small>( This email ID will be used in Mail System )</small></label>
                                    <input type="email" class="form-control" required name="from_mail" value="<?= $data['from_mail'] ?>" placeholder='From Email ID' />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="reply_to">Reply To eMail ID: <small>( This email ID will be used in Mail System )</small></label>
                                    <input type="email" class="form-control" required name="reply_to" value="<?= $data['reply_to'] ?>" placeholder='From Email ID' />
                                </div>
                            </div>
                            <h4>OTP Settings</h4>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="">Order Delivery OTP System <small>( Enable/Disable )</small></label><br>
                                    <input type="checkbox" id="gen_otp" class="js-switch" <?php if (!empty($data['generate-otp']) && $data['generate-otp'] == '1') {
                                                                                                echo 'checked';
                                                                                            } ?>>
                                    <input type="hidden" id="generate-otp" name="generate-otp" value="<?= (!empty($data['generate-otp'])) ? $data['generate-otp'] : 0; ?>">
                                </div>
                            </div>

                            <h4>Maintenance Mode</h4>
                            <small>In this mode you can set your app in Maintenance and that Appilication will not work till not disabled from here</small>
                            <?php
                            $sql = "SELECT * from settings where variable='maintenance_mode'";
                            $db->sql($sql);
                            $modes = json_decode($db->getResult()[0]['value'], 1);

                            ?>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="">Customer APP <small>( Enable/Disable )</small></label><br>
                                    <input type="checkbox" id="customer" class="js-switch" <?php if (!empty($modes['customer']) && $modes['customer'] == '1') {
                                                                                                echo 'checked';
                                                                                            } ?>>
                                    <input type="hidden" id="customer_app_mode" name="customer_app_mode" value="<?= (!empty($modes['customer'])) ? $modes['customer'] : 0; ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="">Seller APP <small>( Enable/Disable )</small></label><br>
                                    <input type="checkbox" id="seller" class="js-switch" <?php if (!empty($modes['seller']) && $modes['seller'] == '1') {
                                                                                                echo 'checked';
                                                                                            } ?>>
                                    <input type="hidden" id="seller_app_mode" name="seller_app_mode" value="<?= (!empty($modes['seller'])) ? $modes['seller'] : 0; ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="">Delivery Boy <small>( Enable/Disable )</small></label><br>
                                    <input type="checkbox" id="delivery_boy" class="js-switch" <?php if (!empty($modes['delivery_boy']) && $modes['delivery_boy'] == '1') {
                                                                                                    echo 'checked';
                                                                                                } ?>>
                                    <input type="hidden" id="delivery_boy_app_mode" name="delivery_boy_app_mode" value="<?= (!empty($modes['delivery_boy'])) ? $modes['delivery_boy'] : 0; ?>">
                                </div>
                            </div>
                            <hr>
                            <h4>SMTP Mail Settings</h4>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="from_mail">From eMail ID: <small>( This email ID will be used in SMTP Mail System )</small></label>
                                    <input type="email" class="form-control" name="smtp-from-mail" value="<?= (isset($data['smtp-from-mail'])) ? $data['smtp-from-mail'] : "" ?>" placeholder='From SMTP Email ID' />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="reply_to">Reply To eMail ID: <small>( This email ID will be used in SMTP Mail System )</small></label>
                                    <input type="email" class="form-control" name="smtp-reply-to" value="<?= (isset($data['smtp-reply-to'])) ? $data['smtp-reply-to'] : ""  ?>" placeholder='From SMTP Email ID' />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="reply_to">SMTP eMail Password: </label>
                                    <input type="text" class="form-control" name="smtp-email-password" value="<?= (isset($data['smtp-email-password'])) ? $data['smtp-email-password'] : "" ?>" placeholder='Enter your SMTP email password' />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="reply_to">SMTP Host: </label>
                                    <input type="text" class="form-control"  name="smtp-host" value="<?= (isset($data['smtp-host'])) ? $data['smtp-host'] : "" ?>" placeholder='SMTP Host address' />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="reply_to">SMTP Port: <small>( <b>TLS: </b>587 <b>SSL: </b>465 )</small> </label>
                                    <input type="text" class="form-control"  name="smtp-port" value="<?= (isset($data['smtp-port'])) ? $data['smtp-port'] : "" ?>" placeholder='SMTP Port' />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="">SMTP eMail Content Type: </label>
                                    <select name="smtp-content-type" class="form-control">
                                        <option value="">Select</option>
                                        <option value="html" <?= (isset($data['smtp-content-type']) && $data['smtp-content-type'] == 'html') ? "selected" : "" ?>>HTML</option>
                                        <option value="text" <?= (isset($data['smtp-content-type']) && $data['smtp-content-type'] == 'text') ? "selected" : "" ?>>Text</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="">SMTP Encryption: </label>
                                    <select name="smtp-encryption-type" class="form-control">
                                        <option value="">Select</option>
                                        <option value="tls" <?= (isset($data['smtp-encryption-type']) && $data['smtp-encryption-type'] == 'tls') ? "selected" : "" ?>>TLS</option>
                                        <option value="ssl" <?= (isset($data['smtp-encryption-type']) && $data['smtp-encryption-type'] == 'ssl') ? "selected" : "" ?>>SSL</option>
                                    </select>
                                </div>
                            </div>
                            <h4>Cron Job URL for Seller commission </h4> <a class='btn btn-xs btn-primary' data-toggle='modal' data-target='#howItWorksModal' title='How it works'>How seller commission works?</a>
                            <hr>
                            <div class=" row">
                                <div class="form-group col-md-6">
                                    <label for="url">Cron Job URL <small>(Set this URL at your server cron job list for "once a day")</small></label>
                                    <input type="text" class="form-control" name="midtrans_notification_url" value="<?= DOMAIN_URL . "update-seller-commission.php" ?>" placeholder="Cron Job URL" disabled />
                                </div>
                            </div>

                        </div>

                        <!-- /.box-body -->
                        <div id="result"></div>
                        <div class="box-footer">
                            <input type="submit" id="btn_update" class="btn-primary btn" value="Update" name="btn_update" />
                            <!-- <input type="submit" class="btn-danger btn" value="Cancel" name="btn_cancel"/> -->
                        </div>
                    </form>
                <?php } else { ?>
                    <div class="alert alert-danger">You have no permission to view settings</div>
                <?php } ?>
                </div>
                <!-- /.box -->
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
    </div>
    </div>
</section>
<div class="separator"> </div>
<?php function getTimezoneOptions()
{
    $list = DateTimeZone::listAbbreviations();
    $idents = DateTimeZone::listIdentifiers();

    $data = $offset = $added = array();
    foreach ($list as $abbr => $info) {
        foreach ($info as $zone) {
            if (
                !empty($zone['timezone_id'])
                and
                !in_array($zone['timezone_id'], $added)
                and
                in_array($zone['timezone_id'], $idents)
            ) {
                $z = new DateTimeZone($zone['timezone_id']);
                $c = new DateTime('', $z);
                $zone['time'] = $c->format('H:i a');
                $offset[] = $zone['offset'] = $z->getOffset($c);
                $data[] = $zone;
                $added[] = $zone['timezone_id'];
            }
        }
    }

    array_multisort($offset, SORT_ASC, $data);
    /*$options = array();
                    foreach ($data as $key => $row) {
                        $options[$row['timezone_id']] = $row['time'] . ' - '
                            . formatOffset($row['offset']). ' ' . $row['timezone_id'];
                    }*/
    $i = 0;
    $temp = array();
    foreach ($data as $key => $row) {
        $temp[0] = $row['time'];
        $temp[1] = formatOffset($row['offset']);
        $temp[2] = $row['timezone_id'];
        $options[$i++] = $temp;
    }

    // echo "<pre>";
    // print_r($options);
    return $options;
}
function formatOffset($offset)
{
    $hours = $offset / 3600;
    $remainder = $offset % 3600;
    $sign = $hours > 0 ? '+' : '-';
    $hour = (int) abs($hours);
    $minutes = (int) abs($remainder / 60);

    if ($hour == 0 and $minutes == 0) {
        $sign = ' ';
    }
    return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0');
}
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.16.0/jquery.validate.min.js"></script>
<script>
    $('#system_timezone').on('change', function(e) {
        gmt = $(this).find(':selected').data('gmt');
        $('#system_timezone_gmt').val(gmt);

    });

    $('#system_configurations_form').validate({
        rules: {
            currency: "required",
        }
    });

    $('#system_configurations_form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        if ($("#system_configurations_form").validate().form()) {
            $.ajax({
                type: 'POST',
                url: 'public/db-operation.php',
                data: formData,
                beforeSend: function() {
                    $('#btn_update').html('Please wait..');
                },
                cache: false,
                contentType: false,
                processData: false,
                success: function(result) {
                    $('#result').html(result);
                    $('#result').show().delay(5000).fadeOut();
                    $('#btn_update').html('Save Settings');
                    // $('#system_configurations_form')[0].reset();
                    // location.reload();
                }
            });
        }
    });
</script>
<script>
    var changeCheckbox = document.querySelector('#version-system-button');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#is-version-system-on').val(1);
        } else {
            $('#is-version-system-on').val(0);
        }
    };
    var changeCheckbox = document.querySelector('#refer-earn-system-button');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#is-refer-earn-on').val(1);
        } else {
            $('#is-refer-earn-on').val(0);
        }
    };
    var changeCheckbox = document.querySelector('#gen_otp');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#generate-otp').val(1);
        } else {
            $('#generate-otp').val(0);
        }
    };
    var changeCheckbox = document.querySelector('#customer');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#customer_app_mode').val(1);
        } else {
            $('#customer_app_mode').val(0);
        }
    };
    var changeCheckbox = document.querySelector('#seller');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#seller_app_mode').val(1);
        } else {
            $('#seller_app_mode').val(0);
        }
    };
    var changeCheckbox = document.querySelector('#delivery_boy');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $('#delivery_boy_app_mode').val(1);
        } else {
            $('#delivery_boy_app_mode').val(0);
        }
    };
    var changeCheckbox = document.querySelector('#area_wise_delivery_charge_btn');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            $(".min_amount").hide();
            $(".delivery_charge").hide();
            $('#area-wise-delivery-charge').val(1);
        } else {
            $(".min_amount").show();
            $(".delivery_charge").show();
            $('#area-wise-delivery-charge').val(0);
        }
    };
    var changeCheckbox = document.querySelector('#shiprocket_btn');
    var init = new Switchery(changeCheckbox);
    changeCheckbox.onchange = function() {
        if ($(this).is(':checked')) {
            // $(".shiprocket_email").show();
            // $(".shiprocket_password").show();
            $('#shiprocket').val(1);

        } else {
            // $(".shiprocket_email").hide();
            // $(".shiprocket_password").hide();
            $('#shiprocket').val(0);
        }
    };
</script>