<?php

class TEAC
{
	function __construct()
	{
		$this -> version = "1.3";
		$this -> server = 'https://api.eveonline.com';//$this -> modSettings['tea_api_server'];//
		$this -> atags = array();
	}

	function get_xml($type, $post = NULL)
	{
		$url = '';
		$xml = '';
		if($type == 'standings')
			$url = "/corp/ContactList.xml.aspx";
		elseif($type == 'alliances')
			$url = "/eve/AllianceList.xml.aspx";
		elseif($type == 'corp')
			$url = "/corp/CorporationSheet.xml.aspx";
		elseif($type == 'charsheet')
			$url = "/char/CharacterSheet.xml.aspx";
		elseif($type == 'facwar')
			$url = "/char/FacWarStats.xml.aspx";
		elseif($type == 'find')
			$url = "/eve/CharacterID.xml.aspx";
		elseif($type == 'name')
			$url = "/eve/CharacterName.xml.aspx";
		elseif($type == 'keyinfo')
			$url = "/account/APIKeyInfo.xml.aspx";
		elseif($type == 'calllist')
			$url = "/api/callList.xml.aspx";
		else
			$url = "/account/Characters.xml.aspx";

		if(!empty($post))
		{ 
			foreach($post as $i => $v)
			{
				$post[$i] = $i.'='.$v;
			}
			$post = implode('&', $post);
		}
		
		$cache = FALSE;
		if($type != 'calllist' && $type != 'standings' && $type != 'alliances' && method_exists($this, 'get_cache'))
		{
			$cache = $this -> get_cache($url, $post);
		}
		if($cache)
		{
			return $cache;
		}
		$xml = $this -> get_site($this -> server.$url, $post);

		if($type != 'calllist' && $type != 'standings' && $type != 'alliances' && method_exists($this, 'set_cache'))
		{
			$cache = $this -> set_cache($url, $post, $xml);
		}
		
		return $xml;
	}

