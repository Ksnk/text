<?php
/* тырим тесты из тестового набора twig */

include_once '../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Ksnk\text\tpl;

class allInOneTest extends TestCase
{

    function _test_tpl(&$d, $pattern=''){
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

    function test_0()
    {
        $data=[
            'index' => "'Добрый день{{ first_name?,{{ first_name}}{{ second_name}}}}!'",
        ];
        $this->_test_tpl($data, "'Добрый день!'");
        $data['data']['first_name']='Сергей';
        $this->_test_tpl($data, "'Добрый день, Сергей!'");
        $data['data']['second_name']='Батькович';
        $this->_test_tpl($data, "'Добрый день, Сергей Батькович!'");
    }


}

