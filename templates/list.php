<div id="<?php echo REDCap::escapeHtml($id); ?>" class="table-responsive<?php echo empty($class) ? '' : ' ' . REDCap::escapeHtml($class); ?>">
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <?php foreach ($header as $value): ?>
                        <th><?php echo $value; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <?php
                foreach ($rows as $key => $row) {
                    $values = '';

                    foreach ($row as $value) {
                        $values .= RCView::td([], $value);
                    }

                    echo RCView::tr(isset($rows_attributes[$key]) ? $rows_attributes[$key] : [], $values);
                }
            ?>
        </table>
    </div>
</div>
