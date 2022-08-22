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
}
?>
