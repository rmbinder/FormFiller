<?php
/******************************************************************************
 * preferences.php
 * 
 * Modul Preferences (Einstellungen) für das Admidio-Plugin FormFiller
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Hinweis:
 * 
 * preferences.php ist eine modifizierte Kombination der Dateien
 * .../modules/lists/mylist.php und .../modules/preferences/preferences.php
 * 
 * Parameters:
 *
 * add	:	Anlegen einer weiteren Konfiguration (true or false)
 *
 *****************************************************************************/

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/../adm_program/system/login_valid.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

// Initialize and check the parameters
$getAdd = admFuncVariableIsValid($_GET, 'add', 'boolean', array('defaultValue' => false));

$pPreferences = new ConfigTablePFF();
$pPreferences->read();

$headline = $gL10n->get('PFF_FORMFILLER');

$num_configs	 = count($pPreferences->config['Formular']['desc']);
if($getAdd)
{
	foreach($pPreferences->config['Formular'] as $key => $dummy)
	{
		$pPreferences->config['Formular'][$key][$num_configs] = $pPreferences->config_default['Formular'][$key][0];
	}
	$num_configs++;
}

$gNavigation->addUrl(CURRENT_URL, $headline);

// create html page object
$page = new HtmlPage($headline);

// open the module configurations if a new configuration is added 
if($getAdd)
{
    $page->addJavascript('$("#tabs_nav_common").attr("class", "active");
        $("#tabs-common").attr("class", "tab-pane active");
        $("#collapse_configurations").attr("class", "panel-collapse collapse in");
        location.hash = "#" + "panel_configurations";', true);
}
else
{
    $page->addJavascript('$("#tabs_nav_common").attr("class", "active");
     $("#tabs-common").attr("class", "tab-pane active");
     ', true);
}

$page->addJavascript('
    $(".form-preferences").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        $("#"+id+" .form-alert").hide();

        // disable default form submit
        event.preventDefault();
        
        $.ajax({
            type:    "POST",
            url:     action,
            data:    $(this).serialize(),
            success: function(data) {
                if(data == "success") {
                    $("#"+id+" .form-alert").attr("class", "alert alert-success form-alert");
                    $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-ok\"></span><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    $("#"+id+" .form-alert").fadeIn("slow");
                    $("#"+id+" .form-alert").animate({opacity: 1.0}, 2500);
                    $("#"+id+" .form-alert").fadeOut("slow");
                }
                else {
                    $("#"+id+" .form-alert").attr("class", "alert alert-danger form-alert");
                    $("#"+id+" .form-alert").fadeIn();
                    $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-remove\"></span>"+data);
                }
            }
        });    
    });
    ', true);

