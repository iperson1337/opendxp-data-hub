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

use GraphQL\Type\Schema;
use OpenDxp\Event\Traits\RequestAwareTrait;
use OpenDxp\Event\Traits\ResponseAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class ExecutorEvent extends Event
{
    use RequestAwareTrait;
    use ResponseAwareTrait;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     * @param bool $asString
     */
    public function setRequest($request, $asString = true)
    {
        $this->request = $asString ? (string)$request : $request;
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    public function setContext(array $context)
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @param string $query
     * @param array $context
     */
    public function __construct(
        Request $request,
        protected $query,
        Schema $schema,
        protected $context
    ) {
        $this->request = $request;
        $this->schema = $schema;
    }
}
