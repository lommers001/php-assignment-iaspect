<?php
require '/var/www/html/vendor/autoload.php';
use Src\DatabaseConnector;

//Init tables, if not already done
$db = new PDO('mysql:host=db-php-assignment; dbname=assignment', 'development', 'development');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dc = new DatabaseConnector();
$dc->init($db);
//If a search query is entered, obtain the search results.
//Otherwise, obtain all bicycles.
if(isset($_GET["q"]))
  $bicycles = $dc->get_bicycles_by_keyword($db, $_GET["q"]);
else
  $bicycles = $dc->get_all_bicycles($db);
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<style>
body {
  margin:0;
  font-family: sans-serif;
}

/* GRID */
#grid-line {
  display: grid;
  grid-template-columns: 20% 10% 20% 20% 10% 20%; /* 6 COL LAYOUT */
}

.theader, .tbody {
  margin: 0px;
  padding: 5px;
}

.theader {
  background: #aaaaff;
}

.tbody {
  background: #ddddff;
}

</style>
</head>
<body>
<!-- Header -->
<iframe src="Header.html" width="100%" height="40px" scrolling="no">
</iframe>

<!-- Message if a POST is successful or not -->
<?php
if (isset($_GET["status"])){
  if($_GET["status"] == 'fail')
    echo("<p>Er is iets misgegaan</p>");
  else if($_GET["status"] == 'success-create')
    echo("<p>E-bike toegevoegd!</p>");
  else if($_GET["status"] == 'success-update')
    echo("<p>E-bike aangepast!</p>");
  else if($_GET["status"] == 'success-delete')
    echo("<p>E-bike verwijderd!</p>");
}
if (isset($_GET["q"])){
  echo("<p>Zoekresultaten voor: " . $_GET["q"] . "</p>");
}
?>

<!-- Content -->
<div id="grid-line">
  <p class="theader">Naam</p>
  <p class="theader">Kleur</p>
  <p class="theader">Batterij</p>
  <p class="theader">Leverancier</p>
  <p class="theader">Prijs</p>
  <p class="theader">Opties</p>
  <?php
  if(count($bicycles) == 0) 
    echo('<p>Geen e-bikes gevonden!</p>');
  foreach($bicycles as $bicycle) {
    echo('
    <p class="tbody">' . $bicycle->name . '</p>
    <p class="tbody">' . $bicycle->color . '</p>
    <p class="tbody">' . $bicycle->battery . '</p>
    <p class="tbody">' . $bicycle->supplier . '</p>
    <p class="tbody">€' . $bicycle->price . '</p>
    <p class="tbody"><a href="UpdatePage.php?id=' . $bicycle->id . '">Update</a>&nbsp;<a href="../PostRequestHandler.php?action=delete&id=' . $bicycle->id . '">Verwijder</a></p>
    ');
  }
  ?>
</div>
</body>
</html>