<?php
namespace PdfMaps\Pdf;

class PdfMapDef {

    public $x;
    public $y;
    public $width;
    public $height;
    public $units;

    function __construct($x, $y, $w, $h, $units='in') {
        $this->x = $x;
        $this->y = $y;
        $this->width = $w;
        $this->height = $h;
        $this->units = $units;
    }
}
?>
