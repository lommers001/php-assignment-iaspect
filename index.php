<?php
require 'vendor/autoload.php';

//Redirects to the main page
header('Location: ' . './src/pages/MainPage.php', true, 303);
die();

