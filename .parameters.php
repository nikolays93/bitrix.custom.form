<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

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

/**
 * @global $arCurrentValues
 */

$allFields = array();
$allLessFields = array();

if( is_array($arCurrentValues['FIELDS']) ) {
    foreach ($arCurrentValues['FIELDS'] as $field)
    {
        if( $customFieldID = strpos($field, ':') ) {
            @list($fieldID, $field) = explode(':', $field);
            $fieldID = esc_cyr($fieldID);
        }
        else {
            $fieldID = esc_cyr($field);
        }

        if( !$fieldID ) continue;
        $allFields[ $fieldID ] = trim($field) . " ($fieldID)";
        $allLessFields[ $fieldID ] = trim($field);
    }
}

$arComponentParameters = array(
    // "GROUPS" => array(
    //     // "BASE",
    //     // "DATA_SOURCE",
    //     // "VISUAL",
    //     // "USER_CONSENT",
    //     // "URL_TEMPLATES",
    //     // "SEF_MODE",
    //     // "AJAX_SETTINGS",
    //     // "CACHE_SETTINGS",
    //     // "ADDITIONAL_SETTINGS",
    // ),
    "PARAMETERS" => array(
        "FROM_NAME" => array(
            "PARENT" => "BASE",
            "NAME" => "Имя администратора",
            "TYPE" => "STRING",
            "DEFAULT" => "Администратор сайта",
        ),
        "SUBJECT" => array(
            "PARENT" => "BASE",
            "NAME" => "Тема сообщения",
            "TYPE" => "STRING",
            "DEFAULT" => "Сообщение с сайта",
        ),
        "TO" => array(
            "PARENT" => "BASE",
            "NAME" => "Получатель",
            "TYPE" => "STRING",
            "DEFAULT" => "nikolays93@ya.ru",
        ),
        "USER_CONSENT" => array(),

        "FIELDS" => array(
            "PARENT" => "BASE",
            "NAME" => "Поля формы",
            "TYPE" => "STRING",
            "MULTIPLE" => "Y",
            "REFRESH" => "Y",
            "DEFAULT" => array(
                "your-name:Ваше имя",
                "your-phone:Номер телефона",
            ),
        ),
        "REQUIRED_FIELDS" => array(
            "PARENT" => "BASE",
            "NAME" => "Обязательные поля",
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $allFields,
        ),

        "SAVE_TO_IBLOCK" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => "Сохранить в инфоблок",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
            "REFRESH" => "Y",
        ),
    ),
);

foreach ($allLessFields as $field_id => $field_name)
{
    $arComponentParameters["PARAMETERS"]["TYPE_" . strtoupper($field_id) ] = array(
        "PARENT" => "BASE",
        "NAME" => "Тип поля \"{$field_name}\"",
        "TYPE" => "LIST",
        "VALUES" => array(
            "STRING" => 'Строка',
            "NUMBER" => 'Число',
            "PHONE"  => 'Номер телефона',
            "EMAIL"  => 'Эл. почта',
            // "URL"    => 'Ссылка',
        ),
    );
}

if ( CModule::IncludeModule("iblock") && 'Y' == $arCurrentValues['SAVE_TO_IBLOCK'] ) {
    /**
     * Get iblock types
     * @var array
     */
    $iblockTypes = \Bitrix\Iblock\TypeTable::getList(array(
        'select' => array('ID', 'LANG_MESSAGE'),
        'filter' => array('!ID' => 'rest_entity')
    ))->FetchAll();

    $arIBlockTypes = array();
    foreach ($iblockTypes as $iblockType)
    {
        $arIBlockTypes[ $iblockType['ID'] ] =!empty($iblockType['IBLOCK_TYPE_LANG_MESSAGE_ELEMENTS_NAME']) ?
            $iblockType['IBLOCK_TYPE_LANG_MESSAGE_ELEMENTS_NAME'] :
            $iblockType['ID'];
    }

    $arComponentParameters["PARAMETERS"]["IBLOCK_TYPE"] = array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => "Тип инфоблока",
        "TYPE" => "LIST",
        "REFRESH" =>  "Y",
        "VALUES" => $arIBlockTypes,
    );

    /**
     * Get iblocks by type
     * @var array $args
     */
    reset($arIBlockTypes);
    if( !$arCurrentValues['IBLOCK_TYPE'] && is_array($arIBlockTypes) ) {
        $arCurrentValues['IBLOCK_TYPE'] = key($arIBlockTypes);
    }

    $args = array(
        'select' => array('ID', 'NAME'),
        'filter' => array(
            'IBLOCK_TYPE_ID' => $arCurrentValues['IBLOCK_TYPE'],
        ),
    );

    /**
     * @var array list of b_iblock_element
     */
    $iblocks = \Bitrix\Iblock\IBlockTable::getList($args)->FetchAll();

    $iblocksList = array();
    foreach ($iblocks as $iblock)
    {
        $iblocksList[ $iblock['ID'] ] = $iblock['NAME'];
    }

    $arComponentParameters["PARAMETERS"]["IBLOCK_ID"] = array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => "ID инфоблока",
        "REFRESH" => "Y",
        "TYPE" => "LIST",
        "VALUES" => $iblocksList,
    );

    $eAllFields = array_merge(array('' => 'Пусто (Не указывать)'), $allFields);

    $arComponentParameters["PARAMETERS"]["ELEMENT_TITLE"] = array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => "Заголовок записи",
        "TYPE" => "LIST",
        "VALUES" => array_merge(array('' => 'Новая запись от #DATE#'), $allFields),
    );

    $arComponentParameters["PARAMETERS"]["ELEMENT_PREVIEW_TEXT"] = array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => "Описание для анонса",
        "TYPE" => "LIST",
        "VALUES" => $eAllFields,
    );

    $arComponentParameters["PARAMETERS"]["DETAIL_TEXT"] = array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => "Детальное описание",
        "TYPE" => "LIST",
        "VALUES" => $eAllFields,
    );

    $args = array(
        'select' => array('ID', 'NAME'),
        'filter' => array(
            'IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'],
        ),
    );

    $properties = \Bitrix\Iblock\PropertyTable::getList($args)->FetchAll();

    foreach ($properties as $property)
    {
        $arComponentParameters["PARAMETERS"]["PROPERTY_" . $property['ID']] = array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => 'Свойство ' . $property['NAME'],
            "TYPE" => "LIST",
            "VALUES" => $eAllFields,
        );
    }
}

return $arComponentParameters;
