<?php
/**
 ***********************************************************************************************
 * Anfügen zusätzlicher Schriftarten
 *
 * @copyright 2004-2024 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

//Note:
//Additional fonts still have to be declarded in the function validFonts()

//add additional fonts to fpdf
$pdf->AddFont('AlteDIN1451Mittelschrift','','din1451alt.php');
$pdf->AddFont('AlteDIN1451Mittelschrift-Geprägt','','din1451alt G.php');
$pdf->AddFont('Angelina','','angelina.php');
$pdf->AddFont('AsphaltFixed-Italic','','02860_ASPHAL1I.php');
$pdf->AddFont('Calligraph','','calligra.php');
$pdf->AddFont('ClarendonBT-Roman','','08634_ClarendonBT.php');
$pdf->AddFont('Edo','','edo.php');
$pdf->AddFont('Exmouth','','exmouth_.php');
$pdf->AddFont('FreebooterScript','','FREEBSC_.php');
$pdf->AddFont('FuturaBT-Medium','','16020_FUTURAM.php');
$pdf->AddFont('LeipzigFraktur','','Leipzig Fraktur Normal.php');
$pdf->AddFont('LeipzigFraktur-Bold','','Leipzig Fraktur Bold.php');
$pdf->AddFont('PlainBlack','','Plain Black.php');
$pdf->AddFont('PlainGermanica','','Plain Germanica.php');
$pdf->AddFont('Scriptina','','SCRIPTIN.php');
$pdf->AddFont('ShadowedBlack','','Shadowed Black.php');
$pdf->AddFont('ShadowedGermanica','','Shadowed Germanica.php');
$pdf->AddFont('Barcode','','03702_Barcode3_9AL.php');
