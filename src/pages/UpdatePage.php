<?php
require '/var/www/html/vendor/autoload.php';
use Src\DatabaseConnector;

//Init tables, if not already done
$dc = new DatabaseConnector();
$dc->init();
//Obtain supplier's names
$suppliers = $dc->get_all($dc::TABLE_SUPPLIERS);
//Obtain bicycle info
$bicycle;
$id = -1;
if(isset($_GET["id"]))
  $id = $_GET["id"];
$bicycle = $dc->get_by_id($dc::TABLE_BICYCLES, $id);
?>

<!DOCTYPE html>
<html>
<head>
<title>Update</title>
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
<form name="createForm" action="<?php echo('../PostRequestHandler.php?action=update&id=' . $id); ?>" 
 onsubmit="return validateForm()" method="post">
  <p> Naam: <input name="name" maxlength="80" value="<?php echo($bicycle->name); ?>" required /> </p>
  <p> Kleur: <input name="color" maxlength="40" value="<?php echo($bicycle->color); ?>" /> </p>
  <p> Accu: <input name="battery" maxlength="80" value="<?php echo($bicycle->battery); ?>" required /> </p>
  <p> Leverancier: <select name="supplier" required />
    <?php
    echo('<option value="' . $bicycle->supplier . '">' .$bicycle->supplier . ' (onveranderd)</option>');
    foreach($suppliers as $supplier) {
      echo('<option value="' . $supplier->name . '">' . $supplier->name . '</option>');
      }
    ?>
  </select> </p>
  <p> Prijs: <input type="number" name="price"  maxlength="6" value="<?php echo($bicycle->price); ?>" required /> </p>
  <p> <input type="submit" value="Pas aan" id="submitButton" /> </p>
</form>
</body>
</html>