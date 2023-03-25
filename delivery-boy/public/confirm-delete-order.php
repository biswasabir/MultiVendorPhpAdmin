<div id="content" class="container col-md-12">
	<?php 
	include_once('../includes/custom-functions.php');
	$fn = new custom_functions;
		if(isset($_POST['btnDelete'])){
			
			if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
				echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
				return false;
			}
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $ID = $db->escapeString($fn->xss_clean($_GET['id']));
            } else { ?>
                <script>
                    alert("Something went wrong, No data available.");
                    window.location.href = "orders.php";
                </script>
            <?php
            }
            $result = $fn->delete_order_item($ID);
			// if delete data success back to pemesanan page
            if ($result == true) {
				header("location: orders.php");
			}else {
                echo "<hr><label class='btn btn-warning'>Found some order items which has return request.Finalize those before deleting it.</label>";
            }
		}
		if(isset($_POST['btnNo'])){
			header("location: orders.php");
		}


	?>
	<h1>Confirm Action</h1>
	<hr />
	<form method="post">
		<p>Are you sure want to delete this order?</p>
		<input type="submit" class="btn btn-primary" value="Delete" name="btnDelete"/>
		<input type="submit" class="btn btn-danger" value="Cancel" name="btnNo"/>
	</form>
	<div class="separator"> </div>
</div>
			
<?php $db->disconnect(); ?>