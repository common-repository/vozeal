<?php
if(!class_exists('InTube_Settings'))
{
	class InTube_Settings
	{
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			// register actions
            add_action('admin_init', array(&$this, 'admin_init'));
        	add_action('admin_menu', array(&$this, 'add_menu'));
		} // END public function __construct
		
        /**
         * hook into WP's admin_init action hook
         */
        public function admin_init()
        {
        	// register your plugin's settings
        	register_setting('intube-group', 'auth_key');
        	register_setting('intube-group', 'setting_b');

        	// add your settings section
        	add_settings_section(
        	    'intube-section', 
        	    'Vozeal Settings', 
        	    array(&$this, 'settings_section_intube'), 
        	    'intube'
        	);
        	
        	// add your setting's fields
            add_settings_field(
                'auth_key', 
                'Authorization Key ', 
                array(&$this, 'settings_field_input_text'), 
                'intube', 
                'intube-section',
                array(
                    'field' => 'auth_key'
                )
            );
           
		 // Possibly do additional admin_init tasks
        } // END public static function activate
        
        public function settings_section_intube()
        {
            // Think of this as help text for the section.
            echo 'Enter the auth key you get on signing up with us to start receiving suggestions!';
        }
        
        /**
         * This function provides text inputs for settings fields
         */
       /* public function sign_up_link(){
		echo '<a href="http://www.vozeal.com">Sign up to get Auth Key</a>';
	}*/
	public function settings_field_input_text($args)
        {
            // Get the field name from the $args array
            $field = $args['field'];
            // Get the value of this setting
            $value = get_option($field);
            // echo a proper input type="text"
            echo sprintf('<input type="text" name="%s" id="%s" value="%s" />', $field, $field, $value);
       	echo '<br><br/><a href="http://www.vozeal.com">Sign up to get Auth Key</a>';
        } // END public function settings_field_input_text($args)
        
        /**
         * add a menu
         */		
        public function add_menu()
        {
            // Add a page to manage this plugin's settings
        	add_options_page(
        	    'Vozeal Settings', 
        	    'Vozeal', 
        	    'manage_options', 
        	    'intube', 
        	    array(&$this, 'plugin_settings_page')
        	);
        } // END public function add_menu()
    
        /**
         * Menu Callback
         */		
        public function plugin_settings_page()
        {
        	if(!current_user_can('manage_options'))
        	{
        		wp_die(__('You do not have sufficient permissions to access this page.'));
        	}
	
        	// Render the settings template
        	include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
        } // END public function plugin_settings_page()
    } // END class InTube_Settings
} // END if(!class_exists('InTube_Settings'))
