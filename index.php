<?php
require 'vendor/autoload.php';

//Redirect to the main page
header('Location: ' . './src/pages/MainPage.php', true, 303);
die();

