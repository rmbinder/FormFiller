<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
<div id="plugin-{$name}" class="admidio-plugin-content">
  
    <div class="card admidio-field-group">
        <div class="card-header">{$l10n->get('PLG_FORMFILLER_SOURCE')}</div>
         <div class="form-text">&nbsp;&nbsp;&nbsp;&nbsp;{$l10n->get('PLG_FORMFILLER_SELECT_ROLE_OR_USER')}</div>
        <div class="card-body">
            {include '../templates/form.select.popover.plugin.formfiller.tpl' data=$elements['lst_id'] popover="{$l10n->get('PLG_FORMFILLER_CHOOSE_LISTSELECTION_DESC')}"}
            {include '../templates/form.select.popover.plugin.formfiller.tpl' data=$elements['rol_uuid'] popover="{$l10n->get('PLG_FORMFILLER_CHOOSE_ROLESELECTION_DESC')}"}
            {include '../templates/form.select.popover.plugin.formfiller.tpl' data=$elements['rol_uuid_exclusion'] popover="{$l10n->get('PLG_FORMFILLER_CHOOSE_ROLESELECTION_EXCLUSION_DESC')}"}
            {include 'sys-template-parts/form.checkbox.tpl' data=$elements['show_former_members']}
            <hr>
            {include '../templates/form.select.popover.plugin.formfiller.tpl' data=$elements['user_id'] popover="{$l10n->get('PLG_FORMFILLER_CHOOSE_USERSELECTION_DESC')}"}               
        </div>
    </div>
        
    <div class="card admidio-field-group">
        <div class="card-header">{$l10n->get('PLG_FORMFILLER_FORM_CONFIGURATION')}</div>
        <div class="card-body">
            {include '../templates/form.select.popover.plugin.formfiller.tpl' data=$elements['form_id'] popover="{$l10n->get('PLG_FORMFILLER_CHOOSE_CONFIGURATION_DESC')}"}
        </div>
    </div>
    
    <div class="card admidio-field-group">
        <div class="card-header">{$l10n->get('PLG_FORMFILLER_PDF_FILE')}{' ('}{$l10n->get('PLG_FORMFILLER_OPTIONAL')}{')'}</div>
        <div class="card-body">
            {include '../templates/form.select.popover.plugin.formfiller.tpl' data=$elements['pdf_id'] popover="{$l10n->get('PLG_FORMFILLER_PDF_FILE_DESC2')}"}
            {include '../templates/form.file.popover.plugin.formfiller.tpl' data=$elements['importpdffile'] popover="{$l10n->get('PLG_FORMFILLER_PDF_FILE_DESC3')}"}
        </div>
    </div>
        
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_save_configurations']}
</div>   </form>
