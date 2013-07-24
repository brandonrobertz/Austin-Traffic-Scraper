<?php

include_once('config.inc.php');

$con = mysql_connect( $serv, $user, $pass);
if (!$con){
  die('Could not connect: ' . mysql_error());
}

mysql_select_db( $db, $con);

$sql = "CREATE TABLE trafficker
(
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pubdate VARCHAR(50),
  location VARCHAR(100),
  description VARCHAR(125),
  category VARCHAR(50),
  lat FLOAT(10,6) NOT NULL,
  lng FLOAT (10,6) NOT NULL,
  filtered VARCHAR( 50 ) NOT NULL
)";
mysql_query($sql,$con);
mysql_close($con);

print "Installed";

?>