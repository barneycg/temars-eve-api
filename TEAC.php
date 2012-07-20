<?php

class TEAC
{
	function __construct()
	{
		$this -> version = "1.3";
		$this -> server = 'http://api.eve-online.com';

		$this -> atags = array();
	}

	function get_xml($type, $post = NULL)
	{
		return TEACN::get_xml($type, $post);
	}

	function get_site($url, $post=FALSE)
	{
		return TEACN::get_site($url, $post);
	}

	function get_site_sock($url, $post)
	{
		return TEACN::get_site_sock($url, $post);
	}

	function get_site_curl($url, $post)
	{
		return TEACN::get_site_curl($url, $post);
	}

	function corp_info($corp)
	{
		if(empty($this -> newc))
			$this -> newc = new TEACN;
		$this -> newc -> atags = $this -> atags;
		return $this -> newc -> corp_info($corp);
	}

	function standings($userid, $apikey)
	{
		return TEACN::standings($userid, $apikey);
	}

	function get_api_characters($userid, $api)
	{
		$check = $this -> is_new($userid, $api);
		if($check)
			$class = $this -> newc;
		else
			$class = $this -> oldc;
		$class -> atags = $this -> atags;
		$chars = $class -> get_api_characters($userid, $api);
		$this -> data = $class -> data;
		return $chars;
	}

	function skills($userid, $api, $charid)
	{
		$check = $this -> is_new($userid, $api);
		if($check)
			$class = $this -> newc;
		else
			$class = $this -> oldc;
		$skills = $class -> skills($userid, $api, $charid);
		return $skills;
	}

	function roles($id, $api, $charid)
	{
		$check = $this -> is_new($id, $api);
		if($check)
			$class = $this -> newc;
		else
			$class = $this -> oldc;
		$roles = $class -> roles($id, $api, $charid);
		return $roles;
	}


	function titles($id, $api, $charid)
	{
		$check = $this -> is_new($id, $api);
		if($check)
			$class = $this -> newc;
		else
			$class = $this -> oldc;
		$titles = $class -> titles($id, $api, $charid);
		return $titles;
	}

	function mititia($id, $api, $charid)
	{
		$check = $this -> is_new($id, $api);
		if($check)
			$class = $this -> newc;
		else
			$class = $this -> oldc;
		$mititia = $class -> mititia($id, $api, $charid);
		return $mititia;
	}
	function get_error($data)
	{
		return TEACN::get_error($data);
	}

	function xmlparse($xml, $tag) // replace functions with xml functions
	{
		return TEACN::xmlparse($xml, $tag);
	}

	function parse($xml) // replace functions with xml functions
	{
		return TEACN::parse($xml);
	}

	function is_new($keyid, $vcode)
	{
		if(empty($this -> newc))
		{
			$this -> newc = new TEACN;
			$this -> oldc = new TEACO;
		}
		if(isset($this -> newold[$keyid]))
		{
			if($this -> newold[$keyid] == "old")
				return FALSE;
			else
				Return TRUE;
		}
		$post = array('keyID' => $keyid, 'vCode' => $vcode);
		$data = $this -> get_xml('keyinfo', $post);
		if(stristr($data, "error"))
		{
			$error = $this -> get_error($data);
			if($error[0] == 222)
			{
				$this -> newold[$keyid] = 'old';
				return FALSE;
			}
			Return TRUE;
		}
		$this -> newold[$keyid] = 'new';
		Return TRUE;
	}
}

class TEACN
{
	function __construct()
	{
		$this -> version = "1.3";
		$this -> server = 'http://api.eve-online.com';

		$this -> atags = array();
	}

	function get_xml($type, $post = NULL)
	{
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
		if($type != 'standings' && $type != 'alliances' && method_exists($this, 'get_cache'))
		{
			$cache = $this -> get_cache($url, $post);
		}
		if($cache)
			return $cache;

		$xml = $this -> get_site($this -> server.$url, $post);

		if($type != 'standings' && $type != 'alliances' && method_exists($this, 'set_cache'))
		{
			$cache = $this -> set_cache($url, $post, $xml);
		}

		return $xml;
	}

	function get_site($url, $post=FALSE)
	{
		if(!function_exists('curl_init'))
		{
			$return = $this->get_site_sock($url, $post);
			if ($return['error'])
			{
				echo $return['errordesc'] . " Reason (" . $return['content'] . ")";
			}

			return $return["content"];
		}
		else
		{
			Return $this->get_site_curl($url, $post);
		}
	}

