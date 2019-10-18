(function($) {
    'use strict';

    $.fn.jqCheckBox=function(options) {
        // Plugin default options.
        var config={
            //1 = checkbox, 2 = switch
            type: 1,
            readonly: false,
            onchange: function(obj,e) {},
            root_url: nAppBaseUrl,
            rel_path: '/lib/jquery.checkbox/'
        };
        if(options) { $.extend(config,options); }

        function init(obj) {
            obj.baseOnChange=function(e) {
                if($(obj).attr('readonly')=='readonly' || config.readonly) { return; }
                var cval=$(this).val();
                if(cval==1) {
                    $(this).val(0);
                } else {
                    $(this).val(1);
                }//if($(this).val()==1)
                if(config.onchange) { config.onchange.call(this,e); }
                var donchange=$(this).attr('onchange');
                if(donchange) { if(donchange.length>0) { eval(donchange); } }
                donchange=$(this).attr('data-onchange');
                if(donchange) { if(donchange.length>0) { eval(donchange); } }
            };

            obj.clear=function() { $(obj).off('change',obj.baseOnChange); };
            $(obj).on('click',obj.baseOnChange);

            switch(config.type) {
                case 2:
                    $(obj).addClass('clsCheckBoxSwitch');
                    $(obj).attr('src',config.root_url + config.rel_path + 'images/transparent-47x28.gif');
                    break;
                case 3:
                    $(obj).addClass('clsCheckBoxRound');
                    $(obj).attr('src',config.root_url + config.rel_path + 'images/transparent-16.gif');
                    break;
                case 4:
                    $(obj).addClass('clsCheckBoxPRed');
                    $(obj).attr('src',config.root_url + config.rel_path + 'images/transparent-16.gif');
                    break;
                case 5:
                    $(obj).addClass('clsSmallCheckBoxSwitch');
                    $(obj).attr('src',config.root_url + config.rel_path + 'images/transparent-27x16.gif');
                    break;
                case 1:
                default:
                    $(obj).addClass('clsCheckBox');
                    $(obj).attr('src',config.root_url + config.rel_path + 'images/transparent-16.gif');
                    break;
            }//END switch
        }//END function init
        // Return jQuery object to maintain chainability.
        return this.each(function() { init(this); });
    };//END $.fn.jqCheckBox
})(jQuery);

// (function($) {
//     $.fn.imageCheckBoxCheck=function() {
//         this.filter('input[type="text"]').each(function() {
//             $(this).val(1);
//             $(this).trigger('change');
//         });
//         return this;
//     };
//     $.fn.imageCheckBoxUncheck=function() {
//         this.filter('input[type="text"]').each(function() {
//             $(this).val(0);
//             $(this).trigger('change');
//         });
//         return this;
//     };
//     $.fn.imageCheckBoxToggle=function() {
//         this.filter('input[type="text"]').each(function() {
//             if($(this).val()==='1') {
//                 $(this).val(0);
//             } else {
//                 $(this).val(1);
//             }//if($(this).val()==='1')
//             $(this).trigger('change');
//         });
//         return this;
//     };
// }(jQuery));