<?php
namespace PdfMaps\Pdf;

class PdfOcDef {
    public $name;
    public $type = 'OCG';  //default

    public $view;
    public $print;
    public $minZoom;
    public $maxZoom;

    public $parent;


    function __construct() {
    }
}
?>
