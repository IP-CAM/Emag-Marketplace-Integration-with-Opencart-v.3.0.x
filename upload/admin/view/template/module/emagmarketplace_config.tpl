<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <?php if ($success) { ?>
  <div class="success"><?php echo $success; ?></div>
  <?php } ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/setting.png" alt="" /> eMAG Marketplace Main Config</h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><?php echo $button_cancel; ?></a>&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="location = 'index.php?route=module/emagmarketplace&page=categories&token=<?php echo $token; ?>';" class="button">Next step &raquo;</a></div>
    </div>
    <div class="content">
      <div id="tabs" class="htabs"><a href="#tab-identity">Identity</a><a href="#tab-products-orders">Products & Orders</a><a href="#tab-shipping">Shipping</a><a href="#tab-cron-jobs">Cron Jobs</a></div>
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
        <div id="tab-identity">
          <table class="form">
            <tr>
              <td><span class="required">*</span> Marketplace URL<br /><span class="help">Homepage address of the eMAG Marketplace website</span></td>
              <td><input type="text" name="emagmp_url" value="<?php echo $emagmp_url; ?>" size="40" />
                <?php if ($error_emagmp_url) { ?>
                <span class="error"><?php echo $error_emagmp_url; ?></span>
                <?php } ?></td>
            </tr>
            <tr>
              <td><span class="required">*</span> API URL<br /><span class="help">Main web address of the API</span></td>
              <td><input type="text" name="emagmp_api_url" value="<?php echo $emagmp_api_url; ?>" size="40" />
                <?php if ($error_emagmp_api_url) { ?>
                <span class="error"><?php echo $error_emagmp_api_url; ?></span>
                <?php } ?></td>
            </tr>
            <tr>
              <td><span class="required">*</span> API Vendor Code<br /><span class="help">Your API Vendor Code</span></td>
              <td><input type="text" name="emagmp_vendorcode" value="<?php echo $emagmp_vendorcode; ?>" size="40" />
                <?php if ($error_emagmp_vendorcode) { ?>
                <span class="error"><?php echo $error_emagmp_vendorcode; ?></span>
                <?php } ?></td>
            </tr>
            <tr>
              <td><span class="required">*</span> API Vendor Username<br /><span class="help">Your API Vendor Username</span></td>
              <td><input type="text" name="emagmp_vendorusername" value="<?php echo $emagmp_vendorusername; ?>" size="40" />
                <?php if ($error_emagmp_vendorusername) { ?>
                <span class="error"><?php echo $error_emagmp_vendorusername; ?></span>
                <?php } ?></td>
            </tr>
            <tr>
              <td><span class="required">*</span> API Password<br /><span class="help">Your API Vendor Password</span></td>
              <td><input type="text" name="emagmp_vendorpassword" value="<?php echo $emagmp_vendorpassword; ?>" size="40" />
                <?php if ($error_emagmp_vendorpassword) { ?>
                <span class="error"><?php echo $error_emagmp_vendorpassword; ?></span>
                <?php } ?></td>
            </tr>
          </table>
		  <a onclick="testEmagMarketplaceConnection()" class="button">Test connection</a>
        </div>
        <div id="tab-products-orders">
          <table class="form">
            <tr>
              <td><span class="required">*</span> Product Queue Limit<br /><span class="help">How many product updates to send per minute</span></td>
              <td><input type="text" name="emagmp_product_queue_limit" value="<?php echo $emagmp_product_queue_limit; ?>" />
                <?php if ($error_emagmp_product_queue_limit) { ?>
                <span class="error"><?php echo $error_emagmp_product_queue_limit; ?></span>
                <?php } ?></td>
            </tr>
			<tr>
              <td><span class="required">*</span> Product Option Required<br /><span class="help">Generate unique product combinations based on required product options only</span></td>
              <td><?php if ($emagmp_product_option_required) { ?>
                <input type="radio" name="emagmp_product_option_required" value="1" checked="checked" />
                Yes
                <input type="radio" name="emagmp_product_option_required" value="0" />
                No
                <?php } else { ?>
                <input type="radio" name="emagmp_product_option_required" value="1" />
                Yes
                <input type="radio" name="emagmp_product_option_required" value="0" checked="checked" />
                No
                <?php } ?></td>
            </tr>
            <tr>
              <td><span class="required">*</span> Product Warranty<br /><span class="help">Default warranty (in months) to send for all products</span></td>
              <td><input type="text" name="emagmp_product_warranty" value="<?php echo $emagmp_product_warranty; ?>" />
                <?php if ($error_emagmp_product_warranty) { ?>
                <span class="error"><?php echo $error_emagmp_product_warranty; ?></span>
                <?php } ?></td>
            </tr>
			<tr>
              <td><span class="required">*</span> Product Language<br /><span class="help">The language used to send the product documentation in</span></td>
              <td><select name="emagmp_product_language_id">
                  <?php foreach ($languages as $language) { ?>
                  <?php if ($language['language_id'] == $emagmp_product_language_id) { ?>
                  <option value="<?php echo $language['language_id']; ?>" selected="selected"><?php echo $language['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $language['language_id']; ?>"><?php echo $language['name']; ?></option>
                  <?php } ?>
                  <?php } ?>
                </select></td>
            </tr>
            <tr>
              <td><span class="required">*</span> Initial Order Status<br /><span class="help">The default order status used to import orders with</span></td>
              <td><select name="emagmp_order_state_id_initial">
                  <?php foreach ($order_statuses as $order_status) { ?>
                  <?php if ($order_status['order_status_id'] == $emagmp_order_state_id_initial) { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                  <?php } ?>
                  <?php } ?>
                </select></td>
            </tr>
			<tr>
              <td><span class="required">*</span> Finalized Order Status<br /><span class="help">The order status that marks the order as finalized</span></td>
              <td><select name="emagmp_order_state_id_finalized">
                  <?php foreach ($order_statuses as $order_status) { ?>
                  <?php if ($order_status['order_status_id'] == $emagmp_order_state_id_finalized) { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                  <?php } ?>
                  <?php } ?>
                </select></td>
            </tr>
			<tr>
              <td><span class="required">*</span> Cancelled Order Status<br /><span class="help">The order status that marks the order as cancelled</span></td>
              <td><select name="emagmp_order_state_id_cancelled">
                  <?php foreach ($order_statuses as $order_status) { ?>
                  <?php if ($order_status['order_status_id'] == $emagmp_order_state_id_cancelled) { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                  <?php } ?>
                  <?php } ?>
                </select></td>
            </tr>
          </table>
        </div>
        <div id="tab-shipping">
          <table class="form">
            <tr>
              <td><span class="required">*</span> Handling Time<br /><span class="help">How many days until delivery of goods to the customer (0 = same day delivery)</span></td>
              <td><input type="text" name="emagmp_handling_time" value="<?php echo $emagmp_handling_time; ?>" size="40" />
                <?php if ($error_emagmp_handling_time) { ?>
                <span class="error"><?php echo $error_emagmp_handling_time; ?></span>
                <?php } ?></td>
            </tr>
            <tr>
              <td><span class="required">*</span> Use eMAG AWB<br /><span class="help">Use the eMAG AWB system to generate dispatch documents and mark the orders as finalized</span></td>
              <td><?php if ($emagmp_use_emag_awb) { ?>
                <input type="radio" name="emagmp_use_emag_awb" value="1" checked="checked" />
                Yes
                <input type="radio" name="emagmp_use_emag_awb" value="0" />
                No
                <?php } else { ?>
                <input type="radio" name="emagmp_use_emag_awb" value="1" />
                Yes
                <input type="radio" name="emagmp_use_emag_awb" value="0" checked="checked" />
                No
                <?php } ?></td>
            </tr>
			<tr>
              <td><span class="required">*</span> AWB Sender Name<br /><span class="help">Sender name to use with the eMAG Marketplace AWB</span></td>
              <td><input type="text" name="emagmp_awb_sender_name" value="<?php echo $emagmp_awb_sender_name; ?>" size="40" />
                <?php if ($error_emagmp_awb_sender_name) { ?>
                <span class="error"><?php echo $error_emagmp_awb_sender_name; ?></span>
                <?php } ?></td>
            </tr>
			<tr>
              <td><span class="required">*</span> AWB Sender Contact<br /><span class="help">Sender contact name to use with the eMAG Marketplace AWB</span></td>
              <td><input type="text" name="emagmp_awb_sender_contact" value="<?php echo $emagmp_awb_sender_contact; ?>" size="40" />
                <?php if ($error_emagmp_awb_sender_contact) { ?>
                <span class="error"><?php echo $error_emagmp_awb_sender_contact; ?></span>
                <?php } ?></td>
            </tr>
			<tr>
              <td><span class="required">*</span> AWB Sender Phone<br /><span class="help">Sender phone number to use with the eMAG Marketplace AWB</span></td>
              <td><input type="text" name="emagmp_awb_sender_phone" value="<?php echo $emagmp_awb_sender_phone; ?>" size="40" />
                <?php if ($error_emagmp_awb_sender_phone) { ?>
                <span class="error"><?php echo $error_emagmp_awb_sender_phone; ?></span>
                <?php } ?></td>
            </tr>
			<tr>
              <td><span class="required">*</span> AWB Sender Locality<br /><span class="help">Sender locality to use with the eMAG Marketplace AWB</span></td>
              <td><input type="text" name="emagmp_awb_sender_locality" value="<?php echo $emagmp_awb_sender_locality; ?>" size="40" />
                <?php if ($error_emagmp_awb_sender_locality) { ?>
                <span class="error"><?php echo $error_emagmp_awb_sender_locality; ?></span>
                <?php } ?></td>
            </tr>
			<tr>
              <td><span class="required">*</span> AWB Sender Street<br /><span class="help">Sender street address to use with the eMAG Marketplace AWB</span></td>
              <td><input type="text" name="emagmp_awb_sender_street" value="<?php echo $emagmp_awb_sender_street; ?>" size="40" />
                <?php if ($error_emagmp_awb_sender_street) { ?>
                <span class="error"><?php echo $error_emagmp_awb_sender_street; ?></span>
                <?php } ?></td>
            </tr>
          </table>
        </div>
		<div id="tab-cron-jobs">
          Install the following Cron jobs on your server:<br />
		  <pre><?php foreach ($cron_jobs as $command) {
		  	echo $command."\n";
			} ?></pre>
        </div>
      </form>
    </div>
  </div>
</div>
<script type="text/javascript"><!--
$('#tabs a').tabs();

function testEmagMarketplaceConnection() {
	$.ajax({
		url: 'index.php?route=module/emagmarketplace&page=test_connection&token=<?php echo $token; ?>',
		type: 'post',
		data: $("#form").serialize(),
		dataType: 'json',	
		beforeSend: function() {
			$('.success, .warning, .attention, .error').remove();
			
			$('.box').before('<div class="attention"><img src="view/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
		},			
		success: function(json) {
			$('.success, .warning, .attention, .error').remove();
			
			// Check for errors
			if (json['error']) {
				alert(json["error"]);
			} else {
				
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

$('input[name=\'emagmp_awb_sender_locality\']').autocomplete({
	delay: 1,
	source: function(request, response) {
		$.ajax({
			url: 'index.php?route=module/emagmarketplace&page=autocomplete_locality&token=<?php echo $token; ?>&keyword=' +  encodeURIComponent(request.term),
			dataType: 'json',
			success: function(json) {
				if (json['warning_localities']) {
					if (confirm(json['warning_localities'])) {
						downloadEmagMarketplaceLocalities();
					}
				}
				response($.map(json, function(item) {
					return {
						label: item['name'],
						value: item['name'],
						id: item['id']
					}
				}));
			}
		});
	}, 
	select: function(event, ui) { 
		$('input[name=\'emagmp_awb_sender_locality\']').attr('value', ui.item['id']);
	}
});

function downloadEmagMarketplaceLocalities() {
	$.ajax({
		url: 'index.php?route=module/emagmarketplace&page=download_localities&token=<?php echo $token; ?>',
		type: 'post',
		data: { },
		dataType: 'json',
		beforeSend: function() {
			$('.success, .warning, .attention, .error').remove();
			
			$('.box').before('<div class="attention"><img src="view/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
		},			
		success: function(json) {
			$('.success, .warning, .attention, .error').remove();
			
			// Check for errors
			if (json['error']) {
				alert(json["error"]);
			} else {
				alert(json['success']);
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