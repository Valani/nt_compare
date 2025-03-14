<?php
class ControllerApiImport1c extends Controller {
    private $dir = '/home/ilweb/nawiteh.com.ua/orders/';

    private function getOrders() {
        return $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE send_1c = 0")->rows;
    }

    private function getProductDetails($orderId) {
        $query = "SELECT op.model, op.quantity, op.price, p.id_1c 
                 FROM " . DB_PREFIX . "order_product op 
                 LEFT JOIN " . DB_PREFIX . "product p ON p.product_id = op.product_id 
                 WHERE order_id = " . intval($orderId);
        return $this->db->query($query)->rows;
    }

    private function buildCommentArray($order) {
        $comment = [];
        $comment[] = 'Замовлення №' . $order['order_id'];
        
        $fields = ['comment', 'shipping_method', 'shipping_zone', 
                  'shipping_city', 'shipping_address_1', 'shipping_address_2'];
        
        foreach ($fields as $field) {
            if (!empty($order[$field])) {
                $comment[] = $order[$field];
            }
        }
        
        return $comment;
    }

    private function generateXML($order, $comment, $productDetails) {
        $xml = '<?xml version="1.0" encoding="utf-8" ?>' . PHP_EOL;
        $xml .= '<data>' . PHP_EOL;
        $xml .= '    <id>' . htmlspecialchars($order['order_id']) . '</id>' . PHP_EOL;
        $xml .= '    <dealerid>' . htmlspecialchars($order['email']) . '</dealerid>' . PHP_EOL;
        $xml .= '    <dealerdescription>' . htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) . '</dealerdescription>' . PHP_EOL;
        $xml .= '    <dealertel>' . htmlspecialchars($order['telephone']) . '</dealertel>' . PHP_EOL;
        $xml .= '    <reserve>0</reserve>' . PHP_EOL;
        $xml .= '    <tip_oplati>1</tip_oplati>' . PHP_EOL;
        $xml .= '    <ordernumber>ORDER' . $order['order_id'] . '</ordernumber>' . PHP_EOL;
        $xml .= '    <date>' . strtotime($order['date_added']) . '</date>' . PHP_EOL;
        $xml .= '    <description>' . htmlspecialchars(implode(', ', $comment)) . '</description>' . PHP_EOL;
        $xml .= '    <products>' . PHP_EOL;

        foreach ($productDetails as $product) {
            $xml .= '        <product article="' . htmlspecialchars($product['model']) . '" code="' . htmlspecialchars($product['id_1c']) . '">';
            $xml .= '<quantity>' . floatval($product['quantity']) . '</quantity>';
            $xml .= '<price>' . floatval($product['price']) . '</price></product>' . PHP_EOL;
        }

        $xml .= '    </products>' . PHP_EOL;
        $xml .= '</data>';

        return $xml;
    }

    private function updateOrderStatus($orderId, $orderStatusId) {
        $this->db->query("UPDATE " . DB_PREFIX . "order SET send_1c = 1 WHERE order_id = " . intval($orderId));
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history 
            SET order_id = " . intval($orderId) . ", 
                order_status_id = " . intval($orderStatusId) . ", 
                notify = 0, 
                comment = 'Замовлення додано до списку файлів для передачі в 1С', 
                date_added = '" . date('Y-m-d H:i:s') . "'");
    }

    public function export_orders() {
        try {
            if (!is_dir($this->dir)) {
                mkdir($this->dir, 0755, true);
            }

            if (!is_writable($this->dir)) {
                throw new Exception("Directory {$this->dir} is not writable");
            }

            $orders = $this->getOrders();
            
            if (empty($orders)) {
                return;
            }

            foreach ($orders as $k => $order) {
                $comment = $this->buildCommentArray($order);
                $productDetails = $this->getProductDetails($order['order_id']);
                
                if (empty($productDetails)) {
                    continue;
                }

                $xml = $this->generateXML($order, $comment, $productDetails);
                $filename = $this->dir . 'order-' . $order['order_id'] . '.xml';
                
                if (file_put_contents($filename, $xml) === false) {
                    throw new Exception("Failed to write file: {$filename}");
                }

                $this->updateOrderStatus($order['order_id'], $order['order_status_id']);
                echo ($k+1) . ". Order ID - " . $order['order_id'] . "<br>";
            }
        } catch (Exception $e) {
            // Handle or report error appropriately
            echo "Error: " . $e->getMessage();
        }
    }
}