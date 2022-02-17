<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-setting" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>&nbsp;&nbsp;&nbsp;&nbsp;<a href="index.php?route=module/emagmarketplace&page=categories&token=<?php echo $token; ?>" data-toggle="tooltip" title="Next step" class="btn btn-default"><i class="fa fa-arrow-right"></i></a></div>
      <h1>eMAG Marketplace</h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> eMAG Marketplace Main Config</h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-setting" class="form-horizontal">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-identity" data-toggle="tab">Identity</a></li>
            <li><a href="#tab-products_orders" data-toggle="tab">Products & Orders</a></li>
            <li><a href="#tab-shipping" data-toggle="tab">Shipping</a></li>
            <li><a href="#tab-cron-jobs" data-toggle="tab">Cron Jobs</a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tab-identity">
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_url"><span data-toggle="tooltip" data-container="#tab-identity" title="Homepage address of the eMAG Marketplace website">Marketplace URL</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_url" value="<?php echo $emagmp_url; ?>" placeholder="Marketplace URL" id="input-emagmp_url" class="form-control" />
                  <?php if ($error_emagmp_url) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_url; ?></div>
                  <?php } ?>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_api_url"><span data-toggle="tooltip" data-container="#tab-identity" title="Main web address of the API">API URL</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_api_url" value="<?php echo $emagmp_api_url; ?>" placeholder="API URL" id="input-emagmp_api_url" class="form-control" />
                  <?php if ($error_emagmp_api_url) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_api_url; ?></div>
                  <?php } ?>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_vendorcode"><span data-toggle="tooltip" data-container="#tab-identity" title="Your API Vendor Code">API Vendor Code</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_vendorcode" value="<?php echo $emagmp_vendorcode; ?>" placeholder="API Vendor Code" id="input-emagmp_vendorcode" class="form-control" />
                  <?php if ($error_emagmp_vendorcode) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_vendorcode; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_vendorusername"><span data-toggle="tooltip" data-container="#tab-identity" title="Your API Vendor Username">API Vendor Username</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_vendorusername" value="<?php echo $emagmp_vendorusername; ?>" placeholder="API Vendor Username" id="input-emagmp_vendorusername" class="form-control" />
                  <?php if ($error_emagmp_vendorusername) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_vendorusername; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_vendorpassword"><span data-toggle="tooltip" data-container="#tab-identity" title="Your API Vendor Password">API Vendor Password</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_vendorpassword" value="<?php echo $emagmp_vendorpassword; ?>" placeholder="API Vendor Password" id="input-emagmp_vendorpassword" class="form-control" />
                  <?php if ($error_emagmp_vendorpassword) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_vendorpassword; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <button type="button" form="form-setting" data-toggle="tooltip" id="test_connection_button" title="Test connection" class="btn btn-primary" onclick="testEmagMarketplaceConnection()"><i class="fa fa-refresh"></i>&nbsp;&nbsp;Test connection</button>
            </div>
			<div class="tab-pane" id="tab-products_orders">
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_product_queue_limit"><span data-toggle="tooltip" data-container="#tab-products_orders" title="How many product updates to send per minute">Product Queue Limit</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_product_queue_limit" value="<?php echo $emagmp_product_queue_limit; ?>" placeholder="Product Queue Limit" id="input-emagmp_product_queue_limit" class="form-control" />
                  <?php if ($error_emagmp_product_queue_limit) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_product_queue_limit; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_product_option_required"><span data-toggle="tooltip" data-container="#tab-products_orders" title="Generate unique product combinations based on required product options only">Product Option Required</span></label>
                <div class="col-sm-10">
                   <label class="radio-inline">
                      <?php if ($emagmp_product_option_required) { ?>
                      <input type="radio" name="emagmp_product_option_required" value="1" checked="checked" />
                      Yes
                      <?php } else { ?>
                      <input type="radio" name="emagmp_product_option_required" value="1" />
                      Yes
                      <?php } ?>
                    </label>
                    <label class="radio-inline">
                      <?php if (!$emagmp_product_option_required) { ?>
                      <input type="radio" name="emagmp_product_option_required" value="0" checked="checked" />
                      No
                      <?php } else { ?>
                      <input type="radio" name="emagmp_product_option_required" value="0" />
                      No
                      <?php } ?>
                    </label>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_product_warranty"><span data-toggle="tooltip" data-container="#tab-products_orders" title="Default warranty (in months) to send for all products">Product Warranty</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_product_warranty" value="<?php echo $emagmp_product_warranty; ?>" placeholder="Product Warranty" id="input-emagmp_product_warranty" class="form-control" />
                  <?php if ($error_emagmp_product_warranty) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_product_warranty; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_product_language_id"><span data-toggle="tooltip" data-container="#tab-products_orders" title="The language used to send the product documentation in">Product Language</span></label>
                <div class="col-sm-10">
                  <select name="emagmp_product_language_id" id="input-emagmp_product_language_id" class="form-control">
				  <?php foreach ($languages as $language) { ?>
                  <?php if ($language['language_id'] == $emagmp_product_language_id) { ?>
                  <option value="<?php echo $language['language_id']; ?>" selected="selected"><?php echo $language['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $language['language_id']; ?>"><?php echo $language['name']; ?></option>
                  <?php } ?>
                  <?php } ?>
				  </select>
                  <?php if ($error_emagmp_product_language_id) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_product_language_id; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_order_state_id_initial"><span data-toggle="tooltip" data-container="#tab-products_orders" title="The default order status used to import orders with">Initial Order Status</span></label>
                <div class="col-sm-10">
                  <select name="emagmp_order_state_id_initial" id="input-emagmp_order_state_id_initial" class="form-control">
				  <?php foreach ($order_statuses as $order_status) { ?>
                  <?php if ($order_status['order_status_id'] == $emagmp_order_state_id_initial) { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                  <?php } ?>
                  <?php } ?>
				  </select>
                  <?php if ($error_emagmp_order_state_id_initial) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_order_state_id_initial; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_order_state_id_finalized"><span data-toggle="tooltip" data-container="#tab-products_orders" title="The order status that marks the order as finalized">Finalized Order Status</span></label>
                <div class="col-sm-10">
                  <select name="emagmp_order_state_id_finalized" id="input-emagmp_order_state_id_finalized" class="form-control">
				  <?php foreach ($order_statuses as $order_status) { ?>
                  <?php if ($order_status['order_status_id'] == $emagmp_order_state_id_finalized) { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                  <?php } ?>
                  <?php } ?>
				  </select>
                  <?php if ($error_emagmp_order_state_id_finalized) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_order_state_id_finalized; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_order_state_id_cancelled"><span data-toggle="tooltip" data-container="#tab-products_orders" title="The order status that marks the order as cancelled">Cancelled Order Status</span></label>
                <div class="col-sm-10">
                  <select name="emagmp_order_state_id_cancelled" id="input-emagmp_order_state_id_cancelled" class="form-control">
				  <?php foreach ($order_statuses as $order_status) { ?>
                  <?php if ($order_status['order_status_id'] == $emagmp_order_state_id_cancelled) { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                  <?php } ?>
                  <?php } ?>
				  </select>
                  <?php if ($error_emagmp_order_state_id_cancelled) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_order_state_id_cancelled; ?></div>
                  <?php } ?>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-shipping">
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_handling_time"><span data-toggle="tooltip" data-container="#tab-shipping" title="How many days until delivery of goods to the customer (0 = same day delivery)">Handling Time</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_handling_time" value="<?php echo $emagmp_handling_time; ?>" placeholder="Handling Time" id="input-emagmp_handling_time" class="form-control" />
                  <?php if ($error_emagmp_handling_time) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_handling_time; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label"><span data-toggle="tooltip" data-container="#tab-shipping" title="Use the eMAG AWB system to generate dispatch documents and mark the orders as finalized">Use eMAG AWB</span></label>
                <div class="col-sm-10">
                   <label class="radio-inline">
                      <?php if ($emagmp_use_emag_awb) { ?>
                      <input type="radio" name="emagmp_use_emag_awb" value="1" checked="checked" />
                      Yes
                      <?php } else { ?>
                      <input type="radio" name="emagmp_use_emag_awb" value="1" />
                      Yes
                      <?php } ?>
                    </label>
                    <label class="radio-inline">
                      <?php if (!$emagmp_use_emag_awb) { ?>
                      <input type="radio" name="emagmp_use_emag_awb" value="0" checked="checked" />
                      No
                      <?php } else { ?>
                      <input type="radio" name="emagmp_use_emag_awb" value="0" />
                      No
                      <?php } ?>
                    </label>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_awb_sender_name"><span data-toggle="tooltip" data-container="#tab-shipping" title="Sender name to use with the eMAG Marketplace AWB">AWB Sender Name</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_awb_sender_name" value="<?php echo $emagmp_awb_sender_name; ?>" placeholder="AWB Sender Name" id="input-emagmp_awb_sender_name" class="form-control" />
                  <?php if ($error_emagmp_awb_sender_name) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_awb_sender_name; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_awb_sender_contact"><span data-toggle="tooltip" data-container="#tab-shipping" title="Sender contact name to use with the eMAG Marketplace AWB">AWB Sender Contact</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_awb_sender_contact" value="<?php echo $emagmp_awb_sender_contact; ?>" placeholder="AWB Sender Contact" id="input-emagmp_awb_sender_contact" class="form-control" />
                  <?php if ($error_emagmp_awb_sender_contact) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_awb_sender_contact; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_awb_sender_phone"><span data-toggle="tooltip" data-container="#tab-shipping" title="Sender phone number to use with the eMAG Marketplace AWB">AWB Sender Phone</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_awb_sender_phone" value="<?php echo $emagmp_awb_sender_phone; ?>" placeholder="AWB Sender Phone" id="input-emagmp_awb_sender_phone" class="form-control" />
                  <?php if ($error_emagmp_awb_sender_phone) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_awb_sender_phone; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_awb_sender_phone"><span data-toggle="tooltip" data-container="#tab-shipping" title="Sender locality to use with the eMAG Marketplace AWB">AWB Sender Locality</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_awb_sender_locality" value="<?php echo $emagmp_awb_sender_locality; ?>" placeholder="AWB Sender Locality" id="input-emagmp_awb_sender_locality" class="form-control" />
                  <?php if ($error_emagmp_awb_sender_locality) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_awb_sender_locality; ?></div>
                  <?php } ?>
                </div>
              </div>
			  <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-emagmp_awb_sender_street"><span data-toggle="tooltip" data-container="#tab-shipping" title="Sender street address to use with the eMAG Marketplace AWB">AWB Sender Street</span></label>
                <div class="col-sm-10">
                  <input type="text" name="emagmp_awb_sender_street" value="<?php echo $emagmp_awb_sender_street; ?>" placeholder="AWB Sender Street" id="input-emagmp_awb_sender_street" class="form-control" />
                  <?php if ($error_emagmp_awb_sender_street) { ?>
                  <div class="text-danger"><?php echo $error_emagmp_awb_sender_street; ?></div>
                  <?php } ?>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-cron-jobs">
			Install the following Cron jobs on your server:<br />
		  <pre><?php foreach ($cron_jobs as $command) {
		  	echo $command."\n";
			} ?></pre>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script type="text/javascript"><!--
