<?php $feed = $this->get('feed'); ?>
<h1><?=($feed && $feed->getId()) ? $this->getTrans('edit') : $this->getTrans('add') ?></h1>
<form method="post">
    <?=$this->getTokenField() ?>

    <div class="row mb-3">
        <label class="col-lg-2 col-form-label">Title</label>
        <div class="col-lg-10">
            <input class="form-control" type="text" name="title" value="<?=$this->escape($feed ? $feed->getTitle() : '') ?>">
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-lg-2 col-form-label"><?=$this->getTrans('feedUrl') ?></label>
        <div class="col-lg-10">
            <input class="form-control" type="url" name="feed_url" value="<?=$this->escape($feed ? $feed->getFeedUrl() : '') ?>">
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-lg-2 col-form-label"><?=$this->getTrans('category') ?></label>
        <div class="col-lg-4">
            <input class="form-control" type="text" name="category" value="<?=$this->escape($feed ? $feed->getCategory() : '') ?>">
        </div>
        <label class="col-lg-2 col-form-label"><?=$this->getTrans('tags') ?></label>
        <div class="col-lg-4">
            <input class="form-control" type="text" name="tags" value="<?=$this->escape($feed ? $feed->getTags() : '') ?>">
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-lg-2 col-form-label"><?=$this->getTrans('updateInterval') ?></label>
        <div class="col-lg-4">
            <input class="form-control" type="number" min="60" name="update_interval" value="<?=$this->escape((string)($feed ? $feed->getUpdateInterval() : $this->get('defaultInterval'))) ?>">
        </div>
        <label class="col-lg-2 col-form-label"><?=$this->getTrans('maxItems') ?></label>
        <div class="col-lg-4">
            <input class="form-control" type="number" min="1" name="max_items" value="<?=$this->escape((string)($feed ? $feed->getMaxItems() : 10)) ?>">
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-lg-2 col-form-label"><?=$this->getTrans('postMode') ?></label>
        <div class="col-lg-4">
            <?php $mode = $feed ? $feed->getPostMode() : 'default'; ?>
            <select class="form-control" name="post_mode">
                <option value="default" <?=$mode === 'default' ? 'selected' : '' ?>>Global</option>
                <option value="aggregator" <?=$mode === 'aggregator' ? 'selected' : '' ?>><?=$this->getTrans('aggregator') ?></option>
                <option value="article" <?=$mode === 'article' ? 'selected' : '' ?>><?=$this->getTrans('article') ?></option>
                <option value="both" <?=$mode === 'both' ? 'selected' : '' ?>><?=$this->getTrans('both') ?></option>
            </select>
        </div>
        <label class="col-lg-2 col-form-label"><?=$this->getTrans('articleCategory') ?></label>
        <div class="col-lg-4">
            <?php $articleCatId = (int)($feed ? $feed->getArticleCatId() : $this->get('defaultArticleCatId')); ?>
            <select class="form-control" name="article_cat_id">
                <?php foreach (($this->get('articleCategories') ?? []) as $category): ?>
                    <option value="<?=$category->getId() ?>" <?=$articleCatId === (int)$category->getId() ? 'selected' : '' ?>>
                        <?=$this->escape($category->getId() . ' - ' . $category->getName()) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-lg-2 col-form-label"><?=$this->getTrans('readAccess') ?></label>
        <div class="col-lg-4">
            <?php $selectedReadAccess = array_map('intval', array_filter(explode(',', (string)($feed ? $feed->getReadAccess() : $this->get('defaultReadAccess'))))); ?>
            <select class="choices-select form-control"
                    id="rssnewsFeedReadAccess"
                    name="read_access[]"
                    data-placeholder="<?=$this->getTrans('selectAssignedGroups') ?>"
                    multiple>
                <?php foreach (($this->get('userGroupList') ?? []) as $group): ?>
                    <option value="<?=$group->getId() ?>" <?=in_array((int)$group->getId(), $selectedReadAccess, true) ? 'selected' : '' ?>>
                        <?=$this->escape($group->getId() . ' - ' . $group->getName()) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <label class="col-lg-2 col-form-label"><?=$this->getTrans('active') ?></label>
        <div class="col-lg-4 pt-2">
            <input type="checkbox" name="is_active" value="1" <?=(!$feed || $feed->getIsActive()) ? 'checked' : '' ?>>
        </div>
    </div>

    <?=($feed && $feed->getId()) ? $this->getSaveBar('updateButton') : $this->getSaveBar('addButton') ?>
</form>

<script>
    $(document).ready(function() {
        new Choices('#rssnewsFeedReadAccess', {
            ...choicesOptions,
            searchEnabled: true,
            removeItemButton: true
        });
    });
</script>
