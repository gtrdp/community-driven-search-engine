<?php
//Functions
include("scripts/getUrl.php");
include("scripts/getUrlData.php");
include("scripts/countUrl.php");
include("scripts/countUser2.php");
include("scripts/cluster.php");
//include("scripts/getTime.php");
include("scripts/metaWeighing.php");


//Time Counter
function getTime()
{
	$a = explode (' ',microtime());
	return(double) $a[0] + $a[1];
}
$start = getTime(); 

/*
//******************************************************************************
//		Frist read files, get and save the url
//******************************************************************************
$end = getTime();
echo "\n\nRead File!\n";
echo "Time taken = ".number_format(($end - $start),2)." secs\n";

//var_dump(getUrlData("http://www.blogtopsites.com/"));


//delete white and black list
$file = fopen("results/list/black_list", "w") or exit ("Cannot open File!");
fclose($file);
$file = fopen("results/list/white_list", "w") or exit ("Cannot open File!");
fclose($file);

//create connection to MySQL Server
$connection = mysql_connect("localhost","root","root");
if (!$connection)
	die("Could not connect : " . mysql_error());
mysql_select_db("search", $connection);
//empty the table meta and url
mysql_query("TRUNCATE meta", $connection);
mysql_query("TRUNCATE url", $connection);
mysql_close($connection);


$handler = opendir("logs");
while ($file = readdir($handler))
{
	if ($file != "." && $file != ".." && $file != "old" && $file != "real")
	{
		getUrl($file);
	}
}
closedir($handler);

$end = getTime();
echo "Time taken = ".number_format(($end - $start),2)." secs\n";
echo "Read File Completed!\n\n";


//*****************************************************************************
//		Counting URL
//*****************************************************************************
$end = getTime();
echo "\n\nCounting URL!\n";
echo "Time taken = ".number_format(($end - $start),2)." secs\n";

//create connection to MySQL Server
$connection = mysql_connect("localhost","root","root");
if (!$connection)	die("Could not connect : " . mysql_error());
mysql_select_db("search", $connection);

//empty the frequency table first
mysql_query("TRUNCATE frequency", $connection);
mysql_close($connection);

$handler = opendir("results/url");
$i = 1;
while ($file = readdir($handler))
{
	if ($file != "." && $file != "..")
	{
		countUrl($file, $i++);
	}
}
closedir($handler);

//Calculate Document Properties, needed for tfidf
//create connection to MySQL Server
$connection = mysql_connect("localhost","root","root");
if (!$connection)	die("Could not connect : " . mysql_error());
mysql_select_db("search", $connection);

//Empty the table document
mysql_query("TRUNCATE document", $connection);

$total_word = array();
$result = mysql_query("SELECT SUM(`f1`) AS sum1, SUM(`f2`) AS sum2, SUM(`f3`) AS sum3 FROM frequency", $connection);
$total_word = mysql_fetch_array($result);
unset($total_word[0], $total_word[1], $total_word[2]);
$total_word["total_word"] = array_sum($total_word);

//insert Data into the table
mysql_query("INSERT INTO document VALUES('', ".$total_word["sum1"].",".$total_word["sum2"].",".$total_word["sum3"].",".$total_word["total_word"].")", $connection);
mysql_close($connection);

$end = getTime();
echo "Time taken = ".number_format(($end - $start),2)." secs\n";
echo "Count URL Completed!\n\n";




//*****************************************************************************
//		Calculating User
//*****************************************************************************
$end = getTime();
echo "\n\nCalculating User!\n";
echo "Time taken = ".number_format(($end - $start),2)." secs\n";

for ($i=1; $i < 4; $i++)
{
	countUser2($i);
}

$end = getTime();
echo "Time taken = ".number_format(($end - $start),2)." secs\n";
echo "Calculating User Completed!\n\n";




/*
//*****************************************************************************
//		Calculating Access time
//*****************************************************************************
$end = getTime();
echo "\n\nCalculating Access Time!\n";
echo "Time taken = ".number_format(($end - $start),2)." secs\n";

for ($i=1; $i < 4; $i++)
{
	countTime($i);
}

//Calculate average
//create connection to MySQL Server
$connection = mysql_connect("localhost","root","root");
if (!$connection)	die("Could not connect : " . mysql_error());
mysql_select_db("search", $connection);

//Select needed data
$result = mysql_query("SELECT `id`,`f1`,`f2`,`f3` FROM time", $connection);

while($row = mysql_fetch_array($result))
{
	$foo = 0;
	$sum = 0;
	for ($i=1; $i < 4; $i++)
	{
		if($row["f".$i])
		{
			$foo++;
			$sum += $row["f".$i];
		}
		else
			$sum += $row["f".$i];
	}
	if($foo == 0)
		$foo = 1;
	
	$query = "UPDATE `time` SET `average`=".($sum/$foo)." WHERE `id`='".$row["id"]."'";
	mysql_query($query, $connection);
}

mysql_close($connection);
$end = getTime();
echo "Time taken = ".number_format(($end - $start),2)." secs\n";
echo "Calculating Access Time Completed!\n\n";




//*****************************************************************************
//		Calculating TFIDF
//*****************************************************************************
$end = getTime();
echo "\n\nCalculating TF-IDF!\n";
echo "Time taken = ".number_format(($end - $start),2)." secs\n";

//create connection to MySQL Server
$connection = mysql_connect("localhost","root","root");
if (!$connection)	die("Could not connect : " . mysql_error());
mysql_select_db("search", $connection);

//Select needed data
$result = mysql_query("SELECT * FROM document", $connection);
$row = mysql_fetch_array($result);
//unset sum first, because we used variable sum before
unset($sum);
$sum[1] = $row["sum1"];
$sum[2] = $row["sum2"];
$sum[3] = $row["sum3"];
$sum["total"] = $row["total_word"];


//Empty the table tfidf and Select All data from table frequency
mysql_query("TRUNCATE tfidf", $connection);
$result = mysql_query("SELECT * FROM frequency", $connection);
mysql_close($connection);

//main loop
$z = 1;
while($row = mysql_fetch_array($result))
{
	$tf_idf = array();
	$tf = array();
	$df = $row["f1"]+$row["f2"]+$row["f3"];
	$id = $row["id"];
	
	//loop for repeating each 'i' (proxies)
	for($i = 1; $i < 4; $i++)
	{
		$tf[$i] = $row["f".$i]/$sum[$i];
		//count TFIDF
		if($tf[$i] != 0)
			$tf_idf[$i] = ((1 + log10($tf[$i])) * (log10($sum["total"]/$df)))+log10($sum["total"]+log10($df));
		else
			$tf_idf[$i] = 0;
	}
	$weight = array_sum($tf_idf)/3;

	
	//Insert calculated data into database
	$connection = mysql_connect("localhost","root","root");
	if (!$connection)	die("Could not connect : " . mysql_error());
	mysql_select_db("search", $connection);
	mysql_query("INSERT INTO tfidf (id, `1`, `2`, `3`, weight) VALUES('".$id."',".$tf_idf[1].",".$tf_idf[2].",".$tf_idf[3].",".$weight.")");
	mysql_close($connection);
}


$end = getTime();
echo "Time taken = ".number_format(($end - $start),2)." secs\n";
echo "Calculating TF-IDF Completed!\n\n";





//*****************************************************************************
//		Clustering
//*****************************************************************************
$end = getTime();
echo "\n\nClustering!\n";
echo "Time taken = ".number_format(($end - $start),2)." secs\n";

//create connection to MySQL Server
$connection = mysql_connect("localhost","root","root");
if (!$connection)	die("Could not connect : " . mysql_error());
mysql_select_db("search", $connection);

//emtpy table clusters first
mysql_query("TRUNCATE cluster", $connection);

$file_x = fopen("results/coordinates/". "x", "w") or exit ("Cannot open File!");
$file_y = fopen("results/coordinates/". "y", "w") or exit ("Cannot open File!");

//Data for saving the coordinates
$data = array();

//Get the X
$i = 0;
$result = mysql_query("SELECT weight from tfidf ORDER BY `tfidf`.`id` ASC", $connection);
while ($row = mysql_fetch_array($result))
{
	$data[][] = $row["weight"];
	fwrite($file_x, $row["weight"]."\n");
	//if($i++ == 100)
	//	break;
}

//Get the Y
$i = 0;
$result = mysql_query("SELECT `f1`,`f2`,`f3` from url_user ORDER BY `url_user`.`id` ASC", $connection);
//$result = mysql_query("SELECT `average` FROM `time` ORDER BY `time`.`id` ASC", $connection);
while ($row = mysql_fetch_array($result))
{
	$data[$i++][1] = $row["f1"] + $row["f2"] + $row["f3"];
	fwrite($file_y, $row["f1"] + $row["f2"] + $row["f3"]."\n");
	//$data[$i++][1] = $row["average"];
	//fwrite($file_y, $row["average"]."\n");
	//if($i++ == 100)
	//	break;
}


//var_dump($data);
//exit();

$kmeans_result = kMeans($data, 2);

//print_r($kmeans_result);

echo "centroidX\n";
foreach($kmeans_result["centroids"] as $value)
	echo $value[0]."\n";

echo "\ncentroidY\n";
foreach($kmeans_result["centroids"] as $value)
	echo $value[1]."\n";


//insert data into database
unset($kmeans_result["centroids"]);
foreach($kmeans_result as $cluster_id => $coordinates)
{
	$cluster = $cluster_id;
	foreach($coordinates as $url_id => $tfidf)
	{
		$query = "INSERT INTO cluster VALUES(".($url_id + 1).",".$cluster.")";
		//$query = "UPDATE words SET cluster=". $cluster ." WHERE id=". ($word_id+1) ."";
		mysql_query($query, $connection);
	}
}

mysql_close($connection);



//******************************************************************************
//		Weigh the word to get the tfidf
//******************************************************************************
print_r(metaWeighing());
*/


