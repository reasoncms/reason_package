<?php

/**
 * Type library for offering selections of one or more options.
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";

/**
 * The abstract class that powers plasmature types that have multiple options
 * (e.g. radio buttons, selects). The optionType encapsulates a list of
 * possible values with the one selected value.
 *
 * This is considered an abstract type.
 *
 * @abstract
 * @package disco
 * @subpackage plasmature
 */
class optionType extends defaultType
{
	var $type = 'option';
	var $type_valid_args = array('options', 'sort_options','disabled_options','add_empty_value_to_top', 'add_other', 'other_label', 'other_options', 'empty_value_label', 'reject_unrecognized_values');
	/**
	 * The possible values of this element.
	 * Format: value as it should be stored => value as it should be displayed.
	 * @var array
	 */
	var $options = array();
	/**
	 * The options that should be disabled
	 *
	 * Format: disabled option keys are the values of this array. Simple numeric indexing.
	 *
	 * @todo Still need to work out compariston rules!!!
	 *
	 * @var array
	 */
	var $disabled_options = array();
	/**
	 * True if the {@link options} array should be sorted into ascending order.
	 * Otherwise, options will be displayed in the order that they were added to the {@link options} array.
	 */
	var $sort_options = true;
	var $add_empty_value_to_top = false;
	/**
	 * Whether to add an "Other" option to the end of the options list
	 */
	var $add_other = false;
	/**
	 * Default label text for the Other option
	 */
	var $other_label = 'Other: ';
	/**
	 * If this is set to an array, the other option will allow the user to choose from one of the values;
	 * otherwise, the user can enter anything at all.
	 */
	var $other_options = array();
	/**
	 * What label should the empty value get?
	 */
	var $empty_value_label = '--';
	/**
	 * Should values that are not recognized be rejected?
	 */
	var $reject_unrecognized_values = false;

	protected function _array_val_ok()
	{
		return true;
	}
	protected function _string_val_ok()
	{
		return true;
	}
	/**
	 *  Loads default options defined using {@link load_options()} and sorts the {@link options} array if {@link sort_options} is true.
	 */
	function additional_init_actions($args = array())
	{
		parent::additional_init_actions($args);
		if( isset($this->options) )
		{
			$this->set_options( $this->options );
		}
		$this->load_options();
		if($this->sort_options)
		{
			asort( $this->options );
		}
	}
	/**
	 *  Hook for child classes that have a default set of options (e.g. {@link stateType}, {@link languageType}).
	 */
	function load_options( $args = array() )
	{
	}
	function set_options( $options )
	{
		if ( is_array( $options ) )
			$this->options = $options;
		else
		{
			trigger_error('Could not set options for element '.$this->name.'; '.$this->type.'::set_options() requires an array as an argument', WARNING);
			return false;
		}
	}
	/**
	 * Sets display order of options.
	 * @param $order array Array of options in the order you want them to appear.
	 *
	 * @todo Should this be disabled if {@link sort_options} is true?
	 */
	function set_option_order( $order )
	{
		if(!empty($this->options))
		{
			if( is_array( $order ) )
			{
				$options = $this->options;
				$new_options = array();
				foreach($order as $option_key)
				{
					if( isset( $this->options[ $option_key ] ) )
					{
						$new_options[ $option_key ] = $this->options[ $option_key ];
						unset( $options[ $option_key ] );
					}
				}
				foreach($options as $key => $display_name)
					$new_options[ $key ] = $display_name;
				$this->options = $new_options;
			}
		}
		else
			trigger_error('Could not set option order; '.$this->name.' does not have any options set.');
	}

	/**
	 * Returns the value as it was displayed in the element -- e.g., if you store
	 * departments as abbreviations but display them in the option element as
	 * department names, will return the department name that corresponds to the
	 * internal value. -- MG 2006/07
	 */
	function get_value_for_display()
	{
		if (!empty($this->value))
		{
			if (!empty($this->options[$this->value]))
				return $this->options[$this->value];

			if ($this->add_other)
			{
				if ($this->other_options && !empty($this->other_options[$this->value]))
					return $this->other_options[$this->value];
				else if (empty($this->other_options))
					return $this->value;
			}
		}
		return false;
	}


