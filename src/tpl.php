<?php
namespace Ksnk\text;

//use Ksnk\project\core\ENGINE;

class tpl {

    protected static $quote=null;

    var $hang, $dec, $numbf, $numb, $thau;

    /**
     * инициализация необходимых запчастей для прописи
     *
     * @return prop
     */
    private function _prop()
    {
        $this->hang = explode("|", "|сто|двести|триста|четыреста|пятьсот|шестьсот|семьсот|восемьсот|девятьсот");
        $this->dec = explode("|", "||двадцать|тридцать|сорок|пятьдесят|шестьдесят|семьдесят|восемьдесят|девяносто");
        $this->numb = explode("|", "|один|два|три|четыре|пять|шесть|семь|восемь|" .
            "девять|десять|одиннадцать|двенадцать|тринадцать|" .
            "четырнадцать|пятнадцать|шестнадцать|семнадцать|" .
            "восемнадцать|девятнадцать");
        $this->numbf = explode("|", "|одна|две");
        $this->thau = $this->prep("+тысяч|а|и|", "миллион||а|ов", "миллиард||а|ов",
            "триллион||а|ов", "квадриллион||а|ов", "квинтиллион||а|ов",
            "секстиллион||а|ов", "септиллион||а|ов", "октиллион||а|ов",
            "нониллион||а|ов", "дециллион||а|ов"
        );
    }

    /**
     *  изготовление массива из входных параметров
     */
    function prep($ss = ' ')
    {
        $res = array();
        $ss = func_get_args();
        for ($i = 0, $numargs = count($ss); $i < $numargs; $i++) {
            if (is_string($ss[$i])) {
                $bas = explode("|", trim($ss[$i], '+') . "||||", 5);
                $res[$i]['fem'] = ($ss[$i]{0} == '+');
                for ($j = 1; $j < 4; $j++)
                    $res[$i][$j] = " " . $bas[0] . $bas[$j];
            }
        }
        return $res;
    }

    /**
     * выводит прописью число меньше 1000 с подписью,
     * если число отрицательное - оно выводится цифрами, с разбивкой по тысячам
     */
    function sema($n, $s = ' ', $last = false) // $last - обязательно дописывать подпись
    {
        if (!is_array($s)) {
            $s = $this->prep($s);
            $s = $s[0];
        }
        $res = "";

        if ($n < 0) {
            $n = -$n;
            if ($n >= 1000) {
                $res .= $this->sema(-floor($n / 1000)) . " ";
                $n %= 1000;
            }
            $res .= sprintf('%2d', $n);
            while ($n > 20) {
                $n %= 10;
            }
        } else {
            if ($n >= 100) {
                $res .= $this->hang[floor($n / 100)] . " ";
                $n %= 100;
            }
            if ($n >= 20) {
                $res .= $this->dec[floor($n / 10)] . " ";
                $n %= 10;
            }
            if ($n < 3) $res .= !empty($s['fem']) ? $this->numbf[$n] : $this->numb[$n];
            else $res .= $this->numb[$n];
        }

        if ($n == 0) {
            if ($res || $last) $res .= $s[3];
        }
        else if ($n == 1) $res .= $s[1];
        else if ($n < 5) $res .= $s[2];
        else $res .= $s[3];

        return $res;
    }

    /**
     *  вывод денежной суммы прописью с сотыми по русски с соблюдением спряжения
     *  числа от 0 до 10**33
     *  запись может содержать только цифры и 1 точку
     *  число копеек отделено .
     *  значение копеек - первые 2 цифры, причем .5 == .50 , .456==.45
     *
     *  $LL - собственно строка
     *  $valute - индекс денежных подписей
     *  $kop - копейки выводить обязательно, даже если 0. или только в случае не 0!
     */
    function num2str($LL, $valute = FALSE, $kop = FALSE)
    {

        if (!$valute) $valute = $this->prep();

        $mm = explode('.', str_replace(',', '.', $LL), 2);
        if (empty($mm[1])) $mm[1] = 0;
        else {
            if (strlen($mm[1]) < 2) $mm[1] .= '00';
            $mm[1] = intval($mm[1]{0} . $mm[1]{1});
        }
        // вместо $m=str_split(str_repeat(' ',3-strlen($mm[0])%3).$mm[0],3) приходится использовать
        $m = explode(' ', trim(chunk_split(str_repeat('0', 3 - strlen($mm[0]) % 3) . $mm[0], 3, ' ')));
        $res = '';
        for ($i = count($m) - 2, $j = 0; $i >= 0; $i--, $j++) {
            $res .= ' ' . $this->sema(intval($m[$j]), $this->thau[$i]);
        }
        $x = intval($m[$j]);
        if ((!$res) && (!$x))
            $res .= ' 00' . $this->sema(0, $valute[0], true);
        else
            $res .= ' ' . $this->sema($x, $valute[0], true);
        if (isset($valute[1])) {
            if ($mm[1]) {
                $res .= ' ' . $this->sema(-$mm[1], $valute[1], true); // копейки цифрами
            } elseif ($kop)
                $res .= ' 00' . $this->sema(0, $valute[1], true);
        }

        return trim($res);
    }

