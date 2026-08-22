<?php

declare(strict_types=1);

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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\Query\Operator\Factory;

use OpenDxp\Bundle\DataHubBundle\GraphQL\Query\Operator\OperatorInterface;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

abstract class DefaultOperatorFactoryBase implements OperatorFactoryInterface
{
    use ServiceTrait;

    /**
     * @var string
     */
    protected $className;

    public function __construct(Service $graphQlService, string $className)
    {
        $this->className = $className;
        $this->setGraphQLService($graphQlService);
    }

    /**
     * @param array|null $context
     */
    public function build(array $configElement = [], $context = null): OperatorInterface
    {
        $operatorImpl = new $this->className($configElement, $context);
        $operatorImpl->setGraphQlService($this->getGraphQlService());

        return $operatorImpl;
    }
}
