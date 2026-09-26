# Upgrade Notes

## Migrating from `pimcore/data-hub` to `iperson1337/opendxp-data-hub`
* Renamed bundle to `OpenDxpDataHubBundle` (composer package: `iperson1337/opendxp-data-hub`)
* Renamed top-level PHP namespace to `OpenDxp\Bundle\DataHubBundle`
* Renamed top-level config node to `opendxp_data_hub`
* Deprecations
  * Removed deprecated constant `OpenDxp\Bundle\DataHubBundle\Configuration\Dao::LEGACY_FILE`
  * Removed deprecated `datahub:graphql:rebuild-definitions` command. Use `OpenDxp\Bundle\DataHubBundle\Command\Configuration\RebuildWorkspacesCommand` instead.
