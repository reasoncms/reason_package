<?php
/**
 * @package thor
 */

/**
 * Include dependencies
 */
include_once('paths.php');
include_once(TYR_INC.'tyr.php');
require_once( INCLUDE_PATH . 'xml/xmlparser.php' );
include_once ( SETTINGS_INC.'thor_settings.php' );
include_once( CARL_UTIL_INC . 'db/db.php'); // Requires ConnectDB Functionality
reason_include_once("function_libraries/file_utils.php");
reason_include_once("classes/plasmature/upload.php");

/**
 * ThorCore - essentially a thor replacement that does less than the old thor, but does it better.
 *
 * Do not extend this class - while other classes may use ThorCore, it should be kept as simple as possible.
 *
 * - Create / delete thor tables
 * - Get / set data in thor tables 
 * - Retrieves thor key/value pairs from a disco form
 * - Adds thor elements to a disco form
 * - Sets the values of disco elements from stored thor data
 *
 * @todo better database abstraction - currently using mysql_query and sqler
 * @todo replace XML Parser with Simple XML after move to PHP 5+ only
 * @todo support caching
 *
 * @author Nathan White
 */
class ThorCore
{
	var $_xml = false;
	var $_table = false;
	var $_db_conn = THOR_FORM_DB_CONN;
	var $_extra_fields = array(
		'id' => 'int(11) NOT NULL AUTO_INCREMENT',
		'submitted_by' => 'tinytext NOT NULL',
		'submitter_ip' => 'tinytext NOT NULL',
		'date_created' => 'timestamp default 0 NOT NULL', // when a record is created
		'date_modified' => 'timestamp default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', // when any row data changes
		'date_user_submitted' => 'timestamp default 0 NOT NULL', // when a user submits from a userland/non-administrative interface
		);
	// date_created and date_user_submitted are developer-controlled dates.
	// date_modified automatically updates when any other column value changes.
	//
	// date_created is intended to represent when a record first came into existance.
	// date_user_submitted is intendeted to represent when the original user submits/updates a record,
	//     not when an admin/maintainer/system updates a record
	//     Therefore, date_user_submitted shouldn't be assigned inside core Thor functions, 
	//     since Thor functions may be used in other context to update data dynamically
	//     such as administrative interfaces or cron jobs
	var $_stashed_sql_for_table_generation = "";
	var $_stashed_structure = array();
	
	function ThorCore($xml = '', $table = '')
	{
		if ($xml) $this->set_thor_xml($xml);
		if ($table) $this->set_thor_table($table);
	}
	
	/**
	 * If you need to instantiate thor with a different db_conn this can be called - but typically the
	 * the database connection is determined by the THOR_FORM_DB_CONN constant.
	 */
	function set_db_conn($db_conn)
	{
		$this->_db_conn = $db_conn;
	}
	
	function get_db_conn()
	{
		return $this->_db_conn;
	}
	
	function set_thor_xml($xml)
	{
		$xml = new XMLParser($xml);
		$xml->Parse();
		$this->_xml = $xml;	
	}
		
	function get_thor_xml()
	{
		return $this->_xml;
	}
	
	function set_thor_table($table)
	{
		$this->_table = $table;
	}
	
	function get_thor_table()
	{
		return $this->_table;
	}
	
	function &get_display_values()
	{
		if (!isset($this->_display_values))
		{
			$this->_build_display_values();	
		}
		return $this->_display_values;
	}
	
	function &get_column_names_indexed_by_label()
	{
		if (!isset($this->_column_names_indexed_by_label))
		{
			$dv =& $this->get_display_values();
			foreach ($dv as $k => $v)
			{
				$this->_column_names_indexed_by_label[$v['label']] = $k;
			}
		}
		return $this->_column_names_indexed_by_label;
	}
	
	function &get_column_names_indexed_by_normalized_label()
	{
		if (!isset($this->_column_names_indexed_by_normalized_label))
		{
			$dv =& $this->get_display_values();
			foreach ($dv as $k => $v)
			{
				$this->_column_names_indexed_by_normalized_label[strtolower(str_replace(" ", "_", $v['label']))] = $k;
			}
		}
		return $this->_column_names_indexed_by_normalized_label;
	}
	
	function &get_column_labels_indexed_by_name()
	{
		if (!isset($this->_column_labels_indexed_by_name))
		{
			$dv =& $this->get_display_values();
			foreach ($dv as $k => $v)
			{
				$this->_column_labels_indexed_by_name[$k] = $v['label'];
			}
		}
		return $this->_column_labels_indexed_by_name;
	}

	/**
	 * Retrieve the database column name from the column label
	 */
	function get_column_name($label)
	{
		$names =& $this->get_column_names_indexed_by_label();
		return (isset($names[$label])) ? $names[$label] : false;
	}
	
	/**
	 * Retrieve the database column name from the normalized column label
	 */
	function get_column_name_from_normalized_label($normalized_label)
	{
		$names =& $this->get_column_names_indexed_by_normalized_label();
		return (isset($names[$normalized_label])) ? $names[$normalized_label] : false;
	}
	
	/**
	 * Retrieve the column label from the database column name
	 */
	function get_column_label($name)
	{
		$labels =& $this->get_column_labels_indexed_by_name();
		return (isset($labels[$name])) ? $labels[$name] : false;
	}
	
