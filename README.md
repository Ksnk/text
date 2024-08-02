# Текстовые утилиты

Все утилиты выполнены в виде функций единственного класса `tpl`. Класс, непосредственно реализующий 
функционал - `Model_tpl`, однако для удобства применения каждая функция может 
быть вызвана как статическая функция `tpl`. Вызов статического 
синонима, как правило, более компактен и не требует предварительного создания класса, 
однако в классическом виде можно настроить поведение более гибко. И с наследованием все 
значительно более правильно. Впрочем, обычно это не нужно.

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
    
По сути - там пара файлов, в случае серьезной нужды - поковыряйтесь в репозитории, скопируйте его 
себе и делайте с ним что угодно. Но не дай БГ так оголодать ))) композер - это действительно удобно. 

Сама реализация допускает небольшую модификацию шаблонизатора на лету или полное перенаследование класса 
с сохранением опции статического вызова уже переопределенных функций.          

## Вывод числа прописью. tpl::prop($number, $podpis, $kop = FALSE)

Первый параметр - число, если число не целое, целая часть выводится словами, вторая цифрами, с обрезанием на 2 цифры. 
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
    tpl::rusd(time(), "j F"); // 25 октября.
    
Используется формат даты для обычного вывода даты по английски. Можно таймстамп, можно строковое 
значение из базы, которое понимается оператором strtotime. Если требуется умное преобразование дат - лучше сделать это вне шаблонизатора и передать уже нужный таймстамп.  

## plural form. tpl::pl

Окончания для числительных. Параметр в строковом виде, `ОДИН, ТРИ, ПЯТЬ`.

    echo 25.' копе'.tpl::pl(25, 'копейка', 'копейки', 'копеек');

Впрочем, самостоятельного применения практически не имеет, обычно в составе шаблона. Опять же,
формат параметров в строке чуть-чуть отличается от используемого функцией `text`, что делает
явное использование функции, скорее, вредной практикой. В шаблонах используется более компактная форма вызова - одна строка параметров,
разделенных `|`. Например этот промер через `::text`
выглядит более управляемо

    echo tpl::text('{kop} копе{|йка|йки|еек}',['kop'=>25]);

## парсинг сокращенных чисел. tpl::parseKMG

Переводит в число строковые cокращения вида .5k 1K и т.д.

    echo tpl::parseKMG('.25Г'); // четверть гига - 268435456

Довольно удобно пользоваться при парсинге вывода консольных утилит. Минусом будет то
что никакой реакции на то, что это не число не предполагается.

## tpl::text

Форматированный вывод с подстановкой значений. 
- первый параметр - шаблон
- второй параметр - данные. Лучше если это будет объект, однако, не обязательно, внутри функции он будет преобразован в 
объект явным образом. Так как наличие свойств в этом объекте проверяется методом `property_exists` - передавать объект с
магическими свойствами не получится.

В шаблоне могут встречаться операторы-подстановки - переменная в фигурных 
скобках. В конце переменной могут находиться модификаторы, отделяемые символом `|`. Пример `{date|t}`
Ведущие пробелы перед переменной в подстановке выводятся вместе со значением подстановки, 
если значение не пусто.

    $data=[
        'first_name'=>'Вассисуалий',
        'second_name'=>'Лоханкин',
        'joined'=>'2022-10-15'
    ];
    
    echo tpl::text('Уважаемый{ first_name}{ second_name}!', $data);
    
Такое не очень обычное обращение с ведущими пробелами подстановки позволяет 
генерировать корректные с точки зрения лишних 
пробелов конструкции - `Уважаемый Вассисуалий!` `Уважаемый!`  `Уважаемый Лоханкин!` `Уважаемый Вассисуалий Лоханкин!`

Некоторые символы имеют служебный смысл и, могут, а в некоторых местах обязаны быть заслешены, чтобы 
они не использовались шаблонизатором. Эти символы `{}?:\|`.

Продемонстрируем чудеса эскейпинга. Технически, не обязательно эскейпить все 
служебные символы внутри подстановки, необходимо только те, которые ожидаются по синтаксису, 
однако, если их эскейпить все - результат будет точно такой же. Наиболее удобным способом поставить 
нужные слеши в PHP будет `addcslashes($x,'{}?:\|');`.

    // не все служебные символы прослешены, но синтаксис позволяет
    $pattern = 'Сумма прописью:{ Очень странная переменная {1\|2\}\?: }';
    // 'Сумма прописью: А вот!',
    echo    tpl::text($pattern, ['Очень странная переменная {1|2}?:' => 'А вот!']);
    
    // все служебные символы прослешены
    $pattern = 'Сумма прописью:{ Очень странная переменная \{1\|2\}\?\: }';
    // 'Сумма прописью: А вот еще!',
    echo    tpl::text($pattern, ['Очень странная переменная {1|2}?:' => 'А вот еще!']);
    
    // правильный способ, всегда дающий правильный результат
    $key='Очень странная переменная {1|2}?:';
    $pattern = 'Сумма прописью:{ '.addcslashes($key,'{}?:\|').' }';
    echo    tpl::text($pattern, [$key => 'А вот еще!']);
                

Кроме подстановок имеются логические тернарные операторы. Если значение параметра-подстановнки не пусто, 
выбирается первый операнд, если пусто - второй. Нужно отметить, что ведущие пробелы внутри 
условия тернарного оператора игнорируются, внутри операндов работают в обычном режиме.

    echo tpl::text('Уважаемый {joined?подписчик:посетитель}!', $data);
  
Внутри операндов также могут находиться подстановки и логические операторы.

    echo tpl::text('Уважаемый {first_name?:посетитель}!', $data);
    
