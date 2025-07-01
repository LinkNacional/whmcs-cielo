<?php

/**
 * @link      https://github.com/LinkNacional/whmcs-cielo-credit-card
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @author    Bruno Ferreira <ferreira.bruno@linknacional.com>
 * @since     2.0.0
 */

use WHMCS\Database\Capsule;

/**
 * Returns the name of the gateway.
 *
 * @return string
 */
function lknc_gateway_name()
{
    return 'lknc_cielo_credit_card';
}

/**
 * Returns all gateways settings or just one.
 *
 * @param string|null
 *
 * @return string|array|null
 */
function lknc_gateway_params($param = null)
{
    // Warning: the license is validated even when this gateway is not enabled.
    // getGatewayVariables throwns a error when the gateway is disabled.
    $return = null;

    try {
        $gatewayParams = getGatewayVariables(lknc_gateway_name());

        if ($gatewayParams) {
            $return = $param
                ? $gatewayParams[$param]
                : $gatewayParams;
        } else {
            $return = null;
        }
    } catch (Exception $e) {
        $return = null;
    }

    if ($param === 'cardSaveMode' && $return === null) {
        return 'Opcional';
    }

    return $return;
}

/**
 * Returns the CIELO API URL.
 *
 * @return string
 */
function lknc_cielo_api_url()
{
    return lknc_gateway_params('activateTestMode') === 'on'
        ? 'https://apisandbox.cieloecommerce.cielo.com.br'
        : 'https://api.cieloecommerce.cielo.com.br';
}

/**
 * Returns the CIELO Query URL.
 *
 * @return string
 */
function lknc_cielo_api_query_url()
{
    return lknc_gateway_params('activateTestMode') === 'on'
        ? 'https://apiquerysandbox.cieloecommerce.cielo.com.br'
        : 'https://apiquery.cieloecommerce.cielo.com.br';
}

/**
 * Checks if the gateway log is enabled. If so, log the paramters.
 *
 * @since 1.0.0
 *
 * @param string $gateway
 * @param string $context
 * @param string $result
 * @param array  $request
 * @param array  $response
 *
 * @return void
 */
function lkn_cielo_credit_card_log(
    $gateway,
    $context,
    $result,
    $request = [],
    $response = []
) {
    if (lknc_gateway_params('activateDebug') === 'on') {
        $lastFour = function ($number) {
            $lastFour = substr_replace($number, '', 0, 12);

            return $number !== '' ? "************$lastFour" : '';
        };

        if (isset($request['Payment']['CreditCard']['CardNumber'])) {
            $request['Payment']['CreditCard']['CardNumber'] = $lastFour($request['Payment']['CreditCard']['CardNumber']);
        }

        if (isset($request['card']['number'])) {
            $request['card']['number'] = $lastFour($request['card']['number']);
        }

        if (isset($request['CardNumber'])) {
            $request['CardNumber'] = $lastFour($request['CardNumber']);
        }

        if (isset($response['card']['number'])) {
            $response['card']['number'] = $lastFour($response['card']['number']);
        }

        if (isset($response['CardNumber'])) {
            $response['CardNumber'] = $lastFour($response['CardNumber']);
        }

        if (isset($request['primeiros_seis_digitos'])) {
            $request['primeiros_seis_digitos'] = $request['primeiros_seis_digitos'];
        }

        //ini_set('serialize_precision', -1);

        logTransaction(
            $gateway,
            [
                'context' => $context,
                'request' => $request,
                'response' => $response
            ],
            $result
        );

        //ini_restore('serialize_precision');
    }
}

/**
 * Formats CIELO paid amount pattern into WHMCS pattern.
 *
 * @param int $cieloPaidAmount the payment amount returned by CIELO.
 *
 * @return float|int
 */
function lknc_format_amount_from_cielo_to_whmcs($cieloPaidAmount)
{
    $invoiceAmountCents = substr($cieloPaidAmount, -2);
    $invoiceAmountReal = substr_replace($cieloPaidAmount, '', -2);
    $invoiceAmountFormatted = $invoiceAmountReal . '.' . $invoiceAmountCents;
    $formattedAmount = number_format($invoiceAmountFormatted, 2, '.', '');

    return $formattedAmount;
}

/**
 * @param string $payAmount
 *
 * @return void
 */
function lknc_format_payamount_to_cielo($payAmount)
{
    $formatted = number_format(floatval($payAmount), 2, '.', '');
    $formatted = str_replace('.', '', $formatted);

    return $formatted;
}

/**
 * Turns 100,222 to 100.22, the default float formatting.
 *
 * @param string|float|int $value
 *
 * @return float
 */
function lknc_format_currency_config($value)
{
    $value = preg_replace('/[^0-9,.]/m', '', $value);

    return str_replace(',', '.', $value);
}

