$(window).load(function() {
    $('.js-regions input').change(function() {
        var mainRegions = $('.js-mainregions');
        var currentValue = mainRegions.val();
        mainRegions.find('option:gt(0)').remove();
        $('.js-regions .RichSelect__item .RichSelect__item--selected').each(function() {
            mainRegions.append('<option value="' + $(this).find('input').val() + '">' + $(this).text() + '</option>\n');
        });
        mainRegions.val(currentValue);
        if (!mainRegions.val()) {
            mainRegions.val('');
        }
    });

    $('.Item__box').each(function() {
        if ((this.offsetWidth < this.scrollWidth)) {
            $(this).addClass('js-tipTruncatedText').attr('title', $(this).html());
        }
    });

    // Тип-тип - на ячейках таблицы
    $('.js-tipTruncatedText').tipTip({
        theme: 'white',
        maxWidth: 340,
        edgeOffset: -12
    });

});