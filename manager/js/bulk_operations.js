$(function() {
    $('button.bulk-operation').click(function() {
        $('#redcap-entity-bulk [name="__operation"]').val($(this).prop('name'));
    });

    $('[name="all_entities"]').click(function() {
        $('[name="entities[]"]').prop('checked', $(this).prop('checked'));
    });
});
