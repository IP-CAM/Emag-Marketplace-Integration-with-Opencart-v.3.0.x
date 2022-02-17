<?php

class ControllerModuleEmagmarketplace extends Controller {
	
	protected $actions = array(
		'check_cron_jobs' => 'initCheckCronJobs',
		'check_errors' => 'initCheckErrors',
		'clean_logs' => 'initCleanLogs',
		'get_orders' => 'initGetOrders',
		'import_order' => 'initImportOrder',
		'refresh_definitions' => 'initRefreshDefinitions',
		'run_queue' => 'initRunQueue'
	);

	protected $payment_modes = array(
		'1' => array('code' => 'cod', 'name' => 'Cash on delivery'),
		'2' => array('code' => 'bank_transfer', 'name' => 'Bank transfer'),
		'3' => array('code' => 'online_card', 'name' => 'Online card payment')
	);
	
	public function index() {
		if (!$this->config->get('emagmp_url'))
			return;
			
		set_time_limit(700);
		ignore_user_abort(true);
		@ini_set('display_errors', 'on');
		
		if (!isset($this->request->get['action']))
			return;
			
		$action = $this->request->get['action'];
		
		if (!isset($this->actions[$action]))
			return;
			
		include_once DIR_APPLICATION.'../admin/model/module/emagmarketplace.php';

		$this->db->query('
			UPDATE `'.DB_PREFIX.'emagmp_cron_jobs`
			SET running = 1, last_started = \''.date('Y-m-d H:i:s').'\'
			WHERE `name` = \''.$this->db->escape($action).'\' and running = 0
		');
		if (!$this->db->countAffected())
		{
			//echo 'Already running';
			return;
		}
		
		try
		{
			$this->{$this->actions[$action]}();
		}
		catch (Exception $e)
		{
			echo 'Ooops! Exception caught: '.$e->getMessage();
		}
		
		$this->db->query('
			UPDATE `'.DB_PREFIX.'emagmp_cron_jobs`
			SET running = 0, last_ended = \''.date('Y-m-d H:i:s').'\'
			WHERE `name` = \''.$this->db->escape($action).'\'
		');
	}
	
	public function initCheckCronJobs()
	{
		$query = $this->db->query('
			SELECT *
			FROM `'.DB_PREFIX.'emagmp_cron_jobs`
			WHERE running = 1 and last_started < date_sub(\''.date('Y-m-d H:i:s').'\', interval 15 minute)
		');
		$stuck_jobs = array();
		foreach ($query->rows as $row)
		{
			$this->db->query('
				UPDATE `'.DB_PREFIX.'emagmp_cron_jobs`
				SET running = 0
				WHERE `name` = \''.$this->db->escape($row['name']).'\'
			');
			$stuck_jobs[] = $row['name'];
		}
		if (count($stuck_jobs))
		{
			$mail = new Mail(); 
			$mail->protocol = $this->config->get('config_mail_protocol');
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->hostname = $this->config->get('config_smtp_host');
			$mail->username = $this->config->get('config_smtp_username');
			$mail->password = $this->config->get('config_smtp_password');
			$mail->port = $this->config->get('config_smtp_port');
			$mail->timeout = $this->config->get('config_smtp_timeout');
			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($this->config->get('config_name'));
			$mail->setSubject(html_entity_decode('eMAG Marketplace Cron Job Errors', ENT_QUOTES, 'UTF-8'));
			$mail->setText(html_entity_decode("The following cron jobs have been running for too long and have been restarted:\r\n\r\n".implode("\r\n", $stuck_jobs), ENT_QUOTES, 'UTF-8'));
			$mail->send();
		}
	}
	
	public function initCheckErrors()
	{
		$query = $this->db->query('
			SELECT COUNT(*) as errors
			FROM `'.DB_PREFIX.'emagmp_api_calls`
			WHERE status = \'error\' and date_sent >= date_sub(\''.date('Y-m-d H:i:s').'\', interval 5 minute)
		');
		if ($query->num_rows && $query->row['errors'])
		{
			$mail = new Mail(); 
			$mail->protocol = $this->config->get('config_mail_protocol');
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->hostname = $this->config->get('config_smtp_host');
			$mail->username = $this->config->get('config_smtp_username');
			$mail->password = $this->config->get('config_smtp_password');
			$mail->port = $this->config->get('config_smtp_port');
			$mail->timeout = $this->config->get('config_smtp_timeout');
			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($this->config->get('config_name'));
			$mail->setSubject(html_entity_decode('eMAG Marketplace Cron Job Errors', ENT_QUOTES, 'UTF-8'));
			$mail->setText(html_entity_decode($query->row['errors']." eMAG Marketplace API calls have failed in the last 5 minutes!\nPlease check your API Call Logs for more details (filter by Status = 'error')!", ENT_QUOTES, 'UTF-8'));
			$mail->send();
		}
	}
	
	public function initCleanLogs()
	{
		$this->db->query('
			DELETE FROM `'.DB_PREFIX.'emagmp_api_calls`
			WHERE status in (\'success\', \'error\') and date_sent < date_sub(\''.date('Y-m-d H:i:s').'\', interval 30 day)
		');
	}
	
	public function initRunQueue()
	{
		$run_queue_limit = $this->config->get('emagmp_product_queue_limit');
		if (!$run_queue_limit)
			$run_queue_limit = 20;

		$queue_result = $this->db->query('
			SELECT *
			FROM `'.DB_PREFIX.'emagmp_api_calls`
			WHERE `status` = "pending"
			ORDER BY `id_emagmp_api_call`
			limit '.$run_queue_limit.'
		');
		
		if (!$queue_result)
			return;
			
		foreach ($queue_result->rows as $row)
		{
			$this->db->query('
				UPDATE `'.DB_PREFIX.'emagmp_api_calls`
				SET `status` = "running"
				WHERE `id_emagmp_api_call` = '.$row['id_emagmp_api_call'].' AND `status` = "pending"
			');
			
			if (!$this->db->countAffected())
				continue;
				
			$emagmp_api_call = new EmagMarketplaceAPICall($row['id_emagmp_api_call']);
			$emagmp_api_call->execute();
			$emagmp_api_call->save();
		}
	}
	
	public function initGetOrders()
	{
		$emagmp_api_call = new EmagMarketplaceAPICall();
		$emagmp_api_call->resource = 'order';
		$emagmp_api_call->action = 'read';
		$emagmp_api_call->data = array(
			'status' => 1,
			'currentPage' => 1,
			'itemsPerPage' => 2
		);
		
		// delete this when finished testing :)
		/*$emagmp_api_call->data = array(
			'status' => 2,
			'currentPage' => 1,
			'itemsPerPage' => 2
		);
		$this->db->query('
			TRUNCATE TABLE `'.DB_PREFIX.'emagmp_order_history`
		');*/
		// delete this when finished testing :)
		
		$emagmp_api_call->execute();
		$emagmp_api_call->save();
		
		if ($emagmp_api_call->status == 'error')
			return;
		
		if (!count($emagmp_api_call->message_in_json->results))
			return;

		foreach ($emagmp_api_call->message_in_json->results as $emag_order)
		{
		/*$emag_order = unserialize(file_get_contents(DIR_APPLICATION.'../emagmp_test.txt'));
		foreach (array($emag_order) as $emag_order) {*/
			$ch = curl_init();
			$url = str_replace('&amp;', '&', $this->url->link('module/emagmarketplace', 'action=import_order'));
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('emag_order' => json_encode($emag_order))));
			$result = curl_exec($ch);
			echo $result;
			curl_close($ch);
		}
	}
	
	public function initImportOrder()
	{
		$emag_order = json_decode(html_entity_decode($this->request->post['emag_order']));
		
		if (!$emag_order)
			return;
		
		//echo "----------------------------------------------\n";
		
		$emag_order->id = (int)$emag_order->id;
		
		$errors = array();
		
		if (!isset($this->payment_modes[$emag_order->payment_mode_id]))
		{
			$errors[] = "Invalid payment_mode_id for eMAG order #".$emag_order->id;
			$this->emailErrors($errors);
			return;
		}
		
		$query = $this->db->query('
			SELECT *
			FROM `'.DB_PREFIX.'emagmp_order_history`
			WHERE emag_order_id = '.$emag_order->id.'
		');
		if ($query->num_rows)
			return;

		$this->db->query('
			INSERT IGNORE INTO `'.DB_PREFIX.'emagmp_order_history`
			SET emag_order_id = '.$emag_order->id.',
			original_emag_definition = \''.$this->db->escape(serialize($emag_order)).'\',
			emag_definition = \''.$this->db->escape(serialize($emag_order)).'\'
		');
		$id_emagmp_order_history = $this->db->getLastId();
		//echo $id_emagmp_order_history."\n";
		if (!$id_emagmp_order_history)
		{
			// return anyway
			return;
		}
		
		/*echo "__IMPORT__\n";
		print_r($emag_order);
		//return;
		echo "\n";*/
		
		$no_imported = 0;
		
		$this->load->model('catalog/product');
		
		$nop = 0;
		foreach ($emag_order->products as $emag_product) {
			if (!$emag_product->status)
				continue;

			$emag_product->product_id = trim($emag_product->product_id);
			$query = $this->db->query('
				SELECT *
				FROM `'.DB_PREFIX.'emagmp_product_combinations`
				WHERE combination_id = '.(int)$emag_product->product_id.'
			');
			if (!$query->num_rows)
			{
				$errors[] = "Product REF '".$emag_product->part_number."' not found for eMAG order #".$emag_order->id;
				continue;
			}
			$product_id = $query->row['product_id'];
			$product_options = $query->row['product_options'];
			$qty = trim($emag_product->quantity);
			$qty = (int)$qty;
			
			$product_info = $this->model_catalog_product->getProduct($product_id);
			
			if (!$product_info) {
				$errors[] = "Product REF '".$emag_product->part_number."' not found for eMAG order #".$emag_order->id;
				continue;
			}
			
			$option = array();
			if ($product_options) {
				$query = $this->db->query("
					SELECT *
					FROM ".DB_PREFIX."product_option_value
					WHERE product_id = ".$product_id." and option_value_id in (".preg_replace("`-`", ",", $product_options).")
				");
				foreach ($query->rows as $row) {
					$option[$row['product_option_id']] = $row['product_option_value_id'];
				}
			}

			$qty = max(0, $qty);
			
			if ($qty == 0)
				continue;

    		if (!$option) {
      			$key = (int)$product_id;
    		} else {
      			$key = (int)$product_id . ':' . base64_encode(serialize($option));
    		}
    		
    		$key2 = array('product_id' => (int)$product_id);
    		if ($option) {
    			$key2['option'] = $option;
			}
			$key2 = base64_encode(serialize($key2));
    		
			$this->session->data['emagmp_price_'.$key] = (float)trim($emag_product->sale_price);
			$this->session->data['emagmp_price_'.$key2] = $this->session->data['emagmp_price_'.$key];
			$this->cart->add($product_id, $qty, $option);
			
			$nop++;
		}
		
		if (!$nop)
		{
			$errors[] = "No valid products found for eMAG order #".$emag_order->id;
			$this->emailErrors($errors);
			return;
		}
		
		if ($errors)
			$this->emailErrors($errors);
		
		$shipping_cost = (float)trim($emag_order->shipping_tax);
		
		$tax_rates = $this->tax->getRates($shipping_cost, $this->config->get('flat_tax_class_id'));
		$first_tax_rate = current($tax_rates);
		$tax_rate = round($first_tax_rate['rate'] / 100, 2);
		
		$shipping_cost = $shipping_cost / (1 + $tax_rate);
		$this->load->language('shipping/flat');
		$this->session->data['shipping_method'] = array(
        	'code'         => 'flat.flat',
        	'title'        => $this->language->get('text_description'),
        	'cost'         => $shipping_cost,
        	'tax_class_id' => $this->config->get('flat_tax_class_id'),
			'text'         => $this->currency->format($this->tax->calculate($shipping_cost, $this->config->get('flat_tax_class_id'), $this->config->get('config_tax')))
      	);
					
		$total_data = array();
		$total = 0;
		$taxes = $this->cart->getTaxes();
		
		$sort_order = array();
		
		if (intval(VERSION) == 2) {
			$this->load->model('extension/extension');
			$results = $this->model_extension_extension->getExtensions('total');
		}
		else {
			$this->load->model('setting/extension');
			$results = $this->model_setting_extension->getExtensions('total');
		}
		
		foreach ($results as $key => $value) {
			$sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
		}
		
		array_multisort($sort_order, SORT_ASC, $results);
		
		foreach ($results as $result) {
			if ($this->config->get($result['code'] . '_status')) {
				$this->load->model('total/' . $result['code']);
	
				$this->{'model_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
			}
		}
		
		$sort_order = array(); 
	  
		foreach ($total_data as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $total_data);
		
		$data = array();
			
		$data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
		$data['store_id'] = $this->config->get('config_store_id');
		$data['store_name'] = $this->config->get('config_name');
		$data['store_url'] = $this->config->get('config_url');
		
		$data['customer_id'] = 0;
		$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		if (preg_match('`(.+?)\s(.+)`', $emag_order->customer->name, $match)) {
			$data['firstname'] = $match[1];
			$data['lastname'] = $match[2];
		}
		else {
			$data['firstname'] = $emag_order->customer->name;
			$data['lastname'] = 'Customer';
		}
		$data['email'] = $emag_order->customer->email;
		if (!$data['email'])
			$data['email'] = $this->config->get('config_email');
		$data['telephone'] = $emag_order->customer->phone_1 ? $emag_order->customer->phone_1 : $emag_order->customer->phone_2;
		$data['fax'] = '';
		
		$data['payment_firstname'] = $data['firstname'];
		$data['payment_lastname'] = $data['lastname'];	
		$data['payment_company'] = $emag_order->customer->company;	
		$data['payment_company_id'] = '';	
		$data['payment_tax_id'] = $emag_order->customer->code;	
		$data['payment_address_1'] = $emag_order->customer->billing_street;
		$data['payment_address_2'] = '';
		$data['payment_city'] = $emag_order->customer->billing_city;
		$data['payment_postcode'] = $emag_order->customer->billing_postal_code;
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($emag_order->customer->billing_country) . "' AND status = '1'");
		if ($query->num_rows) {
			$data['payment_country'] = $query->row['name'];
			$data['payment_country_id'] = $query->row['country_id'];
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE country_id = '" . (int)$query->row['country_id'] . "' AND name = '" . $this->db->escape($emag_order->customer->billing_suburb) . "' AND status = '1'");
			if ($query->num_rows) {
				$data['payment_zone'] = $query->row['name'];
				$data['payment_zone_id'] = $query->row['zone_id'];
			}
			else {
				$data['payment_zone'] = '';
				$data['payment_zone_id'] = 0;
				$data['payment_address_2'] = $emag_order->customer->billing_suburb;
			}
		}
		else {
			$data['payment_country'] = '';
			$data['payment_country_id'] = 0;
		}
		$data['payment_address_format'] = '';
	
		$data['payment_method'] = $this->payment_modes[$emag_order->payment_mode_id]['name'];
		$data['payment_code'] = $this->payment_modes[$emag_order->payment_mode_id]['code'];
					
		$data['shipping_firstname'] = $data['firstname'];
		$data['shipping_lastname'] = $data['lastname'];	
		$data['shipping_company'] = $emag_order->customer->company;	
		$data['shipping_address_1'] = $emag_order->customer->shipping_street;
		$data['shipping_address_2'] = '';
		$data['shipping_city'] = $emag_order->customer->shipping_city;
		$data['shipping_postcode'] = $emag_order->customer->shipping_postal_code;
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($emag_order->customer->shipping_country) . "' AND status = '1'");
		if ($query->num_rows) {
			$data['shipping_country'] = $query->row['name'];
			$data['shipping_country_id'] = $query->row['country_id'];
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE country_id = '" . (int)$query->row['country_id'] . "' AND name = '" . $this->db->escape($emag_order->customer->shipping_suburb) . "' AND status = '1'");
			if ($query->num_rows) {
				$data['shipping_zone'] = $query->row['name'];
				$data['shipping_zone_id'] = $query->row['zone_id'];
			}
			else {
				$data['shipping_zone'] = '';
				$data['shipping_zone_id'] = 0;
				$data['shipping_address_2'] = $emag_order->customer->billing_suburb;
			}
		}
		else {
			$data['shipping_country'] = '';
			$data['shipping_country_id'] = 0;
		}
		$data['shipping_address_format'] = '';
	
		$data['shipping_method'] = 'Flat Shipping Rate';
		$data['shipping_code'] = 'flat.flat';
		
		$product_data = array();
	
		foreach ($this->cart->getProducts() as $product) {
			$option_data = array();

			foreach ($product['option'] as $option) {
				if (intval(VERSION) == 2) {
					if ($option['type'] != 'file') {
						$value = $option['value'];
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$value = $upload_info['name'];
						} else {
							$value = '';
						}
					}
				}
				else {
					if ($option['type'] != 'file') {
						$value = $option['option_value'];	
					} else {
						$value = $this->encryption->decrypt($option['option_value']);
					}
				}
				
				$option_data[] = array(
					'product_option_id'       => $option['product_option_id'],
					'product_option_value_id' => $option['product_option_value_id'],
					'option_id'               => $option['option_id'],
					'option_value_id'         => $option['option_value_id'],								   
					'name'                    => $option['name'],
					'value'                   => $value,
					'type'                    => $option['type']
				);
			}
			
			$product_data[] = array(
				'product_id' => $product['product_id'],
				'name'       => $product['name'],
				'model'      => $product['model'],
				'option'     => $option_data,
				'download'   => $product['download'],
				'quantity'   => $product['quantity'],
				'subtract'   => $product['subtract'],
				'price'      => $product['price'],
				'total'      => $product['total'],
				'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
				'reward'     => $product['reward']
			); 
		}
		
		// Gift Voucher
		$voucher_data = array();
					
		$data['products'] = $product_data;
		$data['vouchers'] = $voucher_data;
		$data['totals'] = $total_data;
		$data['comment'] = $emag_order->observation;
		$data['total'] = $total;
		
		$data['affiliate_id'] = 0;
		$data['commission'] = 0;
		$data['marketing_id'] = 0;
		$data['tracking'] = '';
		
		$data['language_id'] = $this->config->get('config_language_id');
		$data['currency_id'] = $this->currency->getId();
		$data['currency_code'] = $this->currency->getCode();
		$data['currency_value'] = $this->currency->getValue($this->currency->getCode());
		$data['ip'] = $this->request->server['REMOTE_ADDR'];
		$data['forwarded_ip'] = '';
		$data['user_agent'] = '';
		$data['accept_language'] = '';
					
		$this->load->model('checkout/order');
		
		$order_id = $this->model_checkout_order->addOrder($data);
		
		if ($order_id) {
			if (intval(VERSION) == 2) {
				$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('emagmp_order_state_id_initial'));
			}
			else {
				$this->model_checkout_order->confirm($order_id, $this->config->get('emagmp_order_state_id_initial'));
			}
			
			$this->db->query('
				UPDATE `'.DB_PREFIX.'emagmp_order_history`
				SET id_order = '.(int)$order_id.'
				WHERE id_emagmp_order_history = '.$id_emagmp_order_history.'
			');
			
			$emagmp_api_call = new EmagMarketplaceAPICall();
			$emagmp_api_call->resource = 'order';
			$emagmp_api_call->action = 'acknowledge';
			$emagmp_api_call->data = array(
				'id' => $emag_order->id
			);
			$emagmp_api_call->execute();
			$emagmp_api_call->save();
			
			$emag_marketplace_module = new ModelModuleEmagmarketplace($this->registry);
			$emag_marketplace_module->updateOrder($order_id);
			
			//echo "Imported ".(++$no_imported)."\n";
		}
	}
		
	public function emailErrors($errors)
	{
		if ($errors)
		{
			$mail = new Mail(); 
			$mail->protocol = $this->config->get('config_mail_protocol');
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->hostname = $this->config->get('config_smtp_host');
			$mail->username = $this->config->get('config_smtp_username');
			$mail->password = $this->config->get('config_smtp_password');
			$mail->port = $this->config->get('config_smtp_port');
			$mail->timeout = $this->config->get('config_smtp_timeout');
			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($this->config->get('config_name'));
			$mail->setSubject(html_entity_decode('eMAG Marketplace Import Errors', ENT_QUOTES, 'UTF-8'));
			$mail->setText(html_entity_decode("The following errors have been found when importing orders from eMAG Marketplace:\r\n\r\n".implode("\r\n", $errors), ENT_QUOTES, 'UTF-8'));
			$mail->send();
		}
	}
	
	public function initRefreshDefinitions()
	{
	}
}

?>