/**
 * @return array
 */
function lknc_cielo_request_headers()
{
    $gatewayParams = lknc_gateway_params();

    return [
        'Content-Type: application/json',
        'MerchantId:' . $gatewayParams['debitMerchandId'],
        'MerchantKey:' . $gatewayParams['debitMerchantKey']
    ];
}

/**
 * @param array $transaction
 *
 * @return bool
 */
function lknc_whmcs_register_transaction($gateway, $transaction)
{
    $apiResponse = localAPI('AddTransaction', $transaction);

    lkn_cielo_credit_card_log(
        $gateway,
        'Registrar transação no WHMCS',
        'Registrar transação no WHMCS',
        $transaction,
        $apiResponse
    );

    return $apiResponse['result'] === 'success';
}

/**
 * @param string $gateway
 * @param int    $customerId
 * @param int    $invoiceId
 * @param string $cardBrand
 * @param int    $installment
 * @param float  $paymentAmount
 * @param string $cieloPaymentId
 *
 * @return void
 */
function lknc_register_success_payment(
    $gateway,
    $customerId,
    $invoiceId,
    $cardBrand,
    $installment,
    $paymentAmount,
    $cieloPaymentId
) {
    // Inicializa os atributos
    $fee = 0;
    // Insere o id da parcela num  id de transação com a bandeira do cartão
    $transId = strtoupper($cardBrand) . '.' . "{$installment}x.$cieloPaymentId";

    // Verifica se precisa calcular as taxas
    if (lknc_gateway_params('enableCalculateFees') === 'on') {
        $fee = lkn_cielo_credit_card_get_fees($gateway, $paymentAmount, $installment, $cardBrand);
    }

    // Caso a opção de receber os valores de acordo com a data de processamento da Cielo
    if (lknc_gateway_params('enableCreditByDate') === 'on') {
        // Caso haja uma taxa faz o tratamento das parcelas
        if($fee > 0){
            $fee = $fee / $installment;
            $paymentParcels = floatval(number_format($paymentAmount / $installment, 2, ".", ""));
            $subTotal = floatval(number_format($paymentParcels * $installment, 2, ".", ""));
            // Aqui é verificado os valores de parcelamento
            // Caso o valor total dividido seja um número não exato
            // Ele adiciona ou subtrai o valor na primeira parcela da transação
            // A soma das parcelas precisam ser iguais ao valor total
            if($subTotal < $paymentAmount){
                $total = floatval(number_format($paymentAmount - $subTotal, 2, ".", ""));
                $paymentParcels1 = $total + $paymentParcels;
            }elseif ($subTotal > $paymentAmount) {
                // TODO caso haja erro da Cielo com primeira parcela menor
                // Verificar outra solução (como a Cielo cobra)
                $total = floatval(number_format($subTotal - $paymentAmount, 2, ".", ""));
                $paymentParcels1 = $paymentParcels - $total;
            }else{
                $paymentParcels1 = $paymentParcels;
            }
        }

        // Caso seja apenas uma parcela não precisa passar pelo tratamento
        if($installment == 1) {
            $date = date('d/m/Y', strtotime('+1 month +2 days'));
            $transId = strtoupper($cardBrand) . '.' . "1/{$installment}x.$cieloPaymentId";
                $apiResponse = lknc_whmcs_register_transaction(
                    $gateway,
                    [
                        'userid' => $customerId,
                        'invoiceid' => $invoiceId,
                        'transid' => $transId,
                        'amountin' => $paymentAmount,
                        'fees' => $fee ?? 0,
                        'date' => $date,
                        'paymentmethod' => lknc_gateway_name(),
                        'description' => 'Cartão de crédito'
                    ]
                );
        }else{
            // Em caso de múltiplas parcelas é necessário passar por um loop
            // O mesmo faz a adição dos logs de pagamento quebrando assim as parcelas
            // Dentro da fatura e fazendo o cálculo do mesmo
            for ($i = 1; $i <= $installment; $i++) {
                // WHMCS/Cielo exige que a primeira parcela seja daqui a 1 mês + 2 dias
                if($i == 1 ){
                    $date = date('d/m/Y', strtotime('+1 month +2 days'));
                    $paymentParcelsFinal = $paymentParcels1;
    
                }else{
                    $paymentParcelsFinal = $paymentParcels;
                    $date = date('d/m/Y', strtotime('+'.$i.' month'));
                }
                // Cada parcela é registrada no log da fatura com o id da transação
                $transId = strtoupper($cardBrand) . '.' . "{$i}/{$installment}x.$cieloPaymentId";
                $apiResponse = lknc_whmcs_register_transaction(
                    $gateway,
                    [
                        'userid' => $customerId,
                        'invoiceid' => $invoiceId,
                        'transid' => $transId,
                        'amountin' => $paymentParcelsFinal,
                        'fees' => $fee ?? 0,
                        'date' => $date,
                        'paymentmethod' => lknc_gateway_name(),
                        'description' => 'Cartão de crédito'
                    ]
                );
            }
        }

        return $apiResponse;
    }else{
        return lknc_whmcs_register_transaction(
            $gateway,
            [
                'userid' => $customerId,
                'invoiceid' => $invoiceId,
                'transid' => $transId,
                'amountin' => $paymentAmount,
                'fees' => $fee ?? 0,
                'date' => date('d/m/Y'),
                'paymentmethod' => lknc_gateway_name(),
                'description' => 'Cartão de crédito'
            ]
        );
    }
}

