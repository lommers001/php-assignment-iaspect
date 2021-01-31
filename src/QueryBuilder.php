<?php
namespace Src;
require '/var/www/html/vendor/autoload.php';

use PDO;

class QueryBuilder {
    
    private $query_segments;
    
    public const EQ = '=';
    public const GT = '>';
    public const ST = '<';
    public const EQGT = '>=';
    public const EQST = '<=';
    public const LIKE = 'LIKE';

    private const TYPE_SELECT = 0;
    private const TYPE_INSERT = 1;
    private const TYPE_UPDATE= 2;
    private const TYPE_DELETE = 3;

    private const SEGMENT_TYPE = 0;
    private const SEGMENT_FROM = 1;
    private const SEGMENT_SET = 1;
    private const SEGMENT_JOIN = 2;
    private const SEGMENT_WHERE = 3;
    private const SEGMENT_GROUP_BY = 4;
    private const SEGMENT_ORDER_BY = 5;

    private $main_table = "";
    private $query_type = -1;
    private $params = array();
    private $param_id = 0;
    private $error_msg = "";
    
    public function __construct()
    {
        $this->query_segments = array("","","","","","");
    }
    
    public function __toString(){
        $this->checkForErrors();
        return htmlspecialchars(implode(" ", $this->query_segments) . ";");
    }
    
    //For reading values
    public function select(...$fields){
        $query = "SELECT ";
        if(count($fields) == 0)
            $query .= "*";
        else
            $query .= implode(", ", $fields);
        $this->query_segments[self::SEGMENT_TYPE] = $query;
        $this->query_type = self::TYPE_SELECT;
        return $this;
    }
    
    public function from($table){
        $query = "FROM " . $table;
        $this->main_table = $table;
        $this->query_segments[self::SEGMENT_FROM] = $query;
        return $this;
    }

    public function join($join_table, $join_column, $other_column, $second_table = ""){
        $other_table = ($second_table !== "" ? $second_table : $this->main_table);
        if($other_table === "")
            $error_msg = "No specified table to join with";
        $query = "INNER JOIN " . $join_table . " ON " . $other_table . "." . $other_column . " = " .
                   $join_table . "." . $join_column;
        $this->query_segments[self::SEGMENT_JOIN] = $query;
        return $this;
    }
    
    public function where($field, $comparison, $value){
        $query = "WHERE " . $field . " " . $comparison . " " . $this->addParameter($value);
        $this->query_segments[self::SEGMENT_WHERE] = $query;
        return $this;
    }
    
    public function and($field, $comparison, $value){
        $query = " AND " . $field . " " . $comparison . " " . $this->addParameter($value);
        $this->query_segments[self::SEGMENT_WHERE] .= $query;
        return $this;
    }
    
    public function or($field, $comparison, $value){
        $query = " OR " . $field . " " . $comparison . " " . $this->addParameter($value);
        $this->query_segments[self::SEGMENT_WHERE] .= $query;
        return $this;
    }

    public function groupBy(...$fields){
        $query = "GROUP BY ";
        $query .= implode(", ", $fields);
        $this->query_segments[self::SEGMENT_GROUP_BY] = $query;
        return $this;
    }
    
    public function orderBy(...$fields){
        $query = "ORDER BY ";
        $query .= implode(", ", $fields);
        $this->query_segments[self::SEGMENT_ORDER_BY] = $query;
        return $this;
    }
    
    //For creating a new record in the db
    public function insertInto($table, $fields, $values){
        $query = "INSERT INTO " . $table . " (";
        $query .= implode(", ", $fields);
        $query .= ")";
        $this->query_segments[self::SEGMENT_TYPE] = $query;
        $this->query_type = self::TYPE_INSERT;
        
        $query = "VALUES (";
        $size = count($fields);
        if($size != count($values))
            $error_msg = "Fewer values than fields provided.";
        for($i = 0; $i < $size; $i++){
            $next_field = $fields[$i];
            $query .= ($this->addParameter($values[$next_field]));
            if($size > $i + 1)
                $query .= ", ";
        }
        $query .= ")";
        $this->query_segments[self::SEGMENT_SET] = $query;
        return $this;
    }
    
    //For updating values
    public function update($table, $fields, $values){
        $query = "UPDATE " . $table;
        $this->query_type = self::TYPE_UPDATE;
        $this->query_segments[self::SEGMENT_TYPE] = $query;
        
        $query = "SET ";
        $size = count($fields);
        if($size != count($values))
            $error_msg = "Fewer values than fields provided in SET function.";
        for($i = 0; $i < $size; $i++){
            $next_field = $fields[$i];
            $query .= ($next_field . " = " . $this->addParameter($values[$next_field]));
            if($size > $i + 1)
                $query .= ", ";
        }
        $this->query_segments[self::SEGMENT_SET] = $query;
        return $this;
    }
    
    //For deleting records
    public function delete(){
        $query = "DELETE";
        $this->query_type = self::TYPE_DELETE;
        $this->query_segments[self::SEGMENT_TYPE] = $query;
        return $this;
    }
    
    //Execute the query
    public function exec(){
        try {
            $pdo = new PDO('mysql:host=db-php-assignment; dbname=assignment', 'development', 'development');
            $sth = $pdo->prepare($this);
            foreach($this->params as $key => &$param){
                $param_type = PDO::PARAM_INT;
                if(is_string($param))
                    $param_type = PDO::PARAM_STR;
                if(is_bool($param))
                    $param_type = PDO::PARAM_BOOL;
                $sth->bindParam($key, $param, $param_type);
            }
            $sth->execute();
            if($this->query_type == self::TYPE_SELECT)
                return $sth->fetchAll();
            return $sth->rowCount() > 0;
        }
        catch( PDOException $e ) {
            echo($e->getMessage());
        }
        catch( ErrorException $e ) {
            echo($e->getMessage());
        }
    }
    
    public function customQueryExec($query){
        try {
            $pdo = new PDO('mysql:host=db-php-assignment; dbname=assignment', 'development', 'development');
            return $pdo->query(htmlspecialchars($query));
        }
        catch( PDOException $e ) {
            echo($e->getMessage());
        }
    }
    
    private function addParameter($param){
        $this->param_id += 1;
        $this->params[":p{$this->param_id}"] = $param;
        return ":p{$this->param_id}";
    }

    //For error-checking before converting to string
    private function checkForErrors(){
        if($this->query_type == -1)
            throw new ErrorException("Type of query (SELECT, UPDATE etc.) not specified");
        if($this->query_type == self::TYPE_UPDATE && $this->query_segments[self::SEGMENT_SET] === "")
            throw new ErrorException("No SET after UPDATE");
        if($this->query_type == self::TYPE_DELETE && $this->query_segments[self::SEGMENT_WHERE] === "")
            throw new ErrorException("Cannot DELETE without a WHERE statement");
        if($this->query_segments[self::SEGMENT_FROM] === "")
            throw new ErrorException("At least one table must be specified");
        if($this->error_msg !== "")
            throw new ErrorException($error_msg);
    }
}