<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Api;

use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Logger;

/**
 * Holds request to the Authentication API.
 *
 * @since 1.0.0
 */
final class AuthorizationApi extends AbstractHttpClient
{
    private readonly string $baseUrl;
    private readonly string $authBasic;

    public function __construct(
        string $apiClientId,
        string $apiClientSecret
    ) {
        $this->baseUrl = Config::env('accessTokenUrl');
        $this->authBasic = base64_encode("$apiClientId:$apiClientSecret");
    }

    /**
     * @since 1.0.0
     * @see https://developercielo.github.io/manual/integracao-javascript#passo-1-solicita%C3%A7%C3%A3o-de-token-de-acesso
     *
     * @param string $establishmentCode
     * @param string $merchantName
     * @param string $mac
     *
     * @return string|null
     */
    public function requestAccessToken(
        string $establishmentCode,
        string $merchantName,
        string $mac
    ): ?string {
        $header = ["Authorization: Basic {$this->authBasic}"];
        $body = [
            'EstablishmentCode' => $establishmentCode,
            'MerchantName' => $merchantName,
            'MCC' => $mac
        ];

        $response = $this->httpRequest(
            'POST',
            $this->baseUrl,
            'v2/auth/token',
            $body,
            $header
        );

        Logger::log(
            'Solicitar token de acesso',
            ['body' => $body, 'header' => $header],
            $response
        );

        return $response->access_token;
    }
}
