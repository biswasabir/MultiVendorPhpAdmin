<style>
    .btn {
        padding: 9px 12px;
        line-height: 0.42857143;
    }
</style>
<section class="content-header">
    <h1>Transactions /<small><a href="home.php"><i class="fa fa-home"></i> Home</a></small></h1>
</section>


<!-- Main content -->
<section class="content">
    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <div class="col-xs-12">
            <?php if ($permissions['customers']['read'] == 1) { ?>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Transactions</h3>
                    </div><!-- /.box-header -->
                    <div class="box-body table-responsive">
                        <table class="table table-hover" data-toggle="table" id="transaction_list" data-url="api-firebase/get-bootstrap-table-data.php?table=transactions" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="user_name" data-sortable="true">name</th>
                                    <th data-field="order_id" data-sortable="true">Order Id</th>

                                    <th data-field="type" data-sortable="true">Type</th>
                                    <th data-field="txn_id" data-sortable="true">Txn_id</th>
                                    <th data-field="amount" data-sortable="true">Amount</th>
                                    <th data-field="message" data-sortable="true">Message</th>
                                    <th data-field="transaction_date" data-sortable="true">transaction_date</th>
                                    <th data-field="status" data-sortable="true">Status</th>
                                </tr>
                            </thead>
                        </table>
                    </div><!-- /.box-body -->
                </div><!-- /.box -->
            <?php } else { ?>
                <div class="alert alert-danger topmargin-sm" style="margin-top: 20px;">You have no permission to view transactions</div>
        </div>
        <!-- right col (We are only adding the ID to make the widgets sortable)-->
    </div><!-- /.row (main row) -->
</section><!-- /.content -->
<?php }
            $db->disconnect();
?>


<script>
    function queryParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>