<?php
namespace PhpLib\Database;

use PhpLib\Database\DbException;

abstract class Pg_Db extends Db {

    const CLASS_PREFIX = 'pg_';

    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++ connection parameters
    // +++ these values must be defined in the extending class
    protected $host;
    protected $port;   // optional
    protected $name;
    protected $user;
    protected $pass;


    protected function __construct($config=NULL) {
      if ($config) {
        // set pg connection parameters
        $this->host = $config['host'];
        $this->port = isset($config['port']) ? $config['port'] : "5432";
        $this->name = $config['name'];
        $this->user = $config['user'];
        $this->pass = $config['pass'];
      }
      parent::__construct($config);
    }

    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++ connects to the PostgreSQL Database
    // +++ called by __construct in parent
    protected function connect(){
      $connectString = 'host='      . $this->host.
                       ' user='     . $this->user.
                       ' password=' . $this->pass.
                       ' dbname='   . $this->name;

      if (!is_null($this->port)) {
        $connectString .= ' port='.$this->port;
      }
      $this->linkid = @pg_connect($connectString);
      if (!$this->linkid) {
        // fatal error
//        $this->lastError= "Could not connect to the PostgreSQL database(" .
  //                        $this->name . ") on host(" . $this->host . ")";
        $this->lastError= "Could not connect to the PostgreSQL database" .
                          "({$this->name}) on host({$this->host})";
        $this->throwDbException();
      }
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++  execute database query
    public function query($queryString){
      $this->result = pg_query($this->linkid, $queryString);
      if(!$this->result) {
        // fatal error
        $this->lastError = pg_last_error($this->linkid);
        $this->throwDbException(new \Exception("\nSQL_CONTAINING_ERROR = \n$queryString"));
      }
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //  TODO:  test this!
    // ++  execute database query with parms inserted into SQL template
    public function query_params($queryTemplate, $params){
      $this->result = pg_query_params($this->linkid, $queryTemplate, $params);
      if(!$this->result) {
        // fatal error
        $this->lastError = pg_last_error($this->linkid);
        $this->throwDbException();
      }
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

  protected function getColumnDataTypes($tableName) {
    $sql = "
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name='$tableName'
    ";
    //echo "sql = $sql";

    $dataTypes = array();

    $this->query($sql);
    while ($r = $this->fetch_assoc()) {    // new db class
        $dataTypes[$r['column_name']] = $r['data_type'];
    }
    return $dataTypes;
  }
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

  protected function getValueString($columnType, $value, $operator = NULL) {
    $truncatedType = substr($columnType, 0, 7);

    switch ($truncatedType) {
        case 'boolean':
            if ($value) {
                $string = '$$t$$';
            } else {
                $string = '$$f$$';
            }
            break;

        case 'bigint':
        case 'integer':
        case 'numeric':
        case 'smallin':
            if (!is_int($value)) {
                $string = 'DEFAULT';
            } else {
                $string = $value;
            }
            break;

        case 'timesta':
        case 'date':
            if (!$value) {
                $string = "DEFAULT";
            } else {
                $string = "$$".$value."$$";
            }
            break;

        case 'text':
        case 'charact':
            if (is_null($operator) OR $operator == '=') {
                //$string = "$$".$value."$$";
                $string = "$$".trim($value)."$$";
            } else {
                $string = "$$%".trim($value)."%$$";
            }
            break;

        default:
            $string = "xxx BAD TYPE($columnType - $truncatedType ) IN getValueString! xxx";
            break;
    }

    //echo " truncatedType = $truncatedType  value = $value \n";
    //echo " truncatedType = $truncatedType  value = $string \n";

    return $string;
  }
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
}
?>
