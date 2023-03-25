<?php
include_once('../includes/functions.php');
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.16.0/jquery.validate.min.js"></script>
<section class="content-header">
    <h1>Seller Withdrawal Requests /<small><a href="home.php"><i class="fa fa-home"></i> Home</a></small></h1>
    <ol class="breadcrumb">
		<a class="btn btn-block btn-default" href="send-withdrawal-request.php"><i class="fa fa-plus-square"></i> Send Withdrawal Request</a>
	</ol>
</section>
<!-- Main content -->
<section class="content">
    <!-- Main row -->
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                    <div class="box-header">
                      
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-hover" data-toggle="table" id="withdrawal-requests" data-url="get-bootstrap-table-data.php?table=withdrawal-requests" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="false" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-query-params="queryParams_1">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="type_id" data-sortable="true" data-visible="false">Type ID</th>
                                    <th data-field="type" data-sortable="true">Type</th>
                                    <th data-field="name" >Name</th>
                                    <th data-field="amount" data-sortable="true">Amount</th>
                                    <th data-field="balance" >Balance</th>
                                    <th data-field="message" data-sortable="true">Message</th>
                                    <th data-field="status" data-sortable="true">Status</th>
                                    <th data-field="date_created" data-sortable="true">Date Created</th>
                                    <th data-field="last_updated" data-sortable="true" data-visible="false">Last Updated</th>
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