<?php
include_once('../includes/functions.php');
?>
<section class="content-header">
	<h1>Cities /<small><a href="home.php"><i class="fa fa-home"></i> Home</a></small></h1>
	<!-- <ol class="breadcrumb">
		<a class="btn btn-block btn-default" href="add-city.php"><i class="fa fa-plus-square"></i> Add New City</a>
	</ol> -->
</section>

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
						<h3 class="box-title">Cities</h3>
					</div>
					

					<div class="box-body table-responsive">
						
						<table class="table table-hover" data-toggle="table" id="areas_list" data-url="get-bootstrap-table-data.php?table=cities" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-query-params="queryParams_1">
							<thead>
								<tr>
									<th data-field="id" data-sortable="true">ID</th>
									<th data-field="name" data-sortable="true">Name</th>
									<th data-field="status" data-sortable="true">Status</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
			<div class="separator"> </div>
		</div>
	</section>
	<script>
		$('#filter_area').on('change', function() {
			$('#areas_list').bootstrapTable('refresh');

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
