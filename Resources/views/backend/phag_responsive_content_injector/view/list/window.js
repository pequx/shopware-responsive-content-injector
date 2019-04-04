
Ext.define('Shopware.apps.PhagResponsiveContentInjector.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.phag-responsive-content-injector-list-window',
    height: 450,
    title : '{s name=window_title}PhagResponsiveContentInjector listing{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.PhagResponsiveContentInjector.view.list.List',
            listingStore: 'Shopware.apps.PhagResponsiveContentInjector.store.Main'
        };
    }
});