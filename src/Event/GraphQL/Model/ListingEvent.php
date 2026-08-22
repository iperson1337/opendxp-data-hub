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

use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Model\Listing\AbstractListing;
use Symfony\Contracts\EventDispatcher\Event;

class ListingEvent extends Event
{
    /**
     * @var AbstractListing
     */
    protected $listing;

    /**
     * @var ResolveInfo
     */
    protected $resolveInfo;

    public function getListing(): AbstractListing
    {
        return $this->listing;
    }

    public function setListing(AbstractListing $listing)
    {
        $this->listing = $listing;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function getResolveInfo(): ResolveInfo
    {
        return $this->resolveInfo;
    }

    public function setResolveInfo(ResolveInfo $resolveInfo): void
    {
        $this->resolveInfo = $resolveInfo;
    }

    /**
     * @param array $args
     * @param array $context
     */
    public function __construct(
        AbstractListing $listing,
        protected $args,
        protected $context = [],
        ?ResolveInfo $resolveInfo = null
    ) {
        $this->listing = $listing;
        $this->resolveInfo = $resolveInfo;
    }
}
