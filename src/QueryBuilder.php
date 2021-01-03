<?php
namespace Src;

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
    
    public function __construct()
    {
        $this->query_segments = array("","","","","","");
    }
    
    public function __toString(){
        $this->check_for_errors();
        return (implode(" ", $this->query_segments) . ";");
    }
    
    //For reading values
    public function SELECT(...$fields){
        $query = "SELECT ";
        if(count($fields) == 0)
            $query .= "*";
        else
            $query .= implode(", ", $fields);
        $this->query_segments[self::SEGMENT_TYPE] = $query;
        $this->query_type = self::TYPE_SELECT;
        return $this;
    }
    
    public function FROM($table){
        $query = "FROM " . $table;
        $this->main_table = $table;
        $this->query_segments[self::SEGMENT_FROM] = $query;
        return $this;
    }

    public function JOIN($join_table, $join_column, $other_column, $second_table = ""){
        $other_table = ($second_table !== "" ? $second_table : $this->main_table);
        if($other_table === "")
            throw new ErrorException("No specified table to join with");
        $query = "INNER JOIN " . $join_table . " ON " . $other_table . "." . $other_column . " = " .
                   $join_table . "." . $join_column;
        $this->query_segments[self::SEGMENT_JOIN] = $query;
        return $this;
    }
    
    public function WHERE($field, $comparison){
        $query = "WHERE " . $field . " ";
        $query .= ($comparison . " ?");
        $this->query_segments[self::SEGMENT_WHERE] = $query;
        return $this;
    }
    
    public function AND($field, $comparison){
        $query = " AND " . $field . " ";
        $query .= ($comparison . " ?");
        $this->query_segments[self::SEGMENT_WHERE] .= $query;
        return $this;
    }
    
    public function OR($field, $comparison){
        $query = " OR " . $field . " ";
        $query .= ($comparison . " ?");
        $this->query_segments[self::SEGMENT_WHERE] .= $query;
        return $this;
    }

    public function GROUP_BY(...$fields){
        $query = "GROUP BY ";
        $query .= implode(", ", $fields);
        $this->query_segments[self::SEGMENT_GROUP_BY] = $query;
        return $this;
    }
    
    public function ORDER_BY(...$fields){
        $query = "ORDER BY ";
        $query .= implode(", ", $fields);
        $this->query_segments[self::SEGMENT_ORDER_BY] = $query;
        return $this;
    }
    
    //For creating a new record in the db
    public function INSERT_INTO($table, $fields){
        $query = "INSERT INTO " . $table . " (";
        $arrstr = implode(", ", $fields);
        $query .= $arrstr;
        $query .= ")";
        $this->query_segments[self::SEGMENT_TYPE] = $query;
        $query = "VALUES (";
        $q_array = preg_replace("([^,]+)", "?", $arrstr);
        $query .= $q_array;
        $query .= ")";
        $this->query_type = self::TYPE_INSERT;
        $this->query_segments[self::SEGMENT_SET] = $query;
        return $this;
    }
    
    //For updating values
    public function UPDATE($table){
        $query = "UPDATE " . $table;
        $this->query_type = self::TYPE_UPDATE;
        $this->query_segments[self::SEGMENT_TYPE] = $query;
        return $this;
    }
    
    public function SET($fields){
        $query = "SET ";
        $query .= implode(" = ?, ", $fields);
        $query .= " = ? ";
        $this->query_segments[self::SEGMENT_SET] = $query;
        return $this;
    }
    
    //For deleting records
    public function DELETE(){
        $query = "DELETE";
        $this->query_type = self::TYPE_DELETE;
        $this->query_segments[self::SEGMENT_TYPE] = $query;
        return $this;
    }

    //For error-checking before converting to string
    private function check_for_errors(){
        if($this->query_type == -1)
            throw new ErrorException("Type of query (SELECT, UPDATE etc.) not specified");
        if($this->query_type == self::TYPE_UPDATE && $this->query_segments[self::SEGMENT_SET] === "")
            throw new ErrorException("No SET after UPDATE");
        if($this->query_type == self::TYPE_DELETE && $this->query_segments[self::SEGMENT_WHERE] === "")
            throw new ErrorException("Cannot DELETE without a WHERE statement");
        if($this->query_segments[self::SEGMENT_FROM] === "")
            throw new ErrorException("At least one table must be specified");
    }
}