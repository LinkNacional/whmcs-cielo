<?php

/**
 * @link      https://github.com/LinkNacional/whmcs-cielo
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @since     2.0.0
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

$request = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (empty($request) || !isset($request['action'])) {
    exit('No $resquest or $action');
}

require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/gateway_functions.php';
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/card_functions.php';
require_once ROOTDIR . '/modules/gateways/callback/lknc_cielo_credit_card.php';

header('Content-Type: application/json');

switch ($request['action']) {
    case 'payment':
        if (lkn_cielo_credit_card_must_block_attempt($request['invoice']['id']) ?? null) {
            echo '{"success": false, "reason": "attempts"}';
            exit;
        }

        lknc_payment($request);

        break;

    case 'update-json-fees':
        $currentUser = new \WHMCS\Authentication\CurrentUser();

        if (!$currentUser->admin()) {
            exit;
        }

        lknc_update_json_fees();

        break;
    case 'card-can-be-tokenized':
        if (lkn_cielo_credit_card_must_block_attempt($request['invoice']['id']) ?? null) {
            exit;
        }

        $cardNumber = filter_var($request['cardNumber'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $canBeTokenized = lkn_can_card_be_tokenized('lknc_cielo_credit_card', $cardNumber);
        echo json_encode(['data' => ['canBeSaved' => $canBeTokenized]]);

        break;

    case 'get-card-brand':
        if (lkn_cielo_credit_card_must_block_attempt($request['invoice']['id']) ?? null) {
            exit;
        }

        $cardNumber = filter_var($request['cardNumber'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cardBrand = lkn_cielo_credit_brand_to_whmcs(lknc_cielo_get_card_brand('lknc_cielo_credit_card', $cardNumber));

        echo json_encode(['data' => ['brand' => $cardBrand]]);
}
