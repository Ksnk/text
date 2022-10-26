# Текстовые утилиты

Все утилиты выполнены в виде функций единственного класса `tpl`. Практически каждая функция может 
быть вызвана как в статическом, так и в классическом виде. Вызов статического 
синонима, как правило, более компактен, однако в классическом виде можно настроить 
формат вывода более гибко. И с наследованием все значительно более правильно. Впрочем, обычно это не нужно.

Несомненной пользой класса является тот факт, что класс одинаково хорошо работает как в обычном, 
так и битриксовском серверном окружении. 

## Использование

    use Ksnk\text\tpl;
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
    /*
    Глубокоуважаемый Вагоноуважатый!
        При оформлении заказа от 25 октября, Вам предоставлена скидка 250₽ по промокоду `СКОРОЛЕТО`
        Общая сумма заказа составляет: две тысячи триста сорок пять рублей
    */

    
## установка

    composer require ksnk/text
    
По сути - там один файл, в случае серьезной нужды - поковыряйтесь в репозитории, скопируйте его 
себе и делайте с ним что угодно. Но не дай БГ так оголодать ))) композер - это действительно удобно. 

Сам реализация допускает небольшую модификацию шаблонизатора на лету или полное перенаследование класса 
с сохранением опции статического вызова уже переопределенных функций.          

## Вывод числа прописью. tpl::prop($number, $valute = array|string|FALSE, $kop = FALSE)

Первый параметр - число, если число не целое, целая часть выводится словами, вторая цифрами. 
Подпись к обоим частям - второй параметр. `"рубл|ь|я|ей;+копе|йка|йки|ек";`
Третий параметр говорит - можно ли опускать вывод `00 коп`.

При выводе числа учитывается склонение. Женский род помечается ведущим символом `+`, 
при указании второго параметра функции

    $number=101;
    //'сто один баран на поле', склонение в мужском роде - один-два
    tpl::text('{prop} на поле', [
        'prop'=>tpl::prop($number,"баран||а|ов")]);
    );
    // проверка склонения в женском роде одна-две вместо один-два
    //'сто одна овца на поле',
    tpl::text('{prop} на поле', [
        'prop'=>tpl::prop($number,"+овц|а|ы|ец")]) ;
        
Классический вывод цены с копейками        

    $tpl=new tpl();
    echo $tpl->num2str(23456.45, tpl::RUB, true);
    // двадцать три тысячи четыреста пятьдесят шесть рублей 45 копеек
    
    echo tpl:prop(23456.45, tpl::RUB, true);
 
## Вывод русской даты. tpl::rusd($time,$format)

К удивлению, в PHP до сих пор из коробки отсутствует возможность вывода даты по-русски, с названиями в 
родительном падеже. Только в именительном. Как же так?

    // так советуют писать тру ПХПшники
    setlocale(LC_ALL, 'ru_RU', 'ru_RU.UTF-8', 'ru', 'russian');
    echo strftime("%B %d, %Y", time()).PHP_EOL;
    // Октябрь 25, 2022

    // Так хочет бухгалтер
    $tpl->toRusDate(time()); // 25 октября, 2022 г.
    tpl::rusd(time(), "j F"); // 25 октября.
    
Используется формат даты для обычного вывода даты по английски. Можно таймстамп, можно строковое 
значение из базы, которое понимается оператором strtotime. Если требуется умное преобразование дат 
- лучше сделать это вне шаблонизатора и передать уже нужный таймстамп.  

## plural. tpl::pl

Окончания для числительных. Параметр в строковом виде, `ОДИН, ТРИ, ПЯТЬ`. 
Для статического способа вызова более компактная форма вызова - одна строка параметров, 
разделенных `|`.

    echo $tpl->plural(25, 'копейка', 'копейки', 'копеек');
    echo 25.' копе'.tpl::pl(25,'йка|йки|ек');
    
Впрочем, самостоятельного применения практически не имеет, обычно в составе шаблона. Опять же, 
формат параметров в строке чуть-чуть отличается от используемого функцией `text`, что делает 
явное использование формы, скорее, вредной практикой.

## tpl::text

Форматированный вывод с подстановкой значений. Имеются операторы-подстановки - переменная в фигурных 
скобках. В конце переменной могут находиться модификаторы, отделяемые символом `|`. Пример `{date|t}`
Ведущие пробелы перед переменной в подстановке выводятся вместе со значением подстановки, 
если значение не пусто.

    $data=[
        'first_name'=>'Вассисуалий',
        'second_name'=>'Лоханкин',
        'joined'=>'2022-10-15'
    ];
    
    echo tpl::text('Уважаемый {first_name}{ second_name}!', $data);
    
В случае, если второй параметр отсутствует в данных, восклицательный знак 
не отделяется пробелом. Это позволяет генерировать корректные с точки зрения лишних 
пробелов конструкции - `Уважаемый Вассисуалий!` `Уважаемый!` `Уважаемый Вассисуалий Лоханкин!`

