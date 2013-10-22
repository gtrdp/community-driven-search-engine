<?php
/*
$data = array( 
	array(0.05, 0.95),
	array(0.1, 0.9),
	array(0.2, 0.8),
	array(0.25, 0.75),
	array(0.45, 0.55),
	array(0.5, 0.5),
	array(0.55, 0.45), 
	array(0.85, 0.15),
	array(0.9, 0.1),
	array(0.95, 0.05)
);
 *
 */ 
 
/*
$data = array( 
	array(2, 1),
	array(2, 3),
	array(4, 1),
	array(4, 3),
	array(8, 7),
	array(8, 9),
	array(10, 7), 
	array(10, 9)
);

	/*
	// Lets normalise the input data
	foreach($data as $key => $d) {
		$data[$key] = normaliseValue($d, sqrt($d[0]*$d[0] + $d[1] * $d[1]));
	}*/

	
/*
$result = kMeans($data, 2);

echo "centroidX\n";
foreach($result["centroids"] as $value)
{
	echo $value[0]."\n";
}
echo "\ncentroidY\n";
foreach($result["centroids"] as $value)
{
	echo $value[1]."\n";
}

echo "\n\ndataX\n";
foreach($data as $value)
{
	echo $value[0]."\n";
}
echo "\ndatadY\n";
foreach($data as $value)
{
	echo $value[1]."\n";
}

print_r($result);
*/

function initialiseCentroids(array $data, $k) {
	$dimensions = count($data[0]);
	$centroids = array();
	$dimmax = array();
	$dimmin = array();
	

	
	foreach($data as $document) {
		foreach($document as $dim => $val) {
			if(!isset($dimmax[$dim]) || $val > $dimmax[$dim]) {
				$dimmax[$dim] = $val;
			}
			if(!isset($dimmin[$dim]) || $val < $dimmin[$dim]) {
				$dimmin[$dim] = $val;
			}
		}
	}
	for($i = 0; $i < $k; $i++) {
		$centroids[$i] = initialiseCentroid($dimensions, $dimmax, $dimmin);
	}
	return $centroids;
}

function initialiseCentroid($dimensions, $dimmax, $dimmin) {
	$total = 0;
	$centroid = array();
	for($j = 0; $j < $dimensions; $j++) {
		$centroid[$j] = (rand($dimmin[$j] * 1000, $dimmax[$j] * 1000));
		$total += $centroid[$j] * $centroid[$j];
	}
	$centroid = normaliseValue($centroid, sqrt($total));
	return $centroid;
}

function kMeans($data, $k) {
	$centroids = initialiseCentroids($data, $k);
	$mapping = array();

	while(true) {
		$new_mapping = assignCentroids($data, $centroids);

		//sleep(3);
		$changed = false;
		foreach($new_mapping as $documentID => $centroidID) {
			if(!isset($mapping[$documentID]) || $centroidID != $mapping[$documentID]) {
				$mapping = $new_mapping;
				$changed = true;
				break;
			}
		}
		if(!$changed){
			return formatResults($mapping, $data, $centroids); 
		}
		$centroids  = updateCentroids($mapping, $data, $k); 
	}
}

function formatResults($mapping, $data, $centroids) {
	$result  = array();
	$result['centroids'] = $centroids;
	foreach($mapping as $documentID => $centroidID) {
		//$result[$centroidID][] = implode(',', $data[$documentID]);
		$result[$centroidID][$documentID] = $data[$documentID];
	}
	return $result;
}

function assignCentroids($data, $centroids) {
	$mapping = array();

	foreach($data as $documentID => $document) {
		$minDist = 999999999;
		$minCentroid = null;
		foreach($centroids as $centroidID => $centroid) {
			$dist = 0;
			foreach($centroid as $dim => $value) {
				$dist += abs($value - $document[$dim]);
			}
			if($dist < $minDist) {
				$minDist = $dist;
				$minCentroid = $centroidID;
			}
		}
		$mapping[$documentID] = $minCentroid;
	}

	return $mapping;
}

function updateCentroids($mapping, $data, $k) {
	$centroids = array();
	$counts = array_count_values($mapping);

	foreach($mapping as $documentID => $centroidID) {
		foreach($data[$documentID] as $dim => $value) {
			$centroids[$centroidID][$dim] += ($value/$counts[$centroidID]); 
		}
	}

	if(count($centroids) < $k) {
		$centroids = array_merge($centroids, initialiseCentroids($data, $k - count($centroids)));
	}

	return $centroids;
}

function normaliseValue(array $vector, $total) {
	foreach($vector as &$value) {
		$value = $value/$total;
	}
	return $vector;
}

