<div class="modal fade<?php echo empty($class) ? '' : ' ' . REDCap::escapeHtml($class); ?>" id="<?php echo REDCap::escapeHtml($id); ?>" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo empty($title) ? 'Are you sure you want to proceed?' : $title; ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><?php echo empty($body) ? 'This action cannot be undone.' : $body; ?></p>
            </div>
            <?php if (!empty($confirm_btn)): ?>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <?php echo RCView::button($confirm_btn['attrs'], $confirm_btn['title']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
