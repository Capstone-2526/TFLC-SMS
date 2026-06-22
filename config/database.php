<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'school_management';
    private $username = 'root';
    private $password = '';
    public $conn;

    //THE BRIDGE - this lines create ouf PDO Conenction, We use PDO because it is SECURE, OBJECT ORIENTED
    //   way to talk to our MYSQL Database, It allow us to use Prepared Statements, which protect the
    //school's data fromSQL injections attacks
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>