	// MAJOR CHANGE
	/**
	 * Finds the value of this element from userland (in {@link _request}) and returns it
	 * @return mixed array, integer, or string if available, otherwise NULL if no value from userland
	 */
	function grab_value()
	{
		$value = parent::grab_value();

		/* We can detect that the Other field has content for us if:
		   1. $value['__other__'] is set (radio groups)
		   2. '__other__' is an array value in $value (checkbox groups)
		   3. $value = '__other__'
		   */
		if ($this->add_other && ((is_array($value) && (isset($value['__other__']) || array_search('__other__', $value))) || $value == '__other__' ))
		{
			$http_vars = $this->get_request();
			if ( !empty( $http_vars[ $this->name .'_other' ] ) )
			{
				$other_value = trim($http_vars[ $this->name .'_other' ]);
				if ($this->_array_val_ok() && is_array($value))
				{
					$value[$this->name .'_other'] = $other_value;
					if (isset($value['__other__']))
						unset($value['__other__']);
					else if ($key = array_search('__other__', $value))
						unset($value[$key]);
				}
				elseif ($this->_string_val_ok())
					$value = $other_value;
				elseif (!$this->_string_val_ok() && $this->_array_val_ok())
					$value = array($other_value);
			}
		}

		if($this->_array_val_ok() && !empty($this->disabled_options) )
		{
			$val_array = is_array($this->value) ? $this->value : array($this->value);

			if($disabled_selected = array_intersect($this->disabled_options,$val_array,array_keys($this->options)))
			{
				if(is_array($value))
				{
					$value = array_merge($value,$disabled_selected);
				}
				elseif(!is_null($value))
				{
					$value = array_merge(array($value),$disabled_selected);
				}
				else
				{
					$value = $disabled_selected;
				}
			}
		}

		if($this->_array_val_ok() && is_array($value))
		{
			foreach($value as $k=>$v)
			{
				if(!$this->_validate_submitted_value($v))
					unset($value[$k]);
			}
			return $value;
		}
		elseif($this->_string_val_ok() && $this->_validate_submitted_value($value) )
		{
			return $value;
		}
		return NULL;
	}

	protected function _validate_submitted_value($value)
	{
		// NULL is always OK?
		if(is_null($value))
			return true;

		if(!is_scalar($value))
		{
			trigger_error('Illegal value -- ('.gettype($value).') "'.$value.'" -- submitted for '.$this->name.'. This may be an attempt to probe for vulnerabilities.' );
			return false;
		}

		if($this->add_empty_value_to_top && '' === $value)
			return true;

		// If Other entry is enabled, the value is either one of other_options or anything at all
		if ($this->add_other && !isset($this->options[$value]))
		{
			if (!empty($this->other_options) && !isset($this->other_options[$value]))
				return false;
			else
				return true;
		}

		if($this->reject_unrecognized_values && !isset($this->options[$value]))
		{
			// Supress errors, as prefilled values, when enabled, also match
			// this criteria but are desired
			// trigger_error('Unrecognized value -- ('.gettype($value).') "'.$value.'" -- submitted for '.$this->name.'. This may be an attempt to probe for vulnerabilities. Future changes to plasmature will likely block unrecognized values like this.' );
			return false;
		}

		if(!$this->_is_disabled_option($value) || $this->_is_current_value($value))
		{
			return true;
		}


		return false;
	}
	/*
	Problem: Recognize that (str) "5" = (int) 5 and (str) "0" = (int) 0 but that
	(str) "foobar" != (int) 0, both ways. Fast.
	*/
	protected function _is_disabled_option($value)
	{
		if( !isset($this->_flipped_disabled_options) || $this->disabled_options !== $this->_options_change_detection_copy )
		{
			$disabled_options_to_be_flipped = $this->_options_change_detection_copy = $this->disabled_options;
			$changed = false;
			foreach($disabled_options_to_be_flipped as $k=>$v)
			{
				if(!is_string($v) && !is_integer($v))
				{
					unset($disabled_options_to_be_flipped[$k]);
					$changed = true;
				}
			}
			if($changed)
				trigger_error('The disabled_options array may only contain strings or integers. Non-string/non-integer disabled_options set on '.$this->name.' will be ignored.');
			$this->_flipped_disabled_options = array_flip($disabled_options_to_be_flipped);
		}
		return isset($this->_flipped_disabled_options[(string) $value]);
		// Broken solution
		//return in_array( (string) $value, $this->disabled_options );
		// possibly slow solution
		/* foreach($this->disabled_options as $disabled_option)
		{
			if((string) $value == (string) $disabled_option)
				return true;
		}
		return false; */
	}

	protected function _is_current_value($value, $report = false)
	{
		if($report) echo $value.' :: '.$this->value.' :: ';
		if(!isset($this->value) && NULL !== $value )
			return false;
		if(is_array($this->value))
		{
			if($report) echo in_array( (string) $value, $this->value ).'<br />';
			return in_array( (string) $value, $this->value );
		}
		else
		{
			if($report) echo ( (string) $value == (string) $this->value ).'<br />';
			return ( (string) $value == (string) $this->value );
		}
	}

	protected function _is_option($value)
	{
		if($this->add_empty_value_to_top && '' == (string) $value)
			return true;

		return isset($this->options[$value]);
	}

	/**
	  * Make sure the other value is visible in the request
	  **/
	function get_cleanup_rules()
	{
		$rules = parent::get_cleanup_rules();
		if ($this->add_other)
			return array_merge($rules, array( $this->name . '_other' => array('function' => 'turn_into_string' )));
		else
			return $rules;
	}

