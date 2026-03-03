<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Controllers\Admin;

use Modules\Rssnews\Libraries\Aggregator;
use Modules\Rssnews\Mappers\Item as ItemMapper;

class Index extends \Ilch\Controller\Admin
{
    public function init()
    {
        $this->getLayout()->addMenu('menuRssnews', [
            [
                'name' => 'dashboard',
                'active' => $this->getRequest()->getControllerName() === 'index',
                'icon' => 'fa-solid fa-table-list',
                'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'index']),
            ],
            [
                'name' => 'feeds',
                'active' => $this->getRequest()->getControllerName() === 'feeds',
                'icon' => 'fa-solid fa-rss',
                'url' => $this->getLayout()->getUrl(['controller' => 'feeds', 'action' => 'index']),
            ],
            [
                'name' => 'settings',
                'active' => $this->getRequest()->getControllerName() === 'settings',
                'icon' => 'fa-solid fa-gears',
                'url' => $this->getLayout()->getUrl(['controller' => 'settings', 'action' => 'index']),
            ],
        ]);
    }

    public function indexAction()
    {
        $summary = null;
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('fetchAll')) {
            $summary = (new Aggregator($this->getLayout()))->fetchAll(true);
            $this->addMessage('saveSuccess');
        }

        $itemMapper = new ItemMapper();
        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('moduleName'), ['action' => 'index'])
            ->add($this->getTranslator()->trans('dashboard'), ['action' => 'index']);

        $this->getView()->set('summary', $summary);
        $this->getView()->set('items', $itemMapper->getLatestItems(25));
        $this->getView()->set('logs', $itemMapper->getLatestLogs(20));
    }
}
