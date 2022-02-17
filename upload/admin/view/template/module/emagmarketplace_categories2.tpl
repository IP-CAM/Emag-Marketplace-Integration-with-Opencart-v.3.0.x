<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right"><a href="index.php?route=module/emagmarketplace&token=<?php echo $token; ?>" data-toggle="tooltip" title="Previous step" class="btn btn-default"><i class="fa fa-arrow-left"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" data-toggle="tooltip" id="download_categories_button" title="Download eMAG Categories" class="btn btn-primary" onclick="downloadEmagMarketplaceCategories()"><i class="fa fa-download"></i></button>&nbsp;&nbsp;&nbsp;&nbsp;<a href="index.php?route=module/emagmarketplace&page=characteristics&token=<?php echo $token; ?>" data-toggle="tooltip" title="Next step" class="btn btn-default"><i class="fa fa-arrow-right"></i></a></div>
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
        <h3 class="panel-title"><i class="fa fa-list"></i> eMAG Marketplace Category Mapping</h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $delete; ?>" method="post" enctype="multipart/form-data" id="form-category">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
				  <td class="text-left">Store Category</td>
				  <td class="text-left">eMAG Category</td>
				  <td class="text-left">Family Type</td>
				  <td class="text-left">Commission</td>
				  <td class="text-left">Sync</td>
                </tr>
              </thead>
              <tbody>
                <?php if ($categories) { ?>
                <?php foreach ($categories as $category) { ?>
                <tr>
				  <td class="text-left"><?php echo $category['name']; ?></td>
				  <td class="text-left"><input type="text" name="emag_category_id[<?php echo $category['category_id'] ?>]" value="<?php echo $category['emag_category_label'] ?>" size="40" />&nbsp;<button type="button" class="btn btn-sm btn-primary" onclick="updateEmagMarketplaceCategory(this, <?php echo $category['category_id'] ?>, '<?php echo $token; ?>', 'category')">Update</button></td>
				  <td class="text-left"><select name="emag_family_type_id[<?php echo $category['category_id'] ?>]" style="width: 250px;"><option value="0"> </option>
				  <?php if (isset($emag_family_type_definitions[(int)$category['emag_category_label']])) { ?>
					  <?php foreach ($emag_family_type_definitions[(int)$category['emag_category_label']] as $family_type_definition) { ?>
						<option value="<?php echo $family_type_definition['emag_family_type_id'] ?>" <?php if ($family_type_definition['emag_family_type_id'] == $category['emag_family_type_id']) { echo 'selected="selected"'; } ?> ><?php echo $family_type_definition['emag_family_type_name'] ?></option>
					  <?php } ?>
				  <?php } ?>
				  </select>&nbsp;<button type="button" class="btn btn-sm btn-primary" onclick="updateEmagMarketplaceCategory(this, <?php echo $category['category_id'] ?>, '<?php echo $token; ?>', 'family_type')">Update</button></td>
				  <td class="text-left"><input type="text" name="commission[<?php echo $category['category_id'] ?>]" value="<?php echo $category['commission'] > 0 ? $category['commission'] : '' ?>" size="10" style="text-align: right;" />&nbsp;<button type="button" class="btn btn-sm btn-primary" onclick="updateEmagMarketplaceCategory(this, <?php echo $category['category_id'] ?>, '<?php echo $token; ?>', 'commission')">Update</button></td>
				  <td class="text-left"><label><input type="checkbox" name="sync_active[<?php echo $category['category_id'] ?>]" <?php if ($category['sync_active']) { echo 'checked="checked"'; } ?> onclick="updateEmagMarketplaceCategory(this, <?php echo $category['category_id'] ?>, '<?php echo $token; ?>', 'sync_active')" style="vertical-align: -2px;"> Sync</label></td>
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
			$('#download_categories_button').button('loading');
		},
		complete: function() {
			$('#download_categories_button').button('reset');
		},
		success: function(json) {
			// Check for errors
			if (json['error']) {
				alert(json["error"]);
			} else {
				alert(json['success']);
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
	source: function(request, response) {
		results = [];
		for (i in emag_category_definitions) {
			if (emag_category_definitions[i].toLowerCase().indexOf(request) != -1) {
				results.push(emag_category_definitions[i]);
			}
		}
		response($.map(results, function(item) {
			return {
				label: item,
				value: item,
				id: item
			}
		}));
	},
	select: function(item) {
		$(this).val(item['label']);
	}
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

function updateEmagMarketplaceCategory(button, category_id, token, field) {
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
			sync_active: $('[name="sync_active\\['+category_id+'\\]"]').prop('checked') ? 1 : 0
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
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}
//--></script>
</div>
<?php echo $footer; ?>