	/* function set( $value )
	{
		if(is_array( $value ))
		{
			foreach( $value as $v)
			{
				if(!$this->_is_option($v))
				{
					trigger_error('Value set on '.$this->name.' element ('.$v.') is not a recognized option.');
				}
			}
		}
		elseif( !$this->_is_option($value) )
		{
			trigger_error('Value set on '.$this->name.' element ('.$value.') is not a recognized option.');
		}
		parent::set( $value );
	} */

}

/**
 * Powers plasmature types that have multiple options (e.g. radio buttons, select drop-downs).
 * This is the same as the {@link optionType} but leaves options unsorted.
 * @package disco
 * @subpackage plasmature
 * @deprecated Use {@link optionType} instead and set {@link sort_options} to false.
 */
class option_no_sortType extends optionType
{
	var $type = 'option_no_sort';
	var $sort_options = false;
}

/**
 * Displays {@link options} as radio buttons.
 * @package disco
 * @subpackage plasmature
 */
class radioType extends optionType
{
	var $type = 'radio';
	var $type_valid_args = array( 'sub_labels', 'tableless' );
	var $sub_labels = array();
	var $tableless = false;

	protected function _array_val_ok()
	{
		return false;
	}
	function get_display()
	{
		$i = 0;
		$str = '<div id="'.$this->name.'_container" class="radioButtons" role="group" aria-label=
		"'.html_attribute_escape($this->display_name).'">'."\n";
		if (!$this->tableless) $str .= '<table border="0" cellpadding="1" cellspacing="0">'."\n";
		$checked = false;
		if($this->add_empty_value_to_top)
		{
			$str .= $this->_get_radio_item('',$this->empty_value_label,$i++);
			if ( '' === $this->value ) $checked = true;
		}
		foreach( $this->options as $key => $val )
		{
			if (!empty($this->sub_labels[$key])) $str .= $this->_get_sub_label_item($key);
			$str .= $this->_get_radio_item($key,$val,$i++);
			if ( $this->_is_current_value($key) ) $checked = true;
		}
		if ($this->add_other) $str .= $this->_get_other_item($i++, $checked);
		if (!$this->tableless) $str .= '</table>'."\n";
		$str .= '</div>'."\n";
		return $str;
	}

	protected function _get_radio_item($key,$val,$count)
	{
		$str = '';
		$id = 'radio_'.$this->name.'_'.$count;
		if ($this->tableless)
			$str .= '<div class="radioItem"><span class="radioButton"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.htmlspecialchars($key, ENT_QUOTES).'"';
		else
			$str .= '<tr>'."\n".'<td valign="top"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.htmlspecialchars($key, ENT_QUOTES).'"';

		if ( $this->_is_current_value($key) )
			$str .= ' checked="checked"';
		if ( $this->_is_disabled_option($key) )
			$str .= ' disabled="disabled"';
		if ($this->tableless)
			$str .= ' /></span><label for="'.$id.'">'.$val.'</label></div> '."\n";
		else
			$str .= ' /></td>'."\n".'<td valign="top"><label for="'.$id.'">'.$val.'</label></td>'."\n".'</tr>'."\n";
		return $str;
	}

	protected function _get_sub_label_item($key)
	{
		if (!empty($this->sub_labels[$key]))
		{
			if ($this->tableless)
				return '<div class="radioItem sublabel">'.$this->sub_labels[$key].'</div>'."\n";
			else
				return '<tr class="sublabel">'."\n".'<td colspan="2">'.$this->sub_labels[$key].'</td>'."\n".'</tr>'."\n";
		}
	}

	protected function _get_other_item($count, $checked)
	{
		$id = 'radio_'.$this->name.'_'.$count;
		if ($this->tableless)
			$str = '<div class="radioItem radioItemOther"><span class="radioButton"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="__other__"';
		else
			$str = '<tr>'."\n".'<td valign="top"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="__other__"';

		if ( !$checked && $this->value)
		{
			$other_value = $this->value;
			$str .= ' checked="checked"';
		} else {
			$other_value = '';
		}

		if ($this->tableless)
			$str .= ' /></span><label for="'.$id.'">'.$this->other_label.'</label> '."\n";
		else
			$str .= '></td>'."\n".'<td valign="top"><label for="'.$id.'">'.$this->other_label.'</label>';

		if (empty($this->other_options))
		{
			$str .= '<input aria-label="'.str_replace(':','',html_attribute_escape($this->other_label)).' (Enter)" type="text" name="'.$this->name.'_other" value="'.str_replace('"', '&quot;', $other_value).'"  />';
		}
		else
		{
			$str .= '<select aria-label="'.str_replace(':','',html_attribute_escape($this->other_label)).' (Enter)" name="'.$this->name.'_other" class="other">';
			foreach($this->other_options as $k => $v)
			{
				$selected = ($k == $other_value) ? ' selected="selected"' : '';
				$str .= '<option value="'.htmlspecialchars($k, ENT_QUOTES).'"'.$selected.'>'.strip_tags($v).'</option>'."\n";
			}
			$str .= '</select>';
		}
		if ($this->tableless)
			$str .= '</div>'."\n";
		else
			$str .= '</td>'."\n".'</tr>'."\n";

		return $str;
	}
}

