<?php
/**
 ***********************************************************************************************
 * Class manages the configuration table
 *
 * @copyright 2004-2024 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Klasse verwaltet die Konfigurationstabelle "adm_plugin_preferences"
 *
 * Folgende Methoden stehen zur Verfuegung:
 *
 * init()						:	prueft, ob die Konfigurationstabelle existiert,
 * 									legt sie ggf. an und befuellt sie mit Default-Werten
 * save() 					    : 	schreibt die Konfiguration in die Datenbank
 * read()						:	liest die Konfigurationsdaten aus der Datenbank
 * checkforupdate()	            :	vergleicht die Angaben in der Datei version.php
 * 									mit den Daten in der DB
 * delete($deinst_org_select)	:	loescht die Konfigurationsdaten in der Datenbank
 *
 *****************************************************************************/
	
class ConfigTablePFF
{
	public	  $config		= array();     ///< Array mit allen Konfigurationsdaten
	
	protected $table_name;
	protected static $shortcut =  'PFF';
	protected static $version ;
	protected static $stand;
	protected static $dbtoken;
	protected static $dbtoken2;
	
	public  $config_default = array();	
	
    /**
     * ConfigTablePFF constructor
     */
	public function __construct()
	{
		global  $g_tbl_praefix;
		
		require_once(__DIR__ . '/../version.php');
		include(__DIR__ . '/../configdata.php');
		
		$this->table_name = $g_tbl_praefix.'_plugin_preferences';

		if (isset($plugin_version))
		{
			self::$version = $plugin_version;
		}
		if (isset($plugin_stand))
		{
			self::$stand = $plugin_stand;
		}
		if (isset($dbtoken))
		{
			self::$dbtoken = $dbtoken;
		}
		if (isset($dbtoken2))
		{
			self::$dbtoken2 = $dbtoken2;
		}		
		$this->config_default = $config_default;
	}
	
    /**
     * Prueft, ob die Konfigurationstabelle existiert, legt sie ggf an und befuellt sie mit Standardwerten
     * @return void
     */
	public function init()
	{
		global $gProfileFields;
	
		$config_ist = array();
		
		// pruefen, ob es die Tabelle bereits gibt
		$sql = 'SHOW TABLES LIKE \''.$this->table_name.'\' ';
		$statement = $GLOBALS['gDb']->queryPrepared($sql);
    
    	// Tabelle anlegen, wenn es sie noch nicht gibt
     	if (!$statement->rowCount())
    	{
    		// Tabelle ist nicht vorhanden --> anlegen
        	$sql = 'CREATE TABLE '.$this->table_name.' (
            	plp_id 		integer     unsigned not null AUTO_INCREMENT,
            	plp_org_id 	integer   	unsigned not null,
    			plp_name 	varchar(255) not null,
            	plp_value  	text, 
            	primary key (plp_id) )
            	engine = InnoDB
         		auto_increment = 1
          		default character set = utf8
         		collate = utf8_unicode_ci';
    		$GLOBALS['gDb']->queryPrepared($sql);
    	} 
    
		$this->read();
	
		$this->config['Plugininformationen']['version'] = self::$version;
		$this->config['Plugininformationen']['stand'] = self::$stand;
	
		// die eingelesenen Konfigurationsdaten in ein Arbeitsarray kopieren
		$config_ist = $this->config;
	
		// die default_config durchlaufen
		foreach ($this->config_default as $section => $sectiondata)
    	{
        	foreach ($sectiondata as $key => $value)
        	{
        		// gibt es diese Sektion bereits in der config?
        		if (isset($config_ist[$section][$key]))
        		{
        			// wenn ja, diese Sektion in der Ist-config loeschen
        			unset($config_ist[$section][$key]);
        		}
        		else
        		{
        			// wenn nicht, diese Sektion in der config anlegen und mit den Standardwerten aus der Default-config befuellen
        			$this->config[$section][$key]=$value;
        		}
        	}
        	// leere Abschnitte (=leere Arrays) loeschen
        	if ((isset($config_ist[$section]) && count($config_ist[$section]) == 0))
        	{
        		unset($config_ist[$section]);
        	}
    	}
    	
    	// Falls Formularkonfigurationen hinzugefügt oder geloescht wurden, dann stimmt die Anzahl der Beispiele
    	// in den Musterkonfigurationen (3 Stueck) nicht mehr. Dies fuehrt zur Fehlermeldung "Undefined offset....",
    	// deshalb hier alle Formularkonfigurationen pruefen
    	$conf_count = sizeof($this->config['Formular']['desc']);
    	foreach ($this->config['Formular'] as $key => $value)
    	{   		
    		while (sizeof($this->config['Formular'][$key]) > $conf_count)
    		{
    			array_pop($this->config['Formular'][$key]);
    		}
    		while (sizeof($this->config['Formular'][$key]) < $conf_count)
    		{
    			if (is_array($value[0]))
    			{
    				$this->config['Formular'][$key][] = array('');
    			}
    			else 
    			{
    				$this->config['Formular'][$key][]='';	
    			}
    		}
    	}
   
