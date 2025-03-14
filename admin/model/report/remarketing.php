<?php
class ModelReportRemarketing extends Model {

	public function getUtm($utm = '') {
		$utms = [];
		$query = $this->db->query("SELECT DISTINCT(" . $this->db->escape($utm) . ") FROM `" . DB_PREFIX . "remarketing_orders` ro");
		foreach ($query->rows as $row) {
			if (!empty($row[$utm])) {
				$utms[] = $row[$utm];
			} 
		}
		return $utms;
	}

	public function getOrders($data = []) {
		$sql = "SELECT MIN(o.date_added) AS date_start, MAX(o.date_added) AS date_end, COUNT(*) AS `orders`, GROUP_CONCAT(o.order_id SEPARATOR ',') as order_ids, SUM((SELECT SUM(op.quantity) FROM `" . DB_PREFIX . "order_product` op WHERE op.order_id = ro.order_id GROUP BY op.order_id)) AS products, SUM(o.total) AS `total` FROM `" . DB_PREFIX . "remarketing_orders` ro LEFT JOIN `" . DB_PREFIX . "order` o ON o.order_id = ro.order_id";

		if (!empty($data['filter_order_status_id'])) {
			$sql .= " WHERE o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_date_start'])) {
			$sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
		}

		if (!empty($data['filter_date_end'])) {
			$sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
		}

		if (!empty($data['filter_utm_source'])) {
			$sql .= " AND ro.utm_source = '" . $this->db->escape($data['filter_utm_source']) . "'";
		} 

		if (!empty($data['filter_utm_campaign'])) {
			$sql .= " AND ro.utm_campaign = '" . $this->db->escape($data['filter_utm_campaign']) . "'";
		} 

		if (!empty($data['filter_utm_medium'])) {
			$sql .= " AND ro.utm_medium = '" . $this->db->escape($data['filter_utm_medium']) . "'";
		} 

		if (!empty($data['filter_utm_term'])) {
			$sql .= " AND ro.utm_term = '" . $this->db->escape($data['filter_utm_term']) . "'";
		} 

		if (!empty($data['filter_utm_content'])) {
			$sql .= " AND ro.utm_content = '" . $this->db->escape($data['filter_utm_content']) . "'";
		} 

		if (!empty($data['filter_first_referrer'])) {
			$sql .= " AND ro.first_referrer = '" . $this->db->escape($data['filter_first_referrer']) . "'";
		} 

		if (!empty($data['filter_last_referrer'])) {
			$sql .= " AND ro.last_referrer = '" . $this->db->escape($data['filter_last_referrer']) . "'";
		} 
		
		if (!empty($data['filter_group'])) {
			$group = $data['filter_group'];
		} else {
			$group = 'week';
		}

		switch($group) {
			case 'day';
				$sql .= " GROUP BY YEAR(o.date_added), MONTH(o.date_added), DAY(o.date_added)";
				break;
			default:
			case 'week':
				$sql .= " GROUP BY YEAR(o.date_added), WEEK(o.date_added)";
				break;
			case 'month':
				$sql .= " GROUP BY YEAR(o.date_added), MONTH(o.date_added)";
				break;
			case 'year':
				$sql .= " GROUP BY YEAR(o.date_added)";
				break;
		}

		$sql .= " ORDER BY o.date_added DESC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}
	
	public function getOrdersByIds($data = []) {
		$sql = "SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o";
		
		if (!empty($data['filter_order_ids'])) {
			$sql .= " WHERE o.order_id IN (" . $data['filter_order_ids'] . ")";
		}

		$sql .= " ORDER BY o.order_id ASC";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getProductsByIds($data = []) {
		$sql = "SELECT op.product_id, SUM(op.quantity) as product_quantity, SUM(op.total) as product_total FROM " . DB_PREFIX . "order_product op WHERE op.order_id IN (" . $data['filter_order_ids'] . ") GROUP BY op.product_id  ORDER BY product_total DESC";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalOrders($data = array()) {
		if (!empty($data['filter_group'])) {
			$group = $data['filter_group'];
		} else {
			$group = 'week';
		}

		switch($group) {
			case 'day';
				$sql = "SELECT COUNT(DISTINCT YEAR(o.date_added), MONTH(o.date_added), DAY(o.date_added)) AS total FROM `" . DB_PREFIX . "remarketing_orders` ro LEFT JOIN `" . DB_PREFIX . "order` o ON o.order_id = ro.order_id ";
				break;
			default:
			case 'week':
				$sql = "SELECT COUNT(DISTINCT YEAR(o.date_added), WEEK(o.date_added)) AS total FROM `" . DB_PREFIX . "remarketing_orders` ro LEFT JOIN `" . DB_PREFIX . "order` o ON o.order_id = ro.order_id ";
				break;
			case 'month':
				$sql = "SELECT COUNT(DISTINCT YEAR(o.date_added), MONTH(o.date_added)) AS total FROM `" . DB_PREFIX . "remarketing_orders` ro LEFT JOIN `" . DB_PREFIX . "order` o ON o.order_id = ro.order_id ";
				break;
			case 'year':
				$sql = "SELECT COUNT(DISTINCT YEAR(o.date_added)) AS total FROM `" . DB_PREFIX . "remarketing_orders` ro LEFT JOIN `" . DB_PREFIX . "order` o ON o.order_id = ro.order_id ";
				break;
		}

		if (!empty($data['filter_order_status_id'])) {
			$sql .= " WHERE o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_date_start'])) {
			$sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
		}

		if (!empty($data['filter_date_end'])) {
			$sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
		}
		if (!empty($data['filter_utm_source'])) {
			$sql .= " AND ro.utm_source = '" . $this->db->escape($data['filter_utm_source']) . "'";
		} 

		if (!empty($data['filter_utm_campaign'])) {
			$sql .= " AND ro.utm_campaign = '" . $this->db->escape($data['filter_utm_campaign']) . "'";
		} 

		if (!empty($data['filter_utm_medium'])) {
			$sql .= " AND ro.utm_medium = '" . $this->db->escape($data['filter_utm_medium']) . "'";
		} 

		if (!empty($data['filter_utm_term'])) {
			$sql .= " AND ro.utm_term = '" . $this->db->escape($data['filter_utm_term']) . "'";
		} 

		if (!empty($data['filter_utm_content'])) {
			$sql .= " AND ro.utm_content = '" . $this->db->escape($data['filter_utm_content']) . "'";
		} 

		if (!empty($data['filter_first_referrer'])) {
			$sql .= " AND ro.first_referrer = '" . $this->db->escape($data['filter_first_referrer']) . "'";
		} 

		if (!empty($data['filter_last_referrer'])) {
			$sql .= " AND ro.last_referrer = '" . $this->db->escape($data['filter_last_referrer']) . "'";
		} 
		
		$query = $this->db->query($sql);

		return $query->row['total'];
	}
}
