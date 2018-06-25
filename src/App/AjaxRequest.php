<?php
/**
 * AjaxRequest implementation class file
 *
 * This class extends PAF\AjaxRequest
 *
 * @package    NETopes\Modules
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use GibberishAES;
use PAF\AppConfig;
use PAF\AppException;
use NApp;
/**
 * AjaxRequest implementation class
 *
 * This class extends PAF\AjaxRequest
 *
 * @package  NETopes\Modules
 * @access   public
 */
class AjaxRequest extends \PAF\AjaxRequest {
	/**
	 * Generic ajax call
	 *
	 * @param        $window_name
	 * @param        $module
	 * @param        $method
	 * @param string $params
	 * @param string $target
	 * @param int    $non_custom
	 * @param int    $reset_session_params
	 * @return void
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function AjaxRequest($window_name,$module,$method,$params = NULL,$target = NULL,$non_custom = 0,$reset_session_params = 0) {
		if(!strlen($window_name)) { $this->ExecuteJs("window.name = '{$this->app->phash}'"); }
		try {
			$olduserid = $this->app->GetPageParam('user_id');
			$userid = $this->app->GetParam('user_id');
			if($olduserid && $userid!=$olduserid) {
				$this->app->SetPageParam('user_id',$userid);
				$this->ExecuteJs("window.location.href = '".$this->app->GetAppWebLink()."';");
			}//if($olduserid && $userid!=$olduserid)
			$this->app->SetPageParam('user_id',$userid);
			$o_params = new Params($params);
			$o_params->set('target',$target);
			$o_params->set('phash',$window_name);
			if($non_custom) {
				ModulesProvider::ExecNonCustom($module,$method,$o_params,(bool)$reset_session_params);
			} else {
				ModulesProvider::Exec($module,$method,$o_params,(bool)$reset_session_params);
			}//if($non_custom)
			if(strlen(AppConfig::app_areq_js_callback())) { $this->ExecuteJs(AppConfig::app_areq_js_callback()); }
		} catch(AppException $e) {
			\ErrorHandler::AddError($e);
		}//END try
	}//END public function AjaxRequest
	/**
	 * Generic ajax call for controls
	 *
	 * @param        $window_name
	 * @param        $controlhash
	 * @param        $method
	 * @param string $params
	 * @param string $control
	 * @param int    $viapost
	 * @return void
	 * @throws \PAF\AppException
	 * @access public
	 */
	public function ControlAjaxRequest($window_name,$controlhash,$method,$params = NULL,$control = NULL,$viapost = 0) {
		if(!strlen($window_name)) { $this->ExecuteJs("window.name = '{$this->app->phash}'"); }
		// \NApp::_Dlog($controlhash,'$controlhash');
		// \NApp::_Dlog($method,'$method');
		// \NApp::_Dlog($params,'$params');
		try {
			$olduserid = $this->app->GetPageParam('user_id');
			$userid = $this->app->GetParam('user_id');
			if($olduserid && $userid!=$olduserid) {
				$this->app->SetPageParam('user_id',$userid);
				$this->ExecuteJs("window.location.href = '".$this->app->GetAppWebLink()."';");
			}//if($olduserid && $userid!=$olduserid)
			$this->app->SetPageParam('user_id',$userid);
			if($viapost) {
				$lcontrol = strlen($control) ? unserialize(GibberishAES::dec($control,$controlhash)) : NULL;
			} else {
				$lcontrol = $this->app->GetPageParam($controlhash);
				$lcontrol = strlen($lcontrol)>0 ? unserialize($lcontrol) : NULL;
			}//if($viapost)
			if(!is_object($lcontrol) || !method_exists($lcontrol,$method)) { throw new AppException('Invalid class or method',E_ERROR,1); }
			$o_params = new Params($params);
			$o_params->set('output',TRUE);
			$o_params->set('phash',$window_name);
			$lcontrol->$method($o_params);
		} catch(\Exception $e) {
			\ErrorHandler::AddError($e);
		}//END try
	}//END public function ControlAjaxRequest
	/**
	 * Ajax call to logout user
	 *
	 * @param $window_name
	 * @param $namespace
	 * @return void
	 * @access public
	 */
	public function SessionLogout($window_name,$namespace) {
		$app_web_link = $this->app->GetAppWebLink();
		$this->ExecuteJs("window.location.href = '{$app_web_link}'");
	}//END public function SessionLogout
	/**
	 * Ajax call to set language
	 *
	 * @param $window_name
	 * @param $selected_lang
	 * @return void
	 * @access public
	 */
	public function AjaxSetLanguage($window_name,$selected_lang) {
		$alang = explode('^',$selected_lang);
		$old_lang = $this->app->GetLanguageCode();
		$this->app->SetPageParam('lang_code',$alang[1]);
		$this->app->SetPageParam('id_lang',$alang[0]);
		$this->ExecuteJs("window.location.href = window.location.href.toString().replace('/{$old_lang}/','/".$alang[1]."/');");
	}//END public function AjaxSetLanguage
}//END class AjaxRequest extends \PAF\AjaxRequest
?>