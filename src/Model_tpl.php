<?php

namespace Ksnk\text;

//use Ksnk\project\core\ENGINE;
use \Exception;

class Model_tpl
{

    var $hang, $dec, $numbf, $numb, $thau;

    var $modificator = [];

    protected $tags = [], $lastlex = [];

    function __construct()
    {
        $this->_prop();
        $timemod = function ($data, $mod_ext, $spaces, $key, $mod) {
            if (!is_numeric($data) && ($x = strtotime($data)) > 0) $data = $x;
            if (!empty($mod_ext))
                $format = $mod_ext;
            else if ($mod == 't') {
                $format = 'j F Y г. в H:i';
            } else {
                if (empty($data)) $data = 0;
                if (date('Y') == date('Y', $data)) {
                    $format = 'j F';
                } else {
                    $format = 'j F Y г';
                }
            }
            return $spaces . $this->rusd($data, $format);
        };
        // single quote
        $this->implement_text_Modificator('q', function ($data, $mod_ext, $spaces, $key, $mod) {
            if (is_null($data) || '' === $data)
                return "''";
            return "'" . $spaces . preg_replace(
                    ["/'/", '/\r/'],
                    ["\\'", ''], $data) . "'";
        });
        // double quote
        $this->implement_text_Modificator('qq', function ($data, $mod_ext, $spaces, $key, $mod) {
            if (is_null($data) || '' === $data)
                return '""';
            return '"' . $spaces . preg_replace(
                    ['/"/', '/\n/', '/\r/'],
                    ['\\"', ' ', ''], $data) . '"';
        });
        $this->implement_text_Modificator('d', $timemod);
        $this->implement_text_Modificator('t', $timemod);
        $this->implement_text_Modificator('e', function ($data, $mod_ext, $spaces, $key, $mod) {
            if (is_null($data) || '' === $data)
                return '';
            return $spaces . htmlspecialchars($data);
        });
        $this->implement_text_Modificator('', function ($data, $mod_ext, $spaces, $key, $mod) {
            if (is_null($data) || '' === $data)
                return '';
            return $spaces . $data;
        });
    }

    /**
     * парсинг конструкций .5k 1K и т.д.
     * @param $str
     * @return float|int
     */
    function parseKMG($str)
    {
        if (preg_match('/^(.*)(?:([кk])|([мm])|([гg]))$/iu', $str, $m)) {
            if (!empty($m[2])) return 1024 * $m[1];
            if (!empty($m[3])) return 1024 * 1024 * $m[1];
            if (!empty($m[4])) return 1024 * 1024 * 1024 * $m[1];
        }
        return 1 * $str;
    }

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
     * @param string $ss
     * @return array
     */
    private function prep($ss = ' ')
    {
        $res = array();
        $ss = func_get_args();
        for ($i = 0, $numargs = count($ss); $i < $numargs; $i++) {
            if (is_string($ss[$i])) {
                if (preg_match('~^(\+)?([^\|]*)(?:\|([^\|]*))(?:\|([^\|]*))(?:\|([^\|]*))$~u', $ss[$i], $mm)) {
                    $res[$i] = ['fem' => $mm[1] != ''];
                    for ($j = 1; $j < 4; $j++) $res[$i][$j] = ' ' . $mm[2] . $mm[2 + $j];
                };
            }
        }
        return $res;
    }

