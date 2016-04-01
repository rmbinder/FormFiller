<?php
 /******************************************************************************
 * formfiller_show
 *
 * Hauptprogramm fuer das Admidio-Plugin FormFiller
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html  
 *   
 * 
 * Parameters:	keine
 * 
 *****************************************************************************/

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');  
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

// Konfiguration einlesen          
$pPreferences = new ConfigTablePFF();
$pPreferences->read();

// define title (html) and headline
$title = $gL10n->get('PFF_FORMFILLER');
$headline = $gL10n->get('PFF_FORMFILLER');

// Navigation faengt hier im Modul an
$gNavigation->clear();
$gNavigation->addUrl(CURRENT_URL, $headline);
    
$page = new HtmlPage($headline);
$page->setTitle($title);
        
// create module menu
$listsMenu = new HtmlNavbar('menu_lists_list', $headline, $page);

if(check_showpluginPFF($pPreferences->config['Pluginfreigabe']['freigabe_config']))
{
	// show link to pluginpreferences 
	$listsMenu->addItem('admMenuItemPreferencesLists', $g_root_path. '/adm_plugins/'.$plugin_folder.'/preferences.php', 
                        $gL10n->get('PFF_SETTINGS'), 'options.png', 'right');        
}
        
// show module menu
$page->addHtml($listsMenu->show(false));
 
// show form
$form = new HtmlForm('configurations_form', $g_root_path.'/adm_plugins/'.$plugin_folder.'/createpdf.php', $page, array('class' => 'form-preferences'));

$form->addCustomContent('', '<p>');

$form->addDescription('1. '.$gL10n->get('PFF_CHOOSE_LISTSELECTION'));
                    	
$sql = 'SELECT lst_id, lst_name, lst_global FROM '. TBL_LISTS. '
        WHERE lst_org_id = '. $gCurrentOrganization->getValue('org_id'). '
        AND (  lst_usr_id = '. $gCurrentUser->getValue('usr_id'). '
            OR lst_global = 1)
        AND lst_name IS NOT NULL
        ORDER BY lst_global ASC, lst_name ASC';
$result = $gDb->query($sql);
$configurations=array();
$lst_result = $gDb->query($sql);      
if($gDb->num_rows() > 0)
{
	while($row = $gDb->fetch_array($lst_result))
    {
    	$configurations[]=array($row['lst_id'],$row['lst_name'],($row['lst_global']==0 ? $gL10n->get('LST_YOUR_LISTS') : $gL10n->get('LST_GENERAL_LISTS') ));
    }        
}    
$form->addSelectBox('lst_id', $gL10n->get('LST_CONFIGURATION_LIST'), $configurations, array( 'property' => FIELD_REQUIRED , 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PFF_CHOOSE_LISTSELECTION_DESC'));
                    	
$form->addCustomContent('', '<p>');  
         	
$form->addDescription('2. '.$gL10n->get('PFF_CHOOSE_ROLESELECTION'));
$sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
        FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
        WHERE cat.cat_id = rol.rol_cat_id
        AND (  cat.cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
        OR cat.cat_org_id IS NULL )';
$form->addSelectBoxFromSql('rol_id', $gL10n->get('SYS_ROLE'), $gDb, $sql, array( 'property' => FIELD_REQUIRED ,'helpTextIdLabel' => 'PFF_CHOOSE_ROLESELECTION_DESC'));				                                                 
$selectBoxEntries = array($gL10n->get('LST_ACTIVE_MEMBERS'),$gL10n->get('LST_FORMER_MEMBERS'),$gL10n->get('LST_ACTIVE_FORMER_MEMBERS') );
$form->addSelectBox('show_members', $gL10n->get('LST_MEMBER_STATUS'), $selectBoxEntries);
                      
$form->addCustomContent('', '<p>');	 
       
$form->addDescription('3. '.$gL10n->get('PFF_CHOOSE_CONFIGURATION'));
$form->addSelectBox('form_id', $gL10n->get('PFF_CONFIGURATION'), $pPreferences->config['Formular']['desc'], array( 'property' => FIELD_REQUIRED , 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PFF_CHOOSE_CONFIGURATION_DESC'));
        
$form->addSubmitButton('btn_save_configurations', $gL10n->get('PFF_PDF_FILE_GENERATE'), array('icon' => THEME_PATH.'/icons/page_white_acrobat.png', 'class' => ' col-sm-offset-3'));
                        
$page->addHtml($form->show(false));
        
// show complete html page
$page->show();

?>