	function append_thor_elements_to_form(&$disco_obj, $include_submit = true)
	{
		$xml = $this->get_thor_xml();
		// echo "<PRE>" . $xml . "</PRE>";
		if ($xml && $disco_obj)
		{
			foreach ($xml->document->tagChildren as $node)
			{
				// echo "running on [" . $node->tagName . "]...<br>";
				if ($node->tagName == 'input') $this->_transform_input($node, $disco_obj);
				elseif ($node->tagName == 'date') $this->_transform_date($node, $disco_obj);
				elseif ($node->tagName == 'time') $this->_transform_time($node, $disco_obj);
				elseif ($node->tagName == 'textarea') $this->_transform_textarea($node, $disco_obj);
				elseif ($node->tagName == 'radiogroup') $this->_transform_radiogroup($node, $disco_obj);
				elseif ($node->tagName == 'checkboxgroup') $this->_transform_checkboxgroup($node, $disco_obj);
				elseif ($node->tagName == 'optiongroup') $this->_transform_optiongroup($node, $disco_obj);
				elseif ($node->tagName == 'hidden') $this->_transform_hidden($node, $disco_obj);
				elseif ($node->tagName == 'comment') $this->_transform_comment($node, $disco_obj);
				elseif ($node->tagName == 'upload') {
					$disco_obj->form_enctype = "multipart/form-data";
					$this->_transform_upload($node, $disco_obj);
				} elseif ($node->tagName == 'event_tickets') {
					$this->_transform_event_tickets($node, $disco_obj);
				}
			}

			if ($include_submit) {
				$this->_transform_submit($xml->document->tagAttrs, $disco_obj);
			}
		}
		else
		{
			trigger_error('To add thor elements to a disco form you need to provide a disco object and thor xml');
		}
	}

	/**
	 * Applies values stored in thor for a record to the appropriate disco elements
	 */
	function apply_values_for_primary_key_to_form(&$disco_obj, $primary_key)
	{
		if ($this->table_exists())
		{
			$values = $this->get_values_for_primary_key($primary_key);
			$display_values =& $this->get_display_values();
			if ($values)
			{
				foreach ($values as $k=>$v)
				{
					$kEl = $disco_obj->get_element($k);
					if ($kEl)
					{
						$uploadClassName = "UploadType";
						// if ("upload" == $kEl->type || "ReasonUpload" == $kEl->type) {
						if ($uploadClassName == get_class($kEl) || is_subclass_of($kEl, $uploadClassName)) {
							$possibleExistingPath = $this->get_thor_filestorage_row_and_col_specific_storage_dir($primary_key, $k) . $v;

							if (!empty($v) && file_exists($possibleExistingPath)) {
								$kEl->tmp_full_path = $possibleExistingPath;
							}
						}
						$disco_obj->set_value($k, $v);
						// Inject options if data reflects new options
						if (property_exists($kEl, 'options') && strlen($kEl->value) != 0 && !in_array($kEl->value, $kEl->options))
						{
							$kEl->options[$kEl->value] = $kEl->value;
						}
					}
					elseif (isset($display_values[$k]['group_id']))
					{
						$group[$display_values[$k]['group_id']][] = $v;
					}
				}
				if (isset($group))
				{
					foreach ($group as $k=>$v)
					{
						if ($disco_obj->get_element($k))
						{
							$disco_obj->set_value($k, $v);
						}
					}
				}
				return true;
			}
			else return false;
		}
		return false;
	}
	
	/**
	 * Using a reference to a disco form, returns a set of key value pairs in a format saveable by thor_core
	 * Notably, this checks whether checkbox group elements have checked items, and returns appropriate populated
	 * thor column names and values for those items.
	 */
	function get_thor_values_from_form(&$disco_obj)
	{
		$xml = $this->get_thor_xml();
		$thor_values = array();
		if ($xml && $disco_obj)
		{
			foreach ($xml->document->tagChildren as $node)
			{
				if (in_array($node->tagName, array('input', 'date', 'time', 'textarea', 'radiogroup', 'optiongroup', 'hidden', 'event_tickets')))
				{
					// just basic - get the disco value
					$key = $node->tagAttrs['id'];
					$thor_values[$key] = $disco_obj->get_value($key);
				}
				elseif ($node->tagName == 'checkboxgroup')
				{
					$key = $node->tagAttrs['id'];
					$value = $disco_obj->get_value($key);
					foreach ($node->tagChildren as $child_key => $child_node)
					{
						$child_value = (!empty($value) &&  in_array($child_node->tagAttrs['value'], $value)) ? $child_node->tagAttrs['value'] : '';
						$thor_values[$child_node->tagAttrs['id']] = $child_value;
					}
				} 
			}
		}
		return $thor_values;
	}
	
	/** 
	 * Takes an array of thor_values and transforms using labels whenever possible - preserve fields that cannot be transformed
	 *
	 * @param array thor_values - raw thor_value array from database
	 * @param boolean transform_undefined_fields - whether to run prettify_string on field not defined on xml 
	 */
	function transform_thor_values_for_display($thor_values, $transform_undefined_fields = true)
	{	
		$display_values =& $this->get_display_values();
		foreach ($thor_values as $k=>$v)
		{
			if (isset($display_values[$k]))
			{
				if (isset($display_values[$k]['group_id']))
				{
					$group_id = $display_values[$k]['group_id'];
					if (isset($display_values[$group_id]) && !(empty($v)))
					{
						$values[$group_id]['label'] = $display_values[$group_id]['label'];
						$values[$group_id]['value'][] = $display_values[$k]['label'];
					}
				}
				else
				{
					$label = $display_values[$k]['label'];
					$values[$k] = array('label' => $label, 'value' => $v);
				}
			}
			elseif ($transform_undefined_fields)
			{
				$values[$k] = array('label' => prettify_string($k), 'value' => $v);
			}
			else
			{
				$values[$k] = array('label' => $k, 'value' => $v);
			}
		}
		return $values;
	}

