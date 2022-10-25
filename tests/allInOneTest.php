<?php
/* тырим тесты из тестового набора twig */

include_once '../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Ksnk\text\tpl;

class allInOneTest extends TestCase
{

    /**
     * вспомогательная функция
     * @param $d
     * @param string $pattern
     * @throws Exception
     */
    private function _test_tpl(&$d, $pattern=''){
        if(!isset($d['data'])) $d['data']=[];
        if(isset($d['index'])){
            $result=tpl::utext($d['index'],$d['data']);
        }
        if(!empty($pattern)){
            $d['pattern']=$pattern;
        }
        if(!empty($d['pattern'])){
            $this->assertEquals($d['pattern'],$result);
        } else {
            throw new Exception('wrong parameters');
        }
    }

    function test_escaped(){

        $this->assertEquals(
            'Символы { значение установлено } используются как границы в коде.',
            tpl::text('Символы \{ значение {prop?:не установлено} \} используются как границы в {code}.',
                ['code' => 'коде','prop' => 'установлено'])
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
            tpl::text('Символы \{{code} \} используются как границы в {code}.',
                ['code' => 'коде'])
        );
    }

    function test_0()
    {
        $data=[
            'index' => "'Добрый день{{ first_name ?,{{ first_name}}{{ second_name}}}}!'",'data'=>[]
        ];
        $data['data']['first_name']='Сергей';
        $this->_test_tpl($data, "'Добрый день, Сергей!'");
        unset($data['data']['first_name']);
        $this->_test_tpl($data, "'Добрый день!'");
        $data['data']['first_name']='Сергей';
        $data['data']['second_name']='Батькович';
        $this->_test_tpl($data, "'Добрый день, Сергей Батькович!'");
    }

    function test_00()
    {
        $data=[
            'index' => "Сейчас{{ hour}} час{{||а|ов}}",
        ];
        $data['data']['hour']='0';
        $this->_test_tpl($data, "Сейчас 0 часов");
        $data['data']['hour']='11';
        $this->_test_tpl($data, "Сейчас 11 часов");
        $data['data']['hour']='14';
        $this->_test_tpl($data, "Сейчас 14 часов");
        $data['data']['hour']='17';
        $this->_test_tpl($data, "Сейчас 17 часов");
        $data['data']['hour']='1';
        $this->_test_tpl($data, "Сейчас 1 час");
        $data['data']['hour']='4';
        $this->_test_tpl($data, "Сейчас 4 часа");
        $data['data']['hour']='7';
        $this->_test_tpl($data, "Сейчас 7 часов");
        $data=[
            'index' => "Сейчас{{ hour}} час{{hour||а|ов}}",
        ];
        $this->_test_tpl($data, "Сейчас часов");
        $data['data']['hour']='11';
        $this->_test_tpl($data, "Сейчас 11 часов");
        $data['data']['hour']='14';
        $this->_test_tpl($data, "Сейчас 14 часов");
        $data['data']['hour']='17';
        $this->_test_tpl($data, "Сейчас 17 часов");
        $data['data']['hour']='1';
        $this->_test_tpl($data, "Сейчас 1 час");
        $data['data']['hour']='4';
        $this->_test_tpl($data, "Сейчас 4 часа");
        $data['data']['hour']='7';
        $this->_test_tpl($data, "Сейчас 7 часов");
    }

    function testMoney(){
        $pattern='Сумма прописью:{prop}';
        // вывод суммы с обязательными копейками
        $cost=23456.45;
        $this->assertEquals(
            'Сумма прописью:двадцать три тысячи четыреста пятьдесят шесть рублей 45 копеек',
            tpl::text($pattern, ['prop'=>tpl::prop($cost,tpl::RUB,true)])
        );
        // вывод суммы с обязательными копейками -00 коп
        $cost=23451;
        $this->assertEquals(
            'Сумма прописью:двадцать три тысячи четыреста пятьдесят один рубль 00 копеек',
            tpl::text($pattern, ['prop'=>tpl::prop($cost,tpl::RUB,true)])
        );
        // вывод суммы без обязательных копеек
        $cost=23451;
        $this->assertEquals(
            'Сумма прописью:двадцать три тысячи четыреста пятьдесят один рубль',
            tpl::text($pattern, ['prop'=>tpl::prop($cost,tpl::RUB)])
        );
        // проверка склонения в женском роде одна-две вместо один-два
        $number=101;
        $this->assertEquals(
            'сто один баран на поле',
            tpl::text('{prop} на поле', [
                'prop'=>tpl::prop($number,"баран||а|ов")])
        );
        // проверка склонения в женском роде одна-две вместо один-два
        $number=101;
        $this->assertEquals(
            'сто одна овца на поле',
            tpl::text('{prop} на поле', [
                'prop'=>tpl::prop($number,"+овц|а|ы|ец")])
        );
    }

// so let's test sql

    function test_sql(){
        $appendstr=[];
        $appendstr[]='user= {user}';
        $appendstr[]='text={text}';
        $appendstr[]='data={data}';

        $this->assertEquals(
            'insert into table_sclad set  created=NOW(), user_email = "my@email.com", user= NULL,text="just a some text",data="[1,2,3,4,5]"',
            tpl::sql('insert into table_sclad set  created=NOW(), user_email = {own}, ' . implode(',', $appendstr),
                ['text' => 'just a some text',
                    'data' => [1,2,3,4,5],
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
                    'data' => [1,2,3,4,5],
                    'own' => 0,
                    'user' => null
                ])
        );
    }

}

