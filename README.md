# OpenDXP Datahub

***

## Disclaimer

> OpenDXP is a community-driven fork based on the Pimcore® Community Edition (GPLv3).  
> OpenDXP is independent and maintained by its community and contributors.
> It is not affiliated with, endorsed by, or sponsored by Pimcore GmbH.   
> Original credits: [Pimcore GmbH](https://www.pimcore.com)

**OpenDXP DataHub Bundle is based on the Pimcore® Community Edition and remains licensed under GPLv3.**

***

OpenDXP Datahub (data delivery and consumption platform) integrates different input & output channel
technologies into a simple & easy-to-configure system on top of OpenDXP.

The basic configuration of Datahub comes with a GraphQL API, which is described in the next sections of this documentation. To use another configuration, OpenDXP Datahub can be extended with different adapters (see [Further Information](#further-information)).

A short introduction video of an output channel based on the GraphQL query language can be found [here](./doc/img/graphql/intro.mp4).

## Features in a Nutshell
- Easy-to-configure interface layer for data delivery and consumption
- Tool of choice to connect OpenDXP to any other systems and applications besides internal PHP API - whether they are backend applications like ERP systems or frontend applications like your storefront
- Multiple endpoints definition for different use cases and target/source systems
- Central and easy-to-use GUI to transform and prepare data for defined endpoints
- To-be-exposed data restriction to endpoints by defining workspaces and schemas.

## Documentation Overview
- [Installation](./doc/01_Installation_and_Upgrade/README.md)
- [Basic principle](./doc/02_Basic_Principle.md) for configuring an endpoint
- [GraphQL](./doc/10_GraphQL/README.md) [*default and recommended endpoint*]
- [Configuration & Deployment](./doc/20_Deployment.md)
- [Testing](./doc/30_Testing.md)

***

## Upstream Origin & Version Transparency
This project is a fork of the [Pimcore data-hub (9d70ca1 / v1.9.5)](https://github.com/pimcore/data-hub/tree/9d70ca14788e25e34d49010ed00206ebaf761155), which is © Pimcore GmbH and licensed under GPLv3.

## License
Licensed under the GNU General Public License v3.0 (GPLv3). For details, please see [LICENSE.md](LICENSE.md).

## Copyright
© Pimcore GmbH  
© 2026 OpenDXP Contributors — GPLv3

## Trademarks
Pimcore® is a registered [trademark](https://www.trademarkelite.com/europe/trademark/trademark-detail/009309841/PIMCORE) of Pimcore GmbH.
Any use of the Pimcore® mark in this repository is purely descriptive to identify the original upstream project.

***

## Contact
For inquiries, suggestions, or contributions, feel free to reach us at contact@opendxp.io.

## About
OpenDXP is a community-driven project initiated by [DACHCOM.DIGITAL](https://www.dachcom.com/de-ch) (Rheineck, Switzerland) and maintained by its community and contributors.
OpenDXP is independent and not affiliated with Pimcore GmbH.

The project’s purpose is to preserve and maintain a GPLv3‑licensed codebase for community use.

It is **not positioned as a competitor** to products or services of Pimcore GmbH and does **not** purport to replace or supersede any Pimcore offering.   
