<?php

include_once '../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Ksnk\text\tpl, Ksnk\text\Model_tpl, Ksnk\text\Model_sql;

error_reporting(E_STRICT | E_ALL | E_NOTICE | E_CORE_WARNING | E_USER_NOTICE | E_USER_WARNING);

//tpl::_(new Model_tpl);

$stat=[];
$stat['date']=strtotime('2022-10-10');
$stat['send']=23;
$stat['empty']=2;
$stat['double']=1;
$stat['waiting']=2;
$stat['total']=27;

echo    tpl::text('Всего строк - {total}'.
        ', поставлено в очередь {send} пись{|мо|ма|ем} для отправки на {date|d}'.
        '{empty?, {empty} строк{|а|и|} пустые или не email}'.
        '{double?, {double} адрес{||а|ов} повторя{|е|ю|ю}тся}'.
        '{waiting?, {waiting} пись{|мо|ма|ем} уже поставлены в очередь ранее}'.PHP_EOL
        , $stat);





$pattern='{one}{two}{three}{four}';
$data=[
    'one'=>1,
    'two'=>2,
    'three'=>3,
    'four'=>4,
];
// tpl::_(new Model_tpl);
$divide=microtime(true);
for($i=0;$i<1000;$i++){
    $x=tpl::text($pattern, $data);
}
$fin=microtime(true);
printf("%.03f sec ",$fin-$divide);
//1000 - 0.026 sec на домашнем компьютере


