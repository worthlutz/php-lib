<?php
namespace TcpdfOC;

// PDF Optional Content (8.11)
abstract class Tcpdf_OC {

  protected static $lastError;

  protected static $ocDefs = array();

	static function reset() {
    self::$ocDefs = NULL;
    self::$ocDefs = array();
  }

  static function getLastError() {
    return self::$lastError;
  }

	static function setId() {
	  return sprintf( strtolower(static::ITEM_TYPE).'%03d', (count(static::$items) ) );
  }

  // this is the resource id from property subdictionary (8.11.3.2)
  static function exists($name) {
    return isset(self::$ocDefs[$name]);
  }

  // this is the resource id from property subdictionary (8.11.3.2)
  static function getResourceId($name) {
    if ( isset(self::$ocDefs[$name]) ) {
      return self::$ocDefs[$name]->id;
    } else {
      self::$lastError = "Requested Optional Content Definition (".$name.") does not exist";
      return FALSE;
    }
  }

  static function getResourceDictionaryEntries() {
    $out = "";
    foreach (static::$items as $oc) {
      $out .= ' /'.$oc->id.' '.$oc->objnum.' 0 R';
    }
    return $out;
  }

  public function get($property) {
    if (isset($this->$property)) {
      return ($this->$property);
    }
  }

  public function setObjId($value) {
    $this->objnum = $value;
  }

	static function xxx() {
	  static::$yy = 1;  // this will refer to the yy defined in the extending class
	  self:$yy = 1;     // this will refer to the yy defined in this class
	}
}
