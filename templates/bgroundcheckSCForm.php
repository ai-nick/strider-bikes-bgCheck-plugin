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
 $metaUser = get_userData($user);
 ?>

<?php if ($userOrderID[0] > 0) : ?>
<div>
<h4> You have already filled out the preliminary information for your background check, if you would like to check your status click the button below </h4>
<button class="sb-bg-order-check" data-id="<?php echo $userOrderID[0]; ?>" data-url="<?php echo admin_url( 'admin-ajax.php' );?>"
data-nonce="<?php echo wp_create_nonce('sb_bg_check_order_status'); ?>">Check Status</button>
<br><br>
</div>
<?php else: ?>

<form class="form-horizontal" method="post">
  <div class="form-group">
    <label class="control-label col-sm-2" for="bg_bdate">Date of Birth</label>
    <div class="col-sm-10">
      <input type="date" class="form-control" id="bg_bdate" name="bg_bdate">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="bg_phone">Phone Number</label>
      <div class="col-sm-10">
        <input type="tel" class="form-control" id="bg_phone" name="bg_phone">
      </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="bg_socialNum">Social Security Number</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="bg_socialNum" name="bg_socialNum" placeholder="Enter Social Security Number: XXX-XX-XXXX">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="bg_address">Current Address</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="bg_address" name="bg_address" placeholder="Enter home address">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="bg_city">City</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="bg_city" name="bg_city" placeholder="Enter City">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="bg_zipcode">Zipcode</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="bg_zipcode" name="bg_zipcode" placeholder="Enter you zipcode">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="bg_region">Region/State Abbreviation </label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="bg_region" name="bg_region" placeholder="SD for South Dakota, WA for Washington, etc.">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="bg_country">Country</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="bg_country" name="bg_country" placeholder="Enter your Country">
    </div>
  </div>
  </form>
    <div class="col-sm-offset-2 col-sm-10">
      <button class="btn btn-default sbbg-check-submit" name="nonce" data-nonce="<?php echo wp_create_nonce('sb_bg_check_update_userInfo') ?>"
      data-user="<?php echo $metaUser->ID; ?>" data-url="<?php echo admin_url( 'admin-ajax.php' );?>"
      data-fname="<?php echo $metaUser->first_name; ?>" data-lname="<?php echo $metaUser->last_name; ?>" 
      data-email="<?php echo $metaUser->user_email; ?>">Submit</button>
    </div>
<?php endif; ?>
 
<script>

</script>