<?php
include("dbwrapper.php");
try{
	$dbase = new Wrapper($sw_user,$sw_pass,$host,$sw_db,null);

	$before = microtime(true);

	$filteredCount = $dbase->getOptimization($_POST["sessionId"].$_POST["optimize_run"], $_POST, $_POST["search"]["value"], $_POST["order"][0]["column"] + 1,$_POST["order"][0]["dir"],$_POST["start"],$_POST["length"],true);

	$totalCount = $dbase->getOptimization($_POST["sessionId"].$_POST["optimize_run"], array(), $_POST["search"]["value"], $_POST["order"][0]["column"] + 1,$_POST["order"][0]["dir"],$_POST["start"],$_POST["length"],true);

	$result = $dbase->getOptimization($_POST["sessionId"].$_POST["optimize_run"], $_POST, $_POST["search"]["value"], $_POST["order"][0]["column"] + 1,$_POST["order"][0]["dir"],$_POST["start"],$_POST["length"],false);

	$after = microtime(true);
	$difference = $after - $before;
	//file_put_contents("db_selects.txt", date("Y-m-d H:i:s").";".$difference.";".$_POST["sessionId"]."\n",FILE_APPEND | LOCK_EX);

	$response = array();
	$response["draw"] = $_POST["draw"];
	$response["recordsTotal"] = $totalCount[0]["cnt"];
	$response["recordsFiltered"] = $filteredCount[0]["cnt"];
	$response["data"] = $result;

	$dbase->close();
	echo json_encode($response);
}catch(Exception $e){
	$response = array();
	$response["draw"] = $_POST["draw"];
	$response["recordsTotal"] = 0;
	$response["recordsFiltered"] = 0;
	$response["data"] = array();
	$response["error"] = $e->getMessage();
	echo json_encode($response);
}
?>
