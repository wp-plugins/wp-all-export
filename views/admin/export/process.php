<table class="layout pmxe_step_3">
	<tr>
		<td class="left">
			<h2><?php _e('Export XML - <span id="status">Exporting...</span>', 'pmxe_plugin') ?></h2>

			<hr />	
			<p id="process_notice"><?php _e('Exporting may take some time. Please do not close your browser or refresh the page until the process is complete.', 'pmxe_plugin') ?></p>			

		</td>
		<td class="right">
			&nbsp;
		</td>
	</tr>
</table>

<script type="text/javascript">
//<![CDATA[
(function($){
	$('#status').each(function () {
		var $this = $(this);
		if ($this.html().match(/\.{3}$/)) {
			var dots = 0;
			var status = $this.html().replace(/\.{3}$/, '');
			var interval ;						
			interval = setInterval(function () {				
				if ($this.html().match(new RegExp(status + '\\.{1,3}$', ''))) {									
					$this.html(status + '...'.substr(0, dots++ % 3 + 1));
				} else {											
					$('#process_notice').hide();									
					clearInterval(interval);					
				}
			}, 1000);			
		}
	});	

	var request = {
		action:'export',					
    };    

	$.ajax({
		type: 'POST',
		url: ajaxurl,
		data: request,
		success: function(response) {	
			$('#status').html('Complete');	
			window.onbeforeunload = false;	
			window.location.href = "<?php echo add_query_arg('action', 'download', $this->baseUrl); ?>";
		},
		dataType: "json"
	});

	window.onbeforeunload = function () {
		return 'WARNING:\nExport process in under way, leaving the page will interrupt\nthe operation and most likely to cause leftovers in posts.';
	};		
})(jQuery);

//]]>
</script>