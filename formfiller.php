<?php
/**
 ***********************************************************************************************
 * FormFiller
 *
 * Version 2.2.0
 *
 * Dieses Plugin für Admidio ermöglicht das Ausfuellen von PDF-Formularen sowie das Erstellen von Etiketten.
 *
 * Author: rmb
 *
 * Hinweis: FormFiller verwendet die externen PHP-Klassen FPDF, FPDI und FPDF_TPL 
 *  
 * Compatible with Admidio version 3.2
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

//$gNaviagation ist zwar definiert, aber in diesem Script nicht immer sichtbar
global $gNavigation;

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$plugin_folder = '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1);

// Einbinden der Sprachdatei
$gL10n->addLanguagePath(ADMIDIO_PATH . FOLDER_PLUGINS . $plugin_folder . '/languages');

$pPreferences = new ConfigTablePFF();

//Initialisierung und Anzeige des Links nur, wenn vorher keine Deinstallation stattgefunden hat
// sonst waere die Deinstallation hinfaellig, da hier wieder Default-Werte der config in die DB geschrieben werden
if (strpos($gNavigation->getUrl(), 'preferences_function.php?mode=3') === false)
{
	if ($pPreferences->checkforupdate())
	{
		$pPreferences->init();
	}
	else 
	{
		$pPreferences->read();
	}

	//$url und $user_id einlesen, falls von der Profilanzeige aufgerufen wurde
	$url = $_SERVER['REQUEST_URI'];
	$user_id = (isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : '');

	// Zeige Link zum Plugin
	if (check_showpluginPFF($pPreferences->config['Pluginfreigabe']['freigabe']) )
	{
		// wenn in der my_body_bottom.php ein $pluginMenu definiert wurde, dann innerhalb dieses Menues anzeigen,
		// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
		if (isset($pluginMenu))
		{
			$menue = $pluginMenu;
		}
		else 
		{
			$menue = $moduleMenu;
		}

		$menue->addItem('formfiller_show', FOLDER_PLUGINS . $plugin_folder .'/formfiller_show.php',$gL10n->get('PLG_FORMFILLER_FORMFILLER'), '/icons/page_white_acrobat.png');
		if (strstr($url, 'adm_program/modules/profile/profile.php?user_id=') != null )
		{
			foreach ($pPreferences->config['Formular']['desc'] as $key => $data)
			{		
				$menue->addItem($data, FOLDER_PLUGINS . $plugin_folder .'/createpdf.php?user_id='.$user_id.'&form_id='.$key, '['.$data.']', '/icons/page_white_acrobat.png');
			}
		}
	}
}		
