<?php
class ControllerApiTelegram extends Controller
{
    public function index()
    {
        // Отримання товарів, які закінчилися за сьогодні (показуємо sku)
        $products = $this->db->query("
            SELECT sku 
            FROM " . DB_PREFIX . "product 
            WHERE quantity = 0 AND date_modified >= CURDATE()
        ")->rows;

        if (!empty($products)) {
            $message = "Товари (SKU), які закінчилися сьогодні:\n";
            foreach ($products as $product) {
                $message .= $product['sku'] . "\n";  // Додаємо sku до повідомлення
            }

            // Телеграм API для відправки повідомлень
            $botToken = "7698279486:AAEqQfsR9m-tEipXkjDgKPzG3I2b9xd4d88";
            $chatId = "377816625";
            $telegramApiUrl = "https://api.telegram.org/bot$botToken/sendMessage";

            // Відправка повідомлення
            $telegramResponse = file_get_contents($telegramApiUrl . "?chat_id=" . $chatId . "&text=" . urlencode($message));

            // Виведення результату
            $this->response->setOutput(json_encode(['success' => 'Message sent to Telegram', 'telegram_response' => json_decode($telegramResponse)]));
        } else {
            $this->response->setOutput(json_encode(['success' => 'No products out of stock today']));
        }
    }
}


