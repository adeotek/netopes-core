<?php
/**
 * NETopes helper functions file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
/**
 * Get short class name (without namespace)
 *
 * @param $class
 * @return  string Short class name
 */
function get_class_basename($class) {
    $fname = explode('\\',(is_object($class) ? get_class($class) : $class));
    return array_pop($fname);
}//END function get_class_basename
/**
 * SQL-like coalesce function
 *
 * @return  bool Returns first non-null argument
 */
function coalesce() {
    $params = func_get_args();
    foreach($params as $p) { if(isset($p)) { return $p; } }
    return NULL;
}//END function coalesce
/**
 * SQL-like coalesce function for strings
 * (empty string is considered null)
 *
 * @param   mixed [ $arg ] Any number of arguments to be coalesced
 * Obs. Each argument will be checked after trim
 * @return  bool Returns first non-null, non-empty argument
 */
function str_coalesce() {
    $params = func_get_args();
    foreach($params as $p) {
        if(isset($p) && !is_string($p)) { continue; }
        $val = isset($p) ? trim($p) : '';
        if(strlen($val)) { return $p; }
    }//END foreach
    return NULL;
}//END function str_coalesce
/**
 * Echo string after applying htmlentities to it
 *
 * @param   string $string String to echo
 * @return  bool Returns TRUE on success or FALSE if $string is of an unsupporten type
 */
function secho($string) {
    if(is_array($string) || is_object($string)) { return FALSE; }
    echo htmlentities($string);
    return TRUE;
}//END function secho
/**
 * File unlink with check if file exists
 *
 * @param   string $file File to unlink
 * @return  bool Returns TRUE on success or FALSE on error or if the file doesn't exist
 */
function sunlink($file) {
    if(!is_string($file) || !strlen($file) || !file_exists($file)) { return FALSE; }
    try { unlink($file); return TRUE; } catch(Exception $e) { return FALSE; }
}//END function sunlink
/**
 * Check if a string contains one or more strings.
 *
 * @param   string $haystack The string to be searched.
 * @param   mixed $needle The string to be searched for.
 * To search for multiple strings, needle can be an array containing this strings.
 * @param   integer $offset The offset from which the search to begin (default 0, the begining of the string).
 * @param   bool $all_array Used only if the needle param is an array, sets the search type:
 * * if is set TRUE the function will return TRUE only if all the strings contained in needle are found in haystack,
 * * if is set FALSE (default) the function will return TRUE if any (one, several or all)
 * of the strings in the needle are found in haystack.
 * @return  bool Returns TRUE if needle is found in haystack or FALSE otherwise.
 */
function str_contains($haystack,$needle,$offset = 0,$all_array = FALSE) {
    if(is_array($needle)) {
        if(!$haystack || count($needle)==0) { return FALSE; }
        foreach($needle as $n) {
            $tr = strpos($haystack,$n,$offset);
            if(!$all_array && $tr!==FALSE) { return TRUE; }
            if($all_array && $tr===FALSE) { return FALSE; }
        }//foreach($needle as $n)
        return $all_array;
    }//if(is_array($needle))
    return strpos($haystack,$needle,$offset)!==FALSE;
}//END function str_contains
/**
 * Remove all instances of white-spaces from both ends of the string,
 * as well as remove duplicate white-space characters inside the string
 *
 * @param string $input Subject string
 * @param null   $what Optional undesired characters to be replaced
 * @param string $with Undesired characters replacement
 * @return string Returns processed string
 */
function trim_all($input,$what = NULL,$with = ' ') {
    if($what===NULL) {
        //  Character      Decimal      Use
        //  "\0"            0           Null Character
        //  "\t"            9           Tab
        //  "\n"           10           New line
        //  "\x0B"         11           Vertical Tab
        //  "\r"           13           New Line in Mac
        //  " "            32           Space
        $what = "\\x00-\\x20"; // all white-spaces and control chars
    }//if($what===NULL)
    return trim(preg_replace("/[".$what."]+/",$with,$input),$what);
}//END function trim_all
/**
 * Replace last occurrence of a substring
 *
 * @param string $search Substring to be replaced
 * @param string $replace Replacement string
 * @param string $str String to be processed
 * @return string Returns processed string
 */
function str_replace_last($search,$replace,$str) {
    if(($pos = strrpos($str,$search))===FALSE) { return $str; }
    return substr_replace($str,$replace,$pos,strlen($search));
}//END function str_replace_last
/**
 * Normalize string
 *
 * @param string $input String to be normalized
 * @param bool $replace_diacritics Replace diacritics with ANSI corespondent (default TRUE)
 * @param bool $html_entities_decode Decode html entities (default TRUE)
 * @param bool $trim Trim beginning/ending white spaces (default TRUE)
 * @return string Returns processed string
 */
