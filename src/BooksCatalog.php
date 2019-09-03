<?php
namespace App;

use App\ConfigDb;
use \ PDO;

class BooksCatalog
{
    const DEFAULTLIMIT = 10;
    const DEFAULTOFFSET = 0;
    const TABLENAME = 'books_catalog';
    
    private $connect;
    private $tableName;
    private $limit;
    private $minId;
    private $ids;

    function __construct(ConfigDb $db, int $minId, int $limit) {
        $this->connect = $db->connectDb(); 
        $this->tableName = self::TABLENAME;
        $this->limit = $limit;
        $this->minId = $minId;
        $this->maxId = $this->fetchMaxId();
        $this->ids = $this->fetchIds();
    }
    
    public function getIdS() {
       return $this->ids;
    }

    public function getMaxId() {
       return $this->maxId;
    }

    private function fetchMaxId () {
        if($this->connect) {
//            $sql = "SELECT MAX(id) FROM $this->tableName WHERE id > $this->minId LIMIT $this->limit";
            $sql = "SELECT
                MAX(ID)
                FROM
                    ( 
                     SELECT ID 
                      FROM $this->tableName
                      WHERE id > :minId
                      ORDER BY ID
                     LIMIT :limit
                ) as T1";
            $stmt = $this->connect->prepare($sql);
            $stmt->bindValue(':limit', $this->limit, PDO::PARAM_INT);
            $stmt->bindValue(':minId', $this->minId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn();
        }
        return false;
    }

    private function fetchIds () {
        if($this->connect) {
            $sql = "SELECT id FROM $this->tableName
                   WHERE id > :minId
                   ORDER BY id
                   LIMIT :limit
            ";

            $stmt = $this->connect->prepare($sql);
            $stmt->bindValue(':limit', $this->limit, PDO::PARAM_INT);
            $stmt->bindValue(':minId', $this->minId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return false;
    }

/*
    public function setLimit (int $limit) {
        $this->limit = $limit;
        return true;
    }

/*
    public function getCount () {
        if($this->connect) {
            $sql = "SELECT count(*) FROM $this->tableName";
            $stmt = $this->connect->prepare($sql);
            $stmt->execute();
            return $stmt->fetchColumn();
        }
        return false;
    }

    public function getOffset () {
        return $this->offset;
    }

    public function setOffset (int $offset = 0) {
        //тут проверим, что если мы пытаемся назчанить сдвиг больше, чем записей в базе, то назначаем принудительно сдвиг-1
        //ну и 0 по-умолчанию и целевое значение, если оно не превышает кол-во записей в БД
        $all = $this->getCount();
        $offset > $all ? $this->offset = $all-- : $this->offset = $offset;
        return true;
    }


    public function getLimit () {
        return $this->limit;
    }



    public function getAll (int $minId) {
        if($this->connect) {
            $sql = "SELECT * FROM $this->tableName
                where id > $minId
                ORDER BY id
                LIMIT :limit
            ";
            $stmt = $this->connect->prepare($sql);
            $stmt->bindValue(':limit', $this->getLimit(), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        return false;
    }
*/
}
