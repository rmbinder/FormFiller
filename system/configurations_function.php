<?php
/**
 ***********************************************************************************************
 * Preferences functions for the admidio plugin Formfiller
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * form     - The name of the form preferences that were submitted.
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Formfiller\Config\ConfigTable;

try {
    require_once(__DIR__ . '/../../../system/common.php');
    require_once(__DIR__ . '/common_function.php');
    
   // only authorized user are allowed to start this module
    if (!isUserAuthorizedForPreferences())
    {
        throw new Exception('SYS_NO_RIGHTS');
    }

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    // Initialize and check the parameters
    $getForm = admFuncVariableIsValid($_GET, 'form', 'string');

    switch ($getForm) {
        case 'configurations':
        
					unset($pPreferences->config['Formular']);
				
    				for ($conf = 0; isset($_POST['desc'. $conf]); $conf++)
    				{  				
        				$pPreferences->config['Formular']['desc'][] = $_POST['desc'. $conf];
    					$pPreferences->config['Formular']['font'][] = $_POST['font'. $conf];
    					$pPreferences->config['Formular']['style'][] = $_POST['style'. $conf];
    					$pPreferences->config['Formular']['size'][] = $_POST['size'. $conf];
    					$pPreferences->config['Formular']['color'][] = $_POST['color'. $conf];
    					$pPreferences->config['Formular']['labels'][] = $_POST['labels'. $conf];
    					$pPreferences->config['Formular']['pdfform_orientation'][] = $_POST['pdfform_orientation'. $conf];
    					$pPreferences->config['Formular']['pdfform_size'][] = $_POST['pdfform_size'. $conf];
    					$pPreferences->config['Formular']['pdfform_unit'][] = $_POST['pdfform_unit'. $conf];
    					$pPreferences->config['Formular']['pdfid'][] = (isset($_POST['pdfid'. $conf]) ? $_POST['pdfid'. $conf] : 0);
    					$pPreferences->config['Formular']['relation'][] = (isset($_POST['relationtype_id'. $conf]) ? $_POST['relationtype_id'. $conf] : '');
    				
    					$allColumnsEmpty = true;

    					$fields = array();
    					$positions = array();
    					for ($number = 1; isset($_POST['column'.$conf.'_'.$number]); $number++)
    					{
        					if (strlen($_POST['column'.$conf.'_'.$number]) > 0 && strlen($_POST['position'.$conf.'_'.$number]) > 0)
        					{
        						$allColumnsEmpty = false;
            					$fields[] = $_POST['column'.$conf.'_'.$number];
            					
            					//einfache und doppelte Anf�hrungszeichen in den entsprechenden HTML-Code umwandeln 
            					//(Begr.: ansonsten Fehler im Modul preferences, im JavaScript-Code und bei Export)
            					$positions[] = htmlentities($_POST['position'.$conf.'_'.$number], ENT_QUOTES);  
        					}
    					}
    			
    					if ($allColumnsEmpty)
    					{
    						$gMessage->show($gL10n->get('PLG_FORMFILLER_ERROR_MIN_DATA'));
    					}
    					$pPreferences->config['Formular']['fields'][] = $fields;	
    					$pPreferences->config['Formular']['positions'][] = $positions;		
    				}
                    
                    $pPreferences->save();

            echo json_encode(array('status' => 'success', 'message' => $gL10n->get('SYS_SAVE_DATA')));
            
            break;

        default:
            throw new Exception('SYS_INVALID_PAGE_VIEW');
    }
} catch (Exception $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
