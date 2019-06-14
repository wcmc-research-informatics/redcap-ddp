<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/ddp/redcap-ddp/security-checks.php');

// Take the structure returned from sqlsrv_errs and make a string.
// per docs, it returns 'array of arrays' or null.
// https://msdn.microsoft.com/en-us/library/cc296200(SQL.90).aspx
function errs2str($errs) {
    $out = "";
    if (!$errs) { return $out; }
    foreach ($errs as $e) {
        $out = $out . "SQLSTATE: " . $e[ 'SQLSTATE'];
        $out = $out . "; code: " . $e[ 'code'];
        $out = $out . "; message: " . $e[ 'message'] . ". ";
    }
    return $out;
}

/**
 * Abstract class for all connection objects. Each source system will
 * extend this class and define all neccessary connection parameters.
 *
 * @author     Marcos Davila (mzd2016@med.cornell.edu)
 * @since      v3.10
 * @package    utils
 * @license    Open Source
 *
 */
abstract class db_connect
{
    protected $isconnected = FALSE;
    protected $serverName;
    protected $username;
    protected $password;
    protected $database;
    protected $conn_id;
    protected $source;
    
    /**
     * Sets up connection parameters for the connection
     *
     * @param
     *            source - identifier which dictates the database
     *            parameters to connect with
     */
    public function __construct($source)
    {
        $constants = new Constants();
        
        $this->serverName = $constants->host[$source]["Server"];
        $this->username   = $constants->host[$source]["Username"];
        $this->password   = $constants->host[$source]["Password"];
        $this->database   = $constants->host[$source]["Database"];
        $this->db_type    = $constants->host[$source]["Type"];
        $this->source = $source;
    }
    
    /*
     * Returns the status of the connection
     */
    public function getConnectionStatus()
    {
        return $this->isconnected;
    }
    
    /**
     * Queries a data source
     */
    abstract public function query($stmt);
    
    // Subclasses must define how to connect and disconnect
    abstract public function connect();
    abstract public function close();
}

/**
 * Connects to an MSSQL database as datasource for DDP
 *
 * @author mzd2016
 *
 */
class mssql_db_connect extends db_connect
{
    /**
     * Passes arguments to the parent constructor and immediately
     * initializes a connection
     */
    public function __construct($source)
    {
        parent::__construct($source);
        $this->connect();
    }
    
    public function query($stmt)
    {
        $result = sqlsrv_query($this->conn_id, $stmt);
        
        if (!$result) {
            exit('Query to inspect: ' . $stmt . '\n' . errs2str(sqlsrv_errors()));
        }
        
        return $result;
    }
    
    /**
     * Connects to database specified in constants file as datasource for DDP
     */
    public function connect()
    {
        try {
            $connInfo = array("UID"=>$this->username
                , "PWD"=>$this->password
                , 'ReturnDatesAsStrings'=>true);
            $this->conn_id = sqlsrv_connect($this->serverName, $connInfo);
            if (!$this->conn_id) {
                exit('There was a problem in connecting to ' . $this->source);
            }
            
            // See if any ETL is occurring. Unpack the result and fetch the first row.
            // The table it is looking at should only have one value in it called
            // IS_ETL_OCCURRING and it is brought in as an array so we reference only
            // the first element. This is only for ARCH.
            if ($this->source === "ARCH") {
                $sql       = "SELECT IS_ETL_OCCURRING FROM SUPER_WEEKLY.dbo.ETL_STATUS;";
                $rslt      = $this->query($sql);
                $etlstatus = sqlsrv_fetch_array($rslt, SQLSRV_FETCH_NUMERIC);
                
                if ($etlstatus[0] === "Y") {
                    $this->close();
                    exit("Data is currently being updated at this time. Please try again later.");
                }
            }
            
            $this->isconnected = TRUE;
        }
    
        catch (Exception $e) {
            $this->isconnected = FALSE;
            exit("Error caught in mssql_db_connect: " . $e->getMessage() . '; ' . errs2str(sqlsrv_errors()));
        }
    }

/**
 * Closes the connection to a data source.
 */
public function close()
{
    try {
        $close = sqlsrv_close($this->conn_id);
        
        if (!$close) {
            exit('There was an issue in disconnecting from ' . $this->serverName);
        }
        
        $this->isconnected = FALSE;
        
        return $close;
    }
    catch (Exception $e) {
        $this->isconnected = FALSE;
        exit('There was an issue in disconnecting from ' . $this->serverName . '; ' . errs2str(sqlsrv_errors()));
    }
}
}
?>

