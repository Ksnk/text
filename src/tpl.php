<?php
namespace Ksnk\text;

//use Ksnk\project\core\ENGINE;
use \Exception;

/**
 * @method static string prop($LL, $valute = FALSE, $kop = FALSE) - сумма прописью, с копейками или без
 * @method static string text($template, $data) - текстовая замена, одинарные фигурные скобки
 * @method static string utext($template, $data) - текстовая замена, двойные фигурные скобки
 * @method static string sql($template, $data,$type='',$quote=null) - подготовленое выражение sql.
 * @method static string pl($n,$one, $two, $five) - окончание числительных (plural form).
 * @method static string rusd($daystr = null, $format = "j F, Y г.") - дата с месяцами - днями недели по русски.
 */
class tpl {

    protected static $quote=null;

    var $hang, $dec, $numbf, $numb, $thau;

    const RUB="рубл|ь|я|ей;+копе|йка|йки|ек";

    private $tags=[], $lastlex=[];

    /**
     * инициализация необходимых запчастей для прописи
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

        if(!empty($valute) && is_string($valute)){
            $valute=call_user_func_array([$this,'prep'],explode(';',$valute));
        }
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

    private function a2reg(&$a){
        $r=[];
        uksort($a,function($a,$b){
            if(strlen($a)>strlen($b)) return -1;
            if(strlen($a)==strlen($b)) return 0;
            return 1;
        });
        foreach($a as $k=>$v) {
            $r[]=preg_quote($k);
        }
        return '('.implode(')|(',$r).')';
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
     * Добрый день, {{Name?:уважаемый подписчик}} - ДОбрый день, Олег|Добрый день, уважаемый подписчик
     * {{Name|{{Email}}}}
     * {{Name?Dear Username}}
     * {{HasOrders?{{Discount}}}}
     * {{Name?, }}{{Name}}
     * {{Date|d}} - дата с переводом названий месяцев в родительном падеже
     *
     */
    private function _($sql,$param, $type='text', $quote=null){
        if(is_array($param)) $param=(object)$param;

        $eatnext=function($lexems)use(&$sql,&$getopen,&$getnext,&$param,&$last,$quote,$type){
            $lex=[];
            $lexems['\\']='escaped';$lexems['\{']='escaped';$lexems['\}']='escaped';$lexems['\?']='escaped';$lexems['\:']='escaped';
            if(empty($sql)) return [['','EOF']];
            if(preg_match('~^(\s*)(.*?)(?:'.$this->a2reg($lexems).')(.*)$~us',$sql,$m)){
                if(''!=$m[1]){
                    $lex[]=[$m[1],'spaces'];
                }
                if(''!=$m[2]){
                    $lex[]=[$m[2],'plain'];
                }
                $idx=2;
                foreach($lexems as $k=>$v){
                    if(!empty($m[++$idx])){
                        $lex[]=[$m[$idx],$v];
                    }
                }
                $sql=$m[$idx+1];
            } else {
                $lex[]=[$sql,'plain'];
                $sql='';
            }
            return $lex;
        };

        $repl=function($eq,$spaces,$data,$quote,$mod='')use(&$last){
            // замена
            if (is_array($data) || is_object($data)) {
                if (!!$quote) {
                    return $eq . $spaces . $quote(json_encode($data, JSON_UNESCAPED_UNICODE));
                } else {
                    return $eq . $spaces . json_encode($data, JSON_UNESCAPED_UNICODE); // сгодится для отладки
                }
            } else {
                if (!empty($mod)) {
                    $x = explode('|', $mod);
                    if (count($x) == 4) { // pluralform
                        if ('' !== $spaces && isset($data)) $last = $data;
                        return $eq . $this->plural((int)$last, $x[1], $x[2], $x[3]);
                    }
                }
                if ($mod == '|d') { // модификатор - время
                    if (($x = strtotime($data)) > 0) $data = $x;
                    if (date('Y') == date('Y', $data)) {
                        return $eq . $spaces . self::toRusDate($data, 'j F');
                    } else
                        return $eq . $spaces . self::toRusDate($data, 'j F Y г');
                } elseif ($mod == '|t') { // модификатор - время
                    if (!!$quote) {
                        if (ctype_digit($data))
                            return $eq . $spaces . $quote(date("Y-m-d H:i:s", $data));
                        return $eq . $spaces . $quote(date('Y-m-d H:i:s', strtotime($data)));
                    } else {
                        return $eq . $spaces . self::toRusDate($data, 'j F Y г. в H:i');
                    }
                } elseif ($mod == '|l' && !!$quote) {
                    return $eq . $spaces . '"%' . addCslashes($data, '"\%_') . '%"';
                } elseif (is_null($data) || '' == $data) {
                    if (!!$quote) {
                        if (is_null($data)) {
                            if ($eq == '=') {
                                return ' IS NULL';
                            }
                            return $eq . 'NULL';
                        }
                        return $eq . $quote($data);
                    }
                    $last = 0;
                    return '';
                } else {
                    $last = (int)$data;
                    if (!!$quote) {
                        return $eq . $spaces . $quote($data);
                    } else
                        return $eq . $spaces . $data;
                }
            }
        };

        // лексемы
        $last='';
        $getopen=function($eq='')use(&$eatnext,&$getopen,&$getplain,&$param,&$last,$quote,$type,$repl){
            // первый элемент либо условие, либо замена
            $x=['?'=>'cond','|'=>'mod']; $x[$this->tags[1]]='close';
            $lex=$eatnext($x);
            if(count($lex)==0) throw new Exception('wtf?');
            // получаем просвет
            $spaces='';
            while($next=array_shift($lex)){
                if($next[1]=='spaces') $spaces.=$next[0];
                else break;
            };
            // получаем ключ замены
            $key='';$data='';
            do {
                if ($next[1] == 'escaped') $key .=stripcslashes($next[0]);
                elseif ($next[1] == 'plain') $key .=$next[0];
                else break;
                $next=array_shift($lex);
            } while(true);
            $key=trim($key);
            if (property_exists($param, $key)) $data = $param->{$key};
            $mod='';
            // логика или замена
            if(empty($next) || $next[1]=='EOF') {
                throw new Exception('unclosed tag'); // тег не закрыт
            } elseif($next[1]=='open') {
                throw new Exception('misplaced open tag '); // открытие тега не на том месте
            } elseif($next[1]=='cond') {
                // ?:
                $x=[];
                $x[$this->tags[1]]='close';$x[':']='false';
                $true=$getplain($x);
                if($true==='') $true=$data;
                if($this->lastlex[1]=='false'){
                    $x=[]; $x[$this->tags[1]]='close';
                    $false=$getplain($x);
                } else {
                    $false='';
                }
                if(!!$quote){
                    if (!empty($data)) {
                        return $eq . $quote($true); // todo: =NULL
                    } else return $eq . $quote($false);
                } else {
                    if (!empty($data)) {
                        return $eq .  $true;
                    } else return $eq . $false;
                }
            } elseif($next[1]=='mod'){
                $x=[]; $x[$this->tags[1]]='close';
                $mod=$next[0].$getplain($x);
                if($this->lastlex[1]!='close'){
                    throw new Exception('wtf?'); // незакрытый тег
                }
            }

            return $repl($eq,$spaces,$data,$quote,$mod);

        };

        $getplain=function($stopat=[])use(&$getopen,&$eatnext){
            $x=$stopat;
            $x[$this->tags[0]]='open';$x['='.$this->tags[0]]='eqopen';
            $lex=$eatnext($x);
            if(empty($lex)) throw new Exception('wtf?');
            $res='';
            while($next=array_shift($lex)){
                if($next[1]=='EOF') break;
                elseif($next[1]=='spaces') $res.=$next[0];
                elseif($next[1]=='plain') $res.=$next[0];
                elseif($next[1]=='escaped') $res.=stripcslashes($next[0]);
                elseif($next[1]=='open') $res.=$getopen();
                elseif($next[1]=='eqopen') $res.=$getopen('=');
                else {
                    $this->lastlex=$next;
                    break;
                }
                if(empty($lex)){
                    $lex=$eatnext($x);
                }
            };
            return $res;
        };

        if($type=='utext') {
            $this->tags=['{{','}}'];
        } else {
            $this->tags=['{','}'];
        }

        return $getplain();
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
            throw new Exception('The ' . $method . ' is not supported.');
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
                            return escapeshellarg($n);
                        };
                    }
                }
                $parameters[3]=self::$quote;
            }
        }

        return call_user_func_array([$me,self::$methods[$method]], $parameters);
    }
}
