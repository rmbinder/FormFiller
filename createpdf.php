<?php
/**
 ***********************************************************************************************
 * Erzeugt und befuellt die PDF-Datei fuer das Admidio-Plugin FormFiller
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * user_id : 			ID des Mitglieds, dessen Daten ausgelesen werden
 * form_id :			interne Nummer des verwendeten PDF-Formulars
 * lst_id : 			Liste, deren Konfiguration verwendet wird
 * rol_id : 			ID der verwendeten Rolle
 * show_former_members: 0 - (Default) Nur aktive Mitglieder der Rolle anzeigen
 *                      1 - Nur ehemalige Mitglieder der Rolle anzeigen
 * 
 * Mit Aenderungen zur Formatierung von kossihh (Juni 2016)
 * 
 * Interface zum Plugin KeyManager:
 * 
 * kmf-... :		$_POST-Variablen, welche mit kmf- beginnen, werden durch das Plugin KeyManager übergeben
 * 					FormFiller übernimmt hierbei die Aufgabe des Ausdrucks
 * 
 ***********************************************************************************************
 */

use setasign\Fpdi\Fpdi;

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/../../adm_program/system/classes/tablefile.php');
require_once(__DIR__ . '/../../adm_program/system/classes/listconfiguration.php');
require_once(__DIR__ . '/../../adm_program/system/classes/image.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');
require_once(__DIR__ . '/libs/fpdf/fpdf.php');
require_once(__DIR__ . '/libs/fpdi/src/autoload.php');

// only the main script or the plugin keymanager can call and start this module
if (strpos($gNavigation->getUrl(), 'formfiller.php') === false && strpos($gNavigation->getUrl(), 'keys_export_to_pff.php') === false)
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$postFormID      		= admFuncVariableIsValid($_POST, 'form_id', 'numeric', array('defaultValue' => 0));
$postListId      		= admFuncVariableIsValid($_POST, 'lst_id', 'numeric', array('defaultValue' => 0));
$postRoleId      		= admFuncVariableIsValid($_POST, 'rol_id', 'numeric', array('defaultValue' => 0));
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
$relation = new TableUserRelation($gDb);
$relationArray = array();

if (isset($_POST['user_id']))
{
	$userArray = $_POST['user_id'];
}
elseif (($postListId > 0) && ($postRoleId > 0))
{
	//$list->getSQL($role_ids, $postShowFormerMembers) erwartet als Parameter für 
	//$role_ids ein Array, deshalb $postRoleId in ein Array umwandeln
	$role_ids[] = $postRoleId;
	$sql        = '';   // enthaelt das Sql-Statement fuer die Liste

	// create list configuration object and create a sql statement out of it
	$list = new ListConfiguration($gDb, $postListId);
	$sql = $list->getSQL($role_ids, $postShowFormerMembers);
		
	// SQL-Statement der Liste ausfuehren und pruefen ob Daten vorhanden sind
	$statement = $gDb->query($sql);
	
	while ($row = $statement->fetch())
	{
		$userArray[] = $row['usr_id'] ;
	}
}
elseif (isset($_POST['kmf-RECEIVER']))
{	//'kmf-RECEIVER' ist als 'Systemfeld' deklariert, kann nicht geloescht werden und eignet sich deshalb hervorragend zur Ueberpruefung
	//wenn es vorhanden ist, dann wurde 'createpdf.php' von KeyManager aus aufgerufen
	
	$pkmArray = array();                             //Info: pkm = (P)lugin (K)ey(M)anager
	foreach ($_POST as $postVar => $content)
	{
		if (strpos($postVar, 'kmf-') === 0)
		{
			$pkmArray[substr($postVar, 4)] = $content;
		}
	}

	$userArray[] = $pkmArray['RECEIVER'] > 0 ? $pkmArray['RECEIVER'] : 0;
}
else 
{
	//Fehlermeldung ausgeben, wenn Parameter fehlen
	$gMessage->show($gL10n->get('PLG_FORMFILLER_MISSING_PARAMETER'), $gL10n->get('SYS_ERROR'));
}

