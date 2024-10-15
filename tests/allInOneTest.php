<?php
/* тырим тесты из тестового набора twig */

include_once '../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Ksnk\text\tpl, Ksnk\text\Model_sql;

error_reporting(E_STRICT | E_ALL | E_NOTICE | E_CORE_WARNING | E_USER_NOTICE | E_USER_WARNING);

class allInOneTest extends TestCase
{

    function testdeep(){
        $tpl='{EXTRA.COLOR? style="color\:{EXTRA.COLOR};"}';
        $data=json_decode('{
    "ID": "377",
    "ENTITY_ID": "STATUS",
    "STATUS_ID": "UC_5T75BS",
    "NAME": "ВЗЯТО В ДП КАДИС",
    "NAME_INIT": "",
    "SORT": "20",
    "SYSTEM": "N",
    "CATEGORY_ID": "0",
    "COLOR": "#10e5fc",
    "SEMANTICS": null,
    "EXTRA": {
        "SEMANTICS": "process",
        "COLOR": "#10e5fc"
    }
}',JSON_OBJECT_AS_ARRAY);
        //$data=['EXTRA'=>['COLOR'=>'#eee']];
        $this->assertEquals(
            ' style="color:#10e5fc;"',
            tpl::text($tpl,$data)
        );
    }

    function testArrayAccessible(){
        $array = new ArrayAccessable(array('a', 'b', 'c', 'd', 'number' => 101));

 /*       $this->assertEquals(
            tpl::text('{number} baran{||a|ov} na pole', ['number'=>101]),
            '101 baran na pole'
        );*/
        $this->assertEquals(
            tpl::text('{number} baran{||a|ov} na pole', $array),
            '101 baran na pole'
        );
     }

    /**
     * вспомогательная функция - utext
     * @param $d
     * @param string $pattern
     * @throws Exception
     */
    private function _test_tpl(&$d, $pattern = '')
    {
        if (!isset($d['data'])) $d['data'] = [];
        if (isset($d['index'])) {
            $result = tpl::utext($d['index'], $d['data']);
        }
        if (!empty($pattern)) {
            $d['pattern'] = $pattern;
        }
        if (!empty($d['pattern'])) {
            $this->assertEquals($d['pattern'], $result);
        } else {
            throw new Exception('wrong parameters');
        }
    }

    function test_0001(){
        $data=['POINT'=>'111', 'CURRENT.POINT'=>'22', 'CURRENT'=>['diff'=>444,'POINT'=>333]];
        $tpl='{POINT}{ CURRENT.POINT}{ CURRENT.diff}';
        $this->assertEquals(
            '111 22 444',
            tpl::text($tpl,$data)
        );
    }

