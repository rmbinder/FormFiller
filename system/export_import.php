<?php
/**
 ***********************************************************************************************
 * Exporting and importing configurations of the Admidio plugin FormFiller
 *
 * @copyright The Admidio Team
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

use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\FormFiller\classes\Config\ConfigTable;

try
{
	require_once(__DIR__ . '/../../../system/common.php');
	require_once(__DIR__ . '/common_function.php');

	$pPreferences = new ConfigTable();
	$pPreferences->read();

	// only authorized user are allowed to start this module
	if (!isUserAuthorizedForPreferences())
	{
		throw new Exception('SYS_NO_RIGHTS');
	}

	// Initialize and check the parameters
	$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'export_import')));
	$postCfgFile = admFuncVariableIsValid($_POST, 'cfgfile', 'string');

	if (isset($_POST['adm_button_export']) && $getMode === 'export_import')
	{
		$getMode = 'export';
	}
	elseif (isset($_POST['adm_button_import']) && $getMode === 'export_import')
	{
		$getMode = 'import';
	}

	switch ($getMode)
	{
		case 'html':
		
			global $gL10n, $gSettingsManager, $gCurrentSession;
			
			$title = $gL10n->get('PLG_FORMFILLER_EXPORT_IMPORT');
			$headline =$gL10n->get('PLG_FORMFILLER_EXPORT_IMPORT');
			
			$gNavigation->addUrl(CURRENT_URL, $headline);
			
			// create html page object
			$page = PagePresenter::withHtmlIDAndHeadline('plg-formfiller-export_import-html');
			$page->setTitle($title);
			$page->setHeadline($headline);
			
			$formValues = $gSettingsManager->getAll();
			
			$formExportImport = new FormPresenter(
				'adm_preferences_form_export_import',
				'../templates/export_import.tpl',
				SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/export_import.php', array('mode' => 'export_import')),
				$page,
				array('class' => 'form-preferences', 'enableFileUpload' => true)
				);
			
			$formExportImport->addSelectBox('form_id', $gL10n->get('PLG_FORMFILLER_CONFIGURATION').':', $pPreferences->config['Formular']['desc'], array('helpTextId' => 'PLG_FORMFILLER_EXPORT_DESC', 'showContextDependentFirstEntry' => false));
			
			$formExportImport->addSubmitButton(
				'adm_button_export',
				$gL10n->get('PLG_FORMFILLER_EXPORT'),
				array('icon' => 'bi-file-arrow-down', 'class' => 'offset-sm-3')
				);
			  
			$formExportImport->addFileUpload('importfile', $gL10n->get('SYS_FILE').':', array('helpTextId' => 'PLG_FORMFILLER_IMPORT_DESC', 'allowedMimeTypes' => array('application/octet-stream,text/plain')));
				
			$sample_dir = 'en';
			if ($gSettingsManager->getString('system_language') === 'de' || $gSettingsManager->getString('system_language') === 'de-DE')
			{
				$sample_dir = 'de';
			}
			$cfgFiles = FileSystemUtils::getDirectoryContent(__DIR__.'/../examples/'.$sample_dir, false, true, array(FileSystemUtils::CONTENT_TYPE_FILE));
			
			//$cfgFiles aufbereiten für addSelectBox
			$selectBoxEntries = array();
			foreach ($cfgFiles as $cfgFile => $dummy)
			{
				$file = substr($cfgFile,strrpos($cfgFile,DIRECTORY_SEPARATOR)+1);
				$selectBoxEntries[$cfgFile] = $file;
			}
			$formExportImport->addSelectBox('cfgfile', $gL10n->get('PLG_FORMFILLER_SAMPLE_FILE').':', $selectBoxEntries, array( 'showContextDependentFirstEntry' => true));
			
			$formExportImport->addSubmitButton(
				'adm_button_import',
				$gL10n->get('PLG_FORMFILLER_IMPORT'),
				array('icon' => 'bi-file-arrow-up', 'class' => 'offset-sm-3')
				);

			$formExportImport->addToHtmlPage(false);
			
			$page->show();

			break;
		
		case 'export':
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
				elseif (substr($data, 0, 1) == 'b')
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
		
		case 'import':

			if ($postCfgFile !== '')
			{
				$parsedArray = parse_ini_file ($postCfgFile, TRUE );
			}
			else
			{
				if (!isset($_FILES['userfile']['name']))
				{
					//$gNavigation->clear();
					$gMessage->setForwardUrl($gNavigation->getPreviousUrl());
					$gMessage->show($gL10n->get('PLG_FORMFILLER_IMPORT_ERROR_OTHER'), $gL10n->get('SYS_ERROR'));	
				}
				elseif (strlen($_FILES['userfile']['name'][0]) === 0)
				{
					//$gNavigation->clear();
					$gMessage->setForwardUrl($gNavigation->getPreviousUrl());
					$gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('SYS_FILE'))));
				}
				elseif (strtolower(substr($_FILES['userfile']['name'][0],-4)) <> '.cfg')
				{
					//$gNavigation->clear();
					$gMessage->setForwardUrl($gNavigation->getPreviousUrl());
					$gMessage->show($gL10n->get('PLG_FORMFILLER_IMPORT_ERROR_FILE'), $gL10n->get('SYS_ERROR'));	
				}

				$parsedArray = parse_ini_file ( $_FILES['userfile']['tmp_name'][0], TRUE );
			}
		
			//pruefen, ob die eingelesene Datei eine Formularkonfiguration enthaelt
			if (	!(isset($parsedArray['desc']) && $parsedArray['desc'] <> '')
				||  !(isset($parsedArray['fields']) && is_array($parsedArray['fields']))
				||  !(isset($parsedArray['positions']) && is_array($parsedArray['positions']))
				||  !(isset($parsedArray['usf_name_intern']) && is_array($parsedArray['usf_name_intern']))
				||  !(count($parsedArray['fields']) == count($parsedArray['positions']))
				||  !(count($parsedArray['fields']) == count($parsedArray['usf_name_intern']))  )
			{
				//$gNavigation->clear();
				$gMessage->setForwardUrl($gNavigation->getPreviousUrl());
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
						if ($key == 'desc')
						{
							$importArray[$key] = $pPreferences->createDesc($parsedArray[$key]);
						}
						else
						{
							$importArray[$key] = $parsedArray[$key];
						}
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
			
				if ($fieldtype == 'p')                             //p wie (p)rofile-field
				{
					//u.U. wurde die einzulesende Konfiguration unter einer anderen Admidio-Installation erstellt,
					//deshalb prüfen, ob es ein Profilfeld mit diesem 'usf_name_intern' gibt
					if ($gProfileFields->getProperty($parsedArray['usf_name_intern'][$key], 'usf_id') > 0)
					{
						$importArray['fields'][$key] = 'p'.$gProfileFields->getProperty($parsedArray['usf_name_intern'][$key], 'usf_id');
					}
					else
					{
						continue;
					}	
				}
				elseif ($fieldtype == 'b')                         //b wie (b)eziehung (r für relation ist bereits vergeben)
				{
					//u.U. wurde die einzulesende Konfiguration unter einer anderen Admidio-Installation erstellt,
					//deshalb prüfen, ob es ein Profilfeld mit diesem 'usf_name_intern' gibt
					if ($gProfileFields->getProperty($parsedArray['usf_name_intern'][$key], 'usf_id') > 0)
					{
						$importArray['fields'][$key] = 'b'.$gProfileFields->getProperty($parsedArray['usf_name_intern'][$key], 'usf_id');
					}
					else
					{
						continue;
					}
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
			//nur hinzufügen, wenn es sich um eine Größe mit xy-Angaben handelt
			//nicht hinzufügen, wenn die Größe bereits vorhanden ist
			if ($importArray['pdfform_size'] <> '')
			{
				$addSize = explode(',',$importArray['pdfform_size']);
				
				if (count($addSize) == 2 && is_numeric($addSize[0]) && is_numeric($addSize[1])) 
				{
					$addSizeStr = $addSize[0].'x'.$addSize[1];
					if (strpos($pPreferences->config['Optionen']['pdfform_addsizes'], $addSizeStr) === false)
					{
						$pPreferences->config['Optionen']['pdfform_addsizes'] .= ';'.$addSizeStr;
					}
				}	
			}
			$pPreferences->save();

			$gMessage->show($gL10n->get('PLG_FORMFILLER_IMPORT_SUCCESS'));
			
			break;
	}

} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}