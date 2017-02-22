// js/admin-form.js

jQuery(document).ready(function() {
	showhideAdminElements( document.querySelector( '.privacy-blog-active' ),   '.privacy-blog-option',   'yes');
	showhideAdminElements( document.querySelector( '.privacy-multi-active' ),  '.privacy-multi-option',  'filter');
	showhideAdminElements( document.querySelector( '.privacy-plugin-active' ), '.privacy-plugin-filter', 'filter');
	showhideAdminElements( document.querySelector( '.privacy-theme-active' ),  '.privacy-theme-filter',  'filter');
	showhideElements( jQuery( '.showhide' ) );
//	jQuery('.form-colorpicker' ).wpColorPicker();
//	jQuery('.form-image'       ).click(function(e) { imageUploader(this,e); });
//	jQuery('.form-image-delete').click(function(e) { imageDelete(this); });
});

function showhideElements(els) {
  jQuery(els).each(function(el) {
    var target = jQuery(el).attr('data-item');
    var show   = jQuery(el).attr('data-show');
    if (target && show) {
      if (jQuery(el).find('input:radio:checked').val()==show) {
        jQuery(target).parent().parent().show(); //removeClass('hidden');
      } else {
        jQuery(target).parent().parent().hide(); //addClass('hidden');
      }
    }
  });
}

function imageDelete(el) {
  var ans = confirm('Remove this image?');
  if (ans) {
    var iuField = jQuery(el.parentNode).data('field');
    var iuInput = document.getElementById(iuField+'_input');
    var iuImage = document.getElementById(iuField+'_img');
    iuInput.value = '';
    iuImage.src   = '';
    jQuery(iuImage).addClass('hidden');
  }
}

function imageUploader(el,e) {
  e.preventDefault();
  var iuTitle  = jQuery(el.parentNode).data('title');
  var iuButton = jQuery(el.parentNode).data('button');
  var iuField  = jQuery(el.parentNode).data('field');
  var custom_uploader = wp.media({
    title: iuTitle,
    button: { text: iuButton, },
    multiple: false
  });
  custom_uploader.on('select', function() {
    var attachment = custom_uploader.state().get('selection').first().toJSON();
console.log(attachment);
    if (iuField) {
      var iuInput = document.getElementById(iuField+'_input');
      var iuImage = document.getElementById(iuField+'_img');
      iuInput.value = attachment.url;
      iuImage.src   = attachment.url;
      jQuery(el.parentNode).children('.form-image-container').removeClass('hidden');
      jQuery(el.parentNode).children('.form-image-delete').removeClass('hidden');
    }
  });
  custom_uploader.open();
}

function showhidePosi(el,target,show) {
  if (el) {
    var eldiv = el.parentNode.parentNode.parentNode;
    //var eldiv = document.querySelector(selector);
    if (eldiv) {
      showhideAdminElements(eldiv,target,show);
    }
  }
}

function showhideAdminElements(el,target,show) {
	if (el) {
console.log(el);
		var state = jQuery(el).find('input:radio:checked').val();
console.log( state+' - '+show );
		if ( state === show) {
			jQuery(target).parent().parent().show(2000); //removeClass('hidden');
		} else {
		jQuery(target).parent().parent().hide(2000); //addClass('hidden');
		}
	}
}

// Browser compatibility function taken from http://stackoverflow.com/questions/6548748/portability-of-nextelementsibling-nextsibling
// the jquery .next() function is not reliable under certain circumstances - ie: when the DOM element has been dynamically added
function nextElementSibling(el) {
  if (el.nextElementSibling) return el.nextElementSibling;
  do { el = el.nextSibling } while (el && el.nodeType !== 1);
  return el;
}