    function test_to_debug()
    {
        //$v=['created'=>'2022/12/11','title'=>'Не удалось найти ролика', 'video'=>'#', 'text'=>''];
        //$this->assertEquals(
        //    ': ,телефон:, 11 декабря выбран ролик - `Не удалось найти ролика`',tpl::text('{subprofile}{profile}{userid}: {first_name}{ last_name},телефон:{phone_number}{phone}, {created|d} выбран ролик {present}- `{title}`',
//array_merge($v)));

        $data=['count'=>'15','total'=>'16'];
        $this->assertEquals(
            '<b>Ваш счет - 15 из 16</b>. Поздравляем с успешным прохождением тестирования! 
Ваш сертификат будет отправлен на почту указанную в Личном кабинете  \ или ваш сертификат находится в вашем ЛК в разделе Обучение (возможно ли создать такую вкладку)
',
            tpl::text(
                "<b>Ваш счет - {count} из {total}</b>. {10<count?Поздравляем с успешным прохождением тестирования! 
Ваш сертификат будет отправлен на почту указанную в Личном кабинете  \ или ваш сертификат находится в вашем ЛК в разделе Обучение (возможно ли создать такую вкладку)
:Вам немного не хватило баллов для сертификата Проф уровня.}",$data)
        );


        $data=['user'=>10];
        $this->assertEquals(
            'one',
            tpl::text('{user>5?one:two}',
                $data,'insert',function($n){return escapeshellarg($n);})
        );
        $this->assertEquals(
            'two',
            tpl::text('{user>10?one:two}',
                $data,'insert',function($n){return escapeshellarg($n);})
        );



        $data=[
            "mailid"=>172894293,
            "email"=>"zulia@mail.ru",
            'name'=>'Иванова Зульфия Фяридовна',
            'timetosend'=>1667374377,
            "action"=>"unisender",
            "messageid"=>172894293

        ];
        $data['data']=$data;
        $this->assertEquals(
            'insert into forms_mail_queue set timetosend="2022-11-02 10:32:57",sended=0,acy_mailid="172894293",address="zulia@mail.ru",param="{ mailid :172894293, email : zulia@mail.ru , name : Иванова Зульфия Фяридовна , timetosend :1667374377, action : unisender , messageid :172894293}"',
            tpl::sql('insert into forms_mail_queue set timetosend={timetosend|t},sended=0,acy_mailid={mailid},address={email},param={data}',
                $data,'insert',function($n){return escapeshellarg($n);})
        );

        $this->assertEquals(
            'А вот какой то текст',
            tpl::text('А вот какой то текст',
                ['user' => null])
        );
        $this->assertEquals(
            'select xxx=NULL,user= NULL,
where user IS NULL',
            tpl::sql('select xxx=NULL{user?, created<now()},user= {user},
where user={user}',
                ['user' => null])
        );

        $data=['number'=>23];
        $this->assertEquals(
            '23 барана на поле!',
            tpl::text('{number} баран{number||а|ов} на поле!', $data));

        $key='Очень странная переменная {1|2}?:';
        $pattern = 'Сумма прописью:{ '.addcslashes($key,'{}?:\|').' }';
        $this->assertEquals(
            'Сумма прописью: А вот!',
            tpl::text($pattern, [$key => 'А вот!'])
        );

        $pattern = 'Сумма прописью:{prop}';
        $cost = 12345678;
        $this->assertEquals(
            'Сумма прописью:12 345 678 копеек',
            tpl::text($pattern, ['prop' => tpl::numd(-$cost,'+копе|йка|йки|ек')])
        );

        $pattern = 'Сумма прописью:{prop}';
        $cost = 12000;
        $this->assertEquals(
            'Сумма прописью:двенадцать тысяч  рублей 00 копеек',
            tpl::text($pattern, ['prop' => tpl::prop($cost,tpl::RUB, true)])
        );

        $cost = '0.5';
        $this->assertEquals(
            'Сумма прописью:ноль рублей 50 копеек',
            tpl::text($pattern, ['prop' => tpl::prop($cost, tpl::RUB, true)])
        );
        // вывод суммы из древней базы бухгалтера
        $cost = '23.456,45';
        $this->assertEquals(
            'Сумма прописью:двадцать три тысячи четыреста пятьдесят шесть рублей 45 копеек',
            tpl::text($pattern, ['prop' => tpl::prop($cost, tpl::RUB, true)])
        );
        $cost = '0,45';
        $this->assertEquals(
            'Сумма прописью:ноль рублей 45 копеек',
            tpl::text($pattern, ['prop' => tpl::prop($cost, tpl::RUB, true)])
        );
        $cost = '0,05';
        $this->assertEquals(
            'Сумма прописью:ноль рублей 05 копеек',
            tpl::text($pattern, ['prop' => tpl::prop($cost, tpl::RUB, true)])
        );
        $this->assertEquals(
            'Символы {коде } используются как границы в коде.',
            tpl::text('Символы \{{code|\}} } используются как границы в {code}.',
                ['code' => 'коде'])
        );

    }

    function test_date()
    {
        $this->assertEquals(
            'Новый год к нам мчиццо 2022.',
            tpl::text('Новый год к нам мчиццо {date|d|Y}.',
                ['date' => '2022-01-01'])
        );

        tpl::mod('x', function ($data, $mod_ext, $spaces) {
            if (($x = strtotime($data)) > 0) $data = $x;
            if (!empty($mod_ext))
                $format = $mod_ext;
            else {
                $format = 'j F Y г. в H:i';
            }
            return $spaces . tpl::rusd($data, $format);
        });
        $this->assertEquals(
            'Новый год к нам мчиццо 2022-12.',
            tpl::text('Новый год к нам мчиццо {date|x|Y-h}.',
                ['date' => '2022-01-01'])
        );
    }

