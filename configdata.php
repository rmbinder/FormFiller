<?php
/**
 ***********************************************************************************************
 * Konfigurationsdaten fuer das Admidio-Plugin FormFiller
 *
 * @copyright 2004-2024 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

global $gProfileFields;

//Standardwerte einer Neuinstallation oder beim Anfuegen einer zusaetzlichen Konfiguration		
$config_default['Formular'] = array('desc' 			=> array($GLOBALS['gL10n']->get('PLG_FORMFILLER_PATTERN')),
 									'font' 			=> array('Courier','Arial','Arial'), 
 									'style'			=> array('','B',''),
 									'size'			=> array(10,12,12),
 		 							'color'			=> array('0,0,0','0,0,0','0,0,0'),
 									'labels'		=> array('','1,0,1,0','3,50,7,40'),
 									'fields'		=> array(array(	'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('STREET', 'usf_id'),
 																	'p'.$gProfileFields->getProperty('POSTCODE', 'usf_id'),
																	'p'.$gProfileFields->getProperty('CITY', 'usf_id'),
																	'vdummy')),
 									'positions'		=> array(array(	'10,20;{=Hallo ;}=,',
																	'20,30;A=BIU',
 																	'30,40;S=15',
																	'40,50',
																	'50,60;A=B;C=255,102,255',
																	'60,70;V=Das ist ein Beispieltext')),
 									'pdfid'					=> array('0','0','0'),
									'pdfform_orientation'	=> array('','',''),
									'pdfform_size'			=> array('','',''),
									'pdfform_unit'			=> array('','',''),
									'relation'				=> array('','','')  ); 	   		
 
$config_default['Optionen']['maxpdfview'] = 10; 
$config_default['Optionen']['pdfform_addsizes'] = '100x80'; 
    
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';
 
//Zugriffsberechtigung für das Modul preferences
$config_default['access']['preferences'] = array();
  	
/*
 *  Mittels dieser Zeichenkombinationen werden Konfigurationsdaten, die zur Laufzeit als Array verwaltet werden,
 *  zu einem String zusammengefasst und in der Admidiodatenbank gespeichert. 
 *  Muessen die vorgegebenen Zeichenkombinationen (#_# und #!#) jedoch ebenfalls, z.B. in der Beschreibung 
 *  einer Konfiguration, verwendet werden, so kann das Plugin gespeicherte Konfigurationsdaten 
 *  nicht mehr richtig einlesen. In diesem Fall sind die vorgegebenen Zeichenkombination abzuaendern (z.B. in !-!)
 *  
 *  Achtung: Vor einer Aenderung muss eine Deinstallation durchgefuehrt werden!
 *  Bereits gespeicherte Werte in der Datenbank koennen nach einer Aenderung nicht mehr eingelesen werden!
 */
$dbtoken  = '#_#';  
$dbtoken2 = '#!#';  
