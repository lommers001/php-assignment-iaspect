<?php
use PHPUnit\Framework\TestCase;

use Src\DatabaseConnector;
use Src\Objects\Bicycle;

final class CRUDTest extends TestCase
{
    public function testCRUD(): void
    {
        $db = new PDO('mysql:host=db-php-assignment; dbname=assignment', 'development', 'development');
        $dc = new DatabaseConnector();
        //Create dummy array
        $test_array = array("name"=>"123_TestName","color"=>"TestColor","battery"=>"TestBattery 500v","supplier"=>"Logan","price"=>1928);
        //Create a bicycle object for this dummy array
        $test_bicycle = new Bicycle($test_array);
        //Check if bicycle object creation was correctly done
        $this->assertEquals($test_array["name"], $test_bicycle->name);
        $this->assertEquals($test_array["price"], $test_bicycle->price);
        //Insert data into db
        $result = $dc->create_bicycle($db, $test_bicycle);
        $this->assertTrue($result);
        //Check if data was correctly added
        $result = $dc->get_bicycles_by_keyword($db, $test_array["name"]);
        $this->assertEquals(result[0]->name, $test_array["name"]);
        $this->assertEquals(result[0]->supplier, $test_array["supplier"]);
        //Update this bicycle
        $new_bicycle = $result[0];
        $id = $new_bicycle->id;
        $new_supplier = "Omega E-bikes";
        $new_bicycle->supplier = $new_supplier;
        $result = $dc->update_bicycle($db, $id, $new_bicycle);
        $this->assertTrue($result);
        //Check if supplier's name is updated and other data is the same.
        $result = get_bicycle_by_id($db, $id);
        $this->assertEquals(result->supplier, $new_supplier);
        $this->assertEquals(result->name, $test_array["name"]);
        $this->assertEquals(result->price, $test_array["price"]);
        //Delete this bicycle
        $result = $dc->delete_bicycle($db, $id);
        $this->assertTrue($result);
        //Check if the bicycle was deleted
        $result = get_bicycle_by_id($db, $id);
        $this->assertNull($result);
    }
}
