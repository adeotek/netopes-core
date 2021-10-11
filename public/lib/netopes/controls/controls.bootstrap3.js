/**
 * NETopes controls Bootstrap 3 javascript file
 * Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * License    LICENSE.md
 * @author     George Benjamin-Schonberger
 * @version    3.1.0.0
 */

$(function() {
    SmartCBOInitialize();
    ShowToolTip('.clsTitleToolTip');
    ShowToolTip('.clsTitleSToolTip');
    ShowToolTip('.clsDataToolTip','data');
    ShowToolTip('.clsGCBToolTip','selectedtext');
    ShowPopover('.clsWebuiPopover',true);
    ShowPopover('.clsDarkWebuiPopover',true);
    ShowPopover('.clsWebuiSPopover',false);
    ShowPopover('.clsDarkWebuiSPopover',false);
});//$(function()

window.addEventListener('onNAppRequestInit',function(e) {
    if(typeof (e.target)=='string') { DestroyCkEditors(window.name,e.target); }
},false);

window.addEventListener('onNAppRequestComplete',function(e) {
    ShowErrorDialog(false);
    SmartCBOInitialize();
    ShowToolTip('.clsTitleToolTip');
    ShowToolTip('.clsTitleSToolTip');
    ShowToolTip('.clsDataToolTip','data');
    ShowToolTip('.clsGCBToolTip','selectedtext');
    ShowPopover('.clsWebuiPopover',true);
    ShowPopover('.clsDarkWebuiPopover',true);
    ShowPopover('.clsWebuiSPopover',false);
    ShowPopover('.clsDarkWebuiSPopover',false);
},false);

/*** For Errors Popup ***/
function ShowErrorDialog(errstr,encrypted,targetid,title) {
    if(!targetid || typeof (targetid)!='string' || targetid.length<=0) { targetid='errors-dlg'; }
    if(!$('#' + targetid).length) { $('body').append('<div id="' + targetid + '" data-title="Error" style="display: none;"></div>'); }
    ltitle=title ? title : $('#' + targetid).attr('data-title');
    if(!errstr) {
        encrypted=false;
        errstr='';
        $('.IPEPText').each(function() {
            errstr=errstr + '<br>' + GibberishAES.dec($(this).val(),'HTML');
            $(this).remove();
        });//$('.IPEPText').each(function()
    }//if(!errstr)
    if(errstr) {
        if(encrypted) { errstr=GibberishAES.dec(errstr,'HTML'); }
        $('#' + targetid).html(errstr);
        var minWidth=$(window).width()>500 ? 500 : ($(window).width() - 20);
        var maxWidth=$(window).width()>600 ? ($(window).width() - 80) : ($(window).width() - 20);
        $('#' + targetid).dialog({
            title: ltitle,
            dialogClass: 'ui-error-dlg',
            minWidth: minWidth,
            maxWidth: maxWidth,
            minHeight: 'auto',
            resizable: false,
            modal: true,
            autoOpen: true,
            show: {effect: 'slide',duration: 300,direction: 'up'},
            hide: {effect: 'slide',duration: 300,direction: 'down'},
            closeOnEscape: true
        });
    }//if(errstr)
}//END function ShowErrorDialog
/* END For Errors Popup */
/*** For modal dialogs ***/
function ShowConfirmDialog(message,callback,encrypted,options,cancelCallback) {
    if(!callback || (typeof (callback)!='string' && typeof (callback)!='function')) { return false; }
    if(!message || typeof (message)!='string' || message.length<=0) { return false; }
    if(encrypted) { message=GibberishAES.dec(message,'HTML'); }
    let cfg={
        targetid: '',
        title: '',
        ok: '',
        cancel: ''
    };
    if(options && typeof (options)=='object') { $.extend(cfg,options); }
    if(typeof (cfg.targetid)!='string' || cfg.targetid.length<=0) { cfg.targetid=getUid(); }
    if(typeof (cfg.title)!='string') { cfg.title=''; }
    if(typeof (cfg.ok)!='string' || cfg.ok.length<=0) { cfg.ok='OK'; }
    if(typeof (cfg.cancel)!='string' || cfg.cancel.length<=0) { cfg.cancel='Cancel'; }
    let lbuttons={};
    lbuttons[cfg.ok]=function() {
        $(this).dialog('close');
        if(typeof (callback)=='function') {
            callback();
        } else {
            if(encrypted) { callback=GibberishAES.dec(callback,'HTML'); }
            eval(callback);
        }//if(typeof(callback)=='function')
    };
    if(cancelCallback) {
        lbuttons[cfg.cancel]=function() {
            $(this).dialog('close');
            if(typeof (cancelCallback)=='function') {
                cancelCallback();
            } else {
                if(encrypted) { callback=GibberishAES.dec(cancelCallback,'HTML'); }
                eval(cancelCallback);
            }//if(typeof(cancelCallback)=='function')
        };
    } else {
        lbuttons[cfg.cancel]=function() { $(this).dialog('close'); };
    }//if(cancelCallback)
    if(!$('#' + cfg.targetid).length) { $('body').append('<div id="' + cfg.targetid + '" style="display: none;"></div>'); }
    $('#' + cfg.targetid).html(message);
    var minWidth=$(window).width()>500 ? 500 : ($(window).width() - 20);
    var maxWidth=$(window).width()>600 ? ($(window).width() - 80) : ($(window).width() - 20);
    $('#' + cfg.targetid).dialog({
        title: cfg.title,
        dialogClass: 'ui-alert-dlg',
        minWidth: minWidth,
        maxWidth: maxWidth,
        minHeight: 'auto',
        resizable: false,
        modal: true,
        autoOpen: true,
        show: {effect: 'slide',duration: 300,direction: 'up'},
        hide: {effect: 'slide',duration: 300,direction: 'down'},
        closeOnEscape: true,
        buttons: lbuttons
    });
}//END function ShowConfirmDialog