function normalize_string($input,$replace_diacritics = TRUE,$html_entities_decode = TRUE,$trim = TRUE) {
    if(!is_string($input)) { return NULL; }
    if(!strlen($input)) { return $input; }
    $diacritics = [
        'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ț'=>'t','Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ț'=>'T',
        'á'=>'a','Á'=>'A','à'=>'a','À'=>'A','å'=>'a','Å'=>'A','ã'=>'a','Ã'=>'A','ą'=>'a','Ą'=>'A','ā'=>'a','Ā'=>'A','ä'=>'ae','Ä'=>'AE','æ'=>'ae','Æ'=>'AE','ḃ'=>'b','Ḃ'=>'B','ć'=>'c','Ć'=>'C','ĉ'=>'c','Ĉ'=>'C','č'=>'c','Č'=>'C','ċ'=>'c','Ċ'=>'C','ç'=>'c','Ç'=>'C','ď'=>'d','Ď'=>'D','ḋ'=>'d','Ḋ'=>'D','đ'=>'d','Đ'=>'D','ð'=>'dh','Ð'=>'Dh','é'=>'e','É'=>'E','è'=>'e','È'=>'E','ĕ'=>'e','Ĕ'=>'E','ê'=>'e','Ê'=>'E','ě'=>'e','Ě'=>'E','ë'=>'e','Ë'=>'E','ė'=>'e','Ė'=>'E','ę'=>'e','Ę'=>'E','ē'=>'e','Ē'=>'E','ḟ'=>'f','Ḟ'=>'F','ƒ'=>'f','Ƒ'=>'F','ğ'=>'g','Ğ'=>'G','ĝ'=>'g','Ĝ'=>'G','ġ'=>'g','Ġ'=>'G','ģ'=>'g','Ģ'=>'G','ĥ'=>'h','Ĥ'=>'H','ħ'=>'h','Ħ'=>'H','í'=>'i','Í'=>'I','ì'=>'i','Ì'=>'I','ï'=>'i','Ï'=>'I','ĩ'=>'i','Ĩ'=>'I','į'=>'i','Į'=>'I','ī'=>'i','Ī'=>'I','ĵ'=>'j','Ĵ'=>'J','ķ'=>'k','Ķ'=>'K','ĺ'=>'l','Ĺ'=>'L','ľ'=>'l','Ľ'=>'L','ļ'=>'l','Ļ'=>'L','ł'=>'l','Ł'=>'L','ṁ'=>'m','Ṁ'=>'M','ń'=>'n','Ń'=>'N','ň'=>'n','Ň'=>'N','ñ'=>'n','Ñ'=>'N','ņ'=>'n','Ņ'=>'N','ó'=>'o','Ó'=>'O','ò'=>'o','Ò'=>'O','ô'=>'o','Ô'=>'O','ő'=>'o','Ő'=>'O','õ'=>'o','Õ'=>'O','ø'=>'oe','Ø'=>'OE','ō'=>'o','Ō'=>'O','ơ'=>'o','Ơ'=>'O','ö'=>'oe','Ö'=>'OE','ṗ'=>'p','Ṗ'=>'P','ŕ'=>'r','Ŕ'=>'R','ř'=>'r','Ř'=>'R','ŗ'=>'r','Ŗ'=>'R','ś'=>'s','Ś'=>'S','ŝ'=>'s','Ŝ'=>'S','š'=>'s','Š'=>'S','ṡ'=>'s','Ṡ'=>'S','ş'=>'s','Ş'=>'S','ß'=>'SS','ť'=>'t','Ť'=>'T','ṫ'=>'t','Ṫ'=>'T','ţ'=>'t','Ţ'=>'T','ŧ'=>'t','Ŧ'=>'T','ú'=>'u','Ú'=>'U','ù'=>'u','Ù'=>'U','ŭ'=>'u','Ŭ'=>'U','û'=>'u','Û'=>'U','ů'=>'u','Ů'=>'U','ű'=>'u','Ű'=>'U','ũ'=>'u','Ũ'=>'U','ų'=>'u','Ų'=>'U','ū'=>'u','Ū'=>'U','ư'=>'u','Ư'=>'U','ü'=>'ue','Ü'=>'UE','ẃ'=>'w','Ẃ'=>'W','ẁ'=>'w','Ẁ'=>'W','ŵ'=>'w','Ŵ'=>'W','ẅ'=>'w','Ẅ'=>'W','ý'=>'y','Ý'=>'Y','ỳ'=>'y','Ỳ'=>'Y','ŷ'=>'y','Ŷ'=>'Y','ÿ'=>'y','Ÿ'=>'Y','ź'=>'z','Ź'=>'Z','ž'=>'z','Ž'=>'Z','ż'=>'z','Ż'=>'Z','þ'=>'th','Þ'=>'Th','µ'=>'u','а'=>'a','А'=>'a','б'=>'b','Б'=>'b','в'=>'v','В'=>'v','г'=>'g','Г'=>'g','д'=>'d','Д'=>'d','е'=>'e','Е'=>'E','ё'=>'e','Ё'=>'E','ж'=>'zh','Ж'=>'zh','з'=>'z','З'=>'z','и'=>'i','И'=>'i','й'=>'j','Й'=>'j','к'=>'k','К'=>'k','л'=>'l','Л'=>'l','м'=>'m','М'=>'m','н'=>'n','Н'=>'n','о'=>'o','О'=>'o','п'=>'p','П'=>'p','р'=>'r','Р'=>'r','с'=>'s','С'=>'s','т'=>'t','Т'=>'t','у'=>'u','У'=>'u','ф'=>'f','Ф'=>'f','х'=>'h','Х'=>'h','ц'=>'c','Ц'=>'c','ч'=>'ch','Ч'=>'ch','ш'=>'sh','Ш'=>'sh','щ'=>'sch','Щ'=>'sch','ъ'=>'','Ъ'=>'','ы'=>'y','Ы'=>'y','ь'=>'','Ь'=>'','э'=>'e','Э'=>'e','ю'=>'ju','Ю'=>'ju','я'=>'ja','Я'=>'ja'
    ];
    $result = $input;
    if($html_entities_decode) { $result = html_entity_decode($result,ENT_QUOTES | ENT_HTML5); }
    if($replace_diacritics) { $result = str_replace(array_keys($diacritics),array_values($diacritics),$result); }
    if($trim) { $result = trim($result); }
    return $result;
}//END function normalize_string
/**
 * Recursive changes the case of all keys in an array
 *
 * @param array $input
 * @param int   $keysCase
 * @return array
 */
function array_change_key_case_recursive(array $input,int $keysCase = CASE_LOWER): array
{
    return array_map(function($item) use ($keysCase) {
        if(is_array($item)) { $item = array_change_key_case_recursive($item,$keysCase); }
        return $item;
    },array_change_key_case($input,$keysCase));
}//END array_change_key_case_recursive
/**
 * Array merge with overwrite option (the 2 input arrays remains untouched).
 * The second array will overwrite the first.
 *
 * @param   array $arr1 First array to merge
 * @param   array $arr2 Second array to merge
 * @param   bool  $overwrite Overwrite sitch: TRUE with overwrite (default), FALSE without overwrite
 * @param   array $initial_arr2
 * @return  array|bool Returns the merged array or FALSE if one of the arr arguments is not an array
 */
function custom_array_merge($arr1,$arr2,$overwrite = TRUE,$initial_arr2 = NULL) {
    if(!is_array($arr1) || !is_array($arr2)) { return NULL; }
    if(!is_array($arr1)) { return $arr2; }
    if(!is_array($arr2)) { return $arr1; }
    $result = $arr1;
    foreach($arr2 as $k=>$v) {
        $i_arr = is_array($initial_arr2) && array_key_exists($k,$initial_arr2) ? $initial_arr2[$k] : NULL;
        if($i_arr && $v===$i_arr) { continue; }
        if(array_key_exists($k,$result)) {
            if(is_array($result[$k]) && is_array($v)) {
                $result[$k] = custom_array_merge($result[$k],$v,$overwrite,$i_arr);
            } else {
                if($overwrite===TRUE) { $result[$k] = $v; }
            }//if(is_array($result[$k]) && is_array($v))
        } else {
            $result[$k] = $v;
        }//if(array_key_exists($k,$result))
    }//END foreach
    if(is_array($initial_arr2) && count($initial_arr2)) {
        foreach(array_diff_key($initial_arr2,$arr2) as $k=>$v) { unset($result[$k]); }
    }//if(is_array($initial_arr2) && count($initial_arr2))
    return $result;
}//END function custom_array_merge
/**
 * This returns the element from certain level of the backtrace stack.
 *
 * @param   integer $step The backtrace step index to be returned, starting from 0 (default 1)
 * @param   string $param Type of the return.
 * Values can be: "function" and "class" for returning full array of the specified step
 * or "array" and empty string for returning an array containing only the name of the function/method
 * and the  class name (if there is one) of the specified step.
 * @return  array The full array or an array containing function/method and class names from the specified stop.
 */
