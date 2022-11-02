<?php
/* тырим тесты из тестового набора twig */

include_once '../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Ksnk\text\tpl, Ksnk\text\Model_tpl, Ksnk\text\Model_sql;

error_reporting(E_STRICT | E_ALL | E_NOTICE | E_CORE_WARNING | E_USER_NOTICE | E_USER_WARNING);

class allInOneTest extends TestCase
{

    /**
     * вспомогательная функция
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

    function test_to_debug()
    {
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
        $data = [
            'index' => "Сейчас{{ hour}} {{час||а|ов}}",
        ];
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

}


class ChildTpl extends Model_sql
{

    static function xxx()
    {
        return 'xxx';
    }

}

