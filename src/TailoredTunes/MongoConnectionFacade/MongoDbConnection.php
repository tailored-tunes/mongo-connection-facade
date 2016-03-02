<?php
namespace TailoredTunes\MongoConnectionFacade;

use Exception;
use MongoClient;

class MongoDbConnection
{

    /**
     * @var String the hostname of the database
     */
    private $host;
    /**
     * @var String the database username
     */
    private $username;
    /**
     * @var String the database password
     */
    private $password;
    /**
     * @var String the database db
     */
    private $db;
    /**
     * @var Integer the port the db runs on
     */
    private $port;

    /**
     * @var MongoClient db connection
     */
    private $connection;

    /**
     * @param String $host The hostname the database operates on
     * @param String $username The username used to connect to the database
     * @param String $password The password used to connect to the database
     * @param String $db The database
     * @param Integer $port The port the db listens on
     */
    public function __construct($host, $db, $username = "", $password = "", $port = 43047)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
        $this->port = $port;
    }

    public function __get($name)
    {
        return $this->connection()->$name;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->connection(), $name], $arguments);
    }

    /**
     * Forms the connection String
     * @return String
     */
    public function connectionString()
    {
        if (empty($this->username) || empty($this->password)) {
            return sprintf(
                "mongodb://%s:%d/%s",
                $this->host,
                $this->port,
                $this->db
            );
        }
        return sprintf(
            "mongodb://%s:%s@%s:%d/%s",
            $this->username,
            $this->password,
            $this->host,
            $this->port,
            $this->db
        );
    }

    /**
     * Gets the connection
     * @return MongoClient
     */
    public function connection()
    {
        if (empty($this->connection)) {
            try {
                $x = new MongoClient($this->connectionString());
                $this->connection = $x->selectDB($this->db);
            } catch (Exception $e) {
                // retry, mostly when mongodb has been restarted in order to get a new connection
                $maxRetries = 5;
                for ($counts = 1; $counts <= $maxRetries; $counts++) {
                    try {
                        $x = new MongoClient($this->connectionString());
                        $this->connection = $x->selectDB($this->db);
                    } catch (Exception $e) {
                        continue;
                    }
                    return;
                }
            }
            return $this->connection;
        }
    }
}
