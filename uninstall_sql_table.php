<?php

include_once('config.inc.php');

$con = mysql_connect( $serv, $user, $pass);
if (!$con){
  die('Could not connect: ' . mysql_error());
}

mysql_select_db( $db, $con);

$sql = "DROP TABLE trafficker;";
mysql_query($sql,$con);
mysql_close($con);

print "Installed";

?>