<?php 
/*
Plugin Name: Strider Bikes User Background Check 
Plugin URI: https://github.com/nickwilliamsnewby
Description: adds meta boxes to all pages/posts to restrict access until an lpUser completes the selected course
Author: Nicholas Williams
Version: 1.0.0
Author URI: http://williamssoftwaresolutions.com
Tags: learnpress
Text Domain: sbbgCheck
*/

if (!defined('ABSPATH')) {
    exit;
}

if(! defined( 'STRIDER_BIKES_BGCHECK_PATH' ) ) define('STRIDER_BIKES_BGCHECK_PATH', dirname( __FILE__ ) );
if(! defined( 'STRIDER_BIKES_BGCHECK_FILE' ) ) define('STRIDER_BIKES_BGCHECK_FILE', ( __FILE__ ) );


if ( !defined('ABSPATH')) {
    exit;
}


class Strider_Bikes_Background_Check{

	/**
	 * @var object
	 */
	private static $_instance = false;

	/**
	 * @var string
	 */
	private $_plugin_url = '';

	/**
	 * @var string
	 */
    private $_plugin_template_path = '';

    //protected $_meta_boxes = array();

    //protected $_post_type = '';




    function __construct(){
        //$this->_post_type = 'lp_unlock_oncomplete_cpt';
        $this->_tab_slug = sanitize_title( 'sb-background-check' );
        $this->_plugin_template_path = STRIDER_BIKES_BGCHECK_PATH.'/template/';
        $this->_plugin_url  = untrailingslashit( plugins_url( '/', STRIDER_BIKES_BGCHECK_FILE ));
        /*
        add_action('manage_users_columns', array($this, 'strider_bikes_add_user_bg_metaBool'));
        add_filter('manage_users_custom_column', array($this, 'sb_modify_background_check_row'), 10, 3);
        */
        add_action('show_user_profile', array($this, 'sb_bg_bool_profile'));
        add_action('edit_user_profile', array($this, 'sb_bg_bool_profile'));
        add_action('profile_update', array($this, 'sb_bg_update_value'),20,1);
    }
    function sb_bg_update_value($user_id){
        if(current_user_can('edit_user', $user_id)){
            update_user_meta($user_id, 'user_bg_check_passed', $_POST['user_bg_check_passed_bool']);
        }
    }

    function sb_bg_bool_profile($profileuser) {
        ?>
            <table class="form-table">
                <tr>
                    <th>
                        <label for="user_bg_check_passed"><?php _e('Back Ground Check Status'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="user_bg_check_passed_bool" id="user_bg_check_passed" value="1" <?php
                        if ( esc_attr( get_the_author_meta( 'user_bg_check_passed', $profileuser->ID ) ) == "1"){?> checked = "checked"<?php } ?> />
                        <br><span class="description"><?php _e('Check box if user passes background check', 'text-domain'); ?></span>
                    </td>
                </tr>
            </table>
        <?php
        }
    function sb_modify_background_check_row($value, $column_name, $user_id){
        switch($column_name){
            case 'bgCheckPassed':
                return get_the_author_meta('bgCheckPassed', $user_id);
                break;
            default:
                break;
        }
        return $value;
    }

    function strider_bikes_add_user_bg_metaBool( $column ) {
        $column['bgCheckPassed'] = 'Background check status';
        return $column;
    }

	/**
	 * @return bool|Strider_Bikes_Background_Check because OOP is fun
	 */
	static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

}
//create an instance of our add - ons main class 
add_action( 'init', array( 'Strider_Bikes_Background_Check', 'instance' ) );
