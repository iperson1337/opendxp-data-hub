opendxp.registerNS("opendxp.plugin.datahub.Abstract");

opendxp.plugin.datahub.Abstract = Class.create({
    type: null,
    class: null,
    objectClassId: null,
    allowedTypes: null,
    allowedParents: null,
    maxChildCount: null,

    initialize: function(classId) {
        this.objectClassId = classId;
    },

    getBaseTranslationKey: function () {
        var prefix = 'operator';

        if (this.mode == "mutation") {
            prefix = "mutation" + prefix;
        }

        return (
            this.type + "_" + this.defaultText.toLowerCase().replace(' ', '_'),
            prefix + "_" + this.defaultText.toLowerCase().replace(' ', '_')
        );
    },

    getDefaultText: function () {
        return t(this.getBaseTranslationKey());
    },

    getConfigTreeNode: function(configAttributes) {
        return {};
    },


    getCopyNode: function(source) {
        var copy = new Ext.tree.TreeNode({
            text: source.data.text,
            isTarget: true,
            leaf: true,
            configAttributes: {
                label: null,
                type: this.type,
                class: this.class
            }
        });
        return copy;
    },


    getConfigDialog: function(node, params) {
    },

    commitData: function() {
        this.window.close();
    }
});