	/**
	 * Insert a row - we automatically add the date created timestamp
	 *
	 * @param $disco_obj reference to the disco object. Added Nov 2014
	 * @return id of row inserted
	 */
	function insert_values($values, $disco_obj)
	{
		if ($this->get_thor_table() && $values)
		{
			$this->create_table_if_needed(); // create the table if it does not exist
			if (!isset($values['date_created'])){
				$values['date_created'] = get_mysql_datetime();
			}
			if (!isset($values['date_user_submitted'])){
				$values['date_user_submitted'] = get_mysql_datetime();
			}
			if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
  			$GLOBALS['sqler']->mode = 'get_query';
  			$query = $GLOBALS['sqler']->insert( $this->get_thor_table(), $values );
  			$result = db_query($query);
  			$insert_id = mysql_insert_id();

			$this->handle_file_uploads($insert_id, $disco_obj, true);

  			$GLOBALS['sqler']->mode = '';
  			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB

  			return $insert_id;
  		}
  		elseif (!$this->get_thor_table())
  		{
  			trigger_error('insert_values called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
  		}
  		elseif (empty($values))
  		{
  			trigger_error('insert_values called but the values array was empty');
  			return NULL;
  		}
	}

	function create_dirs_if_needed($dirs)
	{
		foreach($dirs as $d) {
			$this->create_dir_if_needed($d);
		}
	}

	function create_dir_if_needed($dir)
	{
		if (!file_exists($dir)) {
			mkdir($dir);
			// chmod($dir, 0666);
		}
	}

	// helper fxns to get at the various directories we use for storing files uploaded via thor
	function get_thor_filestorage_base_storage_dir() { return THOR_SUBMITTED_FILE_STORAGE_BASEDIR; }
	function get_thor_filestorage_form_specific_storage_dir() { return $this->get_thor_filestorage_base_storage_dir() . $this->get_thor_table() . "/"; }
	function get_thor_filestorage_row_specific_storage_dir($row_id) { return $this->get_thor_filestorage_form_specific_storage_dir() . "row_" . $row_id . "/"; }
	function get_thor_filestorage_row_and_col_specific_storage_dir($row_id, $col_id) { return $this->get_thor_filestorage_row_specific_storage_dir($row_id) . $col_id . "/"; }

	// for rmdir_and_contents, see reason_package/reason_4.0/lib/core/function_libraries/util.php
	function delete_file_storage_for_form() { rmdir_and_contents($this->get_thor_filestorage_form_specific_storage_dir()); }
	function delete_file_storage_for_row($row_id) { rmdir_and_contents($this->get_thor_filestorage_row_specific_storage_dir($row_id)); }
	function delete_file_storage_for_row_and_col($row_id, $col_id) { rmdir_and_contents($this->get_thor_filestorage_row_and_col_specific_storage_dir($row_id, $col_id)); }

	// given some data about a particular file submission in a form, returns the path (optionally creating the directories)
	function construct_file_storage_location($row_id, $col_id, $filename, $create_dirs = false) {
		$storage_location_base_dir = $this->get_thor_filestorage_base_storage_dir();
		$form_specific_dir = $this->get_thor_filestorage_form_specific_storage_dir();
		$submission_dir = $this->get_thor_filestorage_row_specific_storage_dir($row_id);
		$destination_dir = $this->get_thor_filestorage_row_and_col_specific_storage_dir($row_id, $col_id);

		if ($create_dirs) {
			$this->create_dirs_if_needed(Array(
								$storage_location_base_dir,
								$form_specific_dir,
								$submission_dir,
								$destination_dir
							));
		}

		return $destination_dir . $filename;
	}

	function handle_file_uploads($primary_key, $disco_obj, $initialSave)
	{
		// svn weirdness
		if ($disco_obj == null) { return; }

		/*
		// 2015-02-10 -- changed behvior; we now delete based on 'delete_requested' in the plasmature element
		// tough to decide, but going with "an update clears out any previous files stored" approach
		if (!$initialSave) {
			$this->delete_file_storage_for_row($primary_key);
		}
		*/

		$update_clauses = Array();

		$xml = $this->get_thor_xml();
		foreach ($xml->document->tagChildren as $node)
		{	
			if ($node->tagName == 'upload') {
				$col_id = $node->tagAttrs['id'];

				$disco_el = $disco_obj->get_element($col_id);
				/*
				$col_label = $node->tagAttrs['label'];
				echo "name=[" . $disco_el->file["name"] . "]<P>";
				var_dump("<PRE>", $disco_el, "</PRE>");
				if ($disco_el->state == "received") {
					echo "\"$col_label\" state is received...<P>";
				} else if ($disco_el->state == "pending") {
					echo "\"$col_label\" state is pending...<P>";
				} else {
					echo "\"$col_label\" state has no state...<P>";
				}
				 */
				// 2014-12-17 modification: this now supports a file that was uploaded but form could not be finished
				// due to other errors. MUCH more user-friendly

				// "received" is pretty standard - file was just uploaded.
				// "pending" can be, for instance:
				// 1. file was submitted but other form errors (no input for required elements for instance) occurred
				// 2. errors were corrected but a different file was not uploaded
				// "ready" can be if the file was included in an earlier form submission and user didn't do anything with
				// it when resubmitting / editing.
				if ($disco_el->state == "received" || $disco_el->state == "pending") {
					$this->delete_file_storage_for_row_and_col($primary_key, $col_id);
					$destination_file = $this->construct_file_storage_location($primary_key, $col_id, $disco_el->file["name"], true);
					$source_file = $disco_el->tmp_full_path;
					// echo "src [$source_file], dest [$destination_file]<P>";

					$success = rename($source_file, $destination_file);
					if ($success) {
						$update_clauses[$col_id] = $disco_el->file["name"]; // just filename; rest can be reconstructed with construct_file_storage_location
					}
				} else if ($disco_el->state == "ready") {
					// file was previously uploaded; leave it alone
				} else if ($disco_el->state == "delete_requested") {
					// file was previously uploaded but user wants it deleted...
					$this->delete_file_storage_for_row_and_col($primary_key, $col_id);
					$update_clauses[$col_id] = "";
				} else {
					// nothing was uploaded, or an error occurred?
					$update_clauses[$col_id] = "";
				}

				/*
				$upload_data = $_FILES[$col_id];
				if ($upload_data["tmp_name"] != "") {
					echo "TMP NAME [" . $upload_data["tmp_name"] . "]<P>";

					$disco_el = $disco_obj->get_element($col_id);

					if ($disco_el->state == "received") { // || $disco_el->state == "pending")
						$source_file = $disco_el->tmp_full_path;
						// $destination_file = $destination_dir . $upload_data["name"];
						$destination_file = $this->construct_file_storage_location($primary_key, $col_id, $upload_data["name"], true);

						$success = rename($source_file, $destination_file);
						if ($success) {
							$update_clauses[$col_id] = $upload_data["name"]; // just filename; rest can be reconstructed with construct_file_storage_location
						}
					} else {
						// echo "ERROR - element not received (" . $disco_el->state . ")<P>";
						// not sure what to do here...when would this occur, with paticularly large files?
						$update_clauses[$col_id] = "";
					}
				} else {
					echo "NO FILE UPLOADED FOR [$col_id]/[$col_label]<P>";
					// no file was uploaded for this; that's ok
					$update_clauses[$col_id] = "";
				}
				 */
			}
		}

		if (count($update_clauses) > 0) {
  			$GLOBALS['sqler']->mode = 'get_query';
  			$update = $GLOBALS['sqler']->update_one($this->get_thor_table(), $update_clauses, $primary_key);
  			$result = db_query($update);
  			$GLOBALS['sqler']->mode = '';
		}
	}
	
	/**
	 * @todo more error checks
	 * 
	 * NOTE - disco_obj is usually a disco obj but it CAN BE NULL - for instance the call to this function
	 * from language_placement_post.php doesn't have a reference to a disco object and so it passes in null. Right
	 * now, disco_obj is only used for handling file uploads.
	 */
	function update_values_for_primary_key($primary_key, $values, $disco_obj = null)
	{
		// echo "FIRING UPDATE [$primary_key]/[<PRE>"; var_dump($values); echo "</PRE>; " . ($disco_obj instanceof Disco ? "valid Disco passed in!" : "garbage...") . "<HR>"; die();
		if ($this->get_thor_table() && !empty($values) && ($primary_key > 0))
		{
			if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
  			$GLOBALS['sqler']->mode = 'get_query';
  			$query = $GLOBALS['sqler']->update_one( $this->get_thor_table(), $values, $primary_key, 'id' );
  			$result = db_query($query);

			$this->handle_file_uploads($primary_key, $disco_obj, false);

  			$GLOBALS['sqler']->mode = '';
  			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB

			return $result;
  		}
  		elseif (!$this->get_thor_table())
  		{
  			trigger_error('update_values_for_primary_key called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
  		}
  		elseif (empty($values))
  		{
  			trigger_error('update_values_for_primary_key called but the values array was empty so nothing was saved');
  			return NULL;
  		}
  		else
  		{
  			trigger_error('update_values_for_primary_key called but no primary key was passed so nothing could be saved.');
  			return NULL;
  		}
	}
	
	/**
	 * Shortcut function maybe overkill
	 */
	function get_values_for_user($username, $sort_field = '', $sort_order = '')
	{
		return $this->get_rows_for_key($username, 'submitted_by', $sort_field, $sort_order);
	}
	
	function get_values_for_primary_key($id) // id is the primary key in a thor table
	{
		$rows = $this->get_rows_for_key($id, 'id');
		return ($rows) ? current($rows) : false;
	}
	
	/**
	 * @return array rows associated with a key and key column
	 */
	function get_rows_for_key($key, $key_column, $sort_field = '', $sort_order = '')
	{
		if ($this->get_thor_table() && (strlen($key) > 0) && $this->table_exists())
		{
			if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
			
			$q = $this->get_select_by_key_sql($key, $key_column, $sort_field, $sort_order);
  			$res = mysql_query($q);
  			if ($res && mysql_num_rows($res) > 0)
  			{
  				while ($row = mysql_fetch_assoc($res))
  				{
  					$result[$row['id']] = $row;
  				}
  			}
  			else $result = false;
  			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
  			return $result;
  		}
  		elseif (!$this->get_thor_table())
  		{
  			trigger_error('get_rows_for_key called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
  		}
  		else
  		{
  			return array(); // the primary key was empty
  		}
	}
	
	function get_rows($sort_field = '', $sort_order = '')
	{
		if ($this->get_thor_table() && $this->table_exists())
		{
			if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
			$q = $this->get_rows_sql($sort_field, $sort_order);
  			$res = mysql_query($q);
  			if ($res && mysql_num_rows($res) > 0)
  			{
  				while ($row = mysql_fetch_assoc($res))
  				{
  					$result[$row['id']] = $row;
  				}
  			}
  			else $result = false;
  			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
  			return $result;
  		}
  		elseif (!$this->get_thor_table())
  		{
  			trigger_error('get_rows called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
  		}
		else
  		{
  			return array();
  		}
	}
	
	function get_row_count()
	{
		if ($this->get_thor_table() && $this->table_exists())
		{
			if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
			$q = $this->get_row_count_sql();
  			$res = mysql_query($q);
  			$result = mysql_fetch_assoc($res);
  			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
  			return $result['count'];
  		}
  		elseif (!$this->get_thor_table())
  		{
  			trigger_error('get_row_count called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
  		}
		else
		{
			return 0;
		}
	}
	
	/**
	 * Delete a row, also call delete_table_if_needed to check if the last row was just deleted
	 */
	function delete_by_primary_key($primary_key)
	{
		if ($this->get_thor_table() && (strlen($primary_key) > 0) )
		{
			$this->delete_file_storage_for_row($primary_key);

			if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
			$q = $this->get_delete_by_key_sql($primary_key, 'id');
  			mysql_query($q);
  			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
  			$this->delete_table_if_needed();
  			return true;
  		}
  		elseif (!$this->get_thor_table())
  		{
  			trigger_error('delete_by_primary_key called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
  		}
  		else
  		{
  			trigger_error('delete_by_primary_key called but no primary key was given');
  			return NULL;
  		}
	}
	
	function get_create_table_sql()
	{
		$db_structure = $this->_build_db_structure();
		$q = 'CREATE TABLE ' . $this->get_thor_table() . '(';
		$q .= '`id`'. $this->_extra_fields['id'].', ';
		//$q .= '`formkey` tinytext NOT NULL , ';
		foreach ($db_structure as $k=>$v)
		{
			switch ($v['type']) {
			case 'tinytext':
   				$q .= '`'.$k.'` tinytext NOT NULL , ';
   				break;
   			case 'dateTimeText':
   				$q .= '`'.$k.'` datetime NOT NULL , ';
   				break;
   			case 'timeText':
   				$q .= '`'.$k.'` tinytext NOT NULL , ';
   				break;
			case 'text':
   				$q .= '`'.$k.'` text NOT NULL , ';
   				break;
			}
		}
		foreach ($this->_extra_fields as $field => $definition)
		{
			if ($field == 'id') continue;
			$q .= '`'.$field.'`'. $definition . ', ';
		}
		
		$q .= 'PRIMARY KEY(`id`)) ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;';
		return $q;
	}
	
	function get_delete_table_sql()
	{
		return 'DROP TABLE '.$this->get_thor_table();
	}
	
	function get_table_exists_sql()
	{
		return 'SELECT TABLE_NAME FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = "'.get_database_name().'" AND `TABLE_NAME` = "'.$this->get_thor_table().'"';
	}

	function get_column_exists_sql($column)
	{
		return 'SHOW COLUMNS FROM '.$this->get_thor_table().' LIKE "'.$column.'"';
	}
	
	/**
	 * Maybe limit to one???
	 */
	function get_delete_by_key_sql($key, $key_column)
	{
		return 'DELETE FROM '.$this->get_thor_table().' WHERE '.$key_column.' = "'.$key.'"';
	}

	/**
	 * Maybe limit to one???
	 */	
	function get_select_by_key_sql($key, $key_column, $sort_field = '', $sort_order = '')
	{
		$str = 'SELECT * FROM '.$this->get_thor_table().' WHERE '.$key_column.' = "'.$key.'"';
		if (!empty($sort_field) && !empty($sort_order))
		{
			$str .= ' ORDER BY `' . $sort_field . '` ' . $sort_order; 
		}
		return $str;
	}

	function get_rows_sql($sort_field = '', $sort_order = '')
	{
		$str = 'SELECT * FROM '.$this->get_thor_table();
		if (!empty($sort_field) && !empty($sort_order))
		{
			$str .= ' ORDER BY `' . $sort_field . '` ' . $sort_order; 
		}
		return $str;
	}
	
	function get_row_count_sql()
	{
		return 'SELECT COUNT(*) AS count FROM '.$this->get_thor_table();
	}
	
	function table_exists()
	{
		if (!isset($this->_table_exists))
		{
			$table = $this->get_thor_table();
			if ($this->get_thor_table())
			{
				if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
				$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
				if ($reconnect_db) connectDB($this->get_db_conn());
  				$q = $this->get_table_exists_sql();
  				$res = mysql_query($q);
  				if (mysql_num_rows($res) > 0) $this->_table_exists = true;
  				else $this->_table_exists = false;
  				if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
			}
  			else
  			{
  				trigger_error('table_exists called but no table has been defined via the thorCore set_thor_table method');
  				return NULL;
  			}
  		}
  		return $this->_table_exists;
	}
	
	/**
	 * Useful for sanity checking
	 */ 
	function column_exists($column)
	{
		if (!isset($this->_column_exists[$column]))
		{
			$table = $this->get_thor_table();
			if ($this->get_thor_table())
			{
				if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
				$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
				if ($reconnect_db) connectDB($this->get_db_conn());
  				$q = $this->get_column_exists_sql($column);
  				$res = mysql_query($q);
  				if ($res && (mysql_num_rows($res) > 0)) $this->_column_exists[$column] = true;
  				else $this->_column_exists[$column] = false;
  				if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
  			}
  			else
  			{
  				trigger_error('column_exists called but no table has been defined via the thorCore set_thor_table method');
  				return NULL;
  			}
  		}
  		return $this->_column_exists[$column];
	}
	
	/**
	 * Creates a SQL table
	 * 
	 * Will check $this->_stashed_sql_for_table_generation first and use the SQL
	 * stashed in this variable first. When the variable is empty, the SQL 
	 * to create the table is generated.
	 * 
	 * @return boolean|null returns TRUE when the table was created, FALSE on a possible
	 *     creation error, and NULL when the table already exists
	 */
	function create_table()
	{
		if ($this->get_thor_table() && !$this->table_exists())
		{
			if (!empty($this->_stashed_sql_for_table_generation)) {
				$sql = $this->_stashed_sql_for_table_generation;
			} else {
				$sql = $this->get_create_table_sql();
			}
			if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
			if ($res = db_query($sql))
				$this->_table_exists = true;
			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
			return $this->_table_exists;
		}
		else
  		{
  			$error = ($this->get_thor_table()) ? 'The table already exists' : 'You need to define the table name using set_thor_table before calling create_table'; 
  			trigger_error($error);
  			return NULL;
  		}
	}
	
	function create_table_if_needed()
	{
		if ($this->get_thor_table() && !$this->table_exists())
		{
			return $this->create_table();
		}
		elseif (!$this->get_thor_table())
		{
			trigger_error('create_table_if_needed called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
		}
		return false;
	}
	
	/**
	 * Store a snapshot of the current database structure and SQL.
	 * 
	 * To be used later when the table is generated.
	 * 
	 * Useful in situations where the thor xml is modified during runtime, like when
	 * event ticket items are in use, but we want to original xml to drive 
	 * the database table creation. All event ticket nodes are used to create 
	 * a database table, but only one event ticket node is used during runtime
	 * and presented to a user.
	 * 
	 * @return boolean TRUE on successful/first stash, FALSE if stashed info already exists
	 */
	function stash_current_structure_for_table_generation()
	{
		if (empty($this->_stashed_sql_for_table_generation)) {
			$this->_stashed_structure = $this->_build_db_structure();
			$this->_stashed_sql_for_table_generation = $this->get_create_table_sql();
			return true;
		} else {
			return false;
		}
	}

	function delete_table()
	{
		if ($this->get_thor_table() && $this->table_exists())
		{
			$this->delete_file_storage_for_form();

			$sql = $this->get_delete_table_sql();
			if (!get_current_db_connection_name()) connectDB($this->get_db_conn());
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
			$res = db_query($sql);
			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
			return true;
		}
		else
  		{
  			$error = (!$this->get_thor_table()) ? 'You need to define the table name using set_thor_table before calling create_table' : 'The table you are trying to delete does not exist!';
  			trigger_error($error);
  			return NULL;
  		}
	}
	
	function delete_table_if_needed()
	{		
		if ($this->get_thor_table() && $this->table_exists() && ($this->get_row_count() == 0) )
		{
			$this->delete_table();
		}
		elseif (!$this->get_thor_table())
		{
			trigger_error('delete_table_if_needed called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
		}
	}
	
	function get_extra_field_names()
	{
		return array_keys($this->_extra_fields);
	}
	
	/**
	 * Add a new extra field to the beginning of the extra field list. Allows code to store
	 * and retrieve arbitrary fields not defined by the thor form.
	 * 
	 * @param $name SQL column name
	 * @param $description SQL column definition
	 */
	function add_extra_field($name, $description)
	{
		$this->_extra_fields = array_merge(array($name => $description), $this->_extra_fields);
	}
	
	/**
	 * Updated for php 4 and php 5 compatibility using Adam Flynn's XML Parser class instead of xpath
	 * - see http://www.criticaldevelopment.net/xml/
	 *
	 * @author Nathan White
	 */
	function _build_db_structure()
	{
		$xml = $this->get_thor_xml();
		foreach ($xml->document->tagChildren as $node) // we use tagName to make sure we iterate through them by order instead of type
		{	
			if ($node->tagName == 'input') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'tinytext';
			}
			elseif ($node->tagName == 'textarea') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'text';
			}
			elseif ($node->tagName == 'date') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'dateTimeText';
			}
			elseif ($node->tagName == 'time') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'timeText';
			}
			elseif ($node->tagName == 'hidden') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'text';
			}
			elseif (($node->tagName == 'radiogroup') || ($node->tagName == 'optiongroup')) {
				$db_structure[$node->tagAttrs['id']]['type'] = 'text';
			}
			elseif ($node->tagName == 'checkboxgroup') {
				$node_children = $node->tagChildren;
				foreach ($node_children as $node2) {
					$db_structure[$node2->tagAttrs['id']]['type'] = 'text';
				}
			}
			elseif ($node->tagName == 'upload') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'tinytext';
			}
			elseif ($node->tagName == 'event_tickets') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'tinytext';
			}
		}
		return $db_structure;
	}
	
	function _build_display_values()
	{	
		$xml = $this->get_thor_xml();
		$display_values = array();
		foreach ($xml->document->tagChildren as $k=>$v)
		{
			$tagname = is_object($v) ? $v->tagName : '';
			if (method_exists($this, '_build_display_'.$tagname))
			{
				$build_function = '_build_display_'.$tagname;
				$display_values = array_merge($display_values, $this->$build_function($v));
			}
		}
		foreach ($this->get_extra_field_names() as $field_name)
		{
			$display_values[$field_name]['label'] = prettify_string($field_name);
			$display_values[$field_name]['type'] = 'text';
		}
		$this->_display_values = (isset($display_values)) ? $display_values : array();
	}

	/**
	 * Helper functions for _build_display_values()
	 * @access private
	 */

	function _build_display_input($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'input';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}

	function _build_display_hidden($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'hidden';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}
 
	function _build_display_textarea($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'textarea';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}
	
	function _build_display_date($element_obj)
 	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'dateTimeText';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}
	
