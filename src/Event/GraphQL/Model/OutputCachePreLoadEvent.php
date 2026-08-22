<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model;

use OpenDxp\Event\Traits\RequestAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class OutputCachePreLoadEvent extends Event
{
    use RequestAwareTrait;

    /**
     * @var bool
     */
    protected $useCache;

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return bool
     */
    public function isUseCache()
    {
        return $this->useCache;
    }

    public function setUseCache(bool $useCache)
    {
        $this->useCache = $useCache;
    }

    public function __construct(Request $request, bool $useCache)
    {
        $this->request = $request;
        $this->useCache = $useCache;
    }
}
