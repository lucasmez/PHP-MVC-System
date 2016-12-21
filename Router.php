<?php

class Router
{
    // Array containing information about regex for routes, http verbs, controllers and actions
    // Example: $routes['/^\/about\/addNew\/(?\w+)$/i'] = 
    // [
    //  'POST' => [controller'=>'cont', 'action'=>'act2'],
    //  'GET'  => [controller'=>'cont', 'action'=>'act'],
    // ];
    // This will match an url such as /about/addNew/hi123 to method 'act' of the 'cont' controller for GET requests
    // and method 'act2' of the 'cont' controller for POST requests.
    private $routes = []; 

    
    /*==============================================================================
     * This function will construct regular expressions out of the $path parameter
     * and push it to the $routes array, along with $controller and $action and $method.
     *
     * $app = new Router();
     * $app->add($path, $controller, $action)
     * $path can be a string or a regular expression that starts and ends with '/'. 
     * It can also contain variables enclosed in brackets e.g. /home/[id], which can only 
     * contain alphanumeric characters, no symbols.
     * $controller and $action can contain back-references to the path regex.
     * $action cannot be reserved words 'before' or 'after'.
     * In case $path is not a regex, a back-reference will refer to variables ([var]). Examples:
     *
     * $app->add('/', 'home', 'index'); 
     *           --> e.g. / => controller='home', action='index
     *
     * $app->add('/about/addNew/[id]', 'about', 'addnew');    
     *           --> e.g /about/addNew/12345hello  => controller='about', action='addnew'
     *
     * $app->add('/login/[controller]/[action]/[id]', '$1', '$2'); 
     *           --> e.g. /login/index/addnew/123 => controller='index', action='addnew' 
     *
     * $app->add('/^\/(\d{3})\/(\w+)$/', 'api', '$2');            
     *           --> e.g. /123/delete   => controller='api, action='delete'     
     * An HTTP method can be specified as the last argument. It defaults to GET as the
     * last examples showed. e.g.:
     * 
     * $app->add('/login', 'login', 'dologin', 'POST');
     *==============================================================================*/
    
    public function add($path, $controller, $action, $method = 'GET') {
        // =====Parse path====
        if(preg_match('/^\/.*\/$/', $path)) { //If path is regex, simply add it to routes array
            $path .= 'i';
        }
        
        else { //Replace strings inside [] with regex (\w+). Those are supposed to be variables
            $path = preg_replace('/\//', '\/', $path);  //replace '/' with '\/'  
            $path = preg_replace('/\[(\w+)]/', '(?<$1>\w+)', $path);
            $path = "/^" . $path . "$/i";
        }
        
        //If action is a keyword ('before' or 'after') throw exception
        if($action === 'before' || $action === 'after') {
            throw new Exception("Action cannot be 'before' or 'after'");
        }
        
        // ====Register Routes====
        $this->routes[$path][$method] = array();
        $this->routes[$path][$method]['controller'] = $controller;
        $this->routes[$path][$method]['action'] = $action;
    }
    
    
    /*======================================================================
     * The following are wrapper functions for the add() method.
     * The method name reflects the HTTP method the path expects.
     *=====================================================================*/
    public function get($path, $controller, $action) {
        $this->add($path, $controller, $action, 'GET');
    }
    
    public function post($path, $controller, $action) {
        $this->add($path, $controller, $action, 'POST');
    }
    
    public function delete($path, $controller, $action) {
        $this->add($path, $controller, $action, 'DELETE');
    }
    
    /*=======================================================================================
     * An $url string as argument and finds a match in the $routes array.
     * If a match is found it constructs and returns an array containing information about the 
     * controller, the action and arguments that are to be passed to the action, otherwise false
     * is returned. Return value e.g. :
     * [
     *      'controller'    => 'home',
     *      'action'        => 'index',
     *      'args'          => ['id' => 123]
     * ]
     *========================================================================================*/
    public function matchAndParse($url) {
        $httpVerb = $_SERVER['REQUEST_METHOD'];
        $matchResult = [];
        $matchResult['args'] = [];
        $foundFlag = false;
        
        // Search for matching path
        foreach($this->routes as $pathPattern => $value) {
            if(preg_match($pathPattern, $url, $matches) && isset($value[$httpVerb])) {
                $foundFlag = true;
                break;
            }
        }
        
        if(!$foundFlag) {
            return false;
        }
        //Parse controller and action
        $controller = $this->routes[$pathPattern][$httpVerb]['controller'];
        $matchResult['controller'] = ($controller[0] == '$') ? $matches[ltrim($controller, '$')] : $controller;
        
        $action = $this->routes[$pathPattern][$httpVerb]['action'];
        $matchResult['action'] = ($action[0] == '$') ? $matches[ltrim($action, '$')] : $action;
        
        $matchResult['args'] = $matches;
        
        //If action is a keyword ('before' or 'after') throw exception
        if($matchResult['action'] === 'before' || $matchResult['action'] === 'after') {
            throw new Exception("Action cannot be 'before' or 'after'");
        }
        
        return $matchResult;
    }
    
    /*===================================================================================
     * After getting the controller, action and arguments from the matchAndParse function,
     * it checks if the file containing the controller exists, and if the action method exists
     * in the controller class. It then dispatches the route by calling the action in the controller
     *====================================================================================*/
    public function dispatch($url) {
        $url = ($url === '/') ? $url : ('/' .  $url);
        $match = $this->matchAndParse($url);
        $controllerClass = ucfirst($match['controller']);
        $action = $match['action'];
     
        // Check if route, and controller file exist
        if(!$match || !file_exists("../App/Controllers/$controllerClass.php")) {   //In case route does not exist throw exception //TODO use conf paths file
            throw new Exception("Route does not exist.");
        }
        
        require_once APP."Controllers/$controllerClass.php";  //TODO USE AUTOLOADER LATER
        $controller = new $controllerClass;
        
        // Check if controller method (the action) exists
        if((!method_exists($controller, $action . '_do'))  && (!method_exists($controller, $action)) ) {
            throw new Exception("Action does not exist.");
        }
        
        // Clean arguments array
        $args = $this->cleanArguments($match['args']);

        // Dispatch
        $controller->$action($args);
    }
    
    /*====================================
     * Return the $routes array
     *====================================*/
    public function getRoutes() {
        return $this->routes;
    }
    
    /*===========================================================
     * Removes numeric keys from $args array and return new array.
     * e.g.  if $args = [0=>'first', 1=>'second', 'prop'=>'third']
     * return value is ['prop' => 'third].
     *===========================================================*/
    private function cleanArguments($args) {
        $result = [];
        foreach($args as $key => $value) {
            if(!is_numeric($key)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    
    
}
