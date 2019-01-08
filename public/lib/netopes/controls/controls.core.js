/**
 * NETopes controls core javascript file
 *
 * Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * License    LICENSE.md
 *
 * @author     George Benjamin-Schonberger
 * @version    2.5.0.1
 */

/*** For Loader and Screen blocking ***/
function ShowLoader(element,full) {
	if(typeof(element)=='object') {
		var obj = element;
	} else {
		var obj = $('#'+element);
	}//if(typeof(element)=='object')
	if($(obj).length>0) {
		if(full===1 || full===true || full==='1') { $(obj).css('height',Math.max($(document).outerHeight(),window.innerHeight)+'px'); }
		$(obj).show();
	}//if($(obj).length>0)
}//function ShowLoader

function HideLoader(element) {
	if(typeof(element)=='object') {
		var obj = element;
	} else {
		var obj = $('#'+element);
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
	if(grecaptcha && elementid && $('#'+elementid).length) {
		if(!site_key || site_key.length===0) {
			site_key = $('#'+elementid).data('site-key');
		}
		if(site_key) { grecaptcha.render(elementid,{ 'sitekey' : site_key }); }
	}
}//function randerReCaptcha

function getReCaptcha(elementid) {
    if(grecaptcha) {
        var response = grecaptcha.getResponse();
        if(response.length) {
        	if(elementid && elementid.length) {
        		$('#'+elementid).val(response);
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
function GetCurrentLanguageCode(){
	var langsel = $('#lang-selector').val();
	return langsel.substring(langsel.indexOf('^',0)+1).toLowerCase();
}//END function GetCurrentLanguageCode

function GetNewLanguageLink(newlang,newdomain,olddomain) {
	var newvalue = newlang;
	if(strpos(newlang,'^')) { newlang = newlang.substring(newlang.indexOf('^',0)+1); }
	var clink = window.location.href.split(/#/)[0];
	var clinkhash = window.location.hash.toString();
	var clang = GetCurrentLanguageCode();
	if(newdomain!=undefined && newdomain.length>0) {
		clink = clink.replace(olddomain,newdomain);
	}//if(newdomain!=undefined && newdomain.length>0)
	var newlink = clink;
	if(strpos(clink,'/'+clang+'/',0)) {
		newlink = clink.replace('/'+clang+'/','/'+newlang+'/');
	} else if(strpos(clink,'/'+clang,0)) {
		newlink = clink.replace('/'+clang,'/'+newlang+'/');
	} else if(strpos(clink,'language='+clang,0)) {
		newlink = clink.replace('language='+clang,'language='+newlang);
	} else if(strpos(clink,'/index.php',0)) {
		newlink = clink.replace('/index.php','/'+newlang+'/index.php');
	} else {
		newlink = clink+(clink.charAt(clink.length-1)=='/' ? '' : '/')+newlang+'/';
	}//if(strpos(clink,newlang,0))
	$('#lang-selector').val(newvalue);
	return newlink+clinkhash;
}//END function GetNewLanguageLink

function SetNewLanguageLink(newlang,newdomain,olddomain) {
	var lnlink = GetNewLanguageLink(newlang,newdomain,olddomain);
	if(lnlink) { window.location.href = lnlink; }
}//END function SetNewLanguageLink
/*** END For Language selector ***/

/*** For getting element value ***/
function GetElementValue(elementid,elementproperty) {
	var lproperty = elementproperty ? elementproperty : 'value';
	var result = '';
	if($('#'+elementid).length>0) {
		switch(lproperty) {
			case 'innerHtml':
				result = $('#'+elementid).html();
				break;
			case 'option':
				result = $('#'+elementid+' option:selected').text();
				break;
			case 'value':
			default:
				result = $('#'+elementid).val();
				break;
		}//END switch
	}//if($('#'+elementid).length>0)
	return result;
}//END function GetElementValue
/*** END For getting element value ***/
/*** For jQuery DateTimePicker ***/
$(document).on('focus','.clsJqDatePicker',function(e) {
	var langcode = GetCurrentLanguageCode();
	$.datepicker.setDefaults($.datepicker.regional[langcode]);
	if($(this).attr('data-jqdpparams').length) {
		eval('$(this).datepicker({' + $(this).attr('data-jqdpparams') + '});');
	} else {
		$(this).datepicker();
	}//if($(this).attr('data-jqdpparams').length)
});//$(document).on('focus','.clsJqDatePicker',function(e)

$(document).on('focus','.clsJqDateTimePicker',function(e) {
	var langcode = GetCurrentLanguageCode();
	$.datepicker.setDefaults($.datepicker.regional[langcode]);
	//$.timepicker.setDefaults($.timepicker.regional[langcode]);
	if($(this).attr('data-jqdpparams').length) {
		eval('$(this).datetimepicker({' + $(this).attr('data-jqdpparams') + '});');
	} else {
		$(this).datetimepicker();
	}//if($(this).attr('data-jqdpparams').length)
});//$(document).on('focus','.clsJqDateTimePicker',function(e)

$(document).on('focus','.clsJqTimePicker',function(e) {
	var langcode = GetCurrentLanguageCode();
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
	var anull = $(this).attr('data-anull');
	if(anull!==1 || $(this).val()!=='') {
		var nformat = $(this).attr('data-format');
		if(nformat) {
			var farr = nformat.split('|');
			var formated_value = $(this).val().replaceAll(farr[3],'').replaceAll(farr[2],'');
			$(this).val(formated_value);
		}//if(nformat)
	}//if(anull!=1 || $(this).val()!='')
});//$(document).on('focus','.clsSetNumberFormat',function(e)

$(document).on('focusout','.clsSetNumberFormat',function(e) {
	var anull = $(this).attr('data-anull');
	if((anull==='1' || anull==='true') && $(this).val()==='') {
		$(this).css('color','#000000');
	} else {
		if($(this).hasClass('clsNumDiscColor')) {
			if($(this).val()<0) { $(this).css('color','#368000');
			} else if ($(this).val()>0) { $(this).css('color','#CF0000');
			} else { $(this).css('color','#000000'); }
		}//if($(this).hasClass('clsNumDiscColor'))
		var nformat = $(this).attr('data-format');
		if(nformat) {
			var farr = nformat.split('|');
			var decimal_no = Number(farr[0]);
			var decimal_separator = farr[1];
			var group_separator = farr[2];
			var sufix = farr[3];
			var tvalue = 0;
			if(decimal_separator) {
				tvalue = $(this).val().replaceAll('%','').replaceAll(group_separator,'').replaceAll(decimal_separator,'.').replaceAll(sufix,'').trim();
			} else {
				tvalue = $(this).val().replaceAll('%','').replaceAll(group_separator,'').replaceAll(sufix,'').trim();
			}//if(decimal_separator)
			var formated_value = $.number(tvalue,decimal_no,decimal_separator,group_separator) + sufix;
			$(this).val(formated_value);
		}//if(nformat)
	}//if((anull=='1' || anull=='true') && $(this).val()=='')
});//$(document).on('focusout','.clsSetNumberFormat',function(e)

/**
 * @return {number}
 */
function FormatToNumericValue(element_value,decimal_separator,group_separator,sufix) {
    return Number(element_value.replaceAll(sufix,'').replaceAll(group_separator,'').replaceAll(decimal_separator,'.'));
}//END function FormatToNumericValue

/**
 * @return {number}
 */
function GetNumericTextboxValue(element) {
	var eObj = null;
	if(typeof(element)==='object') {
		if(element.length) {
			eObj = element;
		}
	} else if(typeof(element)==='string') {
		if(element.length) {
			eObj = $('#'+element);
		}
	}
	if(eObj==null) {
		console.log('Invalid element:');
		console.log(element);
		return null;
	}
	var dFormat = eObj.data('format');
	if(!dFormat.length) {
		return eObj.val();
	} else {
		var farr = dFormat.split('|');
		var decimalSeparator = farr[1] || '.';
		var groupSeparator = farr[2] || ',';
		var suffix = farr[3] || '';
		return FormatToNumericValue(eObj.val(), decimalSeparator, groupSeparator, suffix);
	}//if(!dFormat.length)
}//END function GetNumericTextboxValue

function GetCalculatedValue(element_value,decimal_separator) {
	var formated_value = element_value+'';
	formated_value = formated_value.replaceAll('.',decimal_separator);
	return formated_value;
}//END function GetCalculatedValue
/*** END For NumericTextBox ***/

/*** For CheckBox control ***/
function CheckBoxClickBaseEvent(obj,elementid) {
	if(typeof(obj)!='object') {
		if(!elementid || elementid.length==0) { return false; }
		obj = $('#'+elementid);
	}//if(typeof(obj)!='object')
	var cvalue = $(obj).val();
	if(cvalue==1) {
		$(obj).val(0);
	} else {
		$(obj).val(1);
	}//if(cvalue==1)
	$(obj).trigger('change');
}//END function CheckBoxClickBaseEvent

function UnselectGroupCheckBoxes(grouptag,obj,valuetag){
	var notselected = true;
	$('#'+grouptag+' input[type=image]').each( function() {
		if($(this).attr('id')==obj.id && obj.value!=0){
			if($('#'+valuetag).length>0) {
				var cvalue = $('#'+$(this).attr('id')+'_value').val();
				$('#'+valuetag).val(cvalue);
				notselected = false;
			}//if($('#'+valuetag).length>0)
		}else{
			$(this).val(0);
		}//if($(this).attr('name')==obj.name)
	});
	if(notselected===true) {
		$('#'+valuetag).val('');
	}//if(notselected===true)
}//function UnselectGroupCheckBoxes
/*** END For CheckBox control ***/
/*** For GroupCheckBox control ***/
function GroupCheckBoxBaseEvent(obj) {
	if(!obj || $(obj).val()=='1') { return; }
	var eid = $(obj).attr('data-id');
	if(eid && $('#'+eid).length>0) {
		$('#'+eid).val($(obj).attr('data-val'));
		$('#'+eid+'-container input[type=image].clsGCKBItem').val('0');
		$(obj).val('1');
		$('#'+eid).trigger('change');
		// console.log($('#'+eid).val());
	}//if(eid && $('#'+eid).length>0)
}//END function GroupCheckBoxBaseEvent

$(document).on('click','.clsGCKBItem.active',function(e) {
	GroupCheckBoxBaseEvent(this);
});//$(document).on('click','.clsGCKBItem',function(e)

$(document).on('keypress','.clsGCKBItem.active',function(e) {
	if(event.keyCode==13) { GroupCheckBoxBaseEvent(this); }
});//$(document).on('keypress','.clsGCKBItem',function(e)
/*** END For GroupCheckBox control ***/
/*** For ComboBox ***/
function AppendComboBoxItem(elementid,val,text,selected) {
	if(!elementid || typeof(elementid)!='string' || !elementid.length) { return; }
	var obj = $('#'+elementid);
	if(!obj.length) { return; }
	if($(obj).hasClass("select2-hidden-accessible")) {
		if($(obj).find("option[value='"+val+"']").length) {
			if(selected==1) { $(obj).val(val).trigger('change'); }
		} else {
			var newOption = new Option((text ? text : ''),val,(selected==1),(selected==1));
			$(obj).append(newOption).trigger('change');
		}//if($(obj).find("option[value='"+val+"']").length)
	} else {
		var optObj = $(obj).find('option[value='+(val ? val : '')+']').first();
		if(optObj.length) {
			optObj.html((text ? text : ''));
		} else {
			$(obj).append('<option value="'+val+'">'+(text ? text : '')+'</option>');
		}//if(optObj.length)
		if(selected==1) { $(obj).val(val).trigger('change'); }
	}//if($(obj).hasClass("select2-hidden-accessible"))
}//END function AppendComboBoxItem

//functie pentru afisarea corecta a selecturilor cu style diferit pe optiuni
function UpdateComboBoxClass(elementid) {
	$('#'+elementid+' option').each(function(e) {
		if($(this).attr('selected')=='selected') {
			$('#'+elementid).addClass($(this).attr('class'));
		} else {
			$('#'+elementid).removeClass($(this).attr('class'));
		}//if($(this).attr('selected')=='selected')
	});//$('#'+elementid+' option').each(function(e)
}//END function UpdateComboBoxClass

function CBODDBtnClick(elementid) {
	var obj = $('#'+elementid+'-dropdown');
	if($(obj).css('display')=='none') {
		var lwidth = $(obj).width();
		var cwidth = $('#'+elementid+'-cbo').outerWidth();
		var loffset = $('#'+elementid+'-cbo').position();
		var ltop = loffset.top + $('#'+elementid+'-cbo').outerHeight();
		var lleft = loffset.left;
		if((lleft+lwidth)>window.innerWidth && window.innerWidth>lwidth) { lleft = Math.max(0,(lleft - (lwidth - cwidth))); }
		$(obj).css('top',ltop+'px');
		$(obj).css('left',lleft+'px');
		$(obj).show();
	} else {
		$(obj).hide();
	}//if($(obj).css('display')=='none')
}//END function CBODDBtnClick

function GCBOLoader(state,elementid) {
	var obj = $('#'+elementid+'-dropdown > .gcbo-loader');
	if(obj && obj.length>0) {
		if(state==1) {
			$(obj).css('padding-top',$(obj).outerHeight()/2-16);
			$(obj).show();
		} else {
			$(obj).hide();
		}//if(state==1)
	}//if(obj && obj.length>0)
}//END function GCBOLoader

function GCBODDBtnClick(elementid,open) {
	if($('#'+elementid).attr('disabled')) { return false; }
	var obj = $('#'+elementid+'-dropdown');
	var cbo = $('#'+elementid+'-cbo');
	var act = open===0 ? false : (open===1 ? true : ($(obj).css('display')=='none'));
	if(act) {
		var lwidth = $(obj).width();
		var cwidth = $(cbo).outerWidth();
		var loffset = $(cbo).position();
		var ltop = loffset.top + $(cbo).outerHeight();
		var lleft = loffset.left;
		if((lleft+lwidth)>window.innerWidth && window.innerWidth>lwidth) { lleft = Math.max(0,(lleft - (lwidth - cwidth))); }
		$(obj).css('top',ltop+'px');
		$(obj).css('left',lleft+'px');
		if($(obj).attr('data-reload')==1 || $(cbo).val()!=$('#'+elementid).attr('data-text')) {
			var lcmd = $(cbo).attr('data-ajax');
			if(lcmd && lcmd.length>0) {
				$('#'+elementid+'-gcbo-target').html('');
				$(obj).show();
				lcmd = GibberishAES.dec(lcmd,elementid);
				eval(lcmd);
				if($(cbo).val()=='') { $(cbo).val($('#'+elementid).attr('data-text')); }
			}//if(lcmd && lcmd.length>0)
		}//if($(obj).attr('data-reload')==1 || $(cbo).val()!=$('#'+elementid).attr('data-text'))
		if($(obj).css('display')=='none') { $(obj).show(); }
	} else {
		if($(cbo).val()!=$('#'+elementid).attr('data-text')) {
			var otxt = $('#'+elementid).attr('data-text');
			if(otxt && otxt.length) { $(cbo).val(otxt); }
			else { $(cbo).val(''); }
		}//if($(cbo).val()!=$('#'+elementid).attr('data-text'))
		$(obj).hide();
	}//if(act)
}//END function GCBODDBtnClick

function GCBOSetValue(elementid,val,title,btnclick) {
	if($('#'+elementid).attr('disabled')) { return false; }
	var clear = false;
	var obj = $('#'+elementid);
	var cbo = $('#'+elementid+'-cbo');
	var oval = $(obj).val();
	if(val==null) {
		var emptyval = $(obj).attr('data-eval');
		val = (emptyval && emptyval.length) ? emptyval : '';
		clear = true;
	}//if(val==null)
	$(obj).val(val);
	$('#'+elementid+'-dropdown').attr('data-reload',1);
	$(cbo).val(title);
	$(cbo).attr('data-value',val);
	$(obj).attr('data-text',title);
	$('#'+elementid+'-dropdown .gcbo-selector').each(function(e) { if($(this).val()==1) { $(this).val('0'); } });
	if(val && val!='') {
		$('#'+elementid+'-dropdown #'+elementid+'-'+val+'.gcbo-selector').val('1');
	}//if(val && val!='')
	if(btnclick==true || btnclick==1) { CBODDBtnClick(elementid); }
	else if(clear) { $('#'+elementid+'-dropdown').hide(); }
	var onchange = $('#'+elementid).attr('data-onchange');
	if(onchange && onchange.length>0) { eval(onchange); }
}//END function GCBOSetValue

function TCBOSetValue(elementid,val,title,update_tree) {
	if($('#'+elementid).attr('disabled')) { return false; }
	var oval = $('#'+elementid).val();
	$('#'+elementid).val(val);
	var obj = $('#'+elementid+'-cbo');
	$(obj).val(title);
	$(obj).attr('data-value',val);
	if(update_tree==true || update_tree==1) {
		var tree = $('#'+elementid+'-ctree').fancytree('getTree');
		var node = tree.getNodeByKey(oval);
		if(node!=null) { node.setSelected(false); }
	}//if(update_tree==true || update_tree==1)
	var onchange = $('#'+elementid).attr('data-onchange');
	if(onchange && onchange.length>0) { eval(onchange); }
}//END function TCBOClear

function InitTCBOFancyTree(elementid,val,module,method,url_params,namespace,uid,encrypt,hide_parents_checkbox,icon) {
	if(!elementid || elementid.length===0) { return; }
	var lval = encodeURIComponent(val);
	var aurl = xAppWebLink+'/aindex.php?namespace='+namespace;
	var luid = '';
	var lparams = hide_parents_checkbox ? '&hpc=1' : '';
	if(uid || uid.length>0) { luid += '&uid='+uid; }
	var paramsString = '';
	if(typeof(url_params)==='object') {
	    for(var pk in url_params) { paramsString += '&' + pk + '=' + url_params[pk]; }
	} else if(typeof(url_params)==='string') {
	    paramsString = url_params;
	}//if(typeof(url_params)==='object')
	if(encrypt===1 || encrypt===true) {
		aurl += '&arhash='+encodeURIComponent(GibberishAES.enc('module='+module+'&method='+method+paramsString+lparams+luid+'&phash='+window.name,'xJS'));
	} else {
		aurl += '&module='+module+'&method='+method+paramsString+lparams+luid+'&phash='+window.name;
	}//if(encrypt===1 || encrypt===true)
	$('#'+elementid+'-ctree').fancytree({
		checkbox: true,
        icon: icon||false,
		selectMode: 1,
		clickFolderMode: 1,
		debugLevel: 0,
		source: {
			url: aurl+'&type=json&tree=1&val='+lval,
		},
		lazyLoad: function(event,data) {
			data.result = {
				url: aurl+'&type=json&tree=1&val='+lval,
				data: { key: data.node.key },
			};
		},
		createNode: function(event,data) {
		    if(!data.node.data.hasSelectedChild) { return false; }
			$.ajax({
				url: aurl+'&type=json&tree=1&val='+lval,
				data: { key: data.node.key },
				dataType: 'json',
				success: function(response) { data.node.addChildren(response); }
			});
		},
		select: function(event,data) {
			if(data.node.isSelected()) {
				TCBOSetValue(elementid,data.node.key,data.node.title,false);
				CBODDBtnClick(elementid);
			} else {
				TCBOSetValue(elementid,'','',false);
			}//if(data.node.isSelected())
        }
	});
}//END function InitTCBOFancyTree

function InitFancyTree(elementid,module,method,url_params,namespace,uid,encrypt,hide_parents_checkbox,icon) {
	if(!elementid || elementid.length===0) { return; }
	var aurl = xAppWebLink+'/aindex.php?namespace='+namespace;
	var luid = '';
	var lparams = hide_parents_checkbox ? '&hpc=1' : '';
	if(uid || uid.length>0) { luid += '&uid='+uid; }
	var paramsString = '';
	if(typeof(url_params)==='object') {
	    for(var pk in url_params) { paramsString += '&' + pk + '=' + url_params[pk]; }
	} else if(typeof(url_params)==='string') {
	    paramsString = url_params;
	}//if(typeof(url_params)==='object')
	if(encrypt===1 || encrypt===true) {
		aurl += '&arhash='+encodeURIComponent(GibberishAES.enc('module='+module+'&method='+method+paramsString+lparams+luid+'&phash='+window.name,'xJS'));
	} else {
		aurl += '&module='+module+'&method='+method+paramsString+lparams+luid+'&phash='+window.name;
	}//if(encrypt===1 || encrypt===true)
	$('#'+elementid+'-ctree').fancytree({
		checkbox: true,
        icon: icon||false,
		selectMode: 1,
		clickFolderMode: 1,
		debugLevel: 0,
		source: {
			url: aurl+'&type=json&tree=1',
		},
		lazyLoad: function(event,data) {
			data.result = {
				url: aurl+'&type=json&tree=1',
				data: { key: data.node.key },
			};
		},
		createNode: function(event,data) {
		    if(!data.node.data.hasSelectedChild) { return false; }
			$.ajax({
				url: aurl+'&type=json&tree=1',
				data: { key: data.node.key },
				dataType: 'json',
				success: function(response) { data.node.addChildren(response); }
			});
		}
	});
}//END function InitFancyTree

$(document).on('keydown','input.clsGridComboBox[type=text]',function(e) {
	if($('#'+$(this).attr('data-id')).attr('disabled')) { e.preventDefault(); return false; }
    if(e.keyCode==13) { //Enter
    	var elementid = $(this).attr('data-id');
    	$('#'+elementid+'-dropdown').attr('data-reload',1);
    	GCBODDBtnClick(elementid,1);
    } else if(e.keyCode==27) { //Escape
    	e.preventDefault();
    	GCBODDBtnClick($(this).attr('data-id'),0);
    } else if((e.altKey && (e.keyCode==40 || e.keyCode==98)) || e.keyCode==115) { //Alt+Down/Alt+Numpad2/F4
    	GCBODDBtnClick($(this).attr('data-id'),null);
    }//if(e.keyCode==13)
});//$(document).on('keydown','input.clsGridComboBox[type=text]',function(e)

$(document).on('keydown','input.clsTreeComboBox[type=text]',function(e) {
	if($('#'+$(this).attr('data-id')).attr('disabled')) { e.preventDefault(); return false; }
    if(e.keyCode==13 || (e.altKey && (e.keyCode==40 || e.keyCode==98)) || e.keyCode==115) { //Enter/Alt+Down/Alt+Numpad2/F4
    	CBODDBtnClick( $(this).attr('data-id'));
    } else if(e.keyCode==27) { //Escape
    	e.preventDefault();
    	$('#'+$(this).attr('data-id')+'-dropdown').hide();
    }//if(e.keyCode==13)
});//$(document).on('keydown','input.clsTreeComboBox[type=text]',function(e)
/*** END For ComboBox ***/

/*** For Select2 and SmartComboBox ***/
function SmartCBOInitialize() {
	// console.log('SmartCBOInitialize>>');
	$('select.SmartCBO').each(function(i,obj){
		if(!$(obj).hasClass("select2-hidden-accessible")) {
			var tagid = $(obj).attr('id');
			var params_str = $(obj).attr('data-smartcbo');
			if(tagid && params_str) {
				var oparams = eval(GibberishAES.dec(decodeURIComponent(params_str),tagid));
				// console.log('oparams:');
				// console.log(oparams);
				$(obj).select2(oparams);
			}//if(tagid && params_str)
    	}//if(!$(obj).hasClass("select2-hidden-accessible"))
	});//$('select.SmartCBO').each(function(i,obj)
}//END function SmartCBOInitialize

function GetSmartCBOValue(element,asObject) {
	if(!element) { return null; }
	var lelement = element;
	if(typeof(element)!='object') { lelement = $('#'+element); }
	if(!$(lelement).length) { return null; }
	var lval = $(lelement).val() || '';
	if(typeof(lval)!='object' || asObject==true || asObject==1) { return lval; }
	lval = lval.join(',');
	return lval;
}//END function GetSmartCBOValue

function GetSmartCBOText(element,asObject) {
	if(!element) { return null; }
	var lelement = element;
	if(typeof(element)!='object') { lelement = $('#'+element); }
	if(!$(lelement).length) { return null; }
	var ltext = $(lelement).select2('data');
	if(typeof(ltext)!='object') { return null; }
	var lval = null;
	for(var i=0;i<ltext.length;i++) {
		if(asObject==true || asObject==1) {
			if(lval==null) { lval = {}; }
			lval[i] = ltext[i].name;
		} else {
			lval = (lval==null ? '' : lval+',') + ltext[i].name;
		}//if(asObject==true || asObject==1)
	}//END for
	return lval;
}//END function GetSmartCBOText
/**
 * @return {boolean}
 */
function SetSmartCBOValue(element,new_val) {
	if(!element) { return false; }
	var lelement = element;
	if(typeof(element)!=='object') { lelement = $('#'+element); }
	if(!$(lelement).length) { return false; }
	if(typeof(new_val)==='object') {
	    var sOption = new Option(new_val.id, new_val.name, true, true);
	    $(lelement).append(sOption).trigger('change');
	} else {
	    $(lelement).val(new_val).trigger('change');
	}//if(typeof(new_val)==='object')
	return true;
}//function SetSmartCBOValue

function GetSelect2Val(element,org) {
	if(!element) { return undefined; }
	var lelement = element;
	if(typeof(element)!=='object') { lelement = $('#'+element); }
	var lval = $(lelement).val() || '';
	if(org!==true && org!==1 && typeof(lval)==='object') { lval = lval.join(','); }
	return lval;
}//function GetSelect2Val
/*** END For Select2 and SmartComboBox ***/

/*** For KVList ***/
$(document).on('click','div.MainKVL > .KVLAddBtn',function(e) { KVLAddElement(this); });
$(document).on('click','div.MainKVL > ul.KVLList > li > .KVLIDelBtn',function(e) { KVLRemoveElement(this); });
$(document).on('keydown','div.MainKVL > input[type=text].KVLNewKey',function(e) { if(e.keyCode==13) { KVLAddElement(this); } });

function KVLAddElement(obj) {
	var parent = $(obj).parent();
	var vitem = $(parent).children('input[type=text].KVLNewKey').first();
	var ival = vitem.val();
	var iname = vitem.data('name');
	if(!iname) { iname = $(parent).attr('name'); }
	if(ival && ival.length) {
		$(parent).find('ul.KVLList span.KVLBlank').addClass('hidden');
		$(parent).children('ul.KVLList').first().append('<li><label class="KVLILabel">'+ival+'</label><input type="text" class="KVLIValue postable" name="'+iname+'['+ival+']" placeholder="[value]" value=""><button class="KVLIDelBtn"><i class="fa fa-minus-circle"></i></button></li>');
		$(vitem).val('');
	}//if(ival && ival.length)
}//END function KVLAddElement

function KVLRemoveElement(obj) {
	var parent = $(obj).parent();
	if(parent) {
		var ulparent = $(parent).parent();
		$(parent).remove();
		if(ulparent && $(ulparent).find('li').length<=1) {
			$(ulparent).find('span.KVLBlank').removeClass('hidden');
		}//if(ulparent && $(ulparent).find('li').length<=1)
	}//if(parent)
}//END function KVLRemoveElement
/*** END For KVList ***/

/*** For Actions ***/
function BindShortcuts(saveCtrl,cancelCtrl){
	var kpressfct= function(e){
		if(e.keyCode==13 && $('input:focus[type=text],textarea:focus').length==0)
			$('#'+saveCtrl).click();
		else if(e.keyCode==27){
			e.preventDefault();
			$('#'+cancelCtrl).click();
		}//if(e.keyCode==13 && $('input:focus[type=text],textarea:focus').length==0)
	};//var kpressfct= function(e)
	$(document).on('keypress',kpressfct);
}//END function BindShortcuts

$(document).on('keydown','.clsOnEnterAction',function(e) {
    if(e.keyCode==13){
    	var lact = $(this).attr('data-onenter');
    	if(lact) { eval(lact); }
    }//if(e.keyCode==13)
});//$(document).on('keydown','.clsOnEnterAction',function(e)

$(document).on('keydown','.clsOnEnterActionButton',function(e) {
    if(e.keyCode==13){
    	var lid = $(this).attr('data-onenterbtn');
    	if(lid) {
    		$(this).trigger('focusout');
    		$('#'+lid).click();
    	}//if(lid)
    }//if(e.keyCode==13)
});//$(document).on('keydown','.clsOnEnterActionButton',function(e)

function AddClassOnErrorByParent(parentid,reset,errclass) {
	var lclass = errclass ? errclass : 'clsFieldError';
	if(reset) {
		$('#'+parentid+' .clsRequiredField').removeClass(lclass);
	} else {
		$('#'+parentid+' .clsRequiredField').addClass(lclass);
	}//if(reset)
}//END function AddClassOnErrorByParent

function AddClassOnError(elementid,reset,errclass) {
	var lclass = errclass ? errclass : 'clsFieldError';
	if(reset) {
		$('#'+elementid).removeClass(lclass);
	} else {
		$('#'+elementid).addClass(lclass);
	}//if(reset)
}//function AddClassOnError
/*** END For Actions ***/

/*** For Validations ***/
function CheckIfEnter(e){ //e is event object passed from function invocation
	var characterCode;//  literal character code will be stored in this variable
	 if(e && e.which){ //if which property of event object is supported (NN4)
	 	characterCode = e.which; //character code is contained in NN4's which property
	 }else{
		e = event;
	 	characterCode = e.keyCode; //character code is contained in IE's keyCode property
	 }//if(e && e.which)
	 return characterCode == 13 ? true : false;
}//END function CheckIfEnter

$(document).on('keydown','.clsSetNoNumericValidation',function(e) {
	var key = e.which || e.keyCode || e.charCode || 0;
	if((key>=48 && key<=57)	|| (key>=96 && key<=105)) { e.preventDefault(); }
});//$(document).on('keydown','.clsSetNoNumericValidation',function(e)

$(document).on('keydown','.clsSetNumericValidation',function(e) {
	var key = e.which || e.keyCode || e.charCode || 0;
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
				])!==-1
			|| (!e.shiftKey && key>=96 && key<=105)		//numeric pad keys
			|| (!e.shiftKey && key>=48 && key<=57)			//numeric keys
			|| (!e.shiftKey && (key==189 || key==109)
				&& this.selectionStart==0 && this.value.split('-').length<2)
		) { return; }
	var nformat = $(this).attr('data-format');
	var ds = false;
	if(nformat) {
		var farr = nformat.split('|');
		if(farr[0]!==0) { ds = farr[1]; }
	}//if(nformat)
	if(!e.shiftKey && key==188 && ds==',' && this.value.indexOf(ds)==-1) { return; }
	if(!e.shiftKey && (key==190 || key==110) && ds=='.' && this.value.indexOf(ds)==-1) { return; }
	//alert(e.ctrlKey+'|'+e.altKey+'|'+e.shiftKey+'|'+key);
	e.preventDefault();
});//$(document).on('keydown','.clsSetNumericValidation',function(e)

$(document).on('keydown','.clsSetPhoneValidation',function(e) {
	var key = e.which || e.keyCode || e.charCode || 0;
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
				])!==-1
			|| (!e.shiftKey && $.inArray(key,[
				190,	//.
				110,	//. (numpad)
				189,	//-
				109,	//- (numpad)
				]!==-1))
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
	var lval = $(this).val().ucfirst(false);
	$(this).val(lval);
});//$(document).on('focusout','.clsSetUcFirst',function(e)

$(document).on('focusout','.clsSetUcFirstAll',function(e) {
	var lval = $(this).val().ucfirst(true);
	$(this).val(lval);
});//$(document).on('focusout','.clsSetUcFirstAll',function(e)
/*** END For text inputs ***/

function AnimatedHide(elementid,val,speed) {
	if($('#'+elementid).length>0) {
		var lspeed = speed ? speed : 600;
		if(val==1) {
			$('#'+elementid).hide(lspeed);
		} else {
			$('#'+elementid).show(lspeed);
		}//if(val==1)
	}//if($('#'+elementid).length > 0)
}//END function AnimatedHide

function AnimatedHideWithSave(elementid,valueid,speed) {
	if($('#'+elementid).length>0) {
		var lspeed = speed ? speed : 600;
		if($('#'+valueid).val()==1) {
			$('#'+elementid).hide(lspeed);
		} else {
			$('#'+elementid).show(lspeed);
		}//if($('#'+valueid).val()==1)
		if(typeof(Storage)!=='undefined') {
			localStorage.setItem(valueid,$('#'+valueid).val()+'|'+elementid);
		} else {
			var expdate = new Date();
			expdate.setMonth(expdate.getMonth() + 6);
			document.cookie = valueid+'='+$('#'+valueid).val()+'|'+elementid
				+'; expires='+expdate.toGMTString()+'; path=/; domain='+location.host+';';
		}//if(typeof(Storage)!=='undefined')
	}//if($('#'+elementid).length > 0)
}//END function AnimatedHideWithSave

/*** For CKEditor ***/
function CreateCkEditor(phash,e,multi,econfig,ewidth,eheight) {
	if(multi) {
		var es = e.split(',');
		for(var i=0;i<es.length;i++) { CreateCkEditor(phash,es[i],false,econfig,ewidth,eheight); }
	} else {
		if(!e || e.length==0) { return; }
		// if(!phash) { phash = window.name.length>0 ? window.name : '_xbasepage_'; }
		// console.log('CreateCkEditor: ' + e + ' // ' + phash);
		var ckei = window.ckei_list;
		if(ckei==undefined || ckei==null || typeof(ckei)!='object') { ckei = new Array(); }
		var newconfig = typeof(econfig)=='object' ? econfig : new Object();
		if(ewidth) { newconfig.width = ewidth; }
		if(eheight) { newconfig.height = eheight; }
		CKEDITOR.replace(e,newconfig);
		if($.inArray(e,ckei)==-1) { ckei.push(e); }
		window.ckei_list = ckei;
	}//if(multi)
}//function CreateCkEditor(e,multi)

function DestroyCkEditors(phash,target) {
	if(!target || target.length==0) { return; }
	var targetObj = $('#'+target);
	if(!targetObj) { return; }
	// if(!phash) { phash = window.name.length>0 ? window.name : '_xbasepage_'; }
	// console.log('DestroyCkEditors: ' + target + ' // ' + phash);
	var ckei = window.ckei_list;
	if(ckei==undefined || ckei==null || typeof(ckei)!='object' || ckei.length==0) { return; }
	var newCkei = new Array();
	for(var i=0;i<ckei.length;i++) {
		var dropped = false;
		targetObj.find('#'+ckei[i]).each(function() {
			// console.log('Drop: ' + ckei[i]);
			var editor = CKEDITOR.instances[ckei[i]];
			if(editor) { editor.destroy(true); }
			dropped = true;
		});
		if(!dropped) {
			// console.log('Skip: ' + ckei[i]);
			newCkei.push(ckei[i]);
		}//if(!dropped)
	}//END for
	window.ckei_list = newCkei;
}//function DestroyCkEditors

function DestroyCkEditor(phash,e,multi) {
	// if(!phash) { phash = window.name.length>0 ? window.name : '_xbasepage_'; }
	// console.log('DestroyCkEditor!: ' + e + ' // ' + phash);
	if(e) {
		if(multi) {
			var es = e.split(',');
			for(var i=0;i<es.length;i++) { DestroyCkEditor(phash,es[i],false); }
		} else {
			var ckei = window.ckei_list;
			var editor = CKEDITOR.instances[e];
    		if(editor) { editor.destroy(true); }
			if(ckei==undefined || ckei==null || typeof(ckei)!='object' || $.inArray(e,ckei)==-1) { return; }
			ckei.splice(ckei.indexOf(e),1);
			window.ckei_list = ckei;
		}//if(typeof e=='array')
	} else {
		var ckei = window.ckei_list;
		if(ckei==undefined || ckei==null || typeof(ckei)!='object' || ckei.length==0) { return; }
		for(var i=0;i<ckei.length;i++) {
			var editor = CKEDITOR.instances[ckei[i]];
			if(editor) { editor.destroy(true); }
		}//END for
		window.ckei_list = new Array();
	}//if(e)
}//function DestroyCkEditor
/**
 * @return {string}
 */
function GetCkEditorData(e) {
	if(typeof(e)==='object') { e = e.getAttribute('id'); }
	var editor = CKEDITOR.instances[e];
    if(editor) { return editor.getData(); }
    return '';
}//function GetCkEditorData
/*** END For CKEditor ***/
/*** For FileUploader ***/
function CreateFileUploader(elementid,multi) {
	if(multi===1) {
		if(elementid) {
			$('#'+elementid+' .clsFileUploader').each(function(index) { CreateFileUploader(this,0); });
		} else {
			$('.clsFileUploader').each(function(index) { CreateFileUploader(this,0); });
		}//if(elementid)
	} else if(elementid) {
	    var element = null;
		if(typeof(elementid)==='object') {
			element = $(elementid);
		} else {
			element = $('#'+elementid);
		}//if(typeof(elementid)=='object')
		var droparea = false;
		if($(element).parent().hasClass('clsDropArea')) {
			$(document).on('drop dragover',function(e) { e.preventDefault(); });
			droparea = $(element).parent();
		}//if($(element).parent().hasClass('clsDropArea'))
		$(element).fileupload({
	        dataType: 'json',
	        dropZone: droparea,
	        formData: [
	        	{ name: 'targetdir', value: $(element).attr('data-targetdir') },
	        	{ name: 'subfolder', value: $(element).attr('data-subfolder') }
	        ],
	        start: function(e) {
				var statusid = $(element).attr('data-statusid');
				if(statusid && $('#'+statusid).length) {
					$('#'+statusid).css('display','');
				}//if(statusid && $('#'+statusid).length)
	        },//start: function(e)
	        done: function(e,data) {
	        	var statusid = $(element).attr('data-statusid');
				if(statusid && $('#'+statusid).length) {
					$('#'+statusid).css('display','none');
				}//if(statusid && $('#'+statusid).length)
            	if(data.result.files[0].error && data.result.files[0].error!='') {
            		ShowErrorDialog('Upload failed: '+data.result.files[0].error);
            	} else {
		            var callbackfunc = $(element).attr('data-callback');
		            if(callbackfunc) {
		            	callbackfunc = GibberishAES.dec(decodeURIComponent(callbackfunc),'HTML');
						if(callbackfunc instanceof Function) {
							callbackfunc(data.files[0].name,this);
						} else {
							callbackfunc = callbackfunc.replace('&amp;namespace=','&namespace=');
							callbackfunc = callbackfunc.replaceAll('#uploadedfile#',data.files[0].name);
							eval(callbackfunc);
						}//if(callbackfunc instanceof Function)
					}//if(callbackfunc)
            	}//if(data.files[0].error && data.files[0].error!='')
	        },//done: function(e,data)
	        fail: function(e,data) {
	        	ShowErrorDialog('Upload failed: '+data.errorThrown+' (Status: '+data.textStatus+')');
	        }//fail: function(e,data)
		});//$(element).fileupload
	} else {
		return false;
	}//if(multi==1)
}//END function CreateFileUploader
/*** END For FileUploader ***/
/*** For TreeGrid ***/
function TreeGridViewAction(obj,pid,tableid,cval,orgid) {
	if(!orgid) { orgid = pid; }
	if(cval!=0 && cval!=1) { cval = $(obj).val()==1 ? 0 : 1; }
	if(orgid==pid) { $(obj).val(cval); }
	$('table#'+tableid+' > tbody > tr.clsTreeGridChildOf'+pid).each(function(i) {
		if(cval==1) { $(this).show(); } else { $(this).hide(); }
		obj = $(this).find('input.clsTreeGridBtn').first();
		if(typeof(obj)=='object') {
			pid = $(this).attr('data-id');
			if(pid) { TreeGridViewAction(obj,pid,tableid,(cval==1 ? $(obj).val() : 0),orgid); }
		}//if(typeof(obj)=='object')
	});//$('table#'+tableid+' > tbody > tr.clsTreeGridChildOf'+pid).each(function(i)
}//END function TreeGridViewAction
/*** END For TreeGrid ***/

/*** For Dynamic Forms ***/
function RepeatControl(obj,tagid) {
	if(!obj || !tagid) { return false; }
	var $ltag = $(obj).parent().find("[data-tid='"+tagid+"']").last();
	if($ltag.length<=0) { return false; }
	var lindex = Number($ltag.attr('data-ti'));
	var lntagid = tagid+'-'+(lindex+1);
	var $lntag = $ltag.clone();
	// console.log($lntag);
	if($lntag.hasClass('clsSubForm')) {
		$lntag.find('.postable').each(function() {
			var peid = $(this).attr('id');
			if(peid) {
				var penid = peid.substring(0,peid.lastIndexOf('-'))+'-'+(lindex+1);
				$(this).attr('id',penid);
				$(this).val('');
			}//if(peid)
		});
	}//if($lntag.hasClass('clsSubForm'))
	$lntag.attr('data-ti',lindex+1);
	$lntag.attr('id',lntagid);
	$lntag.val('');
	$lntag.addClass('ctrl-clone');
	$lntag.insertBefore($(obj));
	var lract = $(obj).attr('data-ract');
	$('<button class="clsRepeatableCtrlBtn remove-ctrl-btn" onclick="RemoveRepeatableControl(this,\''+lntagid+'\')"><i class="fa fa-minus-circle" aria-hidden="true"></i>'+(lract?lract:'')+'</button>').insertBefore($(obj));
}//END function RepeatControl

function RemoveRepeatableControl(obj,elementid) {
	if(!elementid) { return false; }
	$('#'+elementid).remove();
	$(obj).remove();
}//END function RemoveRepeatableControl
/*** END For Dynamic Forms ***/