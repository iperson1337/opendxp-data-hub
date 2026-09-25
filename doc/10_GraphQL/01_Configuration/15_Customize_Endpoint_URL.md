# Customizing the Endpoint

The standard endpoint is
```
/opendxp-graphql-webservices/{clientname}
```

The legacy endpoint `/pimcore-graphql-webservices/{clientname}` (route `admin_opendxpdatahub_webservice_legacy`)
is still served for backwards compatibility.

The API key should be sent via the `X-API-Key` HTTP header. So if your configuration name is _blogdemo_ and your
apikey _123456_ then your endpoint would be

```
POST /opendxp-graphql-webservices/blogdemo
X-API-Key: 123456
```

> **Deprecated:** the `?apikey={yourApiKey}` query parameter is still accepted but deprecated. Use the `X-API-Key` header instead.

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
