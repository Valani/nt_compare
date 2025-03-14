<?php
class ModelExtensionPaymentWayforpayProducts extends Model {
    public function getAlternativeProductName($product_id) {
        $query = $this->db->query("SELECT alternative_name, article FROM " . DB_PREFIX . "wayforpay_product_names WHERE product_id = '" . (int)$product_id . "'");
        
        if ($query->num_rows) {
            return array(
                'alternative_name' => $query->row['alternative_name'],
                'article' => $query->row['article']
            );
        }
        
        return false;
    }
}