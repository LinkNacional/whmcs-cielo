<?php

/**
 * API for JavaScript HTTP requests coming from invoice view.
 *
 * @since 1.0.0
 */

$bootstrapDir = __DIR__;
$rootDir = null;

for ($i = 0; $i < 6; $i++) {
    if (is_file($bootstrapDir . '/init.php')) {
        $rootDir = $bootstrapDir;
        break;
    }

    $bootstrapDir = dirname($bootstrapDir);
}

if ($rootDir === null) {
    http_response_code(500);
    error_log('Cielo module bootstrap error: init.php not found');
    exit('Bootstrap error');
}

if (!defined('ROOTDIR')) {
    define('ROOTDIR', $rootDir);
}

require_once ROOTDIR . '/init.php';
require_once ROOTDIR . '/includes/gatewayfunctions.php';

use WHMCS\Authentication\CurrentUser;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequest;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems\Address;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems\CreditCard;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services\AuthorizationService;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Formatter;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Logger;

use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Invoice;

try {
    $request = Formatter::stripTagsArray(json_decode(file_get_contents('php://input'), true));

    // Check if payment attempts are blocked
    if (Invoice::mustBlockAttempt3ds($request['payment']['invoiceId'])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'reason' => 'attempts'
        ]);
        exit;
    }

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