    /**
     * выводит прописью число меньше 1000 с подписью,
     * если число отрицательное - оно выводится цифрами, с разбивкой по тысячам
     * @param $n
     * @param string $podpis
     * @param bool $last
     * @return string
     */
    public function numd($n, $podpis = ' ', $last = false) // $last - обязательно дописывать подпись
    {
        if (!empty($podpis) && is_string($podpis)) {
            $podpis = call_user_func_array([$this, 'prep'], explode(';', $podpis));
            $podpis = $podpis[0];
        }
        if (!is_array($podpis)) {
            $podpis = $this->prep($podpis);
            $podpis = $podpis[0];
        }
        $res = "";

        if ($n < 0) {
            $n = -$n;
            $res = rtrim(number_format($n, 0, '', ' ') . ' ');
        } else {
            if ($n == '0' && $last) $res .= 'ноль';
            if ($n >= 100) {
                $res .= $this->hang[floor($n / 100)] . " ";
                $n %= 100;
            }
            if ($n >= 20) {
                $res .= $this->dec[floor($n / 10)] . " ";
                $n %= 10;
            }
            if ($n < 3) $res .= !empty($podpis['fem']) ? $this->numbf[$n] : $this->numb[$n];
            else $res .= $this->numb[$n];
        }

        $res .= $this->pl($n, $podpis[1], $podpis[2], $podpis[3]);

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
     *  $podpis - индекс денежных подписей
     *  $kop - копейки выводить обязательно, даже если 0. или только в случае не 0!
     * @param $LL
     * @param bool $podpis
     * @param bool $_kop
     * @return string
     */
    public function prop($LL, $podpis = FALSE, $_kop = FALSE)
    {

        if (!empty($podpis) && is_string($podpis)) {
            $podpis = call_user_func_array([$this, 'prep'], explode(';', $podpis));
        }
        if (!$podpis) $podpis = $this->prep();

        // Разбираемся, что нужно выводить
        if (is_numeric($LL)) {
            // счастье!
            $rub = '' . floor($LL);
            $kop = '' . floor(($LL - $rub) * 100);
        } else {
            // Это строка - иногда бухи путают точку и запятую. Иногда отделяют точками тысячи.
            // Но если сумма с копейками, их всегда 2 цифры c конца!
            $x = explode('.', str_replace(',', '.', $LL));
            $kop = array_pop($x);
            if (strlen($kop) !== 2) {
                // это не копейки (
                array_push($x, $kop);
                $kop = 0;
            }
            $rub = join('', $x);
        }
        // режем число по 3 цифры. Вот такой я загадочный, да
        $res = [];
        $trio = '';
        $p = $podpis[0];
        $idx = 0;
        for ($i = strlen($rub) - 1; $i >= 0; $i--) {
            $trio = $rub[$i] . $trio;
            if (strlen($trio) == 3) {
                array_unshift($res, $this->numd(intval($trio), $p, $i == 0));
                $p = $this->thau[$idx++];
                $trio = '';
            }
        }
        if ($trio !== '') {
            array_unshift($res, $this->numd(intval($trio), $p, $i <= 0));
        }
        $res = implode(' ', $res);
        if (isset($podpis[1])) {
            if ($kop) {
                $res .= ' ' . sprintf("%02d", $kop) . $this->pl($kop, $podpis[1][1], $podpis[1][2], $podpis[1][3]); // копейки цифрами
            } elseif ($_kop)
                $res .= ' 00' . $podpis[1][3];
        }

        return trim($res);
    }

    /**
     * стандарные конвертеры не умеют месяцы в родительном падеже. Как так ?
     * @param null $daystr
     * @param string $format
     * @return mixed
     */
    public function rusd($daystr = null, $format = "j F, Y г.")
    {
        if (!!$daystr) {
            if (!is_numeric($daystr) && ($x = strtotime($daystr)) > 0) $daystr = $x;
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
// ну и чтоб 2 раза не вставать - переведем все остальное
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
        return str_ireplace(array_keys($replace), array_values($replace), date($format, $daystr));
    }

    /**
     * @param $n
     * @param $one
     * @param $two
     * @param $five
     * @return mixed
     * @example ~(11,'копейка','копейки','копеек');
     */
    public function pl($n, $one, $two, $five)
    {
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
     * подготовка регулярки и массива лексем для парсинга.
     * функция серьезно тормозит в профайлере, из за uksort.
     * статическое кэширование позволило ускорить тест бенчмарка в 3 раза.
     * @param $a
     * @return mixed
     */
    private function a2reg(&$a)
    {
        static $cache = [];
        $key = count($a);
        foreach ($a as $k => $v) {
            if ($v != 'escaped')
                $key .= $k;
        }
        if (!isset($cache[$key])) {
            $r = [];
            $a['\\'] = 'escaped';
            $a['\{'] = 'escaped';
            $a['\}'] = 'escaped';
            $a['\?'] = 'escaped';
            $a['\|'] = 'escaped';
            $a['\:'] = 'escaped';

            uksort($a, function ($a, $b) {
                if (strlen($a) > strlen($b)) return -1;
                if (strlen($a) == strlen($b)) return 0;
                return 1;
            });
            foreach ($a as $k => $v) {
                $r[] = preg_quote($k);
            }
            $cache[$key] = ['(' . implode(')|(', $r) . ')', $a];
        }
        $a = $cache[$key][1];
        return $cache[$key][0];
    }

    public function text($sql, $param)
    {
        return $this->_($sql, $param, 'text');
    }

    public function utext($sql, $param)
    {
        return $this->_($sql, $param, 'utext');
    }

    public function translit($text)
    {
        $ar_latin = array('a', 'b', 'v', 'g', 'd', 'e', 'jo', 'zh', 'z', 'i', 'j', 'k',
            'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'shh',
            '', 'y', '', 'je', 'ju', 'ja', 'je', 'i');
        $text = trim(str_replace(array('а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к',
            'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ',
            'ъ', 'ы', 'ь', 'э', 'ю', 'я', 'є', 'ї'),
            $ar_latin, $text));
        $text = trim(str_replace(array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К',
                'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ',
                'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'Є', 'Ї')
            , $ar_latin, $text));
        return $text;
    }

    /**
     * Внутренняя функция - поиск и применение модификатора. Вынесена в класс
     * для возможности переопределить в наследнике
     * @param $mod
     * @param $key
     * @param $last
     * @param $data
     * @param $mod_ext
     * @param $spaces
     * @return string
     */
    protected function modifyIt($mod, $key, $last, $data, $mod_ext, $spaces)
    {
        if (isset($this->modificator[$mod])) {
            $call = $this->modificator[$mod];
            return $call('' == $key ? $last : $data, $mod_ext, $spaces, $key, $mod);
        }
        return $spaces . $data;
    }

    /**
     * Дополнительные символы для парсинга шаблона
     * @param $lex
     */
    protected function appendsimbols(&$lex)
    {

    }

    /**
     * Шаблоны с минимальной логикий
     * @param $sql
     * @param $param
     * @param string $type - text - игнорируем квотацию, insert - sql_insert, все остальное тождественно select
     * @param $quote
     * @return string|string[]|null
     * @throws Exception
     * @example
     * {{Email}}
     * Добрый день, {{Name?:уважаемый подписчик}} - ДОбрый день, Олег|Добрый день, уважаемый подписчик
     * {{Name|{{Email}}}}
     * {{Name?Dear Username}}
     * {{HasOrders?{{Discount}}}}
     * {{Name?, }}{{Name}}
     * {{Date|d}} - дата с переводом названий месяцев в родительном падеже
     */
    public function _($sql, $param, $type = 'text', $quote = null)
    {
        if (is_array($param)) $param = (object)$param;

        /**
         * Разрушительный парсинг шаблона
         * @param $lexems
         * @return array|\string[][]
         */
        $eatnext = function ($lexems) use (&$sql, &$getopen, &$getnext, &$param, &$last, $type) {
            if (empty($sql)) return [['', 'EOF']];
            $lex = [];
            if (preg_match('~^(\s*)(.*?)(?:' . $this->a2reg($lexems) . ')(.*)$~us', $sql, $m)) {
                if ('' != $m[1]) {
                    $lex[] = [$m[1], 'spaces'];
                }
                if ('' != $m[2]) {
                    $lex[] = [$m[2], 'plain'];
                }
                $idx = 2;
                foreach ($lexems as $k => $v) {
                    if (!empty($m[++$idx])) {
                        if (is_array($v))
                            $lex[] = [$m[$idx], $v[0], $v[1]];
                        else
                            $lex[] = [$m[$idx], $v];
                    }
                }
                $sql = $m[$idx + 1];
            } else {
                $lex[] = [$sql, 'plain'];
                $sql = '';
            }
            return $lex;
        };

        $repl = function ($spaces, $data, $key, $quote, $mod = '') use (&$last, &$param) {
            // замена
            if (is_array($data) || is_object($data)) {
                $data = json_encode($data, JSON_UNESCAPED_UNICODE); // сгодится для отладки
            } elseif (is_numeric($data)) {
                $last = $data;
            }
            $mod_ext = '';
            if (!empty($mod)) {
                $x = explode('|', $mod);
                if (count($x) == 4) { // pluralform
                    return (property_exists($param, $key) ? '' : $key) . $this->pl((int)$last, $x[1], $x[2], $x[3]);
                }
                $mod = trim($x[1]);
                array_shift($x);
                array_shift($x);
                $mod_ext = join('|', $x);
            }
            return $this->modifyIt($mod, $key, $last, $data, $mod_ext, $spaces, $quote);// $spaces . $data;
        };

        // лексемы
        $last = '';
        $getopen = function () use (&$eatnext, &$getopen, &$getplain, &$param, &$last, $quote, $type, $repl) {
            // первый элемент либо условие, либо замена
            $x = ['?' => 'cond', '|' => 'mod'];
            $x[$this->tags[1]] = 'close';
            $lex = $eatnext($x);
            if (count($lex) == 0) throw new Exception('wtf?');
            // получаем просвет
            $spaces = '';
            while ($next = array_shift($lex)) {
                if ($next[1] == 'spaces') $spaces .= $next[0];
                else break;
            };
            // получаем ключ замены
            $key = '';
            $data = '';
            do {
                if ($next[1] == 'escaped') $key .= stripcslashes($next[0]);
                elseif ($next[1] == 'plain') $key .= $next[0];
                else break;
                if (empty($lex)) {
                    $lex = $eatnext($x);
                }
                $next = array_shift($lex);
            } while (true);
            $key = trim($key);
            if (property_exists($param, $key)) $data = $param->{$key};
            elseif (property_exists($param, strtolower($key))) $data = $param->{strtolower($key)};
            else {
                $k = trim($key) . '=';
                $stack = [];
                $evaled = false;
                $op = '';
                while (!empty($k) && preg_match('/^(.*?)\s*([\.<>=]+)\s*(.*)$/', $k, $m)) {
                    if (property_exists($param, $m[1])) $stack[] = $param->{$m[1]};
                    else  if (property_exists($param, strtolower($m[1]))) $stack[] = $param->{strtolower($m[1])};
                    else $stack[] = $m[1];
                    if (!empty($op)) {
                        $evaled = true;
                        $b = array_pop($stack);
                        $a = array_pop($stack);
                        if ($op == '.') {
                            if(is_array($a) && array_key_exists($b,$a))
                                $stack[] = $a[$b];
                            else if(is_object($a) && property_exists($a,$b))
                                $stack[] = $a->{$b};
                            else
                                $stack[] = '';
                        } elseif ($op == '<') {
                            $stack[] = $a < $b;
                        } else if ($op == '>') {
                            $stack[] = $a > $b;
                        } else if ($op == '=') {
                            $stack[] = $a == $b;
                        }
                    }
                    $op = $m[2];
                    $k = trim($m[3]);
                }
                if ($evaled && $op == '=' && count($stack) == 1) {
                    $data = array_pop($stack);
                }
            }
            $mod = '';
            // логика или замена
            if (empty($next) || $next[1] == 'EOF') {
                throw new Exception('unclosed tag, meet EOF'); // тег не закрыт
            } elseif ($next[1] == 'open') {
                throw new Exception('misplaced open tag '); // открытие тега не на том месте
            } elseif ($next[1] == 'cond') {
                // ?:
                $x = [];
                $x[$this->tags[1]] = 'close';
                $x[':'] = 'false';
                $true = $getplain($x);
                if ($true === '') $true = $data;
                if ($this->lastlex[1] == 'false') {
                    $x = [];
                    $x[$this->tags[1]] = 'close';
                    $false = $getplain($x);
                } else {
                    $false = '';
                }
                if (!empty($data)) {
                    return $true;
                } else return $false;
            } elseif ($next[1] == 'mod') {
                $x = [];
                $x[$this->tags[1]] = 'close';
                $mod = $next[0] . $getplain($x);
                if ($this->lastlex[1] != 'close') {
                    throw new Exception('unclosed tag'); // незакрытый тег
                }
            }

            return $repl($spaces, $data, $key, $quote, $mod);
        };

        /**
         * @param array $stopat
         * @return string
         */
        $getplain = function ($stopat = []) use (&$getopen, &$getplain, &$eatnext, $quote) {
            $x = $stopat;
            $x[$this->tags[0]] = 'open';
            $this->appendsimbols($x);

            $lex = $eatnext($x);
            if (empty($lex)) throw new Exception('wtf?');
            $res = '';
            while ($next = array_shift($lex)) {
                if ($next[1] == 'EOF') break;
                elseif ($next[1] == 'spaces') $res .= $next[0];
                elseif ($next[1] == 'operator') {
                    $operand = $getplain($stopat); // todo: используется трюк, что целый getplain получится только перед тегом-открытием. Неочевидно
                    $xxx = $next[2];
                    $res .= $xxx($operand);
                } elseif ($next[1] == 'plain') $res .= $next[0];
                elseif ($next[1] == 'escaped') $res .= stripcslashes($next[0]);
                elseif ($next[1] == 'open') $res .= $getopen();
                else {
                    $this->lastlex = $next;
                    break;
                }
                if (empty($lex)) {
                    $lex = $eatnext($x);
                }
            };
            return $res;
        };

        if ($type == 'utext') {
            $this->tags = ['{{', '}}'];
        } else {
            $this->tags = ['{', '}'];
        }

        return $getplain();
    }

    public function implement_text_Modificator($mod, $callable)
    {
        $this->modificator[$mod] = $callable;
    }

}
