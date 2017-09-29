<?php
namespace wiLib/database;

// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
abstract class Oci_Db extends Database {

    const CLASS_PREFIX = 'oci_';

    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++ connection parameters
    // +++ these values must be defined in the extending class
    protected user;
    protected password;
    protected TnsName;

    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++  connects to the Oracle Database
    // +++ called by __construct in parent
    protected function connect(){

            //if ( $this->pSid != null ) { PutEnv("ORACLE_SID=".$this->pSid); }
            //if ( $this->pHome != null ){ PutEnv("ORACLE_HOME=".$this->pHome); }
            //if ( $this->pLang != null ){ PutEnv("NLS_LANG=".$this->pLang); }

        $this->linkid = oci_connect( "$this->user","$this->pass","$this->TnsName" );
        if (!$this->linkid) {
            $this->lastError = "Could not connect to the Oracle database.";
            throw new Exception($this->getLastError());
        }
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // ++  execute database query
    public function query($queryString){
        $this->result = oci_parse($this->linkid, $queryString);
        $r = oci_execute($this->linkid);
        if(!$r) {
            //echo $queryString;
            $error = oci_error($this->linkid);
            $this->lastError = $error->message." - ".$error->sqltext;
            throw new Exception($this->getLastError());
            return FALSE;  // never get here
        }
        return TRUE;  // not really needed
    }
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
}
?>
