<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Plugins;

use Modules\Rssnews\Libraries\Aggregator;

class AfterDatabaseLoad
{
    public function __construct(array $pluginData)
    {
        $config = $pluginData['config'] ?? null;
        $db = $pluginData['db'] ?? null;
        $request = $pluginData['request'] ?? null;
        $layout = $pluginData['layout'] ?? null;

        if (!$config || !$request || !$db) {
            return;
        }

        if ($request->getModuleName() === 'rssnews' && $request->getControllerName() === 'cron') {
            return;
        }

        if (!$this->isModuleInstalled($db)) {
            return;
        }

        $interval = max(60, (int)$config->get('rssnews_cronInterval'));
        $lastCheck = (int)$config->get('rssnews_lastAutoFetchCheck');

        if (($lastCheck + $interval) > time()) {
            return;
        }

        (new Aggregator($layout))->fetchAll(false);
    }

    private function isModuleInstalled($db): bool
    {
        try {
            if (method_exists($db, 'tableExists') && !$db->tableExists('rssnews_feeds')) {
                return false;
            }

            $moduleInstalled = (bool)$db->select('key')
                ->from('modules')
                ->where(['key' => 'rssnews'])
                ->execute()
                ->fetchCell();

            if (!$moduleInstalled) {
                return false;
            }
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }
}
