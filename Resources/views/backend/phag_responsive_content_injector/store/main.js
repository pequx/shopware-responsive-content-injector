
Ext.define('Shopware.apps.PhagResponsiveContentInjector.store.Main', {
    extend:'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'PhagResponsiveContentInjector'
        };
    },
    model: 'Shopware.apps.PhagResponsiveContentInjector.model.Main'
});