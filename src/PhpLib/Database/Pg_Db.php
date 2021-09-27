<?php
namespace PhpLib\Database;

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
        $connectString = 'host=' .     $this->host.
                         ' user=' .    $this->user.
                         ' password=' . $this->pass.
                         ' dbname=' .  $this->name;

        if (!is_null($this->port)) {
            $connectString .= ' port='.$this->port;
        }
        $this->linkid = @pg_connect($connectString);
        if (!$this->linkid) {
            $this->lastError= "Could not connect to the PostgreSQL database(".
                               $this->name.") on host(".$this->host.").";
            throw new \Exception($this->getLastError());
        }
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++  execute database query
    public function query($queryString){
        $this->result = pg_query($this->linkid, $queryString);
        if(!$this->result) {

            $this->lastError = pg_last_error($this->linkid);
            // TODO: add the SQL to the lastError only if a DEBUG flag is set.
            //       This would probably be $db->setDebug(true)
            //     . "\nSQL = \n".$queryString;
            throw new \Exception($this->getLastError());
            return false;  // never get here
        }
        return true;  // probably not needed
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //  TODO:  test this!
    // ++  execute database query with parms inserted into SQL template
    public function query_params($queryTemplate, $params){
        $this->result = pg_query_params($this->linkid, $queryTemplate, $params);
        if(!$this->result) {
            //echo $queryString;
            //echo(pg_last_error($this->linkid));

            #$this->lastError = pg_last_error($this->linkid)."\nSQL = \n".$queryString;
            $this->lastError = pg_last_error($this->linkid);
            throw new \Exception($this->getLastError());
            return false;  // never get here
        }
        return true;  // not really needed
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
}
?>
