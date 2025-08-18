<?php

/**
 * This file handles the callback from the credit card payment gateway form.
 * It processes the credit card information and executes the payment.
 *
 * @link      https://github.com/LinkNacional/whmcs-cielo
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @since     1.0.0
 */

// Require libraries needed for gateway module functions.

define('ROOTDIR', dirname(dirname(dirname(__FILE__))));
require_once ROOTDIR . '/init.php';
require_once ROOTDIR . '/includes/gatewayfunctions.php';
require_once ROOTDIR . '/includes/invoicefunctions.php';
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/gateway_functions.php';
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/card_functions.php';
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/fees_functions.php';
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/validations.php';

defined('WHMCS') or die('WHMCS not found');

/**
 * Validates the fields of the respected payment method (normal or tokenized).
 *
 * @param string $payMethod
 * @param array  $requestFields
 *
 * @return array
 */
function lknc_payment_validate_fields($payMethod, $request)
{
    $req = $request;
    $invoice = [];

    $invoice = lknc_get_invoice($req['invoice']['id']);

    // Validates required fields in both payment methods.
    if (lknc_validate_invoice($req['invoice']['id'], $req['payment']['amount'])) {
        $invoice = lknc_get_invoice($req['invoice']['id']);

        if (
            !lknc_validate_payment_amount($req['payment']['amount'], $invoice['amount']) ||
            !lknc_validate_installment($req['payment']['installment'], $req['payment']['amount'])
        ) {
            return false;
        }
    } else {
        return false;
    }

    // Validates only required fields for normal payment method.
    if ($payMethod === 'normal') {
        if (
            lknc_validate_cvv($req['card']['cvv']) &&
            lknc_validate_card_number($req['card']['number']) &&
            lknc_validate_holder_name($req['card']['holderName']) &&

            lknc_validate_expiration($req['card']['expiration']['month'] . '/' . $req['card']['expiration']['year'])
        ) {
            if (lknc_gateway_params('enableCieloZeroAuth') === 'on') {
                $ISOexpiration = $request['card']['expiration']['year'] . '-' . $request['card']['expiration']['month'];
                $cieloExpiration = date('m/Y', strtotime($ISOexpiration));

                return lknc_cielo_zeroauth_validate_card(
                    'lknc_cielo_credit_card',
                    $req['card']['number'],
                    $req['card']['cvv'],
                    $cieloExpiration,
                    lkn_cielo_credit_brand_to_cielo(lknc_cielo_get_card_brand('lknc_cielo_credit_card', $request['card']['number'])),
                    $req['card']['holderName']
                );
            } else {
                return true;
            }
        } else {
            return false;
        }
    } else {
        // Validates only required fields for tokenzed payment method.
        if (
            lknc_validate_card_token($req['card']['token']) &&
            lknc_validate_card_brand($req['card']['brand'])
        ) {
            if (lknc_gateway_params('enableCieloZeroAuth') === 'on') {
                return lknc_cielo_zeroauth_validate_token(
                    'lknc_cielo_credit_card',
                    $req['card']['token'],
                    $req['card']['brand']
                );
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}

/**
 * Apply filter_input validations.
 *
 * @param array $request
 *
 * @return array
 */
function lknc_payment_parse_fields($payMethod, $request)
{
    $getOnlyLetters = function ($field) {
        $field = preg_replace('/[^a-zA-ZÀ-Ú-à-ú -]/', '', trim($field));

        return str_replace('  ', ' ', $field);
    };

    $getOnlyNumbers = function ($field, $convertTo) {
        $field = preg_replace('/[^0-9.,]/', '', str_replace(',', '.', $field));
        $number = $convertTo === 'int' ? intval($field) : floatval($field);

        if ($convertTo === 'int') {
            $number = intval($field);

            return $number;
        } else {
            return number_format($field, 2, '.', '');
        }
    };

    $removeBlanks = function ($field) {
        return str_replace(' ', '', $field);
    };

    $formatExpiryDate = function ($expiryDate) {
        $inputDate = DateTime::createFromFormat('m/y', $expiryDate);

        return $inputDate->format('m/Y');
    };

    if ($payMethod === 'normal') {
        $cardNumber = $getOnlyNumbers($request['card']['number'], 'int');

        $parsed = [
            'card' => [
                'holderName' => $getOnlyLetters($request['card']['holderName']),
                'number' => $cardNumber,
                'cvv' => $request['card']['cvv'],
                'brand' => lkn_cielo_credit_brand_to_cielo(lknc_cielo_get_card_brand('lknc_cielo_credit_card', $request['card']['number'])),
                'expiration' => [
                    'month' => $request['card']['expiration']['month'],
                    'year' => $request['card']['expiration']['year']
                ]
            ]
        ];

        if ($request['saveCardInProfile'] || lknc_gateway_params('cardSaveMode') === 'Obrigatório') {
            $parsed['card']['save'] = lkn_can_card_be_tokenized('lknc_cielo_credit_card', $cardNumber);
        }

        // Converts the expiration date format to m/Y. e.g.: 08/2033.
        $ISOexpiration = $parsed['card']['expiration']['year'] . '-' . $parsed['card']['expiration']['month'];
        $cieloExpiration = date('m/Y', strtotime($ISOexpiration));

        $parsed['card']['expiration']['full'] = $cieloExpiration;
    } else {
        $parsed = [
            'card' => [
                'token' => $removeBlanks($request['card']['token']),
                'expiry' => $formatExpiryDate($removeBlanks($request['card']['expiry'])),
                'brand' => lkn_cielo_credit_brand_to_cielo(preg_replace('/[^a-zA-Z0-9-] /', '', $request['card']['brand'])),
                'type' => $getOnlyLetters($request['card']['type']),
                'save' => false
            ]
        ];
    }

    $installment = lknc_gateway_params('enableInstallment') === 'on'
        ? $getOnlyNumbers($request['payment']['installment'], 'int')
        : 1;

    $parsed = array_merge([
        'customer' => [
            'id' => filter_var($request['customer']['id'], FILTER_SANITIZE_NUMBER_INT)
        ],
        'payment' => [
            'method' => $request['payment']['method'],
            'installment' => $installment,
            'amount' => $getOnlyNumbers($request['payment']['amount'], 'float')
        ],
        'invoice' => [
            'id' => $getOnlyNumbers($request['invoice']['id'], 'int')
        ]
    ], $parsed);

    lkn_cielo_credit_card_log(
        'lknc_cielo_credit_card',
        'Filtrar dados do pagamento',
        'Filtrar dados do pagamento',
        $request,
        $parsed
    );

    return $parsed;
}

function lknc_payment_response($success, $message)
{
    $message = is_array($message) ? $message : [$message];
    header('Content-Type: application/json');

    echo json_encode([
        'payment' => [
            'success' => $success,
            'message' => $message
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// TODO: mesclear função abaixo com a lknc_normal_payment.
/**
 * @param array $request contains all data required for tokenized payment.
 *
 * @return void
 */
function lknc_tokenized_payment($request)
{
    $requestBody = lknc_payment_generate_body('tokenized', $request);

    $cieloResponse = lknc_capture_payment($requestBody);
    $successStatuses = [1, 2];

    lkn_cielo_credit_card_log(
        'lknc_cielo_credit_card',
        'Pagamento tokenizado',
        'Pagamento tokenizado',
        $requestBody,
        $cieloResponse
    );

    if (
        $cieloResponse &&
        in_array($cieloResponse['Payment']['Status'], $successStatuses, true)
    ) {
        $responseMessages = [];

        $transacReg = lknc_register_success_payment(
            'lknc_cielo_credit_card',
            $request['customer']['id'],
            $request['invoice']['id'],
            $request['card']['brand'],
            $request['payment']['installment'],
            lknc_format_amount_from_cielo_to_whmcs(
                $cieloResponse['Payment']['Amount']
            ),
            $cieloResponse['Payment']['PaymentId']
        );

        $responseMessages[] = $transacReg
            ? 'Pagamento efetuado com sucesso.'
            : 'O pagamento foi efetivado, mas não foi possível aplicar o valor do pagamento à fatura.';

        lknc_payment_response(true, $responseMessages);
    } else {
        lknc_payment_failed($request, $cieloResponse);

        $returnMessages = [];
        $returnMessages[] = 'Não foi possível efetuar o pagamento.';
        $returnMessages[] = 'Motivo do erro: ' . $cieloResponse['Payment']['ReturnMessage'] ?? $cieloResponse[0]['Message'];

        lknc_payment_response(false, $returnMessages);
    }
}

/**
 * @param array $request       array containing the parsed data form client.
 * @param array $cieloResponse
 *
 * @return void
 */
function lknc_payment_failed($request, $cieloResponse)
{
    $cieloReturnCode = $cieloResponse['Payment']['ReturnCode'] ?? $cieloResponse[0]['Code'];

    // In case the $cieloReturnCode is the same, transId will also be the same and WHMCS
    // will not allow its registration. So add random_bytes() to garantee it will be unique.
    $cieloPaymentId = $cieloResponse['Payment']['PaymentId'] ?? ('erro' . bin2hex(random_bytes(3)));

    lknc_register_failed_payment(
        'lknc_cielo_credit_card',
        $request['customer']['id'],
        $request['invoice']['id'],
        $request['card']['brand'],
        $request['payment']['installment'],
        $cieloReturnCode,
        $cieloPaymentId
    );
}

function lknc_normal_payment($request)
{
    $requestBody = lknc_payment_generate_body('normal', $request);

    $cieloResponse = lknc_capture_payment($requestBody);
    $successStatuses = [1, 2];

    lkn_cielo_credit_card_log(
        'lknc_cielo_credit_card',
        'Pagamento normal',
        'Pagamento normal',
        $requestBody,
        $cieloResponse
    );

    if (
        $cieloResponse &&
        in_array($cieloResponse['Payment']['Status'], $successStatuses, true)
    ) {
        $responseMessages = [];

        // Register the transaction in WHMCS.
        $transacReg = lknc_register_success_payment(
            'lknc_cielo_credit_card',
            $request['customer']['id'],
            $request['invoice']['id'],
            $request['card']['brand'],
            $request['payment']['installment'],
            lknc_format_amount_from_cielo_to_whmcs(
                $cieloResponse['Payment']['Amount']
            ),
            $cieloResponse['Payment']['PaymentId']
        );

        $responseMessages[] = $transacReg
            ? 'Pagamento efetuado com sucesso.'
            : 'O pagamento foi efetivado, mas não foi possível aplicar o valor do pagamento à fatura.';

        // Handles save card in customer profile.
        if ($request['card']['save'] || lknc_gateway_params('cardSaveMode') === 'Obrigatório') {
            if (empty($cieloResponse['Payment']['CreditCard']['CardToken'])) {
                $responseMessages[] = 'Não foi possível salvar o cartão de crédito no seu perfil.';
            } else {
                $numberLastFourDigits = substr($request['card']['number'], -4);

                $saveCardStatus = lknc_save_card(
                    $request['invoice']['id'],
                    $cieloResponse['Payment']['CreditCard']['CardToken'],
                    $request['card']['brand'],
                    $request['card']['expiration']['full'],
                    $numberLastFourDigits
                );

                if ($saveCardStatus) {
                    $responseMessages[] = 'O cartão de crédito foi salvo no seu perfil.';
                } else {
                    $responseMessages[] = 'Não foi possível salvar o cartão de crédito no seu perfil.';
                }
            }
        }

        return lknc_payment_response(true, $responseMessages);
    } else {
        lknc_payment_failed($request, $cieloResponse);

        $cieloReturnMessage = $cieloResponse['Payment']['ReturnMessage'] ?? $cieloResponse[0]['Message'];

        return lknc_payment_response(false, 'Motivo do erro: ' . $cieloReturnMessage);
    }
}

/**
 * @param string $paymentMethod
 * @param array  $data
 *
 * @return array
 */
function lknc_payment_generate_body($paymentMethod, $data)
{
    $requestBody = [];

    if ($paymentMethod === 'normal') {
        $requestBody['Customer']['Name'] = $data['card']['holderName'];
        $requestBody['Payment']['CreditCard']['CardNumber'] = $data['card']['number'];
        $requestBody['Payment']['CreditCard']['SecurityCode'] = $data['card']['cvv'];
        $requestBody['Payment']['CreditCard']['ExpirationDate'] = $data['card']['expiration']['full'];
        $requestBody['Payment']['CreditCard']['SaveCard'] = $data['card']['save'];
        $requestBody['Payment']['CreditCard']['Brand'] = $data['card']['brand'];
    } else {
        $requestBody['Payment']['CreditCard']['CardToken'] = $data['card']['token'];
        $requestBody['Payment']['CreditCard']['ExpirationDate'] = $data['card']['expiry'];
    }

    $requestBody['MerchantOrderId'] = $data['invoice']['id'] . '_' . uniqid();
    $requestBody['Payment']['Type'] = 'CreditCard';
    $requestBody['Payment']['Amount'] = lknc_format_payamount_to_cielo($data['payment']['amount']);
    $requestBody['Payment']['Installments'] = $data['payment']['installment'];
    $requestBody['Payment']['SoftDescriptor'] = lkn_cielo_credit_card_get_config('invoiceCustomDescription');
    $requestBody['Payment']['Capture'] = true;

    return $requestBody;
}

/**
 * @param array $requestBody
 *
 * @return array
 */
function lknc_capture_payment($requestBody)
{
    $response = lknc_cielo_credit_card_cielo_api_request('1/sales', $requestBody);

    return $response;
}

/**
 * Execute the payment.
 *
 * @return void
 */
function lknc_payment($request)
{
    try {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Iniciar novo pagamento',
            'Iniciar novo pagamento',
            $request
        );

        if (in_array($request['payment']['method'], ['tokenized', 'normal'], true)) {
            $paymentMethod = $request['payment']['method'];

            if (lknc_payment_validate_fields($paymentMethod, $request)) {
                $request = lknc_payment_parse_fields($paymentMethod, $request);

                if ($paymentMethod === 'tokenized') {
                    lknc_tokenized_payment($request);
                } else {
                    lknc_normal_payment($request);
                }
            } else {
                lknc_payment_response(false, 'Campos inválidos ou vazios.');
            }
        } else {
            lkn_cielo_credit_card_log(
                'lknc_cielo_credit_card',
                'Método de pagamento não suportado',
                'Método de pagamento não suportado',
                $request
            );
            lknc_payment_response(false, 'Método de pagamento não suportado.');
        }
    } catch (Exception $e) {
        lknc_payment_response(false, 'Ocorreu um erro e o pagamento não foi efetuado.');
    }
}
