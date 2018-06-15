<?php
/**
 * short description
 *
 * description
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
 	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function multidim_array_search($parents,$searched) {
		if (empty($searched) || empty($parents)) {
			return false;
		}//if (empty($searched) || empty($parents))
		foreach ($parents as $key=>$value) {
			$exists = true;
			foreach ($searched as $skey=>$svalue) {
				$exists = ($exists && isset($parents[$key][$skey]) && $parents[$key][$skey] == $svalue);
			}//foreach ($searched as $skey=>$svalue)
			if($exists){
				return $key;
			}//if($exists)
		}//foreach ($parents as $key=>$value)
		return false;
	}//END function multidim_array_search
	/**
	 * Convert time stored as string (format 'H:i[:s]') to timestamp (number of seconds)
	 *
	 * @param  string $input Time stored as string (format: 'H:i[:s]')
	 * @param  string $separator Time separator (optional, default is ':')
	 * @return integer|double return Time as timestamp (number of seconds)
	 */
	function str_time_to_timestamp($input,$separator = NULL) {
		if(!is_string($input) || !strlen($input)) { return NULL; }
		$result = 0;
		$lseparator = is_string($separator) && strlen($separator) ? $separator : ':';
		$time_arr = explode($lseparator,$input);
		if(count($time_arr)>=3) {
			$result = (int)($time_arr[0]) * 3600 + (int)($time_arr[1]) * 60 + (int)($time_arr[3]);
		} elseif(count($time_arr)==2) {
			$result = (int)($time_arr[0]) * 3600 + (int)($time_arr[1]) * 60;
		} elseif(count($time_arr)==1) {
			$result = (int)($time_arr[0]) * 3600;
		}//if(count($time_arr)>=3)
		return $result;
	}//END function str_time_to_timestamp
	/**
	 * Convert timestamp (number of seconds) to time stored as string (format 'H:i[:s]')
	 *
	 * @param  integer|double $input Time as timestamp (number of seconds)
	 * @param  bool $with_seconds With seconds TRUE/FALSE
	 * @param  bool $zero_hour Show hour if is 0 TRUE/FALSE
	 * @param  string $separator Time separator (optional, default is ':')
	 * @return string return Time stored as string (format: 'H:i[:s]')
	 */
	function timestamp_to_str_time($input,$with_seconds = TRUE,$zero_hour = FALSE,$separator = NULL) {
		if(!is_numeric($input) || $input<0) { return NULL; }
		$lseparator = is_string($separator) && strlen($separator) ? $separator : ':';
		$result = '';
		$hrem = $input % 3600;
		$hours = (($input - $hrem) / 3600) % 24;
		if($hours>0 || $zero_hour) { $result .= str_pad($hours,2,'0',STR_PAD_LEFT).$lseparator; }
		$mrem = $hrem % 60;
		$minutes = ($hrem - $mrem) / 60;
		$result .= ($hours>0 || $zero_hour) ? str_pad($minutes,2,'0',STR_PAD_LEFT) : $minutes;
		if($with_seconds) { $result .= $lseparator.str_pad($mrem,2,'0',STR_PAD_LEFT); }
		return $result;
	}//END function timestamp_to_str_time
	/**
	 * Convert timestamp (number of seconds) to duration stored as string (format 'H:i[:s]')
	 *
	 * @param  integer|double $input Time as timestamp (number of seconds)
	 * @param  bool $with_seconds With seconds TRUE/FALSE
	 * @param  bool $zero_hour Show hour if is 0 TRUE/FALSE
	 * @param  string $separator Time separator (optional, default is ':')
	 * @return string return Time stored as string (format: 'H:i[:s]')
	 */
	function timestamp_to_str_duration($input,$with_seconds = TRUE,$zero_hour = FALSE,$separator = NULL) {
		if(!is_numeric($input) || $input<0) { return NULL; }
		$lseparator = is_string($separator) && strlen($separator) ? $separator : ':';
		$result = '';
		$hrem = $input % 3600;
		$hours = ($input - $hrem) / 3600;
		if($hours>0 || $zero_hour) { $result .= $hours.$lseparator; }
		$mrem = $hrem % 60;
		$minutes = ($hrem - $mrem) / 60;
		$result .= ($hours>0 || $zero_hour) ? str_pad($minutes,2,'0',STR_PAD_LEFT) : $minutes;
		if($with_seconds) { $result .= $lseparator.str_pad($mrem,2,'0',STR_PAD_LEFT); }
		return $result;
	}//END function timestamp_to_str_duration
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function search_db_array($array,$key,$value) {
		if(!is_array($array)) return NULL;
		foreach ($array as $k=>$v) {
			if($v[$key]==$value) return $k;
		}//foreach ($array as $k=>$v)
		return -1;
	}//END function search_db_array
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function in_db_array($array,$value) {
		if(!is_array($array) || count($array)==0) return 0;
		if(is_array($value)) {
			foreach ($array as $v) {
				$match = 0;
				foreach ($value as $vk=>$vv) {
					if($v[$vk]==$vv) $match++;
				}//foreach ($value as $vk=>$vv)
				if($match==sizeof($value)) return 1;
			}//foreach ($array as $k=>$v)
		}else{
			return in_array($value,$array);
		}//if(is_array($value))
		return 0;
	}//END function in_db_array
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function arr_change_key_case($input,$recursive = FALSE,$case = CASE_LOWER) {
		if(!is_array($input)) { return $input; }
		$result = array();
		foreach ($input as $k=>$v) {
			switch ($case) {
				case CASE_LOWER:
					if(is_array($v) && $recursive===TRUE) {
						$result[strtolower($k)] = arr_change_key_case($v,TRUE,CASE_LOWER);
					}else{
						$result[strtolower($k)] = $v;
					}//if(is_array($v))
					break;
				case CASE_UPPER:
					if(is_array($v) && $recursive===TRUE) {
						$result[strtoupper($k)] = arr_change_key_case($v,TRUE,CASE_UPPER);
					}else{
						$result[strtoupper($k)] = $v;
					}//if(is_array($v))
					break;
				default:
					$result[$k] = $v;
					break;
			}//switch ($case)
		}//foreach ($input as $k=>$v)
		return $result;
	}//END function arr_change_key_case
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function arr_change_value_case($input,$recursive = FALSE,$case = CASE_LOWER) {
		if(is_array($input)) {
			$result = array();
			foreach ($input as $k=>$v) {
				switch ($case) {
					case CASE_LOWER:
						if(is_array($v) && $recursive===TRUE) {
							$result[$k] = arr_change_key_case($v,TRUE,CASE_LOWER);
						}else{
							$result[$k] = is_string($v) ? strtolower($v) : $v;
						}//if(is_array($v))
						break;
					case CASE_UPPER:
						if(is_array($v) && $recursive===TRUE) {
							$result[$k] = arr_change_key_case($v,TRUE,CASE_UPPER);
						}else{
							$result[$k] = is_string($v) ? strtolower($v) : $v;
						}//if(is_array($v))
						break;
					default:
						$result[$k] = $v;
						break;
				}//switch ($case)
			}//foreach ($input as $k=>$v)
			return $result;
		}else{
			return $input;
		}//if(is_array($input))
	}//END function arr_change_value_case
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function limit_text($text,$limitchar) {
		if (strlen($text)<=$limitchar){
			return substr($text,0,$limitchar);
		}else{
			return substr($text,0,$limitchar).'...';
		}//if (strlen($text)<=$limitchar)
	}//END function limit_text
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function win2unix_path($path) {
	    return DIRECTORY_SEPARATOR=='\\' ? str_replace('\\','/',$path) : $path;
	}//END function win2unix_path
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function convert_db_array_to_tree($array,$structure,$uppercasekeys = NULL) {
		if(!is_array($array) || count($array)==0) { return NULL; }
		if((!is_array($structure) && strlen($structure)==0) || (is_array($structure) && count($structure)==0)) { return $array; }
		$lstructure = $structure;
		if(!is_array($structure)) { $lstructure = array($structure); }
		$valkey = array_pop($lstructure);
		if(count($lstructure)>0) {
			$keystr = '';
			if($uppercasekeys===TRUE) {
				foreach($lstructure as $key) { $keystr .= '[strtoupper($row["'.strtoupper($key).'"])]'; }
			} elseif($uppercasekeys===FALSE) {
				foreach($lstructure as $key) { $keystr .= '[strtolower($row["'.strtolower($key).'"])]'; }
			} else {
				foreach($lstructure as $key) { $keystr .= '[$row["'.$key.'"]]'; }
			}//if($uppercasekeys===TRUE)
		} else {
			$keystr = '[]';
		}//if(count($lstructure)>0)
		$result = array();
		foreach($array as $row) { eval('$result'.$keystr.' = $row["'.$valkey.'"];'); }
		return $result;
	}//END function convert_db_array_to_tree
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function custom_shuffle(&$array,$return = TRUE) {
		if(!is_array($array)) { if($return) { return $array; } else { return; } }
		$keys = array_keys($array);
		shuffle($keys);
		$random = array();
		foreach($keys as $key) { $random[$key] = $array[$key]; }
		if($return) { return $random; }
		$array = $random;
	}//function custom_shuffle
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	function check_file_404($file,$ext = NULL) {
		$file = preg_replace('{ +}','%20',trim($file));
		if(substr($file,0,7)!=="http://") { $file = "http://".$file; }
		if($ext) {
			$file_ext = strtolower(array_pop(explode('.',$file)));
			if($file_ext!==$ext) { return 1; }
		}//if($ext)
		try {
			$file_headers = @get_headers($file);
		} catch(Exception $e) {
			$file_headers = NULL;
		}//END try
		if(!$file_headers) { return 2; }
		if($file_headers[0] == 'HTTP/1.1 404 Not Found') { return 404; }
	 	return TRUE;
	}//END function check_file_404
	/**
	 * description
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 */
	function curl_call($params = array(),&$info = NULL) {
		if(!is_array($params) || !count($params)) { return FALSE; }
		if(!isset($params['url']) || !strlen($params['url'])) { return FALSE; }
		if(isset($params['user_agent']) && strlen($params['user_agent'])) {
			$req_user_agent = $params['user_agent']=='auto' ? 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36' : $params['user_agent'];
		} else {
			$req_user_agent = 'PHP_CURL_CALL';
		}//if(isset($params['user_agent']) && strlen($params['user_agent']))

		$c_url = curl_init();
		@$options = array(
			CURLOPT_URL=>$params['url'],
			CURLOPT_SSL_VERIFYPEER=>FALSE,
			CURLOPT_RETURNTRANSFER=>TRUE,
			CURLOPT_FOLLOWLOCATION=>TRUE,
			CURLOPT_CONNECTTIMEOUT=>60,
			CURLOPT_TIMEOUT=>300,
			CURLOPT_MUTE=>TRUE,
			// CURLOPT_FRESH_CONNECT=>TRUE,
	        // CURLOPT_HEADER=>FALSE,
			CURLOPT_USERAGENT=>$req_user_agent,
	        // This is what solved the issue (Accepting gzip encoding)
	        CURLOPT_ENCODING=>'gzip, deflate',
		);
		@curl_setopt_array($c_url,$options);

		if(isset($params['post_params']) && $params['post_params']) {
			curl_setopt($c_url,CURLOPT_POST,TRUE);
			curl_setopt($c_url,CURLOPT_POSTFIELDS,$params['post_params']);
		}//if(isset($params['post_params']) && $params['post_params'])
		$result = curl_exec($c_url);
		$error = curl_error($c_url);
		$info = curl_getinfo($c_url);
		curl_close($c_url);
		if($error) { throw new Exception($error); }
		return $result;
	}//END function curl_call
	/**
	 * description
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 */
	function async_curl_call($params = NULL) {
		if(!is_array($params) || !count($params)) { return FALSE; }
		if(!isset($params['url']) || !$params['url']) { return FALSE; }
		$req_user_agent = 'PHP_ASYNC_CURL_CALL';
		$url = $params['url'];
		$options = array(
			CURLOPT_URL=>$url,
			CURLOPT_FAILONERROR=>TRUE,
			CURLOPT_FRESH_CONNECT=>TRUE,
			CURLOPT_HEADER=>FALSE,
			CURLOPT_RETURNTRANSFER=>TRUE,
			CURLOPT_NOSIGNAL=>1, //to timeout immediately if the value is < 1000 ms
			CURLOPT_TIMEOUT_MS=>50, //The maximum number of mseconds to allow cURL functions to execute
			CURLOPT_CONNECTTIMEOUT=>60,
			CURLOPT_TIMEOUT=>36000,
			CURLOPT_USERAGENT=>$req_user_agent,
			CURLOPT_VERBOSE=>1,
	        CURLOPT_HEADER=>1,
		);
		$c_url = curl_init();
		curl_setopt_array($c_url,$options);
		if(isset($params['post_params']) && $params['post_params']) {
			curl_setopt($c_url,CURLOPT_POST,TRUE);
			curl_setopt($c_url,CURLOPT_POSTFIELDS,$params['post_params']);
		}//if(isset($params['post_params']) && $params['post_params'])
		$result = curl_exec($c_url);
		$error = curl_error($c_url);
		curl_close($c_url);
		if($error) { return $error; }
		return $result;
	}//END function async_curl_call
	/**
	 * Emulate ping command
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 */
	function ping($host,$port = 80,$timeout = 10) {
		$ts = microtime(true);
		$errno = $errstr = NULL;
		try {
			$sconn = fSockOpen($host,$port,$errno,$errstr,$timeout);
			if(!$sconn || $errno) { return 'Timeout/Error: ['.$errno.'] '.$errstr; }
			return round(((microtime(true) - $ts) * 1000), 0).' ms';
		} catch(Exception $e) {
			return 'Exception: '.$e->getMessage();
		}//END try
	}//END function ping
?>