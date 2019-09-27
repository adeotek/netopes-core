(function($) {
    'use strict';

    $.fn.NetopesTabs=function(options) {
        // Plugin default options.
        let config={
            type: 'standard', // Available types: standard/accordion/vertical/vertical_floating/wizard
            class: null, // CSS class to be added to the main container
            onchange: null, // Function called after tab change
            defaultTab: 0 // Tab to be opened at initialization
        };
        if(typeof options==='object') { $.extend(config,options); }

        let nContainerClass='nac-tabs';
        let nActiveClass='nac-active';
        let nActionClass='nac-action';
        let nTabClass='nac-tab-item';
        let nContentClass='nac-tab-content';
        let nEffect={duration: 300,easing: 'swing'};

        let toggleContent=function(obj,id) {
            if(config.type==='accordion') {
                $(obj).find('div.' + nContentClass + '.' + nActiveClass).removeClass(nActiveClass).slideUp(nEffect);
                $(obj).children('div' + id).first().addClass(nActiveClass).slideDown(nEffect);
            } else {
                $(obj).find('div.' + nContentClass + '.' + nActiveClass).hide().removeClass(nActiveClass);
                $(obj).children('div' + id).first().addClass(nActiveClass).show();
            }//if(config.type==='accordion')
        };

        let reloadContent=function(obj,id) {
            let tab=$(obj).children('div' + id).first();
            let tabReload=parseInt($(tab).attr('data-reload'));
            if(isNaN(tabReload) || tabReload!==1) {
                return true;
            }
            let tabReloadAction=$(tab).attr('data-reload-action');
            if(typeof tabReloadAction==='string' && tabReloadAction.length>0) {
                try {
                    eval(tabReloadAction);
                } catch(e) {
                    console.log(e);
                    console.log(tabReloadAction);
                }//END try
            }
        };

        let tabClick=function(obj,actObj) {
            if($(actObj).hasClass(nActiveClass)) {
                return false;
            }
            let tabId='#';
            if(config.type==='accordion') {
                $(obj).find('h3.' + nActiveClass).removeClass(nActiveClass);
                $(actObj).addClass(nActiveClass);
                tabId+=$(actObj).attr('data-for');
            } else {
                $(obj).find('li.' + nTabClass + '.' + nActiveClass).removeClass(nActiveClass);
                $(actObj).parent().addClass(nActiveClass);
                tabId=$(actObj).attr('href');
            }//if(config.type==='accordion')
            reloadContent(obj,tabId);
            toggleContent(obj,tabId);
            if(typeof config.onchange==='function') {
                config.onchange($(actObj).index(),obj,actObj);
            }
        };

        let methods={
            tabChange: function(obj,index) {
                let pIndex=parseInt(index);
                if(config.type==='accordion') {
                    let tabs=$(obj).find('h3.' + nTabClass + '.nac-accordion-item');
                    if(isNaN(pIndex) || pIndex>tabs.length) {
                        console.log('Invalid tab index: [' + index + ']!');
                    } else {
                        tabClick(obj,tabs[pIndex]);
                    }
                } else {
                    let tabs=$(obj).find('ul > li.' + nTabClass);
                    if(isNaN(pIndex) || pIndex>tabs.length) {
                        console.log('Invalid tab index: [' + index + ']!');
                    } else {
                        tabClick(obj,$(tabs[pIndex]).children('a.' + nActionClass).first());
                    }
                }//if(config.type==='accordion')
            },
            tabChangeById: function(obj,id) {
                if(typeof id!=='string' || id.length===0) {
                    console.log('Invalid tab id: [' + id + ']!');
                    return false;
                }
                let aObj=false;
                if(config.type==='accordion') {
                    aObj=$(obj).find('h3.' + nActionClass + '.nac-accordion-item[data-for="' + id + '"]').first();
                } else {
                    aObj=$(obj).find('ul > li > a.' + nActionClass + '[href="#' + id + '"]').first();
                }//if(config.type==='accordion')
                if(!aObj || !aObj.length) {
                    console.log('Tab [' + id + '] not found!');
                    return false;
                }
                tabClick(obj,aObj);
            }
        };

        function init(obj) {
            let objClass=nContainerClass;
            let tabClass=nTabClass;
            let contentClass=nContentClass;

            if(config.type==='accordion') {
                objClass+=' nac-accordion-tabs';
                tabClass+=' ' + nActionClass + ' nac-accordion-item';

                $(obj).addClass(objClass);
                if(typeof (config.class)==='string' && config.class.length()>0) {
                    $(obj).addClass(config.class);
                }

                $(obj).children('h3').addClass(tabClass).on('click',function() { tabClick(obj,this); });
                $(obj).children('div').hide().addClass(contentClass);
            } else {
                switch(config.type) {
                    case 'vertical':
                        objClass+=' nac-vertical-tabs';
                        tabClass+=' nac-vertical';
                        break;
                    case 'vertical_floating':
                        objClass+=' nac-vertical-tabs nac-floating';
                        tabClass+=' nac-vertical';
                        break;
                    case 'wizard':
                        objClass+=' nac-wizard-tabs';
                        tabClass+=' nac-wizard';
                        break;
                    case 'standard':
                    default:
                        objClass+=' nac-std-tabs';
                        break;
                }//END switch

                $(obj).addClass(objClass);
                if(typeof (config.class)==='string' && config.class.length()>0) {
                    $(obj).addClass(config.class);
                }

                $(obj).find('ul > li').each(function() {
                    $(this).addClass(tabClass);
                    $(this).children('a').first().addClass(nActionClass + ' nac-tab-button').on('click',function() { tabClick(obj,this); });
                });
                $(obj).children('div').hide().addClass(contentClass);
            }//if(config.type==='accordion')
            methods.tabChange(obj,config.defaultTab);
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