    /**
     * стандарные конвертеры не умеют месяцы в родительном падеже. Как так ?
     * @param null $daystr
     * @param string $format
     * @return mixed
     */
    static function toRusDate($daystr = null, $format = "j F, Y г.")
    {
        if ($daystr) {
            if (!is_numeric($daystr)) $daystr = strtotime($daystr);
        } else
            $daystr = time();
        $replace = array(
            'january' => 'января',
            'february' => 'февраля',
            'march' => 'марта',
            'april' => 'апреля',
            'may' => 'мая',
            'june' => 'июня',
            'july' => 'июля',
            'august' => 'августа',
            'september' => 'сентября',
            'october' => 'октября',
            'november' => 'ноября',
            'december' => 'декабря',

            'jan' => 'янв',
            'feb' => 'фев',
            'mar' => 'мар',
            'apr' => 'апр',
//        'may'=>'мая', - уже есть
            'jun' => 'июн',
            'jul' => 'июл',
            'aug' => 'авг',
            'sep' => 'сен',
            'oct' => 'окт',
            'nov' => 'ноя',
            'dec' => 'дек',

            'monday' => 'понедельник',
            'tuesday' => 'вторник',
            'wednesday' => 'среда',
            'thursday' => 'четверг',
            'friday' => 'пятница',
            'saturday' => 'суббота',
            'sunday' => 'воскресенье',

            'mon' => 'пнд',
            'teu' => 'втр',
            'wed' => 'срд',
            'thu' => 'чтв',
            'fri' => 'птн',
            'sat' => 'сбт',
            'sun' => 'вск',
        );

        return str_replace(array_keys($replace), array_values($replace),
            mb_strtolower(date($format, $daystr),'UTF-8'));
    }

    /**
     * @param $n
     * @param $one
     * @param $two
     * @param $five
     * @return mixed
     * @example ~(11,'копейка','копейки','копеек');
     */
    function plural($n,$one, $two, $five){
        if (is_array($n))
            $n = count($n);
        $n = $n % 100;
        if ($n > 4 && $n < 21)
            return $five;
        $n = $n % 10;
        if ($n == 1)
            return $one;
        if ($n < 5 && $n > 1)
            return $two;
        return $five;
    }

