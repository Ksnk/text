<?php

namespace Ksnk\text;

//use Ksnk\project\core\ENGINE;
use \Exception;

class Model_sql extends Model_tpl
{

    var  $sqlmodificator = [], $quote=null;

    function __construct()
    {
        parent::__construct();
        $this->implement_sql_Modificator('l', function ($data, $mod_ext, $spaces, $key, $mod) {
            return $spaces . '"%' . addCslashes($data, '"\%_') . '%"';
        });
        $this->implement_sql_Modificator('', function ($data, $mod_ext, $spaces, $key, $mod, $quote, $eq) {
            if (is_null($data)) {
                if ($eq == '=') {
                    return ' IS NULL';
                }
                return $eq . 'NULL';
            }
            return $eq . $spaces . $quote($data);
        });
    }

    public function sql($template, $data, $type = '', $quote = null){
        if(empty($type)){
            $type = 'select';
        }
        if (empty($quote)) {
            if (is_null($this->quote)) {
                if (class_exists('\JFactory')) {
                    $this->quote = function ($n) {
                        $db = \JFactory::getDbo();
                        return $db->quote($n);
                    };
                } else if (class_exists('Ksnk\project\core\ENGINE')) {
                    $this->quote = function ($n) {
                        $db = \Ksnk\project\core\ENGINE::db();
                        return '"' . $db->escape($n) . '"';
                    };
                } else if (class_exists('Ksnk\core\ENGINE')) {
                    $this->quote = function ($n) {
                        $db = \Ksnk\core\ENGINE::db();
                        return '"' . $db->escape($n) . '"';
                    };
                } else {
                    $this->quote = function ($n) {
                        return escapeshellarg($n);
                    };
                }
            }
            $quote = $this->quote;
        }
        return $this->_($template, $data,$type, $quote);
    }

    function implement_sql_Modificator($mod, $callable)
    {
        $this->sqlmodificator[$mod] = $callable;
    }

}
