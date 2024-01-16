<?php
/**
 ***********************************************************************************************
 * FormFiller
 *
 * Version 3.3.0
 * 
 * Dieses Plugin für Admidio ermoeglicht das Ausfuellen von PDF-Formularen sowie das Erstellen von Etiketten.
 *
 * Autor: rmb
 *
 * Hinweis: FormFiller verwendet die externen PHP-Klassen FPDF und FPDI
 *  
 * Compatible with Admidio version 4.3
 *
 * @copyright 2004-2024 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

//$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/formfiller...
$scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

// only authorized user are allowed to start this module
if (!isUserAuthorized($scriptName))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Konfiguration einlesen          
$pPreferences = new ConfigTablePFF();
if ($pPreferences->checkforupdate())
{
	$pPreferences->init();
}
else
{
	$pPreferences->read();
}

// define title (html) and headline
$title = $gL10n->get('PLG_FORMFILLER_FORMFILLER');
$headline = $gL10n->get('PLG_FORMFILLER_FORMFILLER');

$gNavigation->addStartUrl(CURRENT_URL, $headline, 'fa-pen-fancy');
    
$page = new HtmlPage('plg-formfiller-mainpage', $headline);
$page->setTitle($title);

if (isUserAuthorizedForPreferences())
{  
	// show link to pluginpreferences
	$page->addPageFunctionsMenuItem('admMenuItemPreferencesLists', $gL10n->get('SYS_SETTINGS'),
	    SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php'),  'fa-cog');
}
 
// show form
$form = new HtmlForm('configurations_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/createpdf.php'), $page, array('class' => 'form-preferences', 'enableFileUpload' => true));

$form->openGroupBox('select_role_or_user', $gL10n->get('PLG_FORMFILLER_SOURCE'));
$form->addDescription($gL10n->get('PLG_FORMFILLER_SELECT_ROLE_OR_USER'));

$sql = 'SELECT lst_id, lst_name, lst_global 
		  FROM '. TBL_LISTS .'
         WHERE lst_org_id = ?
           AND ( lst_usr_id = ?
            OR lst_global = 1)
           AND lst_name IS NOT NULL
      ORDER BY lst_global ASC, lst_name ASC';

$statement = $gDb->queryPrepared($sql, array($gCurrentOrgId, $gCurrentUserId));
$configurations = array();

if ($statement->rowCount() > 0)
{
	while ($row = $statement->fetch())
    {
    	$configurations[] = array($row['lst_id'],$row['lst_name'],($row['lst_global'] == 0 ? $gL10n->get('SYS_YOUR_LISTS') : $gL10n->get('SYS_GENERAL_LISTS') ));
    }        
}    
$form->addSelectBox('lst_id', $gL10n->get('SYS_CONFIGURATION_LIST'), $configurations, array( 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_LISTSELECTION_DESC'));

$roles = array();
$rolesNonEvents = array();
$rolesEvents = array();

// alle Rollen außer Events
$sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
          FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
         WHERE cat.cat_id = rol.rol_cat_id
           AND (  cat.cat_org_id = ?
            OR cat.cat_org_id IS NULL )
           AND cat.cat_name_intern <> ? ';

$statement = $gDb->queryPrepared($sql, array($gCurrentOrgId, 'EVENTS'));

while ($row = $statement->fetch())
{
    $row['cat_name'] = Language::translateIfTranslationStrId($row['cat_name']);
    $rolesNonEvents[] = array($row['rol_id'], $row['rol_name'], $row['cat_name'] );
}

$sortFirst  = array();
$sortSecond = array();
foreach ($rolesNonEvents as $key => $row)
{
    $sortFirst[$key] = $row[2];
    $sortSecond[$key] = $row[1];
}
array_multisort($sortFirst, SORT_ASC, $sortSecond, SORT_ASC, $rolesNonEvents);

// alle Events
$sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
          FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
         WHERE cat.cat_id = rol.rol_cat_id
           AND (  cat.cat_org_id = ?
            OR cat.cat_org_id IS NULL )
           AND cat.cat_name_intern = ? ';

$statement = $gDb->queryPrepared($sql, array($gCurrentOrgId, 'EVENTS'));

while ($row = $statement->fetch())
{
    $row['cat_name'] = Language::translateIfTranslationStrId($row['cat_name']);
    $rolesEvents[] = array($row['rol_id'], $row['rol_name'], $row['cat_name'] );
}

$sortFirst  = array();
$sortSecond = array();
$sortThird  = array();
$sortFourth = array();
foreach ($rolesEvents as $key => $row)
{
    $sortFirst[$key]  = substr($row[1], 6, 4);               // Jahr
    $sortSecond[$key] = substr($row[1], 3, 2);               // Monat
    $sortThird[$key]  = substr($row[1], 0, 2);               // Tag
    $sortFourth[$key] = 0;
    if (is_numeric(substr($row[1], 22, 1)))                  // wenn es kein Ganztagestermin ist, beginnt an Position 22 die Uhrzeit
    {
        $sortFourth[$key] = str_replace(':', '', substr($row[1], 22, 5));
    }
}
array_multisort($sortFirst, SORT_NUMERIC, $sortSecond, SORT_NUMERIC, $sortThird, SORT_NUMERIC, $sortFourth, SORT_NUMERIC, $rolesEvents);
$roles = array_merge($rolesNonEvents, $rolesEvents);

$form->addSelectBox('rol_id', $gL10n->get('SYS_ROLE'), $roles, array( 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_ROLESELECTION_DESC', 'multiselect' => true));	

$form->addCheckbox('show_former_members', $gL10n->get('PLG_FORMFILLER_FORMER_MEMBERS_ONLY'));
$form->addLine();

$sqlData['query']= 'SELECT DISTINCT 
		usr_id, CONCAT(last_name.usd_value, \' \', first_name.usd_value) AS name, SUBSTRING(last_name.usd_value,1,1) AS letter
              FROM '.TBL_MEMBERS.'
        INNER JOIN '.TBL_ROLES.'
                ON rol_id = mem_rol_id
        INNER JOIN '.TBL_CATEGORIES.'
                ON cat_id = rol_cat_id
        INNER JOIN '.TBL_USERS.'
                ON usr_id = mem_usr_id
         LEFT JOIN '.TBL_USER_DATA.' AS last_name
                ON last_name.usd_usr_id = usr_id
               AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
         LEFT JOIN '.TBL_USER_DATA.' AS first_name
                ON first_name.usd_usr_id = usr_id
               AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
             WHERE usr_valid  = 1
               AND cat_org_id = ? -- $gCurrentOrgId
               AND mem_begin <= ? -- DATE_NOW
               AND mem_end    > ? -- DATE_NOW
          ORDER BY CONCAT(last_name.usd_value, \' \', first_name.usd_value), usr_id';

$sqlData['params']= array(
		$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
		$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
		$gCurrentOrgId,
		DATE_NOW,
		DATE_NOW  );

$form->addSelectBoxFromSql('user_id', $gL10n->get('PLG_FORMFILLER_USER'), $gDb, $sqlData, array('helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_USERSELECTION_DESC', 'multiselect' => true));				                                                 
$form->closeGroupBox();			//select_role_or_user

$form->openGroupBox('select_config', $gL10n->get('PLG_FORMFILLER_FORM_CONFIGURATION'));
$form->addSelectBox('form_id', $gL10n->get('PLG_FORMFILLER_CONFIGURATION'), $pPreferences->config['Formular']['desc'], array('property' => HtmlForm::FIELD_REQUIRED , 'showContextDependentFirstEntry' => false, 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_CONFIGURATION_DESC'));
$form->closeGroupBox();

$form->openGroupBox('select_pdffile', $gL10n->get('PLG_FORMFILLER_PDF_FILE').' ('.$gL10n->get('PLG_FORMFILLER_OPTIONAL').')');
$sql = 'SELECT fil.fil_id, fil.fil_name, fol.fol_name
          FROM '.TBL_FOLDERS.' as fol, '.TBL_FILES.' as fil
         WHERE fol.fol_id = fil.fil_fol_id
           AND fil.fil_name LIKE \'%.PDF\'
           AND ( fol.fol_org_id = '.$gCurrentOrgId.'
            OR fol.fol_org_id IS NULL )
      ORDER BY fol.fol_name ASC, fil.fil_name ASC ';
$form->addSelectBoxFromSql('pdf_id', $gL10n->get('PLG_FORMFILLER_PDF_FILE'), $gDb, $sql, array('helpTextIdLabel' => 'PLG_FORMFILLER_PDF_FILE_DESC2'));	
$form->addFileUpload('importpdffile', $gL10n->get('PLG_FORMFILLER_PDF_FILE').' ('.$gL10n->get('PLG_FORMFILLER_LOCAL').')', array( 'allowedMimeTypes' => array('application/pdf'), 'helpTextIdLabel' => 'PLG_FORMFILLER_PDF_FILE_DESC3'));
$form->closeGroupBox();

$form->addSubmitButton('btn_save_configurations', $gL10n->get('PLG_FORMFILLER_PDF_FILE_GENERATE'), array('icon' => 'fa-file-pdf', 'class' => ' col-sm-offset-3'));
                        
$page->addHtml($form->show(false));
        
// show complete html page
$page->show();