    function testMoney()
    {

        // проверка склонения в женском роде одна-две вместо один-два
        $number = 101;
        $this->assertEquals(
            'сто один баран на поле',
            tpl::text('{prop} на поле', [
                'prop' => tpl::prop($number, "баран||а|ов")])
        );
        $number = 101;
        $this->assertEquals(
            '101 баран на поле',
            tpl::text('{number} баран{||а|ов} на поле', [
                'number' => $number])
        );
        $this->assertEquals(
            '101 баран на поле',
            tpl::text('{number} {баран||а|ов} на поле', [
                'number' => $number])
        );

        $data=['number'=>23];
        $this->assertEquals(
            '23 барана на поле!',
            tpl::text('{number} баран{||а|ов} на поле!', $data));
        $this->assertEquals(
            '23 барана на поле!',
            tpl::text('{number} баран{number||а|ов} на поле!', $data));
        $this->assertEquals(
            '23 барана на поле!',
            tpl::text('{number} {баран||а|ов} на поле!', $data));



        $pattern = 'Сумма прописью:{prop}';
        // вывод суммы из древней базы бухгалтера
        $cost = '23.456,45';
        $this->assertEquals(
            'Сумма прописью:двадцать три тысячи четыреста пятьдесят шесть рублей 45 копеек',
            tpl::text($pattern, ['prop' => tpl::prop($cost, tpl::RUB, true)])
        );
        // приличная, во всех отношениях строка
        $cost = '23451.45';
        $this->assertEquals(
            'Сумма прописью:23 451 рубль',
            tpl::text($pattern, ['prop' => tpl::numd(-$cost, "рубл|ь|я|ей", true)])
        );
        $this->assertEquals(
            'Сумма прописью:двадцать три тысячи четыреста пятьдесят один рубль 45 копеек',
            tpl::text($pattern, ['prop' => tpl::prop($cost, tpl::RUB, true)])
        );
        // вывод суммы с обязательными копейками -00 коп
        $cost = 23451;
        $this->assertEquals(
            'Сумма прописью:двадцать три тысячи четыреста пятьдесят один рубль 00 копеек',
            tpl::text($pattern, ['prop' => tpl::prop($cost, tpl::RUB, true)])
        );
        // вывод суммы без обязательных копеек
        $cost = 23451;
        $this->assertEquals(
            'Сумма прописью:двадцать три тысячи четыреста пятьдесят один рубль',
            tpl::text($pattern, ['prop' => tpl::prop($cost, tpl::RUB)])
        );
        // проверка склонения в женском роде одна-две вместо один-два
        $number = 101;
        $this->assertEquals(
            'сто один баран на поле',
            tpl::text('{prop} на поле', [
                'prop' => tpl::prop($number, "баран||а|ов")])
        );
        // проверка склонения в женском роде одна-две вместо один-два
        $number = 101;
        $this->assertEquals(
            'сто одна овца на поле',
            tpl::text('{prop} на поле', [
                'prop' => tpl::prop($number, "+овц|а|ы|ец")])
        );
    }


    /*
        function test_incorrect2(){
            $this->expectExceptionMessage('unclosed tag');
            $this->assertEquals(
                'Символы { значение установлено } используются как границы в коде.',
                tpl::text('выводим {{prop}}',
                    ['code' => 'коде','prop' => 'установлено'])
            );
        }
    */

    function test_incorrect()
    {
        $this->expectExceptionMessage('unclosed tag');
        tpl::text('выводим {prop',
            ['code' => 'коде', 'prop' => 'установлено']);

    }

