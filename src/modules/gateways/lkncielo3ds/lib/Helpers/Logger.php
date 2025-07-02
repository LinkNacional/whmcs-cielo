<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Helpers;

use stdClass;

final class Logger
{
    public static function log(string $result, array|object|null $request, array|stdClass|null $response = []): void
    {
        logTransaction(
            Config::constant('name'),
            json_encode(
                ['request' => $request, 'response' => $response],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            $result
        );
    }
}