function call_back_trace(int $step = 1,?string $param = 'function') {
    $result = [];
    $trdata = debug_backtrace();
    if($step<0 || !array_key_exists($step,$trdata)) { return $result; }
    $lstep = $step + 1;
    switch(strtolower($param)) {
        case 'function':
        case 'class':
            $result = array_key_exists($param,$trdata[$lstep]) ? $trdata[$lstep][$param] : '';
            break;
        case 'array':
            $result = array(
                    'function'=>(array_key_exists('function',$trdata[$lstep]) ? $trdata[$lstep]['function'] : ''),
                    'class'=>(array_key_exists('class',$trdata[$lstep]) ? $trdata[$lstep]['class'] : ''),
                );
            break;
        case 'full':
            $result = $trdata[$lstep];
            break;
        default:
            $result = (array_key_exists('class',$trdata[$lstep]) ? $trdata[$lstep]['class'].'::' : '').(array_key_exists('function',$trdata[$lstep]) ? $trdata[$lstep]['function'] : '').(array_key_exists('file',$trdata[$lstep]) ? ' in file ['.$trdata[$lstep]['file'].']' : '').(array_key_exists('line',$trdata[$lstep]) ? ' on line ['.$trdata[$lstep]['line'].']' : '');
            break;
    }//END switch
    return $result;
}//END function call_back_trace
/**
 * Convert string from unknown character set to UTF-8
 *
 * @param      string $value The string to be converted
 * @return     string Returns the converted string
 * @access     public
 */
function custom_utf8_encode($value) {
    $enc = mb_detect_encoding($value,mb_detect_order(),TRUE);
    if(strtoupper($enc)=='UTF-8' || !function_exists('iconv')) { return $value; }
    return iconv($enc,'UTF-8',$value);
}//END function custom_utf8_encode
/**
 * String explode function based on standard php explode function.
 * Explode on two levels to generate a table-like array.
 *
 * @param   string $str The string to be exploded.
 * @param   string $rsep The string used as row separator.
 * @param   string $csep The string used as column separator.
 * @param   string $ksep The string used as column key-value separator.
 * @param int      $keys_case
 * @return  array The exploded multi-level array.
 */
function explode_to_table($str,$rsep = '|:|',$csep = ']#[',$ksep = NULL,$keys_case = CASE_LOWER) {
    $result = [];
    if(!is_string($str) || !strlen($str) || !is_string($rsep) || !strlen($rsep)
            || (isset($csep) && (!is_string($csep) || !strlen($csep)))
        ) { return $result; }
    foreach(explode($rsep,$str) as $row) {
        if(!strlen($row)) { continue; }
        if(!$csep) {
            $result[] = $row;
            continue;
        }//if(!$csep)
        $r_arr = [];
        foreach(explode($csep,$row) as $col) {
            if(!strlen($col)) { continue; }
            if(!is_string($ksep) || !strlen($ksep)) {
                $r_arr[] = $col;
                continue;
            }//if(!is_string($ksep) || !strlen($ksep))
            $c_kv = explode($ksep,$col);
            if(count($c_kv)!=2) {
                $r_arr[] = $col;
            } else {
                if(is_numeric($keys_case)) {
                    $r_arr[($keys_case==CASE_UPPER ? strtoupper($c_kv[0]) : strtolower($c_kv[0]))] = $c_kv[1];
                } else {
                    $r_arr[$c_kv[0]] = $c_kv[1];
                }//if(is_numeric($keys_case))
            }//if(count($c_kv)!=2)
        }//END foreach
        $result[] = $r_arr;
    }//END foreach
    return $result;
}//END function explode_to_table
/**
 * String explode function based on standard php explode function.
 * After exploding the string, for each non-numeric element, all leading and trailing spaces will be trimmed.
 *
 * @param   string $separator The string used as separator.
 * @param   string $str The string to be exploded.
 * @return  array The exploded and trimed string as array.
 */
function texplode($separator,$str) {
    $vals = array();
    foreach(explode($separator,$str) as $val) {
        if(is_numeric($val)) {
            $vals[] = $val;
        } elseif(strlen($val)>0) {
            $vals[] = trim($val);
        }//if(is_numeric($val))
    }//foreach(explode($separator,$str) as $val)
    return $vals;
}//END function texplode
/**
 * Eliminate last N folders from a path.
 *
 * @param   string $path The path to be processed.
 * @param   integer $no The number of folders to be removed from the end of the path (default 1).
 * @return  string The processed path.
 */
function up_in_path($path,$no = 1) {
    $result = $path;
    for($i=0; $i<$no; $i++) {
        $result = str_replace('/'.basename($result),'',$result);
    }//for($i=0; $i<$no; $i++)
    return $result;
}//END function up_in_path
/**
 * Replaces all url not accepted characters with minus character (-)
 *
 * @param   string $string String to be processed.
 * @return  string The processed string.
 */
function str_to_url($string){
    return trim(str_replace(array('--','~~','~',','),'-',preg_replace('/(\W)/','-',trim($string))),'-');
}//END function str_to_url
/**
 * Converts a string to float
 * (if variable is not a string, is null or is empty, 0 is returned)
 *
 * @param   string $var String to be converted to float
 * @return  string Returns float value or 0.
 */
function custom_floatval($var) {
    if(is_null($var) || !is_string($var)) { return 0; }
    if(is_numeric($var)) { return floatval($var); }
    $lvar = preg_replace("/[^-0-9\.\,]/",'',$var);
    if(strlen($lvar)==0) { return 0; }
    if(substr_count($lvar,',')>0 && substr_count($lvar,'.')==0) {
        $lvar = str_replace(',',(substr_count($lvar,',')==1 ? '.' : ''),$lvar);
    } elseif(substr_count($lvar,',')==0 && substr_count($lvar,'.')>0) {
        $lvar = str_replace('.',(substr_count($lvar,'.')==1 ? '.' : ''),$lvar);
    } else {
        if(strrpos($lvar,'.')<strrpos($lvar,',')) {
            $lvar = str_replace(',','',substr($lvar,0,(-1)*(strrpos($lvar,'.')+1)));
        } else {
            $lvar = str_replace('.','',substr($lvar,0,(-1)*(strrpos($lvar,',')+1)));
        }//if(strrpos($lvar,'.')<strrpos($lvar,','))
    }//if(substr_count($lvar,',')>0 && substr_count($lvar,'.')==0)
    return floatval($lvar);
}//END function custom_floatval
/**
 * Check if variable is a collection (array or object with toArray() method)
 *
 * @param   mixed $value Variable to be validated
 * @param   bool $check_if_empty If TRUE also checks if the collection is empty
 * @return  bool Returns true if is array or collection
 */
