<div id="content" class="container col-md-12">
    <?php
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    // $ID = (isset($_GET['id'])) ? $db->escapeString($fn->xss_clean($_GET['id'])) : "";
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $ID = $db->escapeString($fn->xss_clean($_GET['id']));
    } else { ?>
        <script>
            alert("Something went wrong, No data available.");
            window.location.href = "sellers.php";
        </script>
    <?php
    }
    $result = $fn->get_data($columns = ['status'], "id=" . $ID, 'seller');
    $status = 0;
   
    if (isset($_POST['btnNo'])) {
        header("location: sellers.php");
    }
    ?>
    <h1>Confirm Action</h1>
    <hr />
    <form method="post">
    <?php
    if ($permissions['sellers']['delete'] == 1) { ?>
        <p>Are you sure want to remove this Seller?</p>
        <?php
        if ($result[0]['status'] == 7) {
        ?>
            <input type="submit" class="btn btn-primary" value="Restore" name="btnRestore" id="btnRestore" />
        <?php } else { ?>
            <input type="submit" class="btn btn-primary" value="Remove" name="btnRemove" id="btnRemove" />
        <?php } ?>
        <input type="submit" class="btn btn-primary" value="Permanently Delete" name="btnDelete" id="btnDelete" />
        <!-- <a class="btn-xs btn-danger" href="remove-seller.php?id=' . $row['id'] . '"><i class="fa fa-trash-o"></i>Remove</a> -->
        <input type="submit" class="btn btn-danger" value="Cancel" name="btnNo" />
    </form>
    <div class="separator"> </div>
    <?php } else { ?>
        <div class="alert alert-danger topmargin-sm" style="margin-top: 20px;">You have no permission to remove Seller.</div>
        <form method="post">
            <input type="submit" class="btn btn-danger" value="Back" name="btnNo" />
        </form>

    <?php } ?>
</div>

<?php $db->disconnect(); ?>

<script>
    $('#btnDelete').on('click', function(e) {
        e.preventDefault();
        var s_id = '<?= $ID ?>';
        if (confirm("Are you sure? you want to remove this Seller. All data related to seller will also be deleted")) {
            $.ajax({
                url: 'public/db-operation.php',
                type: "POST",
                data: 'seller_id=' + s_id + '&delete_seller=1',
                success: function(result) {
                    if (result == 1) {
                        window.location = "sellers.php";
                    } else {
                        alert('Error! Seller could not be deleted.Either found some order items which has return request.Finalize those before deleting it.');
                    }
                }
            });
        }
    });
    $('#btnRemove').on('click', function(e) {
        e.preventDefault();
        var s_id = '<?= $ID ?>';
        var status = '<?= $status ?>';
        
            if (confirm("Are you sure? you want to remove this Seller")) {
                $.ajax({
                    url: 'public/db-operation.php',
                    type: "POST",
                    data: 'seller_id=' + s_id + '&remove_seller=1&type=trashed',
                    success: function(result) {
                        if (result == 1) {
                            window.location = "sellers.php";
                        } else {
                            alert('Error! Seller could not be removed.');
                        }
                    }
                });
            }

    });
    $('#btnRestore').on('click', function(e) {
        e.preventDefault();
        var s_id = '<?= $ID ?>';
        
        if (confirm("Are you sure? you want to restore this Seller")) {
            $.ajax({
                url: 'public/db-operation.php',
                type: "POST",
                data: 'seller_id=' + s_id + '&remove_seller=1&type=restore',
                success: function(result) {
                    if (result == 1) {
                        window.location = "sellers.php";
                    } else {
                        alert('Error! Product could not be deactivated.');
                    }
                }
            });
        }

    });
</script>