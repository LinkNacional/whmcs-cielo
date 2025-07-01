<?php

require_once __DIR__ . '/gateway_functions.php';

/**
 * @param int    $invoiceId
 * @param string $token
 * @param string $brand
 * @param string $expiration
 * @param string $numberLastFourDigits
 *
 * @return bool
 */
function lknc_save_card($invoiceId, $token, $brand, $expiration, $numberLastFourDigits)
{
    $arrayLog = [
        'ID da fatura' => $invoiceId,
        'Token do cartão' => $token,
        'Bandeira do cartão' => $brand,
        'Expiração do cartão' => $expiration,
        'Últimos quatro digitos do cartão' => $numberLastFourDigits
    ];

    try {
        // see: https://github.com/LinkNacional/whmcs-cielo-credit-card/blob/508e90263727d252349e1b5df14b8d00f1623d0d/modules/gateways/callback/lknc_cielo_credit_card.php#L445-L446
        // $gateway = new WHMCS\Module\Gateway(lknc_gateway_name());
        // $gateway->load(lknc_gateway_name());

        $gateway = new WHMCS\Module\Gateway('lknc_cielo_credit_card_token');
        $gateway->load('lknc_cielo_credit_card_token');

        $invoice = WHMCS\Billing\Invoice::find($invoiceId);
        $billingContact = $invoice->client->contacts->find($invoice->client->billingContactId);
        $newPayMethod = WHMCS\Payment\PayMethod\Adapter\RemoteCreditCard::factoryPayMethod(
            $invoice->client,
            $billingContact,
            'Adicionado via fatura'
        );

        $payment = $newPayMethod->payment;

        $newPayMethod->setGateway($gateway);
        $remoteToken = "$brand|$token";
        $payment->setRemoteToken($remoteToken);
        $payment->setCardType($brand); // cardType == card brand
        $payment->setExpiryDate(WHMCS\Carbon::createFromCcInput($expiration));
        $payment->setLastFour($numberLastFourDigits);

        $paymentStatus = $payment->save();
        $payMethodStatus = $newPayMethod->save();

        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Salvar cartão no perfil do cliente',
            'Salvar cartão no perfil do cliente',
            $arrayLog,
            ['paymentStatus' => "$paymentStatus", 'payMethodStatus' => "$payMethodStatus"]
        );

        return $paymentStatus && $payMethodStatus;
    } catch (Exception $e) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Erro ao salvar cartão no perfil do cliente',
            'Erro ao salvar cartão no perfil do cliente',
            $arrayLog,
            [$e]
        );

        return false;
    }
}

/**
 * @param string $gateway
 * @param int    $number
 * @param int    $cvv
 * @param string $expirationDate
 * @param string $brand
 * @param string $holderName
 *
 * @return array
 */
function lknc_cielo_zeroauth_validate_card($gateway, $number, $cvv, $expirationDate, $brand, $holderName)
{
    $response = lknc_cielo_zeroauth($gateway, [
        'CardType' => 'CreditCard',
        'CardNumber' => $number,
        'Holder' => $holderName,
        'ExpirationDate' => $expirationDate,
        'SecurityCode' => $cvv,
        'Brand' => lkn_cielo_credit_brand_to_cielo($brand),
        'SaveCard' => false
    ]);

    if (is_array($response)) {
        return isset($response['Valid']) && $response['Valid'] === true;
    } else {
        // Invalid brand or ZeroAuth not enabled.
        return true;
    }
}

/**
 * @param string $gateway
 * @param string $cardToken
 * @param string $cardBrand
 *
 * @return array
 */
function lknc_cielo_zeroauth_validate_token($gateway, $cardToken, $cardBrand)
{
    $response = lknc_cielo_zeroauth($gateway, [
        'CardToken' => $cardToken,
        'Brand' => lkn_cielo_credit_brand_to_cielo($cardBrand),
        'SaveCard' => false
    ]);

    if (is_array($response)) {
        return isset($response['Valid']) && $response['Valid'] === true;
    } else {
        // Invalid brand or ZeroAuth not enabled.
        return true;
    }
}

/**
 * Takes a request body, sends to CIELO and returns the response as an array.
 *
 * @param string $gateway
 * @param string $requestBody
 *
 * @return bool|array
 *                    Returns true if the ZeroAuth operation is not supported with
 *                    the $brand or if the ZeroAuth is not enabled for the CIELO license.
 *                    Otherwise, returns an array containing the CIELO's response.
 */
function lknc_cielo_zeroauth($gateway, $requestBody)
{
    // Checks if the card brand is supported by ZeroAuth.
    // See zeroauth: https://developercielo.github.io/manual/cielo-ecommerce#zero-auth
    $brandInCieloPattern = lkn_cielo_credit_brand_to_cielo($requestBody['Brand']);

    if (!in_array($brandInCieloPattern, ['visa', 'master', 'elo'], true)) {
        lkn_cielo_credit_card_log(
            $gateway,
            'Bandeira não suportada para validação via ZeroAuth.',
            'Bandeira não suportada para validação via ZeroAuth.',
            $requestBody
        );

        return true;
    }

    $response = lknc_cielo_credit_card_cielo_request('1/zeroauth', $requestBody);

    // Returns true if ZeroAuth is not enabled for the current CIELO license.
    if (
        isset($response['Code']) &&
        ($response['Code'] === 389 || $response['Code'] === 322)
    ) {
        lkn_cielo_credit_card_log(
            $gateway,
            'Restrição cadastral de ZeroAuth. ZeroAuth não está habilitado para sua licença.',
            'Restrição cadastral de ZeroAuth. ZeroAuth não está habilitado para sua licença.',
            $requestBody,
            $response
        );

        return true;
    } else {
        lkn_cielo_credit_card_log(
            $gateway,
            'Verificação ZeroAuth',
            'Verificação ZeroAuth',
            $requestBody,
            $response
        );
    }

    return $response;
}

