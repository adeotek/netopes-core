<?php
/**
 * IPdfAdapter interface file
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

/**
 * Interface IPdfAdapter
 *
 * @package NETopes\Core\Reporting
 */
interface IPdfAdapter {

    /**
     * IPdfAdapter constructor.
     */
    public function __construct();

    /**
     * @param array|null $params
     * @return mixed
     */
    public function GetOutput(?array $params=NULL);

    /**
     * @param array|null $params
     * @return string|null
     */
    public function Render(?array $params=NULL): ?string;

    /**
     * @param array|null $params
     * @return mixed
     */
    public function SetCustomHeader(?array $params=NULL);

    /**
     * @param array|null $params
     * @return mixed
     */
    public function SetCustomFooter(?array $params=NULL);

    /**
     * Add new page to PDF
     *
     * @param string $orientation
     * @return void
     */
    public function AddNewPage(string $orientation=''): void;

    /**
     * Get CSS styles (CSS data)
     *
     * @return string|null Document CSS data
     */
    public function GetCssStyles(): ?string;

    /**
     * Set CSS styles (CSS data)
     *
     * @param string|null $cssStyles Document CSS data
     */
    public function SetCssStyles(?string $cssStyles): void;

    /**
     * Add CSS styles (CSS data)
     *
     * @param string $cssStyles Document CSS data
     */
    public function AddCssStyles(string $cssStyles): void;

    /**
     * Get content elements (HTML data)
     *
     * @return array Document HTML data
     */
    public function GetContent(): array;

    /**
     * Set content element (HTML data)
     *
     * @param string      $content
     * @param int|null    $page
     * @param string|null $pageHeader
     * @param string|null $orientation
     * @return int
     */
    public function SetContent(string $content,?int $page=NULL,?string $pageHeader=NULL,?string $orientation=NULL): int;

    /**
     * Add content element (HTML data)
     *
     * @param string      $content
     * @param int|null    $page
     * @param string|null $pageHeader
     * @param string|null $orientation
     * @return int
     */
    public function AddContent(string $content,?int $page=NULL,?string $pageHeader=NULL,?string $orientation=NULL): int;

    /**
     * Set content elements (HTML data)
     *
     * @param array       $contents
     * @param int|null    $startPage
     * @param string|null $pageHeader
     * @return int
     */
    public function AddContents(array $contents,?int $startPage=NULL,?string $pageHeader=NULL): int;

    /**
     * Get content last index
     *
     * @return int|null
     */
    public function GetLastPage(): ?int;

    /**
     * @return string|null
     */
    public function GetTitle();

    /**
     * @param string|null $title
     */
    public function SetTitle($title);

    /**
     * @return string|null
     */
    public function GetFileName(): ?string;

    /**
     * @param string|null $fileName
     */
    public function SetFileName(?string $fileName);

    /**
     * @param string   $family
     * @param string   $style
     * @param int|null $size
     * @throws \NETopes\Core\AppException
     */
    public function SetActiveFont(string $family,string $style='',?int $size=NULL);

    /**
     * @param \DateTime|null $modifiedDate
     * @param \DateTime|null $createDate
     */
    public function SetDocumentDate(?DateTime $modifiedDate,?DateTime $createDate=NULL): void;

    /**
     * @param string|null $fileId
     */
    public function SetFileId(?string $fileId): void;

    /**
     * @param string $pageSize
     */
    public function SetPageSize(string $pageSize): void;

    /**
     * @param float $width
     * @param float $height
     */
    public function SetCustomPageSize(float $width,float $height): void;

    /**
     * @param string      $content
     * @param string|null $name
     * @param string      $dest
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    public function OutputContent(string $content,?string $name=NULL,string $dest='I'): ?string;
}//END interface IPdfAdapter