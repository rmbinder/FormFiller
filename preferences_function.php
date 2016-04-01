<?php
/******************************************************************************
 * 
 * preferences_function.php
 * 
 * Verarbeiten der Einstellungen des Admidio-Plugins FormFiller
 * 
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Parameters:
 *
 * mode     : 1 - Save preferences
 *            2 - show  dialog for deinstallation
 *            3 - deinstall
 * form         - The name of the form preferences that were submitted.
 * 
 ****************************************************************************/

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

$pPreferences = new ConfigTablePFF();
$pPreferences->read();

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'numeric', array('defaultValue' => 1));
$getForm = admFuncVariableIsValid($_GET, 'form', 'string');

// in ajax mode only return simple text on error
if($getMode == 1 )
{
    $gMessage->showHtmlTextOnly(true);
}

switch($getMode)
{
case 1:
	
	try
	{
		switch($getForm)
    	{            	
            case 'configurations':
            	
				unset($pPreferences->config['Formular']);
				$konf_neu = 0;
				
    			for($conf = 0; isset($_POST['desc'. $conf]); $conf++)
    			{
    				if (empty($_POST['desc'. $conf]))	
    				{
    					continue;
    				}
    				else 
    				{            			
            			$konf_neu++;
    				}
    				
        			$pPreferences->config['Formular']['desc'][] = $_POST['desc'. $conf];
    				$pPreferences->config['Formular']['font'][] = $_POST['font'. $conf];
    				$pPreferences->config['Formular']['style'][] = $_POST['style'. $conf];
    				$pPreferences->config['Formular']['size'][] = $_POST['size'. $conf];
    				$pPreferences->config['Formular']['color'][] = $_POST['color'. $conf];
    				$pPreferences->config['Formular']['labels'][] = $_POST['labels'. $conf];
    				$pPreferences->config['Formular']['pdfid'][] = (isset($_POST['pdfid'. $conf]) ? $_POST['pdfid'. $conf] : 0);

    				$allColumnsEmpty = true;

    				$fields = array();
    				$positions = array();
    				for($number = 1; isset($_POST['column'.$conf.'_'.$number]); $number++)
    				{
        				if(strlen($_POST['column'.$conf.'_'.$number]) > 0 && strlen($_POST['position'.$conf.'_'.$number]) > 0)
        				{
        					$allColumnsEmpty = false;
            				$fields[] = $_POST['column'.$conf.'_'.$number];
            				$positions[] = $_POST['position'.$conf.'_'.$number];
        				}
    				}
    			
    				if($allColumnsEmpty)
    				{
    					$gMessage->show($gL10n->get('PFF_ERROR_MIN_DATA'));
    				}
    				$pPreferences->config['Formular']['fields'][] = $fields;	
    				$pPreferences->config['Formular']['positions'][] = $positions;		
    			}
    			
    			// wenn $konf_neu immer noch 0 ist, dann wurden alle Konfigurationen gelöscht (was nicht sein darf)
    			if($konf_neu==0)
    			{
    				$gMessage->show($gL10n->get('PFF_ERROR_MIN_CONFIG'));
    			}
            	break;
            	
        	case 'options':
        		
        		$pPreferences->config['Optionen']['maxpdfview'] = $_POST['maxpdfview'];
            	break; 
                
        	case 'plugin_control':
            	if(isset($_POST['freigabe']))
            	{
    				$pPreferences->config['Pluginfreigabe']['freigabe'] = $_POST['freigabe'];
            	}
            	if(isset($_POST['freigabe_config']))
            	{
    				$pPreferences->config['Pluginfreigabe']['freigabe_config'] = $_POST['freigabe_config'];
            	}
    			break;
            
        	default:
           		$gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    	}
	}
	catch(AdmException $e)
	{
		$e->showText();
	}    
    
	$pPreferences->save();
	echo 'success';
	
	break;

case 2:
	
	
	$headline = $gL10n->get('PFF_DEINSTALLATION');
	 
	    // create html page object
    $page = new HtmlPage($headline);
    
    // add current url to navigation stack
    $gNavigation->addUrl(CURRENT_URL, $headline);

    // create module menu with back link
    $organizationNewMenu = new HtmlNavbar('menu_deinstallation', $headline, $page);
    $organizationNewMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');
    $page->addHtml($organizationNewMenu->show(false));
    
    $page->addHtml('<p class="lead">'.$gL10n->get('PFF_DEINSTALLATION_FORM_DESC').'</p>');

    // show form
    $form = new HtmlForm('deinstallation_form', $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences_function.php?mode=3', $page);
    $radioButtonEntries = array('0' => $gL10n->get('PFF_DEINST_ACTORGONLY'), '1' => $gL10n->get('PFF_DEINST_ALLORG') );
    $form->addRadioButton('deinst_org_select',$gL10n->get('PFF_ORG_CHOICE'),$radioButtonEntries);    
    $form->addSubmitButton('btn_deinstall', $gL10n->get('PFF_DEINSTALLATION'), array('icon' => THEME_PATH.'/icons/delete.png', 'class' => ' col-sm-offset-3'));
    
    // add form to html page and show page
    $page->addHtml($form->show(false));
    $page->show();
    break;
    
case 3:
    
	$gNavigation->addUrl(CURRENT_URL);
	$gMessage->setForwardUrl($gHomepage);		

	$gMessage->show($gL10n->get('PFF_DEINST_STARTMESSAGE').$pPreferences->delete($_POST['deinst_org_select']) );
   	break;
}
?>