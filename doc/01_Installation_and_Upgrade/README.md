# Installation 

## Bundle Installation

To install OpenDxp Datahub follow the three steps below:

1. Install the required dependencies:
```bash
composer require open-dxp/data-hub-bundle
```

2. Make sure the bundle is enabled in the `config/bundles.php` file. The following lines should be added:

```php
use OpenDxp\Bundle\DataHubBundle\OpenDxpDataHubBundle;
// ...

return [
    // ...
    OpenDxpDataHubBundle::class => ['all' => true],
    // ...
];
```

3. Install the bundle:

```bash
bin/console opendxp:bundle:install OpenDxpDataHubBundle
```

## Required Backend User Permission
To access Datahub, user needs to meet one of following criteria:  
* be an `admin`
* have `plugin_datahub_config` permission
