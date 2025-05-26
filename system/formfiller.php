<?php
/**
 ***********************************************************************************************
 * write-pdf-with-presenter
 *
 * 
 * Testscript um eine PDF-Datei zu erzeugen. Der Link dazu wird �ber presenter generiert.
 *
 * Autor: rmb
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Language;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Formfiller\Config\ConfigTable;
 
try {
        require_once(__DIR__ . '/../../../system/common.php');
        require_once(__DIR__ . '/../system/common_function.php');

       // Konfiguration einlesen          
        $pPreferences = new ConfigTable();
	    $pPreferences->read();
	    
        $title = $gL10n->get('PLG_FORMFILLER_FORMFILLER');
        $headline =$gL10n->get('PLG_FORMFILLER_FORMFILLER');

        $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-list-stars');

        // create html page object
        $page = PagePresenter::withHtmlIDAndHeadline('plg-formfiller-main-html');
        $page->setTitle($title);
        $page->setHeadline($headline);
    
        if (isUserAuthorizedForPreferences())
        {
            // show link to pluginpreferences
            $page->addPageFunctionsMenuItem(
                'admMenuItemPreferencesLists',
                $gL10n->get('SYS_SETTINGS'),
                SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/system/preferences.php'),
                'bi-plus-circle-fill');
        }

        // create filter menu with elements for role
        $form = new FormPresenter(
            'formfiller_form',
            '../templates/formfiller.form.tpl',
            SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS .'/formfiller/system/createpdf.php'),
            $page,
            array( 'type' => 'default' , 'method' => 'post',  'setFocus' => false,  'enableFileUpload' => true)
        );

          $sql = 'SELECT lst_id, lst_name, lst_global
		  FROM '. TBL_LISTS .'
         WHERE lst_org_id = ?
           AND ( lst_usr_id = ?
            OR lst_global = true)
           AND lst_name IS NOT NULL
      ORDER BY lst_global ASC, lst_name ASC';
    
    $statement = $gDb->queryPrepared($sql, array($gCurrentOrgId, $gCurrentUserId));
    $configurations = array();
    
    if ($statement->rowCount() > 0)
    {
        while ($row = $statement->fetch())
        {
            $configurations[] = array($row['lst_id'],$row['lst_name'],($row['lst_global'] == 0 ? $gL10n->get('SYS_YOUR_LISTS') : $gL10n->get('SYS_GENERAL_LISTS') ));
        }
    }
    $form->addSelectBox('lst_id', $gL10n->get('SYS_CONFIGURATION_LIST'), $configurations, array( 'showContextDependentFirstEntry' => true));
    
    $roles = array();
    $rolesNonEvents = array();
    $rolesEvents = array();
    
    // alle Rollen au?er Events
    $sql = 'SELECT rol.rol_uuid, rol.rol_name, cat.cat_name
                  FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
                 WHERE cat.cat_id = rol.rol_cat_id
                   AND (  cat.cat_org_id = ?
                    OR cat.cat_org_id IS NULL )
                   AND cat.cat_name_intern <> ? ';
    
    $statement = $gDb->queryPrepared($sql, array($gCurrentOrgId, 'EVENTS'));
    
    while ($row = $statement->fetch())
    {
        $row['cat_name'] = Language::translateIfTranslationStrId($row['cat_name']);
        $rolesNonEvents[] = array($row['rol_uuid'], $row['rol_name'], $row['cat_name'] );
    }
    
    $sortFirst  = array();
    $sortSecond = array();
    foreach ($rolesNonEvents as $key => $row)
    {
        $sortFirst[$key] = $row[2];
        $sortSecond[$key] = $row[1];
    }
    array_multisort($sortFirst, SORT_ASC, $sortSecond, SORT_ASC, $rolesNonEvents);
    
    // alle Events
    $sql = 'SELECT rol.rol_uuid, rol.rol_name, cat.cat_name
          FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
         WHERE cat.cat_id = rol.rol_cat_id
           AND (  cat.cat_org_id = ?
            OR cat.cat_org_id IS NULL )
           AND cat.cat_name_intern = ? ';
    
    $statement = $gDb->queryPrepared($sql, array($gCurrentOrgId, 'EVENTS'));
    
    while ($row = $statement->fetch())
    {
        $row['cat_name'] = Language::translateIfTranslationStrId($row['cat_name']);
        $rolesEvents[] = array($row['rol_uuid'], $row['rol_name'], $row['cat_name'] );
    }
    
    $sortFirst  = array();
    $sortSecond = array();
    $sortThird  = array();
    $sortFourth = array();
    foreach ($rolesEvents as $key => $row)
    {
        $sortFirst[$key]  = substr($row[1], 6, 4);               // Jahr
        $sortSecond[$key] = substr($row[1], 3, 2);               // Monat
        $sortThird[$key]  = substr($row[1], 0, 2);               // Tag
        $sortFourth[$key] = 0;
        if (is_numeric(substr($row[1], 22, 1)))                  // wenn es kein Ganztagestermin ist, beginnt an Position 22 die Uhrzeit
        {
            $sortFourth[$key] = str_replace(':', '', substr($row[1], 22, 5));
        }
    }
    array_multisort($sortFirst, SORT_NUMERIC, $sortSecond, SORT_NUMERIC, $sortThird, SORT_NUMERIC, $sortFourth, SORT_NUMERIC, $rolesEvents);
    $roles = array_merge($rolesNonEvents, $rolesEvents);
    
    $form->addSelectBox('rol_uuid', $gL10n->get('SYS_ROLES'), $roles, array( 'multiselect' => true));
    $form->addSelectBox('rol_uuid_exclusion', $gL10n->get('PLG_FORMFILLER_ROLE_EXCLUSION'), $roles, array( 'multiselect' => true));
    $form->addCheckbox('show_former_members', $gL10n->get('PLG_FORMFILLER_FORMER_MEMBERS_ONLY'));
    
    $sqlData['query']= 'SELECT DISTINCT
		usr_id, CONCAT(last_name.usd_value, \' \', first_name.usd_value) AS name, SUBSTRING(last_name.usd_value,1,1) AS letter
              FROM '.TBL_MEMBERS.'
        INNER JOIN '.TBL_ROLES.'
                ON rol_id = mem_rol_id
        INNER JOIN '.TBL_CATEGORIES.'
                ON cat_id = rol_cat_id
        INNER JOIN '.TBL_USERS.'
                ON usr_id = mem_usr_id
         LEFT JOIN '.TBL_USER_DATA.' AS last_name
                ON last_name.usd_usr_id = usr_id
               AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
         LEFT JOIN '.TBL_USER_DATA.' AS first_name
                ON first_name.usd_usr_id = usr_id
               AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
             WHERE usr_valid  = true
               AND cat_org_id = ? -- $gCurrentOrgId
               AND mem_begin <= ? -- DATE_NOW
               AND mem_end    > ? -- DATE_NOW
          ORDER BY CONCAT(last_name.usd_value, \' \', first_name.usd_value), usr_id';
    
    $sqlData['params']= array(
        $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
        $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
        $gCurrentOrgId,
        DATE_NOW,
        DATE_NOW  );
    
    $form->addSelectBoxFromSql('user_id', $gL10n->get('PLG_FORMFILLER_USER'), $gDb, $sqlData, array('multiselect' => true));
    $form->addSelectBox('form_id', $gL10n->get('PLG_FORMFILLER_CONFIGURATION'), $pPreferences->config['Formular']['desc'], array('property' => FormPresenter::FIELD_REQUIRED , 'showContextDependentFirstEntry' => false));
    
    $sql = 'SELECT fil.fil_id, fil.fil_name, fol.fol_name
          FROM '.TBL_FOLDERS.' as fol, '.TBL_FILES.' as fil
         WHERE fol.fol_id = fil.fil_fol_id
           AND fil.fil_name LIKE \'%.PDF\'
           AND ( fol.fol_org_id = '.$gCurrentOrgId.'
            OR fol.fol_org_id IS NULL )
      ORDER BY fol.fol_name ASC, fil.fil_name ASC ';
    $form->addSelectBoxFromSql('pdf_id', $gL10n->get('PLG_FORMFILLER_PDF_FILE'), $gDb, $sql);
    $form->addFileUpload('importpdffile', $gL10n->get('PLG_FORMFILLER_PDF_FILE').' ('.$gL10n->get('PLG_FORMFILLER_LOCAL').')', array( 'allowedMimeTypes' => array('application/pdf')));

    $form->addSubmitButton('btn_save_configurations', $gL10n->get('PLG_FORMFILLER_PDF_FILE_GENERATE'), array('icon' => 'bi-box-arrow-in-right'));
    $form->addToHtmlPage(false);

    $page->show();
 
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
