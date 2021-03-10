<h1>S Job Scraper</h1>

<input id="sjs-nonce" type="hidden" value="<?php echo wp_create_nonce("sjs_run_scraper"); ?>" />
<a id="sjs-run-scraper" href="#">Run Scraper</a>

<br/>

<span id="sjs-msg"></span>

<script type="text/javascript">
(function ($) {
  'use strict';

  $(document).ready(function () {
  	$('#sjs-run-scraper').click(function() {
  		$('#sjs-msg').text("Scraper Executing...");
  		jQuery.ajax({
		    type: "post",
		    dataType: "json",
		    url: '<?php echo esc_url( admin_url( 'admin-ajax.php?' ) ); ?>',
		    data : {
		      'action': 'sjs_run_scraper',
		      'nonce': $('#sjs-nonce').attr('value')
		    },
		    success: function(response) {
		      $('#sjs-msg').text("Scraper Executed.");
		    }
			});
  	});
  });

})(jQuery);

</script>