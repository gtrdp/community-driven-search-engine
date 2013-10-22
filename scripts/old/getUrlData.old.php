<?php
function getUrlData($url)
{
    $result = false;
    $contents = getUrlContents($url);
	
    if (isset($contents) && is_string($contents))
    {
        $title = null;
        $metaTags = null;
        preg_match('/<title>([^>]*)<\/title>/si', $contents, $match );
 
        if (isset($match) && is_array($match) && count($match) > 0)
        {
            $title = strip_tags($match[1]);
        }

        preg_match_all('/<[\s]*meta[\s]*name="?' . '([^>"]*)"?[\s]*' .'[lang="]*[^>"]*["]*'.'[\s]*content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $contents, $match);
        if (isset($match) && is_array($match) && count($match) == 3)
        {
            $originals = $match[0];
            $names = $match[1];
            $values = $match[2];
 
            if (count($originals) == count($names) && count($names) == count($values))
            {
                $metaTags = array();
 
                for ($i=0, $limiti=count($names); $i < $limiti; $i++)
                {
                    $metaname=strtolower($names[$i]);
                    $metaname=str_replace("'",'',$metaname);
                    $metaname=str_replace("/",'',$metaname);
                    $metaTags[$metaname] = $values[$i];
                }
            }
        }
        if(sizeof($metaTags)==0) {
            preg_match_all('/<[\s]*meta[\s]*content="?' . '([^>"]*)"?[\s]*' .'[lang="]*[^>"]*["]*'.'[\s]*name="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $contents, $match);
 
            if (isset($match) && is_array($match) && count($match) == 3)
            {
                $originals = $match[0];
                $names = $match[2];
                $values = $match[1];
 
                if (count($originals) == count($names) && count($names) == count($values))
                {
                    $metaTags = array();
 
                    for ($i=0, $limiti=count($names); $i < $limiti; $i++)
                    {
                        $metaname=strtolower($names[$i]);
                        $metaname=str_replace("'",'',$metaname);
                        $metaname=str_replace("/",'',$metaname);
                        $metaTags[$metaname] = $values[$i];
                    }
                }
            }
        }
		
		//check for 404
		$pattern = "/(301|302|303|401|404|403|500|502|504|509)/";
		if ($title != NULL)
		{
			if(preg_match($pattern, $title, $match))
				$title = "none";
		}
		else
			$title = "none";

        $result = array (
            'title' => trim($title),
            'description' => isset($metaTags["description"]) && $metaTags["description"] != "" ? trim($metaTags["description"]) : "none",
            'keywords' => isset($metaTags["keywords"]) && $metaTags["keywords"] != "" ? trim($metaTags["keywords"]) : "none"
        );
    }
	else
	{
		echo "gak masuk  ".$url."\n";
		$result = array (
            'title' => "none",
            'description' => "none",
            'keywords' => "none"
        );
	}	
    return $result;
}

function getUrlContents($url, $maximumRedirections = 7, $currentRedirection = 0)
{
	/*
	$ctx = stream_context_create(array(
	    'http' => array(
	        'timeout' => 0
	        'header' => "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:10.0) Gecko/20100101 Firefox/10.0\n".
	        			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*;q=0.8\n".
	        			"Accept-Language: en-us,en;q=0.5",
	        'max_redirects' => 2
	        )
	    )
	);*/
	
    $result = false;
    //$contents = file_get_contents($url,0, $ctx);
    

    
    
    //Get the content of the url using cURL
    $ch = curl_init();
    // set url  
    curl_setopt($ch, CURLOPT_URL, $url);  
    // set browser specific headers  
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
            	"User-Agent: {Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:10.0) Gecko/20100101 Firefox/10.0}",  
            	"Accept-Language: {en-us,en;q=0.5}"  
         		));  
    
    // we don't want the page contents  
    //curl_setopt($ch, CURLOPT_NOBODY, 1);  
    // we need the HTTP Header returned  
    curl_setopt($ch, CURLOPT_HEADER, 1);  
  
    // return the results instead of outputting it  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 7);
    
  
    $contents = curl_exec($ch);  
   

    //was there a redirection HTTP header?  
    if (preg_match("!Location: (.*)!", $contents, $matches))
	{
		$pattern = str_replace(".", "\.", str_replace("www.", "", substr($url, 7)));
		if(!preg_match("/".$pattern, $matches[1], $match))
		{
			//curl_setopt($ch, CURLOPT_URL, $matches[1]);
			//$contents = curl_exec($ch);
			$contents = NULL;
		}
	}
	if (preg_match("!HTTP/1.1 404 Not Found!", $contents, $matches))
    	$contents = NULL;
	if (preg_match("!HTTP/1.1 403 Forbidden!", $contents, $matches))
    	$contents = NULL;
	
	
	//var_dump($contents);
	curl_close($ch);
	//echo $contents;
    if (isset($contents) && is_string($contents))
    {
        preg_match_all('/<[\s]*meta[\s]*http-equiv="?REFRESH"?' . '[\s]*content="?[0-9]*;[\s]*URL[\s]*=[\s]*([^>"]*)"?' . '[\s]*[\/]?[\s]*>/si', $contents, $match);
 
        if (isset($match) && is_array($match) && count($match) == 2 && count($match[1]) == 1)
        {
            if (!isset($maximumRedirections) || $currentRedirection < $maximumRedirections)
            {
            	if (filter_var($match[1][0], FILTER_VALIDATE_URL) !== false)
            	{
					// $url contains a valid URL
					return getUrlContents($match[1][0], $maximumRedirections, ++$currentRedirection);
				}
                
            }
 			
            $result = false;
        }
        else
        {
        	
            $result = $contents;
        }
    }
 	
    return $contents;
}
?>