	function _build_display_time($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'timeText';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}

	function _build_display_checkboxgroup($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => 'checkboxgroup'); 
		$element_children = $element_obj->tagChildren;
		$type = 'checkbox';
		foreach ($element_children as $element_child) 
		{
			$child_attrs = $element_child->tagAttrs;
			$label = $child_attrs['label'];
			$display_values[$child_attrs['id']] = array('label' => $label, 'type' => $type, 'group_id' => $element_attrs['id'] );
		}
		return $display_values;
	}
	
	function _build_display_radiogroup($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$element_children = $element_obj->tagChildren;
		$id = $element_attrs['id'];
		$label = $element_attrs['label'];
		$type = 'radiogroup';
		foreach ($element_children as $element_child)
		{
			$child_attrs =& $element_child->tagAttrs;
			$options[] = $child_attrs['value'];
		}
		$display_values[$id] = array('label' => $label, 'type' => $type, 'options' => $options);
		return $display_values;
	}

	function _build_display_optiongroup($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$element_children = $element_obj->tagChildren;
		$id = $element_attrs['id'];
		$label = $element_attrs['label'];
		$type = 'optiongroup';
		foreach ($element_children as $element_child)
		{
			$child_attrs =& $element_child->tagAttrs;
			$options[] = $child_attrs['value'];
		}
		$display_values[$id] = array('label' => $label, 'type' => $type, 'options' => $options);
		return $display_values;
	}

	function _build_display_upload($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'file';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}
	
	function _build_display_event_tickets($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'event_tickets';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}
	
	function _transform_input($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$size = (!empty($element->tagAttrs['size'])) ? $element->tagAttrs['size'] : 30;
		$maxlength = (!empty($element->tagAttrs['maxlength'])) ? $element->tagAttrs['maxlength'] : '';
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$default = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$args = array('size' => $size,
					  'maxlength' => $maxlength,
					  'display_name' => $display_name,
					  'default' => $default);

		$d->add_element($id, 'text', $args);
		if ( $required ) $d->add_required($id);
	}

	function _transform_hidden($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$value = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$d->add_element($id, 'hidden');
		$d->set_value($id, $value);
		$d->set_display_name($id, '(hidden field) ' . $display_name);
		if ( $required) $d->add_required($id);
	}
 
	function _transform_comment($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$args = Array('text' => $element->tagData);
		$d->add_element($id, 'comment', $args);
	}
	
	function _transform_date($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$args = Array('display_name' => $display_name);
		$time_entry = (!empty($element->tagAttrs['date_field_time_enabled'])) ? true : false;
		if ($time_entry)
			$d->add_element($id, 'textDateTimeNoSeconds', $args);
		else
			$d->add_element($id, 'textDate', $args);
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		if ($required) $d->add_required($id);
	}
	
	function _transform_time($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$args = Array('display_name' => $display_name);
		$d->add_element($id, 'textTimeNoSeconds', $args);
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		if ($required) $d->add_required($id);
	}
 
	function _transform_textarea($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$rows = (!empty($element->tagAttrs['rows'])) ? $element->tagAttrs['rows'] : 6;
		$cols = (!empty($element->tagAttrs['cols'])) ? $element->tagAttrs['cols'] : 40;
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$default = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$args = Array('rows' => $rows,
					  'cols' => $cols,
					  'display_name' => $display_name,
					  'default' => $default);

		$d->add_element($id, 'textarea', $args);
		if ( $required ) $d->add_required($id);
	}

	function _transform_radiogroup($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$args = Array( 'options' => Array(),
					   'display_name' => $display_name,
					   'default' => '' );

		$element_children = $element->tagChildren; 
		foreach ($element_children as $element_child)
		{
			$value = (!empty($element_child->tagAttrs['value'])) ? $element_child->tagAttrs['value'] : '';
			$selected = (!empty($element_child->tagAttrs['selected'])) ? true : false;
			$args['options'][$value] = $value;
			if ( $selected ) $args['default'] = $value;
		}

		$d->add_element($id, 'radio_no_sort', $args);
		if ( $required ) $d->add_required($id);
	}

	function _transform_optiongroup($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$multiple = (!empty($element->tagAttrs['multiple'])) ? true : false;
		$size = (!empty($element->tagAttrs['size']) && ($element->tagAttrs['size'] > 1)) ? $element->tagAttrs['size'] : 1;
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		
		$args = array('options' => array(),
					  'multiple' => $multiple,
					  'size' => $size,
					  'default' => array(),
					  'display_name' => $display_name,
					  'add_null_value_to_top' => true);
					  
		$element_children = $element->tagChildren;
		foreach ($element_children as $element_child)
		{
			$value = (!empty($element_child->tagAttrs['value'])) ? $element_child->tagAttrs['value'] : '';
			$selected = (!empty($element_child->tagAttrs['selected'])) ? true : false;
			$multiple = (!empty($element_child->tagAttrs['multiple'])) ? true : false;
			$args['options'][$value] = $value;
				
			if ( !$multiple && $selected ) $args['default'] = $value;
			else if ( $selected ) $args['default'][]= $value;
		}

		$d->add_element($id, 'select_no_sort', $args);
		if ( $required ) $d->add_required($id);
	}
	
	/**
	 * @todo the transform element that is added should be "cloaked" and not hidden - but we need to make sure
	 *       this change does not break other code. when this is fixed, thor/thor_admin.php should also be updated
	 *       so that on_every_time_edit does not perform a silly substring search for transform
	 */
	function _transform_checkboxgroup($element, &$d) {
		$id = $element->tagAttrs['id'];
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$args = array('options' => array(),
					  'display_name' => $display_name,
					  'default' => array());

		$index = 0;
		$element_children = $element->tagChildren;
		
		foreach ($element_children as $element_child)
		{
			$value = (!empty($element_child->tagAttrs['value'])) ? $element_child->tagAttrs['value'] : '';
			$selected = (!empty($element_child->tagAttrs['selected'])) ? true : false;
			$id2 = $element_child->tagAttrs['id'];
			$args['options'][$value] = $value;
			
			if ( $selected ) $args['default'] []= $value;
			$d->add_element('transform['.$id.']['.$index.']', 'hidden');
			$d->set_value('transform['.$id.']['.$index.']', $id2);
			$index++;
		}

		$d->add_element($id, 'checkboxgroup_no_sort', $args);
		if ( $required ) $d->add_required($id);
	}

	function explodeTrimAndLowerTokenizedInputForUploadRestrictions($stuff)
	{
		$rv = Array();
		$explodedStuff = explode(",",$stuff);
		foreach ($explodedStuff as $stuffChunk) {
			$rv[] = strtolower(trim($stuffChunk));
		}
		return $rv;
	}

	function _transform_upload($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;

		$args = array(
						'display_name' => $display_name,
						'show_restriction_explanation' => true
					);

		if (!empty($element->tagAttrs['restrict_extensions'])) {
			$args['acceptable_extensions'] = $this->explodeTrimAndLowerTokenizedInputForUploadRestrictions($element->tagAttrs['restrict_extensions']);
		}

		if (!empty($element->tagAttrs['restrict_types'])) {
			$args['acceptable_types'] = $this->explodeTrimAndLowerTokenizedInputForUploadRestrictions($element->tagAttrs['restrict_types']);
		}

		if (!empty($element->tagAttrs['restrict_maxsize'])) {
			$args['max_file_size'] = convertFormattedSizeToNumberOfBytes($element->tagAttrs['restrict_maxsize']);
		}

		// var_dump("FINAL ARGS: <PRE>", $args, "</PRE>");

		// $d->add_element($id, 'upload', $args);
		$d->add_element($id, 'ReasonUpload', $args);
		if ( $required ) $d->add_required($id);
	}
	
	function _transform_event_tickets($element, &$d)
	{
		$eventId = $element->tagAttrs['event_id'];
		if(!$eventId) {
			return;
		}
		
		if (array_key_exists('num_total_available', $element->tagAttrs)) {
			$numTotalAvailableForEvent = $element->tagAttrs['num_total_available'];
		}
		if (array_key_exists('max_per_person', $element->tagAttrs)) {
			$numMaxPerPersonForEvent = $element->tagAttrs['max_per_person'];
		}
		if (array_key_exists('event_close_datetime', $element->tagAttrs)) {
			$closeAfterDatetimeForEvent = $element->tagAttrs['event_close_datetime'];
		}

		// This is dynamically added to thor xml at runtime, but may be missing 
		// in some admin views when the form isn't rendered
		if (array_key_exists('remaining_seats', $element->tagAttrs)) {
			$numTicketsCurrentlyRemaining = $element->tagAttrs['remaining_seats'];
		} else {
			$numTicketsCurrentlyRemaining = 100000;
		}
		
		// If we don't have as max per person limit, use 1 ticket per person
		if (intval($numMaxPerPersonForEvent) < 1) {
			$numMaxPerPersonForEvent = 1;
		}

		// Generate list of number of tickets a person can select, being sensitive
		// to the number of tickets still available (except when editing a form submission)
		$allOptions = array();
		$disabledOptions = array();
		
		$userInEditMode = !empty($_GET['table_row_action']) && $_GET['table_row_action'] == 'edit';
		
		foreach (range(0, $numMaxPerPersonForEvent) as $k => $tickentNum) {
			$displayString = $tickentNum;
			if ($tickentNum > $numTicketsCurrentlyRemaining && !$userInEditMode) {
				$displayString .= " &mdash; unavailable";
				$disabledOptions[] = $tickentNum;
			}
			$allOptions[] = $displayString;
		}

		$eventTitle = $element->tagAttrs['label'];

		$discoArgs = array(
			'options' => $allOptions,
			'disabled_options' => $disabledOptions,
			'multiple' => false,
			'default' => 0,
			'display_name' => "Number of seats for $eventTitle",
			'add_null_value_to_top' => false,
		);

		$id = $element->tagAttrs['id'];

		$d->add_element($id, 'select_no_sort', $discoArgs);
		
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		if ($required) {
			$d->add_required($id);
		}
	}

	function _transform_submit($element_attributes, &$d)
	{
		$submit = (!empty($element_attributes['submit'])) ? $element_attributes['submit'] : '';
		$d->actions = Array( 'submit' => $submit);
	}
	
	/**
	 * Get 'thor_info' and a 'submitted_value' field out of the form
	 * 
	 * Submitted value only present on post requests
	 * 
	 * @param Disco $disco_obj
	 * @param int $filter_event_id limit results to this event id
	 * @return array array of event ticket info with keys 'thor_info' and 'submitted_value' for each
	 */
	function get_event_tickets_thor_info($disco_obj, $filter_event_id = 0)
	{
		$xml = $this->get_thor_xml();
		$thor_values = array();
		if ($xml && $disco_obj) {
			foreach ($xml->document->tagChildren as $node) {
				if ($node->tagName == 'event_tickets') {
					if ($filter_event_id != 0 && $node->tagAttrs['event_id'] != $filter_event_id) {
						continue;
					}
					
					// Inject runtime default values mentioned in formbuilder tool
					if (!array_key_exists('num_total_available', $node->tagAttrs) || $node->tagAttrs['num_total_available'] === "") {
						$node->tagAttrs['num_total_available'] = 100000;
					}
					if (!array_key_exists('max_per_person', $node->tagAttrs) || $node->tagAttrs['max_per_person'] === "") {
						$node->tagAttrs['max_per_person'] = 1;
					}
					
					$thor_values[] = array(
						'thor_info' => $node->tagAttrs,
						'submitted_value' => $disco_obj->get_value($node->tagAttrs['id']), // false when empty
					);
				}
			}
		}
		return $thor_values;
	}
}
?>
