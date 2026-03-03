<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Controllers\Admin;

use Modules\Article\Mappers\Category as ArticleCategoryMapper;
use Modules\User\Mappers\Group as GroupMapper;

class Settings extends \Ilch\Controller\Admin
{
    public function init()
    {
        $this->getLayout()->addMenu('menuRssnews', [
            [
                'name' => 'dashboard',
                'active' => false,
                'icon' => 'fa-solid fa-table-list',
                'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'index']),
            ],
            [
                'name' => 'feeds',
                'active' => false,
                'icon' => 'fa-solid fa-rss',
                'url' => $this->getLayout()->getUrl(['controller' => 'feeds', 'action' => 'index']),
            ],
            [
                'name' => 'settings',
                'active' => true,
                'icon' => 'fa-solid fa-gears',
                'url' => $this->getLayout()->getUrl(['controller' => 'settings', 'action' => 'index']),
            ],
        ]);
    }

    public function indexAction()
    {
        $categoryMapper = new ArticleCategoryMapper();
        $groupMapper = new GroupMapper();

        if ($this->getRequest()->isPost()) {
            $readAccess = $this->getRequest()->getPost('read_access');
            if (!is_array($readAccess)) {
                $readAccess = [];
            }

            $readAccess = array_map('intval', $readAccess);
            $readAccess = array_values(array_unique(array_filter($readAccess)));
            if (empty($readAccess)) {
                $readAccess = [1, 2, 3];
            }

            $databaseConfig = new \Ilch\Config\Database(\Ilch\Registry::get('db'));
            $databaseConfig
                ->set('rssnews_defaultInterval', (string)max(60, (int)$this->getRequest()->getPost('default_interval')))
                ->set('rssnews_cronInterval', (string)max(60, (int)$this->getRequest()->getPost('cron_interval')))
                ->set('rssnews_postMode', (string)$this->getRequest()->getPost('post_mode'))
                ->set('rssnews_frontendLayout', (string)$this->getRequest()->getPost('frontend_layout'))
                ->set('rssnews_articleCatId', (string)max(1, (int)$this->getRequest()->getPost('article_cat_id')))
                ->set('rssnews_readAccess', implode(',', $readAccess));

            $token = trim((string)$this->getRequest()->getPost('cron_token'));
            if ($token !== '') {
                $databaseConfig->set('rssnews_cronToken', $token);
            }

            $this->redirect()->withMessage('saveSuccess')->to(['action' => 'index']);
            return;
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('moduleName'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('settings'), ['action' => 'index']);

        $this->getView()->set('defaultInterval', $this->getConfig()->get('rssnews_defaultInterval'));
        $this->getView()->set('cronInterval', $this->getConfig()->get('rssnews_cronInterval'));
        $this->getView()->set('postMode', $this->getConfig()->get('rssnews_postMode'));
        $this->getView()->set('frontendLayout', $this->getConfig()->get('rssnews_frontendLayout') ?: 'list');
        $this->getView()->set('articleCatId', $this->getConfig()->get('rssnews_articleCatId'));
        $this->getView()->set('articleCategories', $categoryMapper->getCategories() ?: []);
        $this->getView()->set('readAccess', $this->getConfig()->get('rssnews_readAccess'));
        $this->getView()->set('userGroupList', $groupMapper->getGroupList() ?: []);
        $this->getView()->set('cronToken', $this->getConfig()->get('rssnews_cronToken'));
    }
}