	function get_site($url, $post=FALSE)
	{
		if(!function_exists('curl_init'))
		{
			Return "NO CURL";
		}
		
		$ch = curl_init();

		if(!empty($post))
		{
			curl_setopt($ch, CURLOPT_POST      ,1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
		}

		curl_setopt($ch, CURLOPT_URL, $url);

		if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
	
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$data = curl_exec($ch);
		curl_close($ch);
	
		Return $data;
	}

	function corp_info($corp)
	{
		$info = array();
		$post = array();;
		$post = array('corporationID' => $corp);
		$xml2 = '';
		$xml2 = $this -> get_xml('corp', $post);
		
		if( (stristr($xml2, "runtime")) || (stristr($xml2, "The service is unavailable")) || (!$xml2) || (stristr($xml2, "404 Not Found")) )
		{
				echo "API System Screwed - Can't fetch Corp Info : \n";
				var_dump ($xml2);
				Return 9999;
		}
		
		if ( stristr($xml2, "403 - Forbidden") )
		{
			echo "Corp Info API forbidden\n";
			Return 403;
		}
		
		if(strstr($xml2, '<description>'))
		{
			$xml2 = explode("<description>", $xml2, 2);
			$xml2[1] = explode("</description>", $xml2[1], 2);
			$xml2 = $xml2[0].'<description>removed</description>'.$xml2[1][1];
		}
		
		libxml_use_internal_errors(true);



                try {
                        $xml = new SimpleXMLElement($xml2);

                }
                catch(Exception $e)
                {
                        echo "corp_info api returning invalid xml\n";
                        return 9999;
                }

  		if (empty($xml))
  		{
			foreach (libxml_get_errors() as $error) {
        		echo 'corp_info Message: ' .$error."\n";
   			}
   			var_dump($xml2);

    		libxml_clear_errors();
    		return 9999;
    	}
		
		if(isset($xml -> result -> corporationName))
		{
			$info['corpname'] = (string)$xml -> result -> corporationName;
			$info['ticker'] = (string)$xml -> result -> ticker;
			$info['allianceid'] = (string)$xml -> result -> allianceID;
			$info['ceoid'] = (string)$xml -> result -> ceoID;
			if(empty($info['allianceid']) || $info['allianceid'] == '')
				$info['allianceid'] = 0;
			$info['alliance'] = (string)$xml -> result -> allianceName;
			if(isset($this -> atags[$info['allianceid']]))
				$info['aticker'] = $this -> atags[$info['allianceid']];
			else
				$info['aticker'] = '';
		}
		Return ($info);
	}

	function standings($keyid, $vcode)
	{
		$post = array('keyID' => $keyid, 'vCode' => $vcode);
		$xml2 = '';
		$xml2 = $this -> get_xml('standings', $post);
		
		if( (stristr($xml2, "runtime")) || (stristr($xml2, "The service is unavailable")) || (!$xml2) || (stristr($xml2, "404 Not Found")) )
		{
				echo "API System Screwed - Can't Fetch Standings : \n";
				var_dump ($xml2);
				Return 9999;
		}
		
		if ( stristr($xml2, "403 - Forbidden") )
		{
			echo "Standings API forbidden - $keyid\n";
			Return 403;
		}
		
		libxml_use_internal_errors(true);
  		
		try {
                        $xml = new SimpleXMLElement($xml2);

                }
                catch(Exception $e)
                {
                        echo "standings api returning invalid xml\n";
                        return 9999;
                }

		if (empty($xml))
  		{
			foreach (libxml_get_errors() as $error) {
        		echo 'standings Message: ' .$error."\n";
   			}
   			var_dump($xml2);

    		libxml_clear_errors();
    		return 9999;
    	}
		
		if(!empty($xml -> result -> rowset[0]))
		{
			foreach($xml -> result -> rowset[0] as $s)
			{
				$cstandings[(string)$s["contactID"]] = array((string)$s["contactName"], (string)$s["standing"]);
			}
		}
		if(!empty($xml -> result -> rowset[1]))
		{
			foreach($xml -> result -> rowset[1] as $s)
			{
				$astandings[(string)$s["contactID"]] = array((string)$s["contactName"], (string)$s["standing"]);
			}
		}

		$count = 0;
		if(!empty($cstandings))
		{
			foreach($cstandings as $i => $c)
			{
				if($c[1] > 0)
				{
					$blues[$i][0] = $c[0];
					$blues[$i][1] = $c[1];
					$blues[$i][2] = 0;
					$count++;
				}
				elseif($c[1] < 0)
				{
					$reds[$i][0] = $c[0];
					$reds[$i][1] = $c[1];
					$reds[$i][2] = 0;
					$count++;
				}
			}
		}

		if(!empty($astandings))
		{
			foreach($astandings as $i => $a)
			{
				if($a[1] > 0)
				{
					$blues[$i][0] = $a[0];
					$blues[$i][2] = $a[1];
					$count++;
					if(!isset($blues[$i][1]))
						$blues[$i][1] = 0;
				}
				elseif($a[1] < 0)
				{
					$reds[$i][0] = $a[0];
					$reds[$i][2] = $a[1];
					$count++;
					if(!isset($reds[$i][1]))
						$reds[$i][1] = 0;
				}
			}
		}
		Return array($blues, $reds, $count);
	}

	function get_api_characters($keyid, $vcode)
	{
		$charlist = array();
		$post = array();
		$post = array('keyID' => $keyid, 'vCode' => $vcode);
		$chars = $this -> get_xml('charlist', $post);
	
		if( (stristr($chars, "runtime")) || (stristr($chars, "The service is unavailable")) || (empty($chars)) || (stristr($chars, "404 Not Found")) )	
		{
				echo "API System Screwed - Can't fetch Toons : \n";
				$this -> data = $chars;
				var_dump ($chars);
				Return 9999;
		}
		
		if ( stristr($chars, "403 - Forbidden") )
		{
			echo "Characters API forbidden - $keyid\n";
			Return 403;
		}
		
		$this -> data = $chars;
		$chars = $this -> xmlparse($chars, "result");
		$chars = $this -> parse($chars);
		if(!empty($chars))
		{
			$charlist = array();
			foreach($chars as $char)
			{
				//	$chars[] = array('name' => $name, 'charid' => $charid, 'corpname' => $corpname, 'corpid' => $corpid);
				$corpinfo = $this -> corp_info($char['corpid']); // corpname, ticker, allianceid, alliance, aticker
				if ($corpinfo == 9999)
				{
					$charlist = 9999;
					return $charlist;
				}
				$char = array_merge($char, $corpinfo);
				$charlist[] = $char;
			}
		}
		Return $charlist;
	}

	function skills($keyid, $vcode, $charid)
	{
		$skills = NULL;
		$skilllist = getSkillArray();
		$sp = 0;
		$post = array();
		$post = array('keyID' => $keyid, 'vCode' => $vcode, 'characterID' => $charid);
		$xml2 = '';
		$xml2 = $this -> get_xml('charsheet', $post);
		
		if( (stristr($xml2, "runtime")) || (stristr($xml2, "The service is unavailable")) || (!$xml2) || (stristr($xml2, "404 Not Found")) )
		{
				echo "API System Screwed - Can't Fetch Skills : \n";
				var_dump ($xml2);
				Return 9999;
		}

		if ( stristr($xml2, "403 - Forbidden") )
		{
			echo "Skills API forbidden - $keyid\n";
			Return 403;
		}
		
		libxml_use_internal_errors(true);
		
		try {
			$xml = new SimpleXMLElement($xml2);
		}
		catch(Exception $e)
		{
			echo "skills api returning invalid xml\n";
			return 9999;
		}

  		if (empty($xml))
  		{
			foreach (libxml_get_errors() as $error) {
        			echo 'skills Message: ' .$error."\n";
   			}
   			var_dump($xml2);

    			libxml_clear_errors();
			return 9999;
    		}
		
		return $skills;
	}
	
	function roles($id, $api, $charid)
	{
		$roles = NULL;
		$post = array();
		$post = array('keyID' => $id, 'vCode' => $api, 'characterID' => $charid);
		$xml2='';
		//echo "getting Roles\n";
		$xml2 = $this -> get_xml('charsheet', $post);
	//	$xml = file_get_contents('me.xml');
		
		if( (stristr($xml2, "runtime")) || (stristr($xml2, "The service is unavailable")) || (!$xml2) || (stristr($xml2, "404 Not Found")) )
		{
				echo "API System Screwed - Can't fetch Roles: \n";
				var_dump ($xml2);
				Return 9999;
		}		

		if ( stristr($xml2, "403 - Forbidden") )
		{
			echo "Roles API forbidden - $id\n";
			Return 403;
		}
		
		libxml_use_internal_errors(true);

                try {
                        $xml = new SimpleXMLElement($xml2);
                }
                catch(Exception $e)
                {
                        echo "roles api returning invalid xml\n";
                        return 9999;
                }

  		if (empty($xml))
  		{
			foreach (libxml_get_errors() as $error) {
        		echo 'roles Message: ' .$error."\n";
   			}
   			var_dump($xml2);
    		libxml_clear_errors();
    		return 9999;
    	}
		
		/*try
		{
			$xml = new SimpleXMLElement($xml2);
		}
		catch(Exception $e)
  		{
  			echo 'roles Message: ' .$e->getMessage();
  			var_dump($xml2);

  		}*/
		$rg = array(2, 3, 4, 5);
		foreach($rg as $i)
		{
			if(!empty($xml -> result -> rowset[$i]))
			{
				foreach($xml -> result -> rowset[$i] as $role)
				{
					$roles[strtolower((string)$role["roleName"])] = TRUE;
				}
			}
		}
		return $roles;
	}

	function titles($id, $api, $charid)
	{
		$titles='';
		$post = array();
		$post = array('keyID' => $id, 'vCode' => $api, 'characterID' => $charid);
		$xml2='';
		$xml2 = $this -> get_xml('charsheet', $post);
			//	$xml = file_get_contents('me.xml');
			
		if( (stristr($xml2, "runtime")) || (stristr($xml2, "The service is unavailable")) || (!$xml2) || (stristr($xml2, "404 Not Found")) )
		{
				echo "API System Screwed - Can't fetch Titles : \n";
				var_dump ($xml2);
				Return 9999;
		}

		if ( stristr($xml2, "403 - Forbidden") )
		{
			echo "Titles API forbidden - $id\n";
			Return 403;
		}
		
		libxml_use_internal_errors(true);

                try {
                        $xml = new SimpleXMLElement($xml2);
                }
                catch(Exception $e)
                {
                        echo "titles api returning invalid xml\n";
                        return 9999;
                }

  		if (empty($xml))
  		{
			foreach (libxml_get_errors() as $error) {
        		echo 'titles Message: ' .$error."\n";
   			}
   			var_dump($xml2);
    		libxml_clear_errors();
    		return 9999;
    	}
			
		/*try
		{
			$xml = new SimpleXMLElement($xml2);
		}
		catch(Exception $e)
  		{
  			echo 'titles Message: ' .$e->getMessage();
  			var_dump($xml2);

  		}*/
		if (!empty($xml -> result -> rowset[6]))
		{
			foreach($xml -> result -> rowset[6] as $title)
			{
				preg_match_all("|<[^>]+>(.*)</[^>]+>|U",$title["titleName"],$tmp, PREG_PATTERN_ORDER);
				if (!empty($tmp[1][0]))
				{
					$tit = $tmp[1][0];
				}
				else
				{
					$tit = $title["titleName"];
				}
				$titles[strtolower((string)$tit)] = TRUE;
			}
		}
		return $titles;
	}

	function militia($id, $api, $charid)
	{
		$post = array();
		$post = array('keyID' => $id, 'vCode' => $api, 'characterID' => $charid);
		$xml2 = '';
		$xml2 = $this -> get_xml('facwar', $post);
		
		if( (stristr($xml2, "runtime")) || (stristr($xml2, "The service is unavailable")) || (!$xml2) || (stristr($xml2, "404 Not Found")) )
		{
				echo "API System Screwed - Can't fetch Militia : \n";
				var_dump ($xml2);
				Return 9999;
		}		
		
		if ( stristr($xml2, "403 - Forbidden") )
		{
			echo "Militia API forbidden - $id\n";
			Return 403;
		}
		
		libxml_use_internal_errors(true);

                try {
                        $xml = new SimpleXMLElement($xml2);
                }
                catch(Exception $e)
                {
                        echo "militia api returning invalid xml\n";
                        return 9999;
                }

  		if (empty($xml))
  		{
			foreach (libxml_get_errors() as $error) {
        		echo 'militia Message: ' .$error."\n";
   			}
   			var_dump($xml2);
    		libxml_clear_errors();
    		return 9999;
    	}
		
		/*try
		{
			$xml = new SimpleXMLElement($xml2);
		}
		catch(Exception $e)
  		{
  			echo 'militia Message: ' .$e->getMessage();
  			var_dump($xml2);

  		}*/
		$faction = $xml -> result -> factionName;
		return $faction;
	}

	/*function get_error($data)
	{
		$data = explode('<error code="', $data, 2);
		if (array_key_exists(1,$data))
		{
			$data = explode('">', $data[1], 2);
			if (array_key_exists(0,$data))
			{
				$id = $data[0];
			}
			else $id = 'no error code';
			if (array_key_exists(1,$data))
			{
				$data = explode('</error>', $data[1], 2);
				if (array_key_exists(0,$data))
				{
					$msg = $data[0];
				}
				else $msg = 'no error message';
			}
			else $msg = 'no error message';
		}
		else
		{
			$id = 'no error code';
			$msg = 'no error message';
		}
		
		Return(array($id, $msg));
	}*/

	function xmlparse($xml, $tag) // replace functions with xml functions
	{
		$tmp = explode("<" . $tag . ">", $xml);
		if(isset($tmp[1]))
			$tmp = explode("</" . $tag . ">", $tmp[1]);
		else
			return NULL;
		return $tmp[0];
	}

	function parse($xml) // replace functions with xml functions
	{
		$chars = NULL;
		$xml = explode("<row ", $xml);
		unset($xml[0]);
		if(!empty($xml))
		{
			foreach($xml as $char)
			{
				$char = explode('name="', $char, 2);
				$char = explode('" characterID="', $char[1], 2);
				$name = $char[0];
				$char = explode('" corporationName="', $char[1], 2);
				$charid = $char[0];
				$char = explode('" corporationID="', $char[1], 2);
				$corpname = $char[0];
				$char = explode('" />', $char[1], 2);
				$corpid = $char[0];
				$chars[] = array('name' => $name, 'charid' => $charid, 'corpname' => $corpname, 'corpid' => $corpid);
			}
		}
		return $chars;
	}

	function is_valid($keyid, $vcode)
	{
		if(isset($this -> valid[$keyid]))
		{
			if($this -> valid[$keyid] == 0)
				return FALSE;
			else
				Return TRUE;
		}
		$post = array();
		$post = array('keyID' => $keyid, 'vCode' => $vcode);
		$data = $this -> get_xml('keyinfo', $post);

		if(stristr($data, "403 - Forbidden"))
		{
			$this -> valid[$keyid] = 0;
			return FALSE;
		}
		$this -> valid[$keyid] = 1;
		Return TRUE;
	}
}