/**
 * Same as {@link radioType} radio buttons, but inline.
 * @package disco
 * @subpackage plasmature
 */
class radio_inlineType extends radioType
{
	var $type = 'radio_inline';
	var $tableless = true;
}

/**
 * Same as {@link radioType}, but doesn't sort {@link options}.
 * @package disco
 * @subpackage plasmature
 */
class radio_no_sortType extends radioType
{
	var $type = 'radio_no_sort';
	var $sort_options = false;
}

/**
 * Same as {@link radio_inlineType}, but doesn't sort {@link options}.
 * @package disco
 * @subpackage plasmature
 */
class radio_inline_no_sortType extends radioType
{
	var $type = 'radio_inline_no_sort';
	var $tableless = true;
	var $sort_options = false;
}

/**
 * Displays {@link options} as radio buttons, adding an "Other" freetext option at the end.
 * @package disco
 * @subpackage plasmature
 */
class radio_with_otherType extends radioType
{
	var $type = 'radio_with_other';
	var $add_other = true;
}

/**
 * Same as {@link radio_with_otherType}, but doesn't sort {@link options}.
 * @package disco
 * @subpackage plasmature
 */
class radio_with_other_no_sortType extends radioType
{
	var $type = 'radio_with_other_no_sort';
	var $add_other = true;
	var $sort_options = false;
}

class radio_inline_with_otherType extends radioType
{
	var $type = 'radio_inline_with_other';
	var $tableless = true;
	var $add_other = true;
}

/**
 * Same as {@link radio_inline_with_otherType}, but doesn't sort {@link options}.
 * @package disco
 * @subpackage plasmature
 */
class radio_inline_with_other_no_sortType extends radioType
{
	var $type = 'radio_inline_with_other_no_sort';
	var $tableless = true;
	var $add_other = true;
	var $sort_options = false;
}

/**
 * Displays {@link options} as a group of checkboxes.
 * Use {@link checkboxType} to create a single checkbox.
 * @package disco
 * @subpackage plasmature
 */
class checkboxgroupType extends optionType
{
	var $type = 'checkboxgroup';
	var $type_valid_args = array( 'sub_labels', 'tableless' );
	var $sub_labels = array();
	var $tableless = false;

	protected function _string_val_ok()
	{
		return false;
	}
	function get_display()
	{
		$str = '<div class="checkBoxGroup" role="group" aria-label=
		"'.html_attribute_escape($this->display_name).'">'."\n";
		if (!$this->tableless) $str .= '<table border="0" cellpadding="1" cellspacing="0">'."\n";
		$i = 0;

		if($this->add_empty_value_to_top)
		{
			$str .= $this->_get_checkbox_item('',$this->empty_value_label,$i++);
		}

		foreach( $this->options as $key => $val )
		{
			if (!empty($this->sub_labels[$key])) $str .= $this->_get_sub_label_item($key);
			$str .= $this->_get_checkbox_item($key,$val,$i++);
		}

		if ($this->add_other) $str .= $this->_get_other_item($i++);

		if (!$this->tableless) $str .= '</table>'."\n";
		$str .= '</div>'."\n";
		return $str;
	}

	protected function _get_checkbox_item($key,$val,$count)
	{
		$id = 'checkbox_'.$this->name.'_'.$count;
		if ($this->tableless)
			$str = '<div class="checkboxItem">';
		else
			$str = '<tr><td valign="top">';

		$str .= '<input type="checkbox" id="'.$id.'" name="'.$this->name.'['.$count.']" value="'.htmlspecialchars($key, ENT_QUOTES).'"';

		if ( $this->_is_current_value($key) )
			$str .= ' checked="checked"';
		if ( $this->_is_disabled_option($key) )
			$str .= ' disabled="disabled"';

		if ($this->tableless)
			$str .= ' />';
		else
			$str .= ' /></td><td valign="top">';

		$str .= '<label for="'.$id.'">'.$val.'</label>';

		if ($this->tableless)
			$str .= '</div>'."\n";
		else
			$str .= '</td></tr>'."\n";

		return $str;
	}

	protected function _get_other_item($count)
	{
		// Identify any set values that aren't supported by the option list
		if (is_array($this->value))
			$val_array = $this->value;
		else if (!empty($this->value))
			$val_array = array($this->value);
		else
			$val_array = array();


		$value = array_diff($val_array, array_keys($this->options));

		$id = 'checkbox_'.$this->name.'_'.$count;


		if ($this->tableless)
			$str = '<div class="checkboxItem checkboxItemOther">'."\n";
		else
			$str = '<tr>'."\n".'<td valign="top">';


		$str .= '<span class="checkBox"><input type="checkbox" id="'.$id.'" name="'.$this->name.'['.$count.']" value="__other__"';


		if (!empty($value))
		{
			$other_value = reset($value);
			$str .= ' checked="checked"';
		} else {
			$other_value = '';
		}

		if ($this->tableless)
			$str .= ' /></span>';
		else
			$str .= ' /></td><td valign="top">';


		$str .= '<label for="'.$id.'">'.$this->other_label.'</label>';

		$str .= '<input type="text" name="'.$this->name.'_other" value="'.str_replace('"', '&quot;', $other_value).'"  />';


		if ($this->tableless)
			$str .= '</div>'."\n";
		else
			$str .= '</td></tr>'."\n";


		return $str;
	}


