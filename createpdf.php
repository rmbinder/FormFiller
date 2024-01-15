<?php
/**
 ***********************************************************************************************
 * Erzeugt und befuellt die PDF-Datei fuer das Admidio-Plugin FormFiller
 *
 * @copyright 2004-2024 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * user_id : 			ID des Mitglieds, dessen Daten ausgelesen werden
 * form_id :			interne Nummer des verwendeten PDF-Formulars
 * lst_id : 			Liste, deren Konfiguration verwendet wird
 * rol_id : 			ID der verwendeten Rolle
 * pdf_id : 			(optional) ID der verwendeten PDF-Datei
 * show_former_members: 0 - (Default) Nur aktive Mitglieder der Rolle anzeigen
 *                      1 - Nur ehemalige Mitglieder der Rolle anzeigen
 * 
 * Mit Aenderungen zur Formatierung von kossihh (Juni 2016)
 * 
 * Interface zum Plugin KeyManager:
 * 
 * kmf-... :		$_GET-Variablen, welche mit kmf- beginnen, werden durch das Plugin KeyManager übergeben
 * 					FormFiller übernimmt hierbei die Aufgabe des Ausdrucks
 * 
 ***********************************************************************************************
 */

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');
require_once(__DIR__ . '/libs/fpdf/fpdf.php');
require_once(__DIR__ . '/libs/fpdi/src/autoload.php');

$awardsIsActiv = false;

if (file_exists(__DIR__ . '/../awards/awards_common.php'))
{
    require_once(__DIR__ . '/../awards/awards_common.php');
    if (isAwardsDbInstalled())
    {
        $awardsIsActiv = true;
    }
}

// only the main script or the plugin keymanager can call and start this module
if (strpos($gNavigation->getUrl(), 'formfiller.php') === false && strpos($gNavigation->getUrl(), 'keys_export_to_pff.php') === false)
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$formID      		    = admFuncVariableIsValid($_POST, 'form_id', 'numeric', array('defaultValue' => 0));
$postListId      		= admFuncVariableIsValid($_POST, 'lst_id', 'numeric', array('defaultValue' => 0));
$postPdfID      		= admFuncVariableIsValid($_POST, 'pdf_id', 'numeric', array('defaultValue' => 0));
$postShowFormerMembers 	= admFuncVariableIsValid($_POST, 'show_former_members', 'bool', array('defaultValue' => false));

$pPreferences = new ConfigTablePFF();
$pPreferences->read();

$userArray = array();
unset($role_ids);
$spalte = 0;
$zeile = 0;	
$attributes = array();
$attributesDefault = array();
$user = new User($gDb, $gProfileFields);
$membership = new TableMembers($gDb);
$relation = new TableUserRelation($gDb);
$relationArray = array();
$completePath = '';

if (isset($_POST['user_id']) && sizeof(array_filter($_POST['user_id'])) > 0)
{
    $userArray = array_filter($_POST['user_id']);
}
elseif (($postListId > 0) && sizeof(array_filter($_POST['rol_id'])) > 0)
{
    $role_ids = array_filter($_POST['rol_id']);
	$sql      = '';   // enthaelt das Sql-Statement fuer die Liste

	// create list configuration object and create a sql statement out of it
	$list = new ListConfiguration($gDb, $postListId);
	$sql = $list->getSQL(
	    array('showRolesMembers'  => $role_ids,
	          'showUserUUID'      => true,
	          'showFormerMembers' => $postShowFormerMembers
	    )
	);
	
	// SQL-Statement der Liste ausfuehren 
	$statement = $gDb->queryPrepared($sql);
	
	while ($row = $statement->fetch())
	{
        $user->readDataByUuid($row['usr_uuid']);
        $userArray[] = $user->getValue('usr_id');
	}
}
elseif (isset($_GET['kmf-RECEIVER']))
{	//'kmf-RECEIVER' ist als 'Systemfeld' deklariert, kann nicht geloescht werden und eignet sich deshalb hervorragend zur Ueberpruefung
	//wenn es vorhanden ist, dann wurde 'createpdf.php' von KeyManager aus aufgerufen
	
	$pkmArray = array();                             //Info: pkm = (P)lugin (K)ey(M)anager
	foreach ($_GET as $getVar => $content)
	{
	    if (strpos($getVar, 'kmf-') === 0)
		{
		    $pkmArray[substr($getVar, 4)] = $content;
		}
	}
	$formID = admFuncVariableIsValid($_GET, 'form_id', 'numeric', array('defaultValue' => 0));
	$userArray[] = $pkmArray['RECEIVER'] > 0 ? $pkmArray['RECEIVER'] : 0;
}
else 
{
	//Fehlermeldung ausgeben, wenn Parameter fehlen
	$gMessage->show($gL10n->get('PLG_FORMFILLER_MISSING_PARAMETER'), $gL10n->get('SYS_ERROR'));
}

//initiate FPDF
if (!empty($pPreferences->config['Formular']['pdfform_orientation'][$formID]))
{
	$orientation = $pPreferences->config['Formular']['pdfform_orientation'][$formID];
}
else 
{
	$orientation = 'P';				//Default
}	
if (!empty($pPreferences->config['Formular']['pdfform_unit'][$formID]))
{
	$unit = $pPreferences->config['Formular']['pdfform_unit'][$formID];
}
else 
{
	$unit = 'mm';					//Default
}	
if (!empty($pPreferences->config['Formular']['pdfform_size'][$formID]))
{
	if (strstr($pPreferences->config['Formular']['pdfform_size'][$formID], ','))
	{
		$size = explode(',', $pPreferences->config['Formular']['pdfform_size'][$formID]);	
	}
	else 
	{
		$size = $pPreferences->config['Formular']['pdfform_size'][$formID];	
	}
}
else 
{
	$size = 'A4';					//Default
}	

