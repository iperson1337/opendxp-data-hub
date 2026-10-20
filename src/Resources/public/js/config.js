
opendxp.registerNS("opendxp.plugin.datahub.config");
opendxp.plugin.datahub.config = Class.create({

    importRoute: "/admin/opendxpdatahub/config/import",
    exportRoute: "/admin/opendxpdatahub/config/export",
    runningExecutionsRoute: "/admin/opendxpdatahub/config/running-executions",

    // Событие на document после каждого опроса индикатора «Идёт выполнение», detail: {items}.
    // Панели адаптеров могут слушать его вместо собственного опроса прогресса.
    runningExecutionsEvent: "opendxp.datahub.runningExecutions",

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
                clearTimeout(this.runningExecutionsHandle);
                document.removeEventListener("visibilitychange", this.visibilityListener);
                opendxp.globalmanager.remove("plugin_opendxp_datahub_config");
            }.bind(this));

            if (this.getRunningExecutionsSettings().enabled) {
                this.panel.on("activate", this.updateRunningExecutions.bind(this));
                this.visibilityListener = function () {
                    if (!document.hidden) {
                        this.updateRunningExecutions();
                    }
                }.bind(this);
                document.addEventListener("visibilitychange", this.visibilityListener);
                this.updateRunningExecutions();
            }

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
                dockedItems: [this.getRunningExecutionsBar()],
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

            // refreshTree() пересоздаёт узлы — значки выполнения надо навесить заново.
            store.on("load", function () {
                this.decoratedNodes = {};
                this.decorateRunningNodes(this.runningExecutions || []);
            }.bind(this));
        }

        return this.tree;
    },

    getRunningExecutionsBar: function () {
        if (!this.runningExecutionsBar) {
            this.runningExecutionsBar = new Ext.toolbar.Toolbar({
                dock: "bottom",
                hidden: true,
                layout: {
                    type: "vbox",
                    align: "stretch"
                },
                cls: "plugin_opendxp_datahub_running_executions",
                items: [{
                    xtype: "component",
                    itemId: "title",
                    html: ""
                }]
            });
            this.runningExecutionBars = {};
        }

        return this.runningExecutionsBar;
    },

    getRunningExecutionsSettings: function () {
        // Интервалы опроса — opendxp_data_hub.running_executions (AdminListener).
        return Ext.apply({
            enabled: true,
            activePollInterval: 10000,
            idlePollInterval: 30000
        }, opendxp.settings["data-hub-running-executions"] || {});
    },

    isPanelVisible: function () {
        const tabPanel = Ext.getCmp("opendxp_panel_tabs");
        return !document.hidden && !!tabPanel && tabPanel.getActiveTab() === this.panel;
    },

    scheduleRunningExecutionsUpdate: function (delay) {
        clearTimeout(this.runningExecutionsHandle);
        this.runningExecutionsHandle = setTimeout(this.updateRunningExecutions.bind(this), delay);
    },

    updateRunningExecutions: function () {
        if (!this.panel || this.panel.destroyed) {
            return;
        }

        // Скрытая вкладка браузера или неактивная вкладка Data Hub не опрашивает сервер:
        // как только её откроют, сработает activate / visibilitychange.
        if (!this.isPanelVisible()) {
            clearTimeout(this.runningExecutionsHandle);
            return;
        }

        Ext.Ajax.request({
            url: this.runningExecutionsRoute,
            method: "GET",
            success: function (response) {
                const data = Ext.decode(response.responseText);
                const items = (data && data.items) || [];
                const settings = this.getRunningExecutionsSettings();
                this.renderRunningExecutions(items);
                document.dispatchEvent(new CustomEvent(this.runningExecutionsEvent, {detail: {items: items}}));
                this.scheduleRunningExecutionsUpdate(
                    items.length ? settings.activePollInterval : settings.idlePollInterval
                );
            }.bind(this),
            failure: function () {
                this.scheduleRunningExecutionsUpdate(this.getRunningExecutionsSettings().idlePollInterval);
            }.bind(this)
        });
    },

    renderRunningExecutions: function (items) {
        if (this.panel.destroyed) {
            return;
        }

        const bar = this.getRunningExecutionsBar();
        const seen = {};

        items.forEach(function (item) {
            seen[item.name] = true;
            // total = 0 — поставщик ещё не знает объём работы (например, файл только разбирается).
            const text = Ext.String.htmlEncode(item.name)
                + (item.total > 0 ? ": " + item.processed + " / " + item.total : "");
            const tooltip = item.message ? Ext.String.htmlEncode(item.message) : text;

            let progressBar = this.runningExecutionBars[item.name];
            if (!progressBar) {
                progressBar = bar.add({
                    xtype: "progressbar",
                    margin: "4 0 0 0",
                    cls: "plugin_opendxp_datahub_running_execution",
                    listeners: {
                        render: function (component) {
                            component.getEl().on("click", this.openRunningConfiguration.bind(this, item.name));
                            component.getEl().set({"data-qtip": tooltip});
                        }.bind(this)
                    }
                });
                this.runningExecutionBars[item.name] = progressBar;
            }
            progressBar.updateProgress(item.progress, text);
            progressBar.getEl() && progressBar.getEl().set({"data-qtip": tooltip});
        }, this);

        Ext.Object.each(this.runningExecutionBars, function (name, progressBar) {
            if (!seen[name]) {
                bar.remove(progressBar);
                delete this.runningExecutionBars[name];
            }
        }, this);

        bar.getComponent("title").setHtml(
            "<b>" + t("plugin_opendxp_datahub_running_executions") + " (" + items.length + ")</b>"
        );
        bar.setVisible(items.length > 0);

        this.runningExecutions = items;
        this.decorateRunningNodes(items);
    },

    decorateRunningNodes: function (items) {
        if (!this.tree) {
            return;
        }

        const store = this.tree.getStore();
        const running = {};
        this.decoratedNodes = this.decoratedNodes || {};

        // Только commit-изменения: у store включён autoSync, «грязная» запись ушла бы на сервер.
        store.suspendAutoSync();

        items.forEach(function (item) {
            running[item.name] = true;
            const record = store.getNodeById(item.name);
            if (!record) {
                return;
            }

            if (!this.decoratedNodes[item.name]) {
                this.decoratedNodes[item.name] = {
                    text: record.get("text"),
                    iconCls: record.get("iconCls")
                };
            }

            const original = this.decoratedNodes[item.name];
            record.set({
                text: original.text + ' <span class="plugin_opendxp_datahub_running_execution_percent">'
                    + Math.round(item.progress * 100) + '%</span>',
                iconCls: "opendxp_icon_loading"
            }, {commit: true});
        }, this);

        Ext.Object.each(this.decoratedNodes, function (name, original) {
            if (running[name]) {
                return;
            }
            const record = store.getNodeById(name);
            if (record) {
                record.set(original, {commit: true});
            }
            delete this.decoratedNodes[name];
        }, this);

        store.resumeAutoSync();
    },

    openRunningConfiguration: function (name) {
        const record = this.tree.getStore().getNodeById(name);
        if (record) {
            this.openConfigurationOnce(record);
        }
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: "center"
            });

            this.editPanel.on("add", function (panel, component) {
                const prefix = "plugin_opendxp_datahub_configpanel_panel_";
                if (this.openingConfigurations && component.id.indexOf(prefix) === 0) {
                    delete this.openingConfigurations[component.id.substring(prefix.length)];
                }
            }, this);
        }

        return this.editPanel;
    },


    onTreeNodeClick: function (tree, record, item, index, e, eOpts) {
        if (!record.isLeaf()) {
            return;
        }

        this.openConfigurationOnce(record);
    },

    /**
     * Адаптер ищет уже открытую панель конфига, но создаёт её только после ответа сервера:
     * второй клик до ответа (двойной клик по узлу или бару) дал бы
     * «Duplicate component id plugin_opendxp_datahub_configpanel_panel_<name>».
     */
    openConfigurationOnce: function (record) {
        const name = record.id;
        const existingPanel = Ext.getCmp("plugin_opendxp_datahub_configpanel_panel_" + name);
        if (existingPanel) {
            this.getEditPanel().setActiveTab(existingPanel);
            return;
        }

        this.openingConfigurations = this.openingConfigurations || {};
        if (this.openingConfigurations[name]) {
            return;
        }
        this.openingConfigurations[name] = true;

        // Снимается, когда панель добавлена (getEditPanel), или по таймеру, если запрос упал.
        setTimeout(function () {
            delete this.openingConfigurations[name];
        }.bind(this), 15000);

        const adapterImpl = new opendxp.plugin.datahub.adapter[record.data.adapter](this);
        adapterImpl.openConfiguration(name);
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
