<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Controllers;

use Modules\Rssnews\Libraries\Aggregator;

class Cron extends \Ilch\Controller\Frontend
{
    public function fetchAllAction()
    {
        $this->getLayout()->setDisabled(true);

        $token = (string)$this->getRequest()->getParam('token');
        $expected = (string)$this->getConfig()->get('rssnews_cronToken');

        if ($expected === '' || !hash_equals($expected, $token)) {
            $this->getView()->set('result', ['status' => 'error', 'message' => 'invalid token', 'summary' => null]);
            return;
        }

        $summary = (new Aggregator($this->getLayout()))->fetchAll((bool)$this->getRequest()->getParam('force'));
        $this->getView()->set('result', ['status' => 'ok', 'message' => 'fetchAll executed', 'summary' => $summary]);
    }
}
