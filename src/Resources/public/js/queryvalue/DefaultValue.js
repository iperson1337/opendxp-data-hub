opendxp.registerNS("opendxp.plugin.datahub.queryvalue.defaultvalue");

opendxp.plugin.datahub.queryvalue.defaultvalue = Class.create(opendxp.plugin.datahub.Abstract, {

    type: "value",
    class: "DefaultValue",

    getConfigTreeNode: function(configAttributes) {
        var node = {
            draggable: true,
            iconCls: "opendxp_icon_" + configAttributes.dataType,
            text: configAttributes.label,
            configAttributes: configAttributes,
            isTarget: true,
            leaf: true
        };

        return node;
    },

    getCopyNode: function(source) {
        var copy = source.createNode({
            iconCls: source.data.iconCls,
            text: source.data.text,
            isTarget: true,
            leaf: true,
            dataType: source.data.dataType,
            qtip: source.data.key,
            key: source.data.key,
            isOperator: source.data.isOperator ?? false,
            configAttributes: {
                label: source.data.text,
                type: this.type,
                class: this.class,
                attribute: source.data.key,
                dataType: source.data.dataType
            }
        });
        return copy;
    },

    getConfigDialog: function(node, params) {
        return null;
    },

    commitData: function(params) {
        if(this.radiogroup.getValue().rb == "custom") {
            this.node.data.configAttributes.label = this.textfield.getValue();
            this.node.set('text', this.textfield.getValue());
        } else {
            this.node.data.configAttributes.label = this.node.get('text');
        }
        this.window.close();
        if (params && params.callback) {
            params.callback();
        }
    }
});
