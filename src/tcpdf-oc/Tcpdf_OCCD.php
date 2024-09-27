<?php
namespace TcpdfOC;

// Optional Content Configuration Dictionary (8.11.4.3)
class Tcpdf_OCCD {

  private $name;
  private $creator;
  private $baseState;
  private $ON;
  private $OFF;
  private $Intent;
  private $AS;
  private $Order;
  private $ListMode;
  private $RBGroups;

  public function __construct($name, $objStrings, $options=array()) {
    $this->name = $name;
    // set from $options array or default value
    $this->baseState = isset($options['baseState']) ? $options['baseState'] : 'ON';
  }

  public function getDictionary($objStrings) {
    $out = ' << ';

#      $out .= ' /Name '.$this->_textstring('OC_Default', $oid);
    $out .= ' /Name ('.$this->name.')';
#      $out .= ' /Creator '.$this->_textstring('TCPDF', $oid);
    $out .= ' /Creator (TCPDF)';
    $out .= ' /BaseState /'.$this->baseState;
    if ($this->baseState == 'ON') {
      $out .= ' /OFF ['.$objStrings['noView'].']';
    } else {
      $out .= ' /ON ['.$objStrings['view'].']';
    }
    $out .= ' /Intent /View';
    $out .= ' /AS [';

    $out .= ' << /Event /Print /Category [/Print /Zoom]';
    $out .= '  /OCGs ['.$objStrings['print'].' ] >>';

    $out .= ' << /Event /View /Category [/View /Zoom]';
    $out .= '  /OCGs ['.$objStrings['view'].' ] >>';

    $out .= ' << /Event /Export /Category [/Export]';
    $out .= ' /OCGs ['.$objStrings['export'].' ] >>';

    $out .= ' ]';
    $out .= ' /Order ['.$objStrings['order'].' ]';
    $out .= ' /ListMode /AllPages';
    //$out .= ' /RBGroups ['..']';   // NOT IMPLEMENTED YET
    $out .= ' /Locked ['.$objStrings['locked'].']';

    $out .= ' >> ';
    return $out;
  }
}
