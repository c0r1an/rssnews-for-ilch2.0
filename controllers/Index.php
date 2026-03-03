<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Controllers;

use Modules\Rssnews\Mappers\Item as ItemMapper;

class Index extends \Ilch\Controller\Frontend
{
    public function indexAction()
    {
        $this->getLayout()->getHmenu()->add($this->getTranslator()->trans('moduleName'), ['action' => 'index']);
        $layoutMode = $this->getConfig()->get('rssnews_frontendLayout') ?: 'list';
        $this->getView()
            ->set('items', (new ItemMapper())->getLatestItems(30))
            ->set('layoutMode', $layoutMode);
    }
}