//******************************************************************************
//		Clustering the Words
//******************************************************************************
//create connection to MySQL Server
$connection = mysql_connect("localhost","root","root");
if (!$connection)	die("Could not connect : " . mysql_error());
mysql_select_db("search", $connection);

//emtpy table clusters first
mysql_query("TRUNCATE cluster_word", $connection);

//Data for saving the coordinates
$data = array();

//Get the X
/*$i = 0;
$result = mysql_query("SELECT id, weight from words ORDER BY `words`.`id` ASC", $connection);
while ($row = mysql_fetch_array($result))
{
	$data[] = array($row["id"], $row["weight"]);
	//if($i++ == 100)
	//	break;
}*/


$file_x = fopen("results/coordinates/". "x", "w") or exit ("Cannot open File!");
$file_y = fopen("results/coordinates/". "y", "w") or exit ("Cannot open File!");
//Get the X
$i = 0;
$result = mysql_query("SELECT id, weight from words ORDER BY `words`.`id` ASC", $connection);
while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
{
	$data[][] = $row["weight"];
	fwrite($file_x, $row["weight"]."\n");
	//if($i++ == 10)
		//break;
}

//Get the Y
$i = 0;
$result = mysql_query("SELECT id, word from words ORDER BY `words`.`id` ASC", $connection);
//$result = mysql_query("SELECT `average` FROM `time` ORDER BY `time`.`id` ASC", $connection);
while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
{
	
	mysql_select_db("wordnet30", $connection);
	$query = "SELECT lemma, lexdomainid FROM `wordsXsensesXsynsets` WHERE `lemma` LIKE '".$row["word"]."' LIMIT 0 , 1";
	$result_word = mysql_query($query, $connection);
	
	if($foo = mysql_fetch_array($result_word, MYSQL_ASSOC))
	{
		fwrite($file_y, $foo["lexdomainid"]."\n");
		$data[$i++][1] = $foo["lexdomainid"];
		//echo $foo["lexdomainid"]."\n";
	}
	else
	{
		fwrite($file_y, "45\n");
		$data[$i++][1] = 45;
		//echo $query."\n"; 45
	}
	
	//if($i == 11)
		//break;
}


//var_dump($data);
//exit();

$kmeans_result = kMeans($data, 7);

//print_r($kmeans_result);

//Print Centroid
echo "centroidX\n";
foreach($kmeans_result["centroids"] as $value)
	echo $value[0]."\n";
echo "\ncentroidY\n";
foreach($kmeans_result["centroids"] as $value)
	echo $value[1]."\n";

/*
//insert data into database
unset($kmeans_result["centroids"]);
foreach($kmeans_result as $cluster_id => $coordinates)
{
	$cluster = $cluster_id;
	foreach($coordinates as $word_id => $tfidf)
	{
		$query = "INSERT INTO cluster_word VALUES(".($word_id + 1).",".$cluster.")";
		//$query = "UPDATE words SET cluster=". $cluster ." WHERE id=". ($word_id+1) ."";
		mysql_query($query, $connection);
	}
}*/

mysql_close($connection);



//Show Message
$end = getTime();
echo "\n\nSuccess!\n";
echo "Time taken = ".number_format(($end - $start),2)." secs\n\n";

?>