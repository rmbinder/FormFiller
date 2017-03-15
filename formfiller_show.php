<?php
/**
 * Zeigt das Menue des Admidio-Plugins FormFiller
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:	keine
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// Konfiguration einlesen          
$pPreferences = new ConfigTablePFF();
$pPreferences->read();

// only authorized user are allowed to start this module
if (!check_showpluginPFF($pPreferences->config['Pluginfreigabe']['freigabe']))
{
	$gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
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

if (check_showpluginPFF($pPreferences->config['Pluginfreigabe']['freigabe_config']))
{
	// show link to pluginpreferences 
	$listsMenu->addItem('admMenuItemPreferencesLists', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/preferences.php',
                        $gL10n->get('PLG_FORMFILLER_SETTINGS'), 'options.png', 'right');        
}
        
// show module menu
$page->addHtml($listsMenu->show(false));
 
// show form
$form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/createpdf.php', $page, array('class' => 'form-preferences'));

$form->addCustomContent('', '<p>');

$form->addDescription('1. '.$gL10n->get('PLG_FORMFILLER_CHOOSE_LISTSELECTION'));
$sql = 'SELECT lst_id, lst_name, lst_global 
		  FROM '. TBL_LISTS .'
         WHERE lst_org_id = '. $gCurrentOrganization->getValue('org_id'). '
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
$form->addSelectBox('lst_id', $gL10n->get('LST_CONFIGURATION_LIST'), $configurations, array('property' => FIELD_REQUIRED , 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_LISTSELECTION_DESC'));
                    	
$form->addCustomContent('', '<p>');  
         	
$form->addDescription('2. '.$gL10n->get('PLG_FORMFILLER_CHOOSE_ROLESELECTION'));
$sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
          FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
         WHERE cat.cat_id = rol.rol_cat_id
           AND (  cat.cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
            OR cat.cat_org_id IS NULL )';
$form->addSelectBoxFromSql('rol_id', $gL10n->get('SYS_ROLE'), $gDb, $sql, array('property' => FIELD_REQUIRED , 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_ROLESELECTION_DESC'));				                                                 
$selectBoxEntries = array($gL10n->get('LST_ACTIVE_MEMBERS'),$gL10n->get('LST_FORMER_MEMBERS'),$gL10n->get('LST_ACTIVE_FORMER_MEMBERS') );
$form->addSelectBox('show_members', $gL10n->get('LST_MEMBER_STATUS'), $selectBoxEntries);
                      
$form->addCustomContent('', '<p>');	 
       
$form->addDescription('3. '.$gL10n->get('PLG_FORMFILLER_CHOOSE_CONFIGURATION'));
$form->addSelectBox('form_id', $gL10n->get('PLG_FORMFILLER_CONFIGURATION'), $pPreferences->config['Formular']['desc'], array('property' => FIELD_REQUIRED , 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PLG_FORMFILLER_CHOOSE_CONFIGURATION_DESC'));
        
$form->addSubmitButton('btn_save_configurations', $gL10n->get('PLG_FORMFILLER_PDF_FILE_GENERATE'), array('icon' => THEME_URL .'/icons/page_white_acrobat.png', 'class' => ' col-sm-offset-3'));
                        
$page->addHtml($form->show(false));
        
// show complete html page
$page->show();
