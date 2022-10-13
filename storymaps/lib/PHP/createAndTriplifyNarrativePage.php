
<?php
//DB POSTGRES
require('PgConn.php');

$slides= $_POST['slides'];
$user= $_POST['user'];
$title= $_POST['narrationTitle'];
$id_nar= $_POST['idNarr']; 
$subjectNarration= $_POST['subject'];
$baseUrl= $_POST['baseURL'];


//////////////Triplify////////////

	$date = new DateTime();
	
	$events=[];
	$entitys=[];
	$info="";
	$currentdbname= $id_nar . $user . "-" . strtolower($subjectNarration);
	
	$start= $date->getTimestamp();

	//get events
	$sql= "SELECT id, value FROM \"".$currentdbname."\" where id ~ '^ev'";
	$result = pg_query($sql) or die('Error message: ' . pg_last_error());


	while ($row=pg_fetch_assoc($result)) {
		//array_push($events, json_decode($row['value']));
		$events[str_replace(" ","",$row['id'])] =  json_decode($row['value']);
	}
	pg_free_result($result);
	
	
	//get entitys
	$sql= "SELECT id, value FROM \"".$currentdbname."\" where id ~ '^Q' or id ~ '^U'" ;
	$result = pg_query($sql) or die('Error message: ' . pg_last_error());
	
	while ($row=pg_fetch_assoc($result)) {
		//array_push($entitys, json_decode($row['value']));
		$entitys[str_replace(" ","",$row['id'])] =  json_decode($row['value']);
	}
	pg_free_result($result);
	

	//get info (A1)
	$sql= "SELECT value FROM \"".$currentdbname."\" where id = 'A1'" ;
	$result = pg_query($sql) or die('Error message: ' . pg_last_error());
	
	while ($row=pg_fetch_assoc($result)) {
		$info= json_decode($row['value']);
	}
	pg_free_result($result);
	
	// create json object
	//header('Content-Type: application/json');
	$results = array("entities"=> $entitys, "narra"=> $info, "events"=> $events);
	$dataJson = json_encode($results);

	
	//create jsonFile to pass at jar file
	//$myfile = fopen("/var/www/tool.dlnarratives.eu/owl/" . $currentdbname . ".json", "w+") or die("Unable to open file!");
	$myfile = fopen("/tmp/" . $currentdbname . ".json", "w+") or die("Unable to open file!");
	fwrite($myfile, $dataJson);
	fclose($myfile);
	
	
	//create owl file  ProvaJv
	//$path = "/usr/local/etc/selfservice/";
	$path = "../Triplify/";
	chdir($path);
	$owlFile= sprintf("%s-%s.owl", $currentdbname, bin2hex(random_bytes(40)));
	
	
	//exect jar file and disply eventual errors
	error_reporting(E_ALL);
	$cmd = "java -jar " . $path . "triplify.jar /tmp/" . $currentdbname . ".json /var/www/tool.dlnarratives.eu/owl/".$user. "_" . $id_nar .".owl ".$baseUrl." 2>&1";

	$output=null;
	$retval=null;
	exec($cmd,$output,$retval);
	//echo "Returned with status $retval and output:\n";
	//print_r($output);  // TO SEE ALL JAVA OUTPUT (ERROR)



	$end= $date->getTimestamp();
	
/* 		// array json
		$arrayJson= array( "id" => $output, "numberEvents" => $output[1], "consistent" => $output[5], "pathFileOwl" => "../owl/".explode("-", $currentdbname)[0]. "_" . $_POST['idNarration'] .".owl");	
		$data= json_encode($arrayJson);
		echo $data; */




