<?
/************************************************************************/
/* Leonardo: Gliding XC Server					                        */
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2004-5 by Andreadakis Manolis                          */
/* http://sourceforge.net/projects/leonardoserver                       */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/

require_once dirname(__FILE__).'/FN_functions.php';
require_once dirname(__FILE__).'/CL_logReplicator.php';

class Server {
	var $ID;

	var $isLeo ;
	var $installation_type;
	var $leonardo_version;
	var $url;
	var $url_base;
	var $url_op;
	var $admin_email;
	var $site_pass;

	var $lastPullUpdateID;
	var $serverPass;
	var $clientPass;

	var $is_active;
	var $gives_waypoints;
	var $waypoint_countries;

	var $valuesArray;
	var $gotValues;
	var $DEBUG;

	function Server($id="") {
		if ($id!="") {
			$this->ID=$id;
		}

	    $this->valuesArray=array("ID","isLeo","installation_type","leonardo_version","url", "url_base",
			"sync_format", "sync_type", "use_zip",
			"url_op","admin_email","site_pass","lastPullUpdateID","serverPass","clientPass","is_active","gives_waypoints","waypoint_countries"
		);
		$this->gotValues=0;
		$this->DEBUG=0;
	}

	function uploadFile($filename,$remoteFilename) {
			require_once dirname(__FILE__)."/lib/xml_rpc/IXR_Library.inc.php";
	
			$serverURL="http://".$this->url_op;
			$client = new IXR_Client($serverURL);
			if ($this->DEBUG) $client->debug=true;
		
			if ( ($fileStr=@file_get_contents($filename)) === FALSE) {
				echo 'uploadFile: Error, cannot get contents of '.$filename;
				return 0;
			}

			$fileStr_base64=base64_encode($fileStr);
			if ( ! $client->query('server.uploadFileInline',$this->site_pass, $fileStr_base64,$remoteFilename) ) {
				echo 'uploadFile: Error '.$client->getErrorCode()." -> ".$client->getErrorMessage();
				return 0; 
			} else {
				return 1;
			}
	}

	function sendOPfiles() {
		$res=0;
		foreach (glob("OP_*.php") as $filename) {
			$res+=$this->uploadFile(dirname(__FILE__)."/".$filename,$filename);
		}	
		//return number of files uploaded
		return $res;
	}

	function version() {
		if ($this->isLeo) {
			list($version,$sub_version,$revision)=explode(".",$this->leonardo_version);
			return array($version,$sub_version,$revision);
		} else return array(0,0,0);
    }
	
	function getInfo() {
		require_once dirname(__FILE__)."/lib/xml_rpc/IXR_Library.inc.php";

		$serverURL="http://".$this->url_op;
		$client = new IXR_Client($serverURL);
		if ($this->DEBUG) $client->debug=true;
	
		if ( ! $client->query('server.info',$this->site_pass) ) {
			echo 'server: info '.$client->getErrorCode()." -> ".$client->getErrorMessage();
			return 0;  // $client->getErrorCode();
		} else {
			$info= $client->getResponse();
			return $info;
		}

	}
	function getTakeoffs($tm) {
			require_once dirname(__FILE__)."/lib/xml_rpc/IXR_Library.inc.php";
	
			$serverURL="http://".$this->url_op;
			$client = new IXR_Client($serverURL);
			if ($this->DEBUG) $client->debug=true;

			$onlyTakeoffs=1;
			if ( ! $client->query('takeoffs.getTakeoffs',$this->site_pass, $tm ,$onlyTakeoffs) ) {
				echo 'getTakeoffs: Error '.$client->getErrorCode()." -> ".$client->getErrorMessage();
				return array(0,array());  // $client->getErrorCode();
			} else {
				list($takeoffsListNum,$takeoffsList)= $client->getResponse();
				return $takeoffsList;
			}
	}

