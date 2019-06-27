<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var customOrderComponent $component */
?>
<form id="site-form" method="post">
    <h3>Форма отправки сообщения</h3>

    <div class="form-group">
        <label>Ваше имя</label>
        <input type="text" class="form-control" name="your-name">
    </div>

    <div class="form-group">
        <label>Электронная почта</label>
        <input type="text" class="form-control" name="your-email">
    </div>

    <div class="form-group">
        <label>Номер телефона</label>
        <input type="text" class="form-control" name="your-phone" data-format="+9 (999) 9999999">
    </div>

    <div class="form-group">
        <label>Дополнительный текст</label>
        <textarea class="form-control" name="advanced"></textarea>
    </div>

    <div class="form-group" style="display: none;">
        <label>Honeypot</label>
        <input type="text" class="form-control" name="surname"></input>
    </div>

    <input type="hidden" name="action" value="sendmail">
    <button type="submit" class="btn btn-default">Отправить</button>

    <div class="result"></div>
</form>

<script>window.jQuery || document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"><\/script>')</script>
<script type="text/javascript">
    jQuery(document).ready(function($) {

        /** Send form */
        function disableSubmit( $form, time ) {
            $form.data('submit', 'submitted');
            $('[type="submit"]', $form).attr('disabled', 'disable');

            if( time ) {
                setTimeout(function() {
                    $form.data('submit', '');
                    $('[type="submit"]', $form).removeAttr('disabled');
                }, time);
            }
        }

        $('#site-form').on('submit', function(event) {
            event.preventDefault();
            var $form = $(this);

            $('.result', $form).css('opacity', '0');
            if( $form.data('submit') ) return;

            $.post(
                '',
                $form.serialize() + '&is_ajax=Y',
                function(data, textStatus, xhr) {
                    if( 'success' == data.status ) {
                        $('.result', $form)
                            .removeClass('failure')
                            .addClass('success')
                            .html( data.message )
                            .animate({opacity: 1}, 200);

                        disableSubmit( $form, 30000 );
                    }
                    else {
                        $('.result', $form)
                            .addClass('failure')
                            .html( data.message )
                            .animate({opacity: 1}, 200);
                    }
                },
                'JSON')
            .fail(function() {
                $('.result', $form)
                .addClass('failure')
                .html( 'Случилась непредвиденная ошибка. Обратитесь к администратору' )
                .animate({opacity: 1}, 200);
            });
        });
    });
</script>