	protected function _get_sub_label_item($key)
	{
		if (!empty($this->sub_labels[$key]))
		{
			if ($this->tableless)
				return '<div class="checkboxItem sublabel">'.$this->sub_labels[$key].'</div>'."\n";
			else
				return '<tr class="sublabel">'."\n".'<td colspan="2">'.$this->sub_labels[$key].'</td>'."\n".'</tr>'."\n";
		}
	}

	/**
	 * Finds the value of this element from userland (in {@link _request}) and returns it
	 * @return mixed array, integer, or string if available, otherwise NULL if no value from userland
	 */
	function grab_value()
	{
		// Without this condition, if the user unchecks all the
		// boxes, the default value will be used. The reason is
		// that if no checkboxes are checked, the browser sends no
		// post variable at all for the group. This is in contrast
		// to other form elements, e.g., input boxes, for which
		// the browser sends an empty string if the user doesn't
		// enter anything. And the default plasmature behavior
		// assumes that all the form elements will behave like the
		// input boxes. So what happens is that $this-value is
		// first set to the default value, and then over written
		// by the request variable if one is present (if an
		// appropriate request variable isn't present, it's
		// assumed that it's the first time). But in the case of
		// checkboxes, this is a false assumption, because (as I
		// said above) if no box is checked, no request variable
		// is sent. --NF 23/Feb/2004
		$val = parent::grab_value();
		return (NULL === $val) ? array() : $val;
	}
	function get()
	{
		// This conditional is needed because when disco checkes
		// whether values have been provided for all required
		// variables, disco simply (and naively, I might add)
		// evaluates the boolean value of $this->get(), rather
		// than the boolean value of, say
		// !empty($this->get()). (It might be worth fixing this
		// behavior of disco, but I'm not sure at this point
		// whether to do so would break anything.) But, in
		// contrast to an empty string, a variable which points to
		// an empty array evaluates to true. So I check for that
		// here. --NF 23/Feb/2004
		if ( empty($this->value) )
			return false;
		else
			return $this->value;
	}
	function get_cleanup_rules()
	{
		$rules = parent::get_cleanup_rules();
		$rules[$this->name] = array('function' => 'turn_into_array' );
		return $rules;
	}
}

/**
 * Like {@link checkboxgroupType}, but doesn't sort options.
 * @package disco
 * @subpackage plasmature
 */
class checkboxgroup_no_sortType extends checkboxgroupType
{
	var $type = 'checkboxgroup_no_sort';
	var $sort_options = false;
}

/**
 * Like {@link checkboxgroupType}, with an additional "Other" fill-in option added.
 * @package disco
 * @subpackage plasmature
 */
class checkboxgroup_with_otherType extends checkboxgroupType
{
	var $type = 'checkboxgroup_with_other';
	var $add_other = true;
}

/**
 * Like {@link checkboxgroup_with_otherType}, but doesn't sort options.
 * @package disco
 * @subpackage plasmature
 */
class checkboxgroup_with_other_no_sortType extends checkboxgroup_with_otherType
{
	var $type = 'checkboxgroup_with_other_no_sort';
	var $sort_options = false;
}

/**
 * Same as {@link checkboxgroupType}, but inline.
 * @package disco
 * @subpackage plasmature
 */
class checkboxgroup_inlineType extends checkboxgroupType
{
	var $type = 'checkboxgroup_inline';
	var $tableless = true;
}

/**
 * Same as {@link checkboxgroup_no_sortType}, but inline.
 * @package disco
 * @subpackage plasmature
 */
class checkboxgroup_inline_no_sortType extends checkboxgroup_inlineType
{
	var $type = 'checkboxgroup_inline_no_sort';
	var $sort_options = false;
}

/**
 * @package disco
 * @subpackage plasmature
 * @todo do better data checking to make sure that value is one of the available options (or empty/null)
 * @todo deprecate add_null_value_to_top argument
 */
