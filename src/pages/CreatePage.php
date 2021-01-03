<?php
require '/var/www/html/vendor/autoload.php';
use Src\DatabaseConnector;

//Init tables, if not already done
$dc = new DatabaseConnector();
$dc->init();
//Obtain supplier's names
$suppliers = $dc->get_all($dc::TABLE_SUPPLIERS);
?>

<!DOCTYPE html>
<html>
<head>
<title>Create</title>
<style>
body {
  margin:0;
  font-family: sans-serif;
}
</style>
<script>
//Extra validation, when an older browser does not recognize the 'required' fields
function validateForm() {
  var x = document.forms["createForm" ];
  if (x["name"].value == "" || x["battery"].value == "" || x["supplier"].value == "" || !(/^\d+$/.test(x["price"].value))) {
    alert("Niet alles is correct ingevuld!");
    return false;
  }
}
</script>
</head>
<body>
<!-- Header -->
<iframe src="./Header.html" width="100%" height="40px">
</iframe>

<h3> Voeg E-bike toe </h3>

<!-- Form -->
<form name="createForm" action="../PostRequestHandler.php?action=create" onsubmit="return validateForm()" method="post">
  <p> Naam: <input name="name" maxlength="80" required /> </p>
  <p> Kleur: <input name="color" maxlength="40" /> </p>
  <p> Accu: <input name="battery" maxlength="80" required /> </p>
  <p> Leverancier: <select name="supplier" required />
    <option value="">--Kies een leverancier--</option>
    <?php
      foreach($suppliers as $supplier) {
        echo('<option value="' . $supplier->name . '">' . $supplier->name . '</option>');
      }
    ?>
  </select> </p>
  <p> Prijs: <input type="number" name="price"  maxlength="6" required /> </p>
  <p> <input type="submit" value="Voeg toe" id="submitButton" /> </p>
</form>
</body>
</html>