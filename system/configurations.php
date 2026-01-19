<?php
/**
 ***********************************************************************************************
 * Modul Configurations of the admidio plugin Formfiller
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * add     : add a configuration
 * delete  : delete a configuration
 * copy    : copy a configuration
 *
 ***********************************************************************************************
 */
use Admidio\Changelog\Service\ChangelogService;
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Users\Entity\UserRelationType;
use Plugins\FormFiller\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/../../../system/login_valid.php');
    require_once (__DIR__ . '/common_function.php');

    // only authorized user are allowed to start this module
    if (! isUserAuthorizedForPreferences()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $awardsIsActiv = false;
    if (file_exists(__DIR__ . '/../../awards/awards_common.php')) {
        require_once (__DIR__ . '/../../awards/awards_common.php');
        if (isAwardsDbInstalled()) {
            $awardsIsActiv = true;
        }
    }

    // Initialize and check the parameters
    $getAdd = admFuncVariableIsValid($_GET, 'add', 'bool');
    $getDelete = admFuncVariableIsValid($_GET, 'delete', 'numeric', array(
        'defaultValue' => 0
    ));
    $getCopy = admFuncVariableIsValid($_GET, 'copy', 'numeric', array(
        'defaultValue' => 0
    ));

    $headline = $gL10n->get('SYS_CONFIGURATIONS');

    // add current url to navigation stack if last url was not the same page
    if (! str_contains($gNavigation->getUrl(), 'configurations.php')) {
        $gNavigation->addUrl(CURRENT_URL, $headline);
    }

    if ($getAdd) {
        foreach ($pPreferences->config['Formular'] as $key => $dummy) {
            $pPreferences->config['Formular'][$key][] = $pPreferences->config_default['Formular'][$key][0];
        }
    }

    if ($getDelete > 0) {
        foreach ($pPreferences->config['Formular'] as $key => $dummy) {
            array_splice($pPreferences->config['Formular'][$key], $getDelete - 1, 1);
        }
    }

    if ($getCopy > 0) {
        foreach ($pPreferences->config['Formular'] as $key => $dummy) {
            if ($key == 'desc') {
                $pPreferences->config['Formular'][$key][] = $pPreferences->createDesc($pPreferences->config['Formular'][$key][$getCopy - 1]);
            } else {
                $pPreferences->config['Formular'][$key][] = $pPreferences->config['Formular'][$key][$getCopy - 1];
            }
        }
    }

    $num_configs = count($pPreferences->config['Formular']['desc']);
    $pPreferences->save();

    // ggf. zusaetzlich definierte Groessen an das Auswahl-Array anfuegen
    $selectBoxSizesEntries = array(
        'A3' => 'A3',
        'A4' => 'A4',
        'A5' => 'A5',
        'Letter' => 'Letter',
        'Legal' => 'Legal'
    );
    $sizes = explode(';', $pPreferences->config['Optionen']['pdfform_addsizes']);
    foreach ($sizes as $data) {
        $xyValues = explode('x', $data);
        if (count($xyValues) == 2 && is_numeric($xyValues[0]) && is_numeric($xyValues[1])) {
            $selectBoxSizesEntries[$xyValues[0] . ',' . $xyValues[1]] = $xyValues[0] . 'x' . $xyValues[1];
        }
    }

    // create html page object
    $page = PagePresenter::withHtmlIDAndHeadline('plg-formfiller-configurations', $headline);
    $javascriptCode = 'var arr_user_fields = createProfileFieldsArray();';

    ChangelogService::displayHistoryButton($page, 'configurations', 'formfiller');

    // create an array with the necessary data
    $javascriptCode = '';

    // create a array with the necessary data
    for ($conf = 0; $conf < $num_configs; $conf ++) {
        if (! empty($pPreferences->config['Formular']['relation'][$conf])) {
            $relationtype = new UserRelationType($gDb, $pPreferences->config['Formular']['relation'][$conf]);
            $javascriptCode .= '
 			var arr_user_fields' . $conf . ' = createProfileFieldsRelationArray("' . $relationtype->getValue('urt_name') . '"); 
    		';
        } else {
            $javascriptCode .= '
  			var arr_user_fields' . $conf . ' = createProfileFieldsArray();
    		';
        }

        $javascriptCode .= ' 
        var arr_default_fields' . $conf . ' = createColumnsArray' . $conf . '();
        var fieldNumberIntern' . $conf . '  = 0;
                
    	// Funktion fuegt eine neue Zeile zum Zuordnen von Spalten fuer die Liste hinzu
    	function addColumn' . $conf . '() 
    	{        
        var category = "";
        var fieldNumberShow  = fieldNumberIntern' . $conf . ' + 1;
        var table = document.getElementById("mylist_fields_tbody' . $conf . '");
        var newTableRow = table.insertRow(fieldNumberIntern' . $conf . ');
        newTableRow.setAttribute("id", "row" + (fieldNumberIntern' . $conf . ' + 1))
        
        //$(newTableRow).css("display", "none"); // ausgebaut wg. Kompatibilitaetsproblemen im IE8
        
        var newCellCount = newTableRow.insertCell(-1);
        newCellCount.innerHTML = (fieldNumberShow) + ".&nbsp;' . $gL10n->get('PLG_FORMFILLER_FIELD') . '&nbsp;:";
        
        // neue Spalte zur Auswahl des Profilfeldes
        var newCellField = newTableRow.insertCell(-1);
        htmlCboFields = "<select class=\"form-control\"  size=\"1\" id=\"column" + fieldNumberShow + "\" class=\"ListProfileField\" name=\"column' . $conf . '_" + fieldNumberShow + "\">" +
                "<option value=\"\"></option>";
                
        var newCellPosition = newTableRow.insertCell(-1);        
      
        htmlPosFields = "<input type=\"text\" class=\"form-control\" id=\"position" + fieldNumberShow + "\" name=\"position' . $conf . '_" + fieldNumberShow + "\" maxlength=\"200\" ";
                
        for(var counter = 1; counter < arr_user_fields' . $conf . '.length; counter++)
        {   
            if(category != arr_user_fields' . $conf . '[counter]["cat_name"])
            {
                if(category.length > 0)
                {
                    htmlCboFields += "</optgroup>";
                }
                htmlCboFields += "<optgroup label=\"" + arr_user_fields' . $conf . '[counter]["cat_name"] + "\">";
                category = arr_user_fields' . $conf . '[counter]["cat_name"];
            }

            var selected = "";
            var position = "";
            
            // bei gespeicherten Listen das entsprechende Profilfeld selektieren
            // und den Feldnamen dem Listenarray hinzufuegen
            if(arr_default_fields' . $conf . '[fieldNumberIntern' . $conf . '])
            {
                if(arr_user_fields' . $conf . '[counter]["id"] == arr_default_fields' . $conf . '[fieldNumberIntern' . $conf . ']["id"])
                {
                    selected = " selected=\"selected\" ";                 
                }
                 position = arr_default_fields' . $conf . '[fieldNumberIntern' . $conf . ']["positions"];
            }
            htmlCboFields += "<option value=\"" + arr_user_fields' . $conf . '[counter]["id"] + "\" " + selected + ">" + arr_user_fields' . $conf . '[counter]["data"] + "</option>";
        	htmlPosFields += " value=\"" + position + "\" ";
    	}
        htmlCboFields += "</select>";
        newCellField.innerHTML = htmlCboFields;

        htmlPosFields += "</input>";
      	newCellPosition.innerHTML = htmlPosFields;
    
        $(newTableRow).fadeIn("slow");
        fieldNumberIntern' . $conf . '++;
    }
    
	function createColumnsArray' . $conf . '()
    {   
        var default_fields = new Array(); ';

        for ($number = 0; $number < count($pPreferences->config['Formular']['fields'][$conf]); $number ++) {
            $javascriptCode .= '
            default_fields[' . $number . '] 		   = new Object();
            default_fields[' . $number . ']["id"]    = "' . $pPreferences->config['Formular']['fields'][$conf][$number] . '";
            default_fields[' . $number . ']["positions"]    = "' . $pPreferences->config['Formular']['positions'][$conf][$number] . '";
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
    foreach ($gProfileFields->getProfileFields() as $field) {
        // add profile fields to user field array
        if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->isAdministratorUsers()) {
            $javascriptCode .= '
                user_fields[' . $i . '] = new Object();
                user_fields[' . $i . ']["cat_name"] = "' . strtr($field->getValue('cat_name'), '"', '\'') . '";
                user_fields[' . $i . ']["id"]   = "p' . $field->getValue('usf_id') . '";
                user_fields[' . $i . ']["data"] = "' . addslashes($field->getValue('usf_name')) . '";
                ';
            $i ++;
        }
    }

    foreach ($gProfileFields->getProfileFields() as $field) {
        // add profile fields to user field array
        if (($field->getValue('usf_hidden') == 0 || $gCurrentUser->isAdministratorUsers()) && $field->getValue('cat_name') == $gL10n->get('SYS_BASIC_DATA')) {
            $javascriptCode .= '
                user_fields[' . $i . '] = new Object();
                user_fields[' . $i . ']["cat_name"] =  "' . strtr($field->getValue('cat_name'), '"', '\'') . '" + ": " + relation ;
                user_fields[' . $i . ']["id"]   = "b' . $field->getValue('usf_id') . '";    //b wie Beziehung (r = Relation ist bereits belegt)
                user_fields[' . $i . ']["data"] = "' . addslashes($field->getValue('usf_name')) . '" + "*";
                ';
            $i ++;
        }
    }

    $javascriptCode .= '
       	 
        user_fields[' . $i . '] = new Object();
        user_fields[' . $i . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . $i . ']["id"]   = "ddummy";           //d wie date
        user_fields[' . $i . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_DATE') . '";
        
        user_fields[' . ($i + 1) . '] = new Object();
        user_fields[' . ($i + 1) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 1) . ']["id"]   = "ldummy";       //l wie logo
        user_fields[' . ($i + 1) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_PROFILE_PHOTO') . '";
        
        user_fields[' . ($i + 2) . '] = new Object();
        user_fields[' . ($i + 2) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 2) . ']["id"]   = "vdummy";      //v wie value
        user_fields[' . ($i + 2) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_VALUE') . '";
        
        user_fields[' . ($i + 3) . '] = new Object();
        user_fields[' . ($i + 3) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 3) . ']["id"]   = "tdummy";      //t wie trace (l ist durch logo bereits belegt)
        user_fields[' . ($i + 3) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_LINE') . '";

        user_fields[' . ($i + 4) . '] = new Object();
        user_fields[' . ($i + 4) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 4) . ']["id"]   = "rdummy";      //r wie rectangle
        user_fields[' . ($i + 4) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_RECTANGLE') . '"; 

        user_fields[' . ($i + 5) . '] = new Object();
        user_fields[' . ($i + 5) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 5) . ']["id"]   = "idummy";      //i wie user_(i)d
        user_fields[' . ($i + 5) . ']["data"] = "User_id";       

        user_fields[' . ($i + 6) . '] = new Object();
        user_fields[' . ($i + 6) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 6) . ']["id"]   = "udummy";      //u wie user(u)uid
        user_fields[' . ($i + 6) . ']["data"] = "User_uuid";  

        user_fields[' . ($i + 7) . '] = new Object();
        user_fields[' . ($i + 7) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS') . '";
        user_fields[' . ($i + 7) . ']["id"]   = "mdummy";      //m wie memberships
        user_fields[' . ($i + 7) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_ROLE_MEMBERSHIPS') . '";       

        user_fields[' . ($i + 8) . '] = new Object();
        user_fields[' . ($i + 8) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS') . '";
        user_fields[' . ($i + 8) . ']["id"]   = "fdummy";      //f wie former memberships
        user_fields[' . ($i + 8) . ']["data"] = "' . $gL10n->get('SYS_FORMER_ROLE_MEMBERSHIP') . '";  ';

    if ($awardsIsActiv) {
        $javascriptCode .= '
            user_fields[' . ($i + 9) . '] = new Object();
            user_fields[' . ($i + 9) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS') . '";
            user_fields[' . ($i + 9) . ']["id"]   = "adummy";      //a wie awards
            user_fields[' . ($i + 9) . ']["data"] = "' . $gL10n->get('AWA_HEADLINE') . '";  ';
    }

    $javascriptCode .= '
        return user_fields;
    }    
        		
    function createProfileFieldsArray()
    { 
        var user_fields = new Array(); ';
    $i = 1;
    foreach ($gProfileFields->getProfileFields() as $field) {
        // add profile fields to user field array
        if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->isAdministratorUsers()) {
            $javascriptCode .= '
                user_fields[' . $i . '] = new Object();
                user_fields[' . $i . ']["cat_name"] = "' . strtr($field->getValue('cat_name'), '"', '\'') . '";
                user_fields[' . $i . ']["id"]   = "p' . $field->getValue('usf_id') . '";
                user_fields[' . $i . ']["data"] = "' . addslashes($field->getValue('usf_name')) . '";
                ';
            $i ++;
        }
    }

    $javascriptCode .= '
        		 
        user_fields[' . $i . '] = new Object();
        user_fields[' . $i . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . $i . ']["id"]   = "ddummy";           //d wie date
        user_fields[' . $i . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_DATE') . '";
        
        user_fields[' . ($i + 1) . '] = new Object();
        user_fields[' . ($i + 1) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 1) . ']["id"]   = "ldummy";       //l wie logo
        user_fields[' . ($i + 1) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_PROFILE_PHOTO') . '";
        
        user_fields[' . ($i + 2) . '] = new Object();
        user_fields[' . ($i + 2) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 2) . ']["id"]   = "vdummy";      //v wie value
        user_fields[' . ($i + 2) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_VALUE') . '";
        
        user_fields[' . ($i + 3) . '] = new Object();
        user_fields[' . ($i + 3) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 3) . ']["id"]   = "tdummy";      //t wie trace (l ist durch logo bereits belegt)
        user_fields[' . ($i + 3) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_LINE') . '";

        user_fields[' . ($i + 4) . '] = new Object();
        user_fields[' . ($i + 4) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 4) . ']["id"]   = "rdummy";      //r wie rectangle
        user_fields[' . ($i + 4) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_RECTANGLE') . '";    

        user_fields[' . ($i + 5) . '] = new Object();
        user_fields[' . ($i + 5) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 5) . ']["id"]   = "idummy";      //i wie user_(i)d
        user_fields[' . ($i + 5) . ']["data"] = "User_id";       

        user_fields[' . ($i + 6) . '] = new Object();
        user_fields[' . ($i + 6) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_ADDITIONAL_FIELDS') . '";
        user_fields[' . ($i + 6) . ']["id"]   = "udummy";      //u wie user(u)uid
        user_fields[' . ($i + 6) . ']["data"] = "User_uuid";  

        user_fields[' . ($i + 7) . '] = new Object();
        user_fields[' . ($i + 7) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS') . '";
        user_fields[' . ($i + 7) . ']["id"]   = "mdummy";      //m wie memberships
        user_fields[' . ($i + 7) . ']["data"] = "' . $gL10n->get('PLG_FORMFILLER_ROLE_MEMBERSHIPS') . '";       

        user_fields[' . ($i + 8) . '] = new Object();
        user_fields[' . ($i + 8) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS') . '";
        user_fields[' . ($i + 8) . ']["id"]   = "fdummy";      //f wie former memberships
        user_fields[' . ($i + 8) . ']["data"] = "' . $gL10n->get('SYS_FORMER_ROLE_MEMBERSHIP') . '";  ';

    if ($awardsIsActiv) {
        $javascriptCode .= '
            user_fields[' . ($i + 9) . '] = new Object();
            user_fields[' . ($i + 9) . ']["cat_name"] = "' . $gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS') . '";
            user_fields[' . ($i + 9) . ']["id"]   = "adummy";      //a wie awards
            user_fields[' . ($i + 9) . ']["data"] = "' . $gL10n->get('AWA_HEADLINE') . '";  ';
    }

    $javascriptCode .= '
        return user_fields;
    }
    ';

    $page->addJavascript($javascriptCode);
    $javascriptCodeExecute = '';

    for ($conf = 0; $conf < $num_configs; $conf ++) {
        $javascriptCodeExecute .= '
    	for(var counter = 0; counter < ' . count($pPreferences->config['Formular']['fields'][$conf]) . '; counter++) {
        	addColumn' . $conf . '();
    	}
    	';
    }
    $page->addJavascript($javascriptCodeExecute, true);

    $formConfigurations = new FormPresenter('adm_configurations_preferences_form', '../templates/configurations.plugin.formfiller.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/configurations_function.php', array(
        'form' => 'configurations'
    )), $page, array(
        'class' => 'form-preferences'
    ));

    $configurations = array();

    for ($conf = 0; $conf < $num_configs; $conf ++) {
        $configuration = array(
            'key' => $conf,
            'desc' => 'desc' . $conf,
            'font' => 'font' . $conf,
            'style' => 'style' . $conf,
            'size' => 'size' . $conf,
            'color' => 'color' . $conf,
            'pdfform_orientation' => 'pdfform_orientation' . $conf,
            'pdfform_size' => 'pdfform_size' . $conf,
            'pdfform_unit' => 'pdfform_unit' . $conf,
            'pdfid' => 'pdfid' . $conf,
            'labels' => 'labels' . $conf,
            'relationtype_id' => 'relationtype_id' . $conf,
            'id' => 'id' . $conf
        );

        $formConfigurations->addInput('desc' . $conf, $gL10n->get('PLG_FORMFILLER_DESCRIPTION'), $pPreferences->config['Formular']['desc'][$conf], array(
            'property' => HtmlForm::FIELD_REQUIRED
        ));
        $formConfigurations->addSelectBox('font' . $conf, $gL10n->get('PLG_FORMFILLER_FONT'), validFonts(), array(
            'defaultValue' => $pPreferences->config['Formular']['font'][$conf],
            'showContextDependentFirstEntry' => false
        ));
        $formConfigurations->addSelectBox('style' . $conf, $gL10n->get('PLG_FORMFILLER_FONTSTYLE'), array(
            '' => 'Normal',
            'B' => 'Fett',
            'I' => 'Kursiv',
            'U' => 'Unterstrichen',
            'BI' => 'Fett-Kursiv',
            'BU' => 'Fett-Unterstrichen',
            'IU' => 'Kursiv-Unterstrichen'
        ), array(
            'defaultValue' => $pPreferences->config['Formular']['style'][$conf],
            'showContextDependentFirstEntry' => false
        ));
        $formConfigurations->addInput('size' . $conf, $gL10n->get('PLG_FORMFILLER_FONTSIZE'), $pPreferences->config['Formular']['size'][$conf], array(
            'step' => 2,
            'type' => 'number',
            'minNumber' => 6,
            'maxNumber' => 40
        ));
        $formConfigurations->addSelectBox('color' . $conf, $gL10n->get('PLG_FORMFILLER_FONTCOLOR'), array(
            '0,0,0' => $gL10n->get('PLG_FORMFILLER_BLACK'),
            '255,0,0' => $gL10n->get('PLG_FORMFILLER_RED'),
            '0,255,0' => $gL10n->get('PLG_FORMFILLER_GREEN'),
            '0,0,255' => $gL10n->get('PLG_FORMFILLER_BLUE')
        ), array(
            'defaultValue' => $pPreferences->config['Formular']['color'][$conf],
            'showContextDependentFirstEntry' => false
        ));
        $formConfigurations->addSelectBox('pdfform_orientation' . $conf, $gL10n->get('PLG_FORMFILLER_PDFFORM_ORIENTATION'), array(
            'P' => 'Hochformat',
            'L' => 'Querformat'
        ), array(
            'defaultValue' => $pPreferences->config['Formular']['pdfform_orientation'][$conf],
            'showContextDependentFirstEntry' => true
        ));
        $formConfigurations->addSelectBox('pdfform_size' . $conf, $gL10n->get('PLG_FORMFILLER_PDFFORM_SIZE'), $selectBoxSizesEntries, array(
            'defaultValue' => $pPreferences->config['Formular']['pdfform_size'][$conf],
            'showContextDependentFirstEntry' => true
        ));
        $formConfigurations->addSelectBox('pdfform_unit' . $conf, $gL10n->get('PLG_FORMFILLER_PDFFORM_UNIT'), array(
            'pt' => 'Punkt',
            'mm' => 'Millimeter',
            'cm' => 'Zentimeter',
            'in' => 'Inch'
        ), array(
            'defaultValue' => $pPreferences->config['Formular']['pdfform_unit'][$conf],
            'showContextDependentFirstEntry' => true
        ));

        $sql = 'SELECT fil.fil_id, fil.fil_name, fol.fol_name
                  FROM ' . TBL_FOLDERS . ' as fol, ' . TBL_FILES . ' as fil
                 WHERE fol.fol_id = fil.fil_fol_id
                   AND fil.fil_name LIKE \'%.PDF\' 
                   AND ( fol.fol_org_id = ' . $gCurrentOrgId . '
                    OR fol.fol_org_id IS NULL )';
        $formConfigurations->addSelectBoxFromSql('pdfid' . $conf, $gL10n->get('PLG_FORMFILLER_PDF_FILE'), $gDb, $sql, array(
            'defaultValue' => $pPreferences->config['Formular']['pdfid'][$conf]
        ));
        $formConfigurations->addInput('labels' . $conf, $gL10n->get('PLG_FORMFILLER_LABELS'), $pPreferences->config['Formular']['labels'][$conf]);

        // select box showing all relation types
        $sql = 'SELECT urt_id, urt_name
             	  FROM ' . TBL_USER_RELATION_TYPES . '
        	  ORDER BY urt_name';
        $formConfigurations->addSelectBoxFromSql('relationtype_id' . $conf, $gL10n->get('PLG_FORMFILLER_RELATION'), $gDb, $sql, array(
            'defaultValue' => $pPreferences->config['Formular']['relation'][$conf],
            'showContextDependentFirstEntry' => true,
            'multiselect' => false
        ));

        if ($num_configs > 1) {
            $configuration['urlConfigDelete'] = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/configurations.php', array(
                'delete' => $conf + 1
            ));
        }
        if (! empty('desc' . $conf)) {
            $configuration['urlConfigCopy'] = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/configurations.php', array(
                'copy' => $conf + 1
            ));
        }
        $configurations[] = $configuration;
    }
    $page->assignSmartyVariable('relations_enabled', $gSettingsManager->getInt('contacts_user_relations_enabled'));
    $page->assignSmartyVariable('configurations', $configurations);
    $page->assignSmartyVariable('urlConfigNew', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/configurations.php', array(
        'add' => 1
    )));
    $page->assignSmartyVariable('urlPopupText', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/configurations_popup.php', array(
        'message_id' => 'mylist_condition',
        'inline' => 'true'
    )));
    $formConfigurations->addSubmitButton('adm_button_save_configurations', $gL10n->get('SYS_SAVE'), array(
        'icon' => 'bi-check-lg'
    ));

    $formConfigurations->addToHtmlPage();
    $gCurrentSession->addFormObject($formConfigurations);

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
