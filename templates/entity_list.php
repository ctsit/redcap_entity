<div id="redcap-entity-view" class="table-responsive">
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <?php foreach ($header as $value): ?>
                        <th><?php echo $value; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $value): ?>
                        <td><?php echo $value; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
