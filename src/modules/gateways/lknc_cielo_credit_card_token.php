<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/../../includes/gatewayfunctions.php';
require_once __DIR__ . '/lknc_cielo_credit_card/helpers/gateway_functions.php';
require_once __DIR__ . '/lknc_cielo_credit_card/helpers/token_gateway_functions.php';
require_once __DIR__ . '/lknc_cielo_credit_card/helpers/card_functions.php';
require_once __DIR__ . '/lknc_cielo_credit_card/helpers/fees_functions.php';
require_once __DIR__ . '/lknc_cielo_credit_card/helpers/license_functions.php';
require_once __DIR__ . '/lknc_cielo_credit_card/helpers/validations.php';
require_once __DIR__ . '/callback/lknc_cielo_credit_card.php';

/**
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 * @return array
 */
function lknc_cielo_credit_card_token_MetaData()
{
    return [
        'DisplayName' => 'Cielo Cartão de Crédito Tokenizado',
        'APIVersion' => '1.1', // Use API Version 1.1
        //'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => true
    ];
}

/**
 * @see https://developers.whmcs.com/payment-gateways/configuration/
 * @return array
 */
function lknc_cielo_credit_card_token_config($params)
{
    $moduleVersion = '2.10.0';
    $systemUrl = rtrim($params['systemurl'], '/');

    $smarty = new Smarty();

    $smarty->setTemplateDir(__DIR__ . '/lknc_cielo_credit_card/inc/templates/');
    $smarty->assign('moduleVersion', $moduleVersion);
    $smarty->assign('moduleUrl', "$systemUrl/modules/gateways/lknc_cielo_credit_card");
    $smarty->assign('moduleName', 'Cielo Cartão de Crédito Tokenizado');
    $header = $smarty->fetch('config_header.tpl');

    $configs = [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Cielo Cartão de Crédito Tokenizado'
        ],
        '' => ['Description' => $header],
        'warnings' => [
            'FriendlyName' => '',
            'Description' => '
            <div style="display: flex; justify-content: center; align-items: center;">
                <p style="font-weight: bold;">
                    Este gateway utiliza as configurações do gateway Cartão de Crédito - (CIELO).
                </p>
            </div>'
        ]
    ];

    $isLinkNacionalLicenseValid = lknc_check_license();

    if ($isLinkNacionalLicenseValid !== true) {
        $configs['warnings']['Description'] = <<<HTML
            <p style="color:#cc0000; font-weight: bold;">
            $isLinkNacionalLicenseValid
            </p>
HTML;
    }

    // Checks if any error was added to the warning config.
    // If not, remove from the confis array.
    if ($configs['warnings']['Description'] === '') {
        unset($configs['warnings']);
    }

    return $configs;
}

/**
 * @see @link https://developers.whmcs.com/payment-gateways/tokenised-remote-storage/ Remote Storage
 *
 * @param array $params
 *
 * @return array $resposta
 */
function lknc_cielo_credit_card_token_storeremote($params)
{
    $action = $params['action'];
    $cardPreviousToken = $params['gatewayid'] ?? '';

    $holderName = $params['clientdetails']['fullname'];

    $cardBrand = lknc_cielo_get_card_brand('lknc_cielo_credit_card_token', $params['cardnum']);
    $cardNumber = $params['cardnum'];
    $cardCvv = $params['cardcvv'];
    $cardExpiration = substr($params['cardexp'], 0, 2) . '/20' . substr($params['cardexp'], 2, 3);

    switch ($action) {
        case 'create':
            $cieloCardBrand = lkn_cielo_credit_brand_to_cielo($cardBrand);
            $tokenizaiton = lknc_tokenize_card(
                'lknc_cielo_credit_card_token',
                $holderName,
                $cardNumber,
                $cardCvv,
                $cardExpiration,
                $cieloCardBrand
            );

            if ($tokenizaiton['success']) {
                return [
                    'status' => 'success',
                    'gatewayid' => $cieloCardBrand . '|' . $tokenizaiton['token'],
                    'rawdata' => 'Cartão tokenizado com sucesso.'
                ];
            }

            return [
                'status' => 'error',
                'gatewayid' => $cardPreviousToken,
                'rawdata' => $tokenizaiton
            ];
        case 'update':
            // Card number is not returned to update action. So it will fail everytime.
            return [
                'status' => 'error',
                'rawdata' => 'Atualização do cartão não suportada'
            ];
        case 'delete':
            return [
                'status' => 'success',
                'rawdata' => print_r($params, true)
            ];

            break;
    }
}

/**
 * Faz a captura do pagamento
 *
 * @param array $params
 *
 * @return array $resposta
 */
