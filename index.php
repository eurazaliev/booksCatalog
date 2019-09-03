<?php

require __DIR__ . '/vendor/autoload.php';
use Isbn\Isbn as Isbn;
use App\ConfigDb;
use App\BooksCatalog;
use App\Book;
use App\RecursiveImplode;
use App\IsbnExtractor;

//у нас в БД может быть 100500 мильенов записей, да еще и под нагрузкой, поэтому обходить БД нужно по кускам размером ну пусть 1000 записей.
const CHUNKSIZE = 1000;

$isbnChecker = new Isbn();
$db = new ConfigDb;

$lastId = 43000;//61100;

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
//        echo PHP_EOL . "BOOK ID: " . $book->id;// . "pieces= ";
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
                $idsWithPotentialIsbns[$book->id][] = $piece;
//
                $isbnExtractor = new IsbnExtractor( $isbnChecker, $piece);
                $correctIsbns = array_merge($correctIsbns, $isbnExtractor->getCorrectIsbns());
                $wrongIsbns = array_merge($wrongIsbns, $isbnExtractor->getWrongIsbns());
//
            }
        }
        if ($correctIsbns) {
            echo "CORRECT ISBNS FOR THE $book->id FOUND: ";
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
//    echo "CHUNK: ".$lastId . PHP_EOL;
}
while ($lastId);
unset($bookId);

/*
echo "BOOKS W POT.ISDNS IN DESC_RU = " . count($idsWithPotentialIsbns) . PHP_EOL;

foreach ($idsWithPotentialIsbns as $bookId => $idsWithPotentialIsbn) {
    echo "BOOK ID: " . $bookId . PHP_EOL;// . "pieces= ";
    $potentialCorrectIsbns = [];
    foreach ($idsWithPotentialIsbn as $piece) {
        $isbnExtractor = new IsbnExtractor( $isbnChecker, $piece);
        //$CorrectIsbns = fetchCorrectIsbns($piece, $isbnChecker);
        $CorrectIsbns = $isbnExtractor->getCorrectIsbns();
        if ($CorrectIsbns)  $potentialCorrectIsbns = array_merge($potentialCorrectIsbns, $CorrectIsbns);
    }
    if (!is_null($potentialCorrectIsbns)) {
        echo "CORRECT ISBNS FOR THE BOOKID $bookId: ";
        $recursiveImplode = new RecursiveImplode;
        printf($recursiveImplode->recursiveImplode($potentialCorrectIsbns, ','));
        echo PHP_EOL;
    }
}

//var_dump($idsWithPotentialIsbns);
var_dump($isbnChecker->validation->isbn('8881837181'));
//$isbnCheker->checkDigit->make('8881837188'));
*/