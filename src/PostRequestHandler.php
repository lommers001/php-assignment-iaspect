<?php
require '/var/www/html/vendor/autoload.php';
use Src\CRUD;
use Src\Objects\Bicycle;

$result = "fail";

//Handle requests for Create, Update and Delete
if (isset($_GET["action"])){
    if($_GET["action"] == "create" and isset($_POST["name"])){
        $bicycle = new Bicycle($_POST);
        $is_sql_executed = CRUD::create(CRUD::TABLE_BICYCLES, $bicycle);
        if($is_sql_executed)
            $result = "success-create";
    }
    if($_GET["action"] == "update" and isset($_POST["name"]) and isset($_GET["id"])){
        $bicycle = new Bicycle($_POST);
        $bicycle->id = $_GET["id"];
        $is_sql_executed = CRUD::update(CRUD::TABLE_BICYCLES, $bicycle);
        if($is_sql_executed)
            $result = "success-update";
    }
    if($_GET["action"] == "delete" and isset($_GET["id"])){
        $is_sql_executed = CRUD::delete(CRUD::TABLE_BICYCLES, $_GET["id"]);
        if($is_sql_executed)
            $result = "success-delete";
    }
}

//Redirect to the main page
header('Location: ' . 'pages/MainPage.php?status=' . $result, true, 303);
die();