$pdf = new Fpdi($orientation, $unit, $size);
include(__DIR__ . '/add_fonts.php');

$pdfID = 0;
if ($pPreferences->config['Formular']['pdfid'][$formID] > 0)          // ist in der Konfiguration eine PDF-Datei definiert?
{
	$pdfID = $pPreferences->config['Formular']['pdfid'][$formID];
}

if ($postPdfID > 0)                                                       // wird eine optionale PDF-Datei mittels SelectBox übergeben?
{
	$pdfID = $postPdfID; 
}

// wenn $pdfID != 0 ist, dann wurde ein PDF-File aus dem Download-Bereich zum Beschreiben übergeben
if ($pdfID != 0)
{
	//pruefen, ob das Formular noch in der DB existiert
	$sql = 'SELECT fil_id 
              FROM '. TBL_FILES .' , '. TBL_CATEGORIES. ' , '. TBL_FOLDERS. '
             WHERE fil_id = ? 
               AND fil_fol_id = fol_id
               AND (  fol_org_id = ?
                OR fol_org_id IS NULL ) ';
    $statement = $gDb->queryPrepared($sql, array($pdfID, $gCurrentOrgId));
    $row = $statement->fetchObject();
         
	// Gibt es das Formular noch in der DB, wenn nicht: Fehlermeldung
    if (!isset($row->fil_id))
    {
    	$gMessage->show($gL10n->get('SYS_FILE_NOT_EXIST'));
    }     
	
	// get recordset of current file from databse
    $file = new TableFile($gDb, $pdfID);
	$fileUuid  = $file->getValue('fil_uuid');
	$file->getFileForDownload($fileUuid);
    
	//kompletten Pfad der Datei holen
	$completePath = $file->getFullFilePath();

	//pruefen ob File ueberhaupt physikalisch existiert
	if (!file_exists($completePath))
	{
		$gMessage->show($gL10n->get('SYS_FILE_NOT_EXIST'));
	}
}

if (isset($_FILES['userfile']['tmp_name'][0]) && strlen($_FILES['userfile']['tmp_name'][0]) != 0)                         // eine lokale PDF-Datei wurde übergeben; diese hat höchste Priorität            
{
    // check if Upload was OK
    if (($_FILES['userfile']['error'][0] !== UPLOAD_ERR_OK) && ($_FILES['userfile']['error'][0] !== UPLOAD_ERR_NO_FILE))
    {
        $gMessage->show($gL10n->get('SYS_ERROR'));
        // => EXIT
    }
        
    // check if file was really uploaded
    if(!file_exists($_FILES['userfile']['tmp_name'][0]) || !is_uploaded_file($_FILES['userfile']['tmp_name'][0]))
    {
        $gMessage->show($gL10n->get('SYS_FILE_NOT_EXIST'));
        // => EXIT
    }
        
    // Pfad der Datei holen
    $completePath = $_FILES['userfile']['tmp_name'][0];
}   

//sind Daten für Etiketten definiert?  (dann die Etikettendaten überpruefen)
$etiketten = explode(',', $pPreferences->config['Formular']['labels'][$formID]);
		
if (count($etiketten) == 4)
{
	foreach ($etiketten as $data)
	{
		if (!(is_numeric($data)))
		{
			$etiketten = array(1,0,1,0);       // das sind die Werte zum Drucken für eine Seite
			break;
		}
	}
}	
else 
{
	$etiketten = array();
}
//wenn 	count($etiketten) jetzt nicht 0 ist, dann wird Etikettendruck durchgefuehrt	

// jetzt Standardattribute (Schrift, Stil, Groesse usw.) festlegen (falls nichts definiert wurde)
$attributesDefault = array(
	'font'      => 'Arial', 
	'style'     => 'BI', 
	'size'      => 10, 
	'color'     => '0,0,0',
    'linewidth' => 1, 								//line and rect only
	'fillcolor' => '0,0,0', 						//line and rect only
	'drawcolor' => '0,0,0', 						//line and rect only
	'rectstyle' => 'D' );							//rect only
	
// Textattribute mit den Daten der jeweiligen Konfiguration überschreiben (falls vorhanden)
foreach ($attributesDefault as $attribute => $dummy)
{
	if (isset($pPreferences->config['Formular'][$attribute][$formID]))
	{
		$attributesDefault[$attribute] = $pPreferences->config['Formular'][$attribute][$formID];
	}
}
	
//eventuell vorhandene Beziehungen einlesen
//da es zu Komplikationen fuehren koennte, wenn $userArray durchlaufen und gleichzeitig Werte darin geloescht werden
//wird das temporaere Array $userScanArray verwendet
$userScanArray = $userArray;
foreach ($userScanArray as $userId)
{
	$user->readDataById($userId);

	if (!empty($pPreferences->config['Formular']['relation'][$formID]) && $user->getValue('GENDER', 'text') === $gL10n->get('SYS_MALE'))
	{
		$sql = 'SELECT *
                  FROM '.TBL_USER_RELATIONS.'
            INNER JOIN '.TBL_USER_RELATION_TYPES.'
                    ON ure_urt_id  = urt_id
                 WHERE ure_usr_id1 = ?
            	   AND urt_id = ?
                   AND urt_name        <> \'\'
                   AND urt_name_male   <> \'\'
                   AND urt_name_female <> \'\'
              ORDER BY urt_name ASC LIMIT 1';
		$relationStatement = $gDb->queryPrepared($sql, array($userId, $pPreferences->config['Formular']['relation'][$formID]));

		if ($row = $relationStatement->fetch())
		{
			$relation->clear();
			$relation->setArray($row);
			if (array_search($relation->getValue('ure_usr_id2'),$userArray) !== FALSE)
			{
				unset($userArray[array_search($relation->getValue('ure_usr_id2'),$userArray)]);
				$relationArray[$userId] = $relation->getValue('ure_usr_id2');
			}
		}
	}
}
unset($userScanArray);

