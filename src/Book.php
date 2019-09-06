<?php
namespace App;

use PDO;
use PDOException;
use Exception;
use App\ConfigDb;
use App\IsbnExtractor;
use Isbn\Isbn;

class Book
{
    const TABLENAME = 'books_catalog';
    const ISBNSINGLEFIELDS = ['isbn', 'isbn2', 'isbn3'];
    const ISBNMULTIFIELDS = ['isbn4', 'isbn_wrong'];
    const CORRECTSINGLEISBLFIELDSTOINSERT = ['isbn2', 'isbn3'];
    const CORRECTMULTIFIELDSTOINSERT = ['isbn4'];
    const WRONGMULTIFIELDSTOINSERT = ['isbn_wrong'];
    const ISBNSDEVIDER = ', ';

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

    
    public function findIsbn (int $bookId, string $searchIsbn, IsbnExtractor $extractor, Isbn $isbnChecker) {
        $book = $this->getBook($bookId);
        if ($book) {
            $isbnsInFields = $this->isbnSearcherInIsbnsFields($book, $extractor);
            foreach ($isbnsInFields as $key => $isbnsInField) {
                foreach ($isbnsInField as $isbnInstance) {
                    if ($isbnInstance == $searchIsbn) {
                        return $key;
                    }
                }
            }
        }
    }

    public function addWrongIsbn (int $bookId, int $isbn, IsbnExtractor $extractor, Isbn $isbnChecker) {
        // добавляем в поля, где может быть несколько isdn
        $isbnCorrectMultiFields = self::WRONGMULTIFIELDSTOINSERT;
        try {
            $result = $this->updateIsbn($bookId, $isbn, $isbnCorrectMultiFields);
            if(!is_null($result)) return $result; 
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    
    public function addCorrectIsbn (int $bookId, int $isbn, IsbnExtractor $extractor, Isbn $isbnChecker) {
        // вначале пробуем добавить КОРРЕКТ isdn в поля, гд может быть только 1 isdn
        $isbnCorrectSingleFields = self::CORRECTSINGLEISBLFIELDSTOINSERT;
        try {
            $result = $this->insertIsbn($bookId, $isbn, $isbnCorrectSingleFields);
            if(!is_null($result)) return $result; 
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        // если не удалось, добавляем в поля, где может быть несколько isdn
        $isbnCorrectMultiFields = self::CORRECTMULTIFIELDSTOINSERT;
        try {
            $result = $this->updateIsbn($bookId, $isbn, $isbnCorrectMultiFields);
            if(!is_null($result)) return $result; 
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    private function insertIsbn (int $bookId, int $isbn, array $fields) {

        foreach ($fields as $field) {
            $book = $this->getBook($bookId);
            if ($book->$field == '' or is_null($book->$field)) {
                try {
                    $this->doIsbnFieldUpdate($bookId, $isbn, $field);
                    return $field;
                }
                catch (Exception $e) {
                    throw new Exception("COULD NOT INSERT $isbn INTO FIELD $field OF BOOK $bookId MESSAGE: " . $e->getMessage());
                }
            }
        }
    }

    private function updateIsbn (int $bookId, int $isbn, array $fields) {
        $book = $this->getBook($bookId);

        foreach ($fields as $field) {
            if ($book->$field == '' or is_null($book->$field)) {
                try {
                    $this->doIsbnFieldUpdate($bookId, $isbn, $field);
                    return $field;
                }
                catch (Exception $e) {
                    throw new Exception("COULD NOT INSERT $isbn INTO FIELD $field OF BOOK $bookId MESSAGE: " . $e->getMessage());
                }
            }
            else {
                try {
                    $isbn = $book->$field . self::ISBNSDEVIDER . $isbn;
                    $this->doIsbnFieldUpdate($bookId, $isbn, $field);
                    return $field;
                }
                catch (Exception $e) {
                    throw new Exception("COULD NOT INSERT $isbn INTO FIELD $field OF BOOK $bookId MESSAGE: " . $e->getMessage());
                }
            }

        }
    }


    private function doIsbnFieldUpdate (int $bookId, string $isbn, string $field) {
        if($this->connect) {
            try {
                $sql = "UPDATE $this->tableName
                    SET $field = :isbn
                    WHERE id = :id
                ";
                $this->connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $this->connect->prepare($sql);
                $stmt->bindValue(':isbn', $isbn, PDO::PARAM_STR);
                $stmt->bindValue(':id', $bookId, PDO::PARAM_INT);
                $stmt->execute();

            }
            catch (PDOException $ex) {
                throw new Exception($ex->getMessage());
            }
        }
    }

    private function isbnSearcherInIsbnsFields (Object $book, IsbnExtractor $extractor) {
        $isbnSingleFields = self::ISBNSINGLEFIELDS;
        foreach ($isbnSingleFields as $isbnField) {
            $extractor->setStringContaining($book->$isbnField);
            $isbns[$isbnField] = $extractor->getAllIsbns();
            $extractor->reset();
        }
        $isbnMultiFields = self::ISBNMULTIFIELDS;
        foreach ($isbnMultiFields as $isbnField) {
            $isbnsRaws = explode(",", $book->$isbnField);
//            var_dump($isbnsRaws);
            $counter = 0;
            foreach ($isbnsRaws as $isbnRaw) {
                $extractor->setStringContaining($isbnRaw);
                $isbns[$isbnField . "[$counter]"] = $extractor->getAllIsbns();
                $extractor->reset();
                $counter++;
            }
        }
//        var_dump($isbns);
//        die;
        return $isbns;
    }

    public function getIdS() {
       return $this->ids;
    }
}