class selectType extends optionType
{
	var $type = 'select';
	var $type_valid_args = array(	'n' => 'size',
									'multiple',
							     	'add_null_value_to_top');
	var $n = 1;
	/**
	 *  True if multiple options may be selected.
	 * @var boolean
	 */
	var $multiple = false;
	var $add_null_value_to_top;
	/**
	 * If true, adds a empty value to the top of the select.
	 */
	var $add_empty_value_to_top = true;
	protected function _array_val_ok()
	{
		return $this->multiple;
	}
	protected function _string_val_ok()
	{
		return !$this->multiple;
	}
	function additional_init_actions($args = array())
	{
		if(isset($this->add_null_value_to_top))
		{
			//trigger_error('add_null_value_to_top is deprecated. Please use add_empty_value_to_top instead.');
			$this->add_empty_value_to_top = $this->add_null_value_to_top;
		}
		parent::additional_init_actions($args);
	}
	function get_display()
	{
		//pray($this->value);
		$str = '<select id="'.htmlspecialchars($this->get_label_target_id()).'" name="'.$this->name.($this->multiple ? '[]' : '').'" size="'.htmlspecialchars($this->n, ENT_QUOTES).'" '.($this->multiple ? 'multiple="multiple"' : '');
		if(!$this->is_labeled())
		{
			$str .= ' aria-label="'.html_attribute_escape($this->display_name).'"';
		}
		$str .= '>'."\n";
		$select_count = 0;
		if($this->add_empty_value_to_top)
		{
			$str .= $this->_get_option_html('',$this->empty_value_label,$select_count);
		}
		foreach( $this->options as $key => $val )
		{
			if( !$this->add_empty_value_to_top && $val === '--' )
			{
				$str .= $this->_get_option_html('',$val,$select_count);
			}
			else
			{
				$str .= $this->_get_option_html($key,$val,$select_count);
			}
		}
		$str .= '</select>'."\n";
		return $str;
	}

	protected function _get_option_html($key,$val,&$select_count)
	{
		$str = '';
		$str .= '<option value="'.htmlspecialchars($key, ENT_QUOTES).'"';
		$selected = ( ( $this->multiple || !$select_count ) && $this->_is_current_value($key) );
		if( $selected )
		{
			$str .= ' selected="selected"';
			$select_count++;
		}
		if ( $this->_is_disabled_option($key) )
		{
			$str .= ' disabled="disabled"';
			if($selected && $this->multiple)
				$str .= ' style="color:#666;background-color:#ddd;" class="disabledSelected"';
		}
		$str .= '>'.$val.'</option>'."\n";
		return $str;
	}
	protected function _get_optgroup_html($opts)
	{
		$str = '';

		// loop through the array, if the val is an array, treat the key as an optgroup label
		foreach( $opts as $key => $val )
		{
			if (is_array($val))
			{
				$str .= '<optgroup label="'.$key.'">'."\n";
				foreach( $val as $k => $v )
				{
					if( !$this->add_empty_value_to_top && $val === '--' )
					{
						$str .= $this->_get_option_html('',$v,$select_count);
					}
					else
					{
						$str .= $this->_get_option_html($k,$v,$select_count);
					}
				}
				$str .= '</optgroup>'."\n";
			} else {
				if( !$this->add_empty_value_to_top && $val === '--' )
				{
					$str .= $this->_get_option_html('',$val,$select_count);
				}
				else
				{
					$str .= $this->_get_option_html($key,$val,$select_count);
				}
			}
		}
		return $str;
	}

	protected function _is_multi_array($array)
	{
		$rv = array_filter($array,'is_array');
	    if(count($rv)>0) return true;
	    return false;
	}
	
	function get_label_target_id()
	{
		return $this->name.'Element';
	}
}

/**
 * Single select with chosen js
 * @require jQuery
 * @package disco
 * @subpackage plasmature
 */
class chosen_selectType extends selectType
{
        var $type = 'chosen_select';
        var $min_width = 275;
        var $type_valid_args = array('min_width', 'search_substrings');
        var $search_substrings = True;


        function get_display()
        {
                $str = $this->get_chosen_select_js_css() . "\n";

                $str .= '<select id="'.$this->name.'Element" name="'.$this->name.($this->multiple ? '[]' : '').'" class="chosen-select" style="min-width:'.$this->min_width.'px;" size="'.htmlspecialchars($this->n, ENT_QUOTES).'" '.($this->multiple ? 'multiple="multiple"' : '').'>'."\n";

                $select_count = 0;

                $str .= $this->_get_optgroup_html($this->options);

                $str .= '</select>'."\n";

                $str .= '<script language="javascript" type="text/javascript">$(".chosen-select").chosen('.($this->search_substrings ? '{ search_contains: true }' : '').');</script>';

                return $str;
        }

        /**
         * We return the main javascript for Chosen Select - we use a static variable to keep track such that we include it only once.
         */
        function get_chosen_select_js_css()
        {
                // we only want to load the main js file once.
                static $loaded_an_instance;
                if (!isset($loaded_an_instance))
                {
                        $js_css = '';
                        $js_css .= '<script language="javascript" type="text/javascript" src="' . REASON_PACKAGE_HTTP_BASE_PATH . 'chosen_select/chosen.jquery.js"></script>'."\n";
                        $js_css .= '<link href="' . REASON_PACKAGE_HTTP_BASE_PATH . 'chosen_select/chosen.css" rel="stylesheet">'."\n";
                        $loaded_an_instance = true;
                }
                return (!empty($js_css)) ? $js_css : '';
        }
}

/**
 * Multiple select with chosen js
 * @require jQuery
 * @package disco
 * @subpackage plasmature
 */
class chosen_select_multipleType extends chosen_selectType
{
	var $type = 'chosen_select_multiple';
	var $multiple = true;


