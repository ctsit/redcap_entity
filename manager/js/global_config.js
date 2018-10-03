$(function() {
    $modal = $('#external-modules-disable-confirm-modal');

    $modal.on('shown.bs.modal', function() {
        var module = $('#external-modules-disable-confirm-module-name').text();

        if (typeof redcapEntity.modules[module] !== 'undefined') {
            $(this).find('.modal-body').append(redcapEntity.disableCheckbox);
        }
    });

    $modal.on('hidden.bs.modal', function() {
        $(this).find('.redcap-entity-disable').remove();
    });
});
