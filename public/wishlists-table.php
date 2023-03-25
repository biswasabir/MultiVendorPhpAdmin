<?php
include_once('includes/functions.php');
?>
<section class="content-header">
	<h1>Wishlists /<small><a href="home.php"><i class="fa fa-home"></i> Home</a></small></h1>
</section>
<?php
if ($permissions['customers']['read'] == 1) {
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
						<h3 class="box-title">Wishlists</h3>
					</div>
					<div class="box-body table-responsive">
						<table class="table table-hover" data-toggle="table" id="wishlists_list" data-url="api-firebase/get-bootstrap-table-data.php?table=wishlists" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="total_qty" data-sort-order="desc" data-query-params="queryParams_1">
							<thead>
								<tr>
									<th data-field="id" data-sortable="true">ID</th>
									<th data-field="product_id" data-sortable="true" data-visible="false">Product ID</th>
									<th data-field="product_name" data-sortable="true" >Product</th>
									<th data-field="total_qty" data-sortable="true">Quantity</th>
									<th data-field="seller_name" data-sortable="true">Seller</th>
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
	
<?php } else { ?>
	<div class="alert alert-danger topmargin-sm" style="margin-top: 20px;">You have no permission to view areas.</div>
<?php } ?>

<script>
    // $('#filter_user').on('change', function() {
    //     $('#user_table').bootstrapTable('refresh');

    // });

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