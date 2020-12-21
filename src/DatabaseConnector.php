<?php

namespace Src;

use PDO;
use Src\Objects\Bicycle;
use Src\Objects\Supplier;

class DatabaseConnector {

    private string $query_create_table_bicycles;
    private string $query_create_table_suppliers;
    private array $suppliers;
    
    private const TYPE_BICYCLE = 0;
    private const TYPE_SUPPLIER = 1;

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
    public function init($db) {
        try {
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
    
    //Create an E-bike
    public function create_bicycle($db, $bicycle){
        try {
            $sth = $db->prepare("INSERT INTO bicycles (name, color, battery, supplier, price) VALUES (?, ?, ?, ?, ?)");
            $sth->bindParam(1, htmlspecialchars($bicycle->name), PDO::PARAM_STR);
            $sth->bindParam(2, htmlspecialchars($bicycle->color), PDO::PARAM_STR);
            $sth->bindParam(3, htmlspecialchars($bicycle->battery), PDO::PARAM_STR);
            $sth->bindParam(4, htmlspecialchars($bicycle->supplier), PDO::PARAM_STR);
            $sth->bindParam(5, $bicycle->price, PDO::PARAM_INT);
            $sth->execute();
            return $sth->rowCount() > 0;
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Get bicycle by its ID
    public function get_bicycle_by_id($db, $id){
        try {
            $sth = $db->prepare("SELECT * FROM bicycles WHERE id = ?");
            $sth->bindParam(1, $id, PDO::PARAM_INT);
            $sth->execute();
            if ($sth->rowCount() > 0)
                return new Bicycle($sth->fetch());
            return null;
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Get bicycles via search
    public function get_bicycles_by_keyword($db, $keyword){
        try {
            $like_param = "%" . htmlspecialchars($keyword) . "%";
            $sth = $db->prepare("SELECT * FROM bicycles WHERE name LIKE ? OR color LIKE ? OR battery LIKE ? ORDER BY name");
            $sth->execute([$like_param, $like_param, $like_param]);
            return $this->convert_to_object_array($sth->fetchAll(), self::TYPE_BICYCLE);
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Get all items in the 'bicycles' table
    public function get_all_bicycles($db){
        try {
            $sql = "SELECT * FROM bicycles ORDER BY name;";
            return $this->convert_to_object_array($db->query($sql)->fetchAll(), self::TYPE_BICYCLE);
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Get all items in the 'suppliers' table
    public function get_all_suppliers($db){
        try {
            $sql = "SELECT * FROM suppliers ORDER BY supplier;";
            return $this->convert_to_object_array($db->query($sql)->fetchAll(), self::TYPE_SUPPLIER);
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Get all items in both 'suppliers' and 'bicycle' tables, using a join to return one table
    public function get_suppliers_and_bicycles($db) {
        try {
            $sql = "SELECT name, color, battery, bicycles.supplier AS supplier, price, address, description, id
                    FROM suppliers INNER JOIN bicycles on bicycles.supplier = suppliers.supplier ORDER BY supplier;";
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
    
    //Edit the properties of a bicycle
    public function update_bicycle($db, $id, $bicycle){
        try {
            $sth = $db->prepare("UPDATE bicycles SET name = ?, color = ?, battery = ?, supplier = ?, price = ? WHERE id = ?");
            $sth->bindParam(1, htmlspecialchars($bicycle->name), PDO::PARAM_STR);
            $sth->bindParam(2, htmlspecialchars($bicycle->color), PDO::PARAM_STR);
            $sth->bindParam(3, htmlspecialchars($bicycle->battery), PDO::PARAM_STR);
            $sth->bindParam(4, htmlspecialchars($bicycle->supplier), PDO::PARAM_STR);
            $sth->bindParam(5, $bicycle->price, PDO::PARAM_INT);
            $sth->bindParam(6, $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->rowCount() > 0;
        }
        catch( PDOException $e ) {
            return $e->getMessage();
        }
    }
    
    //Erase a bicycle from existance
    public function delete_bicycle($db, $id){
        try {
            $sth = $db->prepare("DELETE FROM bicycles WHERE id = ?");
            $sth->bindParam(1, $id, PDO::PARAM_INT);
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
            $this->create_bicycle($db, $name, $color, $battery, $supplier, $price);
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
            $sth = $db->prepare("INSERT INTO suppliers (name, address, description) VALUES (?, ?, ?)");
            $sth->bindParam(1, htmlspecialchars($name), PDO::PARAM_STR);
            $sth->bindParam(2, htmlspecialchars($address), PDO::PARAM_STR);
            $sth->bindParam(3, htmlspecialchars($description), PDO::PARAM_STR);
            try { $sth->execute(); } catch( PDOException $e ) { die( $e->getMessage() ); }
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