    	// die Ist-config durchlaufen 
    	// jetzt befinden sich hier nur noch die DB-Einträge, die nicht verwendet werden und deshalb: 
    	// 1. in der DB geloescht werden können
    	// 2. in der normalen config geloescht werden koennen
		foreach ($config_ist as $section => $sectiondata)
    	{
    		foreach ($sectiondata as $key => $value)
        	{
        		$plp_name = self::$shortcut.'__'.$section.'__'.$key;
				$sql = 'DELETE FROM '.$this->table_name.'
        				      WHERE plp_name = ? 
        				        AND plp_org_id = ? ';
				$GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));
                
				unset($this->config[$section][$key]);
        	}
			// leere Abschnitte (=leere Arrays) loeschen
        	if (count($this->config[$section]) == 0)
        	{
        		unset($this->config[$section]);
        	}
    	}        			
        			
    	// die aktualisierten und bereinigten Konfigurationsdaten in die DB schreiben 
  		$this->save();
	}

    /**
     * Schreibt die Konfigurationsdaten in die Datenbank
     * @return void
     */
	public function save()
	{
    	foreach ($this->config as $section => $sectiondata)
    	{
        	foreach ($sectiondata as $sectiondatakey => $sectiondatavalue)
        	{
            	if (is_array($sectiondatavalue))
            	{
        			for ($i = 0; $i < count($sectiondatavalue); $i++)
    				{
    					if (is_array($sectiondatavalue[$i]))
        				{
        					// um diesen Datensatz in der Datenbank als Array zu kennzeichnen, wird er von Doppelklammern eingeschlossen 
            				$sectiondatavalue[$i] = '(('.implode(self::$dbtoken2,$sectiondatavalue[$i]).'))';
        				}
   					}

                	// um diesen Datensatz in der Datenbank als Array zu kennzeichnen, wird er von Doppelklammern eingeschlossen 
            		$sectiondatavalue = '(('.implode(self::$dbtoken,$sectiondatavalue).'))';
            	} 
            
  				$plp_name = self::$shortcut.'__'.$section.'__'.$sectiondatakey;
          
            	$sql = ' SELECT plp_id 
            			   FROM '.$this->table_name.' 
            			  WHERE plp_name = ? 
            			    AND ( plp_org_id = ?
                 		     OR plp_org_id IS NULL ) ';
            	$statement = $GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));
            	$row = $statement->fetchObject();

            	// Gibt es den Datensatz bereits?
            	// wenn ja: UPDATE des bestehende Datensatzes  
            	if (isset($row->plp_id) AND strlen($row->plp_id) > 0)
            	{
               	    $sql = 'UPDATE '.$this->table_name.' 
                			   SET plp_value = ?
                			 WHERE plp_id = ? ';   
                    $GLOBALS['gDb']->queryPrepared($sql, array($sectiondatavalue, $row->plp_id));      
            	}
            	// wenn nicht: INSERT eines neuen Datensatzes 
            	else
            	{
 					$sql = 'INSERT INTO '.$this->table_name.' (plp_org_id, plp_name, plp_value) 
  							VALUES (? , ? , ?)  -- $GLOBALS[\'gCurrentOrgId\'], self::$shortcut.\'__\'.$section.\'__\'.$sectiondatakey, $sectiondatavalue '; 
 					$GLOBALS['gDb']->queryPrepared($sql, array($GLOBALS['gCurrentOrgId'], self::$shortcut.'__'.$section.'__'.$sectiondatakey, $sectiondatavalue));
            	}   
        	} 
    	}
	}

    /**
     * Liest die Konfigurationsdaten aus der Datenbank
     * @return void
     */
	public function read()
	{
	    $sql = 'SELECT plp_id, plp_name, plp_value
             	  FROM '.$this->table_name.'
             	 WHERE plp_name LIKE ?
             	   AND ( plp_org_id = ?
                 	OR plp_org_id IS NULL ) ';
		$statement = $GLOBALS['gDb']->queryPrepared($sql, array(self::$shortcut.'__%', $GLOBALS['gCurrentOrgId'])); 
	
        while ($row = $statement->fetch())
		{
			$array = explode('__',$row['plp_name']);
		
			// wenn plp_value von ((  )) eingeschlossen ist, dann ist es als Array einzulesen
			if ((substr($row['plp_value'],0,2) == '((' ) && (substr($row['plp_value'],-2) == '))' ))
        	{
        		$row['plp_value'] = substr($row['plp_value'], 2, -2);
        		$this->config[$array[1]] [$array[2]] = explode(self::$dbtoken,$row['plp_value']); 
        		
        		//das erzeugte Array durchlaufen, auf (( )) pruefen und ggf. nochmal zerlegen
        		for ($i = 0; $i < count($this->config[$array[1]] [$array[2]]); $i++)
    			{
    				if ((substr($this->config[$array[1]] [$array[2]][$i],0,2) == '((' ) && (substr($this->config[$array[1]] [$array[2]][$i],-2) == '))' ))
        			{
        				$temp = substr($this->config[$array[1]] [$array[2]][$i], 2, -2);
        				$this->config[$array[1]] [$array[2]][$i] = array();
        				$this->config[$array[1]] [$array[2]][$i] = explode(self::$dbtoken2,$temp); 
        			}
   				}
        	}
        	else 
			{
            	$this->config[$array[1]] [$array[2]] = $row['plp_value'];
        	}
		}
	}

    /**
     * Vergleicht die Daten in der version.php mit den Daten in der DB
     * @return bool
     */
	public function checkforupdate()
	{
	 	$ret = false;
 	
	 	// pruefen, ob es die Tabelle überhaupt gibt
		$sql = 'SHOW TABLES LIKE \''.$this->table_name.'\' ';
		$tableExistStatement = $GLOBALS['gDb']->queryPrepared($sql);
    
        if ($tableExistStatement->rowCount())
    	{
			$plp_name = self::$shortcut.'__Plugininformationen__version';
          
    		$sql = 'SELECT plp_value 
            		  FROM '.$this->table_name.' 
            		 WHERE plp_name = ? 
            		   AND ( plp_org_id = ?
            	    	OR plp_org_id IS NULL ) ';
    		$statement = $GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));
    		$row = $statement->fetchObject();

    		// Vergleich Version.php  ./. DB (hier: version)
    		if (!isset($row->plp_value) || strlen($row->plp_value) == 0 || $row->plp_value<>self::$version)
    		{
    			$ret = true;    
    		}
	
    		$plp_name = self::$shortcut.'__Plugininformationen__stand';
          
    		$sql = 'SELECT plp_value 
            		  FROM '.$this->table_name.' 
            		 WHERE plp_name = ?
            		   AND ( plp_org_id = ?
                 		OR plp_org_id IS NULL ) ';
            $statement = $GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));
    		$row = $statement->fetchObject();

    		// Vergleich Version.php  ./. DB (hier: stand)
    		if (!isset($row->plp_value) || strlen($row->plp_value) == 0 || $row->plp_value<>self::$stand)
    		{
    			$ret = true;    
    		}
    	}
    	else 
    	{
    		$ret = true; 
    	}
    	return $ret;
	}
	
    /**
     * Loescht die Konfigurationsdaten in der Datenbank
     * @param   int     $deinst_org_select  0 = Daten nur in aktueller Org loeschen, 1 = Daten in allen Org loeschen
     * @return  string  $result             Meldung
     */
	public function delete($deinst_org_select)
	{
    	$result = '';
		$result_data = false;
		$result_db = false;
		
		if ($deinst_org_select == 0)                    //0 = Daten nur in aktueller Org loeschen 
		{
			$sql = 'DELETE FROM '.$this->table_name.'
        			      WHERE plp_name LIKE ?
        			        AND plp_org_id = ? ';
			$result_data = $GLOBALS['gDb']->queryPrepared($sql, array(self::$shortcut.'__%', $GLOBALS['gCurrentOrgId']));		
		}
		elseif ($deinst_org_select == 1)              //1 = Daten in allen Org loeschen 
		{
			$sql = 'DELETE FROM '.$this->table_name.'
        			      WHERE plp_name LIKE ? ';
			$result_data = $GLOBALS['gDb']->queryPrepared($sql, array(self::$shortcut.'__%'));				
		}

		// wenn die Tabelle nur Eintraege dieses Plugins hatte, sollte sie jetzt leer sein und kann geloescht werden
		$sql = 'SELECT * FROM '.$this->table_name.' ';
		$statement = $GLOBALS['gDb']->queryPrepared($sql);

        if ($statement->rowCount() == 0)
    	{
        	$sql = 'DROP TABLE '.$this->table_name.' ';
        	$result_db = $GLOBALS['gDb']->queryPrepared($sql);
    	}
    	
    	$result  = ($result_data ? $GLOBALS['gL10n']->get('PLG_FORMFILLER_DEINST_DATA_DELETE_SUCCESS') : $GLOBALS['gL10n']->get('PLG_FORMFILLER_DEINST_DATA_DELETE_ERROR') );
		$result .= ($result_db ? $GLOBALS['gL10n']->get('PLG_FORMFILLER_DEINST_TABLE_DELETE_SUCCESS') : $GLOBALS['gL10n']->get('PLG_FORMFILLER_DEINST_TABLE_DELETE_ERROR') );
    	$result .= ($result_data ? $GLOBALS['gL10n']->get('PLG_FORMFILLER_DEINST_ENDMESSAGE') : '' );
		
		return $result;
	}
}
