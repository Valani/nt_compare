<?php
// system/library/payment/nbu_payment_qr.php

class NbuPaymentQr {
    /**
     * Генерує QR-код для платежу згідно з форматом НБУ версії 002.
     *
     * @param string $recipient Назва отримувача (макс. 38 символів)
     * @param string $edrpou ЄДРПОУ отримувача
     * @param string $iban IBAN отримувача
     * @param float|null $amount Сума платежу
     * @param string|null $purpose Призначення платежу (макс. 140 символів)
     * @return string URL для QR-коду у форматі НБУ
     * @throws Exception
     */
    public function generatePaymentQR(
        string $recipient,
        string $edrpou,
        string $iban,
        ?float $amount = null,
        ?string $purpose = null
    ): string {
        // Перевірка довжини полів згідно специфікації
        if (mb_strlen($recipient) > 38) {
            throw new Exception("Назва отримувача не може перевищувати 38 символів");
        }
        if ($purpose !== null && mb_strlen($purpose) > 140) {
            throw new Exception("Призначення платежу не може перевищувати 140 символів");
        }

        // Форматування суми згідно вимог НБУ
        $amountStr = "";
        if ($amount !== null) {
            if ($amount > 999999999.99) {
                throw new Exception("Сума не може перевищувати 999999999.99");
            }
            $amountStr = "UAH" . (fmod($amount, 1) ? number_format($amount, 2, '.', '') : number_format($amount, 0, '.', ''));
        }

        // Формування структури даних згідно специфікації НБУ (версія 002)
        $data = "BCD\n002\n1\nUCT\n\n{$recipient}\n{$iban}\n{$amountStr}\n{$edrpou}\n\n\n" . ($purpose ?? '') . "\n\n";

        // Кодування даних у base64URL
        $encodedData = rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        // Формування повного URL
        return "https://bank.gov.ua/qr/{$encodedData}";
    }

    /**
     * Зберігає QR-код у файл згідно специфікації НБУ
     *
     * @param string $url URL для QR-коду
     * @param string $filename Ім'я файлу для збереження
     * @return bool
     * @throws Exception
     */
    public function saveQRCode(string $url, string $filename = "payment_qr.png"): bool {
        // Перевіряємо наявність бібліотеки
        if (!file_exists(DIR_SYSTEM . 'library/phpqrcode/qrlib.php')) {
            throw new Exception('Бібліотека phpqrcode не знайдена');
        }

        require_once(DIR_SYSTEM . 'library/phpqrcode/qrlib.php');

        // Створюємо QR код
        try {
            \QRcode::png($url, $filename, 'M', 10, 4);
            return true;
        } catch (Exception $e) {
            throw new Exception('Помилка при генерації QR-коду: ' . $e->getMessage());
        }
    }

    /**
     * Генерує QR-код і повертає його як base64 рядок для відображення в HTML
     *
     * @param string $url URL для QR-коду
     * @return string Base64 encoded PNG
     * @throws Exception
     */
    public function getQRCodeAsBase64(string $url): string {
        if (!file_exists(DIR_SYSTEM . 'library/phpqrcode/qrlib.php')) {
            throw new Exception('Бібліотека phpqrcode не знайдена');
        }

        require_once(DIR_SYSTEM . 'library/phpqrcode/qrlib.php');

        // Створюємо тимчасовий файл
        $tempFile = tempnam(sys_get_temp_dir(), 'qr_');
        
        try {
            // Генеруємо QR код
            \QRcode::png($url, $tempFile, 'M', 10, 4);
            
            // Читаємо файл і конвертуємо в base64
            $imageData = base64_encode(file_get_contents($tempFile));
            
            // Видаляємо тимчасовий файл
            unlink($tempFile);
            
            return 'data:image/png;base64,' . $imageData;
        } catch (Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw new Exception('Помилка при генерації QR-коду: ' . $e->getMessage());
        }
    }
}

// Приклад використання в контролері OpenCart:
/*
class ControllerPaymentNbuQr extends Controller {
    public function index() {
        try {
            $this->load->library('payment/nbu_payment_qr');
            
            $paymentQR = new NbuPaymentQr();
            
            // Отримуємо дані з замовлення
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            
            // Генеруємо URL для QR-коду
            $qrUrl = $paymentQR->generatePaymentQR(
                'ТОВ "НАВІТЕХ"',
                '39811454',
                'UA813052990000026003031002657',
                $order_info['total'],
                'Замовлення #' . $order_info['order_id']
            );
            
            // Отримуємо QR-код як base64 для відображення
            $qrCodeImage = $paymentQR->getQRCodeAsBase64($qrUrl);
            
            $data['qr_code'] = $qrCodeImage;
            $data['payment_url'] = $qrUrl;
            
            return $this->load->view('extension/payment/nbu_qr', $data);
        } catch (Exception $e) {
            $this->log->write('Payment QR Error: ' . $e->getMessage());
            return 'Error generating QR code';
        }
    }
}
*/
?>