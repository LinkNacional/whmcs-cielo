<?php

/**
 * API for JavaScript HTTP requests coming from invoice view.
 *
 * @see https://developers.whmcs.com/advanced/authentication/
 *
 * @since 1.0.0
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/license.php';

use WHMCS\Authentication\CurrentUser;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\ApiController;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Formatter;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Response;

if (lkncielo3ds_check_license() !== true) {
    http_response_code(401);

    exit;
}

$request = Formatter::stripTagsArray(json_decode(file_get_contents('php://input'), true) ?? $_POST);
$action = $request['a'];

$currentUser = new CurrentUser();
$isUserLogged = $currentUser->client() ?? $currentUser->user() ?? $currentUser->admin() ?? false;

if (!$isUserLogged) {
    exit;
}

$response = [];

switch ($action) {
    case 'bin':
        $response = (new ApiController())->requestBin($request);

        Response::raw($response);

        break;

    case 'download-taxes-json':
        if (!$currentUser->admin()) {
            exit;
        }

        (new ApiController())->downloadJsonTaxes();

        break;

    case 'download-default-taxes-json':
        if (!$currentUser->admin()) {
            exit;
        }

        (new ApiController())->downloadDefaultJsonTaxes();

        break;

    case 'upload-taxes-json':
        if (!$currentUser->admin()) {
            exit;
        }

        (new ApiController())->uploadJsonTaxes($request);

        break;

    default:
        http_response_code(404);

        exit;
}
