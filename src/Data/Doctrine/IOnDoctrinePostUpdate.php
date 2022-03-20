<?php
/**
 * Doctrine entities lifecycle post-update event interface
 * Implement for catching entities post-update event interface
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;

/**
 * IOnDoctrinePostUpdate interface
 */
interface IOnDoctrinePostUpdate {
    /**
     * Post-update event callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PostUpdate
     */
    public function ExecuteOnPostUpdate();
}//END interface IOnDoctrinePostUpdate