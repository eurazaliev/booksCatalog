<?php
namespace App\Helper;

use \App\Book;

class IsbnProcessor
{
    private $logger;
    private $isCorrect;
    private $isbnChecker;
    private $isbnExtractor;

    function __construct (bool $isCorrect, \Isbn\Isbn $isbnChecker, \App\IsbnExtractor $isbnExtractor ) {
        $this->isCorrect = $isCorrect; 
        $this->isbnChecker = $isbnChecker;
        $this->isbnExtractor = $isbnExtractor;
        
    }
    
    public function setLogger (Logger $logger) {
        $this->logger = $logger;
    }

    public function processFoundedIsbns (
        array $isbns,
        \App\ConfigDB $db,
        object $book)
    {
        $this->isCorrect ? $wrongOrCorrect = "CORRECT" : $wrongOrCorrect = "WRONG";
        foreach ($isbns as $isbn) {
            $bookObj = new Book($db);
            if ($result = $bookObj->findIsbn($book->id, $isbn, $this->isbnExtractor, $this->isbnChecker)) {
                $event = "$wrongOrCorrect ISBN $isbn HAD BEEN FOUND IN description_ru HAVE BEEN FOUND IN $result FIELD; DO LOG ";
                isset($this->logger) ? $this->logger->writeLog([$book->id, $event]) : null;
            } else {
                $bookObj3 = new Book($db);
                $this->isbnExtractor->reset();
                try {
                    $this->isCorrect  ? 
                        $field = $bookObj3->addCorrectIsbn($book->id, $isbn, $this->isbnExtractor, $this->isbnChecker) :
                        $field = $bookObj3->addWrongIsbn($book->id, $isbn, $this->isbnExtractor, $this->isbnChecker);
                    if (!is_null($field)) {
                        $event = "$wrongOrCorrect ISBN $isbn HAD BEEN FOUND IN description_ru AND HAD NOT FOUND IN ISBNS FIELDS, SO $isbn ADDED TO BOOK INTO $field";
                        isset($this->logger) ? $this->logger->writeLog([$book->id, $event]) : null;
                    }
                }
                catch (Exception $e) {
                    $event = "FAIL TO ADD ISBN $isbn TO BOOK: $book->id". $e->getMessage();
                    isset($this->logger) ? $this->logger->writeLog([$book->id, $event]) : null;
                }
            }
        }
    }
}
