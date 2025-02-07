<?php
class ModelExtensionPaymentPayPal extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/paypal');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_paypal_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('payment_paypal_total') > 0 && $this->config->get('payment_paypal_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_paypal_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'paypal',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_paypal_sort_order')
			);
		}

		return $method_data;
	}
	
	public function log($data, $title = null) {
		if ($this->config->get('payment_paypal_debug')) {
			$log = new Log('paypal.log');
			$log->write('PayPal debug (' . $title . '): ' . json_encode($data));
		}
	}
	
	public function addOrder($order_data) {
		/**
		 * 1 to 1 relationship with order table (extends order info)
		 */

		$this->db->query("INSERT INTO `" . DB_PREFIX . "paypal_order` SET
			`order_id` = '" . (int)$order_data['order_id'] . "',
			`date_added` = NOW(),
			`date_modified` = NOW(),
			`capture_status` = '" . $this->db->escape($order_data['capture_status']) . "',
			`currency_code` = '" . $this->db->escape($order_data['currency_code']) . "',
			`total` = '" . (float)$order_data['total'] . "',
			`authorization_id` = '" . $this->db->escape($order_data['authorization_id']) . "'");

		return $this->db->getLastId();
	}

	public function addTransaction($transaction_data) {
		/**
		 * 1 to many relationship with paypal order table, many transactions per 1 order
		 */

		$this->db->query("INSERT INTO `" . DB_PREFIX . "paypal_order_transaction` SET
			`paypal_order_id` = '" . (int)$transaction_data['paypal_order_id'] . "',
			`transaction_id` = '" . $this->db->escape($transaction_data['transaction_id']) . "',
			`parent_id` = '" . $this->db->escape($transaction_data['parent_id']) . "',
			`date_added` = NOW(),
			`expiration_time` = '" . $this->db->escape($transaction_data['expiration_time']) . "',
			`note` = '" . $this->db->escape($transaction_data['note']) . "',
			`msgsubid` = '" . $this->db->escape($transaction_data['msgsubid']) . "',
			`receipt_id` = '" . $this->db->escape($transaction_data['receipt_id']) . "',
			`payment_type` = '" . $this->db->escape($transaction_data['payment_type']) . "',
			`payment_status` = '" . $this->db->escape($transaction_data['payment_status']) . "',
			`pending_reason` = '" . $this->db->escape($transaction_data['pending_reason']) . "',
			`transaction_entity` = '" . $this->db->escape($transaction_data['transaction_entity']) . "',
			`amount` = '" . (float)$transaction_data['amount'] . "',
			`debug_data` = '" . $this->db->escape($transaction_data['debug_data']) . "'");
	}
}