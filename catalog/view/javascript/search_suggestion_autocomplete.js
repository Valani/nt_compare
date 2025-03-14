// Search suggestion autocomplete */
(function($) {
	$.fn.ss_autocomplete = function(option) {
		return this.each(function() {
			this.timer = null;
			this.items = new Array();

			$.extend(this, option);

			$(this).attr('autocomplete', 'off');

			// Focus
			$(this).on('focus', function() {
				if (!this.ajax_loading) {
					this.request();
				}
			});

			// Blur
			$(this).on('blur', function() {
				setTimeout(function(object) {
					if (!object.ajax_loading) {
						object.hide();
					}
				}, 200, this);
			});

			// Keydown
			$(this).on('keydown', function(event) {
				switch(event.keyCode) {
					case 27: // escape
						this.hide();
						break;
					default:
						this.request();
						break;
				}
			});

			// Click
			this.click = function(event) {
				
        if (window.ss_btn_clicked !== undefined && window.ss_btn_clicked) {
          window.ss_btn_clicked = false;
          return false;
        }

				event.preventDefault();
				
        value = $(event.target).closest('li').attr('data-value');
				
				if (value && this.items[value]) {
					this.select(this.items[value]);
				}
			}

			// Show
			this.show = function() {
				var pos = $(this).position();

				$(this).siblings('ul.dropdown-menu').css({
					top: pos.top + $(this).outerHeight(),
					left: pos.left
				});

				$(this).siblings('ul.dropdown-menu').show();
			}

			// Hide
			this.hide = function() {
				$(this).siblings('ul.dropdown-menu').hide();
			}

			// Request
			this.request = function() {
				clearTimeout(this.timer);

				this.timer = setTimeout(function(object) {
					object.source($(object).val(), $.proxy(object.response, object));
				}, 200, this);
			}

			// Response
			this.response = function(json) {
				html = '';

				if (json.length) {
					for (i = 0; i < json.length; i++) {
						this.items[json[i]['value']] = json[i];
					}

					for (i = 0; i < json.length; i++) {
            if (json[i]['value']) {
						  html += '<li data-value="' + json[i]['value'] + '" class="' + json[i]['class']  + '"><a href="#">' + json[i]['label'] + '</a></li>';
            } else {
              html += '<li class="disabled">' + json[i]['label'] + '</li>';
            } 
					}
				}

				if (html) {
					this.show();
				} else {
					this.hide();
				}

				$(this).siblings('ul.dropdown-menu').html(html);
			}

			$(this).after('<ul class="dropdown-menu"></ul>');
			$(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));
		});
	}
})(window.jQuery);
