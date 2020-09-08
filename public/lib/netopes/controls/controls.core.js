/**
 * NETopes controls core javascript file
 * Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * License    LICENSE.md
 * @author     George Benjamin-Schonberger
 * @version    2.5.0.2
 */

/*** For Loader and Screen blocking ***/
function ShowLoader(element,full) {
    let obj;
    if(typeof (element)=='object') {
        obj=element;
    } else {
        obj=$('#' + element);
    }//if(typeof(element)=='object')
    if($(obj).length>0) {
        if(full===1 || full===true || full==='1') { $(obj).css('height',Math.max($(document).outerHeight(),window.innerHeight) + 'px'); }
        $(obj).show();
    }//if($(obj).length>0)
}//function ShowLoader

function HideLoader(element) {
    let obj;
    if(typeof (element)=='object') {
        obj=element;
    } else {
        obj=$('#' + element);
    }//if(typeof(element)=='object')
    if($(obj).length>0) { $(obj).hide(); }
}//function HideLoader
/*** END For Loader and Screen blocking ***/
/*** Generic functions ***/
function OpenUrl(url,new_tab) {
    if(!url) { return false; }
    if(new_tab===true || new_tab===1 || new_tab==='1') {
        window.open(url,'_blank');
        window.focus();
    } else {
        window.open(url);
    }//if(new_tab===true || new_tab===1 || new_tab==='1')
}//END function OpenUrl

function randerReCaptcha(elementid,site_key) {
    if(grecaptcha && elementid && $('#' + elementid).length) {
        if(!site_key || site_key.length===0) {
            site_key=$('#' + elementid).data('site-key');
        }
        if(site_key) { grecaptcha.render(elementid,{'sitekey': site_key}); }
    }
}//function randerReCaptcha

function getReCaptcha(elementid) {
    if(grecaptcha) {
        let response=grecaptcha.getResponse();
        if(response.length) {
            if(elementid && elementid.length) {
                $('#' + elementid).val(response);
                return true;
            } else {
                return response;
            }
        }
    }
    return false;
}//function getReCaptcha

function resetReCaptcha() {
    if(grecaptcha) { grecaptcha.reset(); }
}//function resetReCaptcha
/*** END Generic functions ***/
/*** For Language selector ***/
function GetCurrentLanguageCode() {
    let langsel=$('#lang-selector').val();
    return langsel.substring(langsel.indexOf('^',0) + 1).toLowerCase();
}//END function GetCurrentLanguageCode

