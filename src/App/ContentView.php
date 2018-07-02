<?php
/**
 * Web view base class file
 *
 * All templates views objects must implement this class.
 *
 * @package    NETopes\CMS
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NETopes\Core\App\ModulesProvider;
use NETopes\Core\Data\DataProvider;
use NApp;
use PAF\AppConfig;

/**
 * ContentView is the web view base class
 *
 * All templates views objects must implement this class.
 *
 * @package  NETopes\CMS
 * @access   public
 */
abstract class ContentView {
	protected $napp = NULL;
	protected $app_path = NULL;
	protected $app_public_path = NULL;
	protected $app_web_link = NULL;
	protected $theme_relative_path = NULL;
	protected $req_params = NULL;
	protected $website_data = NULL;
	protected $tracking_codes = NULL;
	public $body_class = NULL;
	/**
	 * Init method (is called at the end of the __construct method)
	 *
	 * @return void
	 * @access public
	 */
	public function Init() {}
	/**
	 * ContentView class constructor
	 *
	 * @param  array $params An array of params
	 * @return void
	 * @access public
	 */
	public function __construct($params = NULL) {
		global $napp;
		$this->napp = &$napp;
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) { $this->$k = $v; }
		}//if(is_array($params) && count($params))
		$this->Init();
	}//END public function __construct
	/**
	 * description
	 *
	 * @param        $name
	 * @param string $type
	 * @param array  $params Parameters object (instance of [Params])
	 * @return mixed
	 * @throws \PAF\AppException
	 */
	protected function ShowContent($name,$type = 'article',$params = []) {
		if(!strlen($name)) { return FALSE; }
		switch(strtolower($type)) {
			case 'widget':
				$params['name'] = $name;
				return ModulesProvider::Exec('WEB\Content\Content','ShowWidget',$params);
			case 'navigation':
				$params['name'] = $name;
				return ModulesProvider::Exec('WEB\Content\Content','ShowNavigation',$params);
			case 'pagination':
				$params['name'] = $name;
				return ModulesProvider::Exec('WEB\Content\Content','ShowPagination',$params);
			case 'language_selector':
				$params['name'] = $name;
				return ModulesProvider::Exec('WEB\Content\Content','ShowLanguageSelector',$params);
			case 'store':
				$params['name'] = $name;
				return ModulesProvider::Exec('WEB\Store\Store','ShowContent',$params);
			case 'products-categories':
				$params['name'] = $name;
				return ModulesProvider::Exec('WEB\Store\Store','ShowCategories',$params);
			case 'article':
			case 'content':
			default:
				$params['container'] = $name;
				return ModulesProvider::Exec('WEB\Content\Content','ShowContent',$params);
		}//END switch
	}//END protected function ShowContent
	/**
	 * description
	 *
	 * @param      $data
	 * @param null $base
	 * @param null $urltarget
	 * @return void
	 */
	public static function GetFormatedUrl(&$data,$base = NULL,&$urltarget = NULL) {
		if(!is_array($data) || !count($data)) { return NULL; }
		$urltarget = '';
		$result = strlen($base) ? $base : NApp::_GetAppWebLink();
		$urltype = get_array_param($data,'url_type',1,'is_numeric');
		switch($urltype) {
			case 2: //internal custom url
				$c_url = ltrim(get_array_param($data,'tr_custom_url',$data['custom_url'],'is_notempty_string'),'/');
				if(strpos($c_url,'[base_url]')!==FALSE) {
					$result = str_replace('[base_url]',NApp::_GetAppWebLink(NULL,NULL,TRUE),$c_url);
				} elseif(strpos($c_url,'[app_url]')!==FALSE) {
					$result = str_replace('[app_url]',NApp::_GetAppWebLink(NULL,'app'),$c_url);
				} else {
					$result .= $c_url;
				}//if(strpos($c_url,'[base_url]')!==FALSE)
				break;
			case 3: //external url
				$urltarget = $data['url_open_new_page']==1 ? ' target="_blank"' : '';
				$result = get_array_param($data,'tr_custom_url',$data['custom_url'],'is_notempty_string');
				if(strpos(strtolower($result),'http://')===FALSE && strpos(strtolower($result),'https://')===FALSE && strpos(strtolower($result),'mailto:')===FALSE && strpos(strtolower($result),'tel:')===FALSE) { $result = 'http://'.$result; }
				break;
			case 1:
			default: // internal url
				if(get_array_param($data,'isflink',0,'is_numeric')==1 && AppConfig::app_mod_rewrite()) {
					$result .= (strlen($data['tr_url_id_prefix']) ? $data['tr_url_id_prefix'].'/' : '').(strlen($data['tr_url_id']) ? $data['tr_url_id'].'/' : '');
				} else {
					$has_params = FALSE;
					if(strlen(get_array_param($data,'module','','is_string'))) {
						$result .= ($has_params ? '&' : '?').'module='.$data['module'];
						$has_params = TRUE;
					}//if(strlen(get_array_param($data,'module','','is_string')))
					if(strlen(get_array_param($data,'action','','is_string'))) {
						$result .= ($has_params ? '&' : '?').'a='.$data['action'];
						$has_params = TRUE;
					}//if(strlen(get_array_param($data,'action','','is_string')))
					$result .= get_array_param($data,'id',NULL,'is_not0_numeric') ? ($has_params ? '&' : '?').'page='.$data['id'] : '';
				}//if(get_array_param($data,'isflink',0,'is_numeric')==1 && AppConfig::app_mod_rewrite())
				break;
		}//END switch
		$urlparams = trim(get_array_param($data,'params','','is_string'),'?&');
		if(strlen($urlparams)) { $result .= (strpos($result,'?')===FALSE ? '?' : '&').$urlparams; }
		$urlanchor = get_array_param($data,'anchor','','is_string');
		if(strlen($urlanchor)) { $result .= '#'.trim($urlanchor,'#'); }
		return $result;
	}//public static function GetFormatedUrl
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	public function FormatUrl(&$data,$base = NULL,&$urltarget = NULL) {
		return self::GetFormatedUrl($data,$base,$urltarget);
	}//END public function FormatUrl
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	public static function GetFormatedCustomUrl(&$data,$base = NULL) {
		if(!is_array($data) || !count($data)) { return NULL; }
		$curl = ltrim(get_array_param($data,'tr_custom_url',$data['custom_url'],'is_notempty_string'),'/');
		if(!strlen($curl)) { return NULL; }
		if(strpos(strtolower($curl),'http://')===FALSE && strpos(strtolower($curl),'https://')===FALSE && strpos(strtolower($curl),'mailto:')===FALSE && strpos(strtolower($curl),'tel:')===FALSE) {
			$result = (strlen($base) ? $base : NApp::_GetAppWebLink()).$curl;
		} else {
			$result = $curl;
		}//if(...
		$urlparams = trim(get_array_param($data,'params','','is_string'),'?&');
		if(strlen($urlparams)) { $result .= (strpos($result,'?')===FALSE ? '?' : '&').$urlparams; }
		$urlanchor = get_array_param($data,'anchor','','is_string');
		if(strlen($urlanchor)) { $result .= '#'.trim($urlanchor,'#'); }
		return $result;
	}//END public static function GetFormatedCustomUrl
	/**
	  * description
	  *
	  * @param object|null $params Parameters object (instance of [Params])
	  * @return void
	  */
	public function FormatCustomUrl(&$data,$base = NULL) {
		return self::GetFormatedCustomUrl($data,$base);
	}//END public function FormatCustomUrl
	/**
	 * Get tracking codes (E.g. Google Analytics)
	 *
	 * @param  string $location Code location (head/body)
	 * @return string Returns tracking codes string
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function GetTrackingCodes($location) {
		if(!is_string($location) || !strlen($location)) { return NULL; }
		if(!$this->tracking_codes) {
			$this->tracking_codes = DataProvider::GetArray('Cms\Cms','GetRegistryOptions',array('registry_id'=>$this->napp->GetParam('id_registry'),'for_type'=>1));
		}//if(!$this->tracking_codes)
		if(!is_array($this->tracking_codes) || !count($this->tracking_codes)) { return NULL; }
		$data = '';
		foreach($this->tracking_codes as $o) {
			if($o['html']==1 && $o['location']==$location && strlen($o['value'])) {
				$data .= $o['value']."\n";
				if($o['name']=='google_analytics') {
					$ga_ec = preg_match('/ga\(\s?[\'\"]require[\'\"]\s?,\s?[\'\"]ec[\'\"]\s?\);/',$o['value']);
					$ga_pageview = preg_match('/ga\(\s?[\'\"]send[\'\"]\s?,\s?[\'\"]pageview[\'\"]\s?\);/',$o['value']);
					NApp::_SetGlobalVar('send_ga_pageview',!$ga_pageview);
					NApp::_SetPageParam('ga_ecommerce',$ga_ec);
				}//if($o['name']=='google_analytics')
			}//if($o['html']==1 && $o['location']==$location && strlen($o['value']))
		}//END foreach
		return $data;
	}//public function GetTrackingCodes
	/**
	 * Append Google Analytics scripts to 'dynamic_js_scripts' global variable
	 *
	 * @param  string $type Data type
	 * @param  array $params Parameters array
	 * @param  bool $output_only Flag for setting 'dynamic_js_scripts' global variable
	 * or returning data only
	 * @param  bool $ajax Flag indicating ajax action (send event action will be added)
	 * @return string
	 * @access public
	 * @static
	 */
	public static function SetGAData($type,$params = [],$output_only = FALSE,$ajax = FALSE) {
		if(!NApp::_GetPageParam('ga_ecommerce')){ return NULL; }
		$data = '';
		switch($type) {
			case 'addProduct':
				$items = get_array_param($params,'items',[],'is_array');
				if(!count($items)) { return NULL; }
				$data = "if(typeof(ga)!='undefined') { ";
				foreach($items as $v) {
					$pid = get_array_param($v,'product_code',get_array_param($v,'id_product','','is_string'),'is_notempty_string');
					if(!strlen($pid)) { continue; }
					$price = number_format(get_array_param($v,'final_price',0,'is_numeric'),get_array_param($v,'price_decimal_no',2,'is_numeric'),'.','');
					$quantity = number_format(get_array_param($v,'quantity',0,'is_numeric'),get_array_param($v,'quantity_decimal_no',0,'is_numeric'),'.','');
					$data .= "
ga('ec:addProduct', {
	'id': '{$pid}',
	'name': '".get_array_param($v,'product_name','','is_string')."',
	'category': '".get_array_param($v,'product_category','','is_string')."',
	'brand': '".get_array_param($v,'brand','','is_string')."',
	'price': '{$price}',
	'quantity': {$quantity},
});
";
				}//END foreach
				$data .= " }";
				break;
			case 'purchase':
				$doc_ref = get_array_param($params,'document','','is_string','document_ref');
				if(!strlen($doc_ref)) { return NULL; }
				$items = get_array_param($params,'items',[],'is_array');
				if(count($items)) { $data .= self::SetGAData('addProduct',array('items'=>$items),TRUE); }
				$tvalue = get_array_param($params,'document',0,'is_numeric','tvalue');
				$payment_fee = get_array_param($params,'document',0,'is_numeric','final_payment_fee');
				$transport_value = get_array_param($params,'document',0,'is_numeric','final_transport_value');
				$doc_value = number_format($tvalue+$payment_fee+$transport_value,2,'.','');
				$payment_fee = number_format($payment_fee,2,'.','');
				$transport_value = number_format($transport_value,2,'.','');
				$data .= "
if(typeof(ga)!='undefined') { ga('ec:setAction', 'purchase', {
	'id': '{$doc_ref}',
	'revenue': '{$doc_value}',
	'tax': '{$payment_fee}',
	'shipping': '{$transport_value}',
}); }
";
				if($ajax) {
					$pageview = get_array_param($params,'pageview','','is_string');
					if(strlen($pageview)) {
						$pageview = ltrim($pageview,'/');
						$data .= "
if(typeof(ga)!='undefined') {
	ga('set', 'page', '/{$pageview}');
	ga('send', 'pageview');
}
";
					} else {
						$data .= "
if(typeof(ga)!='undefined') { ga('send', 'event', 'UX', 'click', 'Purchase'); }
";
					}//if(strlen($pageview))
				}//if($ajax)
				break;
			case 'click':
				$items = get_array_param($params,'items',[],'is_array');
				if(!count($items)) { return NULL; }
				$data .= self::SetGAData('addProduct',array('items'=>$items),TRUE);
				$list = get_array_param($params,'list','','is_string');
				$data .= "
if(typeof(ga)!='undefined') { ga('ec:setAction', 'click', {".(strlen($list) ? " 'list': '{$list}' " : '')."}); }
";
				if($ajax) {
					$data .= "
if(typeof(ga)!='undefined') { ga('send', 'event', 'UX', 'click', 'Product click'); }
";
				}//if($ajax)
				break;
			case 'detail':
				$items = get_array_param($params,'items',[],'is_array');
				if(!count($items)) { return NULL; }
				$data .= self::SetGAData('addProduct',array('items'=>$items),TRUE);
				$list = get_array_param($params,'list','','is_string');
				$data .= "
if(typeof(ga)!='undefined') { ga('ec:setAction', 'detail', {".(strlen($list) ? " 'list': '{$list}' " : '')."}); }
";
				break;
			case 'add':
				$items = get_array_param($params,'items',[],'is_array');
				if(!count($items)) { return NULL; }
				$data .= self::SetGAData('addProduct',array('items'=>$items),TRUE);
				$data .= "
if(typeof(ga)!='undefined') { ga('ec:setAction', 'add'); }
";
				if($ajax) {
					$data .= "
if(typeof(ga)!='undefined') { ga('send', 'event', 'UX', 'click', 'Add to cart'); }
";
				}//if($ajax)
				break;
			case 'remove':
				$items = get_array_param($params,'items',[],'is_array');
				if(!count($items)) { return NULL; }
				$data .= self::SetGAData('addProduct',array('items'=>$items),TRUE);
				$data .= "
if(typeof(ga)!='undefined') { ga('ec:setAction', 'remove'); }
";
				if($ajax) {
					$data .= "
if(typeof(ga)!='undefined') { ga('send', 'event', 'UX', 'click', 'Remove from cart'); }
";
				}//if($ajax)
				break;
			case 'checkout':
			case 'checkout_option':
				$step = get_array_param($params,'step',0,'is_numeric');
				if($step<=0) { return NULL; }
				$step_title = get_array_param($params,'step_title','','is_string');
				$option = get_array_param($params,'option','','is_string');
				$data .= "
if(typeof(ga)!='undefined') { ga('ec:setAction', 'checkout', {
	'step': {$step}".(strlen($option) ? ",
	'option': '{$option}'" : '')."
}); }
";
				if($ajax) {
					$data .= "
if(typeof(ga)!='undefined') { ga('send', 'event', 'Checkout', 'Step', '{$step_title}'); }
";
				}//if($ajax)
				break;
			case 'checkout':
				$step = get_array_param($params,'step',0,'is_numeric');
				$option = get_array_param($params,'option','','is_string');
				if($step<=0 || !strlen($option)) { return NULL; }
				$step_title = get_array_param($params,'step_title','','is_string');
				$data .= "
if(typeof(ga)!='undefined') { ga('ec:setAction', 'checkout_option', {
	'step': {$step},
	'option': '{$option}'
}); }
";
				if($ajax) {
					$data .= "
if(typeof(ga)!='undefined') { ga('send', 'event', 'Checkout', 'Option', '{$step_title}'); }
";
				}//if($ajax)
				break;
			default:
				return NULL;
		}//END switch
		// NApp::_Dlog($data,$type.'>>$data');
		if(!strlen($data)) { return NULL; }
		if($output_only) { return $data; }
		$dynamic_js_scripts = NApp::_GetGlobalVar('dynamic_js_scripts',[],'is_array');
		$dynamic_js_scripts[] = $data;
		NApp::_SetGlobalVar('dynamic_js_scripts',$dynamic_js_scripts);
	}//END public static function SetGAData
	/**
	 * Set new dynamic javascript to be executed at the end of the current request
	 *
	 * @param  string $value Javascript script to be executed (as string)
	 * @param  boolean $html If TRUE, $value will be outputted directly, not inside script tag
	 * @return void
	 * @access public
	 * @static
	 */
	public static function AddNewDynamicScript($value,$html = FALSE) {
		if(!is_string($value) || !strlen($value)) { return; }
		$dynamic_js_scripts = NApp::_GetGlobalVar('dynamic_js_scripts',[],'is_array');
		$dynamic_js_scripts[] = [($html===TRUE || $html==1 ? 'html' : 'js')=>$value];
		NApp::_SetGlobalVar('dynamic_js_scripts',$dynamic_js_scripts);
	}//END public static function AddNewDynamicScript
	/**
	 * Get formatted dynamic javascript
	 *
	 * @return string Returns scripts to be outputted to the page
	 * @access public
	 * @static
	 */
	public static function GetDynamicScripts() {
		$scripts = NApp::_GetGlobalVar('dynamic_js_scripts',[],'is_array');
		$send_ga_pageview = NApp::_GetGlobalVar('send_ga_pageview',FALSE,'bool');
		if(!count($scripts) && !$send_ga_pageview) { return NULL; }
		$html_data = '';
		$data = '';
		foreach($scripts as $s) {
			if(is_array($s)) {
				$d_js = get_array_param($s,'js','','is_string');
				$d_html = get_array_param($s,'html','','is_string');
				if(strlen($d_js)) { $data .= $d_js."\n"; }
				if(strlen($d_html)) { $html_data .= $d_html."\n"; }
			} elseif(is_string($s) && strlen($s)) {
				$data .= $s."\n\n";
			}//if(is_array($s))
		}//END foreach
		if($send_ga_pageview) { $data .= "\tif(typeof(ga)!='undefined') { ga('send', 'pageview'); }\n"; }
		return $html_data.'<script type="text/javascript">'."\n".$data."\n".'</script>'."\n";
	}//END public static function GetDynamicScripts

	abstract public function HeadCssInclude();
	abstract public function HeadJsInclude();
	abstract public function ShowHeader();
	abstract public function ShowMain();
	abstract public function ShowFooter();
	abstract public function ShowEnd();
}//END abstract class ContentView
?>