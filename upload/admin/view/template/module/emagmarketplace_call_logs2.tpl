<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right"><a href="index.php?route=module/emagmarketplace&page=characteristics&token=<?php echo $token; ?>" data-toggle="tooltip" title="Previous step" class="btn btn-default"><i class="fa fa-arrow-left"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" data-toggle="tooltip" id="upload_products_button" title="Re-upload Products" class="btn btn-primary" onclick="uploadProducts()"><i class="fa fa-upload"></i></button></div>
      <h1>eMAG Marketplace</h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-list"></i> eMAG Marketplace Call Logs</h3>
      </div>
      <div class="panel-body">
	  	<div class="well">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="input-filter_resource">Resource</label>
                <input type="text" name="filter_resource" value="<?php echo $filter_resource; ?>" placeholder="Resource" id="input-filter_resource" class="form-control" />
              </div>
              <div class="form-group">
                <label class="control-label" for="input-filter_action">Action</label>
                <input type="text" name="filter_action" value="<?php echo $filter_action; ?>" placeholder="Action" id="input-filter_action" class="form-control" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="input-filter_status">Status</label>
                <input type="text" name="filter_status" value="<?php echo $filter_status; ?>" placeholder="Status" id="input-filter_status" class="form-control" />
              </div>
              <div class="form-group">
                <label class="control-label" for="input-filter_message">Message</label>
                <input type="text" name="filter_message" value="<?php echo $filter_message; ?>" placeholder="Message" id="input-filter_message" class="form-control" />
              </div>
            </div>
            <div class="col-sm-4">
              <button type="button" id="button-filter" class="btn btn-primary pull-right" onClick="filter()"><i class="fa fa-search"></i> Filter</button>
            </div>
          </div>
        </div>
        <form method="post" enctype="multipart/form-data" id="form-category">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
				  <td class="text-left">Resource</td>
				  <td class="text-left">Action</td>
				  <td class="text-left">Status</td>
				  <td class="text-left">Message</td>
				  <td class="text-left">Date Created</td>
				  <td class="text-left">Date Sent</td>
                </tr>
              </thead>
              <tbody>
                <?php if ($call_logs) { ?>
                <?php foreach ($call_logs as $call_log) { ?>
                <tr>
				  <td class="text-left"><?php echo $call_log['resource']; ?></td>
				  <td class="text-left"><?php echo $call_log['action']; ?></td>
				  <td class="text-left"><?php echo $call_log['status']; ?></td>
				  <td class="text-left"><?php echo $call_log['message']; ?></td>
				  <td class="text-left"><?php echo $call_log['date_created']; ?></td>
				  <td class="text-left"><?php echo $call_log['date_sent']; ?></td>
                </tr>
                <?php } ?>
                <?php } else { ?>
                <tr>
                  <td class="text-center" colspan="4"><?php echo $text_no_results; ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </form>
		<div class="row">
          <div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
        </div>
      </div>
    </div>
  </div>
  <script type="text/javascript"><!--
function filter() {
	url = 'index.php?route=module/emagmarketplace&page=call_logs&token=<?php echo $token; ?>';
	
	var filter_resource = $('input[name=\'filter_resource\']').val();
	
	if (filter_resource) {
		url += '&filter_resource=' + encodeURIComponent(filter_resource);
	}
	
	var filter_action = $('input[name=\'filter_action\']').val();
	
	if (filter_action) {
		url += '&filter_action=' + encodeURIComponent(filter_action);
	}
	
	var filter_status = $('input[name=\'filter_status\']').val();
	
	if (filter_status) {
		url += '&filter_status=' + encodeURIComponent(filter_status);
	}
	
	var filter_message = $('input[name=\'filter_message\']').val();
	
	if (filter_message) {
		url += '&filter_message=' + encodeURIComponent(filter_message);
	}

	location = url;
}
function uploadProducts() {
	if (!confirm('Are you sure you want to re-upload all products now?'))
		return;
	$.ajax({
		url: 'index.php?route=module/emagmarketplace&page=upload_products&token=<?php echo $token; ?>',
		type: 'post',
		data: {
		},
		dataType: 'json',	
		beforeSend: function() {
			$('#upload_products_button').button('loading');
		},
		complete: function() {
			$('#upload_products_button').button('reset');
		},
		success: function(json) {
			// Check for errors
			if (json['error']) {
				alert('The following error occured: ' + json['error']);
			} else {
				alert('Products have been successfully added to upload queue!');
				location.href = 'index.php?route=module/emagmarketplace&page=call_logs&token=<?php echo $token; ?>';
			}
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}
//--></script>
</div>
<?php echo $footer; ?>