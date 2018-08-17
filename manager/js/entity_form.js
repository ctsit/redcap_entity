$(function() {
    $.each(redcapEntity.entityReference.fields, function (name, entityType) {
        $('#entity-form select[name="' + name + '"]').select2({
            ajax: {
                url: redcapEntity.entityReference.url,
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

    redcapEntity.projectReference.fields.forEach(function (name) {
        $('#entity-form select[name="' + name + '"]').select2({
            ajax: {
                url: redcapEntity.projectReference.url,
                dataType: 'json',
                cache: true,
                delay: 250,
                data: function (params) {
                    return {
                        'parameters': params.term
                    };
                }
            }
        });
    });

    redcapEntity.userFields.forEach(function (name) {
        $('#entity-form select[name="' + name + '"]').select2({
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

    redcapEntity.dateFields.forEach(function (name) {
        $('#entity-form input[name="' + name + '"]').datepicker({
            dateFormat: user_date_format_jquery
        });
    });
});
