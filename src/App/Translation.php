<?php
/**
 * Class Translation file
 *
 * Helper class for translating application resources
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NETopes\Core\AppConfig;
use NETopes\Core\Data\DataProvider;
use NApp;
use NETopes\Core\AppException;
/**
 * Class Translation
 *
 * Helper class for translating application resources
 *
 * @package NETopes\Core\App
 */
class Translation {
	/**
	 * @var    array An array containing loaded translations data
	 * @access protected
	 * @static
	 */
	protected static $_LANGUAGES_STRINGS = [];
	/**
	 * description
	 *
	 * @param string $langcode
	 * @param bool   $loop
	 * @return array|null
	 * @access protected
	 * @static
	 */
	protected static function GetTranslationCacheFile($langcode,$loop = TRUE) {
		if(!$langcode) { return NULL; }
		if(NApp::GetCurrentNamespace()=='web') {
			$id_section = NApp::GetParam('id_section') ? NApp::GetParam('id_section') : 0;
			$id_zone = NApp::GetParam('id_zone') ? NApp::GetParam('id_zone') : 0;
			$cnamespace = 'site_strings';
			$lfile = 'lang_'.$langcode.'_'.$id_section.'_'.$id_zone.'.cache';
		} else {
			$id_section = NULL;
			$id_zone = NULL;
			$cnamespace = NApp::GetCurrentNamespace().'_strings';
			$lfile = 'lang_'.$langcode.'.cache';
		}//if(NApp::current_namespace=='web')
		$lpath = NApp::$app_public_path.'/templates/'.NApp::GetCurrentNamespace().NApp::current_section_folder().'/resources/';
		if(NApp::GetParam('translation_cache_is_dirty')==1 && file_exists($lpath.$lfile)) { unlink($lpath.$lfile); }
		if(!file_exists($lpath.$lfile)) {
			$tarray = NULL;
			try {
				$items = DataProvider::GetArray('System\Translations','GetTranslationsResources',[
					'section_id'=>$id_section,
					'zone_id'=>$id_zone,
					'for_label'=>$cnamespace,
					'lang_code'=>$langcode,
					'reset_dirty'=>NApp::GetParam('translation_cache_is_dirty'),
				]);
				$tarray = convert_db_array_to_tree($items,array('module','method','name','value'),FALSE);
			} catch(AppException $e) {
				$tarray = NULL;
			}//END try
			if(!is_array($tarray) || count($tarray)==0) { return NULL; }
			$fcontent = '<?php function GET_'.strtoupper($langcode).'_STRINGS() {'."\n";
			$fcontent .= 'return '.var_export($tarray,TRUE);
			$fcontent .= '; } ?>';
			if(file_put_contents($lpath.$lfile,$fcontent)===FALSE) { return NULL; }
		}//if(!file_exists($lpath.$lfile))
		require_once($lpath.$lfile);
		if(!function_exists('GET_'.strtoupper($langcode).'_STRINGS')) {
			if(!$loop) { return NULL; }
			unlink($lpath.$lfile);
			return static::GetTranslationCacheFile($langcode,FALSE);
		}//if(!function_exists('GET_TRANSLATIONS_'.strtoupper($langcode)))
		if(!is_array(static::$_LANGUAGES_STRINGS)) { static::$_LANGUAGES_STRINGS = []; }
		static::$_LANGUAGES_STRINGS[$langcode] = call_user_func('GET_'.strtoupper($langcode).'_STRINGS');
		return static::$_LANGUAGES_STRINGS[$langcode];
	}//END protected static function GetTranslationCacheFile
	/**
	 * Get resource translation value
	 *
	 * @param string|array $key
	 * @param null $langcode
	 * @param bool $echo
	 * @return string Translated value
	 * @access public
	 * @static
	 */
	public static function Get($key,$langcode = NULL,$echo = FALSE) {
		if(is_array($key)) {
			if(!array_key_exists('key',$key) || strlen($key['key'])==0) { return NULL; }
			$lkey = $key['key'];
			$lmodule = (array_key_exists('module',$key) && strlen($key['module'])>0) ? $key['module'] : '';
			$lmethod = (array_key_exists('method',$key) && strlen($key['method'])>0) ? $key['method'] : '';
		} else {
			$lkey = strtolower($key);
			$lmodule = '';
			$lmethod = '';
		}//if(is_array($key))
		$llang_code = (is_string($langcode) && strlen($langcode)) ? $langcode : NApp::_GetLanguageCode();
		if(!is_array(static::$_LANGUAGES_STRINGS) || !array_key_exists($llang_code,static::$_LANGUAGES_STRINGS) || !is_array(static::$_LANGUAGES_STRINGS[$llang_code])) { static::GetTranslationCacheFile($llang_code); }
		if(!is_array(static::$_LANGUAGES_STRINGS) || !count(static::$_LANGUAGES_STRINGS) || !array_key_exists($llang_code,static::$_LANGUAGES_STRINGS) || !is_array(static::$_LANGUAGES_STRINGS[$llang_code]) || !count(static::$_LANGUAGES_STRINGS[$llang_code]) || !array_key_exists($lmodule,static::$_LANGUAGES_STRINGS[$llang_code]) || !is_array(static::$_LANGUAGES_STRINGS[$llang_code][$lmodule]) || !array_key_exists($lmethod,static::$_LANGUAGES_STRINGS[$llang_code][$lmodule]) || !is_array(static::$_LANGUAGES_STRINGS[$llang_code][$lmodule][$lmethod]) || !array_key_exists($lkey,static::$_LANGUAGES_STRINGS[$llang_code][$lmodule][$lmethod])) {
			if(AppConfig::GetValue('auto_insert_missing_translations')) {
				try {
					if(NApp::GetCurrentNamespace()=='web') {
						$id_section = NApp::GetParam('id_section') ? NApp::GetParam('id_section') : NULL;
						$id_zone = NApp::GetParam('id_zone') ? NApp::GetParam('id_zone') : NULL;
						$cnamespace = 'site_strings';
					} else {
						$id_section = NULL;
						$id_zone = NULL;
						$cnamespace = NApp::GetCurrentNamespace().'_strings';
					}//if(NApp::GetCurrentNamespace()=='web')
					DataProvider::GetArray('System\Translations','SetBlankTranslationsResource',[
						'in_name'=>$lkey,
						'in_config_label'=>$cnamespace,
						'language_code'=>$llang_code,
						'section_id'=>$id_section,
						'zone_id'=>$id_zone,
						'in_module'=>strlen($lmodule) ? $lmodule : NULL,
						'in_method'=>strlen($lmethod) ? $lmethod : NULL,
					]);
				} catch(AppException $e) {
					NApp::Elog($e);
					NApp::Write2LogFile($e->getFullMessage(),'error');
				}//END try
			} else {
				NApp::Write2LogFile("|Module[{$lmodule}]|Method[{$lmethod}]|Key[{$lkey}]",'debug',NApp::$appPath.AppConfig::GetValue('logs_path')."/missing_translations_".NApp::GetCurrentNamespace()."_{$llang_code}.log");
			}//if(AppConfig::GetValue('auto_insert_missing_translations'))
			return "[{$lkey}]";
		}//if(...
		if($echo) { echo static::$_LANGUAGES_STRINGS[$llang_code][$lmodule][$lmethod][$lkey]; }
		return static::$_LANGUAGES_STRINGS[$llang_code][$lmodule][$lmethod][$lkey];
	}//END public static function Get
	/**
	 * Get label translation
	 *
	 * @param string $key
	 * @param null $langcode
	 * @param bool $echo
	 * @return string|null Translated value
	 * @access public
	 * @static
	 */
	public static function GetLabel(string $key,$langcode = NULL,$echo = FALSE) {
		if(!strlen($key)) { return NULL; }
		return static::Get('label_'.$key,$langcode,$echo);
	}//END public static function GetLabel
	/**
	 * Get button translation
	 *
	 * @param string $key
	 * @param null $langcode
	 * @param bool $echo
	 * @return string|null Translated value
	 * @access public
	 * @static
	 */
	public static function GetButton(string $key,$langcode = NULL,$echo = FALSE) {
		if(!strlen($key)) { return NULL; }
		return static::Get('button_'.$key,$langcode,$echo);
	}//END public static function GetButton
	/**
	 * Get title translation
	 *
	 * @param string $key
	 * @param null $langcode
	 * @param bool $echo
	 * @return string|null Translated value
	 * @access public
	 * @static
	 */
	public static function GetTitle(string $key,$langcode = NULL,$echo = FALSE) {
		if(!strlen($key)) { return NULL; }
		return static::Get('title_'.$key,$langcode,$echo);
	}//END public static function GetTitle
	/**
	 * Get message translation
	 *
	 * @param string $key
	 * @param null $langcode
	 * @param bool $echo
	 * @return string|null Translated value
	 * @access public
	 * @static
	 */
	public static function GetMessage(string $key,$langcode = NULL,$echo = FALSE) {
		if(!strlen($key)) { return NULL; }
		return static::Get('msg_'.$key,$langcode,$echo);
	}//END public static function GetMessage
	/**
	 * Get error translation
	 *
	 * @param string $key
	 * @param null $langcode
	 * @param bool $echo
	 * @return string|null Translated value
	 * @access public
	 * @static
	 */
	public static function GetError(string $key,$langcode = NULL,$echo = FALSE) {
		if(!strlen($key)) { return NULL; }
		return static::Get('error_'.$key,$langcode,$echo);
	}//END public static function GetError
	/**
	 * Get URLID translation
	 *
	 * @param string $key
	 * @param null $langcode
	 * @param bool $echo
	 * @return string|null Translated value
	 * @access public
	 * @static
	 */
	public static function GetUrlId(string $key,$langcode = NULL,$echo = FALSE) {
		if(!strlen($key)) { return NULL; }
		return static::Get('urlid_'.$key,$langcode,$echo);
	}//END public static function GetUrlId
}//END class Translation
?>