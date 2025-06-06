<?php
/**
 * Displays a pop-up window with hints in the configurations menu
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:	none
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../../system/common.php');

// set headline of the script
$headline = $gL10n->get('PLG_FORMFILLER_CONFIGURATIONS');

header('Content-type: text/html; charset=utf-8');

echo '
<div class="modal-header">
    <h4 class="modal-title">'.$headline.'</h4>
</div>
<div class="modal-body">
	<strong>'.$gL10n->get('PLG_FORMFILLER_FIELD_SELECTION').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_FIELD_SELECTION_DESC').'<br><br> 
	<strong>'.$gL10n->get('PLG_FORMFILLER_PDF_FILE_MULTIPLE_PAGES').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_PDF_FILE_MULTIPLE_PAGES_DESC').'<br><br>
	<strong>'.$gL10n->get('PLG_FORMFILLER_INTERFACE_KEYMANAGER').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_INTERFACE_KEYMANAGER_DESC').'<br><br>
	<strong>'.$gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_DYNAMIC_FIELDS_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_WORDWRAP').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_WORDWRAP_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_ORIENTATION').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_ORIENTATION_DESC').'
</div>';
