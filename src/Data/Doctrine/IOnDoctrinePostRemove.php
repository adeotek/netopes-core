<?php
/**
 * Doctrine entities lifecycle post-persist event interface
 * Implement for catching entities post-remove event interface
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;

/**
 * IOnDoctrinePostRemove interface
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