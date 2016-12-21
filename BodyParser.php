<?php

class BodyParser
{
    
    /*===============================================================================
     * Returns array elements from the global variable $_REQUEST.
     * If $queryStr is null, $_REQUEST is returned.
     * If $queryStr is a string, e.g. 'query' , $_REQUEST['query'] is returned.
     * If $queryStr is an array, another array cotaining data from $_REQUEST, indexed
     * by the argument array is returned. e.g:
     * $queryStr = ['name', 'age', 'prop'], returns
     * [
     *      'name' => $_REQUEST['name'],
     *      'age'  => $_REQUEST['age'],
     *      'prop' => null (In case $_REQUEST['prop'] is not set)
     * ]
     *===============================================================================*/
    
    public static function parse($queryStr = null) {
        $results = [];
        
        if(!$queryStr) {
            return $_REQUEST;
        }
        
        if(!is_array($queryStr)) {
            $queryStr =  array($queryStr);
        }
        
        foreach($queryStr as $value) {
            if(!isset($_REQUEST[$value])) {
                $results[$value] = null;
            }
            else {
                $results[$value] = $_REQUEST[$value];
            }
        }
        
        
        if(count($results) === 1) {
            $results = $results[$value];
        }
    
        return $results;
        
    }
    
    
    
}