function lknc_cielo_credit_card_token_capture($params)
{
    $allowPaymentOnlyForBrl = lkn_cielo_credit_card_get_config('allow_payment_only_for_brl') ?? true;

    if ($allowPaymentOnlyForBrl && $params['currency'] !== 'BRL') {
        return [
            'status' => 'error',
            'transid' => 'PGTOAPENASPARABRL',
            'rawdata' => ['currency' => $params['currency']]
        ];
    }

    if (lkn_cielo_credit_card_invoice_reached_max_attempts($params['invoiceid'])) {
        lkn_cielo_credit_card_log('lknc_cielo_credit_card_token', 'Usuário bloqueado', 'failure', $params);

        return [
            'status' => 'error',
            'transid' => 'USUARIO.BLOQUEADO'
        ];
    }

    $amount = floatval(str_replace(',', '.', $params['amount']));
    $isValidInvoice = lknc_validate_invoice($params['invoiceid'], $amount);

    if (!$isValidInvoice) {
        lkn_cielo_credit_card_log('lknc_cielo_credit_card_token', 'Valor de pagamento inválido para fatura', 'failure', $params);

        return [
            'status' => 'error',
            'transid' => 'PGTO.INVALIDO'
        ];
    }

    $isLinkNacionalLicenseValid = lknc_check_license();

    if ($isLinkNacionalLicenseValid !== true) {
        lkn_cielo_credit_card_log('lknc_cielo_credit_card_token', 'Token: licença inválida', 'failure', $params);

        return [
            'status' => 'error',
            'transid' => 'LICENÇA.INVÁLIDA'
        ];
    }

    $requestBody = lknc_whmcs_capture_to_cielo_payment_body($params);

    if (lknc_gateway_params('enableCieloZeroAuth') === 'on') {
        // Validates a card token
        if (isset($requestBody['Payment']['CreditCard']['CardToken'])) {
            $zeroAuth = lknc_cielo_zeroauth_validate_token(
                'lknc_cielo_credit_card_token',
                $requestBody['Payment']['CreditCard']['CardToken'],
                $requestBody['Payment']['CreditCard']['Brand']
            );
        } else {
            $zeroAuth = lknc_cielo_zeroauth_validate_card(
                'lknc_cielo_credit_card_token',
                $requestBody['Payment']['CreditCard']['CardNumber'],
                $requestBody['Payment']['CreditCard']['SecurityCode'],
                $requestBody['Payment']['CreditCard']['ExpirationDate'],
                $requestBody['Payment']['CreditCard']['Brand'],
                $requestBody['Customer']['Name']
            );
        }

        if (!$zeroAuth) {
            lkn_cielo_credit_card_log('lknc_cielo_credit_card_token', 'Token: zeroAuth inválido', 'failure', $requestBody);

            return [
                'status' => 'error',
                'transid' => 'ERRO.ZEROAUTH',
                'rawdata' => ['request' => $requestBody, 'response' => $zeroAuth]
            ];
        }
    }

    $cieloResponse = lknc_capture_payment($requestBody);

    $logText = $params['cardnum'] === '' ? 'tokenizado' : 'normal';
    lkn_cielo_credit_card_log(
        'lknc_cielo_credit_card_token',
        "Pagamento $logText",
        "Pagamento $logText",
        $requestBody,
        $cieloResponse
    );

    $successStatuses = [1, 2];
    $installment = $requestBody['Payment']['Installments'] ;
    $cieloCardBrand = $requestBody['Payment']['CreditCard']['Brand'];
    $cieloPaymentId = $cieloResponse['Payment']['PaymentId'];
    $cieloReturnCode = $cieloResponse['Payment']['ReturnCode'] ?? $cieloResponse[0]['Code'];

    // Creates the transaction id.
    if (in_array($cieloResponse['Payment']['Status'], $successStatuses, true)) {
        $status = 'success';
        $transacId = strtoupper($cieloCardBrand) . ".{$installment}x" . ".$cieloPaymentId";
    } else {
        $status = 'error';
        $transacId =
            strtoupper($cieloCardBrand) . ".{$installment}x" .
            ($cieloReturnCode ? ".$cieloReturnCode" : '') . ".$cieloPaymentId";
    }

    if (lknc_gateway_params('enableCalculateFees') === 'on') {
        $fee = lkn_cielo_credit_card_get_fees(
            'lknc_cielo_credit_card_token',
            lknc_format_amount_from_cielo_to_whmcs($cieloResponse['Payment']['Amount']),
            $installment,
            $cieloCardBrand
        );
    }

    return [
        'status' => $status,
        'transid' => $transacId,
        'fee' => $fee ?? 0,
        'rawdata' => ['request' => $requestBody, 'response' => $cieloResponse]
    ];
}

/**
 * Método para reembolso
 *
 * @param array $params
 *
 * @return array $resposta
 */

function lknc_cielo_credit_card_token_refund($params)
{
    $transactionId = explode('.', $params['transid'])[2];

    $invoice = localAPI('GetInvoice', ['invoiceid' => $params['invoiceid']]);
    $invoiceAmount = number_format(floatval($invoice['subtotal']), 2);

    // Verify if the invoice must be completely refounded.
    $refundAmount = floatval($params['amount']);
    $refundTotal = $invoiceAmount === $refundAmount;

    $refundUrl = !$refundTotal ? '?amount=' . $refundAmount * 100 : '';

    $requestBody = ['PaymentId' => $transactionId];

    $request = curl_init();

    curl_setopt_array($request, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_URL => lknc_cielo_api_url() . '/1/sales/' . $transactionId . '/void' . $refundUrl,
        CURLOPT_HTTPHEADER => lknc_cielo_request_headers(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($requestBody)
    ]);

    $responseJson = curl_exec($request);
    $responseArray = json_decode($responseJson, true);

    curl_close($request);

    $successCodes = [10, 11, 2];

    if (in_array($responseArray['Status'], $successCodes, true)) {
        return ['status' => 'success', 'rawdata' => $responseJson, 'transid' => 'REEMBOLSO.' . $transactionId];
    } else {
        return ['status' => 'failed', 'rawdata' => $responseJson];
    }
}
