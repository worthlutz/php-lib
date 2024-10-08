<?php
namespace PdfMaps\Mapserver;

// *********************************************************************************************
//  COLOR methods
//  from: http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
// *********************************************************************************************

function hex2rgb($hex) {
  $hex = str_replace("#", "", $hex);

  if (strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
  } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
  }
  $rgb = array($r, $g, $b);
  //return implode(",", $rgb); // returns the rgb values separated by commas
  return $rgb; // returns an array with the rgb values
}

function rgb2hex($rgb) {
    $hex = "#";
    $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

    return $hex; // returns the hex value including the number sign (#)
}
// *********************************************************************************************

function get_rgb($olColor) {
  if (is_array($olColor)) {
    $rgb = $olColor;
  } else {
    // assume hex color
    $rgb = hex2rgb($olColor);
  }
  return $rgb;
}

function createLabelObject($olStyle) {
  $label = new \labelObj();


  // font

  $label->font = 'vera';
  $label->autoangle = \mapscript::MS_TRUE;
  $label->autofollow = \mapscript::MS_TRUE;

  if (isset($olStyle->fontSize)) {
    $size = (int) (0.5 * $olStyle->fontSize);
    $label->size = $size;
  }

  if (isset($olStyle->fontFillColor)) {
    $rgb = get_rgb($olStyle->fontFillColor);
    $label->color->setRGB($rgb[0],$rgb[1],$rgb[2]);
  }

  if (isset($olStyle->fontStrokeColor)) {
    $rgb = get_rgb($olStyle->fontStrokeColor);
    $label->outlinecolor->setRGB($rgb[0],$rgb[1],$rgb[2]);
  }

  if (isset($olStyle->fontStrokeWidth)) {
    $label->outlinewidth = $olStyle->fontStrokeWidth;
  }

  if (isset($olStyle->textAlign)) {
    switch ($olStyle->textAlign) {

      case 'left':  // text right of point
        $label->position = 'cr';
        break;

      case 'right':  // text left of point
        // this does not work (see offsetx calc in MS_Pdf)
        //$label->position = 'cl';
        break;

      default:
        // centered on point
        break;
    }
  }

  if (isset($olStyle->labelXOffset)) {
    //$labeloffsetx = $olStyle->labelXOffset;
    //
  }
  if (isset($olStyle->labelYOffset)) {
    $label->offsety = -$olStyle->labelYOffset;
  }

  // in case markers are too close together this will force all to have labels
  $label->force = \mapscript::MC_TRUE;
  return $label;
}

