<?php
/*******************************************************************************
 * Procedure:
 * 	1. get unique url list from frequency table
 * 	2. loop unique url and look for unique user
 * 	3. insert data into database
 * *****************************************************************************
 */
function countUser($i)
{
	//create connection to MySQL Server
	$connection = mysql_connect("localhost","root","root");
	if (!$connection)
		die("Could not connect : " . mysql_error());
	mysql_select_db("search", $connection);
	
	//empty the table url_user
	if ($i == 1)
		mysql_query("TRUNCATE url_user", $connection);
	
	//get all unique url
	$result = mysql_query("SELECT `url` FROM frequency", $connection);
	
	while($url = mysql_fetch_array($result))
	{
		$query =	"SELECT * FROM `url_user_".$i."` ".
					"WHERE `url`='".$url["url"]."' ".
					"GROUP BY `url`,`user`";
		$foo = mysql_query($query, $connection);
		$rows = mysql_num_rows($foo);
		
		if ($i == 1)
		{
			//and then insert the data
			$query = "INSERT INTO `url_user` (url, `f1`) VALUES('".$url["url"]."',".$rows.")";
			mysql_query($query, $connection);
		}
		else
		{
			$query = "UPDATE `url_user` SET `f".$i."`=".$rows." WHERE `url`='".$url["url"]."'";
			mysql_query($query, $connection);
		}
	}
	
	mysql_close($connection);
}
