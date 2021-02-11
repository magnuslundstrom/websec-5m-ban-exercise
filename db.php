<?php

class Database
{
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $dbname = 'websec';
    public $db;
    public $statement;
    public function __construct()
    {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . '; charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ];
        try {
            $this->db = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $ex) {
            http_response_code(500);
            echo $ex;
            exit();
        }
    }

    public function prepare($sql)
    {
        $this->statement = $this->db->prepare($sql);
        return $this;
    }

    public function bindAndExecute($values = [])
    {
        for ($i = 0; $i < count($values); $i += 2) {
            $this->statement->bindValue($values[$i], $values[$i + 1]);
        }
        $this->statement->execute();
        return $this;

    }

    public function getOne()
    {
        return $this->statement->fetch();
    }
}
