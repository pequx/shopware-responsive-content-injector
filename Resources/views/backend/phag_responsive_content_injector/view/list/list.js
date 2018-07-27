
Ext.define('Shopware.apps.PhagResponsiveContentInjector.view.list.List', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.phag-responsive-content-injector-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.PhagResponsiveContentInjector.view.detail.Window'
        };
    }
});
