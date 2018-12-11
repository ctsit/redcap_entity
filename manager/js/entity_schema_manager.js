$(function() {
    $('.entity-schema-op-btn').click(function() {
        var $form = $('#entity_type_table_operation');
        var fields = ['operation', 'entity_type'];

        // Setting up form fields.
        for (i = 0; i < fields.length; i++) {
            $form.find('[name="' + fields[i] + '"]').val($(this).data(fields[i]));
        }

        $form.submit();
    });

    $('.delete-table-confirm-name').keyup(function() {
        // Enabling/disabling drop db button according to the confirmation field.
        var entityType = $(this).data('entity_type');
        $('button[data-entity_type="' + entityType + '"]').prop('disabled', $(this).val() !== 'redcap_entity_' + entityType);
    });

    return;
})
