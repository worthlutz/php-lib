<?php
namespace PhpLib\Base;

abstract class Singleton {

    protected function _construct() {}

    final public static function getInstance($config) {
        static $instances = array();
        $className = get_called_class();
        if ( !isset($instances[$className]) ) {
            $instances[$className] = new $className($config);
        }
        return $instances[$className];
    }

    final private function __clone() {}
}
?>
