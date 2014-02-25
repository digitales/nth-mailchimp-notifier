<div class="wrap">
	<?php screen_icon(); ?>
    <h2><?php _e( 'MailChimp notification settings' , 'nthmailchimp'); ?></h2>
    
    <p>From this page you can select the MailChimp list from your list that will be used to send the notifications each time a post is published.</p>
    
    <form action="">
        
        
    <h2>Choose the template to use</h2>
    
    <?php if ( isset( $template_list ) && !empty( $template_list ) ): ?>
    
        <?php if ( isset( $template_list['user'] ) && !empty( $template_list['user'] ) ): ?>
            <p>These are the <i>user</i> templates in your account.</p>
    
            <?php foreach( $template_list['user'] AS $template ): ?>
                <div class="box">
                    <img src="<?php echo $template['preview_image'];?>" />
                    <label for="template-<?php echo $template['id']?>">
                        <input type="radio" id="template-<?php echo $template['id']?>" name="template_id" value="<?php echo $template['id']?>" <?php echo (isset( $template_id) && $template_id == $template['id'])? ' selected="selected" ' : '' ;?> />
                        <?php echo $template['name'];?>
                    </label>
                </div>
    
            <?php endforeach; ?>
        <?php else: ?>
            <p>No user defined templates found.</p>
        <?php endif; ?>
        
        
        <?php if ( isset( $template_list['gallery'] ) && !empty( $template_list['gallery'] ) ): ?>
            <p>These are the <i>gallery</i> templates in your account.</p>
    
            <?php foreach( $template_list['gallery'] AS $template ): ?>
                <div class="box">
                    <img src="<?php echo $template['preview_image'];?>" />
                    <label for="template-<?php echo $template['id']?>">
                        <input type="radio" id="template-<?php echo $template['id']?>" name="template_id" value="<?php echo $template['id']?>" <?php echo (isset( $template_id) && $template_id == $template['id'])? ' selected="selected" ' : '' ;?> />
                        <?php echo $template['name'];?>
                    </label>
                </div>
    
            <?php endforeach; ?>
        <?php else: ?>
            <p>No <i>gallery</i> templates found.</p>
        <?php endif; ?>
        
        
        <?php if ( isset( $template_list['base'] ) && !empty( $template_list['base'] ) ): ?>
            <p>These are the <i>base</i> templates in your account.</p>
    
            <?php foreach( $template_list['base'] AS $template ): ?>
                <div class="box">
                    <img src="<?php echo $template['preview_image'];?>" />
                    <label for="template-<?php echo $template['id']?>">
                        <input type="radio" id="template-<?php echo $template['id']?>" name="template_id" value="<?php echo $template['id']?>" <?php echo (isset( $template_id) && $template_id == $template['id'])? ' selected="selected" ' : '' ;?> />
                        <?php echo $template['name'];?>
                    </label>
                </div>
    
            <?php endforeach; ?>
        <?php else: ?>
            <p>No <i>base</i> templates found.</p>
        <?php endif; ?>
    <?php endif; ?>
    
    
    
    </form>
    
	
</div>
