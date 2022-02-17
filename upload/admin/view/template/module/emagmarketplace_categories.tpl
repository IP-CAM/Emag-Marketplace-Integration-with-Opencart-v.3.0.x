<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/setting.png" alt="" /> eMAG Marketplace Category Mapping</h1>
      <div class="buttons"><a onclick="location = 'index.php?route=module/emagmarketplace&token=<?php echo $token; ?>';" class="button">&laquo; Previous step</a>&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="downloadEmagMarketplaceCategories()" class="button">Download eMAG Categories</a>&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="location = 'index.php?route=module/emagmarketplace&page=characteristics&token=<?php echo $token; ?>';" class="button">Next step &raquo;</a></div>
    </div>
    <div class="content">
        <table class="list">
          <thead>
            <tr>
              <td class="left">Store Category</td>
              <td class="left">eMAG Category</td>
              <td class="left">Family Type</td>
              <td class="left">Commission</td>
              <td class="left">Sync</td>
            </tr>
          </thead>
          <tbody>
            <?php if ($categories) { ?>
            <?php foreach ($categories as $category) { ?>
            <tr>
              <td class="left"><?php echo $category['name']; ?></td>
              <td class="left"><input type="text" name="emag_category_id[<?php echo $category['category_id'] ?>]" value="<?php echo $category['emag_category_label'] ?>" size="40" />&nbsp;<a class="button" href="javascript:;" onclick="updateEmagMarketplaceCategory(<?php echo $category['category_id'] ?>, '<?php echo $token; ?>', 'category')">Update</a></td>
              <td class="left"><select name="emag_family_type_id[<?php echo $category['category_id'] ?>]" style="width: 250px;"><option value="0"> </option>
			  <?php if (isset($emag_family_type_definitions[(int)$category['emag_category_label']])) { ?>
				  <?php foreach ($emag_family_type_definitions[(int)$category['emag_category_label']] as $family_type_definition) { ?>
					<option value="<?php echo $family_type_definition['emag_family_type_id'] ?>" <?php if ($family_type_definition['emag_family_type_id'] == $category['emag_family_type_id']) { echo 'selected="selected"'; } ?> ><?php echo $family_type_definition['emag_family_type_name'] ?></option>
				  <?php } ?>
			  <?php } ?>
			  </select>&nbsp;<a class="button" href="javascript:;" onclick="updateEmagMarketplaceCategory(<?php echo $category['category_id'] ?>, '<?php echo $token; ?>', 'family_type')">Update</a></td>
              <td class="left"><input type="text" name="commission[<?php echo $category['category_id'] ?>]" value="<?php echo $category['commission'] > 0 ? $category['commission'] : '' ?>" size="10" style="text-align: right;" />&nbsp;<a class="button" href="javascript:;" onclick="updateEmagMarketplaceCategory(<?php echo $category['category_id'] ?>, '<?php echo $token; ?>', 'commission')">Update</a></td>
              <td class="left"><label><input type="checkbox" name="sync_active[<?php echo $category['category_id'] ?>]" <?php if ($category['sync_active']) { echo 'checked="checked"'; } ?> onclick="updateEmagMarketplaceCategory(<?php echo $category['category_id'] ?>, '<?php echo $token; ?>', 'sync_active')">&nbsp;Sync</label></td>
            </tr>
            <?php } ?>
            <?php } else { ?>
            <tr>
              <td class="center" colspan="4"><?php echo $text_no_results; ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
    </div>
  </div>
</div>
<script type="text/javascript"><!--
function downloadEmagMarketplaceCategories() {
	if (!confirm('Do you want to download the eMAG categories now? This operation might take a few minutes! Please wait for it to finish!'))
		return;
	$.ajax({
		url: 'index.php?route=module/emagmarketplace&page=download_categories&token=<?php echo $token; ?>',
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
				this.location.href = this.location.href;			
			}	
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}

var emag_category_definitions = [
	<?php foreach ($emag_category_definitions as $category_definition) { ?>
		"<?php echo $category_definition['emag_category_id'] ?> - <?php echo $category_definition['emag_category_name'] ?>",
	<?php } ?>
];
$('[name^="emag_category_id"]').autocomplete({
	source: emag_category_definitions
});
var emag_family_type_definitions = {
	<?php foreach ($emag_family_type_definitions as $emag_category_id => $family_type_definitions) { ?>
		"<?php echo $emag_category_id ?>": [
			<?php foreach ($family_type_definitions as $family_type_definition) { ?>
				{   "id": "<?php echo $family_type_definition['emag_family_type_id'] ?>", "value": "<?php echo $family_type_definition['emag_family_type_name'] ?>"   },
			<?php } ?>
		],
	<?php } ?>
};

function updateEmagMarketplaceCategory(category_id, token, field) {
	var emag_category_id = $('[name="emag_category_id\\['+category_id+'\\]"]').val();
	$.ajax({
		url: 'index.php?route=module/emagmarketplace&page=update_category&token=<?php echo $token; ?>',
		type: 'post',
		data: {
			category_id: category_id,
			field: field,
			emag_category_id: emag_category_id,
			emag_family_type_id: $('[name="emag_family_type_id\\['+category_id+'\\]"]').val(),
			commission: $('[name="commission\\['+category_id+'\\]"]').val(),
			sync_active: $('[name="sync_active\\['+category_id+'\\]"]').attr('checked') ? 1 : 0
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
				if (field == 'category') {
					$('[name="emag_family_type_id\\['+category_id+'\\]"]').val(0);
					$('[name="emag_family_type_id\\['+category_id+'\\]"] option').each(function(index, element) {
						if (index == 0)
							return;
						$(this).remove();
					});
					var pattern = /([0-9]+) - .+/;
					var result = pattern.exec(emag_category_id);
					if (result) {
						emag_category_id = result[1];
						if (emag_family_type_definitions[emag_category_id]) {
							for (i = 0; i < emag_family_type_definitions[emag_category_id].length; i++) {
								$('[name="emag_family_type_id\\['+category_id+'\\]"]').append('<option value="'+emag_family_type_definitions[emag_category_id][i]['id']+'">'+emag_family_type_definitions[emag_category_id][i]['value']+'</option>');
							}
						}
					}
				}
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