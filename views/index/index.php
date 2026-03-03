<?php $layoutMode = $this->get('layoutMode') ?: 'list'; ?>
<?php
$wrapperClass = 'rssnews-list';
$itemClass = 'mb-3';

if ($layoutMode === 'grid-2' || $layoutMode === 'grid-3') {
    $wrapperClass .= ' row';
    $itemClass = $layoutMode === 'grid-3' ? 'col-12 col-md-6 col-xl-4 mb-4' : 'col-12 col-lg-6 mb-4';
}
?>

<style>
    .rssnews-content::after {
        content: "";
        display: block;
        clear: both;
    }

    .rssnews-content .rssnews-hero-image {
        float: left;
        width: 50%;
        margin: 0 1rem 1rem 0;
    }

    .rssnews-content .rssnews-hero-image img {
        display: block;
        width: 100%;
        height: auto;
        margin: 0;
    }

    .rssnews-content img {
        display: block;
        width: 50%;
        height: auto;
        float: left;
        margin: 0 1rem 1rem 0;
    }

    .rssnews-content figure {
        margin: 0 1rem 1rem 0;
    }
</style>

<div class="<?=$this->escape($wrapperClass) ?>">
    <?php foreach (($this->get('items') ?? []) as $item): ?>
        <div class="<?=$this->escape($itemClass) ?>">
            <article class="card h-100">
                <div class="card-body">
                <h3 class="h5 mb-1">
                    <?php if (!empty($item['source_link'])): ?>
                        <a href="<?=$this->escape($item['source_link']) ?>" target="_blank" rel="noopener"><?=$this->escape($item['title']) ?></a>
                    <?php else: ?>
                        <?=$this->escape($item['title']) ?>
                    <?php endif; ?>
                </h3>
                <div class="small text-muted mb-2">
                    <?=$this->escape($item['feed_name'] ?? '') ?>
                    <?php if (!empty($item['category'])): ?>
                        | <?=$this->escape($item['category']) ?>
                    <?php endif; ?>
                    | <?=$this->escape($item['published_at']) ?>
                </div>
                <div class="rssnews-content mb-2"><?=$this->purify($item['content']) ?></div>
                <?php if (!empty($item['tags'])): ?>
                    <div class="small text-muted"><?=$this->escape($item['tags']) ?></div>
                <?php endif; ?>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>
