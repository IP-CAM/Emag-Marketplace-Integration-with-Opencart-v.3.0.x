<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/setting.png" alt="" /> eMAG Marketplace Call Logs</h1>
      <div class="buttons"><a onclick="location = 'index.php?route=module/emagmarketplace&page=characteristics&token=<?php echo $token; ?>';" class="button">&laquo; Previous step</a>&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="uploadProducts()" class="button">Re-upload Products</a></div>
    </div>
    <div class="content">
        <table class="list">
          <thead>
            <tr>
              <td class="left">Resource</td>
              <td class="left">Action</td>
              <td class="left">Status</td>
              <td class="left">Message</td>
              <td class="left">Date Created</td>
              <td class="left">Date Sent</td>
			  <td class="left">Action</td>
            </tr>
          </thead>
          <tbody>
		    <tr class="filter">
              <td><input type="text" name="filter_resource" value="<?php echo $filter_resource; ?>" /></td>
              <td><input type="text" name="filter_action" value="<?php echo $filter_action; ?>" /></td>
              <td><input type="text" name="filter_status" value="<?php echo $filter_status; ?>" /></td>
              <td><input type="text" name="filter_message" value="<?php echo $filter_message; ?>" /></td>
			  <td></td>
			  <td></td>
              <td align="right"><a onclick="filter();" class="button">Filter</a></td>
            </tr>
            <?php if ($call_logs) { ?>
            <?php foreach ($call_logs as $call_log) { ?>
            <tr>
              <td class="left"><?php echo $call_log['resource']; ?></td>
              <td class="left"><?php echo $call_log['action']; ?></td>
              <td class="left"><?php echo $call_log['status']; ?></td>
			  <td class="left"><?php echo $call_log['message']; ?></td>
			  <td class="left"><?php echo $call_log['date_created']; ?></td>
			  <td class="left"><?php echo $call_log['date_sent']; ?></td>
			  <td class="left"></td>
            </tr>
            <?php } ?>
            <?php } else { ?>
            <tr>
              <td class="center" colspan="7">No results found</td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
		 <div class="pagination"><?php echo $pagination; ?></div>
    </div>
  </div>
</div>
<script type="text/javascript"><!--
function filter() {
	url = 'index.php?route=module/emagmarketplace&page=call_logs&token=<?php echo $token; ?>';
	
	var filter_resource = $('input[name=\'filter_resource\']').attr('value');
	
	if (filter_resource) {
		url += '&filter_resource=' + encodeURIComponent(filter_resource);
	}
	
	var filter_action = $('input[name=\'filter_action\']').attr('value');
	
	if (filter_action) {
		url += '&filter_action=' + encodeURIComponent(filter_action);
	}
	
	var filter_status = $('input[name=\'filter_status\']').attr('value');
	
	if (filter_status) {
		url += '&filter_status=' + encodeURIComponent(filter_status);
	}
	
	var filter_message = $('input[name=\'filter_message\']').attr('value');
	
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
			$('.success, .warning, .attention, .error').remove();
			
			$('.box').before('<div class="attention"><img src="view/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
		},			
		success: function(json) {
			$('.success, .warning, .attention, .error').remove();
			
			// Check for errors
			if (json['error']) {
				alert('The following error occured: ' + json['error']);
			} else {
				alert('Products have been successfully added to upload queue!');
				location.href = 'index.php?route=module/emagmarketplace&page=call_logs&token=<?php echo $token; ?>';
			}

			if (json['success']) {
				$('.box').before('<div class="success" style="display: none;">' + json['success'] + '</div>');
				
				$('.success').fadeIn('slow');			
			}	
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}
//--></script>
<?php echo $footer; ?>