/**
 * @param string $gateway
 * @param int    $customerId
 * @param int    $invoiceId
 * @param string $cardBrand
 * @param int    $installment
 * @param string $cieloReturnCode
 * @param string $cieloPaymentId
 *
 * @return bool
 */
function lknc_register_failed_payment(
    $gateway,
    $customerId,
    $invoiceId,
    $cardBrand,
    $installment,
    $cieloReturnCode,
    $cieloPaymentId
) {
    $transId = strtoupper($cardBrand) . ".{$installment}x.$cieloReturnCode.$cieloPaymentId";

    return lknc_whmcs_register_transaction($gateway, [
        'userid' => $customerId,
        'invoiceid' => $invoiceId,
        'transid' => $transId,
        'amountin' => 0,
        'fees' => 0,
        'date' => date('d/m/Y'),
        'paymentmethod' => lknc_gateway_name(),
        'description' => 'Cartão de crédito'
    ]);
}

/**
 * @param array $params an array returned by WHMCS at invoice payment in client area or admin area.
 *
 * @return string
 */
function lknc_whmcs_find_invoice_area_card_expiration($params)
{
    if (
        isset($params['cardExpiryMonth']) && !empty($params['cardExpiryMonth']) &&
        isset($params['cardExpiryYear']) && !empty($params['cardExpiryYear'])
    ) {
        $return = $params['cardExpiryMonth'] . '/' . $params['cardExpiryYear'];
    } else {
        $expDateTime = explode(' ', $params['payMethod']['payment']['expiry_date']);
        $expDate = explode('-', $expDateTime[0]);
        $return = $expDate[1] . '/' . $expDate[0];
    }

    return $return;
}

/**
 * @param string $resource   Cielo resource.
 * @param array  $body       request body.
 * @param string $httpMethod
 * @param array  $headers    request headers.
 * @param string $url        'api' for Cielo API URL or 'query' for Cielo Query API.
 *
 * @return array
 */
