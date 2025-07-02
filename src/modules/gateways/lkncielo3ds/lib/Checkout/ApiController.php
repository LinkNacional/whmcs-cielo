<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout;

use Throwable;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services\CardBinService;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Formatter;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Response;

final class ApiController
{
    public function requestBin(array $request): array
    {
        $cardNumber = Formatter::removeNonNumber(strip_tags($request['cardNumber']));

        $serviceResponse = (new CardBinService())->run($cardNumber);

        if (isset($serviceResponse['type'])) {
            $response = [
                'success' => true,
                'data' => [
                    'type' => $serviceResponse['type']
                ]
            ];
        } else {
            $response['success'] = false;
            $response['serviceResponse'] = $serviceResponse;
        }

        return $response;
    }

    public function uploadJsonTaxes()
    {
        try {
            $tempFile = $_FILES['lkncielo3ds-taxes-json'];
            $tempFilePath = $tempFile['tmp_name'];

            $jsonContent = file_get_contents($tempFilePath);
            $isValid = json_decode($jsonContent, true) && $tempFile['size'] > 0;

            if (!$isValid) {
                Response::json(false, ['error' => 'JSON inválido.']);

                return;
            }

            $customBrandTaxesJsonPath = __DIR__ . '/../resources/json/custom_brand_taxes.json';

            if (!move_uploaded_file($tempFilePath, $customBrandTaxesJsonPath)) {
                Response::json(false, ['error' => 'Não foi possível savar o arquivo de taxas.']);

                return;
            }

            Response::json(true, ['msg' => 'Arquivo salvo! Agora, as taxas serão calculadas a partir dele.']);
        } catch (Throwable $th) {
            Response::json(false, ['error' => 'Erro: ' . $th->getMessage()]);
        }
    }

    public function downloadJsonTaxes()
    {
        $customBrandTaxesJsonPath = __DIR__ . '/../resources/json/custom_brand_taxes.json';
        $defaultBrandTaxesJsonPath = __DIR__ . '/../resources/json/default_brand_taxes.json';

        if (file_exists($customBrandTaxesJsonPath)) {
            Response::file(file_get_contents($customBrandTaxesJsonPath), 'custom_brand_taxes.json');
        } elseif (file_exists($defaultBrandTaxesJsonPath)) {
            Response::file(file_get_contents($defaultBrandTaxesJsonPath), 'default_brand_taxes.json');
        } else {
            Response::api(false, ['error' => 'no-taxes-file-found']);
        }
    }

    public function downloadDefaultJsonTaxes()
    {
        $defaultBrandTaxesJsonPath = __DIR__ . '/../resources/json/default_brand_taxes.json';

        if (file_exists($defaultBrandTaxesJsonPath)) {
            Response::file(file_get_contents($defaultBrandTaxesJsonPath), 'default_brand_taxes.json');
        } else {
            Response::api(false, ['error' => 'no-taxes-file-found']);
        }
    }
}
