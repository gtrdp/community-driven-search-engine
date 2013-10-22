<?php
/*******************************************************************************
 * Procedure:
 * 	1. Check whether there is a url which is match with the pattern
 * 	2. From the url get the title, and check whether that url is listed in
 * 	   white list, black, list or nothing.
 * 		a. Whitelist
 * 		   Continue to user pattern check, then write the url and user into DB
 * 		b. Blacklist
 * 		   Skip this url and back to the #1 procedure
 * 		c. Nothing in the list
 * 		   check the url meta then insert the url into appropriate list
 *	3. End.
 ******************************************************************************/
function getUrl($file_name)
{
	$stack_url = array();
	$stack_user = array();
	
	
	//Open file
	if (isset($file_name))
		$log = fopen("logs/". $file_name, "r") or exit("Unable to open file!");
	else
	{
		echo "Please check file directory.";
		return FALSE;
	}
	
	//Create connection to database server
	$connection = mysql_connect("localhost","root","root");
	if (!$connection)
		die("Could not connect : " . mysql_error());
	mysql_select_db("search", $connection);
	
	//Open Black and White list url
	$black_list = file("results/list/black_list");
	$white_list = file("results/list/white_list");
	//Remove the newline character everylist and change the key
	foreach($black_list as $key => $value)
	{
		$value = trim($value);
		$black_list[$value] = $key;
		unset($black_list[$key]);
	}
	foreach($white_list as $key => $value)
	{
		$value = trim($value);
		$white_list[$value] = $key;
		unset($white_list[$key]);
	}
	
	//print_r($white_list);
	//Loop inside the file to find exact string from the pattern
	while (!feof($log))
	{
		$foo = fgets($log);
		if(preg_match("/(http|https|ftp):\/\/([A-Za-z0-9.-]+)\//", $foo, $match))
		{
			$url = $match[0];
		
			//echo $url."\n";
			
			if(!isset($black_list[$url]))
			{
				if (isset($white_list[$url]))
				{
					//That's a white url!
					//insert the url to the url stack
					array_push($stack_url, $url);
					
					//Check for the user who access it
					$pattern_user = "/\s([A-Za-z0-9.-]+)@/";
					if(preg_match($pattern_user, $foo, $match))
					{
						$user = $match[0];
						//remove char we don't need
						$user = str_replace(" ", "", $user);
						$user = str_replace("@", "", $user);
						//put on the array
						array_push($stack_user, $user);
						//echo "udah di white list, user:".$user."\n";
					}
					else
						array_push($stack_user, "none");
						
				}
				else
				{
					//we do not know whether the url is white or black
					//So check for the meta
					$meta = getUrlData($url);
					
					//if there is any meta, put it in the white list, and
					//viceversa
					if (($meta["title"] != "none" || $meta["description"] != "none") && $meta["title"] != "")
					{
						//We conclude that the url is a whitelist's
						$white_list[$url] = 123;
						//insert url into array
						array_push($stack_url, $url);
						
						//escape the special character before inserting to the Database
						$meta["title"] = mysql_real_escape_string($meta["title"]);
						$meta["description"] = mysql_real_escape_string($meta["description"]);
						$meta["keywords"] = mysql_real_escape_string($meta["keywords"]);
						
						//write the url into the database
						mysql_query("INSERT INTO url VALUES('', '".$url."')", $connection);
						//Write the Meta into the database
						mysql_query("INSERT INTO meta VALUES('', '".$meta["title"]."', '".$meta["description"]."', '".$meta["keywords"]."')", $connection);
						$bar = mysql_error();
						if ($bar != "")
						{
							echo "INSERT INTO meta VALUES('', '".$meta["title"]."', '".$meta["description"]."', '".$meta["keywords"]."')\n";
							echo $bar."\n";
						}
						//Check for the user who access it
						$pattern_user = "/\s([A-Za-z0-9.-]+)@/";
						if(preg_match($pattern_user, $foo, $match))
						{
							$user = $match[0];
							//remove char we don't need
							$user = str_replace(" ", "", $user);
							$user = str_replace("@", "", $user);
							//put on the array
							array_push($stack_user, $user);
							//echo "belom di white list, user:".$user."\n";
						}
						else
							array_push($stack_user, "none");
						
					}
					else
					{
						//if not available, so its suppose to be a blacklist's url
						$black_list[$url] = 123;
						//echo "Black list baru.\n";
					}
				}
			}
			//else
				//echo "Kena Black list.\n";
		}
	}
	
	//write the black list and white list
	$file = fopen("results/list/black_list", "w") or exit("Unable to open file!");
	foreach ($black_list as $key => $value)
		fwrite($file, $key . "\n");
	fclose($file);
	$file = fopen("results/list/white_list", "w") or exit("Unable to open file!");
	foreach ($white_list as $key => $value)
		fwrite($file, $key . "\n");
	fclose($file);
	
	$url_file = fopen("results/url/". $file_name, "w") or exit ("Cannot open File!");
	$user_file = fopen("results/user/". $file_name, "w") or exit ("Cannot open File!");
	//loop for writing url begin
	foreach ($stack_url as $value)
		fwrite($url_file, $value."\n");
	fclose($url_file);
	
	//loop for writing user begin
	foreach ($stack_user as $value)
		fwrite($user_file, $value."\n");
	fclose($user_file);
}