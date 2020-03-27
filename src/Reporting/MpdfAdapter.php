<?php
/**
 * MpdfAdapter class file
 *
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.10.1
 * @filesource
 */
namespace NETopes\Core\Reporting;
use DateTime;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use Mpdf\Utils\PdfDate;
use NETopes\Core\AppException;
use NETopes\Core\DataHelpers;

/**
 * Class TcpdfAdapter
 *
 * @package NETopes\Core\Reporting
 */
class MpdfAdapter extends mPDF implements IPdfAdapter {
    use TPdfAdapter;

    /**
     * @var string|null
     */
    protected $file_id=NULL;

    /**
     * @var DateTime|null
     */
    protected $doc_creation_timestamp=NULL;

    /**
     * @var DateTime|null
     */
    protected $doc_modification_timestamp=NULL;

    /**
     * @var bool Flag indicating if global CSS styles are loaded
     */
    protected $cssStylesLoaded=FALSE;

    /**
     * IPdfAdapter constructor.
     *
     * @param array $params
     * @throws \Mpdf\MpdfException
     */
    public function __construct(array $params=[]) {
        $this->ProcessInitialParams($params);
        parent::__construct([
            'mode'=>'',
            'format'=>$this->pageSize,
            'default_font_size'=>0,
            'default_font'=>'',
            'margin_left'=>10,
            'margin_right'=>10,
            'margin_top'=>10,
            'margin_bottom'=>10,
            'margin_header'=>8,
            'margin_footer'=>8,
            'orientation'=>$this->orientation,
        ]);
    }//END public function __construct

    /**
     * @param string   $family
     * @param string   $style
     * @param int|null $size
     * @throws \NETopes\Core\AppException
     */
    public function SetActiveFont(string $family,string $style='',?int $size=NULL) {
        try {
            // $family, $style = '', $size = 0, $write = true, $forcewrite = false
            $this->SetFont($family,$style,$size ?? 0,TRUE,FALSE);
        } catch(MpdfException $e) {
            throw AppException::GetInstance($e);
        }//END try
    }//END public function SetActiveFont

    /**
     * @return bool
     * @throws \Mpdf\MpdfException
     */
    public function WriteCssStyles(): bool {
        if($this->cssStylesLoaded || !strlen($this->cssStyles)) {
            return FALSE;
        }
        $this->WriteHTML($this->cssStyles,HTMLParserMode::HEADER_CSS,TRUE,FALSE);
        $this->cssStylesLoaded=TRUE;
        return TRUE;
    }//END public function WriteCssStyles

    /**
     * @param string $content
     * @param string $name
     * @param string $dest
     * @throws \NETopes\Core\AppException
     */
    public function OutputContent(string $content,?string $name=NULL,string $dest='I') {
        if(!strlen($content)) {
            throw new AppException('Invalid PDF content!');
        }
        $name=strlen($name) ? DataHelpers::normalizeString($name) : date("Y-m-d-H-i-s").'.pdf';
        try {
            switch(strtoupper($dest)) {
                case Destination::INLINE:
                    if(headers_sent($filename,$line)) {
                        throw new MpdfException(
                            sprintf('Data has already been sent to output (%s at line %s), unable to output PDF file',$filename,$line)
                        );
                    }
                    if($this->debug && !$this->allow_output_buffering && ob_get_contents()) {
                        throw new MpdfException('Output has already been sent from the script - PDF file generation aborted.');
                    }
                    // We send to a browser
                    if(PHP_SAPI!=='cli') {
                        header('Content-Type: application/pdf');
                        if(!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
                            // don't use length if server using compression
                            header('Content-Length: '.strlen($this->buffer));
                        }
                        header('Content-disposition: inline; filename="'.$name.'"');
                        header('Cache-Control: public, must-revalidate, max-age=0');
                        header('Pragma: public');
                        header('X-Generator: mPDF '.static::VERSION);
                        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                    }
                    echo $content;
                    break;
                case Destination::DOWNLOAD:
                    if(headers_sent()) {
                        throw new MpdfException('Data has already been sent to output, unable to output PDF file');
                    }
                    header('Content-Description: File Transfer');
                    header('Content-Transfer-Encoding: binary');
                    header('Cache-Control: public, must-revalidate, max-age=0');
                    header('Pragma: public');
                    header('X-Generator: mPDF '.static::VERSION);
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                    header('Content-Type: application/pdf');
                    if(!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
                        // don't use length if server using compression
                        header('Content-Length: '.strlen($this->buffer));
                    }
                    header('Content-Disposition: attachment; filename="'.$name.'"');
                    echo $content;
                    break;
                default:
                    throw new MpdfException(sprintf('Incorrect output destination %s',$dest));
            }
        } catch(MpdfException $e) {
            throw AppException::GetInstance($e);
        }//END try
    }//END public function OutputContent

