<?php
/*******************************************************************************
 * Procedure:
 * 	1. get unique url list from frequency table
 * 	2. loop unique url and look for the elapsed time
 * 	3. calculate the mean
 *  4. insert the data into database
 * *****************************************************************************
 */
function countTime($i)
{
	$list_url = file("results/url/".$i);
	$list_time = file("results/time/".$i);
	
	foreach($list_time as &$foo)
		$foo = trim($foo);
	
	$data = array();
	
	foreach($list_url as $url_index => $url_value)
	{
		$url_value = trim($url_value);
		
		if(isset($data[$url_value]))
		{
			if($list_time[$url_index])
				$data[$url_value] = ($data[$url_value] + $list_time[$url_index]) / 2;
		}
		else
		{
			$data[$url_value] = $list_time[$url_index];
		}
	}
	
	//print_r($data);
	
	//insert Data into database
	//create connection to MySQL Server
	$connection = mysql_connect("localhost","root","root");
	if (!$connection)
		die("Could not connect : " . mysql_error());
	mysql_select_db("search", $connection);
	
	//empty the table url_user
	if ($i == 1)
		mysql_query("TRUNCATE time", $connection);
	
	//Select the unique url list
	$result = mysql_query("SELECT `id`,`url` FROM frequency", $connection);
	
	while($unique = mysql_fetch_array($result))
	{
		if(isset($data[$unique["url"]]))
		{
			//update the data
			$query = "UPDATE `time` SET `f".$i."`=".$data[$unique["url"]]." WHERE `id`='".$unique["id"]."'";
			mysql_query($query, $connection);
			
			//if nothing to update, it's suppose to be new url, so insert it
			if(mysql_affected_rows() == 0)
			{
				//and then insert the data
				$query = "INSERT INTO `time` (id, `f".$i."`) VALUES(".$unique["id"].", ".$data[$unique["url"]].")";
				mysql_query($query, $connection);
			}
		}
	}
	mysql_close($connection);
}
