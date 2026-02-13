<?php

namespace App\Services;
use App\Models\Version;
use Google\Client;
use Illuminate\Support\Facades\Http;
class FirebasePushService
{

    public static function sendMultiple($deviceToken, $title, $body, $data = [])
    {
       // $deviceToken = 'exZ0hNBbS5mCJUNmebJOWX:APA91bFxFba1GySt9QVewGdtl6iswMUhDGLpaTh0xbBxtRCDFKB7c-CWOWtswa0i_v9c2uhFyFPN998z9TOPT0v47lT3ye4doGU38JUjKRJGvC1KrgK86T0';
        if (empty($deviceToken))
            return false;

       
        $payload = [
            "message" => [
                "token" => $deviceToken,
                "notification" => [
                    "title" => $title,
                    "body" => $body
                ],
                "data" => $data
            ]
        ];
        $accessToken = self::getAccessToken();
        // self::callFCM($payload);

        $response = Http::withToken($accessToken)
            ->post(
                'https://fcm.googleapis.com/v1/projects/quickdials-2f0f0/messages:send',
                $payload
            );

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'response' => $response->json()
        ];

    }

    private static function callFCM($payload)
    {

        $accessToken = self::getAccessToken();

        $headers = [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ];

        $url = "https://fcm.googleapis.com/v1/projects/quickdials-2f0f0/messages:send";

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                'status' => false,
                'error' => $error
            ];
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200) {
            return [
                'status' => true,
                'message_id' => $result['name'] ?? null,
                'response' => $result
            ];
        }


        return [
            'status' => false,
            'http_code' => $httpCode,
            'error' => $result
        ];

        //     $accessToken = self::getAccessToken();

        //     $headers = [
        //         "Authorization: Bearer {$accessToken}",
        //         "Content-Type: application/json"
        //     ];

        //     $url = "https://fcm.googleapis.com/v1/projects/quickdials-2f0f0/messages:send";
        //     $ch = curl_init();
        //     curl_setopt($ch, CURLOPT_URL, $url);
        //     curl_setopt($ch, CURLOPT_POST, true);
        //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        //     curl_exec($ch);
        //     curl_close($ch);



        //     $response = curl_exec($ch);

        //     if ($response === false) {
        //     $error = curl_error($ch);
        //     curl_close($ch);
        //     return ['error' => $error];
        //     }

        //     curl_close($ch);
        //     return json_decode($response, true);



    }
    private static function getAccessToken()
    {

        $filePath = storage_path('app/firebase/quickdials-2f0f0.json');
        if (!file_exists($filePath)) {
            return "File not found";
        }

        $client = new Client();
        $client->setHttpClient(
            new \GuzzleHttp\Client([
                'verify' => false
            ])
        );
        $client->setAuthConfig($filePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $token = $client->fetchAccessTokenWithAssertion();

        if (!isset($token['access_token'])) {
            throw new Exception('Unable to fetch access token');
        }

        return $token['access_token'];
    }

}
