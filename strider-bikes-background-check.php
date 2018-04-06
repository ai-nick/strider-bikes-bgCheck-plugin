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
if(! defined( 'STRIDER_BIKES_BGCHECK_ORDER_KEY' ) ) define('STRIDER_BIKES_BGCHECK_ORDER_KEY', 'sb_bg_check_canidate_order_id' );




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
    private $_grav_id = 0;



    function __construct(){
        //$this->_post_type = 'lp_unlock_oncomplete_cpt';
        $this->_tab_slug = sanitize_title( 'sb-background-check' );
        $this->_grav_id = get_option('sb_bg_check_abg_grav_ID');
        $this->_plugin_template_path = STRIDER_BIKES_BGCHECK_PATH.'/templates/';
        $this->_plugin_url  = untrailingslashit( plugins_url( '/', STRIDER_BIKES_BGCHECK_FILE ));

        add_action( 'load-post.php', array( $this, 'sb_bg_add_meta_boxes' ), 0 );
        add_action( 'load-post-new.php', array( $this, 'sb_bg_add_meta_boxes' ), 0 );
        add_action('admin_menu', array($this, 'sb_bg_check_create_menu'));
        add_action('gform_pre_submission_'.$this->_grav_id, array($this, 'sb_bg_pre_gravity_form'));
        add_action('gform_after_submission_5', array($this, 'sb_bg_check_make_order_grav_forms'));
        add_action('admin_enqueue_scripts', array($this, 'sb_bg_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'sb_bg_scripts'));
        add_action('show_user_profile', array($this, 'sb_bg_bool_profile'));
        add_action('edit_user_profile', array($this, 'sb_bg_bool_profile'));
        add_action('profile_update', array($this, 'sb_bg_update_value'),20,1);
        add_shortcode('sb_instructor_backgroundCheck_form', array($this, 'backgroundCheckFormLoader'));
        add_shortcode('sb_instructor_backgroundCheck_check_status', array($this, 'sb_bg_check_status_shortcode'));
        //LP_Request_Handler::register_ajax('sb_bg_check_update_userInfo', array($this, 'sb_bg_check_update_userInfo'));
        add_action('wp_ajax_check_make_order', array($this, 'sb_bg_check_make_order'));
        add_action('wp_ajax_check_update_userInfo', array($this, 'sb_bg_check_update_userInfo'));
        add_action('wp_ajax_check_order_status', array($this, 'sb_bg_check_order_status'));
        add_action('wp', array($this, 'restrict_until_complete_maybe'));
        add_action('wp', array($this, 'add_menu_filter'));
    }
    // hooks into the menu settup proccess prior to rendering, allows us to restrict and remove pages
    function add_menu_filter(){
        add_filter('nav_menu_link_attributes', array($this,'sb_bg_hide_appropriate_nav_links'), 10, 3);
    }
    // hides pages from users who have been locked out 
    function sb_bg_hide_appropriate_nav_links($atts, $item, $args){
        if( $args->menu == 'primary' ){
            $id = $item->object_id;
            $itemUnlocked = $this->lp_unlock_check_ze_page($id);
            if (!$itemUnlocked){
                $atts['style'] = 'display: none;';
            }
        }
        return $atts;
    }

   // add meta box to set page as locked until background check is complete
    public function sb_bg_add_meta_boxes() {
        $prefix                                        = '_lp_';
        new RW_Meta_Box(
            apply_filters( 'sb_bg_lock_page_until_bgCheck', array(
                    'title'      => 'BackGround Check Page Lock',
                    'post_types' => 'page',
                    'context'    => 'normal',
                    'priority'   => 'high',
                    'fields'     => array(
                        array(
                            'name'        => 'Locked (checked = locked, unchecked = not locked)',
                            'id'          => "sb_bg_lock_until_passed_check",
                            'type'        => 'checkbox',
                            'description' => __('Do you want to block this page from user who havent passed a Background Check', 'sbbgCheck'),
                            'std'         => 0
                        )
                )
            )
        )
        );
        }
    // calls function to check if page should be locked or not for the current user, if it should be it redirects the user to the wordpress
    // site root url
    function restrict_until_complete_maybe(){
            global $wp_query;
            $pID = $wp_query->get_queried_object_id();
            $unlocked = $this->lp_unlock_check_ze_page($pID);
            if (!$unlocked){
                wp_redirect(get_site_url());
                exit;
            }
        }
    // checks to see if A) the page is supposed to lock out users B) if the user has passed the bg check, if so it returns true, if
    // not it returns false
    function lp_unlock_check_ze_page($cPageId){
            $cUser = learn_press_get_current_user();
            $lockVar = get_post_meta($cPageId, 'sb_bg_lock_until_passed_check', true);
            $isUnlocked = true;
            if($lockVar<1){
                return $isUnlocked;
            } else {
                $uID = $cUser->ID;
                $bgStatus = get_user_meta($uID, 'user_bg_check_passed', true);
                if ($bgStatus == 0){
                    $isUnlocked = false;
                }
            }
            return $isUnlocked;
        }

    function sb_bg_check_create_menu() {
    
        //create new top-level menu
        add_menu_page('Strider Bikes Bg Check Settings', 'Background Check Settings', 'administrator',__FILE__, array($this, 'sb_bg_check_settings_page') );
        // add canidates page to plugin menu
        add_submenu_page(__FILE__,'Strider Bikes Bg Check Canidates', 'Background Check Candidates', 'administrator','sbbgCheckCanidates', array($this, 'sb_bg_check_candidates_admin_page'));
        //call register settings function
        add_action( 'admin_init', array($this, 'register_sb_bg_check_settings') );
    }
    // canidates admin page
    function sb_bg_check_candidates_admin_page(){
        $out = '<div class="wrap">';
        $users = get_users();
        foreach($users as $i){
            $orderID = get_user_meta($i->ID,'sb_bg_check_canidate_order_id');
            if($orderID[0]>0){
                $out .= '<p>'.$i->display_name.'</p><p>'.$i->user_email.'</p>';
                $out .= '<div><button class="sb-bg-order-check-admin" data-url="'.admin_url( 'admin-ajax.php' ).'"
                        data-id="'.$orderID[0].'" data-nonce="'.wp_create_nonce('sb_bg_check_order_status').'"> Check Status
                        </button></div>';
                $out .= '<div> <a href="https://www.striderbikes.com/_education/wp-admin/user-edit.php?user_id='.$i->ID.'"><p>edit user</p></a> </div>';          
            }
        }
        $out .= '</div>';
        echo $out;
    }
    
    function register_sb_bg_check_settings() {
        //register our settings
        register_setting( 'sb-bg-check-settings-group', 'sb_bg_check_abg_api_key' );
        register_setting( 'sb-bg-check-settings-group', 'sb_bg_check_abg_api_secret' );
        register_setting( 'sb-bg-check-settings-group', 'sb_bg_check_abg_api_baseurl' );
        register_setting( 'sb-bg-check-settings-group', 'sb_bg_check_abg_admin_email' );
        register_setting( 'sb-bg-check-settings-group', 'sb_bg_check_abg_grav_ID' );
    }
    // lays out settings page
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

            <tr valign="top">
            <th scope="row">BackGround Check OnSubmit Email</th>
            <td><input type="text" name="sb_bg_check_abg_admin_email" value="<?php echo esc_attr( get_option('sb_bg_check_abg_admin_email') ); ?>" /></td>
            </tr>

            <tr valign="top">
            <th scope="row">BackGround Check Gravity Form ID Number</th>
            <td><input type="number" name="sb_bg_check_abg_grav_ID" value="<?php echo esc_attr( get_option('sb_bg_check_abg_grav_ID') ); ?>" /></td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    
    </form>
    </div>
    <?php } 
    // function that controls the first conditional of our shortcode
    function sb_bg_check_status_shortcode(){
        $cUserID = get_current_user_id();
        if (!$cUserID){
            return;
        }
        $userBGCheck = get_user_meta($cUserID, STRIDER_BIKES_BGCHECK_ORDER_KEY, true);
        $bgCheckPageURL = get_option('sb_bg_check_abg_api_baseurl');
        $out = '<div class="container-fluid">';
        if (sizeof($userBGCheck)<1){
            $out .= '<p> You have not submitted your information for 
            a background check yet, please visit the <a href="'.$bgCheckPageURL.'"> background check page </a>to fill out and 
            submit the form </p>';
            return $out;
        } else {
            ob_start();
            require_once($this->_plugin_template_path.'sbbgCheckStatus.php');
            return ob_get_clean();
        }
    }
    // this call accurate bg api with the users ID then shows the candidates status in a js alert box
    function sb_bg_check_order_status(){
        $nonce = !empty( $_POST['nonce']) ? $_POST['nonce']: null;
        
        if(!wp_verify_nonce($nonce, 'sb_bg_check_order_status')){
            die ( __('you have been DENIED'));
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
        return ;
    }
    // use stored form data to call accurate background api to create the order, this is called from ajax after we send the 
    // the response from creating a new candidate
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
        $apiUrl = 'https://api.accuratebackground.com/v3/candidate/';
        $nonce = !empty( $_POST['nonce']) ? $_POST['nonce']: null;
        
        if(!wp_verify_nonce($nonce, 'sb_bg_check_update_userInfo')){
            die ( __('you have been DENIED', 'learnpress'));
        }
        $data = array(
            'address' => sanitize_text_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'country' => sanitize_text_field($_POST['country']),
            'dateOfBirth' => $_POST['dateOfBirth'],
            'email' => sanitize_email($_POST['email']),
            'firstName' => sanitize_text_field($_POST['firstName']),
            'lastName' => sanitize_text_field($_POST['lastName']),
            'phone' => $_POST['phone'],
            'postalCode' => intval($_POST['postalCode']),
            'region' => sanitize_text_field($_POST['region']),
            'ssn' => $_POST['ssn']
        );
        if (strlen($data['postalCode']) != 5 || !$data['postalcode']){
            $errorMsg = 'error invalid postal code';
            json_encode($errorMsg);
            wp_send_json($errorMsg);
            wp_die();
        }
        foreach($data as $key => $value){
            update_user_meta($userID, 'sb_bg_check_'.$key, $value);
        }
        $data_string = json_encode($data);
        $key = get_option('sb_bg_check_abg_api_key');
        $secret = get_option('sb_bg_check_abg_api_secret');
        $adminEmail = get_option('sb_bg_check_abg_admin_email');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$apiUrl);
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
        if($canID){
            $to = $adminEmail;
            $subject = 'A user has just submitted info for a background check';
            $body = 'the user '.$data['firstName'].' '.$data['lastName'].' has just submitted their initial background
            check form. ';
            $body .= '<h4>Info: </h4> <table>';
            foreach($result as $key=>$value){
                $body .= '<tr><td>'.json_encode($key).'</td><td>'.json_encode($value).'</td></tr>';
            }
            $body .= '</table>';
            $headers = array('Content-type: text/html; charset=UTF-8');
            wp_mail($to, $subject, $body, $headers);
        }
        update_user_meta($userID, STRIDER_BIKES_BGCHECK_ID_KEY, $canID);
        curl_close($ch);
        wp_send_json($result);
        wp_die();
    }

    function sb_bg_pre_gravity_form($form){
        $userID = get_current_user_id();
        $metaUser = get_userData($userID);
        $url = 'https://api.accuratebackground.com/v3/candidate/';
        $data = array(
            'address' => $_POST['input_5_1'],
            'city' => $_POST['input_5_3'],
            'country' => $_POST['input_6'],
            'dateOfBirth' => $_POST['input_1'],
            'email' => $metaUser->user_email,
            'firstName' => $metaUser->first_name,
            'lastName' => $metaUser->last_name,
            'phone' => $_POST['input_2'],
            'postalCode' => $_POST['input_5_5'],
            'region' => $_POST['input_5_4'],
            'ssn' => $_POST['input_4']
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
        //$this->sb_bg_check_make_order_grav_forms($canID);
        //wp_die();
    }

    function sb_bg_check_make_order_grav_forms(){
        $userID = get_current_user_id();
        $canID = get_user_meta($userID, STRIDER_BIKES_BGCHECK_ID_KEY)[0];
        $key = get_option('sb_bg_check_abg_api_key');
        $secret = get_option('sb_bg_check_abg_api_secret');
        $url = 'https://api.accuratebackground.com/v3/order/';
        $datada = array(
            'candidateId' => $canID,
            'packageType' => 'PKG_BASIC',
            'workflow' =>   'INTERACTIVE',
            'jobLocation' => array(
            'city' => get_user_meta($userID, 'sb_bg_check_city')[0],
            'region' => get_user_meta($userID, 'sb_bg_check_region')[0],
            'country' => get_user_meta($userID, 'sb_bg_check_country')[0]
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
        $adminEmail = get_option('sb_bg_check_abg_admin_email');
        $to = $adminEmail;
        $subject = 'A user has just submitted info for a background check';
        $body = 'the user '.$datada['candidateId'].' has just submitted their initial background
        check form. ';
        $body .= '<h4>Info: </h4> <table>';
        foreach($result as $key=>$value){
            $body .= '<tr><td>'.json_encode($key).'</td><td>'.json_encode($value).'</td></tr>';
        }
        $body .= '</table>';
        $headers = array('Content-type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $body, $headers);
        update_user_meta(get_current_user_id(), 'sb_bg_check_canidate_order_id', $orderID);
        //wp_die();
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
