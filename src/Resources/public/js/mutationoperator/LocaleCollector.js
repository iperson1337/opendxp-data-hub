opendxp.registerNS("opendxp.plugin.datahub.mutationoperator.localecollector");

opendxp.plugin.datahub.mutationoperator.localecollector = Class.create(opendxp.plugin.datahub.mutationoperator.mutationoperator, {
    class: "LocaleCollector",
    iconCls: "plugin_opendxp_datahub_icon_localecollector",
    defaultText: "Locale Collector",
    group: "other",
    hasTooltip: true,

    allowChild: function (targetNode, dropNode) {
        return (
            !targetNode.childNodes.length > 0
            && in_array(dropNode.data.dataType, [
                "booleanSelect",
                "checkbox",
                "country",
                "countrymultiselect",
                "date",
                "datetime",
                "email",
                "externalImage",
                "geopoint",
                "firstname",
                "gender",
                "input",
                "image",
                "language",
                "lastname",
                "newsletterActive",
                "manyToOneRelation",
                "multiselect",
                "newsletterConfirmed",
                "numeric",
                "select",
                "slider",
                "textarea",
                "time",
                "wysiwyg"
            ])
        );
    }
});
