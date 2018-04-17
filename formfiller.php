<?php
/**
 ***********************************************************************************************
 * FormFiller
 *
 * Version 2.3.0
 *
 * Dieses Plugin für Admidio ermoeglicht das Ausfuellen von PDF-Formularen sowie das Erstellen von Etiketten.
 *
 * Autor: rmb
 *
 * Hinweis: FormFiller verwendet die externen PHP-Klassen FPDF und FPDI
 *  
 * Compatible with Admidio version 3.3
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SERVER['SCRIPT_NAME']))
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

// Navigation faengt hier im Modul an
$gNavigation->clear();
$gNavigation->addUrl(CURRENT_URL, $headline);
    
$page = new HtmlPage($headline);
$page->setTitle($title);
        
// create module menu
$listsMenu = new HtmlNavbar('menu_lists_list', $headline, $page);

if ($gCurrentUser->isAdministrator())
{
	// show link to pluginpreferences 
	$listsMenu->addItem('admMenuItemPreferencesLists', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php',
                        $gL10n->get('PLG_FORMFILLER_SETTINGS'), 'options.png', 'right');        
}
        
// show module menu
$page->addHtml($listsMenu->show(false));
 
// show form
$form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/createpdf.php', $page, array('class' => 'form-preferences'));

$form->openGroupBox('select_role_or_user', $gL10n->get('PLG_FORMFILLER_SOURCE'));
$form->addDescription($gL10n->get('PLG_FORMFILLER_SELECT_ROLE_OR_USER'));

$form->openGroupBox('select_role');
$sql = 'SELECT lst_id, lst_name, lst_global 
		  FROM '. TBL_LISTS .'
         WHERE lst_org_id = '. ORG_ID. '
           AND (  lst_usr_id = '. $gCurrentUser->getValue('usr_id'). '
            OR lst_global = 1)
           AND lst_name IS NOT NULL
      ORDER BY lst_global ASC, lst_name ASC';

$configurations = array();
$statement = $gDb->query($sql);     
if ($statement->rowCount() > 0)
{
	while ($row = $statement->fetch())
    {
    	$configurations[] = array($row['lst_id'],$row['lst_name'],($row['lst_global'] == 0 ? $gL10n->get('LST_YOUR_LISTS') : $gL10n->get('LST_GENERAL_LISTS') ));
    }        
}    
$form->addSelectBox('lst_id', $gL10n->get('LST_CONFIGURATION_LIST'), $configurations, array( 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_LISTSELECTION_DESC'));
                    	
$sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
          FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
         WHERE cat.cat_id = rol.rol_cat_id
           AND (  cat.cat_org_id = '.ORG_ID.'
            OR cat.cat_org_id IS NULL )';

$form->addSelectBoxFromSql('rol_id', $gL10n->get('SYS_ROLE'), $gDb, $sql, array( 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_ROLESELECTION_DESC'));				                                                 
$form->addCheckbox('show_former_members', $gL10n->get('PLG_FORMFILLER_FORMER_MEMBERS_ONLY'));
$form->closeGroupBox();			//select_role

$form->openGroupBox('select_user');
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
               AND cat_org_id = ? -- ORG_ID
               AND mem_begin <= ? -- DATE_NOW
               AND mem_end    > ? -- DATE_NOW
          ORDER BY last_name.usd_value, first_name.usd_value, usr_id';

$sqlData['params']= array(
		$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
		$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
		ORG_ID,
		DATE_NOW,
		DATE_NOW  );

$form->addSelectBoxFromSql('user_id', $gL10n->get('PLG_FORMFILLER_USER'), $gDb, $sqlData, array( 'property' => FIELD_REQUIRED , 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_USERSELECTION_DESC', 'multiselect' => true));				                                                 

$form->closeGroupBox();			//select_user
$form->closeGroupBox();			//select_role_or_user

$form->openGroupBox('select_config', $gL10n->get('PLG_FORMFILLER_FORM_CONFIGURATION'));
$form->addSelectBox('form_id', $gL10n->get('PLG_FORMFILLER_CONFIGURATION'), $pPreferences->config['Formular']['desc'], array('property' => FIELD_REQUIRED , 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_CONFIGURATION_DESC'));
$form->closeGroupBox();

$form->openGroupBox('select_pdffile', $gL10n->get('PLG_FORMFILLER_PDF_FILE').' ('.$gL10n->get('PLG_FORMFILLER_OPTIONAL').')');
$sql = 'SELECT fil.fil_id, fil.fil_name, fol.fol_name
          FROM '.TBL_FOLDERS.' as fol, '.TBL_FILES.' as fil
         WHERE fol.fol_id = fil.fil_fol_id
           AND fil.fil_name LIKE \'%.PDF\'
           AND ( fol.fol_org_id = '.ORG_ID.'
            OR fol.fol_org_id IS NULL )';
$form->addSelectBoxFromSql('pdf_id', $gL10n->get('PLG_FORMFILLER_PDF_FILE'), $gDb, $sql, array('helpTextIdLabel' => 'PLG_FORMFILLER_PDF_FILE_DESC2'));				                                            
$form->closeGroupBox();

$form->addSubmitButton('btn_save_configurations', $gL10n->get('PLG_FORMFILLER_PDF_FILE_GENERATE'), array('icon' => THEME_URL .'/icons/page_white_acrobat.png', 'class' => ' col-sm-offset-3'));
                        
$page->addHtml($form->show(false));
        
// show complete html page
$page->show();
