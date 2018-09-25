<?php
/**
 * Simple Report class file
 *
 * Used for generating in-page simple reports.
 *
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Reporting;
/**
 * ClassName description
 *
 * long_description
 *
 * @package  NETopes\Reporting
 * @access   public
 */
class SimpleReport {
	protected $decimal_separator = NULL;
	protected $group_separator = NULL;
	protected $date_separator = NULL;
	protected $time_separator = NULL;
	protected $result = '';
	protected $baseclass = 'listing lineslisting span-cent';
	public function __construct(&$layout = [],&$data = [],&$class) {
		$this->decimal_separator = NApp::_GetParam('decimal_separator');
		$this->group_separator = NApp::_GetParam('group_separator');
		$this->date_separator = NApp::_GetParam('date_separator');
		$this->time_separator = NApp::_GetParam('time_separator');
		foreach($layout as $layout_item) {
			$formats = [];
			foreach($layout_item['formats'] as $fkey=>$format) {
				$style = '';
				foreach($format as $frm=>$value) {
					if($frm=='font')
						$style .= 'font-family: '.$value.';';
					elseif($frm=='bold' && $value==TRUE)
						$style .= 'font-weight: bold;';
					elseif($frm=='italic' && $value==TRUE)
						$style .= 'font-style: italic;';
					elseif($frm=='color')
						$style .= 'color: '.$value.';';
					elseif($frm=='align')
						$style .= 'text-align: '.$value.';';
				}//foreach($format as $frm=>$value)
				$formats[$fkey]['style'] = $style;
			}//foreach($layout_item['formats'] as $fkey=>$format)
			// add columns
			$header = "\t\t".'<thead>'."\n";
			$header .= "\t\t\t".'<tr>'."\n";
            $colnr = 0;
            $has_total = FALSE;
			foreach($layout_item['columns'] as $column) {
				$hclass = array_key_exists('htip',$column) ? 'clsTitleToolTip' : '';
				$hstyle = array_key_exists('width',$column) ? ' style="width: '.$column['width'].'px;"' : '';
                $tip = array_key_exists('htip',$column) ? ' title="'.$column['htip'].'" ' : '';
				$header .= "\t\t\t\t".'<th class="'.$hclass.'"'.$tip.$hstyle.'>'.$column['name'].'</th>'."\n";
                $is_sum[$colnr] = (array_key_exists('sum',$column) && $column['sum']) ? ($column['sum']===TRUE ? 'sum' : $column['sum']) : FALSE;
                if($is_sum[$colnr]!==FALSE) { $has_total = TRUE; }
                $cellFormatCall[$colnr] = array_key_exists('formatcall',$column) ? $column['formatcall'] : NULL;
                $cellFormatStyle[$colnr] = array_key_exists('format',$column) ? ' style="'.$formats[$column['format']]['style'].'"' : '';
                $sum_value[$colnr] = 0;
                $colnr++;
			}//foreach($layout_item['columns'] as $column)
			$header .= "\t\t\t".'</tr>'."\n";
			$header .= "\t\t".'</thead>'."\n";
			$body = "\t\t".'<tbody>'."\n";
			// add data
			if(!is_array($data))
				return;
			foreach($data as $data_row) {
				$body .= "\t\t\t".'<tr>'."\n";
                $colnr = 0;
				foreach($layout_item['columns'] as $column) {
					$value = $this->FormatValue($column,$data_row,$cellFormatCall[$colnr],$class);
					if($value!==NULL && strlen($value)>0) {
						$sufix = (array_key_exists('sufix',$column) && strlen($column['sufix'])>0) ? $column['sufix'] : '';
						if(strlen($cellFormatCall[$colnr])>0 && !is_array($column['dbfield'])) {
							$value = $class->$cellFormatCall[$colnr]($value).$sufix;
						} else {
							$value = $value.$sufix;
						}//if($formatCall)
					} else {
						$value = (array_key_exists('defaultvalue',$column) && strlen($column['defaultvalue'])>0) ? $column['defaultvalue'] : '&nbsp;';
					}//if($value!==NULL && strlen($value)>0)
                    if($is_sum[$colnr]) { $sum_value[$colnr] += floatval(str_replace($this->decimal_separator,'.', str_replace($this->group_separator, '', $value))); }
					$body .= "\t\t\t\t".'<td'.$cellFormatStyle[$colnr].'>'.$value.'</td>'."\n";
                    $colnr++;
				}//foreach($layout_item['columns'] as $column)
				$body .= "\t\t\t".'</tr>'."\n";
			}//foreach($data as $data_row)
			$body .= "\t\t".'</tbody>'."\n";
			$total = '';
			if($has_total){
                $colnr = 0;
				$total = "\t\t".'<tfoot>'."\n";
                $total .= "\t\t\t".'<tr>'."\n";
				$blankcolspan = 0;
				$blankcol = FALSE;
                foreach($is_sum as $sum) {
                	if($sum=='sum') {
                        $value = (strlen($cellFormatCall[$colnr])>0) ? $class->$cellFormatCall[$colnr]($sum_value[$colnr]) : $sum_value[$colnr];
						if($blankcol) {
							$total .= "\t\t\t\t".'<td class="tcfirst" colspan="'.$blankcolspan.'">&nbsp;</td>'."\n";
							$blankcolspan = 0;
							$blankcol = FALSE;
						}//if($blankcol)
                        $total .= "\t\t\t\t".'<td class="tcolumn bold" '.$cellFormatStyle[$colnr].'>'.$value.'</td>'."\n";
                    } elseif(is_array($sum) && get_array_value($sum,'type')=='func' && check_array_key('func_name',$sum,'is_notempty_string')) {
                    	$funcname = get_array_value($sum,'func_name');
                    	$value = $this->$funcname($sum,$sum_value);
						if($blankcol) {
							$total .= "\t\t\t\t".'<td class="tcfirst" colspan="'.$blankcolspan.'">&nbsp;</td>'."\n";
							$blankcolspan = 0;
							$blankcol = FALSE;
						}//if($blankcol)
                        $total .= "\t\t\t\t".'<td class="tcolumn bold" '.$cellFormatStyle[$colnr].'>'.$value.'</td>'."\n";
					} else {
                    	$blankcolspan++;
						$blankcol = TRUE;
                    }//if($sum == 1)
                    $colnr++;
                }//foreach($is_sum as $sum)
                if($blankcol) { $total .= "\t\t\t\t".'<td colspan="'.$blankcolspan.'">&nbsp;</td>'."\n"; }
                $total .= "\t\t\t".'</tr>'."\n";
                $total .= "\t\t".'</tfoot>'."\n";
            }//if($has_total)
			$table_class = (array_key_exists('table_class',$layout_item) && strlen($layout_item['table_class'])>0) ? ' class="'.$layout_item['table_class'].'"' : ' class="'.$this->baseclass.'"';
			$result = "\t".'<table'.$table_class.'>'."\n";
			$result .= $header;
			$result .= $body;
			$result .= $total;
			$result .= "\t".'</table>'."\n";
			$this->result = $result;
		}//foreach($layout as $layout_item)
	}//END public function __construct

