<?php
/*******************************************************************************
 * Procedure:
 * 	1. Get all meta data from the database
 *  2. In the main loop remove all stopwords from the string and count the 
 * 	   tf-idf
 ******************************************************************************/
function metaWeighing()
{
		//create connection to MySQL Server
		$connection = mysql_connect("localhost","root","root");
		if (!$connection)	die("Could not connect : " . mysql_error());
		mysql_select_db("search", $connection);
		
		//get the data from the database
		$result = mysql_query("SELECT * FROM meta ORDER BY `meta`.`id` ASC", $connection);
		
        $dictionary = array();
		
		$i = 1;
		while ($meta = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$meta_string = "";
			//remove all stopwords
			foreach($meta as $key => $foo)
			{
				if ($key != "id")
					$meta_string .= omitStopWords($foo). " ";
			}
				
			
			$terms = explode(' ', $meta_string);
			foreach($terms as $term) {
						$term = trim($term);
                        if(!isset($dictionary[$term])) {
                                $dictionary[$term] = array('df' => 0, 'postings' => array());
                        }
                        if(!isset($dictionary[$term]['postings'][$meta["id"]])) {
                                $dictionary[$term]['df']++;
                                $dictionary[$term]['postings'][$meta["id"]] = 0;
                        }

                        $dictionary[$term]['postings'][$meta["id"]]++;
            }
			
			//if ($i++ == 50)
				//break;
		}
		
		unset($dictionary[""]);
		//Empty the table words first
		mysql_query("TRUNCATE words", $connection);
		//Write the data into database and calculate tfidf
		$result = mysql_query("SELECT COUNT(*) AS total FROM url", $connection);
		$count = mysql_fetch_array($result);
		$N = $count["total"];
		foreach($dictionary as $word => $other)
		{
			$url = "";
			$tf = "";
			$tfidf = 0;
			
			foreach ($other["postings"] as $key => $value)
			{
				$tfidf += (1+log10($value))*log10($N/$other["df"]);
				$url .= $key.",";
				$tf .= $value.",";
			}
			$tfidf = $tfidf / count($other["postings"]);
			
			$query = "INSERT INTO words VALUES('', '".$word."', ".$other["df"].", '".$url."', '".$tf."',".$tfidf.")";
			mysql_query($query, $connection);
		}
		
		mysql_close($connection);
        //return $dictionary;
}

function omitStopWords($string)
{
	//English
	$stop_words = file("stopwords/stopwords-english");

	foreach ($stop_words as $word) {
		$word = rtrim($word);
		$string = preg_replace("/\b$word\b/i", " ", $string);
	}
	//Bahasa Indonesia
	$stop_words = file("stopwords/stopwords-indonesian");

	foreach ($stop_words as $word) {
		$word = rtrim($word);
		$string = preg_replace("/\b$word\b/i", " ", $string);
	}
	
	
	//remove special character
	$string = preg_replace("/[^a-zA-Z\s]/s", " ", $string);
	//remove double white spaces
	$string = preg_replace("/(\s)+/", " ", $string);
	
	//return small case string
	return strtolower($string);
}