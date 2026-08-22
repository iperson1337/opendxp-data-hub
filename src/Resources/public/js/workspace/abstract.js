

opendxp.registerNS("opendxp.plugin.datahub.workspace.abstract");
opendxp.plugin.datahub.workspace.abstract = Class.create({

    availableRights : ["create", "read","update", "delete"],

    initialize: function (parent) {
        this.parent = parent;
        this.workspaces = this.parent.data.workspaces;
    },

    getPanel: function () {

        var gridPlugins = [];
        var storeFields = ["cpath"];

        var typesColumns = [
            {text: t("path"), flex: 1, sortable: false, dataIndex: 'cpath',
                editor: new Ext.form.TextField({}),
                tdCls: "opendxp_property_droptarget",
                renderer: Ext.util.Format.htmlEncode
            }
        ];

        var check;
        for (var i=0; i<this.availableRights.length; i++) {

            var checkConfig = {
                text: t("plugin_opendxp_datahub_workspace_permission_" + this.availableRights[i]),
                dataIndex: this.availableRights[i],
                width: 70,
                hidden: this.rightCheckboxHidden || false,
                disabled: this. rightCheckboxDisabled || false
            };

            check = new Ext.grid.column.Check(checkConfig);

            typesColumns.push(check);
            gridPlugins.push(check);

            // store fields
            storeFields.push({name:this.availableRights[i], type: 'bool'});
        }

        typesColumns.push({
            xtype: 'actioncolumn',
            menuText: t('delete'),
            width: 40,
            items: [{
                tooltip: t('delete'),
                icon: "/bundles/opendxpadmin/img/flat-color-icons/delete.svg",
                handler: function (grid, rowIndex) {
                    grid.getStore().removeAt(rowIndex);
                    this.updateRows();
                }.bind(this)
            }]
        });

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            proxy: {
                type: 'memory',
                reader: {
                    rootProperty: this.type
                }
            },
            fields: storeFields,
            data: this.workspaces
        });

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            store: this.store,
            columns : typesColumns,
            trackMouseOver: true,
            columnLines: true,
            stripeRows: true,
            autoExpandColumn: "cpath",
            autoHeight: true,
            style: "margin-bottom:20px;",
            plugins: [
                this.cellEditing
            ],
            tbar: [
                {
                    xtype: "tbtext",
                    text: "<b>" + t(this.type + "s") + "</b>"
                },
                "-","-",
                {
                    iconCls: "opendxp_icon_add",
                    text: t("add"),
                    handler: this.onAdd.bind(this)
                }
            ],
            viewConfig: {
                forceFit: true,
                listeners: {
                    rowupdated: this.updateRows.bind(this),
                    refresh: this.updateRows.bind(this)
                }
            }
        });

        this.store.on("update", this.updateRows.bind(this));
        this.grid.on("viewready", this.updateRows.bind(this));


        return this.grid;
    },

    updateRows: function () {

        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid-row");

        for (var i = 0; i < rows.length; i++) {

            var dd = new Ext.dd.DropZone(rows[i], {
                ddGroup: "element",

                getTargetFromEvent: function(e) {
                    return this.getEl();
                },

                onNodeOver : function(target, dd, e, data) {
                    if (data.records.length == 1 && data.records[0].data.elementType == this.type) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                }.bind(this),

                onNodeDrop : function(myRowIndex, target, dd, e, data) {
                    if (opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                        try {
                            var record = data.records[0];
                            var data = record.data;

                            // check for duplicate records
                            var index = this.grid.getStore().findExact("cpath", data.path);
                            if (index >= 0) {
                                return false;
                            }

                            if (data.elementType != this.type) {
                                return false;
                            }

                            var rec = this.grid.getStore().getAt(myRowIndex);
                            rec.set("cpath", data.path);

                            this.updateRows();

                            return true;
                        } catch (e) {
                            console.log(e);
                        }
                    }
                }.bind(this, i)
            });
        }

    },

    onAdd: function (btn, ev) {
        this.grid.store.add({
            read: true,
            cpath: ""
        });

        this.updateRows();
    },

    getValues: function () {

        var values = [];
        this.store.commitChanges();

        var records = this.store.getRange();
        for (var i = 0; i < records.length; i++) {
            var currentData = records[i];
            if (currentData) {
                values.push(currentData.data);
            }
        }

        return values;
    }
});
