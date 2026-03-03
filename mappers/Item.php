<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Mappers;

use Modules\Rssnews\Models\Item as ItemModel;

class Item extends \Ilch\Mapper
{
    public function getByDedupeHash(string $dedupeHash): ?array
    {
        $row = $this->db()->select('*')
            ->from('rssnews_items')
            ->where(['dedupe_hash' => $dedupeHash])
            ->execute()
            ->fetchAssoc();

        if (empty($row)) {
            return null;
        }

        return $row;
    }

    public function existsByDedupeHash(string $dedupeHash): bool
    {
        return (bool)$this->db()->select('id')->from('rssnews_items')->where(['dedupe_hash' => $dedupeHash])->execute()->fetchCell();
    }

    public function updateMirrorState(int $itemId, string $mirrorMode, int $mirrorArticleId): void
    {
        $this->db()->update('rssnews_items')
            ->values([
                'mirror_mode' => $mirrorMode,
                'mirror_article_id' => $mirrorArticleId,
            ])
            ->where(['id' => $itemId])
            ->execute();
    }

    public function updateFromImport(int $itemId, array $data): void
    {
        $values = [
            'title' => (string)($data['title'] ?? ''),
            'teaser' => (string)($data['teaser'] ?? ''),
            'content' => (string)($data['content'] ?? ''),
            'author' => (string)($data['author'] ?? ''),
            'published_at' => (string)($data['published_at'] ?? ''),
            'category' => (string)($data['category'] ?? ''),
            'tags' => (string)($data['tags'] ?? ''),
        ];

        $this->db()->update('rssnews_items')
            ->values($values)
            ->where(['id' => $itemId])
            ->execute();
    }

    public function save(ItemModel $item): int
    {
        return (int)$this->db()->insert('rssnews_items')->values([
            'feed_id' => (int)$item->getFeedId(),
            'dedupe_hash' => (string)$item->getDedupeHash(),
            'source_guid' => (string)$item->getSourceGuid(),
            'source_link' => (string)$item->getSourceLink(),
            'source_title' => (string)$item->getSourceTitle(),
            'title' => (string)$item->getTitle(),
            'teaser' => (string)$item->getTeaser(),
            'content' => (string)$item->getContent(),
            'author' => (string)$item->getAuthor(),
            'published_at' => (string)$item->getPublishedAt(),
            'category' => (string)$item->getCategory(),
            'tags' => (string)$item->getTags(),
            'mirror_mode' => (string)$item->getMirrorMode(),
            'mirror_article_id' => (int)$item->getMirrorArticleId(),
            'created_at' => (string)$item->getCreatedAt(),
        ])->execute();
    }

    public function getLatestItems(int $limit = 25): array
    {
        $rows = $this->db()->queryArray(
            'SELECT i.*, f.title AS feed_name
            FROM `[prefix]_rssnews_items` i
            LEFT JOIN `[prefix]_rssnews_feeds` f ON i.feed_id = f.id
            ORDER BY i.published_at DESC, i.id DESC
            LIMIT ' . (int)$limit
        );

        return $rows ?: [];
    }

    public function getLatestLogs(int $limit = 20): array
    {
        $rows = $this->db()->queryArray(
            'SELECT l.*, f.title AS feed_name
            FROM `[prefix]_rssnews_logs` l
            LEFT JOIN `[prefix]_rssnews_feeds` f ON l.feed_id = f.id
            ORDER BY l.id DESC
            LIMIT ' . (int)$limit
        );

        return $rows ?: [];
    }

    public function addLog(int $feedId, string $status, string $message, int $fetchedCount, int $importedCount, int $skippedCount): void
    {
        $this->db()->insert('rssnews_logs')->values([
            'feed_id' => $feedId,
            'status' => $status,
            'message' => $message,
            'fetched_count' => $fetchedCount,
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
            'created_at' => (new \Ilch\Date())->format('Y-m-d H:i:s'),
        ])->execute();
    }
}
