<?
	//set errors and time limit this script may take a long time to run the first time
	ini_set('display_errors', 1);
	error_reporting(-1);
	set_time_limit(0);
	
	include("../inc/functions.php");
	
	//connect to the db	
	$link = db_conect();
	mysql_select_db("appdata", $link)
	or die(mysql_error());
	
	//Print out the time before start for debuging
	print date('h:i:s');
	echo "<br>";
	
	//Get all corps
	$urlcorps = "http://www.eve-icsc.com/xml/corporationlist/corporations.xml";	
	$datacorps = file_get_contents($urlcorps);
	$listcorps = new SimpleXMLElement($datacorps);
	$corpcount = 0;
	foreach ($listcorps->corporations->corporation as $corp):
		$corpcount++;
		$corpid =  dontHateMe($corp['corporationID']);
		$corpname =  dontHateMe($corp['corporationName']);
		$corpticker =  dontHateMe($corp['ticker']);
		
		//check if the corp exists if not add it
		$queryCorp = mysql_query("select id from corps where id = ".$corpid);	
		if (mysql_num_rows ($queryCorp) == 0){
			mysql_query("INSERT INTO corps (id, name, ticker) VALUES ('".$corpid."','".$corpname."','".$corpticker."')"); 
			echo $corpid;
			echo $corpname;
			echo $corpticker;
		}
		
	endforeach;
	//echo $corpcount;
	
	//fetch the alliances
	$urlAliance = "https://api.eveonline.com/eve/AllianceList.xml.aspx";	
	$dataAliance = file_get_contents($urlAliance);
	$listAliance = new SimpleXMLElement($dataAliance);
	$i = 0;
	
	foreach ($listAliance->result->rowset->row as $row):
	// Each alliance in the list
	$name = dontHateMe($row['name']); 
	$ticker =  dontHateMe($row['shortName']);
	$id =  dontHateMe($row['allianceID']);
	
	//check if aliance exists in db
    $queryAliance = mysql_query("select id from aliances where id = ".$id);
	
	//if it dosent exist add it
	if (mysql_num_rows ($queryAliance) == 0){
		mysql_query("INSERT INTO aliances (id, name, ticker) VALUES ('".$id."', '".$name."','".$ticker."')"); 
	}
	
		foreach ($row->rowset->row as $corp):			
					
			$corpid =  dontHateMe($corp['corporationID']);
			//check if the corp exists and is signed as part of that alliance			
			$queryCorp = mysql_query("select id from corps where id = ".$corpid." AND aliance_id = ".$id);	
			if (mysql_num_rows ($queryCorp) == 0){
				
				//check if the corp exists at all if it does change the alaince if not add it
				$queryCorp = mysql_query("select id from corps where id = ".$corpid);	
				if (mysql_num_rows ($queryCorp) == 0){					
					//If corp dosent exist
					$urlCorp = "https://api.eveonline.com/corp/CorporationSheet.xml.aspx?corporationID=".$corpid;	
					
					$ch = curl_init($urlCorp);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$dataCorp = '';
					if( ($dataCorp = curl_exec($ch) ) === false)
					{
					    echo 'Curl error: ' . curl_error($ch);
					}
					curl_close($ch);
					
					$listCorp = new SimpleXMLElement($dataCorp);
					$corpname =  dontHateMe($listCorp->result->corporationName);
					$corpticker =  dontHateMe($listCorp->result->ticker);						
					mysql_query("INSERT INTO corps (id, name, ticker, aliance_id) VALUES ('".$corpid."', '".$corpname."','".$corpticker."','".$id."')"); 
				}
				else{
					//Corp exists update the aliance id
					mysql_query("UPDATE corps SET aliance_id = '".$id."' WHERE id = ".$corpid); 
				}	
				
			}
			
			

		endforeach;
	//break after doing 1 row remove after done
	$i++;

	
	endforeach;	
	
	//print time after done for debugging
	print date('h:i:s');
?>