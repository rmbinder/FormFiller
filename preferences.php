<?php
/**
 ***********************************************************************************************
 * Modul Preferences (Einstellungen) für das Admidio-Plugin FormFiller
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:  preferences.php ist eine modifizierte Kombination der Dateien
 *           .../modules/lists/mylist.php und .../modules/preferences/preferences.php
 *
 * Parameters:
 *
 * add_delete : -1 - Erzeugen einer Konfiguration
 * 				>0 - Löschen einer Konfiguration
 * 
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/../../adm_program/system/login_valid.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$awardsIsActiv = false;
//Awards ist noch nicht kompatibel zu Admidio 4
/*if (file_exists(__DIR__ . '/../awards/awards_common.php'))
{
    require_once(__DIR__ . '/../awards/awards_common.php');
    if (isAwardsDbInstalled())
    {
        $awardsIsActiv = true;
    }
}*/

// only authorized user are allowed to start this module
if (!$gCurrentUser->isAdministrator())
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getAddDelete  = admFuncVariableIsValid($_GET, 'add_delete', 'numeric', array('defaultValue' => 0));

$pPreferences = new ConfigTablePFF();
$pPreferences->read();

$headline = $gL10n->get('PLG_FORMFILLER_FORMFILLER');

if ($getAddDelete === -1)
{
	foreach($pPreferences->config['Formular'] as $key => $dummy)
	{
		$pPreferences->config['Formular'][$key][] = $pPreferences->config_default['Formular'][$key][0];
	}
}
elseif ($getAddDelete > 0)
{
	foreach($pPreferences->config['Formular'] as $key => $dummy)
	{
	    array_splice($pPreferences->config['Formular'][$key], $getAddDelete-1, 1);
	}
}

$num_configs = count($pPreferences->config['Formular']['desc']);
$pPreferences->save();

//ggf. zusaetzlich definierte Groessen an das Auswahl-Array anfuegen
$selectBoxSizesEntries = array('A3'=>'A3', 'A4'=>'A4', 'A5'=>'A5', 'Letter'=>'Letter', 'Legal'=>'Legal' );
$sizes = explode(';',$pPreferences->config['Optionen']['pdfform_addsizes']);
foreach ($sizes as $data)
{
	$xyValues = explode('x', $data);
	if (count($xyValues) == 2 && is_numeric($xyValues[0]) && is_numeric($xyValues[1])) 
	{
		$selectBoxSizesEntries[$xyValues[0].','.$xyValues[1]] = $xyValues[0].'x'.$xyValues[1];
	}	
}

// add current url to navigation stack if last url was not the same page
if ( !StringUtils::strContains($gNavigation->getUrl(), 'preferences.php'))
{
    $gNavigation->addUrl(CURRENT_URL);
}

// create html page object
$page = new HtmlPage($headline);
$page->setUrlPreviousPage($gNavigation->getPreviousUrl());