    function test_escaped()
    {
        /*
                $data=[
                    'promo'=>'СКОРОЛЕТО',
                    'first_name'=>'Вагоноуважатый',
                    'discountrub'=>250,
                    'prop'=>tpl::prop(2345,tpl::RUB),
                    'date'=>'now'
                    ];
            echo tpl::text('Глубокоуважаемый{first_name?{ first_name}{ second_name}}!
        При оформлении заказа от {date|d}, Вам предоставлена скидка {discountrub?{discountrub}₽:{discount}%} по промокоду `{promo}`
        Общая сумма заказа составляет: {prop}
        ',$data);
        */
        /*
                echo tpl::rusd(time()).PHP_EOL;
                setlocale(LC_ALL, 'ru_RU', 'ru_RU.UTF-8', 'ru', 'russian');
                echo iconv('cp1251','UTF-8',strftime("%B %d, %Y", time())).PHP_EOL;
        */

        $this->assertEquals(
            'Символы { значение установлено } используются как границы в коде.',
            tpl::text('Символы \{ значение {prop?:не установлено} \} используются как границы в {code}.',
                ['code' => 'коде', 'prop' => 'установлено'])
        );
        $this->assertEquals(
            'Символы \, } и { значение не установлено } используются как границы в коде.',
            tpl::text('Символы \\, } и \{ значение {prop?:не установлено} \} используются как границы в {code}.',
                ['code' => 'коде'])
        );
        $this->assertEquals(
            'Символы { и } используются как границы в коде.',
            tpl::text('Символы \{ и \} используются как границы в {code}.',
                ['code' => 'коде'])
        );
        $this->assertEquals(
            'Символы {коде } используются как границы в коде.',
            tpl::text('Символы \{{code|\}} } используются как границы в {code}.',
                ['code' => 'коде'])
        );
        $stat=[];
        $stat['date']=strtotime('2022-10-10');
        $stat['send']=23;
        $stat['empty']=2;
        $stat['double']=1;
        $stat['waiting']=2;
        $stat['total']=27;
        // полный вывод даты
        $this->assertEquals(
            'Всего строк - 27, поставлено в очередь 23 письма для отправки на 10 октября 2022 г, 2 строки пустые или не email, 1 адрес повторяется, 2 письма уже поставлены в очередь ранее'.PHP_EOL,
            tpl::text('Всего строк - {total}'.
                ', поставлено в очередь {send} пись{|мо|ма|ем} для отправки на {date|d}'.
                '{empty?, {empty} строк{|а|и|} пустые или не email}'.
                '{double?, {double} адрес{||а|ов} повторя{|е|ю|ю}тся}'.
                '{waiting?, {waiting} пись{|мо|ма|ем} уже поставлены в очередь ранее}'.PHP_EOL
                , $stat)
        );
        $stat['date']=strtotime(date('Y').'-10-10');
        // не выводим год

        $this->assertEquals(
            'Всего строк - 27, поставлено в очередь 23 письма для отправки на 10 октября, 2 строки пустые или не email, 1 адрес повторяется, 2 письма уже поставлены в очередь ранее'.PHP_EOL,
            tpl::text('Всего строк - {total}'.
                ', поставлено в очередь {send} пись{|мо|ма|ем} для отправки на {date|d}'.
                '{empty?, {empty} строк{|а|и|} пустые или не email}'.
                '{double?, {double} адрес{||а|ов} повторя{|е|ю|ю}тся}'.
                '{waiting?, {waiting} пись{|мо|ма|ем} уже поставлены в очередь ранее}'.PHP_EOL
                , $stat)
        );

    }

    function test_0()
    {
        $data = [
            'index' => "'Добрый день{{ first_name ?,{{ first_name}}{{ second_name}}}}!'", 'data' => []
        ];
        $data['data']['first_name'] = 'Сергей';
        $this->_test_tpl($data, "'Добрый день, Сергей!'");
        unset($data['data']['first_name']);
        $this->_test_tpl($data, "'Добрый день!'");
        $data['data']['first_name'] = 'Сергей';
        $data['data']['second_name'] = 'Батькович';
        $this->_test_tpl($data, "'Добрый день, Сергей Батькович!'");
    }

    function test_00()
    {
        $data = [
            'index' => "Сейчас{{ hour}} час{{||а|ов}}",
        ];
        $data['data']['hour'] = '0';
        $this->_test_tpl($data, "Сейчас 0 часов");
        $data['data']['hour'] = '11';
        $this->_test_tpl($data, "Сейчас 11 часов");
        $data['data']['hour'] = '14';
        $this->_test_tpl($data, "Сейчас 14 часов");
        $data['data']['hour'] = '17';
        $this->_test_tpl($data, "Сейчас 17 часов");
        $data['data']['hour'] = '1';
        $this->_test_tpl($data, "Сейчас 1 час");
        $data['data']['hour'] = '4';
        $this->_test_tpl($data, "Сейчас 4 часа");
        $data['data']['hour'] = '7';
        $this->_test_tpl($data, "Сейчас 7 часов");
        unset($data['data']);
        $this->_test_tpl($data, "Сейчас часов");
        $data['data']['hour'] = '11';
        $this->_test_tpl($data, "Сейчас 11 часов");
        $data['data']['hour'] = '14';
        $this->_test_tpl($data, "Сейчас 14 часов");
        $data['data']['hour'] = '17';
        $this->_test_tpl($data, "Сейчас 17 часов");
        $data['data']['hour'] = '1';
        $this->_test_tpl($data, "Сейчас 1 час");
        $data['data']['hour'] = '4';
        $this->_test_tpl($data, "Сейчас 4 часа");
        $data['data']['hour'] = '7';
        $this->_test_tpl($data, "Сейчас 7 часов");

    }

