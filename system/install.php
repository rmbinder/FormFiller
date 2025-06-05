<?php
/**
 ***********************************************************************************************
 * Installation routine for the Admidio plugin Formfiller
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:  none
 *
 ***********************************************************************************************
 */


use Admidio\Categories\Entity\Category;
use Admidio\Menu\Entity\MenuEntry;
use Admidio\Roles\Entity\Role;
use Admidio\Roles\Entity\RolesRights;
use Formfiller\Config\ConfigTable;

try {
    require_once(__DIR__ . '/../../../system/common.php');
    require_once(__DIR__ . '/common_function.php');
    
    // only administrators are allowed to start this module
    if (!$gCurrentUser->isAdministrator())          
    {
        //throw new Exception('SYS_NO_RIGHTS');                     // über Exception wird nur SYS_NO_RIGHTS angezeigt
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }
    else
    {
        // Menüpunkt erzeugen, wenn keiner vorhanden ist
        if (!isMenuItem())
        {
            addMenuItem();
        }
        
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

// Funktionen, die nur in diesem Script benoetigt werden

/**
 * Creates a menu item in the extensions menu
 * @return  void
 */
function addMenuItem() 
{   
    global $gDb, $gCurrentUser, $gL10n, $gCurrentOrgId, $gCurrentSession;
    
    // im ersten Schritt eine neue Rolle in der Kategorie Allgemein (Common) anlegen
    
    // dazu zuerst die Id (cat_id) der Kategorie Allgemein ermitteln
    $category = new Category($gDb);
    $category->readDataByColumns(array('cat_org_id' => $gCurrentOrgId, 'cat_type' => 'ROL', 'cat_name_intern' => 'COMMON'));
    $categoryCommonId = $category->getValue('cat_id');
    
    // danach die Rolle anlegen
    $role = new Role($gDb);

    // für den Fall, dass beim letzten Uninstall zwar der Menüpunkt entfernt wurde, aber vergessen wurde, die Rolle auch zu entfernen: diese Rolle einlesen
    $role->readDataByColumns(array('rol_cat_id' => $categoryCommonId, 'rol_name' => $gL10n->get('PLG_FORMFILLER_MENU_ITEM'), 'rol_description' => $gL10n->get('PLG_FORMFILLER_MENU_ITEM_DESC')));

    $role->saveChangesWithoutRights();                                          // toDo: ggf. Berechtigungen für die Rolle vergeben
    $role->setValue('rol_cat_id', $categoryCommonId, false);
    $role->setValue('rol_name', $gL10n->get('PLG_FORMFILLER_MENU_ITEM'));
    $role->setValue('rol_description', $gL10n->get('PLG_FORMFILLER_MENU_ITEM_DESC'));
    $role-> save();
    
    // und den aktuellen Benutzer dieser Rolle hinzufügen
    $role->startMembership((int) $gCurrentUser->getValue('usr_id'));
    $role->save();
    
    // danach einen neuen Menüeintrag in der Menüebene Erweiterungen (Extensions) anlegen
    
    // dazu zuerst die Id (men_id) der Menüebene Erweiterungen ermitteln
    $menuParent = new MenuEntry($gDb);
    $menuParent->readDataByColumns(array('men_name_intern' => 'extensions'));
    $menIdParent = $menuParent->getValue('men_id');
    
    // danach den Menüeintrag anlegen
    $menu = new MenuEntry($gDb);
    $menu->setValue('men_men_id_parent', $menIdParent);
    $menu->setValue('men_url', FOLDER_PLUGINS. PLUGIN_FOLDER .'/index.php');
    $menu->setValue('men_icon', 'pen');
    $menu->setValue('men_name', 'PLG_FORMFILLER_NAME');
    $menu->setValue('men_description', 'PLG_FORMFILLER_NAME_DESC');
    $menu->save();
    
    // diesen Menüeintrag nur für die vorher angelegte Rolle freischalten ('Sichtbar für')
    $rightMenuView = new RolesRights($gDb, 'menu_view', $menu->getValue('men_id'));
    $rightMenuView->saveRoles(array($role->getValue('rol_id')));
    
    // damit am Bildschirm die Menüeinträge aktualisiert werden: alle Sesssions neu laden
    $gCurrentSession->reloadAllSessions();
}

/**
 * Checks whether a menu item already exists
 * @return bool Return true if menu item exists
 */
function isMenuItem()
{
    global $gDb;
    
    $menu = new MenuEntry($gDb);
    $menu->readDataByColumns(array('men_url' => FOLDER_PLUGINS. PLUGIN_FOLDER .'/index.php'));
        
    if ($menu->getValue('men_id') === 0)
    {
        return false;
    }
    else
    {
        return true;
    }
}
