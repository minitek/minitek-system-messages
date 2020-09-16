/**
 * mGrowl 3.0.1
 *
 * jQuery plugin for creating unobtrusive popup messages.
 *
 * Based on jGrowl by Stan Lemon
 *
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 */

// Support for UMD style, based on UMDjs (https://github.com/umdjs/umd/blob/master/templates/jqueryPlugin.js)
;(function(factory)
{
  if (typeof define === 'function' && define.amd)
  {
    define(['jquery'], factory);
  }
  else if (typeof module === 'object' && module.exports)
  {
    module.exports = function(root, jQuery)
    {
      if (jQuery === undefined)
      {
        if (typeof window !== 'undefined')
        {
          jQuery = require('jquery');
        }
        else
        {
          jQuery = require('jquery')(root);
        }
      }
      factory(jQuery);
      return jQuery;
    };
  }
  else
  {
    factory(jQuery);
  }
}(function($)
{
	/** mGrowl Wrapper - Establish a base mGrowl Container for compatibility with older releases. **/
	$.mGrowl = function(m, o)
  {
		// To maintain compatibility with older version that only supported one instance we'll create the base container.
		if ($('#msmessages').length === 0)
    {
			$('<div id="msmessages"></div>')
        .addClass((o && o.position) ? o.position : $.mGrowl.defaults.position)
        .appendTo((o && o.appendTo) ? o.appendTo : $.mGrowl.defaults.appendTo);
    }

		// Create a notification on the container.
		$('#msmessages').mGrowl(m, o);
	};

	/** Raise mGrowl Notification on a mGrowl Container **/
	$.fn.mGrowl = function(m, o)
  {
		// Short hand for passing in just an object to this method
		if (o === undefined && $.isPlainObject(m))
    {
			o = m;
			m = o.message;
		}

		if ($.isFunction(this.each))
    {
			var args = arguments;

			return this.each(function()
      {
				/** Create a mGrowl Instance on the Container if it does not exist **/
				if ($(this).data('mGrowl.instance') === undefined)
        {
					$(this).data('mGrowl.instance', $.extend(new $.fn.mGrowl(), {
            notifications: [],
            element: null,
            interval: null
          }));
					$(this)
            .data('mGrowl.instance')
            .startup(this);
				}

				/** Optionally call mGrowl instance methods, or just raise a normal notification **/
				if ($.isFunction($(this).data('mGrowl.instance')[m]))
        {
					$(this)
            .data('mGrowl.instance')[m]
            .apply($(this).data('mGrowl.instance') , $.makeArray(args).slice(1));
				}
        else
        {
					$(this)
            .data('mGrowl.instance')
            .create(m, o);
				}
			});
		}
	};

	$.extend($.fn.mGrowl.prototype, {

		/** Default mGrowl Settings **/
		defaults: {
      position: 'top-right',
			theme: '',
			messageType: '',
      header: '',
      appendTo: 'body',
      glue: 'after',
			pool: 0,
      sticky: false,
      check: 250,
			life: 3000,
      easing: 'swing',
      closer: true,
      closeTemplate: '&times;',
			closerTemplate: '',
      closeDuration: 'normal',
			openDuration: 'normal',
			log: function() {},
			beforeOpen: function() {},
			afterOpen: function() {},
			open: function() {},
			beforeClose: function() {},
			close: function() {},
			click: function() {},
			animateOpen: {
				opacity: 'show'
			},
			animateClose: {
				opacity: 'hide'
			}
		},

		notifications: [],

		/** mGrowl Container Node **/
		element: null,

		/** Interval Function **/
		interval: null,

		/** Create a Notification **/
		create: function(message, options)
    {
			var o = $.extend({}, this.defaults, options);

			/* To keep backward compatibility with 1.24 and earlier, honor 'speed' if the user has set it */
			if (typeof o.speed !== 'undefined')
      {
				o.openDuration = o.speed;
				o.closeDuration = o.speed;
			}

			this.notifications.push({ message: message , options: o });

			o.log.apply(this.element, [this.element,message,o]);
		},

		render: function(n)
    {
			var self = this;
			var message = n.message;
			var o = n.options;

			var notification = $('<div/>')
				.addClass('mGrowl-notification ' + ((o.messageType !== undefined && o.messageType !== '') ? ' ' + o.messageType : ''))
				.append($('<button/>').addClass('mGrowl-close').html(o.closeTemplate))
				.append($('<div/>').addClass('mGrowl-header').html(o.header))
				.append($('<div/>').addClass('mGrowl-message').html(message))
				.data("mGrowl", o)
        .children('.mGrowl-close')
        .bind("click.mGrowl", function()
        {
					$(this).parent().trigger('mGrowl.beforeClose');
					return false;
				})
				.parent();

			/** Notification Actions **/
			$(notification)
        .bind("mouseover.mGrowl", function()
        {
  				$('.mGrowl-notification', self.element).data("mGrowl.pause", true);
  			})
        .bind("mouseout.mGrowl", function()
        {
  				$('.mGrowl-notification', self.element).data("mGrowl.pause", false);
  			})
        .bind('mGrowl.beforeOpen', function()
        {
  				if (o.beforeOpen.apply(notification, [notification,message,o,self.element]) !== false)
          {
  					$(this).trigger('mGrowl.open');
  				}
  			})
        .bind('mGrowl.open', function()
        {
  				if (o.open.apply(notification, [notification,message,o,self.element]) !== false)
          {
  					if (o.glue == 'after')
            {
  						$('.mGrowl-notification:last', self.element).after(notification);
  					}
            else
            {
  						$('.mGrowl-notification:first', self.element).before(notification);
  					}

  					$(this).animate(o.animateOpen, o.openDuration, o.easing, function()
            {
  						// Fixes some anti-aliasing issues with IE filters.
  						if ($.support.opacity === false)
              {
  							this.style.removeAttribute('filter');
              }

              // Happens when a notification is closing before it's open.
  						if ($(this).data("mGrowl") !== null && typeof $(this).data("mGrowl") !== 'undefined')
              {
  							$(this).data("mGrowl").created = new Date();
              }

  						$(this).trigger('mGrowl.afterOpen');
  					});
  				}
  			})
        .bind('mGrowl.afterOpen', function()
        {
  				o.afterOpen.apply(notification, [notification,message,o,self.element]);
  			})
        .bind('click', function()
        {
  				o.click.apply(notification, [notification,message,o,self.element]);
  			})
        .bind('mGrowl.beforeClose', function()
        {
  				if (o.beforeClose.apply(notification, [notification,message,o,self.element]) !== false)
          {
  					$(this).trigger('mGrowl.close');
          }
  			})
        .bind('mGrowl.close', function()
        {
  				// Pause the notification, lest during the course of animation another close event gets called.
  				$(this).data('mGrowl.pause', true);
  				$(this).animate(o.animateClose, o.closeDuration, o.easing, function()
          {
  					if ($.isFunction(o.close))
            {
  						if (o.close.apply(notification, [notification,message,o,self.element]) !== false)
              {
  							$(this).remove();
              }
  					}
            else
            {
  						$(this).remove();
  					}
  				});
  			})
        .trigger('mGrowl.beforeOpen');

			/** Add a Global Closer if more than one notification exists **/
      var append_closer_to = this.defaults.theme == 'msm-panel' ? '.msm-header' : self.element;

			if ($('.mGrowl-notification:parent', self.element).length > 1 &&
  			$('.mGrowl-closer', append_closer_to).length === 0 && this.defaults.closer !== 'false')
      {
        if (o.glue == 'after')
        {
          $(this.defaults.closerTemplate)
            .addClass('mGrowl-closer')
            .appendTo(append_closer_to)
            .animate(this.defaults.animateOpen, this.defaults.speed, this.defaults.easing)
            .bind("click.mGrowl", function()
            {
              if (self.defaults.theme == 'msm-panel')
              {
                $('#msmessages').children().trigger("mGrowl.beforeClose");
              }
              else
              {
                $(this).siblings().trigger("mGrowl.beforeClose");
              }

              if ($.isFunction(self.defaults.closer))
              {
                self.defaults.closer.apply($(this).parent()[0], [$(this).parent()[0]]);
              }
            });
        }
        else
        {
          $(this.defaults.closerTemplate)
            .addClass('mGrowl-closer')
            .prependTo(append_closer_to)
            .animate(this.defaults.animateOpen, this.defaults.speed, this.defaults.easing)
            .bind("click.mGrowl", function()
            {
              if (self.defaults.theme == 'msm-panel')
              {
                $('#msmessages').children().trigger("mGrowl.beforeClose");
              }
              else
              {
                $(this).siblings().trigger("mGrowl.beforeClose");
              }

              if ($.isFunction(self.defaults.closer))
              {
                self.defaults.closer.apply($(this).parent()[0], [$(this).parent()[0]]);
              }
            });
        }
			}
		},

		/** Update the mGrowl Container, removing old mGrowl notifications **/
		update: function()
    {
			$(this.element).find('.mGrowl-notification:parent').each(function()
      {
				if ($(this).data("mGrowl") !== undefined && $(this).data("mGrowl").created !== undefined &&
  				($(this).data("mGrowl").created.getTime() + parseInt($(this).data("mGrowl").life, 10)) < (new Date()).getTime() &&
  				$(this).data("mGrowl").sticky !== true &&
  				($(this).data("mGrowl.pause") === undefined || $(this).data("mGrowl.pause") !== true))
        {
					// Pause the notification, lest during the course of animation another close event gets called.
					$(this).trigger('mGrowl.beforeClose');
				}
			});

			if (this.notifications.length > 0 &&
				(this.defaults.pool === 0 || $(this.element).find('.mGrowl-notification:parent').length < this.defaults.pool))
      {
        this.render(this.notifications.shift());
      }

			if ($(this.element).find('.mGrowl-notification:parent').length < 2)
      {
				$('.mGrowl')
          .find('.mGrowl-closer')
          .animate(this.defaults.animateClose, this.defaults.speed, this.defaults.easing, function()
          {
            $(this).remove();
          });
			}
		},

		/** Setup the mGrowl Notification Container **/
		startup: function(e)
    {
      var parentClass = this.defaults.theme != 'msm-panel' ? 'mGrowl' : '';

			this.element = $(e)
        .addClass(parentClass)
        .addClass(this.defaults.theme)
        .append('<div class="mGrowl-notification"></div>');
			this.interval = setInterval(function()
      {
				var instance = $(e).data('mGrowl.instance');
				if (undefined !== instance)
        {
					try
          {
						instance.update();
					}
          catch (e)
          {
						instance.shutdown();
						throw e;
					}
				}
			}, parseInt(this.defaults.check, 10));
		},

		/** Shutdown mGrowl, removing it and clearing the interval **/
		shutdown: function()
    {
	    try
      {
    		$(this.element)
          .removeClass('mGrowl')
    	    .find('.mGrowl-notification')
          .trigger('mGrowl.close')
    	    .parent()
          .empty();
	    }
      catch (e)
      {
        throw e;
      }
      finally
      {
        clearInterval(this.interval);
      }
		},

		close: function()
    {
			$(this.element).find('.mGrowl-notification').each(function()
      {
				$(this).trigger('mGrowl.beforeClose');
			});
		}
	});

	/** Reference the Defaults Object for compatibility with older versions of mGrowl **/
	$.mGrowl.defaults = $.fn.mGrowl.prototype.defaults;

}));