    function test_parseKGM(){
        $this->assertEquals(
            1073741824,
            tpl::parseKMG('1g')
        );
        $this->assertEquals(
            512,
            tpl::parseKMG('.5k')
        );
        $this->assertEquals(
            3145728,
            tpl::parseKMG('3m')
        );
        $this->assertEquals(
            3145728,
            tpl::parseKMG('3м')
        );

    }

// so let's test sql

    function test_sql()
    {
        $appendstr = [];
        $appendstr[] = 'user= {user}';
        $appendstr[] = 'text={text}';
        $appendstr[] = 'data={data}';

        $this->assertEquals(
            'insert into table_sclad set  created=NOW(), user_email = "my@email.com", user= NULL,text="just a some text",data="[1,2,3,4,5]"',
            tpl::sql('insert into table_sclad set  created=NOW(), user_email = {own}, ' . implode(',', $appendstr),
                ['text' => 'just a some text',
                    'data' => [1, 2, 3, 4, 5],
                    'own' => 'my@email.com',
                    'user' => null
                ])
        );
        $this->assertEquals(
            'insert into table_sclad set  created=NOW(),
user_email = "0",user= NULL,
text="just a some text",data="[1,2,3,4,5]"
where user IS NULL',
            tpl::sql('insert into table_sclad set  created=NOW(),
user_email = {own},user= {user},
text={text},data={data}
where user={user}',
                ['text' => 'just a some text',
                    'data' => [1, 2, 3, 4, 5],
                    'own' => 0,
                    'user' => null
                ])
        );
        $this->assertEquals(
            'insert into table_sclad set  created=NOW(),
user_email = "0",xxx=NULL,user= NULL,
text="just a some text",data="[1,2,3,4,5]"
where user IS NULL',
            tpl::sql('insert into table_sclad set  created=NOW(),
user_email = {own},xxx=NULL{user?, created<now()},user= {user},
text={text},data={data}
where user={user}',
                ['text' => 'just a some text',
                    'data' => [1, 2, 3, 4, 5],
                    'own' => 0,
                    'user' => null
                ])
        );

        $this->assertEquals(
            'insert into table_sclad set  created=NOW(),
user_email = "0",xxx=NULL,user= NULL,
text="just a some text",data like "[1,2,3,\"\%\_\\\\\"\",5]%",text like "%just a some text%"
where user IS NULL',
            tpl::sql('insert into table_sclad set  created=NOW(),
user_email = {own},xxx=NULL{user?, created<now()},user= {user},
text={text},data like {data|l|_%},text like {text|l}
where user={user}',
                ['text' => 'just a some text',
                    'data' => [1, 2, 3, '%_"', 5],
                    'own' => 0,
                    'user' => null
                ])
        );

        $data=[
            "mailid"=>172894293,
            "email"=>"zulia@mail.ru",
            'name'=>'Иванова Зульфия Фяридовна',
            'timetosend'=>1667374377,
            "action"=>"unisender",
            "messageid"=>172894293

        ];
        $data['data']=$data;
        $this->assertEquals(
            'insert into forms_mail_queue set timetosend="2022-11-02 10:32:57",sended=0,acy_mailid="172894293",address="zulia@mail.ru",param="{ mailid :172894293, email : zulia@mail.ru , name : Иванова Зульфия Фяридовна , timetosend :1667374377, action : unisender , messageid :172894293}"',
            tpl::sql('insert into forms_mail_queue set timetosend={timetosend|t},sended=0,acy_mailid={mailid},address={email},param={data}',
                $data)
        );
        //insert into forms_mail_queue set timetosend=1970-01-01 03:00:00,sended=0,acy_mailid=172894293,address=zulia@mail.ru,param={"mailid":172894293,"email":"zulia@mail.ru","name":"Иванова Зульфия Фяридовна","timetosend":1667374377,"action":"unisender","messageid":172894293}
        //--><!--xxx-debug-func:insert,cls:Ksnk\model\database\xDatabase_parent,file:/var/www/html/express.kadis.org/src/model/mail_Helper.php,line:112 = "QUERY[0.000574]: insert into forms_mail_queue set timetosend=1970-01-01 03:00:00,sended=0,acy_mailid=172894293,address=zulia@mail.ru,param={"mailid":172894293,"email":"zulia@mail.ru","name":"Иванова Зульфия Фяридовна","timetosend":1667374377,"action":"unisender","messageid":172894293}
        $this->assertEquals(
            'insert into forms_mail_queue set timetosend="2022-11-02 10:32:57",sended=0,acy_mailid="172894293",address="zulia@mail.ru",param="{ mailid :172894293, email : zulia@mail.ru , name : Иванова Зульфия Фяридовна , timetosend :1667374377, action : unisender , messageid :172894293}"',
            tpl::sql('insert into forms_mail_queue set timetosend={timetosend|t},sended=0,acy_mailid={mailid},address={email},param={data}',
                $data,'insert',function($n){return escapeshellarg($n);})
        );
        /*, function($n)use(&$db){return "'".$db->escape($n)."'";}*/
    }

