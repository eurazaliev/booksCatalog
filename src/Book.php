<?php
namespace App;

use \ PDO;
use App\ConfigDb;
use App\IsbnExtractor;
use Isbn\Isbn;

class Book
{
    const TABLENAME = 'books_catalog';
    const ISBNSFIELDS = ['isbn', 'isbn2', 'isbn3', 'isbn4', 'isbn_wrong'];

    private $connect;
    private $tableName;

    function __construct(ConfigDb $db) {
        $this->connect = $db->connectDb(); 
        $this->tableName = self::TABLENAME;
    }
    
    public function getBook(int $id) {
        if($this->connect) {
            $sql = "SELECT * FROM $this->tableName
                WHERE id = :id
            ";
            $stmt = $this->connect->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        }
        return null;
    }

    public function setIsbn (int $id, string $isbn, string $field) {
        if($this->connect) {
            $sql = "UPDATE $this->tableName
                SET $field = :isbn
                WHERE id = :id
            ";
            $stmt = $this->connect->prepare($sql);
            $stmt->bindValue(':isbn', $isbn, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $this->getBook($id);
        }
        return null;
    }
    
    public function findIsbn (int $id, string $searchIsbn, IsbnExtractor $extractor, Isbn $isbnChecker) {
        $book = $this->getBook($id);
        if ($book) {
            $isbnsInFields = $this->isbnSearcherInIsbnsFields($book, $extractor);
            foreach ($isbnsInFields as $key => $isbnsInField) {
                //die(var_dump($isbnsInFields));
                foreach ($isbnsInField as $isbnInstance) {
                //die(var_dump($isbnInstance));
                    if ($isbnInstance == $searchIsbn) {
                        return $key;
                    }
                }
            }
        }
    }
    private function isbnSearcherInIsbnsFields (Object $book, IsbnExtractor $extractor) {
        $isbnFields = self::ISBNSFIELDS;
        foreach ($isbnFields as $isbnField) {
            $extractor->setStringContaining($book->$isbnField);
            $isbns[$isbnField] = $extractor->getAllIsbns();
            $extractor->reset();
        }
        return $isbns;
    }

    public function getIdS() {
       return $this->ids;
    }
}