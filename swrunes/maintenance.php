<?php

include("dbwrapper.php");
$dbase = new Wrapper($sw_user,$sw_pass,$host,$sw_db,null);

//$optimize = $dbase->Maintenance();
/*$optimize = $dbase->createNewOptimizerTableCopyData(15,"sw_optimizer1");
$optimize = $dbase->dropTable("sw_optimizer");
$optimize = $dbase->renameTable("sw_optimizer1","sw_optimizer");*/

$optimize = $dbase->dropTable("sw_optimizer");
$optimize = $dbase->createNewOptimizerTable("sw_optimizer");
$dbase->close();

print_r($optimize);
?>
