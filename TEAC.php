<?php

class TEAC
{
	function __construct()
	{
		$this -> version = "1.3";
		$this -> server = 'https://api.eveonline.com';//$this -> modSettings['tea_api_server'];//
		$this -> atags = array();
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
	
	function is_valid_xml($xml)
	{
		if ((stristr($xml, "<?xml version='1.0' encoding='UTF-8'?>")) && (stristr($xml, '<eveapi version="2">')))
			return True;
		else
			return False;
	}
		
	function error_check($xml)
	{
		$error_code = (int)$xml->error[0]['code'];
		$error_msg = (string)$xml->error[0];
		Return(array($error_code, $error_msg));
		/*
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
		//else
			//TODO : work out the http error.
		//	return XXXXXXX;
		*/
	}
	
	function get_xml($type, $post = NULL)
	{
		$url = '';
		$xml = '';
		$error = '';
		$error_code = '';
		
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
			$ret_val = $cache;
		else
			$ret_val = $this -> get_site($this -> server.$url, $post);

		if ($this->is_valid_xml($ret_val))
		{
			$xml = new SimpleXMLElement($ret_val);
			$error = $this->error_check($xml);
			
			if (!empty($error[0]))
			{
				$error_code = $error[0];
			}
			
			switch ($error_code)
			{
				case '' :
					if($type != 'calllist' && $type != 'standings' && $type != 'alliances' && method_exists($this, 'set_cache'))
					{
						$cache = $this -> set_cache($url, $post, $ret_val);
					}
					return $xml;
				default :
					if($type != 'calllist' && $type != 'standings' && $type != 'alliances' && method_exists($this, 'set_cache'))
					{
						$cache = $this -> set_cache($url, $post, $ret_val);
					}
					return $error_code;
			}
		}
		else
			//TODO : work out the http error.
			return False;
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
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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
		
		$xml = $this -> get_xml('corp', $post);
		
		if (($xml) && (gettype($xml) == 'object'))
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
			
			Return ($info);
		}
		elseif (($xml) && (gettype($xml) == 'integer'))
		{
			echo "API call error while fetching corp info: \nError Code = $xml for corpid = $corp\n";
			Return $xml;
		}
		else
		{
			echo "API System Screwed - Can't Fetch Corp Info : \n";
			Return 9999;
		}
	}

	function standings($keyid, $vcode)
	{
		$post = array('keyID' => $keyid, 'vCode' => $vcode);
		$xml = $this -> get_xml('standings', $post);
		
		if (($xml) && (gettype($xml) == 'object')) 
		{
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
		elseif (($xml) && (gettype($xml) == 'integer'))
		{
			echo "API call error while fetching standings: \nError Code = $xml for key id = $keyid\n";
			Return $xml;
		}
		else
		{
			echo "API System Screwed - Can't Fetch Standings : \n";
			Return 9999;
		}		
	}

	function get_api_characters($keyid, $vcode)
	{
		$charlist = array();
		$post = array();
		$post = array('keyID' => $keyid, 'vCode' => $vcode);
		$xml = $this -> get_xml('charlist', $post);

		if (($xml) && (gettype($xml) == 'object'))
		{
			//var_dump($chars->result[0]->rowset[0]->row);
			foreach ($xml->result[0]->rowset[0]->row as $char)
			{
				$charinfo['name']=(string)$char['name'];
				$charinfo['corpid']=(string)$char['corporationID'];
				$charinfo['corpname']=(string)$char['corporationName'];
				$charinfo['charid']=(string)$char['characterID'];
				
				$corpinfo = $this -> corp_info((string)$char['corporationID']); // corpname, ticker, allianceid, alliance, aticker
				if (gettype($corpinfo) == 'integer')
				{
					return 9999;
				}
				$char = array_merge($charinfo, $corpinfo);
				$charlist[] = $char;
			}
			Return $charlist;
		}
		elseif (($xml) && (gettype($xml) == 'integer'))
		{
			echo "API call error while fetching toons: \nError Code = $xml for key id = $keyid\n";
			Return $xml;		
		}
		else
		{
			echo "API System Screwed - Can't fetch Toons : \n";
			Return 9999;
		}
	}

	//TODO : Skills function needs complete overhall
	function skills($keyid, $vcode, $charid)
	{
		$skills = NULL;
		$skilllist = getSkillArray();
		$sp = 0;
		$post = array();
		$post = array('keyID' => $keyid, 'vCode' => $vcode, 'characterID' => $charid);
		$xml = $this -> get_xml('charsheet', $post);
		
		if (($xml) && (gettype($xml) == 'object'))
		{
			foreach ($xml->result->rowset[0]->row as $skill)
			{
				$id = (string)$skill['typeID'];
				$level = (string)$skill['level'];
				$name = $skilllist[$id];
				$skills[strtolower($name)] = $level;
			}
			
			return $skills;
		}
		elseif (($xml) && (gettype($xml) == 'integer'))
		{
			echo "API call error while fetching skills: \nError Code = $xml for key id = $keyid $vcode $charid\n";
			Return $xml;
		}
		else
		{
			echo "API System Screwed - Can't Fetch Skills : \n";
			Return 9999;
		}
	}
	
	function roles($keyid, $vcode, $charid)
	{
		$roles = NULL;
		$post = array();
		$post = array('keyID' => $keyid, 'vCode' => $vcode, 'characterID' => $charid);

		//echo "getting Roles\n";
		$xml = $this -> get_xml('charsheet', $post);
	//	$xml = file_get_contents('me.xml');
		
		if (($xml) && (gettype($xml) == 'object'))
		{
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
		elseif (($xml) && (gettype($xml) == 'integer'))
		{
			echo "API call error while fetching roles: \nError Code = $xml for key id = $keyid\n";
			Return $xml;
		}
		else
		{
			echo "API System Screwed - Can't Fetch Roles : \n";
			Return 9999;
		}
	}

	function titles($keyid, $vcode, $charid)
	{
		$titles='';
		$post = array();
		$post = array('keyID' => $keyid, 'vCode' => $vcode, 'characterID' => $charid);

		$xml = $this -> get_xml('charsheet', $post);
		
		if (($xml) && (gettype($xml) == 'object'))
		{
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
		elseif (($xml) && (gettype($xml) == 'integer'))
		{
			echo "API call error while fetching titles: \nError Code = $xml for key id = $keyid\n";
			Return $xml;
		}
		else
		{
			echo "API System Screwed - Can't Fetch Titles : \n";
			Return 9999;
		}
		
	}

	function militia($keyid, $vcode, $charid)
	{
		$post = array();
		$post = array('keyID' => $keyid, 'vCode' => $vcode, 'characterID' => $charid);
	
		$xml = $this -> get_xml('facwar', $post);
		
		if (($xml) && (gettype($xml) == 'object'))
		{
			$faction = $xml -> result -> factionName;
			return $faction;
		}
		elseif (($xml) && (gettype($xml) == 'integer'))
		{
			echo "API call error while fetching militia: \nError Code = $xml for key id = $keyid\n";
			Return $xml;
		}
		else
		{
			echo "API System Screwed - Can't Fetch Militia : \n";
			Return 9999;
		}
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
}
