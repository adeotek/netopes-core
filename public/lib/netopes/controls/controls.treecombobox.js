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
            uid: '',
            encrypt: false,
            hideParentsCheckbox: false,
            useIcons: false
        };
        if(typeof options==='object') { $.extend(config,options); }

        let methods={};

        function init(obj) {
            console.log('Init...');
            console.log(obj);

            obj.tagId=$(obj).attr('id');
            obj.selectedValue=encodeURIComponent(config.value);
            obj.ajaxUrl=config.ajaxUrl;
            obj.ajaxUrlParams='&module=' + config.module + '&method=' + config.method;
            if(config.hideParentsCheckbox) { obj.ajaxUrlParams+='&hpc=1'; }
            if(config.uid || config.uid.length>0) { obj.ajaxUrlParams+='&uid=' + config.uid; }
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
                // console.log('URL: '+this.ajaxUrl+'&response_type=json&tree=1&val='+obj.selectedValue);
                return this.ajaxUrl + '&response_type=json&tree=1&val=' + obj.selectedValue;
            };

            $('#' + obj.tagId + '-ctree').fancytree({
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
                    // },
                    // select: function(event,data) {
                    //     if(data.node.isSelected()) {
                    //         TreeCBOSetValue(elementid,data.node.key,data.node.title,false);
                    //         CBODDBtnClick(elementid);
                    //     } else {
                    //         TreeCBOSetValue(elementid,'','',false);
                    //     }//if(data.node.isSelected())
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
    };//END $.fn.NetopesTabs
})(jQuery);