function is_collection($value,$check_if_empty = TRUE) {
    if(is_array($value)) { return $check_if_empty ? count($value)>0 : TRUE; }
    if(is_object($value) && method_exists($value,'toArray')) {
        return $check_if_empty ? (method_exists($value,'count') ? $value->count()>0 : count($value->toArray())>0) : TRUE;
    }//if(is_object($value) && method_exists($value,'toArray'))
    return FALSE;
}//END function is_collection
/**
 * Validate variable value
 *
 * @param   mixed $value Variable to be validated
 * @param   mixed $def_value Default value to be returned if param is not validated
 * @param   string $validation Validation type
 * @param   bool $checkOnly Flag for setting validation as check only
 * @return  mixed Returns param value or default value if not validated
 * or TRUE/FALSE if $checkOnly is TRUE
 */
function validate_param($value,$def_value = NULL,?string $validation = NULL,bool $checkOnly = FALSE) {
    if(!strlen($validation)) {
        if($checkOnly) { return isset($value); }
        return (isset($value) ? $value : $def_value);
    }//if(!strlen($validation))
    if(substr($validation,0,1)=='?') {
        if(is_null($value)) { return NULL; }
        $validation = substr($validation,1);
    }//if(substr($validation,0,1)=='?')
    if($checkOnly) {
        switch(strtolower($validation)) {
            case 'true':
                return ($value ? TRUE : FALSE);
            case 'is_object':
                return is_object($value);
            case 'is_scalar':
                return is_scalar($value);
            case 'is_numeric':
                return is_numeric($value);
            case 'is_integer':
                return (is_numeric($value) && is_integer($value*1));
            case 'is_float':
                return (is_numeric($value) && is_float($value*1));
            case 'is_not0_numeric':
                return (is_numeric($value) && $value<>0);
            case 'is_not0_integer':
                return (is_numeric($value) && is_integer($value*1) && $value<>0);
            case 'is_not0_float':
                return (is_numeric($value) && is_float($value*1) && $value<>0);
            case 'is_array':
                return is_array($value);
            case 'is_notempty_array':
                return (is_array($value) && count($value));
            case 'is_string':
                return is_scalar($value);
            case 'is_notempty_string':
                return (is_scalar($value) && strlen(strval($value)));
            case 'trim_is_notempty_string':
                return (is_scalar($value) && strlen(trim(strval($value))));
            case 'db_date':
            case 'db_datetime':
                return (is_string($value) && strlen($value));
            case 'is_bool':
            case 'is_boolean':
                return is_bool($value);
            case 'isset':
            case 'bool':
            default: return isset($value);
        }//END switch
    }//if($checkOnly)
    switch(strtolower($validation)) {
        case 'true':
            return ($value ? $value : $def_value);
        case 'is_object':
            return (is_object($value) ? $value : $def_value);
        case 'is_scalar':
            return is_scalar($value) ? $value : $def_value;
        case 'is_numeric':
            return (is_numeric($value) ? ($value+0) : $def_value);
        case 'is_integer':
            return (is_numeric($value) ? intval($value) : $def_value);
        case 'is_float':
            return (is_numeric($value) ? floatval($value) : $def_value);
        case 'is_not0_numeric':
            return (is_numeric($value) && $value<>0 ? ($value+0) : $def_value);
        case 'is_not0_integer':
            return (is_numeric($value) && intval($value)<>0 ? intval($value) : $def_value);
        case 'is_not0_float':
            return (is_numeric($value) && $value<>0 ? floatval($value) : $def_value);
        case 'is_array':
            return is_array($value) ? $value : $def_value;
        case 'is_notempty_array':
            return (is_array($value) && count($value) ? $value : $def_value);
        case 'is_string':
            return (is_scalar($value) ? strval($value) : $def_value);
        case 'is_notempty_string':
            return (is_scalar($value) && strlen(strval($value)) ? strval($value) : $def_value);
        case 'trim_is_notempty_string':
            return (is_scalar($value) && strlen(trim(strval($value))) ? strval($value) : $def_value);
        case 'db_date':
        case 'db_datetime':
            return (is_string($value) && strlen($value) ? strval($value) : $def_value);
        case 'is_bool':
        case 'is_boolean':
            return is_bool($value) ? $value : $def_value;
        case 'bool':
            return (isset($value) ? (strtolower($value)=='true' ? TRUE : (strtolower($value)=='false' ? FALSE : ($value ? TRUE : FALSE))) : $def_value);
        case 'isset':
        default:
            return (isset($value) ? $value : $def_value);
    }//END switch
}//END function validate_param
/**
 * Checks if a key exists in an array and validates its value
 * (if validation is set)
 *
 * @param   mixed $key Key to be checked
 * @param   array $array Array to be searched (passed by reference)
 * @param   string $validation Validation type
 * (as implemented in validate_param function)
 * @return  bool Returns TRUE if $key exists in the $array or FALSE otherwise.
 * If $validation is not NULL, result is TRUE only if $array[$key] is validated
 */
function check_array_key($key,&$array,?string $validation = NULL) {
    if(!is_array($array) || is_null($key) || (!is_integer($key) && !is_string($key)) || !array_key_exists($key,$array)){ return FALSE; }
    if(!is_string($validation)){ return TRUE; }
    return validate_param($array[$key],NULL,$validation,TRUE);
}//END function check_array_key
/**
 * Extracts a value from a an multi-dimensional array
 *
 * @param   mixed $params Params array
 * (parsed as reference)
 * @param   string|array $key Key of the param to be returned
 * @param   mixed $def_value Default value to be returned if param is not validated
 * @param   string $validation Validation type
 * (as implemented in validate_param function)
 * @return  mixed Returns param value or default value if not validated
 */
