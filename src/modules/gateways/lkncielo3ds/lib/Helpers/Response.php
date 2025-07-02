<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Helpers;

abstract class Response
{
    final public static function raw(array $response): void
    {
        header('Content-Type: application/json');

        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    final public static function api(bool $success, array $data = []): array
    {
        $response = ['success' => $success];

        if (count($data) > 0) {
            $response['data'] = $data;
        }

        return $response;
    }

    final public static function json(bool $success, array $data = [])
    {
        header('Content-Type: application/json');

        $response = self::api($success, $data);

        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    }

    final public static function file(string $data, string $filename): void
    {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo $data;
    }
}
