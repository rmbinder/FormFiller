<?php
/**
 ***********************************************************************************************
 * Formfiller preferences
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode     : html           - (default) Show page with all preferences panels
 *            html_form      - Returns the html of the requested form
 *            save           - Save organization preferences
 * panel    : The name of the preferences panel that should be shown or saved.
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Exception;
use Presenter\FormfillerPreferencesPresenter;
use Service\FormfillerPreferencesService;

spl_autoload_register();

try {
    require_once(__DIR__ . '/../../../system/common.php');
  //  require_once(__DIR__ . '/../../../system/login_valid.php');
    require_once(__DIR__ . '/../src/Presenter/FormfillerPreferencesPresenter.php');
    require_once(__DIR__ . '/../src/Service/FormfillerPreferencesService.php');
  

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string',
        array(
            'defaultValue' => 'html',
            'validValues' => array('html', 'html_form', 'save', 'htaccess')
  //          'validValues' => array('html', 'html_form', 'save', 'htaccess', 'test_email', 'backup')
        ));
    $getPanel = admFuncVariableIsValid($_GET, 'panel', 'string');

    // only administrators are allowed to view, edit organization preferences or create new organizations
    if (!$gCurrentUser->isAdministrator()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    switch ($getMode) {
        case 'html':
            // create html page object
            $page = new FormfillerPreferencesPresenter($getPanel);

            $gNavigation->addUrl(CURRENT_URL, $page->getHeadline());

            $page->show();
            break;
            
        case 'save':
            $preferences = new FormfillerPreferencesService();
            $result = $preferences->save($getPanel, $_POST);
           
           // echo json_encode(array('status' => 'success', 'message' => $gL10n->get('SYS_SAVE_DATA')));
            echo json_encode(array('status' => 'success', 'message' => $result));
            
            
 
            break;
            
        // Returns the html of the requested form
        case 'html_form':
            $preferencesUI = new FormfillerPreferencesPresenter('adm_preferences_form');
            $methodName = 'create' . str_replace('_', '', ucwords($getPanel, '_')) . 'Form';
            echo $preferencesUI->{$methodName}();
            break;

    }
} catch (Throwable $exception) {
    if (in_array($getMode, array('save', 'new_org_create'))) {
        echo json_encode(array('status' => 'error', 'message' => $exception->getMessage()));
    } elseif ($getMode === 'html_form') {
        echo $exception->getMessage();
    } else {
        $gMessage->show($exception->getMessage());
    }
}