Условный оператор в виде `?:` имеет несколько отличающуюся от очевидной логику вывода. 
Если параметр не пуст, выводится он, если пуст - выводится значение по умолчанию. 
Фактически, является сокращением 
`{first_name?{first_name}:посетитель}`

Внутри "условной" части тернарного оператора могут быть вычисляемые конструкции 

    tpl::text(
    "<b>Ваш счет - {count} из {total}</b>. {10<count?Поздравляем с успешным прохождением тестирования!
    Ваш сертификат будет отправлен на почту указанную в Личном кабинете  \ или ваш сертификат находится в вашем ЛК в разделе Обучение (возможно ли создать такую вкладку)
    :Вам немного не хватило баллов для сертификата Проф уровня.}",$data)

Если массив данных содержит подмассивы - можно указать значение через точку. При этом, если в массиве данных имеется ключ "с точкой", будет использовано его значение.

     $tpl::text('{POINT}{ CURRENT.POINT}{ CURRENT.diff}',
       ['POINT'=>'111', 'CURRENT.POINT'=>'22', 'CURRENT'=>['diff'=>444,'POINT'=>333]]); // '111 22 444'
     

Окончание числительных - |||
   
    $data['number]=23;
    echo tpl::text('{number} баран{||а|ов} пас{|ё|у|у}тся на поле!', $data);
    echo tpl::text('{number} баран{number||а|ов} пас{|ё|у|у}тся на поле!', $data);
    echo tpl::text('{number} {баран||а|ов} пас{|ё|у|у}тся на поле!', $data);
    // 23 барана пасутся на поле
    
 Модификатор, в котором присутствует 3 символа `|`.
 Можно использовать в 2-х формах, полная -  `баран{number||а|ов}` и сокращенная 
 `баран{||а|ов}` 
 в последнем случае числовое значение берется из последнего числового шаблона-подмены. 
   
Список модификаторов.
- `окончание числительных` - рассмотрено параграфом выше.
- `|q` - одинарные кавычки. Значение квотится и обрамлянтся одинарными кавычками.
- `|qq` - двойные кавычки. Значение квотится и обрамлянтся двойными кавычками. 
При этом само значение занимает одну строку. Удобно для вставки в javascript 
- `|t` - время выводится в виде `25 апреля 2022 г. в 10:00`
- `|d` - время выводится в виде `25 апреля` или `25 апреля 2020 г.`, 
если год даты не совпадает с текущим.
- `|d|j F Y г. в H\:i` - время с явным указанием формата вывода. Формат 
выедается до закрывающих скобок. Обратите внимание, что если модификатору `t` 
также указать формат, разницы с `d` не будет.

При использовании модификаторов даты можно использовать значение, совместимое c strtotime

Можно добавить свои собственные модификаторы, для этого служит функция `tpl::mod`.

    tpl::mod('x', function ($data, $mod_ext, $spaces, $key, $mod) {
        //...
        return $spaces . $data;
    });
        
Параметрами callback функции будут
- $data - полученное из параметров значение
- $mod_ext - параметр модификатора, если он указан в шаблоне,
- $spaces - строка ведущих пробелов перед указанием ключа. 
Остальные параметры могут не указываться в функции-модификаторе, однако они передаются при 
вызове и могут быть использованы по необходимости.
- $key - ключевое слово, по которому искалось значение. Может применяться в экзотических случаях, 
когда указанный ключ имеет особый смысл. Например, когда он `''` - значение $data не указано 
явно, а берется из предшествующего числового оператора подстановки. Или при отсутствии данных в параметре требуется 
произвести дополнительный поиск по ключу в дополнительном массиве данных. 
- $mod - сам модификатор, в случае, если одна и та же функция ставится на разные модификаторы - можно 
различить способ вызова через этот параметр.

## tpl::utext

Отличается от text тем, что используются двойные фигурные скобки вместо одинарных 
для определения границ подстановок и логических операторов. В остальном логика и операторы шаблонов
одинаковы. В том числе условные операторы и символы модификаторов не дублированы
В определенной степени, шаблоны включают в себя шаблонный язык Unisender. 
Во всяком случае - я обрабатываю шаблоны, скачанные оттуда именно в этом режиме. 
Несколько бОльшие, по сравнению с Unisender возможности с ним самим не конфликтуют.
 
## tpl::sql

Является экспериментальным режимом работы шаблонизатора. Позволяет готовить 
sql запросы в достаточно экзотических условиях. Не рекомендуется
для широкого применения. 
Пользой этого режима для меня является единообразие 
sql вне зависимости от используемых CMS, что позволяет писать более 
переносимые с системы на систему модули, однако экспериментальный режим не позволяет 
широко рекомендовать такой подход к работе с БД. 

## Что делать, если очень нужно унаследоваться. tpl::_

Представим, что нам захотелось дополнить или изменить функционал класса, а добраться до 
репозитория нет возможности. Можно смастерить наследника класса, произвести там все необходимые дополнения 

    class ChildTpl extends Model_tpl
    {
    
        function xxx()
        {
            return 'xxx';
        }
    
    }

А чтобы сохранился "статический" функционал вызова - выполнить 
вот такой код 

    tpl::_(new ChildTpl);
    
После этого появится возможность вызова `tpl::xxx()` без ругательств от PHP. Обратите внимание - 
функции класса-наследника не должны быть описаны как статические. После описания и инициализации 
все использование утилит будет идти через переопределенный класс. 

Более сложный пример наследования - класс `Model_sql` в репозитории. Он полностью переопрееделяет 
логику шаблонизатора, позволяя ему использоваться для приготовления SQL запросов. Чтобы оставалась 
возможность использовать шаблон в обычном текстовом виде, вводится отдельная функция SQL, в которой 
включаеься и отключается спец режим работы.       