$pageCounter = 1;                                               // notwendig bei importierten PDFs mit mehreren Seiten (Seitenzähler)
$pageNumber = 1;                                                // notwendig bei importierten PDFs mit mehreren Seiten (Seitenanzahl)

if ($completePath != '')                                        // wenn nicht leer, dann wurde eine PDF-Datei zum Befüllen übergeben
{
    try
    {
        $pageNumber = $pdf->setSourceFile($completePath);			// Seitenanzahl der importieren PDF-Datei
    }
    catch (PdfParserException $exception)
    {
        $gMessage->show($exception->getMessage());
        // => EXIT
    }
}
	
foreach ($userArray as $userId)
{
	$user->readDataById($userId);
	
	$pageCounterUser = 1;								        // notwendig bei Verwendung des Parameters P (Seitenzähler je user)
	$pageToPrintUser = 1;							            // notwendig bei Verwendung des Parameters P (max. Seitenanzahl je user)
	
	while ($pageCounterUser <= $pageToPrintUser )               // Schleife bei Verwendung des Parameters P
	{
	    if ($pageCounter > $pageNumber)
	    {
	        $pageCounter = 1;                                   // die importierte PDF-Datei hat zuwenig Seiten, deshalb wieder bei Seite 1 beginnen
	    }
	    
		$sortArray = array();
		$orderArray = array();
		
		if ( $zeile == 0 && $spalte == 0)
		{			
			$pdf->AddPage();
			$pdf->SetAutoPageBreak(false);
		
			// set the sourcefile
			if ($completePath != '')
			{
				// Sonderfälle absichern:
				// 1.   Klassischer Etikettendruck (mehrere Etiketten nebeneinander, mehrere Etiketten untereinander, Druck über mehrere Seiten)
				//      kann nicht angewendet werden, wenn die importierte PDF-Datei mehrere Seiten aufweist
				// 2.   Wenn der Parameter P=x (Druck über mehrere Seiten) verwendet wird und die importierte PDF-Datei aber nur eine Seite enthält
				// In diesen Fällen wird von der importierten PDF-Datei nur die erste Seite eingelesen und verwendet (1), bzw. immer nur die einzige Seite verwendet (2)
			    if ( (isset($etiketten[0]) && $etiketten[0] != 1 && isset($etiketten[2]) && $etiketten[2] != 1) || $pageNumber == 1)
				{
				    $pageCounter = 1;
				}
				
				//import page
				$tplIdx = $pdf->importPage($pageCounter);
		
				//use the imported page...
				$pdf->useTemplate($tplIdx,0,0,null,null,true);
			}
		}
		
		// jetzt alle Felder durchlaufen
		foreach ($pPreferences->config['Formular']['fields'][$formID] as $fieldkey => $fielddata)
		{ 
			//der zu schreibende Text koennte auch direkt in $sortArray geschrieben werden,
			//aber anhand der Variablen $text ist der Code etwas übersichtlicher :-) 
			$text = '';		
				
			//$fielddata splitten in Typ und ID
        	$fieldtype = substr($fielddata, 0, 1);
        	$fieldid = (int) substr($fielddata, 1);
        	
        	$formdata = $pPreferences->config['Formular']['positions'][$formID][$fieldkey];
			  
			if (!empty($formdata))
			{
				$xyKoord = array();
			
				//die beiden (mit ! maskierten) Sonderzeichen ; und =, die auch für Parameter verwendet werden, temporär ersetzen
				$formdata = str_replace('!;!','!#!', $formdata);
				$formdata = str_replace('!=!','!*!', $formdata);  
				
				//evtl. vorhandene html-Codes wieder decodieren 
				$formdata = html_entity_decode($formdata, ENT_QUOTES);
				
				//$formdata splitten in Koordinaten und Rest
				$arrSplit = explode(';', $formdata);

				// xyKoordinaten extrahieren und in $arrSplit loeschen
				$xyKoord = explode(',', array_shift($arrSplit));

				// Routine beenden, wenn nicht mindestens die Koordinaten für X und Y angegeben wurden
				if (count($xyKoord) < 2)
				{
					continue ;
				}
			
				//arrSplit zerlegen in ein assoziatives Array
				$fontData = array();		
				foreach ($arrSplit as $splitData)
				{
				    //prüfen, ob das Attribut im richtigen Format vorliegt (Text=Text)
				    if (strpos($splitData, '=', 1) !== false)
				    {
				        $attr = explode('=', $splitData);
				        $fontData[$attr[0]] = $attr[1];
				    }
				}
				
				//die temporäre Ersetzung der Sonderzeichen wiederherstellen
				foreach ($fontData as $fontKey => $dummy)
				{
				    $fontData[$fontKey] = str_replace('!#!',';', $fontData[$fontKey]);
				    $fontData[$fontKey] = str_replace('!*!','=', $fontData[$fontKey]);
				}
				
				// Parameter "P" auswerten (importierte PDF-Dokumente mit mehreren Seiten) 
				if ($pageCounterUser == 1)                // die erste Seite
				{
					if (array_key_exists('P', $fontData) && $fontData['P'] <> 1)
					{
					    $pageToPrintUser = max($fontData['P'], $pageToPrintUser);
						continue;                     //Feld bei Pruefung durchgefallen; zum naechsten Feld
					}
				}
				else 
				{
					if (!array_key_exists('P', $fontData) || (array_key_exists('P', $fontData) && $fontData['P'] <> $pageCounterUser))
					{
						continue;                     //Feld bei Pruefung durchgefallen; zum naechsten Feld
					}
				}
				
				$sortArray[] = array(
						'xykoord'         => $xyKoord,
						'attributes'      => $attributesDefault,
						'image'           => array('path'=>'', 'zufall'=>0),
						'text'            => $text,
				        'orderindex'      => '',
				        'orderwidth'      => 5,
				        'wordwraplength'  => 0,
				        'wordwrapwidth'   => 0,
						'trace'           => false,
						'rect'            => false );
				$pointer = count($sortArray)-1;	
			
				// wurde eine abweichende Schriftfarbe definiert? ->  pruefen und ggf. überschreiben
				if (array_key_exists('C', $fontData))
				{
					// jetzt mit den konfigurierten Daten überschreiben
					$key = true;
					foreach (explode(',', $fontData['C']) as $data)
					{
						if (!(is_numeric($data)))
						{
							$key = false;
							break;
						}
					}
					if ($key)
					{
						$sortArray[$pointer]['attributes']['color'] = $fontData['C'];
					}
				}
			
				// wurde eine abweichende Schriftgroesse definiert? -> pruefen und ggf. setzen
				if (array_key_exists('S', $fontData) && is_numeric($fontData['S']))
				{
					$sortArray[$pointer]['attributes']['size'] = $fontData['S'];
				}	

				// wurde ein abweichender Schrifttyp definiert? -> pruefen und ggf. setzen
				if (array_key_exists('F', $fontData) && in_array($fontData['F'], validFonts()))
				{
					$sortArray[$pointer]['attributes']['font'] = $fontData['F'];
				}			

				// wurden abweichende Schriftattribute definiert? -> pruefen und ggf. setzen
				if (array_key_exists('A', $fontData ) && strstr_multiple('BIU', $fontData['A'])) 
				{
					$sortArray[$pointer]['attributes']['style'] = $fontData['A'];
				}
			
				// wurde eine abweichende Linienbreite definiert? -> pruefen und ggf. setzen
				if (array_key_exists('LW', $fontData ) && is_numeric($fontData['LW']))
				{
					$sortArray[$pointer]['attributes']['linewidth'] = $fontData['LW'];
				}
							
				// wurde eine abweichende Fuellfarbe definiert? -> pruefen und ggf. setzen
				if (array_key_exists('FC', $fontData ) )
				{
					// jetzt mit den konfigurierten Daten überschreiben
					$key = true;
					foreach (explode(',', $fontData['FC']) as $data)
					{
						if (!(is_numeric($data)))
						{
							$key = false;
							break;
						}
					}
					if ($key)
					{
						$sortArray[$pointer]['attributes']['fillcolor'] = $fontData['FC'];
					}
				}
			
				// wurde eine abweichende Zeichenfarbe definiert? -> pruefen und ggf. setzen
				if (array_key_exists('DC', $fontData ) )
				{
					// jetzt mit den konfigurierten Daten überschreiben
					$key = true;
					foreach (explode(',', $fontData['DC']) as $data)
					{
						if (!(is_numeric($data)))
						{
							$key = false;
							break;
						}
					}
					if ($key)
					{
						$sortArray[$pointer]['attributes']['drawcolor'] = $fontData['DC'];
					}
				}
			
				// wurde ein abweichender Rechteckstil definiert? -> pruefen und ggf. setzen
				if (array_key_exists('RS', $fontData ) && strstr_multiple('DF', $fontData['RS'])) 
				{
					$sortArray[$pointer]['attributes']['rectstyle'] = $fontData['RS'];
				}
				
				// wurde eine Reihenfolge definiert?   (order index = OI)                 
				if (array_key_exists('OI', $fontData ) && is_numeric($fontData['OI']))
				{
				    $sortArray[$pointer]['orderindex'] = $fontData['OI'];
				    $orderArray[] =  $fontData['OI'];
				}
				
				// wurde ein Abstand für dynamische Felder definiert?   (order width = OW)
				if (array_key_exists('OW', $fontData ) && is_numeric($fontData['OW']))
				{
				    $sortArray[$pointer]['orderwidth'] = $fontData['OW'];
				}
				
				// wurden Parameter für einen Zeilenumbruch definiert?   (wordwrap lenght = WL)
				if (array_key_exists('WL', $fontData ) && is_numeric($fontData['WL']))
				{
				    $sortArray[$pointer]['wordwraplength'] = $fontData['WL'];
				}
				
				// wurden Parameter für einen Zeilenumbruch definiert?   (wordwrap width = WW)
				if (array_key_exists('WW', $fontData ) && is_numeric($fontData['WL']))
				{
				    $sortArray[$pointer]['wordwrapwidth'] = $fontData['WW'];
				}
				
				switch ($fieldtype)
				{
					case 'l':
						if (array_key_exists('L', $fontData ) )                         //Foto aus einer alternativen Bilddatei)
						{
							$downloadFolder = new TableFolder($gDb);
							if (file_exists(ADMIDIO_PATH . FOLDER_DATA . '/'.$downloadFolder->getRootFolderName().'/'.$fontData['L']))
							{
								$sortArray[$pointer]['image']['path'] = ADMIDIO_PATH . FOLDER_DATA .'/'.$downloadFolder->getRootFolderName().'/'.$fontData['L'];
							}	
						}
						elseif ((int) $gSettingsManager->get('profile_photo_storage') === 1)              //Foto aus adm_my_files
						{
							if (file_exists(ADMIDIO_PATH . FOLDER_DATA . '/user_profile_photos/'.$userId.'.jpg'))
							{
								$sortArray[$pointer]['image']['path'] = ADMIDIO_PATH . FOLDER_DATA . '/user_profile_photos/'.$userId.'.jpg';
							}
						}
						elseif ((int) $gSettingsManager->get('profile_photo_storage') === 0)               //Foto aus der Datenbank
						{		
							if (strlen($user->getValue('usr_photo')) != NULL)
    						{
        						$image = new Image();
        						$image->setImageFromData($user->getValue('usr_photo'));
        
        						// die Methode Image der Klasse FPDF benoetigt einen Pfad zur Imagedatei
        						// ich habe es nicht geschafft von der Klasse Image direkt an die Klasse FPDF diesen Pfad zu uebergeben
        						// deshalb der Umweg ueber eine temporaere Datei
        						$zufall = mt_rand(10000,99999);
        						$image->copyToFile(null, ADMIDIO_PATH . FOLDER_DATA . '/PFF'.$zufall.'.png');
        						$image->delete();
        					
        						$sortArray[$pointer]['image']['path'] = ADMIDIO_PATH . FOLDER_DATA . '/PFF'.$zufall.'.png';
        						$sortArray[$pointer]['image']['zufall'] = $zufall;       // zwischenspeichern, damit nach der Sortierung die Zufallsdatei wieder gelöscht werden kann  
    						}
						}
						break;

					case 'v':
						// wurde ein "beliebiger Text" definiert? 
						if (array_key_exists('V', $fontData))
						{
							$text = $fontData['V'];	               // Hinweis: der uebergebene Inhalt wird nicht überprueft 
						}
						elseif (array_key_exists('K', $fontData) && isset($pkmArray[$fontData['K']]))
						{
							$text = $pkmArray[$fontData['K']];	   // Hinweis: der uebergebene Inhalt wird nicht überprueft
						}
						break;	

					case 'd':
						$text = date("d.m.Y");
				
						// wurden Werte für das Datum definiert? 
						if (array_key_exists('D', $fontData))
						{
							$text = date($fontData['D']);	       // Hinweis: der übergebene Inhalt wird nicht überprüft 
						}	
						break;  

					case 'p':
						switch ($gProfileFields->getPropertyById($fieldid, 'usf_type'))
						{
							case 'RADIO_BUTTON':
							case 'DROPDOWN':

							    $text = ' ';
							    if (is_numeric($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'), 'database')))
							    {
							        $pos  = $user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'), 'database') - 1;
							        
							        if (array_key_exists('T', $fontData))    // Nehme n-ten Text aus Konfiguration
							        {
							            $textarray = explode(',', $fontData['T']);
							            if (isset($textarray[$pos]))               // Wenn Text für diese Stelle definiert
							            {
							                $text = $textarray[$pos];
							            }
							        }
							        else    // lese Wert aus Datenbank
							        {
							            // show selected text of optionfield or combobox
							            $arrListValues = $gProfileFields->getPropertyById($fieldid, 'usf_value_list', 'text');
							            if (isset($arrListValues[$pos+1]))
							            {
							                $text = $arrListValues[$pos+1];
							            }
							        }
							        
							        if ($pos > 0) // Wenn nicht erstes Auswahlelement und weitere Positionen definiert
							        {
							            if (isset($xyKoord[$pos * 2]) && isset($xyKoord[$pos * 2 + 1]))
							            {
							                //beim Schreiben in die PDF-Datei werden nur xykoord[0] und [1] ausgelesen,
							                //deshalb hier die jeweiligen Positionen auslesen und in [0] und [1] schreiben
							                $sortArray[$pointer]['xykoord'][0] = $xyKoord[$pos * 2];
							                $sortArray[$pointer]['xykoord'][1] = $xyKoord[$pos * 2 + 1];
							            }
							        }
							    }
								break;
							
							case 'CHECKBOX':
								if ($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')))
								{
									$text = 'x';
								}
								break;
							
							default:
								$text = html_entity_decode($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')), ENT_QUOTES | ENT_HTML5);
						}
						break;
				
                    case 'b':  
                        if (array_key_exists($userId, $relationArray))
					    {
                            $user->readDataById($relationArray[$userId]);

						    switch ($gProfileFields->getPropertyById($fieldid, 'usf_type'))
                            {
                                case 'RADIO_BUTTON':
							    case 'DROPDOWN':
				
							        $text = ' ';
							        if (is_numeric($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'), 'database')))
							        {
							            $pos = $user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'), 'database') - 1;
							            
							            if (array_key_exists('T', $fontData))    // Nehme n-ten Text aus Konfiguration
							            {
							                $textarray = explode(',', $fontData['T']);
							                if (isset($textarray[$pos]))               // Wenn Text für diese Stelle definiert
							                {
							                    $text = $textarray[$pos];
							                }
							            }
							            else    // lese Wert aus Datenbank
							            {
							                // show selected text of optionfield or combobox
							                $arrListValues = $gProfileFields->getPropertyById($fieldid, 'usf_value_list', 'text');
							                if (isset($arrListValues[$pos+1]))
							                {
							                    $text = $arrListValues[$pos+1];
							                }
							            }
							            
							            if ($pos > 0) // Wenn nicht erstes Auswahlelement und weitere Positionen definiert
							            {
							                if (isset($xyKoord[$pos * 2]) && isset($xyKoord[$pos * 2 + 1]))
							                {
							                    //beim Schreiben in die PDF-Datei werden nur xykoord[0] und [1] ausgelesen,
							                    //deshalb hier die jeweiligen Positionen auslesen und in [0] und [1] schreiben
							                    $sortArray[$pointer]['xykoord'][0] = $xyKoord[$pos * 2];
							                    $sortArray[$pointer]['xykoord'][1] = $xyKoord[$pos * 2 + 1];
							                }
							            }
							        }
								    break;
							
							    case 'CHECKBOX':
                                    if ($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')))
								    {
									   $text = 'x';
								    }
								    break;
							
                                default:
							         $text = html_entity_decode($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')), ENT_QUOTES | ENT_HTML5);
                            }
                            $user->readDataById($userId);
					    }
						break;
					
					case 't':              // trace				
						//pruefen, ob Koordinaten x2 und y2 vorhanden sind
						if (count($xyKoord) < 4)
						{
							continue 2;      
						}
				
						$sortArray[$pointer]['trace'] = true;
						break;  
				
					case 'r':              // rectangle				
						//pruefen, ob Koordinaten w und h vorhanden sind
						if (count($xyKoord) < 4)
						{
							continue 2;     
						}
				
						$sortArray[$pointer]['rect'] = true;
						break; 
						
				    case 'm':              // memberships
				        $role = new TableRoles($gDb);
				        $roleMemberships = $user->getRoleMemberships();
				        
				        foreach ($roleMemberships as $roleId)
				        {
				            $role->readDataById($roleId);
				            $sortArray[$pointer]['text'] = html_entity_decode($role->getValue('rol_name'), ENT_QUOTES | ENT_HTML5);

				            $membership->readDataByColumns(array('mem_rol_id' => $roleId, 'mem_usr_id' => $userId));
				            
				            if (array_key_exists('MS', $fontData ) && strpos($fontData['MS'], 'B') !== false && strpos($fontData['MS'], 'E') !== false)
                            {
                                $sortArray[$pointer]['text'] .= ' ('. $gL10n->get('SYS_FROM_TO', array($membership->getValue('mem_begin', $gSettingsManager->getString('system_date')), $membership->getValue('mem_end', $gSettingsManager->getString('system_date')))).')';
				            }
				            elseif (array_key_exists('MS', $fontData ) && $fontData['MS'] === 'B')
				            {
                                $sortArray[$pointer]['text'] .= ' ('. $gL10n->get('SYS_FROM', array($membership->getValue('mem_begin', $gSettingsManager->getString('system_date')))).')';
				            }
				            elseif (array_key_exists('MS', $fontData ) && $fontData['MS'] === 'E')	                
				            {
				                $sortArray[$pointer]['text'] .= ' ('.$gL10n->get('SYS_ROLE_MEMBERSHIP_TO').$membership->getValue('mem_end', $gSettingsManager->getString('system_date')).')';
				            }
				            				            
				            $sortArray[] = $sortArray[$pointer];
				            $pointer = count($sortArray)-1;	
				            $sortArray[$pointer]['xykoord'][1] += $sortArray[$pointer-1]['orderwidth'];
				        }
					    break;
					    
				    case 'f':              // former memberships
				        $sql = 'SELECT *
                                  FROM '.TBL_MEMBERS.'
                            INNER JOIN '.TBL_ROLES.'
                                    ON rol_id = mem_rol_id
                            INNER JOIN '.TBL_CATEGORIES.'
                                    ON cat_id = rol_cat_id
                                 WHERE mem_usr_id  = ? -- $userId
                                   AND mem_end     < ? -- DATE_NOW
                                   AND rol_valid   = 1
                                   AND cat_name_intern <> \'EVENTS\'
                                   AND (  cat_org_id  = ? -- $gCurrentOrgId
                                    OR cat_org_id IS NULL )
                              ORDER BY cat_org_id, cat_sequence, rol_name';
				    
				        $formerRolesStatement = $gDb->queryPrepared($sql, array($userId, DATE_NOW, $gCurrentOrgId));
		
				        while ($row = $formerRolesStatement->fetch())
				        {
				            $sortArray[$pointer]['text'] = $row['rol_name'];
				            
				            $membership->readDataByColumns(array('mem_rol_id' => $row['rol_id'], 'mem_usr_id' => $userId));
				            
				            if (array_key_exists('MS', $fontData ) && strpos($fontData['MS'], 'B') !== false && strpos($fontData['MS'], 'E') !== false)
				            {
				                $sortArray[$pointer]['text'] .= ' ('. $gL10n->get('SYS_FROM_TO', array($membership->getValue('mem_begin', $gSettingsManager->getString('system_date')), $membership->getValue('mem_end', $gSettingsManager->getString('system_date')))).')';
				            }
				            elseif (array_key_exists('MS', $fontData ) && $fontData['MS'] === 'B')
				            {
				                $sortArray[$pointer]['text'] .= ' ('. $gL10n->get('SYS_FROM', array($membership->getValue('mem_begin', $gSettingsManager->getString('system_date')))).')';
				            }
				            elseif (array_key_exists('MS', $fontData ) && $fontData['MS'] === 'E')
				            {
				                $sortArray[$pointer]['text'] .= ' ('.$gL10n->get('SYS_ROLE_MEMBERSHIP_TO').$membership->getValue('mem_end', $gSettingsManager->getString('system_date')).')';
				            }
				            
				            $sortArray[] = $sortArray[$pointer];
				            $pointer = count($sortArray)-1;
				            $sortArray[$pointer]['xykoord'][1] += $sortArray[$pointer-1]['orderwidth'];
				        }
				        break; 
				        
				    case 'a':              // awards
				        if ($awardsIsActiv)
				        {
				            $awards=awa_load_awards($userId,true);

				            if (is_array($awards))
				            {
				                foreach ($awards as $row)
				                {
				                    $sortArray[$pointer]['text'] = $row['awa_cat_name'].' - '.$row['awa_name'];
				                    $sortArray[$pointer]['text'] .= (strlen($row['awa_info'])>0) ? ' ('.$row['awa_info'].')' : '';
                                    $sortArray[$pointer]['text'] .= ' ('.$gL10n->get('AWA_SINCE').' '.date('d.m.Y',strtotime($row['awa_date'])).')' ;
				                    $sortArray[] = $sortArray[$pointer];
				                    $pointer = count($sortArray)-1;
				                    $sortArray[$pointer]['xykoord'][1] += $sortArray[$pointer-1]['orderwidth'];
				                }
				            }
				        }
				        break; 
				        
				    case 'i':              // user_id
				        $text = $user->getValue('usr_id');
				        break;
				        
				    case 'u':              // user_uuid
				        $text = $user->getValue('usr_uuid');
				        break;
				}
			
				// wurde optionaler Text angegeben?   (von lagro)
         		if (array_key_exists('}', $fontData))
         		{
            		$text .= $fontData['}'];
         		}
         		if (array_key_exists('{', $fontData))
         		{
            		$text = $fontData['{'].$text;
         		}
         		
         		$sortArray[$pointer]['text'] = $text;                  
			}	
		}  // zum naechsten Profilfeld 
	
		if (count($orderArray) > 0)								// mind. bei einem Feld wurden Eintragungen für eine Reihenfolge gesetzt
		{
		    $orderIndexMin = min($orderArray);                  // den kleinsten Index bestimmen
		    $sortFirst  = array();
		    $sortSecond = array();
		    foreach ($sortArray as $key => $row)
		    {
		        $sortFirst[$key] = $row['orderindex'];
		        $sortSecond[$key] = $row['xykoord'][1];
		    }
		    
		    array_multisort($sortFirst, SORT_NUMERIC, $sortSecond, SORT_NUMERIC, $sortArray);
		    
		    foreach ($sortArray as $key => $sortData)
		    {
		        if (!isset($newY) && $sortData['orderindex'] == $orderIndexMin )
		        {
		            $newY = $sortData['xykoord'][1];
		            continue;
		        }
		        
		        if ($sortData['orderindex'] != '')        
		        {
		            $diff = 0;
		            $newY += $sortData['orderwidth'];
		            $sortArray[$key]['xykoord'][1] = $newY;
		            
		            if (isset($sortData['xykoord'][3]))
		            {
		                $diff = $sortData['xykoord'][3] - $sortData['xykoord'][1];
		                $sortArray[$key]['xykoord'][3] = $sortArray[$key]['xykoord'][1] + $diff;
		            }
		        }
		    }
		    unset($newY);
		}
		
		//Etikettendruck
		$sortFirst  = array();
		$sortSecond = array();
		foreach ($sortArray as $key => $row) 
		{
    		$sortFirst[$key] = $row['xykoord'][1];          
    		$sortSecond[$key] = $row['xykoord'][0];    
		}
		array_multisort($sortFirst, SORT_NUMERIC, $sortSecond, SORT_NUMERIC, $sortArray);
	
		$yPrevKoord = '';														// wird beim Etikettendruck benoetigt
		foreach ($sortArray as $key => $sortData) 
		{
			$width = 0;
			$height = 0;
			$koordX2 = 0;
			$koordY2 = 0;
			if (isset($sortData['xykoord'][2]))
			{
				$width = $sortData['xykoord'][2];
				$koordX2 = $sortData['xykoord'][2];
			}
			if (isset($sortData['xykoord'][3]))
			{
				$height = $sortData['xykoord'][3];
				$koordY2 = $sortData['xykoord'][3];
			}
			
			if ($sortData['image']['path'] <> '')
			{
				$imageSize = getimagesize( $sortData['image']['path']);
				
				//Berechnungsalgorithmus aus FPDF-Library
				if ($unit == 'pt')
					$k = 1;
				elseif ($unit == 'mm')
					$k = 72/25.4;
				elseif ($unit == 'cm')
					$k = 72/2.54;
				elseif ($unit == 'in')
					$k = 72;
		
				if ($width == 0 && $height == 0)
				{
					// Put image at 96 dpi
					$width = -96;
					$height = -96;
				}

				if ($width < 0)
					$width = -$imageSize[0]*72/$width/$k;
				if ($height < 0)
					$height = -$imageSize[1]*72/$height/$k;
				if ($width == 0)
					$width = $height*$imageSize[0]/$imageSize[1];
				if ($height == 0)
					$height = $width*$imageSize[1]/$imageSize[0];
			}
         
        	if (count($etiketten) > 0)									//Etikettendruck
        	{
        		if ($yPrevKoord == $sortData['xykoord'][1]+($zeile*$etiketten[3]))     // Druck in derselben Zeile
        		{
        			// das Leerzeichen zwischen Texten bzw Bildern in der Groesse der Standardattribute ausgeben,
					// ansonsten koennten unterschiedlich breite Leerzeichen im Etikett vorhanden sein
        		    // vorher prüfen, ob ein NICHT-Core Font gesetzt ist. Wenn ja, dürfen die Schriftstile B und I nicht verwendet werden
        		    // ---> fpdf erzeugt Fehler, wenn ein nicht definierter Font verwendet wird, z.B. "Edo B" (Fettschrift)
        		    if (!in_arrayi($attributesDefault['font'], coreFonts()))
        		    {
        		        $attributesDefault['style'] = str_replace('B', '', $attributesDefault['style']);
        		        $attributesDefault['style'] = str_replace('I', '', $attributesDefault['style']);
        		    }
					$pdf->SetFont($attributesDefault['font'], $attributesDefault['style'], $attributesDefault['size']);
					
        			if ($sortData['trace'] || $sortData['rect'])         // bei Linien und Rechtecken die absoluten Koordinaten verwenden, keinen Etikettendruck anwenden 
        			{
        				$koordX = $sortData['xykoord'][0]+($spalte*$etiketten[1]);
         				$koordY = $sortData['xykoord'][1]+($zeile*$etiketten[3]);	
        			}
        			elseif ($sortData['image']['path'] <> '')				// bei Bildern ein Leerzeichen vorher und die Bildweite mit einbauen
        			{
						$pdf->Write(0,utf8_decode('  '));
						$koordX = $pdf->GetX();
						$koordY = $pdf->GetY();
						$pdf->SetX($koordX+$width);		
					}
					else 												// bei Texten innerhalb derselben Zeile nur ein Leerzeichen dazwischen
					{
						$pdf->Write(0,utf8_decode(' '));
        			}
        		}
        		else                                                 	// eine neue Zeile des Etikettes wurde angefangen
        		{
        			$koordX=$sortData['xykoord'][0]+($spalte*$etiketten[1]);
         			$koordY=$sortData['xykoord'][1]+($zeile*$etiketten[3]);	
         			$pdf->SetXY($koordX+$width,$koordY);
        		}
        		
        		// yKoordinate (=Zeile) zwischenspeichern um im naechsten Durchgang erkennen zu koennen,
        		// ob in einer neuen Zeile gedruckt wird
        		$yPrevKoord = $sortData['xykoord'][1]+($zeile*$etiketten[3]);
        	
        		$koordX2 = $koordX2+($spalte*$etiketten[1]);
        		$koordY2 = $koordY2+($zeile*$etiketten[3]);	
        		
        	}
       	 	else 														//Formulardruck
        	{
         		$pdf->SetXY($sortData['xykoord'][0], $sortData['xykoord'][1]);
         		$koordX = $sortData['xykoord'][0];
         		$koordY = $sortData['xykoord'][1];
        	}
       
			if ($sortData['image']['path'] <> '')						//Bild in PDF-Datei schreiben
			{
       			$pdf->Image($sortData['image']['path'], $koordX, $koordY, $width, $height);
			}
			elseif ($sortData['trace']) 								// Linie in PDF-Datei schreiben
			{
				$pdf->SetLineWidth($sortData['attributes']['linewidth']);
				$color = explode(',', $sortData['attributes']['drawcolor']);
				$pdf->SetDrawColor($color[0],$color[1],$color[2]);
				$pdf->Line($koordX,$koordY,$koordX2,$koordY2);
			}
			elseif ($sortData['rect']) 								// Rechteck in PDF-Datei schreiben
			{
				$pdf->SetLineWidth($sortData['attributes']['linewidth']);
				$color = explode(',', $sortData['attributes']['drawcolor']);
				$pdf->SetDrawColor($color[0],$color[1],$color[2]);
				$color = explode(',', $sortData['attributes']['fillcolor']);
				$pdf->SetFillColor($color[0],$color[1],$color[2]);
				$pdf->Rect($koordX,$koordY,$width,$height,$sortData['attributes']['rectstyle']);
			}
			else 													// Text in PDF-Datei schreiben
			{
				$color = explode(',', $sortData['attributes']['color']);
				$pdf->SetTextColor($color[0],$color[1],$color[2]);
				
				// vor SetFont prüfen, ob ein NICHT-Core Font verwendet wird. Wenn ja, dürfen die Schriftstile B und I nicht verwendet werden
				// ---> fpdf erzeugt Fehler, wenn ein nicht definierter Font verwendet wird, z.B. "Edo B" (Fettschrift)
				if (!in_arrayi($sortData['attributes']['font'], coreFonts()))
				{
				    $sortData['attributes']['style'] = str_replace('B', '', $sortData['attributes']['style']);
				    $sortData['attributes']['style'] = str_replace('I', '', $sortData['attributes']['style']);
				}
				$pdf->SetFont($sortData['attributes']['font'], $sortData['attributes']['style'], $sortData['attributes']['size']);	
					
				// prüfen, ob Parameter für einen Zeilenumbruch definiert sind 
				if (($sortData['wordwraplength'] > 0) && ($sortData['wordwrapwidth'] > 0) && (strlen($sortData['text']) > $sortData['wordwraplength']))	 
				{								    
				    $koordXPrev = $pdf->GetX();
				    $koordYPrev = $pdf->GetY();
				    $i = 0;
				    $sortDataTextArr = strToFormattedArray($sortData['text'], $sortData['wordwraplength']);
				    
				    foreach ($sortDataTextArr as $data)
				    {
				        $pdf->SetXY($koordXPrev, $koordYPrev + $i);
				        $pdf->Write(0,utf8_decode($data));
				        $i += $sortData['wordwrapwidth'];
				    }
				    $pdf->SetXY($koordXPrev, $koordYPrev);
				}
				else 
				{
				    $pdf->Write(0,utf8_decode($sortData['text']));
				}	
			}
				
			// ggf. eine temporaer erzeugte Bilddatei wieder loeschen
			if (file_exists( ADMIDIO_PATH . FOLDER_DATA . '/PFF'.$sortData['image']['zufall'].'.png'))
       	 	{
        		unlink( ADMIDIO_PATH . FOLDER_DATA . '/PFF'.$sortData['image']['zufall'].'.png');
        	}	
		}	
		$pageCounter++;							
        $pageCounterUser++;
	}
	
	if (count($etiketten) > 0)
    {
		$spalte++;
		if ($spalte == $etiketten[0])
		{
			$spalte = 0;
			$zeile++;
		}
		if ($zeile == $etiketten[2])
		{
			$zeile = 0;
		}
	}
}  // zum naechsten User

if ($pdf->PageNo() > $pPreferences->config['Optionen']['maxpdfview'] )
{
	$pdf->Output($pPreferences->config['Formular']['desc'][$formID].'.pdf', 'D');	
}
else 
{
	$pdf->Output();
}
