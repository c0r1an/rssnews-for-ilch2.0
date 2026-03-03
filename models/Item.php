<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Models;

class Item extends \Ilch\Model
{
    protected $id;
    protected $feedId;
    protected $dedupeHash;
    protected $sourceGuid;
    protected $sourceLink;
    protected $sourceTitle;
    protected $title;
    protected $teaser;
    protected $content;
    protected $author;
    protected $publishedAt;
    protected $category;
    protected $tags;
    protected $mirrorMode;
    protected $mirrorArticleId;
    protected $createdAt;
}
