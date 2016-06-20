<html>
<body>
<?php
include("dbwrapper.php");

$dbase = new Wrapper($sw_user,$sw_pass,$host,$sw_db,null);

$totalRecords = $dbase->getTotalRecords();
//$totalSessions = $dbase->getUniqueSessions();
$recordsPerSession = $dbase->getSessionsAndRecords();

$dbase->close();

echo "Statistics for the most recent usage (last 1 hour): <br/>";
echo "Total builds count: ".$totalRecords[0]["cnt"]."<br/>";
echo "Total users: ".count($recordsPerSession)."<br/>";
echo "Sessions and number of builds: (if the tool works slow, blame the top people on the list)<br/>";
if(count($recordsPerSession) > 0){
	echo "<table border=\"1\"> <thead><th>Session ID</th><th>Builds count</th></thead> <tbody>
	";
	for($i=0; $i<count($recordsPerSession) ; $i++) {
		echo "<tr><td>".substr($recordsPerSession[$i]["session"], 0, 4)."**</td><td>".$recordsPerSession[$i]["cnt"]."</td></tr>
		";
	}
	echo "</tbody> </table>
	";
}


?>

</body>
</html>
