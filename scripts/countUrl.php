<?php
function countUrl($file_name, $i)
{
	$url_index = array();
	
		//read the url list text and count the frequency
		$file = file("results/url/". $file_name);
		$file = array_count_values($file);
		
		//create connection to MySQL Server
		$connection = mysql_connect("localhost","root","root");
		if (!$connection)
			die("Could not connect : " . mysql_error());
		mysql_select_db("search", $connection);
		
		$result = mysql_query("SELECT COUNT(*) AS total FROM frequency", $connection);
		$count = mysql_fetch_array($result);
		
		//Check the url index from url table
		$result = mysql_query("SELECT * FROM url", $connection);
		while ($foo = mysql_fetch_array($result))
			$url_index[$foo["url"]] = $foo["id"];
		unset($result);
		
		//loop for inserting data to database
		foreach ($file as $url => $frequency)
		{
			if ($count["total"] == 0)
			{
				//Insert words into database
				$query = "INSERT INTO frequency (id, `f".$file_name."`) VALUES('". $url_index[trim($url)] ."',".$frequency.")";
				mysql_query($query, $connection);
			}
			else
			{
				//update the data
				$query = "UPDATE frequency SET `f".$file_name."`=".$frequency." WHERE `id`='".$url_index[trim($url)]."'";
				mysql_query($query, $connection);
				
				//if nothing to update, it's suppose to be new url, so insert it
				if(mysql_affected_rows() == 0)
				{
					//and then insert the data
					$query = "INSERT INTO frequency (id, `f".$file_name."`) VALUES('". $url_index[trim($url)] ."',".$frequency.")";
					mysql_query($query, $connection);
				}
			}
		}
	mysql_close($connection);
}