function lknc_cielo_credit_card_cielo_request($resource, $body = [], $httpMethod = 'POST', $headers = [], $url = 'api')
{
    $cieloUrl = $url === 'query'
        ? lknc_cielo_api_query_url()
        : lknc_cielo_api_url();

    $requestHeaders = array_merge($headers, lknc_cielo_request_headers());
    $request = curl_init();

    curl_setopt_array($request, [
        CURLOPT_URL => $cieloUrl . '/' . $resource,
        CURLOPT_CUSTOMREQUEST => $httpMethod,
        CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_RETURNTRANSFER => true
    ]);

    if ($body !== []) {
        curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($request);
    curl_close($request);

    return json_decode($response, true);
}

/**
 * @param string $resource   Cielo resource.
 * @param array  $body       request body.
 * @param string $httpMethod
 * @param array  $headers    request headers.
 *
 * @return array
 */
function lknc_cielo_credit_card_cielo_query_request($resource, $body = [], $httpMethod = 'POST', $headers = [])
{
    return lknc_cielo_credit_card_cielo_request($resource, $body, $httpMethod, $headers, 'query');
}

/**
 * @param string $resource   Cielo resource.
 * @param array  $body       request body.
 * @param string $httpMethod
 * @param array  $headers    request headers.
 *
 * @return array
 */
function lknc_cielo_credit_card_cielo_api_request($resource, $body = [], $httpMethod = 'POST', $headers = [])
{
    return lknc_cielo_credit_card_cielo_request($resource, $body, $httpMethod, $headers, 'api');
}

/**
 * Returns a default JSON if the user has not sent one.
 *
 * @return array|null
 */
function lkn_cielo_credit_card_get_json_fees()
{
    $jsonFeesPath = __DIR__ . '/../inc/';
    $customTaxesPath = $jsonFeesPath . 'custom_taxes.json';

    $jsonContent = file_exists($customTaxesPath)
        ? file_get_contents($customTaxesPath)
        : file_get_contents($jsonFeesPath . 'lknc_cielo_credit_card_taxes.json');

    return json_decode($jsonContent, true);
}

function lkn_cielo_credit_card_invoice_reached_max_attempts($invoiceId)
{
    $maximumPaymentAttempts = lkn_cielo_credit_card_get_config('maximumPaymentAttempts') ?? 0;

    if ($maximumPaymentAttempts === 0) {
        return false;
    }

    $invoiceTransactions = localAPI(
        'GetTransactions',
        ['invoiceid' => $invoiceId]
    );

    if (isset($invoiceTransactions['transactions']['transaction'])) {
        $transactions = $invoiceTransactions['transactions']['transaction'];

        $invoiceTransactionsForGateway = array_filter($transactions, function ($transaction) {
            return $transaction['gateway'] === lknc_gateway_name();
        });

        $invoiceTransactionsForGateway = array_reverse($invoiceTransactionsForGateway);

        $invalidTransactions = 0;

        for ($index = 0; $index < $maximumPaymentAttempts; $index++) {
            $transaction = $invoiceTransactionsForGateway[$index];
            $transactionId = explode('.', $transaction['transid']);

            // Failed transactions are store with the following transid: Brand.Installment.CieloReturnCode.CieloPaymentId.
            // Successful transactions do not have Cielo return code in its transid.
            if (count($transactionId) === 4) {
                $invalidTransactions++;
            }
        }

        if ($invalidTransactions >= $maximumPaymentAttempts) {
            return true;
        }
    }
}

/**
 * @since 2.2.2
 * @link https://github.com/LinkNacional/whmcs-cielo-credit-card/issues/101
 *
 * @param string $invoiceId
 *
 * @return bool|void
 */
function lkn_cielo_credit_card_must_block_attempt($invoiceId)
{
    $invoiceId = strip_tags($invoiceId);

    if ($invoiceId === null) {
        return true;
    }

    if (lkn_cielo_credit_card_invoice_reached_max_attempts($invoiceId)) {
        return true;
    }

    // See: https://developers.whmcs.com/advanced/authentication/
    $currentUser = new \WHMCS\Authentication\CurrentUser();
    $client = $currentUser->client();
    $user = $currentUser->user();
    $admin = $currentUser->admin();

    if (!$client && !$user && !$admin && !$currentUser->isMasqueradingAdmin()) {
        return true;
    }

    if (!$admin) {
        $clientId = $client->id ?? $user->id;

        $isValidInvoiceOwnwerAndStatus = Capsule::table('tblinvoices')
            ->where('id', $invoiceId)
            ->where('userid', $clientId)
            ->where('status', 'Unpaid')
            ->exists();

        if (!$isValidInvoiceOwnwerAndStatus) {
            return true;
        }
    }

    return false;
}

/**
 * @since 2.4.0
 *
 * @param string $name
 *
 * @return string
 */
function lkn_cielo_credit_card_get_config($name)
{
    $configs = getGatewayVariables(lknc_gateway_name());

    if (array_key_exists($name, $configs)) {
        $config = $configs[$name];
    } else {
        $config = null;
    }

    if ($name === 'cardSaveMode' && $config === null) {
        return 'Opcional';
    }

    return lkn_cielo_credit_card_format_config($name, $config);
}

/**
 * @since 2.4.0
 *
 * @param string $name
 * @param string $value
 *
 * @return mixed|string|int
 */
function lkn_cielo_credit_card_format_config($name, $value)
{
    switch ($name) {
        case 'invoiceCustomDescription':
            $newValue = preg_replace('/[^a-zA-Z0-9 ]/', '', $value);
            $newValue = preg_replace('/\s\s+/', ' ', trim($newValue));
            $newValue = substr($newValue, 0, 12);

            break;
        case 'partialPaymentMinimumAmount':
            $newValue = preg_replace('/[^,.0-9 ]/', '', $value);
            $newValue = str_replace(',', '.', $newValue);
            $newValue = floatval($newValue);

            break;
        case 'minimumInstallmentAmount':
            $newValue = preg_replace('/[^,.0-9 ]/', '', $value);
            $newValue = str_replace(',', '.', $newValue);
            $newValue = floatval($newValue);

            break;
        case 'maximumPaymentAttempts':
            $newValue = preg_replace('/[^0-9]/', '', $value);
            $newValue = intval($newValue);

            break;

        case 'maxAttemptsReachedFeedback':
            $newValue = trim($value);

            break;
        default:
            $newValue = $value;

            break;
    }

    return $newValue;
}

/**
 * @since 2.4.0
 *
 * @return string
 */
function lkn_cielo_credit_card_whmcs_installation_path()
{
    return rtrim(Capsule::table('tblconfiguration')
    ->where('setting', 'SystemURL')
    ->value('value'), '/');
}
