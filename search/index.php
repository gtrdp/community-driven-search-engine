<?php
include ('scripts/pagination.php');
if ($_GET["query"] != "")
{
	$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
	$link = "?query=". urlencode($_GET["query"]) . "&";
	//Parse the query
	$query = preg_replace("/[^a-zA-Z\s]/s", " ", $_GET["query"]);
	$query = preg_replace("/(\s)+/", " ", $query);
	$query = explode(" ", trim($query));
	
	//Create connection to database server
	$connection = mysql_connect("localhost","root","root");
	if (!$connection)
		die("Could not connect : " . mysql_error());
	mysql_select_db("search", $connection);
	
	//for storing url that match
	$matchUrl = array();
	//get the N (total document (URL))
	$result = mysql_query("SELECT COUNT(*) AS total FROM url", $connection);
	$count = mysql_fetch_array($result);
	$N = $count["total"];
	
	//Rank the URL based on tfidf value
	foreach($query as $qterm)
	{
		//get the desired word from database
		$result = mysql_query("SELECT df, url, tf FROM words WHERE word LIKE '".$qterm."'", $connection);
		
		//main loop for counting tfidf
		while ($doc = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			//fetch url and tf
			$url = explode(",", substr($doc["url"], 0, -1));
			$tf = explode(",", substr($doc["tf"], 0, -1));
			
			foreach ($url as $key => $value)
			{
				$matchUrl[$value] += $tf[$key] * log($N + 1 / $doc["df"], 2);
			}
		}
	}
	//Sort the value from high to low
	arsort($matchUrl);
	//print_r($matchUrl);
	
	//Display the url
	if (count($matchUrl) != 0)
	{
		//print_r($matchUrl);
		$div = "<ul>";	//for the div html
		$i = 0;
		$startpoint = ($page * 10) - 10;
		foreach ($matchUrl as $key => $value)
		{
			if ($i >= $startpoint && $i < ($startpoint + 10))
			{
				$sql = 	"SELECT url, title, description ".
						"FROM `meta` ".
						"LEFT JOIN url ON meta.id = url.id ".
						"WHERE url.id = ".$key;
			
				$result = mysql_fetch_array(mysql_query($sql, $connection), MYSQL_ASSOC);
				
				$result["description"] = substr($result["description"], 0, 250);
				//for bolding the query in the title and description
				foreach ($query as $qterm)
				{
					$result["title"] = preg_replace("/(".$qterm.")/i", " <em>$1</em> ", $result["title"]);
					$result["description"] = preg_replace("/(".$qterm.")/i", " <em>$1</em> ", $result["description"]);
					$url = preg_replace("/(".$qterm.")/i", "<em>$1</em>", $result["url"]);
				}
				$result["description"] = ($result["description"] == "none")? "" : $result["description"];
				
				$div .=	"<li>".
						"<h2><a href=\"".$result["url"]."\">".$result["title"]."</a></h2>".		//Title URL
						"<span class=\"url\">".$url."</span>".									//the URL
						"<p class=\"desc\">". $result["description"] ."</p>".						// The Description
						"</li>";
			}
			$i++;
		}
		$div .= "</ul>";
	}
	else
		$div = "<h1 align=\"center\">Whoops, sorry, there is no result to be shown for query: <br /> <em>".$_GET["query"]."</em></h1>";
}
else
	$div = "<h1 align=\"center\">Please insert a search query.</h1>";
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"	"http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title><?php ($_GET["query"] == "") ? NULL : print($_GET["query"]." - "); ?>Custom Search Engine</title>
	<link rel="stylesheet" type="text/css" href="style/style.css" />
	<link href="style/pagination.css" rel="stylesheet" type="text/css" />
	<link href="style/A_green.css" rel="stylesheet" type="text/css" />
</head>
<body>
	<div id="wrapper">
		<div id="search" align="center">
			<form name="input" id="input" action="" method="get">
				<span class="search">Search query:</span> <input type="text" name="query" value="<?php echo $_GET["query"];?>"/>
				<input type="submit" id="submit_button" value="Submit" />
			</form>
		</div>
		
		<div id="result">
			<?php echo $div; ?>
		</div>
		
		<div align="center" id="navigation">
			<?php
			if (count($matchUrl) > 10)
				echo pagination(count($matchUrl),$page,$link);
			?>
		</div>
	</div>
</body>
</html>