<?php
namespace PhpLib\Database;

abstract class Mssql_Db extends Database {

    const CLASS_PREFIX = 'mssql_';

    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++ connection parameters
    // +++ these values must be defined in the extending class
    protected $host;
    protected $user;
    protected $pass;
    protected $name;

    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++ connects to the MsSQL Database
    // +++ called by __construct in parent
    protected function connect(){

        $this->linkid = mssql_connect( "$this->host","$this->user","$this->pass" );
        if (!$this->linkid) {
            $this->lastError = "Could not connect to the MSSQL server(".
                                                              $this->host.").";
            throw new Exception($this->getLastError());
        } else {
            // TODO: ****** FIX THIS *********** test_db??????
            mssql_select_db('test_db', $r);
            if (!mssql_select_db( '['.$this->name.']', $r )) {
                $this->lastError = "Could not select the active MSSQL database(".
                                                            $this->dbName.").";
                throw new Exception($this->getLastError());
            }
        }
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++  execute database query
    public function query($queryString){
        $this->result = mssql_query($queryString, $this->linkid);
        if(!$this->result) {
            //echo $queryString;
            $this->lastError = mssql_get_last_message()."-".$queryString;
            throw new Exception($this->getLastError());
            return FALSE;  // never get here
        }
        return TRUE;  // not really needed
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
}
?>
