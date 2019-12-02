<?php
/**
 * TPdfAdapter trait file
 *
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.10.1
 * @filesource
 */
namespace NETopes\Core\Reporting;

/**
 * Trait TPdfAdapter
 *
 * @package NETopes\Core\Reporting
 */
trait TPdfAdapter {

    /**
     * @var    string PDF document orientation
     */
    public $orientation='P';

    /**
     * @var    string PDF document measurement unit
     */
    public $unit='mm';

    /**
     * @var    string PDF document page size
     */
    public $pageSize='A4';

    /**
     * @var    bool PDF document unicode
     */
    public $unicode=TRUE;

    /**
     * @var    string PDF document charset
     */
    public $charset='UTF-8';

    /**
     * @var    string PDF document file name
     */
    public $fileName;

    /**
     * @var    bool Flag for custom header method
     */
    public $customHeader=FALSE;

    /**
     * @var    array Custom header params
     */
    public $customHeaderParams=[];

    /**
     * @var    bool Flag for custom footer method
     */
    public $customFooter=FALSE;

    /**
     * @var    array Custom footer params
     */
    public $customFooterParams=[];

    /**
     * @var    array PDF document content
     */
    protected $content=[];

    /**
     * @param array $params
     */
    protected function ProcessInitialParams(array $params=[]): void {
        foreach($params as $k=>$v) {
            if(property_exists($this,$k)) {
                $this->$k=$v;
            }
        }//END foreach
    }//END protected function ProcessInitialParams

    /**
     * Get content elements (HTML data)
     *
     * @return array Document HTML data
     */
    public function GetContent(): array {
        return $this->content;
    }//END public function GetContent

    /**
     * Set content element (HTML data)
     *
     * @param string   $content
     * @param int|null $page
     */
    public function SetContent(string $content,?int $page=NULL): void {
        if(is_integer($page)) {
            $this->content[$page]=$content;
        } else {
            $this->content[]=$content;
        }
    }//END public function SetContent

    /**
     * Add content element (HTML data)
     *
     * @param string   $content
     * @param int|null $page
     */
    public function AddContent(string $content,?int $page=NULL): void {
        if(is_integer($page)) {
            if(!isset($this->content[$page])) {
                $this->content[$page]='';
            }
            $this->content[$page].=$content;
        } else {
            $this->content[]=$content;
        }
    }//END public function AddContent

    /**
     * Set content elements (HTML data)
     *
     * @param array $contents
     */
    public function AddContents(array $contents): void {
        foreach($contents as $k=>$content) {
            $this->AddContent($content,is_integer($k) ? $k : NULL);
        }//END foreach
    }//END public function AddContents

    /**
     * @return string|null
     */
    public function GetFileName(): ?string {
        return $this->fileName;
    }

    /**
     * @param string|null $fileName
     */
    public function SetFileName(?string $fileName) {
        $this->fileName=$fileName;
    }

    /**
     * @param array $format
     * @return string
     */
    public function GetHtmlFormatString($format=[]) {
        $style='';
        if(!is_array($format) || !count($format)) {
            return $style;
        }
        foreach($format as $frm=>$value) {
            switch($frm) {
                case 'font':
                    $style.='font-family: '.$value.';';
                    break;
                case 'font_size':
                    $style.='font-size: '.$value.';';
                    break;
                case 'bold':
                    $style.=$value ? 'font-weight: bold;' : '';
                    break;
                case 'italic':
                    $style.=$value ? 'font-style: italic;' : '';
                    break;
                case 'color':
                    $style.='color: '.$value.';';
                    break;
                case 'background_color':
                    $style.='background-color: '.$value.';';
                    break;
                case 'align_h':
                    $style.='text-align: '.str_replace('h_','',$value).';';
                    break;
                case 'align_v':
                    $style.='vertical-align: '.str_replace('v_','',$value).';';
                    break;
                default:
                    $style.=$frm.': '.$value.';';
                    break;
            }//END switch
        }//foreach($format as $frm=>$value)
        return $style;
    }//END public function GetHtmlFormatString
}//END trait TPdfAdapter