// open the module configurations if a configuration is added or deleted 
if ($getAddDelete)
{
    $page->addJavascript('
        $("#tabs_nav_common").attr("class", "nav-link active");
        $("#tabs-common").attr("class", "tab-pane fade show active");
        $("#collapse_configurations").attr("class", "collapse show");
        location.hash = "#" + "panel_configurations";',
        true
        );
}
else
{
    $page->addJavascript('
        $("#tabs_nav_common").attr("class", "nav-link active");
        $("#tabs-common").attr("class", "tab-pane active");
    ', true);
}

$page->addJavascript('
    $(".form-preferences").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        var formAlert = $("#" + id + " .form-alert");
        formAlert.hide();
    
        // disable default form submit
        event.preventDefault();
    
        $.post({
    
            url: action,
            data: $(this).serialize(),
            success: function(data) {
                if (data === "success") {
    
                    formAlert.attr("class", "alert alert-success form-alert");
                    formAlert.html("<i class=\"fas fa-check\"></i><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    formAlert.fadeIn("slow");
                    formAlert.animate({opacity: 1.0}, 2500);
                    formAlert.fadeOut("slow");
                } else {
                    formAlert.attr("class", "alert alert-danger form-alert");
                    formAlert.fadeIn();
                    formAlert.html("<i class=\"fas fa-exclamation-circle\"></i>" + data);
                }
            }
        });
    });',
    true
    );

	$javascriptCode = '';
	
    // create a array with the necessary data
	for ($conf = 0;$conf < $num_configs; $conf++)
    {      
    	if (!empty($pPreferences->config['Formular']['relation'][$conf]))
    	{
    		$relationtype = new TableUserRelationType($gDb, $pPreferences->config['Formular']['relation'][$conf]);
    		$javascriptCode .= '
 			var arr_user_fields'.$conf.' = createProfileFieldsRelationArray("'.$relationtype->getValue('urt_name').'"); 
    		';
    	}
    	else 
    	{
    		$javascriptCode .= '
  			var arr_user_fields'.$conf.' = createProfileFieldsArray();
    		';
    	}
    	
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
        newCellCount.innerHTML = (fieldNumberShow) + ".&nbsp;'.$gL10n->get('PLG_FORMFILLER_FIELD').'&nbsp;:";
        
        // neue Spalte zur Auswahl des Profilfeldes
        var newCellField = newTableRow.insertCell(-1);
        htmlCboFields = "<select class=\"form-control\"  size=\"1\" id=\"column" + fieldNumberShow + "\" class=\"ListProfileField\" name=\"column'.$conf.'_" + fieldNumberShow + "\">" +
                "<option value=\"\"></option>";
                
        var newCellPosition = newTableRow.insertCell(-1);        
      
        htmlPosFields = "<input type=\"text\" class=\"form-control\" id=\"position" + fieldNumberShow + "\" name=\"position'.$conf.'_" + fieldNumberShow + "\" maxlength=\"200\" ";
                
        for(var counter = 1; counter < arr_user_fields'.$conf.'.length; counter++)
        {   
            if(category != arr_user_fields'.$conf.'[counter]["cat_name"])
            {
                if(category.length > 0)
                {
                    htmlCboFields += "</optgroup>";
                }
                htmlCboFields += "<optgroup label=\"" + arr_user_fields'.$conf.'[counter]["cat_name"] + "\">";
                category = arr_user_fields'.$conf.'[counter]["cat_name"];
            }

            var selected = "";
            var position = "";
            
            // bei gespeicherten Listen das entsprechende Profilfeld selektieren
            // und den Feldnamen dem Listenarray hinzufuegen
            if(arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.'])
            {
                if(arr_user_fields'.$conf.'[counter]["id"] == arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["id"])
                {
                    selected = " selected=\"selected\" ";                 
                }
                 position = arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["positions"];
            }
            htmlCboFields += "<option value=\"" + arr_user_fields'.$conf.'[counter]["id"] + "\" " + selected + ">" + arr_user_fields'.$conf.'[counter]["data"] + "</option>";
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
    	
        for ($number = 0; $number < count($pPreferences->config['Formular']['fields'][$conf]); $number++)
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
	function createProfileFieldsRelationArray(relation)
    { 
        var user_fields = new Array(); ';
        $i = 1;
        foreach ($gProfileFields->getProfileFields() as $field)
        {    
            // add profile fields to user field array
            if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers())
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
       
        foreach ($gProfileFields->getProfileFields() as $field)
        {
        	// add profile fields to user field array
        	if (($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers()) && $field->getValue('cat_name') == $gL10n->get('SYS_MASTER_DATA'))
        	{
        		$javascriptCode .= '
                user_fields['. $i. '] = new Object();
                user_fields['. $i. ']["cat_name"] =  "'. strtr($field->getValue('cat_name'), '"', '\'').'" + ": " + relation ;
                user_fields['. $i. ']["id"]   = "b'. $field->getValue('usf_id'). '";    //b wie Beziehung (r = Relation ist bereits belegt)
                user_fields['. $i. ']["data"] = "'. addslashes($field->getValue('usf_name')). '" + "*";
                ';
        		$i++;
        	}
        }
        
        $javascriptCode .= '
       	 
        user_fields['. $i. '] = new Object();
        user_fields['. $i. ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. $i. ']["id"]   = "ddummy";           //d wie date
        user_fields['. $i. ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_DATE').'";
        
        user_fields['. ($i+1). '] = new Object();
        user_fields['. ($i+1). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+1). ']["id"]   = "ldummy";       //l wie logo
        user_fields['. ($i+1). ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_PROFILE_PHOTO').'";
        
        user_fields['. ($i+2). '] = new Object();
        user_fields['. ($i+2). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+2). ']["id"]   = "vdummy";      //v wie value
        user_fields['. ($i+2). ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_VALUE').'";
        
        user_fields['. ($i+3). '] = new Object();
        user_fields['. ($i+3). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+3). ']["id"]   = "tdummy";      //t wie trace (l ist durch logo bereits belegt)
        user_fields['. ($i+3). ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_LINE').'";

        user_fields['. ($i+4). '] = new Object();
        user_fields['. ($i+4). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+4). ']["id"]   = "rdummy";      //r wie rectangle
        user_fields['. ($i+4). ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_RECTANGLE').'"; 

        user_fields['. ($i+5). '] = new Object();
        user_fields['. ($i+5). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS').'";
        user_fields['. ($i+5). ']["id"]   = "mdummy";      //m wie memberships
        user_fields['. ($i+5). ']["data"] = "'.$gL10n->get('ROL_ROLE_MEMBERSHIPS').'";       

        user_fields['. ($i+6). '] = new Object();
        user_fields['. ($i+6). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS').'";
        user_fields['. ($i+6). ']["id"]   = "fdummy";      //f wie former memberships
        user_fields['. ($i+6). ']["data"] = "'.$gL10n->get('PRO_FORMER_ROLE_MEMBERSHIP').'";  ';
        
        if ($awardsIsActiv)
        {
            $javascriptCode .= '
            user_fields['. ($i+7). '] = new Object();
            user_fields['. ($i+7). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS').'";
            user_fields['. ($i+7). ']["id"]   = "adummy";      //a wie awards
            user_fields['. ($i+7). ']["data"] = "'.$gL10n->get('AWA_HEADLINE').'";  ';
        }
        
        $javascriptCode .= '
        return user_fields;
    }    
        		
    function createProfileFieldsArray()
    { 
        var user_fields = new Array(); ';
        $i = 1;
        foreach ($gProfileFields->getProfileFields() as $field)
        {    
            // add profile fields to user field array
            if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers())
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
        user_fields['. $i. ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. $i. ']["id"]   = "ddummy";           //d wie date
        user_fields['. $i. ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_DATE').'";
        
        user_fields['. ($i+1). '] = new Object();
        user_fields['. ($i+1). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+1). ']["id"]   = "ldummy";       //l wie logo
        user_fields['. ($i+1). ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_PROFILE_PHOTO').'";
        
        user_fields['. ($i+2). '] = new Object();
        user_fields['. ($i+2). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+2). ']["id"]   = "vdummy";      //v wie value
        user_fields['. ($i+2). ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_VALUE').'";
        
        user_fields['. ($i+3). '] = new Object();
        user_fields['. ($i+3). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+3). ']["id"]   = "tdummy";      //t wie trace (l ist durch logo bereits belegt)
        user_fields['. ($i+3). ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_LINE').'";

        user_fields['. ($i+4). '] = new Object();
        user_fields['. ($i+4). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS').'";
        user_fields['. ($i+4). ']["id"]   = "rdummy";      //r wie rectangle
        user_fields['. ($i+4). ']["data"] = "'.$gL10n->get('PLG_FORMFILLER_RECTANGLE').'";    

        user_fields['. ($i+5). '] = new Object();
        user_fields['. ($i+5). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS').'";
        user_fields['. ($i+5). ']["id"]   = "mdummy";      //m wie membership
        user_fields['. ($i+5). ']["data"] = "'.$gL10n->get('ROL_ROLE_MEMBERSHIPS').'";   

        user_fields['. ($i+6). '] = new Object();
        user_fields['. ($i+6). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS').'";
        user_fields['. ($i+6). ']["id"]   = "fdummy";      //f wie former
        user_fields['. ($i+6). ']["data"] = "'.$gL10n->get('PRO_FORMER_ROLE_MEMBERSHIP').'";  ';
        
        if ($awardsIsActiv)
        {
            $javascriptCode .= '
            user_fields['. ($i+7). '] = new Object();
            user_fields['. ($i+7). ']["cat_name"] = "'.$gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS').'";
            user_fields['. ($i+7). ']["id"]   = "adummy";      //a wie awards
            user_fields['. ($i+7). ']["data"] = "'.$gL10n->get('AWA_HEADLINE').'";  ';
        }
        
        $javascriptCode .= '        
        
        return user_fields;
    }
    
';
        
$page->addJavascript($javascriptCode);        
$javascriptCode = '$(document).ready(function() {   
';
	for ($conf = 0; $conf < $num_configs; $conf++)
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

/**
 * @param string $group
 * @param string $id
 * @param string $title
 * @param string $icon
 * @param string $body
 * @return string
 */
function getPreferencePanel($group, $id, $title, $icon, $body)
{
    $html = '
        <div class="card" id="panel_' . $id . '">
            <div class="card-header">
                <a type="button" data-toggle="collapse" data-target="#collapse_' . $id . '">
                    <i class="' . $icon . ' fa-fw"></i>' . $title . '
                </a>
            </div>
            <div id="collapse_' . $id . '" class="collapse" aria-labelledby="headingOne" data-parent="#accordion_preferences">
                <div class="card-body">
                    ' . $body . '
                </div>
            </div>
        </div>
    ';
    return $html;
}

$page->addHtml('
<ul id="preferences_tabs" class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a id="tabs_nav_common" class="nav-link" href="#tabs-common" data-toggle="tab" role="tab">'.$gL10n->get('SYS_SETTINGS').'</a>
    </li>
</ul>
    
<div class="tab-content">
    <div class="tab-pane fade" id="tabs-common" role="tabpanel">
        <div class="accordion" id="accordion_preferences">');

// PANEL: CONFIGURATIONS

$formConfigurations = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=configurations', $page, array('class' => 'form-preferences'));
                        
$html = '<a class="admidio-icon-link openPopup" href="javascript:void(0);"
    data-href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_popup.php').'">'.
    '<i class="fas fa-info" data-toggle="tooltip" title="' . $gL10n->get('SYS_HELP') . '"></i> '.$gL10n->get('SYS_HELP').'</a>';
$formConfigurations->addDescription($gL10n->get('PLG_FORMFILLER_FORM_CONFIG_HEADER').' '.$html);
$formConfigurations->addLine();
$formConfigurations->addDescription('<div style="width:100%; height:550px; overflow:auto; border:20px;">');
for ($conf = 0; $conf < $num_configs; $conf++)
{                           			
        $formConfigurations->openGroupBox('configurations_group',($conf+1).'. '.$gL10n->get('PLG_FORMFILLER_CONFIGURATION'));
        $formConfigurations->addInput('desc'.$conf, $gL10n->get('PLG_FORMFILLER_DESCRIPTION'), $pPreferences->config['Formular']['desc'][$conf], array('property' => HtmlForm::FIELD_REQUIRED));
        $formConfigurations->addSelectBox('font'.$conf, $gL10n->get('PLG_FORMFILLER_FONT'), array('Courier'=>'Courier','Arial'=>'Arial','Times'=>'Times','Symbol'=>'Symbol','ZapfDingbats'=>'ZapfDingbats' ), array('defaultValue' => $pPreferences->config['Formular']['font'][$conf], 'showContextDependentFirstEntry' => false));
        $formConfigurations->addSelectBox('style'.$conf, $gL10n->get('PLG_FORMFILLER_FONTSTYLE'), array(''=>'Normal','B'=>'Fett','I'=>'Kursiv','U'=>'Unterstrichen','BI'=>'Fett-Kursiv','BU'=>'Fett-Unterstrichen','IU'=>'Kursiv-Unterstrichen'), array('defaultValue' => $pPreferences->config['Formular']['style'][$conf],  'showContextDependentFirstEntry' => false));
        $formConfigurations->addInput('size'.$conf, $gL10n->get('PLG_FORMFILLER_FONTSIZE'), $pPreferences->config['Formular']['size'][$conf], array('step' => 2,'type' => 'number', 'minNumber' => 6, 'maxNumber' => 40));
        $formConfigurations->addSelectBox('color'.$conf, $gL10n->get('PLG_FORMFILLER_FONTCOLOR'), array('0,0,0'=>$gL10n->get('PLG_FORMFILLER_BLACK'),'255,0,0'=>$gL10n->get('PLG_FORMFILLER_RED'),'0,255,0'=>$gL10n->get('PLG_FORMFILLER_GREEN'),'0,0,255'=>$gL10n->get('PLG_FORMFILLER_BLUE')), array('defaultValue' => $pPreferences->config['Formular']['color'][$conf],  'showContextDependentFirstEntry' => false));
        $formConfigurations->addSelectBox('pdfform_orientation'.$conf, $gL10n->get('PLG_FORMFILLER_PDFFORM_ORIENTATION'), array('P'=>'Hochformat','L'=>'Querformat' ), array('defaultValue' => $pPreferences->config['Formular']['pdfform_orientation'][$conf], 'showContextDependentFirstEntry' => true));
        $formConfigurations->addSelectBox('pdfform_size'.$conf, $gL10n->get('PLG_FORMFILLER_PDFFORM_SIZE'), $selectBoxSizesEntries, array('defaultValue' => $pPreferences->config['Formular']['pdfform_size'][$conf], 'showContextDependentFirstEntry' => true));
        $formConfigurations->addSelectBox('pdfform_unit'.$conf, $gL10n->get('PLG_FORMFILLER_PDFFORM_UNIT'), array('pt'=>'Punkt','mm'=>'Millimeter','cm'=>'Zentimeter','in'=>'Inch' ), array('defaultValue' => $pPreferences->config['Formular']['pdfform_unit'][$conf], 'showContextDependentFirstEntry' => true));							
							
        $sql = 'SELECT fil.fil_id, fil.fil_name, fol.fol_name
                  FROM '.TBL_FOLDERS.' as fol, '.TBL_FILES.' as fil
                 WHERE fol.fol_id = fil.fil_fol_id
                   AND fil.fil_name LIKE \'%.PDF\' 
                   AND ( fol.fol_org_id = '.ORG_ID.'
                    OR fol.fol_org_id IS NULL )';
        $formConfigurations->addSelectBoxFromSql('pdfid'.$conf, $gL10n->get('PLG_FORMFILLER_PDF_FILE'), $gDb, $sql, array('defaultValue' => $pPreferences->config['Formular']['pdfid'][$conf]));				                                            
        $formConfigurations->addInput('labels'.$conf, $gL10n->get('PLG_FORMFILLER_LABELS'), $pPreferences->config['Formular']['labels'][$conf]);
						
        if ($gSettingsManager->getInt('members_enable_user_relations') == 1)
        {
            // select box showing all relation types
            $sql = 'SELECT urt_id, urt_name
              	      FROM '.TBL_USER_RELATION_TYPES.'
          			 ORDER BY urt_name';
            $formConfigurations->addSelectBoxFromSql('relationtype_id'.$conf, $gL10n->get('PLG_FORMFILLER_RELATION'), $gDb, $sql,
                array('defaultValue' => $pPreferences->config['Formular']['relation'][$conf],'showContextDependentFirstEntry' => true, 'multiselect' => false));
        }
                     		
    	$html = '
        <div class="table-responsive">
            <table class="table table-condensed" id="mylist_fields_table">
                <thead>
                    <tr>
                        <th style="width: 10%;">'.$gL10n->get('SYS_ABR_NO').'</th>
                        <th style="width: 25%;">'.$gL10n->get('SYS_CONTENT').'</th> 
                        <th style="width: 65%;">'.$gL10n->get('PLG_FORMFILLER_POSITION').'</th>    
                    </tr>
                </thead>
                <tbody id="mylist_fields_tbody'.$conf.'">
                    <tr id="table_row_button">
                        <td colspan="3">
                            <a class="icon-text-link" href="javascript:addColumn'.$conf.'()"><i class="fas fa-plus-circle"></i> '.$gL10n->get('PLG_FORMFILLER_ADD_ANOTHER_FIELD').'</a>
                        </td>
                    </tr>
                </tbody>
            </table>
    	</div>';

        $formConfigurations->addCustomContent($gL10n->get('PLG_FORMFILLER_FIELD_SELECTION'), $html); 
        if ($num_configs != 1)
        {
            $formConfigurations->addLine();
            $html = '<a id="delete_config" class="icon-text-link" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php?add_delete='.($conf+1).'">
                <i class="fas fa-trash-alt"></i> '.$gL10n->get('PLG_FORMFILLER_DELETE_CONFIG').'</a>';
            $formConfigurations->addCustomContent('', $html);
        }
        $formConfigurations->closeGroupBox();
    }
$formConfigurations->addDescription('</div>');
$formConfigurations->addLine();
$html = '<a id="add_config" class="icon-text-link" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php?add_delete=-1">
    <i class="fas fa-clone"></i> '.$gL10n->get('PLG_FORMFILLER_ADD_ANOTHER_CONFIG').'</a>';
$htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('ORG_NOT_SAVED_SETTINGS_LOST').'</div>';
$formConfigurations->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc)); 
$formConfigurations->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' col-sm-offset-3'));

