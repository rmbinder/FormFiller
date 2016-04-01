<?php
/******************************************************************************
 * FormFiller
 *
 * Version 2.0.1
 *
 * Dieses Plugin für Admidio ermöglicht das Ausfüllen von PDF-Formularen sowie das Erstellen von Etiketten.
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * Author		    : rmb 
 * 
 * Libraries 	  : FormFiller verwendet die externen PHP-Klassen FPDF, FPDI und FPDF_TPL
 *
 * Version		  : 2.0.1
 * Datum        : 02.11.2015
 * Änderung     : - Fehler (verursacht durch die Methode addHeadline) behoben
 * 
 * Version		  : 2.0.0
 * Datum        : 27.05.2015
 * Änderung     : - Anpassung an Admidio 3.0
 * 		            - Deinstallationsroutine erstellt
 *                - Verfahren zum Einbinden des Plugins (include) geändert 
 *                - Menübezeichnungen angepasst (gleichlautend mit anderen Plugins)  
 *                - Nur Intern: Verwaltung der Konfigurationsdaten geändert
 * 
 * Version      : 1.0.3
 * Datum        : 04.12.2014
 * Änderung     : Druckmöglichkeit von Profilfoto und aktuellem Datum
 * 
 * Version 		  : 1.0.2
 * Datum        : 07.05.2014
 * Änderung     : Erzeugung von Mehrfachdokumenten über neues Modul Listenwahl realisiert
 * 
 * Version 		  : 1.0.1 
 * Datum        : 30.04.2014
 * Änderung     : Aufruf des Plugins über die Klasse Menu realisiert
 * 				        (Systemanforderung jetzt Admidio Version 2.4.4 oder höher)
 * 
 * Version		  : 1.0.0
 * Datum        : 14.04.2014
 * 
 *****************************************************************************/

//$gNaviagation ist zwar definiert, aber in diesem Script in bestimmten Fällen nicht sichtbar
global $gNavigation;

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

$pPreferences = new ConfigTablePFF();

// DB auf Admidio setzen, da evtl. noch andere DBs beim User laufen
$gDb->setCurrentDB();

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');

//Initialisierung und Anzeige des Links nur, wenn vorher keine Deinstallation stattgefunden hat
// sonst wäre die Deinstallation hinfällig, da hier wieder Default-Werte der config in die DB geschrieben werden
if(  strpos($gNavigation->getUrl(), 'preferences_function.php?mode=3') === false)
{
	if ($pPreferences->checkforupdate())
	{
		$pPreferences->init();
	}
	else 
	{
		$pPreferences->read();
	}

	//$url und $user_id einlesen, falls von der Proilanzeige aufgerufen wurde
	$url = $_SERVER['REQUEST_URI'];
	$user_id = (isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : '');

	// Zeige Link zum Plugin
	if(check_showpluginPFF($pPreferences->config['Pluginfreigabe']['freigabe']) )
	{
		// wenn in der my_body_bottom.php ein $pluginMenu definiert wurde, dann innerhalb dieses Menüs anzeigen,
		// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
		if (isset($pluginMenu))
		{
			$menue=$pluginMenu;
		}
		else 
		{
			$menue=$moduleMenu;
		}

		$menue->addItem('formfiller_show', '/adm_plugins/'.$plugin_folder.'/formfiller_show.php',$gL10n->get('PFF_FORMFILLER'), '/icons/page_white_acrobat.png'); 
		if(strstr($url, 'adm_program/modules/profile/profile.php?user_id=')!=null )
		{
			foreach($pPreferences->config['Formular']['desc'] as $key => $data)
			{		
				$menue->addItem($data, '/adm_plugins/'.$plugin_folder.'/createpdf.php?user_id='.$user_id.'&form_id='.$key, '['.$data.']', '/icons/page_white_acrobat.png');
			}
		}
	}
}		

?>