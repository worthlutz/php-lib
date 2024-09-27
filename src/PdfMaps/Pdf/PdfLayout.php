<?php
namespace PdfMaps\Pdf;

use PdfMaps\Pdf\Pdf;

//
// abstract class for defining a pdf layout
//
abstract class PdfLayout extends Pdf {

    protected $originalOptions;

    protected $orientation;  //  'A' (auto)|'P'|'L'
    protected $paperSize;    // 'ANSI_A'|'ANSI_B'|'ANSI_C'|'ANSI_D'|'ANSI_E'
    protected $units;        //  'pt'|'mm'|'cm'|'in'
    protected $zoom;   //  'fullpage'|'fullwidth'|'real'|'default'| #
    protected $layout; //  'SinglePage'|'OneColumn'|'TwoColumnLeft'|'TwoColumnRight'|'TwoPageLeft'|'TwoPageRight'
    protected $mode;   //  'UseNone'|'UseOutlines'|'UseThumbs'|'FullScreen'|'UseThumbs'|'UseAttachments'
    protected $sizeFactor;

    // ************************************************************************
    //    STATIC FUNCTIONS
    // ************************************************************************


    // ************************************************************************
    //    PUBLIC FUNCTIONS
    // ************************************************************************

    public function __construct($options) {

        $this->originalOptions = $options;

        $pdfFormat = isset($options->pdfFormat) ? $options->pdfFormat : new \StdClass();

        // set defaults
        //$orientation = isset($options->orientation) ? $options->orientation : 'L';

        if (!isset($pdfFormat->orientation)) {
            $this->orientation = 'L';
        } else if ($pdfFormat->orientation != 'A') {
            $this->orientation = $pdfFormat->orientation;
        } else if (isset($options->mapDetails->bounds)) {
            $this->orientation = self::determineOrientation($options->mapDetails->bounds);
        } else {
            $this->orientation = 'L';
        }

        $this->paperSize = isset($pdfFormat->paperSize) ? $pdfFormat->paperSize : 'ANSI_A';
        $this->units =     isset($pdfFormat->units)     ? $pdfFormat->units     : 'in';

        $this->zoom =      isset($pdfFormat->zoom)      ? $pdfFormat->zoom      : 'fullpage';
        $this->layout =    isset($pdfFormat->layout)    ? $pdfFormat->layout    : 'SinglePage';
        $this->mode =      isset($pdfFormat->mode)      ? $pdfFormat->mode      : 'UseThumbs';

        parent::__construct($this->orientation, $this->units, $this->paperSize);

        switch ($this->paperSize) {
          case 'ANSI_E':
            $this->sizeFactor = 4;
            break;
          case 'ANSI_D':  // fallthrough on purpose
          case 'ANSI_C':
            $this->sizeFactor = 2;
            break;
          case 'ANSI_B':  // fallthrough to default on purpose
          case 'ANSI_A':  // fallthrough to default on purpose
          default:
            $this->sizeFactor = 1;
            break;
        }

        $this->SetDisplayMode($this->zoom, $this->layout, $this->mode);

        // these margins are for the pdf code calculations
        // will not write outside of these
        $this->setMargins(0.1,0.1,0.1);      // this is Top, Left, Right margins
        $this->SetAutoPageBreak(TRUE, 0.0);  // this is bottom margin

        $this->setPrintHeader(FALSE);  // tcpdf settings...
        $this->setPrintFooter(FALSE);
    }

    //
    // override this function if map shape in layout is not related to paper orientation
    //
    public function determineOrientation($bounds) {
        // determine if landscape or portrait view
        $deltaX = $bounds[2] - $bounds[0];
        $deltaY = $bounds[3] - $bounds[1];
        if ($deltaX < $deltaY) {
            // portrait
            $orientation = 'P';
        } else {
            // landscape
            $orientation = 'L';
        }
        return $orientation;
    }

    public function saveFile($fileName) {
        $this->Output($fileName, 'F');
    }

    abstract public function getMapDef();  // mapDef defines location and size of map on page
    abstract public function create($options);     // TODO: define signature...


    // ************************************************************************
    //    PROTECTED FUNCTIONS
    // ************************************************************************

    protected function processOptionalContentArray($ocDef) {
          foreach ($ocDef as $name => $options) {
            $this->defineOC($name, $options);
          }
    }

    protected function getLayerTemplateId($layer) {
        //$fileName = '/var/www/html'.$layer['pdfFileName'];   //TODO: FIX TEMP DIR PATH...
        $fileName = $_SERVER['DOCUMENT_ROOT'].$layer['pdfFileName'];

        //echo(' ----- before setSourceFile ----- ');
        $numPages = $this->setSourceFile($fileName);  // FPDI method
        $templateId = $this->importPage(1);           // FPDI method
        return $templateId;
    }

    protected function getMapTemplates($layers) {

        for ($i = 0, $len = count($layers); $i < $len; $i++) {
            if ( $layers[$i]['type'] == 'CONTAINER' ) {
                $this->getMapTemplates($layers[$i]['items']);
            } else {
                $layers[$i]['templateId'] = $this->getLayerTemplateId($layers[$i]);
          }
        }
        return $layers;
    }

    protected function addMapTemplates($layers) {
        $mapDef = $this->getMapDef();

        for ($i = 0, $len = count($layers); $i < $len; $i++) {
            if ( $layers[$i]['type'] == 'CONTAINER' ) {

                $this->startOptionalContent($layers[$i]['ocId']);
                $this->addPdfMapLayers($mapDef, $layers[$i]['items']);
                $this->endOptionalContent();

            } else {

                if ( $layers[$i]['ocId'] !== '' ) {
                  $this->startOptionalContent($layers[$i]['ocId']);
                }

                if ( isset($layers[$i]['templateId']) AND
                           $layers[$i]['templateId'] != '' ) {
                    $templateId = $layers[$i]['templateId'];
                } else {
                    $templateId = $this->getLayerTemplateId($layers[$i]);
                }

                //$dims = $this->getPageDimensions();
                //echo("\n orientation= ".$dims['or']." width= ".$dims['wk']." height= ".$dims['hk']);
                //var_dump($mapDef);

                $this->useTemplate( $templateId,        // FPDI method
                                    $mapDef->x,
                                    $mapDef->y,
                                    $mapDef->width,
                                    $mapDef->height
                                  );

                if ( $layers[$i]['ocId'] !== '' ) {
                  $this->endOptionalContent();
                }
          }
        }
    }
}
?>
