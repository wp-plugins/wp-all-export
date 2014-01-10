/**
 * plugin admin area javascript
 */
(function($){$(function () {
	if ( ! $('body.pmxe_plugin').length) return; // do not execute any code if we are not on plugin page

	// fix layout position
	setTimeout(function () {
		$('table.layout').length && $('table.layout td.left h2:first-child').css('margin-top',  $('.wrap').offset().top - $('table.layout').offset().top);
	}, 10);	
	
	// help icons
	$('a.help').tipsy({
		gravity: function() {
			var ver = 'n';
			if ($(document).scrollTop() < $(this).offset().top - $('.tipsy').height() - 2) {
				ver = 's';
			}
			var hor = '';
			if ($(this).offset().left + $('.tipsy').width() < $(window).width() + $(document).scrollLeft()) {
				hor = 'w';
			} else if ($(this).offset().left - $('.tipsy').width() > $(document).scrollLeft()) {
				hor = 'e';
			}
	        return ver + hor;
	    },
		live: true,
		html: true,
		opacity: 1
	}).live('click', function () {
		return false;
	}).each(function () { // fix tipsy title for IE
		$(this).attr('original-title', $(this).attr('title'));
		$(this).removeAttr('title');
	});	
	
	// swither show/hide logic
	$('input.switcher').live('change', function (e) {	

		if ($(this).is(':radio:checked')) {
			$(this).parents('form').find('input.switcher:radio[name="' + $(this).attr('name') + '"]').not(this).change();
		}
		var $targets = $('.switcher-target-' + $(this).attr('id'));

		var is_show = $(this).is(':checked'); if ($(this).is('.switcher-reversed')) is_show = ! is_show;
		if (is_show) {
			$targets.fadeIn();
		} else {
			$targets.hide().find('.clear-on-switch').add($targets.filter('.clear-on-switch')).val('');
		}
	}).change();
	
	// autoselect input content on click
	$('input.selectable').live('click', function () {
		$(this).select();
	});
	
	// input tags with title
	$('input[title]').each(function () {
		var $this = $(this);
		$this.bind('focus', function () {
			if ('' == $(this).val() || $(this).val() == $(this).attr('title')) {
				$(this).removeClass('note').val('');
			}
		}).bind('blur', function () {
			if ('' == $(this).val() || $(this).val() == $(this).attr('title')) {
				$(this).addClass('note').val($(this).attr('title'));
			}
		}).blur();
		$this.parents('form').bind('submit', function () {
			if ($this.val() == $this.attr('title')) {
				$this.val('');
			}
		});
	});

	// datepicker
	$('input.datepicker').datepicker({
		dateFormat: 'yy-mm-dd',
		showOn: 'button',
		buttonText: '',
		constrainInput: false,
		showAnim: 'fadeIn',
		showOptions: 'fast'
	}).bind('change', function () {
		var selectedDate = $(this).val();
		var instance = $(this).data('datepicker');
		var date = null;
		if ('' != selectedDate) {
			try {
				date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
			} catch (e) {
				date = null;
			}
		}
		if ($(this).hasClass('range-from')) {
			$(this).parent().find('.datepicker.range-to').datepicker("option", "minDate", date);
		}
		if ($(this).hasClass('range-to')) {
			$(this).parent().find('.datepicker.range-from').datepicker("option", "maxDate", date);
		}
	}).change();
	$('.ui-datepicker').hide(); // fix: make sure datepicker doesn't break wordpress layout upon initialization 
	
	// no-enter-submit forms
	$('form.no-enter-submit').find('input,select,textarea').not('*[type="submit"]').keydown(function (e) {
		if (13 == e.keyCode) e.preventDefault();
	});

	// enter-submit form on step 1
	if ($('.pmxe_step_1').length){
		$('body').keydown(function (e) {
			if (13 == e.keyCode){
				$('form.choose-file').submit();
			}
		});
	}
	
	// choose file form: option selection dynamic
	// options form: highlight options of selected post type
	$('form.choose-post-type input[name="type"]').click(function() {		
		var $container = $(this).parents('.file-type-container');		
		$('.file-type-container').not($container).removeClass('selected').find('.file-type-options').hide();
		$container.addClass('selected').find('.file-type-options').show();
	}).filter(':checked').click();	
	
	// options form: add / remove custom params
	$('.form-table a.action[href="#add"]').live('click', function () {
		var $template = $(this).parents('table').first().find('tr.template');
		$template.clone(true).insertBefore($template).css('display', 'none').removeClass('template').fadeIn();
		return false;
	});
	
	$('.form-table .action.remove a').live('click', function () {
		$(this).parents('tr').first().remove();
		return false;
	});	
	
    $('.pmxe_choosen').each(function(){    	
    	$(this).find(".choosen_input").select2({tags: $(this).find('.choosen_values').html().split(',')});
    });
	
});})(jQuery);
