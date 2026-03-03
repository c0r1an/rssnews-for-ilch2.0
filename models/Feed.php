<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Models;

class Feed extends \Ilch\Model
{
    protected $id;
    protected $title;
    protected $feedUrl;
    protected $category;
    protected $tags;
    protected $updateInterval;
    protected $maxItems;
    protected $postMode;
    protected $articleCatId;
    protected $readAccess;
    protected $isActive;
    protected $lastFetchAt;
    protected $lastSuccessAt;
    protected $lastError;
    protected $createdAt;
    protected $updatedAt;
}
