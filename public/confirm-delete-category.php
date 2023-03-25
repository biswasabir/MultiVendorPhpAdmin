<div id="content" class="container col-md-12">
    <?php
    include_once('includes/custom-functions.php');
    $fn = new custom_functions;

    if (isset($_POST['btnDelete'])) {
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
                window.location.href = "categories.php";
            </script>
    <?php
        }

        $sql_query = "SELECT image FROM category WHERE id =" . $ID;
        $db->sql($sql_query);
        $res = $db->getResult();
        unlink($res[0]['image']);
        $sql_query = "DELETE FROM category WHERE id =" . $ID;
        $db->sql($sql_query);
        $delete_category_result = $db->getResult();
        if (!empty($delete_category_result)) {
            $delete_category_result = 0;
        } else {
            $delete_category_result = 1;
        }

        $sql_query = "SELECT image FROM subcategory WHERE category_id =" . $ID;
        $db->sql($sql_query);
        $res = $db->getResult();
        if (!empty($res)) {
            $delete = unlink($res[0]['image']);
            $sql_subcategory = "SELECT id FROM subcategory WHERE category_id=" . $ID;
            $db->sql($sql_subcategory);
            $res_subcategory = $db->getResult();
            $sql_query = "DELETE FROM subcategory WHERE category_id =" . $ID;
            $db->sql($sql_query);
            $delete_subcategory_result = $db->getResult();
            if (!empty($delete_subcategory_result)) {
                $delete_subcategory_result = 0;
            } else {
                $delete_subcategory_result = 1;
            }

            // get image file from table
            $sql_query = "SELECT image,other_images FROM products WHERE subcategory_id =" . $res_subcategory[0]['id'];
            $db->sql($sql_query);
            $res = $db->getResult();
            // delete all menu image files from directory
            foreach ($res as $row) {
                unlink($res[0]['image']);
            }
        }
        //delete image file from directory

        // delete data from menu table
        $sql_query = "DELETE FROM products WHERE category_id =" . $ID;
        $db->sql($sql_query);
        $sql_query = "DELETE FROM seller_commission WHERE category_id =" . $ID;
        $db->sql($sql_query);
        $delete_product_result = $db->getResult();

        if (!empty($delete_product_result)) {
            $delete_product_result = 0;
        } else {
            $delete_product_result = 1;
        }
        if ($delete_category_result == 1  && $delete_product_result = 1) {
            $sql = "SELECT categories,id FROM `seller` where find_in_set($ID,categories)";
            $db->sql($sql);
            $result = $db->getResult();
            foreach ($result as $row) {
                $category_ids = explode(',', $row['categories']);
                if (($key = array_search($ID, $category_ids)) !== false) {
                    unset($category_ids[$key]);
                }

                $category_ids = implode(',', $category_ids);

                $sql = "UPDATE `seller` SET `categories` = '$category_ids' WHERE id=" . $row['id'];
                $db->sql($sql);
            }
            header("location: categories.php");
        }
    }

    if (isset($_POST['btnNo'])) {
        header("location: categories.php");
    }
    if (isset($_POST['btncancel'])) {
        header("location: categories.php");
    }

    ?>
    <h1>Confirm Action</h1>
    <?php
    if ($permissions['categories']['delete'] == 1) { ?>
        <hr />
        <form method="post">
            <p>Are you sure want to delete this category?All the Subcategories and products will also be Deleted.</p>
            <input type="submit" class="btn btn-primary" value="Delete" name="btnDelete" />
            <input type="submit" class="btn btn-danger" value="Cancel" name="btnNo" />
        </form>
        <div class="separator"> </div>
    <?php } else { ?>
        <div class="alert alert-danger topmargin-sm">You have no permission to delete category.</div>
        <form method="post">
            <input type="submit" class="btn btn-danger" value="Back" name="btncancel" />
        </form>
    <?php } ?>
</div>

<?php $db->disconnect(); ?>