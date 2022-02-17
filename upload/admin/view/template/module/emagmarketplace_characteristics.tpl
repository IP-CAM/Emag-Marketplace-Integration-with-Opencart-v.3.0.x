<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/setting.png" alt="" /> eMAG Marketplace Characteristic Mapping</h1>
      <div class="buttons"><a onclick="location = 'index.php?route=module/emagmarketplace&page=categories&token=<?php echo $token; ?>';" class="button">&laquo; Previous step</a>&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="uploadProducts()" class="button">Upload Products</a></div>
    </div>
    <div class="content">
        <table class="list">
          <thead>
            <tr>
              <td class="left">eMAG Category</td>
              <td class="left">eMAG Characteristic</td>
              <td class="left">Attribute</td>
              <td class="left">Option</td>
            </tr>
          </thead>
          <tbody>
            <?php if ($emag_characteristic_definitions) { ?>
            <?php foreach ($emag_characteristic_definitions as $characteristic_definition) { ?>
            <tr>
              <td class="left"><?php echo $characteristic_definition['emag_category_name']; ?></td>
              <td class="left"><?php echo $characteristic_definition['emag_characteristic_name']; ?></td>
              <td class="left"><select name="attribute[<?php echo $characteristic_definition['emag_characteristic_id'] ?>][<?php echo $characteristic_definition['emag_category_id'] ?>]" style="width: 250px;"><option value=""> </option>
			  <?php if (isset($attributes)) { ?>
				  <?php foreach ($attributes as $attribute) { ?>
					<option value="<?php echo $attribute['attribute_id'] ?>" <?php if ($characteristic_definition['attribute_id'] == $attribute['attribute_id']) { echo 'selected="selected"'; } ?> ><?php echo $attribute['attribute_group'] . ' > ' . $attribute['attribute_name'] ?></option>
				  <?php } ?>
			  <?php } ?>
			  </select>&nbsp;<a class="button" href="javascript:;" onclick="updateEmagMarketplaceCharacteristic(<?php echo $characteristic_definition['emag_characteristic_id'] ?>, <?php echo $characteristic_definition['emag_category_id'] ?>, '<?php echo $token; ?>', 'attribute')">Update</a></td>
			  <td class="left"><select name="option[<?php echo $characteristic_definition['emag_characteristic_id'] ?>][<?php echo $characteristic_definition['emag_category_id'] ?>]" style="width: 250px;"><option value=""> </option>
			  <?php if (isset($options)) { ?>
				  <?php foreach ($options as $option) { ?>
					<option value="<?php echo $option['option_id'] ?>" <?php if ($characteristic_definition['option_id'] == $option['option_id']) { echo 'selected="selected"'; } ?> ><?php echo $option['option_name'] ?></option>
				  <?php } ?>
			  <?php } ?>
			  </select>&nbsp;<a class="button" href="javascript:;" onclick="updateEmagMarketplaceCharacteristic(<?php echo $characteristic_definition['emag_characteristic_id'] ?>, <?php echo $characteristic_definition['emag_category_id'] ?>, '<?php echo $token; ?>', 'option')">Update</a></td>
            </tr>
            <?php } ?>
            <?php } else { ?>
            <tr>
              <td class="center" colspan="4"><?php echo $text_no_results; ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
		 <div class="pagination"><?php echo $pagination; ?></div>
    </div>
  </div>
</div>
<script type="text/javascript"><!--
function updateEmagMarketplaceCharacteristic(emag_characteristic_id, emag_category_id, token, field) {
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
			$('.success, .warning, .attention, .error').remove();
			
			$('.box').before('<div class="attention"><img src="view/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
		},			
		success: function(json) {
			$('.success, .warning, .attention, .error').remove();
			
			// Check for errors
			if (json['error']) {
				alert('The following error occured: ' + json['error']);
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