function get_array_value(&$params,$key,$def_value = NULL,?string $validation = NULL) {
    if(is_array($key)) {
        if(!count($key)) { return $def_value; }
        $lKey = array_shift($key);
    } else {
        $lKey = $key;
        $key = [];
    }//if(is_array($key))
    if(is_null($lKey) || !(is_string($lKey) || is_integer($lKey))) { return $def_value; }
    if(is_array($params)) {
        if(!array_key_exists($lKey,$params)) { return $def_value; }
        if(is_array($key) && count($key)) {
            $value = get_array_value($params[$lKey],$key,$def_value,$validation);
        } else {
            $value = $params[$lKey];
        }//if(is_array($key) && count($key))
    } elseif(is_object($params) && method_exists($params,'toArray')) {
        $lparams = $params->toArray();
        if(!is_array($lparams) || !array_key_exists($lKey,$lparams)) { return $def_value; }
        if(is_array($key) && count($key)) {
            $value = get_array_value($lparams[$lKey],$key,$def_value,$validation);
        } else {
            $value = $lparams[$lKey];
        }//if(is_array($key) && count($key))
    } else {
        return $def_value;
    }//if(is_array($params))
    return validate_param($value,$def_value,$validation);
}//END function get_array_value
/**
 * Extracts a param value from a params array
 *
 * @param   array $params Params array
 * (parsed as reference)
 * @param   string $key Key of the param to be returned
 * @param   mixed $def_value Default value to be returned if param is not validated
 * @param   string $validation Validation type
 * (as implemented in validate_param function)
 * @param   string $sub_key
 * @return  mixed Returns param value or default value if not validated
 */
function get_array_param(&$params,$key,$def_value = NULL,?string $validation = NULL,$sub_key = NULL) {
    if(is_string($sub_key) || is_numeric($sub_key)) { $key = [$key,$sub_key]; }
    return get_array_value($params,$key,$def_value,$validation);
}//END function get_array_param
/**
 * Converts a hex color to RGB
 *
 * @param  string $hex Color hex code
 * @param  number $r B code by reference (for output)
 * @param null    $g
 * @param null    $b
 * @return array Returns an array containing the RGB values - array(R,G,B)
 */
function hex2rgb($hex,&$r = NULL,&$g = NULL,&$b = NULL) {
   $hex = str_replace('#','',$hex);
   if(strlen($hex)==3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }//if(strlen($hex)==3)
   return array($r,$g,$b);
}//END function hex2rgb
/**
 * Custom round numeric values
 *
 * @param  float $value The numeric value to be rounded
 * @param  float $scale The rounding precision (number of decimals to keep)
 * @param int      $step The step value for rounding (integers from 1 to 9)
 * @param int      $mode Rounding mode: 1 = round up, 0 = mathematical round (default) and -1 = round down
 * @return float Returns the rounded number or FALSE on wrong params
 */
function custom_round($value,$scale,$step = 1,$mode = 0) {
    if(!is_numeric($value) || !is_numeric($scale)) { return FALSE; }
    $lstep = (!is_numeric($step) || $step<=0 || $step>9) ? 1 : intval($step);
    $lscale = pow(10,$scale-1);
    $val = intval($value*$lscale)/$lscale;
    $rem = round(($value)-$val,6)*$lscale*10;
    if($rem==0) { return $value; }
    $rval = intval($rem/$lstep)*$lstep;
    switch($mode) {
        case -1:
            $rem = $rval;
            break;
        case 1:
            $rem = $rval+$lstep;
            break;
        case 0;
        default:
            $rem = $rval+(($rem-$rval*$lstep)>=($lstep/2) ? $lstep : 0);
            break;
    }//END switch
    return ($val + $rem/($lscale*10));
}//END function custom_round
/**
 * Converts a date from unix timestamp to excel serial
 *
 * @param  mixed   $date The date to be converted in unix time stamp format
 * or in string format (if string the $ts_input param must be set to FALSE)
 * @param  string $timezone The time zone for the string data to be converted
 * @param  string $new_timezone User's time zone
 * @return int Returns the date in excel serial format
 */
function unixts2excel($date,$timezone = NULL,$new_timezone = NULL) {
    if(!$date) { return NULL; }
    try {
        if(is_numeric($date)) {
            $dt = strlen($timezone) ? new DateTime(date('Y-m-d H:i:s',$date),new DateTimeZone($timezone)) : new DateTime(date('Y-m-d H:i:s',$date));
        } elseif(is_object($date)) {
            $dt = $date;
        } else {
            $date = trim($date,' -.:/');
            if(!strlen($date)) { return NULL; }
            $dt = strlen($timezone) ? new DateTime($date,new DateTimeZone($timezone)) : new DateTime($date);
        }//if(strlen($timezone))
        if(strlen($new_timezone) && $new_timezone!==$timezone) {
            $dt->setTimezone(new DateTimeZone($new_timezone));
        }//if(strlen($new_timezone) && $new_timezone!==$timezone)
        $result = (25569.083333333 + ($dt->getTimestamp() + 3600) / 86400);
        return $result;
    } catch(Exception $ne) {
        return NULL;
    }//END try
}//END function unixts2excel
/**
 * Converts a date from excel serial to unix time stamp
 *
 * @param  float $date The date to be converted from excel serial format
 * @param  string $timezone User's time zone
 * @param  string $new_timezone The time zone for the string data to be converted
 * @param  string $format The format in which the string data will be outputed
 * If NULL or empty, numeric time stamp is returned
 * @return int Returns the date as string or or unix time stamp
 */
function excel2unixts($date,$timezone = NULL,$new_timezone = NULL,$format = 'Y-m-d H:i:s') {
    if(!is_numeric($date)) { return NULL; }
    try {
        $ldate = date('Y-m-d H:i:s',(round(($date - 25569.083333333) * 86400) - 3600));
        $dt = strlen($timezone) ? new DateTime($ldate,new DateTimeZone($timezone)) : new DateTime($ldate);
        if(strlen($new_timezone) && $new_timezone!==$timezone) {
            $dt->setTimezone(new DateTimeZone($new_timezone));
        }//if(strlen($new_timezone) && $new_timezone!==$timezone)
        if(!$format || !strlen($format)) {
            return $dt->getTimestamp();
        }//if(!$format || !strlen($format))
        return $dt->format($format);
    } catch(Exception $e) {
        return NULL;
    }//END try
}//END function excel2unixts
/**
 * Gets the Unix timestamp for a date/time with an optional timezone
 *
 * @param  string $date The string representing a date/time in a PHP accepted format
 * (if NULL or 'now' is passed, the function will return the current Unix timestamp)
 * @param  string $timezone Optional parameter representing the timezone string
 * @param  string $new_timezone User's time zone (optional)
 * @return int The Unix timestamp
 */
