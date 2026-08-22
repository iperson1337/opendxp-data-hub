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

use OpenDxp\Bundle\DataHubBundle\GraphQL\Query\QueryType;
use OpenDxp\Event\Traits\RequestAwareTrait;
use OpenDxp\Event\Traits\ResponseAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class QueryTypeEvent extends Event
{
    use RequestAwareTrait;
    use ResponseAwareTrait;

    /**
     * @var QueryType
     */
    protected $queryType;

    /**
     * @return QueryType
     */
    public function getQueryType()
    {
        return $this->queryType;
    }

    public function setQueryType(QueryType $queryType)
    {
        $this->queryType = $queryType;
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
        QueryType $queryType,
        protected $config,
        protected $context
    ) {
        $this->queryType = $queryType;
    }
}