$javascriptCode = '
    var arr_user_fields    = createProfileFieldsArray();
    ';
    
    // create a array with the necessary data
	for ($conf=0;$conf<$num_configs;$conf++)
    {      
    	$javascriptCode .= ' 
                
        var arr_default_fields'.$conf.' = createColumnsArray'.$conf.'();
        var fieldNumberIntern'.$conf.'  = 0;
                
    	// Funktion fuegt eine neue Zeile zum Zuordnen von Spalten fuer die Liste hinzu
    	function addColumn'.$conf.'() 
    	{        
        var category = "";
        var fieldNumberShow  = fieldNumberIntern'.$conf.' + 1;
        var table = document.getElementById("mylist_fields_tbody'.$conf.'");
        var newTableRow = table.insertRow(fieldNumberIntern'.$conf.');
        newTableRow.setAttribute("id", "row" + (fieldNumberIntern'.$conf.' + 1))
        
        //$(newTableRow).css("display", "none"); // ausgebaut wg. Kompatibilitaetsproblemen im IE8
        
        var newCellCount = newTableRow.insertCell(-1);
        newCellCount.innerHTML = (fieldNumberShow) + ".&nbsp;'.$gL10n->get('PFF_FIELD').'&nbsp;:";
        
        // neue Spalte zur Auswahl des Profilfeldes
        var newCellField = newTableRow.insertCell(-1);
        htmlCboFields = "<select class=\"form-control\"  size=\"1\" id=\"column" + fieldNumberShow + "\" class=\"ListProfileField\" name=\"column'.$conf.'_" + fieldNumberShow + "\">" +
                "<option value=\"\"></option>";
                
        var newCellPosition = newTableRow.insertCell(-1);        
      
        htmlPosFields = "<input type=\"text\" class=\"form-control\" id=\"position" + fieldNumberShow + "\" name=\"position'.$conf.'_" + fieldNumberShow + "\" maxlength=\"50\" ";
                
        for(var counter = 1; counter < arr_user_fields.length; counter++)
        {   
            if(category != arr_user_fields[counter]["cat_name"])
            {
                if(category.length > 0)
                {
                    htmlCboFields += "</optgroup>";
                }
                htmlCboFields += "<optgroup label=\"" + arr_user_fields[counter]["cat_name"] + "\">";
                category = arr_user_fields[counter]["cat_name"];
            }

            var selected = "";
            var position = "";
            
            // bei gespeicherten Listen das entsprechende Profilfeld selektieren
            // und den Feldnamen dem Listenarray hinzufügen
            if(arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.'])
            {
                if(arr_user_fields[counter]["id"] == arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["id"])
                {
                    selected = " selected=\"selected\" ";                 
                }
                 position = arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["positions"];
            }
            htmlCboFields += "<option value=\"" + arr_user_fields[counter]["id"] + "\" " + selected + ">" + arr_user_fields[counter]["data"] + "</option>";
        	htmlPosFields += " value=\"" + position + "\" ";
    	}
        htmlCboFields += "</select>";
        newCellField.innerHTML = htmlCboFields;

        htmlPosFields += "</input>";
      	newCellPosition.innerHTML = htmlPosFields;
    
        $(newTableRow).fadeIn("slow");
        fieldNumberIntern'.$conf.'++;
    }
    
	function createColumnsArray'.$conf.'()
    {   
        var default_fields = new Array(); ';
    	
        for($number = 0; $number < count($pPreferences->config['Formular']['fields'][$conf]); $number++)
        {          	
        	$javascriptCode .= '
            default_fields['. $number. '] 		   = new Object();
            default_fields['. $number. ']["id"]    = "'. $pPreferences->config['Formular']['fields'][$conf][$number]. '";
            default_fields['. $number. ']["positions"]    = "'. $pPreferences->config['Formular']['positions'][$conf][$number]. '";
            ';
        }
        $javascriptCode .= '
        return default_fields;
    }
        
    ';
    }       
    $javascriptCode .= '
    function createProfileFieldsArray()
    { 
        var user_fields = new Array(); ';
        $i = 1;
        foreach($gProfileFields->mProfileFields as $field)
        {    
            // add profile fields to user field array
            if($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers())
            {   
                $javascriptCode .= '
                user_fields['. $i. '] = new Object();
                user_fields['. $i. ']["cat_name"] = "'. strtr($field->getValue('cat_name'), '"', '\''). '";
                user_fields['. $i. ']["id"]   = "p'. $field->getValue('usf_id'). '";
                user_fields['. $i. ']["data"] = "'. addslashes($field->getValue('usf_name')). '";
                ';
                $i++;
            }
        }        
   
        $javascriptCode .= '
        user_fields['. $i. '] = new Object();
        user_fields['. $i. ']["cat_name"] = "'.$gL10n->get('PFF_ADDITIONAL_FIELDS').'";
        user_fields['. $i. ']["id"]   = "ddummy";           //d wie date
        user_fields['. $i. ']["data"] = "'.$gL10n->get('PFF_DATE').'";
        
        user_fields['. ($i+1). '] = new Object();
        user_fields['. ($i+1). ']["cat_name"] = "'.$gL10n->get('PFF_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+1). ']["id"]   = "ldummy";       //l wie logo
        user_fields['. ($i+1). ']["data"] = "'.$gL10n->get('PFF_PROFILE_PHOTO').'";
        
        user_fields['. ($i+2). '] = new Object();
        user_fields['. ($i+2). ']["cat_name"] = "'.$gL10n->get('PFF_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+2). ']["id"]   = "vdummy";      //v wie value
        user_fields['. ($i+2). ']["data"] = "'.$gL10n->get('PFF_VALUE').'";
        
        return user_fields;
    }
