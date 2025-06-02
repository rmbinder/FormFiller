<?php
namespace Formfiller\Service;

use Admidio\Infrastructure\Exception;
use Formfiller\Config\ConfigTable;

/**
 * @brief Class with methods to display the module pages.
 *
 * This class adds some functions that are used in the preferences module to keep the
 * code easy to read and short
 * 
 * FormfillerPreferencesService is a modified (Admidio)PreferencesService
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
class FormfillerPreferencesService
{

    /**
     * Save all form data of the panel to the database.
     * @param string $panel Name of the panel for which the data should be saved.
     * @param array $formData All form data of the panel.
     * @return void
     * @throws Exception
     */
    public function save(string $panel, array $formData)
    {
        global $gL10n, $gSettingsManager, $gCurrentSession, $gDb, $gCurrentOrgId, $gProfileFields;
        
        require_once(__DIR__ . '/../../system/common_function.php');
        $pPreferences = new ConfigTable();
        $pPreferences->read();
        
        $result =  $gL10n->get('SYS_SAVE_DATA');

        // first check the fields of the submitted form
        switch ($panel) {
            
            case 'Options':

                $pPreferences->config['Optionen']['maxpdfview'] = $formData['maxpdfview'];
                $pPreferences->config['Optionen']['pdfform_addsizes'] = $formData['pdfform_addsizes'];
                $pPreferences->save();
                
                break;

            case 'Access':
                if (isset($formData['access_preferences']))
                {
                    $pPreferences->config['access']['preferences'] = array_values(array_filter($formData['access_preferences']));
                }
                else
                {
                    $pPreferences->config['access']['preferences'] = array();
                }
                $pPreferences->save();
                
                break;
            
            case 'Deinstallation':

                $result = $gL10n->get('PLG_FORMFILLER_DEINST_STARTMESSAGE').$pPreferences->delete($formData['deinst_org_select']) ;
                
                break;
            
            case 'Assort':
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
                
                $result = $gL10n->get('PLG_FORMFILLER_ASSORT_SUCCESS');

                break;
        }
       
        return $result;

        // clean up
        //$gCurrentSession->reloadAllSessions();
    }

}
