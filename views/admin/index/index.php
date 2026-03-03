<form method="post" class="mb-4">
    <?=$this->getTokenField() ?>
    <?=$this->getSaveBar('manualFetch', null, 'fetchAll') ?>
</form>

<?php if ($this->get('summary')): ?>
    <div class="alert alert-info"><?=$this->escape(json_encode($this->get('summary'), JSON_UNESCAPED_UNICODE)) ?></div>
<?php endif; ?>

<h1><?=$this->getTrans('moduleName') ?></h1>
<div class="table-responsive mb-4">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th><?=$this->getTrans('publishedAt') ?></th>
            <th><?=$this->getTrans('source') ?></th>
            <th><?=$this->getTrans('category') ?></th>
            <th><?=$this->getTrans('postMode') ?></th>
            <th>Title</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($this->get('items') ?? []) as $item): ?>
            <tr>
                <td><?=$this->escape($item['published_at']) ?></td>
                <td><?=$this->escape($item['feed_name'] ?? '') ?></td>
                <td><?=$this->escape($item['category']) ?></td>
                <td><?=$this->escape($item['mirror_mode']) ?></td>
                <td>
                    <?php if (!empty($item['source_link'])): ?>
                        <a href="<?=$this->escape($item['source_link']) ?>" target="_blank" rel="noopener"><?=$this->escape($item['title']) ?></a>
                    <?php else: ?>
                        <?=$this->escape($item['title']) ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2><?=$this->getTrans('log') ?></h2>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th>Time</th>
            <th><?=$this->getTrans('source') ?></th>
            <th>Status</th>
            <th>Message</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($this->get('logs') ?? []) as $log): ?>
            <tr>
                <td><?=$this->escape($log['created_at']) ?></td>
                <td><?=$this->escape($log['feed_name'] ?? '-') ?></td>
                <td><?=$this->escape($log['status']) ?></td>
                <td><?=$this->escape($log['message']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