Некотоорые символы имеют служебный смысл и, могут, а в некоторых местах обязаны быть заслешены, чтобы 
не использоватся шаблонизатором. Эти символы `{}?:\`.
Кроме подстановок имеются логические тернарные операторы. Если значение пераметра-подставнки не пусто, 
выбирается первый операнд, если пусто - второй.

    echo tpl::text('Уважаемый {joined?подписчик:посетитель}!', $data);
  
Внутри операндов также могут находиться подстановки и логические операторы.

    echo tpl::text('Уважаемый {first_name?:посетитель}!', $data);
    
Условный оператор в виде  `?:` имеет несколько отличающуюся от очевидной, логику вывода. 
Если параметр не пуст, выводится он, если пуст - выводится значение по умолчанию. 
Фактически, является сокращением 
`{first_name?{first_name}:посетитель}`
   
    $data['number]=23;
    echo tpl::text('{number} баран{||а|ов} на поле!', $data);
    // 23 барана на поле
    
Окончание числительных. Модификатор, в котором присутствует 3 символа `|`.
 Можно использовать в 2-х формах, полная -  `баран{order_num||а|ов}` и сокращенная 
 `баран{||а|ов}` или, `{баран||а|ов}` 
 в последних случаях числовое значение берется из последнего подмененного шаблона. 
 Вариант `{баран||а|ов}` совпадает с форматом параметра plural функции, однако при его использовании следует 
 проявить осторожность и, убедиться, что параметра `баран` нет в получаемых данных.
   
Список модификаторов
- `окончание числительных`
- `|t` - время выводится в виде `25 апреля 2022 г. в 10:00`
- `|d` - время выводится в виде `25 апреля` или `25 апреля 2020 г.`, 
если год даты не совпадает с текущим.
- `|d|j F Y г. в H\:i` - время с явным указанием формата вывода. Формат 
выедается до закрывающих скобок. Обратите внимание, что если модификатору `t` 
также указать формат, разницы с `d` не будет.
Символ `}` который должен встретится внутри описателя модификатора обязан быть заслешен.

Можно добавить свои собственные модификаторы, для этого служит функция `tpl::mod`.
Параметрами callback функции будут
- $data - полученное из параметров значение
- $mod_ext - параметр модификатора, если он указан,
- $spaces - строка ведущих пробелов перед указанием ключа. 
Остальные параметры могут не указываться в функции модификаторе, однако они передаются при вызове и, 
возможно, могут быть использованы.
- $key - ключевое слово, по которому искалось значение. Может применяться в экзотических случаях, 
когда указанный ключ имеет особенное значение. Например, когда он `''` - значение $data не указано 
явно,  а берется из предшествующего оператора подстановки. Или при отсутствии данных в параметре требуется 
произвести дополнительный поиск по ключу в дополнительном массиве данных. 
- $mod - модификатор, в случае, если одна и та же функция ставится на разные модификаторы - можно 
различить способ вызова через этот параметр.

    tpl::mod('x', function($data,$mod_ext,$spaces,$key,$mod){
        if (($x = strtotime($data)) > 0) $data = $x;
        if(!empty($mod_ext))
            $format= $mod_ext;
        else {
            $format= 'j F Y г. в H:i';
        } 
        return $spaces . tpl::rusd($data, $format);
    });

## tpl::utext

Отличается от text тем, что используются двойные фигурные скобки вместо одинарных 
для определения границ подстановок и логических операторов. В остальном логика и операторы шаблонов
одинаковы.
В определенной степени, шаблоны включают в себя шаблонный язык Unisender. 
Во всяком случае - я обрабатываю шаблоны, скачанные оттуда именно в этом  режиме. 
Несколько большие, по сравнению с Unisender возможности не конфликтуют.
 
## tpl::sql

Является экспериментальным режимом работы шаблонизатора. Позволяет готовить 
sql запросы в достаточно экзотических условиях. Не рекомендуется
для широкого применения. Пользой этого режима является единообразие 
sql вне зависимости от используемых CMS, что позволяет писать более 
переносимые с системы на систему модули.

## Что делать, если очень нужно использовать наследника от класса tpl::_

Представим, что нам захотелось дополнить или изменить функционал класса, а добраться до 
репозитория нет возможности. Можно смастерить наследника класса, произвести там все необходимые дополнения 

    class ChildTpl extends tpl
    {
    
        function xxx()
        {
            return 'xxx';
        }
    
    }

А чтобы сохранился "статический" функционал и прикол вызова статических функций - выполнить 
вот такой код 

    tpl::_(new ChildTpl);
    
После этого появится возможность вызова `tpl::xxx()` без ругательств от PHP. Обратите внимание - 
функции класса-наследника не должны быть описаны как статические. После описания и инициализации 
все использование утилит будет идти через переопределенный класс.        