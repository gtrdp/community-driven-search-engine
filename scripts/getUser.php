<?php
function getUser($file_name)
{
	$stack_url = array();
	$match = array();
	
	//Open file
	if (isset($file_name))
		$file = fopen("logs/". $file_name, "r") or exit("Unable to open file!");
	else
	{
		echo "Please check file directory.";
		return FALSE;
	}

	//loop inside the file to find the exact string that match with
	//the pattern (url)
	while(!feof($file))
	{
		$foo = fgets($file);
		//$pattern_url = "/(http|https|ftp):\/\/([A-Za-z0-9.-]+)\//";
		$pattern_url = "/\s([A-Za-z0-9.-]+)@/";
		if(preg_match($pattern_url, $foo, $match))
		{
			//insert into array
			array_push($stack_url, $match[0]);
		}
	}
	fclose($file);
	
	//sort the url list
	sort($stack_url);
	
	//write to file
	$url_file = fopen("results/url/". $file_name, "w") or exit ("Cannot open File!");
	
	//loop begin
	foreach ($stack_url as $foo)
		fwrite($url_file, $foo . "\n");
	fclose($url_file);
}
