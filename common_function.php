<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin FormFiller
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
 
require_once(__DIR__ . '/../../adm_program/system/common.php');

if(!defined('PLUGIN_FOLDER'))
{
	define('PLUGIN_FOLDER', '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1));
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
	global $gMessage;
	
	$userIsAuthorized = false;
	$menId = 0;
	
	$sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
	
	$menuStatement = $GLOBALS['gDb']->queryPrepared($sql, array($scriptName));
	
	if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
	{
		$GLOBALS['gLogger']->notice('FormFiller: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
		$GLOBALS['gLogger']->notice('FormFiller: Error with menu entry: ScriptName: '. $scriptName);
		$gMessage->show($GLOBALS['gL10n']->get('PLG_FORMFILLER_MENU_URL_ERROR', array($scriptName)), $GLOBALS['gL10n']->get('SYS_ERROR'));
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
	
	$menuStatement = $GLOBALS['gDb']->queryPrepared($sql, array($menId));
	while ($row = $menuStatement->fetch())
	{
		if ((int) $row['men_com_id'] === 0 || Component::isVisible($row['com_name_intern']))
		{
			// Read current roles rights of the menu
			$displayMenu = new RolesRights($GLOBALS['gDb'], 'menu_view', $row['men_id']);
			$rolesDisplayRight = $displayMenu->getRolesIds();
			
			// check for right to show the menu
			if (count($rolesDisplayRight) === 0 || $displayMenu->hasRight($GLOBALS['gCurrentUser']->getRoleMemberships()))
			{
				$userIsAuthorized = true;
			}
		}
	}
	return $userIsAuthorized;
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist, das Modul Preferences aufzurufen.
 * @param   none
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorizedForPreferences()
{
    global $pPreferences;
    
    $userIsAuthorized = false;
    
    if ($GLOBALS['gCurrentUser']->isAdministrator())                   // Mitglieder der Rolle Administrator dürfen "Preferences" immer aufrufen
    {
        $userIsAuthorized = true;
    }
    else
    {
        foreach ($pPreferences->config['access']['preferences'] as $roleId)
        {
            if ($GLOBALS['gCurrentUser']->isMemberOfRole((int) $roleId))
            {
                $userIsAuthorized = true;
                continue;
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
    global $pPreferences;
   
    while (in_array($name, $pPreferences->config['Formular']['desc']))
    {
        $name .= ' - '.$GLOBALS['gL10n']->get('SYS_CARBON_COPY');
    }
    
    return $name;
}

/**
 * Funktion erstellt ein Array mit den Kernschriftarten (Core Fonts)
 * @param   none
 * @return  array $fonts
 */
function coreFonts()
{
    //core fonts
    $fonts = array(
        'Courier'=>'Courier',
        'Arial'=>'Arial',
        'Times'=>'Times',
        'Symbol'=>'Symbol',
        'ZapfDingbats'=>'ZapfDingbats');
       
    return $fonts;
}


/**
 * Funktion erstellt die Schriftarten-Auswahlliste (core fonts + add fonts)
 * @param   none
 * @return  array $fonts
 */
function validFonts()
{
    //core fonts
    $fonts = coreFonts();
    
    //additional fonts
    $fonts['AlteDIN1451Mittelschrift'] = 'AlteDIN1451Mittelschrift';
    $fonts['AlteDIN1451Mittelschrift-Geprägt'] = 'AlteDIN1451Mittelschrift-Geprägt';
    $fonts['Angelina'] = 'Angelina';                     
    $fonts['AsphaltFixed-Italic'] = 'AsphaltFixed-Italic';
    $fonts['Calligraph'] = 'Calligraph';
    $fonts['ClarendonBT-Roman'] = 'ClarendonBT-Roman';
    $fonts['Edo'] = 'Edo';
    $fonts['Exmouth'] = 'Exmouth';
    $fonts['FreebooterScript'] = 'FreebooterScript';
    $fonts['FuturaBT-Medium'] = 'FuturaBT-Medium';    
    $fonts['LeipzigFraktur'] = 'LeipzigFraktur';
    $fonts['LeipzigFraktur-Bold'] = 'LeipzigFraktur-Bold';   
    $fonts['PlainBlack'] = 'PlainBlack';
    $fonts['PlainGermanica'] = 'PlainGermanica';
    $fonts['Scriptina'] = 'Scriptina';
    $fonts['ShadowedBlack'] = 'ShadowedBlack';
    $fonts['ShadowedGermanica'] = 'ShadowedGermanica';
    $fonts['Barcode'] = 'Barcode';
    
    return $fonts;
}

/**
 * in_array function variant that performs case-insensitive comparison when needle is a string.
 *
 * @param mixed $needle
 * @param array $haystack
 * @param bool $strict
 *
 * @return bool
 */
function in_arrayi($needle, array $haystack, bool $strict = false): bool
{
    if (is_string($needle)) 
    {
        $needle = strtolower($needle);
        
        foreach ($haystack as $value)
        {
            if (is_string($value)) 
            {
                if (strtolower($value) === $needle) 
                {
                    return true;
                }
            }
        }
        return false;
    }
    return in_array($needle, $haystack, $strict);
}

/**
 * Funktion splittet Text (Satz oder Adresse) in mehrere Teile gleicher/maximaler Länge auf
 *
 * @param string    $string
 * @param int       $maxLength (maximale Anzahl der Zeichen)
 *
 * @return array
 */
function strToFormattedArray($string, $maxLength)
{
    $retArr = array(''); 
    $strArr = explode(' ', $string);
    
    if (count($strArr) === 1)                   //$string wird komplett zurückgegeben, da keine ' ' enthalten sind
    {
        $retArr = $strArr; 
    }
    else 
    {
        foreach ($strArr as $str )
        {
            $last_key = count($retArr) - 1;
        
            if ((strlen($retArr[$last_key]) + 1 + strlen($str)) < $maxLength)
            {
                $retArr[$last_key] .= $str.' ';
            }
            else
            {
                $retArr[$last_key] = rtrim($retArr[$last_key]);
                $retArr[] = $str.' ';
            }        
        }
        $retArr[$last_key] = rtrim($retArr[$last_key]);
        
        //wenn das erste Wort bereits länger als $maxLength war, dann ist das erste Arrayelement leer (-> löschen).
        $retArr = array_filter($retArr);
    }
    return $retArr;
}