	protected function FormatValue(&$rowformat,$item,$formatcall = NULL,$class = NULL) {
		if(!is_array($rowformat['dbfield'])) {
			if(array_key_exists('indexof',$rowformat) && is_array($rowformat['indexof']) && count($rowformat['indexof'])) {
				if(array_key_exists($item[$rowformat['dbfield']],$rowformat['indexof'])) {
					if(is_array($rowformat['indexof'][$item[$rowformat['dbfield']]]) && array_key_exists('name',$rowformat['indexof'][$item[$rowformat['dbfield']]])) {
						return $rowformat['indexof'][$item[$rowformat['dbfield']]]['name'];
					}//if(is_array($rowformat['indexof'][$item[$rowformat['dbfield']]]) && array_key_exists('name',$rowformat['indexof'][$item[$rowformat['dbfield']]]))
					if(!is_array($rowformat['indexof'][$item[$rowformat['dbfield']]])) {
						return $rowformat['indexof'][$item[$rowformat['dbfield']]];
					}//if(!is_array($rowformat['indexof'][$item[$rowformat['dbfield']]]))
				}//if(array_key_exists($item[$rowformat['dbfield']],$rowformat['indexof']))
				return '';
			}//if(array_key_exists('indexof',$rowformat) && is_array($rowformat['indexof']) && count($rowformat['indexof']))
			return array_key_exists($rowformat['dbfield'],$item) ? $item[$rowformat['dbfield']] : '';
		}//if(!is_array($rowformat['dbfield']))
		if(is_null($formatcall)) {
			$result = '';
			foreach($rowformat['dbfield'] as $field) { $result .= ' '.$item[$field]; }
			return trim($result);
        }//if(is_null($formatcall)
        return $class->$formatcall($rowformat['dbfield'],$item);
	}//END protected function format_value

	protected function NumberFormat0($value) {
		return number_format($value,0,$this->decimal_separator,$this->group_separator);
	}//END protected function number_format0

	protected function NumberFormat2($value) {
		return number_format($value,2,$this->decimal_separator,$this->group_separator);
	}//END protected function number_format2

	protected function DateFormat($value){
    	return \NETopes\Core\App\Validator::ConvertDateTimeFromDbFormat($value,NApp::_GetParam('timezone',FALSE),TRUE,$this->date_separator,$this->time_separator);
	}//END protected function DateFormat

	protected function DateTimeFormat($value){
    	return \NETopes\Core\App\Validator::ConvertDateTimeFromDbFormat($value,NApp::_GetParam('timezone',FALSE),FALSE,$this->date_separator,$this->time_separator);
	}//END protected function DateTimeFormat

	protected function NoTimezoneDateFormat($value){
    	return \NETopes\Core\App\Validator::ConvertDateTimeFromDbFormat($value,'',TRUE,$this->date_separator,$this->time_separator);
	}//END protected function DateFormat

	protected function NoTimezoneDateTimeFormat($value){
    	return \NETopes\Core\App\Validator::ConvertDateTimeFromDbFormat($value,'',FALSE,$this->date_separator,$this->time_separator);
	}//END protected function DateTimeFormat

	public function Show() {
		echo $this->result;
	}//END public function Show
}//END class SimpleReport
?>