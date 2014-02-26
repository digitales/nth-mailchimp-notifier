<div class="wrap">
	<?php screen_icon(); ?>
    <h2><?php _e( 'MailChimp API details' , 'nthmailchimp'); ?></h2>
    
    <form method="post" action="options.php">
        <?php
       // This prints out all hidden setting fields
        settings_fields( 'api_group' );   
        do_settings_sections( 'api-admin' );
        submit_button(); 
    
        ?>
    </form>
	
	<p><a href="<?php add_query_arg(array("page" => "nth-mailchimp-settings"), admin_url('options-general.php'));?> wp-admin/options-general.php?page=nth-mailchimp-settings" class="button">Back to settings</a></p>
</div>