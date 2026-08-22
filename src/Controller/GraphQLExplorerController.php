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

namespace OpenDxp\Bundle\DataHubBundle\Controller;

use DateTime;
use Exception;
use OpenDxp\Bundle\DataHubBundle\Service\CheckConsumerPermissionsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class GraphQLExplorerController extends AbstractController
{
    /**
     * @throws Exception
     */
    public function explorerAction(RouterInterface $routingService, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $urlParams = array_merge($request->request->all(), $request->query->all());

        $clientName = $request->attributes->getString('clientname');

        $url = $routingService->generate('admin_opendxpdatahub_webservice', ['clientname' => $clientName]);

        if (!$url) {
            throw new Exception('unable to resolve');
        }

        if ($urlParams) {
            $url = $url . '?' . http_build_query($urlParams);
        }

        $response = $this->render('@OpenDxpDataHub/Feature/explorer.html.twig', [
            'graphQLUrl' => $url,
            'tokenHeader' => CheckConsumerPermissionsService::TOKEN_HEADER,
        ]);

        $response->setPublic();
        $response->setExpires(new DateTime('tomorrow'));

        return $response;
    }
}
