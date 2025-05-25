<p>{$l10n->get('PLG_FORMFILLER_DEINSTALLATION_DESC')}</p>

<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    {include 'sys-template-parts/form.radio.tpl' data=$elements['deinst_org_select']}
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_deinstallation']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
