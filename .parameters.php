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
    }
}

$arComponentParameters = array(
    "GROUPS" => array(
        "BASE",
        "DATA_SOURCE",
        // "VISUAL",
        // "USER_CONSENT",
        // "URL_TEMPLATES",
        // "SEF_MODE",
        // "AJAX_SETTINGS",
        // "CACHE_SETTINGS",
        // "ADDITIONAL_SETTINGS",
    ),
    "PARAMETERS" => array(
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

        "FROM_NAME" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => "Имя администратора",
            "TYPE" => "STRING",
            "DEFAULT" => "Администратор сайта",
        ),
        "SUBJECT" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => "Тема сообщения",
            "TYPE" => "STRING",
            "DEFAULT" => "Сообщение с сайта",
        ),
        "TO" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => "Получатель",
            "TYPE" => "STRING",
            "DEFAULT" => "nikolays93@ya.ru",
        ),
    ),
);

foreach ($allFields as $field_id => $field_name)
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
