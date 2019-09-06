<?php
namespace App;

use PDO;
use PDOException;

class ConfigDb
{

    private $host;
    private $database; // имя базы данных
    private $user; // имя пользователя
    private $password; // пароль
    private $charset;
    private $socket = '/var/lib/mysql/mysql.sock';

    function __construct() {
        $config = parse_ini_file("db.ini");
        $this->database = $config['database'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->charset = $config['charset'];
        $this->host = $config['host'];
    }

    public function connectDb()
    {
        $dsn = "mysql:host=$this->host;dbname=$this->database;charset=$this->charset";
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new \PDO($dsn, $this->user, $this->password, $opt);
            return $pdo;
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}
