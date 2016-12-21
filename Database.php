<?php

abstract class Database
{
    public static function connect() {
        return new PDO(DB_DBMS.":host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    }
}