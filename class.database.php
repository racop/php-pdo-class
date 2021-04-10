<?php
/*
AUTHOR: RAHUL RANJAN
THIS SOFTWARE IS DESIGNED TO BE USED FOR INTERNAL USE IN LICESCENED BLOG ONLY.
Built for Mysql database
USAGE:
dbExists($databaseName) -> Checks if database exists
tableExists($databaseName) -> Checks if table exists
count($query) -> Number of rows for given query
driversAvailable() -> Returns database drivers available for use
error() -> Return error if exists
debug() -> Return logs
pdoObject() -> Return PDO object to be used outside class
query($query,$paramsArray) -> Builds and Binds query params, Parameters should be passed as array ["param1","param2"]
multiquery($query) -> is not preferred for input from user
----------To print result of select Query----------------
foreach($query as $row){
echo $row['Name'];
}
*/
class database{
    private $host;
    private $username;
    private $password;
    private $dbname;
    private $pdo;
    public $conFailed;
    public $debugMessages;
    private $debugErrorMessages;
    private $dMcount;
    private $dEcount;
    function __construct($host,$username,$password,$dbname){
        $this->dMcount=0;
        $this->dEcount=0;
        $this->conFailed=false;
        $this->debugMessages=array();
        $this->debugErrorMessages=array();
        try {
            $this->pdo = new PDO("mysql:host=$host;", "$username", "$password");
        }catch(PDOException $e){
            $this->conFailed=true;
        }
        if($this->conFailed){
            $this->debugMessages[$this->dMcount++]="Connection to the Mysql failed";
        }else{
            if(!$this->dbExists($dbname)){
                $this->debugMessages[$this->dMcount++]="Oops Database not exist";
                $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); //ENABLE PREPARED STATEMENTS to avoid SQL injection
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->query("CREATE DATABASE IF NOT EXISTS $dbname");
                $this->pdo->exec("use $dbname");
                $this->debugMessages[$this->dMcount++]="Created and selected database";
            }else{
                $this->pdo->exec("use $dbname");
                $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); //ENABLE PREPARED STATEMENTS to avoid SQL injection
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->debugMessages[$this->dMcount++]="Database Exists";
            }
        }
    }

    function dbExists($dv){
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dv'");
        return (bool)$stmt->fetchColumn();
    }

    function tableExists($table){
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
        return (bool)$stmt==1?true:false;
    }

    function count($query){
        return $query->rowCount();
    }

    function driversAvailable(){
        return PDO::getAvailableDrivers();
    }

    function pdoObject(){
        return $this->pdo;
    }

    function query($query,$paramsArray=[]){
        $this->debugMessages[$this->dMcount++]="<b>Building the query:</b> $query";
        try{
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($paramsArray);//$paramsArray=[$email, $status]
            return $stmt;
        }catch(PDOException $e){
            $this->debugMessages[$this->dMcount++]="<b>ERROR:</b> $e";
            $this->debugErrorMessages[$this->dEcount++]="<b>ERROR:</b> $e";
        }
        return false;
    }

    function error(){
        if(count($this->debugErrorMessages)>0){
            return $this->debugErrorMessages;
        }
        return false;
    }

    function debug(){
        if(count($this->debugMessages)>0){
            return $this->debugMessages;
        }
        return false;
    }

    function multiquery($query){
        $this->debugMessages[$this->dMcount++]="<b>Building multiline query:</b> $query";
        try{
            $this->pdo->exec($query);
        }catch(Exception $e){
            $this->debugMessages[$this->dMcount++]="<b>ERROR:</b> $e";
        }
    }
}
?>
