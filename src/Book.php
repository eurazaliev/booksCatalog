<?php
namespace App;

use PDO;
use PDOException;
use Exception;
use App\ConfigDb;
use App\IsbnExtractor;
use App\Config\MainConfig;
use Isbn\Isbn;

class Book
{
    private $connect;
    private $tableName;

    function __construct(ConfigDb $db) {
        $this->connect = $db->connectDb(); 
        $this->tableName = MainConfig::TABLENAME;
    }
    // выбираем книжку по id
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

    // тут находим исбн в полях таблицы
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

    // функция добавления неправильных исбн
    public function addWrongIsbn (int $bookId, int $isbn, IsbnExtractor $extractor, Isbn $isbnChecker) {
        // добавляем в поля, где может быть несколько isdn
        $isbnCorrectMultiFields = MainConfig::WRONGMULTIFIELDSTOINSERT;
        try {
            $result = $this->updateIsbn($bookId, $isbn, $isbnCorrectMultiFields);
            if(!is_null($result)) return $result; 
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // соотвественно правильных
    public function addCorrectIsbn (int $bookId, int $isbn, IsbnExtractor $extractor, Isbn $isbnChecker) {
        // вначале пробуем добавить КОРРЕКТ isdn в поля, гд может быть только 1 isdn
        $isbnCorrectSingleFields = MainConfig::CORRECTSINGLEISBLFIELDSTOINSERT;
        try {
            $result = $this->insertIsbn($bookId, $isbn, $isbnCorrectSingleFields);
            if(!is_null($result)) return $result; 
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        // если не удалось, добавляем в поля, где может быть несколько isdn
        $isbnCorrectMultiFields = MainConfig::CORRECTMULTIFIELDSTOINSERT;
        try {
            $result = $this->updateIsbn($bookId, $isbn, $isbnCorrectMultiFields);
            if(!is_null($result)) return $result; 
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // метод, реализующий добавление исбн в таблицу, проверяет, что соответствующее поле пустое
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

    // обновляем только подходящие поля, если isbn2 что-то содержит, пробуем записать в следующее
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
                    $isbn = $book->$field . MainConfig::ISBNSDEVIDER . $isbn;
                    $this->doIsbnFieldUpdate($bookId, $isbn, $field);
                    return $field;
                }
                catch (Exception $e) {
                    throw new Exception("COULD NOT INSERT $isbn INTO FIELD $field OF BOOK $bookId MESSAGE: " . $e->getMessage());
                }
            }

        }
    }


    // функция обновления поля в БД
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

    // метод, который проходит по всем полям, где могут храниться исбн и возвращает их в массиве
    private function isbnSearcherInIsbnsFields (Object $book, IsbnExtractor $extractor) {
        $isbnSingleFields = MainConfig::ISBNSINGLEFIELDS;
        foreach ($isbnSingleFields as $isbnField) {
            if (!is_null($book->$isbnField)) {$extractor->setStringContaining($book->$isbnField);}
            $isbns[$isbnField] = $extractor->getAllIsbns();
            $extractor->reset();
        }
        $isbnMultiFields = MainConfig::ISBNMULTIFIELDS;
        foreach ($isbnMultiFields as $isbnField) {
            $isbnsRaws = explode(MainConfig::ISBNDEVIDER2, $book->$isbnField);
            $counter = 0;
            foreach ($isbnsRaws as $isbnRaw) {
                if (!is_null($book->$isbnField)) {$extractor->setStringContaining($isbnRaw);}
                $isbns[$isbnField . "[$counter]"] = $extractor->getAllIsbns();
                $extractor->reset();
                $counter++;
            }
        }
        return $isbns;
    }

    public function getIdS() {
       return $this->ids;
    }
}