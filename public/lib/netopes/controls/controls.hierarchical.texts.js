(function($) {
    'use strict';

    $.fn.NetopesHierarchicalTexts=function(options) {
        // Plugin default options.
        let config={
            tagId: null,
            tagName: null,
            sections: [],
            fieldErrorClass: '',
            editButtonClass: 'btn btn-primary',
            deleteButtonClass: 'btn btn-danger',
            deleteConfirmText: 'Are you sure you want to delete item?',
            deleteConfirmTitle: 'Action confirm',
            deleteConfirmOkLabel: 'OK',
            deleteConfirmCancelLabel: 'Cancel'
        };
        if(typeof options==='object') { $.extend(config,options); }

        let sanitizeString=function(str) {
            return str.replace(/(?:\r\n|\r|\n)/g,'<br>');
        };

        let getNewSectionElement=function(id,name) {
            return $('<li class="hItemSection" data-id="' + id + '">' +
                '<span class="hItemTitle">' + name + '</span>' +
                '<ul class="hTexts sortable"></ul>' +
                '</li>');
        };

        let getNewTextElement=function(id,text) {
            return $('<li class="hItem">' +
                '<div class="hItemData postable" name="' + config.tagName + '[' + id + '][]">' + sanitizeString(text) + '</div>' +
                '<div class="hItemEditActions">' +
                '<button class="' + config.editButtonClass + ' hTextsEditButton"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button>' +
                '</div>' +
                '<div class="hItemDeleteActions">' +
                '<button class="' + config.deleteButtonClass + ' hTextsDeleteButton"><i class="fa fa-trash" aria-hidden="true"></i></button>' +
                '</div>' +
                '</li>');
        };

        let saveTextItem=function(obj,actObj) {
            let targetId=$(actObj).data('id');
            let targetName=$(actObj).text();
            let currentId=$(obj).find('.hTextsInput #' + config.tagId + '_hId').first().val();
            let textValue=$(obj).find('.hTextsInput #' + config.tagId + '_hValue').first().val();
            $(obj).find('.hTextsInput #' + config.tagId + '_hValue').removeClass(config.fieldErrorClass);
            if(textValue.trim()==='') {
                $(obj).find('.hTextsInput #' + config.tagId + '_hValue').addClass(config.fieldErrorClass);
                return;
            }
            let section=$(obj).find('.hTextsData ul.hSections li.hItemSection[data-id="' + targetId + '"]').first();
            if(section.length===0) {
                section=getNewSectionElement(targetId,targetName);
                $(obj).find('.hTextsData ul.hSections').append(section);
            }
            section.find('ul.hTexts').append(getNewTextElement(targetId,textValue));
            $(obj).find('.hTextsInput #' + config.tagId + '_hValue').val('');
            $(obj).find('.hTextsInput #' + config.tagId + '_hId').val('');
        };

        let editTextItem=function(obj,actObj) {

        };

        let deleteTextItem=function(obj,actObj) {
            ShowConfirmDialog(config.deleteConfirmText,function() {
                $(actObj).parents('li.hItem').remove();
                // Remove section if empty
            },false,{title: config.deleteConfirmTitle,ok: config.deleteConfirmOkLabel,cancel: config.deleteConfirmCancelLabel});
        };

        let methods={};

        function init(obj) {
            $(obj).find('.hTextsActions .hTextsActionButton').on('click',function() { saveTextItem(obj,this); });
            $(obj).find('.hTextsData button.hTextsEditButton').on('click',function() { editTextItem(obj,this); });
            $(obj).find('.hTextsData button.hTextsDeleteButton').on('click',function() { deleteTextItem(obj,this); });
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
    };//END $.fn.NetopesHierarchicalTexts
})(jQuery);