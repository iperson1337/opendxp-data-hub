
opendxp.registerNS("opendxp.plugin.datahub");


opendxp.plugin.datahub = Class.create({
    getClassName: function () {
        return "opendxp.plugin.datahub";
    },

    initialize: function () {
        // if the new event exists, we use this
        if (opendxp.events.preMenuBuild) {
            document.addEventListener(opendxp.events.preMenuBuild, this.preMenuBuild.bind(this));
        } else {
            document.addEventListener(opendxp.events.opendxpReady, this.opendxpReady.bind(this));
        }


        document.addEventListener("opendxp.perspectiveEditor.permissions.structure.load", (e) => {
            if (e.detail.context === 'toolbar') {
                e.detail.structure['datahub'] = {};
            }
        });

        document.addEventListener("opendxp.perspectiveEditor.permissions.load", (e) => {
            const context = e.detail.context;
            const menu = e.detail.menu;
            const permissions = e.detail.permissions;

            if (context === 'toolbar' && menu === 'datahub') {
                if (permissions[context][menu] === undefined) {
                    permissions[context][menu] = [];
                }
                if (permissions[context][menu].indexOf('hidden') === -1) {
                    permissions[context][menu].push('hidden');
                }
            }
        });
    },

    preMenuBuild: function (e) {
        const perspectiveCfg = opendxp.globalmanager.get("perspective");

        if (perspectiveCfg.inToolbar("datahub") === false) {
            return
        }

        const user = opendxp.globalmanager.get("user");
        if (user.admin || user.isAllowed("plugin_datahub_config")) {
            let menu = e.detail.menu;

            menu.datahub = {
                label: t('plugin_opendxp_datahub_toolbar'),
                iconCls: 'opendxp_main_nav_icon_mind_map',
                priority: 55,
                shadow: false,
                handler: this.openDataHub,
                cls: "opendxp_navigation_flyout",
                noSubmenus: true
            };
        }
    },

    openDataHub: function(e) {
        try {
            opendxp.globalmanager.get("plugin_opendxp_datahub_config").activate();
        } catch (e) {
            opendxp.globalmanager.add("plugin_opendxp_datahub_config", new opendxp.plugin.datahub.config());
        }
    },

    opendxpReady: function(e) {
        const perspectiveCfg = opendxp.globalmanager.get("perspective");

        if (perspectiveCfg.inToolbar("datahub") === false) {
            return
        }

        const user = opendxp.globalmanager.get("user");
        if (user.admin || user.isAllowed("plugin_datahub_config")) {

            let navEl = Ext.get('opendxp_menu_search').insertSibling('<li id="opendxp_menu_datahub" data-menu-tooltip="'
                + t('plugin_opendxp_datahub_toolbar') +
                '" class="opendxp_menu_item opendxp_menu_needs_children"><img alt="datahub" src="/bundles/opendxpadmin/img/flat-white-icons/mind_map.svg"></li>', 'before');

            navEl.on('mousedown', function () {
                try {
                    opendxp.globalmanager.get("plugin_opendxp_datahub_config").activate();
                } catch (e) {
                    opendxp.globalmanager.add("plugin_opendxp_datahub_config", new opendxp.plugin.datahub.config());
                }
            });

            opendxp.helpers.initMenuTooltips();
        }
    }
});

var datahubPlugin = new opendxp.plugin.datahub();
