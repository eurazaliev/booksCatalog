<?php

require __DIR__ . '/vendor/autoload.php';
use Isbn\Isbn as Isbn;
use App\ConfigDb;
use App\BooksCatalog;
use App\Book;
use App\RecursiveImplode;
use App\IsbnExtractor;

/* у нас в БД может быть 100500 мильенов записей, да еще и под нагрузкой, поэтому обходить БД нужно по кускам размером ну пусть 1000 записей.
   если делать так: "select * from table" можно завесить sql-сервер, а заодно и сервер приложений, когда кончится память от миллиарда объектов
   поэтому данные из БД забираем частями.
*/
const CHUNKSIZE = 1000;

$isbnChecker = new Isbn();
$db = new ConfigDb;

/* можно начать поиск с определенного id
*/
$lastId = 63007;//61100;

/* тут проходим всю таблицу, разбивая ее на кусочки и находим id книг, где есть что-то похожее на isbn в поле description_ru 
   на выходе получаем массив массивов, где ключ внешнего массива = id_книги, а внутренний массив разбитые на части по пробелу куски текста,
   содержащие 10 или более цифр, разделенных любым количеством символов
*/
do  {
    $booksCatalog = new BooksCatalog($db, $lastId, CHUNKSIZE);
    $booksIds = $booksCatalog->getIds();
    //перебираем все книжки в кол-ве записей = CHUNKSIZE
    foreach ($booksIds as $bookId) {
        $bookObj = new Book($db);
        $book = $bookObj->getBook($bookId['id']);
        $correctIsbns = [];
        $wrongIsbns = [];
        //description_ru делим на кусочки, разделенные пробелом
        $pieces = explode(" ", $book->description_ru);
        //если в каком-то кусочке есть интересующий нас паттерн кладем в выходной массив
        foreach ($pieces as $piece) {
            preg_match_all('!\d+!', $piece, $matches, PREG_SET_ORDER);
            $recursiveImplode = new RecursiveImplode;
            $toStr = $recursiveImplode->recursiveImplode($matches);
            if (strlen($toStr) >= 10) {
                $isbnExtractor = new IsbnExtractor( $isbnChecker, $piece);
                $correctIsbns = array_merge($correctIsbns, $isbnExtractor->getCorrectIsbns());
                $wrongIsbns = array_merge($wrongIsbns, $isbnExtractor->getWrongIsbns());
            }
        }
        if ($correctIsbns) {
            echo "CORRECT ISBNS FOR THE $book->id FOUND: ";
            $bookToUpd = new Book($db);
            $bookToUpd->setIsbn($book->id, $correctIsbns[0], 'isbn2');
            $recursiveImplode = new RecursiveImplode;
            printf($recursiveImplode->recursiveImplode($correctIsbns, ','));
            echo PHP_EOL;
        }
        if ($wrongIsbns) {
            echo "WRONG ISBNS FOR THE $book->id FOUND: ";
            $recursiveImplode = new RecursiveImplode;
            printf($recursiveImplode->recursiveImplode($wrongIsbns, ','));
            echo PHP_EOL;
        }

    }
    $lastId = $booksCatalog->getMaxId();
}
while ($lastId);

