<?php
/**
 * PdfBuilder class file
 *
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.10.1
 * @filesource
 */
namespace NETopes\Core\Reporting;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\Data\TPlaceholdersManipulation;

/**
 * Class PdfBuilder
 *
 * @package NETopes\Core\Reporting
 */
class PdfBuilder {
    use TPlaceholdersManipulation;

    /**
     * @var bool Skip labels
     */
    public $skipLabels=FALSE;

    /**
     * @var \NETopes\Core\Reporting\IPdfAdapter|null
     */
    protected $pdf=NULL;

    /**
     * PdfBaseBuilder constructor.
     *
     * @param array       $params
     * @param string|null $adapterClass
     * @throws \NETopes\Core\AppException
     */
    public function __construct(array $params=[],?string $adapterClass=NULL) {
        if(strlen($adapterClass)) {
            if(!class_exists($adapterClass)) {
                throw new AppException("Invalid PDF adapter class [{$adapterClass}]!");
            }
        } else {
            $adapterClass=AppConfig::GetValue('pdf_adapter_class');
            if(strlen($adapterClass)) {
                if(!class_exists($adapterClass)) {
                    throw new AppException("Invalid PDF adapter class [{$adapterClass}]!");
                }
            } else {
                $adapterClass=TcpdfAdapter::class;
            }
        }
        $this->pdf=new $adapterClass($params);
    }//END public function __construct

    /**
     * @return \NETopes\Core\Reporting\IPdfAdapter
     */
    public function GetPdf(): IPdfAdapter {
        return $this->pdf;
    }

    /**
     * @return string Document HTML data
     */
    public function GetOrientation(): string {
        return $this->pdf->orientation;
    }

    /**
     * @param string Document HTML data $orientation the orientation
     */
    public function SetOrientation(string $orientation): void {
        $this->pdf->orientation=$orientation;
    }

    /**
     * @return string Document HTML data
     */
    public function GetUnit(): string {
        return $this->pdf->unit;
    }

    /**
     * @param string Document HTML data $orientation the orientation
     */
    public function SetUnit(string $unit): void {
        $this->pdf->unit=$unit;
    }

    /**
     * @return string Document HTML data
     */
    public function GetPageSize(): string {
        return $this->pdf->pageSize;
    }

    /**
     * @param string Document HTML data $pageSize the page size
     */
    public function SetPageSize(string $pageSize): void {
        $this->pdf->SetPageSize($pageSize);
    }

    /**
     * @param float $width
     * @param float $height
     */
    public function SetCustomPageSize(float $width,float $height): void {
        $this->pdf->SetCustomPageSize($width,$height);
    }

    /**
     * @return bool Unicode PDF initialization value
     */
    public function GetUnicode(): bool {
        return $this->pdf->unicode;
    }

    /**
     * @param string $unicode
     */
    public function SetUnicode(string $unicode): void {
        $this->pdf->unicode=$unicode;
    }

    /**
     * @return string Charset PDF initialization value
     */
    public function GetCharset(): string {
        return $this->pdf->charset;
    }

    /**
     * @param string Charset PDF initialization value $charset the charset
     */
    public function SetCharset(string $charset): void {
        $this->pdf->charset=$charset;
    }

    /**
     * @return string|null
     */
    public function GetFileName(): ?string {
        return $this->pdf->GetFileName();
    }

    /**
     * @param string|null $fileName the filename
     */
    public function SetFileName(?string $fileName): void {
        $this->pdf->SetFileName($fileName);
    }

    /**
     * @param string   $family
     * @param string   $style
     * @param int|null $size
     * @throws \NETopes\Core\AppException
     */
    public function SetFont(string $family,string $style='',?int $size=NULL) {
        $this->pdf->SetActiveFont($family,$style,$size);
    }

    /**
     * @return string|null
     */
    public function GetTitle(): ?string {
        return $this->pdf->GetTitle();
    }

    /**
     * @param string|null $title
     */
    public function SetTitle(?string $title): void {
        $this->pdf->SetTitle($title);
    }

    /**
     * Add new page
     */
    public function AddPage(): void {
        $this->pdf->AddNewPage();
    }//END public function AddPage

    /**
     * Get CSS styles (CSS data)
     *
     * @return string|null Document CSS data
     */
    public function GetCssStyles(): ?string {
        return $this->pdf->GetCssStyles();
    }//END public function GetContent

    /**
     * Set CSS styles (CSS data)
     *
     * @param string|null $cssStyles Document CSS data
     */
    public function SetCssStyles(?string $cssStyles): void {
        $this->pdf->SetCssStyles($cssStyles);
    }//END public function SetCssStyles

    /**
     * Add CSS styles (CSS data)
     *
     * @param string $cssStyles Document CSS data
     */
    public function AddCssStyles(string $cssStyles): void {
        $this->pdf->AddCssStyles($cssStyles);
    }//END public function AddCssStyles

