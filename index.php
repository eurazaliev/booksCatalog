<?php

require __DIR__ . '/vendor/autoload.php';
use Isbn\Isbn as Isbn;
use App\ConfigDb;
use App\BooksCatalog;
use App\Book;
use App\Helper\RecursiveImplode;
use App\IsbnExtractor;
use App\Helper\Logger;
use App\Helper\FileNameGenerator; 
use App\Helper\IsbnProcessor;
use App\Config\MainConfig as MainConfig;

    
$fileNameGenerator = new FileNameGenerator;
$csvCaption = ['id', 'date time', 'book ID', 'event'];
try {
    $logger = new Logger ($fileNameGenerator, $csvCaption);
    $logging = true;
}
catch (Exception $e) {
   echo 'LOGGING ERROR: ' . $e->getMessage(). '. LOGGING IS DISABLED';
   $logging = false;
}

$isbnChecker = new Isbn();
$db = new ConfigDb;

// можно начать поиск с определенного id
$lastId = 62000;

$logRow = ['','Job started'];
$logging ? $logger->writeLog($logRow) : null;

/** тут проходим всю таблицу, разбивая ее на кусочки и находим id книг, 
   где есть что-то похожее на isbn в поле description_ru 
   на выходе получаем массив массивов, где ключ внешнего массива = id_книги,
   а внутренний массив разбитые на части по пробелу куски текста,
   содержащие 10 или более цифр, разделенных любым количеством символов **/
do  {
    
    /** у нас в БД может быть 100500 мильенов записей, да еще и под нагрузкой, поэтому обходить БД нужно по кускам размером ну пусть 1000 записей.
    если делать так: "select * from table" можно завесить sql-сервер, а заодно и сервер приложений, когда кончится память от миллиарда объектов
    поэтому данные из БД забираем частями. **/

    $booksCatalog = new BooksCatalog($db, $lastId, MainConfig::CHUNKSIZE);
    $booksIds = $booksCatalog->getIds();
    // перебираем все книжки в кол-ве записей = CHUNKSIZE
    foreach ($booksIds as $bookId) {
        $bookObj = new Book($db);
        $book = $bookObj->getBook($bookId['id']);
        $correctIsbns = [];
        $wrongIsbns = [];
        // description_ru делим на кусочки, разделенные пробелом
        $pieces = explode(" ", $book->description_ru);
        // если в каком-то кусочке есть интересующий нас паттерн кладем в выходной массив
        foreach ($pieces as $piece) {
            preg_match_all('!\d+!', $piece, $matches, PREG_SET_ORDER);
            $recursiveImplode = new RecursiveImplode;
            $toStr = $recursiveImplode->recursiveImplode($matches);
            /** последовательности менее 10 цифр нас не интересуют.
               из найденной последовательности цифр выжимаем
               корректные и некорректные isbn в массивы **/
            if (strlen($toStr) >= 10) {
                $isbnExtractor = new IsbnExtractor($isbnChecker);
                $isbnExtractor->setStringContaining($piece);
                $correctIsbns = array_merge($correctIsbns, $isbnExtractor->getCorrectIsbns());
                $wrongIsbns = array_merge($wrongIsbns, $isbnExtractor->getWrongIsbns());
            }
        }
        echo "PROCESSING BOOK ID $book->id ", PHP_EOL;

        $correct = true;
        $isbnExtractor = new IsbnExtractor($isbnChecker);

        $isbnProcessor = new IsbnProcessor($correct, $isbnChecker, $isbnExtractor);
        $isbnProcessor->setLogger($logger);
        $isbnProcessor->processFoundedIsbns ($correctIsbns, $db, $book);

        $correct = false;
        $isbnProcessor = new IsbnProcessor($correct, $isbnChecker, $isbnExtractor);
        $isbnProcessor->setLogger($logger);
        $isbnProcessor->processFoundedIsbns ($wrongIsbns, $db, $book);
        
        $recursiveImplode = new RecursiveImplode;
        if ($correctIsbns) {
            echo "CORRECT ISBNS FOR THE $book->id FOUND: ";
            printf($recursiveImplode->recursiveImplode($correctIsbns, ','));
            echo PHP_EOL;
        }
        if ($wrongIsbns) {
            echo "WRONG ISBNS FOR THE $book->id FOUND: ";
            printf($recursiveImplode->recursiveImplode($wrongIsbns, ','));
            echo PHP_EOL;
        }
    }
    $lastId = $booksCatalog->getMaxId();
}
while ($lastId);

$logRow = ['','Job ended'];
$logging ? $logger->writeLog($logRow) : null;

