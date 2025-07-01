<?php

$request = json_decode(file_get_contents('php://input'), true) ?? $_POST;
!empty($request) or die('Action cannot be empty.');

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/func/gateway_functions.php';

try {
    if (isset($request['invoiceId'])) {
        $invoiceId = $request['invoiceId'];

        $invoiceTransactions = localAPI(
            'GetTransactions',
            ['invoiceid' => $invoiceId]
        )['transactions']['transaction'];

        $qrCodeAddedTransaction = end($invoiceTransactions);

        if ($qrCodeAddedTransaction['gateway'] === lkn_cielo_qr_code_gateway_name()) {
            $paymentId = substr($qrCodeAddedTransaction['transid'], 7);
            $isTransactionPaid = lkn_cielo_qr_code_is_qr_code_paid($paymentId);

            if ($isTransactionPaid['isPaid']) {
                $cieloPaidAmount = $isTransactionPaid['cieloResponse']['Payment']['Amount'];
                $paidAmount = lkn_cielo_qr_code_format_amount_from_cielo_to_whmcs($cieloPaidAmount);

                $userId = $qrCodeAddedTransaction['userid'];
                $fees = lkn_cielo_qr_code_get_fees($paidAmount, $invoiceId);

                $apiResponse = localAPI('AddTransaction', [
                    'userid' => $userId,
                    'invoiceid' => $invoiceId,
                    'transid' => 'PAGO.' . $paymentId,
                    'amountin' => $paidAmount,
                    'fees' => $fees,
                    'date' => date('d/m/Y'),
                    'paymentmethod' => lkn_cielo_qr_code_gateway_name(),
                    'description' => 'QR Code CIELO',
                ]);

                lkn_cielo_qr_code_log_transac('QR Code pago para a fatura #' . $invoiceId, $isTransactionPaid['cieloResponse']);

                lkn_cielo_qr_code_api_response([
                    'paid' => true
                ]);
            } else {
                lkn_cielo_qr_code_api_response([
                    'paid' => false
                ]);
            }
        }
    }
} catch (Exception $e) {
    lkn_cielo_qr_code_log_transac('Erro ao consultar pagamento para fatura', $e->getMessage());
}
