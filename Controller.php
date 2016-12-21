<?php

/*==========================================================================================================
 * All your controllers should extend this class.
 * If you want actions performed before and/or after your route function, add 'before' and/or 'after'
 * methods to your class and name your route methods in this fashion: <name>_do. e.g.:
 * public function index_do() {}. If you do not want 'before' or 'after' to be called for a specific route function,
 * simply do not include  '_do' after the function's name, for example if you change 'index_do' to 'index', it will be
 * called directly.
 * You can pass arguments from the before function to your route function by changing the $this->args property.
 *========================================================================================================*/

//TODO BUG: controller classes should create an empty constructor
abstract class Controller 
{
    public $args = [];
    public $view = null;
    
    public function __call($action, $args) {
        $this->args =  $args;

        if(method_exists($this, 'before')) {
            $this->before();
        }
        
        $actionFunction = $action . "_do";
        $this->$actionFunction($this->args);
        
        if(method_exists($this, 'after')) {
            $this->after();
        }
    }
    
    
    public function setView($viewFile) {
        //$this->view = new View($viewFile);
        $this->view = VIEWS . $viewFile . ".php";
    }
    
    
    public function render($args = null) {
        if(is_array($args)) {
            foreach($args as $key => $value) {
                $args[$key] = htmlspecialchars($value);
            }
            extract($args);
        }
        //$this->view->render($args);
        include($this->view);
    }
           
    public function getModel($modelName) {
        $modelPath = MODELS . ucfirst($modelName) . ".php";
        if(!file_exists($modelPath)) {
            return false;
        }
        
        require($modelPath);  //TODO Use autoloader!
        return new $modelName();
    }
}