<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Libraries;

use Modules\Article\Mappers\Article as ArticleMapper;
use Modules\Article\Models\Article as ArticleModel;
use Modules\Rssnews\Mappers\Feed as FeedMapper;
use Modules\Rssnews\Mappers\Item as ItemMapper;
use Modules\Rssnews\Models\Feed as FeedModel;
use Modules\Rssnews\Models\Item as ItemModel;

class Aggregator
{
    /** @var \Ilch\Layout\Base|\Ilch\Layout\Admin|\Ilch\Layout\Frontend|null */
    private $layout;
    private $config;

    public function __construct($layout = null)
    {
        $this->layout = $layout;
        $this->config = \Ilch\Registry::get('config');
    }

    public function fetchAll(bool $force = false, ?int $onlyFeedId = null): array
    {
        $feedMapper = new FeedMapper();
        $itemMapper = new ItemMapper();
        $feeds = $onlyFeedId ? array_filter([$feedMapper->getFeedById($onlyFeedId)]) : $feedMapper->getFeeds(true);
        $summary = ['checked' => 0, 'fetched' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => 0, 'messages' => []];

        foreach ($feeds as $feed) {
            $summary['checked']++;

            if (!$force && !$this->isFeedDue($feed)) {
                $summary['messages'][] = $feed->getTitle() . ': skipped (interval not reached)';
                continue;
            }

            $result = $this->fetchFeed($feed);
            $summary['fetched'] += $result['fetched'];
            $summary['imported'] += $result['imported'];
            $summary['skipped'] += $result['skipped'];
            $summary['errors'] += $result['status'] === 'error' ? 1 : 0;
            $summary['messages'][] = $feed->getTitle() . ': ' . $result['message'];

            $itemMapper->addLog((int)$feed->getId(), $result['status'], $result['message'], $result['fetched'], $result['imported'], $result['skipped']);
        }

        if ($this->config) {
            (new \Ilch\Config\Database(\Ilch\Registry::get('db')))->set('rssnews_lastAutoFetchCheck', (string)time());
        }

        return $summary;
    }

    public function fetchFeed(FeedModel $feed): array
    {
        $feedMapper = new FeedMapper();
        $itemMapper = new ItemMapper();
        $now = (new \Ilch\Date())->format('Y-m-d H:i:s');
        $result = ['status' => 'success', 'message' => 'ok', 'fetched' => 0, 'imported' => 0, 'skipped' => 0];

        try {
            $rawXml = $this->loadUrl($feed->getFeedUrl());
            if ($rawXml === '') {
                throw new \RuntimeException('empty feed response');
            }

            $entries = $this->parseFeed($rawXml, $feed);
            $result['fetched'] = count($entries);

            foreach ($entries as $entry) {
                $dedupeHash = $this->buildDedupeHash($entry);
                $mirrorMode = $this->resolvePostMode($feed);
                $existingItem = $itemMapper->getByDedupeHash($dedupeHash);

                if ($existingItem) {
                    $itemMapper->updateFromImport((int)$existingItem['id'], $entry);

                    if (($mirrorMode === 'article' || $mirrorMode === 'both') && (int)$existingItem['mirror_article_id'] === 0) {
                        $mirrorArticleId = $this->mirrorToArticle($entry, $feed);
                        if ($mirrorArticleId > 0) {
                            $itemMapper->updateMirrorState((int)$existingItem['id'], $mirrorMode, $mirrorArticleId);
                            $result['imported']++;
                            continue;
                        }
                    }

                    if (($mirrorMode === 'article' || $mirrorMode === 'both') && (int)$existingItem['mirror_article_id'] > 0) {
                        $updatedArticleId = $this->mirrorToArticle($entry, $feed, (int)$existingItem['mirror_article_id']);
                        if ($updatedArticleId > 0) {
                            $itemMapper->updateMirrorState((int)$existingItem['id'], $mirrorMode, $updatedArticleId);
                            $result['imported']++;
                            continue;
                        }
                    }

                    $result['skipped']++;
                    continue;
                }

                $mirrorArticleId = 0;
                if ($mirrorMode === 'article' || $mirrorMode === 'both') {
                    $mirrorArticleId = $this->mirrorToArticle($entry, $feed);
                }

                $item = new ItemModel();
                $item->setFeedId($feed->getId());
                $item->setDedupeHash($dedupeHash);
                $item->setSourceGuid($entry['guid']);
                $item->setSourceLink($entry['link']);
                $item->setSourceTitle($feed->getTitle());
                $item->setTitle($entry['title']);
                $item->setTeaser($entry['teaser']);
                $item->setContent($entry['content']);
                $item->setAuthor($entry['author']);
                $item->setPublishedAt($entry['published_at']);
                $item->setCategory($entry['category']);
                $item->setTags($entry['tags']);
                $item->setMirrorMode($mirrorMode);
                $item->setMirrorArticleId($mirrorArticleId);
                $item->setCreatedAt($now);
                $itemMapper->save($item);

                $result['imported']++;
            }

            $feedMapper->updateFetchState((int)$feed->getId(), $now, $now, null);
            $result['message'] = sprintf('fetched %d, imported %d, skipped %d', $result['fetched'], $result['imported'], $result['skipped']);
        } catch (\Throwable $exception) {
            $feedMapper->updateFetchState((int)$feed->getId(), $now, $feed->getLastSuccessAt(), $exception->getMessage());
            $result['status'] = 'error';
            $result['message'] = $exception->getMessage();
        }

        return $result;
    }

