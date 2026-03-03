<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Controllers\Admin;

use Modules\Article\Mappers\Category as ArticleCategoryMapper;
use Modules\Rssnews\Libraries\Aggregator;
use Modules\Rssnews\Mappers\Feed as FeedMapper;
use Modules\Rssnews\Models\Feed as FeedModel;
use Modules\User\Mappers\Group as GroupMapper;

class Feeds extends \Ilch\Controller\Admin
{
    public function init()
    {
        $isTreat = $this->getRequest()->getActionName() === 'treat';

        $this->getLayout()->addMenu('menuRssnews', [
            [
                'name' => 'dashboard',
                'active' => false,
                'icon' => 'fa-solid fa-table-list',
                'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'index']),
            ],
            [
                'name' => 'feeds',
                'active' => !$isTreat,
                'icon' => 'fa-solid fa-rss',
                'url' => $this->getLayout()->getUrl(['controller' => 'feeds', 'action' => 'index']),
                [
                    'name' => 'add',
                    'active' => $isTreat,
                    'icon' => 'fa-solid fa-circle-plus',
                    'url' => $this->getLayout()->getUrl(['controller' => 'feeds', 'action' => 'treat']),
                ],
            ],
            [
                'name' => 'settings',
                'active' => false,
                'icon' => 'fa-solid fa-gears',
                'url' => $this->getLayout()->getUrl(['controller' => 'settings', 'action' => 'index']),
            ],
        ]);
    }

    public function indexAction()
    {
        $feedMapper = new FeedMapper();

        if ($this->getRequest()->getParam('delete')) {
            $feedMapper->delete((int)$this->getRequest()->getParam('delete'));
            $this->redirect()->withMessage('deleteSuccess')->to(['action' => 'index']);
            return;
        }

        if ($this->getRequest()->getParam('fetch')) {
            $feed = $feedMapper->getFeedById((int)$this->getRequest()->getParam('fetch'));
            if ($feed) {
                (new Aggregator($this->getLayout()))->fetchFeed($feed);
            }
            $this->redirect()->withMessage('saveSuccess')->to(['action' => 'index']);
            return;
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('moduleName'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('feeds'), ['action' => 'index']);

        $this->getView()->set('feeds', $feedMapper->getFeeds());
    }

    public function treatAction()
    {
        $feedMapper = new FeedMapper();
        $categoryMapper = new ArticleCategoryMapper();
        $groupMapper = new GroupMapper();
        $id = (int)$this->getRequest()->getParam('id');
        $feed = $id > 0 ? $feedMapper->getFeedById($id) : null;

        if ($this->getRequest()->isPost()) {
            $model = new FeedModel();
            if ($id > 0) {
                $model->setId($id);
            }

            $readAccess = $this->getRequest()->getPost('read_access');
            if (!is_array($readAccess)) {
                $readAccess = [];
            }

            $readAccess = array_map('intval', $readAccess);
            $readAccess = array_values(array_unique(array_filter($readAccess)));
            if (empty($readAccess)) {
                $readAccess = [1, 2, 3];
            }

            $model->setTitle(trim((string)$this->getRequest()->getPost('title')));
            $model->setFeedUrl(trim((string)$this->getRequest()->getPost('feed_url')));
            $model->setCategory(trim((string)$this->getRequest()->getPost('category')));
            $model->setTags(trim((string)$this->getRequest()->getPost('tags')));
            $model->setUpdateInterval((int)$this->getRequest()->getPost('update_interval'));
            $model->setMaxItems((int)$this->getRequest()->getPost('max_items'));
            $model->setPostMode((string)$this->getRequest()->getPost('post_mode'));
            $model->setArticleCatId((int)$this->getRequest()->getPost('article_cat_id'));
            $model->setReadAccess(implode(',', $readAccess));
            $model->setIsActive($this->getRequest()->getPost('is_active') ? 1 : 0);

            if ($model->getTitle() === '' || $model->getFeedUrl() === '') {
                $this->addMessage('missingFields', 'danger');
            } else {
                $feedMapper->save($model);
                $this->redirect()->withMessage('saveSuccess')->to(['action' => 'index']);
                return;
            }
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('moduleName'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('feeds'), ['action' => 'index'])
            ->add($this->getTranslator()->trans($id > 0 ? 'edit' : 'add'), ['action' => 'treat']);

        $this->getView()->set('feed', $feed);
        $this->getView()->set('defaultInterval', $this->getConfig()->get('rssnews_defaultInterval'));
        $this->getView()->set('defaultArticleCatId', $this->getConfig()->get('rssnews_articleCatId'));
        $this->getView()->set('articleCategories', $categoryMapper->getCategories() ?: []);
        $this->getView()->set('defaultReadAccess', $this->getConfig()->get('rssnews_readAccess'));
        $this->getView()->set('userGroupList', $groupMapper->getGroupList() ?: []);
    }
}
