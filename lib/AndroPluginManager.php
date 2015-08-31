<?php
class AndroPluginManager
{
    public $type;
    public $plugins;

    public function __construct()
    {
    }

    public function loadPlugin($name)
    {
        global $AG;
        if (file_exists($AG['dirs']['lib'] .'plugins/' .$name .'.php')) {
            include_once $AG['dirs']['lib'] .'plugins/' .$name .'.php';
        } elseif (file_exists($AG['application'] .'plugins/' .$name .'.php')) {
            include_once $AG['dirs']['application'] .'plugins/' .$name .'.php';
        } else {
            trigger_error("AndroPluginManager: " .$name ." not found", E_USER_ERROR);
            return false;
        }
        $objName = 'AndroPlugin_' .$name;
        $this->plugins[$name] = new $objName();
        return $this->plugins[$name];
    }
}
