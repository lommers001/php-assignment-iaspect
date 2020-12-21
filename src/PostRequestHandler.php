<?php
require '/var/www/html/vendor/autoload.php';
use Src\DatabaseConnector;
use Src\Objects\Bicycle;

$db = new PDO('mysql:host=db-php-assignment; dbname=assignment', 'development', 'development');
$dc = new DatabaseConnector();
$result = "fail";

//Handle requests for Create, Update and Delete
if (isset($_GET["action"])){
    if($_GET["action"] == "create" and isset($_POST["name"])){
        $bicycle = new Bicycle($_POST);
        $is_sql_executed = $dc->create_bicycle($db, $bicycle);
        if($is_sql_executed)
            $result = "success-create";
    }
    if($_GET["action"] == "update" and isset($_POST["name"]) and isset($_GET["id"])){
        $bicycle = new Bicycle($_POST);
        $is_sql_executed = $dc->update_bicycle($db, $_GET["id"], $bicycle);
        if($is_sql_executed)
            $result = "success-update";
    }
    if($_GET["action"] == "delete" and isset($_GET["id"])){
        $is_sql_executed = $dc->delete_bicycle($db, $_GET["id"]);
        if($is_sql_executed)
            $result = "success-delete";
    }
}

//Redirect to the main page
header('Location: ' . 'pages/MainPage.php?status=' . $result, true, 303);
die();