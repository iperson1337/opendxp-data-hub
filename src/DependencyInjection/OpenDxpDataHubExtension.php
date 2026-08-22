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

namespace OpenDxp\Bundle\DataHubBundle\DependencyInjection;

use OpenDxp\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use OpenDxp\Bundle\DataHubBundle\Configuration\Dao;
use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class OpenDxpDataHubExtension extends Extension implements PrependExtensionInterface
{
    #[Override]
    public function getAlias(): string
    {
        return 'opendxp_data_hub';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('opendxp_data_hub', $config);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('config.yml');
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('doctrine_migrations')) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../Resources/config')
            );

            $loader->load('doctrine_migrations.yml');
        }

        $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, 'opendxp_data_hub');
        $configDir = $containerConfig['config_location']['data_hub']['write_target']['options']['directory'];

        $configLoader = new YamlFileLoader(
            $container,
            new FileLocator([$configDir, Dao::CONFIG_PATH])
        );

        $configLocator = new \OpenDxp\Bundle\DataHubBundle\Configuration\DatahubConfigLocator();
        $configs =
            [
                ...ConfigurationHelper::getSymfonyConfigFiles($configDir),
                ...ConfigurationHelper::getSymfonyConfigFiles($_SERVER['OPENDXP_CONFIG_STORAGE_DIR_DATA_HUB'] ?? ''),
                ...$configLocator->locate('config'),
            ];

        foreach ($configs as $config) {
            $configLoader->load($config);
        }
    }
}
