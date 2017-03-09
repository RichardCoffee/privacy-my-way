// js/admin-form.js

jQuery( document ).ready( function() {
	showhideAdminElements( '.privacy-blog-active',   '.privacy-blog-option',   'yes' );
	showhideAdminElements( '.privacy-multi-active',  '.privacy-multi-option',  'filter' );
	showhideAdminElements( '.privacy-plugin-active', '.privacy-plugin-filter', 'filter' );
	showhideAdminElements( '.privacy-theme-active',  '.privacy-theme-filter',  'filter' );
});

function showhidePosi( el, target, show ) {
  if ( el ) {
    var eldiv = el.parentNode.parentNode.parentNode;
    if ( eldiv ) {
      showhideAdminElements( eldiv, target, show );
    }
  }
}

function showhideAdminElements( el, target, show ) {
	if ( el ) {
		var radio = jQuery( el ).find( 'input:radio:checked' );
		if ( radio ) {
			var state = jQuery( radio ).val();
			if ( state && show ) {
				if ( state === show ) {
					jQuery( target ).parent().parent().show();
				} else {
					jQuery( target ).parent().parent().hide();
				}
			}
		}
	}
}
