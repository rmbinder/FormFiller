<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin FormFiller
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
 
require_once(__DIR__ . '/../../adm_program/system/common.php');

$plugin_folder = '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1);

/**
 * Funktion liest die Role-ID einer Rolle aus
 * @param   string  $role_name Name der zu pruefenden Rolle
 * @return  int     rol_id
 */
function getRole_IDPFF($role_name)
{
    global $gDb, $gCurrentOrganization;
	
    $sql    = 'SELECT rol_id
                 FROM '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                 WHERE rol_name   = \''.$role_name.'\'
                 AND rol_valid  = 1 
                 AND rol_cat_id = cat_id
                 AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                 OR cat_org_id IS NULL ) ';
                      
    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();

    // für den seltenen Fall, dass waehrend des Betriebes die Sprache umgeschaltet wird:  $row->rol_id pruefen
    return (isset($row->rol_id) ?  $row->rol_id : 0);
}

/**
 * Funktion prueft, ob der Nutzer, aufgrund seiner Rollenzugehoerigkeit berechtigt ist das Plugin aufzurufen
 * @param   array  $array   Array mit Rollen-IDs:   entweder $pPreferences->config['Pluginfreigabe']['freigabe']
 *                                                  oder $pPreferences->config['Pluginfreigabe']['freigabe_config']
 * @return  bool   $showPlugin
 */
function check_showpluginPFF($array)
{
	global $gCurrentUser;
	
    $showPlugin = false;

    foreach ($array AS $i)
    {
        if($gCurrentUser ->isMemberOfRole($i))
        {
            $showPlugin = true;
        } 
    } 
    return $showPlugin;
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
	for ($i=0;$i<strlen($needle);$i++)
	{
		if (!(strstr($haystack,substr($needle,$i,1))))
		{
			return false;
		}
	}
	return true;
}