	function get_display()
	{
		$str = $this->get_chosen_select_js_css() . "\n";

		$str .= '<select id="'.$this->name.'Element" name="'.$this->name.($this->multiple ? '[]' : '').'" class="chosen-select" style="min-width:'.$this->min_width.'px;" size="'.htmlspecialchars($this->n, ENT_QUOTES).'" multiple="multiple">'."\n";

		$select_count = 0;

		foreach( $this->options as $key => $val )
		{
			if( !$this->add_empty_value_to_top && $val === '--' )
			{
				$str .= $this->_get_option_html('',$val,$select_count);
			}
			else
			{
				$str .= $this->_get_option_html($key,$val,$select_count);
			}
		}
		$str .= '</select>'."\n";
		$str .= '<script language="javascript" type="text/javascript">$(".chosen-select").chosen('.($this->search_substrings ? '{ search_contains: true }' : '').');</script>';
		return $str;
	}

	function grab_value()
	{
		// See checkboxgroup notes
		$val = parent::grab_value();
		return (NULL === $val) ? array() : $val;
	}
	function get()
	{
		// See checkboxgroup notes
		if ( empty($this->value) )
			return array();
		else
			return $this->value;
	}
}
/**
 * Same as {@link selectType}  but doesn't sort the {@link options}.
 * @package disco
 * @subpackage plasmature
 */
class select_no_sortType extends selectType
{
	var $type = 'select_no_sort';
	var $add_empty_value_to_top = false;
	var $sort_options = false;
}

/**
 * @package disco
 * @subpackage plasmature
 */
class select_no_labelType extends selectType // {{{
{
	var $_labeled = false;
}

/**
 * @package disco
 * @subpackage plasmature
 */
class file_listerType extends selectType
{
	var $type = 'file_lister';
	var $type_valid_args = array( 'extension',
								  'strip_extension',
								  'prettify_file_name',
								  'directory',
								  'hide_files_with_initial_period',
							);
	var $extension;
	var $strip_extension = false;
	var $prettify_file_name = false;
	var $directory;
	var $hide_files_with_initial_period = true;
	function load_options( $args = array())
	{
		$files = array();
		if ( isset( $this->directory ) )
		{
			if($handle = opendir( $this->directory ))
			{
				while( $entry = readdir( $handle ) )
				{
					if( is_file( $this->directory.$entry ) && ( !$this->hide_files_with_initial_period || 0 !== strpos($entry,'.') ) )
					{
						$show_entry = true;
						$entry_display = $entry_value = $entry;
						if( !empty( $this->strip_extension ) )
							$entry_display = $entry_value = substr( $entry, 0, strrpos( $entry, '.' ));
						if( !empty( $this->prettify_file_name ) )
							$entry_display = prettify_string( substr( $entry, 0, strrpos( $entry, '.' ) 	) );
						if( !empty( $this->extension ) )
							if ( !preg_match( '/'.$this->extension.'$/',$entry ) )
								$show_entry = false;
						if( $show_entry )
							$files[ $entry_value ] = $entry_display;
					}
				}
				ksort( $files );
			}
			else
			{
				trigger_error('Directory does not appear to be readable ('.$this->directory.').');
			}
		}
		$this->options += $files;
	}
}

/**
 * Presents options in a select element after loading them from a table.
 *
 * @deprecated
 * @package disco
 * @subpackage plasmature
 */
class tablelinkerType extends selectType
{
	var $type = 'tablelinker';
	var $table;
	var $type_valid_args = array('table');
	function load_options( $args = array() )
	{
		trigger_error('The tablelinker type is in the process of being removed from Plasmature, and no longer works. Please use another method to accomplish this goal.');
	}
	function get_display()
	{
		echo '(This field needs to be upgraded.)';
	}
}

/**
 * Displays a drop-down of numbers within the given range.
 * Begins at {@link start} (default 0) and stops after {@link end} (default 10), using {@link step} (default 1) to increment.
 * @package disco
 * @subpackage plasmature
 */
class numrangeType extends selectType
{
	var $type = 'numrange';
	var $start = 0;
	var $end = 10;
	var $step = 1;
	var $sort_options = false;
	var $type_valid_args = array ( 'start',
								   'end',
								   'step',
								  );
	function load_options( $args = array() )
	{
		$this->options = array();
		$this->add_numrange_to_options();
	}
	function add_numrange_to_options()
	{
		if($this->start <= $this->end)
		{
			for( $i = $this->start; $i <= $this->end; $i += $this->step )
				$this->options[ $i ] = $i;
		}
		else
		{
			for( $i = $this->start; $i >= $this->end; $i -= $this->step )
				$this->options[ $i ] = $i;
		}
	}
}
/**
 * Displays a drop-down of ages.
 * Exactly like {@link numrangeType} except that it displays '0-1' instead of '0' and won't display negative numbers.
 * @package disco
 * @subpackage plasmature
 */
