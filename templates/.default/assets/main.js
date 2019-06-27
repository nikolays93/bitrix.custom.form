jQuery(document).ready(function($) {
    /** Fancybox advance */
    var showPreloader = function() {};
    var hidePreloader = function() {};

    if( $.fancybox ) {
        $.fancybox.defaults.lang = "ru";
        $.fancybox.defaults.i18n.ru = {
            CLOSE: "Закрыть",
            NEXT: "Следующий",
            PREV: "Предыдущий",
            ERROR: "Контент по запросу не найден. <br/> Пожалуйста попробуйте снова, позже.",
            PLAY_START: "Начать слайдшоу",
            PLAY_STOP: "Пауза",
            FULL_SCREEN: "На весь экран",
            THUMBS: "Превью",
            DOWNLOAD: "Скачать",
            SHARE: "Поделиться",
            ZOOM: "Приблизить"
        }

        showPreloader = function(message) {
            if(!message) message = 'Загрузка..';

            $.fancybox.open({
                content  : $('<p>'+message+'</p>').css({ 'margin-top': '50px', 'margin-bottom': '-40px', 'padding-bottom': '', 'color': '#ddd' }),
                type     : 'html',
                smallBtn : false,
                buttons : ["close"],
                afterLoad: function(instance, current) {
                    current.$content.css('background', 'none');
                },
                afterShow: function(instance, current) {
                    instance.showLoading( current );
                },
                afterClose: function(instance, current) {
                    instance.hideLoading( current );
                }
            });
        }

        hidePreloader = function() {
            $.fancybox.getInstance().close();
        };
    }
});
