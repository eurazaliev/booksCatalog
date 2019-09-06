<?php
namespace App;

use App\ConfigDb;
use PDO;

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

}
