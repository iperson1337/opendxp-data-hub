
opendxp.registerNS("opendxp.plugin.datahub.adapter.abstract");
opendxp.plugin.datahub.adapter.abstract = Class.create({
    initialize: function (configPanel) {
        this.configPanel = configPanel;
    },

    addConfiguration: function (type) {
        Ext.MessageBox.prompt(t('plugin_opendxp_datahub_configpanel_enterkey_title'), t('plugin_opendxp_datahub_configpanel_enterkey_prompt'), this.addConfigurationComplete.bind(this, type), null, null, "");
    },

    addConfigurationComplete: function (type, button, value, object) {
        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 2 && value.length <= 80 && regresult == value) {
            Ext.Ajax.request({
                url: "/admin/opendxpdatahub/config/add",
                params: {
                    name: value,
                    type: type
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    this.configPanel.refreshTree();

                    if (!data || !data.success) {
                        opendxp.helpers.showNotification(t("error"), t("plugin_opendxp_datahub_configpanel_error_adding_config") + ': <br/>' + data.message, "error");
                    } else {
                        this.openConfiguration(data.name);
                    }

                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t("plugin_opendxp_datahub_configpanel"), value.length <= 80 ? t("plugin_opendxp_datahub_configpanel_invalid_name") : t("plugin_opendxp_datahub_configpanel_invalid_length"));
        }
    },

    openConfiguration: function (id) {
        this.checkIfPanelExists(id);
    },

    cloneConfiguration: function (tree, record) {
        Ext.MessageBox.prompt(t('plugin_opendxp_datahub_configpanel_enterclonekey_title'), t('plugin_opendxp_datahub_configpanel_enterclonekey_enterclonekey_prompt'),
            this.cloneConfigurationComplete.bind(this, tree, record), null, null, "");
    },

    cloneConfigurationComplete: function (tree, record, button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 2 && value.length <= 80 && regresult == value) {
            Ext.Ajax.request({
                url: "/admin/opendxpdatahub/config/clone",
                params: {
                    name: value,
                    originalName: record.data.id
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.configPanel.refreshTree();

                    if (!data || !data.success) {
                        opendxp.helpers.showNotification(t("error"), t("plugin_opendxp_datahub_configpanel_error_cloning_config") + ': <br/>' + data.message, "error");
                    } else {
                        this.openConfiguration(data.name, tree, record);
                    }

                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t("plugin_opendxp_datahub_configpanel"), value.length <= 80 ? t("plugin_opendxp_datahub_configpanel_invalid_name") : t("plugin_opendxp_datahub_configpanel_invalid_length"));
        }
    },

    deleteConfiguration: function (tree, record) {
        Ext.Msg.confirm(t('delete'), t('delete_message'), function (btn) {
            if (btn == 'yes') {
                Ext.Ajax.request({
                    url: "/admin/opendxpdatahub/config/delete",
                    params: {
                        name: record.data.id
                    }
                });

                this.configPanel.getEditPanel().removeAll();
                record.remove();
            }
        }.bind(this));
    },

    checkIfPanelExists: function(id) {
        let existingPanel = Ext.getCmp("plugin_opendxp_datahub_configpanel_panel_" + id);
        if(existingPanel) {
            this.configPanel.editPanel.setActiveTab(existingPanel);
            return true;
        }
        return false;
    }
});