	function findTakeoff($lat,$lon) {
		require_once dirname(__FILE__)."/CL_gpsPoint.php";
		list($version,$sub_version,$revision)=$this->version();
		if ($version>=2) { // new rpc method
			require_once dirname(__FILE__)."/lib/xml_rpc/IXR_Library.inc.php";
	
			$serverURL="http://".$this->url_op;
			$client = new IXR_Client($serverURL);
			if ($this->DEBUG) $client->debug=true;
		
			if ( ! $client->query('takeoffs.findTakeoff',$this->site_pass, $lat, $lon ) ) {
				echo 'findTakeoff: Error '.$client->getErrorCode()." -> ".$client->getErrorMessage();
				return array(0,-1);  // $client->getErrorCode();
			} else {
				list( $nearestWaypoint,$minTakeoffDistance)= $client->getResponse();
				$nearestWaypoint=(object) $nearestWaypoint;
				return array($nearestWaypoint,$minTakeoffDistance);
			}


		} else if ($version==1 && $sub_version >=4) { // use EXT_takeoff.php method
			require_once dirname(__FILE__)."/FN_functions.php";
			$serverURL="http://".$this->url_base."/EXT_takeoff.php?op=find_wpt&lat=$lat&lon=$lon";
			//echo $serverURL;
			$contents=fetchURL($serverURL);
			if (!$contents) {
				echo "SERVER at: ".$this->url_base." is NOT ACTIVE<br>"; 
				return array(0,-1);
			}
			//echo $contents;
			require_once dirname(__FILE__).'/lib/miniXML/minixml.inc.php';
			$xmlDoc = new MiniXMLDoc();
			$xmlDoc->fromString($contents);
			$xmlArray=$xmlDoc->toArray();

			$wpt=new waypoint();
			
			$wpt->waypointID=$xmlArray[search][waypoint][name];
			$wpt->name		=$xmlArray[search][waypoint][name];
			$wpt->intName	=$xmlArray[search][waypoint][intName];
			$wpt->location	=$xmlArray[search][waypoint][location];
			$wpt->intLocation=$xmlArray[search][waypoint][intLocation];
			$wpt->type		=$xmlArray[search][waypoint][type];
			$wpt->countryCode=$xmlArray[search][waypoint][countryCode];
			$wpt->lat		=$xmlArray[search][waypoint][lat];
			$wpt->lon		=$xmlArray[search][waypoint][lon];
			$wpt->link		=$xmlArray[search][waypoint][link];
			$wpt->description=$xmlArray[search][waypoint][description];
			$wpt->modifyDate=$xmlArray[search][waypoint][modifyDate];
			
			$distance=$xmlArray[search][distance];
			
			return array($wpt,$distance);
		} else if ( $version==0 && !$this->isLeo ) { // we are dealing with 'alien' servers
			require_once dirname(__FILE__)."/FN_functions.php";
			//the installation_type in this case point to the ID of the alien server			
			$takeoffsList=getExtrernalServerTakeoffs($this->installation_type,$lat,$lon,1000,1);			
			$wpt=new waypoint();
			$distance=$takeoffsList[0]['distance'];

			$wpt->intName=$takeoffsList[0]['name'];
			$wpt->name=$takeoffsList[0]['name'];
			$wpt->location=$takeoffsList[0]['area'];
			$wpt->intLocation=$takeoffsList[0]['area'];
			$wpt->countryCode=$takeoffsList[0]['countryCode'];
			$wpt->link=$takeoffsList[0]['url'];
			$wpt->lat=$takeoffsList[0]['lat'];
			$wpt->lon=$takeoffsList[0]['lon'];
			
			return array($wpt,$distance);
		}
		
	}
	
	function registerServerToMaster() {
		require_once dirname(__FILE__)."/lib/xml_rpc/IXR_Library.inc.php";
		$masterServer=new Server($CONF_master_server_id);
		$masterServer->getFromDB();
		
		$masterServerURL="http://".$masterServer->url_op;	
		$thisServerURL="http://".$this->url_op;
		$client = new IXR_Client($masterServerURL);
		if ($this->DEBUG) $client->debug=true;
	
		if ( ! $client->query('server.registerSlave',$this->site_pass, $lat, $lon ) ) {
			echo 'registerSlave: Error '.$client->getErrorCode()." -> ".$client->getErrorMessage();
			return 0;  // $client->getErrorCode();
		} else {
			$newServerID= $client->getResponse();
			return $newServerID;
		}
	}
	
	function getServers() {
		global $db,$serversTable;
		$res= $db->sql_query("SELECT * FROM $serversTable order BY ID");
  		if($res <= 0){   
			 echo "Error getting all servers from DB<BR>";
		     return;
	    }

		$i=0;
	    while ($row= $db->sql_fetchrow($res) ) {
			$servers[$i]=new Server($row[$id]);
			foreach ($servers[$i]->valuesArray as $valStr) {
				$servers[$i]->$valStr=$row["$valStr"];
			}
			$servers[$i]->gotValues=1;
			$i++;
		}
		return $servers;
	}

