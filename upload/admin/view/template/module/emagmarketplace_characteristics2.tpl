<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right"><a href="index.php?route=module/emagmarketplace&page=categories&token=<?php echo $token; ?>" data-toggle="tooltip" title="Previous step" class="btn btn-default"><i class="fa fa-arrow-left"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" data-toggle="tooltip" id="upload_products_button" title="Upload Products" class="btn btn-primary" onclick="uploadProducts()"><i class="fa fa-upload"></i></button></div>
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
        <h3 class="panel-title"><i class="fa fa-list"></i> eMAG Marketplace Characteristic Mapping</h3>
      </div>
      <div class="panel-body">
        <form method="post" enctype="multipart/form-data" id="form-category">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
				  <td class="text-left">eMAG Category</td>
				  <td class="text-left">eMAG Characteristic</td>
				  <td class="text-left">Attribute</td>
				  <td class="text-left">Option</td>
                </tr>
              </thead>
              <tbody>
                <?php if ($emag_characteristic_definitions) { ?>
                <?php foreach ($emag_characteristic_definitions as $characteristic_definition) { ?>
                <tr>
				  <td class="text-left"><?php echo $characteristic_definition['emag_category_name']; ?></td>
				  <td class="text-left"><?php echo $characteristic_definition['emag_characteristic_name']; ?></td>
				  <td class="text-left"><select name="attribute[<?php echo $characteristic_definition['emag_characteristic_id'] ?>][<?php echo $characteristic_definition['emag_category_id'] ?>]" style="width: 250px;"><option value=""> </option>
				  <?php if (isset($attributes)) { ?>
					  <?php foreach ($attributes as $attribute) { ?>
						<option value="<?php echo $attribute['attribute_id'] ?>" <?php if ($characteristic_definition['attribute_id'] == $attribute['attribute_id']) { echo 'selected="selected"'; } ?> ><?php echo $attribute['attribute_group'] . ' > ' . $attribute['attribute_name'] ?></option>
					  <?php } ?>
				  <?php } ?>
				  </select>&nbsp;<button type="button" class="btn btn-sm btn-primary" onclick="updateEmagMarketplaceCharacteristic(this, <?php echo $characteristic_definition['emag_characteristic_id'] ?>, <?php echo $characteristic_definition['emag_category_id'] ?>, '<?php echo $token; ?>', 'attribute')">Update</button></td>
				  <td class="text-left"><select name="option[<?php echo $characteristic_definition['emag_characteristic_id'] ?>][<?php echo $characteristic_definition['emag_category_id'] ?>]" style="width: 250px;"><option value=""> </option>
				  <?php if (isset($options)) { ?>
					  <?php foreach ($options as $option) { ?>
						<option value="<?php echo $option['option_id'] ?>" <?php if ($characteristic_definition['option_id'] == $option['option_id']) { echo 'selected="selected"'; } ?> ><?php echo $option['option_name'] ?></option>
					  <?php } ?>
				  <?php } ?>
				  </select>&nbsp;<button type="button" class="btn btn-sm btn-primary" onclick="updateEmagMarketplaceCharacteristic(this, <?php echo $characteristic_definition['emag_characteristic_id'] ?>, <?php echo $characteristic_definition['emag_category_id'] ?>, '<?php echo $token; ?>', 'option')">Update</button></td>
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
function updateEmagMarketplaceCharacteristic(button, emag_characteristic_id, emag_category_id, token, field) {
	$.ajax({
		url: 'index.php?route=module/emagmarketplace&page=update_characteristic&token=<?php echo $token; ?>',
		type: 'post',
		data: {
			emag_characteristic_id: emag_characteristic_id,
			emag_category_id: emag_category_id,
			field: field,
			attribute_id: $('[name="attribute\\['+emag_characteristic_id+'\\]\\['+emag_category_id+'\\]"]').val(),
			option_id: $('[name="option\\['+emag_characteristic_id+'\\]\\['+emag_category_id+'\\]"]').val()
		},
		dataType: 'json',	
		beforeSend: function() {
			$(button).button('loading');
		},
		complete: function() {
			$(button).button('reset');
		},
		success: function(json) {
			// Check for errors
			if (json['error']) {
				alert('The following error occured: ' + json['error']);
			} else {
				
			}
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}

function uploadProducts() {
	if (!confirm('Are you sure you want to upload all products now?'))
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