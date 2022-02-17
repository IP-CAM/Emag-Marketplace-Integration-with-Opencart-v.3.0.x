<?php
class ModelExtensionModuleEmagmarketplace extends Model {
	public function updateOrder($order_id) {
		$order_id = (int)$order_id;

		if (!$this->config->get('module_emagmp_url'))
			return;

		$query = $this->db->query('
			SELECT *
			FROM `'.DB_PREFIX.'emagmp_order_history`
			WHERE id_order = '.$order_id.'
		');

		if (!$query->num_rows)
			return;

		$order_history = $query->row;

		$order_history['emag_definition'] = unserialize($order_history['emag_definition']);
		//$order_history['emag_definition'] = unserialize(file_get_contents(DIR_APPLICATION.'../emagmp_test.txt'));

		$order_history['last_definition'] = unserialize($order_history['last_definition']);
		if (!is_array($order_history['last_definition']))
			$order_history['last_definition'] = array();

		$query = $this->db->query("
			SELECT o.*, IFNULL(ot.value, 0) AS shipping_total
			FROM `" . DB_PREFIX . "order` o
			LEFT JOIN " . DB_PREFIX . "order_total AS ot ON (o.order_id = ot.order_id AND ot.code = 'shipping')
			WHERE o.order_id = '" . (int)$order_id . "'
		");

		if (!$query->num_rows)
			return;

		$order = $query->row;

		$status = null;

		$emagmp_order_states = array(
			'emagmp_order_state_id_initial' => 2,
			'emagmp_order_state_id_finalized' => 4,
			'emagmp_order_state_id_cancelled' => 0
		);
		foreach ($emagmp_order_states as $state_name => $state_id) {
			if ($order['order_status_id'] == $this->config->get($state_name)) {
				$status = $emagmp_order_states[$state_name];
				break;
			}
		}

		if ($status === null) {
			if (count($order_history['last_definition']))
				$status = $order_history['last_definition']['status'];
			else
				return;
		}

		$emag_order_products = array();
		foreach ($order_history['emag_definition']->products as $emag_order_product)
		{
			$emag_order_product->status = 0;
			$emag_order_products[$emag_order_product->product_id] = $emag_order_product;
		}

		$order_products = array();
		$query = $this->db->query("
			SELECT op.*, GROUP_CONCAT(pov.option_value_id ORDER BY pov.option_value_id SEPARATOR '-') as product_options
			FROM " . DB_PREFIX . "order_product AS op
			LEFT JOIN " . DB_PREFIX . "order_option AS oo ON (op.order_product_id = oo.order_product_id)
			LEFT JOIN " . DB_PREFIX . "product_option_value AS pov ON (oo.product_option_value_id = pov.product_option_value_id)
			WHERE op.order_id = $order_id
			GROUP BY op.order_product_id
		");
		$order_products = $query->rows;
		
		foreach ($order_products as $order_product)
		{
			$product_combination_info = $this->get_combination_info($order_product['product_id'], $order_product['product_options'], true);
			$product_id = $product_combination_info['combination_id'];
			
			if (!isset($emag_order_products[$product_id]))
				$emag_order_products[$product_id] = new stdClass();
				
			$emag_order_products[$product_id]->product_id = $product_id;
			$emag_order_products[$product_id]->status = 1;
			$emag_order_products[$product_id]->quantity = $order_product['quantity'];
			$emag_order_products[$product_id]->sale_price = $order_product['price'];
		}
		
		$products = array();
		foreach ($emag_order_products as $emag_order_product)
		{
			$products[] = (array) $emag_order_product;
		}

		$attachments = array();
		$order_invoices = array();
		foreach ($order_invoices as $invoice)
		{
			$attachments[] = array(
				'name' => '',
				'url' => '',
				'type' => 1
			);
		}

		require_once(DIR_SYSTEM . 'library/cart/customer.php');
		require_once(DIR_SYSTEM . 'library/cart/tax.php');

		$this->registry->set('customer', new Customer($this->registry));

		$tax = new Tax($this->registry);
		$tax_rates = $tax->getRates($order['shipping_total'], $this->config->get('flat_tax_class_id'));
		$first_tax_rate = current($tax_rates);
		$tax_rate = round($first_tax_rate['rate'] / 100, 2);
		$order['shipping_tax_rate'] = $tax_rate;
		$order['shipping_total_with_tax'] = round($order['shipping_total'] * (1 + $tax_rate), 2);

		$new_definition = array(
			'status' => $status,
			'id' => $order_history['emag_order_id'],
			'products' => $products,
			'shipping_tax' => number_format($order['shipping_total_with_tax'], 2, ".", ""),
			'attachments' => $attachments
		);

		$emag_definition = (array) $order_history['emag_definition'];
		foreach ($emag_definition as $key => $value)
		{
			if (is_object($value))
				$value = (array) $value;

			if (is_array($value))
			{
				foreach ($value as $k => $v)
				{
					if (is_object($v))
						$value[$k] = (array) $v;
				}
			}
				
			if (!isset($new_definition[$key]))
				$new_definition[$key] = $value;
		}

		$emagmp_api_call = new EmagMarketplaceAPICall();
		$emagmp_api_call->resource = 'order';
		$emagmp_api_call->action = 'save';
		$emagmp_api_call->data = array(
			$new_definition
		);

		$emagmp_api_call->last_definition = serialize($new_definition);
		$emagmp_api_call->id_order = $order['order_id'];
		$emagmp_api_call->execute();
		$emagmp_api_call->save();
		
		return $this->refreshEmagOrder($order, $order_history);
	}

	public function refreshEmagOrder($order, $order_history)
	{
		if (!$this->config->get('module_emagmp_url'))
			return;
			
		// re-read order & update if necessary
		
		if (!isset($GLOBALS['EMAGMP_REFRESH_ORDER_'.$order['order_id']]))
			$GLOBALS['EMAGMP_REFRESH_ORDER_'.$order['order_id']] = 0;
			
		$GLOBALS['EMAGMP_REFRESH_ORDER_'.$order['order_id']]++;
		
		if ($GLOBALS['EMAGMP_REFRESH_ORDER_'.$order['order_id']] > 5)
		{
			return;
		}

		$emagmp_api_call = new EmagMarketplaceAPICall();
		$emagmp_api_call->resource = 'order';
		$emagmp_api_call->action = 'read';
		$emagmp_api_call->data = array(
			'id' => $order_history['emag_order_id']
		);
		$emagmp_api_call->execute();
		$emagmp_api_call->save();
		
		if ($emagmp_api_call->status != 'success')
			return;
		
		$emag_order = $emagmp_api_call->message_in_json->results[0];
		//$emag_order = unserialize(file_get_contents(DIR_APPLICATION.'../emagmp_test.txt'));
		
		$order_updated = false;
		
		require_once(DIR_SYSTEM . 'library/cart/customer.php');
		require_once(DIR_SYSTEM . 'library/cart/tax.php');
		
		$this->registry->set('customer', new Customer($this->registry));
		
		$tax = new Tax($this->registry);
		
		$option_values = array();
		$query = $this->db->query("
			SELECT ov.option_value_id, od.name, o.type, ovd.name as value
			FROM `" . DB_PREFIX . "option` o
			LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id)
			LEFT JOIN " . DB_PREFIX . "option_value ov ON (o.option_id = ov.option_id)
			LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id)
			WHERE od.language_id = '" . (int)$this->config->get('module_emagmp_product_language_id') . "' AND ovd.language_id = '" . (int)$this->config->get('module_emagmp_product_language_id') . "'
		");
		foreach ($query->rows as $row) {
			$option_values[$row['option_value_id']] = $row;
		}
		
		$order_totals = array();
		$query = $this->db->query("
			SELECT *
			FROM `".DB_PREFIX."order_total`
			WHERE order_id = ".$order['order_id']."
		");
		foreach ($query->rows as $row) {
			$order_totals[$row['code']] = $row['value'];
		}

		$order_totals['shipping_tax'] = $order['shipping_total_with_tax'] - $order['shipping_total'];
		$order_totals['sub_total_tax'] = $order_totals['tax'] - $order_totals['shipping_tax'];

		// refresh shipping tax
		
		if (round(trim($emag_order->shipping_tax), 2) != round($order['shipping_total_with_tax'], 2))
		{
			$shipping_diff_with_tax = (round(trim($emag_order->shipping_tax), 2) - round($order['shipping_total_with_tax'], 2));
			$shipping_diff = $shipping_diff_with_tax / (1 + $order['shipping_tax_rate']);
					
			$order_totals['shipping'] += $shipping_diff;
			$order_totals['tax'] += $shipping_diff_with_tax - $shipping_diff;
			
			$order_totals['shipping_tax'] += $shipping_diff_with_tax - $shipping_diff;
			
			$order['total'] += $shipping_diff_with_tax;
			
			$order_updated = true;
		}
		
		// refresh products
		if (count($emag_order->products))
		{
			$products = array();
			
			$order_products = array();
			$query = $this->db->query("
				SELECT op.*, GROUP_CONCAT(pov.option_value_id ORDER BY pov.option_value_id SEPARATOR '-') as product_options
				FROM " . DB_PREFIX . "order_product AS op
				LEFT JOIN " . DB_PREFIX . "order_option AS oo ON (op.order_product_id = oo.order_product_id)
				LEFT JOIN " . DB_PREFIX . "product_option_value AS pov ON (oo.product_option_value_id = pov.product_option_value_id)
				WHERE op.order_id = " . (int)$order['order_id'] . "
				GROUP BY op.order_product_id
			");
			$order_products = $query->rows;
			foreach ($order_products as $order_product)
			{
				$product_combination_info = $this->get_combination_info($order_product['product_id'], $order_product['product_options'], true);
				$products[$product_combination_info['combination_id']]['old'] = array(
					'price' => round($order_product['price'], 2),
					'quantity' => (int)$order_product['quantity'],
					'order_product_id' => $order_product['order_product_id']
				);
			}
			
			$emag_order_products = array();
			foreach ($emag_order->products as $emag_product)
			{
				if (!$emag_product->status)
					continue;
				$products[$emag_product->product_id]['new'] = array(
					'price' => round($emag_product->sale_price, 2),
					'quantity' => (int)$emag_product->quantity
				);
			}
			
			if ($order_totals['sub_total'] <= 0) {
				$sub_total_tax_rate = 0;
			}
			else {
				$sub_total_tax_rate = $order_totals['sub_total_tax'] / $order_totals['sub_total'];
			}
			
			foreach ($products as $combination_id => $values)
			{
				// update existing products
				
				if (isset($values['old']) && isset($values['new']))
				{
					if ($values['old']['price'] == $values['new']['price'] && $values['old']['quantity'] == $values['new']['quantity'])
						continue;
						
					$this->db->query("
						UPDATE `" . DB_PREFIX . "order_product` SET
						quantity = '" . (int)$values['new']['quantity'] . "',
						price = '" . (float)$values['new']['price'] . "',
						total = '" . ((float)$values['new']['price'] * $values['new']['quantity']) . "',
						reward = (reward / quantity) * " . (int)$values['new']['quantity'] . "
						WHERE order_product_id = " . (int)$values['old']['order_product_id'] . "
					");
					
					$sub_total_diff = $values['new']['price'] * $values['new']['quantity'] - $values['old']['price'] * $values['old']['quantity'];
					
					$order_totals['sub_total'] += $sub_total_diff;
					$order_totals['tax'] += $sub_total_tax_rate * $sub_total_diff;
					
					$order['total'] += $sub_total_diff + $sub_total_tax_rate * $sub_total_diff;
					
					$order_updated = true;
				}
				
				// delete existing products
				
				elseif (isset($values['old']) && !isset($values['new']))
				{
					$this->db->query("
						DELETE FROM `" . DB_PREFIX . "order_product`
						WHERE order_product_id = " . (int)$values['old']['order_product_id'] . "
					");
					
					$this->db->query("
						DELETE FROM `" . DB_PREFIX . "order_option`
						WHERE order_product_id = " . (int)$values['old']['order_product_id'] . "
					");
					
					$sub_total_diff = 0 - $values['old']['price'] * $values['old']['quantity'];
					
					$order_totals['sub_total'] += $sub_total_diff;
					$order_totals['tax'] += $sub_total_tax_rate * $sub_total_diff;
					
					$order['total'] += $sub_total_diff + $sub_total_tax_rate * $sub_total_diff;
					
					$order_updated = true;
				}
				
				// add new products
				
				elseif (!isset($values['old']) && isset($values['new']))
				{
					$query = $this->db->query('
						SELECT *
						FROM `'.DB_PREFIX.'emagmp_product_combinations`
						WHERE combination_id = '.(int)$combination_id.'
					');
					if (!$query->num_rows) {
						continue;
					}
					$product_id = $query->row['product_id'];
					$product_options = $query->row['product_options'];
					
					$product_info = $this->model_catalog_product->getProduct($product_id);
					
					if (!$product_info) {
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
							$option[$row['product_option_id']] = $row;
						}
					}

					$product_reward_query = $this->db->query("SELECT points FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");
					
					if ($product_reward_query->num_rows) {	
						$reward = $product_reward_query->row['points'];
					} else {
						$reward = 0;
					}
					
					$this->db->query("
						INSERT INTO " . DB_PREFIX . "order_product SET
						order_id = '" . (int)$order['order_id'] . "',
						product_id = '" . (int)$product_info['product_id'] . "',
						name = '" . $this->db->escape($product_info['name']) . "',
						model = '" . $this->db->escape($product_info['model']) . "',
						quantity = '" . (int)$values['new']['quantity'] . "',
						price = '" . (float)$values['new']['price'] . "',
						total = '" . ((float)$values['new']['price'] * $values['new']['quantity']) . "',
						tax = '" . (float)$tax->getTax($values['new']['price'], $product_info['tax_class_id']) . "',
						reward = '" . (int)($reward * $values['new']['quantity']) . "'
					");
			
					$order_product_id = $this->db->getLastId();
		
					if (count($option)) {
						foreach ($option as $product_option_id => $product_option_value) {
							$this->db->query("
								INSERT INTO " . DB_PREFIX . "order_option SET
								order_id = '" . (int)$order['order_id'] . "',
								order_product_id = '" . (int)$order_product_id . "',
								product_option_id = '" . (int)$product_option_id . "',
								product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "',
								name = '" . $this->db->escape($option_values[$product_option_value['option_value_id']]['name']) . "',
								`value` = '" . $this->db->escape($option_values[$product_option_value['option_value_id']]['value']) . "',
								`type` = '" . $this->db->escape($option_values[$product_option_value['option_value_id']]['type']) . "'
							");
						}
					}
					
					$sub_total_diff = $values['new']['price'] * $values['new']['quantity'];
					
					$order_totals['sub_total'] += $sub_total_diff;
					$order_totals['tax'] += $sub_total_tax_rate * $sub_total_diff;
					
					$order['total'] += $sub_total_diff + $sub_total_tax_rate * $sub_total_diff;
				
					$order_updated = true;
				}
			}
		}
		
		// refresh vouchers
		
		if (count($emag_order->vouchers))
		{
			$order_cart_rules = array();
			$query = $this->db->query('
				SELECT *
				FROM `'.DB_PREFIX.'emagmp_order_vouchers` ov
				JOIN `'.DB_PREFIX.'order_total` ot ON ot.order_total_id = ov.id_order_cart_rule AND ot.order_id = '.$order['order_id'].' and ot.code = \'voucher\'
				WHERE ov.`id_order` = '.$order['order_id'].'
			');
			foreach ($query->rows as $row)
			{
				$order_cart_rules[$row['emag_voucher_id']] = $row;
			}
			//echo "order_cart_rules:\n"; print_r($order_cart_rules);
			
			foreach ($emag_order->vouchers as $emag_voucher)
			{
				//echo 'emag_voucher_id: '.$emag_voucher->id."\n\n";
				if (!isset($order_cart_rules[$emag_voucher->id]))
				{
					$voucher_code = substr(md5(mt_rand()), 0, 10);
					$voucher_amount = round(abs($emag_voucher->sale_price + $emag_voucher->sale_price_vat), 2);
					
					$this->db->query("
						INSERT INTO " . DB_PREFIX . "voucher SET
						order_id = '" . (int)$order['order_id'] . "',
						code = '" . $this->db->escape($voucher_code) . "',
						from_name = 'eMAG Marketplace',
						from_email = '',
						to_name = 'Order #".$order['order_id']."',
						to_email = '',
						voucher_theme_id = '" . 0 . "',
						message = '',
						amount = '" . $voucher_amount . "',
						status = '1',
						date_added = NOW()
					");
					$voucher_id = $this->db->getLastId();
					
					$this->db->query("
						INSERT INTO ".DB_PREFIX."order_total SET
						order_id = ".$order['order_id'].",
						code = 'voucher',
						title = '".$this->db->escape($emag_voucher->voucher_name)."',
						" . (intval(VERSION) < 2 ? "text = '".$this->db->escape($this->currency->format(-$voucher_amount))."'," : "") . "
						value = '".-$voucher_amount."',
						sort_order = '".$this->db->escape($this->config->get('voucher_sort_order'))."'
					");
					$order_total_id = $this->db->getLastId();
		
					$this->db->query('
						INSERT INTO `'.DB_PREFIX.'emagmp_order_vouchers`
						SET id_order = '.$order['order_id'].',
						emag_voucher_id = '.$emag_voucher->id.',
						id_order_cart_rule = '.$order_total_id.'
					');
					
					$order['total'] -= $voucher_amount;
					
					$order_updated = true;
				}
				else
				{
					$voucher_amount = round(abs($emag_voucher->sale_price + $emag_voucher->sale_price_vat), 2);
					$old_amount = round(abs($order_cart_rules[$emag_voucher->id]['value']), 2);
					if ($old_amount != $voucher_amount)
					{
						$diff_amount = $voucher_amount - $old_amount;
						$this->db->query("
							UPDATE ".DB_PREFIX."order_total SET
							" . (intval(VERSION) < 2 ? "text = '".$this->db->escape($this->currency->format(-$voucher_amount))."'," : "") . "
							value = '".-$voucher_amount."'
							WHERE order_total_id = ".$order_cart_rules[$emag_voucher->id]['order_total_id']."
						");
						
						$order['total'] -= $diff_amount;
		
						$order_updated = true;
					}
				}
			}
		}

		// Update Order if necessary
		if ($order_updated) {
			$this->db->query("
				UPDATE `".DB_PREFIX."order` SET
				total = ".$order['total']."
				WHERE order_id = ".$order['order_id']."
			");
		
			$this->db->query("
				UPDATE `".DB_PREFIX."order_total` SET
				" . (intval(VERSION) < 2 ? "text = '".$this->db->escape($this->currency->format($order_totals['sub_total']))."'," : "") . "
				value = '".$order_totals['sub_total']."'
				WHERE order_id = ".$order['order_id']." AND code = 'sub_total'
			");

			$this->db->query("
				UPDATE `".DB_PREFIX."order_total` SET
				" . (intval(VERSION) < 2 ? "text = '".$this->db->escape($this->currency->format($order_totals['shipping']))."'," : "") . "
				value = '".$order_totals['shipping']."'
				WHERE order_id = ".$order['order_id']." AND code = 'shipping'
			");

			$this->db->query("
				UPDATE `".DB_PREFIX."order_total` SET
				" . (intval(VERSION) < 2 ? "text = '".$this->db->escape($this->currency->format($order_totals['tax']))."'," : "") . "
				value = '".$order_totals['tax']."'
				WHERE order_id = ".$order['order_id']." AND code = 'tax'
			");

			$this->db->query("
				UPDATE `".DB_PREFIX."order_total` SET
				" . (intval(VERSION) < 2 ? "text = '".$this->db->escape($this->currency->format($order['total']))."'," : "") . "
				value = '".$order['total']."'
				WHERE order_id = ".$order['order_id']." AND code = 'total'
			");

			$this->updateOrder($order['order_id']);
		}
			
		return true;
	}
	
	public function get_cartesian_product($arrays)
	{
	    $result = array();
	    $arrays = array_values($arrays);
	    $sizeIn = sizeof($arrays);
	    $size = $sizeIn > 0 ? 1 : 0;
	    foreach ($arrays as $array)
	        $size = $size * sizeof($array);
	    for ($i = 0; $i < $size; $i ++)
	    {
	        $result[$i] = array();
	        for ($j = 0; $j < $sizeIn; $j ++)
	            array_push($result[$i], current($arrays[$j]));
	        for ($j = ($sizeIn -1); $j >= 0; $j --)
	        {
	            if (next($arrays[$j]))
	                break;
	            elseif (isset ($arrays[$j]))
	                reset($arrays[$j]);
	        }
	    }
	    return $result;
	}
	
	public function get_combination_info($product_id, $product_options, $force_create = false)
	{
		if (!$this->config->get('module_emagmp_url'))
			return;
			
		$product_id = (int)$product_id;
		$product_options = $product_options;
		
		if ($force_create)
		{
			// avoid auto increment gaps
			$this->db->query('
				INSERT INTO `'.DB_PREFIX.'emagmp_product_combinations` (product_id, product_options)
				SELECT '.$product_id.', \''.$product_options.'\' FROM DUAL
				WHERE NOT EXISTS (
					SELECT *
					FROM `'.DB_PREFIX.'emagmp_product_combinations`
					WHERE product_id = '.$product_id.' AND product_options = \''.$product_options.'\'
				)
			');
			$combination_id = $this->db->getLastId();
			$last_definition = array();
		}
		if (!$combination_id)
		{
			$query = $this->db->query('
				SELECT combination_id, last_definition
				FROM `'.DB_PREFIX.'emagmp_product_combinations`
				WHERE product_id = '.$product_id.' AND product_options = \''.$product_options.'\'
			');
			$row = $query->row;
			$combination_id = $row['combination_id'];
			$last_definition = unserialize($row['last_definition']);
			if (!is_array($last_definition))
				$last_definition = array();
		}
		
		return array('combination_id' => $combination_id, 'last_definition' => $last_definition);
	}
	
	public function updateProduct($product_id = null, $delta = true)
	{
		if (!$this->config->get('module_emagmp_url'))
			return;
			
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		require_once(DIR_SYSTEM . 'library/cart/customer.php');
		require_once(DIR_SYSTEM . 'library/cart/tax.php');

		$this->registry->set('customer', new Customer($this->registry));
		$tax = new Tax($this->registry);
		$tax->setShippingAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		$tax->setPaymentAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		
		$option_values = array();
		$query = $this->db->query("
			SELECT *
			FROM " . DB_PREFIX . "option_value ov
			LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id)
			WHERE ovd.language_id = '" . (int)$this->config->get('module_emagmp_product_language_id') . "'
		");
		foreach ($query->rows as $row) {
			$option_values[$row['option_value_id']] = $row;
		}
		
		$url = new Url(defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER);
		
		$offer_fields = array(
			'id' => true,
			'status' => true,
			'sale_price' => true,
			'vat_id' => true,
			'commission' => true,
			'availability' => true,
			'stock' => true,
			'handling_time' => true
		);
				
		// get VAT rates
		$vat_rates = array();
		$emagmp_api_call = new EmagMarketplaceAPICall();
		$emagmp_api_call->resource = 'vat';
		$emagmp_api_call->action = 'read';
		
		$emagmp_api_call->execute();
		$emagmp_api_call->save();
		
		if ($emagmp_api_call->status == 'success')
		{
			foreach ($emagmp_api_call->message_in_json->results as $vat_def)
			{
				$tax_rate = round($vat_def->vat_rate, 2);
				$vat_rates["$tax_rate"] = $vat_def->vat_id;
			}
		}
				
		// get category and characteristic mapping
		$categories = array();
		$query = $this->db->query('
			SELECT ec.*
			FROM `'.DB_PREFIX.'emagmp_categories` AS ec
			WHERE ec.emag_category_id > 0 AND ec.sync_active = 1
		');
		foreach ($query->rows as $row) {
			$categories[$row['emag_category_id']] = $row;
		}
		
		$characteristics = array();
		$query = $this->db->query('
			SELECT ec.emag_category_id, ea.emag_characteristic_id as emag_characteristic_id_attribute, eo.emag_characteristic_id as emag_characteristic_id_option, ea.attribute_id, eo.option_id
			FROM `'.DB_PREFIX.'emagmp_categories` AS ec
			JOIN `'.DB_PREFIX.'emagmp_characteristic_definitions` AS ecd ON (ec.emag_category_id = ecd.emag_category_id)
			LEFT JOIN `'.DB_PREFIX.'emagmp_attributes` AS ea ON (ecd.emag_characteristic_id = ea.emag_characteristic_id AND ecd.emag_category_id = ea.emag_category_id)
			LEFT JOIN `'.DB_PREFIX.'emagmp_options` AS eo ON (ecd.emag_characteristic_id = eo.emag_characteristic_id AND ecd.emag_category_id = eo.emag_category_id)
			WHERE ec.emag_category_id > 0 AND ec.sync_active = 1 and (ea.attribute_id > 0 or eo.option_id > 0)
		');
		foreach ($query->rows as $row) {
			$row['emag_characteristic_id'] = $row['emag_characteristic_id_option'] ? $row['emag_characteristic_id_option'] : $row['emag_characteristic_id_attribute'];
			$characteristics[$row['emag_category_id']][$row['emag_characteristic_id']] = $row;
		}
		
		// get products
		$sql = '
			SELECT ec.emag_category_id, ec.emag_family_type_id, ep.emag_category_id AS emag_category_id_p, ep.emag_family_type_id AS emag_family_type_id_p, ec.commission, ep.commission AS commission_p, p.product_id, p.model, p.price, p.tax_class_id, p.quantity, p.status, pd.name, pd.description, p.image, m.name as manufacturer_name, group_concat(concat(pov.option_id, "_", pov.option_value_id, "_", pov.quantity, "_", pov.price * if(pov.price_prefix = "+", 1, -1))) as product_options
			FROM `'.DB_PREFIX.'emagmp_categories` ec
			JOIN `'.DB_PREFIX.'product_to_category` pc ON (ec.category_id = pc.category_id)
			JOIN `'.DB_PREFIX.'product` p ON (pc.product_id = p.product_id)
			JOIN '.DB_PREFIX.'product_description as pd on (p.product_id = pd.product_id and pd.language_id = '.(int)$this->config->get('module_emagmp_product_language_id').')
			LEFT JOIN '.DB_PREFIX.'manufacturer as m on (p.manufacturer_id = m.manufacturer_id)
			LEFT JOIN `'.DB_PREFIX.'emagmp_products` ep ON (p.`product_id` = ep.`product_id`)
			LEFT JOIN `'.DB_PREFIX.'product_option` po ON (p.`product_id` = po.`product_id`' . ($this->config->get('module_emagmp_product_option_required') ? ' and po.required = 1' : '') . ')
			LEFT JOIN `'.DB_PREFIX.'product_option_value` pov ON (po.`product_option_id` = pov.`product_option_id`)
			WHERE ec.emag_category_id > 0 AND ec.sync_active = 1
		';
		if ($product_id === null)
		{
			// upload all active products
			$sql .= ' AND p.`status` = 1';
		}
		else
		{
			// upload only current product
			$sql .= ' AND p.`product_id` = '.(int)$product_id;
			
			if (!$delta)
				$sql .= ' AND p.`status` = 1';
		}
		$sql .= ' GROUP BY p.`product_id`';
		//echo $sql;
		$products = array();
		$query = $this->db->query($sql);
		foreach ($query->rows as $row) {
			if ($row['product_options']) {
				$product_option_values = array();
				$product_option_quantities = array();
				$product_option_prices = array();
				$tmp = explode(",", $row['product_options']);
				foreach ($tmp as $option) {
					$tmp2 = explode("_", $option);
					$product_option_values[$tmp2[0]][] = $tmp2[1];
					$product_option_quantities[$tmp2[1]] = $tmp2[2];
					$product_option_prices[$tmp2[1]] = $tmp2[3];
				}
				$product_option_list = array();
				$cartesian_product = $this->get_cartesian_product($product_option_values);
				foreach ($cartesian_product as $item) {
					sort($item);
					$row['product_options'] = implode("-", $item);
					$option_quantity = 0;
					$option_price = 0;
					$noq = 0;
					foreach ($item as $option_value_id) {
						$noq++;
						if ($noq == 1)
							$option_quantity = $product_option_quantities[$option_value_id];
						else
							$option_quantity = min($option_quantity, $product_option_quantities[$option_value_id]);
						$option_price += round($product_option_prices[$option_value_id], 4);
					}
					$row['quantity'] = $option_quantity;
					$row['option_price'] = $option_price;
					$products[] = $row;
					$product_option_list[] = $row['product_options'];
				}
				$this->deleteProduct($row['product_id'], $product_option_list);
				continue;
			}
			$products[] = $row;
			$this->deleteProduct($row['product_id'], array(''));
		}
		
		foreach ($products as $row) {
			$product_combination_info = $this->get_combination_info($row['product_id'], $row['product_options'], $row['status'] ? true : false);
			$combination_id = $product_combination_info['combination_id'];
			$last_definition = $product_combination_info['last_definition'];
			
			if (!$combination_id)
				continue;
				
			$name = $row['name'];
			
			$options = array();
			if ($row['product_options']) {
				$tmp = explode("-", $row['product_options']);
				foreach ($tmp as $option_value_id) {
					$name .= ", ".$option_values[$option_value_id]['name'];
					$options[$option_values[$option_value_id]['option_id']] = $option_values[$option_value_id]['name'];
				}
				$row['price'] = round($row['price'], 4) + $row['option_price'];
			}
			
			$reference = $row['model'];
			$quantity = $row['quantity'];
			
			if (!$reference)
				$reference = 'EMAGMPREF_'.$combination_id;
			
			$description = html_entity_decode($row['description'], ENT_QUOTES, 'UTF-8');
			
			$tax_rates = $tax->getRates($row['price'], $row['tax_class_id']);
			$first_tax_rate = current($tax_rates);
			$tax_rate = round($first_tax_rate['rate'] / 100, 2);
			
			$sale_price = number_format($row['price'], 4, '.', '');
			$recommended_price = number_format($row['price'], 4, '.', '');
			
			$query = $this->db->query("
				SELECT price
				FROM " . DB_PREFIX . "product_special ps
				WHERE ps.product_id = " . (int)$row['product_id'] . " AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))
				ORDER BY ps.priority ASC, ps.price ASC
				LIMIT 1
			");
			if ($query->rows) {
				$sale_price = $query->row['price'];
			}
			
			if ($row['emag_category_id_p'] > 0)
			{
				$row['emag_category_id'] = $row['emag_category_id_p'];
			}
				
			if ($row['emag_family_type_id_p'] > 0)
			{
				$row['emag_family_type_id'] = $row['emag_family_type_id_p'];
			}
				
			if ($row['commission_p'] > 0)
				$row['commission'] = $row['commission_p'];
			
			$images = array();
			$product_images = $this->model_catalog_product->getProductImages($row['product_id']);
			if ($row['image']) {
				$product_images[] = array('image' => $row['image'], 'main' => true);
			}
			foreach ($product_images as $image)
			{
				$images[] = array(
					'display_type' => isset($image['main']) ? 1 : 2,
					'url' => $this->model_tool_image->resize($image['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height'))
				);
			}
			
			$attributes = array();
			$query = $this->db->query("
				SELECT pa.attribute_id, pa.text
				FROM " . DB_PREFIX . "product_attribute pa
				WHERE pa.product_id = '" . (int)$row['product_id'] . "' AND pa.language_id = '" . (int)$this->config->get('module_emagmp_product_language_id') . "'
			");
			foreach ($query->rows as $attribute) {
				$attributes[$attribute['attribute_id']] = $attribute['text'];
			}
			
			if ($quantity < 0)
				$quantity = 0;
			
			if ($quantity <= 0)
				$availability = 5;
			elseif ($quantity <= 3)
				$availability = 2;
			else
				$availability = 3;
				
			$warranty = (int)$this->config->get('module_emagmp_product_warranty');
			$handling_time = (int)$this->config->get('module_emagmp_handling_time');
				
			$product_data = array(
				'id' => $combination_id,
				'category_id' => $row['emag_category_id'],
				'name' => $name,
				'part_number' => $reference,
				'description' => $description,
				'brand' => $row['manufacturer_name'],
				'images' => $images,
				'url' => str_replace('&amp;', '&', $url->link('product/product', 'product_id=' . $row['product_id'])),
				'warranty' => $warranty,
				'status' => (int)$row['status'],
				'sale_price' => $sale_price,
				'availability' => array(
					array(
						'warehouse_id' => 1,
						'id' => $availability
					)
				),
				'stock' => array(
					array(
						'warehouse_id' => 1,
						'value' => $quantity
					)
				),
				'handling_time' => array(
					array(
						'warehouse_id' => 1,
						'value' => $handling_time
					)
				),
				'commission' => array(
					'type' => 'percentage',
					'value' => $row['commission']
				),
				'vat_id' => $vat_rates["$tax_rate"],
				'recommended_price' => $recommended_price,
				'characteristics' => array(),
				'family' => array('id' => 0)
			);
			
			if ($availability == 5)
			{
				$product_data['status'] = 0;
			}
			
			if (!isset($characteristics[$row['emag_category_id']]))
				$characteristics[$row['emag_category_id']] = array();
			foreach ($characteristics[$row['emag_category_id']] as $characteristic_id => $characteristic)
			{
				$attribute_id = $characteristic['attribute_id'];
				$option_id = $characteristic['option_id'];
				
				if (!isset($attributes[$attribute_id]))
					$attributes[$attribute_id] = '';
					
				if (!isset($options[$option_id]))
					$options[$option_id] = '';
					
				if (!$attributes[$attribute_id] && !$options[$option_id])
					continue;
					
				$product_data['characteristics'][] = array(
					'id' => $characteristic_id,
					'value' => $options[$option_id] ? $options[$option_id] : $attributes[$attribute_id]
				);
			}
			
			if ($row['product_options'] && $row['emag_family_type_id'] > 0)
			{
				$product_data['family'] = array(
					'id' => $row['product_id'],
					'family_type_id' => $row['emag_family_type_id'],
					'name' => $row['name']
				);
			}
			
			// don't send unnecessary data unless it has changed or is part of the mandatory offer update data, or we are sending all products
			$new_definition = $product_data;
			if ($delta)
			{
				$new_definition_keys = array_keys($new_definition);
				$last_definition_keys = array_keys($last_definition);
				$all_keys = array_unique(array_merge($new_definition_keys, $last_definition_keys));
				foreach ($all_keys as $key)
				{
					if (isset($offer_fields[$key]))
						continue;
						
					if (isset($new_definition[$key]) && isset($last_definition[$key]) && $new_definition[$key] === $last_definition[$key])
						unset($product_data[$key]);
				}
			}
			
			//print_r($product_data);
			$emagmp_api_call = new EmagMarketplaceAPICall();
			$emagmp_api_call->resource = 'product_offer';
			$emagmp_api_call->action = 'save';
			$emagmp_api_call->data = array(
				$product_data
			);
			$emagmp_api_call->last_definition = serialize($new_definition);
			$emagmp_api_call->save();
		}
	}
	
	public function deleteProduct($product_id, $product_option_list = array())
	{
		if (!$this->config->get('module_emagmp_url'))
			return;
			
		$sql = "
			SELECT *
			FROM `".DB_PREFIX."emagmp_product_combinations`
			WHERE product_id = ".(int)$product_id."
		";
		if (count($product_option_list))
			$sql .= " AND product_options NOT IN ('" . implode("', '", $product_option_list) . "')";
			
		$query = $this->db->query($sql);
		foreach ($query->rows as $row)
		{
			$last_definition = unserialize($row['last_definition']);
			if (is_array($last_definition) && $last_definition['status'] == 0)
				continue;
			
			$product_data = array(
				'id' => $row['combination_id'],
				'status' => 0
			);
			
			$emagmp_api_call = new EmagMarketplaceAPICall();
			$emagmp_api_call->resource = 'product_offer';
			$emagmp_api_call->action = 'save';
			$emagmp_api_call->data = array(
				$product_data
			);
			$emagmp_api_call->last_definition = serialize($product_data);
			$emagmp_api_call->save();
		}
	}
}

class EmagMarketplaceAPICall
{
	public $config;
	public $db;
	
	public $id;

	public $id_emagmp_api_call;
	public $date_created;
	public $resource;
	public $action;
	public $last_definition;
	public $message_out;
	public $message_in;
	public $status;
	public $date_sent;
	public $id_order;
	
	public $emagmp_api_url = null;
	public $emagmp_vendorcode = null;
	public $emagmp_vendorusername = null;
	public $emagmp_vendorpassword = null;
	
	public $data = null;
	public $message_in_json = null;
	
	public $module_version = '1.8';

	/**
	* @see ObjectModel::$definition
	*/
	public $definition = array(
		'table' => 'emagmp_api_calls',
		'primary' => 'id_emagmp_api_call',
		'fields' => array(
			'date_created' => array('type' => 'string'),
			'resource' => array('type' => 'string'),
			'action' => array('type' => 'string'),
			'last_definition' => array('type' => 'string'),
			'message_out' => array('type' => 'string'),
			'message_in' => array('type' => 'string'),
			'status' => array('type' => 'string', 'default' => 'pending'),
			'date_sent' => array('type' => 'string'),
			'id_order' => array('type' => 'integer')
		)
	);
	
	public function __construct($id = null)
	{
		global $registry;
		
		$this->config = $registry->get('config');
		$this->db = $registry->get('db');
		
		$this->emagmp_api_url = $this->config->get('module_emagmp_api_url');
		$this->emagmp_vendorcode = $this->config->get('module_emagmp_vendorcode');
		$this->emagmp_vendorusername = $this->config->get('module_emagmp_vendorusername');
		$this->emagmp_vendorpassword = $this->config->get('module_emagmp_vendorpassword');
		
		if (!$id)
		{
			foreach ($this->definition['fields'] as $field_name => $field_definition)
			{
				if (isset($field_definition['default']))
					$this->{$field_name} = $field_definition['default'];
			}
			$this->date_created = date('Y-m-d H:i:s');
			$this->data = array();
		}
		else
		{
			$query = $this->db->query("
				select *
				from ".DB_PREFIX.$this->definition['table']."
				where ".$this->definition['primary']." = ".intval($id)."
			");
			$row = $query->row;
			if (is_array($row))
			{
				$this->id = (int)$id;
				foreach ($row as $key => $value)
				{
					if (array_key_exists($key, $this))
						$this->{$key} = $value;
				}
			}

			$this->data = unserialize($this->message_out);
		}

	}
	
	public function execute()
	{
		if (!$this->config->get('module_emagmp_url'))
			return;
			
		$debug_info = array(
			'site' => HTTP_SERVER,
			'platform' => 'OpenCart',
			'version' => VERSION,
			'extension_version' => $this->module_version,
			'others' => ''
		);
		
		$hash = base64_encode($this->emagmp_vendorusername .':'. $this->emagmp_vendorpassword);
			$headers = array(
				'Authorization: Basic ' . $hash
			);

		$requestData = array(
		    'code' => $this->emagmp_vendorcode,
		    //'username' => $this->emagmp_vendorusername,
		    'data' => $this->data,
		    //'hash' => $hash,
		    'debug_info' => $debug_info
		);

		$ch = curl_init();
		$url = $this->emagmp_api_url.'/'.$this->resource.'/'.$this->action;

		if ($this->resource == 'order' && $this->action == 'acknowledge')
			$url .= '/'.$this->data['id'];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		//curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestData));
		
		$fp = fopen(DIR_LOGS.'emagmp_call_result.txt', 'a');
		/*curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_STDERR, $fp);*/
		
		$result = curl_exec($ch);
		
		$this->date_sent = date("Y-m-d H:i:s");

		/*ob_start();
		echo "\n\n---------------------------------------------------------------------------\n";
		print_r($this->data);
		print_r(curl_getinfo($ch, CURLINFO_HEADER_OUT));
		fwrite($fp, ob_get_contents());
		ob_end_clean();*/

		//fwrite($fp, "\n\n\n".$result);

		if (curl_errno($ch))
		{
			$this->message_in = curl_error($ch);
			$this->status = 'error';
		}
		else
		{
			$this->message_in = $result;
			$this->message_in_json = json_decode($result);
			
			if (is_object($this->message_in_json) && $this->message_in_json->isError === false)
			{
				$this->status = 'success';
			}
			else
			{
				$this->status = 'error';
			}
		}
		
		curl_close($ch);
		
		fclose($fp);
		
		// save last sent data for products and orders
		if ($this->status == 'success' && $this->action == 'save')
		{
			switch ($this->resource)
			{
				case 'product_offer':
					$definition_table_name = 'emagmp_product_combinations';
					$definition_table_primary_field = 'combination_id';
					break;
				case 'order':
					$definition_table_name = 'emagmp_order_history';
					$definition_table_primary_field = 'emag_order_id';
					break;
			}
			
			if ($definition_table_name && $definition_table_primary_field)
			{
				$this->db->query('
					UPDATE `'.DB_PREFIX.$definition_table_name.'` SET
					last_definition = \''.$this->db->escape($this->last_definition).'\'
					WHERE '.$definition_table_primary_field.' = '.(int)$this->data[0]['id'].'
				');
			}
		}
		
		// save last eMAG order definition
		if ($this->status == 'success' && $this->action == 'read' && $this->resource == 'order' && isset($this->data['id']))
		{
			$definition_table_name = 'emagmp_order_history';
			$definition_table_primary_field = 'emag_order_id';
			$this->db->query('
				UPDATE `'.DB_PREFIX.$definition_table_name.'` SET
				emag_definition = \''.$this->db->escape(serialize($this->message_in_json->results[0])).'\'
				WHERE '.$definition_table_primary_field.' = '.(int)$this->data['id'].'
			');
		}
	}
	
	public function save()
	{
		if (!$this->config->get('module_emagmp_url'))
			return;
			
		$fp = fopen(DIR_LOGS.'emagmp_call_queue.txt', 'a');
		ob_start();
		echo "\n\n---------------------------------------------------------------------------\n";
		print_r($this->data);
		echo $this->resource.'/'.$this->action."\n";
		//fwrite($fp, ob_get_contents());
		ob_end_clean();
		fclose($fp);

		$this->message_out = serialize($this->data);
		
		if (!$this->id)
		{
			$sql_action = "INSERT INTO";
			$sql_condition = "";
		}
		else
		{
			$sql_action = "UPDATE";
			$sql_condition = "WHERE ".$this->definition['primary']." = ".(int)$this->id;
		}
		
		$sql_fields = "";
		foreach ($this->definition['fields'] as $field_name => $field_definition)
		{
			if ($sql_fields)
				$sql_fields .= ", ";
			switch ($field_definition['type'])
			{
				case 'integer':
					$value = (int)$this->{$field_name};
					break;
				case 'string':
					$value = "'".$this->db->escape($this->{$field_name})."'";
					break;
			}
			$sql_fields .= "$field_name = $value";
		}
		
		$this->db->query("
			$sql_action ".DB_PREFIX.$this->definition['table']." SET
			$sql_fields
			$sql_condition
		");
		
		$this->id = $this->db->getLastId();
		$this->{$this->definition['primary']} = $this->id;
		
		return true;
	}

}
?>