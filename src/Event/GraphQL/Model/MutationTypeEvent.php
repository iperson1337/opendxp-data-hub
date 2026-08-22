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

use OpenDxp\Bundle\DataHubBundle\GraphQL\Mutation\MutationType;
use OpenDxp\Event\Traits\RequestAwareTrait;
use OpenDxp\Event\Traits\ResponseAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class MutationTypeEvent extends Event
{
    use RequestAwareTrait;
    use ResponseAwareTrait;

    /**
     * @var MutationType
     */
    protected $mutationType;

    /**
     * @return MutationType
     */
    public function getMutationType()
    {
        return $this->mutationType;
    }

    public function setMutationType(MutationType $mutationType)
    {
        $this->mutationType = $mutationType;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(
        MutationType $mutationType,
        protected $config,
        protected $context
    ) {
        $this->mutationType = $mutationType;
    }
}
