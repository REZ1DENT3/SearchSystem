$(function() {
    $('#from_search li a').click(function() {
        $(this).parents('div')
            .find('button')
            .first()
            .html($(this).text() + " <span class='caret'></span>");
        $("[name=table]").val($(this).text() == 'all' ? '' : $(this).text());
    });
});