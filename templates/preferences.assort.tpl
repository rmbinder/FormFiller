<p>{$l10n->get('PLG_FORMFILLER_ASSORT_DESC')}</p>


<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

   
   
   
   
     {include 'sys-template-parts/form.custom-content.tpl' data=$elements['assort']}
     
     
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_assort']}
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
