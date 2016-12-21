<?php

abstract class Model extends Database
{
    public static $dbConnection = null;
    
    public function __construct() {
        if(Model::$dbConnection === null) {
            Model::$dbConnection = $this->connect(); 
        }
    }
    
    public function getConnection() {
        return Model::$dbConnection;
    }
}