//initiate FPDF
if (!empty($pPreferences->config['Formular']['pdfform_orientation'][$postFormID]))
{
	$orientation = $pPreferences->config['Formular']['pdfform_orientation'][$postFormID];
}
else 
{
	$orientation = 'P';				//Default
}	
if (!empty($pPreferences->config['Formular']['pdfform_unit'][$postFormID]))
{
	$unit = $pPreferences->config['Formular']['pdfform_unit'][$postFormID];
}
else 
{
	$unit = 'mm';					//Default
}	
if (!empty($pPreferences->config['Formular']['pdfform_size'][$postFormID]))
{
	if (strstr($pPreferences->config['Formular']['pdfform_size'][$postFormID], ','))
	{
		$size = explode(',', $pPreferences->config['Formular']['pdfform_size'][$postFormID]);	
	}
	else 
	{
		$size = $pPreferences->config['Formular']['pdfform_size'][$postFormID];	
	}
}
else 
{
	$size = 'A4';					//Default
}	

$pdf = new FPDI($orientation, $unit, $size);

// falls ein Formular definiert wurde, dann ist der Wert der form_pdfid > 0
if ($pPreferences->config['Formular']['pdfid'][$postFormID] > 0)
{
	//pruefen, ob das Formular noch in der DB existiert
	$sql = 'SELECT fil_id 
              FROM '. TBL_FILES .' , '. TBL_CATEGORIES. ' , '. TBL_FOLDERS. '
             WHERE fil_id = \''.$pPreferences->config['Formular']['pdfid'][$postFormID].'\' 
               AND fil_fol_id = fol_id
               AND (  fol_org_id = '.ORG_ID.'
                OR fol_org_id IS NULL ) ';
    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();
         
	// Gibt es das Formular noch in der DB, wenn nicht: Fehlermeldung
    if (!isset($row->fil_id) && !(strlen($row->fil_id) > 0) )
    {
    	$gMessage->show($gL10n->get('SYS_FILE_NOT_EXIST'));
    }     
	
	// get recordset of current file from databse
	$file = new TableFile($gDb);
	
	$file->getFileForDownload($pPreferences->config['Formular']['pdfid'][$postFormID]);
    
	//kompletten Pfad der Datei holen
	$completePath = $file->getFullFilePath();

	//pruefen ob File ueberhaupt physikalisch existiert
	if (!file_exists($completePath))
	{
		$gMessage->show($gL10n->get('SYS_FILE_NOT_EXIST'));
	}
}

//sind Daten für Etiketten definiert?  (dann die Etikettendaten überpruefen)
$etiketten = explode(',', $pPreferences->config['Formular']['labels'][$postFormID]);
		
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
	if (isset($pPreferences->config['Formular'][$attribute][$postFormID]))
	{
		$attributesDefault[$attribute] = $pPreferences->config['Formular'][$attribute][$postFormID];
	}
}
	