$page->addHtml(getPreferencePanel('common', 'configurations', $gL10n->get('PLG_FORMFILLER_CONFIGURATIONS'), 'fas fa-cogs', $formConfigurations->show()));
                        
// PANEL: OPTIONS  
                        
$formOptions = new HtmlForm('options_preferences_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=options', $page, array('class' => 'form-preferences'));
$formOptions->addInput('maxpdfview', $gL10n->get('PLG_FORMFILLER_MAX_PDFVIEW'), $pPreferences->config['Optionen']['maxpdfview'], 
    array('step' => 1,'type' => 'number', 'minNumber' => 0,  'helpTextIdInline' => 'PLG_FORMFILLER_MAX_PDFVIEW_DESC'));
$formOptions->addInput('pdfform_addsizes', $gL10n->get('PLG_FORMFILLER_PDFFORM_ADDSIZES'), $pPreferences->config['Optionen']['pdfform_addsizes'], array('helpTextIdInline' => 'PLG_FORMFILLER_PDFFORM_ADDSIZES_DESC'));
$html = '<a class="btn" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/export_import.php?mode=1">
    <i class="fas fa-exchange-alt"></i> '.$gL10n->get('PLG_FORMFILLER_LINK_TO_EXPORT_IMPORT').'</a>';
$formOptions->addCustomContent($gL10n->get('PLG_FORMFILLER_EXPORT_IMPORT'), $html, array('helpTextIdInline' => 'PLG_FORMFILLER_EXPORT_IMPORT_DESC'));
$html = '<a class="btn" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?mode=2">
    <i class="fas fa-trash-alt"></i> '.$gL10n->get('PLG_FORMFILLER_LINK_TO_DEINSTALLATION').'</a>';
