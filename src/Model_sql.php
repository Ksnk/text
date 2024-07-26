<?php

namespace Ksnk\text;

/**
 * Пример модификации шаблонизатора, для подготовки SQL выражений. Все переменные-подстановки предварительно квотятся.
 * операция подстановки ={NULL} отдельно обрабатывается, чтобы корректно генерировать конструкции
 * where A IS NULL и SET a = NULL из комбинаций a={a}.
 * Class Model_sql
 * @package Ksnk\text
 */
class Model_sql extends Model_tpl
{

    /**
     * @var callable $quote
     */
    var $sqlmodificator = [],
        // функция квотирования
        $quote = null,
        // признак, что сейчас работаем в режиме sql, чтобы не мешать обычной работе шаблона
        $sql_mod = false;

    function __construct()
    {
        parent::__construct();
        //  добавляем некоторые модификаторы, типичные для моего sql
        $this->implement_sql_Modificator('l', function ($data, $mod_ext, $spaces, $key, $mod) {
            // модификатор готовки данных для оператора like -  x like {data|l|_%}
            if (empty($mod_ext)) $mod_ext = '%%';
            if(strlen($mod_ext)<2)$mod_ext.='  ';
            $pref = '';
            $suf = '';
            if ($mod_ext[0] == '%') $pref = '%';
            if ($mod_ext[1] == '%') $suf = '%';
            return $spaces . '"' . $pref . addCslashes($data, '"\%_') . $suf . '"';
        });
        $timemod = function ($data, $mod_ext, $spaces, $key, $mod, $q) {
            if (!is_numeric($data) && ($x = strtotime($data)) > 0) $data = $x;
            $format = 'Y-m-d H:i:s';
            return $spaces . $q(date($format,$data));
        };
        $this->implement_sql_Modificator('d', $timemod);
        $this->implement_sql_Modificator('t', $timemod);
        //  по умолчанию подстановки делаем так
        $this->implement_sql_Modificator('', function ($data, $mod_ext, $spaces, $key, $mod, $q /*, $quote, $eq */) {
            if (is_null($data)) {
                return 'NULL';
            }
            if(!is_callable($q))
                return  $data;
            return $spaces . $q($data);
        });
    }

    protected function modifyIt($mod, $key, $last, $data, $mod_ext, $spaces,$quote=null)
    {
        // для режима sql проверяем дополнительные модификаторы
        if (isset($this->sqlmodificator[$mod]) && $this->sql_mod) {
            $call = $this->sqlmodificator[$mod];
            return $call('' == $key ? $last : $data, $mod_ext, $spaces, $key, $mod, $quote?:$this->quote);
        } else {
            return parent::modifyIt($mod, $key, $last, $data, $mod_ext, $spaces);
        }
    }

    /**
     * Дополнительная функция шаблона. При ее выполнении шаблон работает в новом режиме.
     * @param $template
     * @param $data
     * @param string $type
     * @param null $quote
     * @return string|string[]|null
     * @throws \Exception
     */
    public function sql($template, $data, $type = '', $quote = null)
    {
        if (empty($type)) {
            $type = 'select';
        }
        if (empty($quote)) {
            if (is_null($this->quote)) {
                if (class_exists('\JFactory')) {
                    $this->quote = function ($n) {
                        $db = \JFactory::getDbo();
                        return $db->quote($n);
                    };
                } else if (class_exists('Ksnk\core\ENGINE')) {
                    $this->quote = function ($n) {
                        $db = \Ksnk\core\ENGINE::db();
                        return '"' . $db->escape($n) . '"';
                    };
                } else {
                    // квотинг для тестирования, не принимайте слишком серьезно, хотя...
                    $this->quote = function ($n) {
                        return escapeshellarg($n);
                    };
                }
            }
            $quote = $this->quote;
        }
        $this->sql_mod = true;
        $result = $this->_($template, $data, $type, $quote);
        $this->sql_mod = false;
        return $result;
    }

    /**
     * Трюк для отдельной обработки подстановки чистого NULL
     * @param $lex
     */
    function appendsimbols(&$lex)
    {
        if (!!$this->sql_mod)
            $lex['='] = ['operator', function ($a) {
                if ($a == 'NULL') return ' IS NULL';
                else return '=' . $a;
            }];
    }

    function implement_sql_Modificator($mod, $callable)
    {
        $this->sqlmodificator[$mod] = $callable;
    }

}
