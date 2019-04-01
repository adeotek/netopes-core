<?php
/**
 * Doctrine entities lifecycle pre-update event interface
 * Implement for catching entities pre-update event interface
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data\Doctrine;
/**
 * Interface IOnDoctrinePreUpdate
 *
 * @package NETopes\Core\Data\Doctrine
 */
interface IOnDoctrinePreUpdate {
    /**
     * Pre-update callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PreUpdate
     */
    public function ExecuteOnPreUpdate();
}//END interface IOnDoctrinePreUpdate