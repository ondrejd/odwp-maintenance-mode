
( function( $ ) {
    "use strict";

    wp.customize( "odwp-maintenance_mode[enabled]", function( setting ) {
        setting.bind( function( pageId ) {
            pageId = parseInt( pageId, 10 );
            if ( pageId > 0 ) {
                api.previewer.previewUrl.set( api.settings.url.home );
            }
        });
    });
} )();