    private function isFeedDue(FeedModel $feed): bool
    {
        if (!$feed->getLastFetchAt()) {
            return true;
        }

        $lastTimestamp = strtotime($feed->getLastFetchAt());
        if (!$lastTimestamp) {
            return true;
        }

        return ($lastTimestamp + max(60, (int)$feed->getUpdateInterval())) <= time();
    }

    private function loadUrl(string $url): string
    {
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO => buildPath(ROOT_PATH, 'certificate', 'cacert.pem'),
                CURLOPT_USERAGENT => 'Ilch RSSNews/1.0',
            ]);
            $content = (string)curl_exec($handle);
            $httpCode = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $error = curl_error($handle);
            curl_close($handle);

            if ($content === '' || ($httpCode >= 400 && $httpCode !== 0)) {
                throw new \RuntimeException($error ?: 'HTTP ' . $httpCode);
            }

            return $content;
        }

        $context = stream_context_create([
            'http' => ['timeout' => 25, 'user_agent' => 'Ilch RSSNews/1.0'],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new \RuntimeException('unable to fetch feed');
        }

        return (string)$content;
    }

    private function parseFeed(string $rawXml, FeedModel $feed): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        if (!@$dom->loadXML($rawXml, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOCDATA)) {
            throw new \RuntimeException('invalid XML feed');
        }

        $root = $dom->documentElement;
        if (!$root) {
            throw new \RuntimeException('feed has no root element');
        }

        $itemNodes = [];
        if (strtolower($root->localName) === 'rss') {
            $channels = $root->getElementsByTagName('channel');
            if ($channels->length > 0) {
                foreach ($channels->item(0)->childNodes as $childNode) {
                    if ($childNode instanceof \DOMElement && strtolower($childNode->localName) === 'item') {
                        $itemNodes[] = $childNode;
                    }
                }
            }
        } elseif (strtolower($root->localName) === 'feed') {
            foreach ($root->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement && strtolower($childNode->localName) === 'entry') {
                    $itemNodes[] = $childNode;
                }
            }
        } else {
            throw new \RuntimeException('unsupported feed format');
        }

        $items = [];
        $maxItems = max(1, (int)$feed->getMaxItems());
        $count = 0;
        foreach ($itemNodes as $node) {
            if ($count >= $maxItems) {
                break;
            }

            $title = $this->firstChildText($node, ['title']) ?: 'Untitled';
            $guid = $this->firstChildText($node, ['guid', 'id']);
            $link = $this->extractLink($node);
            $content = $this->firstChildText($node, ['encoded', 'content', 'description', 'summary']) ?: '';
            $author = $this->extractAuthor($node);
            $publishedAt = $this->normalizeDate($this->firstChildText($node, ['pubDate', 'published', 'updated', 'date']) ?: null);
            $categories = $this->extractCategories($node);
            $videoUrl = $this->extractPrimaryVideo($node, $content);
            $imageUrl = $this->extractPrimaryImage($node, $content);
            $imageUrl = $this->resolveHighQualityImage($imageUrl, $link);
            $sanitizedContent = $this->sanitizeHtml($content);
            $sanitizedContent = $this->injectPrimaryVideoIntoContent($sanitizedContent, $videoUrl);
            $sanitizedContent = $this->injectPrimaryImageIntoContent($sanitizedContent, $imageUrl);
            $plainTeaser = trim(strip_tags(html_entity_decode($sanitizedContent, ENT_QUOTES, 'UTF-8')));
            $teaser = mb_substr($plainTeaser, 0, 252) . (mb_strlen($plainTeaser) > 252 ? '...' : '');

            $items[] = [
                'guid' => trim((string)$guid),
                'link' => trim((string)$link),
                'title' => trim((string)$title),
                'teaser' => $teaser,
                'content' => $sanitizedContent,
                'author' => trim((string)$author),
                'published_at' => $publishedAt,
                'category' => $feed->getCategory() ?: ($categories[0] ?? ''),
                'tags' => $this->mergeTags($feed->getTags(), implode(',', $categories)),
                'image_url' => $imageUrl,
            ];

            $count++;
        }

        return $items;
    }

    private function firstChildText(\DOMElement $node, array $names): string
    {
        $map = array_map('strtolower', $names);
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement && in_array(strtolower($childNode->localName), $map, true)) {
                return trim($childNode->textContent);
            }
        }

        return '';
    }

    private function extractLink(\DOMElement $node): string
    {
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement || strtolower($childNode->localName) !== 'link') {
                continue;
            }

            return $childNode->hasAttribute('href') ? trim($childNode->getAttribute('href')) : trim($childNode->textContent);
        }

        return '';
    }

    private function extractAuthor(\DOMElement $node): string
    {
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement || strtolower($childNode->localName) !== 'author') {
                continue;
            }

            foreach ($childNode->childNodes as $grandChild) {
                if ($grandChild instanceof \DOMElement && strtolower($grandChild->localName) === 'name') {
                    return trim($grandChild->textContent);
                }
            }

            return trim($childNode->textContent);
        }

        return '';
    }

    private function extractCategories(\DOMElement $node): array
    {
        $categories = [];
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement || strtolower($childNode->localName) !== 'category') {
                continue;
            }

            $value = $childNode->hasAttribute('term') ? $childNode->getAttribute('term') : $childNode->textContent;
            $value = trim($value);
            if ($value !== '') {
                $categories[] = $value;
            }
        }

        return array_values(array_unique($categories));
    }

    private function normalizeDate(?string $value): string
    {
        $timestamp = $value ? strtotime($value) : false;
        if ($timestamp === false) {
            return (new \Ilch\Date())->format('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function buildDedupeHash(array $entry): string
    {
        if (!empty($entry['guid'])) {
            return sha1('guid:' . mb_strtolower(trim($entry['guid'])));
        }

        if (!empty($entry['link'])) {
            return sha1('link:' . mb_strtolower(trim($entry['link'])));
        }

        return sha1('titledate:' . mb_strtolower(trim($entry['title'])) . '|' . substr((string)$entry['published_at'], 0, 19));
    }

    private function sanitizeHtml(string $content): string
    {
        if ($content === '') {
            return '';
        }

        if ($this->layout && method_exists($this->layout, 'alwaysPurify')) {
            try {
                return $this->layout->alwaysPurify($content);
            } catch (\Throwable $exception) {
                // The AfterDatabaseLoad plugin runs before Ilch initializes HTMLPurifier.
                // Fall back to a conservative HTML whitelist in that early phase.
            }
        }

        return strip_tags($content, '<p><br><strong><em><ul><ol><li><a><blockquote><h2><h3><h4><img><figure><figcaption><iframe><video><source>');
    }

    private function mergeTags(string $left, string $right): string
    {
        $tags = array_filter(array_map('trim', explode(',', $left . ',' . $right)));
        return implode(',', array_values(array_unique($tags)));
    }

    private function resolvePostMode(FeedModel $feed): string
    {
        $mode = $feed->getPostMode();
        if (!$mode || $mode === 'default') {
            $mode = $this->config ? $this->config->get('rssnews_postMode') : 'aggregator';
        }

        return in_array($mode, ['aggregator', 'article', 'both'], true) ? $mode : 'aggregator';
    }

    private function mirrorToArticle(array $entry, FeedModel $feed, int $existingArticleId = 0): int
    {
        if (!class_exists(ArticleMapper::class) || !class_exists(ArticleModel::class)) {
            return 0;
        }

        $articleMapper = new ArticleMapper();
        $article = new ArticleModel();
        if ($existingArticleId > 0) {
            $article->setId($existingArticleId);
        }
        $catId = max(1, (int)($feed->getArticleCatId() ?: ($this->config ? $this->config->get('rssnews_articleCatId') : 1)));
        $readAccess = $feed->getReadAccess() ?: ($this->config ? $this->config->get('rssnews_readAccess') : '1,2,3');
        $perma = 'rssnews-' . date('Ymd-His', strtotime($entry['published_at'])) . '-' . substr(sha1($entry['link'] ?: $entry['title']), 0, 10) . '.html';
        $linkHtml = $entry['link'] ? '<p><a href="' . htmlspecialchars($entry['link'], ENT_QUOTES, 'UTF-8') . '" rel="noopener" target="_blank">Original</a></p>' : '';
        $articleImage = '';
        $articleImageSource = '';

        if (!empty($entry['image_url'])) {
            if ($this->isLocalArticleImagePath($entry['image_url'])) {
                $articleImage = ltrim($entry['image_url'], '/');
            } else {
                $articleImageSource = $entry['image_url'];
            }
        }

        $article
            ->setCatId((string)$catId)
            ->setAuthorId(1)
            ->setDescription($entry['teaser'])
            ->setKeywords($entry['tags'])
            ->setTitle($entry['title'])
            ->setDateCreated($entry['published_at'])
            ->setTeaser($entry['teaser'])
            ->setContent($entry['content'] . $linkHtml)
            ->setPerma($perma)
            ->setTopArticle(0)
            ->setCommentsDisabled(0)
            ->setReadAccess($readAccess)
            ->setLocale('')
            ->setImage($articleImage)
            ->setImageSource($articleImageSource);

        return (int)$articleMapper->save($article);
    }

    private function extractPrimaryImage(\DOMElement $node, string $content): string
    {
        $bestUrl = '';
        $bestScore = -1;

        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement) {
                continue;
            }

            $localName = strtolower($childNode->localName);

            if ($localName === 'enclosure') {
                $type = strtolower((string)$childNode->getAttribute('type'));
                $url = trim((string)$childNode->getAttribute('url'));
                if ($url !== '' && ($type === '' || strpos($type, 'image/') === 0)) {
                    $candidate = $this->upgradeImageUrl($url);
                    $score = $this->scoreImageCandidate(
                        $candidate,
                        (int)$childNode->getAttribute('width'),
                        (int)$childNode->getAttribute('height')
                    ) + 1000;

                    if ($score > $bestScore) {
                        $bestUrl = $candidate;
                        $bestScore = $score;
                    }
                }
            }

            if (in_array($localName, ['thumbnail', 'content'], true)) {
                foreach (['url', 'src', 'href'] as $attribute) {
                    $value = trim((string)$childNode->getAttribute($attribute));
                    if ($value !== '' && $this->looksLikeImageUrl($value)) {
                        $candidate = $this->upgradeImageUrl($value);
                        $score = $this->scoreImageCandidate(
                            $candidate,
                            (int)$childNode->getAttribute('width'),
                            (int)$childNode->getAttribute('height')
                        );

                        if ($score > $bestScore) {
                            $bestUrl = $candidate;
                            $bestScore = $score;
                        }
                    }
                }
            }
        }

        $htmlCandidates = $this->extractImageCandidatesFromHtml($content);
        foreach ($htmlCandidates as $candidate) {
            $score = $this->scoreImageCandidate($candidate);
            if ($score > $bestScore) {
                $bestUrl = $candidate;
                $bestScore = $score;
            }
        }

        return $bestUrl;
    }

    private function injectPrimaryImageIntoContent(string $content, string $imageUrl): string
    {
        if ($imageUrl === '') {
            return $content;
        }

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content)) {
            return $content;
        }

        $imageTag = '<figure class="rssnews-hero-image" style="float:left;width:50%;margin:0 1rem 1rem 0;">'
            . '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="" style="display:block;width:100%;height:auto;">'
            . '</figure>';

        return $imageTag . $content;
    }

    private function extractPrimaryVideo(\DOMElement $node, string $content): string
    {
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement) {
                continue;
            }

            $localName = strtolower($childNode->localName);

            if ($localName === 'enclosure') {
                $type = strtolower((string)$childNode->getAttribute('type'));
                $url = trim((string)$childNode->getAttribute('url'));
                if ($url !== '' && strpos($type, 'video/') === 0) {
                    return $url;
                }
            }

            if (in_array($localName, ['content', 'player'], true)) {
                $medium = strtolower((string)$childNode->getAttribute('medium'));
                foreach (['url', 'src', 'href'] as $attribute) {
                    $value = trim((string)$childNode->getAttribute($attribute));
                    if ($value !== '' && ($medium === 'video' || $this->looksLikeVideoUrl($value))) {
                        return $value;
                    }
                }
            }
        }

        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/<video[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/<source[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function injectPrimaryVideoIntoContent(string $content, string $videoUrl): string
    {
        if ($videoUrl === '') {
            return $content;
        }

        if (preg_match('/<(iframe|video)\b/i', $content)) {
            return $content;
        }

        $embed = $this->buildVideoEmbedHtml($videoUrl);
        if ($embed === '') {
            return $content;
        }

        return $embed . $content;
    }

    private function buildVideoEmbedHtml(string $videoUrl): string
    {
        $videoUrl = trim($videoUrl);
        if ($videoUrl === '') {
            return '';
        }

        $embedUrl = $this->normalizeEmbedVideoUrl($videoUrl);
        if ($embedUrl['type'] === 'file') {
            return '<figure class="rssnews-hero-video" style="margin:0 0 1rem 0;">'
                . '<video controls preload="metadata" style="display:block;width:100%;height:auto;">'
                . '<source src="' . htmlspecialchars($embedUrl['url'], ENT_QUOTES, 'UTF-8') . '">'
                . '</video>'
                . '</figure>';
        }

        return '<figure class="rssnews-hero-video" style="margin:0 0 1rem 0;">'
            . '<iframe src="' . htmlspecialchars($embedUrl['url'], ENT_QUOTES, 'UTF-8') . '" '
            . 'style="display:block;width:100%;aspect-ratio:16 / 9;border:0;" '
            . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
            . 'allowfullscreen loading="lazy"></iframe>'
            . '</figure>';
    }

    private function normalizeEmbedVideoUrl(string $videoUrl): array
    {
        $videoUrl = trim($videoUrl);

        if (preg_match('#https?://(?:www\.)?youtube\.com/watch\?[^"\']*v=([A-Za-z0-9_-]{6,})#i', $videoUrl, $matches)) {
            return ['type' => 'iframe', 'url' => 'https://www.youtube.com/embed/' . $matches[1]];
        }

        if (preg_match('#https?://youtu\.be/([A-Za-z0-9_-]{6,})#i', $videoUrl, $matches)) {
            return ['type' => 'iframe', 'url' => 'https://www.youtube.com/embed/' . $matches[1]];
        }

        if (preg_match('#https?://(?:www\.)?youtube\.com/embed/([A-Za-z0-9_-]{6,})#i', $videoUrl, $matches)) {
            return ['type' => 'iframe', 'url' => 'https://www.youtube.com/embed/' . $matches[1]];
        }

        if (preg_match('#https?://(?:www\.)?vimeo\.com/(\d+)#i', $videoUrl, $matches)) {
            return ['type' => 'iframe', 'url' => 'https://player.vimeo.com/video/' . $matches[1]];
        }

        if (preg_match('#https?://player\.vimeo\.com/video/(\d+)#i', $videoUrl, $matches)) {
            return ['type' => 'iframe', 'url' => 'https://player.vimeo.com/video/' . $matches[1]];
        }

        if ($this->looksLikeVideoUrl($videoUrl)) {
            return ['type' => 'file', 'url' => $videoUrl];
        }

        return ['type' => 'iframe', 'url' => $videoUrl];
    }

    private function looksLikeImageUrl(string $value): bool
    {
        $path = parse_url($value, PHP_URL_PATH);
        $path = is_string($path) ? $path : $value;

        return (bool)preg_match('/\.(jpg|jpeg|png|gif|webp|svg)(?:$|\?)/i', $path);
    }

    private function looksLikeVideoUrl(string $value): bool
    {
        if (preg_match('#(youtube\.com|youtu\.be|vimeo\.com)#i', $value)) {
            return true;
        }

        $path = parse_url($value, PHP_URL_PATH);
        $path = is_string($path) ? $path : $value;

        return (bool)preg_match('/\.(mp4|webm|ogg|mov|m4v)(?:$|\?)/i', $path);
    }

    private function isLocalArticleImagePath(string $value): bool
    {
        return !preg_match('#^(https?:)?//#i', $value);
    }

    private function resolveHighQualityImage(string $imageUrl, string $sourceLink): string
    {
        $imageUrl = trim($imageUrl);

        if ($sourceLink === '') {
            return $imageUrl;
        }

        if ($imageUrl === '' || $this->isLikelyLowQualityImage($imageUrl)) {
            $sourceImage = $this->extractHighQualityImageFromSourcePage($sourceLink);
            if ($sourceImage !== '') {
                return $sourceImage;
            }
        }

        return $imageUrl;
    }

    private function isLikelyLowQualityImage(string $url): bool
    {
        if ($url === '') {
            return true;
        }

        if (preg_match('/(?:thumb|thumbnail|preview|small|avatar)/i', $url)) {
            return true;
        }

        if (preg_match('/(?:^|[^0-9])(120|150|180|200|240|300|320|360|400)x(120|150|180|200|240|300|320|360|400)(?:[^0-9]|$)/i', $url)) {
            return true;
        }

        if (preg_match('/(?:^|[^0-9])(120|150|180|200|240|300|320|360|400)w(?:[^0-9]|$)/i', $url)) {
            return true;
        }

        return false;
    }

    private function extractHighQualityImageFromSourcePage(string $sourceLink): string
    {
        try {
            $html = $this->loadUrl($sourceLink);
        } catch (\Throwable $exception) {
            return '';
        }

        if ($html === '') {
            return '';
        }

        $candidates = [];
        $metaPatterns = [
            '/<meta[^>]+property=["\']og:image(?::secure_url)?["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+name=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<link[^>]+rel=["\']image_src["\'][^>]+href=["\']([^"\']+)["\']/i',
        ];

        foreach ($metaPatterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $candidate) {
                    $candidate = trim($candidate);
                    if ($candidate !== '' && $this->looksLikeImageUrl($candidate)) {
                        $candidates[] = $this->upgradeImageUrl($candidate);
                    }
                }
            }
        }

        foreach ($this->extractImageCandidatesFromHtml($html) as $candidate) {
            $candidates[] = $candidate;
        }

        $candidates = array_values(array_unique(array_filter($candidates)));
        if (empty($candidates)) {
            return '';
        }

        $bestUrl = '';
        $bestScore = -1;
        foreach ($candidates as $candidate) {
            $score = $this->scoreImageCandidate($candidate) + 500000;
            if ($score > $bestScore) {
                $bestUrl = $candidate;
                $bestScore = $score;
            }
        }

        return $bestUrl;
    }

    private function extractImageCandidatesFromHtml(string $content): array
    {
        $candidates = [];

        if ($content === '') {
            return $candidates;
        }

        if (preg_match_all('/<img\b[^>]*>/i', $content, $imageTags)) {
            foreach ($imageTags[0] as $imageTag) {
                foreach (['data-full', 'data-original', 'data-lazy-src', 'data-src', 'src'] as $attribute) {
                    if (preg_match('/' . preg_quote($attribute, '/') . '=["\']([^"\']+)["\']/i', $imageTag, $matches)) {
                        $url = trim($matches[1]);
                        if ($url !== '' && $this->looksLikeImageUrl($url)) {
                            $candidates[] = $this->upgradeImageUrl($url);
                        }
                    }
                }

                if (preg_match('/srcset=["\']([^"\']+)["\']/i', $imageTag, $matches)) {
                    $srcsetCandidates = $this->parseSrcsetCandidates($matches[1]);
                    foreach ($srcsetCandidates as $candidate) {
                        $candidates[] = $candidate;
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function parseSrcsetCandidates(string $srcset): array
    {
        $candidates = [];

        foreach (explode(',', $srcset) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $segments = preg_split('/\s+/', $part);
            if (empty($segments[0])) {
                continue;
            }

            $url = trim($segments[0]);
            if ($this->looksLikeImageUrl($url)) {
                $candidates[] = $this->upgradeImageUrl($url);
            }
        }

        return $candidates;
    }

    private function scoreImageCandidate(string $url, int $width = 0, int $height = 0): int
    {
        $score = 0;

        if ($width > 0 && $height > 0) {
            $score += min($width * $height, 5000000);
        } elseif ($width > 0) {
            $score += min($width * 1000, 5000000);
        }

        if (preg_match('/(?:^|[^0-9])([1-9][0-9]{2,4})x([1-9][0-9]{2,4})(?:[^0-9]|$)/i', $url, $matches)) {
            $score += min(((int)$matches[1]) * ((int)$matches[2]), 3000000);
        }

        if (preg_match('/(?:^|[^0-9])([1-9][0-9]{2,4})w(?:[^0-9]|$)/i', $url, $matches)) {
            $score += min((int)$matches[1] * 1000, 3000000);
        }

        if (preg_match('/-(150|300|320|480)x(150|200|240|320|480)\./i', $url)) {
            $score -= 500000;
        }

        if (preg_match('/(?:thumb|thumbnail|preview|small|avatar)/i', $url)) {
            $score -= 250000;
        }

        $score += strlen($url);

        return $score;
    }

    private function upgradeImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $url = preg_replace('/-\d{2,4}x\d{2,4}(?=\.(jpg|jpeg|png|gif|webp|svg)(?:\?|$))/i', '', $url);

        $parts = parse_url($url);
        if ($parts === false || empty($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $query);
        $changed = false;

        foreach (['w', 'width', 'h', 'height', 'resize', 'fit', 'crop'] as $param) {
            if (array_key_exists($param, $query)) {
                unset($query[$param]);
                $changed = true;
            }
        }

        if (!$changed) {
            return $url;
        }

        $rebuilt = '';
        if (!empty($parts['scheme'])) {
            $rebuilt .= $parts['scheme'] . '://';
        }

        if (!empty($parts['user'])) {
            $rebuilt .= $parts['user'];
            if (!empty($parts['pass'])) {
                $rebuilt .= ':' . $parts['pass'];
            }
            $rebuilt .= '@';
        }

        if (!empty($parts['host'])) {
            $rebuilt .= $parts['host'];
        }

        if (!empty($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }

        $rebuilt .= $parts['path'] ?? '';

        if (!empty($query)) {
            $rebuilt .= '?' . http_build_query($query);
        }

        if (!empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }
}
