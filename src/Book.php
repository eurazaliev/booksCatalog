<?php
namespace App;

use App\ConfigDb;
use \ PDO;

class Book
{
    const TABLENAME = 'books_catalog';

    private $connect;
    private $tableName;

    private $description_ru;
    private $isbn2;
    private $isbn3;
    private $isbn4;
    private $isbn_wrong;

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

    public function getIdS() {
       return $this->ids;
    }
}