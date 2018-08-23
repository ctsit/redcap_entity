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
            <?php
                foreach ($rows as $id => $row) {
                    $output = '';
                    foreach ($row as $value) {
                         $output .= RCView::td([], $value);
                    }

                    echo RCView::tr($rows_attributes[$id], $output);
                }
            ?>
        </table>
    </div>
</div>
