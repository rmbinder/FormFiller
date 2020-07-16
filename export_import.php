<?php
/**
 ***********************************************************************************************
 * Exportieren und Importieren von Konfigurationen des Admidio-Plugins FormFiller
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode     : 1 - show dialog for export/import
 *            2 - export procedure
 *            3 - import procedure
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!$gCurrentUser->isAdministrator())
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$pPreferences = new ConfigTablePFF();
$pPreferences->read();

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'numeric', array('defaultValue' => 1));

switch ($getMode)
{
	case 1:
	
		$headline = $gL10n->get('PLG_FORMFILLER_EXPORT_IMPORT');
	 
	    // create html page object
    	$page = new HtmlPage($headline);
    
    	// add current url to navigation stack
    	$gNavigation->addUrl(CURRENT_URL, $headline);
    	$page->setUrlPreviousPage($gNavigation->getPreviousUrl());

    	// show form
    	$form = new HtmlForm('export_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/export_import.php', array('mode' => 2)), $page);
		$form->openGroupBox('export', $headline = $gL10n->get('PLG_FORMFILLER_EXPORT'));
    	$form->addDescription($gL10n->get('PLG_FORMFILLER_EXPORT_DESC'));
		$form->addSelectBox('form_id', $gL10n->get('PLG_FORMFILLER_CONFIGURATION').':', $pPreferences->config['Formular']['desc'], array( 'showContextDependentFirstEntry' => false));
		$form->addSubmitButton('btn_export', $gL10n->get('PLG_FORMFILLER_EXPORT'), array('icon' => 'fa-file-export', 'class' => ' col-sm-offset-3'));
    	$form->closeGroupBox();
    	 
      	// add form to html page and show page
    	$page->addHtml($form->show(false));
    
    	// show form
    	$form = new HtmlForm('import_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/export_import.php', array('mode' => 3)), $page, array('enableFileUpload' => true));
    	$form->openGroupBox('import', $headline = $gL10n->get('PLG_FORMFILLER_IMPORT'));
    	$form->addDescription($gL10n->get('PLG_FORMFILLER_IMPORT_DESC'));
		$form->addFileUpload('importfile', $gL10n->get('SYS_FILE').':', array( 'allowedMimeTypes' => array('application/octet-stream,text/plain')));
		$form->addSubmitButton('btn_import', $gL10n->get('PLG_FORMFILLER_IMPORT'), array('icon' => 'fa-file-import', 'class' => ' col-sm-offset-3'));
    	$form->closeGroupBox(); 
    
    	// add form to html page and show page
    	$page->addHtml($form->show(false));
    	
    	$page->show();
    	break;
    
	case 2:
		$exportArray = array();
		foreach ($pPreferences->config['Formular'] as $key => $data)
		{
			$exportArray[$key] = $data[$_POST['form_id']];
		} 
    
    	// Dateityp, der immer abgespeichert wird
		header('Content-Type: text/plain; Charset=utf-8');    

		// noetig fuer IE, da ansonsten der Download mit SSL nicht funktioniert
		header('Cache-Control: private');

		// Im Grunde ueberfluessig, hat sich anscheinend bewährt
		header("Content-Transfer-Encoding: binary");

		// Zwischenspeichern auf Proxies verhindern
		header("Cache-Control: post-check=0, pre-check=0");
		header('Content-Disposition: attachment; filename="'.$exportArray['desc'].'.cfg"');
	
		echo ';### ' . $exportArray['desc'].'.cfg' . ' ### ' . date('Y-m-d H:i:s') . ' ### utf-8 ###'."\r\n";
		echo ';### This is a configuration file of a form configuration of the plugin FormFiller ###'."\r\n";
    	echo ';### ATTENTION: ADD NO LINES - DELETE NO LINES ###'."\r\n\r\n";
        
    	foreach ($exportArray['fields'] as $key => $data)
		{
			if (substr($data, 0, 1) == 'p')
			{
        		$fieldid = (int) substr($data, 1);
        		$exportArray['usf_name_intern'][] = $gProfileFields->getPropertyById($fieldid, 'usf_name_intern');
        		$exportArray['usf_name'][] = $gProfileFields->getPropertyById($fieldid, 'usf_name');
			}
			else 
			{
        		$exportArray['usf_name_intern'][] = '';
        		$exportArray['usf_name'][] = '';
			}
		}
		foreach ($exportArray as $key => $data)
		{
			if (!is_array($data))
			{
				echo $key." = '".$data."'\r\n";
			}
		} 
		foreach ($exportArray as $key => $data)
		{
			if (is_array($data))
			{
				echo "\r\n";
				echo "[".$key."]\r\n";
				foreach ($data as $subkey => $subdata)
				{
					echo $subkey." = '".$subdata."'\r\n";
				}
			}
		} 		
		break;
   	
	case 3:

		if (!isset($_FILES['userfile']['name']))
		{
    		$gMessage->show($gL10n->get('PLG_FORMFILLER_IMPORT_ERROR_OTHER'), $gL10n->get('SYS_ERROR'));	
		}
		elseif (strlen($_FILES['userfile']['name'][0]) === 0)
		{
    		$gMessage->show($gL10n->get('SYS_FIELD_EMPTY', $gL10n->get('SYS_FILE')));
		}
		elseif (strtolower(substr($_FILES['userfile']['name'][0],-4)) <> '.cfg')
		{
			$gMessage->show($gL10n->get('PLG_FORMFILLER_IMPORT_ERROR_FILE'), $gL10n->get('SYS_ERROR'));	
		}
		
		$parsedArray = parse_ini_file ( $_FILES['userfile']['tmp_name'][0], TRUE );
	
		//pruefen, ob die eingelesene Datei eine Formularkonfiguration enthaelt
		if (	!(isset($parsedArray['desc']) && $parsedArray['desc'] <> '')
			||  !(isset($parsedArray['fields']) && is_array($parsedArray['fields']))
			||  !(isset($parsedArray['positions']) && is_array($parsedArray['positions']))
			||  !(isset($parsedArray['usf_name_intern']) && is_array($parsedArray['usf_name_intern']))
			||  !(count($parsedArray['fields']) == count($parsedArray['positions']))
			||  !(count($parsedArray['fields']) == count($parsedArray['usf_name_intern']))  )
		{
			$gMessage->show($gL10n->get('PLG_FORMFILLER_IMPORT_ERROR_FILE'), $gL10n->get('SYS_ERROR'));
		}
	
		$importArray = array();
	
		//alle Werte der eingelesenen Datei die kein Array sind in $importArray überfuehren
		//dabei werden nur Werte eingelesen, die in der aktuellen $pPreferences->config vorhanden sind
		foreach ($pPreferences->config['Formular'] as $key => $data)
		{
			if (isset($parsedArray[$key]))
			{
				if (is_array($parsedArray[$key]))
				{
					$importArray[$key] = array();
				}
				else
				{
					$importArray[$key] = $parsedArray[$key];
				}
			}
		}
	
		//jetzt die Profilfelder und Positionen (=Arrays) einlesen
		//dabei die Profilfelder nicht direkt einlesen, sondern anhand von usf_name_intern bestimmen
		//(Begr.: usf_ids koennen von Admidio-Installation zu Admidio-Installation unterschiedlich sein) 
		foreach ($parsedArray['fields'] as $key => $data)
		{
			//$fieldtype extrahieren
        	$fieldtype = substr($data, 0, 1);
        
        	if ($fieldtype == 'p')
        	{
        		$importArray['fields'][$key] = 'p'.$gProfileFields->getProperty($parsedArray['usf_name_intern'][$key], 'usf_id');
        	}
        	else 
        	{
				$importArray['fields'][$key] = $parsedArray['fields'][$key];	
        	}	
        	$importArray['positions'][$key] = $parsedArray['positions'][$key];		
		}
		
		$pointer = count($pPreferences->config['Formular']['desc']);
    	foreach ($importArray as $key => $data)	
    	{
        	$pPreferences->config['Formular'][$key][$pointer] = $data;
    	}		

    	//pruefen, ob eine PDF-Form-Groesse in der Importdatei übergeben wurde
    	//wenn ja, dann diese PDF-Form-Groesse den 'zusaetzlichen PDF-Groessen' hinzufuegen
    	if ($importArray['pdfform_size'] <> '')
    	{
    		$addSize = explode(',',$importArray['pdfform_size']);
    		$pPreferences->config['Optionen']['pdfform_addsizes'] .= ';'.$addSize[0].'x'.$addSize[1];;
    	}
		$pPreferences->save();

		$gMessage->show($gL10n->get('PLG_FORMFILLER_IMPORT_SUCCESS'));
   		break;
}