';
        
$page->addJavascript($javascriptCode);        
$javascriptCode = '$(document).ready(function() {   
';
	for($conf = 0; $conf < $num_configs; $conf++)
	{
		$javascriptCode .= '  
    	for(var counter = 0; counter < '. count($pPreferences->config['Formular']['fields'][$conf]). '; counter++) {
        	addColumn'. $conf. '();
    	}
    	';
	}     	
$javascriptCode .= '
});
';
$page->addJavascript($javascriptCode, true);  

// create module menu with back link
$preferencesMenu = new HtmlNavbar('menu_dates_create', $headline, $page);
$preferencesMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');
$page->addHtml($preferencesMenu->show(false));

$page->addHtml('
<ul class="nav nav-tabs" id="preferences_tabs">
  	<li id="tabs_nav_common"><a href="#tabs-common" data-toggle="tab">'.$gL10n->get('SYS_SETTINGS').'</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane" id="tabs-common">
        <div class="panel-group" id="accordion_common">
            <div class="panel panel-default" id="panel_configurations">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_common" href="#collapse_configurations">
                            <img src="'.THEME_PATH.'/icons/application_form_edit.png" alt="'.$gL10n->get('PFF_CONFIGURATIONS').'" title="'.$gL10n->get('PFF_CONFIGURATIONS').'" />'.$gL10n->get('PFF_CONFIGURATIONS').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_configurations" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences_function.php?form=configurations', $page, array('class' => 'form-preferences'));
                        $form->addDescription($gL10n->get('PFF_FORM_CONFIG_HEADER'));
                    	$form->addLine();
                        $form->addDescription('<div style="width:100%; height:550px; overflow:auto; border:20px;">');
                        for ($conf=0;$conf<$num_configs;$conf++)
						{                           			
							$form->openGroupBox('configurations_group',($conf+1).'. '.$gL10n->get('PFF_CONFIGURATION'));
							$form->addInput('desc'.$conf, $gL10n->get('PFF_DESCRIPTION'), $pPreferences->config['Formular']['desc'][$conf],array('helpTextIdLabel' => 'PFF_DESCRIPTION_DESC'));
							$form->addSelectBox('font'.$conf, $gL10n->get('PFF_FONT'), array('Courier'=>'Courier','Arial'=>'Arial','Times'=>'Times','Symbol'=>'Symbol','ZapfDingbats'=>'ZapfDingbats' ), array('defaultValue' => $pPreferences->config['Formular']['font'][$conf], 'showContextDependentFirstEntry' => false, 'helpTextIdLabel' => 'PFF_FONT_DESC'));
							$form->addSelectBox('style'.$conf, $gL10n->get('PFF_FONTSTYLE'), array(''=>'Normal','B'=>'Fett','I'=>'Kursiv','U'=>'Unterstrichen','BI'=>'Fett-Kursiv','BU'=>'Fett-Unterstrichen','IU'=>'Kursiv-Unterstrichen'), array('defaultValue' => $pPreferences->config['Formular']['style'][$conf],  'showContextDependentFirstEntry' => false, 'helpTextIdLabel' => 'PFF_FONTSTYLE_DESC'));
							$form->addInput('size'.$conf, $gL10n->get('PFF_FONTSIZE'), $pPreferences->config['Formular']['size'][$conf], 
                            	array('step' => 2,'type' => 'number', 'minNumber' => 6, 'maxNumber' => 40, 'helpTextIdLabel' => 'PFF_FONTSIZE_DESC'));
							$form->addSelectBox('color'.$conf, $gL10n->get('PFF_FONTCOLOR'), array('0,0,0'=>$gL10n->get('PFF_BLACK'),'255,0,0'=>$gL10n->get('PFF_RED'),'0,255,0'=>$gL10n->get('PFF_GREEN'),'0,0,255'=>$gL10n->get('PFF_BLUE')), array('defaultValue' => $pPreferences->config['Formular']['color'][$conf],  'showContextDependentFirstEntry' => false, 'helpTextIdLabel' => 'PFF_FONTCOLOR_DESC'));
                     
							$sql = 'SELECT fil.fil_id, fil.fil_name, fol.fol_name
                                FROM '.TBL_FOLDERS.' as fol, '.TBL_FILES.' as fil
                                WHERE fol.fol_id = fil.fil_fol_id
                                AND fil.fil_name LIKE \'%.PDF\' 
                                AND (  fol.fol_org_id = '.$gCurrentOrganization->getValue('org_id').'
                                OR fol.fol_org_id IS NULL )';
				        	$form->addSelectBoxFromSql('pdfid'.$conf, $gL10n->get('PFF_PDF_FILE'), $gDb, $sql, array('defaultValue' => $pPreferences->config['Formular']['pdfid'][$conf], 'helpTextIdLabel' => 'PFF_PDF_FILE_DESC'));				                                            
                     		$form->addInput('labels'.$conf, $gL10n->get('PFF_LABELS'), $pPreferences->config['Formular']['labels'][$conf],array('helpTextIdLabel' => 'PFF_LABELS_DESC'));
						
							$html = '
							<div class="table-responsive">
    							<table class="table table-condensed" id="mylist_fields_table">
        							<thead>
            							<tr>
                							<th style="width: 15%;">'.$gL10n->get('SYS_ABR_NO').'</th>
                							<th style="width: 45%;">'.$gL10n->get('SYS_CONTENT').'</th> 
                							<th style="width: 40%;">'.$gL10n->get('PFF_POSITION').'</th>    
            							</tr>
        							</thead>
        							<tbody id="mylist_fields_tbody'.$conf.'">
            							<tr id="table_row_button">
                							<td colspan="3">
                    							<a class="icon-text-link" href="javascript:addColumn'.$conf.'()"><img src="'. THEME_PATH. '/icons/add.png" alt="'.$gL10n->get('PFF_ADD_ANOTHER_FIELD').'" />'.$gL10n->get('PFF_ADD_ANOTHER_FIELD').'</a>
                							</td>
            							</tr>
        							</tbody>
    							</table>
    						</div>';
                        	$form->addCustomContent($gL10n->get('PFF_FIELD_SELECTION'), $html, array('helpTextIdLabel' => 'PFF_FIELD_SELECTION_DESC')); 
                        	$form->closeGroupBox();
						}
                        $form->addDescription('</div>');
                        $form->addLine();
                        $html = '<a id="add_config" class="icon-text-link" href="'. $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences.php?add=true"><img
                                    src="'. THEME_PATH. '/icons/add.png" alt="'.$gL10n->get('PFF_ADD_ANOTHER_CONFIG').'" />'.$gL10n->get('PFF_ADD_ANOTHER_CONFIG').'</a>';
						$htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('ORG_NOT_SAVED_SETTINGS_LOST').'</div>';
                        $form->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc)); 
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_PATH.'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        
                        $page->addHtml($form->show(false));
                    	$page->addHtml('
                    </div>
                </div>
            </div>           
            
            <div class="panel panel-default" id="panel_options">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_common" href="#collapse_options">
                            <img src="'.THEME_PATH.'/icons/options.png" alt="'.$gL10n->get('PFF_OPTIONS').'" title="'.$gL10n->get('PFF_OPTIONS').'" />'.$gL10n->get('PFF_OPTIONS').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_options" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('options_preferences_form', $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences_function.php?form=options', $page, array('class' => 'form-preferences'));
                        $form->addInput('maxpdfview', $gL10n->get('PFF_MAX_PDFVIEW'), $pPreferences->config['Optionen']['maxpdfview'], 
                            	array('step' => 1,'type' => 'number', 'minNumber' => 0,  'helpTextIdInline' => 'PFF_MAX_PDFVIEW_DESC'));
                            	                                           
                        $html = '<a id="deinstallation" class="icon-text-link" href="'. $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences_function.php?mode=2"><img
                                    src="'. THEME_PATH. '/icons/delete.png" alt="'.$gL10n->get('PFF_LINK_TO_DEINSTALLATION').'" />'.$gL10n->get('PFF_LINK_TO_DEINSTALLATION').'</a>';
                        $form->addCustomContent($gL10n->get('PFF_DEINSTALLATION'), $html, array('helpTextIdInline' => 'PFF_DEINSTALLATION_DESC'));
                        $form->addSubmitButton('btn_save_options', $gL10n->get('SYS_SAVE'), array('icon' => THEME_PATH.'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    	$page->addHtml('
                    </div>
                </div>
            </div>
            
            <div class="panel panel-default" id="panel_plugin_control">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_common" href="#collapse_plugin_control">
                            <img src="'.THEME_PATH.'/icons/lock.png" alt="'.$gL10n->get('PFF_PLUGIN_CONTROL').'" title="'.$gL10n->get('PFF_PLUGIN_CONTROL').'" />'.$gL10n->get('PFF_PLUGIN_CONTROL').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_plugin_control" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('plugin_control_preferences_form', $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences_function.php?form=plugin_control', $page, array('class' => 'form-preferences'));
                        $sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
                                FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
                                WHERE cat.cat_id = rol.rol_cat_id
                                AND (  cat.cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                                OR cat.cat_org_id IS NULL )';
				        $form->addSelectBoxFromSql('freigabe', $gL10n->get('PFF_ROLE_SELECTION'), $gDb, $sql, array('defaultValue' => $pPreferences->config['Pluginfreigabe']['freigabe'], 'helpTextIdInline' => 'PFF_ROLE_SELECTION_DESC','multiselect' => true));				                                                 
                        $form->addSelectBoxFromSql('freigabe_config', '', $gDb, $sql, array('defaultValue' => $pPreferences->config['Pluginfreigabe']['freigabe_config'], 'helpTextIdInline' => 'PFF_ROLE_SELECTION_DESC2','multiselect' => true));
                        $form->addSubmitButton('btn_save_plugin_control_preferences', $gL10n->get('SYS_SAVE'), array('icon' => THEME_PATH.'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    	$page->addHtml('
                    </div>
                </div>
            </div>
            
            <div class="panel panel-default" id="panel_plugin_informations">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_common" href="#collapse_plugin_informations">
                            <img src="'.THEME_PATH.'/icons/info.png" alt="'.$gL10n->get('PFF_PLUGIN_INFORMATION').'" title="'.$gL10n->get('PFF_PLUGIN_INFORMATION').'" />'.$gL10n->get('PFF_PLUGIN_INFORMATION').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_plugin_informations" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // create a static form
                        $form = new HtmlForm('plugin_informations_preferences_form', null, $page);                        
                        $form->addStaticControl('plg_name', $gL10n->get('PFF_PLUGIN_NAME'), $gL10n->get('PFF_FORMFILLER'));
                        $form->addStaticControl('plg_version', $gL10n->get('PFF_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
                        $form->addStaticControl('plg_date', $gL10n->get('PFF_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);
                        $html = '<a class="icon-text-link" href="http://http://www.admidio.de/dokuwiki/doku.php?id=de:plugins:formfiller" target="_blank"><img
                                    src="'. THEME_PATH. '/icons/eye.png" alt="'.$gL10n->get('PFF_DOCUMENTATION_OPEN').'" />'.$gL10n->get('PFF_DOCUMENTATION_OPEN').'</a>';
                        $form->addCustomContent($gL10n->get('PFF_DOCUMENTATION'), $html, array('helpTextIdInline' => 'PFF_DOCUMENTATION_OPEN_DESC'));
                        $page->addHtml($form->show(false));
                   		$page->addHtml('
                   	</div>
                </div>
            </div>
        </div>
    </div>
</div>
');

$page->show();

?>