	function get_site_sock($url, $post)
	{
		$get_url = parse_url($url);

		$address = gethostbyname($get_url['host']);

		/* Get the port for the WWW service. */
		$service_port = getservbyname('www', 'tcp');

		/* Create a TCP/IP socket. */
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		// Check to see if the socket failed to create.
		if ($socket === false) {
			$return["error"] = true;
			$return["errordesc"] = "Failed to create a socket";
			$return["content"] = socket_strerror(socket_last_error());

			return $return;
		}
		
		// Set some sane read timeouts to prevent the bot from hanging forever.
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 30, "usec" => 0));
		
		$connect_result = @socket_connect($socket, $address, $service_port);

		// Make sure we have a connection
		if ($connect_result === false)
		{
			echo "Failed to connect to server: $url\n";
			$return["error"] = true;
			$return["errordesc"] = "Coult not connect to server " . $address . ":" . $service_port . " (" . $url . ")";
			$return["content"] = socket_strerror(socket_last_error());
			
			return $return;
		}

		// Rebuild the full query after parse_url
		$url = $get_url["path"];
		// if (!empty($get_url["query"]))
		// {
			// $url .= '?' . $get_url["query"];
		// }
		if(!empty($post))
		{
			$url .= '?'.$post;
		}

		$in = "GET $url HTTP/1.0\r\n";
		$in .= "Host: " . $get_url['host'] . "\r\n";
		$in .= "Connection: Close\r\n";
		$in .= "User-Agent:TEA 1.1.1\r\n\r\n";

		$write_result = @socket_write($socket, $in, strlen($in));

		// Make sure we wrote to the server okay.
		if ($write_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Coult not write to server";
			$return["content"] = socket_strerror(socket_last_error());
			
			return $return;
		}

		$return["content"] = "";
		$read_result = @socket_read($socket, 2048);
		while ($read_result != "" && $read_result !== false)
		{
			$return["content"] .= $read_result;
			$read_result = @socket_read($socket, 2048);
		}

		// Make sure we got a response back from the server.
		if ($read_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Server returned no data";
			$return["content"] = socket_strerror(socket_last_error());
			
			return $return;
		}

		$close_result = @socket_close($socket);

		// Make sure we closed our socket properly.  Open sockets are bad!
		if ($close_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Failed to close socket";
			$return["content"] = socket_strerror(socket_last_error());
	
			return $return;
		}

		// Did the calling function want http headers stripped?
	//	if ($strip_headers)
	//	{
			$split = explode("\r\n\r\n", $return['content'], 2);
			$return["content"] = $split[1];
	//	}

		return $return;
	}

	function get_site_curl($url, $post)
	{
		$ch = curl_init();

		if(!empty($post))
		{
			curl_setopt($ch, CURLOPT_POST      ,1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
		}

		curl_setopt($ch, CURLOPT_URL, $url);

		if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$data = curl_exec($ch);
		curl_close($ch);

		//echo "<pre>"; var_dump($data); echo "</pre>";
		Return $data;
	}

	function corp_info($corp)
	{
		$post = array('corporationID' => $corp);
		$xml2 = $this -> get_xml('corp', $post);
		if(strstr($xml2, '<description>'))
		{
			$xml2 = explode("<description>", $xml2, 2);
			$xml2[1] = explode("</description>", $xml2[1], 2);
			$xml2 = $xml2[0].'<description>removed</description>'.$xml2[1][1];
		}
		$xml = new SimpleXMLElement($xml2);
		if(isset($xml -> result -> corporationName))
		{
			$info['corpname'] = (string)$xml -> result -> corporationName;
			$info['ticker'] = (string)$xml -> result -> ticker;
			$info['allianceid'] = (string)$xml -> result -> allianceID;
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
		$xml = $this -> get_xml('standings', $post);

		$xml = new SimpleXMLElement($xml);
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
		$charlist = NULL;
		$post = array('keyID' => $keyid, 'vCode' => $vcode);
		$chars = $this -> get_xml('charlist', $post);
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
		$post = array('keyID' => $keyid, 'vCode' => $vcode, 'characterID' => $charid);
		$xml = $this -> get_xml('charsheet', $post);
		$xml = new SimpleXMLElement($xml);
		if(!empty($xml -> result -> rowset[0]))
		{
			foreach($xml -> result -> rowset[0] as $skill)
			{
				//echo "<pre>";var_dump($skill["typeID"]); echo '<hr>';
				$skills[strtolower($skilllist[(string)$skill["typeID"]])] = (string)$skill["level"];
			}
		}
		return $skills;
	}

	function roles($id, $api, $charid)
	{
		$roles = NULL;
		$post = array('keyID' => $id, 'vCode' => $api, 'characterID' => $charid);
		$xml = $this -> get_xml('charsheet', $post);
	//	$xml = file_get_contents('me.xml');
		$xml = new SimpleXMLElement($xml);
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
		$post = array('keyID' => $id, 'vCode' => $api, 'characterID' => $charid);
		$xml = $this -> get_xml('charsheet', $post);
	//	$xml = file_get_contents('me.xml');
		$xml = new SimpleXMLElement($xml);
		foreach($xml -> result -> rowset[6] as $title)
		{
			$titles[strtolower((string)$title["titleName"])] = TRUE;
		}
		return $titles;
	}

	function mititia($id, $api, $charid)
	{
		$post = array('keyID' => $id, 'vCode' => $api, 'characterID' => $charid);
		$xml = $this -> get_xml('facwar', $post);
		$xml = new SimpleXMLElement($xml);
		$faction = $xml -> result -> factionName;
		return $faction;
	}

	function get_error($data)
	{
		$data = explode('<error code="', $data, 2);
		$data = explode('">', $data[1], 2);
		$id = $data[0];
		$data = explode('</error>', $data[1], 2);
		$msg = $data[0];
		Return(array($id, $msg));
	}

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


class TEACO
{
	function __construct()
	{
		$this -> version = "1.3";
		$this -> server = 'http://api.eve-online.com';

		$this -> atags = array();
	}

	function get_xml($type, $post = NULL)
	{
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
		if($type != 'standings' && $type != 'alliances' && method_exists($this, 'get_cache'))
		{
			$cache = $this -> get_cache($url, $post);
		}
		if($cache)
			return $cache;

		$xml = $this -> get_site($this -> server.$url, $post);

		if($type != 'standings' && $type != 'alliances' && method_exists($this, 'set_cache'))
		{
			$cache = $this -> set_cache($url, $post, $xml);
		}

		return $xml;
	}

	function get_site($url, $post=FALSE)
	{
		if(!function_exists('curl_init'))
		{
			$return = $this->get_site_sock($url, $post);
			if ($return['error'])
			{
				echo $return['errordesc'] . " Reason (" . $return['content'] . ")";
			}

			return $return["content"];
		}
		else
		{
			Return $this->get_site_curl($url, $post);
		}
	}

	function get_site_sock($url, $post)
	{
		$get_url = parse_url($url);

		$address = gethostbyname($get_url['host']);

		/* Get the port for the WWW service. */
		$service_port = getservbyname('www', 'tcp');

		/* Create a TCP/IP socket. */
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		// Check to see if the socket failed to create.
		if ($socket === false) {
			$return["error"] = true;
			$return["errordesc"] = "Failed to create a socket";
			$return["content"] = socket_strerror(socket_last_error());

			return $return;
		}
		
		// Set some sane read timeouts to prevent the bot from hanging forever.
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 30, "usec" => 0));
		
		$connect_result = @socket_connect($socket, $address, $service_port);

		// Make sure we have a connection
		if ($connect_result === false)
		{
			echo "Failed to connect to server: $url\n";
			$return["error"] = true;
			$return["errordesc"] = "Coult not connect to server " . $address . ":" . $service_port . " (" . $url . ")";
			$return["content"] = socket_strerror(socket_last_error());
			
			return $return;
		}

		// Rebuild the full query after parse_url
		$url = $get_url["path"];
		// if (!empty($get_url["query"]))
		// {
			// $url .= '?' . $get_url["query"];
		// }
		if(!empty($post))
		{
			$url .= '?'.$post;
		}

		$in = "GET $url HTTP/1.0\r\n";
		$in .= "Host: " . $get_url['host'] . "\r\n";
		$in .= "Connection: Close\r\n";
		$in .= "User-Agent:TEA 1.1.1\r\n\r\n";

		$write_result = @socket_write($socket, $in, strlen($in));

		// Make sure we wrote to the server okay.
		if ($write_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Coult not write to server";
			$return["content"] = socket_strerror(socket_last_error());
			
			return $return;
		}

		$return["content"] = "";
		$read_result = @socket_read($socket, 2048);
		while ($read_result != "" && $read_result !== false)
		{
			$return["content"] .= $read_result;
			$read_result = @socket_read($socket, 2048);
		}

		// Make sure we got a response back from the server.
		if ($read_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Server returned no data";
			$return["content"] = socket_strerror(socket_last_error());
			
			return $return;
		}

		$close_result = @socket_close($socket);

		// Make sure we closed our socket properly.  Open sockets are bad!
		if ($close_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Failed to close socket";
			$return["content"] = socket_strerror(socket_last_error());
	
			return $return;
		}

		// Did the calling function want http headers stripped?
	//	if ($strip_headers)
	//	{
			$split = explode("\r\n\r\n", $return['content'], 2);
			$return["content"] = $split[1];
	//	}

		return $return;
	}

	function get_site_curl($url, $post)
	{
		$ch = curl_init();

		if(!empty($post))
		{
			curl_setopt($ch, CURLOPT_POST      ,1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
		}

		curl_setopt($ch, CURLOPT_URL, $url);

		if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$data = curl_exec($ch);
		curl_close($ch);

		//echo "<pre>"; var_dump($data); echo "</pre>";
		Return $data;
	}

	function corp_info($corp)
	{
		$post = array('corporationID' => $corp);
		$xml2 = $this -> get_xml('corp', $post);
		if(strstr($xml2, '<description>'))
		{
			$xml2 = explode("<description>", $xml2, 2);
			$xml2[1] = explode("</description>", $xml2[1], 2);
			$xml2 = $xml2[0].'<description>removed</description>'.$xml2[1][1];
		}
		$xml = new SimpleXMLElement($xml2);
		if(isset($xml -> result -> corporationName))
		{
			$info['corpname'] = (string)$xml -> result -> corporationName;
			$info['ticker'] = (string)$xml -> result -> ticker;
			$info['allianceid'] = (string)$xml -> result -> allianceID;
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

	function standings($userid, $apikey, $charid)
	{
		$post = array('userID' => $userid, 'apiKey' => $apikey, 'characterID' => $charid);
		$xml = $this -> get_xml('standings', $post);

		$xml = new SimpleXMLElement($xml);
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

	function get_api_characters($userid, $api)
	{
		$charlist = NULL;
		$post = array('userID' => $userid, 'apiKey' => $api);
		$chars = $this -> get_xml('charlist', $post);
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
				$char = array_merge($char, $corpinfo);
				$charlist[] = $char;
			}
		}
		Return $charlist;
	}

	function skills($id, $api, $charid)
	{
		$skills = NULL;
		$skilllist = getSkillArray();
		$post = array('userID' => $id, 'apiKey' => $api, 'characterID' => $charid);
		$xml = $this -> get_xml('charsheet', $post);
		$xml = new SimpleXMLElement($xml);
		if(!empty($xml -> result -> rowset[0]))
		{
			foreach($xml -> result -> rowset[0] as $skill)
			{
				//echo "<pre>";var_dump($skill["typeID"]); echo '<hr>';
				$skills[strtolower($skilllist[(string)$skill["typeID"]])] = (string)$skill["level"];
			}
		}
		return $skills;
	}

	function roles($id, $api, $charid)
	{
		$roles = NULL;
		$post = array('userID' => $id, 'apiKey' => $api, 'characterID' => $charid);
		$xml = $this -> get_xml('charsheet', $post);
	//	$xml = file_get_contents('me.xml');
		$xml = new SimpleXMLElement($xml);
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
		$post = array('userID' => $id, 'apiKey' => $api, 'characterID' => $charid);
		$xml = $this -> get_xml('charsheet', $post);
	//	$xml = file_get_contents('me.xml');
		$xml = new SimpleXMLElement($xml);
		foreach($xml -> result -> rowset[6] as $title)
		{
			$titles[strtolower((string)$title["titleName"])] = TRUE;
		}
		return $titles;
	}

	function mititia($id, $api, $charid)
	{
		$post = array('userID' => $id, 'apiKey' => $api, 'characterID' => $charid);
		$xml = $this -> get_xml('facwar', $post);
		$xml = new SimpleXMLElement($xml);
		$faction = $xml -> result -> factionName;
		return $faction;
	}

	function get_error($data)
	{
		$data = explode('<error code="', $data, 2);
		$data = explode('">', $data[1], 2);
		$id = $data[0];
		$data = explode('</error>', $data[1], 2);
		$msg = $data[0];
		Return(array($id, $msg));
	}

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

?>