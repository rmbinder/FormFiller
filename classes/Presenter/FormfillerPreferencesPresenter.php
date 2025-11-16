<?php
namespace Plugins\FormFiller\classes\Presenter;

use Admidio\Changelog\Service\ChangelogService;
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\FormFiller\classes\Config\ConfigTable;

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
     * @var array Array with all possible entries for the preferences.
     *            Each entry consists of an array that has the following structure:
     *            array ('key' => 'xzy', 'label' => 'xyz', 'panels' => array('id' => 'xyz', 'title' => 'xyz', 'icon' => 'xyz'))
     * 
     *            There are thwo different visualizations of the preferences:
     *              1) a nested tab structure (main tabs created by 'key' and 'label' and sub tabs created by 'panels')
     *              2) a accordion structure when the @media query (max-width: 768px) is active ('key' and 'label' are used for card header
     *                 and 'panels' for accordions inside the card) 
     */
    protected array $preferenceTabs = array();
    /**
     * @var string Name of the preference panel that should be shown after page loading.
     *             If this parameter is empty, then show the common preferences.
     */
    protected string $preferencesPanelToShow = '';

    /**
     * Constructor that initializes the class member parameters
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
        $this->preferenceTabs = array(
            // === 1) Configuration ===
            array(
                'key'    => 'configuration',
                'label'  => $gL10n->get('PLG_FORMFILLER_CONFIGURATIONS'),
                'panels' => array(
                    array('id'=>'configurations_edit',  'title'=>$gL10n->get('PLG_FORMFILLER_EDIT'),                'icon'=>'bi-sliders2',          'subcards'=>false),
                    array('id'=>'assort',               'title'=>$gL10n->get('PLG_FORMFILLER_ASSORT'),              'icon'=>'bi-sort-alpha-down',   'subcards'=>false),
                    array('id'=>'export_import',        'title'=>$gL10n->get('PLG_FORMFILLER_EXPORT_IMPORT'),       'icon'=>'bi-code',              'subcards'=>false),
                ),
            ),
            
            // === 2) System ===
            array(
                'key'    => 'system',
                'label'  => $gL10n->get('SYS_SYSTEM'),
                'panels' => array(
                    array('id'=>'informations',         'title'=>$gL10n->get('SYS_INFORMATIONS'),                   'icon'=>'bi-info-circle',       'subcards'=>false),
                    array('id'=>'options',              'title'=>$gL10n->get('PLG_FORMFILLER_OPTIONS'),             'icon'=>'bi-gear',              'subcards'=>false),
                    array('id'=>'uninstallation',       'title'=>$gL10n->get('PLG_FORMFILLER_UNINSTALLATION'),      'icon'=>'bi-trash',             'subcards'=>false),                    
                ),
            ),
            
            // === 3) Security ===
            array(
                'key'    => 'security',
                'label'  => $gL10n->get('SYS_SECURITY'),
                'panels' => array(
                    array('id'=>'access',               'title'=>$gL10n->get('PLG_FORMFILLER_ACCESS_PREFERENCES'),  'icon'=>'bi-key',               'subcards'=>false),
                ),
            ),
        );
    }

    /**
     * Generates the html of the form from the configurations preferences and will return the complete html.
     * @return string Returns the complete html of the form from the configurations preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createConfigurationsEditForm(): string
    {
        $this->assignSmartyVariable('open_configs', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/configurations.php'));
        $smarty = $this->getSmartyTemplate();
        return $smarty->fetch('../templates/preferences.configurations.plugin.formfiller.tpl');
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
        return $smarty->fetch('../templates/preferences.export_import.plugin.formfiller.tpl');
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
            '../templates/preferences.assort.plugin.formfiller.tpl',
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
        return $smarty->fetch('../templates/preferences.assort.plugin.formfiller.tpl');
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
            '../templates/preferences.options.plugin.formfiller.tpl',
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
        return $smarty->fetch('../templates/preferences.options.plugin.formfiller.tpl');
    }
    
    /**
     * Generates the html of the form from the deinstallation preferences and will return the complete html.
     * @return string Returns the complete html of the form from the configurations preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createUninstallationForm(): string
    {
        $this->assignSmartyVariable('open_uninstall', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/uninstall.php'));
        $smarty = $this->getSmartyTemplate();
        return $smarty->fetch('../templates/preferences.uninstall.plugin.formfiller.tpl');
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
            '../templates/preferences.access.plugin.formfiller.tpl',
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
            array('defaultValue' => $pPreferences->config['access']['preferences'], 'helpTextId' => 'PLG_FORMFILLER_ACCESS_PREFERENCES_DESC', 'multiselect' => true)
        );

        //Warning: Undefined array key "inventory_profile_view"
        $formAccess->addInput(
            'inventory_profile_view',
            '',
            '',
            array( 'property' => FormPresenter::FIELD_HIDDEN)
        );
        
        $formAccess->addSubmitButton(
            'adm_button_save_access',
            $gL10n->get('SYS_SAVE'),
            array('icon' => 'bi-check-lg', 'class' => 'offset-sm-3')
        );
        
        $smarty = $this->getSmartyTemplate();
        $formAccess->addToSmarty($smarty);
        return $smarty->fetch('../templates/preferences.access.plugin.formfiller.tpl');
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
        
        $this->assignSmartyVariable('plg_name', $gL10n->get('PLG_FORMFILLER_NAME'));
        $this->assignSmartyVariable('plg_version', $pPreferences->config['Plugininformationen']['version']);
        $this->assignSmartyVariable('plg_date', $pPreferences->config['Plugininformationen']['stand']);
        $this->assignSmartyVariable('open_doc', SecurityUtils::encodeUrl('https://www.admidio.org/dokuwiki/doku.php', array('id' => 'de:plugins:formfiller#formfiller')));
        
        $smarty = $this->getSmartyTemplate();
        return $smarty->fetch('../templates/preferences.informations.plugin.formfiller.tpl');
    }

    /**
     * Set a panel name that should be opened at a page load.
     * @param string $panelName Name of the panel that should be opened at a page load.
     * @return void
     */
    public function setPanelToShow(string $panelName): void
    {
        $this->preferencesPanelToShow = $panelName;
    }

    /**
     * Read all available registrations from the database and create the HTML content of this
     * page with the Smarty template engine and write the HTML output to the internal
     * parameter **$pageContent**. If no registration is found, then show a message to the user.
     */
    public function show(): void
    {
        global $gL10n;

        if ($this->preferencesPanelToShow !== '') {
            // open the selected panel
            if ($this->preferencesPanelToShow !== '') {
                $this->addJavascript('
                    // --- Reset Tab active states for large screens
                    $("#adm_preferences_tabs .nav-link").removeClass("active");
                    $("#adm_preferences_tab_content .tab-pane").removeClass("active show");

                    // --- Reset Accordion active states for small screens
                    $("#adm_preferences_accordion [aria-expanded=\'true\']").attr("aria-expanded", "false");
                    $("#adm_preferences_accordion .accordion-button").addClass("collapsed");
                    $("#adm_preferences_accordion .accordion-item").removeClass("show");
                    $("#adm_preferences_accordion .accordion-collapse").removeClass("show");
                    
                    // --- Activate the selected Tab and its content
                    $("#adm_tab_' . $this->preferencesPanelToShow . '").addClass("active");
                    $("#adm_tab_' . $this->preferencesPanelToShow . '_content").addClass("active show");
                    
                    // --- For Mobile Accordion: open the desired accordion panel
                    $("#collapse_' . $this->preferencesPanelToShow . '").addClass("show");
                                        
                    // --- Desktop vs. Mobile via jQuery visibility
                    if ($(".d-none.d-md-block").is(":visible")) {
                        // Desktop mode
                        $("#adm_preferences_tabs .nav-link[data-bs-target=\'#adm_tab_' . $this->preferencesPanelToShow . '_content\']").addClass("active");
                        $("#adm_preferences_tab_content .tab-pane#adm_tab_' . $this->preferencesPanelToShow . '_content").addClass("active show");
                    } else {
                        // Mobile mode
                        $("#collapse_' . $this->preferencesPanelToShow . '").addClass("show").attr("aria-expanded", "true");
                        $("#heading_' . $this->preferencesPanelToShow . ' .accordion-button").removeClass("collapsed").attr("aria-expanded", "true");
                        // --- Hash setzen, damit Bookmark/Scroll stimmt und zum Element scrollen
                        location.hash = "#heading_' . $this->preferencesPanelToShow . '";
                    }
                ', true);
            }
        }

        $this->addJavascript('
            // === 1) Panel laden und Events binden ===
            function loadPreferencesPanel(panelId) {
                var panelContainer = $("[data-preferences-panel=\"" + panelId + "\"]");
                if (!panelContainer.length) return;

                // Schritt 1: Spinner einf√ºgen
                panelContainer.html("<div class=\"d-flex justify-content-center align-items-center\" style=\"height: 200px;\"><div class=\"spinner-border text-primary\" role=\"status\"><span class=\"visually-hidden\">Lade...</span></div></div>");

                $.get("' . ADMIDIO_URL . FOLDER_PLUGINS . '/formfiller/system/preferences.php", {
                    mode: "html_form",
                    panel: panelId
                }, function(htmlContent) {
                    panelContainer.html(htmlContent);
                    initializePanelInteractions(panelId);
                }).fail(function() {
                    panelContainer.html("<div class=\"text-danger\">Fehler beim Laden</div>");
                });
            }
        
            // === 2) Innerhalb eines Panels die Klick-Handler anmelden ===
            function initializePanelInteractions(panelId) {
                var panelContainer = $("[data-preferences-panel=\"" + panelId + "\"]");
                           
                // Module Settings visibility
                // Universal handling for module enabled toggle within the current panel container
                
                // define additional ids that should also be considered for visibility toggling
                var additionalIds = [\'#system_notifications_enabled\'];
                // Look for any input whose id ends with "_module_enabled"
                var selectors = ["[id$=\'_module_enabled\']"].concat(additionalIds);
            }
        
            // === 3) Hooks f¸r Desktop-Tabs ===
            $(document).on("shown.bs.tab", "ul#adm_preferences_tabs button.nav-link", function(e) {
                var target = e.target.getAttribute("data-bs-target");
                var match = target && target.match(/^#adm_tab_(.+)_content$/);
                if (match) {
                    loadPreferencesPanel(match[1]);
                }
                // scroll to the top of the page
                $("html, body").animate({
                    scrollTop: 0
                }, 500);
            });
            // initial: load the active tab panel
            $("ul#adm_preferences_tabs button.nav-link.active").each(function() {
                var target = this.getAttribute("data-bs-target");
                var match = target && target.match(/^#adm_tab_(.+)_content$/);
                if (match) {
                    loadPreferencesPanel(match[1]);
                }
            });
        
            // === 4) Hooks f¸r Mobile-Accordion ===
            $(document).on("shown.bs.collapse", "#adm_preferences_accordion .accordion-collapse", function() {
                var panelId = this.id.replace(/^collapse_/, "");
                loadPreferencesPanel(panelId);
            });
            // initial: geˆffnetes Accordion-Panel laden
            $("#adm_preferences_accordion .accordion-collapse.show").each(function() {
                var panelId = this.id.replace(/^collapse_/, "");
                loadPreferencesPanel(panelId);
            });
        
            // === 5) Formular-Submit per AJAX ===
            $(document).on("submit", "form[id^=\"adm_preferences_form_\"]", formSubmit);
      ', true);
      
        // Load the select2 in case any of the form uses a select box. Unfortunately, each section
        // is loaded on-demand, when there is no HTML page anymore to insert the css/JS file loading,
        // so we need to do it here, even when no selectbox will be used...
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/css/select2.css');
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2-bootstrap-theme/select2-bootstrap-5-theme.css');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/select2.js');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/i18n/' . $gL10n->getLanguageLibs() . '.js');

        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/bootstrap-tabs-x/css/bootstrap-tabs-x-admidio.css');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/bootstrap-tabs-x/js/bootstrap-tabs-x-admidio.js');

        $this->assignSmartyVariable('preferenceTabs', $this->preferenceTabs);
        $this->addTemplateFile('preferences/preferences.tpl');

        parent::show();
    }
}