	function getFromDB($id=0) {
		if (!$id) $id=$this->ID;

		global $db,$serversTable;
		$res= $db->sql_query("SELECT * FROM $serversTable WHERE ID=".$id );
  		if($res <= 0){   
			 echo "Error getting server from DB<BR>";
		     return;
	    }

	    $row= $db->sql_fetchrow($res);
		foreach ($this->valuesArray as $valStr) {
			$this->$valStr=$row["$valStr"];
		}
		$this->gotValues=1;
    }

	function getNextFreeID() {
		global $db,$serversTable;
		$res= $db->sql_query("SELECT max(ID) as maxid FROM $serversTable ");
  		if($res <= 0){   
			 echo "Error getting info for last server ID from DB<BR>";
		     return -1;
	    }
	    $row= $db->sql_fetchrow($res);
		$maxid=$row['maxid']+1;
		if ($maxid<100) $maxid=100;
		return $maxid;
	}

	function putToDB($update=0) {
		global $db,$serversTable;

		if ($update) {
			$query="REPLACE INTO ";		
			$fl_id_1="ID,";
			$fl_id_2=$this->ID.", ";
		}else {
			$query="INSERT INTO ";		
			$fl_id_1="";
			$fl_id_2="";
		}


		$query.=" $serversTable  ( ";
		foreach ($this->valuesArray as $valStr) {
				$query.= $valStr.",";		
		}
		$query=substr($query,0,-1);

		$query.= " ) VALUES ( ";
		foreach ($this->valuesArray as $valStr) {
			$query.= "'".prep_for_DB($this->$valStr)."',";
		}
		$query=substr($query,0,-1);
		$query.= " ) ";
		// echo $query;
	    $res= $db->sql_query($query);
	    if($res <= 0){
		  echo "Error putting server to DB<BR>";
		  return 0;
	    }		
		$this->gotValues=1;			
		return 1;
    }

	// serverPass -> the password that the client server with the speficic ID must provide
	// clientPass -> the password WE (the local server) uses when it wants to connect to server with ID

	// they are the same only in the entry of the local server

	function checkServerPass($serverID,$pass) {
		global $db,$serversTable;
		$res= $db->sql_query("SELECT * FROM $serversTable WHERE ID=$serverID AND serverPass='$pass' ");
  		if($res <= 0){   
			 echo "Error getting server from DB<BR>";
		     return;
	    }

	    if ( $row= $db->sql_fetchrow($res) ) {
			return 1;
		} else {
			return 0;
		}
    }

	function deleteAllSyncedFlights() {
		global $db,$flightsTable;
		// if (!$this->gotValues) $this->getFromDB();
		
		$res= $db->sql_query("SELECT * FROM $flightsTable WHERE  serverID=$this->ID ");
  		if($res <= 0){   
			 echo "Error getting server's flights from local DB<BR>";
		     return;
	    }

	    while( $row= $db->sql_fetchrow($res) ) {
			  $flight=new flight();
			  $flight->getFlightFromDB($row['ID']);		
			  $flight->deleteFlight();
		} 

		//	reset the counter
		$this->lastPullUpdateID=0;
		$this->putToDB(1);

	}