function GetNewLanguageLink(newlang,newdomain,olddomain) {
    let newvalue=newlang;
    if(strpos(newlang,'^')) { newlang=newlang.substring(newlang.indexOf('^',0) + 1); }
    let clink=window.location.href.split(/#/)[0];
    let clinkhash=window.location.hash.toString();
    let clang=GetCurrentLanguageCode();
    if(newdomain!=undefined && newdomain.length>0) {
        clink=clink.replace(olddomain,newdomain);
    }//if(newdomain!=undefined && newdomain.length>0)
    let newlink=clink;
    if(strpos(clink,'/' + clang + '/',0)) {
        newlink=clink.replace('/' + clang + '/','/' + newlang + '/');
    } else if(strpos(clink,'/' + clang,0)) {
        newlink=clink.replace('/' + clang,'/' + newlang + '/');
    } else if(strpos(clink,'language=' + clang,0)) {
        newlink=clink.replace('language=' + clang,'language=' + newlang);
    } else if(strpos(clink,'/index.php',0)) {
        newlink=clink.replace('/index.php','/' + newlang + '/index.php');
    } else {
        newlink=clink + (clink.charAt(clink.length - 1)=='/' ? '' : '/') + newlang + '/';
    }//if(strpos(clink,newlang,0))
    $('#lang-selector').val(newvalue);
    return newlink + clinkhash;
}//END function GetNewLanguageLink

function SetNewLanguageLink(newlang,newdomain,olddomain) {
    let lnlink=GetNewLanguageLink(newlang,newdomain,olddomain);
    if(lnlink) { window.location.href=lnlink; }
}//END function SetNewLanguageLink
/*** END For Language selector ***/

/*** For getting element value ***/
function GetElementValue(elementid,elementproperty) {
    let lproperty=elementproperty ? elementproperty : 'value';
    let result='';
    if($('#' + elementid).length>0) {
        switch(lproperty) {
            case 'innerHtml':
                result=$('#' + elementid).html();
                break;
            case 'option':
                result=$('#' + elementid + ' option:selected').text();
                break;
            case 'value':
            default:
                result=$('#' + elementid).val();
                break;
        }//END switch
    }//if($('#'+elementid).length>0)
    return result;
}//END function GetElementValue
/*** END For getting element value ***/
/*** For jQuery DateTimePicker ***/
$(document).on('focus','.clsJqDatePicker',function(e) {
    let langcode=GetCurrentLanguageCode();
    $.datepicker.setDefaults($.datepicker.regional[langcode]);
    if($(this).attr('data-jqdpparams').length) {
        eval('$(this).datepicker({' + $(this).attr('data-jqdpparams') + '});');
    } else {
        $(this).datepicker();
    }//if($(this).attr('data-jqdpparams').length)
});//$(document).on('focus','.clsJqDatePicker',function(e)

$(document).on('focus','.clsJqDateTimePicker',function(e) {
    let langcode=GetCurrentLanguageCode();
    $.datepicker.setDefaults($.datepicker.regional[langcode]);
    //$.timepicker.setDefaults($.timepicker.regional[langcode]);
    if($(this).attr('data-jqdpparams').length) {
        eval('$(this).datetimepicker({' + $(this).attr('data-jqdpparams') + '});');
    } else {
        $(this).datetimepicker();
    }//if($(this).attr('data-jqdpparams').length)
});//$(document).on('focus','.clsJqDateTimePicker',function(e)

$(document).on('focus','.clsJqTimePicker',function(e) {
    let langcode=GetCurrentLanguageCode();
    //$.timepicker.setDefaults($.timepicker.regional[langcode]);
    if($(this).attr('data-jqdpparams').length) {
        eval('$(this).timepicker({' + $(this).attr('data-jqdpparams') + '});');
    } else {
        $(this).timepicker();
    }//if($(this).attr('data-jqdpparams').length)
});//$(document).on('focus','.clsJqTimePicker',function(e)
/*** END For jQuery DateTimePicker ***/

/*** For NumericTextBox ***/
$(document).on('focus','.clsSetNumberFormat',function(e) {
    let aNull=$(this).attr('data-anull');
    if(aNull!==1 || $(this).val()!=='') {
        let nFormat=$(this).attr('data-format');
        if(nFormat) {
            let fArray=nFormat.split('|');
            // let decimalNo=Number(fArray[0]);
            // let decimalSeparator=fArray[1];
            let groupSeparator=fArray[2];
            let sufix=fArray[3];
            let formattedValue=$(this).val().replaceAll(sufix,'').replaceAll(groupSeparator,'');
            $(this).val(formattedValue);
        }//if(nFormat)
    }//if(aNull!=1 || $(this).val()!='')
});//$(document).on('focus','.clsSetNumberFormat',function(e)

$(document).on('focusout','.clsSetNumberFormat',function(e) {
    let aNull=$(this).attr('data-anull');
    if((aNull==='1' || aNull==='true') && $(this).val()==='') {
        $(this).css('color','#000000');
    } else {
        if($(this).hasClass('clsNumDiscColor')) {
            if($(this).val()<0) {
                $(this).css('color','#368000');
            } else if($(this).val()>0) {
                $(this).css('color','#CF0000');
            } else { $(this).css('color','#000000'); }
        }//if($(this).hasClass('clsNumDiscColor'))
        let nFormat=$(this).attr('data-format');
        if(nFormat) {
            let fArray=nFormat.split('|');
            let decimalNo=Number(fArray[0]);
            let decimalSeparator=fArray[1];
            let groupSeparator=fArray[2];
            let sufix=fArray[3];
            let tValue=$(this).val();
            tValue=tValue.replaceAll('%','').replaceAll(sufix,'').replaceAll(groupSeparator,'');
            if(decimalSeparator) {
                tValue=tValue.replaceAll(decimalSeparator,'.').trim();
            }//if(decimal_separator)
            let formattedValue=$.number(tValue,decimalNo,decimalSeparator,groupSeparator) + sufix;
            $(this).val(formattedValue);
        }//if(nFormat)
    }//if((aNull==='1' || aNull==='true') && $(this).val()==='')
});//$(document).on('focusout','.clsSetNumberFormat',function(e)

/**
 * @return {number}
 */
function FormatToNumericValue(elementValue,decimalSeparator,groupSeparator,sufix) {
    if(elementValue) {
        return Number(elementValue.replaceAll(sufix,'').replaceAll(groupSeparator,'').replaceAll(decimalSeparator,'.'));
    } else {
        return elementValue;
    }
}//END function FormatToNumericValue

/**
 * @return {number}
 */
function GetNumericTextBoxValue(element) {
    let eObj=null;
    if(typeof (element)==='object') {
        if(element.length) {
            eObj=$(element);
        }
    } else if(typeof (element)==='string') {
        if(element.length) {
            eObj=$('#' + element);
        }
    }
    if(eObj==null) {
        console.log('Invalid element:');
        console.log(element);
        return null;
    }
    let nFormat=eObj.data('format');
    if(!nFormat.length) {
        return eObj.val();
    } else {
        let farr=nFormat.split('|');
        let decimalSeparator=farr[1] || '.';
        let groupSeparator=farr[2] || ',';
        let suffix=farr[3] || '';
        return FormatToNumericValue(eObj.val(),decimalSeparator,groupSeparator,suffix);
    }//if(!dFormat.length)
}//END function GetNumericTextBoxValue

function SetNumericTextBoxValue(element,value) {
    let eObj=null;
    if(typeof (element)==='object') {
        if(element.length) {
            eObj=element;
        }
    } else if(typeof (element)==='string') {
        if(element.length) {
            eObj=$('#' + element);
        }
    }
    if(eObj==null) {
        console.log('Invalid element:');
        console.log(element);
    } else {
        let nFormat=eObj.data('format');
        if(nFormat && nFormat.length) {
            let fArray=nFormat.split('|');
            let decimalNo=Number(fArray[0]);
            let decimalSeparator=fArray[1];
            let groupSeparator=fArray[2];
            let sufix=fArray[3];
            let formattedValue=$.number(value,decimalNo,decimalSeparator,groupSeparator) + sufix;
            eObj.val(formattedValue);
        }//if(nFormat && nFormat.length)
    }
}//END function SetNumericTextBoxValue

function GetCalculatedValue(element_value,decimal_separator) {
    let formated_value=element_value + '';
    formated_value=formated_value.replaceAll('.',decimal_separator);
    return formated_value;
}//END function GetCalculatedValue
/*** END For NumericTextBox ***/
/*** For GroupCheckBox control ***/
function GroupCheckBoxBaseEvent(obj) {
    let multiple=$(obj).data('multiple') ? $(obj).data('multiple')==1 : false;
    if(!obj || ($(obj).val()==1 && multiple==false)) { return; }
    let eid=$(obj).attr('data-id');
    if(eid && $('#' + eid).length>0) {
        if(multiple) {
            let values=[];
            values=$('#' + eid).val().trim()=='' ? [] : $('#' + eid).val().trim().split('|');
            if($(obj).val()==1) {
                $(obj).val(0);
                values=values.filter(function(element) {
                    return element!=$(obj).data('val');
                });
            } else {
                $(obj).val(1);
                values.push($(obj).data('val'));
            }
            $('#' + eid).val(values.join('|'));
        } else {
            $('#' + eid).val($(obj).data('val'));
            $('#' + eid + '-container input[type=image].clsGCKBItem').val('0');
            $(obj).val('1');
        }//if (multiple) {
        $('#' + eid).trigger('change');
    }//if(eid && $('#'+eid).length>0)
}//END function GroupCheckBoxBaseEvent

$(document).on('click','.clsGCKBItem.active',function(e) {
    GroupCheckBoxBaseEvent(this);
});//$(document).on('click','.clsGCKBItem',function(e)

$(document).on('keypress','.clsGCKBItem.active',function(e) {
    if(event.keyCode==13) { GroupCheckBoxBaseEvent(this); }
});//$(document).on('keypress','.clsGCKBItem',function(e)

function UnselectGroupCheckBoxes(grouptag,obj,valuetag) {
    let notselected=true;
    $('#' + grouptag + ' input[type=image]').each(function() {
        if($(this).attr('id')==obj.id && obj.value!=0) {
            if($('#' + valuetag).length>0) {
                let cvalue=$('#' + $(this).attr('id') + '_value').val();
                $('#' + valuetag).val(cvalue);
                notselected=false;
            }//if($('#'+valuetag).length>0)
        } else {
            $(this).val(0);
        }//if($(this).attr('name')==obj.name)
    });
    if(notselected===true) {
        $('#' + valuetag).val('');
    }//if(notselected===true)
}//function UnselectGroupCheckBoxes
/*** END For GroupCheckBox control ***/
/*** For ComboBox ***/
function AppendComboBoxItem(elementid,val,text,selected) {
    if(!elementid || typeof (elementid)!='string' || !elementid.length) { return; }
    let obj=$('#' + elementid);
    if(!obj.length) { return; }
    if($(obj).hasClass('select2-hidden-accessible')) {
        if($(obj).find('option[value=\'' + val + '\']').length) {
            if(selected==1) { $(obj).val(val).trigger('change'); }
        } else {
            let newOption=new Option((text ? text : ''),val,(selected==1),(selected==1));
            $(obj).append(newOption).trigger('change');
        }//if($(obj).find("option[value='"+val+"']").length)
    } else {
        let optObj=$(obj).find('option[value=' + (val ? val : '') + ']').first();
        if(optObj.length) {
            optObj.html((text ? text : ''));
        } else {
            $(obj).append('<option value="' + val + '">' + (text ? text : '') + '</option>');
        }//if(optObj.length)
        if(selected==1) { $(obj).val(val).trigger('change'); }
    }//if($(obj).hasClass("select2-hidden-accessible"))
}//END function AppendComboBoxItem

//functie pentru afisarea corecta a selecturilor cu style diferit pe optiuni
function UpdateComboBoxClass(elementid) {
    $('#' + elementid + ' option').each(function(e) {
        if($(this).attr('selected')=='selected') {
            $('#' + elementid).addClass($(this).attr('class'));
        } else {
            $('#' + elementid).removeClass($(this).attr('class'));
        }//if($(this).attr('selected')=='selected')
    });//$('#'+elementid+' option').each(function(e)
}//END function UpdateComboBoxClass

function CBODDBtnClick(elementid) {
    let obj=$('#' + elementid + '-dropdown');
    if($(obj).css('display')=='none') {
        let lwidth=$(obj).width();
        let cwidth=$('#' + elementid + '-cbo').outerWidth();
        let loffset=$('#' + elementid + '-cbo').position();
        let ltop=loffset.top + $('#' + elementid + '-cbo').outerHeight();
        let lleft=loffset.left;
        if((lleft + lwidth)>window.innerWidth && window.innerWidth>lwidth) { lleft=Math.max(0,(lleft - (lwidth - cwidth))); }
        $(obj).css('top',ltop + 'px');
        $(obj).css('left',lleft + 'px');
        $(obj).show();
    } else {
        $(obj).hide();
    }//if($(obj).css('display')=='none')
}//END function CBODDBtnClick

function GCBOLoader(state,elementid) {
    let obj=$('#' + elementid + '-dropdown > .gcbo-loader');
    if(obj && obj.length>0) {
        if(state==1) {
            $(obj).css('padding-top',$(obj).outerHeight() / 2 - 16);
            $(obj).show();
        } else {
            $(obj).hide();
        }//if(state==1)
    }//if(obj && obj.length>0)
}//END function GCBOLoader

function GCBODDBtnClick(elementid,open) {
    if($('#' + elementid).attr('disabled')) { return false; }
    let obj=$('#' + elementid + '-dropdown');
    let cbo=$('#' + elementid + '-cbo');
    let act=open===0 ? false : (open===1 ? true : ($(obj).css('display')=='none'));
    if(act) {
        let lwidth=$(obj).width();
        let cwidth=$(cbo).outerWidth();
        let loffset=$(cbo).position();
        let ltop=loffset.top + $(cbo).outerHeight();
        let lleft=loffset.left;
        if((lleft + lwidth)>window.innerWidth && window.innerWidth>lwidth) { lleft=Math.max(0,(lleft - (lwidth - cwidth))); }
        $(obj).css('top',ltop + 'px');
        $(obj).css('left',lleft + 'px');
        if($(obj).attr('data-reload')==1 || $(cbo).val()!=$('#' + elementid).attr('data-text')) {
            let lcmd=$(cbo).attr('data-ajax');
            if(lcmd && lcmd.length>0) {
                $('#' + elementid + '-gcbo-target').html('');
                $(obj).show();
                lcmd=GibberishAES.dec(lcmd,elementid);
                eval(lcmd);
                if($(cbo).val()=='') { $(cbo).val($('#' + elementid).attr('data-text')); }
            }//if(lcmd && lcmd.length>0)
        }//if($(obj).attr('data-reload')==1 || $(cbo).val()!=$('#'+elementid).attr('data-text'))
        if($(obj).css('display')=='none') { $(obj).show(); }
    } else {
        if($(cbo).val()!=$('#' + elementid).attr('data-text')) {
            let otxt=$('#' + elementid).attr('data-text');
            if(otxt && otxt.length) { $(cbo).val(otxt); } else { $(cbo).val(''); }
        }//if($(cbo).val()!=$('#'+elementid).attr('data-text'))
        $(obj).hide();
    }//if(act)
}//END function GCBODDBtnClick

function GCBOSetValue(elementid,val,title,btnclick) {
    if($('#' + elementid).attr('disabled')) { return false; }
    let clear=false;
    let obj=$('#' + elementid);
    let cbo=$('#' + elementid + '-cbo');
    let oval=$(obj).val();
    if(val==null) {
        let emptyval=$(obj).attr('data-eval');
        val=(emptyval && emptyval.length) ? emptyval : '';
        clear=true;
    }//if(val==null)
    $(obj).val(val);
    $('#' + elementid + '-dropdown').attr('data-reload',1);
    $(cbo).val(title);
    $(cbo).attr('data-value',val);
    $(obj).attr('data-text',title);
    $('#' + elementid + '-dropdown .gcbo-selector').each(function(e) { if($(this).val()==1) { $(this).val('0'); } });
    if(val && val!='') {
        $('#' + elementid + '-dropdown #' + elementid + '-' + val + '.gcbo-selector').val('1');
    }//if(val && val!='')
    if(btnclick==true || btnclick==1) { CBODDBtnClick(elementid); } else if(clear) { $('#' + elementid + '-dropdown').hide(); }
    let onchange=$('#' + elementid).attr('data-onchange');
    if(onchange && onchange.length>0) { eval(onchange); }
}//END function GCBOSetValue

function InitFancyTree(elementid,module,method,url_params,namespace,uid,encrypt,checkboxes,hide_parents_checkbox,icon) {
    if(!elementid || elementid.length===0) { return; }
    let aurl=nAppBaseUrl + '/aindex.php?namespace=' + namespace;
    let luid='';
    let lparams=hide_parents_checkbox ? '&hpc=1' : '';
    if(uid || uid.length>0) { luid+='&uid=' + uid; }
    let paramsString='';
    if(typeof (url_params)==='object') {
        for(let pk in url_params) { paramsString+='&' + pk + '=' + url_params[pk]; }
    } else if(typeof (url_params)==='string') {
        paramsString=url_params;
    }//if(typeof(url_params)==='object')
    if(encrypt===1 || encrypt===true) {
        aurl+='&arhash=' + encodeURIComponent(GibberishAES.enc('module=' + module + '&method=' + method + paramsString + lparams + luid + '&phash=' + window.name,'xJS'));
    } else {
        aurl+='&module=' + module + '&method=' + method + paramsString + lparams + luid + '&phash=' + window.name;
    }//if(encrypt===1 || encrypt===true)
    $('#' + elementid).fancytree({
        checkbox: checkboxes || false,
        icon: icon || false,
        selectMode: 1,
        clickFolderMode: 1,
        debugLevel: 0,
        source: {
            url: aurl + '&response_type=json&tree=1',
        },
        lazyLoad: function(event,data) {
            data.result={
                url: aurl + '&response_type=json&tree=1',
                data: {key: data.node.key},
            };
        },
        createNode: function(event,data) {
            if(!data.node.data.hasSelectedChild) { return false; }
            $.ajax({
                url: aurl + '&response_type=json&tree=1',
                data: {key: data.node.key},
                dataType: 'json',
                success: function(response) { data.node.addChildren(response); }
            });
        },
        click: function(event,data) { $(this).trigger('fancyTree.onclick',data); },
        dblclick: function(event,data) { $(this).trigger('fancyTree.ondblclick',data); },
        select: function(event,data) { $(this).trigger('fancyTree.onselect',data); }
    });
}//END function InitFancyTree

$(document).on('keydown','input.clsGridComboBox[type=text]',function(e) {
    if($('#' + $(this).attr('data-id')).attr('disabled')) {
        e.preventDefault();
        return false;
    }
    if(e.keyCode===13) { //Enter
        let elementid=$(this).attr('data-id');
        $('#' + elementid + '-dropdown').attr('data-reload',1);
        GCBODDBtnClick(elementid,1);
    } else if(e.keyCode===27) { //Escape
        e.preventDefault();
        GCBODDBtnClick($(this).attr('data-id'),0);
    } else if((e.altKey && (e.keyCode===40 || e.keyCode===98)) || e.keyCode===115) { //Alt+Down/Alt+Numpad2/F4
        GCBODDBtnClick($(this).attr('data-id'),null);
    }//if(e.keyCode==13)
});//$(document).on('keydown','input.clsGridComboBox[type=text]',function(e)

$(document).on('keydown','input.clsTreeComboBox[type=text]',function(e) {
    if($('#' + $(this).attr('data-id')).attr('disabled')) {
        e.preventDefault();
        return false;
    }
    if(e.keyCode===13 || (e.altKey && (e.keyCode===40 || e.keyCode===98)) || e.keyCode===115) { //Enter/Alt+Down/Alt+Numpad2/F4
        CBODDBtnClick($(this).attr('data-id'));
    } else if(e.keyCode===27) { //Escape
        e.preventDefault();
        $('#' + $(this).attr('data-id') + '-dropdown').hide();
    }//if(e.keyCode==13)
});//$(document).on('keydown','input.clsTreeComboBox[type=text]',function(e)
/*** END For ComboBox ***/

/*** For Select2 and SmartComboBox ***/
function SmartCBOInitialize() {
    // console.log('SmartCBOInitialize>>');
    $('select.SmartCBO').each(function(i,obj) {
        if(!$(obj).hasClass('select2-hidden-accessible')) {
            let tagId=$(obj).attr('id');
            let params_str=$(obj).attr('data-smartcbo');
            if(tagId && params_str) {
                let oParams={};
                let oParamsString=GibberishAES.dec(decodeURIComponent(params_str),tagId);
                try {
                    oParams=eval(oParamsString);
                } catch(e) {
                    console.log(e);
                    console.log(oParamsString);
                }
                // console.log('oParams:');
                // console.log(oParams);
                new Promise((resolve,reject) => {
                    $(obj).select2(oParams);
                    resolve();
                }).then(function() {
                    $(obj).trigger('select2-loaded');
                });
            }//if(tagId && params_str)
        }//if(!$(obj).hasClass("select2-hidden-accessible"))
    });//$('select.SmartCBO').each(function(i,obj)
}//END function SmartCBOInitialize

function GetSmartCBOValue(element,asObject) {
    if(!element) { return null; }
    let lElement=element;
    if(typeof (element)!='object') { lElement=$('#' + element); }
    if(!$(lElement).length) { return null; }
    let lVal=$(lElement).val() || '';
    if(!Array.isArray(lVal) || asObject===true || asObject==1) { return lVal; }
    lVal=lVal.join(',');
    return lVal;
}//END function GetSmartCBOValue

function GetSmartCBOText(element,asObject) {
    if(!element) { return null; }
    let lElement=element;
    if(typeof (element)!='object') { lElement=$('#' + element); }
    if(!$(lElement).length) { return null; }
    let lText=$(lElement).select2('data');
    if(!Array.isArray(lText) || asObject===true || asObject==1) { return lText; }
    let lVal='';
    for(let i=0; i<lText.length; i++) {
        lVal+=(lVal.length ? ', ' : '') + (lText[i].name || lText[i].text);
    }//END for
    return lVal;
}//END function GetSmartCBOText
/**
 * @return {boolean}
 */
function SetSmartCBOValue(element,newVal) {
    if(!element) { return false; }
    let lElement=element;
    if(typeof (element)!='object') { lElement=$('#' + element); }
    if(!$(lElement).length) { return false; }
    if(typeof (newVal)=='object') {
        let sOption=new Option(newVal.name,newVal.id,true,true);
        $(lElement).append(sOption).trigger('change');
    } else {
        $(lElement).val(newVal).trigger('change');
    }//if(typeof(newVal)==='object')
    return true;
}//function SetSmartCBOValue

function GetSelect2Val(element,org) {
    if(!element) { return undefined; }
    let lElement=element;
    if(typeof (element)!=='object') { lElement=$('#' + element); }
    let lVal=$(lElement).val() || '';
    if(org!==true && org!==1 && typeof (lVal)==='object') { lVal=lVal.join(','); }
    return lVal;
}//function GetSelect2Val
/*** END For Select2 and SmartComboBox ***/

/*** For KVList ***/
$(document).on('click','div.MainKVL > .KVLAddBtn',function(e) { KVLAddElement(this); });
$(document).on('click','div.MainKVL > ul.KVLList > li > .KVLIDelBtn',function(e) { KVLRemoveElement(this); });
$(document).on('keydown','div.MainKVL > input[type=text].KVLNewKey',function(e) { if(e.keyCode==13) { KVLAddElement(this); } });

function KVLAddElement(obj) {
    let parent=$(obj).parent();
    let vitem=$(parent).children('input[type=text].KVLNewKey').first();
    let ival=vitem.val();
    let iname=vitem.data('name');
    if(!iname) { iname=$(parent).attr('name'); }
    if(ival && ival.length) {
        $(parent).find('ul.KVLList span.KVLBlank').addClass('hidden');
        $(parent).children('ul.KVLList').first().append('<li><label class="KVLILabel">' + ival + '</label><input type="text" class="KVLIValue postable" name="' + iname + '[' + ival + ']" placeholder="[value]" value=""><button class="KVLIDelBtn"><i class="fa fa-minus-circle"></i></button></li>');
        $(vitem).val('');
    }//if(ival && ival.length)
}//END function KVLAddElement

function KVLRemoveElement(obj) {
    let parent=$(obj).parent();
    if(parent) {
        let ulparent=$(parent).parent();
        $(parent).remove();
        if(ulparent && $(ulparent).find('li').length<=1) {
            $(ulparent).find('span.KVLBlank').removeClass('hidden');
        }//if(ulparent && $(ulparent).find('li').length<=1)
    }//if(parent)
}//END function KVLRemoveElement
/*** END For KVList ***/

/*** For Actions ***/
function BindShortcuts(saveCtrl,cancelCtrl) {
    let kpressfct=function(e) {
        if(e.keyCode==13 && $('input:focus[type=text],textarea:focus').length==0)
            $('#' + saveCtrl).click();
        else if(e.keyCode==27) {
            e.preventDefault();
            $('#' + cancelCtrl).click();
        }//if(e.keyCode==13 && $('input:focus[type=text],textarea:focus').length==0)
    };//let kpressfct= function(e)
    $(document).on('keypress',kpressfct);
}//END function BindShortcuts

$(document).on('keydown','.clsOnEnterAction',function(e) {
    if(e.keyCode==13) {
        let lact=$(this).attr('data-onenter');
        if(lact) { eval(lact); }
    }//if(e.keyCode==13)
});//$(document).on('keydown','.clsOnEnterAction',function(e)

$(document).on('keydown','.clsOnEnterActionButton',function(e) {
    if(e.keyCode==13) {
        let lid=$(this).attr('data-onenterbtn');
        if(lid) {
            $(this).trigger('focusout');
            $('#' + lid).click();
        }//if(lid)
    }//if(e.keyCode==13)
});//$(document).on('keydown','.clsOnEnterActionButton',function(e)

/**
 * @return {boolean}
 */
function SetFieldErrorClass(obj,reset,errClass,skipValueValidation) {
    if(!obj) {
        return false;
    }
    let lClass=errClass ? errClass : 'clsFieldError';
    let $obj=$(obj);
    let validValue=false;
    if(reset) {
        validValue=true;
    } else if(skipValueValidation) {
        validValue=false;
    } else {
        if($obj.hasClass('clsNumericTextBox')) {
            validValue=GetNumericTextBoxValue($obj)!==0;
        } else if($obj.hasClass('clsSmartComboBox')) {
            validValue= !!GetSmartCBOValue($obj.attr('id'));
        } else {
            validValue=$obj.val() && $obj.val().length>0;
        }
        if($obj.hasClass('clsCkEditor')) {
            $obj=$obj.parent().find('#cke_' + $obj.attr('id') + ' .cke_contents .cke_wysiwyg_div');
        }
    }
    if(validValue) {
        $obj.removeClass(lClass);
        if($obj.hasClass('clsSmartComboBox')) {
            let $sObj=$obj.data('select2');
            if($sObj) {
                $sObj.$selection.removeClass(lClass);
            }
        }
    } else {
        $obj.addClass(lClass);
        if($obj.hasClass('clsSmartComboBox')) {
            let $sObj=$obj.data('select2');
            if($sObj) {
                $sObj.$selection.addClass(lClass);
            }
        }
    }
    return true;
}//END function SetFieldErrorClass

function AddClassOnErrorByParent(parentId,reset,errClass,skipValueValidation) {
    $('#' + parentId + ' .clsRequiredField').each(function() {
        if($(this).hasClass('select2-selection')) {
            return;
        }
        SetFieldErrorClass(this,reset,errClass,skipValueValidation);
    });
}//END function AddClassOnErrorByParent

function AddClassOnError(elementId,reset,errClass,skipValueValidation) {
    SetFieldErrorClass($('#' + elementId),reset,errClass,skipValueValidation);
}//function AddClassOnError

function AddClassOnErrorByName(formId,elementName,reset,errClass,skipValueValidation) {
    $('#' + formId + ' [name="' + elementName + '"]').each(function() {
        if($(this).hasClass('select2-selection')) {
            return;
        }
        SetFieldErrorClass(this,reset,errClass,skipValueValidation);
    });
}//function AddClassOnErrorByName
/*** END For Actions ***/

/*** For Validations ***/
function CheckIfEnter(e) { //e is event object passed from function invocation
    let characterCode;//  literal character code will be stored in this variable
    if(e && e.which) { //if which property of event object is supported (NN4)
        characterCode=e.which; //character code is contained in NN4's which property
    } else {
        e=event;
        characterCode=e.keyCode; //character code is contained in IE's keyCode property
    }//if(e && e.which)
    return characterCode==13 ? true : false;
}//END function CheckIfEnter

$(document).on('keydown','.clsSetNoNumericValidation',function(e) {
    let key=e.which || e.keyCode || e.charCode || 0;
    if((key>=48 && key<=57) || (key>=96 && key<=105)) { e.preventDefault(); }
});//$(document).on('keydown','.clsSetNoNumericValidation',function(e)

$(document).on('keydown','.clsSetNumericValidation',function(e) {
    let key=e.which || e.keyCode || e.charCode || 0;
    if(e.ctrlKey || e.altKey
        || $.inArray(key,[
            0,		//null
            8,		//Backspace
            9,		//Tab
            13,		//Enter
            16,		//Shift
            27,		//Esc
            33,		//PgUp
            34,		//PgDown
            35,		//End
            36,		//Home
            37,		//Left
            38,		//Up
            39,		//Right
            40,		//Down
            45,		//Ins
            46,		//Del
        ])!== -1
        || (!e.shiftKey && key>=96 && key<=105)		//numeric pad keys
        || (!e.shiftKey && key>=48 && key<=57)			//numeric keys
        || (!e.shiftKey && (key==189 || key==109)
            && this.selectionStart==0 && this.value.split('-').length<2)
    ) { return; }
    let nformat=$(this).attr('data-format');
    let ds=false;
    if(nformat) {
        let farr=nformat.split('|');
        if(farr[0]!==0) { ds=farr[1]; }
    }//if(nformat)
    if(!e.shiftKey && key==188 && ds==',' && this.value.indexOf(ds)== -1) { return; }
    if(!e.shiftKey && (key==190 || key==110) && ds=='.' && this.value.indexOf(ds)== -1) { return; }
    //alert(e.ctrlKey+'|'+e.altKey+'|'+e.shiftKey+'|'+key);
    e.preventDefault();
});//$(document).on('keydown','.clsSetNumericValidation',function(e)

$(document).on('keydown','.clsSetPhoneValidation',function(e) {
    let key=e.which || e.keyCode || e.charCode || 0;
    if(e.ctrlKey || e.altKey
        || $.inArray(key,[
            0,		//null
            8,		//Backspace
            9,		//Tab
            13,		//Enter
            16,		//Shift
            27,		//Esc
            32,		//Space
            33,		//PgUp
            34,		//PgDown
            35,		//End
            36,		//Home
            37,		//Left
            38,		//Up
            39,		//Right
            40,		//Down
            45,		//Ins
            46,		//Del
        ])!== -1
        || (!e.shiftKey && $.inArray(key,[
            190,	//.
            110,	//. (numpad)
            189,	//-
            109,	//- (numpad)
        ]!== -1))
        || (!e.shiftKey && key>=96 && key<=105)			//numeric pad keys
        || (!e.shiftKey && key>=48 && key<=57)			//numeric keys
        || ((!e.shiftKey && key==107) || (e.shiftKey && key==187)
            && this.selectionStart==0 && this.value.split('+').length<2)
    ) { return; }
    e.preventDefault();
});//$(document).on('keydown','.clsSetPhoneValidation',function(e)
/*** END For Validations ***/

/*** For text inputs ***/
$(document).on('focusout','.clsSetUcFirst',function(e) {
    let lVal=$(this).val().ucfirst(false);
    $(this).val(lVal);
});//$(document).on('focusout','.clsSetUcFirst',function(e)

$(document).on('focusout','.clsSetUcFirstAll',function(e) {
    let lVal=$(this).val().ucfirst(true);
    $(this).val(lVal);
});//$(document).on('focusout','.clsSetUcFirstAll',function(e)
/*** END For text inputs ***/

function AnimatedHide(elementid,val,speed) {
    if($('#' + elementid).length>0) {
        let lspeed=speed ? speed : 600;
        if(val==1) {
            $('#' + elementid).hide(lspeed);
        } else {
            $('#' + elementid).show(lspeed);
        }//if(val==1)
    }//if($('#'+elementid).length > 0)
}//END function AnimatedHide

function AnimatedHideWithSave(elementid,valueid,speed) {
    if($('#' + elementid).length>0) {
        let lspeed=speed ? speed : 600;
        if($('#' + valueid).val()===1) {
            $('#' + elementid).hide(lspeed);
        } else {
            $('#' + elementid).show(lspeed);
        }//if($('#'+valueid).val()==1)
        if(typeof (Storage)!=='undefined') {
            localStorage.setItem(valueid,$('#' + valueid).val() + '|' + elementid);
        } else {
            let expdate=new Date();
            expdate.setMonth(expdate.getMonth() + 6);
            document.cookie=valueid + '=' + $('#' + valueid).val() + '|' + elementid
                + '; expires=' + expdate.toGMTString() + '; path=/; domain=' + location.host + ';';
        }//if(typeof(Storage)!=='undefined')
    }//if($('#'+elementid).length > 0)
}//END function AnimatedHideWithSave

/*** For CKEditor ***/
function CreateCkEditor(phash,e,multi,econfig,ewidth,eheight) {
    if(multi) {
        let es=e.split(',');
        for(let i=0; i<es.length; i++) { CreateCkEditor(phash,es[i],false,econfig,ewidth,eheight); }
    } else {
        if(!e || e.length===0) { return; }
        // if(!phash) { phash = window.name.length>0 ? window.name : '_xbasepage_'; }
        // console.log('CreateCkEditor: ' + e + ' // ' + phash);
        let ckei=window.ckei_list;
        if(ckei===undefined || ckei===null || typeof (ckei)!='object') { ckei=[]; }
        let newconfig=typeof (econfig)=='object' ? econfig : {};
        if(ewidth) { newconfig.width=ewidth; }
        if(eheight) { newconfig.height=eheight; }
        if(CKEDITOR.instances[e]) { CKEDITOR.instances[e].destroy(true); }
        CKEDITOR.replace(e,newconfig);
        if($.inArray(e,ckei)=== -1) { ckei.push(e); }
        window.ckei_list=ckei;
    }//if(multi)
}//function CreateCkEditor(e,multi)

function DestroyCkEditors(phash,target) {
    if(!target || target.length===0) { return; }
    let targetObj=$('#' + target);
    if(!targetObj) { return; }
    // if(!phash) { phash = window.name.length>0 ? window.name : '_xbasepage_'; }
    // console.log('DestroyCkEditors: ' + target + ' // ' + phash);
    let ckei=window.ckei_list;
    if(ckei===undefined || ckei===null || typeof (ckei)!='object' || ckei.length===0) { return; }
    let newCkei=[];
    for(let i=0; i<ckei.length; i++) {
        let dropped=false;
        targetObj.find('#' + ckei[i]).each(function() {
            // console.log('Drop: ' + ckei[i]);
            let editor=CKEDITOR.instances[ckei[i]];
            if(editor) { editor.destroy(true); }
            dropped=true;
        });
        if(!dropped) {
            // console.log('Skip: ' + ckei[i]);
            newCkei.push(ckei[i]);
        }//if(!dropped)
    }//END for
    window.ckei_list=newCkei;
}//function DestroyCkEditors

function DestroyCkEditor(phash,e,multi) {
    // if(!phash) { phash = window.name.length>0 ? window.name : '_xbasepage_'; }
    // console.log('DestroyCkEditor!: ' + e + ' // ' + phash);
    if(e) {
        if(multi) {
            let es=e.split(',');
            for(let i=0; i<es.length; i++) { DestroyCkEditor(phash,es[i],false); }
        } else {
            let ckei=window.ckei_list;
            let editor=CKEDITOR.instances[e];
            if(editor) { editor.destroy(true); }
            if(ckei===undefined || ckei===null || typeof (ckei)!='object' || $.inArray(e,ckei)=== -1) { return; }
            ckei.splice(ckei.indexOf(e),1);
            window.ckei_list=ckei;
        }//if(typeof e=='array')
    } else {
        let ckei=window.ckei_list;
        if(ckei===undefined || ckei===null || typeof (ckei)!='object' || ckei.length===0) { return; }
        for(let i=0; i<ckei.length; i++) {
            let editor=CKEDITOR.instances[ckei[i]];
            if(editor) { editor.destroy(true); }
        }//END for
        window.ckei_list=[];
    }//if(e)
}//function DestroyCkEditor
/**
 * @return {string}
 */
function GetCkEditorData(e) {
    if(typeof (e)==='object') { e=e.getAttribute('id'); }
    let editor=CKEDITOR.instances[e];
    if(editor) { return editor.getData(); }
    return '';
}//function GetCkEditorData

function SetCkEditorData(e,data) {
    if(typeof (e)==='object') { e=e.getAttribute('id'); }
    let editor=CKEDITOR.instances[e];
    if(editor) {
        editor.setData(data);
    }
}//function SetCkEditorData

function SetCkEditorReadonly(e,value) {
    if(typeof (e)==='object') { e=e.getAttribute('id'); }
    let editor=CKEDITOR.instances[e];
    if(editor) {
        editor.setReadOnly(value===true || value==='true' || value===1 || value==='1');
    }
}//function SetCkEditorData

function AppendCkEditorData(e,data) {
    if(typeof (e)==='object') { e=e.getAttribute('id'); }
    let editor=CKEDITOR.instances[e];
    if(editor) {
        editor.insertHtml(data);
    }
}//function AppendCkEditorData
/*** END For CKEditor ***/

/*** For FileUploader ***/
function CreateFileUploader(elementid,multi) {
    if(multi===1) {
        if(elementid) {
            $('#' + elementid + ' .clsFileUploader').each(function(index) { CreateFileUploader(this,0); });
        } else {
            $('.clsFileUploader').each(function(index) { CreateFileUploader(this,0); });
        }//if(elementid)
    } else if(elementid) {
        let element=null;
        if(typeof (elementid)==='object') {
            element=$(elementid);
        } else {
            element=$('#' + elementid);
        }//if(typeof(elementid)=='object')
        let droparea=false;
        if($(element).parent().hasClass('clsDropArea')) {
            $(document).on('drop dragover',function(e) { e.preventDefault(); });
            droparea=$(element).parent();
        }//if($(element).parent().hasClass('clsDropArea'))
        $(element).fileupload({
            dataType: 'json',
            dropZone: droparea,
            formData: [
                {name: 'targetdir',value: $(element).attr('data-targetdir')},
                {name: 'subfolder',value: $(element).attr('data-subfolder')}
            ],
            start: function(e) {
                let statusid=$(element).attr('data-statusid');
                if(statusid && $('#' + statusid).length) {
                    $('#' + statusid).css('display','');
                }//if(statusid && $('#'+statusid).length)
            },//start: function(e)
            done: function(e,data) {
                let statusid=$(element).attr('data-statusid');
                if(statusid && $('#' + statusid).length) {
                    $('#' + statusid).css('display','none');
                }//if(statusid && $('#'+statusid).length)
                if(data.result.files[0].error && data.result.files[0].error!=='') {
                    ShowErrorDialog('Upload failed: ' + data.result.files[0].error);
                } else {
                    let callbackFunc=$(element).attr('data-callback');
                    if(callbackFunc) {
                        callbackFunc=GibberishAES.dec(decodeURIComponent(callbackFunc),'HTML');
                        if(callbackFunc instanceof Function) {
                            callbackFunc(data.result.files[0].name,data.files[0].name,this);
                        } else {
                            callbackFunc=callbackFunc.replace('&amp;namespace=','&namespace=');
                            callbackFunc=callbackFunc.replaceAll('#uploadedFile#',data.result.files[0].name);
                            callbackFunc=callbackFunc.replaceAll('#originalFile#',data.files[0].name);
                            eval(callbackFunc);
                        }//if(callbackFunc instanceof Function)
                    }//if(callbackFunc)
                }//if(data.files[0].error && data.files[0].error!='')
            },//done: function(e,data)
            fail: function(e,data) {
                ShowErrorDialog('Upload failed: ' + data.errorThrown + ' (Status: ' + data.textStatus + ')');
            }//fail: function(e,data)
        });//$(element).fileupload
    } else {
        return false;
    }//if(multi==1)
}//END function CreateFileUploader
/*** END For FileUploader ***/

/*** For TreeGrid ***/
function TreeGridViewAction(obj,pid,tableid,cval,orgid) {
    if(!orgid) { orgid=pid; }
    if(cval!=0 && cval!=1) { cval=$(obj).val()==1 ? 0 : 1; }
    if(orgid==pid) { $(obj).val(cval); }
    $('table#' + tableid + ' > tbody > tr.clsTreeGridChildOf' + pid).each(function() {
        if(cval==1) { $(this).show(); } else { $(this).hide(); }
        obj=$(this).find('input.clsTreeGridBtn').first();
        if(typeof (obj)=='object') {
            pid=$(this).attr('data-id');
            if(pid) { TreeGridViewAction(obj,pid,tableid,(cval==1 ? $(obj).val() : 0),orgid); }
        }//if(typeof(obj)=='object')
    });//$('table#'+tableid+' > tbody > tr.clsTreeGridChildOf'+pid).each(function(i)
}//END function TreeGridViewAction
/*** END For TreeGrid ***/