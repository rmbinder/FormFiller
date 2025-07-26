<p>{$l10n->get('PLG_FORMFILLER_FORM_CONFIG_HEADER')}                          
    <a class="admidio-icon-link openPopup" href="javascript:void(0);" data-class="modal-lg" data-href="{$urlPopupText}">
        <i class="bi bi-info-circle-fill admidio-info-icon"></i>
    </a>
</p>
<hr />
 <div style="width:100%; height:1000px; overflow:auto; border:20px;">
<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    <div class="admidio-form-required-notice"><span>{$l10n->get('SYS_REQUIRED_INPUT')}</span></div>
    {include 'sys-template-parts/form.input.tpl' data=$elements['adm_csrf_token']}

    {foreach $configurations as $configuration}
        <div class="card admidio-field-group">
            <div class="card-header">{$configuration.key+1}. {$l10n->get('SYS_CONFIGURATION')}</div>
            <div class="card-body">
                {include '../templates/form.input.popover.tpl' data=$elements[$configuration.desc] popover="{$l10n->get('PLG_FORMFILLER_DESCRIPTION_DESC')}"}
                {include '../templates/form.select.popover.tpl' data=$elements[$configuration.font] popover="{$l10n->get('PLG_FORMFILLER_FONT_DESC')}"}
                {include '../templates/form.select.popover.tpl' data=$elements[$configuration.style] popover="{$l10n->get('PLG_FORMFILLER_FONTSTYLE_DESC')}"}
                {include '../templates/form.input.popover.tpl' data=$elements[$configuration.size] popover="{$l10n->get('PLG_FORMFILLER_FONTSIZE_DESC')}"}
                {include '../templates/form.select.popover.tpl' data=$elements[$configuration.color] popover="{$l10n->get('PLG_FORMFILLER_FONTCOLOR_DESC')}"}
                {include '../templates/form.select.popover.tpl' data=$elements[$configuration.pdfform_orientation] popover="{$l10n->get('PLG_FORMFILLER_PDFFORM_ORIENTATION_DESC')}"}
                {include '../templates/form.select.popover.tpl' data=$elements[$configuration.pdfform_size] popover="{$l10n->get('PLG_FORMFILLER_PDFFORM_SIZE_DESC')}"}
                {include '../templates/form.select.popover.tpl' data=$elements[$configuration.pdfform_unit] popover="{$l10n->get('PLG_FORMFILLER_PDFFORM_UNIT_DESC')}"}
                {include '../templates/form.select.popover.tpl' data=$elements[$configuration.pdfid] popover="{$l10n->get('PLG_FORMFILLER_PDF_FILE_DESC')}"}
                {include '../templates/form.input.popover.tpl' data=$elements[$configuration.labels] popover="{$l10n->get('PLG_FORMFILLER_LABELS_DESC')}"}
 
                {if {$relations_enabled}}
                     {include '../templates/form.select.popover.tpl' data=$elements[$configuration.relationtype_id] popover="{$l10n->get('PLG_FORMFILLER_RELATION_DESC')}"}
                {/if}               
                            
                <div class="admidio-form-group admidio-form-custom-content row mb-3">
                    <label class="col-sm-3 col-form-label">
                        {$l10n->get('PLG_FORMFILLER_FIELD_SELECTION')}                   
                    </label>
                    <div class="col-sm-9">
                        <div class="table-responsive">
                            <table class="table table-condensed" id="mylist_fields_table">
                                <thead>
                                <tr>
                                    <th style="width: 10%;">{$l10n->get('SYS_ABR_NO')}</th>
                                    <th style="width: 25%;">{$l10n->get('SYS_CONTENT')}</th>
                                    <th style="width: 65%;">{$l10n->get('PLG_FORMFILLER_POSITION')}</th>
                                </tr>
                                </thead>
                                <tbody id="mylist_fields_tbody{$configuration.key}">
                                <tr id="table_row_button">
                                    <td colspan="3">
                                        <a class="icon-text-link" href="javascript:addColumn{$configuration.key}()"><i class="bi bi-plus-circle-fill"></i> {$l10n->get('SYS_ADD_COLUMN')}</a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            
                {if isset($configuration.urlConfigCopy)}
                    <a id="copy_config" class="icon-text-lin offset-sm-3" href="{$configuration.urlConfigCopy}">
                        <i class="bi bi-copy"></i> {$l10n->get('SYS_COPY_CONFIGURATION')}</a>
                {/if}
                {if isset($configuration.urlConfigDelete)}
                    &nbsp;&nbsp;&nbsp;&nbsp;<a id="delete_config" class="icon-text-link offset-sm-3" href="{$configuration.urlConfigDelete}">
                    <i class="bi bi-trash"></i> {$l10n->get('SYS_DELETE_CONFIGURATION')}</a>
                {/if}
            </div>
        </div>
    {/foreach}
 </div>
    <hr />
    <a id="add_config" class="icon-text-link" href="{$urlConfigNew}">
        <i class="bi bi-plus-circle-fill"></i> {$l10n->get('SYS_ADD_ANOTHER_CONFIG')}
    </a>
    <div class="alert alert-warning alert-small" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>{$l10n->get('ORG_NOT_SAVED_SETTINGS_LOST')}
    </div>

    <div class="form-alert" style="display: none;">&nbsp;</div>
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_configurations']}
</form>
