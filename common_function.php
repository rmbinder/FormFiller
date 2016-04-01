<?php
/******************************************************************************
 * 
 * common_function.php
 *   
 * Gemeinsame Funktionen fuer das Admidio-Plugin FormFiller
 * 
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 ****************************************************************************/

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);
 
require_once($plugin_path. '/../adm_program/system/common.php');

// Funktion liest die Role-ID einer Rolle aus
// $role_name - Name der zu pruefenden Rolle
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
                      
    $result = $gDb->query($sql);
    $row = $gDb->fetch_object($result);

    // für den seltenen Fall, dass während des Betriebes die Sprache umgeschaltet wird:  $row->rol_id prüfen
    return (isset($row->rol_id) ?  $row->rol_id : 0);
}

// Funktion prueft, ob der Nutzer, aufgrund seiner Rollenzugehörigkeit, berechtigt ist das Plugin aufzurufen
// Parameter: Array mit Rollen-IDs: entweder $pPreferences->config['Pluginfreigabe']['freigabe']
//      oder $pPreferences->config['Pluginfreigabe']['freigabe_config']
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

// Funktion überprüft, ob jeder einzelne Wert von $needle in $heystack enthalten ist
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
 
?>