/*
 * This class holds static methods to convert OpenLayers Vector layers to MapServer layers
 *
 */

 class OlToMapserver {

  static function addClass(&$layer, $geomType, $olStyle) {
    //var_dump($olStyle);

    $class = new \classObj($layer);
    $class->name = 'class name';      // TODO: figure out for legend??
    $style = new \styleObj($class);

    if ($geomType === 'POINT') {

      if (isset($olStyle->externalGraphic)) {

        // This method was first used
        // removed because of problems with JC & "https"
        //$symbolText = $_SERVER['HTTP_REFERER'].$layerDef['style']->externalGraphic;

        //  images must be put into the mapfile symbols directory
        //  TODO: remove hard coded path "markers" and determine system which
        //        should match the Sencha "resources" directory
        $a = explode('/', $olStyle->externalGraphic);
        $symbolText = '../common/symbols/graphics/markers/'.$a[ (count($a) - 1) ];

        $style->updateFromString('STYLE SYMBOL "'.$symbolText.'" END');

        // TODO: What about graphicWidth? does MapServer just keep aspect ratio?
        $style->size = $olStyle->graphicHeight;

        // convert OpenLayers offsets to MapServer offsets
        $offsetX = $olStyle->graphicXOffset + ($olStyle->graphicWidth / 2);
        $offsetY = $olStyle->graphicYOffset + ($olStyle->graphicHeight / 2);

        $style->offsetx = $offsetX;
        $style->offsety = $offsetY;

      } else {

        $symbolName = isset($olStyle->graphicName) ? $olStyle->graphicName : 'circle';

        if (isset($olStyle->fillColor)) {
          // MapServer has fill in SYMBOL so they should be defined with
          // and without fill (i.e. triangle & triangle_fill) in mapfile
          $symbolName = $symbolName . "_fill";

          $rgb = get_rgb($olStyle->fillColor);
          $style->color->setRGB($rgb[0],$rgb[1],$rgb[2]);
          if (isset($rgb[3])) {
            $layer->setOpacity( (int) (100 * $rgb[3]) );
          }
        }

        $style->updateFromString('STYLE SYMBOL "'.$symbolName.'" END');
        if (isset($olStyle->radius)) {
          $style->size = $olStyle->radius * 2;
        }

        if (isset($olStyle->strokeColor)) {
          $rgb = get_rgb($olStyle->strokeColor);
          $style->outlinecolor->setRGB($rgb[0],$rgb[1],$rgb[2]);
          if (isset($rgb[3])) {
            $layer->setOpacity( (int) (100 * $rgb[3]) );
          }
        }

        if (isset($olStyle->strokeWidth)) {
          $style->width = $olStyle->strokeWidth;
        }
      }

      if (isset($olStyle->label) and $olStyle->label === TRUE) {
        $label = createLabelObject($olStyle);
        $class->addLabel($label);
      }

    } else if ($geomType === 'LINESTRING') {

      if (isset($olStyle->strokeColor)) {
        $rgb = get_rgb($olStyle->strokeColor);
        $style->color->setRGB($rgb[0],$rgb[1],$rgb[2]);
        if (isset($rgb[3])) {
          $layer->setOpacity( (int) (100 * $rgb[3]) );
        }
      }

      if (isset($olStyle->strokeWidth)) {
        $style->width = $olStyle->strokeWidth;
      }

      // opacity: MapServer 0-100  -  OpenLayers 0-1
      if (isset($olStyle->strokeOpacity)) {
        $layer->setOpacity( (int) (100 * $olStyle->strokeOpacity) );
      }

      if (isset($olStyle->strokeLinecap)) {
        $style->linecap = $olStyle->strokeLinecap;
      }

      //TODO:  FIX THE FOLLOWING...
      if (isset($olStyle->strokeDashstyle)) {
      }

      //$style->size = 15;

      if (isset($olStyle->label) and $olStyle->label === TRUE) {
        $label = createLabelObject($olStyle);
        $class->addLabel($label);
      }

    } else if ($geomType === 'POLYGON') {

      if (isset($olStyle->fill)) {
        $rgb = get_rgb($olStyle->fill);
        $style->color->setRGB($rgb[0],$rgb[1],$rgb[2]);
        if (isset($rgb[3])) {
          $layer->setOpacity( (int) (100 * $rgb[3]) );
        }
      }

      // opacity: MapServer 0-100  -  OpenLayers 0-1
      if (isset($olStyle->fillOpacity)) {
          $layer->setOpacity( (int) (100 * $olStyle->fillOpacity) );
      }

      // ++ create outline style ++
      $style2 = new \styleObj($class);

      if (isset($olStyle->strokeColor)) {
        $rgb = get_rgb($olStyle->strokeColor);
        $style2->outlinecolor->setRGB($rgb[0],$rgb[1],$rgb[2]);
      }

      // opacity: MapServer 0-100  -  OpenLayers 0-1
      // overides opacity set from strokeColor
      if (isset($olStyle->strokeOpacity)) {
        //$style2->set('opacity', (int) (100 * $olStyle->strokeOpacity));
        $layer->setOpacity( (int) (100 * $olStyle->strokeOpacity) );
      } else {
        //$style2->set('opacity', 100);
        $layer->setOpacity(100);
      }

      if (isset($olStyle->strokeWidth)) {
        $style2->width = $olStyle->strokeWidth;
      }

      if (isset($olStyle->strokeLinecap)) {
        $style2->linecap = $olStyle->strokeLinecap;
      }

      //TODO:  FIX THE FOLLOWING...
      if (isset($olStyle->strokeDashstyle)) {
      }

      if (isset($olStyle->label) and $olStyle->label === TRUE) {
        $label = createLabelObject($olStyle);
        $class->addLabel($label);
      }
    }
  }

  static function createPointLayer($map, $layerDef) {
    $layer = new \layerObj($map);
    $layer->name = $layerDef['layerName'];
    $layer->type = \mapscript::MS_LAYER_POINT;
    $layer->status = \mapscript::MS_ON;
    //$layer->sizeunits = \mapscript::MS_FEET;

    SELF::addClass($layer, 'POINT', $layerDef['style']);

    return $layer;
  }

  static function createLineLayer($map, $layerDef) {
    $layer = new \layerObj($map);
    $layer->name = $layerDef['layerName'];
    $layer->type = \mapscript::MS_LAYER_LINE;
    $layer->status = \mapscript::MS_ON;
    //$layer->sizeunits = \mapscript::MS_FEET;
    $layer->labelitem = 'label';

    SELF::addClass($layer, 'LINESTRING', $layerDef['style']);

    return $layer;
  }

  static function createPolygonLayer($map, $layerDef) {
    $layer = new \layerObj($map);
    $layer->name = $layerDef['layerName'];
    $layer->type = \mapscript::MS_LAYER_POLYGON;
    $layer->status = \mapscript::MS_ON;
    //$layer->sizeunits = \mapscript::MS_FEET;

    SELF::addClass($layer, 'POLYGON', $layerDef['style']);

    return $layer;
  }
}
?>
