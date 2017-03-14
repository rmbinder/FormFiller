<?php
/**
 * Zeigt im Menue Einstellungen ein Popup-Fenster mit Hinweisen an
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:	keine
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');

// set headline of the script
$headline = $gL10n->get('PLG_FORMFILLER_CONFIGURATIONS');

header('Content-type: text/html; charset=utf-8');

echo '
<div class="modal-header">
    <h4 class="modal-title">'.$headline.'</h4>
</div>
<div class="modal-body">
	<strong>'.$gL10n->get('PLG_FORMFILLER_DESCRIPTION').'</strong><br>
    '.$gL10n->get('PLG_FORMFILLER_DESCRIPTION_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_FONT').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_FONT_DESC').'<br><br>		
    <strong>'.$gL10n->get('PLG_FORMFILLER_FONTSTYLE').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_FONTSTYLE_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_FONTSIZE').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_FONTSIZE_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_FONTCOLOR').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_FONTCOLOR_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_PDFFORM_ORIENTATION').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_PDFFORM_ORIENTATION_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_PDFFORM_SIZE').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_PDFFORM_SIZE_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_PDFFORM_UNIT').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_PDFFORM_UNIT_DESC').'<br><br>			
    <strong>'.$gL10n->get('PLG_FORMFILLER_PDF_FILE').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_PDF_FILE_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_LABELS').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_LABELS_DESC').'<br><br>
	<strong>'.$gL10n->get('PLG_FORMFILLER_RELATION').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_RELATION_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_FORMFILLER_FIELD_SELECTION').'</strong><br>
	'.$gL10n->get('PLG_FORMFILLER_FIELD_SELECTION_DESC').'
</div>';
