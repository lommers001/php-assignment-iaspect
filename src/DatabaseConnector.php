<?php

namespace Src;

use PDO;
use Src\QueryBuilder;
use Src\Objects\Bicycle;
use Src\Objects\Supplier;

class DatabaseConnector {

    private string $query_create_table_bicycles;
    private string $query_create_table_suppliers;
    private array $suppliers;
    
    private const TYPE_BICYCLE = 0;
    private const TYPE_SUPPLIER = 1;
    
    public const TABLE_BICYCLES = "bicycles";
    public const TABLE_SUPPLIERS = "suppliers";

    public function __construct()
    {
        $this->query_create_table_bicycles = 'CREATE TABLE bicycles(id INT AUTO_INCREMENT PRIMARY KEY, 
						name VARCHAR(80) NOT NULL,
						color VARCHAR(40),
						battery VARCHAR(80) NOT NULL,
						supplier VARCHAR(80) NOT NULL,
						price INT NOT NULL
						)';
        $this->query_create_table_suppliers = 'CREATE TABLE suppliers(name VARCHAR(80) PRIMARY KEY,
						address VARCHAR(80),
						description VARCHAR(240)
						)';
        $this->suppliers = ["Omega E-bikes", "Logan", "Fietsen Fiasco"];
    }

    //Initialize database - if not done already
    public function init() {
        try {
            $db = $this->get_db();
            //Check if tables exist, if not: create them and add (random) values
            $sql = $db->query('SELECT * FROM bicycles');
            if($sql === false){
                $db->query($this->query_create_table_bicycles);
                $db->query($this->query_create_table_suppliers);
                $this->populate_suppliers_rnd($db);
                //$this->populate_bicycles_rnd($db, 3);
            }
            //$sql = $db->query('ALTER TABLE suppliers CHANGE name supplier VARCHAR(80)');
        }
        catch( PDOException $e ) {
            die( $e->getMessage() );
        }
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
    
    //Obtain PDO instance s we can communicate with the database
    private function get_db() {
        return new PDO('mysql:host=db-php-assignment; dbname=assignment', 'development', 'development');
    }
    
    //Create a new record
    public function create($table, $object){
        try {
            $db = $this->get_db();
            $keys = array_keys(get_object_vars($object));
            $sql = new QueryBuilder();
            $sql->INSERT_INTO($table, $keys);
            $sth = $db->prepare($sql);
            $i = 1;
            //For each parameter of the object
            for( ;$i <= count($keys); $i++) {
                $param = $keys[$i - 1];
                $is_string = is_string($object->$param);
                if ($is_string)
                    $sth->bindParam($i, htmlspecialchars($object->$param), PDO::PARAM_STR);
                else
                    $sth->bindParam($i, $object->$param, PDO::PARAM_INT);
            }
            $sth->execute();
            return $sth->rowCount() > 0;
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Get record by its ID
    public function get_by_id($table, $id){
        try {
            $db = $this->get_db();
            $sql = new QueryBuilder();
            $sql->SELECT()->FROM($table)->WHERE("id", $sql::EQ);
            $sth = $db->prepare($sql);
            $sth->bindParam(1, $id, PDO::PARAM_INT);
            $sth->execute();
            if ($sth->rowCount() == 0)
                return null;
            if ($table == self::TABLE_BICYCLES)
                return new Bicycle($sth->fetch());
            else
                return new Supplier($sth->fetch());
            
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Get records via search
    public function get_by_keyword($table, $keyword){
        try {
            $db = $this->get_db();
            $like_param = "%" . htmlspecialchars($keyword) . "%";
            $sql = new QueryBuilder();
            $sql->SELECT()->FROM($table)->WHERE("name", $sql::LIKE)->OR("color", $sql::LIKE)->OR("battery", $sql::LIKE)->ORDER_BY("name");
            $sth = $db->prepare($sql);
            $sth->execute([$like_param, $like_param, $like_param]);
            return $this->convert_to_object_array($sth->fetchAll(), self::TYPE_BICYCLE);
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Get all items from a table
    public function get_all($table){
        try {
            $db = $this->get_db();
            $sql = new QueryBuilder();
            $sql->SELECT()->FROM($table)->ORDER_BY($table == self::TABLE_BICYCLES ? "name" : "supplier");
            if ($table == self::TABLE_BICYCLES)
                return $this->convert_to_object_array($db->query($sql)->fetchAll(), self::TYPE_BICYCLE);
            return $this->convert_to_object_array($db->query($sql)->fetchAll(), self::TYPE_SUPPLIER);
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Get all items in both 'suppliers' and 'bicycle' tables, using a join to return one table
    public function get_suppliers_and_bicycles() {
        try {
            $db = $this->get_db();
            $sql = new QueryBuilder();
            $sql->SELECT("name, color, battery, bicycles.supplier AS supplier, price, address, description, id")
                ->FROM("suppliers")->JOIN("bicycles", "supplier", "supplier")->ORDER_BY("supplier");
            $table = $db->query($sql)->fetchAll();
            $result = array();
            $new_supplier = null;
            $prev_supplier_name = null;
            foreach($table as $row) {
                if($row["supplier"] != $prev_supplier_name){
                    if($new_supplier != null)
                        array_push($result, $new_supplier);
                    $new_supplier = new Supplier($row);
                    $prev_supplier_name = $row["supplier"];
                }
                $new_supplier->add_bicycle(new Bicycle($row));
            }
            array_push($result, $new_supplier);
            return $result;
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Edit the properties of a record
    public function update($table, $object){
        try {
            $db = $this->get_db();
            $keys = array_keys(get_object_vars($object));
            $sql = new QueryBuilder();
            $sql->UPDATE($table)->SET($keys)->WHERE("id", $sql::EQ);
            $sth = $db->prepare($sql);
            $i = 1;
            //For each parameter of the object
            for( ;$i <= count($keys); $i++) {
                $param = $keys[$i - 1];
                $is_string = is_string($object->$param);
                if ($is_string)
                    $sth->bindParam($i, htmlspecialchars($object->$param), PDO::PARAM_STR);
                else
                    $sth->bindParam($i, $object->$param, PDO::PARAM_INT);
            }
            //Final binding: check for matching ID
            $sth->bindParam($i, $object->id, PDO::PARAM_INT);
            
            $sth->execute();
            return $sth->rowCount() > 0;
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Erase a record from existance
    public function delete($table, $var, $column_name = "id"){
        try {
            $db = $this->get_db();
            $sql = new QueryBuilder;
            $sql->DELETE()->FROM($table)->WHERE($column_name, $sql::EQ);
            $sth = $db->prepare($sql);
            $is_string = is_string($var);
            if ($is_string)
                $sth->bindParam(1, htmlspecialchars($var), PDO::PARAM_STR);
            else
                $sth->bindParam(1, $var, PDO::PARAM_INT);
            $sth->execute();
            return $sth->rowCount() > 0;
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }

    //Populate table with random data
    private function populate_bicycles_rnd($db, $number_of_records) {
        $N = $number_of_records;
        $name_elements = ["cat","mo","ti","vel","dy","span","u"];
        $colors = ["Rood","Zwart","Blauw","Wit","Grijs"];
        $batteries = ["MegaVolt 600v","Giga-Amp 550v","LBAB-97k 500v"];
        while ($N > 0){
            $N -= 1;
            $name = $name_elements[random_int(0,6)] . $name_elements[random_int(0,6)] . $name_elements[random_int(0,5)];
            $name = ucfirst($name);
            $color = $colors[random_int(0,sizeof($colors))];
            $battery = $batteries[random_int(0,sizeof($batteries))];
            $supplier = $this->suppliers[random_int(0, sizeof($this->suppliers))];
            $price = random_int(900, 2400);
            $arr = ["name"=> $name, "color"=> $color, "battery"=> $battery, "supplier"=> $supplier, "price"=> $price];
            $bicycle = new Bicycle($arr);
            $this->create(self::TABLE_BICYCLES, $bicycle);
        }
    }

    //Populate table with random data
    private function populate_suppliers_rnd($db) {
        $adds = ["Amsterdam","New York","Verweggistan"];
        $descs = ["De beste ter wereld!","Gewoon omdat het kan!","Totaalcomfort is ons motto!"];
        for ($N = 0; $N < sizeof($this->suppliers); $N++){
            $name = $this->suppliers[$N];
            $address = $adds[$N];
            $description = $descs[$N];
            $arr = ["name"=> $name, "address"=> $address, "description"=> $description];
            $supplier = new Supplier($arr);
            $this->create(self::TABLE_SUPPLIERS, $supplier);
        }
    }
    
    private function convert_to_object_array($fetch_result, $type){
        $array = array();
        foreach($fetch_result as $item){
            if($type == self::TYPE_BICYCLE)
                array_push($array, new Bicycle($item));
            else if($type == self::TYPE_SUPPLIER)
                array_push($array, new Supplier($item));
        }
        return $array;
    }

}