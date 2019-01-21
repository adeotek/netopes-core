/**
 * NETopes controls extra javascript file
 * Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * License    LICENSE.md
 * @author     George Benjamin-Schonberger
 * @version    3.0.0.0
 */

$(function() {
	SmartCBOInitialize();
	ShowToolTip('.clsTitleToolTip[title]');
	ShowToolTip('.clsTitleSToolTip[title]');
	ShowToolTip('.clsDataToolTip[data]');
	ShowToolTip('.clsGCBToolTip');
	ShowPopover('.clsWebuiPopover');
	ShowPopover('.clsDarkWebuiPopover');
});//$(function()

$(document).on('onARequestInit',function(e) {
	DestroyCkEditors(window.name,e.target);
	ToolTipCleanup();
});

$(document).on('onARequestComplete',function(e) {
	// if(e.source=='runRepeated') { return; }
	ShowErrorDialog(false);
	SmartCBOInitialize();
	ShowToolTip('.clsTitleToolTip[title]');
	ShowToolTip('.clsTitleSToolTip[title]');
	ShowToolTip('.clsDataToolTip[data]');
	ShowToolTip('.clsGCBToolTip');
	ShowPopover('.clsWebuiPopover');
	ShowPopover('.clsDarkWebuiPopover');
});

/*** For Errors Popup ***/
function ShowErrorDialog(errstr,encrypted,targetid,title) {
	if(!targetid || typeof(targetid)!='string' || targetid.length<=0) { targetid = 'errors-dlg'; }
	if(!$('#'+targetid).length) { $('body').append('<div id="'+targetid+'" data-title="Error" style="display: none;"></div>'); }
	ltitle = title ? title : $('#'+targetid).attr('data-title');
	if(!errstr) {
		encrypted = false;
		errstr = '';
		$('.IPEPText').each(function() {
			errstr = errstr + '<br>' + GibberishAES.dec($(this).val(),'HTML');
			$(this).remove();
		});//$('.IPEPText').each(function()
	}//if(!errstr)
	if(errstr) {
		if(encrypted) { errstr = GibberishAES.dec(errstr,'HTML'); }
		$('#'+targetid).html(errstr);
		var minWidth = $(window).width()>500 ? 500 : ($(window).width() - 20);
		var maxWidth = $(window).width()>600 ? ($(window).width() - 80) : ($(window).width() - 20);
		$('#'+targetid).dialog({
			title: ltitle,
			dialogClass: 'ui-error-dlg',
			minWidth: minWidth,
			maxWidth: maxWidth,
			minHeight: 'auto',
			resizable: false,
			modal: true,
			autoOpen: true,
			show: {effect: 'slide', duration: 300, direction: 'up'},
			hide: {effect: 'slide', duration: 300, direction: 'down'},
			closeOnEscape: true
	    });
	}//if(errstr)
}//END function ShowErrorDialog
/* END For Errors Popup */
/*** For modal dialogs ***/
function ShowConfirmDialog(message,callback,encrypted,options) {
	if(!callback || (typeof(callback)!='string' && typeof(callback)!='function')) { return false; }
	if(!message || typeof(message)!='string' || message.length<=0) { return false; }
	if(encrypted) { message = GibberishAES.dec(message,'HTML'); }
	var cfg = {
		targetid: '',
		title: '',
		ok: '',
		cancel: ''
	};
	if(options && typeof(options)=='object') { $.extend(cfg,options); }
	if(typeof(cfg.targetid)!='string' || cfg.targetid.length<=0) { cfg.targetid = getUid(); }
	if(typeof(cfg.title)!='string') { cfg.title = ''; }
	if(typeof(cfg.ok)!='string' || cfg.ok.length<=0) { cfg.ok = 'OK'; }
	if(typeof(cfg.cancel)!='string' || cfg.cancel.length<=0) { cfg.cancel = 'Cancel'; }
	var lbuttons = {};
	lbuttons[cfg.ok] = function() {
		$(this).dialog('close');
		if(typeof(callback)=='function') {
			callback();
		} else {
			if(encrypted) { callback = GibberishAES.dec(callback,'HTML'); }
			eval(callback);
		}//if(typeof(callback)=='function')
	};
	lbuttons[cfg.cancel] = function() { $(this).dialog('close'); };
	if(!$('#'+cfg.targetid).length) { $('body').append('<div id="'+cfg.targetid+'" style="display: none;"></div>'); }
	$('#'+cfg.targetid).html(message);
	var minWidth = $(window).width()>500 ? 500 : ($(window).width() - 20);
	var maxWidth = $(window).width()>600 ? ($(window).width() - 80) : ($(window).width() - 20);
	$('#'+cfg.targetid).dialog({
		title: cfg.title,
		dialogClass: 'ui-alert-dlg',
		minWidth: minWidth,
		maxWidth: maxWidth,
		minHeight: 'auto',
		resizable: false,
		modal: true,
		autoOpen: true,
		show: {effect: 'slide', duration: 300, direction: 'up'},
		hide: {effect: 'slide', duration: 300, direction: 'down'},
		closeOnEscape: true,
		buttons: lbuttons
    });
}//END function ShowConfirmDialog

