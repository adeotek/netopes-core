(function($) {
    'use strict';

    $.fn.NetopesHierarchicalTexts=function(options) {
        // Plugin default options.
        let defaults={
            tagId: null,
            tagName: null,
            textEditorType: 'textarea', // Options: 'textarea' (default) | 'ckeditor'
            showEmptySections: false,
            sortableSections: false,
            sortableTexts: false,
            fieldErrorClass: '',
            editButtonClass: 'btn btn-primary',
            deleteButtonClass: 'btn btn-danger',
            deleteConfirmText: 'Are you sure you want to delete item?',
            deleteConfirmTitle: 'Action confirm',
            deleteConfirmOkLabel: 'OK',
            deleteConfirmCancelLabel: 'Cancel',
            requiredSectionClass: '',
            requiredSectionMarker: ''
        };

        let configKey='_NetopesHierarchicalTextsConfig';
        let config=false;

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

        let getNewSectionElement=function(obj,id,name,code,required,position) {
            $(obj).find('.hTextsData ul.hSections li.hItemSection.empty').remove();
            let sectionHtml='<li class="hItemSection' + (config.sortableSections ? ' sortable' : '') + '" data-id="' + id + '" data-required="' + required + '" data-position="' + position + '">';
            if(required.toString()==='1') {
                sectionHtml+='<span class="hItemTitle ' + config.requiredSectionClass + '">' + name + config.requiredSectionMarker + '</span>';
            } else {
                sectionHtml+='<span class="hItemTitle">' + name + '</span>';
            }
            sectionHtml+='<input type="hidden" class="postable" name="' + config.tagName + '[' + id + '][id]" value="' + id + '">' +
                '<input type="hidden" class="postable" name="' + config.tagName + '[' + id + '][name]" value="' + name + '">' +
                '<input type="hidden" class="postable" name="' + config.tagName + '[' + id + '][code]" value="' + code + '">' +
                '<input type="hidden" class="postable" name="' + config.tagName + '[' + id + '][required]" value="' + required + '">' +
                '<input type="hidden" class="postable" name="' + config.tagName + '[' + id + '][position]" value="' + position + '">' +
                '<ul class="hTexts"></ul>' +
                '</li>';
            let section=$(sectionHtml);
            if(isNaN(parseInt(position))) {
                $(obj).find('.hTextsData ul.hSections').append(section);
            } else {
                let appended=false;
                let count=0;
                $(obj).find('.hTextsData ul.hSections li.hItemSection').each(function() {
                    if(isNaN(parseInt($(this).data('position'))) || parseInt(position)<parseInt($(this).data('position'))) {
                        $(this).before(section);
                        appended=true;
                        return false;
                    }
                    count++;
                });
                if(!appended) {
                    if(count>0) {
                        $(obj).find('.hTextsData ul.hSections').append(section);
                    } else {
                        $(obj).find('.hTextsData ul.hSections').prepend(section);
                    }
                }
            }
            if(config.sortableTexts) {
                $(section).find('ul.hTexts').sortable({
                    revert: true,
                    placeholder: 'ui-state-highlight',
                });
            }
            return section;
        };

        let getNewTextElement=function(obj,id,text) {
            let element=$('<li class="hItem' + (config.sortableTexts ? ' sortable' : '') + '" data-id="' + id + '">' +
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
                if($(section).data('required').toString()!=='1' && !config.showEmptySections && $(section).find('ul.hTexts > li.hItem').length===0) {
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
                    section=getNewSectionElement(obj,targetId,targetName,targetCode,$(actObj).data('required'),$(actObj).data('position'));
                }
                section.find('ul.hTexts').append(getNewTextElement(obj,targetId,textValue));
            }
            $(obj).find('.hTextsInput #' + config.tagId + '_hId').val('');
            setTextInputValue(obj,'');
            $(actObj).parents('.hTextsEditActions').hide();
            $(obj).find('.hTextsActions .hTextsActionButton').prop('disabled',false);
        };

        let addTextItem=function(obj,textValue,sectionId,sectionName,sectionCode,sectionRequired,sectionPosition) {
            let section=$(obj).find('.hTextsData ul.hSections li.hItemSection[data-id="' + sectionId + '"]').first();
            if(section.length===0) {
                section=getNewSectionElement(obj,sectionId,sectionName,sectionCode,sectionRequired,sectionPosition);
            }
            section.find('ul.hTexts').append(getNewTextElement(obj,sectionId,textValue));
        };

        let methods={
            setValueForSection: function(obj,text,sectionId) {
                setTextInputValue(obj,text);
                $(obj).find('.hTextsActions .hTextsActionButton').each(function() {
                    if($(this).data('id')!==sectionId) {
                        $(this).prop('disabled','disabled');
                    }
                });
            },
            setValue: function(obj,json) {
                if(typeof json==='string') {
                    json=JSON.parse(json);
                }
                for(let x in json) {
                    let item=json[x];
                    for(let i in item.data) {
                        let value=item.data[i];
                        if(value) {
                            addTextItem(obj,value,item.id,item.name,item.code,item.required,item.position);
                        }
                    }
                }
            },
            setDisabled: function(obj,disabled) {
                if(disabled===true || disabled==='true' || disabled===1 || disabled==='1') {
                    $(obj).attr('data-disabled','disabled');
                    $(obj).find('.hTextsActions .hTextsActionButton').prop('disabled','disabled');
                    $(obj).find('.hTextsData button.hTextsEditButton').prop('disabled','disabled');
                    $(obj).find('.hTextsData button.hTextsDeleteButton').prop('disabled','disabled');
                    if(config.textEditorType==='ckeditor') {
                        SetCkEditorReadonly(config.tagId + '_hValue',true);
                    } else {
                        $(obj).find('.hTextsInput #' + config.tagId + '_hValue').prop('disabled','disabled');
                    }
                    if(config.sortableTexts) {
                        $(obj).find('.hTextsData ul.hSections ul.hTexts').sortable('option','disabled',true);
                    }
                    if(config.sortableSections) {
                        $(obj).find('.hTextsData ul.hSections').sortable('option','disabled',true);
                    }
                } else if(disabled===false || disabled==='false' || disabled===0 || disabled==='0') {
                    $(obj).attr('data-disabled','');
                    $(obj).find('.hTextsActions .hTextsActionButton').prop('disabled',false);
                    $(obj).find('.hTextsData button.hTextsEditButton').prop('disabled',false);
                    $(obj).find('.hTextsData button.hTextsDeleteButton').prop('disabled',false);
                    if(config.textEditorType==='ckeditor') {
                        SetCkEditorReadonly(config.tagId + '_hValue',false);
                    } else {
                        $(obj).find('.hTextsInput #' + config.tagId + '_hValue').prop('disabled',false);
                    }
                    if(config.sortableTexts) {
                        $(obj).find('.hTextsData ul.hSections ul.hTexts').sortable('option','disabled',false);
                    }
                    if(config.sortableSections) {
                        $(obj).find('.hTextsData ul.hSections').sortable('option','disabled',false);
                    }
                } else {
                    console.log('Invalid setDisabled value [' + disabled + ']!');
                }
            }
        };

        function _construct(obj,options) {
            config=options;
            $(obj).data(configKey,config);
            $(obj).find('.hTextsActions .hTextsActionButton').on('click',function() { saveTextItem(obj,this); });
            $(obj).find('.hTextsData button.hTextsEditButton').on('click',function() { editTextItem(obj,this); });
            $(obj).find('.hTextsData button.hTextsDeleteButton').on('click',function() { deleteTextItem(obj,this); });
            $(obj).find('.hTextsEditActions button.hTextsSaveButton').on('click',function() { saveTextItem(obj,this); });
            $(obj).find('.hTextsEditActions button.hTextsCancelButton').on('click',function() { cancelEditTextItem(obj,this); });
            if(config.sortableTexts) {
                $(obj).find('.hTextsData ul.hSections ul.hTexts').sortable({
                    revert: true,
                    placeholder: 'ui-state-highlight',
                });
            }
            if(config.sortableSections) {
                $(obj).find('.hTextsData ul.hSections').sortable({
                    revert: true,
                    placeholder: 'ui-state-highlight',
                });
            }
        }//END function _construct

        if(typeof options==='string') {
            if(methods[options]) {
                let methodArgs=Array.prototype.slice.call(arguments,1);
                methodArgs.unshift(this);
                config=$(this).data(configKey);
                if(!config) {
                    console.log('Invalid NetopesHierarchicalTexts instance for element [' + $(this).attr('id') + ']!');
                } else {
                    return methods[options].apply(this,methodArgs);
                }
            } else {
                console.log('Invalid or inaccessible method: [' + options + ']!');
            }
        } else {
            let instanceConfig=(typeof options==='object' ? $.extend(defaults,options) : defaults);
            // Return jQuery object to maintain chainabillity.
            return this.each(function() { _construct(this,instanceConfig); });
        }
    };//END $.fn.NetopesHierarchicalTexts
})(jQuery);