<?php

/**
 * @link      https://github.com/LinkNacional/whmcs-cielo-credit-card
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @author    Bruno Ferreira <ferreira.bruno@linknacional.com>
 * @since     2.0.0
 */

require_once __DIR__ . '/../../../init.php';

$request = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (empty($request) || !isset($request['action'])) {
    exit('No $resquest or $action');
}

require_once __DIR__ . '/helpers/gateway_functions.php';
require_once __DIR__ . '/helpers/card_functions.php';
require_once __DIR__ . '/../callback/lknc_cielo_credit_card.php';

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
