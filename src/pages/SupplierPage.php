<?php
require '/var/www/html/vendor/autoload.php';
use Src\DatabaseConnector;

//Init tables, if not already done
$dc = new DatabaseConnector();
$dc->init();
//Obtain data
$suppliers = $dc->get_suppliers_and_bicycles();
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
  grid-template-columns: 25% 25% 25% 25%; /* 4 COL LAYOUT */
}

#grid-line-header {
  display: grid;
  grid-template-columns: 30% 35% 35%; /* 3 COL LAYOUT */
}

.theader, .tbody {
  margin: 0px;
  padding: 5px;
}

.theader {
  background: #aaaaff;
  border: 1px solid black;
  text-align: center;
}

.tbody {
  background: #ddddff;
}

</style>
</head>
<body>
<!-- Header -->
<iframe src="./Header.html" width="100%" height="40px">
</iframe>

<!-- Content -->
<?php
$prev_supplier = "";
if(count($suppliers) == 0)
  echo('<p>Geen leveranciers gevonden!</p>');
foreach($suppliers as $supplier) {
  echo('
  <div id="grid-line-header">
    <p class="theader">' . $supplier->name . '</p>
    <p class="theader">Locatie: ' . $supplier->address . '</p>
    <p class="theader">Motto: ' . $supplier->description . '</p>
  </div>
  ');
  foreach($supplier->bicycles as $bicycle) {
    echo('
    <div id="grid-line">
      <p class="tbody">' . $bicycle->name . '</p>
      <p class="tbody">' . $bicycle->color . '</p>
      <p class="tbody">' . $bicycle->battery . '</p>
      <p class="tbody">€' . $bicycle->price . '</p>
    </div>
    ');
  }
}
?>
</body>
</html>