<?php declare(strict_types=1);

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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\SharedType;

use Exception;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils as GraphQLUtils;

class JsonType extends ScalarType
{
    public function serialize(mixed $value): string
    {
        return json_encode($value);
    }

    public function parseValue(mixed $value): mixed
    {
        return json_decode((string) $value);
    }

    public function parseLiteral(mixed $valueNode, ?array $variables = null): mixed
    {
        if (! property_exists($valueNode, 'value')) {
            throw new Exception('Can only parse objects with a value property. Input: ' . GraphQLUtils::printSafeJson($valueNode));
        }

        return json_decode((string) $valueNode->value);
    }
}
