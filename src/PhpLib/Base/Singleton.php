<?php
namespace PhpLib\Base;

abstract class Singleton {

    protected function _construct() {}

    final public static function getInstance() {
        static $instances = array();
        $className = get_called_class();
        if ( !isset($instances[$className]) ) {
            $instances[$className] = new $className();
        }
        return $instances[$className];
    }

    final private function __clone() {}
}
?>
