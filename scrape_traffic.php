<?php
##############################################################################
#
# Austin-Travis County Traffic Report Page Scraper
# by Brandon Roberts GPLv3+, 2011
# brandon at austincut dot com
#
##############################################################################

# configuration file
include_once("config.inc.php");

function load_file_from_url($url) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_REFERER, $refhost);
  $str = curl_exec($curl);
  if(str === false){
      print "Error loading " . $url;
  }
  curl_close($curl);
  return $str;
}

function load_xml_from_url($url) {
  return simplexml_load_string(load_file_from_url($url));
}

$con = mysql_connect( $serv, $user, $pass);
if (!$con) {  die('Could not connect: ' . mysql_error());  }

mysql_select_db( $db, $con);

$t_file = load_file_from_url('http://www.ci.austin.tx.us/qact/qact_rss.cfm');
$doc = new DOMDocument();
$doc->loadXML($t_file);
$arrFeeds = array();

# loop through traffic events and grab values
foreach ($doc->getElementsByTagName('item') as $node) {
  # title - "000 Main St - Crash Service", etc
  $title = $node->getElementsByTagName('title')->item(0)->nodeValue;
  # date
  $pubdate = $node->getElementsByTagName('pubdate')->item(0)->nodeValue;
  # location (from title)
  $location = substr( $title, 0, 0 - (strlen($title) - strpos($title, "  ")));
  # Type of event
  $dtmp = $node->getElementsByTagName('description')->item(0)->nodeValue;
  $description = substr( $dtmp, 0, 0 - (strlen($dtmp) - strpos($dtmp, "  ")));
  # Category - for information about the codes, see crash_codes.txt
  # I got this information directly from the APD
  $cattmp = substr( $title, strpos($title, "  - ")+4);
  $category = substr( $cattmp, 0, 0 - (strlen($cattmp) - strpos($cattmp, "  ")));
  # add our items to array (for geocoding)
  $itemRSS = array (
  	'pubdate'	=> $pubdate,
  	'location'	=> $location,
  	'description'	=> $description,
  	'category'	=>  $category,
    );
  array_push($arrFeeds, $itemRSS);

  $result = mysql_query("SELECT * FROM trafficker WHERE
				pubdate='" . $pubdate  . "' AND
				location='" . $location . "'");
  if( !mysql_num_rows( $result )){
    $sql="INSERT INTO trafficker (pubdate, location, description, category)
		VALUES
		('$pubdate','$location','$description','$category')";
    if (!mysql_query($sql,$con)) {
      die('Error: ' . mysql_error());
    }
  }
  ## GEOCODE ##
  # Select all the rows in the markers table
  $query = "SELECT * FROM trafficker WHERE (pubdate='".$pubdate."')
                                       AND (location='".$location."')";
  $result = mysql_query($query);
  if (!$result) {
    die("Invalid query: " . mysql_error());
  }

  # Initialize delay in geocode speed
  $delay = 1000;
  $base_url = "http://" . MAPS_HOST . "/maps/geo?output=xml" . "&sensor=false" . "&key=" . KEY;

  $search  = array(' NB ', ' SB ', ' WB ', ' EB ', ' svrd ', '-BLK ', 'Upper Deck', ' Ramp ', ' FR ', ' hwy ');
  $city = " , Austin, TX";

  # Iterate through the rows, geocoding each address
  while ($row = @mysql_fetch_assoc($result)) {
    $geocode_pending = true;
    while ($geocode_pending) {
      # This code is really ghetto ... I know. But in order for google maps
      # to understand a lot of the traffic page's version of street names
      # and misc formatting oddities, we need to convert some. These
      # str_ireplace chains aren't perfect and probably create errors of their
      # own, but for the most part, they do an OK, but hack-y, job.
      $address = $row["location"];
      $address = str_ireplace( $search, ' ', $address);
      $address = str_ireplace( "/", " & ", $address);
      $address = str_ireplace( "Ih 35", "Interstate 35 Frontage Rd ", $address);
      $address = str_ireplace( " FM 620 Rd", " Ranch Road 620 ", $address);
      $address = str_ireplace( " SH 71", " Texas 71 ", $address);
      $address = str_ireplace( " sh 95", " Texas 95 ", $address);
      $address = str_ireplace( " sh 130", " Texas 130  ", $address);
      $address = str_ireplace( " US 183 ", " U.S. Route 183 ", $address);
      $address = str_ireplace( " US 290 ", " U.S. 290 ", $address);
      $address = str_ireplace( "Fm 2222 Rd", "Ranch Road 2222", $address);
      $address = str_ireplace( " mlk ", " Martin Luther King Jr Blvd ", $address);
      $address = str_ireplace( " Cap Tx ", " Capital of Texas Highway ", $address);
      $address = str_ireplace( "Northland", " Northland Drive ", $address);
      $address = str_ireplace( "35 TH", "35TH", $address);
      $address = str_ireplace( "S Interstate 35 Frontage Rd ", "Interstate 35 Frontage Rd ", $address);
      $address = str_ireplace( "N Interstate 35 Frontage Rd ", "Interstate 35 Frontage Rd ", $address);
      $address = str_ireplace( " & ", " and ", $address);
      if( stristr( $address, "mopac")){
        /*
        # this was an attempt at fixing the handling of intersections w/
        # mopac, which will be put in the format 35th/Mopac, etc, but will
        # get geododed the same as plain old "mopac" ... adding a block
        # number simulates this OK, but this particular code caused other
        # problems
        if( stripos( $address, "35th")) {
          $address = "3500 Mopac Expy";
        } else if( strpos( $address, "45th")) {
          $address = "4500 Mopac Expy";
        }
        */
      } else if( stristr( $address, 'Research') && stristr( $address, " to ") ){
        $address = str_ireplace( " to ", " & ", $address);
      }
      if( stristr( $address, ' To ')){
        $address = substr( $address, 0, stripos( $address, ' To '));
      }

      if( stristr( $address, "Anderson Mill") && stristr( $address, "183") ){
        $city = ", Anderson Mill, TX";
      }
      $address = $address . $city;
      $address = str_ireplace( $search, ' ', $address);
      print "Original Address: " . $address . "\n";

      $id = $row["id"];
      $request_url = $base_url . "&q=" . urlencode($address);
      print "Request URL: " . $request_url . "\n";
      $xml = load_xml_from_url($request_url) or die("url not loading");

      $status = $xml->Response->Status->code;
      print "Status code:" . $status . "\n";
      if (strcmp($status, "200") == 0) {
        // Successful geocode
        $geocode_pending = false;
        $coordinates = $xml->Response->Placemark->Point->coordinates;
        $coordinatesSplit = split(",", $coordinates);
        // Format: Longitude, Latitude, Altitude
        $lat = $coordinatesSplit[1];
        $lng = $coordinatesSplit[0];
        print $lat . " " . $lng . "\n";
        $query = sprintf("UPDATE trafficker " .
               " SET lat = '%s', lng = '%s', filtered = '%s' " .
               " WHERE id = '%s' LIMIT 1;",
               mysql_real_escape_string($lat),
               mysql_real_escape_string($lng),
               mysql_real_escape_string($address),
               mysql_real_escape_string($id));
        $update_result = mysql_query($query);
        if (!$update_result) {
          die("Invalid query: " . mysql_error());
        }
      } else if (strcmp($status, "620") == 0) {
        // sent geocodes too fast
        $delay += 100000;
      } else if (strcmp($status, "602") == 0) {
        // Try w/ county
        $city = ",Travis, TX";
      } else {
        // failure to geocode
        $geocode_pending = false;
        print "Address:  " . $address . " failed to geocode." . "Received status " . $status . "\n";
      }
      usleep($delay);
    }
  }
}

mysql_close($con);

?>