    /**
     * Шаблоны с минимальной логикий
     * @param $sql
     * @param $param
     * @param $type - text - игнорируем квотацию, insert - sql_insert, все остальное тождественно select
     * @param $quote
     * @return string|string[]|null
     * @example
     * {{Email}}
     * {{Name|уважаемый подписчик}}
     * {{Name|{{Email}}}}
     * {{Name?Dear Username}}
     * {{HasOrders?{{Discount}}}}
     * {{Name?, }}{{Name}}
     * {{Date:d}} - дата с переводом названий месяцев в родительном падеже
     *
     */
    private function _($sql,$param, $type='text', $quote=null){
        $placeholders=[];$cnt=0;$last=0;
        if(is_array($param))$param=(object)$param;
        // этап 1 - заменяем шаблоны - вставки на плейсхолдеры
        if($type=='text' || $type=='utext'){ // игнорируем quote
            $gimmireplace=function ($w) use ($type, $param, &$last) {

                if (property_exists($param,$w[3])) $data=$param->{$w[3]}; else {
                    $w[2]='';
                    $data='';
                }
                if (is_array($data) || is_object($data))
                    return $w[1] .$w[2] . json_encode($data, JSON_UNESCAPED_UNICODE); // сгодится для отладки
                else {
                    if(!empty($w[4])){
                        $x = explode('|', $w[4]);
                        if (count($x) == 4) { // pluralform
                            if ('' !== $w[3] && isset($data)) $last = $data;
                            return $this->plural((int)$last,$x[1],$x[2],$x[3]);
                        }
                    }
                    if($w[4]=='|t'){ // модификатор - время
                        return $w[1] .$w[2] . self::toRusDate($data,'j F Y г. в H:i');
                    } else {
                        $last = $data;
                        return $w[1] .$w[2] . $data;
                    }
                }
            };
        } else {
            $gimmireplace=function ($w) use ($type, $param, $quote) {
                if (property_exists( $param,$w[3]) && is_null($param->{$w[3]})) {
                    if ($w[1] == '=' && $type !== 'insert') {
                        return  ' IS NULL';
                    } else {
                        return  $w[1] .$w[2] . 'NULL';
                    }
                } else if (property_exists($param,$w[3])) {
                    $data=$param->{$w[3]};
                    if (is_array($data))
                        return  $w[1] .$w[2] . $quote(json_encode($data));
                    else {
                        if($w[4]=='|t'){ // модификатор - время
                            if(ctype_digit($data))
                                return $w[1] .$w[2] . $quote(date("Y-m-d H:i:s",$data)) ;
                            return  $w[1] .$w[2] . $quote(date("Y-m-d H:i:s",strtotime($data))) ;
                        } else if($w[4]=='|l'){ // модификатор - like
                            return  $w[1] .$w[2] . '"%' . addCslashes($data, '"\%_') . '%"' ;
                        } else{
                            return  $w[1] .$w[2] . $quote($data);
                        }
                    }
                }
                return  $w[1] . $quote('');
            };
        }
        if($type!='utext')
            $reg=['/(=)?{(\s*)(\w*)\s*(\|[\|\w]*)?}/u','/{\s*(\w+)\s*\?([^}:]+)(?:\:([^}]+))?\s*}/'];
        else
            $reg=['/(=)?{{(\s*)(\w*)\s*(\|[\|\w]*)?}}/u','/{{\s*(\w+)\s*\?([^}:]+)(?:\:([^}]+))?\s*+}}/'];
        $sql = preg_replace_callback(
            $reg[0],function ($w) use ( &$placeholders, &$cnt,&$gimmireplace) {
            if(empty($w[4]))$w[4]=''; // force to create
            $placeholders[]=$gimmireplace($w);
            return '@@'.($cnt++).'@@';
        } , $sql);
        // этап 2 - заменяем логику
        $sql = preg_replace_callback(
            $reg[1],
            function ($w) use ( $param) {
                if (!property_exists($param,$w[1])) {
                    return '';
                } else if(!!$param->{$w[1]}) {
                    return $w[2];
                } else {
                    return isset($w[3])?$w[3]:'';
                }
            }
            , $sql);
        // этап 3 - заменяем обратно плейсхолдеры
        $sql = preg_replace_callback(
            '/@@(\d+)@@/',
            function ($w) use ( &$placeholders) {
                return $placeholders[$w[1]];
            }
            , $sql);

        return $sql;
    }

    /**
     * А вот для нормальной жизни унас есть возможность статического вызова в упрощенной форме
     */

    /**
     * список статических методов
     * @var string[]
     */
    private static $methods = [
        'text' => '_',
        'sql' => '_',
        'utext'=> '_',
        'prop' =>'num2str',
        'pl' =>'plural',
        'rusd' =>'toRusDate'
    ];

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic( $method, array $parameters){
        if (!array_key_exists($method, self::$methods)) {
            throw new \Exception('The ' . $method . ' is not supported.');
        }
        static $me;
        if (!isset($me)) {
            $me = new self();
            $me->_prop();
        }

        // корректируем парамеры по умолчанию
        if($method=='text'){
            $parameters[2]='text';
        } elseif($method=='utext'){
            $parameters[2]='utext';
        } elseif($method=='sql'){
            if(empty($parameters[2])){
                $parameters[2]='select';
            }
            if(empty($parameters[3])){
                if(is_null(self::$quote)) {
                    if (class_exists('\JFactory')) {
                        self::$quote = function ($n) {
                            $db = \JFactory::getDbo();
                            return $db->quote($n);
                        };
                    } else if(class_exists('Ksnk\project\core\ENGINE')) {
                        self::$quote = function ($n) {
                            $db = \Ksnk\project\core\ENGINE::db();
                            return '"'.$db->escape($n).'"';
                        };
                    } else if(class_exists('Ksnk\core\ENGINE')) {
                        self::$quote = function ($n) {
                            $db = \Ksnk\core\ENGINE::db();
                            return '"'.$db->escape($n).'"';
                        };
                    } else {
                        self::$quote = function ($n) {
                            return "'".escapeshellarg($n)."'";
                        };
                    }
                }
                $parameters[3]=self::$quote;
            }
        }

        return call_user_func_array([$me,self::$methods[$method]], $parameters);
    }
}