//eventuell vorhandene Beziehungen einlesen
//da es zu Komplikationen fuehren koennte, wenn $userArray durchlaufen und gleichzeitig Werte darin geloescht werden
//wird das temporaere Array $userScanArray verwendet
$userScanArray = $userArray;
foreach ($userScanArray as $userId)
{
	$user->readDataById($userId);

	if (!empty($pPreferences->config['Formular']['relation'][$postFormID]) && $user->getValue('GENDER', 'text') === $gL10n->get('SYS_MALE'))
	{
		$sql = 'SELECT *
                  FROM '.TBL_USER_RELATIONS.'
            INNER JOIN '.TBL_USER_RELATION_TYPES.'
                    ON ure_urt_id  = urt_id
                 WHERE ure_usr_id1 = '.$userId.'
            	   AND urt_id = '.$pPreferences->config['Formular']['relation'][$postFormID].'
                   AND urt_name        <> \'\'
                   AND urt_name_male   <> \'\'
                   AND urt_name_female <> \'\'
              ORDER BY urt_name ASC LIMIT 1';
		$relationStatement = $gDb->query($sql);

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

foreach ($userArray as $userId)
{
	$user->readDataById($userId);
	$pageCounter = 1;								// notwendig bei importierten PDFs mit mehreren Seiten
	$pageNumber = 1;							    // notwendig bei importierten PDFs mit mehreren Seiten
	
	if ($zeile == 0 && $spalte == 0)
	{
		$pdf->AddPage();
		$pdf->SetAutoPageBreak(false);
	}
	
	while ($pageCounter <= $pageNumber )            // Schleife bei importierten PDFs mit mehreren Seiten
	{
		$sortArray = array();
		if ($pPreferences->config['Formular']['pdfid'][$postFormID]>0 && $zeile == 0 && $spalte == 0)
		{
			// set the sourcefile
			$pageNumber = $pdf->setSourceFile($completePath);
		
			if ($pageCounter > 1)
			{
				$pdf->AddPage();                   // zusaetzliches AddPage bei importierten PDFs mit mehreren Seiten
			}
			
			//import page
			$tplIdx = $pdf->importPage($pageCounter);
		
			//use the imported page...
			$pdf->useTemplate($tplIdx,0,0,null,null,true);
		}
		
		// jetzt alle Felder durchlaufen
		foreach ($pPreferences->config['Formular']['fields'][$postFormID] as $fieldkey => $fielddata)
		{ 
			//der zu schreibende Text koennte auch direkt in $sortArray geschrieben werden,
			//aber anhand der Variablen $text ist der Code etwas übersichtlicher :-) 
			$text = '';		
				
			//$fielddata splitten in Typ und ID
        	$fieldtype = substr($fielddata, 0, 1);
        	$fieldid = (int) substr($fielddata, 1);
        	
        	$formdata = $pPreferences->config['Formular']['positions'][$postFormID][$fieldkey];
			  
			if (!empty($formdata))
			{
				$xyKoord = array();
			
				//$formdata splitten in Koordinaten und Rest
				$arrSplit = explode(';', $formdata);

				// xyKoordinaten extrahieren und in $arrSplit loeschen
				$xyKoord = explode(',', array_shift($arrSplit));

				// Routine beenden, wenn nicht mindestens die Koordinaten für X und Y angegeben wurden
				if (count($xyKoord) < 2)
				{
					continue ;
				}
			
				$sortArray[] = array(
			 		'xykoord'    => $xyKoord, 
			 		'attributes' => $attributesDefault,
			 		'image'      => array('path'=>'', 'zufall'=>0),
			 		'text'       => $text,
					'trace'      => false ,
					'rect'       => false     );	
				$pointer = count($sortArray)-1;	
			
				//arrSplit zerlegen in ein assoziatives Array
				$fontData = array();		
				foreach ($arrSplit as $splitData)
				{
					$attr = explode('=', $splitData);
					$fontData[$attr[0]] = $attr[1];
				}
			
				// Parameter "P" auswerten (importierte PDF-Dokumente mit mehreren Seiten) 
				if ($pageCounter == 1)                // die erste Seite
				{
					if (array_key_exists('P', $fontData) && $fontData['P'] <> 1)
					{
						continue;                     //Feld bei Pruefung durchgefallen; zum naechsten Feld
					}
				}
				else 
				{
					if (!array_key_exists('P', $fontData) || (array_key_exists('P', $fontData) && $fontData['P'] <> $pageCounter))
					{
						continue;                     //Feld bei Pruefung durchgefallen; zum naechsten Feld
					}
				}			
			
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
				if (array_key_exists('F', $fontData) && in_array($fontData['F'], array('Courier', 'Arial', 'Times', 'Symbol', 'ZapfDingbats')))
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
						elseif ($gPreferences['profile_photo_storage'] == 1)              //Foto aus adm_my_files
						{
							if (file_exists(ADMIDIO_PATH . FOLDER_DATA . '/user_profile_photos/'.$userId.'.jpg'))
							{
								$sortArray[$pointer]['image']['path'] = ADMIDIO_PATH . FOLDER_DATA . '/user_profile_photos/'.$userId.'.jpg';
							}
						}
						elseif ($gPreferences['profile_photo_storage'] == 0)               //Foto aus der Datenbank
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
						elseif (array_key_exists('K', $fontData))
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

								$pos  = $user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'), 'database') - 1;

								if (array_key_exists('T', $fontData))    // Nehme n-ten Text aus Konfiguration
								{
									$textarray = explode(',', $fontData['T']);
									if (isset($textarray[$pos]))               // Wenn Text für diese Stelle definiert
									{
										$text = $textarray[$pos];
									}
									else                                      // sonst schreibe Leerzeichen
									{
										$text = ' ';
									}
								}
								else    // lese Wert aus Datenbank
								{
									$text = $user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'));
									if ((substr($text, 0, 4) == '<img') && (substr($text, -2) == '/>'))
									{
										// Option wurde mit Icon definiert, wir muessen aus dem HTML Tag das Title-Attribut auslesen
										$doc = new DOMDocument();
										$doc->loadXML($text);
										$nodeList = $doc->getElementsByTagName('img');
										$nodes = iterator_to_array($nodeList);
										$node = $nodes[0];
										if ($node->getattribute('title') == $gProfileFields->getPropertyById($fieldid, 'usf_name'))
										{
											// Kein Tooltip in der Option, nehme Icon-Name als Wert
											$text = $node->getattribute('src');
											$text = substr($text, strrpos($text, '/') + 1);
											$texttemp = explode('.', $text, 2);
											$text = $texttemp[0];
										}
										else
										{
											$text = $node->getattribute('title');									
										}
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
								break;
							
							case 'CHECKBOX':
								if ($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')))
								{
									$text = 'x';
								}
								break;
							
							default:
								$text = $user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'));
						}
						break;
				
					case 'b'  &&  array_key_exists($userId, $relationArray):
				
						$user->readDataById($relationArray[$userId]);

						switch ($gProfileFields->getPropertyById($fieldid, 'usf_type'))
						{
							case 'RADIO_BUTTON':
							case 'DROPDOWN':
				
								$pos = $user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'), 'database') - 1;
				
								if (array_key_exists('T', $fontData))    // Nehme n-ten Text aus Konfiguration
								{
									$textarray = explode(',', $fontData['T']);
									if (isset($textarray[$pos]))               // Wenn Text für diese Stelle definiert
									{
										$text = $textarray[$pos];
									}
									else                                      // sonst schreibe Leerzeichen
									{
										$text = ' ';
									}
								}
								else    // lese Wert aus Datenbank
								{
									$text = $user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'));
									if ((substr($text, 0, 4) == '<img') && (substr($text, -2) == '/>'))
									{
										// Option wurde mit Icon definiert, wir muessen aus dem HTML Tag das Title-Attribut auslesen
										$doc = new DOMDocument();
										$doc->loadXML($text);
										$nodeList = $doc->getElementsByTagName('img');
										$nodes = iterator_to_array($nodeList);
										$node = $nodes[0];
										if ($node->getattribute('title') == $gProfileFields->getPropertyById($fieldid, 'usf_name'))
										{
											// Kein Tooltip in der Option, nehme Icon-Name als Wert
											$text = $node->getattribute('src');
											$text = substr($text, strrpos($text, '/') + 1);
											$texttemp = explode('.', $text, 2);
											$text = $texttemp[0];
										}
										else
										{
											$text = $node->getattribute('title');
										}
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
								break;
							
							case 'CHECKBOX':
								if ($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')))
								{
									$text = 'x';
								}
								break;
							
							default:
							$text = $user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'));
						}
						$user->readDataById($userId);
						break;
					
					case 't':              // trace				
						//pruefen, ob Koordinaten x2 und y2 vorhanden sind
						if (count($xyKoord) < 4)
						{
							continue ;      
						}
				
						$sortArray[$pointer]['trace'] = true;
						break;  
				
					case 'r':              // rectangle				
						//pruefen, ob Koordinaten w und h vorhanden sind
						if (count($xyKoord) < 4)
						{
							continue ;     
						}
				
						$sortArray[$pointer]['rect'] = true;
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
				$pdf->SetFont($sortData['attributes']['font'], $sortData['attributes']['style'], $sortData['attributes']['size']);	
				$pdf->Write(0,utf8_decode($sortData['text']));	
			}
				
			// ggf. eine temporaer erzeugte Bilddatei wieder loeschen
			if(file_exists( ADMIDIO_PATH . FOLDER_DATA . '/PFF'.$sortData['image']['zufall'].'.png'))
       	 	{
        		unlink( ADMIDIO_PATH . FOLDER_DATA . '/PFF'.$sortData['image']['zufall'].'.png');
        	}	
		}	
		$pageCounter++;							// genutzt bei importierten PDFs mit mehreren Seiten
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
	$pdf->Output($pPreferences->config['Formular']['desc'][$postFormID].'.pdf', 'D');	
}
else 
{
	$pdf->Output();
}