/**
 * @return {boolean}
 */
function ShowMessageDialog(message,title,encrypted,targetId,dlgClass) {
    if(!message || typeof (message)!='string' || message.length<=0) { return false; }
    if(encrypted) { message=GibberishAES.dec(message,'HTML'); }
    let lTitle=title ? title : '';
    if(!targetId || typeof (targetId)!='string' && targetId.length<=0) { targetId=getUid(); }
    if(!$('#' + targetId).length) { $('body').append('<div id="' + targetId + '" style="display: none;"></div>'); }
    $('#' + targetId).html(message);
    let minWidth=$(window).width()>500 ? 500 : ($(window).width() - 20);
    let maxWidth=$(window).width()>600 ? ($(window).width() - 80) : ($(window).width() - 20);
    let cssClass=dlgClass || 'ui-dlg';
    $('#' + targetId).dialog({
        title: lTitle,
        dialogClass: cssClass,
        minWidth: minWidth,
        maxWidth: maxWidth,
        minHeight: 'auto',
        resizable: false,
        modal: true,
        autoOpen: true,
        show: {effect: 'slide',duration: 300,direction: 'up'},
        hide: {effect: 'slide',duration: 300,direction: 'down'},
        closeOnEscape: true
    });
}//END function ShowMessageDialog
/* END For modal dialogs */
/*** For showing and closing the modal form ***/
function ShowModalForm(width,title,close_callback,targetid) {
    if(!targetid || typeof (targetid)!=='string' || targetid.length<=0) { targetid='modal'; }
    // console.log('ShowModalForm>>');
    // console.log('targetid: '+targetid);
    // console.log($('#'+targetid));
    if(!$('#' + targetid).length) { $('body').append('<div id="' + targetid + '" class="ui-modal" style="display: none;"></div>'); }
    let lwidth;
    if(is_numeric(width)) {
        lwidth=Number(width) + 30;
        if(lwidth>$(window).width()) { lwidth=($(window).width() - 30); }
    } else if(width.includes('%')) {
        lwidth=$(window).width() * Number(width.replace('%','')) / 100;
    } else {
        lwidth='auto';
    }//if(is_numeric(width))
    $('#' + targetid).dialog({
        title: title,
        dialogClass: 'ui-modal-dlg',
        width: lwidth,
        minHeight: 'auto',
        resizable: false,
        modal: true,
        autoOpen: true,
        show: {effect: 'fade',duration: 300},
        hide: {effect: 'fade',duration: 300},
        closeOnEscape: false,
        dragStop: function(event,ui) { $(this).dialog({height: 'auto'}); },
        close: function() {
            if(typeof (close_callback)==='function') {
                close_callback();
            } else if(typeof (close_callback)==='string' && close_callback.length>0) {
                eval(close_callback);
            } else {
                let d_close_callback=$(this).attr('data-close-callback');
                if(d_close_callback) { eval(d_close_callback); }
            }
            if(targetid!=='modal') {
                $('#' + targetid).dialog('destroy');
                $('#' + targetid).html('');
            }
        },
        open: function() {
            if($.ui && $.ui.dialog && $.ui.dialog.prototype._allowInteraction) {
                var ui_dialog_interaction=$.ui.dialog.prototype._allowInteraction;
                $.ui.dialog.prototype._allowInteraction=function(e) {
                    if($(e.target).closest('.select2-dropdown').length) { return true; }
                    return ui_dialog_interaction.apply(this,arguments);
                };
            }//if($.ui && $.ui.dialog && $.ui.dialog.prototype._allowInteraction)
        },
        _allowInteraction: function(event) {
            return !!$(event.target).is('.select2-input') || this._super(event);
        }
    });
}//END function ShowModalForm