function ShowMessageDialog(message,title,encrypted,targetid) {
	if(!message || typeof(message)!='string' || message.length<=0) { return false; }
	if(encrypted) { message = GibberishAES.dec(message,'HTML'); }
	var ltitle = title ? title : '';
	if(!targetid || typeof(targetid)!='string' && targetid.length<=0) { targetid = getUid(); }
	if(!$('#'+targetid).length) { $('body').append('<div id="'+targetid+'" style="display: none;"></div>'); }
	$('#'+targetid).html(message);
	var minWidth = $(window).width()>500 ? 500 : ($(window).width() - 20);
	var maxWidth = $(window).width()>600 ? ($(window).width() - 80) : ($(window).width() - 20);
	$('#'+targetid).dialog({
		title: ltitle,
		dialogClass: 'ui-dlg',
		minWidth: minWidth,
		maxWidth: maxWidth,
		minHeight: 'auto',
		resizable: false,
		modal: true,
		autoOpen: true,
		show: {effect: 'slide', duration: 300, direction: 'up'},
		hide: {effect: 'slide', duration: 300, direction: 'down'},
		closeOnEscape: true
    });
}//END function ShowMessageDialog
/* END For modal dialogs */
/*** For showing and closing the modal form ***/
function ShowModalForm(width,title,close_callback,targetid) {
	if(!targetid || typeof(targetid)!='string' || targetid.length<=0) { targetid = 'modal'; }
	// console.log('ShowModalForm>>');
	// console.log('targetid: '+targetid);
	// console.log($('#'+targetid));
	if(!$('#'+targetid).length) { $('body').append('<div id="'+targetid+'" class="ui-modal" style="display: none;"></div>'); }
	var lwidth = 0;
	if(is_numeric(width)) {
		lwidth = Number(width)+30;
		if(lwidth>$(window).width()) { lwidth = ($(window).width() - 30); }
	} else {
		lwidth = $(window).width() * Number(width.replace('%','')) / 100;
	}//if(is_numeric(width))
	$('#'+targetid).dialog({
		title: title,
		dialogClass: 'ui-modal-dlg',
		width: lwidth,
		minHeight: 'auto',
		resizable: false,
		modal: true,
		autoOpen: true,
		show: {effect: 'fade', duration: 300},
		hide: {effect: 'fade', duration: 300},
		closeOnEscape: false,
		dragStop: function(event,ui) { $(this).dialog({height:'auto'}); },
		close: function() {
			if(close_callback) { eval(close_callback); }
			else {
				var d_close_callback = $(this).attr('data-close-callback');
				if(d_close_callback) { eval (d_close_callback); }
            }
		},
        open: function() {
        	if($.ui && $.ui.dialog && $.ui.dialog.prototype._allowInteraction) {
    			var ui_dialog_interaction = $.ui.dialog.prototype._allowInteraction;
    			$.ui.dialog.prototype._allowInteraction = function(e) {
        			if($(e.target).closest('.select2-dropdown').length) { return true; }
        			return ui_dialog_interaction.apply(this, arguments);
    			};
			}//if($.ui && $.ui.dialog && $.ui.dialog.prototype._allowInteraction)
    	},
		_allowInteraction: function(event) {
			return !!$(event.target).is(".select2-input") || this._super(event);
		}
    });
}//END function ShowModalForm

function CloseModalForm(callback,targetid,dynamic,skip_default_ccb) {
	if(!targetid || typeof(targetid)!='string' || targetid.length<=0) { targetid = 'modal'; }
	// console.log('CloseModalForm>>');
	// console.log('targetid: '+targetid);
	// console.log($('#'+targetid));
	// console.log('dynamic: '+dynamic);
	if(skip_default_ccb==1 || skip_default_ccb==true) { $('#'+targetid).attr('data-close-callback',''); }
	if($('#'+targetid).dialog('instance')) {
		$('#'+targetid).dialog('close');
	} else {
		$('#'+targetid).hide();
	}//if($('#'+targetid).dialog('instance'))
	$('#'+targetid).html('');
	if(targetid!='modal' && (dynamic==1 || dynamic==true)) { $('#'+targetid).remove(); }
	if(callback) { eval(GibberishAES.dec(callback,'cmf')); }
}//END function CloseModalForm

