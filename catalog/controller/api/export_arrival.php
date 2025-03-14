<?php
class ControllerApiExportArrival extends Controller {
    public function index() {
        $work_dir = dirname(DIR_APPLICATION) . '/work/';
        
        // Read arrival.txt file
        $id_1c_list = file($work_dir . 'arrival.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (!$id_1c_list) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'success' => false,
                'message' => 'Could not read arrival.txt file'
            ]));
            return;
        }

        // Check for duplicates in arrival.txt
        $id_1c_count = array_count_values($id_1c_list);
        $duplicates = array_filter($id_1c_count, function($count) {
            return $count > 1;
        });

        // Get unique IDs
        $id_1c_list_unique = array_unique($id_1c_list);

        // Prepare CSV files
        $products_no_image = fopen($work_dir . 'products_no_image.csv', 'w');
        $products_with_image = fopen($work_dir . 'products_with_image.csv', 'w');

        // Write headers with UTF-8 BOM for Excel compatibility
        fprintf($products_no_image, chr(0xEF).chr(0xBB).chr(0xBF));
        fprintf($products_with_image, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($products_no_image, ['article', 'name']);
        fputcsv($products_with_image, ['article', 'name', 'url']);

        // Step 1: Get product_ids from oc_product using id_1c
        $id_1c_formatted = "'" . implode("','", array_map(array($this->db, 'escape'), $id_1c_list_unique)) . "'";
        
        $sql_products = "SELECT product_id, image, id_1c FROM " . DB_PREFIX . "product 
                        WHERE id_1c IN (" . $id_1c_formatted . ")";
        
        $query_products = $this->db->query($sql_products);
        
        // Track which ID_1C were found
        $found_id_1c = array_column($query_products->rows, 'id_1c');
        
        // Find not found IDs
        $not_found_ids = array_diff($id_1c_list_unique, $found_id_1c);

        if ($query_products->num_rows) {
            // Get list of product_ids
            $product_ids = array_column($query_products->rows, 'product_id');
            $product_images = array_column($query_products->rows, 'image', 'product_id');
            
            // Step 2: Get data from wayforpay using product_ids
            $product_ids_list = implode(',', array_map('intval', $product_ids));
            
            $sql_wayforpay = "SELECT * FROM " . DB_PREFIX . "wayforpay_product_names 
                             WHERE product_id IN (" . $product_ids_list . ")";
            
            $query_wayforpay = $this->db->query($sql_wayforpay);

            foreach ($query_wayforpay->rows as $product) {
                $data = [$product['article'], $product['alternative_name']];
                $has_image = !empty($product_images[$product['product_id']]);
                
                if (!$has_image) {
                    fputcsv($products_no_image, $data);
                } else {
                    // Add URL for products with images
                    $product_url = $this->url->link('product/product', 'product_id=' . $product['product_id']);
                    $data[] = $product_url;
                    fputcsv($products_with_image, $data);
                }
            }
        }

        // Close files
        fclose($products_no_image);
        fclose($products_with_image);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => true,
            'message' => 'CSV files have been generated successfully!',
            'processed_products' => isset($query_wayforpay) ? count($query_wayforpay->rows) : 0,
            'debug' => [
                'total_ids_from_file' => count($id_1c_list),
                'unique_ids_count' => count($id_1c_list_unique),
                'sample_ids' => array_slice($id_1c_list, 0, 5),
                'product_ids_found' => isset($product_ids) ? count($product_ids) : 0,
                'duplicates' => !empty($duplicates) ? $duplicates : 'No duplicates',
                'not_found_ids' => !empty($not_found_ids) ? array_values($not_found_ids) : 'All IDs found',
                'sql_products' => $sql_products,
                'sql_wayforpay' => isset($sql_wayforpay) ? $sql_wayforpay : null
            ]
        ]));
    }
}