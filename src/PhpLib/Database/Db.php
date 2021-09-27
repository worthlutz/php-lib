<?php
namespace PhpLib\Database;

use PhpLib\Base\Singleton;

// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// ++ This is an abstract parent class for Database access.

abstract class Db extends Singleton {

  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // ++ required values to be provided by the extending class

  const CLASS_PREFIX = '(CLASS_PREFIX_not_defined_in_extending_DB_class)_';

  // +++ connection parameters must be defined in the extending class

  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // ++ common class variables

  protected $linkid;         // DB link identifier
  protected $result;         // query result

  protected $lastError = ''; // last error message saved

  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // ++ constructor for Singleton Instance
  // +++ connects to the database

  protected function __construct($config=NULL) {
    parent::__construct($config);
    // connect should throw exception on failure
    // note that will crash 1st call to Db::getInstance() !!!
    $this->connect();
  }
  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +++ "connect" should setup connection parameters & connect to the database.
  // +++ connection handle should be assigned to "$this->linkid".
  // +++ connect should throw exception on failure

  abstract protected function connect();

  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +++ This function calls common db functions with the appropriate prefix.
  // +++ Call db functions without the specific db function prefix.
  //     For example, pg_fetch_assoc() would be:  $classInstance->fetch_assoc()
  // +++ Individual functions may be overridden below or in the extending
  //     class in which case this function will not be used.

  public function __call($fnName, $args){
    $function = static::CLASS_PREFIX.$fnName;
      //echo "<br/>calling fn: ".static::CLASS_PREFIX.$fnName;
      array_unshift($args, $this->result);

      if (!function_exists($function)) {
        throw new \Exception("Database function($fnName) does not exist.", 1);
      }

      return call_user_func_array($function, $args);
    }
  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

  abstract public function query($queryString);

  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +++ get the last error message

  public function getLastError() {
    return $this->lastError;
  }
  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
}
?>
