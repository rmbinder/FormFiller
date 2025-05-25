<p>{$l10n->get('PLG_FORMFILLER_EXPORT_IMPORT_DESC')}</p>



<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

     {include 'sys-template-parts/form.select.tpl' data=$elements['form_id']}
  
   
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_export']}
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
