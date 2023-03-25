<?php
session_start();
header("Expires: on, 01 Jan 1970 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// start session
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
include_once('includes/crud.php');
include_once('includes/functions.php');
include_once('includes/custom-functions.php');
$fn = new custom_functions;
$permissions = $fn->get_permissions($_SESSION['id']);
$db = new Database();
$db->connect();
if (isset($_POST['update_categories_order']) && $_POST['update_categories_order'] == 1) {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['subcategories']['update'] == 1) {
        $row_order =  $db->escapeString($fn->xss_clean($_POST["row_order"]));
        $id_ary = explode(",", $row_order);
        for ($i = 0; $i < count($id_ary); $i++) {
            $sql = "UPDATE `subcategory` SET row_order='" . $i . "' WHERE id=" . $id_ary[$i];
            $db->sql($sql);
            $res = $db->getResult();
        }
        echo "<p class='alert alert-success'>Sub-categories order updated!</p>";
        return false;
    } else {
        echo "<p class='alert alert-danger'>You have no permission to update sub-categories order</p>";
        return false;
    }
}
?>
<?php include "header.php"; ?>
<html>

<head>
    <title>Sub Categories Orders | <?= $settings['app_name'] ?> - Dashboard</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        #sortable-row li {
            margin-bottom: 4px;
            padding: 10px;
            background-color: #fff;
            cursor: move;
        }

        #sortable-row li.ui-state-highlight {
            height: 1.0em;
            background-color: #F0F0F0;
            border: #ccc 2px dotted;
        }

        #sortable-row-2 li {
            margin-bottom: 4px;
            padding: 10px;
            background-color: #fff;
            cursor: move;
        }

        #sortable-row-2 li.ui-state-highlight {
            height: 1.0em;
            background-color: #F0F0F0;
            border: #ccc 2px dotted;
        }
    </style>
</head>
</body>
<?php
$sql = "SELECT * FROM `subcategory` ORDER BY `id` DESC";
$db->sql($sql);
$categories = $db->getResult();
if ((!isset($_GET['subcategories_id']) && ($_GET['subcategories_id'] == ''))) {
    $sql = "SELECT * FROM `subcategory`  ORDER BY `row_order` ASC";
    $db->sql($sql);
    $res = $db->getResult();
}
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <div class="container">
        <h2>Sub-categories Order</h2>
        <hr>
        <div class='row'>
            <div class="col-md-6 col-sm-12 col-xs-12 refresh">
                <form id="category_form" method="POST" action="subcategories-order.php" data-parsley-validate class="form-horizontal form-label-left">
                    <input type="hidden" id="update_categories_order" name="update_categories_order" required value='1' />
                    <div class="form-group" style="overflow: scroll;height: 269px;overflow-x: hidden;">
                        <input type="hidden" name="row_order" id="row_order" required readonly />
                        <ol id="sortable-row">
                            <?php foreach ($res as $category) { ?>
                                <li id=<?php echo $category["id"]; ?>>
                                    <?php if (!empty($category["image"])) {
                                        echo "<big>" . $category["row_order"] . ".</big> &nbsp;<img src='$category[image]' height=30 > " . $category["name"];
                                    } else {
                                        echo "<big>" . $category["row_order"] . ".</big> &nbsp;<img src='images/logo.png' height=30 > " . $category["name"];
                                    } ?>
                                </li>
                            <?php } ?>
                        </ol>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <button type="submit" id="submit_btn" class="btn btn-success">Save Order</button>
                        </div>
                    </div>
                    <div class="row">
                        <div id="result"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div><!-- /.content-wrapper -->
</body>

</html>
<?php include "footer.php"; ?>
<!-- jQuery -->
<!-- <script src=""></script> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
<script>
    $(function() {
        $("#sortable-row").sortable({
            placeholder: "ui-state-highlight"
        });
        $("#sortable-row-2").sortable({
            placeholder: "ui-state-highlight"
        });
    });
</script>
<script>
    $('#category_form').on('submit', function(e) {
        e.preventDefault();
        var selectedLanguage = new Array();
        $('ol#sortable-row li').each(function() {
            selectedLanguage.push($(this).attr("id"));
        });
        $("#row_order").val(selectedLanguage);
        var formData = new FormData(this);
        if ($("#category_form").validate().form()) {
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
                    $('#result').show().delay(5000).fadeOut();
                    $('#submit_btn').html('Save Order');
                }
            });
        }
    });
</script>