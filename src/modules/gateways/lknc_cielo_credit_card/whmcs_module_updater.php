<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';

defined('WHMCS') or die();

class WhmcsModuleUpdater
{
    /**
     * @var string
     */
    private $temporaryPath;

    /**
     * @var string
     */
    private $installationPath;

    /**
     * @var string
     */
    private $moduleName;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $moduleUpdateFilename;

    /**
     * @param string $temporaryPath
     * @param string $installationPath
     * @param string $moduleName
     * @param string $localVersion
     */
    public function __construct(
        $temporaryPath,
        $installationPath,
        $moduleName,
        $zipFilename
    ) {
        $this->temporaryPath = $temporaryPath;
        $this->installationPath = $installationPath;
        $this->moduleName = $moduleName;
        $this->moduleUpdateFilename = $zipFilename . '.zip';
    }

    public function getLatestVersion()
    {
        return '2.10.0';
    }
}
