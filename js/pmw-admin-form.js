// js/pmw-admin-form.js

jQuery( document ).ready( function() {
	if ( tcc_admin_options.showhide ) {
		jQuery.each( tcc_admin_options.showhide, function( counter, item ) {
		if ( targetableElement( item ) ) {
			var origin = '.' + item.origin + ' input:radio';
			jQuery( origin ).change( item, function( e ) {
				targetableElement( e.data );
			});
			}
		});
	}
});

function targetableElement( item ) {
	return showhideAdminElement( '.'+item.origin, '.'+item.target, item.show, item.hide );
}

function showhideAdminElement( origin, target, show, hide ) {
	if ( origin && target ) {
		var radio = jQuery( origin + ' input:radio:checked' );
		if ( radio.length ) {
			var state = jQuery( radio ).val();
			if ( state ) {
				if ( show ) {
					if ( state === show ) {
						jQuery( target ).parent().parent().show( 2000 ); //removeClass('hidden');
					} else {
						jQuery( target ).parent().parent().hide( 2000 ); //addClass('hidden');
					}
				} else if ( hide ) {
					if ( state === hide ) {
						jQuery( target ).parent().parent().hide( 2000 ); //addClass('hidden');
					} else {
						jQuery( target ).parent().parent().show( 2000 ); //removeClass('hidden');
					}
				}
			}
			return true;
		}
	}
	return false;
}
