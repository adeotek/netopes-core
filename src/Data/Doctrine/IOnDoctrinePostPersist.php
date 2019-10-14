<?php
/**
 * Doctrine entities lifecycle post-persist event interface
 * Implement for catching entities post-persist event interface
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
 * Interface IOnDoctrinePostPersist
 *
 * @package NETopes\Core\Data\Doctrine
 */
interface IOnDoctrinePostPersist {
    /**
     * Post-persist event callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PostPersist
     */
    public function ExecuteOnPostPersist();
}//END interface IOnDoctrinePostPersist