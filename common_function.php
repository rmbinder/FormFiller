<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin FormFiller
 *
 * @copyright 2004-2021 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
 
require_once(__DIR__ . '/../../adm_program/system/common.php');

if(!defined('PLUGIN_FOLDER'))
{
	define('PLUGIN_FOLDER', '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1));
}

if(!defined('ORG_ID'))
{
	define('ORG_ID', (int) $gCurrentOrganization->getValue('org_id'));
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung werden die Einstellungen von 'Modulrechte' und 'Sichtbar für'
 * verwendet, die im Modul Menü für dieses Plugin gesetzt wurden.
 * @param   string  $scriptName   Der Scriptname des Plugins
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
	global $gDb, $gCurrentUser, $gMessage, $gL10n, $gLogger;
	
	$userIsAuthorized = false;
	$menId = 0;
	
	$sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
	
	$menuStatement = $gDb->queryPrepared($sql, array($scriptName));
	
	if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
	{
		$gLogger->notice('FormFiller: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
		$gLogger->notice('FormFiller: Error with menu entry: ScriptName: '. $scriptName);
		$gMessage->show($gL10n->get('PLG_FORMFILLER_MENU_URL_ERROR', array($scriptName)), $gL10n->get('SYS_ERROR'));
	}
	else
	{
		while ($row = $menuStatement->fetch())
		{
			$menId = (int) $row['men_id'];
		}
	}
	
	$sql = 'SELECT men_id, men_com_id, com_name_intern
              FROM '.TBL_MENU.'
         LEFT JOIN '.TBL_COMPONENTS.'
                ON com_id = men_com_id
             WHERE men_id = ? -- $menId
          ORDER BY men_men_id_parent DESC, men_order';
	
	$menuStatement = $gDb->queryPrepared($sql, array($menId));
	while ($row = $menuStatement->fetch())
	{
		if ((int) $row['men_com_id'] === 0 || Component::isVisible($row['com_name_intern']))
		{
			// Read current roles rights of the menu
			$displayMenu = new RolesRights($gDb, 'menu_view', $row['men_id']);
			$rolesDisplayRight = $displayMenu->getRolesIds();
			
			// check for right to show the menu
			if (count($rolesDisplayRight) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships()))
			{
				$userIsAuthorized = true;
			}
		}
	}
	return $userIsAuthorized;
}

/**
 * Funktion prueft, ob jeder einzelne Wert von $needle in $haystack enthalten ist
 *
 * @param   string  $haystack
 * @param   string  $needle
 * @return  bool
 */
function strstr_multiple($haystack, $needle )
{
	for ($i = 0; $i < strlen($needle); $i++)
	{
		if (!(strstr($haystack, substr($needle, $i, 1))))
		{
			return false;
		}
	}
	return true;
}

/**
 * Funktion prüft, ob es eine Konfiguration mit dem übergebenen Namen bereits gibt
 * wenn ja: wird "- Kopie" angehängt und rekursiv überprüft
 * @param   string  $name
 * @return  string
 */
function createDesc($name)
{
    global $pPreferences, $gL10n;
   
    while (in_array($name, $pPreferences->config['Formular']['desc']))
    {
        $name .= ' - '.$gL10n->get('MAI_CARBON_COPY');
    }
    
    return $name;
}
