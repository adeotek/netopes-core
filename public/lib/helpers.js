$.fn.andSelf = function() { return this.addBack.apply(this, arguments); }

String.prototype.ucfirst = function(all) {
	if(this=='') { return this; }
	if(all) {
		// Split the string into words if string contains multiple words.
		var x = this.split(/\s+/g);
		for (var i = 0; i < x.length; i++) {
			// Splits the word into two parts. One part being the first letter,
			// second being the rest of the word.
			var parts = x[i].match(/(\w)(\w*)/);
			// Put it back together but uppercase the first letter and
			// lowercase the rest of the word.
			if(parts[1]) { x[i] = parts[1].toUpperCase() + parts[2]; }
		}//for (var i = 0; i < x.length; i++)
		// Rejoin the string and return.
		return x.join(' ');
	} else {
		var x = this;
		// Splits the word into two parts. One part being the first letter,
		// second being the rest of the word.
		var parts = x.match(/(\w)(\w*)/);
		// Put it back together but uppercase the first letter and
		// lowercase the rest of the word.
		if(parts[1]) { x = parts[1].toUpperCase() + parts[2]; }
		// Rejoin the string and return.
		return x;
	}//if(all)
};//String.prototype.ucfirst = function(all = false)

if(typeof(Storage)!=='undefined') {
	Storage.prototype.setObject = function(key,value) {
		if(!key || !value || typeof value!=="object") { return false; }
	    this.setItem(key,JSON.stringify(value));
	};//Storage.prototype.setObject = function(key,value)

	Storage.prototype.getObject = function(key) {
	    return JSON.parse(this.getItem(key));
	};//Storage.prototype.getObject = function(key)
}//if(typeof(Storage)!=='undefined')

//jquery.text-overflow
(function($) {
	$.fn.ellipsis = function(enableUpdating){
		var s = document.documentElement.style;
		if (!('textOverflow' in s || 'OTextOverflow' in s)) {
			return this.each(function(){
				var el = $(this);
				if(el.css("overflow") == "hidden"){
					var originalText = el.html();
					var w = el.width();

					var t = $(this.cloneNode(true)).hide().css({
                        'position': 'absolute',
                        'width': 'auto',
                        'overflow': 'visible',
                        'max-width': 'inherit'
                    });
					el.after(t);

					var text = originalText;
					while(text.length > 0 && t.width() > el.width()){
						text = text.substr(0, text.length - 1);
						t.html(text + "...");
					}
					el.html(t.html());

					t.remove();

					if(enableUpdating == true){
						var oldW = el.width();
						setInterval(function(){
							if(el.width() != oldW){
								oldW = el.width();
								el.html(originalText);
								el.ellipsis();
							}
						}, 200);
					}
				}
			});
		} else return this;
	};
})(jQuery);

function is_numeric(input) {
    return ((input - 0) == input && (''+input).trim().length > 0);
}//END function is_numeric

function strpos(haystack,needle,offset) {
	var i = (haystack+'').indexOf(needle,(offset || 0));
	return (i===-1 ? false : i);
}//function strpos(haystack,needle,offset)

function SetStorageParam(key,value) {
	if(typeof(Storage)!=='undefined') {
		localStorage.setItem(key,value);
	} else {
		var expdate = new Date();
		expdate.setMonth(expdate.getMonth() + 6);
		document.cookie = '|'+key+'='+escape(value)+']'+key+'|; expires='+expdate.toGMTString()+'; path=/; domain='+location.host+';';
	}//if(typeof(Storage)!=='undefined')
}//END function SetStorageParam

function GetStorageParam(key) {
	if(typeof(Storage)!=='undefined') {
		var result = localStorage.getItem(key);
	} else {
		if(document.cookie.length>0 && document.cookie.indexOf('|'+key+'=')!=(-1)) {
			var result = unescape(document.cookie.substring(document.cookie.indexOf('|'+key+'=')+key.length+2,document.cookie.indexOf(']'+key+'|')));
		} else {
			var result = undefined;
		}//if(document.cookie.length>0 && document.cookie.indexOf('|'+key+'=')!=(-1))
	}//if(typeof(Storage)!=='undefined')
	return result;
}//END function GetStorageParam

function CookiesAccept(elementid) {
	if(!elementid) { return; }
	var lac = false;
	if(typeof(Storage)!=='undefined') {
		var lacv = localStorage.getItem('__xUSRCA');
		lac = lacv!=1 && lacv!='1';
	} else {
		lac = !(document.cookie.length>0 && document.cookie.indexOf('__xUSRCA=1')!=(-1));
	}//if(typeof(Storage)!=='undefined')
	if(lac) { $('#'+elementid).show(); }
}//END CookiesAccept

function SetCookiesAccept() {
	var expdate = new Date();
	expdate.setMonth(expdate.getMonth() + 12);
	var cdomain = location.host.toLowerCase();
	if(typeof(Storage)!=='undefined') {
		localStorage.setItem('__xUSRCA',1);
	} else {
		document.cookie = '__xUSRCA=1; expires='+expdate.toGMTString()+'; path=/; domain='+cdomain+';';
	}//if(typeof(Storage)!=='undefined')
}//END SetCookiesAccept

function DumpObjectElements(obj,arrresult,noinherited,nomethods,noproperties) {
	var result = null;
	var properties = [];
	var methods = [];
	for(var e in obj) {
		if(!noinherited || obj.hasOwnProperty(e)) {
			switch(typeof obj[e]) {
				case 'function':
					methods.push(e);
					break;
				default:
					properties.push(e);
					break;
			}//switch(typeof obj[e])
		}//if(!noinherited || obj.hasOwnProperty(e))
	}//for(var e in obj)
	if(arrresult) {
		result = [];
		if(!noproperties) {
			result['properties'] = properties;
		}//if(!noproperties)
		if(!nomethods) {
			result['methods'] = methods;
		}//if(!nomethods)
	} else {
		result = '';
		if(!noproperties) {
			result += 'Properties:\n\t'+properties.join(",\n\t");
		}//if(!noproperties)
		if(!nomethods) {
			result += '\nMethods:\n\t'+methods.join(",\n\t");
		}//if(!nomethods)
	}//if(!arrresult)
	return result;
}//function DumpObjectElements(obj,arrresult,noinherited,nomethods,noproperties)

function print_r(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;
	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";
	if(typeof(arr) == 'object') { //Array/Hashes/Objects
	    for(var item in arr) {
	        var value = arr[item];
	        if(typeof(value) == 'object') { //If it is an array,
	            dumped_text += level_padding + "'" + item + "' ...\n";
	            dumped_text += print_r(value,level+1);
	        } else {
	            dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
	        }
	    }
	} else { //Stings/Chars/Numbers etc.
	    dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}//function print_r(arr,level)