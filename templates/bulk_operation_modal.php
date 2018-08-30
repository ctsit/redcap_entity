<div class="modal fade" id="redcap-entity-bulk-operation-modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" id="redcap-entity-bulk-form">
                <input type="hidden" name="__operation">
                <div class="modal-header">
                    <h5 class="modal-title">Are you sure you want to proceed?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?php echo isset($op['messages']['confirmation']) ? REDCap::escapeHtml($op['messages']['confirmation']) : 'This action cannot be undone.'; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-<?php echo $btn_class; ?>"><?php echo REDCap::escapeHtml($op['label']); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
