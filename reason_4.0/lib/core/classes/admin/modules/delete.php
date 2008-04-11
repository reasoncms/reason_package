<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'classes/admin/admin_disco.php' );
	class DeleteModule extends DefaultModule // {{{
	{
		var $deletable = false;
		/**
		 * Possible values:
		 * 
		 * - no_id_provided
		 * - insufficient_privileges
		 * - already_deleted
		 * - dependencies
		 */
		var $_not_deletable_reason;
		function DeleteModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}		
		function init() // {{{
		{
			$this->admin_page->set_show( 'leftbar', false );
			
			$this->deletable = $this->_is_deletable();
			if($this->deletable)
			{
				$this->_set_up_form();
			}
		} // }}}
		
		function _is_deletable()
		{
			if(empty($this->admin_page->id))
			{
				$this->_not_deletable_reason = 'no_id_provided';
				return false;
			}
			$item = new entity($this->admin_page->id);
			if($item->get_value('state') == 'Deleted')
			{
				$this->_not_deletable_reason = 'already_deleted';
				return false;
			}
			if($item->get_value('state') == 'Pending')
			{
				if(!reason_user_has_privs($this->admin_page->user_id, 'delete_pending'))
				{
					$this->_not_deletable_reason = 'insufficient_privileges';
					return false;
				}
			}
			else
			{
				if(!reason_user_has_privs($this->admin_page->user_id, 'delete'))
				{
					$this->_not_deletable_reason = 'insufficient_privileges';
					return false;
				}
			}
			if( $this->admin_page->is_deletable() )
			{
				return true;
			}
			else
			{
				$this->_not_deletable_reason = 'dependencies';
				return false;
			}
		}
		
		
		function _set_up_form()
		{
			$deleter = 'deleteDisco';
			$type = new entity( $this->admin_page->type_id );
			if( $type->get_value( 'custom_deleter' ) )
			{
				reason_include( 'content_deleters/' . $type->get_value( 'custom_deleter' ) );
				if(!empty($GLOBALS[ '_reason_content_deleters' ][ $type->get_value( 'custom_deleter' ) ] ) )
					$deleter = $GLOBALS[ '_reason_content_deleters' ][ $type->get_value( 'custom_deleter' ) ];
				else
					trigger_error($type->get_value( 'custom_deleter' ).' needs to record its class name in $GLOBALS[ "_reason_content_deleters" ].');
			}
			$this->disco_item = new $deleter;
			$this->disco_item->actions = array();
			$this->disco_item->set_page( $this->admin_page );
			$this->disco_item->actions[ 'delete' ] = 'Yes, Delete and Go Back to List';
			
			$this->disco_item->actions[ 'cancel' ] = 'No, Cancel';	
			$this->disco_item->grab_info( $this->admin_page->id , $graph );
			$this->disco_item->init();
		}
		function run() // {{{
		{
			if($this->deletable)
			{
				$this->disco_item->run();
			}
			else
			{
				switch($this->_not_deletable_reason)
				{
					case 'no_id_provided':
						echo '<p>Unable to delete item. Item may already have been deleted (sometimes this happens if you click twice on the delete button)</p>';
						return false;
					case 'insufficient_privileges':
						echo '<p>You do not have the privileges to delete this item.</p>';
						return false;
					case 'already_deleted':
						echo '<p>This item cannot be deleted because it has already been deleted. (sometimes you get this message if you click twice on the delete button)</p>';
						return false;
					case 'dependencies':
						$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'NoDelete' ) ) );
						header( 'Location: ' . $link );
						die();
					default:
						trigger_error('Unknown reason given for not being able to delete item: '.$this->_not_deletable_reason);
						echo '<p>Not able to delete item</p>';
						return false;
				}
			}
		} // }}}
	} // }}}
?>