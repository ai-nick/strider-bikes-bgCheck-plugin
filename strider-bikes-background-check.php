<?php 
/*
Plugin Name: Strider Bikes User Background Check 
Plugin URI: https://github.com/nickwilliamsnewby
Description: adds meta boxes to all pages/posts to restrict access until an lpUser completes the selected course
Author: Nicholas Williams
Version: 1.0.0
Author URI: http://williamssoftwaresolutions.com
Text Domain: sbbgCheck
*/

if (!defined('ABSPATH')) {
    exit;
}

if(! defined( 'STRIDER_BIKES_BGCHECK_PATH' ) ) define('STRIDER_BIKES_BGCHECK_PATH', dirname( __FILE__ ) );
if(! defined( 'STRIDER_BIKES_BGCHECK_FILE' ) ) define('STRIDER_BIKES_BGCHECK_FILE', ( __FILE__ ) );
if(! defined( 'STRIDER_BIKES_BGCHECK_ID_KEY' ) ) define('STRIDER_BIKES_BGCHECK_ID_KEY', 'sb_bg_check_canidate_ID' );


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
        $this->_plugin_template_path = STRIDER_BIKES_BGCHECK_PATH.'/templates/';
        $this->_plugin_url  = untrailingslashit( plugins_url( '/', STRIDER_BIKES_BGCHECK_FILE ));
        /*
        add_action('manage_users_columns', array($this, 'strider_bikes_add_user_bg_metaBool'));
        add_filter('manage_users_custom_column', array($this, 'sb_modify_background_check_row'), 10, 3);
        */
        add_action('admin_menu', array($this, 'sb_bg_check_create_menu'));
        add_action('gform_pre_submission_5', array($this, 'sb_bg_pre_gravity_form'));
        add_action('wp_enqueue_scripts', array($this, 'sb_bg_scripts'));
        add_action('show_user_profile', array($this, 'sb_bg_bool_profile'));
        add_action('edit_user_profile', array($this, 'sb_bg_bool_profile'));
        add_action('profile_update', array($this, 'sb_bg_update_value'),20,1);
        add_shortcode('sb_instructor_backgroundCheck_form', array($this, 'backgroundCheckFormLoader'));
        add_shortcode('sb_instructor_backgroundCheck_status', array($this, 'sb_bg_status_shortcode'));
        //LP_Request_Handler::register_ajax('sb_bg_check_update_userInfo', array($this, 'sb_bg_check_update_userInfo'));
        add_action('wp_ajax_check_make_order', array($this, 'sb_bg_check_make_order'));
        add_action('wp_ajax_check_update_userInfo', array($this, 'sb_bg_check_update_userInfo'));
        add_action('wp_ajax_check_order_status', array($this, 'sb_bg_check_order_status'));
    }
    
    function sb_bg_check_create_menu() {
    
        //create new top-level menu
        add_menu_page('Strider Bikes Bg Check Settings', 'Background Check Settings', 'administrator',__FILE__, array($this, 'sb_bg_check_settings_page') );
    
        //call register settings function
        add_action( 'admin_init', array($this, 'register_sb_bg_check_settings') );
    }
    
    
    function register_sb_bg_check_settings() {
        //register our settings
        register_setting( 'sb-bg-check-settings-group', 'sb_bg_check_abg_api_key' );
        register_setting( 'sb-bg-check-settings-group', 'sb_bg_check_abg_api_secret' );
        register_setting( 'sb-bg-check-settings-group', 'sb_bg_check_abg_api_baseurl' );
    }
    
    function sb_bg_check_settings_page() {
    ?>
    <div class="wrap">
    <h1>Strider Bikes Background Check Plugin</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields( 'sb-bg-check-settings-group' ); ?>
        <?php do_settings_sections( 'sb-bg-check-settings-group' ); ?>
        <table class="form-table">
            <tr valign="top">
            <th scope="row">Accurate Background Api Key</th>
            <td><input type="text" name="sb_bg_check_abg_api_key" value="<?php echo esc_attr( get_option('sb_bg_check_abg_api_key') ); ?>" /></td>
            </tr>
             
            <tr valign="top">
            <th scope="row">Accurate Background Api Secret</th>
            <td><input type="text" name="sb_bg_check_abg_api_secret" value="<?php echo esc_attr( get_option('sb_bg_check_abg_api_secret') ); ?>" /></td>
            </tr>
            
            <tr valign="top">
            <th scope="row">BackGround Check Form Url</th>
            <td><input type="text" name="sb_bg_check_abg_api_baseurl" value="<?php echo esc_attr( get_option('sb_bg_check_abg_api_baseurl') ); ?>" /></td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    
    </form>
    </div>
    <?php } 

    function sb_bg_status_shortcode(){
        $cUserID = get_current_user_id();
        $userBGCheck = get_user_meta($cUserID, STRIDER_BIKES_BGCHECK_ID_KEY);
        $bgCheckPageURL = get_option('sb_bg_check_abg_api_baseurl');
        $out = '<div class="container-fluid">';
        if (sizeof($userBGCheck[0])<1){
            $out .= '<p> You have not submitted your information for 
            a background check yet, please visit the <a href="'.$bgCheckPageURL.'"> background check page </a>to fill out and 
            submit the form </p>';
        } else {
            $out .= '<p> You have already submitted your information for a background check
            please visit the<a href="'.$bgCheckPageURL.'"> background check page </a> to view your status</p>';
        }
        return $out;
    }

    function sb_bg_check_order_status(){
        $nonce = !empty( $_POST['nonce']) ? $_POST['nonce']: null;
        
        if(!wp_verify_nonce($nonce, 'sb_bg_check_order_status')){
            die ( __('you have been DENIED', 'learnpress'));
        }
        $orderID = $_POST['id'];
        //$canID = get_user_meta(get_current_user_id(), STRIDER_BIKES_BGCHECK_ID_KEY);
        $url = 'https://api.accuratebackground.com/v3/order/'.$orderID;
        $key = get_option('sb_bg_check_abg_api_key');
        $secret = get_option('sb_bg_check_abg_api_secret');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                                                                                      
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     
        curl_setopt($ch, CURLOPT_USERPWD, $key.':'.$secret);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
            'Accept: application/json',
            'Content-Type: application/json')                                                           
        ); 
        $result = curl_exec($ch);
        $result = json_decode($result);
        wp_send_json($result);
        wp_die();
        return '<h1>STATUS: '.$result->status.'</h1>';
    }

    function sb_bg_check_make_order(){
        $bgID = $_POST['id'];
        $userID = get_current_user_id();
        //$userBGID = get_user_meta($userID, STRIDER_BIKES_BGCHECK_ID_KEY);
        $key = get_option('sb_bg_check_abg_api_key');
        $secret = get_option('sb_bg_check_abg_api_secret');
        $url = 'https://api.accuratebackground.com/v3/order/';
        $datada = array(
            'candidateId' => $bgID,
            'packageType' => 'PKG_BASIC',
            'workflow' =>   'EXPRESS',
            'jobLocation' => array(
            'city' => get_user_meta($userID, 'sb_bg_check_city')[0],
            'region' => get_user_meta($userID, 'sb_bg_check_region')[0],
            'country' => get_user_meta($userID, 'sb_bg_check_country')[0], 
            )
        );
        $data_string = json_encode($datada);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
        curl_setopt($ch, CURLOPT_POST, true);                                                                   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     
        curl_setopt($ch, CURLOPT_USERPWD, $key.':'.$secret);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
            'Accept: application/json',
            'Content-Type: application/json')                                                           
        ); 
        $result = curl_exec($ch);
        $result = json_decode($result);
        $orderID = $result->id;
        update_user_meta(get_current_user_id(), 'sb_bg_check_canidate_order_id', $orderID);
        wp_send_json($result);
        wp_die();
    }

    function sb_bg_check_update_userInfo(){
        $userID = get_current_user_id();
        $url = 'https://api.accuratebackground.com/v3/candidate/';
        $nonce = !empty( $_POST['nonce']) ? $_POST['nonce']: null;
        
        if(!wp_verify_nonce($nonce, 'sb_bg_check_update_userInfo')){
            die ( __('you have been DENIED', 'learnpress'));
        }
        $data = array(
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'country' => $_POST['country'],
            'dateOfBirth' => $_POST['dateOfBirth'],
            'email' => $_POST['email'],
            'firstName' => $_POST['firstName'],
            'lastName' => $_POST['lastName'],
            'phone' => $_POST['phone'],
            'postalCode' => $_POST['postalCode'],
            'region' => $_POST['region'],
            'ssn' => $_POST['ssn']
        );
        foreach($data as $key => $value){
            update_user_meta($userID, 'sb_bg_check_'.$key, $value);
        }
        $data_string = json_encode($data);
        $key = get_option('sb_bg_check_abg_api_key');
        $secret = get_option('sb_bg_check_abg_api_secret');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
        curl_setopt($ch, CURLOPT_POST, true);                                                                   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     
        curl_setopt($ch, CURLOPT_USERPWD, $key.':'.$secret);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
            'Accept: application/json',
            'Content-Type: application/json')                                                           
        ); 
        $result = curl_exec($ch);
        $result = json_decode($result);
        $canID = $result->id;
        update_user_meta($userID, STRIDER_BIKES_BGCHECK_ID_KEY, $canID);
        curl_close($ch);
        wp_send_json($result);
        wp_die();
    }

    function sb_bg_pre_gravity_form(){
        $userID = get_current_user_id();
        $metaUser = get_userData($user);
        $url = 'https://api.accuratebackground.com/v3/candidate/';
        $data = array(
            'address' => $_POST['input_5.1'],
            'city' => $_POST['input_5.3'],
            'country' => $_POST['input_5.6'],
            'dateOfBirth' => $_POST['input_1'],
            'email' => $metaUser->user_email,
            'firstName' => $metaUser->first_name,
            'lastName' => $metaUser->last_name,
            'phone' => $_POST['input_2'],
            'postalCode' => $_POST['input_5.5'],
            'region' => $_POST['input_5.4'],
            'ssn' => $_POST['input_3']
        );
        foreach($data as $key => $value){
            update_user_meta($userID, 'sb_bg_check_'.$key, $value);
        }
        $data_string = json_encode($data);
        $key = get_option('sb_bg_check_abg_api_key');
        $secret = get_option('sb_bg_check_abg_api_secret');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
        curl_setopt($ch, CURLOPT_POST, true);                                                                   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     
        curl_setopt($ch, CURLOPT_USERPWD, $key.':'.$secret);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
            'Accept: application/json',
            'Content-Type: application/json')                                                           
        ); 
        $result = curl_exec($ch);
        $result = json_decode($result);
        $canID = $result->id;
        update_user_meta($userID, STRIDER_BIKES_BGCHECK_ID_KEY, $canID);
        curl_close($ch);
        $this->sb_bg_check_make_order_grav_forms($canID);
        wp_die();
    }

    function sb_bg_check_make_order_grav_forms($canID){
        $userID = get_current_user_id();
        $key = get_option('sb_bg_check_abg_api_key');
        $secret = get_option('sb_bg_check_abg_api_secret');
        $url = 'https://api.accuratebackground.com/v3/order/';
        $datada = array(
            'candidateId' => $canID,
            'packageType' => 'PKG_BASIC',
            'workflow' =>   'INTERACTIVE',
            'jobLocation' => array(
            'city' => get_user_meta($userID, 'sb_bg_check_city'),
            'region' => get_user_meta($userID, 'sb_bg_check_region'),
            'country' => 'US'
            )
        );
        $data_string = json_encode($datada);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
        curl_setopt($ch, CURLOPT_POST, true);                                                                   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     
        curl_setopt($ch, CURLOPT_USERPWD, $key.':'.$secret);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
            'Accept: application/json',
            'Content-Type: application/json')                                                           
        ); 
        $result = curl_exec($ch);
        $result = json_decode($result);
        $orderID = $result->id;
        update_user_meta(get_current_user_id(), 'sb_bg_check_canidate_order_id', $orderID);
        wp_die();
    }

    function sb_bg_scripts(){
        wp_enqueue_script( 'sb-background-check-ajax-script', untrailingslashit( plugins_url( '/', STRIDER_BIKES_BGCHECK_FILE ) )  . '/assets/sbbgCheck.js' , array( 'jquery' ) );
    }
    
    function sb_bg_update_value($user_id){
        if(current_user_can('edit_user', $user_id)){
            update_user_meta($user_id, 'user_bg_check_passed', $_POST['user_bg_check_passed_bool']);
        }
    }
    function backgroundCheckFormLoader($atts){
        ob_start();
        require_once($this->_plugin_template_path.'bgroundcheckSCForm.php');
        return ob_get_clean();
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