$formOptions->addCustomContent($gL10n->get('PLG_FORMFILLER_DEINSTALLATION'), $html, array('helpTextIdInline' => 'PLG_FORMFILLER_DEINSTALLATION_DESC'));
$html = '<a class="btn" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/assort.php">
    <i class="fas fa-sort"></i> '.$gL10n->get('PLG_FORMFILLER_ASSORT').'</a>';
$formOptions->addCustomContent($gL10n->get('PLG_FORMFILLER_ASSORT'), $html, array('helpTextIdInline' => 'PLG_FORMFILLER_ASSORT_DESC', 'helpTextIdLabel' => 'PLG_FORMFILLER_ASSORT_NOTE'));
$formOptions->addSubmitButton('btn_save_options', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' col-sm-offset-3'));

$page->addHtml(getPreferencePanel('common', 'options', $gL10n->get('PLG_FORMFILLER_OPTIONS'), 'fas fa-cog', $formOptions->show()));
                        
// PANEL: PLUGIN INFORMATIONS
                        
$formPluginInformations = new HtmlForm('plugin_informations_preferences_form', null, $page);                        
$formPluginInformations->addStaticControl('plg_name', $gL10n->get('PLG_FORMFILLER_PLUGIN_NAME'), $gL10n->get('PLG_FORMFILLER_FORMFILLER'));
$formPluginInformations->addStaticControl('plg_version', $gL10n->get('PLG_FORMFILLER_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
$formPluginInformations->addStaticControl('plg_date', $gL10n->get('PLG_FORMFILLER_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);
$html = '<a class="icon-text-link" href="https://www.admidio.org/dokuwiki/doku.php?id=de:plugins:formfiller#formfiller" target="_blank">
    <i class="fas fa-external-link-square-alt"></i> '.$gL10n->get('PLG_FORMFILLER_DOCUMENTATION_OPEN').'</a>';
$formPluginInformations->addCustomContent($gL10n->get('PLG_FORMFILLER_DOCUMENTATION'), $html, array('helpTextIdInline' => 'PLG_FORMFILLER_DOCUMENTATION_OPEN_DESC'));

$page->addHtml(getPreferencePanel('common', 'plugin_informations', $gL10n->get('PLG_FORMFILLER_PLUGIN_INFORMATION'), 'fas fa-info-circle', $formPluginInformations->show()));

$page->addHtml('
        </div>
    </div>
</div>');

$page->show();
