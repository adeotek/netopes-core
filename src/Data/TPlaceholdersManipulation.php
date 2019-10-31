<?php
namespace NETopes\Core\Data;
/**
 * Trait TPlaceholdersManipulation
 */
trait TPlaceholdersManipulation {

    /**
     * @var    array Dynamic parameters placeholders separators
     */
    public $placeholderSeparators=['[[',']]'];

    /**
     * @var    string Regular expression for finding placeholders for dynamic parameters
     */
    public $placeholdersRegExp='/\[{2}[^\]]*\]{2}/iU';

    /**
     * @var    string Chars to trim for removing placeholders for dynamic parameters
     */
    public $placeholdersTrimChars='[]';

    /**
     * @param string $content
     * @param array  $parameters
     * @return string
     */
    public function ReplacePlaceholders(string $content,array $parameters): string {
        $placeholders=[];
        if(preg_match_all($this->placeholdersRegExp,$content,$placeholders)) {
            foreach($placeholders[0] as $placeholder) {
                $content=str_replace($placeholder,$this->getPlaceholderValue(trim($placeholder,$this->placeholdersTrimChars),$parameters),$content);
            }//END foreach
        }//if(preg_match_all($this->placeholdersRegExp,$content,$placeholders))
        return $content;
    }//END public function ReplacePlaceholders

    /**
     * @param string $placeholder
     * @param array  $parameters
     * @return string
     */
    public function GetPlaceholderValue(string $placeholder,array $parameters): string {
        $paramValue=get_array_value($parameters,$placeholder,NULL,'isset');
        if(is_array($paramValue)) {
            $value=get_array_param($paramValue,'value',NULL,'?is_string');
            if(!strlen($value)) {
                return '';
            }
            $tagType=strtolower(get_array_param($paramValue,'type','','is_notempty_string'));
            $label=get_array_param($paramValue,'label',NULL,'?is_string');
            $style=get_array_param($paramValue,'style',NULL,'?is_string');
            switch($tagType) {
                case 'table':
                    return '<table'.(strlen($style) ? ' style="'.$style.'"' : '').'><tr><td>'.(strlen($label) ? $label.':&nbsp;' : '').$value.'</td></tr></table>';
                case 'table_x2':
                    return '<table'.(strlen($style) ? ' style="'.$style.'"' : '').'><tr><td>'.(strlen($label) ? $label.':&nbsp;' : '').'</td><td>'.$value.'</td></tr></table>';
                case 'tr':
                    return '<tr><td>'.(strlen($label) ? $label.':&nbsp;' : '').$value.'</td></tr>';
                case 'tr_x2':
                    return '<tr><td>'.(strlen($label) ? $label.':&nbsp;' : '').'</td><td>'.$value.'</td></tr>';
                case 'div':
                case 'span':
                    return '<'.$tagType.(strlen($style) ? ' style="'.$style.'"' : '').'>'.(strlen($label) ? $label.':&nbsp;' : '').$value.'</'.$tagType.'>';
                case 'no_tag':
                    return (strlen($label) ? $label.':&nbsp;' : '').$value;
                default:
                    return $value;
            }//END switch
        }//if(is_array($paramValue))
        return (is_scalar($paramValue) ? $paramValue : NULL) ?? '';
    }//END public function GetPlaceholderValue
}//END trait TPlaceholdersManipulation