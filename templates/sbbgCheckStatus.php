<?php
/**
 * Template form that will be used to gather info to run bg checks
 * on users
 *
 * @author  Nick Williams
 * @version 1.0.0
 */

 defined('ABSPATH') || exit();
 header('Access-Control-Allow-Origin: *');


 $user = get_current_user_id();
 $userBGID = get_user_meta($user, STRIDER_BIKES_BGCHECK_ID_KEY);
 $userOrderID = get_user_meta($user,'sb_bg_check_canidate_order_id');
 ?>

<?php if (sizeof($userOrderID[0]) > 0) : ?>
<div>
<h4> You have already filled out the preliminary information for your background check, if you would like to check your status click the button below </h4>
<button class="sb-bg-order-check" data-id="<?php echo $userOrderID[0]; ?>" data-url="<?php echo admin_url( 'admin-ajax.php' );?>"
data-nonce="<?php echo wp_create_nonce('sb_bg_check_order_status'); ?>">Check Status</button>
<br><br>
</div>
<?php endif; ?>