<?php

namespace Ksnk\text;

//use Ksnk\project\core\ENGINE;
use \Exception;

class Model_tpl
{

    var $hang, $dec, $numbf, $numb, $thau;

    var $modificator = [], $sqlmodificator = [];

    private $tags = [], $lastlex = [];

    function __construct()
    {
        $this->_prop();
        $timemod = function ($data, $mod_ext, $spaces, $key, $mod) {
            if (($x = strtotime($data)) > 0) $data = $x;
            if (!empty($mod_ext))
                $format = $mod_ext;
            else if ($mod == 't') {
                $format = 'j F Y г. в H:i';
            } else {
                if (date('Y') == date('Y', $data)) {
                    $format = 'j F';
                } else {
                    $format = 'j F Y г';
                }
            }
            return $spaces . $this->rusd($data, $format);
        };
        $this->implement_text_Modificator('d', $timemod);
        $this->implement_text_Modificator('t', $timemod);
        $this->implement_text_Modificator('', function ($data, $mod_ext, $spaces, $key, $mod) {
            if (is_null($data) || '' === $data)
                return '';
            return $spaces . $data;
        });
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
     * @param $n
     * @param string $podpis
     * @param bool $last
     * @return string
     */
    function numd($n, $podpis = ' ', $last = false) // $last - обязательно дописывать подпись
    {
        if (!empty($podpis) && is_string($podpis)) {
            $podpis = call_user_func_array([$this, 'prep'], explode(';', $podpis));
            $podpis = $podpis[0];
        }
        if (!$podpis) $podpis = $this->prep();
        if (!is_array($podpis)) {
            $podpis = $this->prep($podpis);
            $podpis = $podpis[0];
        }
        $res = "";

        if ($n < 0) {
            $n = -$n;
            if ($n >= 1000) {
                $res .= $this->numd(-floor($n / 1000)) . " ";
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
            if ($n < 3) $res .= !empty($podpis['fem']) ? $this->numbf[$n] : $this->numb[$n];
            else $res .= $this->numb[$n];
        }

        if ($n == 0) {
            if ($res || $last) $res .= $podpis[3];
        } else if ($n == 1) $res .= $podpis[1];
        else if ($n < 5) $res .= $podpis[2];
        else $res .= $podpis[3];

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
    function prop($LL, $podpis = FALSE, $_kop = FALSE)
    {

        if (!empty($podpis) && is_string($podpis)) {
            $podpis = call_user_func_array([$this, 'prep'], explode(';', $podpis));
        }
        if (!$podpis) $podpis = $this->prep();

        // Разбираемся, что нужно выводить
        if (is_numeric($LL)) {
            // счастье!
            $rub = floor($LL);
            $kop = floor(($LL - $rub) * 100);
        } else {
            // Это строка - иногда бухи путают точку и запятую. Иногда отделяют точками тысячи.
            // Но если сумма с копейками, их всегда 2 цифры!
            $x = explode('.', str_replace(',', '.', $LL));
            $kop = array_pop($x);
            if (strlen($kop) !== 2) {
                // это не копейки (
                array_push($x, $kop);
                $kop = 0;
            }
            $rub = join('', $x);
        }
        // режем число по 3 цифры
        // вместо $m=str_split(str_repeat(' ',3-strlen($mm[0])%3).$mm[0],3) приходится использовать
        $m = explode(' ', trim(chunk_split(str_repeat('0', 3 - strlen($rub) % 3) . $rub, 3, ' ')));
        $res = '';
        for ($i = count($m) - 2, $j = 0; $i >= 0; $i--, $j++) {
            $res .= ' ' . $this->numd(intval($m[$j]), $this->thau[$i]);
        }
        $x = intval($m[$j]);
        if ((!$res) && (!$x))
            $res .= ' 00' . $this->numd(0, $podpis[0], true);
        else
            $res .= ' ' . $this->numd($x, $podpis[0], true);
        if (isset($podpis[1])) {
            if ($kop) {
                $res .= ' ' . $this->numd(-$kop, $podpis[1], true); // копейки цифрами
            } elseif ($_kop)
                $res .= ' 00' . $this->numd(0, $podpis[1], true);
        }

        return trim($res);
    }

    /**
     * стандарные конвертеры не умеют месяцы в родительном падеже. Как так ?
     * @param null $daystr
     * @param string $format
     * @return mixed
     */
    function rusd($daystr = null, $format = "j F, Y г.")
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
            mb_strtolower(date($format, $daystr), 'UTF-8'));
    }

    /**
     * @param $n
     * @param $one
     * @param $two
     * @param $five
     * @return mixed
     * @example ~(11,'копейка','копейки','копеек');
     */
    function pl($n, $one, $two, $five)
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

    private function a2reg(&$a)
    {
        $r = [];
        uksort($a, function ($a, $b) {
            if (strlen($a) > strlen($b)) return -1;
            if (strlen($a) == strlen($b)) return 0;
            return 1;
        });
        foreach ($a as $k => $v) {
            $r[] = preg_quote($k);
        }
        return '(' . implode(')|(', $r) . ')';
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

        $eatnext = function ($lexems) use (&$sql, &$getopen, &$getnext, &$param, &$last, $quote, $type) {
            $lex = [];
            $lexems['\\'] = 'escaped';
            $lexems['\{'] = 'escaped';
            $lexems['\}'] = 'escaped';
            $lexems['\?'] = 'escaped';
            $lexems['\:'] = 'escaped';
            if (empty($sql)) return [['', 'EOF']];
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

        $repl = function ($eq, $spaces, $data, $key, $quote, $mod = '') use (&$last) {
            // замена
            if (is_array($data) || is_object($data)) {
                $data = json_encode($data, JSON_UNESCAPED_UNICODE); // сгодится для отладки
            } elseif (is_numeric($data)) {
                $last = $data;
            }
            if (!empty($mod)) {
                $x = explode('|', $mod);
                if (count($x) == 4) { // pluralform
                    return $eq . $key . $this->pl((int)$last, $x[1], $x[2], $x[3]);
                }
                $mod = trim($x[1]);
                array_shift($x);
                array_shift($x);
                $mod_ext = join('|', $x);
            }
            if (isset($this->sqlmodificator[$mod]) && !!$quote) {
                $call = $this->sqlmodificator[$mod];
                return $call('' == $key ? $last : $data, $mod_ext, $spaces, $key, $mod, $quote, $eq);
            } elseif (isset($this->modificator[$mod])) {
                $call = $this->modificator[$mod];
                return $eq . $call('' == $key ? $last : $data, $mod_ext, $spaces, $key, $mod);
            }
            return $eq . $spaces . $data;
        };

        // лексемы
        $last = '';
        $getopen = function ($eq = '') use (&$eatnext, &$getopen, &$getplain, &$param, &$last, $quote, $type, $repl) {
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
                $next = array_shift($lex);
            } while (true);
            $key = trim($key);
            if (property_exists($param, $key)) $data = $param->{$key};
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
                    return $eq . $true;
                } else return $eq . $false;
            } elseif ($next[1] == 'mod') {
                $x = [];
                $x[$this->tags[1]] = 'close';
                $mod = $next[0] . $getplain($x);
                if ($this->lastlex[1] != 'close') {
                    throw new Exception('unclosed tag'); // незакрытый тег
                }
            }

            return $repl($eq, $spaces, $data, $key, $quote, $mod);
        };

        $getplain = function ($stopat = []) use (&$getopen, &$eatnext) {
            $x = $stopat;
            $x[$this->tags[0]] = 'open';
            $x['=' . $this->tags[0]] = 'eqopen';
            $lex = $eatnext($x);
            if (empty($lex)) throw new Exception('wtf?');
            $res = '';
            while ($next = array_shift($lex)) {
                if ($next[1] == 'EOF') break;
                elseif ($next[1] == 'spaces') $res .= $next[0];
                elseif ($next[1] == 'plain') $res .= $next[0];
                elseif ($next[1] == 'escaped') $res .= stripcslashes($next[0]);
                elseif ($next[1] == 'open') $res .= $getopen();
                elseif ($next[1] == 'eqopen') $res .= $getopen('=');
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

    /**
     * А вот для нормальной жизни у нас есть возможность статического вызова в упрощенной форме
     */

    function implement_text_Modificator($mod, $callable)
    {
        $this->modificator[$mod] = $callable;
    }

    function implement_sql_Modificator($mod, $callable)
    {
        $this->sqlmodificator[$mod] = $callable;
    }

}
