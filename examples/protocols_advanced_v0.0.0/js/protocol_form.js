$(function() {
    var $number = $('#entity-form [name="number"]');

    if ($number.val()) {
        // Disables "Number" field when it is already set.
        $number.attr('disabled', '');
    }
});
