<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services;

use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\BuildClassMapFormRequest;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\CieloAmountFormatter;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\View;

final class BuildClassMapFormService
{
    public function run(BuildClassMapFormRequest $request): string
    {
        return View::render(
            'form.form',
            [
                'data' => $request
            ]
        );
    }
}
