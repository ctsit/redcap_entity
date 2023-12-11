$(function() {
    $('select.redcap-entity-select').each(function() {
        var label = $('label[for="' + $(this).prop('id') + '"]').text();

        $(this).select2({
            allowClear: true,
            placeholder: '-- ' + label + ' --'
        });
    });

    $('select.redcap-entity-select-entity-reference').each(function() {
        var entityType = $(this).data('entity_type');
        var label = $('label[for="' + $(this).prop('id') + '"]').text();

        $(this).select2({
            allowClear: true,
            placeholder: '-- ' + label + ' --',
            ajax: {
                url: redcapEntity.entityReferenceUrl,
                dataType: 'json',
                cache: true,
                delay: 250,
                data: function (params) {
                    params.entity_type = entityType;
                    return params;
                }
            }
        });
    });

    $('select.redcap-entity-select-project').each(function() {
        var label = $('label[for="' + $(this).prop('id') + '"]').text();

        let element = $(this);

        // select2's built-in ajax functionality disables select2 filtering
        // instead populate options with array from a completed ajax call
        // See: https:select2.org/data-sources/ajax#transforming-response-data
        $.ajax({
            type: 'GET',
            url: redcapEntity.projectReferenceUrl,
            dataType: 'json'
        }).then(function (data) {
            element.select2({
                placeholder: '-- ' + label + '--',
                allowClear: true,
                data: data.results
            })
        });
    });

    $('select.redcap-entity-select-user').each(function() {
        var label = $('label[for="' + $(this).prop('id') + '"]').text();

        $(this).select2({
            placeholder: '-- ' + label + '--',
            allowClear: true,
            ajax: {
                url: app_path_webroot + 'UserRights/search_user.php',
                dataType: 'json',
                cache: true,
                delay: 250,
                data: function (params) {
                    params.searchEmail = true;
                    return params;
                },
                processResults: function (data) {
                    var results = {
                        results: [],
                        more: false
                    };

                    data.forEach(function (item) {
                        results.results.push({
                            id: item.value,
                            text: item.label.replace(/<b>/g, '').replace(/<\/b>/g, '')
                        });
                    });

                    return results;
                }
            }
        });
    });

    $('input.redca-entity-field-date').datepicker({
        dateFormat: user_date_format_jquery
    });
});
