<?php

$request = json_decode(file_get_contents('php://input'), true) ?? $_POST;
!empty($request) or die('Action cannot be empty.');

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php'; // Necessário para a função addInvoicePayment()
require_once __DIR__ . '/func/gateway_functions.php';

try {
    if (isset($request['invoiceId'])) {
        $invoiceId = $request['invoiceId'];

        // 1. Busca todas as transações da fatura
        $invoiceTransactions = localAPI('GetTransactions', ['invoiceid' => $invoiceId]);
        $transactions = $invoiceTransactions['transactions']['transaction'] ?? [];

        // Correção de anomalia da API do WHMCS (quando há só 1 transação, ela não vem em array)
        if (isset($transactions['id'])) {
            $transactions = [$transactions];
        }

        $paidCieloIds = [];
        $generatedCieloIds = [];

        // 2. Mapeia todos os PaymentIds desta fatura usando o explode (seguro)
        foreach ($transactions as $txn) {
            if ($txn['gateway'] === lkn_cielo_qr_code_gateway_name()) {
                $transid = $txn['transid'];
                
                // Corta a string no primeiro ponto '.'
                $partes = explode('.', $transid, 2);
                
                if (count($partes) === 2) {
                    $prefixo = $partes[0];
                    $idCieloReal = $partes[1];
                    
                    if ($prefixo === 'PAGO') {
                        $paidCieloIds[] = $idCieloReal;
                    } else {
                        $generatedCieloIds[] = $idCieloReal;
                    }
                }
            }
        }

        // 3. Descobre quais QR Codes foram gerados, mas ainda NÃO constam como pagos
        $pendingIds = array_diff($generatedCieloIds, $paidCieloIds);
        $paymentWasAdded = false;

        // 4. Consulta a Cielo APENAS para os IDs pendentes
        foreach ($pendingIds as $pendingId) {
            $isTransactionPaid = lkn_cielo_qr_code_is_qr_code_paid($pendingId);

            if ($isTransactionPaid['isPaid']) {
                $cieloPaidAmount = $isTransactionPaid['cieloResponse']['Payment']['Amount'];
                $paidAmount = lkn_cielo_qr_code_format_amount_from_cielo_to_whmcs($cieloPaidAmount);
                $fees = lkn_cielo_qr_code_get_fees($paidAmount, $invoiceId);

                // Usa a função nativa do WHMCS para dar baixa. 
                // Se a fatura já estiver paga por outra transação, isso vira CRÉDITO na conta do cliente!
                addInvoicePayment(
                    $invoiceId,
                    'PAGO.' . $pendingId,
                    $paidAmount,
                    $fees,
                    lkn_cielo_qr_code_gateway_name()
                );

                lkn_cielo_qr_code_log_transac('QR Code pago para a fatura #' . $invoiceId, $isTransactionPaid['cieloResponse']);
                $paymentWasAdded = true;
            }
        }

        // 5. Verifica o status final da fatura para avisar o Javascript
        $invoiceData = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);

        if ($invoiceData['status'] === 'Paid' || $paymentWasAdded) {
            lkn_cielo_qr_code_api_response(['paid' => true]);
        } else {
            lkn_cielo_qr_code_api_response(['paid' => false]);
        }
    } else {
        lkn_cielo_qr_code_api_response(['paid' => false]);
    }
} catch (Exception $e) {
    lkn_cielo_qr_code_log_transac('Erro ao consultar pagamento para fatura', $e->getMessage());
    lkn_cielo_qr_code_api_response(['paid' => false, 'error' => $e->getMessage()]);
}
