<?php
/**
 * Doctrine entities lifecycle post-load event interface
 *
 * Implement for catching entities post-load event interface
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
/**
 * Interface IOnDoctrinePostLoad
 *
 * @package NETopes\Core\Data
 */
interface IOnDoctrinePostLoad {
    /**
     * Post-load event callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PrePersist
     */
    public function ExecuteOnPostLoad();
}//END interface IOnDoctrinePostLoad