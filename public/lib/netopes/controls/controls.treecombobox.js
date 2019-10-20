(function($) {
    'use strict';
    $.fn.NetopesTreeComboBox=function(options) {
        // Plugin default options.
        let config={
            value: '',
            ajaxUrl: '',
            module: '',
            method: '',
            urlParams: '',
            jsParams: {},
            encrypt: false,
            hideParentsCheckbox: false,
            useIcons: false,
            onChange: false,
            disabled: false
        };
        if(typeof options==='object') { $.extend(config,options); }

        let clearValue=function(obj,btnClick) {
            if(!btnClick || !$(obj).prop('disabled')) {
                let oldValue=$(obj).val();
                $(obj).val('');
                $(obj).siblings('input[type="text"]').val('');
                let tree=$('#' + obj.id + '-ctree').fancytree('getTree');
                let node=tree.getNodeByKey(oldValue);
                if(node!=null) { node.setSelected(false); }
            }
        };

        let setValue=function(obj,val,title,updateTree,reload) {
            let oldValue=$(obj).val();
            $(obj).val(val);
            $(obj).siblings('input[type="text"]').val(title);
            if($(obj).prop('disabled')) {
                return false;
            }
            if(reload===true || reload===1 || reload==='1') {
                let tree=$('#' + obj.id + '-ctree').fancytree('getTree');
                tree.reload();
            } else if(updateTree===true || updateTree===1 || updateTree==='1') {
                let tree=$('#' + obj.id + '-ctree').fancytree('getTree');
                let node=tree.getNodeByKey(oldValue);
                if(node!=null) { node.setSelected(false); }
            }
            $(obj).trigger('change');
        };

        let dropdownBtnClick=function(obj) {
            if(!$(obj).prop('disabled')) {
                let dropdownObj=$(obj).siblings('div.ctrl-dropdown');
                let inputObj=$(obj).siblings('input[type="text"]');
                if($(dropdownObj).css('display')==='none') {
                    let ddWidth=$(dropdownObj).width();
                    let inputWidth=$(inputObj).outerWidth();
                    let positionOffset=$(inputObj).position();
                    let newTop=positionOffset.top + $(inputObj).outerHeight();
                    let newLeft=positionOffset.left;
                    if((newLeft + ddWidth)>window.innerWidth && window.innerWidth>ddWidth) { newLeft=Math.max(0,(newLeft - (ddWidth - inputWidth))); }
                    $(dropdownObj).css('top',newTop + 'px');
                    $(dropdownObj).css('left',newLeft + 'px');
                    $(dropdownObj).show();
                } else {
                    $(dropdownObj).hide();
                }
            }
        };

        let methods={
            disabled: function(obj,val,resetValue) {
                if(val===true || val===1 || val==='1') {
                    $(obj).prop('disabled','disabled');
                    $(obj).siblings('div.ctrl-dropdown').hide();
                    $(obj).siblings('input[type="text"]').removeClass('stdro');
                } else {
                    $(obj).prop('disabled',false);
                    $(obj).siblings('input[type="text"]').addClass('stdro');
                }
                if(resetValue===true || resetValue===1 || resetValue==='1') {
                    clearValue(obj,false);
                }
                return true;
            },
            clear: function(obj) {
                clearValue(obj,false);
            },
            setValue: function(obj,val,title,updateTree,reload) {
                setValue(obj,val,title,updateTree,reload);
            }
        };

        function init(obj) {
            $(obj).siblings('input[type="text"]').on('click',function() { dropdownBtnClick(obj); });
            $(obj).siblings('div.ctrl-dd-i-btn').on('click',function() { dropdownBtnClick(obj); });
            $(obj).siblings('div.ctrl-clear').on('click',function() { clearValue(obj,true); });
            if(config.disabled) {
                $(obj).prop('disabled','disabled');
                $(obj).removeClass('stdro');
                $(obj).siblings('div.ctrl-dropdown').hide();
            }
            if(typeof config.onChange==='string' && config.onChange.length>0) {
                $(obj).on('change',function() {
                    try {
                        eval(config.onChange);
                    } catch(e) {
                        console.log(e);
                        console.log(config.onChange);
                    }
                });
            }
            obj.ajaxUrl=config.ajaxUrl;
            obj.ajaxUrlParams='&module=' + config.module + '&method=' + config.method;
            if(config.hideParentsCheckbox) { obj.ajaxUrlParams+='&hpc=1'; }
            if(config.urlParams) { obj.ajaxUrlParams+=config.urlParams; }

            obj.urlCallback=function() {
                let paramsString='';
                if(typeof (config.jsParams)==='object') {
                    for(let pk in config.jsParams) {
                        let jspVal;
                        try {
                            jspVal=eval(config.jsParams[pk]);
                        } catch(er) {
                            console.log(er);
                            console.log(config.jsParams[pk]);
                            jspVal='';
                        }//try
                        paramsString+='&' + pk + '=' + jspVal;
                    }//for
                } else if(typeof (config.jsParams)==='string') {
                    paramsString=config.jsParams;
                }//if(typeof(js_params)==='object')
                if(config.encrypt===1 || config.encrypt===true) {
                    this.ajaxUrl+='&arhash=' + encodeURIComponent(GibberishAES.enc(obj.ajaxUrlParams + paramsString + '&phash=' + window.name,'xJS'));
                } else {
                    this.ajaxUrl+=obj.ajaxUrlParams + paramsString + '&phash=' + window.name;
                }//if(config.encrypt===1 || config.encrypt===true)
                // console.log('URL: '+this.ajaxUrl+'&response_type=json&tree=1&val='+encodeURIComponent($(obj).val()));
                return this.ajaxUrl + '&response_type=json&tree=1&val=' + encodeURIComponent($(obj).val());
            };

            $('#' + obj.id + '-ctree').fancytree({
                checkbox: true,
                icon: config.useIcons || false,
                selectMode: 1,
                clickFolderMode: 1,
                debugLevel: 0,
                source: function() {
                    let iUrl=obj.urlCallback();
                    return {
                        url: iUrl,
                        cache: false
                    };
                },
                lazyLoad: function(event,data) {
                    let iUrl=obj.urlCallback();
                    data.result={
                        url: iUrl,
                        data: {key: data.node.key},
                    };
                },
                createNode: function(event,data) {
                    if(!data.node.data.hasSelectedChild) { return false; }
                    let iUrl=obj.urlCallback();
                    $.ajax({
                        url: iUrl,
                        data: {key: data.node.key},
                        dataType: 'json',
                        success: function(response) { data.node.addChildren(response); }
                    });
                },
                select: function(event,data) {
                    if(data.node.isSelected()) {
                        setValue(obj,data.node.key,data.node.title,false,false);
                        dropdownBtnClick(obj);
                    } else {
                        setValue(obj,'','',false,false);
                    }//if(data.node.isSelected())
                }
            });

        }//END function init

        if(typeof options==='string') {
            if(methods[options]) {
                let methodArgs=Array.prototype.slice.call(arguments,1);
                methodArgs.unshift(this);
                return methods[options].apply(this,methodArgs);
            } else {
                console.log('Invalid or inaccessible method: [' + options + ']!');
            }
        } else {
            // Return jQuery object to maintain chainabillity.
            return this.each(function() { init(this); });
        }
    };//END $.fn.NetopesTreeComboBox
})(jQuery);