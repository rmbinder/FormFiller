<?php
namespace Formfiller\Presenter;

use Admidio\Changelog\Service\ChangelogService;
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Formfiller\Config\ConfigTable;

/**
 * @brief Class with methods to display the preference page and helpful functions.
 *
 * This class adds some functions that are used in the formfiller-preferences module to keep the
 * code easy to read and short
 * 
 * FormfillerPreferencesPresenter is a modified (Admidio)PreferencesPresenter
 *
 * **Code example**
 * ```
 * // generate html output
 * $page = new FormfillerPreferencesPresenter('Options', $headline);
 * $page->createOptionsForm();
 * $page->show();
 * ```
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
class FormfillerPreferencesPresenter extends PagePresenter
{
    /**
     * @var array Array with all possible accordion entries for the system preferences.
     *            Each accordion entry consists of an array that has the following structure:
     *            array('id' => 'xzy', 'title' => 'xyz', 'icon' => 'xyz')
     */
    protected array $accordionCommonPanels = array();
    
    /**
     * @var string Name of the preference panel that should be shown after page loading.
     *             If this parameter is empty then show the common preferences.
     */
    protected string $preferencesPanelToShow = '';

    /**
     * Constructor that initialize the class member parameters
     * @throws Exception
     */
    public function __construct(string $panel = '')
    {
        global $gL10n;

        $this->initialize();
        $this->setPanelToShow($panel);

        $this->setHtmlID('adm_preferences');
        $this->setHeadline($gL10n->get('SYS_SETTINGS'));
        
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    private function initialize(): void
    {
        global $gL10n;
      
        $this->accordionCommonPanels = array(
            'configurations' => array(
                'id' => 'configurations',
                'title' => $gL10n->get('PLG_FORMFILLER_CONFIGURATIONS'),
                'icon' => 'bi-sliders2'
            ),
            'options' => array(
                'id' => 'options',
                'title' => $gL10n->get('PLG_FORMFILLER_OPTIONS'),
                'icon' => 'bi-gear'
            ),
            'export_import' => array(
                'id' => 'export_import',
                'title' => $gL10n->get('PLG_FORMFILLER_EXPORT_IMPORT'),
                'icon' => 'bi-code'
            ),
            'assort' => array(
                'id' => 'assort',
                'title' => $gL10n->get('PLG_FORMFILLER_ASSORT'),
                'icon' => 'bi-sort-alpha-down'
            ),
            'deinstallation' => array(
                'id' => 'deinstallation',
                'title' => $gL10n->get('PLG_FORMFILLER_DEINSTALLATION'),
                'icon' => 'bi-trash'
            ),
            'access' => array(
                'id' => 'access',
                'title' => $gL10n->get('PLG_FORMFILLER_ACCESS_PREFERENCES'),
                'icon' => 'bi-key'
            ),
            'informations' => array(
                'id' => 'informations',
                'title' => $gL10n->get('PLG_FORMFILLER_PLUGIN_INFORMATION'),
                'icon' => 'bi-info-circle'
            )
        );
    }

    /**
     * Generates the html of the form from the configurations preferences and will return the complete html.
     * @return string Returns the complete html of the form from the configurations preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createConfigurationsForm(): string
    {
        $this->assignSmartyVariable('open_configs', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/configurations.php'));
        $smarty = $this->getSmartyTemplate();
        return $smarty->fetch('../templates/preferences.configurations.tpl');
    }
    
    /**
     * Generates the html of the form from the exportimport preferences and will return the complete html.
     * @return string Returns the complete html of the form from the exportimport preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createExportImportForm(): string
    {
        $this->assignSmartyVariable('open_export_import', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/export_import.php'));
        $smarty = $this->getSmartyTemplate();
        return $smarty->fetch('../templates/preferences.export_import.tpl');
    }
    
    /**
     * Generates the html of the form from the assort preferences and will return the complete html.
     * @return string Returns the complete html of the form from the assort preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createAssortForm(): string
    {
        global $gL10n, $gSettingsManager, $gCurrentSession;
        
        $formValues = $gSettingsManager->getAll();
        
        $formAssort = new FormPresenter(
            'adm_preferences_form_configurations',
            '../templates/preferences.assort.tpl',
            SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/preferences.php', array('mode' => 'save', 'panel' => 'Assort')),
            null,
            array('class' => 'form-preferences')
            );
        
        $formAssort->addCustomContent('assort','','', array( 'alertWarning' => $gL10n->get('PLG_FORMFILLER_ASSORT_NOTE')));
       
        $formAssort->addSubmitButton(
            'adm_button_save_assort',
            $gL10n->get('PLG_FORMFILLER_ASSORT'),
            array('icon' => 'bi-sort-alpha-down', 'class' => 'offset-sm-3')
            );
        
        $smarty = $this->getSmartyTemplate();
        $formAssort->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formAssort);
        return $smarty->fetch('../templates/preferences.assort.tpl');
    }
    
    /**
     * Generates the html of the form from the options preferences and will return the complete html.
     * @return string Returns the complete html of the form from the options preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createOptionsForm(): string
    {
        global $gL10n, $gSettingsManager, $gCurrentSession;
        
        $pPreferences = new ConfigTable();
        $pPreferences->read();
        
        $formValues = $gSettingsManager->getAll();
        
        $formOptions = new FormPresenter(
            'adm_preferences_form_options',
            '../templates/preferences.options.tpl',
            SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/preferences.php', array('mode' => 'save', 'panel' => 'Options')),
            null,
            array('class' => 'form-preferences')
            );
        
        $formOptions->addInput(
            'maxpdfview',
            $gL10n->get('PLG_FORMFILLER_MAX_PDFVIEW'),
            $pPreferences->config['Optionen']['maxpdfview'],
            array('step' => 1,'type' => 'number', 'minNumber' => 0, 'helpTextId' => 'PLG_FORMFILLER_MAX_PDFVIEW_DESC')
            );
        
        $formOptions->addInput(
            'pdfform_addsizes',
            $gL10n->get('PLG_FORMFILLER_PDFFORM_ADDSIZES'),
            $pPreferences->config['Optionen']['pdfform_addsizes'],
            array( 'helpTextId' => 'PLG_FORMFILLER_PDFFORM_ADDSIZES_DESC')
            );

        $formOptions->addSubmitButton(
            'adm_button_save_options',
            $gL10n->get('SYS_SAVE'),
            array('icon' => 'bi-check-lg', 'class' => 'offset-sm-3')
            );
        
        $smarty = $this->getSmartyTemplate();
        $formOptions->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formOptions);
        return $smarty->fetch('../templates/preferences.options.tpl');
    }
    
    /**
     * Generates the html of the form from the deinstallation preferences and will return the complete html.
     * @return string Returns the complete html of the form from the deinstallation preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createDeinstallationForm(): string
    {
        global $gL10n, $gCurrentSession;
        
        $formDeinstallation = new FormPresenter(
            'adm_preferences_form_deinstallation',
            '../templates/preferences.deinstallation.tpl',
            SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/preferences.php', array('mode' => 'save', 'panel' => 'Deinstallation')),
            null,
            array('class' => 'form-preferences')
            );
        
        $radioButtonEntries = array('0' => $gL10n->get('PLG_FORMFILLER_DEINST_ACTORGONLY'), '1' => $gL10n->get('PLG_FORMFILLER_DEINST_ALLORG') );
        $formDeinstallation->addRadioButton('deinst_org_select',$gL10n->get('PLG_FORMFILLER_ORG_CHOICE'),$radioButtonEntries, array('defaultValue' => '0',  'alertWarning' => $gL10n->get('PLG_FORMFILLER_DEINSTALLATION_FORM_DESC_ALERT')));    
        
        $formDeinstallation->addSubmitButton(
            'adm_button_save_deinstallation',
            $gL10n->get('PLG_FORMFILLER_DEINSTALLATION'),
            array('icon' => 'bi-trash', 'class' => 'offset-sm-3')
            );
        
        $smarty = $this->getSmartyTemplate();
        $formDeinstallation->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formDeinstallation);
        return $smarty->fetch('../templates/preferences.deinstallation.tpl');
    }

    /**
     * Generates the html of the form from the access preferences and will return the complete html.
     * @return string Returns the complete html of the form from the access preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createAccessForm(): string
    {
        global $gL10n, $gSettingsManager, $gCurrentSession, $gCurrentOrgId, $gDb;
        
        $pPreferences = new ConfigTable();
        $pPreferences->read();
        
        $formValues = $gSettingsManager->getAll();
        
        $formAccess = new FormPresenter(
            'adm_preferences_form_access',
            '../templates/preferences.access.tpl',
            SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/preferences.php', array('mode' => 'save', 'panel' => 'Access')),
            null,
            array('class' => 'form-preferences')
            );
        
        $sql = 'SELECT rol_id, rol_name, cat_name
                  FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.' 
                 WHERE cat_id = rol_cat_id
                   AND ( cat_org_id = ' . $gCurrentOrgId . '
                    OR cat_org_id IS NULL )
              ORDER BY cat_sequence, rol_name ASC';
 
        $formAccess->addSelectBoxFromSql(
            'access_preferences', 
            '',
            $gDb, 
            $sql, 
            array('defaultValue' => $pPreferences->config['access']['preferences'], 'helpTextId' => 'PLG_FORMFILLER_ACCESS_PREFERENCES_DESC', 'multiselect' => true));
        
        $formAccess->addSubmitButton(
            'adm_button_save_access',
            $gL10n->get('SYS_SAVE'),
            array('icon' => 'bi-check-lg', 'class' => 'offset-sm-3')
            );
        
        $smarty = $this->getSmartyTemplate();
        $formAccess->addToSmarty($smarty);
        return $smarty->fetch('../templates/preferences.access.tpl');
    }
    
    /**
     * Generates the html of the form from the informations preferences and will return the complete html.
     * @return string Returns the complete html of the form from the informations preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createInformationsForm(): string
    {
        global $gL10n;
        
        $pPreferences = new ConfigTable();
        $pPreferences->read();
   
        $this->assignSmartyVariable('plg_name', $gL10n->get('PLG_FORMFILLER_FORMFILLER'));
        $this->assignSmartyVariable('plg_version', $pPreferences->config['Plugininformationen']['version']);
        $this->assignSmartyVariable('plg_date', $pPreferences->config['Plugininformationen']['stand']);
        $this->assignSmartyVariable('open_doc', SecurityUtils::encodeUrl('https://www.admidio.org/dokuwiki/doku.php', array('id' => 'de:plugins:formfiller#formfiller')));
        
        $smarty = $this->getSmartyTemplate();
        return $smarty->fetch('../templates/preferences.informations.tpl');
    }

    /**
     * Set a panel name that should be opened at page load.
     * @param string $panelName Name of the panel that should be opened at page load.
     * @return void
     */
    public function setPanelToShow(string $panelName)
    {
        $this->preferencesPanelToShow = $panelName;
    }

    /**
     * Read all available panels from the database and create the html content of this
     * page with the Smarty template engine and write the html output to the internal
     * parameter **$pageContent**. If no panel is found than show a message to the user.
     */
    public function show(): void
    {
        global $gL10n;

        if ($this->preferencesPanelToShow !== '') {

                $this->addJavascript(
                    '
                $("#adm_tabs_nav_common").attr("class", "nav-link active");
                $("#adm_tabs_common").attr("class", "tab-pane fade show active");
                $("#adm_collapse_preferences' . $this->preferencesPanelToShow . '").attr("class", "collapse show");
                $.get("' . ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/src/preferences.php?mode=html_form&panel=' . $this->preferencesPanelToShow . '", function (data) {
                        $("#adm_panel_preferences_' . $this->preferencesPanelToShow . ' .accordion-body").html(data);
                        $("#adm_collapse_preferences_' . $this->preferencesPanelToShow . '").addClass("show");
                    });
                location.hash = "#adm_panel_preferences_' . $this->preferencesPanelToShow . '";
                    ',
                    true
                );
        }

        $this->addJavascript(
            '
            var panels = [  "configurations", "export_import" , "options", "assort", "deinstallation", "access", "informations"];

            for(var i = 0; i < panels.length; i++) {
                $("#adm_panel_preferences_" + panels[i] + " .accordion-header").click(function (e) {
                    var id = $(this).data("preferences-panel");
                    if ($("#adm_panel_preferences_" + id + " h2").attr("aria-expanded") == "true") {
                        $.get("' . ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/preferences.php?mode=html_form&panel=" + id, function (data) {
                            $("#adm_panel_preferences_" + id + " .accordion-body").html(data);
                        });
                    }
                });

                $(document).on("submit", "#adm_preferences_form_" + panels[i], formSubmit);
            }',
            true
        );

        ChangelogService::displayHistoryButton($this, 'preferences', 'preferences,texts');

        // Load the select2 in case any of the form uses a select box. Unfortunately, each section
        // is loaded on-demand, when there is no html page any more to insert the css/JS file loading,
        // so we need to do it here, even when no selectbox will be used...
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/css/select2.css');
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2-bootstrap-theme/select2-bootstrap-5-theme.css');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/select2.js');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/i18n/' . $gL10n->getLanguageLibs() . '.js');

        $this->assignSmartyVariable('accordionCommonPanels', $this->accordionCommonPanels);
        $this->addTemplateFolder(ADMIDIO_PATH. FOLDER_PLUGINS . PLUGIN_FOLDER. '/templates');
        $this->addTemplateFile('preferences.accordion.menu.tpl');

        parent::show();
    }
}
