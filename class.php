<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \NikolayS93\PHPMailInterface;

if( !function_exists('esc_cyr') ) {
    function esc_cyr($s) {
        $s = strip_tags( (string) $s);
        $s = str_replace(array("\n", "\r"), " ", $s);
        $s = preg_replace("/\s+/", ' ', $s);
        $s = trim($s);
        $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s);
        $s = strtr($s, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
        $s = preg_replace("/[^0-9a-z-_ ]/i", "", $s);
        $s = str_replace(" ", "-", $s);

        return $s;
    }
}

class CustomFormBitrixComponent extends CBitrixComponent
{
    function __construct($component = null)
    {
        Main\EventManager::getInstance()->addEventHandler(
            'feedbackAdvanced',
            'OnSendFeedbackAdvanced',
            array($this, 'sendMail'),
            10
        );

        parent::__construct($component);
    }

    /**
     * @note Default bitrix method
     */
    function executeComponent()
    {
        global $APPLICATION;

        if( 'sendmail' === $this->request['action'] ) {
            $Maili = $this->doSendEvent();

            // Technical additional information
            if( $Maili->Body ) {
                $Maili->Body.= "\r\n";
                $Maili->Body.= "URI запроса: ". $_SERVER['REQUEST_URI'] . "\r\n";
                $Maili->Body.= "URL источника запроса: ". str_replace($Maili::$protocol . ':', '', $_SERVER['HTTP_REFERER']) . "\r\n";
            }

            // execute
            // $Maili->sendMail();

            /** @todo show html result */
            if( !empty($_REQUEST['is_ajax']) ) {
                $Maili->showResult();
                die();
            }
        }

        $this->includeComponentTemplate();
    }

    /**
     * @note Default bitrix method
     */
    function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    function doSendEvent()
    {
        $event = new Main\Event("feedbackAdvanced", "OnSendFeedbackAdvanced", $this->arParams);
        $event->send();

        // Обработка результатов вызова
        if ( $event->getResults() ) {
            /** @var \Bitrix\Main\EventResult $eventResult */
            foreach ($event->getResults() as $eventResult)
            {
                // echo "<pre>";
                // var_dump( $eventResult );
                // echo "</pre>";
            }
        }

        return $eventResult->getParameters();
    }

    function sendMail(Bitrix\Main\Event $event)
    {
        global $APPLICATION;

        $arParams = $event->getParameters();

        /** @var bool  must be empty for spam filter */
        $is_spam = !empty($_REQUEST["surname"]);

        if( !empty($_REQUEST['is_ajax']) ) {
            header('Content-Type: application/json');
            $APPLICATION->RestartBuffer();

            require __DIR__ . '/vendor/autoload.php';

            if( $is_spam ) { header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403); die(); }
        }

        $Maili = PHPMailInterface::getInstance();

        // User Name who sent message: %s <no-reply@domain.ltd>
        $Maili->fromName = $arParams['FROM_NAME'];

        // Mail subject
        $Maili->Subject  = $arParams['SUBJECT'];

        // Address where to send the message
        $Maili->addAddress($arParams['TO']);

        // Mail carbon copy
        // $Maili->addCC('trashmailsizh@ya.ru');

        // Inputs for handle
        if( is_array($arParams['FIELDS']) ) {
            foreach ($arParams['FIELDS'] as $field)
            {
                if( $customFieldID = strpos($field, ':') ) {
                    @list($fieldID, $field) = explode(':', $field);
                    $fieldID = esc_cyr($fieldID);
                }
                else {
                    $fieldID = esc_cyr($field);
                }

                $callback = false;
                if( isset($arParams["TYPE_" . strtoupper( $fieldID )]) ) {
                    switch ($arParams["TYPE_" . strtoupper( $fieldID )]) {
                        case 'STRING': $callback = 'strval'; break;
                        case 'NUMBER': $callback = 'floatval'; break;
                        case 'PHONE':  $callback = array($Maili, 'sanitize_phone'); break;
                        case 'EMAIL':  $callback = array($Maili, 'sanitize_email'); break;
                        case 'URL':    // $callback = 'floatval'; break;
                    }
                }

                if( !$fieldID ) continue;
                $Maili->addField( $fieldID, trim($field), $callback );
            }
        }

        // Field with this key must be filled
        foreach ($arParams['REQUIRED_FIELDS'] as $requiredField)
        {
            $Maili->setRequired($requiredField);
        }

        /** @var array List field key => sanitized requested value */
        $fields = $Maili->getFields();

        /** @var array List field key => field name (title/label) */
        $fieldNames = $Maili->getFieldNames();

        // Message is HTML
        // $Maili->isHTML(true);

        // Collect information on email body
        foreach ($fields as $key => $value)
        {
            if( $value ) $Maili->Body.= $fieldNames[$key] . ": $value\r\n";
        }

        $result = new Bitrix\Main\EventResult($event->getEventType(), $arParams);
        return $result;
    }
}
