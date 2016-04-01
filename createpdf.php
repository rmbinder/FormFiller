<?php
/**
 ***********************************************************************************************
 * Erzeugt und befuellt die PDF-Datei fuer das Admidio-Plugin FormFiller
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * user_id : 		ID des Mitglieds, dessen Daten ausgelesen werden
 * form_id :		interne Nummer des verwendeten PDF-Formulars
 * 					Hinweis: form_id wird abhängig vom aufrufenden Programm
 * 						     entweder über $_GET oder über $_POST übergeben
 * lst_id : 		Liste, deren Konfiguration verwendet wird
 * rol_id : 		ID der verwendeten Rolle
 * show_members : 	0 - (Default) aktive Mitglieder der Rolle anzeigen
 *                	1 - Ehemalige Mitglieder der Rolle anzeigen
 *                	2 - Aktive und ehemalige Mitglieder der Rolle anzeigen
 *
 * Hinweis: Abhängig vom aufrufenden Programm wird
 * 	   entweder user_id oder (lst_id und rol_id und show_members) übergeben
 ***********************************************************************************************
 */

require_once(substr(__FILE__, 0,strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/common.php');
require_once(SERVER_PATH. '/adm_program/system/classes/tablefile.php');
require_once(SERVER_PATH. '/adm_program/system/classes/listconfiguration.php');
require_once(SERVER_PATH. '/adm_program/system/classes/image.php');

require_once(dirname(__FILE__).'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 
require_once($plugin_path. '/'.$plugin_folder.'/library/fpdf.php');
require_once($plugin_path. '/'.$plugin_folder.'/library/fpdi.php');

// Initialize and check the parameters
$getUserId       = admFuncVariableIsValid($_GET, 'user_id', 'numeric', array('defaultValue' => 0));
$getFormID       = admFuncVariableIsValid($_GET, 'form_id', 'numeric', array('defaultValue' => 0));
$postFormID      = admFuncVariableIsValid($_POST, 'form_id', 'numeric', array('defaultValue' => 0));
$postListId      = admFuncVariableIsValid($_POST, 'lst_id', 'numeric', array('defaultValue' => 0));
$postRoleId      = admFuncVariableIsValid($_POST, 'rol_id', 'numeric', array('defaultValue' => 0));
$postShowMembers = admFuncVariableIsValid($_POST, 'show_members', 'numeric', array('defaultValue' => 0));

$userArray = array();
unset($role_ids);
$getpostFormID = 0;
$spalte = 0;
$zeile = 0;	
$etikettenText = array();
		
$user = new User($gDb, $gProfileFields);

// Konfiguration einlesen
$pPreferences = new ConfigTablePFF();
$pPreferences->read();

// wenn von der Profilanzeige aufgerufen wurde, dann ist $getUserId>0
// und form_id wurde über $_GET übergeben
if($getUserId > 0)
{
	$userArray[] = $getUserId ;
	$getpostFormID = $getFormID;
}
elseif(($postListId > 0) && ($postRoleId > 0))
{
	//$list->getSQL($role_ids, $postShowMembers) erwartet als Parameter für 
	//$role_ids ein Array, deshalb $postRoleId in ein Array umwandeln
	$role_ids[] = $postRoleId;
	$sql        = '';   // enthaelt das Sql-Statement fuer die Liste

	// create list configuration object and create a sql statement out of it
	$list = new ListConfiguration($gDb, $postListId);
	$sql = $list->getSQL($role_ids, $postShowMembers);
		
	// SQL-Statement der Liste ausfuehren und pruefen ob Daten vorhanden sind
	$statement = $gDb->query($sql);
	
	while ($row = $statement->fetch())
	{
		$userArray[] = $row['usr_id'] ;
	}
	$getpostFormID = $postFormID;
}
else 
{
	//Fehlermeldung ausgeben, wenn Parameter fehlen
	$gMessage->show($gL10n->get('PLG_FORMFILLER_MISSING_PARAMETER'),$gL10n->get('SYS_ERROR'));
}

//initiate FPDF
$pdf = new FPDI('P','mm','A4');

// falls ein Formular definiert wurde, dann ist der Wert der form_pdfid>0
if ($pPreferences->config['Formular']['pdfid'][$getpostFormID]>0)
{
	//prüfen, ob das Formular noch in der DB existiert
	$sql = 'SELECT fil_id 
            FROM '.TBL_FILES.' , '. TBL_CATEGORIES. ', '. TBL_FOLDERS. '
            WHERE fil_id = \''.$pPreferences->config['Formular']['pdfid'][$getpostFormID].'\' 
            AND fil_fol_id = fol_id
            AND (  fol_org_id = '.$gCurrentOrganization->getValue('org_id').'
            	OR fol_org_id IS NULL ) ';
    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();
         
	// Gibt es das Formular noch in der DB, wenn nicht: Fehlermeldung
    if( !isset($row->fil_id) && !(strlen($row->fil_id) > 0) )
    {
    	$gMessage->show($gL10n->get('SYS_FILE_NOT_EXIST'));
    }     
	
	// get recordset of current file from databse
	$file = new TableFile($gDb);
	
	$file->getFileForDownload($pPreferences->config['Formular']['pdfid'][$getpostFormID]);
    
	//kompletten Pfad der Datei holen
	$completePath = $file->getCompletePathOfFile();

	//pruefen ob File ueberhaupt physikalisch existiert
	if (!file_exists($completePath))
	{
		$gMessage->show($gL10n->get('SYS_FILE_NOT_EXIST'));
	}
}

//sind Daten für Etiketten definiert?  (dann die Etikettendaten überprüfen)
$etiketten = explode(',',$pPreferences->config['Formular']['labels'][$getpostFormID]);
		
if(count($etiketten)==4)
{
	foreach ($etiketten as $data)
	{
		if(!(is_numeric($data)))
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
//wenn 	count($etiketten) jetzt nicht 0 ist, dann wird Etikettendruck durchgeführt	
						
foreach($userArray as $UserId)
{
	$user->readDataById($UserId);
	
	if ($zeile==0 && $spalte==0)
	{
		$pdf->AddPage();
		$pdf->SetAutoPageBreak(false);
	}

	if ($pPreferences->config['Formular']['pdfid'][$getpostFormID]>0 && $zeile==0 && $spalte==0)
	{
		// set the sourcefile
		$pdf->setSourceFile($completePath);

		//import page
		$tplIdx = $pdf->importPage(1);

		//use the imported page...
		$pdf->useTemplate($tplIdx,null,null,0,0,true);
	}

	// nur zur Info: FPDI kann auch die Größe einer PDF-Datei auslesen
	//$arr_size = $pdf->getTemplateSize($tplIdx);
	
	// zuerst mal Standardschriftfarbe festlegen (falls nichts definiert wurde)
	$pdf->SetTextColor(0,0,0);

	// und Standardschrift, -stil und -größe festlegen (falls nichts definiert wurde)
	$pdf->SetFont('Arial','BI',10);
	
	// jetzt alle Felder durchlaufen
	//foreach($gProfileFields->mProfileFields as $field )
	foreach($pPreferences->config['Formular']['fields'][$getpostFormID] as $key => $fielddata)
	{ 
		$text = '';
	
		//$fielddata splitten in Typ und ID
        $fieldtype=substr($fielddata,0,1);
        $fieldid=substr($fielddata,1);
        	
        $formdata = $pPreferences->config['Formular']['positions'][$getpostFormID][$key];
        	
		// Textfarbe mit den Daten der jeweiligen Konfiguration überschreiben
		$color = explode(',',$pPreferences->config['Formular']['color'][$getpostFormID]);
		$pdf->SetTextColor($color[0],$color[1],$color[2]);
			  
		// Font mit den Daten der jeweiligen Konfiguration überschreiben
		$pdf->SetFont($pPreferences->config['Formular']['font'][$getpostFormID],
			  	  	  $pPreferences->config['Formular']['style'][$getpostFormID],
			 	  	  $pPreferences->config['Formular']['size'][$getpostFormID]   );										
			 	  	  
		if(!empty($formdata))
		{
			//zuerst mal sehen, ob Schrift-Parameter angefügt sind (wenn sich ein ';' darin befindet)
			$xyKoord = array();
			$gender = array('x','x');
			
			//$formdata splitten in Koordinaten und Rest
			$arrSplit = explode(';',$formdata);

			// xyKoordinaten extrahieren und in $arrSplit löschen
			$xyKoord = explode(',',array_shift($arrSplit));

			// Routine beenden, wenn nicht mindestens die Koordinaten für X und Y angegeben wurden
			if (count($xyKoord)<2)
			{
				continue ;
			}
			$pdf->SetXY($xyKoord[0], $xyKoord[1]);
					
			//arrSplit zerlegen in ein assoziatives Array
			$fontData = array();		
			foreach($arrSplit as $splitData)
			{
				$fontData[substr($splitData,0,1)] = substr($splitData,2) ;	
			}
		
			// wurde eine abweichende Schriftfarbe definiert? ->  prüfen und ggf. überschreiben
			if ( array_key_exists ( 'C', $fontData ) )
			{
				// jetzt mit den konfigurierten Daten überschreiben
				$color = explode(',',$fontData['C']);
				$key = true;
				foreach ($color as $data)
				{
					if(!(is_numeric($data)))
					{
						$key = false;
						break;
					}
				}
				if ($key)
				{
					$pdf->SetTextColor($color[0],$color[1],$color[2]);					
				}
			}
			
			// wurde eine abweichende Schriftgröße definiert? -> prüfen und bei Syntaxfehler ggf. löschen
			if ( array_key_exists ( 'S', $fontData ) && !(is_numeric($fontData['S'])))
			{
				unset($fontData['S']);
			}	

			// wurde ein abweichender Schrifttyp definiert? -> prüfen und bei Syntaxfehler ggf. löschen
			if ( array_key_exists ( 'F', $fontData ) && !(in_array($fontData['F'],array('Courier','Arial','Times','Symbol','ZapfDingbats' ))))
			{
				unset($fontData['F']);
			}			

			// wurden abweichende Schriftattribute definiert? -> prüfen und bei Syntaxfehler ggf. löschen
			if ( array_key_exists ( 'A', $fontData ) && !(strstr_multiple('BIU',$fontData['A'])) )
			{
				unset($fontData['A']);
			}	
		
			// wurden abweichende Schriftart, Schriftstil oder Schriftgröße definiert?	-> überschreiben	
			$pdf->SetFont(
				((array_key_exists ( 'F', $fontData )) ? $fontData['F'] : $pPreferences->config['Formular']['font'][$getpostFormID]),
				((array_key_exists ( 'A', $fontData )) ? $fontData['A'] : $pPreferences->config['Formular']['style'][$getpostFormID]),
				((array_key_exists ( 'S', $fontData )) ? $fontData['S'] : $pPreferences->config['Formular']['size'][$getpostFormID])   );		

			switch ($fieldtype)
			{
				case 'l':
					$image  = null;
					$zufall = 0;
					$imageW = 0;
					$imageH = 0;
					$picpath= '';	
		
					// wurden Parameter für Weite und Höhe übergeben?
					if (isset($xyKoord[2]))
					{
						$imageW = $xyKoord[2];
						if (isset($xyKoord[3]))
						{
							$imageH = $xyKoord[3];		
						}	
					}

					if ( array_key_exists ( 'L', $fontData ) )                         //Foto aus einer alternativen Bilddatei)
					{
						if(file_exists(SERVER_PATH. '/adm_my_files/download/'.$fontData['L']))
						{
							$picpath = SERVER_PATH. '/adm_my_files/download/'.$fontData['L'];
						}	
					}
					elseif($gPreferences['profile_photo_storage'] == 1 )              //Foto aus adm_my_files
					{
						if(file_exists(SERVER_PATH. '/adm_my_files/user_profile_photos/'.$UserId.'.jpg'))
						{
							$picpath = SERVER_PATH. '/adm_my_files/user_profile_photos/'.$UserId.'.jpg';
						}
					}
					elseif($gPreferences['profile_photo_storage'] == 0 )               //Foto aus der Datenbank
					{		
						if(strlen($user->getValue('usr_photo')) != NULL)
    					{
        					$image = new Image();
        					$image->setImageFromData($user->getValue('usr_photo'));
        
        					// die Methode Image der Klasse FPDF benötigt einen Pfad zur Imagedatei
        					// ich habe es nicht geschafft von der Klasse Image direkt an die Klasse FPDF diesen Pfad zu übergeben
        					// deshalb der Umweg über eine temporäre Datei
        					$zufall = mt_rand(10000,99999);
        					$image->copyToFile(null,SERVER_PATH. '/adm_my_files/PFF'.$zufall.'.png');
        					$image->delete();
        					$picpath = SERVER_PATH. '/adm_my_files/PFF'.$zufall.'.png';   
    					}
					}

					//Bild nur in PDF-Datei schreiben, wenn auch ein Bild zum Mitglied gefunden wurde
					if ($picpath<>'')
					{
       					$pdf->Image($picpath,$xyKoord[0],$xyKoord[1], $imageW, $imageH);

						// ggf. die temporär angelegte Bilddatei wieder löschen
						if(file_exists(SERVER_PATH. '/adm_my_files/PFF'.$zufall.'.png'))
            			{
                			unlink(SERVER_PATH. '/adm_my_files/PFF'.$zufall.'.png');
            			}
					}
					break;

				case 'v':
					// wurde ein Wert definiert? 
					if ( array_key_exists ( 'V', $fontData ) )
					{
						$text = $fontData['V'];	               // Hinweis: der übergebene Inhalt wird nicht überprüft 
					}
					break;	

				case 'd':
					$text = date("d.m.Y");
				
					// wurden Werte für das Datum definiert? 
					if ( array_key_exists ( 'D', $fontData ) )
					{
						$text = date($fontData['D']);	       // Hinweis: der übergebene Inhalt wird nicht überprüft 
					}	
					break;  

				case 'p':
        			if($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')=='GENDER')
        			{
						if ( array_key_exists ( 'T', $fontData ) )       // wurden Werte für das Geschlecht definiert?
						{
							$gender = explode(',',$fontData['T']);
							if(!isset($gender[0]))
							{
								$gender[0]='x';
							}
							if(!isset($gender[1]))
							{
								$gender[1]=$gender[0];
							}
						}	
			
        				if(strstr($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')),'female'))
        				{
        	    			//female
        					if (isset($xyKoord[2]) && isset($xyKoord[3]))
        					{
        						$pdf->SetXY($xyKoord[2], $xyKoord[3]);
        					}
        					$text = $gender[1];
        				}
        				elseif(strstr($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')),'male')) 
        				{
        					$text = $gender[0];
        				}
        			}
					elseif($gProfileFields->getPropertyById($fieldid, 'usf_type') == 'CHECKBOX')
        			{
        				if($user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern')))
        				{
        					$text = 'x';
        				}
        		}
        		else 
        		{
        			$text= $user->getValue($gProfileFields->getPropertyById($fieldid, 'usf_name_intern'));
        		}
        		break;
			}
			
			// wurde optionaler Text angegeben?   (von lagro)
         	if ( array_key_exists ( '}', $fontData ) )
         	{
            	$text .= $fontData['}'];
         	}
         	if ( array_key_exists ( '{', $fontData ) )
         	{
            	$text = $fontData['{'].$text;
         	}

        	//über ein Hilfsarray gehen, falls mit Etiketten gearbeitet wird
        	if (count($etiketten)>0)
        	{
        		$etikettenText[$pdf->GetY()][$pdf->GetX()]=$text;
        	}
        	else 
        	{
        		$pdf->Write(0,utf8_decode($text));
        	}
		}		
	}  // zum naechsten Profilfeld 

	$text = '';
	if (count($etiketten)>0)
    {        	
        foreach($etikettenText as $yKoord => $zeileData)	
        {
        	$pdf->SetY($yKoord+($zeile*$etiketten[3]));
        	
        	ksort($zeileData);
        	reset($zeileData);
        	$xKoord = key($zeileData);
        	
        	$pdf->SetX($xKoord+($spalte*$etiketten[1]));
        	
        	$text = '';
        	foreach($zeileData as $spalteText => $spalteData)
        	{
        		$text .= $spalteData.' ';
        	}
        	$pdf->Write(0,utf8_decode($text));
        }
	
    	unset($etikettenText);
        
		$spalte++;
		if($spalte==$etiketten[0])
		{
			$spalte=0;
			$zeile++;
		}
		if($zeile==$etiketten[2])
		{
			$zeile=0;
		}
    }
}  // zum naechsten User

if($pdf->PageNo() > $pPreferences->config['Optionen']['maxpdfview'] )
{
	$pdf->Output($pPreferences->config['Formular']['desc'][$getpostFormID].'.pdf','D');	
}
else 
{
	$pdf->Output();
}
