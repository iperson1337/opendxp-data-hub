# Customizing the Endpoint

The standard endpoint is
```
/opendxp-graphql-webservices/{clientname}?apikey={yourApiKey}
```

So if your configuration name is _blogdemo_ and your apikey _123456_
then your endpoint would be

```
/opendxp-graphql-webservices/blogdemo?apikey=12345
```

Here is a configuration example showing how to override the standard endpoint:

```yml
# app/config/routing.yml

# Changing URL to the explorer environement
admin_opendxpdatahub_config:
  path: /opendxp-datahub-webservices-my-endpoint/explorer/{clientname}
  defaults: { _controller: OpenDxp\Bundle\DataHubBundle\Controller\GraphQLExplorerController::explorerAction }

# Changing endoint URL
admin_opendxpdatahub_webservice:
  path: /opendxp-graphql-webservices-my-endpoint/{clientname}
  defaults: { _controller: OpenDxp\Bundle\DataHubBundle\Controller\WebserviceController::webonyxAction }
```
