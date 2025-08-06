<?php

$dbhost = "localhost";
$dbuser = "{my_username}";
$dbpass = "{my_password}";
$dbname = "cfbp";

if(!$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname)) {
    die("failed to connect to db");
}
