<?php

/**
 * API for JavaScript HTTP requests coming from invoice view.
 *
 * @since 1.0.0
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';

use WHMCS\Authentication\CurrentUser;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequest;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems\Address;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems\CreditCard;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services\AuthorizationService;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Formatter;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Logger;

try {
    $request = Formatter::stripTagsArray(json_decode(file_get_contents('php://input'), true));

    if (
        !empty($request['address']['billing']['street1'])
        && !empty($request['address']['billing']['street2'])
        && !empty($request['address']['billing']['zipcode'])
        && !empty($request['address']['billing']['city'])
        && !empty($request['address']['billing']['state'])
        && !empty($request['address']['billing']['country'])
    ) {
        $address = new Address(
            $request['address']['billing']['street1'],
            '',
            '',
            $request['address']['billing']['street2'],
            $request['address']['billing']['zipcode'],
            $request['address']['billing']['city'],
            $request['address']['billing']['state'],
            $request['address']['billing']['country']
        );
    } else {
        $address = null;
    }

    $request = new AuthorizationRequest(
        (new CurrentUser())->client()->id,
        $request['payment']['invoiceId'],
        $request['externalAuthentication']['cavv'],
        $request['externalAuthentication']['eci'],
        $request['externalAuthentication']['version'],
        $request['externalAuthentication']['referenceId'],
        $request['merchantOrderId'],
        $request['address']['billing']['customerName'] ?? null,
        $request['address']['billing']['email'] ?? null,
        'BRL',
        ($request['card']['type'] === 'debit' ? 0 : $request['payment']['installments']),
        true,
        true,
        Config::setting('api_soft_descriptor'),
        round($request['payment']['amount'], 2),
        $request['payment']['clientEnabledPartialPayment'],
        $address,
        new CreditCard(
            $request['card']['number'],
            $request['card']['holder'],
            "{$request['card']['expiration']['month']}/{$request['card']['expiration']['year']}",
            false,
            $request['card']['type'],
            $request['card']['cvv']
        )
    );

    $authorizationService = (new AuthorizationService($request))->run();

    header('Content-Type: application/json');

    echo json_encode(
        $authorizationService,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $th) {
    Logger::log(
        'Erro de autorização',
        [
            'msg' => $th->getMessage(),
            'code' => $th->getCode(),
            'file' => $th->getFile()
        ]
    );

    http_response_code(500);

    header('Content-Type: application/json');

    echo json_encode(
        [
            'success' => false,
            'reason' => 'Os dados do pagamento são inválidos.'
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}
