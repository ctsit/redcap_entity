<div id="redcap_entity_list-<?php echo $entity_type; ?>" class="table-responsive redcap-entity-list">
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <?php foreach ($header as $value): ?>
                        <th><?php echo $value; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <?php echo $rows; ?>
        </table>
    </div>
</div>
