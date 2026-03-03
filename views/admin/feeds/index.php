<div class="mb-3">
    <a class="btn btn-primary" href="<?=$this->getUrl(['action' => 'treat']) ?>"><?=$this->getTrans('add') ?></a>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th>Title</th>
            <th><?=$this->getTrans('feedUrl') ?></th>
            <th><?=$this->getTrans('category') ?></th>
            <th><?=$this->getTrans('tags') ?></th>
            <th><?=$this->getTrans('updateInterval') ?></th>
            <th><?=$this->getTrans('postMode') ?></th>
            <th><?=$this->getTrans('lastFetch') ?></th>
            <th><?=$this->getTrans('lastError') ?></th>
            <th><?=$this->getTrans('active') ?></th>
            <th><?=$this->getTrans('actions') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($this->get('feeds') ?? []) as $feed): ?>
            <tr>
                <td><?=$this->escape($feed->getTitle()) ?></td>
                <td class="text-break"><?=$this->escape($feed->getFeedUrl()) ?></td>
                <td><?=$this->escape($feed->getCategory()) ?></td>
                <td><?=$this->escape($feed->getTags()) ?></td>
                <td><?=$this->escape((string)$feed->getUpdateInterval()) ?></td>
                <td><?=$this->escape($feed->getPostMode()) ?></td>
                <td><?=$this->escape((string)$feed->getLastFetchAt()) ?></td>
                <td><?=$this->escape((string)$feed->getLastError()) ?></td>
                <td><?=$feed->getIsActive() ? 'Ja' : 'Nein' ?></td>
                <td>
                    <a class="btn btn-sm btn-outline-primary" href="<?=$this->getUrl(['action' => 'treat', 'id' => $feed->getId()]) ?>"><?=$this->getTrans('edit') ?></a>
                    <a class="btn btn-sm btn-outline-success" href="<?=$this->getUrl(['action' => 'index', 'fetch' => $feed->getId()], null, true) ?>">Fetch</a>
                    <a class="btn btn-sm btn-outline-danger" href="<?=$this->getUrl(['action' => 'index', 'delete' => $feed->getId()], null, true) ?>"><?=$this->getTrans('delete') ?></a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
