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
 
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Menu\Entity\MenuEntry;
use Admidio\Roles\Entity\RolesRights;
use Plugins\FormFiller\classes\Config\ConfigTable;

if (basename($_SERVER['SCRIPT_FILENAME']) === 'common_function.php') {
    exit('This page may not be called directly!');
}

require_once(__DIR__ . '/../../../system/common.php');

$folders = explode('/', $_SERVER['SCRIPT_FILENAME']);
while (array_search(substr(FOLDER_PLUGINS, 1), $folders))
{
    array_shift($folders);
}
array_shift($folders);

if(!defined('PLUGIN_FOLDER'))
{
    define('PLUGIN_FOLDER', '/'.$folders[0]);
}
unset($folders);

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
    $prefix = 'Plugins\\';
    
    // Base-Directory für den Namespace-Prefix.
    $baseDir = __DIR__ . '/../../';
    
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
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin auszuführen.
 * 
 * In Admidio im Modul Menü kann über 'Sichtbar für' die Sichtbarkeit eines Menüpunkts eingeschränkt werden.
 * Der Zugriff auf die darunter liegende Seite ist von dieser Berechtigung jedoch nicht betroffen.
 * 
 * Mit Admidio 5 werden alle Startcripte meiner Plugins umbenannt zu index.php
 * Um die index.php auszuführen, kann die bei einem Menüpunkt angegebene URL wie folgt angegeben sein:
 * /adm_plugins/<Installationsordner des Plugins>
 *   oder
 * /adm_plugins/<Installationsordner des Plugins>/
 *   oder
 * /adm_plugins/<Installationsordner des Plugins>/<Dateiname.php>
 * 
 * Das Installationsscript des Plugins erstellt automatisch einen Menüpunkt in der Form: /adm_plugins/<Installationsordner des Plugins>/index.php
 * Standardmäßig wird deshalb für die Prüfung index.php als <Dateiname.php> verwendet, alternativ die übergebene Datei ($scriptname).
 * 
 * Diese Funktion ermittelt nur die Menüpunkte, die einen Dateinamen am Ende (index.php oder $scriptname) aufweisen, liest bei diesen Menüpunkten
 * die unter 'Sichtbar für' eingetragenen Rollen ein und prüft, ob der angemeldete Benutzer Mitglied mindestens einer dieser Rollen ist.
 * Wenn ja, ist der Benutzer berechtigt, das Plugin auszuführen (auch, wenn es weitere Menüpunkte ohne Dateinamen am Ende gibt).
 * Wichtiger Hinweis: Sind unter 'Sichtbar für' keine Rollen angegeben, so darf jeder Benutzer das Plugin ausführen
 * 
 * @param   string  $scriptName   Der Scriptname des Plugins (default: 'index.php')
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized( string $scriptname = '')
{
    global $gDb, $gCurrentUser;
    
    $userIsAuthorized = false;
    $menIds = array();
    
    $menuItemURL = FOLDER_PLUGINS. PLUGIN_FOLDER. '/'. ((strlen($scriptname) === 0) ? 'index.php' : $scriptname);
    
    $sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $menuItemURL';
    
    $menuStatement = $gDb->queryPrepared($sql, array($menuItemURL));
    
    if ( $menuStatement->rowCount() !== 0 )
    {
        while ($row = $menuStatement->fetch())
        {
            $menIds[] = (int) $row['men_id'];
        }
        
        foreach ($menIds as $menId)
        {
            // read current roles rights of the menu
            $displayMenu = new RolesRights($gDb, 'menu_view', $menId);
            
            // check for right to show the menu
            if (count($displayMenu->getRolesIds()) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships()))
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
    global $gCurrentUser;
        
    // Konfiguration einlesen
    $pPreferences = new ConfigTable();
    $pPreferences->read();
    
    $userIsAuthorized = false;
    
    if ($gCurrentUser->isAdministrator())                   // Mitglieder der Rolle Administrator dürfen "Preferences" immer aufrufen
    {
        $userIsAuthorized = true;
    }
    else
    {
        foreach ($pPreferences->config['access']['preferences'] as $roleId)
        {
            if ($gCurrentUser->isMemberOfRole((int) $roleId))
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


