<form method="post">
    <?=$this->getTokenField() ?>

    <div class="row mb-3">
        <label class="col-lg-3 col-form-label"><?=$this->getTrans('defaultInterval') ?></label>
        <div class="col-lg-9">
            <input class="form-control" type="number" min="60" name="default_interval" value="<?=$this->escape($this->get('defaultInterval')) ?>">
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-lg-3 col-form-label"><?=$this->getTrans('cronInterval') ?></label>
        <div class="col-lg-9">
            <input class="form-control" type="number" min="60" name="cron_interval" value="<?=$this->escape($this->get('cronInterval')) ?>">
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-lg-3 col-form-label"><?=$this->getTrans('postMode') ?></label>
        <div class="col-lg-9">
            <?php $mode = $this->get('postMode'); ?>
            <select class="form-control" name="post_mode">
                <option value="aggregator" <?=$mode === 'aggregator' ? 'selected' : '' ?>><?=$this->getTrans('aggregator') ?></option>
                <option value="article" <?=$mode === 'article' ? 'selected' : '' ?>><?=$this->getTrans('article') ?></option>
                <option value="both" <?=$mode === 'both' ? 'selected' : '' ?>><?=$this->getTrans('both') ?></option>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-lg-3 col-form-label"><?=$this->getTrans('frontendLayout') ?></label>
        <div class="col-lg-9">
            <?php $frontendLayout = $this->get('frontendLayout'); ?>
            <select class="form-control" name="frontend_layout">
                <option value="list" <?=$frontendLayout === 'list' ? 'selected' : '' ?>><?=$this->getTrans('layoutList') ?></option>
                <option value="grid-2" <?=$frontendLayout === 'grid-2' ? 'selected' : '' ?>><?=$this->getTrans('layoutGrid2') ?></option>
                <option value="grid-3" <?=$frontendLayout === 'grid-3' ? 'selected' : '' ?>><?=$this->getTrans('layoutGrid3') ?></option>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-lg-3 col-form-label"><?=$this->getTrans('articleCategory') ?></label>
        <div class="col-lg-9">
            <?php $articleCatId = (int)$this->get('articleCatId'); ?>
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
        <label class="col-lg-3 col-form-label"><?=$this->getTrans('readAccess') ?></label>
        <div class="col-lg-9">
            <?php $selectedReadAccess = array_map('intval', array_filter(explode(',', (string)$this->get('readAccess')))); ?>
            <select class="choices-select form-control"
                    id="rssnewsSettingsReadAccess"
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
    </div>

    <div class="row mb-3">
        <label class="col-lg-3 col-form-label"><?=$this->getTrans('cronToken') ?></label>
        <div class="col-lg-9">
            <input class="form-control" type="text" name="cron_token" value="<?=$this->escape($this->get('cronToken')) ?>">
            <small class="form-text text-muted"><?=$this->getTrans('cronInfo') ?></small>
        </div>
    </div>

    <?=$this->getSaveBar('saveButton') ?>
</form>

<script>
    $(document).ready(function() {
        new Choices('#rssnewsSettingsReadAccess', {
            ...choicesOptions,
            searchEnabled: true,
            removeItemButton: true
        });
    });
</script>