/**
 * @param string $gateway
 * @param string $cardNumber
 *
 * @return string card brand in lowercase.
 */
function lknc_cielo_get_card_brand($gateway, $cardNumber)
{
    $cardFirstNineDigits = $cardNumber;

    $cieloResource = '1/cardBin/' . $cardFirstNineDigits;
    $response = lknc_cielo_credit_card_cielo_query_request($cieloResource, [], 'GET');

    lkn_cielo_credit_card_log(
        $gateway,
        'Captura online da bandeira do cartão',
        'Captura online da bandeira do cartão',
        ['numero_cartao' => $cardFirstNineDigits],
        [$response]
    );

    if (isset($response['Provider'])) {
        return strtolower($response['Provider']);
    }

    $cardBrand = lkn_get_card_brand_offline($cardNumber);
    lkn_cielo_credit_card_log(
        $gateway,
        'Capturar offline a bandeira do cartão',
        'Capturar offline a bandeira do cartão',
        ['cardNumber' => $cardNumber],
        ['cardBrand' => $cardBrand]
    );

    return $cardBrand;
}

/**
 * Checks if a card can be saved.
 *
 * @param string $gateway
 * @param string $cardNumber
 *
 * @return bool
 */
function lkn_can_card_be_tokenized($gateway, $cardNumber)
{
    $brand = lknc_cielo_get_card_brand($gateway, $cardNumber);

    return $brand !== null;
}

/**
 * Converts a card brand into a standard accepted by CIELO. Ex: American Express becomes amex.
 *
 * @param  string $brand
 * @return string
 */
function lkn_cielo_credit_brand_to_cielo($brand)
{
    $internalBrand = str_replace(' ', '', strtolower($brand));

    switch ($internalBrand) {
        case 'americanexpress':
            return 'amex';
        case 'mastercard':
            return 'master';
        case 'dinersclub':
            return 'diners';
        default:
            return strtolower($brand);
    }
}

/**
 * Converts a standard CIELO card brand into its full name.
 *
 * @param  string $brand
 * @return string
 */
function lkn_cielo_credit_brand_to_whmcs($brand)
{
    $internalBrand = str_replace(' ', '', strtolower($brand));

    switch ($internalBrand) {
        case 'amex':
            return 'american express';
        case 'master':
            return 'mastercard';
        case 'diners':
            return 'diners club';
        default:
            return strtolower($brand);
    }
}

/**
 * ATTENTION: do not move this function away. Otherwise, this will not work in _storeremote().
 *
 * @param string $gateway
 * @param string $customerName
 * @param string $number
 * @param string $holder
 * @param string $expiration   - card`s expiration date in CIELO pattern (mm/yy).
 * @param string $brand        - card's brand in CIELO pattern.
 *
 * @return string empty string if an error was ocurred or string if the token was created.
 */
function lknc_tokenize_card($gateway, $holderName, $number, $cvv, $expiration, $brand)
{
    $zeroAuthValidation = true;

    if (lknc_gateway_params('enableCieloZeroAuth') === 'on') {
        $zeroAuthValidation = lknc_cielo_zeroauth(
            $gateway,
            [
                'CardNumber' => $number,
                'Holder' => $holderName,
                'ExpirationDate' => $expiration,
                'SecurityCode' => $cvv,
                'SaveCard' => false,
                'Brand' => $brand
            ]
        );
    }

    if ($zeroAuthValidation === true || $zeroAuthValidation['Valid'] === true) {
        $requestBody = [
            'CustomerName' => $holderName,
            'CardNumber' => $number,
            'Holder' => $holderName,
            'ExpirationDate' => $expiration,
            'Brand' => $brand
        ];

        $response = lknc_cielo_credit_card_cielo_api_request('1/card', $requestBody);

        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card_token',
            'Tokenizar',
            'Tokenizar',
            $requestBody,
            $response
        );

        return [
            'success' => isset($response['CardToken']),
            'token' => $response['CardToken'] ?? ''
        ];
    } else {
        return [
            'success' => false,
            'msg' => $zeroAuthValidation['ReturnMessage'] ?? $zeroAuthValidation['Message']
        ];
    }
}

/**
 * Returns card brands accepted by CIELO according to a card number.
 *
 * @param string $number card number
 *
 * @see https://github.com/LinkNacional/give-cielo/blob/main/includes/actions.php#L411-L484
 * @return string|null
 */
function lkn_get_card_brand_offline($number)
{
    $brandsRegex = [
        'elo' => '/(4011|431274|438935|451416|457393|4576|457631|457632|504175|627780|636297|636368|636369|(6503[1-3])|(6500(3[5-9]|4[0-9]|5[0-1]))|(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|(650(48[5-9]|49[0-9]|50[0-9]|51[1-9]|52[0-9]|53[0-7]))|(6505(4[0-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|(6507(0[0-9]|1[0-8]))|(6507(2[0-7]))|(650(90[1-9]|91[0-9]|920))|(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[1-9]))|(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8]))|(506(699|77[0-8]|7[1-6][0-9))|(509([0-9][0-9][0-9])))/',
        'hipercard' => '/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',
        'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
        'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/',
        'aura' => '/^50[0-9]{14,17}$/',
        'amex' => '/^3[47][0-9]{13}$/',
        'master' => '/^5[1-5]\d{14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{12}$/',
        'visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/'
    ];

    foreach ($brandsRegex as $brand => $regex) {
        if (preg_match($regex, $number)) {
            return $brand;
        }
    }

    return null;
}
