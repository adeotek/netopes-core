<?php
/**
 * Doctrine entities lifecycle post-persist event interface
 * Implement for catching entities post-remove event interface
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
 * Interface IOnDoctrinePostRemove
 *
 * @package NETopes\Core\Data\Doctrine
 */
interface IOnDoctrinePostRemove {
    /**
     * Post-remove event callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PostRemove
     */
    public function ExecuteOnPostRemove();
}//END interface IOnDoctrinePostRemove