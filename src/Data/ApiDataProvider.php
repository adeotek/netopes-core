<?php
/**
 * API calls dispatcher class file
 *
 * All calls between application instances are mediated by this dispatcher class.
 *
 * @package    NETopes\API
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use NApp;

/**
 * ApiDispatcher class is the API curl calls dispatcher
 *
 * The methods in this class process the parametrs and initiate a curl request between application instances.
 *
 * @package  NETopes\API
 * @access   public
 */
class ApiDataProvider {
	/**
	 * Gets the url to be used by curl Api call from an array of parameters
	 *
	 * @param  array $params An array of parameters that contains either the url
	 * or the parts from which the url is composed:
	 * - url (optional) if passed all other parameters are ignored and this value is returned
	 * - protocol (optional) defines the protocol type http (default) or https
	 * - domain (required)
	 * - get_params (optional) a key-value array with parameters to be send as GET params
	 * @return string Returns the url to be used for curl API call or NULL if the parametrs are invalide
	 * @access private
	 * @static
	 */
	private static function GetUrl($params = NULL) {
		if(!is_array($params) || count($params)==0) { return NULL; }
		if(array_key_exists('url',$params) && $params['url']) { return $params['url']; }
		if(!array_key_exists('domain',$params) || !$params['domain']) { return NULL; }
		$protocol = (array_key_exists('protocol',$params) && $params['protocol']) ? $params['protocol'] : 'http';
		$url = $protocol.'://'.$params['domain'].'/pipe/api.php';
		if(array_key_exists('get_params',$params) && is_array($params['get_params']) && count($params['get_params'])>0) {
			$gparams = '';
			foreach($params['get_params'] as $k=>$v) {
				$gparams .= ($gparams ? '&' : '?').$k.'='.rawurlencode($v);
			}//END foreach
			$url .= $gparams;
		}//if(array_key_exists('get_params',$params) && is_array($params['get_params']) && count($params['get_params'])>0)
		return $url;
	}//END private static function GetUrl
	/**
	 * This method initiates the curl object and makes the curl request
	 * based on the received parameters
	 *
	 * @param  array $params An array of parameters for initiating a new curl request
	 * @return mixed Returns the response of the curl request,
	 * an key-value array containing the response and the error if one is returned by curl
	 * or FALSE for other errors
	 * @access protected
	 * @static
	 */
	protected static function CurlCall($params = NULL) {
		if(!is_array($params) || count($params)==0) { return FALSE; }
		$req_user_agent = (array_key_exists('HTTP_USER_AGENT',$_SERVER) && preg_match('/Chrome|Firefox/',$_SERVER['HTTP_USER_AGENT'])===1) ? $_SERVER['HTTP_USER_AGENT'] : 'AUTOMATED_REQUEST';
		$url = self::GetUrl($params);
		if(!$url) { return FALSE; }
		$req_post = FALSE;
		if(array_key_exists('post_params',$params) && is_array($params['post_params']) && count($params['post_params'])>0) {
			$req_post = TRUE;
		}//if(array_key_exists('post_params',$params) && is_array($params['post_params']) && count($params['post_params'])>0)
		$options = array(
				CURLOPT_URL=>$url,
				CURLOPT_FAILONERROR=>TRUE,
				CURLOPT_FRESH_CONNECT=>TRUE,
				CURLOPT_HEADER=>FALSE,
				CURLOPT_RETURNTRANSFER=>TRUE,
				CURLOPT_CONNECTTIMEOUT=>60,
				CURLOPT_TIMEOUT=>300,
				CURLOPT_USERAGENT=>$req_user_agent
			);
		$c_url = curl_init();
		curl_setopt_array($c_url,$options);
		if($req_post) {
			curl_setopt($c_url,CURLOPT_POST,TRUE);
			curl_setopt($c_url,CURLOPT_POSTFIELDS,$params['post_params']);
		}//if($req_post)
		$result = curl_exec($c_url);
		$error = curl_error($c_url);
		curl_close($c_url);
		if($error) { return array('result'=>$result,'error'=>$error); }
		return $result;
	}//END protected static function CurlCall
	/**
	 * Sends a new API request between application instances based on the received parameters.
	 *
	 * @param  array $params An array of parameters from which the curl call is initiated
	 * @return mixed An key value array containing request respones ('result' key)
	 * and the request errors ('error' key) or FALSE for other errors
	 * @access public
	 * @static
	 */
	public static function Call($params = NULL) {
		if(!is_array($params) || count($params)==0) { return FALSE; }
		$rtype = array_key_exists('request_type',$params) ? $params['request_type'] : NULL;
		$pparams = (array_key_exists('post_params',$params) && is_array($params['post_params'])) ? $params['post_params'] : NULL;
		if(array_key_exists('target',$params) && is_array($params['target']) && count($params['target'])>0 && $params['target']['id']) {
			$targets = array($params['target']);
			$singletarget = TRUE;
		} else {
			$singletarget = array_key_exists('single_target',$params) ? $params['single_target'] : FALSE;
			$ttype = (array_key_exists('target_type',$params) && is_numeric($params['target_type'])) ? $params['target_type'] : 0;
			$tid = (array_key_exists('id_target_account',$params) && is_numeric($params['id_target_account'])) ? $params['id_target_account'] : NULL;
			$targets = DataProvider::GetArray('Api\Native','GetApiTargets',array('for_type'=>$ttype,'for_id'=>$tid));
			if(!is_array($targets) || count($targets)==0) { return FALSE; }
			if($singletarget) { $targets = array($targets[0]); }
		}//if(array_key_exists('target',$params) && is_array($params['target']) && count($params['target'])>0 && $params['target']['id'])
		/*if(NApp::$debug) {
			echo '<strong>Targets:</strong><br/>';
			var_dump($targets); echo '<br/><br/>';
		}//if(NApp::$debug)*/
		$result = [];
		foreach($targets as $target) {
			$taccesskey = array_key_exists('access_key',$target) ? $target['access_key'] : '';
			$pparams = $pparams ? \GibberishAES::enc(serialize($pparams),$taccesskey) : NULL;
			$lparams = array(
					'domain'=>(array_key_exists('website',$target) ? $target['website'] : ''),
					'post_params'=>array(
							'type'=>rawurlencode(GibberishAES::enc($rtype,$taccesskey)),
							'access_key'=>rawurlencode(GibberishAES::enc(NApp::_GetMyAccessKey().NApp::_GetApiSeparator().time(),$taccesskey)),
							'params'=>rawurlencode($pparams)
						),
					'get_params'=>array('dbg'=>NApp::$debug)
				);
			$error = NULL;
			try {
				$response = self::CurlCall($lparams);
			} catch(Exception $e) {
				$error = $e->getMessage();
			}//END try
			if(is_array($response) && array_key_exists('error',$response)) {
				$error = $response['error'];
				$response = $response['result'];
			}//if(is_array($response) && array_key_exists('error',$response))
			if(!$error && $response===FALSE) { $error = 'Unknown API comunication error!'; }
			if(!$error) {
				/*if(NApp::$debug) {
					echo '<strong>API Call raw result ('.$rtype.'):</strong><br/>';
					var_dump($response); echo '<br/><br/>';
				}//if(NApp::$debug)*/
				$result_data = explode(NApp::_GetApiSeparator(),$response);
				if(NApp::$debug && FALSE) {
					echo '<strong>API Call non result output ('.$rtype.'):</strong><br/>';
					echo get_array_value($result_data,0).'<br/>'.get_array_value($result_data,2).'<br/><br/>';
				}//if(NApp::$debug)
				if(is_array($result_data) && count($result_data)>=2 && $result_data[1]) {
					$target['result'] = unserialize(GibberishAES::dec($result_data[1],NApp::_GetMyAccessKey()));
				} else {
					$target['result'] = FALSE;
				}//if(is_array($result_data) && count($result_data)>=2 && $result_data[1])
			} else {
				$target['result'] = FALSE;
			}//if(!$error)
			$target['error'] = $error;
			/*if(NApp::$debug) {
				echo '<br/><strong>API Call final result ('.$rtype.'):</strong><br/>';
				var_dump($target); echo '<br/><br/>';
			}//if(NApp::$debug)*/
			$result[] = $target;
			if(NApp::$debug && $error) { echo '<strong>Error:</strong><br/>'.$error.'<br/></br/>'; }
		}//END foreach
		if($singletarget) { return (is_array($result) && count($result)>0) ? $result[0] : FALSE; }
		return $result;
	}//END public static function Call
}//END class ApiDataProvider
?>