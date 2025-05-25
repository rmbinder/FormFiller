<?php
/**
 ***********************************************************************************************
 * FormFiller
 *
 * Version 3.3.2
 * 
 * Dieses Plugin für Admidio ermoeglicht das Ausfuellen von PDF-Formularen sowie das Erstellen von Etiketten.
 *
 * Autor: rmb
 *
 * Hinweis: FormFiller verwendet die externen PHP-Klassen FPDF und FPDI
 *  
 * Compatible with Admidio version 4.3
 *
 * @copyright 2004-2025 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

use Formfiller\Config\ConfigTable;

//Fehlermeldungen anzeigen
error_reporting(E_ALL);

try {
    require_once(__DIR__ . '/../../system/common.php');
    require_once(__DIR__ . '/system/common_function.php');

    //$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/formfiller...
    //Prüfung werden die Einstellungen von 'Modulrechte' und 'Sichtbar für'              * verwendet, die im Modul Menü für dieses Plugin gesetzt wurden.          * @param   string  $scriptName   Der Scriptname des Plugins
    $scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

    // only authorized user are allowed to start this module
    if (!isUserAuthorized($scriptName)) 
    {
        throw new Exception('SYS_NO_RIGHTS');
    }
    else
    {
        // Konfiguration initialisieren
        $pPreferences = new ConfigTable();
        if ($pPreferences->checkforupdate())
        {
            $pPreferences->init();
        }
        
         admRedirect(ADMIDIO_URL . FOLDER_PLUGINS. PLUGIN_FOLDER . '/system/formfiller.php');
    }
    
                                      
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
