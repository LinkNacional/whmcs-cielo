<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Helpers;

use Exception;
use Smarty;
use WHMCS\Config\Setting;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config as ConfigDate;

final class View
{
    private const RESOURCES_PATH = __DIR__ . '/../resources';

    public static function render(string $view, array $vars = []): string
    {
        $viewPath = str_replace('.', '/', $view);
        $viewPath = Config::constant('resources_path') . "/$viewPath.tpl";

        if (!file_exists($viewPath)) {
            throw new Exception('Smarty template not found.');
        }

        $smarty = new Smarty();
        $smarty = self::assignVars($smarty, $vars);
        return $smarty->fetch($viewPath);
    }

    private static function assignVars(Smarty $smartyInstance, array $vars): Smarty
    {
        $systemURL = Setting::getValue('SystemURL');
        $smartyInstance->assign('systemURL', $systemURL);
        $habilitar_parcelas = ConfigDate::setting('enable_credit_card_installments');
        $smartyInstance->assign('habilitar_parcelas', $habilitar_parcelas);

        foreach ($vars as $key => $value) {
            $smartyInstance->assign($key, $value);
        }

        return $smartyInstance;
    }
}
