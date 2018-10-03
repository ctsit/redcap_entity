$(function() {
    $('#redcap-entity-exp-filters-form select').select2();
    var $buttons = $('button.bulk-operation');

    $buttons.click(function() {
        $('#redcap-entity-bulk-form [name="__operation"]').val($(this).prop('name'));
    });

    $('[name="entities[]"]').click(function() {
        $('[name="all_entities"]').prop('checked', $('[name="entities[]"]:not(:checked)').length === 0);
        setBulkOperationButtonsStatus();
    });

    $('[name="all_entities"]').click(function() {
        $('[name="entities[]"]').prop('checked', $(this).prop('checked'));
        setBulkOperationButtonsStatus();
    });

    function setBulkOperationButtonsStatus() {
        $buttons.prop('disabled', $('[name="entities[]"]:checked').length === 0);
    }
});
