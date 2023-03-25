<div id="content" class="container col-md-12">
    <?php
    include_once('includes/custom-functions.php');
    $fn = new custom_functions;
    if (isset($_POST['btnDelete'])) {
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
            return false;
        }
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $ID = $db->escapeString($fn->xss_clean($_GET['id']));
        } else { ?>
            <script>
                alert("Please add atleast one varient for product");
                window.location.href = "products.php";
            </script>
    <?php
        }


        $product_id = $fn->get_product_id_by_variant_id($ID);

        $result = $fn->delete_product($product_id);
        if (!empty($result)) {
            header("location: products.php");
        } else {
            echo "<hr><label class='btn btn-warning'>Found some order items which has return request.Finalize those before deleting it.</label>";
        }
    }

    if (isset($_POST['btnNo'])) {
        header("location: products.php");
    }
    if (isset($_POST['btncancel'])) {
        header("location: products.php");
    }

    ?>
    <?php
    if ($permissions['products']['delete'] == 1) { ?>
        <h1>Confirm Action</h1>
        <hr />
        <form method="post">
            <p>Are you sure want to delete this Product? related product data will also deleted.</p>
            <input type="submit" class="btn btn-primary" value="Delete" name="btnDelete" />
            <input type="submit" class="btn btn-danger" value="Cancel" name="btnNo" />
        </form>
        <div class="separator"> </div>
    <?php } else { ?>
        <div class="alert alert-danger topmargin-sm" style="margin-top: 20px;">You have no permission to delete product.</div>
        <form method="post">
            <input type="submit" class="btn btn-danger" value="Back" name="btncancel" />
        </form>

    <?php } ?>
</div>

<?php $db->disconnect(); ?>