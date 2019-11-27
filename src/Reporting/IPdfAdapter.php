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
     * @return void
     */
    public function Render(?array $params=NULL);

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
     * Get content elements (HTML data)
     *
     * @return array Document HTML data
     */
    public function getContent(): array;

    /**
     * Set content element (HTML data)
     *
     * @param string   $content
     * @param int|null $page
     */
    public function SetContent(string $content,?int $page=0): void;

    /**
     * Add content element (HTML data)
     *
     * @param string   $content
     * @param int|null $page
     */
    public function AddContent(string $content,int $page=0): void;

    /**
     * Set content elements (HTML data)
     *
     * @param array $contents
     */
    public function AddContents(array $contents): void;

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
     * @param float $timestamp
     */
    public function SetModificationTimestamp(float $timestamp): void;

    /**
     * @param string|null $fileId
     */
    public function SetFileId(?string $fileId): void;
}//END interface IPdfAdapter