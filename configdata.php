<?php
/******************************************************************************
 * 
 * configdata.php
 *   
 * Konfigurationsdaten fuer das Admidio-Plugin FormFiller
 * 
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 ****************************************************************************/

global $gL10n, $gProfileFields;

//Standardwerte einer Neuinstallation oder beim Anfügen einer zusätzlichen Konfiguration
$config_default['Pluginfreigabe']['freigabe'] = array(	getRole_IDPFF($gL10n->get('SYS_WEBMASTER')),
													getRole_IDPFF($gL10n->get('SYS_MEMBER')));    		
$config_default['Pluginfreigabe']['freigabe_config'] = array(	getRole_IDPFF($gL10n->get('SYS_WEBMASTER')),
															getRole_IDPFF($gL10n->get('SYS_MEMBER')));    		

$config_default['Formular'] = array('desc' 			=> array($gL10n->get('PFF_PATTERN'),
															 $gL10n->get('PFF_ENVELOPE'),
															 $gL10n->get('PFF_ADDRESSLABELS') ),
 									'font' 			=> array('Courier','Arial','Arial'), 
 									'style'			=> array('','B',''),
 									'size'			=> array(10,12,12),
 		 							'color'			=> array('0,0,0','0,0,0','0,0,0'),
 									'labels'		=> array('','1,0,1,0','3,50,7,40'),
 									'fields'		=> array(array(	'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('ADDRESS', 'usf_id'),
 																	'p'.$gProfileFields->getProperty('POSTCODE', 'usf_id'),
																	'p'.$gProfileFields->getProperty('CITY', 'usf_id'),
																	'vdummy'),
															 array(	'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('ADDRESS', 'usf_id'),
 																	'p'.$gProfileFields->getProperty('POSTCODE', 'usf_id'),
																	'p'.$gProfileFields->getProperty('CITY', 'usf_id'),
																	'p'.$gProfileFields->getProperty('GENDER', 'usf_id')),
															 array(	'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('ADDRESS', 'usf_id'),
 																	'p'.$gProfileFields->getProperty('POSTCODE', 'usf_id'),
																	'p'.$gProfileFields->getProperty('CITY', 'usf_id'),
																	'p'.$gProfileFields->getProperty('GENDER', 'usf_id'))
															),
 									'positions'		=> array(array(	'10,20;{=Hallo ;}=,',
																	'20,30;A=B',
 																	'30,40;S=15',
																	'40,50',
																	'50,60',
																	'60,70;V=Das ist ein Beispieltext'),
															array(	'26,65',
																	'27,65',
 																	'25,70',
																	'25,75',
																	'26,75',
																	'25,65;T=Herrn,Frau'),
															array(	'25,30',
																	'26,30',
 																	'25,35',
																	'25,40',
																	'26,40',
																	'25,25;T=Herrn,Frau')
															),
 									'pdfid'			=> array('0','0','0') ); 	   		
 
$config_default['Optionen']['maxpdfview'] = 10; 
    
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';
   	
/*
 *  Mittels dieser Zeichenkombinationen werden Konfigurationsdaten, die zur Laufzeit als Array verwaltet werden,
 *  zu einem String zusammengefasst und in der Admidiodatenbank gespeichert. 
 *  Müssen die vorgegebenen Zeichenkombinationen (#_# und #!#) jedoch ebenfalls, z.B. in der Beschreibung 
 *  einer Konfiguration, verwendet werden, so kann das Plugin gespeicherte Konfigurationsdaten 
 *  nicht mehr richtig einlesen. In diesem Fall sind die vorgegebenen Zeichenkombination abzuändern (z.B. in !-!)
 *  
 *  Achtung: Vor einer Änderung muss eine Deinstallation durchgeführt werden!
 *  Bereits gespeicherte Werte in der Datenbank können nach einer Änderung nicht mehr eingelesen werden!
 */
$dbtoken  = '#_#';  
$dbtoken2 = '#!#';  

?>
