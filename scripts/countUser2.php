<?php
/*******************************************************************************
 * Procedure:
 * 	1. get unique url list from frequency table
 * 	2. loop unique url and look for unique user
 * 	3. insert data into database
 * *****************************************************************************
 */
function countUser2($i)
{
	$list_url = file("results/url/".$i);
	$list_user = file("results/user/".$i);
	
	foreach($list_user as &$foo)
		$foo = trim($foo);
	
	$data = array();
	
	foreach($list_url as $url_index => $url_value)
	{
		$url_value = trim($url_value);
		
		if(isset($data[$url_value]))
		{
			if(!isset($data[$url_value][$list_user[$url_index]]))
				$data[$url_value][$list_user[$url_index]] = 1;
			else
				$data[$url_value][$list_user[$url_index]]++;
		}
		else
		{
			$data[$url_value][$list_user[$url_index]] = 1;
		}
	}
	
	
	//insert Data into database
	//create connection to MySQL Server
	$connection = mysql_connect("localhost","root","root");
	if (!$connection)
		die("Could not connect : " . mysql_error());
	mysql_select_db("search", $connection);
	
	//empty the table url_user
	if ($i == 1)
		mysql_query("TRUNCATE url_user", $connection);
	
	//Select the unique url list
	$result = mysql_query("SELECT `id`,`url` FROM url", $connection);
	
	while($unique = mysql_fetch_array($result))
	{
		if(isset($data[$unique["url"]]))
		{
			$unique_user = count($data[$unique["url"]]);
			
			//update the data
			$query = "UPDATE `url_user` SET `f".$i."`=".$unique_user." WHERE `id`=".$unique["id"]."";
			mysql_query($query, $connection);
			
			//if nothing to update, it's suppose to be new url, so insert it
			if(mysql_affected_rows() == 0)
			{
				//and then insert the data
				$query = "INSERT INTO `url_user` (id, `f".$i."`) VALUES(".$unique["id"].",".$unique_user.")";
				mysql_query($query, $connection);
			}
		}
	}
	
	mysql_close($connection);
}
