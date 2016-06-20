<?php
ini_set('display_errors',1);
error_reporting(-1);
include("dbwrapper.php");
header('Content-Type: text/json');

function newResult() {
	return array("success"=>false, "id"=>0, "error"=>"Unexpected error", "data"=>"", "redirect"=>"", "field_errors"=>"");
}

if(!isset($_POST["sessionId"]) || !isset($_POST["optimize_run"])
|| !is_numeric($_POST["sessionId"]) || !is_numeric($_POST["optimize_run"])) {
	$ajax_result = newResult();
	echo json_encode($ajax_result);
	die();
}

if( $use_file == 1) {
	try{
		$filename = $import_file_path.$_POST["sessionId"]."_".$_POST["optimize_run"].".csv";

		$ajax_result = newResult();
		$newRows = 0;
		$ajax_result["error"] = "";

		$before = microtime(true);
		//$dbase->newOptimizationFromFile($swrunes_dir.$filename);
		$res = loadFromFile2($swrunes_dir.$filename);
		unlink($filename);
		$after = microtime(true);
		$difference = $after - $before;
		$ajax_result["error"] .= " File load db time: ".$difference;
		file_put_contents("db_insert.txt", date("Y-m-d H:i:s").";".$difference.";".$_POST["sessionId"]."\n",FILE_APPEND | LOCK_EX);

		if($res ==1 ) {
			$ajax_result["id"] = 1;
			$ajax_result["success"] = true;
			$ajax_result["optimize_run"] = $_POST["optimize_run"];
		}else {
			$ajax_result["id"] = 0;
			$ajax_result["error"] = $res;
		}
		echo json_encode($ajax_result);
	}catch(Exception $e){
		$ajax_result = newResult();
		$ajax_result["optimize_run"] = $_POST["optimize_run"];
		$ajax_result["error"] = $e->getMessage();
		echo json_encode($ajax_result);
	}
}
else {
	$ajax_result = newResult();
	$ajax_result["id"] = 1;
	$ajax_result["success"] = true;
	$ajax_result["optimize_run"] = $_POST["optimize_run"];
	echo json_encode($ajax_result);
}
?>
