
<h3>{$l10n->get('PLG_FORMFILLER_EXPORT')}</h3>

<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

     {include 'sys-template-parts/form.select.tpl' data=$elements['form_id']}
  
   
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_export']}
    
    
                     <hr />
 
   <h3>{$l10n->get('PLG_FORMFILLER_IMPORT')}</h3> 
 
  
                
     {include 'sys-template-parts/form.file.tpl' data=$elements['importfile']}
   {include 'sys-template-parts/form.select.tpl' data=$elements['cfgfile']}
    
      {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_import']}
    <div class="form-alert" style="display: none;">&nbsp;</div>
    
    
  
    
    
    
</form>
