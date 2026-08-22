
opendxp.registerNS("opendxp.plugin.datahub.adapter.graphql");
opendxp.plugin.datahub.adapter.graphql = Class.create(opendxp.plugin.datahub.adapter.abstract, {
    openConfiguration: function (id) {
        if(this.checkIfPanelExists(id)) {
            return;
        }

        Ext.Ajax.request({
            url: "/admin/opendxpdatahub/config/get",
            params: {
                name: id
            },
            success: function (response) {
                // check again here to prevent double click problem
                if(this.checkIfPanelExists(id)) {
                    return;
                }

                let data = Ext.decode(response.responseText);

                opendxp.plugin.datahub.graphql = opendxp.plugin.datahub.graphql || {};
                opendxp.plugin.datahub.graphql.supportedQueryDataTypes = data.supportedGraphQLQueryDataTypes;
                opendxp.plugin.datahub.graphql.supportedMutationDataTypes = data.supportedGraphQLMutationDataTypes;

                let fieldPanel = new opendxp.plugin.datahub.configuration.graphql.configItem(data, this);
                opendxp.layout.refresh();
            }.bind(this)
        });
    }
});
