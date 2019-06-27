<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();

require_once __DIR__ . '/.parameters.php';

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \NikolayS93\PHPMailInterface;

class CustomFormBitrixComponent extends CBitrixComponent
{
    function __construct($component = null)
    {
        /**
         * @todo
         */
        // Main\EventManager::getInstance()->addEventHandler(
        //     'customFrom',
        //     'OnSendCustomFrom',
        //     array($this, 'sendMail'),
        //     10
        // );

        parent::__construct($component);
    }

    function onPrepareComponentParams($arParams)
    {
        // if( 'Y' == $arParams['SAVE_TO_IBLOCK'] ) {
        //     try
        //     {
        //         if( Loader::includeModule('iblock') ) {
        //             throw new LoaderException("Для сохранения требуется модуль инфоблоков");
        //         }
        //     }
        //     catch (LoaderException $exception)
        //     {
        //         echo '<p style="color: #f00">' . $exception->getMessage() . '</p>';
        //     }
        // }

        return $arParams;
    }

    function executeComponent()
    {
        global $APPLICATION;

        if( 'sendmail' === $this->request['action'] ) {
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
            $Maili->fromName = $this->arParams['FROM_NAME'];

            // Mail subject
            $Maili->Subject  = $this->arParams['SUBJECT'];

            // Address where to send the message
            $Maili->addAddress($this->arParams['TO']);

            // Mail carbon copy
            // $Maili->addCC('trashmailsizh@ya.ru');

            /** @var array List field key => sanitized requested value */
            $fields = $this->prepareFields( $Maili );

            /** @var array List field key => field name (title/label) */
            $fieldNames = $Maili->getFieldNames();

            /** @var String Mail message */
            $Maili->Body = $this->combineMailBody($fields, $fieldNames);

            // execute
            $Maili->sendMail();

            if( 'Y' == $this->arParams['SAVE_TO_IBLOCK'] ) {
                $this->addElement( $fields );
            }

            /** @todo show html result */
            if( !empty($_REQUEST['is_ajax']) ) {
                $Maili->showResult();
                die();
            }
        }

        $this->includeComponentTemplate();
    }

    function prepareFields( &$Maili )
    {
        // Inputs for handle
        if( is_array($this->arParams['FIELDS']) ) {
            foreach ($this->arParams['FIELDS'] as $field)
            {
                if( $customFieldID = strpos($field, ':') ) {
                    @list($fieldID, $field) = explode(':', $field);
                    $fieldID = esc_cyr($fieldID);
                }
                else {
                    $fieldID = esc_cyr($field);
                }

                $callback = false;
                if( isset($this->arParams["TYPE_" . strtoupper( $fieldID )]) ) {
                    switch ($this->arParams["TYPE_" . strtoupper( $fieldID )]) {
                        // case 'STRING': $callback = 'strval'; break;
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
        foreach ($this->arParams['REQUIRED_FIELDS'] as $requiredField)
        {
            $Maili->setRequired($requiredField);
        }

        return $Maili->getFields();
    }

    function combineMailBody($fields, $fieldNames)
    {
        // Message is HTML
        // $Maili->isHTML(true);

        $body = '';
        // Collect information on email body
        foreach ($fields as $key => $value)
        {
            if( $value ) $body.= $fieldNames[$key] . ": $value\r\n";
        }

        // Technical additional information
        if( $body ) {
            $body.= "\r\n";
            $body.= "URI запроса: ". $_SERVER['REQUEST_URI'] . "\r\n";
            $body.= "URL источника запроса: " .
                preg_replace('/(?:https?:\/\/)?(?:www\.)?(.*)\/?$/i', '$1', $_SERVER['HTTP_REFERER']) . "\r\n";
        }

        return $body;
    }

    function addElement( $fields = array() )
    {
        $rsUsers = CUser::GetList(($field = "id"), ($order = "asc"), array("GROUPS_ID" => Array(1)));
        $rsUsers->NavNext(true, "USER_");

        $arLoadProductArray = Array(
            "MODIFIED_BY"       => $USER_ID, // @todo change this
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID"         => $this->arParams['IBLOCK_ID'],
            "ACTIVE"            => "N",
            "NAME"              => isset( $fields[ $this->arParams['ELEMENT_TITLE'] ] ) ? $fields[ $this->arParams['ELEMENT_TITLE'] ] : '',
            "CODE"              => isset( $fields[ $this->arParams['ELEMENT_TITLE'] ] ) ? esc_cyr($fields[ $this->arParams['ELEMENT_TITLE'] ]) : '',
            "PREVIEW_TEXT"      => isset( $fields[ $this->arParams['ELEMENT_PREVIEW_TEXT'] ] ) ? $fields[ $this->arParams['ELEMENT_PREVIEW_TEXT'] ] : '',
            "DETAIL_TEXT"       => isset( $fields[ $this->arParams['ELEMENT_DETAIL_TEXT'] ] ) ? $fields[ $this->arParams['ELEMENT_DETAIL_TEXT'] ] : '',
        );

        // Prepare name
        if( !$arLoadProductArray['NAME'] ) {
            $arLoadProductArray['NAME'] = 'Новая запись от ' . date('d.m.Y h:i:s');
            $arLoadProductArray['CODE'] = date('Ymdhis');
        }

        // Prepare properties
        $args = array(
            'select' => array('ID', 'NAME'),
            'filter' => array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            ),
        );

        $properties = \Bitrix\Iblock\PropertyTable::getList($args)->FetchAll();
        if( count($properties) ) {
            $arLoadProductArray["PROPERTY_VALUES"] = array();

            foreach ($properties as $property)
            {
                if( isset( $fields[ $this->arParams["PROPERTY_" . $property['ID']] ] ) ) {
                    $arLoadProductArray["PROPERTY_VALUES"][ $property['ID'] ]
                        = $fields[ $this->arParams["PROPERTY_" . $property['ID']] ];
                }
            }
        }

        $el = new CIBlockElement;
        $el_id = $el->Add($arLoadProductArray);
        // if( !$el_id ) echo $el->LAST_ERROR;
    }
}
