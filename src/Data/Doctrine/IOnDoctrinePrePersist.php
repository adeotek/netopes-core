<?php
/**
 * Doctrine entities lifecycle pre-persist event interface
 * Implement for catching entities pre-persist event interface
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
 * Interface IOnDoctrinePrePersist
 *
 * @package NETopes\Core\Data\Doctrine
 */
interface IOnDoctrinePrePersist {
    /**
     * Pre-persist event callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PrePersist
     */
    public function ExecuteOnPrePersist();
}//END interface IOnDoctrinePrePersist