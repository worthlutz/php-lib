<?php
namespace PhpLib\Database;

use PhpLib\Database\Pg_Db;

class PostGIS_Db extends Pg_Db {

  // the default "geometry" column in the database
  // Use "static::GEOM_COLUMN" to reference this variable for late static
  //    binding. It can then be overridden in extending classes.
  const GEOM_COLUMN = 'geom';

  // +++ Public Functions ++++++++++++++++++++++++++++++++++++++++++++

  public function getGeometryType($table) {
    $sql = "
      SELECT
        GeometryType(".static::GEOM_COLUMN.") AS geometrytype
      FROM $table
      LIMIT 1;
    ";
    //echo("\n ++ $sql ++ ");

    $this->query($sql);
    $r = $this->fetch_assoc();
    if ($r) {
      return $r['geometrytype'];
    } else {
      // nothing found..... or no rows?
      $this->lastError = "Cannot get GeometryType(".static::GEOM_COLUMN.") from ($table)";
      $this->throwDbException();
    }
  }
  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

  public function getSRID($table) {
    $sql = "
      SELECT
        ST_SRID(".static::GEOM_COLUMN.") AS srid
      FROM $table
      LIMIT 1;
    ";
    //echo("\n ++ $sql ++ ");

    $this->query($sql);
    $r = $this->fetch_assoc();
    if ($r) {
      return $r['srid'];
    } else {
      // nothing found..... or no rows?
      $this->lastError = "Cannot get ST_SRID(".static::GEOM_COLUMN.") from ($table)";
      $this->throwDbException();
    }
  }
  // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
}
?>
