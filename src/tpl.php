<?php

namespace Ksnk\text;

use \Exception;

/**
 * Полностью статический класс обертка для текстовых утилит.
 * @method static string prop($LL, $valute = FALSE, $kop = FALSE) - сумма прописью, с подписью с копейками или без
 * @method static string numd($LL, $valute = FALSE, $kop = FALSE) - сумма цифрами, с подписью, с копейками или без
 * @method static string text($template, $data) - текстовая замена, одинарные фигурные скобки
 * @method static string utext($template, $data) - текстовая замена, двойные фигурные скобки
 * @method static string sql($template, $data, $type = '', $quote = null) - подготовленое выражение sql.
 * @method static string pl($n, $one, $two, $five) - окончание числительных (plural form).
 * @method static string rusd($daystr = null, $format = "j F, Y г.") - дата с месяцами - днями недели по русски.
 */
class tpl
{
    private static $quote = null;

    const RUB = "рубл|ь|я|ей;+копе|йка|йки|ек";

    /**
     * список статических методов-синонимов
     * @var string[]
     */
    private static $methods = [
        '_' => '',
        'num2str'=>'prop',
        'toRusDate'=>'rusd', // синоним внешний, иногда у меня в коде встречается tpl::toRusDate
        'mod' => 'implement_text_Modificator'
    ];

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($method, array $parameters)
    {
        static $me;
        if (!isset($me)) {
            $me = new Model_sql();
        }
        if (!array_key_exists($method, self::$methods)) {
            if (method_exists($me, $method)) {
                self::$methods[$method] = $method;
            } else {
                throw new Exception('The ' . $method . ' is not supported.');
            }
        }

        // корректируем парамеры по умолчанию
        if ($method == '_') {
            if (is_subclass_of($parameters[0], get_class($me) /*'Ksnk\text\Model_tpl'*/)) {
                $me = $parameters[0];
                return;
            }
            throw new Exception('Parameter not a subclass');
        }
        return call_user_func_array([$me, self::$methods[$method]], $parameters);
    }
}
