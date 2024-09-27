<?php
namespace PdfMaps\Mapserver;

class MS_Utilities {

  // ++++ getAllLayerNames ++++++++++++++++++++++++++++++++++++++++++++++++++++
  static function getAllLayerNames($map) {
    $numLayers = $map->numlayers;
    $layerNames = [];
    for ($i=0; $i < $numLayers; $i++) {
      $layer = $map->getLayer($i);
      $layerNames[$i] = $layer->name;
    }
    return $layerNames;
  }

  // ++++ getAllGroupNames ++++++++++++++++++++++++++++++++++++++++++++++++++++
  static function getAllGroupNames($map) {
    $numLayers = $map->numlayers;
    $groupNames = [];
    for ($i=0; $i < $numLayers; $i++) {
      $layer = $map->getLayer($i);
      $groupName = $layer->group;
      if (!in_array($groupName, $groupNames)) {
        $groupNames[] = $groupName;
      }
      $layerNames[$i] = $layer->name;
    }
    return $groupNames;
  }

  // ++++ setDrawOrder +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function setDrawOrder($map, $layerNameList) {
        $layers = self::getAllLayerNames($map);
        $groups = self::getAllGroupNames($map);
        $drawOrder = array();
        for ($i = 0, $len = count($layerNameList); $i < $len; $i++) {
            $layerName = $layerNameList[$i];
            if (in_array($layerName, $layers)) {
                $layerObj = $map->getLayerByName($layerName);
                $drawOrder[] = $layerObj->index;
            } else if (in_array($layerName, $groups)) {
                $groupLayers = $map->getLayersIndexByGroup($layerName);
                for ($j = 0, $len = count($groupLayers); $j < $len; $j++) {
                    $drawOrder[] = $groupLayers[$j];
                }
            } else {
                echo "\n setDrawOrder ** requested layer($layerName) not in layers or groups **";
            }
        }
        for ($i = 0; $i < $map->numlayers; $i++) {
            if (! in_array($i, $drawOrder)) {
                $drawOrder[] = $i;
            }
        }
        //var_dump($drawOrder);
        // TODO: figure out how to replace the following line with something that works!!
        //$map->setLayersDrawingOrder($drawOrder);
    }

    // ++++ turnOffLayerOrGroup +++++++++++++++++++++++++++++++++++++++++++++++++
    static function turnOffLayerOrGroup(&$map, $layerName) {
        self::setLayerStatus($map, $layerName, MS_OFF);
    }

    // ++++ turnOnLayerOrGroup +++++++++++++++++++++++++++++++++++++++++++++++++
    static function turnOnLayerOrGroup(&$map, $layerName) {
        self::setLayerStatus($map, $layerName, MS_ON);
    }

    // ++++ setLayerStatus +++++++++++++++++++++++++++++++++++++++++++++++++++
    static function setLayerStatus(&$map, $layerName, $value) {
        $layers = self::getAllLayerNames($map);
        $groups = self::getAllGroupNames($map);
        if (in_array($layerName, $layers)) {
            $layer = $map->getLayerByName($layerName);
            $layer->status = $value;
            //$layer->set('status', $value);
        } else if (in_array($layerName, $groups)) {
            $layers = $map->getLayersIndexByGroup($layerName);
            for ($i = 0, $len = count($layers); $i < $len; $i++) {
                $layer = $map->getLayer($layers[$i]);
                $layer->status = $value;
                //$layer->set('status', $value);
            }
        } else {
            echo "\n setLayerStatus ** requested layer($layerName) not in layers or groups **";
        }
    }

    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
}
?>
