<?php
class ControllerExtensionModuleEmagmarketplace extends Controller {
	private $error = array(); 
	
	public function index() {
		if (!isset($this->request->get['page']))
			$this->request->get['page'] = 'config';
			
		$this->load->model('extension/module/emagmarketplace');
		
		switch ($this->request->get['page'])
		{
			case "config":
				$this->config();
				break;
			case "test_connection":
				$this->test_connection();
				break;
			case "autocomplete_locality":
				$this->autocomplete_locality();
				break;
			case "download_localities":
				$this->download_localities();
				break;
			case "categories":
				$this->categories();
				break;
			case "download_categories":
				$this->download_categories();
				break;
			case "update_category":
				$this->update_category();
				break;
			case "characteristics":
				$this->characteristics();
				break;
			case "update_characteristic":
				$this->update_characteristic();
				break;
			case "upload_products":
				$this->upload_products();
				break;
			case "generate_awb":
				$this->generate_awb();
				break;
			case "call_logs":
				$this->call_logs();
				break;
		}
	}

	public function config() {

		$this->load->language('extension/module/emagmarketplace');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_emagmp', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		foreach (array('module_emagmp_url', 'module_emagmp_api_url', 'module_emagmp_vendorcode', 'module_emagmp_vendorusername', 'module_emagmp_vendorpassword', 'module_emagmp_product_queue_limit', 'module_emagmp_product_option_required', 'module_emagmp_product_warranty', 'module_emagmp_product_language_id', 'module_emagmp_order_state_id_initial', 'module_emagmp_order_state_id_finalized', 'module_emagmp_order_state_id_cancelled', 'module_emagmp_handling_time', 'module_emagmp_use_emag_awb', 'module_emagmp_awb_sender_name', 'module_emagmp_awb_sender_contact', 'module_emagmp_awb_sender_phone', 'module_emagmp_awb_sender_locality', 'module_emagmp_awb_sender_street') as $config_name) {
			if (isset($this->error[$config_name])) {
				$data['error_'.$config_name] = $this->error[$config_name];
			} else {
				$data['error_'.$config_name] = '';
			}

			if (isset($this->request->post[$config_name])) {
				$data[$config_name] = $this->request->post[$config_name];
			} else {
				$data[$config_name] = $this->config->get($config_name);
			}
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language");

		$data['languages'] = $query->rows;
		
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$url = new Url(HTTP_CATALOG);
		$data['cron_jobs'] = array();
		$data['cron_jobs'][] = '*/5 * * * * wget -O - "'.$url->link('extension/module/emagmarketplace', 'action=check_cron_jobs').'" >> '.DIR_LOGS.'check_cron_jobs.log';
		$data['cron_jobs'][] = '*/5 * * * * wget -O - "'.$url->link('extension/module/emagmarketplace', 'action=check_errors').'" >> '.DIR_LOGS.'check_errors.log';
		$data['cron_jobs'][] = '0 5 * * * wget -O - "'.$url->link('extension/module/emagmarketplace', 'action=clean_logs').'" >> '.DIR_LOGS.'clean_logs.log';
		$data['cron_jobs'][] = '* * * * * wget -O - "'.$url->link('extension/module/emagmarketplace', 'action=get_orders').'" >> '.DIR_LOGS.'get_orders.log';
		$data['cron_jobs'][] = '10 5 * * * wget -O - "'.$url->link('extension/module/emagmarketplace', 'action=refresh_definitions').'" >> '.DIR_LOGS.'refresh_definitions.log';
		$data['cron_jobs'][] = '* * * * * wget -O - "'.$url->link('extension/module/emagmarketplace', 'action=run_queue').'" >> '.DIR_LOGS.'run_queue.log';

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dasboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/emagmarketplace', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => 'Main Config',
			'href' => $this->url->link('extension/module/emagmarketplace', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['action'] = $this->url->link('extension/module/emagmarketplace', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('extension/module/emagmarketplace', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['user_token'] = $this->session->data['user_token'];

		$data['text_wait'] = $this->language->get('text_wait');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/emagmarketplace_config', $data));
	}

	public function test_connection() {
		$this->load->language('extension/module/emagmarketplace');

		$json = array();

		$emagmp_api_call = new EmagMarketplaceAPICall();

		$emagmp_api_call->emagmp_api_url = $this->request->post['module_emagmp_api_url'];
		$emagmp_api_call->emagmp_vendorcode = $this->request->post['module_emagmp_vendorcode'];
		$emagmp_api_call->emagmp_vendorusername = $this->request->post['module_emagmp_vendorusername'];
		$emagmp_api_call->emagmp_vendorpassword = $this->request->post['module_emagmp_vendorpassword'];

		$emagmp_api_call->resource = 'vat';
		$emagmp_api_call->action = 'read';

		$emagmp_api_call->execute();
		if ($emagmp_api_call->save())
		{
			if ($emagmp_api_call->status == 'error')
			{
				$json['error'] = 'Connection could not be established! The API function returned the following error: '.$emagmp_api_call->message_in;
			}
			elseif ($emagmp_api_call->status == 'success')
			{
				$json['success'] = $this->language->get('text_connection_ok');
			}
			else
			{
				$json['error'] = 'The API call could not be executed!';
			}
		}
		else
		{
			$json['error'] = 'Something is terribly wrong! Could not save the API call in the database!!!';
		}

		$this->response->setOutput(json_encode($json));
	}

	public function download_localities() {
		$this->load->language('extension/module/emagmarketplace');

		$json = array();

		$page = 1;
		$per_page = 2000;
		do {
			$emagmp_api_call = new EmagMarketplaceAPICall();
			$emagmp_api_call->resource = 'locality';
			$emagmp_api_call->action = 'read';
			$emagmp_api_call->data = array(
				'currentPage' => $page,
				'itemsPerPage' => $per_page
			);

			$emagmp_api_call->execute();

			$emagmp_api_call->save();

			if ($emagmp_api_call->status == 'success') {
				foreach ($emagmp_api_call->message_in_json->results as $locality_definition)
				{
					$this->db->query('
						INSERT INTO `'.DB_PREFIX.'emagmp_locality_definitions`
						SET
						`emag_locality_id` = '.(int)$locality_definition->emag_id.',
						`emag_region2_latin` = "'.$this->db->escape($locality_definition->region2_latin).'",
						`emag_region3_latin` = "'.$this->db->escape($locality_definition->region3_latin).'",
						`emag_name_latin` = "'.$this->db->escape($locality_definition->name_latin).'"
						ON DUPLICATE KEY UPDATE
						`emag_region2_latin` = "'.$this->db->escape($locality_definition->region2_latin).'",
						`emag_region3_latin` = "'.$this->db->escape($locality_definition->region3_latin).'",
						`emag_name_latin` = "'.$this->db->escape($locality_definition->name_latin).'"
					');
				}
				$page++;
			} else {
				$json['error'] = 'Connection could not be established! The API function returned the following error: '.$emagmp_api_call->message_in;
				break;
			}
		} while (count($emagmp_api_call->message_in_json->results) == $per_page);

		if (!isset($json['error']))
			$json['success'] = 'Localities have been downloaded successfully! You can now choose your AWB sender locality from the autocomplete list!';

		$this->response->setOutput(json_encode($json));
	}

	public function autocomplete_locality() {
		$json = array();

		$query = $this->db->query("
			SELECT * FROM `".DB_PREFIX."emagmp_locality_definitions`
			WHERE emag_name_latin like '%".$this->db->escape($this->request->get['keyword'])."%'
		");
		foreach ($query->rows as $row) {
			$json[] = array(
				'id' => $row['emag_locality_id'],
				'value' => $row['emag_name_latin'].', '.$row['emag_region3_latin'].', '.$row['emag_region2_latin'],
				'label' => $row['emag_name_latin'].', '.$row['emag_region3_latin'].', '.$row['emag_region2_latin'],
				'name' => $row['emag_name_latin'].', '.$row['emag_region3_latin'].', '.$row['emag_region2_latin']
			);
		}

		if (!$query->num_rows) {
			$query = $this->db->query("
				SELECT count(*) as locality_count FROM `".DB_PREFIX."emagmp_locality_definitions`
			");
			if (!$query->row['locality_count']) {
				$json['warning_localities'] = 'eMAG localities have not been downloaded yet! Do you want to download them now? This operation might take a few minutes! Please wait for it to finish!';
			}
		}

		$this->response->setOutput(json_encode($json));
	}

	public function categories() {

		$this->load->language('extension/module/emagmarketplace');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['emag_categories'] = array();
		$query = $this->db->query("
			SELECT *
			FROM `".DB_PREFIX."emagmp_categories`
		");
		foreach ($query->rows as $row) {
			$data['emag_categories'][$row['category_id']] = $row;
		}

		$data['emag_category_definitions'] = array();

		$query = $this->db->query("
			SELECT `emag_category_id`, `emag_category_name`
			FROM `".DB_PREFIX."emagmp_category_definitions`
		");

		foreach ($query->rows as $row) {
			$data['emag_category_definitions'][$row['emag_category_id']] = $row;
		}

		$data['emag_family_type_definitions'] = array();

		$query = $this->db->query("
			select *
			from ".DB_PREFIX."emagmp_family_type_definitions
		");

		foreach ($query->rows as $row) {
			$data['emag_family_type_definitions'][$row['emag_category_id']][] = $row;
		}

		$data['categories'] = array();

		$this->load->model('catalog/category');

		$results = $this->model_catalog_category->getCategories(0);

		foreach ($results as $result) {
			$data['categories'][] = array(
				'category_id'         => $result['category_id'],
				'name'                => $result['name'],
				'emag_category_label' => isset($data['emag_categories'][$result['category_id']]) && $data['emag_categories'][$result['category_id']]['emag_category_id'] > 0 ? $data['emag_categories'][$result['category_id']]['emag_category_id'] . ' - ' . $data['emag_category_definitions'][$data['emag_categories'][$result['category_id']]['emag_category_id']]['emag_category_name'] : '',
				'emag_family_type_id' => isset($data['emag_categories'][$result['category_id']]) ? $data['emag_categories'][$result['category_id']]['emag_family_type_id'] : 0,
				'commission'          => isset($data['emag_categories'][$result['category_id']]) ? $data['emag_categories'][$result['category_id']]['commission'] : '',
				'sync_active'         => isset($data['emag_categories'][$result['category_id']]) ? $data['emag_categories'][$result['category_id']]['sync_active'] : 0
			);
		}

		$data['text_no_results'] = $this->language->get('text_no_results');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dasboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/emagmarketplace', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => 'Category Mapping',
			'href' => $this->url->link('extension/module/emagmarketplace', 'page=categories&user_token=' . $this->session->data['user_token'], true),
		);

		$data['action'] = $this->url->link('extension/module/emagmarketplace', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('extension/module/emagmarketplace', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/emagmarketplace_categories', $data));
	}

	public function download_categories() {
		$this->load->language('extension/module/emagmarketplace');

		$json = array();

		$page = 1;
		$per_page = 200;
		do {
			$emagmp_api_call = new EmagMarketplaceAPICall();
			$emagmp_api_call->resource = 'category';
			$emagmp_api_call->action = 'read';
			$emagmp_api_call->data = array(
				'currentPage' => $page,
				'itemsPerPage' => $per_page
			);
			$emagmp_api_call->execute();
			$emagmp_api_call->save();
			if ($emagmp_api_call->status == 'success')
			{
				foreach ($emagmp_api_call->message_in_json->results as $category_definition)
				{
					$this->db->query('
						INSERT INTO `' . DB_PREFIX . 'emagmp_category_definitions`
						SET
						`emag_category_id` = '.(int)$category_definition->id.',
						`emag_category_name` = "'.$this->db->escape($category_definition->name).'"
						ON DUPLICATE KEY UPDATE
						`emag_category_name` = "'.$this->db->escape($category_definition->name).'"
					');
					foreach ($category_definition->characteristics as $characteristic)
					{
						$this->db->query('
							INSERT INTO `'.DB_PREFIX.'emagmp_characteristic_definitions`
							SET
							`emag_characteristic_id` = '.(int)$characteristic->id.',
							`emag_category_id` = '.(int)$category_definition->id.',
							`emag_characteristic_name` = "'.$this->db->escape($characteristic->name).'",
							`display_order` = '.(int)$characteristic->display_order.'
							ON DUPLICATE KEY UPDATE
							`emag_characteristic_name` = "'.$this->db->escape($characteristic->name).'",
							`display_order` = '.(int)$characteristic->display_order.'
						');
					}
					foreach ($category_definition->family_types as $family_type)
					{
						$this->db->query('
							INSERT INTO `'.DB_PREFIX.'emagmp_family_type_definitions`
							SET
							`emag_family_type_id` = '.(int)$family_type->id.',
							`emag_category_id` = '.(int)$category_definition->id.',
							`emag_family_type_name` = "'.$this->db->escape($family_type->name).'"
							ON DUPLICATE KEY UPDATE
							`emag_family_type_name` = "'.$this->db->escape($family_type->name).'"
						');
					}
				}
				$page++;
			}
			else
			{
				$json['error'] = 'Connection could not be established! The API function returned the following error: '.$emagmp_api_call->message_in;
				break;
			}
		} while (count($emagmp_api_call->message_in_json->results) == $per_page);

		if (!isset($json['error']))
			$json['success'] = 'eMAG categories have been downloaded successfully! You can now start mapping your categories!';

		$this->response->setOutput(json_encode($json));
	}

	public function update_category() {
		$json = array();

		switch ($this->request->post['field']) {
			case "category":
				$sql_update = "emag_category_id = ".(int)$this->request->post['emag_category_id'].", emag_family_type_id = 0";
				break;
			case "family_type":
				$sql_update = "emag_family_type_id = ".(int)$this->request->post['emag_family_type_id'];
				break;
			case "commission":
				$sql_update = "commission = ".(float)$this->request->post['commission'];
				break;
			case "sync_active":
				$sql_update = "sync_active = ".(int)$this->request->post['sync_active'];
				break;
		}

		$this->db->query("
			insert into ".DB_PREFIX."emagmp_categories
			set
			category_id = ".(int)$this->request->post['category_id'].",
			$sql_update
			on duplicate key update
			$sql_update
		");

		$json['success'] = 'Category updated successfully!';
		
		$this->response->setOutput(json_encode($json));
	}

	public function characteristics() {
		
		$this->load->language('extension/module/emagmarketplace');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->get['pagination_set'])) {
			$pagination_set = (int)$this->request->get['pagination_set'];
		} else {
			$pagination_set = 1;
		}
		
		$pagination_limit = 20;

		if ($this->config->get('config_admin_limit')) {
			$pagination_limit = $this->config->get('config_admin_limit');
		}
		elseif ($this->config->get('config_limit_admin')) {
			$pagination_limit = $this->config->get('config_limit_admin');
		}

		$data['emag_characteristic_definitions'] = array();

		$query = $this->db->query("
			SELECT SQL_CALC_FOUND_ROWS cha.emag_characteristic_id, cha.emag_characteristic_name, cat.emag_category_id, cat.emag_category_name, IFNULL(ea.`attribute_id`, 0) as attribute_id, IFNULL(eo.`option_id`, 0) as option_id
			FROM `".DB_PREFIX."emagmp_categories` as c
			JOIN ".DB_PREFIX."emagmp_category_definitions as cat ON (c.emag_category_id = cat.emag_category_id)
			JOIN `".DB_PREFIX."emagmp_characteristic_definitions` as cha ON (cha.emag_category_id = cat.emag_category_id)
			LEFT JOIN `".DB_PREFIX."emagmp_attributes` ea ON (cha.`emag_characteristic_id` = ea.`emag_characteristic_id` AND cha.`emag_category_id` = ea.`emag_category_id`)
			LEFT JOIN `".DB_PREFIX."emagmp_options` eo ON (cha.`emag_characteristic_id` = eo.`emag_characteristic_id` AND cha.`emag_category_id` = eo.`emag_category_id`)
			WHERE c.sync_active = 1
			ORDER BY cat.emag_category_name, cha.display_order, cha.emag_characteristic_id
			LIMIT ".(($pagination_set - 1) * $pagination_limit).", ".$pagination_limit."
		");
		foreach ($query->rows as $row) {
			$data['emag_characteristic_definitions'][] = $row;
		}

		$query = $this->db->query("SELECT FOUND_ROWS() as total_results");
		$total_results = $query->row['total_results'];
		
		$pagination = new Pagination();
		$pagination->total = $total_results;
		$pagination->page = $pagination_set;
		$pagination->limit = $pagination_limit;
		$pagination->text = $this->language->get('text_pagination');
		$pagination->url = $this->url->link('extension/module/emagmarketplace', 'page=characteristics&user_token=' . $this->session->data['user_token'] . '&pagination_set={page}', true);	
		$data['pagination'] = $pagination->render();
		
		$data['attributes'] = array();

		$query = $this->db->query("
			SELECT a.attribute_id, ad.name as attribute_name, agd.name AS attribute_group
			FROM " . DB_PREFIX . "attribute a
			LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id)
			LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (agd.attribute_group_id = a.attribute_group_id AND agd.language_id = '" . (int)$this->config->get('module_emagmp_product_language_id') . "')
			WHERE ad.language_id = '" . (int)$this->config->get('module_emagmp_product_language_id') . "'
		");
		foreach ($query->rows as $row) {
			$data['attributes'][] = $row;
		}
		
		$data['options'] = array();
		$query = $this->db->query("
			SELECT *, od.name as option_name
			FROM `" . DB_PREFIX . "option` o
			LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id)
			WHERE od.language_id = '" . (int)$this->config->get('module_emagmp_product_language_id') . "'
		");
		foreach ($query->rows as $row) {
			$data['options'][] = $row;
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dasboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('exmodule/emagmarketplace', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => 'Characteristic Mapping',
			'href' => $this->url->link('extension/module/emagmarketplace', 'page=characteristics&user_token=' . $this->session->data['user_token'], true),
		);

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/emagmarketplace_characteristics', $data));
	}
	
	public function update_characteristic() {
		$json = array();

		switch ($this->request->post['field']) {
			case "attribute":
				$sql_table = "emagmp_attributes";
				$sql_update = "attribute_id = ".(int)$this->request->post['attribute_id'];
				break;
			case "option":
				$sql_table = "emagmp_options";
				$sql_update = "option_id = ".(int)$this->request->post['option_id'];
				break;
		}

		$this->db->query("
			insert into ".DB_PREFIX."$sql_table
			set
			emag_characteristic_id = ".(int)$this->request->post['emag_characteristic_id'].",
			emag_category_id = ".(int)$this->request->post['emag_category_id'].",
			$sql_update
			on duplicate key update
			$sql_update
		");

		$json['success'] = 'Characteristic updated successfully!';

		$this->response->setOutput(json_encode($json));
	}
	
	public function upload_products() {
		$json = array();

		$this->model_extension_module_emagmarketplace->updateProduct(null, false);

		$json['success'] = 'Product upload queue started! Products will be uploaded in the background!';

		$this->response->setOutput(json_encode($json));
	}

	public function generate_awb() {
		$json = array();

		$order_id = $this->request->get['order_id'];
		$emag_locality_id = $this->request->get['emag_locality_id'];

		$query = $this->db->query('
			SELECT *
			FROM `'.DB_PREFIX.'emagmp_order_history`
			WHERE id_order = '.(int)$order_id.'
		');
		if (!$query->num_rows) {
			$json['error'] = 'This is not an eMAG Marketplace order!';
			
			$this->response->setOutput(json_encode($json));
			return;
		}
		$order_history = $query->row;

		$query = $this->db->query("SELECT * FROM `".DB_PREFIX."order` WHERE order_id = ".(int)$order_id);
		if (!$query->num_rows) {
			$json['error'] = 'Order doesn\'t exist!';

			$this->response->setOutput(json_encode($json));
			return;
		}
		$order = $query->row;

		if ($order_history['awb_id'])
		{
			$json['error'] = 'An eMAG Marketplace AWB has already been generated for this order! Please refresh this page and check the AWB download link!';
		}
		else
		{
			$tmp = explode(", ", $this->config->get('module_emagmp_awb_sender_locality'));
			$query = $this->db->query('
				SELECT * FROM `'.DB_PREFIX.'emagmp_locality_definitions`
				WHERE emag_region2_latin like \'%'.$this->db->escape($tmp[2]).'%\' or emag_region3_latin like \'%'.$this->db->escape($tmp[1]).'%\' or emag_name_latin like \'%'.$this->db->escape($tmp[0]).'%\'
			');
			$sender_locality_id = 0;
			if ($query->num_rows) {
				$sender_locality_id = $query->row['emag_locality_id'];
			}

			$order_history['emag_definition'] = unserialize($order_history['emag_definition']);

			$emagmp_api_call = new EmagMarketplaceAPICall();
			$emagmp_api_call->resource = 'awb';
			$emagmp_api_call->action = 'save';
			$emagmp_api_call->data = array(
				'order_id' => $order_history['emag_order_id'],
				'sender' => array(
					'name' => $this->config->get('module_emagmp_awb_sender_name'),
					'contact' => $this->config->get('module_emagmp_awb_sender_contact'),
					'phone1' => $this->config->get('module_emagmp_awb_sender_phone'),
					'locality_id' => $sender_locality_id,
					'street' => $this->config->get('module_emagmp_awb_sender_street')
				),
				'receiver' => array(
					'name' => $order['shipping_company'] ? $order['shipping_company'] : $order['shipping_firstname'].' '.$order['shipping_lastname'],
					'contact' =>  $order['shipping_firstname'].' '.$order['shipping_lastname'],
					'phone1' => $order['telephone'],
					'locality_id' => $emag_locality_id,
					'street' => $order['shipping_address_1']
				),
				'envelope_number' => 0,
				'parcel_number' => 1,
				'cod' => $order_history['emag_definition']->payment_mode_id == 1 ? $order['total'] : 0
			);

			$emagmp_api_call->id_order = $order_id;
			$emagmp_api_call->execute();
			$emagmp_api_call->save();
			
			if ($emagmp_api_call->status == 'success') {

				$order_history['last_definition'] = unserialize($order_history['last_definition']);
				if (!is_array($order_history['last_definition']))
					$order_history['last_definition'] = array();

				$order_history['last_definition']['status'] = 4;

				$awb_id = $emagmp_api_call->message_in_json->results->awb[0]->emag_id;

				$this->db->query('
					UPDATE `'.DB_PREFIX.'emagmp_order_history` SET
					last_definition = \''.$this->db->escape(serialize($order_history['last_definition'])).'\',
					awb_id = '.(int)$awb_id.'
					WHERE id_order = '.(int)$order_id.'
				');

				$awb_number = $emagmp_api_call->message_in_json->results->awb[0]->awb_number;
				
				$json['awb_url'] = $this->config->get('module_emagmp_url').'/awb/read_pdf?emag_id='.$order_history['awb_id'].'&code='.$this->config->get('module_emagmp_vendorcode').'&username='.$this->config->get('module_emagmp_vendorusername').'&hash='.base64_encode($this->config->get('module_emagmp_vendorpassword'));
			}
			else
				$json['error'] = 'The eMAG AWB could not be generated! The API function returned the following error: '.$emagmp_api_call->message_in;
		}

		$this->response->setOutput(json_encode($json));
	}

	public function call_logs() {

		$this->load->language('extension/module/emagmarketplace');

		$this->document->setTitle($this->language->get('heading_title'));

		$sql_conditions = array();

		$url = '';
		
		if (isset($this->request->get['filter_resource'])) {
			$data['filter_resource'] = $this->request->get['filter_resource'];
			$sql_conditions[] = "resource like '%".$this->db->escape($data['filter_resource'])."%'";
			$url .= '&filter_resource=' . urlencode(html_entity_decode($this->request->get['filter_resource'], ENT_QUOTES, 'UTF-8'));
		} else {
			$data['filter_resource'] = null;
		}

		if (isset($this->request->get['filter_action'])) {
			$data['filter_action'] = $this->request->get['filter_action'];
			$sql_conditions[] = "action like '%".$this->db->escape($data['filter_action'])."%'";
			$url .= '&filter_action=' . urlencode(html_entity_decode($this->request->get['filter_action'], ENT_QUOTES, 'UTF-8'));
		} else {
			$data['filter_action'] = null;
		}

		if (isset($this->request->get['filter_status'])) {
			$data['filter_status'] = $this->request->get['filter_status'];
			$sql_conditions[] = "status like '%".$this->db->escape($data['filter_status'])."%'";
			$url .= '&filter_status=' . urlencode(html_entity_decode($this->request->get['filter_status'], ENT_QUOTES, 'UTF-8'));
		} else {
			$data['filter_status'] = null;
		}

		if (isset($this->request->get['filter_message'])) {
			$data['filter_message'] = $this->request->get['filter_message'];
			$sql_conditions[] = "message_in like '%".$this->db->escape($data['filter_message'])."%'";
			$url .= '&filter_message=' . urlencode(html_entity_decode($this->request->get['filter_message'], ENT_QUOTES, 'UTF-8'));
		} else {
			$data['filter_message'] = null;
		}

		if (isset($this->request->get['pagination_set'])) {
			$pagination_set = (int)$this->request->get['pagination_set'];
		} else {
			$pagination_set = 1;
		}

		$pagination_limit = 20;
		if ($this->config->get('config_admin_limit')) {
			$pagination_limit = $this->config->get('config_admin_limit');
		}
		elseif ($this->config->get('config_limit_admin')) {
			$pagination_limit = $this->config->get('config_limit_admin');
		}

		$data['call_logs'] = array();

		$query = $this->db->query("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM `".DB_PREFIX."emagmp_api_calls`".
			($sql_conditions ? "WHERE ".implode(" AND ", $sql_conditions) : '')
			."ORDER BY id_emagmp_api_call DESC
			LIMIT ".(($pagination_set - 1) * $pagination_limit).", ".$pagination_limit."
		");

		foreach ($query->rows as $row) {
			$row['message'] = '';
			$message_in_json = json_decode($row['message_in']);
			if ($message_in_json) {
				$row['message'] = implode('<BR>', (array)$message_in_json->messages);
			}
			$data['call_logs'][] = $row;
		}

		$query = $this->db->query("SELECT FOUND_ROWS() as total_results");
        
		$total_results = $query->row['total_results'];

		$pagination = new Pagination();
		$pagination->total = $total_results;
		$pagination->page = $pagination_set;
		$pagination->limit = $pagination_limit;
        
		$pagination->text = $this->language->get('text_pagination');
        
		$pagination->url = $this->url->link('extension/module/emagmarketplace', 'page=call_logs&user_token=' . $this->session->data['user_token'] . $url . '&pagination_set={page}', true);	
        
		$data['pagination'] = $pagination->render();
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dasboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_extension'),
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/emagmarketplace', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text'      => 'Call Logs',
			'href'      => $this->url->link('extension/module/emagmarketplace', 'page=call_logs&user_token=' . $this->session->data['user_token'], true),
		);

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/emagmarketplace_call_logs', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/emagmarketplace')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}

	public function install() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('module_emagmp', array(
			'module_emagmp_url' => 'https://marketplace.emag.ro',
			'module_emagmp_api_url' => 'https://marketplace.emag.ro/api-3',
			'module_emagmp_vendorcode' => '',
			'module_emagmp_vendorusername' => '',
			'module_emagmp_vendorpassword' => '',
			'module_emagmp_product_queue_limit' => '20',
			'module_emagmp_product_option_required' => 1,
			'module_emagmp_product_warranty' => 0,
			'module_emagmp_product_language_id' => 1,
			'module_emagmp_order_state_id_initial' => 1,
			'module_emagmp_order_state_id_finalized' => 3,
			'module_emagmp_order_state_id_cancelled' => 7,
			'module_emagmp_handling_time' => 1,
			'module_emagmp_use_emag_awb' => 1,
			'module_emagmp_awb_sender_name' => '',
			'module_emagmp_awb_sender_contact' => '',
			'module_emagmp_awb_sender_phone' => '',
			'module_emagmp_awb_sender_locality' => '',
			'module_emagmp_awb_sender_street' => '',
		));

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_category_definitions` (
				`emag_category_id` int(11) unsigned NOT NULL,
				`emag_category_name` varchar(255) NOT NULL,
				PRIMARY KEY (`emag_category_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_characteristic_definitions` (
				`emag_characteristic_id` int(11) unsigned NOT NULL,
				`emag_category_id` int(11) unsigned NOT NULL,
				`emag_characteristic_name` varchar(255) NOT NULL,
				`display_order` int(11) NOT NULL,
				UNIQUE KEY `emag_characteristic_id` (`emag_characteristic_id`,`emag_category_id`),
				KEY `emag_category_id` (`emag_category_id`),
				KEY `display_order` (`display_order`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_family_type_definitions` (
				`emag_family_type_id` int(11) unsigned NOT NULL,
				`emag_category_id` int(11) unsigned NOT NULL,
				`emag_family_type_name` varchar(255) NOT NULL,
				UNIQUE KEY `emag_family_type_id` (`emag_family_type_id`,`emag_category_id`),
				KEY `emag_category_id` (`emag_category_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_locality_definitions` (
				`emag_locality_id` int(11) unsigned NOT NULL,
				`emag_region2_latin` varchar(255) NOT NULL,
				`emag_region3_latin` varchar(255) NOT NULL,
				`emag_name_latin` varchar(255) NOT NULL,
				PRIMARY KEY (`emag_locality_id`),
				KEY `emag_region2_latin` (`emag_region2_latin`),
				KEY `emag_region3_latin` (`emag_region3_latin`),
				KEY `emag_name_latin` (`emag_name_latin`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_categories` (
				`id_emagmp_category` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`category_id` int(11) unsigned NOT NULL,
				`emag_category_id` int(11) unsigned NOT NULL,
				`emag_family_type_id` int(11) unsigned NOT NULL,
				`commission` decimal(7,4) NOT NULL,
				`sync_active` tinyint(1) NOT NULL,
				PRIMARY KEY (`id_emagmp_category`),
				UNIQUE KEY `category_id` (`category_id`),
				KEY `emag_category_id` (`emag_category_id`),
				KEY `sync_active` (`sync_active`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_products` (
				`id_emagmp_product` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`product_id` int(11) unsigned NOT NULL,
				`emag_category_id` int(11) NOT NULL,
				`emag_family_type_id` int(11) NOT NULL,
				`commission` decimal(7,4) NOT NULL,
				PRIMARY KEY (`id_emagmp_product`),
				UNIQUE KEY `product_id` (`product_id`),
				KEY `emag_category_id` (`emag_category_id`),
				KEY `emag_family_type_id` (`emag_family_type_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_attributes` (
				`id_emagmp_attribute` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`emag_characteristic_id` int(11) unsigned NOT NULL,
				`emag_category_id` int(11) unsigned NOT NULL,
				`attribute_id` int(11) unsigned NOT NULL,
				PRIMARY KEY (`id_emagmp_attribute`),
				UNIQUE KEY `emag_characteristic_id` (`emag_characteristic_id`,`emag_category_id`),
				KEY `emag_category_id` (`emag_category_id`),
				KEY `attribute_id` (`attribute_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_options` (
				`id_emagmp_option` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`emag_characteristic_id` int(11) unsigned NOT NULL,
				`emag_category_id` int(11) unsigned NOT NULL,
				`option_id` int(11) unsigned NOT NULL,
				PRIMARY KEY (`id_emagmp_option`),
				UNIQUE KEY `emag_characteristic_id` (`emag_characteristic_id`,`emag_category_id`),
				KEY `emag_category_id` (`emag_category_id`),
				KEY `option_id` (`option_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_api_calls` (
				`id_emagmp_api_call` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`date_created` datetime NOT NULL,
				`resource` varchar(20) NOT NULL,
				`action` varchar(20) NOT NULL,
				`last_definition` longtext NOT NULL,
				`message_out` longtext NOT NULL,
				`message_in` longtext NOT NULL,
				`status` varchar(20) NOT NULL,
				`date_sent` datetime NOT NULL,
				`id_order` int(10) unsigned NOT NULL,
				PRIMARY KEY (`id_emagmp_api_call`),
				KEY `date_created` (`date_created`),
				KEY `status` (`status`),
				KEY `date_sent` (`date_sent`),
				KEY `id_order` (`id_order`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_product_combinations` (
				`combination_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`product_id` int(11) unsigned NOT NULL,
				`product_options` varchar(255) NOT NULL,
				`last_definition` longtext NOT NULL,
				PRIMARY KEY (`combination_id`),
				UNIQUE KEY `product_id` (`product_id`,`product_options`),
				KEY `product_options` (`product_options`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_order_history` (
				`id_emagmp_order_history` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`emag_order_id` int(11) unsigned NOT NULL,
				`original_emag_definition` longtext NOT NULL,
				`emag_definition` longtext NOT NULL,
				`id_order` int(11) unsigned NOT NULL,
				`last_definition` longtext NOT NULL,
				`awb_id` int(11) unsigned NOT NULL,
				`id_attachment` int(10) unsigned NOT NULL,
				PRIMARY KEY (`id_emagmp_order_history`),
				UNIQUE KEY `emag_order_id` (`emag_order_id`),
				KEY `id_order` (`id_order`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_order_vouchers` (
				`id_order` int(11) unsigned NOT NULL,
				`emag_voucher_id` int(11) unsigned NOT NULL,
				`id_order_cart_rule` int(11) unsigned NOT NULL,
				KEY `id_order` (`id_order`),
				KEY `id_order_cart_rule` (`id_order_cart_rule`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."emagmp_cron_jobs` (
				`name` varchar(100) NOT NULL,
				`running` tinyint(1) NOT NULL,
				`last_started` datetime NOT NULL,
				`last_ended` datetime NOT NULL,
				PRIMARY KEY (`name`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		$this->db->query("
			INSERT IGNORE INTO `".DB_PREFIX."emagmp_cron_jobs` (`name`, `running`, `last_started`, `last_ended`) VALUES
			('check_cron_jobs', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
			('check_errors', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
			('clean_logs', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
			('get_orders', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
			('import_order', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
			('refresh_definitions', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
			('run_queue', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
		");
	}
}

?>