class ageType extends numrangeType
{
	var $type = 'age';
	function load_options( $args = array() )
	{
		if( $this->start < 0 )
			$this->start = 0;
		if( $this->start == 0 )
		{
			$this->options[ '0-1' ] = '0-1';
			$this->start = 1;
		}
		$this->add_numrange_to_options();
	}
}

/**
 * @package disco
 * @subpackage plasmature
 */
class select_multipleType extends selectType
{
	var $type = 'select_multiple';
	var $type_valid_args = array( 'select_size',
								  'multiple_display_type'
								);
	/** Can be 'checkbox' or 'select' to use multiple checkboxes or a multiple select box, respectively*/
	var $multiple_display_type = 'select';
	/** if using multiple select box, how many rows to show at once? */
	var $select_size = 8;
	var $add_empty_value_to_top = false;

	protected function _string_val_ok()
	{
		return false;
	}
	protected function _array_val_ok()
	{
		return true;
	}
	function get_display()
	{
		$str = '';
		if( $this->multiple_display_type == 'checkbox' )
		{
			if($this->add_empty_value_to_top)
			{
				$str .= $this->_get_checkbox_html('',$this->empty_value_label);
			}
			foreach( $this->options as $key => $val )
			{
				$str .= $this->_get_checkbox_html($key,$val);
			}
			$str .= "\n";
		}
		else
		{
			$str = '<select id="'.htmlspecialchars($this->get_label_target_id()).'" name="'.$this->name.'[]" multiple="multiple" size="'.$this->select_size.'">'."\n";
			$select_count = 0;
			if($this->add_empty_value_to_top)
			{
				$str .= $this->_get_option_html('',$this->empty_value_label,$select_count);
			}
			foreach( $this->options as $key => $val )
			{
				$str .= $this->_get_option_html($key,$val,$select_count);
			}
			$str .= '</select>'."\n";
		}
		return $str;
	}

	protected function _get_checkbox_html($key,$val)
	{
		$str = '';
		$str .= "\n".'<input type="checkbox" id="'.$this->name.'-'.htmlspecialchars($key, ENT_QUOTES).'" name="'.$this->name.'[]" value="'.htmlspecialchars($key, ENT_QUOTES).'"';
		if( $this->_is_current_value($key) )
			$str .= ' checked="checked"';
		if ( $this->_is_disabled_option($key) )
			$str .= ' disabled="disabled"';
		$str .= ' />'."\n".'<label for="'.$this->name.'-'.htmlspecialchars($key, ENT_QUOTES).'">'.$val.'</label><br />'."\n";
		return $str;
	}

	protected function _get_option_html($key,$val,&$select_count)
	{
		$str = '';
		$str .= '<option value="'.htmlspecialchars($key, ENT_QUOTES).'"';
		$is_cur_val = $this->_is_current_value($key);
		if ( $is_cur_val )
			$str .= ' selected="selected"';
		if ( $this->_is_disabled_option($key) )
		{
			$str .= ' disabled="disabled"';
			if($is_cur_val)
				$str .= ' style="color:#666;background-color:#ddd;" class="disabledSelected"';
		}
		$str .= '>'.$val.'</option>'."\n";
		return $str;
	}
	function grab_value()
	{
		// See checkboxgroup notes
		$val = parent::grab_value();
		return (NULL === $val) ? array() : $val;
	}
  
	function get()
	{
		// See checkboxgroup notes
		if ( empty($this->value) )
			return array();
		else
			return $this->value;
	}
	function get_label_target_id()
	{
		if( $this->multiple_display_type == 'checkbox' )
			return false;
		return parent::get_label_target_id();
	}
}

/**
 * @package disco
 * @subpackage plasmature
 */
class select_jsType extends selectType
{
	var $type = 'select_js';
	var $script_url = '';
	var $type_valid_args = array('n' => 'size', 'multiple', 'script_url',
		'add_null_value_to_top');

	function get_display()
	{
		$str  = $this->script_tag();
		$str .= parent::get_display();
		return $str;
	}

	function script_tag()
	{
		$s = '';
		if ( !empty( $this->script_url ) )
			$s = '<script language="JavaScript" src="'.$this->script_url.'"></script>'."\n";
		return $s;
	}
}

/**
 * Same as {@link select_jsType} but doesn't sort options.
 * @package disco
 * @subpackage plasmature
 */
class select_no_sort_jsType extends select_jsType
{
	var $type = 'select_no_sort_js';
	var $sort_options = false;
}


class range_sliderType extends defaultType
{
	var $type = 'range_slider';

	/** @access private */
	var $type_valid_args = array( 'min', 'max', 'step', 'value' );
	var $min = 0;
	var $max = 10;
	var $step = 1;
	var $value = 1;


	function get_display()
	{
		return '<input type="range" name="'.$this->name.'" value="'.str_replace('"', '&quot;', $this->get()).'"   id="'.htmlspecialchars($this->get_label_target_id()).'" min="'.$this->min.'" max="'.$this->max.'" step="'.$this->step.'" />';
	}
	function get_label_target_id()
	{
		return $this->name.'Element';
	}
}