    /**
     * @param array|null $params
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function GetOutput(?array $params=NULL) {
        $destination=get_array_value($params,'destination','S','is_notempty_string');
        $currentFileName=get_array_value($params,'file_name',NULL,'?is_string');
        if(!strlen($currentFileName)) {
            $currentFileName=$this->fileName ? DataHelpers::normalizeString($this->fileName) : date("Y-m-d-H-i-s").'.pdf';
        }
        try {
            $first=TRUE;
            foreach($this->content as $content) {
                if($first) {
                    $first=FALSE;
                    $this->WriteCssStyles();
                } else {
                    $this->AddPage();
                }
                $pageInit=TRUE;
                if(isset($content['page_header']) && strlen($content['page_header'])) {
                    // Fix usage of '<br />' or '<br/>'
                    $pageHeader=preg_replace('/<br\s?\/>/im','<br>',$content['page_header']);
                    // Fix for error when content is ending with '<br />' or '<br />&nbsp;'
                    $pageHeader=preg_replace('/(<br\s?\/>)+(\&nbsp;)*$/im','',rtrim($pageHeader));
                    // string $html [, int $mode [, boolean $initialise [, boolean $close ]]]
                    $this->WriteHTML($pageHeader,HTMLParserMode::HTML_BODY,$pageInit,TRUE);
                    $pageInit=FALSE;
                }
                // Fix usage of '<br />' or '<br/>'
                $pageContent=preg_replace('/<br\s?\/>/im','<br>',$content['content']);
                // Fix for error when content is ending with '<br />' or '<br />&nbsp;'
                $pageContent=preg_replace('/(<br\s?\/>)+(\&nbsp;)*$/im','',rtrim($pageContent));
                // string $html [, int $mode [, boolean $initialise [, boolean $close ]]]
                $this->WriteHTML($pageContent,HTMLParserMode::HTML_BODY,$pageInit,TRUE);
            }//END foreach
            return $this->Output($currentFileName,$destination);
        } catch(MpdfException $e) {
            throw AppException::GetInstance($e);
        }//END try
    }//END public function GetOutput

    /**
     * @param array|null $params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function Render(?array $params=NULL) {
        if(!is_array($params)) {
            $params=[];
        }
        $params['destination']='I';
        $this->GetOutput($params);
    }//END public function Render

    /**
     * @param array|null $params
     * @return void
     * @throws \Mpdf\MpdfException
     */
    public function SetCustomHeader(?array $params=NULL) {
        if(is_null($params)) {
            $params=$this->customHeaderParams;
        }
        if(!count($params)) {
            return;
        }
        $this->WriteCssStyles();
        $write=get_array_value($params,'write',TRUE,'bool');
        $html=get_array_value($params,'html','','is_notempty_string');
        $this->setAutoTopMargin='stretch';
        // string $html [, string $side [, boolean $write ]]
        $this->SetHTMLHeader($html,'O',$write);
    }//END public function SetCustomHeader

    /**
     * @param array|null $params
     * @return void
     * @throws \Mpdf\MpdfException
     */
    public function SetCustomFooter(?array $params=NULL) {
        if(is_null($params)) {
            $params=$this->customHeaderParams;
        }
        if(!count($params)) {
            return;
        }
        $this->WriteCssStyles();
        $html=get_array_value($params,'html','','is_notempty_string');
        $this->setAutoBottomMargin='stretch';
        // string $html [, string $side]
        $this->SetHTMLFooter($html);
    }//END public function SetCustomFooter

    /**
     * @return string|null
     */
    public function GetTitle() {
        return $this->title;
    }

    /**
     * Add new page to PDF
     *
     * @param string $orientation
     * @return void
     */
    public function AddNewPage(string $orientation=''): void {
        $this->AddPage($orientation);
    }

    /**
     * @param \DateTime|null $modifiedDate
     * @param \DateTime|null $createDate
     * @throws \Exception
     */
    public function SetDocumentDate(?DateTime $modifiedDate,?DateTime $createDate=NULL): void {
        $this->doc_creation_timestamp=($createDate ?? $modifiedDate ?? new DateTime())->getTimestamp();
        $this->doc_modification_timestamp=($modifiedDate ?? new DateTime())->getTimestamp();
    }

    /**
     * @param string|null $fileId
     */
    public function SetFileId(?string $fileId): void {
        $this->file_id=$fileId;
    }

    /**
     * @param string $pageSize
     */
    public function SetPageSize(string $pageSize): void {
        $this->pageSize=$pageSize;
        $this->_setPageSize($this->pageSize,$this->orientation);
    }

    /**
     * @param float $width
     * @param float $height
     */
    public function SetCustomPageSize(float $width,float $height): void {
        $this->pageSize='';
        $this->_setPageSize([$width,$height],$this->orientation);
    }

    /**
     *
     */
    function _enddoc() {
        parent::_enddoc();
        if(strlen($this->file_id)) {
            // /ID [<17c247569bd744cdb637f8d8e89baa29> <17c247569bd744cdb637f8d8e89baa29>]
            $fileId="/ID [<{$this->file_id}> <{$this->file_id}>]";
            $this->buffer=preg_replace('/\/ID[\s]\[<[0-9a-zA-Z]*>[\s]<[0-9a-zA-Z]*>\]/',$fileId,$this->buffer);
        }
        if($this->doc_creation_timestamp) {
            // /CreationDate (D:20191128130335+02'00')
            $createdAt=PdfDate::format($this->doc_creation_timestamp);
            $docCreatedAt='/CreationDate (D:'.$createdAt.')';
            $this->buffer=preg_replace('/\/CreationDate[\s]\(D\:[0-9\+\']*\)/',$docCreatedAt,$this->buffer);
        }
        if($this->doc_modification_timestamp) {
            // /ModDate (D:20191128130335+02'00')
            $modifiedAt=PdfDate::format($this->doc_modification_timestamp);
            $docModifiedAt='/ModDate (D:'.$modifiedAt.')';
            $this->buffer=preg_replace('/\/ModDate[\s]\(D\:[0-9\+\']*\)/',$docModifiedAt,$this->buffer);
        }
    }
}//END class MpdfAdapter extends mPDF implements IPdfAdapter