function testEmagMarketplaceConnection() {
	$.ajax({
		url: 'index.php?route=module/emagmarketplace&page=test_connection&token=<?php echo $token; ?>',
		type: 'post',
		data: $("#form-setting").serialize(),
		dataType: 'json',	
		beforeSend: function() {
			$('#test_connection_button').button('loading');
		},
		complete: function() {
			$('#test_connection_button').button('reset');
		},
		success: function(json) {
			// Check for errors
			if (json['error']) {
				alert(json["error"]);
			} else {
				
			}

			if (json['success']) {
				alert(json['success']);			
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
			url: 'index.php?route=module/emagmarketplace&page=autocomplete_locality&token=<?php echo $token; ?>&keyword=' +  encodeURIComponent(request),
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
	select: function(item) {
		$('input[name=\'emagmp_awb_sender_locality\']').val(item['label']);
	}
});

function downloadEmagMarketplaceLocalities() {
	$.ajax({
		url: 'index.php?route=module/emagmarketplace&page=download_localities&token=<?php echo $token; ?>',
		type: 'post',
		data: { },
		dataType: 'json',
		beforeSend: function() {
			$('input[name=\'emagmp_awb_sender_locality\']').after(' <i class="fa fa-circle-o-notch fa-spin"></i>');
		},
		complete: function() {
			$('.fa-spin').remove();
		},
		success: function(json) {
			// Check for errors
			if (json['error']) {
				alert(json["error"]);
			} else {
				alert(json['success']);
			}
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}
//--></script></div>
<?php echo $footer; ?>