function get_timestamp($date,$timezone = NULL,$new_timezone = NULL) {
    try {
        $dt = new DateTime($date,new DateTimeZone(strlen($timezone) ? $timezone : \NETopes\Core\AppConfig::server_timezone()));
        if(strlen($new_timezone)) {
            $dt->setTimezone(new DateTimeZone($new_timezone));
        }//if(strlen($new_timezone))
        return $dt->getTimestamp();
    } catch(Exception $e) {
        return NULL;
    }//END try
}//END function get_timestamp
/**
 * Returns a string containing a formated number
 *
 * @param  number $value The number to be formated
 * @param  string $format The format string in NETopes style
 * (NETopes format: "[number of decimals]|[decimal separator|[group separator]|[sufix]"
 * @return string Returns the formated number or NULL in case of errors
 */
function custom_number_format($value,$format = '0|||') {
    if(!is_numeric($value) || !is_string($format) || !strlen($format)) { return NULL; }
    $f_arr = explode('|',$format);
    if(!is_array($f_arr) || count($f_arr)!=4) { return NULL; }
    return number_format($value,$f_arr[0],$f_arr[1],$f_arr[2]).$f_arr[3];
}//END function custom_number_format
/**
 * Returns an array of files from the provided path and all its sub folders.
 * For each file the value is an array with the following structure:
 * array(
 * 		'name'=>(string) File name (with extension),
 * 		'path'=>(string) Full path of the file (without file name),
 * 		'ext'=>(string) File extension (without "." character)
 * )
 *
 * @param  string $path The starting path for the search
 * @param  array  $extensions An array of accepted file extensions (without the "." character)
 * or NULL for all
 * @param  string $exclude A regex string for filtering files and folders names with preg_match function
 * or NULL for all
 * @param  int    $sort Sort type in php scandir() format (default SCANDIR_SORT_ASCENDING)
 * @param  array  $dir_exclude An array of folders to be excluded (at any level of the tree)
 * @return array|bool  Returns an array of found files
 */
function get_files_recursive($path,$extensions = NULL,$exclude = NULL,$sort = SCANDIR_SORT_ASCENDING,$dir_exclude = NULL) {
    if(!$path || !file_exists($path)) { return FALSE; }
    $result = array();
    foreach(scandir($path,$sort) as $v) {
        if($v=='.' || $v=='..' || (strlen($exclude) && preg_match($exclude,$v))) { continue; }
        if(is_dir($path.'/'.$v)) {
            if(is_array($dir_exclude) && count($dir_exclude) && in_array($v,$dir_exclude)) { continue; }
            $tmp_result = get_files_recursive($path.'/'.$v,$extensions,$exclude,$sort,$dir_exclude);
            if(is_array($tmp_result)) { $result = array_merge($result,$tmp_result); }
        } else {
            $ext = strrpos($v,'.')===FALSE || strrpos($v,'.')==0 ? '' : substr($v,strrpos($v,'.')+1);
            if(is_array($extensions) && !in_array($ext,$extensions)) { continue; }
            $result[] = array('name'=>$v,'path'=>$path,'ext'=>$ext);
        }//if(is_dir($path.'/'.$v))
    }//END foreach
    return $result;
}//END function get_files_recursive
/**
 * Changes the case of the first letter of the string or for the first letter of each word in string.
 *
 * @param   string|null $str String to be processed.
 * @param   bool   $all If all param is set TRUE, all words in the string will be processed with ucfirst()
 * standard php function, otherwise just the first letter in string will be changed to upper.
 * @param bool     $lowercase
 * @param string|null   $delimiter
 * @param bool     $remove_delimiter
 * @return  string|null The processed string.
 */
function custom_ucfirst(?string $str,bool $all = TRUE,bool $lowercase = TRUE,?string $delimiter = NULL,bool $remove_delimiter = FALSE): ?string {
    if(!strlen($str)) { return $str; }
    if($all) {
        $delimiter = strlen($delimiter) ? $delimiter : ' ';
        $str_arr = explode($delimiter,trim(($lowercase ? strtolower($str) : $str)));
        $result = '';
        foreach($str_arr as $stri) { $result .= (strlen($result) && !$remove_delimiter ? $delimiter : '').ucfirst($stri); }
    } else {
        $result = ucfirst(trim(($lowercase ? strtolower($str) : $str)));
    }//if($all)
    return $result;
}//END function custom_ucfirst
/**
 * Converts a string of form [abcd_efgh_ijk] into a camelcase form [AbcdEfghIjk]
 *
 * @param  string $string String to be converted
 * @param  bool   $lower_first Flag to indicate if the first char should be lower case
 * @param bool    $namespaced
 * @return string Returns the string in camelcase format or NULL on error
 */
function convert_to_camel_case(?string $string,bool $lower_first = FALSE,bool $namespaced = FALSE): ?string {
    if(!strlen($string)) { return $string; }
    if($namespaced) {
        $str_arr = explode('-',$string);
        $result = implode('\\',array_map(function($str){
            return custom_ucfirst($str,TRUE,FALSE,'_',TRUE);
        },$str_arr));
    } else {
        $result = custom_ucfirst($string,TRUE,FALSE,'_',TRUE);
        if($lower_first) { $result = lcfirst($result); }
    }//if($namespaced)
    return $result;
}//END function convert_to_camel_case
/**
 * Converts a camelcase string to one of form [abcd_efgh_ijk]
 *
 * @param  string $string String to be converted
 * @param  bool   $upper Flag to indicate if the result should be upper case
 * @return string Returns the string converted from camel case format or NULL on error
 */
function convert_from_camel_case($string,$upper = FALSE) {
    $result = str_replace('\\','-',$string);
    $result = preg_replace('/(?<=\\w)(?=[A-Z])/','_$1',$result);
    return ($upper ? strtoupper($result) : strtolower($result));
}//END function convert_from_camel_case
/**
 * Convert number (integer part) to words representation
 *
 * @param float $value Number to be converted (only integer part will be processed)
 * @param string $langcode Language code
 * @return null|string
 */
