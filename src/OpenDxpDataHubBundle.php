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

namespace OpenDxp\Bundle\DataHubBundle;

use OpenDxp\Bundle\AdminBundle\OpenDxpAdminBundle;
use OpenDxp\Bundle\DataHubBundle\DependencyInjection\Compiler\ContainerAwarePass;
use OpenDxp\Bundle\DataHubBundle\DependencyInjection\Compiler\CustomDocumentTypePass;
use OpenDxp\Bundle\DataHubBundle\DependencyInjection\Compiler\ImportExportLocatorsPass;
use OpenDxp\Bundle\DataHubBundle\DependencyInjection\OpenDxpDataHubExtension;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\Installer\InstallerInterface;
use OpenDxp\Extension\Bundle\OpenDxpBundleAdminClassicInterface;
use OpenDxp\Extension\Bundle\Traits\BundleAdminClassicTrait;
use OpenDxp\Extension\Bundle\Traits\PackageVersionTrait;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class OpenDxpDataHubBundle extends AbstractOpenDxpBundle implements OpenDxpBundleAdminClassicInterface, DependentBundleInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public const string RUNTIME_CONTEXT_KEY = 'datahub_context';

    public const int NOT_ALLOWED_POLICY_EXCEPTION = 1;

    public const int NOT_ALLOWED_POLICY_NULL = 2;

    //TODO decide whether we want to return null here or throw an exception (maybe make this configurable?)
    public static int $notAllowedPolicy = self::NOT_ALLOWED_POLICY_NULL;

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ContainerAwarePass());
        $container->addCompilerPass(new ImportExportLocatorsPass());
        $container->addCompilerPass(new CustomDocumentTypePass());
    }

    #[Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OpenDxpDataHubExtension();
        }

        return $this->extension;
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(new OpenDxpAdminBundle(), 60);
    }

    protected function getComposerPackageName(): string
    {
        return 'iperson1337/data-hub';
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/opendxpdatahub/css/icons.css',
            '/bundles/opendxpdatahub/css/style.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/opendxpdatahub/js/datahub.js',
            '/bundles/opendxpdatahub/js/config.js',
            '/bundles/opendxpdatahub/js/adapter/abstract.js',
            '/bundles/opendxpdatahub/js/adapter/graphql.js',
            '/bundles/opendxpdatahub/js/configuration/graphql/configItem.js',
            '/bundles/opendxpdatahub/js/fieldConfigDialog.js',
            '/bundles/opendxpdatahub/js/Abstract.js',
            '/bundles/opendxpdatahub/js/mutationvalue/DefaultValue.js',
            '/bundles/opendxpdatahub/js/queryvalue/DefaultValue.js',
            '/bundles/opendxpdatahub/js/queryoperator/Alias.js',
            '/bundles/opendxpdatahub/js/queryoperator/Concatenator.js',
            '/bundles/opendxpdatahub/js/queryoperator/DateFormatter.js',
            '/bundles/opendxpdatahub/js/queryoperator/ElementCounter.js',
            '/bundles/opendxpdatahub/js/queryoperator/Text.js',
            '/bundles/opendxpdatahub/js/queryoperator/Merge.js',
            '/bundles/opendxpdatahub/js/queryoperator/Substring.js',
            '/bundles/opendxpdatahub/js/queryoperator/Thumbnail.js',
            '/bundles/opendxpdatahub/js/queryoperator/ThumbnailHtml.js',
            '/bundles/opendxpdatahub/js/queryoperator/TranslateValue.js',
            '/bundles/opendxpdatahub/js/queryoperator/Trimmer.js',
            '/bundles/opendxpdatahub/js/mutationoperator/mutationoperator.js',
            '/bundles/opendxpdatahub/js/mutationoperator/IfEmpty.js',
            '/bundles/opendxpdatahub/js/mutationoperator/LocaleSwitcher.js',
            '/bundles/opendxpdatahub/js/mutationoperator/LocaleCollector.js',
            '/bundles/opendxpdatahub/js/workspace/abstract.js',
            '/bundles/opendxpdatahub/js/workspace/document.js',
            '/bundles/opendxpdatahub/js/workspace/asset.js',
            '/bundles/opendxpdatahub/js/workspace/object.js',
        ];
    }

    /**
     * If the bundle has an installation routine, an installer is responsible of handling installation related tasks
     */
    public function getInstaller(): ?InstallerInterface
    {
        return $this->container->get(Installer::class);
    }

    /**
     * @return int
     */
    public static function getNotAllowedPolicy()
    {
        return self::$notAllowedPolicy;
    }

    /**
     * @param mixed $notAllowedPolicy
     */
    public static function setNotAllowedPolicy($notAllowedPolicy): void
    {
        self::$notAllowedPolicy = $notAllowedPolicy;
    }
}
