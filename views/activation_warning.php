<div class="updated">
	<p>
        <?php echo sprintf( __('To make full use of the Nth MailChimp Notifier plugin on your site, please <a href="%s" class="button  button-primary">Link it with your MailChimp account</a> or <a href="%s" class="button  button-primary">Add an API key</a>', 'nthmailchimp'), add_query_arg(array("nthmc_action" => "authorise"), home_url('index.php')), add_query_arg(array("page" => "nth-mailchimp-api"), admin_url('options.php')) );?>
	<p/>
</div>