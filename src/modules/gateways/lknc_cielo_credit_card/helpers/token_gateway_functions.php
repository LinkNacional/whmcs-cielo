<?php

/**
 * @link      https://github.com/LinkNacional/whmcs-cielo
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @since     2.0.0
 */

define('ROOTDIR', dirname(dirname(dirname(dirname(__FILE__)))));
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/card_functions.php';
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/gateway_functions.php';

/**
 * Returns the name of the gateway.
 *
 * @return string
 */
function lknc_token_gateway_name()
{
    return 'lknc_cielo_credit_card_token';
}

/**
 * @param array $params array that WHMCS passes to _capture function.
 *
 * @return array
 */
function lknc_whmcs_capture_to_cielo_payment_body($params)
{
    $requestBody = [];
    $cardNumber = $params['cardnum'];

    // It's a tokenized payment when $cardNumber is empty.
    if ($cardNumber === '') {
        // Since version 2.1.2, the brand is saved toegether with the CIELO token.
        $explodedRemoteToken = explode('|', $params['gatewayid']);
        $hasBrandInRemoteToken = count($explodedRemoteToken) > 1 && $explodedRemoteToken[0] !== '';

        $cardBrand = $hasBrandInRemoteToken ? $explodedRemoteToken[0] : $params['cardtype'];
        $token = $hasBrandInRemoteToken ? $explodedRemoteToken[1] : $params['gatewayid'];

        $requestBody['Payment']['CreditCard']['Brand'] = lkn_cielo_credit_brand_to_cielo($cardBrand);
        $requestBody['Payment']['CreditCard']['CardToken'] = $token;
    } else {
        $requestBody['Payment']['CreditCard']['CardNumber'] = $cardNumber;
        $requestBody['Payment']['CreditCard']['SecurityCode'] = $params['cccvv'];
        $cardBrand = lknc_cielo_get_card_brand('lknc_cielo_credit_card_token', $cardNumber);
        $requestBody['Payment']['CreditCard']['Brand'] = lkn_cielo_credit_brand_to_cielo($cardBrand);

        $expiration = explode(' ', $params['payMethod']['payment']['expiry_date']);
        $expiration = explode('-', $expiration[0]);
        $requestBody['Payment']['CreditCard']['ExpirationDate'] = lknc_whmcs_find_invoice_area_card_expiration($params);
    }

    $requestBody['Customer']['Name'] = $params['clientdetails']['fullname'];
    $requestBody['MerchantOrderId'] = $params['invoiceid'] . '_' . uniqid();
    $requestBody['Payment']['Type'] = 'CreditCard';
    $requestBody['Payment']['Amount'] = lknc_format_payamount_to_cielo($params['amount']);
    $requestBody['Payment']['Installments'] = $_POST['lknc_installment'] ?? 1;
    $requestBody['Payment']['SoftDescriptor'] = lkn_cielo_credit_card_get_config('invoiceCustomDescription');
    $requestBody['Payment']['Capture'] = true;
    $requestBody['Payment']['CreditCard']['SaveCard'] = false;

    return $requestBody;
}
