<?php
/**
 ***********************************************************************************************
 * Sortieren von Konfigurationen des Admidio-Plugins FormFiller
 *
 * @copyright 2004-2024 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:      none
 *
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePFF();
$pPreferences->read();

// only authorized user are allowed to start this module
if (!isUserAuthorizedForPreferences())
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

foreach ($pPreferences->config['Formular']['positions'] as $positionsKey => $positionsArray)
{
    $xyKoord = array();
    $xyKoordAll = array();
    
    foreach ($positionsArray as $positionsArrayKey => $positionsData)
    {
        //$positionsData splitten in Koordinaten und Rest
        $arrSplit = explode(';', $positionsData);
        
        // xyKoordinaten extrahieren und für eine Sortierung aufbereiten (sortiert wird zuerst nach Y-, dann nach X-Koordinaten)
        // um eventuelle Dezimalzahlen (z.B. 45.5) verarbeiten zu können, werden alle Werte mit 1000000 multipliziert
        // die XY-Koordinate 25,45.5 wird zu: 45500000000000025000000
        $xyKoord = explode(',', array_shift($arrSplit)); 
        $xyKoordAll[] = ($xyKoord[1]* 1000000).str_pad(($xyKoord[0]* 1000000), 15, '0', STR_PAD_LEFT);
    }
  
    // jetzt die Positionen und Felder neu sortieren
    array_multisort($xyKoordAll, SORT_NUMERIC, $pPreferences->config['Formular']['positions'][$positionsKey], $pPreferences->config['Formular']['fields'][$positionsKey]);
}

// die Konfigurationen nach 'Beschreibung' ('desc') sortieren
array_multisort($pPreferences->config['Formular']['desc'], SORT_ASC,$pPreferences->config['Formular']['font'], 
                                                                    $pPreferences->config['Formular']['style'],
                                                                    $pPreferences->config['Formular']['size'],
                                                                    $pPreferences->config['Formular']['color'],
                                                                    $pPreferences->config['Formular']['labels'],
                                                                    $pPreferences->config['Formular']['fields'],
                                                                    $pPreferences->config['Formular']['positions'],
                                                                    $pPreferences->config['Formular']['pdfid'],
                                                                    $pPreferences->config['Formular']['pdfform_orientation'],
                                                                    $pPreferences->config['Formular']['pdfform_size'],
                                                                    $pPreferences->config['Formular']['pdfform_unit'],
                                                                    $pPreferences->config['Formular']['relation'] );

$pPreferences->save();

$gMessage->show($gL10n->get('PLG_FORMFILLER_ASSORT_SUCCESS'));

   		
