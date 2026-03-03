<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Mappers;

use Modules\Rssnews\Models\Feed as FeedModel;

class Feed extends \Ilch\Mapper
{
    public function getFeeds(bool $activeOnly = false): array
    {
        $select = $this->db()->select('*')->from('rssnews_feeds')->order(['title' => 'ASC']);

        if ($activeOnly) {
            $select->where(['is_active' => 1]);
        }

        $rows = $select->execute()->fetchRows();
        if (empty($rows)) {
            return [];
        }

        $feeds = [];
        foreach ($rows as $row) {
            $feeds[] = $this->mapRow($row);
        }

        return $feeds;
    }

    public function getFeedById(int $id): ?FeedModel
    {
        $row = $this->db()->select('*')->from('rssnews_feeds')->where(['id' => $id])->execute()->fetchAssoc();

        if (empty($row)) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function save(FeedModel $feed): int
    {
        $now = (new \Ilch\Date())->format('Y-m-d H:i:s');
        $values = [
            'title' => (string)$feed->getTitle(),
            'feed_url' => (string)$feed->getFeedUrl(),
            'category' => (string)$feed->getCategory(),
            'tags' => (string)$feed->getTags(),
            'update_interval' => max(60, (int)$feed->getUpdateInterval()),
            'max_items' => max(1, (int)$feed->getMaxItems()),
            'post_mode' => (string)$feed->getPostMode(),
            'article_cat_id' => max(1, (int)$feed->getArticleCatId()),
            'read_access' => (string)$feed->getReadAccess(),
            'is_active' => (int)$feed->getIsActive(),
            'updated_at' => $now,
        ];

        if ($feed->getId()) {
            $this->db()->update('rssnews_feeds')->values($values)->where(['id' => $feed->getId()])->execute();
            return (int)$feed->getId();
        }

        $values['created_at'] = $now;

        return (int)$this->db()->insert('rssnews_feeds')->values($values)->execute();
    }

    public function updateFetchState(int $feedId, ?string $lastFetchAt, ?string $lastSuccessAt, ?string $lastError): void
    {
        $this->db()->update('rssnews_feeds')
            ->values([
                'last_fetch_at' => $lastFetchAt,
                'last_success_at' => $lastSuccessAt,
                'last_error' => $lastError,
                'updated_at' => (new \Ilch\Date())->format('Y-m-d H:i:s'),
            ])
            ->where(['id' => $feedId])
            ->execute();
    }

    public function delete(int $id): void
    {
        $this->db()->delete('rssnews_feeds')->where(['id' => $id])->execute();
    }

    private function mapRow(array $row): FeedModel
    {
        $model = new FeedModel();
        $model->setId($row['id']);
        $model->setTitle($row['title']);
        $model->setFeedUrl($row['feed_url']);
        $model->setCategory($row['category']);
        $model->setTags($row['tags']);
        $model->setUpdateInterval($row['update_interval']);
        $model->setMaxItems($row['max_items']);
        $model->setPostMode($row['post_mode']);
        $model->setArticleCatId($row['article_cat_id']);
        $model->setReadAccess($row['read_access']);
        $model->setIsActive($row['is_active']);
        $model->setLastFetchAt($row['last_fetch_at']);
        $model->setLastSuccessAt($row['last_success_at']);
        $model->setLastError($row['last_error']);
        $model->setCreatedAt($row['created_at']);
        $model->setUpdatedAt($row['updated_at']);

        return $model;
    }
}