function CloseModalForm(callback,targetid,dynamic,skip_default_ccb) {
    if(!targetid || typeof (targetid)!='string' || targetid.length<=0) { targetid='modal'; }
    // console.log('CloseModalForm>>');
    // console.log('targetid: '+targetid);
    // console.log($('#'+targetid));
    // console.log('dynamic: '+dynamic);
    if(skip_default_ccb==1 || skip_default_ccb==true) { $('#' + targetid).attr('data-close-callback',''); }
    if($('#' + targetid).dialog('instance')) {
        // console.log('dialog: close');
        $('#' + targetid).dialog('close');
    } else {
        // console.log('element: hide');
        $('#' + targetid).hide();
    }//if($('#'+targetid).dialog('instance'))
    $('#' + targetid).html('');
    if(targetid!='modal' && (dynamic==1 || dynamic==true)) { $('#' + targetid).remove(); }
    if(callback) { eval(GibberishAES.dec(callback,'cmf')); }
}//END function CloseModalForm

function AppendDynamicModal(targetid) {
    if(!targetid || typeof (targetid)!='string' || targetid.length<=0 || $('#' + targetid).length) { return false; }
    $('body').append('<div id="' + targetid + '" class="ui-modal" style="display: none;"></div>');
}//END function AppendDynamicModal

function ShowDynamicModalForm(targetid,width,title,close_callback) {
    if(!targetid || typeof (targetid)!='string' || targetid.length<=0) { return; }
    return ShowModalForm(width,title,close_callback,targetid);
}//END function ShowDynamicModalForm
/* END For showing and closing the modal form */
function ShowToolTip(etype,source) {
    switch(source) {
        case 'data':
            $(etype).tooltip({
                html: true,
                title: function() {
                    var ldata=$(this).attr('data');
                    if(!ldata || ldata.length<=0) { return false; }
                    ldata=GibberishAES.dec(ldata,'HTML');
                    return ldata;
                }
            });
            break;
        case 'selectedtext':
            $(etype).tooltip({
                title: function() {
                    if($('#' + $(this).attr('id') + '_selectedtext').length) { return false; }
                    var ldata=$('#' + $(this).attr('id') + '_selectedtext').val();
                    return ldata;
                }
            });
            break;
        default:
            $(etype).tooltip();
            break;
    }//END switch
}//function ShowToolTip

function ShowPopover(etype,encrypted) {
    var styleClass='';
    if(etype==='.clsDarkWebuiPopover') {
        styleClass='inverse';
    }
    $(etype).webuiPopover({
        // title: function() {
        // 	var ldata = $(this).attr('data-title');
        // 	if(!ldata || ldata.length<=0) { ldata = $(this).attr('title'); }
        // 	return ldata;
        // },
        content: function() {
            if(encrypted==true || encrypted==1 || encrypted=='1' || encrypted=='true') {
                var ldata=$(this).attr('data');
                if(!ldata || ldata.length<=0) { return false; }
                ldata=GibberishAES.dec(ldata,'HTML');
                return ldata;
            } else {
                return $(this).attr('data');
            }//if(encrypted==true || encrypted==1 || encrypted=='1' || encrypted=='true')
        },
        trigger: 'hover',
        style: styleClass
    });
}//function ShowPopover