    /**
     * Get content elements (HTML data)
     *
     * @return array Document HTML data
     */
    public function GetContent(): array {
        return $this->pdf->GetContent();
    }//END public function GetContent

    /**
     * Set content element (HTML data)
     *
     * @param string      $content
     * @param array       $params
     * @param int|null    $page
     * @param string|null $pageHeader
     * @param string|null $orientation
     * @return int
     */
    public function SetContent(string $content,array $params=[],?int $page=NULL,?string $pageHeader=NULL,?string $orientation=NULL): int {
        $content=$this->ReplacePlaceholders($content,$params,TRUE,$this->skipLabels);
        return $this->pdf->SetContent($content,$page,$pageHeader,$orientation);
    }//END public function setContent

    /**
     * Set content element (HTML data)
     *
     * @param string      $content
     * @param array       $params
     * @param int|null    $page
     * @param string|null $pageHeader
     * @param string|null $orientation
     * @return int
     */
    public function AddContent(string $content,array $params=[],?int $page=NULL,?string $pageHeader=NULL,?string $orientation=NULL): int {
        $content=$this->ReplacePlaceholders($content,$params,TRUE,$this->skipLabels);
        return $this->pdf->AddContent($content,$page,$pageHeader,$orientation);
    }//END public function AddContent

    /**
     * Set content elements (HTML data)
     *
     * @param array       $contents
     * @param array       $params
     * @param int|null    $startPage
     * @param string|null $pageHeader
     * @return int|null
     */
    public function AddContents(array $contents,array $params=[],?int $startPage=NULL,?string $pageHeader=NULL): ?int {
        foreach($contents as $content) {
            $content=$this->ReplacePlaceholders($content,$params,TRUE,$this->skipLabels);
            $startPage=$this->pdf->AddContent($content,$startPage,$pageHeader) + 1;
        }//END foreach
        return $startPage;
    }//END public function AddContents

    /**
     * Get content last index
     *
     * @return int|null
     */
    public function GetLastPage(): ?int {
        return $this->pdf->GetLastPage();
    }//END public function GetLastPage

    /**
     * @return bool Custom header
     */
    public function GetCustomHeader(): bool {
        return $this->pdf->customHeader;
    }//END public function GetCustomHeader

    /**
     * @param bool $customHeader
     */
    public function SetCustomHeader(bool $customHeader): void {
        $this->pdf->customHeader=$customHeader;
    }//END public function SetCustomHeader

    /**
     * @return bool Custom footer
     */
    public function GetCustomFooter(): bool {
        return $this->pdf->customFooter;
    }//END public function GetCustomFooter

    /**
     * @param bool $customFooter
     */
    public function SetCustomFooter(bool $customFooter): void {
        $this->pdf->customFooter=$customFooter;
    }//END public function SetCustomFooter

    /**
     * @return array Custom header params
     */
    public function GetCustomHeaderParams(): array {
        return $this->pdf->customHeaderParams;
    }//END public function GetCustomHeaderParams

    /**
     * @param array $customHeaderParams
     */
    public function SetCustomHeaderParams(array $customHeaderParams): void {
        $this->pdf->customHeaderParams=$customHeaderParams;
    }//END public function SetCustomHeaderParams

    /**
     * @return array Custom footer params
     */
    public function GetCustomFooterParams(): array {
        return $this->pdf->customFooterParams;
    }//END public function GetCustomFooterParams

    /**
     * @param array $customFooterParams
     */
    public function SetCustomFooterParams(array $customFooterParams): void {
        $this->pdf->customFooterParams=$customFooterParams;
    }//END public function SetCustomFooterParams

    /**
     * @param array|null $params
     * @return void
     */
    public function SetHeader(?array $params=NULL) {
        if(is_array($params)) {
            $params['html']=$this->ReplacePlaceholders(get_array_value($params,'html','','is_notempty_string'),get_array_value($params,'params',[],'is_array'));
            unset($params['params']);
        }
        $this->pdf->SetCustomHeader($params);
    }//END public function SetHeader

    /**
     * @param array|null $params
     * @return void
     */
    public function SetFooter(?array $params=NULL) {
        if(is_array($params)) {
            $params['html']=$this->ReplacePlaceholders(get_array_value($params,'html','','is_notempty_string'),get_array_value($params,'params',[],'is_array'));
            unset($params['params']);
        }
        $this->pdf->SetCustomFooter($params);
    }//END public function SetFooter

    /**
     * @param array|null $params
     * @return mixed
     */
    public function GetOutput(?array $params=NULL) {
        return $this->pdf->GetOutput($params);
    }//END public function GetOutput

    /**
     * @param array|null $params
     * @return void
     */
    public function Render(?array $params=NULL) {
        $this->pdf->Render($params);
    }//END public function Render
}//END class PdfBuilder