function AppendDynamicModal(targetid) {
	if(!targetid || typeof(targetid)!='string' || targetid.length<=0 || $('#'+targetid).length) { return false; }
	$('body').append('<div id="'+targetid+'" class="ui-modal" style="display: none;"></div>');
}//END function AppendDynamicModal

function ShowDynamicModalForm(targetid,width,title,close_callback) {
	if(!targetid || typeof(targetid)!='string' || targetid.length<=0) { return; }
	return ShowModalForm(width,title,close_callback,targetid);
}//END function ShowDynamicModalForm
/* END For showing and closing the modal form */
/*** For ToolTip ***/
function ShowToolTip(etype) {
	var ttclass = '';
	switch(etype) {
		case '.clsDataToolTip[data]':
			ttclass = 'white-bg';
			break;
		case '.clsTitleToolTip[title]':
		case '.clsTitleSToolTip[title]':
		case '.clsGCBToolTip':
		default:
			ttclass = 'dgrey-bg';
			break;
	}//END swich
	$(etype).each(function() {
		if($(this).data('ui-tooltip')) { return; }
		var dtclass = $(this).attr('data-ttcls');
		if(dtclass && dtclass.length) { ttclass = dtclass; }
		$(this).tooltip({
			items: etype,
			tooltipClass: ttclass,
			content: function() {
				switch(etype) {
					case '.clsDataToolTip[data]':
						var ldata = $(this).attr('data');
						if(ldata) { return GibberishAES.dec(ldata,'HTML'); }
						return '';
					case '.clsGCBToolTip':
						if($('#'+$(this).attr('id')+'_selectedtext').length) { return $('#'+$(this).attr('id')+'_selectedtext').val(); }
						return '';
					default:
						return $(this).attr('title');
				}//END swich
			},//content: function()
			position: {
	        	my: 'center bottom-20',
	        	at: 'center top',
	        	using: function(position,feedback) {
	          		$(this).css(position);
	          		if(etype!='.clsTitleSToolTip[title]') {
		          		$('<div>')
				            .addClass('arrow-'+ttclass)
				            .addClass(feedback.vertical)
				            .addClass(feedback.horizontal)
				            .appendTo(this);
					}//if(etype!='.clsTitleSToolTip[title]')
	        	}//using: function(position,feedback)
	      	}//position:
	    });//$(this).tooltip
	});//$(etype).each(function()
}//function ShowToolTip

function ToolTipCleanup() {
	 $('.ui-tooltip-content').parent('div.ui-tooltip').remove();
}//END function ToolTipCleanup

function ShowPopover(etype) {
	var styleClass = '';
	if(etype=='.clsDarkWebuiPopover') { styleClass = 'inverse'; }
	$(etype).webuiPopover({
		// title: function() {
		// 	var ldata = $(this).attr('data-title');
		// 	if(!ldata || ldata.length<=0) { ldata = $(this).attr('title'); }
		// 	return ldata;
		// },
		content: function() {
			var ldata = $(this).attr('data');
			if(!ldata || ldata.length<=0) { return false; }
			ldata = GibberishAES.dec(ldata,'HTML');
			return ldata;
		},
		trigger: 'hover',
		style: styleClass
	});
}//function ShowPopover
/* END For ToolTip */
/*** For Language selector ***/
function ShowLanguagesList(cobject,elementid,action,lheight) {
	switch (action) {
		case 1:
			$('#'+elementid).css('position','absolute');
			var loffset = $(cobject).offset();
			var ltop = loffset.top + 19;
			var lleft = loffset.left;
			$('#'+elementid).css('top',ltop+'px');
			$('#'+elementid).css('left',lleft+'px');
			$('#'+elementid).css('height',lheight+'px');
			$('#'+elementid).css('display','');
			break;
		case 2:
			$('#'+elementid).css('height',lheight+'px');
			$('#'+elementid).css('display','');
			break;
		default:
			$('#'+elementid).css('display','none');
			break;
	}//switch (action)
}//function ShowLanguagesList
/* END For Language selector */