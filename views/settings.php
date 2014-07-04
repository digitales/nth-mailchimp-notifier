<div class="wrap">
	<?php screen_icon(); ?>
    <h2><?php _e( 'MailChimp notification settings' , 'nthmailchimp'); ?></h2>

    <?php
    if ( isset( $message ) && $message ){
        echo '<div id="message" class="updated below-h2"><p>'.$message.'</p></div>';
	}

	if ( isset( $error ) && $error ){
        echo '<div id="error" class="error below-h2"><p>'.$error.'</p></div>';
	}
    ?>

    <p>From this page you can select the MailChimp list from your list that will be used to send the notifications each time a post is published.</p>

    <form method="post" action="" enctype="multipart/form-data" class="nth-mailchimp-notifier">
        <?php wp_nonce_field( 'mailchimp-notifications', '_wpnonce-mailchimp-notifications' ); ?>

        <h2>Choose the template to use</h2>

        <?php if ( isset( $template_list ) && !empty( $template_list ) ): ?>

            <?php if ( isset( $template_list['user'] ) && !empty( $template_list['user'] ) ): ?>
                <p>These are the <i>user</i> templates in your account.</p>
                <div class="templates">
                <?php foreach( $template_list['user'] AS $template ): ?>
                    <div class="box">
                        <label for="template-<?php echo $template['id']?>">
                            <input type="radio" id="template-<?php echo $template['id']?>" name="template_id" value="<?php echo $template['id']?>" <?php echo (isset( $template_id) && $template_id == $template['id'])? ' checked="checked" ' : '' ;?> />
                            <?php echo $template['name'];?>
                        </label>
												<img src="<?php echo $template['preview_image'];?>" />
                        <div class="clear"></div>

                    </div>

                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No user defined templates found.</p>
            <?php endif; ?>


            <?php if ( isset( $template_list['gallery'] ) && !empty( $template_list['gallery'] ) ): ?>
                <p>These are the <i>gallery</i> templates in your account.</p>

                <div class="templates">
                <?php foreach( $template_list['gallery'] AS $template ): ?>
                    <div class="box">
                        <img src="<?php echo $template['preview_image'];?>" />
                        <label for="template-<?php echo $template['id']?>">
                            <input type="radio" id="template-<?php echo $template['id']?>" name="template_id" value="<?php echo $template['id']?>" <?php echo (isset( $template_id) && $template_id == $template['id'])? ' checked="checked" ' : '' ;?> />
                            <?php echo $template['name'];?>
                        </label>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No <i>gallery</i> templates found.</p>
            <?php endif; ?>


            <?php if ( isset( $template_list['base'] ) && !empty( $template_list['base'] ) ): ?>
                <p>These are the <i>base</i> templates in your account.</p>
                <div class="templates">
                <?php foreach( $template_list['base'] AS $template ): ?>
                    <div class="box">
                        <img src="<?php echo $template['preview_image'];?>" />
                        <label for="template-<?php echo $template['id']?>">
                            <input type="radio" id="template-<?php echo $template['id']?>" name="template_id" value="<?php echo $template['id']?>" <?php echo (isset( $template_id) && $template_id == $template['id'])? ' checked="checked" ' : '' ;?> />
                            <?php echo $template['name'];?>
                        </label>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No <i>base</i> templates found.</p>
            <?php endif; ?>
        <?php endif; ?>

        <h2>Choose the list to use</h2>

        <?php if( isset( $list_list['data'] ) ):?>
            <label for="list_id">MailChimp list:</label>

            <select name="list_id" id="list_id">
                <option>Please select ...</option>
                <?php foreach( $list_list['data'] AS $list ):?>
                    <option value="<?php echo $list['id'];?>" <?php echo ( isset( $list_id ) && $list_id == $list['id'] )? ' selected="selected" ' : ''; ?> ><?php echo $list['name'];?></option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <p>Sorry, we couldn't find any lists to use.</p>

        <?php endif; ?>


        <h2>Choose the segment of the list to use</h2>

        <?php if( isset( $segment_list ) ):?>

            <label for="segment_id">MailChimp list segment:</label>

            <select name="segment_id" id="segment_id">
                <option>Please select ...</option>
                <option value="none" <?php echo ( isset( $segment_id ) && $segment_id == 'none' )? ' selected="selected" ' : '' ;?>>None</option>

                <optgroup label="--Static segments--">
                    <?php if( isset( $segment_list['static'] ) ): ?>
                        <?php foreach( $segment_list['static'] AS $segment ): ?>
                            <option value="<?php echo $segment['id'];?>" <?php echo ( isset( $segment_id ) && $segment_id == $segment['id'] )? ' selected="selected" ' : ''; ?> ><?php echo $segment['name'];?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </optgroup>

                <optgroup label="--Saved segments--">
                    <?php if( isset( $segment_list['saved'] ) ): ?>
                        <?php foreach( $segment_list['saved'] AS $segment ): ?>
                            <option value="<?php echo $segment['id'];?>" <?php echo ( isset( $segment_id ) && $segment_id == $segment['id'] )? ' selected="selected" ' : ''; ?> ><?php echo $segment['name'];?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </optgroup>

                <?php foreach( $segment_list['static'] AS $segment ):?>

                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <p>Sorry, we couldn't find any segments, please select a different list.</p>

        <?php endif; ?>


	   <hr />


	   <h2>Email settings</h2>
        <p>
            <label for="enabled">
                <input type="checkbox" id="enabled" name="enabled" value="yes" <?php echo ( isset( $enabled ) && 1 == $enabled )? ' checked="checked" ' : '' ;?>/> Enable emails
            </label>
        </p>

        <p>
            <label for="test_mode">
                <input type="checkbox" id="test_mode" name="test_mode" value="yes" <?php echo ( isset( $test_mode ) && 1 == $test_mode )? ' checked="checked" ' : '' ;?>/> Test mode
            </label>
        </p>

        <p>
            <label for="test_emails">Test emails address:</label>
            <input type="text" class="widefat" id="test_emails" name="test_emails" value="<?php echo ( isset( $test_emails ) && !empty( $test_emails ) )? $test_emails : '' ;?>"/>
        </p>

		<p>
            <label for="email_subject">Subject line:</label>
            <input type="text" class="widefat" id="email_subject" name="email_subject" value="<?php echo ( isset( $email_subject ) && !empty( $email_subject ) )? $email_subject : '' ;?>"/>
        </p>

		<p>
            <label for="email_content">Email body (HTML):</label>
            <textarea class="widefat" id="email_content" name="email_content" rows="15"><?php echo ( isset( $email_content ) && !empty( $email_content ) )? $email_content : '' ;?></textarea>
        </p>

		<p>
            <label for="email_text">Email body (TXT):</label>
            <textarea class="widefat" id="email_text" name="email_text" rows="15"><?php echo ( isset( $email_text ) && !empty( $email_text ) )? $email_text : '' ;?></textarea>
        </p>


	<p>Merge tags:</p>
	<ul>
		<li><em>*|EMAIL|*</em> Email Address</li>
		<li><em>*|FNAME|*</em> First Name</li>
		<li><em>*|LNAME|*</em> Last Name</li>
		<li>The following tags will be merged before sending the content to MailChimp.</li>
		<li><em>*|P-TITLE|*</em> The post title.</li>
		<li><em>*|P-URL|*</em> The URL for the post.</li>
		<li><em>*|P-DATE|*</em> The publish date of the post item. For example, *|DATE:d/m/y|* where d is replaced by the day, m by the month, and y by the year. View a full reference of date options on the PHP website (<a href="http://us3.php.net/manual/en/function.date.php">http://us3.php.net/manual/en/function.date.php</a>). <br />
You can also include optional date formatting. </li>
		<li><em>*|P-AUTHOR|*</em> The name of the post author</li>
		<li><em>*|P-CATEGORIES|*</em> A comma-separated list of the categories (tags and "posted in").</li>
		<li><em>*|P-EXCERPT|*</em> The post excerpt.</li>
		<li><em>*|P-CONTENT|*</em> The full blog post content.</li>


	</ul>




        <p class="submit">
			<input type="submit" class="button-primary" value="<?php _e( 'Save settings' , 'nthmailchimp'); ?>" />
		</p>


    </form>

</div>
