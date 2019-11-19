(function($) {
    'use strict';
    $.fn.NetopesCheckBox=function(options) {

        // Plugin default options.
        let config={
            type: 'checkbox', // checkbox/round_checkbox/switch/small_switch
            checkedClass: '',
            uncheckedClass: '',
            baseUrl: '',
            onChange: false,
            onClick: false
        };
        if(options) { $.extend(config,options); }

        let methods={
            toggle: function(obj,prevDefault) {
                if($(obj).prop('disabled') || $(obj).prop('readonly')) {
                    return false;
                }
                if($(obj).val()==='1') {
                    $(obj).val(0);
                } else {
                    $(obj).val(1);
                }//if($(obj).val()==='1')
                if(prevDefault!==true && prevDefault!==1 && prevDefault!=='1') {
                    $(obj).trigger('change');
                }
            },
            check: function(obj,prevDefault) {
                if($(obj).prop('disabled') || $(obj).prop('readonly')) {
                    return false;
                }
                $(obj).val(1);
                if(prevDefault!==true && prevDefault!==1 && prevDefault!=='1') {
                    $(obj).trigger('change');
                }
            },
            uncheck: function(obj,prevDefault) {
                if($(obj).prop('disabled') || $(obj).prop('readonly')) {
                    return false;
                }
                $(obj).val(0);
                if(prevDefault!==true && prevDefault!==1 && prevDefault!=='1') {
                    $(obj).trigger('change');
                }
            }
        };

        function init(obj) {
            switch(config.type) {
                case 'round_checkbox':
                    $(obj).addClass('clsCheckBoxRound');
                    $(obj).attr('src',config.baseUrl + 'images/transparent.gif');
                    break;
                case 'switch':
                    $(obj).addClass('clsCheckBoxSwitch');
                    $(obj).attr('src',config.baseUrl + 'images/transparent-47x28.gif');
                    break;
                case 'small_switch':
                    $(obj).addClass('clsCheckBoxSmallSwitch');
                    $(obj).attr('src',config.baseUrl + 'images/transparent-27x16.gif');
                    break;
                case 'checkbox':
                default:
                    $(obj).attr('src',config.baseUrl + 'images/transparent.gif');
                    break;
            }//END switch

            if(config.checkedClass.length>0) {
                $(obj).addClass(config.checkedClass);
            } else {
                $(obj).addClass('cb-default-ck');
            }
            if(config.uncheckedClass.length>0) {
                $(obj).addClass(config.uncheckedClass);
            } else {
                $(obj).addClass('cb-default-uk');
            }

            if(typeof config.onClick==='function') {
                $(obj).on('click',function(e) {
                    try {
                        config.onClick(obj,e);
                    } catch(e) {
                        console.log(e);
                        console.log(config.onClick);
                    }
                });
            } else if(typeof config.onClick==='string' && config.onClick.length>0) {
                $(obj).on('click',function(e) {
                    try {
                        eval(config.onClick);
                    } catch(e) {
                        console.log(e);
                        console.log(config.onClick);
                    }
                });
            } else {
                $(obj).on('click',function() {
                    methods.toggle(obj);
                });
            }

            if(typeof config.onChange==='function') {
                $(obj).on('change',function(e) {
                    if($(this).data('prevdef')!=='1') {
                        try {
                            config.onChange(obj,e);
                        } catch(e) {
                            console.log(e);
                            console.log(config.onChange);
                        }
                    }
                });
            } else if(typeof config.onChange==='string' && config.onChange.length>0) {
                $(obj).on('change',function() {
                    if($(this).data('prevdef')!=='1') {
                        try {
                            eval(config.onChange);
                        } catch(e) {
                            console.log(e);
                            console.log(config.onChange);
                        }
                    }
                });
            }
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
            return this.each(function() {
                if(this.nodeName==='INPUT' && this.type==='image') {
                    init(this);
                } else {
                    console.log('Invalid tag for NetopesCheckBox usage!');
                    console.log(this);
                }
            });
        }
    };//END $.fn.jqCheckBox
})(jQuery);