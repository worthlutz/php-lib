<?php
namespace TcpdfOC;

use TcpdfOC\Tcpdf_OC;
use TcpdfOC\Tcpdf_OCCD;

// Optional Content Group Dictionary (8.11.2.1)
class Tcpdf_OCG extends Tcpdf_OC {

  const ITEM_TYPE = 'OCG';

  // list of Optional Content Membership Dictionaries
  static $items = array();
  static $index = array();

  static $_tree_level_1 = array();  // list of 1st level of OCGs

  static $defaults = array( 'baseState' => 'ON' );
  static $defaultOCCD = NULL;


	static function reset() {
    parent::$ocDefs = NULL;
    parent::$ocDefs = array();
    self::$items = NULL;
    self::$items = array();
    self::$index = NULL;
    self::$index = array();
  }

  static function nestedOrderString($items, $children) {
      $s = "";
      foreach ($children as $key) {
        $s .= " ".$items[$key]->get('objnum')." 0 R";
        if (!empty($items[$key]->children)) {
          $s .= " [";
          $s .= nestedOrderString($items, $items[$key]->children);
          $s .= " ]";
        }
      }
      return $s;
    }

  static function getOrderString() {

    $s = "";
    for ($i = 0, $len = count(self::$items); $i < $len; $i++) {
      if (!isset(self::$items[$i]->parent)) {
        $s .= " ".self::$items[$i]->objnum." 0 R";
        if (!empty(self::$items[$i]->children)) {
          $s .= " [";
          $s .= self::nestedOrderString(self::$items, self::$items[$i]->children);
          $s .= " ]";
        }
      } else {
      }
    }
    return $s;
  }

  static function getObjArrayStrings() {
    $otherKeys = array('print', 'export', 'locked');
    $s = array();
    $s['all'] = "";
    $s['view'] = "";
    $s['noView'] = "";
    foreach ($otherKeys as $key) {
      $s[$key] = "";
    }
    foreach (self::$items as $ocg) {
      $s['all'] .= ' '.$ocg->objnum.' 0 R';
      if ($ocg->view) {
        $s['view'] .= ' '.$ocg->objnum.' 0 R';
      } else {
        $s['noView'] .= ' '.$ocg->objnum.' 0 R';
      }
      foreach ($otherKeys as $key) {
        if ($ocg->$key) {
          $s[$key] .= ' '.$ocg->objnum.' 0 R';
        }
      }
    }
    $s['order'] = self::getOrderString();
    return $s;
  }

  //  OCProperties entry for Document catalog (8.11.4.2)
  static function getOCProperties() {
    $out = "";
    if ( !empty(self::$items) ) {
      $objStrings = self::getObjArrayStrings();
      $out .= ' /OCProperties <<';
      // list all ocgs
      $out .= ' /OCGs ['.$objStrings['all'].' ]';
      // get required default Optional Content Configuration Dictionary
      if ( !isset(self::$defaultOCCD) ) {
        self::$defaultOCCD = new Tcpdf_OCCD('OC Default', $objStrings);
      }
      $out .= ' /D '.self::$defaultOCCD->getDictionary($objStrings);
      $out .= ' >>';
    }
    return $out;
  }

//************* end static properties and functions ****************************


	/**
	 * The ocg indentifier used in the resource dictionaryto map the ocg to the
   * object id.  Its value is set in the constructor.<br/>
   * This varible will take the form "ocgxxx" where "xxx" is a zero padded
   * integer to make a unique identifier.  Example: ocg001
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

  private $name;    //this is the string which will be displayed to viewer
  private $print;
  private $view;
  private $export;
  private $locked;  // locks ocg-prevents user changing visibility
  private $minZoom;
  private $maxZoom;
  private $children = array();

  protected $parent = '_ROOT'; // FOR ALL TOP LEVEL


  public function __construct($name, $options=array()) {
    if (self::exists($name)) {
      // item with this name already exists
      parent::$lastError = "OCG with name(".$name.") already exists.";
      echo "<br/>".self::$lastError;
      return FALSE;

    } else {

      self::$ocDefs[$name] = $this;
      #self::$items[$name] = $this;  // indexed by name
      //   -or-
      $indexNum = count(self::$items);
      self::$items[] = $this;  // indexed by integer
      self::$index[$name] = $indexNum;


      $this->name = $name;
			$this->id = self::setId();

      $this->objnum = NULL;

      // set from $options array or default value
      $this->print   = isset($options['print'])   ? $options['print']   : TRUE;
      $this->view    = isset($options['view'])    ? $options['view']    : TRUE;
      $this->export  = isset($options['export'])  ? $options['export']  : TRUE;
      $this->locked  = isset($options['locked'])  ? $options['locked']  : FALSE;
      $this->minZoom = isset($options['minZoom']) ? $options['minZoom'] : NULL;
      $this->maxZoom = isset($options['maxZoom']) ? $options['maxZoom'] : NULL;

      $this->parent  = isset($options['parent'])  ? $options['parent']  : NULL;

      if ( isset($this->parent) ) {
        if ( isset(self::$index[$this->parent]) ) {
          self::$items[self::$index[$this->parent]]->children[] = $indexNum;
        } else {
          echo "Parent(".$this->parent.") of ".$this->name." not defined yet!.";
        }
      }

    }
  }

  public function addChild($child) {
    $this->children[] = $child;
  }

  public function getObjDefinitionString() {
    $out =  ' << /Type /OCG';
    $out .= ' /Name ('.$this->name.')';
    #$out .= ' /Name ('.$this->id.')';

    $out .= ' /Usage <<';
    $out .= ' /Print << /PrintState /'.($this->print ? 'ON' : 'OFF').' >>';
    $out .= ' /View << /ViewState /'.($this->view ? 'ON' : 'OFF').' >>';

    $zoomDetail = '';
    if (isset($this->minZoom) AND $this->minZoom > 0 ) {
     $zoomDetail .= sprintf(' /min %.1f', $this->minZoom );
    }
    if (isset($this->maxZoom) AND $this->maxZoom > 0 ) {
     $zoomDetail .= sprintf(' /max %.1f', $this->maxZoom );
    }
    if ( !empty($zoomDetail) ) {
     $out .= ' /Zoom <<'.$zoomDetail.' >>';
    }

    $out .= ' >> >>';
    return $out;
  }

  public function getResourceDictString() {
    return ' /'.$ocg->id.' '.$ocg->objnum.' 0 R';
  }

}