	function sync($chunkSize=5) { // we pull data from this server
		global $CONF_server_id,$CONF_tmp_path;
		
		if (!$this->gotValues) $this->getFromDB();

		$startID=$this->lastPullUpdateID+1;
		$urlToPull='http://'.$this->url_base.'/sync.php?type=1';
		$urlToPull.="&c=$chunkSize&startID=$startID&format=".$this->sync_format;
		$urlToPull.="&clientID=$CONF_server_id&clientPass=".$this->clientPass.
					"&sync_type=".$this->sync_type."&use_zip=".$this->use_zip;


		

		echo "Getting <strong>$this->sync_format</strong> sync-log from $urlToPull ... ";
		flush2Browser();

		$rssStr=fetchURL($urlToPull,60);
		if (!$rssStr) {
			echo "<div class='error'>Cannot get data from server</div><BR>";
			return 0;
		}
		echo " <div class='ok'>DONE</div><br>";
		flush2Browser();

		if ($this->use_zip) { // we have a zip file in $rssStr, unzip it
			echo "Unziping sync-log ... ";
			$tmpZIPfolder=$CONF_tmp_path.'/'.$this->ID."_".time();
			mkdir($tmpZIPfolder);
			
			$zipFile="$tmpZIPfolder/sync_log.zip";
			writeFile($zipFile,$rssStr);
			
			require_once dirname(__FILE__)."/lib/pclzip/pclzip.lib.php";
			
			$archive = new PclZip($zipFile);
			$list 	 = $archive->extract(PCLZIP_OPT_PATH, $tmpZIPfolder,
										PCLZIP_OPT_REMOVE_ALL_PATH,
										PCLZIP_OPT_BY_PREG, "/(\.igc)|(\.olc)|(\.txt)$/i");
			echo " <div class='ok'>DONE</div><br>";
			echo "<br><b>List of uploaded igc/olc/txt files</b><BR>";
			$f_num=1;
			foreach($list as $fileInZip) {
				echo "$f_num) ".$fileInZip['stored_filename']. ' ('.floor($fileInZip['size']/1024).'Kb)<br>';
				$f_num++;
			}
			flush2Browser();

			if (is_file($tmpZIPfolder.'/sync.txt') ) {
				$rssStr=implode('',file($tmpZIPfolder.'/sync.txt') );
			} else {
				echo "Could not find sync.txt. <div class='error'>Aborting</div>";
				delDir($tmpZIPfolder);			
				return 0;
			}
			
			//delDir($tmpZIPfolder);			
			//exit;		
		}
		
	
		// 
		// getIGC
		// zip


		// for debugging json
		//writeFile(dirname(__FILE__).'/sync.txt',$rssStr);
		//return;

		// echo "<PRE>$rssStr</pre>";
		if ($this->sync_format=='XML')	{	
			require_once dirname(__FILE__).'/lib/miniXML/minixml.inc.php';
			$xmlDoc = new MiniXMLDoc();
			$xmlDoc->fromString($rssStr);
			$xmlArray=$xmlDoc->toArray();
	
			//echo "<PRE>";
			//print_r($xmlArray);
			//echo "</PRE>";
	
			if ($xmlArray['log']['item']['_num']) {
				foreach ($xmlArray['log']['item'] as $i=>$logItem) {
					if (!is_numeric($i) ) continue;				
					if ( ! $this->processSyncEntry($this->ID,$logItem) ) { // if we got an error break the loop, the admin must solve the error
						break;
					}	
				}
			} else {
				$this->processSyncEntry($this->ID,$xmlArray['log']['item']);
			}
		} else if ($this->sync_format=='JSON') {
			echo "Decoding log from JSON format ...";
			flush2Browser();
			require_once dirname(__FILE__).'/lib/json/CL_json.php';
			$arr=json::decode($rssStr);
			echo " <div class='ok'>DONE</div><br>";
			flush2Browser();
			//print_r($arr);
			//exit;
			if ( count($arr['log']) ) {
				foreach ($arr['log'] as $i=>$logItem) {
					if (!is_numeric($i) ) continue;				
					if (  $this->processSyncEntry($this->ID,$logItem['item']) <= -128 ) { // if we got an error break the loop, the admin must solve the error
						echo "<div class'error'>Got fatal Error, will exit</div>";
						break;
					}	
				}
			} else {
				echo "The sync-log returned error. Error: <br />";
				print_r($arr);
				echo "<hr><pre>$rssStr</pre>";
			}

		}
		
		// clean up
		delDir($tmpZIPfolder);			
	}

	function processSyncEntry($ID,$logItem) {
		echo "Processing entry ".($logItem['transactionID']+0)." ...  ";
		flush2Browser();

		//	print_r($logItem);
		// return 1;

		list($result,$message)=logReplicator::processEntry($ID,$logItem,$this->sync_type);
		if ($result < 0 ) {
			echo "<div class='error'>ERROR </div>: $message <BR>";
			return 0;
		} else {
			// update the 
			$this->lastPullUpdateID=$logItem['transactionID']+0;
			$this->putToDB(1);
			echo "<div class='ok'>OK</div>: $message<BR>";
			return 1;
		}
	}
}

?>