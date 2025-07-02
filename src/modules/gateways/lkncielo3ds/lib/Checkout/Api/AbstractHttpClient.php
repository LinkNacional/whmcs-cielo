<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Api;

use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Logger;
use stdClass;

abstract class AbstractHttpClient
{
    /**
     * @since 1.0.0
     *
     * @param string     $method
     * @param string     $baseUrl
     * @param string     $endpoint
     * @param array|null $body
     * @param array|null $header
     *
     * @return stdClass|array|stdClass[]
     */
    protected function httpRequest(
        string $method,
        string $baseUrl,
        string $endpoint,
        array $body = [],
        array $header = []
    ): stdClass|array|null {
        $header[] = 'Content-Type: application/json';

        $request = curl_init();
        $requestUrl = "$baseUrl/$endpoint";

        $curlOptions = [
            CURLOPT_URL => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $header
        ];

        if (in_array($method, ['POST', 'PUT'], true)) {
            if ($body === []) {
                $curlOptions[CURLOPT_POSTFIELDS] = '{}';
            } else {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        curl_setopt_array($request, $curlOptions);

        $response = curl_exec($request);

        Logger::log(
            'debug request',
            [
                'headers' => $header,
                'body' => $body,
                'url' => $requestUrl
            ],
            [
                'res' => $response,
                'curl_error' => curl_error($request),
                'curl_errno' => curl_errno($request),
                'curl_getinfo' => curl_getinfo($request),
                'curlOptions' => $curlOptions
            ]
        );

        curl_close($request);

        return json_decode($response);
    }
}
