<?php
class TelcoSMS {
    private $apiKey;
    private $baseUrl = 'https://www.telcosms.co.ao/api/v2/send_message';

    public function __construct() {
        if (!defined('TELCOSMS_API_KEY') || empty(TELCOSMS_API_KEY)) {
            error_log("TelcoSMS Error: TELCOSMS_API_KEY not defined.");
        }
        $this->apiKey = defined('TELCOSMS_API_KEY') ? TELCOSMS_API_KEY : '';
    }

    public function sendSMS($to, $message) {
        if (empty($this->apiKey)) {
            error_log("TelcoSMS Error: API Key is missing.");
            return false;
        }

        // Normalize phone number for TelcoSMS (usually expects 9 digits for Angola)
        // Remove +244 or 244 prefix
        $to_normalized = preg_replace('/^(\+?244)/', '', $to);
        
        // Debug: Log payload attempt
        error_log("TelcoSMS Debug: Preparing to send SMS to $to (Normalized: $to_normalized)");

        $payload = [
            'message' => [
                'api_key_app' => $this->apiKey,
                'phone_number' => $to_normalized,
                'message_body' => $message
            ]
        ];

        // Debug: Log payload content (be careful with sensitive data in prod)
        error_log("TelcoSMS Debug Payload: " . json_encode($payload));

        $response = $this->request($this->baseUrl, $payload);
        
        // Debug: Log final result
        if ($response) {
            error_log("TelcoSMS Success: " . json_encode($response));
            return true;
        } else {
            error_log("TelcoSMS Failed: No valid response received.");
            return false;
        }
    }

    private function request($url, $data) {
        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json;charset=UTF-8',
            'Accept: application/json'
        ];

        // Debug: Log URL
        error_log("TelcoSMS Debug URL: $url");

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        // Debug: Verbose curl output for deep inspection
        // curl_setopt($ch, CURLOPT_VERBOSE, true);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log("TelcoSMS Debug HTTP Code: $httpCode");
        
        if ($result === false) {
             error_log("TelcoSMS Curl Error: $curlError");
             return false;
        }

        error_log("TelcoSMS Raw Response: $result");

        if ($httpCode >= 400) {
             error_log("TelcoSMS Request Failed ($httpCode): $result");
             return false;
        }

        return json_decode($result, true);
    }
}
?>