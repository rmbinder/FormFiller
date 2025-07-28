<?php
/**
 ***********************************************************************************************
 * Common functions for the admidio plugin FormFiller
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
 
use Admidio\Components\Entity\Component;
use Admidio\Menu\Entity\MenuEntry;
use Admidio\Roles\Entity\RolesRights;

require_once(__DIR__ . '/../../../system/common.php');

if(!defined('PLUGIN_FOLDER'))
{
	define('PLUGIN_FOLDER', '/'.substr(dirname(__DIR__),strrpos(dirname(__DIR__),DIRECTORY_SEPARATOR)+1));
}

spl_autoload_register('myAutoloader');

/**
 * Mein Autoloader
 * Script aus dem Netz
 * https://www.marcosimbuerger.ch/tech-blog/php-autoloader.html
 * @param   string  $className   Die übergebene Klasse
 * @return  string  Der überprüfte Klassenname
 */
function myAutoloader($className) {
    // Projekt spezifischer Namespace-Prefix.
    $prefix = 'Formfiller\\';
    
    // Base-Directory für den Namespace-Prefix.
    $baseDir = __DIR__ . '/../classes/';
    
    // Check, ob die Klasse den Namespace-Prefix verwendet.
    $len = strlen($prefix);
    
    if (strncmp($prefix, $className, $len) !== 0) {
        // Wenn der Namespace-Prefix nicht verwendet wird, wird abgebrochen.
        return;
    }
    // Den relativen Klassennamen ermitteln.
    $relativeClassName = substr($className, $len);
    
    // Den Namespace-Präfix mit dem Base-Directory ergänzen,
    // Namespace-Trennzeichen durch Verzeichnis-Trennzeichen im relativen Klassennamen ersetzen,
    // .php anhängen.
    $file = $baseDir . str_replace('\\', '/', $relativeClassName) . '.php';
    // Pfad zur Klassen-Datei zurückgeben.
    if (file_exists($file)) {
        require $file;
    }
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung wird die Einstellung von 'Sichtbar für' verwendet,
 * die im Modul Menü für dieses Plugin gesetzt wurde.
 * @param   string  $scriptName   Der Scriptname des Plugins
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
    global $gDb, $gMessage, $gLogger, $gL10n, $gCurrentUser;
    
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
    
    // read current roles rights of the menu
    $displayMenu = new RolesRights($gDb, 'menu_view', $menId);
    
    // check for right to show the menu
    if (count($displayMenu->getRolesIds()) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships()))
    {
        $userIsAuthorized = true;
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