function convert_number_to_words($value,$langcode) {
    if(!is_numeric($value) || !is_string($langcode) || !strlen($langcode)) { return NULL; }
    $words_list = [
        'en'=>[
            'and'=>'-',
            '0-20'=>['0'=>'zero','1'=>'one','2'=>'two','2f'=>'two','3'=>'three','4'=>'four','5'=>'five','6'=>'six','7'=>'seven','8'=>'eight','9'=>'nine','10'=>'ten','11'=>'eleven','12'=>'twelve','13'=>'thirteen','14'=>'fourteen','15'=>'fifteen','16'=>'sixteen','17'=>'seventeen','18'=>'eighteen','19'=>'nineteen'],
            '2z-9z'=>['2'=>'twentieth','3'=>'thirtieth','4'=>'forty','5'=>'fifty','6'=>'sixty','7'=>'seventy','8'=>'eighty','9'=>'ninety'],
            'um'=>['c'=>'hundred','oc'=>'one hundred','2'=>'thousand','o2'=>'one thousand','3'=>'million','o3'=>'one million','4'=>'billion','o4'=>'one billion'],
        ],
        'ro'=>[
            'and'=>' și ',
            '0-20'=>['0'=>'zero','1'=>'unu','2'=>'doi','2f'=>'două','3'=>'trei','4'=>'patru','5'=>'cinci','6'=>'șase','7'=>'șapte','8'=>'opt','9'=>'nouă','10'=>'zece','11'=>'unsprezece','12'=>'doisprezece','13'=>'treisprezece','14'=>'paisprezece','15'=>'cincisprezece','16'=>'șaisprezece','17'=>'șaptesprezece','18'=>'optsprezece','19'=>'nouăsprezece'],
            '2z-9z'=>['2'=>'douăzeci','3'=>'treizeci','4'=>'patruzeci','5'=>'cincizeci','6'=>'șaizeci','7'=>'șaptezeci','8'=>'optzeci','9'=>'nouăzeci'],
            'um'=>['c'=>'sute','oc'=>'o sută','2'=>'mii','o2'=>'o mie','3'=>'milioane','o3'=>'un milion','4'=>'miliarde','o4'=>'un miliard'],
        ],
    ];
    if(!array_key_exists($langcode,$words_list)) { return NULL; }
    $value = intval($value);
    if($value==0) { return $words_list[$langcode]['0-20']['0']; }
    $words = [];
    $groups = str_split(str_pad($value,ceil(strlen($value)/3)*3,'0',STR_PAD_LEFT),3);
    foreach($groups as $k=>$group) {
        if($group=='000') { continue; }
        if($group=='001') {
            if((count($groups)-$k)==1) {
                $words[] = $words_list[$langcode]['0-20']['1'];
            } else {
                $words[] = $words_list[$langcode]['um']['o'.(count($groups)-$k)];
            }//if((count($groups)-$k)==1)
        } elseif($group=='002') {
            if((count($groups)-$k)==1) {
                $words[] = $words_list[$langcode]['0-20']['2'];
            } else {
                $words[] = $words_list[$langcode]['0-20']['2f'].' '.$words_list[$langcode]['um'][(count($groups)-$k)];
            }//if((count($groups)-$k)==1)
        } else {
            $val = str_split($group);
            if($val[0]!='0') {
                if($val[0]==1) {
                    $words[] = $words_list[$langcode]['um']['oc'];
                } elseif($val[0]==2) {
                    $words[] = $words_list[$langcode]['0-20']['2f'].' '.$words_list[$langcode]['um']['c'];
                } else {
                    $words[] = $words_list[$langcode]['0-20'][$val[0]].' '.$words_list[$langcode]['um']['c'];
                }//if($val[0]==1)
            }//if($val[0]!='0')
            if($val[1]!='0') {
                if($val[1]==1) {
                    $words[] = $words_list[$langcode]['0-20'][$val[1].$val[2]];
                } else {
                    $tmpw = $words_list[$langcode]['2z-9z'][$val[1]];
                    if($val[2]>0) {
                        $tmpw .= $words_list[$langcode]['and'];
                        $tmpw .= $words_list[$langcode]['0-20'][$val[2]];
                    }//if($val[2]>0)
                    $words[] = $tmpw;
                }//if($val[1]==1)
            } elseif($val[2]>0) {
                $words[] = $words_list[$langcode]['0-20'][$val[2]];
            }//if($val[1]!='0')
            if((count($groups)-$k)!=1) { $words[] = $words_list[$langcode]['um'][(count($groups)-$k)]; }
        }//if($group=='001')
    }//END foreach
    return implode(' ',$words);
}//END function convert_number_to_words
/**
 * Read a CSV file and convert content to an associative array
 *
 * @param string $file Input file full name (including path)
 * @param int $line_length Line length in characters (default 1000)
 * @param bool|array $with_header If TRUE first row will be considered table header, if FALSE no header
 * or an array containing table header
 * @param string $delimiter Delimiter character (default [,])
 * @param string $enclosure Enclosure character (default ["])
 * @return null|array Returns resulted data array or NULL on error
 */
function csvfile_to_array($file,$line_length = 1000,$with_header = TRUE,$delimiter = ',',$enclosure = '"') {
    if(!is_string($file) || !strlen($file) || !file_exists($file)) { return NULL; }
    if(($handle = fopen($file,"r"))===FALSE) { return NULL; }
    $result = [];
    $header = is_array($with_header) ? $with_header : [];
    while(($row = fgetcsv($handle,$line_length,$delimiter,$enclosure))!==FALSE) {
        if($with_header===TRUE) {
            if(!count($header)) {
                $header = $row;
            } else {
                $drow = [];
                foreach($header as $i=>$k) { $drow[$k] = count($row)>=($i+1) ? $row[$i] : NULL; }
                $result[] = $drow;
            }//if(!count($header))
        } else {
            $result[] = $row;
        }//if($with_header===TRUE)
    }//END while
    fclose($handle);
    return $result;
}//END function csvfile_to_array
/**
 * Get file mime type by extension
 *
 * @param  string $filename Target file name (with or without path)
 * @return string Returns the mime type identified by file extension
 */
function get_file_mime_type_by_extension($filename) {
    $standard_mime_types = array(
            'pdf'=>'application/pdf',
            'txt'=>'text/plain',
            'html'=>'text/html',
            'htm'=>'text/html',
            'exe'=>'application/octet-stream',
            'zip'=>'application/zip',
            'doc'=>'application/msword',
            'xls'=>'application/vnd.ms-excel',
            'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'=>'application/vnd.ms-powerpoint',
            'dbf'=>'application/x-dbf',
            'gif'=>'image/gif',
            'png'=>'image/png',
            'jpeg'=>'image/jpg',
            'jpg'=>'image/jpg',
            'php'=>'text/plain',
            'apk'=>'application/octet-stream',
            'log'=>'text/plain',
        );
    $fileext = substr($filename,strrpos($filename,'.')+1);
    return (array_key_exists($fileext,$standard_mime_types) ? $standard_mime_types[$fileext] : 'application/force-download');
}//END function get_file_mime_type_by_extension
/**
 * Get file extension by mime type
 *
 * @param  string $mime_type Target mime type
 * @return string Returns the file extension identified by mime type
 */
