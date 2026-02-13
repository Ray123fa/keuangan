<?php
declare(strict_types=1);

namespace App\Application\Services;
/**
 * FonnteService - Mengirim pesan ke WhatsApp via Fonnte API
 */

class FonnteService
{
    private string $token;
    private string $apiUrl = 'https://api.fonnte.com/send';

    public function __construct()
    {
        $this->token = FONNTE_TOKEN;
    }

    /**
     * Kirim pesan teks ke WhatsApp
     */
    public function sendMessage(string $target, string $message): array
    {
        return $this->send([
            'target' => $target,
            'message' => $message,
        ]);
    }

    /**
     * Kirim request ke Fonnte API
     */
    private function send(array $data): array
    {
        $curl = curl_init();

        $curlOptions = [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->token,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            error_log('Fonnte API Error: ' . $error);
            return ['status' => false, 'reason' => $error];
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Fonnte API Invalid JSON: ' . $response);
            return ['status' => false, 'reason' => 'Invalid response'];
        }

        return $result;
    }
}
