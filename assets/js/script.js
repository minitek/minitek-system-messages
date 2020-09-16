;(function($) {
	$(function(){

		var $close_all = window.miniteksystemmessages.close_all;
		var $group_messages = window.miniteksystemmessages.group_messages;
		var $joomla_container = window.miniteksystemmessages.joomla_container;
		var $application_messages = window.miniteksystemmessages.application_messages;
		var $validation_messages = window.miniteksystemmessages.validation_messages;
		var $error_text = window.miniteksystemmessages.error_text;
		var $message_text = window.miniteksystemmessages.message_text;
		var $notice_text = window.miniteksystemmessages.notice_text;
		var $warning_text = window.miniteksystemmessages.warning_text;
		
		// mGrowl options
		var $mg_position = window.miniteksystemmessages.mg_position;
		var $mg_theme = 'msm-' + window.miniteksystemmessages.mg_theme;
		var $mg_glue = window.miniteksystemmessages.mg_glue;
		var $mg_pool = parseInt(window.miniteksystemmessages.mg_pool, 10);
		var $mg_sticky = window.miniteksystemmessages.mg_sticky;
		var $mg_life = parseInt(window.miniteksystemmessages.mg_life, 10);
		var $mg_closer = window.miniteksystemmessages.mg_closer;
		var $mg_closerTemplate = '<div>[' + $close_all + ']<\/div>';
		var $mg_closeDuration = parseInt(window.miniteksystemmessages.mg_closeDuration, 10);
		var $mg_openDuration = parseInt(window.miniteksystemmessages.mg_openDuration, 10);

		// Assign default options values
		$.mGrowl.defaults.theme = $mg_theme;
		$.mGrowl.defaults.pool = $mg_pool;
		$.mGrowl.defaults.closer = $mg_closer;
		$.mGrowl.defaults.closerTemplate = $mg_closerTemplate;

		// Creates new message
		function createMessage(text, type, sticky)
		{
			$header = $notice_text;
			$type = 'msm-alert msm-notice';

			if (type == 'error' || type == 'danger' || type == 'error alert-danger')
			{
				$header = '<i class=\"fa fa-times-circle\"><\/i> ';
				$header = $header + '<span class=\"msm-title\">' + $error_text + '<\/span>';
				$type = 'msm-alert msm-error';
			}
			else if (type == 'message' || type == 'success')
			{
				$header = '<i class=\"fa fa-check-circle\"><\/i> ';
				$header = $header + '<span class=\"msm-title\">' + $message_text + '<\/span>';
				$type = 'msm-alert msm-message';
			}
			else if (type == 'warning')
			{
				$header = '<i class=\"fa fa-exclamation-circle\"><\/i> ';
				$header = $header + '<span class=\"msm-title\">' + $warning_text + '<\/span>';
				$type = 'msm-alert msm-warning';
			}
			else
			{
				$header = '<i class=\"fa fa-info-circle\"><\/i> ';
				$header = $header + '<span class=\"msm-title\">' + $notice_text + '<\/span>';
				$type = 'msm-alert msm-notice';
			}

			if ($mg_sticky == 0 && sticky != true)
			{
				var sticky = false;
			}
			else
			{
				var sticky = true;
			}

			var popup = $.mGrowl(text, {
				position: $mg_position,
				theme: $mg_theme,
				messageType: $type,
				header: $header,
				glue: $mg_glue,
				pool: $mg_pool,
				sticky: sticky,
				life: $mg_life,
				closeDuration: $mg_closeDuration,
				openDuration: $mg_openDuration,
			});
		}

		// Display application messages
		if ($application_messages != false)
		{
			// Hide and empty Joomla system messages container
			$('#'+$joomla_container).hide().empty();

			var application_messages = JSON.parse($application_messages);

			application_messages.forEach(function(element)
			{
				createMessage(element.message, element.type);
			});
		}

		// Create an observer instance - Observes the system-message-container for newly added messages
		function observeContainer(mode)
		{
			var system_container = $('#'+$joomla_container)[0];

			var observer = new MutationObserver(function( mutations )
			{
				mutations.forEach(function( mutation )
				{
					var newNodes = mutation.addedNodes; // DOM NodeList

					// Τhere are new nodes added
					if (newNodes !== null) 
					{
						// Validation messages enabled - create messages
						if (mode == 'create')
						{
							var $nodes = $(newNodes);
							$nodes.each(function()
							{
								var $node = $(this);

								if ($node.attr('role') == 'alert')
								{
									var $node_type = $node.attr('type');
									var $children_div = $node.children('div'); // Get children div

									// Group children
									if ($group_messages == true)
									{
										var $merged_messages = '';
										$children_div.each(function()
										{
											var $child_div = $(this);
											$merged_messages += $child_div[0].innerHTML +'<br>'; // Get div text
										});
										createMessage($merged_messages, $node_type);
									}
									else
									{
										$children_div.each(function()
										{
											var $child_div = $(this);
											$div_content = $child_div[0].innerHTML; // Get div text
											createMessage($div_content, $node_type);
										});
									}

									// Hide and empty Joomla system messages container
									$('#'+$joomla_container).hide().empty();
								}
							});
						}
						// Validation messages disabled - show container
						else if (mode == 'observe')
						{
							$('#'+$joomla_container).show();
						}
					}
				});
			});

			// Configuration of the observer:
			var config = {
				attributes: true,
				childList: true,
				characterData: true
			};

			// Pass in the target node, as well as the observer options
			observer.observe(system_container, config);
		}
		
		// Form validation messages
		if ($validation_messages != false && $('#'+$joomla_container).length > 0)
		{
			observeContainer('create');
		}
		else if ($application_messages != false && $validation_messages == false)
		{			
			observeContainer('observe');
		}

	})
})(jQuery);
