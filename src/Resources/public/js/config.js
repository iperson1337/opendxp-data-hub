
opendxp.registerNS("opendxp.plugin.datahub.config");
opendxp.plugin.datahub.config = Class.create({

    importRoute: "/admin/opendxpdatahub/config/import",
    exportRoute: "/admin/opendxpdatahub/config/export",

    initialize: function () {
        this.getTabPanel();
    },

    activate: function () {
        let tabPanel = Ext.getCmp("opendxp_panel_tabs");
        tabPanel.setActiveItem(this.getTabPanel());
    },

    getTabPanel: function () {
        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "opendxp_plugin_datahub_config_tab",
                title: t("plugin_opendxp_datahub_toolbar"),
                iconCls: "plugin_opendxp_datahub_icon",
                border: false,
                layout: "border",
                closable: true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("opendxp_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("opendxp_plugin_datahub_config_tab");

            this.panel.on("destroy", function () {
                opendxp.globalmanager.remove("plugin_opendxp_datahub_config");
            }.bind(this));

            opendxp.layout.refresh();
        }

        return this.panel;
    },

    userIsAllowedToCreate: function(adapter) {
        let user = opendxp.globalmanager.get("user");
        // Only admins can add configurations
        return !!(user && user.admin === true);
    },

    getTree: function () {
        if (!this.tree) {

            var store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/opendxpdatahub/config/list',
                    reader: {
                        type: 'json'
                    }
                }
            });

            let menuItems = [];
            let firstHandler;
            const currentUser = opendxp.globalmanager.get("user");
            const isAdmin = !!(currentUser && currentUser.admin === true);

            for (let key in opendxp.plugin.datahub.adapter) {
                if( key !== 'abstract' && opendxp.plugin.datahub.adapter.hasOwnProperty( key ) && this.userIsAllowedToCreate(key)) {
                    let adapter = new opendxp.plugin.datahub.adapter[key](this);

                    if (!firstHandler) {
                        firstHandler = adapter.addConfiguration.bind(adapter, key);
                    }
                    menuItems.push(
                    {
                        text: t('plugin_opendxp_datahub_type_' + key),
                        iconCls: "plugin_opendxp_datahub_icon_" + key,
                        handler: adapter.addConfiguration.bind(adapter, key)
                    });
                }
            }

            var addConfigButton = null;
            if (isAdmin && firstHandler) {
                addConfigButton = new Ext.SplitButton({
                    text: t("plugin_opendxp_datahub_configpanel_add"),
                    iconCls: "opendxp_icon_add",
                    handler: firstHandler,
                    disabled: !opendxp.settings['data-hub-writeable'] || !firstHandler,
                    menu: menuItems,
                });
            }

            const importButton = new Ext.Button({
                tooltip: t('plugin_opendxp_datahub_import'),
                iconCls: 'opendxp_icon_upload',
                handler: function () {
                    opendxp.helpers.uploadDialog(
                        this.importRoute,
                        "Filedata",
                        function (response) {
                            response = response.response;
                            const data = Ext.decode(response.responseText);

                            if(data){
                                const editPanel = new opendxp.plugin.datahub.adapter[data.type](this);
                                editPanel.openConfiguration(data.name)
                            }
                            this.refreshTree();

                        }.bind(this),
                        function (response) {
                            response = response.response;
                            const data = Ext.decode(response.responseText);
                            Ext.MessageBox.alert(t("error"), data.message);
                        }
                    );
                }.bind(this)
            });

            this.tree = new Ext.tree.TreePanel({
                store: store,
                region: "west",
                autoScroll: true,
                animate: true,
                containerScroll: true,
                border: true,
                width: 230,
                split: true,
                root: {
                    id: '0',
                    expanded: true,
                    iconCls: "opendxp_icon_thumbnails"
                },
                rootVisible: false,
                tbar: {
                    items: (function(){
                        const items = [];
                        if (addConfigButton) { items.push(addConfigButton); }
                        if (isAdmin) { items.push(importButton); }
                        return items;
                    })()
                },
                listeners: {
                    itemclick: this.onTreeNodeClick.bind(this),
                    itemcontextmenu: this.onTreeNodeContextmenu.bind(this),
                    render: function () {
                        this.getRootNode().expand()
                    }
                }
            });
        }

        return this.tree;
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: "center"
            });
        }

        return this.editPanel;
    },


    onTreeNodeClick: function (tree, record, item, index, e, eOpts) {
        if (!record.isLeaf()) {
            return;
        }

        let adapterType = record.data.adapter;
        let adapterImpl = new opendxp.plugin.datahub.adapter[adapterType](this);
        adapterImpl.openConfiguration(record.id);
    },


    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        if (!record.isLeaf()) {
            return;
        }

        // Show no context menu for non-admins
        var user = opendxp.globalmanager.get("user");
        if (!user || user.admin !== true) {
            return;
        }

        e.stopEvent();

        tree.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "opendxp_icon_delete",
            disabled: !record.data['writeable'] || (!record.data.permissions.delete),
            handler: this.deleteConfiguration.bind(this, tree, record)
        }));

        menu.add(new Ext.menu.Item({
            text: t('clone'),
            iconCls: "opendxp_icon_clone",
            disabled: !opendxp.settings['data-hub-writeable'] || !this.userIsAllowedToCreate(record.data.adapter),
            handler: this.cloneConfiguration.bind(this, tree, record)
        }));

        menu.add(new Ext.menu.Item({
            text: t('plugin_opendxp_datahub_export'),
            iconCls: 'opendxp_icon_download',
            handler: function () {
                const recordName = record.data.id;
                opendxp.helpers.download(this.exportRoute + '?name=' + recordName);
            }.bind(this, tree, record)
        }));

        menu.showAt(e.pageX, e.pageY);
    },

    cloneConfiguration: function (tree, record) {
        let adapterType = record.data.adapter;
        let adapterImpl = new opendxp.plugin.datahub.adapter[adapterType](this);
        adapterImpl.cloneConfiguration(tree, record);
    },

    deleteConfiguration: function (tree, record) {
        let adapterType = record.data.adapter;
        let adapterImpl = new opendxp.plugin.datahub.adapter[adapterType](this);
        adapterImpl.deleteConfiguration(tree, record);
    },

    refreshTree: function() {
        this.tree.getStore().load({
            node: this.tree.getRootNode()
        });
    }

});
