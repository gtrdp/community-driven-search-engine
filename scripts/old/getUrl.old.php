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
 * ****************************************************************************/
function getUrl($file_name)
{
	$stack_url = array();
	$stack_user = array();
	$stack_time = array();
	$stack_meta = array("url"=>array(), "title"=>array(), "description"=>array(), "keywords"=>array());
	$match = array();
	
	//Open file
	if (isset($file_name))
		$file = fopen("logs/". $file_name, "r") or exit("Unable to open file!");
	else
	{
		echo "Please check file directory.";
		return FALSE;
	}

	//Open Black and White list url
	$black_list = fopen("list/black_list", "r") or exit("Unable to open black list file!");
	$white_list = fopen("list/white_list", "r") or exit("Unable to open white list file!");
	//Remove the newline character everylist and cheng
	/*foreach($black_list as $key => $value)
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
	}*/


	//loop inside the file to find the exact string that match with
	//the pattern (url)
	while(!feof($file))
	{
		$foo = fgets($file);
		$pattern_url = "/(http|https|ftp):\/\/([A-Za-z0-9.-]+)\//";
		preg_match($pattern_url, $foo, $match);
		$url = $match[0];
		echo $url."\n";
		if(!isset($black_list[$url]))
		{
			if (isset($white_list[$url]))
			{
				//insert url and its meta into array
				array_push($stack_url, $url);
					
				//check for the user
				$pattern_user = "/\s([A-Za-z0-9.-]+)@/";
				if(preg_match($pattern_user, $foo, $match))
				{
					$user = $match[0];
					//remove char we don't need
					$user = str_replace(" ", "", $user);
					$user = str_replace("@", "", $user);
					//put on the array
					array_push($stack_user, $user);
				}
				else
					array_push($stack_user, "none");

				/*check for time
				$pattern_time = "/\.([0-9]{3})\s+([0-9]+)\s/";
				if(preg_match($pattern_time, $foo, $match))
				{
					//remove char we don't need
					$match[0] = substr($match[0], 4);
					$match[0] = trim($match[0]);
					//put on the array
					array_push($stack_time, $match[0]);
				}*/
			}
			else
			{
				//check the url meta	
				$meta = getUrlData($url);
				
				//if the title meta is available
				if ($meta["title"] != "none")
				{
					//insert url and its meta into array
					array_push($stack_url, $url);
					$meta["url"] = $url;
					foreach($meta as $key => $value)
						$stack_meta[$key][] = $value;
					
					//check for the user
					$pattern_user = "/\s([A-Za-z0-9.-]+)@/";
					if(preg_match($pattern_user, $foo, $match))
					{
						$user = $match[0];
						//remove char we don't need
						$user = str_replace(" ", "", $user);
						$user = str_replace("@", "", $user);
						//put on the array
						array_push($stack_user, $user);
					}
					else
						array_push($stack_user, "none");
					
					$white_list[$url] = "string";
					/*check for time
					$pattern_time = "/\.([0-9]{3})\s+([0-9]+)\s/";
					if(preg_match($pattern_time, $foo, $match))
					{
						//remove char we don't need
						$match[0] = substr($match[0], 4);
						$match[0] = trim($match[0]);
						//put on the array
						array_push($stack_time, $match[0]);
					}*/
				}
				//if not available, so its suppose to be a blacklist's url
				else
					$black_list[$url] = 123;
			}
		}
	}
	fclose($file);
	fclose($black_list);
	fclose($white_list);
	
	print_r($white_list);
	
	//sort the url list
	//sort($stack_url);
	
	/***************************************************************************
	 * 
	 * 							Writing Process
	 * 
	 **************************************************************************/
	/*
	//write to file
	$url_file = fopen("results/url/". $file_name, "w") or exit ("Cannot open File!");
	$user_file = fopen("results/user/". $file_name, "w") or exit ("Cannot open File!");
	$time_file = fopen("results/time/". $file_name, "w") or exit ("Cannot open File!");
	
	//loop for writing url begin
	foreach ($stack_url as $value)
		fwrite($url_file, $value."\n");
	
	fclose($url_file);
	
	//loop for writing user begin
	$i = 1;
	foreach ($stack_user as $value)
		fwrite($user_file, $value."\n");
		
	fclose($user_file);
	
	//loop for writing elapsed time begin
	foreach ($stack_time as $value)
		fwrite($time_file, $value."\n");
	
	fclose($time_file);
	
	//write the black and white list
	 * */
}