    function test_inheritance()
    {
        tpl::_(new ChildTpl);
        $this->assertEquals('xxx',
            tpl::xxx()
        );
    }
    function test_quotes()
    {
        $data = [
            'index' => "Сейчас {{ hour|q}} уже {{ночь|qq}}",
        ];
        $data['data']['hour'] = '0';
        $this->_test_tpl($data, "Сейчас ' 0' уже \"\"");
    }

    function test_Benchmark()
    {
        $pattern='{one}{two}{three}{four}';
        $data=[
            'one'=>1,
            'two'=>2,
            'three'=>3,
            'four'=>4,
        ];
        $variant=[];
        $variant['tpl::text']=function()use($pattern, $data){
            return tpl::text($pattern, $data);
        };

        $variant['strtr']=function()use($pattern, $data){
            $r=[];
            array_walk($data, function($a, $b) use(&$r){ $r['{'.$b.'}']=$a; });
            return strtr($pattern, $r);
        };

        $variant['pregreplace']=function()use($pattern, $data){
            $keys=[];$regs=[];
            array_walk($data, function($a, $b) use(&$keys,&$regs) {
                $regs[] = '~{\s*' . preg_quote($b) . '\s*}~su';
                $keys[] = $a;
            });
            return preg_replace($regs, $keys, $pattern);
        };
        $variant['pregreplace2']=function()use($pattern, $data){
            $keys=[];$regs=[];
            array_walk($data, function($a, $b) use(&$keys,&$regs) {
                $regs[] = '~{\s*' . $b . '\s*}~su';
                $keys[] = $a;
            });
            return preg_replace($regs, $keys, $pattern);
        };


        // tpl::_(new Model_tpl);
        foreach($variant as $v){
            $this->assertEquals('1234',
                $v()
            );
        }
        // OK! start benchmark
        foreach($variant as $name=>$v){
            $start=microtime(true);
            for($i=0;$i<1000;$i++) {
                $x=$v();
            }
            printf('%s - %.03f sec'. PHP_EOL,$name, microtime(true)-$start);
        }

    }

    function testTranslit(){
        $number = 101;
        $this->assertEquals(
            '{number} baran{||a|ov} na pole',
            tpl::translit('{number} баран{||а|ов} на поле', [
                'number' => $number])
        );
    }

    function testKMG(){
        $this->assertEquals(
            268435456,
            tpl::parseKMG('.25Г')
        );
    }


}


class ChildTpl extends Model_sql
{

    static function xxx()
    {
        return 'xxx';
    }

}

class ArrayAccessable implements ArrayAccess
{
    protected $_container = array();

    public function __construct($array = null)
    {
        if (!is_null($array)) {
            $this->_container = $array;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_container[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->_container[$offset] : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->_container[] = $value;
        } else {
            $this->_container[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->_container[$offset]);
    }
}