//////////////Publish////////////
if($baseUrl == "dlnarratives.moving.d4science.org"){
	
	$folderToSaveStory= "storymaps";
	$buttonLeft='		
		<a href="../../../Search/?user='.$user.'&id=N'.$id_nar.'">
		<div class="otherNarratives">
		  <button class="dropbtn">Search</button>

		</div>
		</a>';
		
	$buttonRight= '
		<div class="otherVisual">
		  <button class="dropbtn">Other Visualizations</button>
		  <div class="otherVisual-content">
			<a href="?visualization=map">Storymap</a>
			<a href="?visualization=timeline">Timeline</a>
		  </div>
		</div>';
		
} else {
	$folderToSaveStory="storymaps";
	$buttonLeft= '
		<div class="otherNarratives">
		<button class="dropbtn" onclick="history.back()">Back</button>
		</div>';
		
	$buttonRight= '
		<div class="otherVisual">
		  <button class="dropbtn">Other Visualizations</button>
		  <div class="otherVisual-content">
			<a href="?visualization=map">Storymap</a>
			<a href="?visualization=timeline">Timeline</a>
			<a href="../../../Entity_With_Events/?user='.$user.'&idNarra=N'.$id_nar.'">Entity With Related Events</a>
		  </div>
		</div>';
}

if (!file_exists('../'.$folderToSaveStory.'/'.$user)) {
    mkdir('../'.$folderToSaveStory.'/'.$user, 0777, true);
	chmod('../'.$folderToSaveStory.'/'.$user, 0777);
}


if (!file_exists('../'.$folderToSaveStory.'/'.$user. '/N' . $id_nar)) {
    mkdir('../'.$folderToSaveStory.'/'.$user. '/N' . $id_nar, 0777, true);
	chmod('../'.$folderToSaveStory.'/'.$user. '/N' . $id_nar, 0777);
}


$fp = fopen('../'.$folderToSaveStory.'/'.$user. '/N' . $id_nar. '/slide.json', 'w');
fwrite($fp, $slides);
fclose($fp);
chmod('../'.$folderToSaveStory.'/'.$user. '/N' . $id_nar. '/slide.json', 0777);




$html= '<html>
<head>
	<meta charset="UTF-8">
    <title>Narrative - '. $title .'</title>
    <link rel="stylesheet" type="text/css" href="../../../lib/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="../../../lib/narra.css" />
    <script src="../../../lib/jquery-3.2.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="../../../lib/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="../../../lib/bootstrap.min.js" type="text/javascript" charset="utf-8"></script>
	
    <script src="../../../lib/typeahead.bundle.min.js" type="text/javascript" charset="utf-8"></script>
	

	<script src="../../lib/visualization.js" type="text/javascript" charset="utf-8"></script>

	<link rel="stylesheet" href="https://cdn.knightlab.com/libs/storymapjs/0.8.6/css/storymap.css">
	<script type="text/javascript" src="../../../lib/storymap.js"></script>  
	<link rel="stylesheet" type="text/css" href="../../../lib/timeline.css" />
	<script src="../../../lib/timeline-min.js" type="text/javascript" charset="utf-8"></script>
	
	<link rel="stylesheet" type="text/css" href="../../lib/narrativeVisualization.css" />

</head>

<body>
	<div id="menu">
		<div id="titleTable">
		<h1>'. $title .'</h1>
		</div>
	
	'.$buttonRight.'
		
	'.$buttonLeft.'
		
	</div>
	
	<div id="mapdiv"></div>

	<!-- <script src="../../lib/LoadJsonSlidesAndBugFixSlide.js" type="text/javascript" charset="utf-8"></script>-->
	
	
</body>
</html>
';

$fp1 = fopen('../'.$folderToSaveStory.'/'.$user. '/N' . $id_nar. '/index.html', 'w');
fwrite($fp1, $html);
fclose($fp1);
chmod('../'.$folderToSaveStory.'/'.$user. '/N' . $id_nar. '/index.html', 0777);




		
		
// array json
		$arrayJson= array( "msg" => $slides, "link" => 'https://'.$baseUrl.'/'.$folderToSaveStory.'/'.$user. '/N' . $id_nar . '/', "output" => $output);		
		$data= json_encode($arrayJson);
		echo $data;
?>
