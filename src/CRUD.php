<?php
namespace Src;
require '/var/www/html/vendor/autoload.php';

use PDO;
use Src\QueryBuilder;
use Src\Objects\Bicycle;
use Src\Objects\Supplier;

class CRUD {
    public const TABLE_BICYCLES = "bicycles";
    public const TABLE_SUPPLIERS = "suppliers";
    
    private const SUPPLIERS = ["Omega E-bikes", "Logan", "Fietsen Fiasco"];
    
    private const TYPE_BICYCLE = 0;
    private const TYPE_SUPPLIER = 1;

    //Initialize database - if not done already
    public static function init() {
        $sql = new QueryBuilder();
        //Check if tables exist, if not: create them and add (random) values
        $sql->select()->from(self::TABLE_BICYCLES);
        if(!$sql->exec()){
            $query_create_table_bicycles = 'CREATE TABLE bicycles(id INT AUTO_INCREMENT PRIMARY KEY, 
						                    name VARCHAR(80) NOT NULL,
						                    color VARCHAR(40),
						                    battery VARCHAR(80) NOT NULL,
						                    supplier VARCHAR(80) NOT NULL,
						                    price INT NOT NULL
						                    )';
            $query_create_table_suppliers = 'CREATE TABLE suppliers(name VARCHAR(80) PRIMARY KEY,
						                     address VARCHAR(80),
						                     description VARCHAR(240)
						                     )';
            $sql->customQueryExec($query_create_table_bicycles);
            $sql->customQueryExec($query_create_table_suppliers);
            self::populateSuppliersRnd();
            self::populateBicyclesRnd(3);
        }
    }
    
    //Create a new record
    public static function create($table, $object){
        $values = get_object_vars($object);
        $keys = array_keys($values);
        $sql = new QueryBuilder();
        return $sql->insertInto($table, $keys, $values)->exec();
    }
    
    //Get record by its ID
    public static function getById($table, $id){
        $sql = new QueryBuilder();
        $sql->select()->from($table)->where("id", $sql::EQ, $id);
        $result = $sql->exec()[0];
        if (count($result) == 0)
            return null;
        if ($table == self::TABLE_BICYCLES)
            return new Bicycle($result);
        if ($table == self::TABLE_SUPPLIERS)
            return new Supplier($result);
        return $result;
    }
    
    //Get records via search
    public static function getByKeyword($table, $keyword){
        $like_param = "%" . $keyword . "%";
        $sql = new QueryBuilder();
        $sql->select()->from($table)->where("name", $sql::LIKE, $like_param)->or("color", $sql::LIKE, $like_param)
             ->or("battery", $sql::LIKE, $like_param)->orderBy("name");
        return self::convertToObjectArray($sql->exec(), self::TYPE_BICYCLE);
    }
    
    //Get all items from a table
    public static function getAll($table){
        $sql = new QueryBuilder();
        $sql->select()->from($table);
        if ($table == self::TABLE_BICYCLES){
            $sql->orderBy("name");
            return self::convertToObjectArray($sql->exec(), self::TYPE_BICYCLE);
        }
        if ($table == self::TABLE_SUPPLIERS){
            $sql->orderBy("supplier");
            return self::convertToObjectArray($sql->exec(), self::TYPE_SUPPLIER);
        }
        return $sql->exec();
    }
    
    //Get all items in both 'suppliers' and 'bicycle' tables, using a join to return one table
    public static function getSuppliersAndBicycles() {
        $sql = new QueryBuilder();
        $sql->select("name, color, battery, bicycles.supplier AS supplier, price, address, description, id")
            ->from("suppliers")->join("bicycles", "supplier", "supplier")->orderBy("supplier");
        $table = $sql->exec();
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
    
    //Edit the properties of a record
    public static function update($table, $object){
        $values = get_object_vars($object);
        $keys = array_keys($values);
        $sql = new QueryBuilder();
        $sql->update($table, $keys, $values)->where("id", $sql::EQ, $object->id);
        return $sql->exec();
    }
    
    //Erase a record from existance
    public static function delete($table, $var, $column_name = "id"){
        $sql = new QueryBuilder;
        $sql->delete()->from($table)->where($column_name, $sql::EQ, $var);
        return $sql->exec();
    }

    //Populate table with random data
    private static function populateBicyclesRnd($number_of_records) {
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
            $supplier = self::SUPPLIERS[random_int(0, sizeof(self::SUPPLIERS))];
            $price = random_int(900, 2400);
            $arr = ["name"=> $name, "color"=> $color, "battery"=> $battery, "supplier"=> $supplier, "price"=> $price];
            $bicycle = new Bicycle($arr);
            self::create(self::TABLE_BICYCLES, $bicycle);
        }
    }

    //Populate table with random data
    private static function populateSuppliersRnd() {
        $adds = ["Amsterdam","New York","Verweggistan"];
        $descs = ["De beste ter wereld!","Gewoon omdat het kan!","Totaalcomfort is ons motto!"];
        $size = count(self::SUPPLIERS);
        for ($N = 0; $N < $size; $N++){
            $name = self::SUPPLIERS[$N];
            $address = $adds[$N];
            $description = $descs[$N];
            $arr = ["name"=> $name, "address"=> $address, "description"=> $description];
            $supplier = new Supplier($arr);
            self::create(self::TABLE_SUPPLIERS, $supplier);
        }
    }
    
    private static function convertToObjectArray($fetch_result, $type){
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