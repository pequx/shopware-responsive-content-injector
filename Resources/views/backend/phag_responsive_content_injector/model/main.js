
Ext.define('Shopware.apps.PhagResponsiveContentInjector.model.Main', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'PhagResponsiveContentInjector',
            detail: 'Shopware.apps.PhagResponsiveContentInjector.view.detail.Container'
        };
    },


    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'name', type: 'string', useNull: false }
    ]
});

