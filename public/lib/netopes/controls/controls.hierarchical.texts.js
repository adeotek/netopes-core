(function($) {
    'use strict';

    $.fn.NetopesHierarchicalTexts=function(options) {
        // Plugin default options.
        let config={
            tagId: null,
            tagName: null,
            textEditorType: 'textarea', // Options: 'textarea' (default) | 'ckeditor'
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

        let unSanitizeString=function(str) {
            return str.replaceAll('<br>','\n');
        };

        let getTextInputValue=function(obj) {
            if(config.textEditorType==='ckeditor') {
                return GetCkEditorData(config.tagId + '_hValue');
            } else {
                return sanitizeString($(obj).find('.hTextsInput #' + config.tagId + '_hValue').first().val());
            }
        };

        let setTextInputValue=function(obj,value) {
            if(config.textEditorType==='ckeditor') {
                SetCkEditorData(config.tagId + '_hValue',value);
            } else {
                $(obj).find('.hTextsInput #' + config.tagId + '_hValue').first().val(unSanitizeString(value));
            }
        };

        let getNewSectionElement=function(obj,id,name,code) {
            $(obj).find('.hTextsData ul.hSections li.hItemSection.empty').remove();
            let section=$('<li class="hItemSection" data-id="' + id + '">' +
                '<span class="hItemTitle">' + name + '</span>' +
                '<input type="hidden" class="postable" name="' + config.tagName + '[' + id + '][id]" value="' + id + '">' +
                '<input type="hidden" class="postable" name="' + config.tagName + '[' + id + '][name]" value="' + name + '">' +
                '<input type="hidden" class="postable" name="' + config.tagName + '[' + id + '][code]" value="' + code + '">' +
                '<ul class="hTexts sortable"></ul>' +
                '</li>');
            $(obj).find('.hTextsData ul.hSections').append(section);
            return section;
        };

        let getNewTextElement=function(obj,id,text) {
            let element=$('<li class="hItem" data-id="' + id + '">' +
                '<div class="hItemData postable" name="' + config.tagName + '[' + id + '][data][]">' + text + '</div>' +
                '<div class="hItemEditActions"></div>' +
                '<div class="hItemDeleteActions"></div>' +
                '</li>');
            $(element).find('.hItemEditActions').append($('<button class="' + config.editButtonClass + ' hTextsEditButton"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button>').on('click',function() { editTextItem(obj,this); }));
            $(element).find('.hItemDeleteActions').append($('<button class="' + config.deleteButtonClass + ' hTextsDeleteButton"><i class="fa fa-trash" aria-hidden="true"></i></button>').on('click',function() { deleteTextItem(obj,this); }));
            return element;
        };

        let editTextItem=function(obj,actObj) {
            let textElement=$(actObj).parents('li.hItem');
            let textValue=$(textElement).find('.hItemData').first().html();
            $(obj).find('.hTextsInput #' + config.tagId + '_hId').first().val($(textElement).data('id') + '|' + $(textElement).index());
            setTextInputValue(obj,textValue);
            $(obj).find('.hTextsEditActions').show();
        };

        let deleteTextItem=function(obj,actObj) {
            ShowConfirmDialog(config.deleteConfirmText,function() {
                let section=$(actObj).parents('li.hItemSection');
                $(actObj).parents('li.hItem').remove();
                // Remove section if empty
                if($(section).find('ul.hTexts > li.hItem').length===0) {
                    $(section).remove();
                }
            },false,{title: config.deleteConfirmTitle,ok: config.deleteConfirmOkLabel,cancel: config.deleteConfirmCancelLabel});
        };

        let cancelEditTextItem=function(obj,actObj) {
            $(obj).find('.hTextsInput #' + config.tagId + '_hId').first().val('');
            setTextInputValue(obj,'');
            $(actObj).parents('.hTextsEditActions').hide();
        };

        let saveTextItem=function(obj,actObj) {
            let textValue=getTextInputValue(obj);
            $(obj).find('.hTextsInput #' + config.tagId + '_hValue').removeClass(config.fieldErrorClass);
            if(textValue.trim()==='') {
                $(obj).find('.hTextsInput #' + config.tagId + '_hValue').addClass(config.fieldErrorClass);
                return;
            }
            let currentId=$(obj).find('.hTextsInput #' + config.tagId + '_hId').first().val();
            if(currentId.length>0 && currentId.includes('|')) {
                let targetId=currentId.split('|')[0];
                let index=parseInt(currentId.split('|')[1]);
                if(isNaN(index)) {
                    console.log('Invalid text item index!');
                    return;
                }
                index+=1;
                $(obj).find('.hTextsData ul.hSections li.hItemSection[data-id="' + targetId + '"] ul.hTexts li.hItem:nth-child(' + index + ') .hItemData').html(textValue);
            } else {
                let targetId=$(actObj).data('id');
                if(!targetId) {
                    console.log('Invalid save action!');
                    return;
                }
                let section=$(obj).find('.hTextsData ul.hSections li.hItemSection[data-id="' + targetId + '"]').first();
                if(section.length===0) {
                    let targetName=$(actObj).text();
                    let targetCode=$(actObj).data('code') || '';
                    section=getNewSectionElement(obj,targetId,targetName,targetCode);
                }
                section.find('ul.hTexts').append(getNewTextElement(obj,targetId,textValue));
            }
            $(obj).find('.hTextsInput #' + config.tagId + '_hId').val('');
            setTextInputValue(obj,'');
            $(actObj).parents('.hTextsEditActions').hide();
        };

        let methods={};

        function init(obj) {
            $(obj).find('.hTextsActions .hTextsActionButton').on('click',function() { saveTextItem(obj,this); });
            $(obj).find('.hTextsData button.hTextsEditButton').on('click',function() { editTextItem(obj,this); });
            $(obj).find('.hTextsData button.hTextsDeleteButton').on('click',function() { deleteTextItem(obj,this); });
            $(obj).find('.hTextsEditActions button.hTextsSaveButton').on('click',function() { saveTextItem(obj,this); });
            $(obj).find('.hTextsEditActions button.hTextsCancelButton').on('click',function() { cancelEditTextItem(obj,this); });
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