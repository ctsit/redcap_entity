$(function() {
    $('.entity-schema-op-btn').click(function() {
        var $form = $('#entity_type_table_operation');
        var fields = ['operation', 'entity_type'];

        for (i = 0; i < fields.length; i++) {
            $form.find('[name="' + fields[i] + '"]').val($(this).data(fields[i]));
        }

        $form.submit();
    });
})
