<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class & dependencies, and register the module with Reason
  */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/calendar.php' );
reason_include_once( 'classes/calendar_grid.php' );
reason_include_once( 'classes/icalendar.php' );
reason_include_once( 'classes/page_types.php' );
reason_include_once( 'classes/function_bundle.php' );
reason_include_once( 'classes/api/api.php' );
reason_include_once( 'classes/borrow_this.php' );
reason_include_once( 'function_libraries/safe_json.php' );
reason_include_once( 'classes/string_to_sites.php' );
include_once(CARL_UTIL_INC . 'cache/object_cache.php');
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
include_once( CARL_UTIL_INC . 'basic/cleanup_funcs.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsModule';


/**
 * A minisite module that presents a calendar of events
 *
 * By default, this module shows upcoming events on the current site,
 * and proves an interface to see past events
 */
class EventsModule extends DefaultMinisiteModule
{
	/**
	 * The string that acts as a cache for the run method. Set by the get_run_output() method
	 * @var string
	 */
	var $run_output_string;
	/**
	 * The number of events that the calendar attempts to display if there is no
	 * duration specified. Note that the actual number may be larger or smaller
	 * due to the calendar "snapping" to full days.
	 * @var integer
	 */
	var $ideal_count;
	/**
	 * Callback to a function which operates on the entity selector used by 
	 * the reasonCalendar object to find events (passed by reference as the 
	 * first argument).
	 * @var string
	 */
	var $es_callback;
	/**
	 * The id of the wrapper class around the module
	 * @var string
	 * @access public
	 */
	var $div_id = 'calendar';
	/**
	 * Should the module display options (e.g. categories, audiences, etc.)?
	 * @var boolean
	 * @deprecated This is someting that should be handled by the events_list_chrome class; will be removed in 4.6
	 */
	var $show_options;
	/**
	 * Should the module display navigation (e.g. next/previous links)?
	 * @var boolean
	 * @deprecated This is someting that should be handled by the events_list_chrome class; will be removed in 4.6
	 */
	var $show_navigation;
	/**
	 * Should the module display available views (e.g. all/year/month/day)?
	 * @var boolean
	 * @deprecated This is someting that should be handled by the events_list_chrome class; will be removed in 4.6
	 */
	var $show_views;
	/**
	 * Should the module display a month-grid date picker?
	 * @var boolean
	 * @deprecated This is someting that should be handled by the events_list_chrome class; will be removed in 4.6
	 */
	var $show_calendar_grid;
	/**
	 * Should the module display event times?
	 * @var boolean
	 * @deprecated This is someting that should be handled by the events_list or the events_list_item class; will be removed in 4.6
	 */
	var $show_times;
	/**
	 * The request keys that represent persistent state.
	 *
	 * The module ought to include these request variables in links if they are present.
	 *
	 * @var array
	 * @access private
	 */
	var $passables = array('start_date','textonly','view','category','audience','end_date','search');
	
	/**
	 * Should the module limit events to the current site?
	 *
	 * @deprecated Use additional_sites and sharing_mode parameters instead
	 * @var boolean
	 */
	var $limit_to_current_site = true;
	/**
	 * Key-value pairs (from the request) that should be included in links
	 * unless explicitly cleared.
	 *
	 * These are determined by @passables and represent state that should persist among
	 * distinct page views (again, unless explicitly changed or cleared by the user).
	 *
	 * @var array
	 * @access private
	 */
	var $pass_vars = array();
	/**
	 * Does the module have content to display?
	 *
	 * This should be set in the init phase, as the has_content function needs it to respond correctly.
	 * @var boolean
	 * @access private
	 */
	var $has_content = true;
	/**
	 * The reasonCalendar object that represents the primary set of events being displayed
	 *
	 * @var object
	 * @access private
	 */
	var $calendar;
	/**
	 * The beginning date for events being displayed
	 *
	 * This is populated during init phase.
	 *
	 * @var string A MySQL-formatted date (e.g. YYYY-MM-DD)
	 * @access private
	 */
	var $start_date;
	/**
	 * When calculating the window of time to display (if not given),
	 * should the calendar limit itself to defined views (e.g. day/week/monthyear)?
	 *
	 * @var boolean true to limit to defined views; false to select arbitrary date range
	 */
	var $snap_to_nearest_view = true;
	/**
	 * Events to display
	 *
	 * This is populated during init phase.
	 *
	 * Note that these are date entities, not individual occurrences. Do not iterate through
	 * this array to build the calendar, as you will not handle repeating events correctly.
	 *
	 * @var array of Reason entities
	 * @access private
	 */
	var $events = array();
	/**
	 * Event ids to display, organized by date
	 *
	 * This is populated during init phase.
	 *
	 * @var array in form array('YYYY-MM-DD'=>array('1','2',3'),'YYYY-MM-DD'=>array(...),...)
	 * @access private
	 */
	var $events_by_date = array();
	/**
	 * HTML for the next and previous links
	 *
	 * It turns out that generating these links is surprisingly expensive, so we only do it once, and store the resuts here.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $next_and_previous_links;
	/**
	 * HTML for the calendar grid
	 *
	 * Since generating the grid can be expensive, we store the results here so we don't have to
	 * do it twice.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $calendar_grid_markup = '';
	/**
	 * HTML for the options bar (e.g. categories/audiences/etc.)
	 *
	 * Since generating the options bar can be expensive, we store the results here 
	 * so we don't have to do it twice.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $options_bar;
	/**
	 * Today's date, in MySQL format (YYYY-MM-DD)
	 *
	 * This is set in init.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $today;
	/**
	 * Tomorrow's date, in MySQL format (YYYY-MM-DD)
	 *
	 * This is set in init.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $tomorrow;
	/**
	 * Yesterday's date, in MySQL format (YYYY-MM-DD)
	 *
	 * This is set in init.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $yesterday;
	
	/**
	 * The current event (if viewing an individual event)
	 *
	 * This is set in init.
	 *
	 * @var mixed a Reason event entity or NULL
	 * @access private
	 */
	var $event;
	
	/**
	 * The URL of the events page
	 *
	 * In the base events module, this is empty, but in sidebar-style modules this should be
	 * populated in the init phase with the proper URL.
	 *
	 * @var mixed URL string or NULL
	 */
	var $events_page_url;
	/**
	 * The format for the display of time information in the listing
	 * @var string
	 * @deprecated List markup now responsible for this
	 */
	var $list_time_format = 'g:i a';
	/**
	 * The format for the display of date information in the listing
	 * @var string
	 * @deprecated List markup now responsible for this
	 */
	var $list_date_format = 'l, F jS';
	/**
	 * The audiences that should be available as filters on this calendar
	 * 
	 * This is set in the init phase.
	 *
	 * @var array of reason entities
	 * @access private
	 */
	var $audiences = array();
	/**
	 * Should this calendar show the most recent event if no future events are found?
	 * @var boolean
	 */
	var $rerun_if_empty = true;
	/**
	 * Should this calendar report date of the current event (or event being shown) when
	 * asked for last modified information?
	 * @var boolean
	 */
	var $report_last_modified_date = true;
	/**
	 * HTML markup for the view navigation (e.g. day/week/etc.)
	 *
	 * In order to avoid generating the view navigation twice, it gets stored here
	 * @var string
	 * @access private
	 */
	var $view_markup = '';
	/**
	 * The year that has the earliest calendar item
	 *
	 * This should not be accessed directly; instead use the method get_min_year()
	 *
	 * @var string YYYY
	 * @access private
	 */
	var $min_year;
	/**
	 * The year that has the last calendar item
	 *
	 * This should not be accessed directly; instead use the method get_max_year()
	 *
	 * @var string YYYY
	 * @access private
	 */
	var $max_year;
	/**
	 * The parameters that page types can set on the module.
	 *
	 * 'additional_sites' (string) Sites other than the current one to pull events from.
	 * This can be a comma-separated set of site and/or site type unique names OR the keywords 'k_parent_sites', 'k_child_sites', or 'k_sharing_sites'
	 *
	 * 'cache_lifespan' How long, in seconds, should the calendar cache the events?
	 *
	 * 'cache_lifespan_meta' How long, in seconds, should the calendar cache calendar metadata,
	 *  like window, category, and audience determination?
	 *
	 * 'calendar_link_text' (string) text of link to full calendar view.
	 *
	 * 'default_view_min_days' (integer) sets a smallest number of days the dynamically selected view can have.
	 *
	 * 'exclude_audiences' (string, comma spaced for multiple) don't show events for these audiences
	 *
	 * 'freetext_filters' An array of filters, each in the following format:
	 *   array('string to filter on','fields,to,search')
	 *   The string to filter on is interpreted as a LIKE statement.
	 *
	 * 'ideal_count' (integer) sets the @ideal_count value for dynamic view selection
	 *
	 * 'item_admin_markup' () NEEDS DESCRIPTION
	 *
	 * 'item_markup' (string) The path to the item markup class
	 *
	 * 'limit_by_related_types' (array) allows you to select events that share an association
	 *   with a page. Pass an array with type and relationships, e.g.:
	 *
	 *		'limit_by_related_types' => array(
	 *				'shared_type' => array(
	 *					'page_rel' => array('shared_type_to_page'),
	 *					'entity_rel' => array('event_to_shared_type'),
	 *					)),
	 *
	 * 'limit_to_audiences' (string, comma spaced for multiple) limit to these audiences
	 *
	 * 'limit_to_page_categories' (boolean) determines if the module will display all events on site
	 * or just those with a category matching one on current page
	 *
	 * 'link_shared_events_to_parent_site' (boolean) by default, the detail view of an event
	 *   borrowed from another site is shown in the local site context. Set this to true to
	 *   have borrowed events link directly back to their parent site.
	 *
	 * 'list_chrome_markup' (string) The path to the list chrome markup class
	 *
	 * 'list_item_markup'  (string) The path to the list item markup class
	 *
	 * 'list_markup' (string) The path to the list markup class
	 *
	 * 'list_thumbnail_crop' () NEEDS DESCRIPTION
	 *
	 * 'list_thumbnail_default_image' () NEEDS DESCRIPTION
	 *
	 * 'list_thumbnail_height' (int) NEEDS DESCRIPTION
	 *
	 * 'list_thumbnail_width' (int) NEEDS DESCRIPTION
	 *
	 * 'list_type' (string) can be 'standard' or 'verbose' NOTE: This parameter is deprecated. Use list_markup to specify a markup generator instead.
	 *
	 * 'map_zoom_level' (int) set a zoom level for google maps - default 12
	 *
	 * 'natural_sort_categories' (boolean) NEEDS DESCRIPTION
	 *
	 * 'ongoing_show_ends' (boolean) Show the ending dates for events? Note that in combination with 
	 *
	 * 'sharing_mode' (string) can be 'all' (e.g. both shared and private) or 'shared_only'
	 *
	 * 'show_images' (boolean) determines if teaser images are displayed in list
	 *
	 * 'start_date' (string) forces the calendar to use as its default start date a date other than the current one 
	 *
	 * 'view' (string) forces a specific view. Possible values: daily, weekly, monthly, yearly, all
	 *
	 * 'wrapper_id' () NEEDS DESCRIPTION
	 *
	 * @var array
	 * @access private
	 * @todo review current default default_view_min_days value for sanity
	 */
	var $acceptable_params = array(
							'additional_sites'=>'',
							'exclude_sites'=>'',
	 						'cache_lifespan' => 0,
	 						'cache_lifespan_meta' => 0,
	 						'calendar_link_text' => 'More events',
	 						'calendar_link_replacement' => '',
							'default_view_min_days'=>1,
	 						'exclude_audiences' => '',
	 						'freetext_filters' => array(),
							'ideal_count'=>NULL,
							'item_admin_markup' => '',
							'item_markup' => '',
	 						'limit_by_related_types' => '', // as comma spaced type unique names
							'limit_to_ticketed_events'=>false,
	 						'limit_to_audiences' => '',	 // as comma spaced strings
							'limit_to_page_categories'=>false,
							'filter_on_page_name' => false,
	 						'link_shared_events_to_parent_site' => false,
							'list_chrome_markup' => '',
							'list_item_markup' => '',
							'list_markup' => '',
							'list_thumbnail_crop' => '',
							'list_thumbnail_default_image' => '', // a unique name
							'list_thumbnail_height' => 0,
							'list_thumbnail_width' => 0,
							'list_type'=>'', // deprecated -- use list_markup instead
	 						'map_zoom_level' => 12,
	 						'natural_sort_categories' => false,
	 						'ongoing_show_ends' => true, // deprecated
							'sharing_mode'=>'',
							'show_images'=>false,
	 						'start_date'=>'',
	 						'view'=>'',
	 						'wrapper_id' => '',
						);
	var $default_item_markup = 'minisite_templates/modules/events_markup/default/events_item.php';
	var $default_item_admin_markup = 'minisite_templates/modules/events_markup/default/events_item_admin.php';
	var $default_list_markup = 'minisite_templates/modules/events_markup/default/events_list.php';
	var $default_list_item_markup = 'minisite_templates/modules/events_markup/default/events_list_item.php';
	//var $default_list_item_markup = 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php';
	var $default_list_chrome_markup = 'minisite_templates/modules/events_markup/default/events_list_chrome.php';
	/**
	 * Views that should not be indexed by search engines
	 *
	 * The idea is that this keeps search engines from indexing pages that are redundant 
	 * with other pages. Note that these pages should still allow robots to follow links, just
	 * not to index the pages.
	 * @var array
	 * @access private
	 */
	var $views_no_index = array('daily','weekly','monthly');
	/**
	 * Sites that should be queried for events
	 * @access private
	 * @var array
	 */
	var $event_sites;
	/**
	 * Toggles on and off the links to iCalendar-formatted data.
	 *
	 * Set to true to turn on the links; false to turn them off
	 * @var boolean
	 * @deprecated Now a responsibility of the events_list_chrome class
	 */
	var $show_icalendar_links = true;
	
	var $noncanonical_request_keys = array(
									'audience',
									'view',
									'start_date',
									'category',
									'end_date',
									'nav_date',
									'textonly',
									'start_month',
									'start_day',
									'start_year',
									'search',
									'format',
									'no_search');

	/**
	 * A place to store the default image so it does not have to be
	 * re-identified for each imageless event
	 * @var mixed NULL, false, or image entity object
	 */
	protected $_list_thumbnail_default_image;
	/**
	 * Flag indicating whether the current user can inline edit on this site.
	 *
	 * @var boolean
	 */
	protected $_user_can_inline_edit;
	/**
	 * List of site ids and whether the user can inline edit them; used to
	 * efficiently keep track of editability of events that may be coming
	 * from multiple sites.
	 *
	 * @var array
	 */
	protected $_user_can_inline_edit_sites = array();
	/**
	 * Flag indicating whether the current user has editing rights for this site.
	 *
	 * @var boolean
	 */
	protected $_user_has_editing_rights_to_current_site;
	
	/**
	 * Array of set-up markup classes
	 *
	 * Cache for get_markup_object()
	 *
	 * @var array
	 */
	protected $_markups = array();
	
	/**
	 * A message to display in the list markup
	 *
	 * Do not set this on the class manually -- it is set dynamically.
	 *
	 * @todo come up with a better way to handle this
	 *
	 * @var string html
	 */
	protected $_no_events_message = '';

	/**
	 * Container array of form controllers for forms associated with the event
	 * 
	 * Usually there is only one form per event.
	 * 
	 * Controllers here haven't had init() run yet
	 * 
	 * @var array
	 */
	var $ticket_form_controllers = array();
	
	//////////////////////////////////////
	// General Functions
	//////////////////////////////////////
	
	/**
	 * Initialize the module
	 */
	function init( $args = array() )
	{
		parent::init( $args );
		$head_items =& $this->get_head_items();

		// $this->head_items('Content-Disposition: attachment; filename=data.csv'); // Added by rabbanii
		if(!empty($this->params['list_type']))
		{
			if('verbose' == $this->params['list_type'] && '' == $this->params['list_item_markup'])
			{
				$this->params['list_item_markup'] = 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php';
				trigger_error('Events module: list_type parameter is deprecated and will stop working in Reason 4.6. Please specify "list_item_markup" => "'.$this->params['list_item_markup'].'" instead.');
			}
			elseif('schedule' == $this->params['list_type'] && '' == $this->params['list_markup'])
			{
				$this->params['list_markup'] = 'minisite_templates/modules/events_markup/schedule/schedule_events_list.php';
				$this->params['list_item_markup'] = 'minisite_templates/modules/events_markup/schedule/schedule_events_list_item.php';
				trigger_error('Events module: list_type parameter is deprecated and will stop working in Reason 4.6. Please specify "list_markup" => "'.$this->params['list_markup'].'", "list_item_markup" => "'.$this->params['list_item_markup'].'" instead.');
			}
		}
		
		$this->validate_inputs();
		
		$this->register_passables();
		
		$this->handle_jump();
				
		if(empty($this->request['event_id']))
		{
			$this->init_list();
		}
		else
		{
			$this->init_event();
		}
		$inline_edit =& get_reason_inline_editing($this->page_id);
		$inline_edit->register_module($this, $this->user_can_inline_edit());
		if ($inline_edit->active_for_module($this))
		{
			$user = reason_get_current_user_entity();
			$event_id = $this->request['event_id'];
			/* Event adding is currently not functional
			if( !($event_id = $this->request['event_id'] ) )
			{
				if(reason_user_has_privs($user->id(), 'add' ))
				{
					if ($event_id = create_entity( $this->site_id, id_of('event_type'), $user->id(), '', array( 'entity' => array( 'state' => 'Pending' ) ) ))
					{					
						// We have to do a few things to trick the module into
						// recognizing a new event
						$this->request['event_id'] = $event_id;
						$this->init_event();
						$this->_ok_to_show[$event_id] = true;
					}
				}
			}*/
			
			if ($user && $event = new entity($event_id))
			{
				reason_include_once('classes/admin/admin_page.php');
				reason_include_once('classes/admin/modules/editor.php');
				$site = $event->get_owner();
				
				// Create a new admin page object and set some values to make it
				// think it's running in the admin context.
				$admin_page = new AdminPage();
				$admin_page->id = $event->id();
				$admin_page->site_id = $site->id();
				$admin_page->type_id = id_of('event_type');
				$admin_page->user_id = $user->id(); 
				$admin_page->request = array();
				$admin_page->head_items =& $this->get_head_items();
	
				// Create a new editor with the admin page. This will pick the 
				// appropriate editor class and set up the disco form.
				$this->edit_handler = new EditorModule( $admin_page );
				$this->edit_handler->head_items =& $this->get_head_items();
				$this->edit_handler->init();
				
				$this->edit_handler->disco_item->actions = array('finish' => 'Save');
				$this->edit_handler->disco_item->add_callback(array($this,'editor_where_to'),'where_to');
			}
		}
		if(!empty($this->params['wrapper_id']))
			$this->div_id = $this->params['wrapper_id'];
		
		// get_run_output() should be the very last thing done before the end of init()
		// This is done to allow the markup classes to add head items
		if($this->has_content())
		{
			$this->get_run_output();
		}
	}

	/**
	 * Get a markup object
	 *
	 * @param string $type 'item', 'list', 'list_chrome', 'list_item', 'item_admin'
	 * @return object
	 */
	function get_markup_object($type)
	{
		if(isset($this->_markups[$type]))
			return $this->_markups[$type];
		
		if(isset($this->params[$type.'_markup']))
		{
			if(!empty($this->params[$type.'_markup']))
			{
				$path = $this->params[$type.'_markup'];
			}
			else
			{
				$var = 'default_'.$type.'_markup';
				$path = $this->$var;
			}
			if(reason_file_exists($path))
			{
				reason_include_once($path);
				if(!empty($GLOBALS['events_markup'][$path]))
				{
					if(class_exists($GLOBALS['events_markup'][$path]))
					{
						$markup = new $GLOBALS['events_markup'][$path];
						if('item' == $type)
						{
							if($markup instanceof eventsItemMarkup)
								$this->_markups[$type] = $markup;
							else
								trigger_error('Markup does not implement eventsItemMarkup interface');
						}
						elseif('item_admin' == $type)
						{
							if($markup instanceof eventsItemAdminMarkup)
								$this->_markups[$type] = $markup;
							else
								trigger_error('Markup does not implement eventsItemAdminMarkup interface');
						}
						elseif('list' == $type)
						{
							if($markup instanceof eventsListMarkup)
								$this->_markups[$type] = $markup;
							else
								trigger_error('Markup does not implement eventsListMarkup interface');
						}
						elseif('list_item' == $type)
						{
							if($markup instanceof eventsListItemMarkup)
								$this->_markups[$type] = $markup;
							else
								trigger_error('Markup does not implement eventsListItemMarkup interface');
						}
						elseif('list_chrome' == $type)
						{
							if($markup instanceof eventsListChromeMarkup)
								$this->_markups[$type] = $markup;
							else
								trigger_error('Markup does not implement eventsListChromeMarkup interface');
						}
						else
						{
							trigger_error('Unknown markup type');
						}
					}
					else
					{
						trigger_error('No class with name '.$GLOBALS['events_markup'][$path].' found');
					}
				}
				else
				{
					trigger_error('Events markup not properly registered at '.$path);
				}
			}
			else
			{
				trigger_error('No markup file exists at '.$path);
			}
		}
		else
		{
			trigger_error('Unrecognized markup type ('.$type.')');
		}
		if(!isset($this->_markups[$type]))
			$this->_markups[$type] = false;
		return $this->_markups[$type];
	}

	/**
	 * Replace the default destination of the event editing form with our custom
	 * destination.
	 * @return string url
	 */
	function editor_where_to()
	{
		return carl_make_redirect(array('inline_edit'=>''));	
	}
	
	
	/**
	 * Get the set of cleanup rules for user input
	 * @return array
	 */
	function get_cleanup_rules()
	{
		if (!isset($this->calendar)) $this->calendar = new reasonCalendar;
		$views = $this->calendar->get_views();
		$formats = array('ical', 'json');

		return array(
			'audience' => array(
				'function' => 'turn_into_int',
			),
			'view' => array(
				'function' => 'check_against_array',
				'extra_args' => $views,
			),
			'start_date' => array(
				'function' => 'turn_into_date',
				'method'=>'get',
			),
			'date' => array(
				'function' => 'turn_into_date',
				'method'=>'get',
			),
			'category' => array(
				'function' => 'turn_into_int'
			),
			'event_id' => array(
				'function' => 'turn_into_int'
			),
			'end_date' => array(
				'function'=>'turn_into_date',
				'method'=>'get',
			),
			'nav_date' => array(
				'function'=>'turn_into_date'
			),
			'textonly' => array(
				'function'=>'turn_into_int'
			),
			'start_month' => array(
				'function'=>'turn_into_int'
			),
			'start_day' => array(
				'function'=>'turn_into_int'
			),
			'start_year' => array(
				'function'=>'turn_into_int'
			),
			'search' => array(
				'function'=>'turn_into_string'
			),
			'format' => array(
				'function'=>'check_against_array',
				'extra_args'=>$formats,
			),
			'no_search' => array(
				'function'=>'turn_into_int',
			),
			'admin_view' => array(
				'function' => 'check_against_array',
				'extra_args' => array('true'),
			),
			'delete_registrant' => array(
				'function' => 'turn_into_string',
			),
			'bust_cache' => array(
				'function'=>'turn_into_int',
			),
		);
	}
	
	/**
	 * Redirect to well-constructed start_date variable when presented with the separate 
	 * start_year/start_day/start_month fields in the date-choosing form.
	 * @return void
	 */
	function handle_jump()
	{
		if(!empty($this->request['start_year']))
		{
			$year = $this->request['start_year'];
			$day = 1;
			$month = 1;
			if(!empty($this->request['start_day']))
				$day = $this->request['start_day'];
			if(!empty($this->request['start_month']))
				$month = $this->request['start_month'];
			$year = str_pad($year,4,'0',STR_PAD_LEFT);
			$day = str_pad($day,2,'0',STR_PAD_LEFT);
			$month = str_pad($month,2,'0',STR_PAD_LEFT);
			$full_date = $year.'-'.$month.'-'.$day;
			$query_string = unhtmlentities($this->construct_link(array('start_date'=>$full_date)));
			$url_array = parse_url(get_current_url());
			$link = $url_array['scheme'].'://'.$url_array['host'].$url_array['path'].$query_string;
			header('Location: '.$link);
			die();
		}
	}

	/**
	 * Make sure the input from userland is sanitized
	 * @return void
	 */
	function validate_inputs()
	{
		if (!isset($this->calendar)) $this->calendar = new reasonCalendar;
		$views = $this->calendar->get_views();
		
		if(!empty($this->request['start_date']))
			$this->request['start_date'] = prettify_mysql_datetime($this->request['start_date'], 'Y-m-d');
			
		if(!empty($this->request['end_date']))
			$this->request['end_date'] = prettify_mysql_datetime($this->request['end_date'], 'Y-m-d');
			
		if(!empty($this->request['date']))
			$this->request['date'] = prettify_mysql_datetime($this->request['date'], 'Y-m-d');
			
		if(!empty($this->request['category']))
		{
			$e = new entity($this->request['category']);
			if(!($e->get_values() && $e->get_value('type') == id_of('category_type')))
			{
				unset($this->request['category']);
			}
		}
		if(!empty($this->request['audience']))
		{
			$e = new entity($this->request['audience']);
			if(!($e->get_values() && $e->get_value('type') == id_of('audience_type')))
			{
				unset($this->request['audience']);
			}
		}
	}
	
	/**
	 * Set up the parts of the request that can be passed around in links (i.e. state variables)
	 *
	 * @return void
	 */
	function register_passables()
	{
		foreach($this->request as $key => $value)
		{
			if(in_array($key,$this->passables))
				$this->pass_vars[$key] = $value;
		}
	}
	
	/**
	 * Does this module have content to display?
	 * @return boolean
	 */
	function has_content()
	{
		return true;
	}
	/**
	 * Get the current calendar object
	 *
	 * @return mixed object or NULL
	 */
	function get_current_calendar()
	{
		return $this->calendar;
	}
	/**
	 * Get the current value for today
	 *
	 * @return mixed string (mysql date) or NULL
	 */
	function get_today()
	{
		return $this->today;
	}
	/**
	 * Run the module
	 *
	 * @return void
	 */
	function run()
	{
		echo $this->get_run_output();	
	}
	
	/**
	 * Get the output for the run phase
	 *
	 * This method internally caches the output, so it can be called multiple times
	 * during page generation
	 *
	 * @return string
	 */
	function get_run_output()
	{
		if(!isset($this->run_output_string))
		{
			ob_start();
			echo '<div id="'.$this->div_id.'">'."\n";
			if (empty($this->request['event_id']))
			{
				$this->list_events();
			}
			else
				$this->show_event();
			echo '</div>'."\n";
			$this->run_output_string = ob_get_contents();
			ob_end_clean();
		}
		return $this->run_output_string;
	}
			
					
	/**
	 * Get the length of time that the module's cache of events should persist
	 *
	 * @return integer number of seconds
	 */
	protected function get_cache_lifespan()
	{
		if($this->should_bust_caches())
		{
			return 1;
		}
		return $this->params['cache_lifespan'];
	}
	/**
	 * Get the length of time that the module's cache of metadata (nav/options information) should persist
	 *
	 * @return integer number of seconds
	 */
	protected function get_cache_lifespan_meta()
	{
		
		if($this->should_bust_caches())
		{
			return 1;
		}
		if($this->params['cache_lifespan_meta'])
			return $this->params['cache_lifespan_meta'];
		return $this->get_cache_lifespan();
	}
	/**
	 * Should caches be rebuilt?
	 * @return boolean
	 */
	protected function should_bust_caches()
	{
		if(isset($this->request['bust_cache']) && $this->request['bust_cache'] && $this->user_has_editing_rights_to_current_site())
		{
			return true;
		}
		return false;
	}
	
	//////////////////////////////////////
	// For The Events Listing
	//////////////////////////////////////
	
	/**
	 * Set up the events list
	 *
	 * @return void
	 */
	function init_list()
	{
		$this->today = date('Y-m-d');
		$this->tomorrow = date('Y-m-d',strtotime($this->today.' +1 day'));
		$this->yesterday = date('Y-m-d',strtotime($this->today.' -1 day'));
		
		if(!empty($this->request['format']) && $this->request['format'] == 'ical')
		{
			$this->init_and_run_ical_calendar();
		}
		if(!empty($this->request['format']) && $this->request['format'] == 'json')
		{
			$this->init_and_run_json_calendar();
		}
		else
		{
			$this->init_html_calendar();
		}
		
	}
	/**
	 * Returns an array of id => reason audience entities to limit to based on the limit_to_audiences parameter
	 * 
	 * If given a faulty audience unique name, ignore it -- still limit to other audiences.
	 * Error triggered if not passed in a string, or if the audiences passed in aren't Reason unique
	 * names.
	 * @return array of id => audience entities, or an empty array
	 */
	function _get_audiences_to_limit_to()
	{
		$all_audiences = array();
		if(!empty($this->params['limit_to_audiences']))
		{
			if(gettype($this->params['limit_to_audiences']) != 'string')
			{
				trigger_error('The limit_to_audiences parameter must be a comma-seperated string of reason unique names.
 					Please check your syntax. Example: \'public_audience, students_audience\'. ');
				return $all_audiences;
			}
			$audiences_to_limit_to = explode(',', $this->params['limit_to_audiences']);
			foreach($audiences_to_limit_to as $audience)
			{
				$audience = trim($audience);
				if(reason_unique_name_exists($audience))
				{
					$audience_id = id_of($audience);
					$all_audiences[$audience_id] = new entity($audience_id);
				}
				else
					trigger_error('Strings passed in must be Reason unique names. \'' . $audience . '\' is 
									not a Reason unique name. Use: \'public_audience\' for example');
			}
		}
		return $all_audiences;
	}
	/**
	 * Returns an array of id => reason audience entities to exclude based on the exclude_audiences parameter.
	 *
	 * If given a faulty audience unique name, ignore it -- still exclude others
	 * Error triggered if not passed in a string, or if the audiences passed in aren't Reason unique
	 * names.
	 * @return array of id => audience entities, or an empty array
	 */
	function _get_audiences_to_exclude()
	{
		$excluded_audiences = array();
		if(!empty($this->params['exclude_audiences']))
		{
			if(gettype($this->params['exclude_audiences']) != "string")
			{
				trigger_error('The exluded_audiences parameter must be a comma-seperated string of reason unique names. Please 
								check your syntax. Example: \'public_audience, students_audience\'. ');
				return $excluded_audiences;
			}
			$audiences_to_exclude = explode(',', $this->params['exclude_audiences']);
			foreach($audiences_to_exclude as $audience)
			{
				$audience = trim($audience);
				if(reason_unique_name_exists($audience))
				{
					$audience_id = id_of($audience);
					$excluded_audiences[$audience_id] = new entity($audience_id);
				}
				else
				{
					trigger_error('Strings passed in must be Reason unique names. \'' . $audience . '\' is 
									not a Reason unique name. Use: \'public_audience\' for example');
				}
			}
		}
		return $excluded_audiences;
	}
	/**
	 * Get the set of sites that the module should be querying for events
	 *
	 * @return array site entities
	 */
	function _get_sites()
	{
		if(empty($this->params['additional_sites']))
		{
			return new entity($this->site_id);
		}
		if(!empty($this->event_sites))
		{
			return $this->event_sites;
		}
		$sites = $this->_get_sites_from_string($this->params['additional_sites']);
		if(!empty($this->params['exclude_sites']))
		{
			$excluded = $this->_get_sites_from_string($this->params['exclude_sites']);
			foreach($excluded as $site)
			{
				if(isset($sites[$site->id()]))
					unset($sites[$site->id()]);
			}
		}
		$sites[$this->site_id] = new entity($this->site_id);
		$this->event_sites = $sites;
		return $this->event_sites;
	}
	function _get_sites_from_string($string)
	{
		$parser = new stringToSites();
		$site = new entity($this->site_id);
		$parser->set_context_site($site);
		$sites = $parser->get_sites_from_string($string);
		return $sites;
	}
	/**
	 * Get the parent sites for the current site
	 *
	 * @param integer $site_id the id of the current site
	 * return array site entities
	 */
	function _get_parent_sites($site_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_right_relationship( $site_id, relationship_id_of( 'parent_site' ) );
		
		$site = new entity($site_id);
		if($site->get_value('site_state') == 'Live')
		{
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
		}
		else
		{
			$es->limit_tables();
			$es->limit_fields();
		}
		$es->set_cache_lifespan($this->get_cache_lifespan_meta());
		return $es->run_one();
	}
	/**
	 * Get the child sites for the current site
	 *
	 * @param integer $site_id the id of the current site
	 * return array site entities
	 */
	function _get_child_sites($site_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_left_relationship( $site_id, relationship_id_of( 'parent_site' ) );
		
		$site = new entity($site_id);
		if($site->get_value('site_state') == 'Live')
		{
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
		}
		else
		{
			$es->limit_tables();
			$es->limit_fields();
		}
		$es->set_cache_lifespan($this->get_cache_lifespan_meta());
		return $es->run_one();
	}
	/**
	 * Returns an array of site entities keyed by site id
	 *
	 * If a site unique name is given, this function will return a single-member array of that site
	 *
	 * If a site type unique name is given, this function will return all the sites that are of that site type.
	 * If the context site is live, only live sites will be returned.
	 *
	 * @param string $unique_name the unique anem of a site or a site type entity
	 * @param integer $context_site_id the id of the context site
	 * @access private
	 */
	function _get_sites_by_unique_name($unique_name, $context_site_id)
	{
		$return = array();
		if($id = id_of($unique_name))
		{
			$entity = new entity($id);
		
			switch($entity->get_value('type'))
			{
				case id_of('site'):
					$return[$id] = $entity;
					break;
				case id_of('site_type_type'):
					$es = new entity_selector();
					$es->add_type(id_of('site'));
					$es->add_left_relationship( $id, relationship_id_of( 'site_to_site_type' ) );
					$context_site = new entity($context_site_id);
					if($context_site->get_value('site_state') == 'Live')
					{
						$es->limit_tables('site');
						$es->limit_fields('site_state');
						$es->add_relation('site_state="Live"');
					}
					else
					{
						$es->limit_tables();
						$es->limit_fields();
					}
					$es->set_cache_lifespan($this->get_cache_lifespan_meta());
					$return = $es->run_one();
					break;
				default:
					trigger_error('Unique name "'.$unique_name.'" passed to events module in additional_sites parameter does not correspond to a Reason site or site type. Not included in sites shown.');
			}
		}
		else
		{
			trigger_error($unique_name.' is not a unique name of any Reason entity. Site(s) will not be included.');
		}
		return $return;
	}
	
	/**
	 * Get the sites that share events
	 *
	 * This module will return non-live sites if the current site is non-live.
	 *
	 * @param integer $site_id the id of the current site
	 * return array site entities
	 */
	function _get_sharing_sites($site_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_left_relationship( id_of('event_type'), relationship_id_of('site_shares_type'));
		
		$site = new entity($site_id);
		if($site->get_value('site_state') == 'Live')
		{
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
		}
		else
		{
			$es->limit_tables();
			$es->limit_fields();
		}
		$es->set_cache_lifespan($this->get_cache_lifespan_meta());
		
		return $es->run_one();
	}
	/**
	 * Get the starting date of the current view
	 *
	 * @return string date
	 */
	function _get_start_date()
	{
		if(!empty($this->request['start_date']))
			return $this->request['start_date'];
		
		if(!empty($this->params['start_date']))
			return $this->params['start_date'];

		return $this->today;
	}
	/**
	 * Set up and produce ical ouput
	 *
	 * @return void
	 */
	function init_and_run_ical_calendar()
	{
		$init_array = $this->make_reason_calendar_init_array($this->_get_start_date(), '', 'all');
		
		$this->calendar = $this->_get_runned_calendar($init_array);
		
		$events = $this->calendar->get_all_events();
		
		$this->export_ical($events);
	}
	/**
	 * Set up and produce json ouput
	 *
	 * @return void
	 */
	function init_and_run_json_calendar()
	{
		$init_array = $this->make_reason_calendar_init_array($this->_get_start_date(), '', 'all');

		$this->calendar = $this->_get_runned_calendar($init_array);

		$events = $this->calendar->get_all_events();

		$this->export_json($events);
	}
	/**
	 * Do the set up required for the standard html output
	 *
	 * @return void
	 */
	function init_html_calendar()
	{
		$start_date = '';
		$end_date = '';
		$view = '';
		
		if( empty($this->request['no_search']) && empty($this->request['textonly']) && ( !empty($this->request['start_date']) || !empty($this->request['end_date']) || !empty($this->request['view']) || !empty($this->request['audience']) || !empty($this->request['view']) || !empty($this->request['nav_date']) || !empty($this->request['category']) ) && $head_items =& $this->get_head_items() )
		{
			$head_items->add_head_item('meta', array('name'=>'robots','content'=>'noindex,follow'));
		}
		
		if(!empty($this->pass_vars['end_date']))
			$end_date = $this->pass_vars['end_date'];
		else
		{
			if(!empty($this->pass_vars['view']))
				$view = $this->pass_vars['view'];
			elseif(!empty($this->params['view']))
				$view = $this->params['view'];
		}
		$init_array = $this->make_reason_calendar_init_array($this->_get_start_date(), $end_date, $view);
		
		$this->calendar = $this->_get_runned_calendar($init_array);
	}
	
	/**
	 * Set up and run a calendar with a given initialization array
	 * @param array $init_array
	 * @return object reason calendar
	 */
	function _get_runned_calendar($init_array)
	{
		$calendar = new reasonCalendar($init_array);
		$calendar->run();
		return $calendar;
	}
	/**
	 * Get the view options section markup
	 * @return string
	 */
	function get_section_markup_view_options()
	{
		ob_start();
		$this->show_view_options();
		return ob_get_clean();
	}
	/**
	 * Get the navigation section markup
	 * @return string
	 */
	function get_section_markup_navigation()
	{
		ob_start();
		$this->show_navigation();
		return ob_get_clean();
	}
	/**
	 * Get the focus section markup
	 * @return string
	 */
	function get_section_markup_focus()
	{
		ob_start();
		$this->show_focus();
		return ob_get_clean();
	}
	
	function get_section_markup_notice()
	{
		$ret = '';
		
		if( $cal = $this->get_current_calendar() )
		{
			if( $cal->limit_reached() )
			{
				$ret .= '<div class="notice"><h3>Not Showing All Events For This Time Period</h3><p>This calendar can only show ' . number_format( $cal->get_limit() ) . ' events at a time. Because the currently selected view contains more events than that, there are some events that are not shown below. Please try choosing a shorter timespan (e.g. day/week/month).</p></div>';
			}
		}
		return $ret;
	}
	/**
	 * Get the ical links section markup
	 * @return string
	 */
	function get_section_markup_ical_links()
	{
		ob_start();
		$this->show_list_export_links();
		return ob_get_clean();
	}
	/**
	 * Get the rss links section markup
	 * @return string
	 */
	function get_section_markup_rss_links()
	{
		ob_start();
		$this->show_feed_link();
		return ob_get_clean();
	}
	/**
	 * Get the list title section markup
	 * @return string
	 */
	 function get_section_markup_list_title()
	{
		ob_start();
		$this->display_list_title();
		return ob_get_clean();
	}
	/**
	 * Get the calendar grid section markup
	 * @return string
	 */
	function get_section_markup_calendar_grid()
	{
		ob_start();
		$this->show_calendar_grid();
		return ob_get_clean();
	}
	/**
	 * Get the date picker section markup
	 * @return string
	 */
	function get_section_markup_date_picker()
	{
		ob_start();
		$this->show_date_picker();
		return ob_get_clean();
	}
	/**
	 * Get the search section markup
	 * @return string
	 */
	function get_section_markup_search()
	{
		ob_start();
		$this->show_search();
		return ob_get_clean();
	}
	/**
	 * Get the options section markup
	 * @return string
	 */
	function get_section_markup_options()
	{
		ob_start();
		$this->show_options_bar();
		return ob_get_clean();
	}
	/**
	 * Display the entire events list markup
	 * @return void
	 */
	function list_events()
	{
		$this->_no_events_message = '';
		if($this->calendar->contains_any_events())
		{
			$this->events_by_date = $this->calendar->get_all_days();
			if($this->rerun_if_empty && empty($this->pass_vars) && empty($this->events_by_date))
			{
				$this->rerun_calendar();
				$this->events_by_date = $this->calendar->get_all_days();
				if(count(current($this->events_by_date)) > 1)
				{
					$this->_no_events_message = '<p>This calendar has no events coming up. Here are the last events available:</p>'."\n";
				}
				else
				{
					$this->_no_events_message = '<p>This calendar has no events coming up. Here is the last event available:</p>'."\n";
				}
				
			}
			$this->events = $this->calendar->get_all_events();
			if($markup = $this->get_markup_object('list_chrome'))
			{
				$bundle = new functionBundle();
				$bundle->set_function('calendar', array($this, 'get_current_calendar'));
				$bundle->set_function('construct_link', array($this, 'construct_link'));
				$bundle->set_function('view_options_markup', array($this, 'get_section_markup_view_options'));
				$bundle->set_function('calendar_grid_markup', array($this, 'get_section_markup_calendar_grid'));
				$bundle->set_function('search_markup', array($this, 'get_section_markup_search'));
				$bundle->set_function('options_markup', array($this, 'get_section_markup_options'));
				$bundle->set_function('navigation_markup', array($this, 'get_section_markup_navigation'));
				$bundle->set_function('focus_markup', array($this, 'get_section_markup_focus'));
				$bundle->set_function('notice_markup', array($this, 'get_section_markup_notice'));
				$bundle->set_function('list_title_markup', array($this, 'get_section_markup_list_title'));
				$bundle->set_function('ical_links_markup', array($this, 'get_section_markup_ical_links'));
				$bundle->set_function('rss_links_markup', array($this, 'get_section_markup_rss_links'));
				$bundle->set_function('list_markup', array($this, 'get_events_list_markup'));
				$bundle->set_function('date_picker_markup', array($this, 'get_section_markup_date_picker'));
				$bundle->set_function('options_markup', array($this, 'get_section_markup_options'));
				$bundle->set_function('full_calendar_link_markup', array($this, 'get_full_calendar_link_markup'));
				$bundle->set_function('prettify_duration', array($this, 'prettify_duration') );
				$bundle->set_function('events_page_url', array($this, 'get_events_page_url') );
				$bundle->set_function('current_page', array($this, 'get_current_page') );
				// get_full_calendar_link_markup()
				$this->modify_list_chrome_function_bundle($bundle);
				/* if($markup->needs_markup('list'))
					$markup->set_markup('list', $this->get_events_list_markup($msg)); */
				$markup->set_bundle($bundle);
				if($head_items = $this->get_head_items())	
					$markup->modify_head_items($head_items);
				echo $markup->get_markup();
			}
		}
	}
	/**
	 * Add additional functions to the list chrome function bundle
	 *
	 * This is for classes that extend the events module to add additional functionality for the markup class
	 *
	 * @param object $bundle
	 * @return void
	 */
	function modify_list_chrome_function_bundle($bundle)
	{
		// for overloading
	}
	/**
	 * Get the markup for just the events list (not including display chrome)
	 * @param string $ongoing_display 'above', 'below', or 'inline'
	 * @return string
	 */
	function get_events_list_markup($ongoing_display = 'above')
	{
		ob_start();
		if(!empty($this->events_by_date))
		{
			echo $this->_no_events_message;
			/* if($this->calendar->get_start_date() < $this->today && empty($this->request['search']))
			{
				echo '<p>Viewing archive. <a href="'.$this->construct_link(array('start_date'=>'')).'">Reset calendar to today</a></p>';
			} */
			echo '<div id="events">'."\n";
			if(($list_markup = $this->get_markup_object('list')) && ($item_markup = $this->get_markup_object('list_item')))
			{
				$item_bundle = new functionBundle();
				$item_bundle->set_function('event_link', array($this, 'get_event_link') );
				$item_bundle->set_function('get_ticket_info_from_form', array($this, 'get_ticket_info_from_form') );
				$item_bundle->set_function('teaser_image', array($this, 'get_teaser_image_html') );
				$item_bundle->set_function('media_works', array($this, 'get_event_media_works'));
				$item_bundle->set_function('prettify_duration', array($this, 'prettify_duration') );
				$this->modify_list_item_function_bundle($item_bundle);
				$item_markup->set_bundle($item_bundle);
				if($head_items = $this->get_head_items())	
					$item_markup->modify_head_items($head_items);
				
				$list_bundle = new functionBundle();
				$list_bundle->set_function('list_item_markup', array($item_markup,'get_markup') );
				$list_bundle->set_function('events', array($this, 'get_integrated_events_array') );
				$list_bundle->set_function('calendar', array($this, 'get_current_calendar') );
				$list_bundle->set_function('today', array($this, 'get_today') );
				$this->modify_list_function_bundle($list_bundle);
				$list_markup->set_bundle($list_bundle);
				if($head_items = $this->get_head_items())	
					$list_markup->modify_head_items($head_items);
				echo $list_markup->get_markup();
			}
			echo '</div>'."\n";
		}
		else
		{
			$this->no_events_error();
		}
		return ob_get_clean();
	}
	/**
	 * Add additional functions to the list item function bundle
	 *
	 * This is for classes that extend the events module to add additional functionality for the markup class
	 *
	 * @param object $bundle
	 * @return void
	 */
	function modify_list_item_function_bundle($bundle)
	{
		// for overloading
	}
	/**
	 * Add additional functions to the list function bundle
	 *
	 * This is for classes that extend the events module to add additional functionality for the markup class
	 *
	 * @param object $bundle
	 * @return void
	 */
	function modify_list_function_bundle($bundle)
	{
		// for overloading
	}
	/**
	 * Get a single events array that contains dates, times, event occurrences, and event entities
	 *
	 * format:
	 * 
	 * array(
	 *		'ongoing' => array(
	 *			'all_day' => array(
	 *				'1234' => $event_object,
	 *				'2345' => $event_object,
	 *			),
	 *		),
	 *		'2014-10-05 => array(
	 *			'all_day' => array(
	 *				'3456' => $event_object,
	 *				'4567' => $event_object,
	 *			),
	 *			'09:00:00' => array(
	 *				'5678' => $event_object,
	 *			),
	 *			'15:30:00' => array(
	 *				'6789' => $event_object,
	 *				'7890' => $event_object,
	 *			),
	 *			...
	 *		),
	 *		'2014-10-07' => array(
	 *			...
	 *		),
	 *		...
	 *	),
	 *
	 * This function also sweetens the event entities with the following "magic" values:
	 *
	 * '_inline_editable' (boolean)
	 * '_inline_editable_link' (string, url-escaped)
	 * '_ongoing_through' (string, mysql date formatted)
	 * '_ongoing_through_formatted' (string, html formatted, english description of ending date relative to starting date)
	 * '_ongoing_starts' (string, mysql date formatted)
	 * '_ongoing_ends' (string, mysql date formatted)
	 *
	 * @param string $ongoing_display what method of ongoing display is desired? 'inline', 'above' or 'below'
	 * @return array
	 */
	function get_integrated_events_array($ongoing_display)
	{
		if(empty($ongoing_display) || !in_array($ongoing_display, array('inline','above','below')))
		{
			trigger_error('Parameter indicating how ongoing events should be displayed required. Pass "inline", "above", or "below".');
			return array();
		}
		$integrated = array();
		$editable = false;
		if(empty($this->events_page_url)) // We're on an actual events page, not an events_mini feed
		{
			$inline_edit = get_reason_inline_editing($this->page_id);
			$editable = $inline_edit->available_for_module($this);
			$activation_params = $inline_edit->get_activation_params($this);
		}
		if( $ongoing_display != 'inline' && $ongoing = $this->get_ongoing_events($ongoing_display) )
		{
			foreach($ongoing as $event)
			{
				$event->set_value('_inline_editable', false);
				$event->set_value('_inline_editable_link', '');
				if ($editable && $this->user_can_inline_edit_event($event->id()))
				{
					$event->set_value('_inline_editable', true);
					$params = $activation_params;
					$params['edit_id'] = $params['event_id'] = $event->id();
					$event->set_value('_inline_editable_link', carl_make_link($params));
				}
				if(!$event->has_value('_ongoing_through'))
					$event->set_value('_ongoing_through', $event->get_value('end_date') );
				if(!$event->has_value('_ongoing_through_formatted'))
					$event->set_value('_ongoing_through_formatted', $this->_get_formatted_end_date($event));
			}
			// data structure supports future possibility of ongoing events not just being all day events
			$integrated['ongoing'] = array('all_day' => $ongoing);
		}
		foreach($this->events_by_date as $day => $val)
		{
			if ( $this->calendar->get_end_date() && $day > $this->calendar->get_end_date() )
				break;
			foreach ($this->events_by_date[$day] as $event_id)
			{
				if(empty($this->events[$event_id]))
					continue;
				$event = $this->events[$event_id];
				
				$ongoing_type = $this->get_event_ongoing_type_for_day($event_id,$day,$ongoing_display);
				if( 'middle' == $ongoing_type )
					continue;
				$event->set_value('_inline_editable', false);
				$event->set_value('_inline_editable_link', '');
				if ($editable && $this->user_can_inline_edit_event($event->id()))
				{
					$event->set_value('_inline_editable', true);
					$params = $activation_params;
					$params['edit_id'] = $params['event_id'] = $event_id;
					$event->set_value('_inline_editable_link', carl_make_link($params));
				}
				
				if(!$event->has_value('_ongoing_starts'))
					$event->set_value('_ongoing_starts', '');
				if(!$event->has_value('_ongoing_ends'))
					$event->set_value('_ongoing_ends', '');
				
				if($ongoing_type)
				{
					if(!$event->has_value('_ongoing_through'))
						$event->set_value('_ongoing_through', $event->get_value('end_date') );
					if(!$event->has_value('_ongoing_through_formatted'))
						$event->set_value('_ongoing_through_formatted', $this->_get_formatted_end_date($event));
				
					switch($ongoing_type)
					{
						case 'starts':
							$event->set_value('_ongoing_starts', $day);
							break;
						case 'ends':
							$event->set_value('_ongoing_ends', $day);
							break;
					}
				}
				
				if(!isset($integrated[$day]))
					$integrated[$day] = array();
				$time = substr($event->get_value( 'datetime' ), 11);
				if('00:00:00' == $time)
					$time = 'all_day';
				
				if(!isset($integrated[$day][$time]))
					$integrated[$day][$time] = array();
				$integrated[$day][$time][] = $event;
			}
		}
		return $integrated;
	}
	/**
	 * Get an HTML-formatted URL for an event
	 *
	 * @param mixed $event_id Integer event ID or event object
	 * @param string $day Mysql date, e.g. YYYY-MM-DD
	 * @return string
	 */
	function get_event_link($event_id, $day = '')
	{
		$id = (is_object($event_id)) ? $event_id->id() : $event_id;
		$base = $this->events_page_url;
		
		if ($this->params['link_shared_events_to_parent_site'])
		{
			$event = (is_object($event_id)) ? $event_id : new entity($event_id);
			if ($owner = $this->get_owner_site_info($event))
				$base = $owner->get_value('_link');
		}
		
		return $base.$this->construct_link(array('event_id'=>$id,'date'=>$day));
	}
	
	/**
	 * Get the ids of all ongoing events in the calendar
	 *
	 * @param string $ongoing_display 'inline', 'above', or 'below' -- to determine which events
	 *               should be considered ongoing
	 * @return array event ids
	 */
	function get_ongoing_event_ids($ongoing_display = '')
	{
		if(empty($ongoing_display))
		{
			if($markup = $this->get_markup_object('list'))
				$ongoing_display = $markup->get_ongoing_display_type();
			else
				$ongoing_display = 'above';
		}
		
		$ongoing_ids = array();
		foreach($this->events_by_date as $day => $val)
		{
			if ( $this->calendar->get_end_date() && $day > $this->calendar->get_end_date() )
				break;
			$ongoing_ids = array_merge($ongoing_ids,$val);
		}
		$ongoing_ids = array_unique($ongoing_ids);
		if('above' == $ongoing_display)
		{
			foreach($ongoing_ids as $k => $id)
			{
				if(!$this->event_is_ongoing($this->events[$id]) || $this->events[$id]->get_value('datetime') >= $this->calendar->get_start_date())
					unset($ongoing_ids[$k]);
			}
		}
		elseif('below' == $ongoing_display)
		{
			foreach($ongoing_ids as $k => $id)
			{
				if(!$this->event_is_ongoing($this->events[$id]) || $this->events[$id]->get_value('datetime') >= $this->calendar->get_start_date() || $this->events[$id]->get_value('last_occurence') <= $this->calendar->get_end_date())
					unset($ongoing_ids[$k]);
			}
		}
		else
		{
			trigger_error('Unrecognized string passed to get_ongoing_event_ids(): '.$ongoing_display.'. Should be "above" or "below".');
		}
		return $ongoing_ids;
	}
	/**
	 * Get the ongoing events as event entities rather than IDs
	 *
	 * @params string $ongoing_display
	 * @return array
	 */
	function get_ongoing_events($ongoing_display = '')
	{
		$ids = $this->get_ongoing_event_ids($ongoing_display);
		$events = array();
		foreach($ids as $id)
		{
			if(isset($this->events[$id]))
				$events[] = $this->events[$id];
		}
		return $events;
	}
	
	/**
	 * Output the focus portion of the list chrome
	 *
	 * @todo move into a markup class
	 * @return void
	 */
	function show_focus()
	{
		if(!empty($this->request['search']) || !empty($this->request['category']) ||  !empty($this->request['audience']) )
		{
			echo '<div class="focus">'."\n";
			if(!empty($this->request['category']) ||  !empty($this->request['audience']) || !empty($this->request['search']))
			{
				$this->show_focus_description();
			}
			echo '</div>'."\n";
		}	
	}
	
	/**
	 * Output the description of the current focus
	 *
	 * @todo move into a markup class, with show_focus()
	 * @return void
	 */
	function show_focus_description()
	{
		$out = '';
		$needs_intro = true;
		$cat_str = $this->get_category_focus_description();
		if(!empty($cat_str))
		{
			$out .= $cat_str;
			$needs_intro = false;
		}
		$aud_str = $this->get_audience_focus_description($needs_intro);
		if(!empty($aud_str))
		{
			$out .= $aud_str;
			$needs_intro = false;
		}
		$search_str = $this->get_search_focus_description($needs_intro);
		if(!empty($search_str))
		{
			$out .= $search_str;
		}
		
		if(!empty($out))
		{
			echo '<h3>Currently Browsing:</h3>';
			echo '<ul>'.$out.'</ul>'."\n";
		}
	}
	
	/**
	 * Output the description of the current category focus
	 *
	 * @todo move into a markup class, with show_focus()
	 * @return void
	 */
	function get_category_focus_description()
	{
		$ret = '';
		if(!empty($this->request['category']))
		{
			$e = new entity($this->request['category']);
			$name = strip_tags($e->get_value('name'));
			$ret .= '<li class="categories first">';
			$ret .= '<h4>Events in category: '.$name.'</h4>'."\n";
			$ret .= '<a href="'.$this->construct_link(array('category'=>'','view'=>'')).'" class="clear">See all categories (clear <em>&quot;'.$name.'&quot;</em>)</a>';
			$ret .= '</li>';
		}
		return $ret;
	}
	
	/**
	 * Output the description of the current audience focus
	 *
	 * @todo move into a markup class, with show_focus()
	 * @return void
	 */
	function get_audience_focus_description($needs_intro = false)
	{
		$ret = '';
		if(!empty($this->request['audience']))
		{
			$e = new entity($this->request['audience']);
			$ret .= '<li class="audiences';
			if(empty($this->request['category']))
				$ret .= ' first';
			$ret .= '"><h4>';
			if($needs_intro)
				$ret .= 'Events ';
			$name = strip_tags($e->get_value('name'));
			$ret .= 'for '.$name.'</h4>'."\n";
			$ret .= '<a href="'.$this->construct_link(array('audience'=>'','view'=>'')).'" class="clear">See events for all groups (clear <em>&quot;'.$name.'&quot;</em>)</a>';
			$ret .= '</li>';
		}
		return $ret;
	}
	
	/**
	 * Output the description of the current search focus
	 *
	 * @todo move into a markup class, with show_focus()
	 * @return void
	 */
	function get_search_focus_description($needs_intro = false)
	{
		$ret = '';
		if(!empty($this->request['search']))
		{
			$ret .= '<li class="search';
			if(empty($this->request['category']) && empty($this->request['audience']))
				$ret .= ' first';
			$ret .= '">';
			$ret .= '<h4><label for="calendar_search_above">';
			if($needs_intro)
				$ret .= 'Events ';
			$ret .= 'containing</label></h4> ';
			$ret .= $this->get_search_form('calendar_search_above',true);
			$ret .= $this->get_search_other_actions();
			$ret .= '</li>';
		}
		return $ret;
	}
	
	/**
	 * Re-run the calendar (if there are no items to show in the standard view)
	 * @return void
	 */
	function rerun_calendar()
	{
		//trigger_error('get_max_date called');
		$init_array = $this->make_reason_calendar_init_array($this->calendar->get_max_date(),'','all' );
		$this->calendar = $this->_get_runned_calendar($init_array);
	}
	
	/**
	 * Display a title for use in the list chrome
	 * 
	 * The base events class does not display a title; this is for overloading by modules like events_mini, which do
	 *
	 * @return void
	 * @todo move into markup class
	 */
	function display_list_title()
	{
	}
	
	/**
	 * Display an error message if there are no events in the current view
	 * @return void
	 * @todo move into markup class
	 */
	function no_events_error()
	{
		echo '<div class="newEventsError">'."\n";
		$start_date = $this->calendar->get_start_date();
		$audiences = $this->calendar->get_audiences();
		$categories = $this->calendar->get_categories();
		$min_date = $this->calendar->get_min_date();
		if($this->calendar->get_view() == 'all' && empty($categories) && empty( $audiences ) && empty($this->request['search']) )
		{
			//trigger_error('get_max_date called');
			$max_date = $this->calendar->get_max_date();
			if(empty($max_date))
			{
				echo '<p>This calendar does not have any events.</p>'."\n";
			}
			else
			{
				echo '<p>There are no future events in this calendar.</p>'."\n";
				echo '<ul>'."\n";
				echo '<li><a href="'.$this->construct_link(array('start_date'=>$max_date, 'view'=>'all','category'=>'','audience'=>'','search'=>'')).'">View most recent event</a></li>'."\n";
				if($start_date > '1970-01-01')
				{
					echo '<li><a href="'.$this->construct_link(array('start_date'=>$min_date, 'view'=>'all','category'=>'','audience'=>'','search'=>'')).'">View entire event archive</a></li>'."\n";
				}
				echo '</ul>'."\n";
			}
		}
		else
		{
			if(empty($categories) && empty($audiences) && empty($this->request['search']))
			{
				$desc = $this->get_scope_description();
				if(!empty($desc))
				{
					echo '<p>There are no events '.$this->get_scope_description().'.</p>'."\n";
				if($start_date > '1970-01-01')
				{
					echo '<ul><li><a href="'.$this->construct_link(array('start_date'=>'1970-01-01', 'view'=>'all')).'">View entire event archive</a></li></ul>'."\n";
				}
				}
				else
				{
					echo '<p>There are no events available.</p>'."\n";
				}
			}
			else
			{
				echo '<p>There are no events available';
				$clears = '<ul>'."\n";
				if(!empty($audiences))
				{
					$audience = current($audiences);
					echo ' for '.strtolower($audience->get_value('name'));
					$clears .= '<li><a href="'.$this->construct_link(array('audience'=>'')).'">Clear group/audience</a></li>'."\n";
				}
				if(!empty($categories))
				{
					$cat = current($categories);
					echo ' in the '.$cat->get_value('name').' category';
					$clears .= '<li><a href="'.$this->construct_link(array('category'=>'')).'">Clear category</a></li>'."\n";
				}
				if(!empty($this->request['search']))
				{
					echo ' that match your search for "'.htmlspecialchars($this->request['search']).'"';
					$clears .= '<li><a href="'.$this->construct_link(array('search'=>'')).'">Clear search</a></li>'."\n";
				}
				$clears .= '</ul>'."\n";
				echo $clears;
				if($this->calendar->get_start_date() > $this->today)
				{
					echo '<p><a href="'.$this->construct_link(array('start_date'=>'', 'view'=>'','category'=>'','audience'=>'', 'end_date'=>'','search'=>'')).'">Reset calendar to today</a></p>';
				}
				if($start_date > '1970-01-01')
				{
					echo '<p><a href="'.$this->construct_link(array('start_date'=>'1970-01-01', 'view'=>'all')).'">View entire event archive</a></p>'."\n";
				}
			}
		}
		echo '</div>'."\n";
	}
	/**
	 * Get an English description of the current timeframe being viewed
	 * @return void
	 */
	function get_scope_description()
	{
		$scope = $this->get_scope('through','F');
		if(!empty($scope))
		{
			if($this->calendar->get_start_date() == $this->calendar->get_end_date() )
			{
				if($this->calendar->get_view() == 'all')
					return 'on or after '.$this->get_scope('through','F');
				else
				{
					return 'on '.$this->get_scope('through','F');
				}
			}
			else
			{
				return 'between '.$this->get_scope('and','F');
			}
		}
		return '';
	}
	
	/**
	 * Can the current user inline edit on this site?
	 *
	 * @return boolean;
	 */
	function user_can_inline_edit()
	{
		if (!isset($this->_user_can_inline_edit))
		{
			$this->_user_can_inline_edit = $this->user_has_editing_rights_to_current_site();
		}
		return $this->_user_can_inline_edit;
	}
	/**
	 * Does the current user have editing rights to the current site?
	 * @return boolean
	 */
	function user_has_editing_rights_to_current_site()
	{
		if (!isset($this->_user_has_editing_rights_to_current_site))
		{
			$this->_user_has_editing_rights_to_current_site = reason_check_access_to_site($this->site_id);
		}
		return $this->_user_has_editing_rights_to_current_site;
	}

	/**
	 * Can the current user inline edit a particular event?
	 *
	 * @param integer $event_id
	 * @return boolean;
	 */
	function user_can_inline_edit_event($event_id)
	{
		if ($this->event && $event_id == $this->event->id())
			$owner_site = $this->event->get_owner();
		elseif (isset($this->events[$event_id]))
			$owner_site = $this->events[$event_id]->get_owner();
		else
			return false;
			
		if (!isset($this->_user_can_inline_edit_sites[$owner_site->id()]))
		{
			$this->_user_can_inline_edit_sites[$owner_site->id()] = reason_check_access_to_site($owner_site->id());
		}
		return $this->_user_can_inline_edit_sites[$owner_site->id()];
	}
	
	/**
	 * Get link for the borrow this interface
	 *
	 * @param integer $event_id
	 * @return string;
	 */
	function cur_user_is_reason_editor()
	{
		if($username = reason_check_authentication())
		{
			return username_is_a_reason_editor($username);
		}
		return false;
	}

	/**
	 * Get link for the borrow this interface
	 *
	 * @param integer $event_id
	 * @return string;
	 */
	function get_borrow_this_link($event_id)
	{
		if(BorrowThis::item_borrowable_by_username($event_id))
		{
			return BorrowThis::link($event_id);
		}
		return '';
	}
	
	/**
	 * Display the view options part of the list chrome
	 * @return void
	 * @todo move into markup class
	 */
	function show_view_options()
	{
		if(!empty($this->show_views))
		{
			trigger_error('show_views class variable is deprecated. Specify a event list chrome markup class that does not show the view options instead.');
			if(!$this->show_views)
				return '';
		}
		if(empty($this->view_markup))
		{
			$this->view_markup = $this->get_view_options();
		}
		echo $this->view_markup;
	}
	
	/**
	 * For a given event and a given day, should
	 * the event be displayed as starting, ending, "ongoing", or not at all?
	 *
	 * @param integer $event_id
	 * @param string $day YYY-MM-DD
	 * @return string Values: 'starts', 'ends', 'middle', or ''
	 */
	function get_event_ongoing_type_for_day($event_id,$day,$ongoing_display = '')
	{
		if('' === $ongoing_display)
		{
			if($markup = $this->get_markup_object('list'))
				$ongoing_display = $markup->get_ongoing_display_type();
			else
				$ongoing_display = 'above';
		}
		if($ongoing_display != 'inline' && $this->event_is_ongoing($this->events[$event_id]))
		{
			if(substr($this->events[$event_id]->get_value( 'datetime' ), 0,10) == $day)
			{
				return 'starts';
			}
			elseif($this->events[$event_id]->get_value( 'last_occurence' ) == $day)
			{
				return 'ends';
			}
			else
			{
				return 'middle';
			}
		}
		return '';
	}
	
	/**
	 * Get a nicely formatted duration of an event for humans
	 * @param object $event event
	 * @return string
	 */
	public function prettify_duration($event)
	{
		$duration = '';
		if ($event->get_value( 'hours' ))
		{
			if ( $event->get_value( 'hours' ) > 1 )
				$hour_word = 'hours';
			else
				$hour_word = 'hour';
			$duration .= $event->get_value( 'hours' ).' '.$hour_word;
			if ($event->get_value( 'minutes' ))
				$duration .= ', ';
		}
		if ($event->get_value( 'minutes' ))
		{
			$duration .= $event->get_value( 'minutes' ).' minutes';
		}
		return $duration;
	}
	
	public function get_repetition_explanation($event)
	{
		$ret = '';
		$rpt = $event->get_value('recurrence');
		$freq = '';
		$words = array();
		$dates_text = '';
		$occurence_days = array();
		if (!($rpt == 'none' || empty($rpt)))
		{
			$words = array('daily'=>array('singular'=>'day','plural'=>'days'),
							'weekly'=>array('singular'=>'week','plural'=>'weeks'),
							'monthly'=>array('singular'=>'month','plural'=>'months'),
							'yearly'=>array('singular'=>'year','plural'=>'years'),
					);
			if ($event->get_value('frequency') <= 1)
				$sp = 'singular';
			else
			{
				$sp = 'plural';
				$freq = $event->get_value('frequency').' ';
			}
			if ($rpt == 'weekly')
			{
				$days_of_week = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
				foreach($days_of_week as $day)
				{
					if($event->get_value($day))
						$occurence_days[] = $day;
				}
				$last_day = array_pop($occurence_days);
				$dates_text = ' on ';
				if (!empty( $occurence_days ) )
				{
					$comma = '';
					if(count($occurence_days) > 2)
						$comma = ',';
					$dates_text .= ucwords(implode(', ', $occurence_days)).$comma.' and ';
				}
				$dates_text .= prettify_string($last_day);
			}
			elseif ($rpt == 'monthly')
			{
				$suffix = array(1=>'st',2=>'nd',3=>'rd',4=>'th',5=>'th');
				if ($event->get_value('week_of_month'))
				{
					$dates_text = ' on the '.$event->get_value('week_of_month');
					$dates_text .= $suffix[$event->get_value('week_of_month')];
					$dates_text .= ' '.$event->get_value('month_day_of_week');
				}
				else
					$dates_text = ' on the '.prettify_mysql_datetime($event->get_value('datetime'), 'jS').' day of the month';
			}
			elseif ($rpt == 'yearly')
			{
				$dates_text = ' on '.prettify_mysql_datetime($event->get_value('datetime'), 'F jS');
			}
			$ret .= 'This event takes place each ';
			$ret .= $freq;
			$ret .= $words[$rpt][$sp];
			$ret .= $dates_text;
			$ret .= ' from '.prettify_mysql_datetime($event->get_value('datetime'), 'F jS, Y').' to '.prettify_mysql_datetime($event->get_value('last_occurence'), 'F jS, Y').'.';
		}
		return $ret;
	}
	
	/**
	 * Format a date to be shown in a "through [formatted date]" phrase
	 *
	 * @param object $event entity
	 * @return string formatted date
	 */
	function _get_formatted_end_date($event)
	{
		$full_month = prettify_mysql_datetime($event->get_value('last_occurence'),'F');
		$month = prettify_mysql_datetime($event->get_value('last_occurence'),'M');
		
		$ret = $month;
		if($full_month != $month)
			$ret .= '.';
		$ret .= ' ';
		$ret .= prettify_mysql_datetime($event->get_value('last_occurence'),'j');
		
		$start_year = max(substr($event->get_value('datetime'),0,4), substr($this->calendar->get_start_date(),0,4));
		
		if($start_year != substr($event->get_value('last_occurence'),0,4))
			$ret .= ', '.substr($event->get_value('last_occurence'),0,4);
		
		return $ret;
	}
	/**
	 * Get a teaser image for a given event
	 * @param mixed $event_id Event ID or Event object
	 * @param string $link
	 * @return string html
	 * @todo move into a markup class
	 */
	function get_teaser_image_html($event_id, $link = '')
	{
		if(empty($this->params['show_images']))
			return '';
		
		if(is_object($event_id))
			$event_id = $event_id->id();
		
		static $image_cache = array();
		if(!array_key_exists($event_id, $image_cache))
		{
			$es = new entity_selector();
        	$es->description = 'Selecting images for event';
        	$es->add_type( id_of('image') );
        	$es->add_right_relationship( $event_id, relationship_id_of('event_to_image') );
        	$es->add_rel_sort_field($event_id, relationship_id_of('event_to_image'));
        	$es->set_order('rel_sort_order ASC');
        	$es->set_num(1);
        	$es->set_env( 'site' , $this->site_id );
        	$images = $es->run_one();
        	if(!empty($images))
        	{
        		$image_cache[$event_id] = current($images);
        	}
        	elseif($image = $this->_get_list_thumbnail_default_image())
        	{
        		$image_cache[$event_id] = $image;
        	}
        	else
        	{
        		$image_cache[$event_id] = NULL;
        	}
        }

        if(!empty($image_cache[$event_id]))
        {
        	if($this->params['list_thumbnail_width'] || $this->params['list_thumbnail_height'])
        	{
        		$rsi = new reasonSizedImage;
        		$rsi->set_id($image_cache[$event_id]->id());
        		if(0 != $this->params['list_thumbnail_height']) $rsi->set_height($this->params['list_thumbnail_height']);
				if(0 != $this->params['list_thumbnail_width']) $rsi->set_width($this->params['list_thumbnail_width']);
				if('' != $this->params['list_thumbnail_crop']) $rsi->set_crop_style($this->params['list_thumbnail_crop']);
				return get_show_image_html( $rsi, true, false, false, '', $this->textonly, false, $link );
        	}
        	else
        	{
        		return get_show_image_html( $image_cache[$event_id], true, false, false, '', $this->textonly, false, $link );
        	}
        }
	}
	/**
	 * Get the default thumbnail image to use if there is none attached to an event
	 * @return mixed image entity object or boolean false
	 */
	protected function _get_list_thumbnail_default_image()
	{
		if(!isset($this->_list_thumbnail_default_image))
		{
			if(!empty($this->params['list_thumbnail_default_image']))
			{
				$image_id = id_of($this->params['list_thumbnail_default_image']);
				if(!empty($image_id))
				{
					$this->_list_thumbnail_default_image = new entity($image_id);
				}
				else
				{
					trigger_error('list_thumbnail_default_image must be a unique name');
				}
			}
			if(empty($this->_list_thumbnail_default_image))
				$this->_list_thumbnail_default_image = false;
		}
		return $this->_list_thumbnail_default_image;
	}
	/**
	 * Display the navigation portion of the list chrome (i.e. next & previous links)
	 * 
	 * @return void
	 * @todo move into markup class
	 */
	function show_navigation()
	{
		if(isset($this->show_navigation))
		{
			trigger_error('show_navigation class variable deprecated. Specify an events list chrome markup class instead.');
			if(!$this->show_navigation)
				return;
		}
		echo '<div class="nav">'."\n";
		if(empty($this->next_and_previous_links))
			$this->generate_next_and_previous_links();
		echo $this->next_and_previous_links;
		echo '</div>'."\n";
	}
	/**
	 * Display the options bar portion of the list chrome
	 *
	 * @return void
	 * @todo move into markup class
	 */
	function show_options_bar() // {{{
	{
		if(isset($this->show_options))
		{
			trigger_error('show_options class variable deprecated -- specify an events_list_chrome markup class instead');
			if(!$this->show_options)
				return;
		}
			
		if(empty($this->options_bar))
			$this->generate_options_bar();
		echo $this->options_bar;
	} // }}}
	
	/**
	 * Generate the options bar
	 *
	 * This method populates $this->options_bar
	 *
	 * @return void
	 * @todo move into markup class
	 */
	function generate_options_bar()
	{
		$this->options_bar .= '<div class="options">'."\n";
		$this->options_bar .= $this->get_all_categories();
		$this->options_bar .= $this->get_audiences();
		$this->options_bar .= $this->get_today_link();
		$this->options_bar .= $this->get_archive_toggler();
		$this->options_bar .= $this->get_cache_buster_section();
		$this->options_bar .= '</div>'."\n";
	}
	/**
	 * Display the view options portion of the list chrome
	 *
	 * That is, the tabs for daily, weekly, monthly, yearly views
	 *
	 * @return void
	 * @todo move into markup class
	 */
	function get_view_options()
	{
		$ret = '';
		$ret .= "\n".'<div class="views">'."\n";
		$ret .= '<h4>View:</h4>';
		$ret .= '<ul>'."\n";
		$on_defined_view = false;
		foreach($this->calendar->get_views() as $view_name=>$view)
		{
			if($view != $this->calendar->get_view())
			{
				$link_params = array('view'=>$view,'end_date'=>'');
				if(in_array($view,$this->views_no_index))
					$link_params['no_search'] = 1;
				$opener = '<li class="'.$view.'View"><a href="'.$this->construct_link($link_params).'">';
				$closer = '</a></li>';
			}
			else
			{
				$opener = '<li class="'.$view.'View current"><strong>';
				$closer = '</strong></li>';
				$on_defined_view = true;
			}
			
			$ret .= $opener.prettify_string($view_name).$closer;
		}
		if(!$on_defined_view)
		{
			$ret .= '<li class="current"><strong>'.$this->get_scope('-').'</strong></li>'."\n";
		}
		$ret .= '</ul>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	}
	
	/**
	 * Get the markup for the categories section of the options bar
	 * 
	 * @return string
	 * @todo move into markup class
	 */
	function get_all_categories()
	{
		$ret = '';
		$cs = new entity_selector($this->parent->site_id);
		$cs->description = 'Selecting all categories on the site';
		$cs->add_type(id_of('category_type'));
		$cs->set_order('entity.name ASC');
		$cs->set_cache_lifespan($this->get_cache_lifespan_meta());
		$cats = $cs->run_one();
		$cats = $this->check_categories($cats);
		if(empty($cats))
			return '';
		
		$cat_names = array();
		foreach($cats as $cat)
			$cat_names[$cat->id()] = $cat->get_value('name');
		
		if($this->params['natural_sort_categories'])
			natcasesort($cat_names);
		
		$ret .= '<div class="categories';
		if ($this->calendar->get_view() == "all")
			$ret .= ' divider';
		$ret .= '">'."\n";
		$ret .= '<h4>Event Categories</h4>'."\n";
		$ret .= '<ul>'."\n";
		$ret .= '<li class="all">';
		$used_cats = $this->calendar->get_categories();
			if (empty( $used_cats ))
				$ret .= '<strong>All Categories</strong>';
			else
				$ret .= '<a href="'.$this->construct_link(array('category'=>'','view'=>'')).'" title="Events in all categories">All Categories</a>';
		$ret .= '</li>';
		foreach($cat_names as $cat_id=>$cat_name)
		{
			$cat = $cats[$cat_id];
			$ret .= '<li>';
			if (array_key_exists($cat->id(), $this->calendar->get_categories()))
				$ret .= '<strong>'.$cat->get_value('name').'</strong>';
			else
				$ret .= '<a href="'.$this->construct_link(array('category'=>$cat->id(),'view'=>'','no_search'=>'1')).'" title="'.reason_htmlspecialchars(strip_tags($cat->get_value('name'))).' events">'.$cat->get_value('name').'</a>';
			$ret .= '</li>';
		}
		$ret .= '</ul>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	}
	/**
	 * Given a set of categories, remove those that are not used on the current calendar
	 *
	 * @param array $cats category entities
	 * @return array $cats category entities
	 */
	function check_categories($cats)
	{
		if($this->params['limit_to_page_categories'])
		{
			$or_cats = $this->calendar->get_or_categories();
			if(!empty($or_cats))
			{
				foreach($cats as $id=>$cat)
				{
					if(!array_key_exists($id,$or_cats))
					{
						unset($cats[$id]);
					}
				}
			}
		}
		$setup_es = new entity_selector($this->parent->site_id);
		$setup_es->add_type( id_of('event_type') );
		$setup_es->set_env('site_id',$this->parent->site_id);
		$setup_es = $this->alter_categories_checker_es($setup_es);
		$setup_es->set_num(1);
		$setup_es->set_cache_lifespan($this->get_cache_lifespan_meta());
		$rel_id = relationship_id_of('event_to_event_category');
		foreach($cats as $id=>$cat)
		{
			$es = carl_clone($setup_es);
			$es->add_left_relationship( $id, $rel_id);
			$results = $es->run_one();
			if(empty($results))
			{
				unset($cats[$id]);
			}
			$results = array();
		}
		return $cats;
	}
	
	/**
	 * Add additional rules to the entity selector that checks categories.
	 *
	 * This function is intended to be overloaded by extending modules
	 *
	 * @param object $es entity selector
	 * @return object $es entity_selector
	 */
	function alter_categories_checker_es($es)
	{
		return $es;
	}
	
	/**
	 * Populate $audiences class variable
	 * @todo Remove use of $limit_to_current_site class var, which is deprecated
	 */
	function init_audiences()
	{
		if(REASON_USES_DISTRIBUTED_AUDIENCE_MODEL)
		{
			$es = new entity_selector($this->parent->site_id);
		}
		else
		{
			$es = new entity_selector();
		}
		$es->set_order('sortable.sort_order ASC');
		$es->set_cache_lifespan($this->get_cache_lifespan_meta());
		$audiences = $es->run_one(id_of('audience_type'));
		
		$event_type_id = id_of('event_type');
		$rel_id = relationship_id_of('event_to_audience');
		if($this->limit_to_current_site)
			$setup_es = new entity_selector($this->parent->site_id);
		else
			$setup_es = new entity_selector();
		$setup_es->set_num(1);
		$setup_es->add_type($event_type_id);
		$setup_es->set_cache_lifespan($this->get_cache_lifespan_meta());
		$setup_es = $this->alter_audiences_checker_es($setup_es);
		foreach($audiences as $id=>$audience)
		{
			$es = carl_clone($setup_es);
			$es->add_left_relationship($id, $rel_id);
			$auds = $es->run_one();
			if(empty($auds))
				unset($audiences[$id]);
		}
		$this->audiences = $audiences;
	}
	
	/**
	 * Add additional rules to the entity selector that checks audiences.
	 *
	 * This function is intended to be overloaded by extending modules
	 *
	 * @param object $es entity selector
	 * @return object $es entity_selector
	 */
	function alter_audiences_checker_es($es)
	{
		return $es;
	}
	/**
	 * Get the markup for the audiences section of the options bar
	 * 
	 * @return string
	 * @todo move into markup class
	 */
	function get_audiences()
	{
		$ret = '';
		$ret .= '<div class="audiences">'."\n";
		$ret .= '<h4>View Events for:</h4>'."\n";
		$ret .= '<ul>'."\n";
		$ret .= '<li class="all">';
		$this->init_audiences();
		$used_auds = $this->calendar->get_audiences();
		if (empty($used_auds))
			$ret .= '<strong>All Groups</strong>';
		else
			$ret .= '<a href="'.$this->construct_link(array('audience'=>'','view'=>'')).'" title="Events for all groups">All Groups</a>';
		$ret .= '</li>';
		foreach ($this->audiences as $id=>$audience)
		{
			$ret .= '<li>';
			if (array_key_exists($id, $used_auds))
				$ret .= '<strong>'.$audience->get_value('name').'</strong>';
			else
				$ret .= '<a href="'.$this->construct_link(array('audience'=>$id,'no_search'=>'1','view'=>'')).'" title="Events for '.strtolower($audience->get_value('name')).'">'.$audience->get_value('name').'</a>';
			$ret .= '</li>';
		}
		$ret .= '</ul>'."\n";
		
		$ret .= '</div>'."\n";
		return $ret;
	}
	/**
	 * Populate the $next_and_previous_links class variable with appropriate markup
	 * 
	 * @return void
	 * @todo move into markup class
	 */
	function generate_next_and_previous_links()
	{
		$start_array = explode('-',$this->calendar->get_start_date() );
		if ($this->calendar->get_view() != 'all')
		{
			$show_links = true;
			$prev_u = 0;
			//$end_array = explode('-',$this->calendar->get_end_date() );
			if( $this->calendar->get_view() == 'daily' )
			{
				$prev_u = get_unix_timestamp($this->calendar->get_start_date()) - 60*60*24;
				$next_u = get_unix_timestamp($this->calendar->get_start_date()) + 60*60*24;
				$word = '';
			}
			elseif($this->calendar->get_view() == 'weekly')
			{
				$prev_u = get_unix_timestamp($this->calendar->get_start_date()) - 60*60*24*7;
				$next_u = get_unix_timestamp($start_array[0].'-'.$start_array[1].'-'.str_pad($start_array[2]+7, 2, "0", STR_PAD_LEFT));
				$word = 'Week';
			}
			elseif($this->calendar->get_view() == 'monthly')
			{
				$prev_u = get_unix_timestamp($start_array[0].'-'.str_pad($start_array[1]-1, 2, "0", STR_PAD_LEFT).'-'.$start_array[2]);
				$next_u = get_unix_timestamp($start_array[0].'-'.str_pad($start_array[1]+1, 2, "0", STR_PAD_LEFT).'-'.$start_array[2]);
				$word = 'Month';
			}
			elseif($this->calendar->get_view() == 'yearly')
			{
				$prev_u = get_unix_timestamp($start_array[0]-1 .'-'.$start_array[1].'-'.$start_array[2]);
				$next_u = get_unix_timestamp($start_array[0]+1 .'-'.$start_array[1].'-'.$start_array[2]);
				$word = 'Year';
			}
			else
			{
				$show_links = false;
			}
			if($show_links)
			{
				$prev_start = date('Y-m-d', $prev_u);
				$next_start = date('Y-m-d', $next_u);
				
				$starting = '';
				if($this->calendar->get_view() != 'daily')
					$starting = ' Starting';
					
				$format_prev_year = '';
				if (date('Y', $prev_u) != date('Y'))
				{
					$format_prev_year = ', Y';
				}
				
				$format_next_year = '';
				if (date('Y', $next_u) != date('Y'))
					$format_next_year = ', Y';	
				if($this->calendar->contains_any_events_before($this->calendar->get_start_date()) )
				{
					$this->next_and_previous_links = '<a class="previous" href="';
					$link_params = array('start_date'=>$prev_start,'view'=>$this->calendar->get_view());
					if(in_array($this->calendar->get_view(),$this->views_no_index))
						$link_params['no_search'] = 1;
					$this->next_and_previous_links .= $this->construct_link($link_params);
					if(date('M', $prev_u) == 'May') // All months but may need a period after them
						$punctuation = '';
					else
						$punctuation = '.';
					$this->next_and_previous_links .= '" title="View '.$word.$starting.' '.date('M'.$punctuation.' j'.$format_prev_year, $prev_u).'">';
					$this->next_and_previous_links .= '&laquo;</a> &nbsp; ';
				}
			}
			$this->next_and_previous_links .= '<strong>'.$this->get_scope('&#8212;').'</strong>';
			if($show_links && $this->calendar->contains_any_events_after($next_start) )
			{
				$this->next_and_previous_links .= ' &nbsp; <a class="next" href="';
				$link_params = array('start_date'=>$next_start,'view'=>$this->calendar->get_view());
				if(in_array($this->calendar->get_view(),$this->views_no_index))
						$link_params['no_search'] = 1;
				$this->next_and_previous_links .= $this->construct_link($link_params);
				if(date('M', $next_u) == 'May') // All months but may need a period after them
					$punctuation = '';
				else
					$punctuation = '.';
				$this->next_and_previous_links .= '" title="View '.$word.$starting.' '.date('M'.$punctuation.' j'.$format_next_year, $next_u).'">';
				$this->next_and_previous_links .= '&raquo;</a>'."\n";
			}
		}
		else // "all" view should have a 1-month-back link
		{
			$this->next_and_previous_links = '';
			
			if($this->calendar->contains_any_events_before($this->calendar->get_start_date()) )
			{
			
				$prev_u = get_unix_timestamp($start_array[0].'-'.str_pad($start_array[1]-1, 2, "0", 	STR_PAD_LEFT).'-'.$start_array[2]);
				
				$prev_start = date('Y-m-d', $prev_u);
				
				$format_prev_year = '';
				if (date('Y', $prev_u) != date('Y'))
				{
					$format_prev_year = ', Y';
				}
				
				$this->next_and_previous_links = '<a class="previous" href="';
				$link_params = array('start_date'=>$prev_start,'view'=>'monthly');
				if(in_array($this->calendar->get_view(),$this->views_no_index))
					$link_params['no_search'] = 1;
				$this->next_and_previous_links .= $this->construct_link($link_params);
				if(date('M', $prev_u) == 'May') // All months but may need a period after them
					$punctuation = '';
				else
					$punctuation = '.';
				$this->next_and_previous_links .= '" title="View Month Starting '.date('M'.$punctuation.' j'.$format_prev_year, $prev_u).'">';
				$this->next_and_previous_links .= '&laquo;</a> &nbsp; ';
			}
			
			$this->next_and_previous_links .= '<strong>Starting '.prettify_mysql_datetime($this->calendar->get_start_date(),$this->list_date_format.', Y');
			switch($this->calendar->get_start_date())
			{
				case $this->today:
					$this->next_and_previous_links .= ' (today)';
					break;
				case $this->tomorrow:
					$this->next_and_previous_links .= ' (tomorrow)';
					break;
				case $this->yesterday:
					$this->next_and_previous_links .= ' (yesterday)';
					break;
			}
			$this->next_and_previous_links .= '</strong>';
		}
	}
	
	
	/**
	 * Get a string representing the current scope (i.e. time frame calendar is looking at)
	 *
	 * @param string $through The string to use meaning "through", e.g. "-"
	 * @param string $month_format a date() formate for months
	 * 
	 * @return string
	 */
	function get_scope($through = 'through', $month_format = 'M')
	{
		$scope = '';
		$format_start_year = '';
		$format_end_year = '';
		if ((prettify_mysql_datetime($this->calendar->get_start_date(), 'Y') != prettify_mysql_datetime($this->calendar->get_end_date(), 'Y'))
			|| ($this->calendar->get_view() == 'daily' && (prettify_mysql_datetime($this->calendar->get_start_date(), 'Y') != date('Y'))))
			$format_start_year = ', Y';
		
		if($month_format != 'M' || prettify_mysql_datetime($this->calendar->get_start_date(), 'M') == 'May') // All months but may need a period after them if month format is "M"
			$punctuation = '';
		else
			$punctuation = '.';
		$scope .= prettify_mysql_datetime($this->calendar->get_start_date(), $month_format.$punctuation.' j'.$format_start_year);
		if($this->calendar->get_start_date() == $this->today)
			$scope .= ' (Today)';
		if($this->calendar->get_view() != 'daily' && $this->calendar->get_start_date() != $this->calendar->get_end_date())
		{
			if ((prettify_mysql_datetime($this->calendar->get_start_date(), 'Y') != prettify_mysql_datetime($this->calendar->get_end_date(), 'Y')) || (prettify_mysql_datetime($this->calendar->get_end_date(), 'Y') != date('Y')))
				$format_end_year = ', Y';
			if($month_format != 'M' || prettify_mysql_datetime($this->calendar->get_end_date(), 'M') == 'May') // All months but may need a period after them
				$punctuation = '';
			else
				$punctuation = '.';
			$scope .= ' '.$through.' '.prettify_mysql_datetime($this->calendar->get_end_date(), $month_format.$punctuation.' j'.$format_end_year);
			if($this->calendar->get_end_date() == $this->today)
				$scope .= ' (Today)';
		}
		return $scope;
	}
	
	/**
	 * Get markup for toggling between archive view and standard view
	 *
	 * @return string html
	 * @todo move into a markup class
	 */
	function get_archive_toggler()
	{
		$ret = '';
		if($this->calendar->get_start_date() >= $this->today)
		{
			$new_start = date('Y-m-d', strtotime($this->calendar->get_start_date().' -1 month') );
			$ret .= '<div class="archive"><a href="'.$this->construct_link(array('start_date'=>$new_start, 'view'=>'monthly') ).'">View Archived Events</a></div>';
		}
		elseif($this->calendar->contains_any_events_after($this->yesterday))
			$ret .= '<div class="archive"><a href="'.$this->construct_link(array('start_date'=>$this->today, 'view'=>'')).'">View Upcoming Events</a></div>';
		return $ret;
	}
	
	/**
	 * Get markup for linking back to today's events
	 *
	 * @return string html
	 * @todo move into a markup class
	 */
	function get_today_link()
	{
		if($this->calendar->get_start_date() > $this->today && $this->calendar->contains_any_events_after($this->yesterday))
		return '<div class="today"><a href="'.$this->construct_link(array('start_date'=>$this->today)).'">Today\'s Events</a></div>'."\n";
	}
	/**
	 * Display the calendar grid markup
	 *
	 * @return void
	 * @todo Move to a markup class
	 */
	function show_calendar_grid()
	{
		if(isset($this->show_calendar_grid))
		{
			trigger_error('show_calendar_grid is a deprecated class variable. Specify an events list chrome markup class that does not show the calendar grid instead.');
			if(!$this->show_calendar_grid)
				return;
		}
		if(empty($this->calendar_grid_markup))
		{
			$this->generate_calendar_grid_markup();
		}
		echo $this->calendar_grid_markup;
	}
	/**
	 * Generate the calendar grid markup and assign to $this->calendar_grid_markup
	 *
	 * @return void
	 * @todo Move to a markup class
	 */
	function generate_calendar_grid_markup()
	{
		$grid = new calendar_grid();
		$start_day_on_cal = false;
		if(!empty($this->request['nav_date']))
		{
			$nav_date = $this->request['nav_date'];
			if(substr($nav_date,0,7) == substr($this->calendar->get_start_date(),0,7) )
				$start_day_on_cal = true;
		}
		else
		{
			$nav_date = $this->calendar->get_start_date();
			$start_day_on_cal = true;
		}
		$date_parts = explode('-',$nav_date);
		$grid->set_year($date_parts[0]);
		$grid->set_month($date_parts[1]);
		if($start_day_on_cal)
		{
			$grid->set_day($date_parts[2]);
		}
		$grid->set_linked_dates($this->get_calendar_grid_links($date_parts[0], $date_parts[1]) );
		
		if($this->calendar->contains_any_events_before($date_parts[0].'-'.$date_parts[1].'-01'))
		{
			$prev_u = get_unix_timestamp($date_parts[0].'-'.str_pad($date_parts[1]-1, 2, "0", STR_PAD_LEFT).'-'.$date_parts[2]);
			$prev_date = carl_date('Y-m-d',$prev_u);
			$grid->set_previous_month_query_string($this->construct_link(array('nav_date'=>$prev_date,'no_search'=>'1' ) ) );
		}
		if($this->calendar->contains_any_events_after($date_parts[0].'-'.$date_parts[1].'-31'))
		{
			$next_u = get_unix_timestamp($date_parts[0].'-'.str_pad($date_parts[1]+1, 2, "0", STR_PAD_LEFT).'-'.$date_parts[2]);
			$next_date = carl_date('Y-m-d',$next_u);
			$grid->set_next_month_query_string($this->construct_link(array('nav_date'=>$next_date,'no_search'=>'1' ) ) );
		}
		
		$nav_month = substr($nav_date,0,7);
		
		$start_month = substr($this->calendar->get_start_date(),0,7);
		$start_day = intval(substr($this->calendar->get_start_date(),8,2));
		
		$end_month = substr($this->calendar->get_end_date(),0,7);
		$end_day = intval(substr($this->calendar->get_end_date(),8,2));
		
		if(!($start_month > $nav_month || $end_month < $nav_month))
		{
			if($start_month == $nav_month)
			{
				$first_day_in_view = $start_day;
				$grid->add_class_to_dates('startDate', array($start_day));
			}
			else
			{
				$first_day_in_view = 1;
			}
			if($end_month == $nav_month)
			{
				$last_day_in_view = $end_day;
			}
			else
			{
				$last_day_in_view = 31;
			}
			
			$viewing_days = array();
			for($i = $first_day_in_view; $i <= $last_day_in_view; $i++)
			{
				$viewing_days[] = $i;
			}
			$grid->add_class_to_dates('currentlyViewing', $viewing_days);
			
		}
		$days_with_events = $this->get_days_with_events($date_parts[0], $date_parts[1]);
		if(!empty($days_with_events))
		{
			$grid->add_class_to_dates('hasEvent', array_keys($days_with_events));
		}
		$this->calendar_grid_markup = $grid->get_calendar_markup();
	}
	/**
	 * Get the calendar grid links for a given month
	 *
	 * @param string $year e.g. 2014
	 * @param string $month e.g. 07
	 * @return array('YYYY-MM-DD' => 'link-to-view', 'YYYY-MM-DD' => 'linko-to-view', ...)
	 */
	function get_calendar_grid_links($year, $month)
	{
		$links = array();
		$weeks = get_calendar_data_for_month( $year, $month );
		if(!empty($this->request['view']) && $this->request['view'] != 'all')
			$pass_view_val = $this->request['view'];
		else
			$pass_view_val = '';
		foreach($weeks as $week)
		{
			foreach($week as $day)
			{
				$date = $year.'-'.$month.'-'.str_pad($day,2,'0',STR_PAD_LEFT);
				$links[$day] =  $this->construct_link(array('start_date'=>$date,'view'=>$pass_view_val,'no_search'=>'1'));
			}
		}
		return $links;
	}
	/**
	 * Get a list of dates with event counts
	 *
	 * @param string $year e.g. 2014
	 * @param string $month e.g. 07
	 * @return array('YYYY-MM-DD' => 3, 'YYYY-MM-DD' => 1, ...)
	 */
	function get_days_with_events($year, $month)
	{
		$first_day_in_month = $year.'-'.$month.'-01';
		$init_array = $this->make_reason_calendar_init_array($first_day_in_month, '', 'monthly');
		$cal = $this->_get_runned_calendar($init_array);
		$days = $cal->get_all_days();
		$counts = array();
		foreach($days as $day=>$ids)
		{
			$num = count($ids);
			if($num > 0)
			{
				$counts[intval(substr($day,8,2))] = $num;
			}
		}
		return $counts;
	}
	/**
	 * Display a date-picking interface
	 *
	 * @return void
	 * @todo Move to a markup class
	 */
	function show_date_picker()
	{
		$start = $this->calendar->get_start_date();
		$cur_month = substr($start,5,2);
		$cur_day = substr($start,8,2);
		$cur_year = substr($start,0,4);
		/* $min = $this->calendar->get_min_date();
		$min_year = substr($min,0,4); */
		$min_year = $this->get_min_year();
		/* $max = $this->calendar->get_max_date();
		substr($max,0,4); */
		$max_year = $this->get_max_year();
		echo '<div class="dateJump">'."\n";
		echo '<form action="'.$this->construct_link().'" method="post">'."\n";
		echo '<h4>Jump to date:</h4>';
		echo '<span style="white-space:nowrap;">'."\n";
		echo '<label for="eventDateJumpMonthSelect" class="hide">Month</label>';
		echo '<select name="start_month" id="eventDateJumpMonthSelect">'."\n";
		for($m = 1; $m <= 12; $m++)
		{
			$m_padded = str_pad($m,2,'0',STR_PAD_LEFT);
			 $month_name = prettify_mysql_datetime('1970-'.$m_padded.'-01','M');
			 echo '<option value="'.$m_padded.'"';
			 if($m_padded == $cur_month)
			 	echo ' selected="selected"';
			 echo '>'.$month_name.'</option>'."\n";
		}
		echo '</select>'."\n";
		echo '<label for="eventDateJumpDaySelect" class="hide">Day</label>';
		echo '<select name="start_day" id="eventDateJumpDaySelect">'."\n";
		for($d = 1; $d <= 31; $d++)
		{
			 echo '<option value="'.$d.'"';
			 if($d == $cur_day)
			 	echo ' selected="selected"';
			 echo '>'.$d.'</option>'."\n";
		}
		echo '</select>'."\n";
		echo '<label for="eventDateJumpYearSelect" class="hide">Year</label>';
		echo '<select name="start_year" id="eventDateJumpYearSelect">'."\n";
		for($y = $min_year; $y <= $max_year; $y++)
		{
			 echo '<option value="'.$y.'"';
			 if($y == $cur_year)
			 	echo ' selected="selected"';
			 echo '>'.$y.'</option>'."\n";
		}
		echo '</select>'."\n";
		echo '</span>'."\n";
		if(!empty($this->request['view']))
			echo '<input type="hidden" name="view" value="" />'."\n";
		echo '<input type="submit" name="go" value="go" />'."\n";
		echo '</form>'."\n";
		echo '</div>'."\n";
	}
	/**
	 * Get the last year in the calendar
	 * @return integer year
	 */
	function get_max_year()
	{
		if(!empty($this->max_year))
			return $this->max_year;
		//$year = substr($this->calendar->get_start_date(),0,4);
		$year = carl_date('Y');
		$max_year = NULL;
		$max_found_so_far = $year;
		for($i=2; $i < 64; $i = $i*2)
		{
			//echo ($year+$i.'<br />');
			if($this->calendar->contains_any_events_after($year+$i.'-01-01'))
			{
				$max_found_so_far = $year+$i;
				continue;
			}
			else
			{
				$max_year = $this->refine_get_max_year($year+$i, $max_found_so_far);
				break;
			}
		}
		if(empty($max_year))
			$max_year = $year + $i;
		$this->max_year = $max_year;
		return $max_year;
	}
	/**
	 * Get the exact last year in the calendar, given two bounding years we know are inside and outside the bounds of the calendar
	 * @param integer $year_outside_bounds
	 * @param integer $year_inside_bounds
	 * @param integer $depth
	 * @return integer year
	 */
	function refine_get_max_year($year_outside_bounds, $year_inside_bounds, $depth = 1)
	{
		if($depth > 4)
			return $year_outside_bounds;
		$median_year = floor(($year_outside_bounds + $year_inside_bounds)/2);
		//echo $median_year;
		if($median_year == $year_inside_bounds)
			return $year_inside_bounds;
		if($this->calendar->contains_any_events_after($median_year.'-01-01'))
		{
			return $this->refine_get_max_year($year_outside_bounds, $median_year, $depth++);
		}
		else
		{
			return $this->refine_get_max_year($median_year, $year_inside_bounds, $depth++);
		}
		
	}
	/**
	 * Get earliest year that has events in the calendar
	 * @return integer year
	 */
	function get_min_year()
	{
		if(!empty($this->min_year))
		{
			return $this->min_year;
		}
		$year = carl_date('Y');
		//echo 'start: '.$year.'<br />';
		$min_year = NULL;
		$min_found_so_far = $year;
		for( $i=2; $i < 65; $i = $i*2 )
		{
			//echo 'testing: '. ( $year - $i ) .'<br />';
			if($this->calendar->contains_any_events_before(($year-$i).'-01-01'))
			{
				$min_found_so_far = $year - $i;
				continue;
			}
			else
			{
				$min_year = $this->refine_get_min_year($year-$i, $min_found_so_far);
				break;
			}
		}
		if(empty($min_year))
			$min_year = $year - $i;
		$this->min_year = $min_year;
		return $min_year;
	}
	/**
	 * Get the exact earliest year in the calendar, given two bounding years we know are inside and outside the bounds of the calendar
	 * @param integer $year_outside_bounds
	 * @param integer $year_inside_bounds
	 * @param integer $depth
	 * @return integer year
	 */
	function refine_get_min_year($year_outside_bounds, $year_inside_bounds, $depth = 1)
	{
		if($depth > 4)
			return $year_outside_bounds;
		$median_year = ceil(($year_outside_bounds + $year_inside_bounds)/2);
		//echo $median_year;
		if($median_year == $year_inside_bounds)
			return $year_outside_bounds;
		if($this->calendar->contains_any_events_before($median_year.'-01-01'))
		{
			return $this->refine_get_min_year($year_outside_bounds, $median_year, $depth++);
		}
		else
		{
			return $this->refine_get_min_year($median_year, $year_inside_bounds, $depth++);
		}
		
	}
	/**
	 * Get the html for a link to bust the cache (if one exists)
	 * @return string HTML
	 */
	function get_cache_buster_section()
	{
		$ret = '';
		if((!empty($this->params['cache_lifespan']) || !empty($this->params['cache_lifespan_meta'])) && $this->user_has_editing_rights_to_current_site())
		{
			$ret .= '<div class="cacheBuster"><a href="?bust_cache=1">Refresh event data</a></div>';
		}
		return $ret;
	}
	/**
	 * Display the search interface
	 *
	 * @return void
	 * @todo move to a markup class
	 */
	function show_search()
	{
		echo '<div class="search">'."\n";
		echo '<h4><label for="calendar_search">Search Events:</label></h4>'."\n";
		echo $this->get_search_form();
		echo $this->get_search_other_actions();
		echo '</div>'."\n";
	}
	/**
	 * Generate the markup for the search form
	 *
	 * @param string $input_id the HTML id for the input field
	 * @param boolean $use_val_for_width If true, set the width of the element to be the same as the string length as the string searched
	 * @return string markup
	 * @todo use mb_strlen()
	 * @todo move to a markup class
	 */
	function get_search_form($input_id = 'calendar_search',$use_val_for_width = false)
	{
		$ret = '';
		$ret .= '<form action="?" method="get">'."\n";
		$width = 10;
		if(!empty($this->request['search']))
		{
			$val = htmlspecialchars($this->request['search']);
			if($use_val_for_width)
			{
				$width = ceil(strlen($this->request['search'])*0.8);
				if($width > 10)
				{
					$width = 10;
				}
			}
		}
		else
			$val = '';
		$ret .= '<input type="text" name="search" class="search" id="'.$input_id.'" value="'.$val.'" size="'.$width.'" />'."\n";
		$ret .= '<input type="submit" name="go" value="go" />'."\n";
		foreach($this->passables as $passable)
		{
			if(!empty($this->request[$passable]) && !in_array($passable,array('search','view','end_date') ) )
				$ret .= '<input type="hidden" name="'.$passable.'" value="'.htmlspecialchars($this->request[$passable]).'" />'."\n";
		}
		$ret .= '</form>'."\n";
		return $ret;
	}
	/**
	 * Generate the markup for clearing or time-shifting the search results
	 * @return string markup
	 */
	function get_search_other_actions()
	{
		$ret = '';
		if(!empty($this->request['search']))
		{
			$ret .= '<div class="otherActions">'."\n";
			$ret .= '<span class="clear"><a href="'.$this->construct_link(array('search'=>'','view'=>'')).'">Clear search</a></span> | '."\n";
			if($this->calendar->get_start_date() > $this->get_min_year().'-01-01')
			{
				$ret .= '<span class="toArchive"><a href="'.$this->construct_link(array('start_date'=>$this->get_min_year().'-01-01','view'=>'')).'">Search archived events for <em class="searchTerm">"'.htmlspecialchars($this->request['search']).'"</em></a></span>'."\n";
			}
			else
			{
				$ret .= '<span class="toCurrent"><a href="'.$this->construct_link(array('start_date'=>$this->today,'view'=>'')).'">Search upcoming events for <em class="searchTerm">"'.htmlspecialchars($this->request['search']).'"</em></a></span>'."\n";
			}
			$ret .= '</div>'."\n";
		}
		return $ret;
	}
	/**
	 * What is the sharing mode of the module?
	 * @return string 'all' or 'shared_only'
	 */
	function _get_sharing_mode()
	{
		return $this->params['sharing_mode'];
	}
	/**
	 * Set up an initalization array for a reason calendar object
	 *
	 * @param string $start_date mysql date
	 * @param string $end_date mysql date
	 * @param string $view
	 * @return array
	 */
	function make_reason_calendar_init_array($start_date, $end_date = '', $view = '')
	{
		$init_array = array();
		$init_array['context_site'] = $this->parent->site_info;
		$init_array['site'] = $this->_get_sites();
		$init_array['sharing_mode'] = $this->_get_sharing_mode();
		if(!empty($start_date))
			$init_array['start_date'] = $start_date;
		if(!empty($end_date))
		{
			$init_array['end_date'] = $end_date;
		}
		elseif(!empty($view))
		{
			$init_array['view'] = $view;
		}
		if(!empty($this->pass_vars['audience']))
		{
			$audience = new entity($this->pass_vars['audience']);
			$init_array['audiences'] = array( $audience->id()=>$audience );
		}
		if(!empty($this->pass_vars['category']))
		{
			$category = new entity($this->pass_vars['category']);
			$init_array['categories'] = array( $category->id()=>$category );
		}
		if($this->params['limit_to_page_categories'])
		{
			$es = new entity_selector( $this->parent->site_id );
			$es->description = 'Selecting categories for this page';
			$es->add_type( id_of('category_type') );
			$es->set_env('site',$this->parent->site_id);
			$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_category') );
			$es->set_cache_lifespan($this->get_cache_lifespan_meta());
			$cats = $es->run_one();
			if(!empty($cats))
			{
				$init_array['or_categories'] = $cats;
			}
		}		
		// Figure out what entities of the specified types the page may have in common with events
		if($this->params['limit_by_related_types'])
		{
			foreach ($this->params['limit_by_related_types'] as $type => $rels)
			{
				if (!$type_id = id_of($type))
				{
					trigger_error('Invalid type name "'.$type.' in limit_by_related_types page type parameter.');
					continue;
				}
				
				$related = array();
				$relations = get_allowable_relationships_for_type($type_id);
				
				if (!isset($rels['page_rel']) || !is_array($rels['page_rel']))
				{
					trigger_error('limit_by_related_types page type parameter incorrectly formed: missing page_rel.');
					continue;
				}
				foreach ($rels['page_rel'] as $rel_name)
				{
					if (!$rel_id = relationship_id_of($rel_name))
					{
						trigger_error('Invalid relationship name "'.$rel_name.' in limit_by_related_types page type parameter.');
						continue;
					}
					
					// Find all the entities of the requested types that are associated with 
					// the current page.
					$entities = $this->parent->cur_page->get_relationship($rel_name);
					foreach ($entities as $entity)
					{
						$related[$type][] = $entity->id();
					}
				}

				if (!empty($related))
				{
					if (!isset($rels['entity_rel']) || !is_array($rels['entity_rel']))
					{
						trigger_error('limit_by_related_types page type parameter incorrectly formed: missing entity_rel.');
						continue;
					}
								
					foreach ($rels['entity_rel'] as $rel_name)
					{
						if (!$rel_id = relationship_id_of($rel_name))
						{
							trigger_error('Invalid relationship name "'.$rel_name.' in limit_by_related_types page type parameter.');
							continue;
						}

						$init_array['rels'][] = array(
							'rel_id' => $rel_id,
							'entity_ids' => $related[$type],
							'dir' => (($relations[$rel_id]['relationship_a'] == id_of('event_type')) ? 'left' : 'right')
							);
					}
				}
			}
		}
		
		if($this->params['ideal_count'])
			$init_array['ideal_count'] = $this->params['ideal_count'];
		elseif(!empty($this->ideal_count))
			$init_array['ideal_count'] = $this->ideal_count;
		
		if($this->params['default_view_min_days']) 
			$init_array['default_view_min_days'] = $this->params['default_view_min_days'];
		
		$init_array['automagic_window_snap_to_nearest_view'] = $this->snap_to_nearest_view;
		
		if($markup = $this->get_markup_object('list'))
			$display_type = $markup->get_ongoing_display_type();
		else
			$display_type = 'above';
		
		if('inline' == $display_type)
		{
			$init_array['ongoing_count_all_occurrences'] = true;
		}
		elseif('above' == $display_type)
		{
			$init_array['ongoing_count_all_occurrences'] = false;
			$init_array['ongoing_count_pre_start_dates'] = true;
			$init_array['ongoing_count_ends'] = $this->params['ongoing_show_ends'];
		}
		elseif('below' == $display_type)
		{
			$init_array['ongoing_count_all_occurrences'] = false;
			$init_array['ongoing_count_pre_start_dates'] = false;
			$init_array['ongoing_count_ends'] = $this->params['ongoing_show_ends'];
		}
		
		if(!empty($this->request['search']))
		{
			$init_array['simple_search'] = $this->request['search'];
		}
		$init_array['es_callback'] = array($this, 'reason_calendar_master_callback');
		$init_array['cache_lifespan'] = $this->get_cache_lifespan();
		$init_array['cache_lifespan_meta'] = $this->get_cache_lifespan_meta();
		return $init_array;
	}
	
	/**
	 * Attach universal callback to an entity selector
	 *
	 * Modifies the entity selector in reason's calendar to take into account inclduing audiences
	 * or excluding audiences. Also calls any other callback function that might be included in the
	 * calendar class.
	 *
	 * This should be attached to any calendar set up in the class
	 *
	 * @param entity_selector $es the entity selector from reason's calendar class used to select events.
	 * @return void
	 */
	function reason_calendar_master_callback($es)
	{
		/* 
			In case there is another callback function, make sure to call it in addition to limiting
			and excluding audiences
		*/
		if(!empty($this->es_callback))
		{
			$callback_array = array();
			$callback_array[] =& $es;
			call_user_func_array($this->es_callback, $callback_array);
		}
		if($audiences_to_limit_to = $this->_get_audiences_to_limit_to())
		{
			$es->add_left_relationship(array_keys($audiences_to_limit_to), relationship_id_of('event_to_audience'));
		}
		if($audiences_to_exclude = $this->_get_audiences_to_exclude())
		{
			// get all events -- exclude those who have an audience in the excluded audience list
			$audience_table_info = $es->add_left_relationship_field('event_to_audience', 'entity', 'id', 'audience_id');
			$audience_ids = array_keys($audiences_to_exclude);
			array_walk($audience_ids,'db_prep_walk');
			$es->add_relation($audience_table_info['audience_id']['table'].'.'.$audience_table_info['audience_id']['field'].' NOT IN ('.implode(',',$audience_ids).')');
		}
		
		if($this->params['limit_to_ticketed_events']) {
			// Limit to events with the 'event_to_form' relationship AND
			// the state of the form entity is Live
			$table_info = $es->add_left_relationship_field('event_to_form', 'entity', 'state', 'form_id');
			$es->add_relation($table_info['form_id']['table'] . '.' . $table_info['form_id']['field'] . ' = "Live"');
		}
		
		if(!empty($this->params['freetext_filters']))
		{
			foreach($this->params['freetext_filters'] as $filter)
			{
				$string = $filter[0];
				$fields = explode(',',$filter[1]);
				$parts = array();
				foreach($fields as $field)
				{
					$field_parts = explode('.',trim($field));
					
					$parts[] = '`'.implode('`.`',$field_parts).'` LIKE "'.reason_sql_string_escape($string).'"';
				}
				$where = '('.implode(' OR ',$parts).')';
				$es->add_relation($where);
			}
		}
		if($this->params['filter_on_page_name'])
		{
			if(is_bool($this->params['filter_on_page_name']))
			{
				$es->add_relation('entity.name LIKE "%'.reason_sql_string_escape($this->parent->cur_page->get_value('name')).'%"');
			}
			elseif(is_string($this->params['filter_on_page_name']))
			{
				$es->add_relation('`'.reason_sql_string_escape($this->params['filter_on_page_name']).'` LIKE "%'.reason_sql_string_escape($this->parent->cur_page->get_value('name')).'%"');
			}
			else
			{
				trigger_error('filter_on_page_name does not support values other than boolean or string');
			}
		}
	}
	/**
	 * Display a link to the calendar's RSS feed
	 * @return void
	 * @todo Move to a markup class
	 */
	function show_feed_link()
	{
		$type = new entity(id_of('event_type'));
		if($type->get_value('feed_url_string'))
			echo '<div class="feedInfo"><a href="'.$this->parent->site_info->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/'.$type->get_value('feed_url_string').'" title="RSS feed for this site\'s events">xml</a></div>';
	}
	/**
	 * Generate the markup for a link to the full calendar link (for feed-style events modules)
	 *
	 * This method will return an empty string if this is a "normal" non-sidebar/feed-style module
	 *
	 * @return string
	 * @todo Move to a markup class
	 */
	function get_full_calendar_link_markup()
	{
		if(!empty($this->params['calendar_link_replacement']))
			return $this->params['calendar_link_replacement'];
		
		if(empty($this->events_page_url) || empty($this->params['calendar_link_text']))
			return '';
		
		return '<p class="more"><a href="'.$this->events_page_url.'">'.$this->params['calendar_link_text'].'</a></p>'."\n";
	}
	
	function get_events_page_url()
	{
		return $this->events_page_url;
	}
	
	function get_current_page()
	{
		return $this->cur_page;
	}
	/**
	 * Display the ical link section
	 *
	 * @return void
	 * @todo Move to a markup class
	 * @todo Find a way to make Yahoo accept urls with ampersands
	 */
	function show_list_export_links()
	{
		echo '<div class="iCalExport">'."\n";
		
		/* If they are looking at the current view or a future view, start date in link should be pinned to current date.
			If they are looking at an archive view, start date should be pinned to the start date they are currently viewing */
		
		$start_date = $this->today;
		if(!empty($this->request['start_date']) && $this->_get_start_date() < $this->today)
		{
			$start_date = $this->request['start_date'];
		}
		$ical_link = '//'.REASON_HOST.$this->parent->pages->get_full_url( $this->page_id ).$this->construct_link(array('start_date'=>$start_date,'view'=>'','end_date'=>'','format'=>'ical'), true, false);
		
		$webcal_url = 'webcal:'.$ical_link;
		$gcal_url = 'https://calendar.google.com/calendar/render?cid='.urlencode($webcal_url);
		if(!empty($this->request['category']) || !empty($this->request['audience']) || !empty($this->request['search']))
		{
			$subscribe_desktop_text = 'Subscribe to this view (Desktop)';
			$subscribe_gcal_text = 'Subscribe to this view (Google)';
			//$subscribe_ycal_text = 'Subscribe to this view (Yahoo!)';
			$download_text = 'Download these events (.ics)';
		}
		else
		{
			$subscribe_desktop_text = 'Subscribe (Desktop)';
			$subscribe_gcal_text = 'Subscribe (Google)';
			//$subscribe_ycal_text = 'Subscribe (Yahoo!)';
			$download_text = 'Download events (.ics)';
		}
		echo '<a href="'.htmlspecialchars($webcal_url).'">'.$subscribe_desktop_text.'</a>';
		echo ' <span class="divider">|</span> <a href="'.htmlspecialchars($gcal_url).'" target="_blank">'.$subscribe_gcal_text.'</a>';
		//echo ' <span class="divider">|</span> <a href="'.$ycal_url.'" target="_blank">'.$subscribe_ycal_text.'</a>';
		if(!empty($this->events)) 
		{
			echo ' <span class="divider">|</span> <a href="'.htmlspecialchars($ical_link).'">'.$download_text.'</a>';
		}
		if (defined("REASON_URL_FOR_ICAL_FEED_HELP") && ( (bool) REASON_URL_FOR_ICAL_FEED_HELP != FALSE))
		{
			echo ' <span class="divider">|</span> <a href="'.REASON_URL_FOR_ICAL_FEED_HELP.'"><span class="helpIcon"><img src="'.REASON_HTTP_BASE_PATH . 'silk_icons/help.png" alt="Help" width="16px" height="16px" /> </span>How to Use This</a>';
		}
		echo '</div>'."\n";
	}
	
	
	///////////////////////////////////////////
	// Showing a Specific Event
	///////////////////////////////////////////
	
	
	/**
	 * Initialiation function for event detail mode
	 *
	 * @todo should probably just grab audience and categories right here.
	 *
	 * @return void
	 */
	function init_event() // {{{
	{
		$this->event = new entity($this->request['event_id']);
		if ($this->event_ok_to_show($this->event))
		{
			if(!empty($this->request['format']) && $this->request['format'] == 'ical')
			{
				$event = carl_clone($this->event);
				if(!empty($this->request['date']))
				{
					$event->set_value('recurrence','none');
					$event->set_value('datetime',$this->request['date'].' '.prettify_mysql_datetime($event->get_value('datetime'), 'H:i:s'));
				}
				$this->export_ical(array($event));
			}
			if(!empty($this->request['format']) && $this->request['format'] == 'json')
			{
				$event = carl_clone($this->event);
				if(!empty($this->request['date']))
				{
					$event->set_value('recurrence','none');
					$event->set_value('datetime',$this->request['date'].' '.prettify_mysql_datetime($event->get_value('datetime'), 'H:i:s'));
				}
				$this->export_json(array($event));
			}
			else
			{
				$this->_add_crumb( $this->event->get_value( 'name' ) );
				$this->parent->pages->make_current_page_a_link();
				
				if($head_items = $this->get_head_items() )
				{
					if($this->event->get_value('keywords'))
					{
						$head_items->add_head_item('meta',array( 'name' => 'keywords', 'content' => htmlspecialchars($this->event->get_value('keywords'),ENT_QUOTES,'UTF-8')));
					}
				}

				$forms = $this->get_registration_forms($this->event);
				if (!empty($forms))
				{
					if ($head_items = $this->get_head_items())
					{
						$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/events/event_registration_forms.css');
					}
				}
			}
		}
	} // }}}
	/**
	 * Given set of events, generate ical representation, send as ical, and die
	 *
	 * Note that this method will never return, as it calls die().
	 *
	 * @param array $events entities
	 * @return void
	 */
	function export_ical($events)
	{
		while(ob_get_level() > 0)
			ob_end_clean();
		$ical = $this->get_ical($events);
		$size_in_bytes = strlen($ical);
		if(count($events) > 1)
			$filename = 'events.ics';
		else
			$filename = 'event.ics';
		$ic = new reason_iCalendar();
		header( $ic->get_icalendar_header() );
		header('Content-Disposition: attachment; filename='.$filename.'; size='.$size_in_bytes);
		echo $ical;
		die();
	}
	/**
	 * Given set of events, generate json representation, send as json, and die
	 *
	 * Note that this method will never return, as it calls die().
	 *
	 * @param array $events entities
	 * @return void
	 */
	function export_json($events)
	{
		while(ob_get_level() > 0)
			ob_end_clean();

		$encoded = $this->get_json($events);
		$size_in_bytes = strlen($encoded);

		header('Content-Type: application/json; charset=utf-8');
		header('Content-Length: '.$size_in_bytes);
		echo $encoded;
		die();
	}
	/**
	 * Get an ical representation of a set of events
	 * @param array $events entities
	 * @return string iCal
	 */
	function get_ical($events)
	{
		if(!is_array($events))
		{
			trigger_error('get_ical needs an array of event entities');
			return '';
		}
		
		$calendar = new reason_iCalendar();
		$nav = $this->get_page_nav();
		$calendar->set_events_page_url( $nav->get_full_url( $this->page_id, true, true ) );
  		$calendar->set_events($events);
		if (count($events) > 1)
		{
			$site = new entity($this->site_id);
			$site_name = $site->get_value('name');
			$calendar->set_title($site_name);
		}
  		return $calendar -> get_icalendar_events();
	}
	/**
	 * Get a json representation of a set of events
	 * @param array $events entities
	 * @return string JSON
	 */
	function get_json($events)
	{
		if(!is_array($events))
		{
			trigger_error('get_json needs an array of event entities');
			return '';
		}

		$nav = $this->get_page_nav();
		$page_url = $nav->get_full_url( $this->page_id, true, true );

		$events = array_values(array_map(function($e) use ($page_url) {
			// ensure that all events have an associated URL
			if(!$e->get_value('url')) {
				$e->set_value('url', $page_url.'?event_id='.$e->id());
			}
			$values = $e->get_values();

			$values['calendar_record'] = $values['calendar_record'] ? (int)$values['calendar_record'] : null;
			$values['dates'] = explode(", ", $values['dates']);
			$values['datetime'] = $values['datetime'] ?: null;
			$values['end_date'] = $values['end_date'] ?: null;
			$values['frequency'] = $values['frequency'] ? (int)$values['frequency'] : null;
			$values['friday'] = $values['friday'] == 'true';
			$values['hours'] = $values['hours'] ? (int)$values['hours'] : null;
			$values['id'] = (int)$values['id'];
			$values['last_occurence'] = $values['last_occurence'] ?: null;
			$values['latitude'] = $values['latitude'] ? (double)$values['latitude'] : null;
			$values['longitude'] = $values['longitude'] ? (double)$values['longitude'] : null;
			$values['minutes'] = $values['minutes'] ? (int)$values['minutes'] : null;
			$values['monday'] = $values['monday'] == 'true';
			$values['month_day_of_week'] = $values['month_day_of_week'] ?: null;
			$values['monthly_repeat'] = $values['monthly_repeat'] ?: null;
			// $values['new'] = $values['new'] == '1';
			// $values['no_share'] = $values['no_share'] == '1';
			$values['recurrence'] = $values['recurrence'] ?: null;
			$values['registration'] = $values['registration'] ?: null;
			$values['saturday'] = $values['saturday'] == 'true';
			$values['show_hide'] = $values['show_hide'] ?: null;
			$values['sunday'] = $values['sunday'] == 'true';
			$values['term_only'] = $values['term_only'] ? $values['term_only'] === "yes" : null;
			$values['thursday'] = $values['thursday'] == 'true';
			$values['tuesday'] = $values['tuesday'] == 'true';
			$values['wednesday'] = $values['wednesday'] == 'true';
			$values['week_of_month'] = $values['week_of_month'] ? (int)$values['week_of_month'] : null;

			return $values;
		}, $events));

		return safe_json_encode($events);
	}
	/**
	 * Output HTML for the detail view of an event
	 * @return void
	 */
	function show_event() // {{{
	{
		if ($this->event_ok_to_show($this->event))
			$this->show_event_details();
		else
			$this->show_event_error();
	} // }}}
	/**
	 * Is a given event acceptable to show in the calendar?
	 * @param object $event entity
	 * @return boolean
	 */
	function event_ok_to_show($event)
	{
		$id = $event->id();
		if (!isset($this->_ok_to_show[$id]))
		{
			$sites = $this->_get_sites();
			if(is_array($sites)) $es = new entity_selector(array_keys($sites));
			else $es = new entity_selector($sites->id());
			$es->add_type(id_of('event_type'));
			$es->add_relation('entity.id = "'.$id.'"');
			$es->add_relation(table_of('show_hide', id_of('event_type')). ' = "show"');
			$es->set_num(1);
			$es->limit_tables(get_table_from_field('show_hide', id_of('event_type')));
			$es->limit_fields();
			if($this->_get_sharing_mode() == 'shared_only') $es->add_relation('entity.no_share != 1');
			$this->_ok_to_show[$id] = ($es->run_one());
		}
		return $this->_ok_to_show[$id];
	}
	/**
	 * Show the detail view of the current event
	 *
	 * @return void
	 */
	function show_event_details()
	{
		$e =& $this->event;
		$inline_edit =& get_reason_inline_editing($this->page_id);
		$editable = $inline_edit->available_for_module($this);
		$regionclass = $active = '';
		if ($editable && $this->user_can_inline_edit_event($this->event->id()))
		{
			$active = $inline_edit->active_for_module($this);
			$class = ($active) ? 'editable editing' : 'editable';
			echo '<div class="'.$class.'">'."\n";
			if ($active) 
			{
				$this->edit_handler->disco_item->run();
			} else {
				$regionclass = 'editRegion';
			}
		}
		
		echo '<div class="eventDetails '.$regionclass.'">'."\n";
		if ($regionclass == 'editRegion')
		{
			$activation_params = $inline_edit->get_activation_params($this);
			$activation_params['edit_id'] = $e->id();
			$url = carl_make_link($activation_params);
			echo ' <a href="'.$url.'" class="editThis">Edit Event</a>'."\n";
			
		}
		
		if($active)
		{
			$this->show_back_link();
		}
		elseif($markup = $this->get_markup_object('item'))
		{
			$bundle = new functionBundle();
			$bundle->set_function('current_site_id',array($this, 'get_current_site_id'));
			$bundle->set_function('back_link',array($this,'construct_link'));
			$bundle->set_function('request_date',array($this,'get_request_date'));
			$bundle->set_function('images', array($this, 'get_event_images') );
			$bundle->set_function('media_works', array($this, 'get_event_media_works'));
			$bundle->set_function('owner_site', array($this, 'get_owner_site_info'));
			$bundle->set_function('ical_link', array($this, 'get_item_ical_link'));
			$bundle->set_function('external_link', array($this, 'get_item_external_link'));
			$bundle->set_function('contact_info', array($this, 'get_contact_info'));
			$bundle->set_function('categories', array($this, 'get_event_categories'));
			$bundle->set_function('audiences', array($this, 'get_event_audiences'));
			$bundle->set_function('keyword_links', array($this, 'get_event_keyword_links'));
			$bundle->set_function('is_all_day_event', array($this, 'event_is_all_day_event'));
			$bundle->set_function('map_zoom_level', array($this, 'get_map_zoom_level'));
			$bundle->set_function('get_registration_forms', array($this, 'get_registration_forms'));
			$bundle->set_function('registration_markup', array($this, 'get_registration_forms_markup'));
			$bundle->set_function('prettify_duration', array($this, 'prettify_duration'));
			$bundle->set_function('repetition_explanation', array($this, 'get_repetition_explanation'));
			
			if($admin_markup = $this->get_markup_object('item_admin'))
			{
				$admin_bundle = new functionBundle();
				$admin_bundle->set_function('borrow_this_link', array($this, 'get_borrow_this_link'));
				$admin_bundle->set_function('cur_user_is_reason_editor', array($this, 'cur_user_is_reason_editor'));
				$admin_markup->set_bundle($admin_bundle);
				if($head_items = $this->get_head_items())	
					$admin_markup->modify_head_items($head_items, $e);
				$bundle->set_function('admin_markup', array($admin_markup, 'get_markup'));
			}
			
			$this->modify_item_function_bundle($bundle);
			$markup->set_bundle($bundle);
			if($head_items = $this->get_head_items())	
				$markup->modify_head_items($head_items, $e);
			echo $markup->get_markup($e);
		}
		echo '</div>'."\n";
		
		if ($editable && $this->user_can_inline_edit_event($this->event->id()))
			echo '</div>'."\n";

	}
	/**
	 * Add additional functions to the item function bundle
	 *
	 * This is for classes that extend the events module to add additional functionality for the markup class
	 *
	 * @param object $bundle
	 * @return void
	 */
	function modify_item_function_bundle($bundle)
	{
		// for overloading
	}
	
	function get_map_zoom_level($event)
	{
		if (isset($this->params['map_zoom_level']) && !empty($this->params['map_zoom_level'])) 
			return $this->params['map_zoom_level'];
		return null;
	}
	function get_request_date()
	{
		if(!empty($this->request['date']))
			return $this->request['date'];
		return null;
	}
	
	/**
	 * If a requested event is not found, what should we output?
	 *
	 * By default, this method outputs an error message and lists
	 * other events
	 *
	 * @return void
	 */
	function show_event_error() // {{{
	{
		echo '<p>We\'re sorry; the event requested does not exist or has been removed from this calendar. This may be due to incorrectly typing in the page address; if you believe this is a bug, please report it to the contact person listed at the bottom of the page.</p>';
		$this->init_list();
		$this->list_events();
	} // }}}
	
	function get_owner_site_info($e)
	{
		$owner_site = $e->get_owner();
		if($owner_site->id() != $this->parent->site_info->id())
		{
			reason_include_once( 'classes/module_sets.php' );
			$ms =& reason_get_module_sets();
			$modules = $ms->get('event_display');
			$rpts =& get_reason_page_types();
			$page_types = $rpts->get_page_type_names_that_use_module($modules);
			if (!empty($page_types))
			{
				$tree = NULL;
				$owner_site->set_value('_link', get_page_link($owner_site, $tree, $page_types, true));
			}
			else
			{
				$owner_site->set_value('_link', $owner_site->get_value('base_url'));
			}
			return $owner_site;
		}
		return false;
	}
	/**
	 * Get an array of contact information for a given event entity
	 *
	 * Array keys: 'username', 'email', 'fullname', 'phone', 'organization'
	 *
	 * @param object $e event entity
	 * @return array
	 */
	function get_contact_info($e)
	{
		$ret = array();
		$contact = $e->get_value('contact_username');
		if(!empty($contact) )
		{
			$ret['username'] = $contact;
			$dir = new directory_service();
			$dir->search_by_attribute('ds_username', array(trim($contact)), array('ds_email','ds_fullname','ds_phone',));
			$ret['email'] = $dir->get_first_value('ds_email');
			$ret['fullname'] = $dir->get_first_value('ds_fullname');
			$ret['phone'] = $dir->get_first_value('ds_phone');
			$ret['organization'] = $e->get_value('contact_organization');
		}
		return $ret;
	}
	/**
	 * Get the ical link for a given event entity
	 *
	 * @param object $e event entity
	 * @param boolean $all_ocurrences false will create a link for JUST a single date ocurrence
	 * @return string html-encoded URL
	 */
	function get_item_ical_link($e, $all_ocurrences = true)
	{
		$date = '';
		if(!empty($this->request['date']))
			$date = $this->request['date'];
		if($all_ocurrences)
			return $this->construct_link(array('event_id'=>$e->id(),'format'=>'ical','date'=>''));
		else
			return $this->construct_link(array('event_id'=>$e->id(),'format'=>'ical','date'=>$date));
	}
	/**
	 * Get the external add-to-calendar link for a given event entity
	 *
	 * @param object $e event entity
	 * @param string $calendar determines which external source, 'google' or 'yahoo'
	 * @return string html-encoded URL
	 */
	function get_item_external_link($e, $calendar) {
		//Make DateTime object, assign single instance date if relevant:
		$dateTime = new DateTime($e->get_value('datetime'));
		$reqDate = '';
		if ($e->get_value('recurrence') != 'none' && !empty($this->request['date']))
			$reqDate = $this->request['date'];
		if (!empty($reqDate))
			$dateTime->modify($reqDate.' '.$dateTime->format('H:i:s'));
		//Make DateTime object, set UTC, and record start dates and times:
		$hours = $e->get_value('hours');
		$minutes = $e->get_value('minutes');
		$all_day = (($dateTime->format('His') == '000000') && ((($hours == 0) || ($hours == 24)) && ($minutes == 0)));
		$dateTime->setTimezone(new DateTimeZone('UTC'));
		$startDate = $dateTime->format('Ymd');
		$startTime = $dateTime->format('His');																						
		//Get event title and adjust and make safe for transfer:
		$title = $this->string_to_external_url_safe($e->get_value('name'));
		//Create location string:
		$location = $this->string_to_external_url_safe($e->get_value('location'));
		$address = $this->string_to_external_url_safe($e->get_value('address'));
		$location .= (!empty($location) && !empty($address)) ? ', ' : '';
		$location .= (!empty($address)) ? $address : '';
		//Create details string:
		$content = $this->string_to_external_url_safe($e->get_value('content'));
		$info_url = $this->string_to_external_url_safe('For more information, visit: '.$e->get_value('url'));
		$event_url = 'http://'.REASON_HOST.$this->parent->pages->get_full_url($this->page_id).'?event_id='.$e->id();
		if(!empty($this->request['date']))
			$event_url .= '&date='.urlencode($this->request['date']);
		$cal_url = $this->string_to_external_url_safe('Event source: '.$event_url);
		$details = (!empty($content)) ? $content.'%0A%0A' : '';
		$url = $e->get_value('url');
		$details .= (!empty($url)) ? $info_url.'%0A%0A'.$cal_url : $cal_url;
		switch ($calendar) {
			case 'google':
				//Complete additional formating:
				if($all_day && $e->get_value('recurrence') == 'daily' && $e->get_value('frequency') == '1')
				{
					$dateTime = new DateTime($e->get_value('last_occurence'));
					$dateTime->add(new DateInterval('PT24H0M'));
				}
				elseif($e->get_value('hours') || $e->get_value('minutes'))
				{
					$hours = $e->get_value('hours') ? $e->get_value('hours') : '0';
					$minutes = $e->get_value('minutes') ? $e->get_value('minutes') : '0';
					$dateTime->add(new DateInterval('PT'.$hours.'H'.$minutes.'M'));
				}
				$endDate = $dateTime->format('Ymd');
				$endTime = $dateTime->format('His');
				$dates = $all_day ? $startDate.'/'.$endDate : $startDate.'T'.$startTime.'Z/'.$endDate.'T'.$endTime.'Z';
				//Create string to return:
				$ret = 'http://www.google.com/calendar/event?action=TEMPLATE';
				$ret .= '&dates='.$dates.'&ctz='.REASON_DEFAULT_TIMEZONE.'&text='.$title;
				$ret .= (!empty($location)) ? '&location='.$location : '';
				$ret .= (!empty($details)) ? '&details='.$details : '';
				break;
			case 'yahoo':
				//Complete additional formating:
				$duration = '';
				if (!$all_day) {
					$duration = ($hours < 10) ? '0'.$hours : $hours;
					$duration .= ($minutes < 10) ? '0'.$minutes : $minutes;
				}
				$startDateTime = $all_day ? $startDate : $startDate.'T'.$startTime.'Z';
				//Create string to return:
				$ret = 'http://calendar.yahoo.com/?v=60&ST='.$startDateTime.'&TITLE='.$title;
				$ret .= (!empty($duration)) ? '&DUR='.$duration : '';
				$ret .= (!empty($location)) ? '&in_loc='.$location : '';
				$ret .= (!empty($details)) ? '&DESC='.$details : '';
				break;
			default:
				trigger_error('External calendar of "'.$calendar.'" given is not implemented. Unable to return url.');
				$ret = NULL;
		}
		return $ret;
	}
	/**
	 * Returns given string converted for use as a parameter value in urls.
	 * Operations: Format lists, strip tags, straight to curly quotes, replace ampersand, urlencode, other minor changes.
	 *
	 * @param string $e string to convert
	 * @return string converted string
	 */
	function string_to_external_url_safe($string) {
		//not sure how best to handle ol or levels, so convert all to simple ul bullets
		$string = preg_replace('/<li[^>]*>/',' • ',$string);
		return urlencode(html_entity_decode(strip_tags($string)));
	}
	/**
	 * Output HTML of a link back to the events listing from the view of an individual event
	 *
	 * Note that this is now only used when there is no valid event being shown.
	 * If a valid event is being shown, the item markup generator is responsible for producing the back link(s).
	 *
	 * @return void
	 */
	function show_back_link()
	{
		echo '<p class="back"><a href="'.$this->construct_link().'">Back to event listing</a></p>'."\n";
	}
	/**
	 * Get the images for a given event entity
	 *
	 * @param object $e event entity
	 * @return array image entities
	 */
	function get_event_images($e)
	{
		$images = array();
		if ($rel_id = relationship_id_of('event_to_poster_image', true, false))
		{
			$es = new entity_selector();
			$es->description = 'Selecting poster images for event';
			$es->add_type( id_of('image') );
			$es->add_right_relationship( $e->id(), $rel_id );
			$es->add_rel_sort_field($e->id(), $rel_id);
        	$es->set_order('rel_sort_order ASC');
			$images = $es->run_one();
		}
		$es = new entity_selector();
		$es->description = 'Selecting images for event';
		$es->add_type( id_of('image') );
		$es->add_right_relationship( $e->id(), relationship_id_of('event_to_image') );
		if(!empty($images))
			$es->add_relation('`entity`.`id` NOT IN ("'.implode('","',array_keys($images)).'")');
		$es->add_rel_sort_field($e->id(), relationship_id_of('event_to_image'));
		$es->set_order('rel_sort_order ASC');
        $es->set_env( 'site' , $this->site_id );
		$images += $es->run_one();
		return $images;
	}
	/**
	* Get the media works for a given event entity
	*
	* @param object $e event entity
	* @return array media work entities
	*/
	function get_event_media_works($e)
	{
		static $cache = array();
		if(empty($cache[$e->id()]))
		{
			$es = new entity_selector();
			$es->add_type( id_of('av'));
			$es->add_right_relationship( $e->id(), relationship_id_of('event_to_media_work'));
			$es->add_rel_sort_field($e->id(), relationship_id_of('event_to_media_work'));
			$es->set_order('rel_sort_order ASC');
			$es->add_relation( 'show_hide.show_hide = "show"' );
			$es->add_relation( '(media_work.transcoding_status = "ready" OR ISNULL(media_work.transcoding_status) OR media_work.transcoding_status = "")' );
			$cache[$e->id()] = $es->run_one();
		}
		return $cache[$e->id()];
	}
	/**
	 * Get the categories for a given event entity
	 *
	 * Returned category entities are sweetened with the value _link, containing an html-encoded URL
	 *
	 * @param object $e event entity
	 * @return array category entities
	 */
	function get_event_categories($e)
	{
		$es = new entity_selector();
		$es->description = 'Selecting categories for event';
		$es->add_type( id_of('category_type'));
        $es->add_right_relationship( $e->id(), relationship_id_of('event_to_event_category') );
        $cats = $es->run_one();
        foreach($cats as $cat)
        {
        	$cat->set_value('_link', $this->construct_link(array('category'=>$cat->id(),'no_search'=>'1'), false));
        }
        return $cats;
	}
	/**
	 * Get the audiences for a given event entity
	 *
	 * Returned audience entities are sweetened with the value _link, containing an html-encoded URL
	 *
	 * @param object $e event entity
	 * @return array audience entities
	 */
	function get_event_audiences($e)
	{
		$audiences = array();
		$es = new entity_selector();
		$es->description = 'Selecting audiences for event';
		$es->limit_tables();
		$es->limit_fields();
		$es->enable_multivalue_results();
		$es->add_type( id_of('event_type'));
		$es->add_relation('entity.id = ' . $e->id());
		$es->add_left_relationship_field('event_to_audience', 'entity', 'id', 'aud_ids');
		$with_audiences = $es->run_one();
		if (!empty($with_audiences))
        {
        	$audiences = array();
        	$event = reset($with_audiences);
        	$aud_ids = $event->get_value('aud_ids');
        	$aud_ids = is_array($aud_ids) ? $aud_ids : array($aud_ids);
        	foreach( $aud_ids AS $aud_id )
        	{
        		$aud = new entity($aud_id);
        		$aud->set_value('_link', $this->construct_link(array('audience'=>$aud->id(),'no_search'=>'1'), false));
        		$audiences[$aud_id] = $aud;
        	}
        }
        return $audiences;
	}
	/**
	 * Get links to an event's keywords
	 *
	 * @param object $e event entity
	 * @return array keyword => html-encoded URL
	 */
	function get_event_keyword_links($e)
	{
		$keywords = array();
		if($e->get_value('keywords'))
		{
			$keys = explode(',',$e->get_value('keywords'));
			foreach($keys as $key)
			{
				$key = trim(strip_tags($key));
				$keywords[$key] = $this->construct_link(array('search'=>$key,'no_search'=>'1'),false);
			}
		}
		return $keywords;
	}
	
	/**
	* Template calls this function to figure out the most recently last modified item on page
	* This function uses the most recently modified event in list if not looking at an individual event
	* If looking at details, it returns last modified info for that the event in question
	* @return mixed last modified value or false
	*/
	function last_modified() // {{{
	{
		if( $this->report_last_modified_date && $this->has_content() )
		{
			if((!empty($this->event)) && $this->event->get_values())
			{
				return $this->event->get_value('last_modified');
			}
			elseif(!empty($this->events_by_date))
			{
				$max_date = '';
				foreach($this->events_by_date as $date=>$events)
				{
					foreach($events as $event_id)
					{
						if($max_date < $this->events[$event_id]->get_value('last_modified'))
						{
							$max_date = $this->events[$event_id]->get_value('last_modified');
						}
					}
				}
				if(!empty($max_date))
				{
					return $max_date;
				}
			}
		}
		return false;
	} // }}}
	
	//////////////////////////////////////
	// Registration Forms
	//////////////////////////////////////

	/**
	 * Get the registration forms associated with this event.
	 * 
	 * @param entity $event event entity
	 * @return array registration form entities
	 */
	function get_registration_forms(entity $event)
	{
		$es = new entity_selector();
		$es->description = "Getting the registration forms for this event";
		$es->add_type( id_of( 'form' ) );
		$es->add_right_relationship($event->id(), relationship_id_of('event_to_form'));
		$result = $es->run_one();
		
		return $result;
	}
	
	/**
	 * Get the markup for the registration forms for a given event
	 * @param entity $event event entity
	 * @return string markup
	 */
	function get_registration_forms_markup(entity $event)
	{
		ob_start();

		$event_time = new Datetime($event->get_value('datetime'));
		// show form for 12 hours after event,
		// but the form itself may display a closed message
		$event_time->modify("+12 hours");
		$now = new Datetime();
		$should_display_form =  $event_time > $now;
		if ($should_display_form) {			
			$forms = $this->get_registration_forms($event);
			foreach ($forms as $form) {
				$this->show_registration_form($form);
			}
		}

		$form_html = ob_get_clean();

		return $form_html;
	}

	/**
	 * Get a Thor Form Controller
	 * 
	 * @param entity $form form entity
	 * @return ThorFormController controller that hasn't been inited
	 */
	function get_ticket_form_controller(entity $form)
	{
		$formId = $form->id();
		if (empty($this->ticket_form_controllers[$formId])) {
			reason_include_once('minisite_templates/modules/form/models/thor.php');
			reason_include_once('minisite_templates/modules/form/views/thor/default.php');
			reason_include_once('minisite_templates/modules/form/controllers/thor.php');

			// Assemble the MVC components to handle forms in this context
			$form_model = new ThorFormModel();
			$form_model->init_from_module($this);
			// Unlike typical forms with a 'page_to_form' relationship,
			// here our form ID comes from an 'event_to_form' relationship and is already
			// present on this Event object so we manually define it on the model. 
			$form_model->set_form_id($formId);
			$form_model->set_form_entity($formId);

			$form_controller = new ThorFormController();
			$form_controller->set_model($form_model);

			$this->ticket_form_controllers[$formId] = $form_controller;
		}

		return $this->ticket_form_controllers[$formId];
	}

	/**
	 * Display the registration form
	 *
	 * @param entity $form form entity
	 * @return void
	 */
	function show_registration_form(entity $form)
	{
		if (array_key_exists('event_id', $this->request)) {
			$eventIdInRequest = $this->request['event_id'];
		} else {
			$eventIdInRequest = false;
		}
		
		$controller = $this->get_ticket_form_controller($form);
		$controller->init();
		$eventsOnForm = $controller->model->get_events_on_form();
		$eventIdInRequestIsValid = false;
		foreach ($eventsOnForm as $event) {
			if ($eventIdInRequest && $event->id() == $eventIdInRequest) {
				$eventIdInRequestIsValid = true;
			}
		}	

	
		if ($eventIdInRequestIsValid) {
			// Prepare the form, store response html in var 
			ob_start();
			
			// since the form is in the middle of the page, make sure users
			// see the form response using an anchor jump
			$view =& $controller->get_view();
			$view->form_action = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES) . "#jumpToForm";
			
			$controller->run();
			$formHtml = ob_get_clean();

			// This demotion is to reuse existing styles (and partially semantic, too)
			$formHtml = demote_headings($formHtml, 1);

			// Use the full form name for the header above the form
			$model =& $controller->get_model();
			$formEntity = $model->_form;

			$html = <<<HTML
<a name="jumpToForm"></a>
<div id="slotInfo">
	<div class="form">
		<h3>{$formEntity->get_value('name')}</h3>
		$formHtml
	</div>
</div>
HTML;

			echo $html;
		}
	}

	/**
	 * Get Event Ticket info & state from all related forms
	 *
	 * If a event hasn't received any submissions it will not appear in this array
	 *
	 * @param entity $event event entity
	 * @return array
	 */
	function get_ticket_info_from_form(entity $event) {
		// find forms on this event
		$forms = $this->get_registration_forms($event);
		// Look for the event in related forms, though there is usually only one form
		$eventInfo = array();
		foreach($forms as $form) {
			$form_controller = $this->get_ticket_form_controller($form);
			$form_controller->init();
			$model = $form_controller->get_model();
			$eventInfoFromForm = $model->event_tickets_get_all_event_seat_info();
			foreach($eventInfoFromForm as $k => $info) {
				$eventInfo[$k] = $info;
			}
		}

		return $eventInfo;
	}
	
	//////////////////////////////////////
	// Utilities
	//////////////////////////////////////
	
	/**
	 * Make a query-string-based link within the events module
	 *
	 * @param array $vars The query string variables for the link
	 * @param boolean $pass_passables should the items in $this->pass_vars
	 *                be passed if they are present in the current query?
	 * @param boolean $html_encode Should the output be html encoded? Use true for direct inclusion in HTML.
	 * @return string
	 *
	 * @todo replace this with carl_ functions
	 */
	function construct_link( $vars = array(), $pass_passables = true, $html_encode = true ) // {{{
	{
		if($pass_passables)
			$link_vars = $this->pass_vars;
		else
		{
			$link_vars = array();
			if(!empty($this->pass_vars['textonly']))
				$link_vars['textonly'] = 1; // always pass the textonly value
		}
		foreach( array_keys($vars) as $key )
		{
			$link_vars[$key] = $vars[$key];
		}
		foreach(array_keys($link_vars) as $key)
		{
			$link_vars[$key] = urlencode($link_vars[$key]);
		}
		$ret = '?'.implode_with_keys('&',$link_vars);
		if($html_encode)
		{
			$ret = htmlspecialchars($ret, ENT_QUOTES);
		}
		return $ret;
	} // }}}
	/**
	 * Is a given event an all-day event?
	 * @param object $event entity
	 * @return boolean
	 */
	public function event_is_all_day_event($event)
	{
		return $this->calendar->event_is_all_day_event($event);
	}
	/**
	 * Is a given event an "ongoing" event?
	 * @param object $event entity
	 * @return boolean
	 */
	protected function event_is_ongoing($event)
	{
		return $this->calendar->event_is_ongoing($event);
	}
	/**
	 * Get the ID of the current site
	 * @return integer
	 */
	public function get_current_site_id()
	{
		return $this->site_id;
	}
}