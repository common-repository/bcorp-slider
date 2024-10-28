jQuery(function($){
	"use strict";
	$("body").append('<div id="bcsc-dialog-overlay"></div><div id="bcsc-dialog" class="bcsc-dialog"></div>');
  $('#bcorp-slider-button').on('click',function(){
		chooseElement($(this));
		return false;
	});
	function chooseElement($this) {
		var elements_dialog_html = '<div class="bcsc-dialog-header"><h2>BCorp Slider</h2><span class="bcsc-dialog-cancel dashicons dashicons-no"></span></div><div class="bcsc-element-list">';
		elements_dialog_html += '<p>The BCorp Slider Plugin is an extension for the BCorp Shortcodes Plugin which is required to be installed and activated for the BCorp Slider to function.</p>';
		elements_dialog_html += '<p>Save and reload this page after you have installed and activated the plugin.</p></div>';
		elements_dialog_html +='<div class="bcsc-dialog-buttons"><a class="bcorp-installer-button button button-primary button-large" href="'+bcorp_installer.url+'" target="_blank">';
		if (bcorp_installer.installed) elements_dialog_html += 'Activate'; else elements_dialog_html += 'Install';
		elements_dialog_html +='</a>';

		var elements_dialog_id = '#bcsc-dialog';
		var $element_selection_page = $('#bcsc-dialog').html(elements_dialog_html);
		$("#bcsc-dialog-overlay").click(function(){closeElementsDialog(elements_dialog_id)});
		$("#bcsc-dialog-overlay").css({"display":"block",opacity:0});
		$("#bcsc-dialog-overlay").fadeTo(200,0.3);
		$(elements_dialog_id).css({"display":"block"});
		$(elements_dialog_id).removeClass('zoomOut').addClass('zoomIn');
		$(elements_dialog_id+" .bcsc-dialog-cancel").click(function(){ closeElementsDialog(elements_dialog_id); });
		$('.bcorp-installer-button').click(function(){ $('#bcorp-slider-button').hide(); closeElementsDialog(elements_dialog_id);  });
	}
	function closeElementsDialog(elements_dialog_id){
		$("#bcsc-dialog-overlay").fadeOut(200);
		$(elements_dialog_id).addClass('zoomOut');
		setTimeout(function(){ $(elements_dialog_id).css({"display":"none"}); }, 200);
	}

});
