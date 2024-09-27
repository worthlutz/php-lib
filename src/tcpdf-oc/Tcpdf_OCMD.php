<?php
namespace TcpdfOC;

use TcpdfOC\Tcpdf_OC;

// Optional Content Membership Dictionary (8.11.2.2)
class Tcpdf_OCMD extends Tcpdf_OC {

  const ITEM_TYPE = 'OCMD';

  // list of Optional Content Membership Dictionaries
  static $items = array();

//************* end static properties and functions ****************************

	/**
	 * The ocmd resource id used in the Properties subdictionary of the
   * resource dictionary to map the ocmd to the
   * object id.  Its value is set in the constructor.<br/>
   * This varible will take the form "ocmdxxx" where "xxx" is a zero padded
   * integer to make a unique identifier.  Example: ocmd001
	 * @private
	 */
  protected $id;

	/**
	 * The pdf document indirect object number assigned to this ocg.  This
   * value is to be set with the class method setObjId while enumerating
   * the indirect objects.
   * @private
	 */
  protected $objnum;

	private $OCGs = array();  // ARRAY OF NAMES USED TO REFERENCE OCGs ? IDs NOT SET YET
	private $visibilityPolicy;  // [ 'AllOn' | 'AnyOn' | 'AnyOff' | 'AllOn' ]
	private $visibilityExpression;


	static $count_test = 0;

	static function reset() {
    parent::$ocDefs = NULL;
    parent::$ocDefs = array();
    self::$items = NULL;
    self::$items = array();
  }

  public function __construct($name, $options=array()) {
    if (self::exists($name)) {
    } else {
      self::$ocDefs[$name] = $this;
      self::$items[] = $this;

      self::$count_test++;

      $this->name = $name;
      $this->id = self::setId();
      $this->objnum = self::$count_test;    // NOT DEFINED YET

  		echo "options OCGs = ".$options['OCGs'];

      // set from $options array or default value
      $this->OCGs = isset($options['OCGs']) ? $options['OCGs'] : array();

      $this->visibilityPolicy   = isset($options['visibilityPolicy']) ?
  		                                  $options['visibilityPolicy'] : 'AnyOn';

  		$this->visibilityExpression = NULL;
    }
  }

  public function getObjDefinitionString() {
    $out =  ' << /Type /OCMD';

    $out .= ' /OCGs [';
		for ($i = 0, $len = count($this->OCGs); $i < $len; $i++) {
		  $out .= ' '.self::$ocDefs[$this->OCGs[$i]]->get('objnum').' 0 R';
		}
		$out .= ' ]';
    $out .= ' /P /'.$this->visibilityPolicy;

    $out .= ' >>';
    return $out;
  }
}