function get_file_extension_by_mime_type($mime_type) {
    if(!is_string($mime_type) || !strlen(trim($mime_type))) { return FALSE; }
    $standard_extensions = array(
        'application/pdf'=>'pdf',
        'text/html'=>'html',
        'image/jpg'=>'jpg',
        'image/png'=>'png',
        'image/gif'=>'gif',
        'application/msword'=>'doc',
        'application/vnd.ms-excel'=>'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlsx',
        'application/vnd.ms-powerpoint'=>'ppt',
        'application/zip'=>'zip',
        'application/octet-stream'=>'exe',
        'text/plain'=>'txt',
    );
    foreach($standard_extensions as $k=>$v) { if(strpos(strtolower($mime_type),$k)!==FALSE) { return $v; } }
    return NULL;
}//END function get_file_mime_type_by_extension
/**
 * @param $string
 * @return null|string
 */
function custom_nl2br($string) {
    if(!is_string($string)) { return NULL; }
    return nl2br(str_replace("\t",'&nbsp;&nbsp;&nbsp;',$string));
}//END function custom_nl2br
/**
 * @param $string
 * @return mixed|null
 */
function custom_br2nl($string) {
    if(!is_string($string)) { return NULL; }
    return str_replace('&nbsp;&nbsp;&nbsp;',"\t",str_replace(array('<br/>','<br />','<br>'),"\n",$string));
}//END function custom_br2nl
/**
 * @param      $data
 * @param bool $for_html
 * @return mixed|string
 * @throws \Exception
 */
function safe_json_encode($data,$for_html = TRUE) {
    $result = json_encode($data);
    if($for_html) {
        $result = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',$result);
        $result = nl2br($result);
    } else {
        $result = str_replace(array("\r\n","\r","\n","\t"),' ',$result);
    }//if($for_html)
    return $result;
}//END function safe_json_encode
/**
 * @param      $var
 * @param bool $html_entities
 * @param bool $return
 * @param bool $utf8encode
 * @return string|null
 */
function vprint($var,$html_entities = FALSE,$return = FALSE,$utf8encode = FALSE) {
    if(is_string($var)) { $result = $var; }
    else { $result = print_r($var,TRUE); }
    if($html_entities) {
        $result = htmlentities($result,NULL,($utf8encode ? 'utf-8' : NULL));
    } else {
        if($utf8encode) { $result = utf8_encode($result); }
        $result = '<pre>'.$result.'</pre>';
    }//if($html_entities)
    if($return===TRUE) { return $result; }
    echo $result;
    return NULL;
}//END function vprint
/**
 * @param        $array
 * @param string $separator
 * @param null   $parent
 * @return bool|string
 */
function array2string($array,$separator = '|',$parent = NULL) {
    if(!is_array($array) || !count($array)) { return FALSE; }
    $result = '';
    foreach($array as $k=>$v) {
        if(is_array($v)) {
            $result .= array2string($v,$separator,$parent.(strlen($parent) ? '/' : '').$k);
        } else {
            $result .= $k.$separator."'{$v}'".$separator.$parent.$separator.$separator."\n";
        }//if(is_array($v))
    }//END foreach
    return $result;
}//END function array2string
/**
 * description
 *
 * @param $parents
 * @param $searched
 * @return mixed
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
 * @param $array
 * @param $key
 * @param $value
 * @return int
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
 * @param $array
 * @param $value
 * @return bool|int
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
 * @param      $input
 * @param bool $recursive
 * @param int  $case
 * @return array
  */
function arr_change_key_case($input,$recursive = FALSE,$case = CASE_LOWER) {
    if(!is_array($input)) { return $input; }
    $result = [];
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
 * @param array|null $input
 * @param bool       $recursive
 * @param int        $case
 * @return array|null
 */
function arr_change_value_case(?array $input,bool $recursive = FALSE,int $case = CASE_LOWER): ?array {
    if(is_null($input)) { return NULL; }
    if(!in_array($case,[CASE_LOWER,CASE_UPPER])) { return $input; }
    $result = [];
    foreach ($input as $k=>$v) {
        if(is_array($v) && $recursive===TRUE) {
            $result[$k] = arr_change_value_case($v,TRUE,CASE_LOWER);
        } else {
            $result[$k] = is_string($v) ? ($case==CASE_UPPER ? strtoupper($v) : strtolower($v)) : $v;
        }//if(is_array($v))
    }//foreach ($input as $k=>$v)
    return $result;
}//END function arr_change_value_case
/**
 * description
 *
 * @param mixed       $text
 * @param int         $maxLength
 * @param string|null $sufix
 * @return string
 */
function limit_text($text,int $maxLength,?string $sufix = NULL): ?string {
    if(!is_string($text)) { return NULL; }
    if (strlen($text)<=$maxLength) { return $text; }
    return substr($text,0,$maxLength).(isset($sufix) ? $sufix : '');
}//END function limit_text
/**
  * description
  *
 * @param $path
 * @return string
  */
function win2unix_path(string $path): string {
    return DIRECTORY_SEPARATOR=='\\' ? str_replace('\\','/',$path) : $path;
}//END function win2unix_path
/**
  * description
  *
 * @param      $array
 * @param      $structure
 * @param null $uppercasekeys
 * @return array|null
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
    $result = [];
    foreach($array as $row) { eval('$result'.$keystr.' = $row["'.$valkey.'"];'); }
    return $result;
}//END function convert_db_array_to_tree
/**
  * description
  *
 * @param      $array
 * @param bool $return
 * @return array|null
  */
function custom_shuffle(&$array,$return = TRUE) {
    if(!is_array($array)) { if($return) { return $array; } else { return NULL; } }
    $keys = array_keys($array);
    shuffle($keys);
    $random = [];
    foreach($keys as $key) { $random[$key] = $array[$key]; }
    if($return) { return $random; }
    $array = $random;
    return $array;
}//function custom_shuffle
/**
  * description
  *
 * @param      $file
 * @param null $ext
 * @return bool|int
  */
function check_file_404($file,$ext = NULL) {
    $file = preg_replace('{ +}','%20',trim($file));
    if(substr($file,0,7)!=="http://") { $file = "http://".$file; }
    if($ext) {
        $file_arr = explode('.',$file);
        $file_ext = strtolower(array_pop($file_arr));
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
 * @param array $params Parameters object (instance of [Params])
 * @param null  $info
 * @return bool|string
 * @throws \Exception
 */
function curl_call($params = [],&$info = NULL) {
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
        // CURLOPT_MUTE=>TRUE,
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
    if(isset($params['auth_username']) && $params['auth_username']) {
        curl_setopt($c_url,CURLOPT_USERPWD,$params['auth_username'].':'.(isset($params['auth_password']) ? $params['auth_password'] : ''));
        curl_setopt($c_url,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);
    }//if(isset($params['auth_username']) && $params['auth_username'])
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
 * @return mixed
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
 * @param     $host
 * @param int $port
 * @param int $timeout
 * @return string
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