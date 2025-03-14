<?php // for Centos correct file association ?>
<script>

/* Dynamic Content by Ajax
----------------------------------------------------------------------------- */

// Если не блокировать всю страницу, а только место, где должна появлятсья таблица.
// Использовалось
// При ожидании первичной загрузки динамического контента
// Фильтрация товаров
// При добавлении новых рядов товара - фильтрация товаров
// При удалении товаров


/*
function dynamicContentloaderOn() {
  //$('.dynamic-content-load-bar').fadeOut('normal').html('');
  $('.dynamic-content-load-bar').addClass('_active');
	//$('.dynamic-content-load-bar').fadeIn('normal');
}

function dynamicContentloaderOff() {
	//$('.dynamic-content-load-bar').fadeOut('normal', function() {
	//	$('.dynamic-content-load-bar').removeClass('_active');
	//});
	$('.dynamic-content-load-bar').removeClass('_active');
}
*/

function dynamicUpdate(url) {
	//dynamicContentloaderOn();
	loaderOn();

	// https://habr.com/sandbox/43096/

	$.get(url, function(data) {
		//$('#dynamic-content').fadeOut('normal'); // если использовать отельную иконку dynamicContentloader
		$('#dynamic-content').html(data);
		//$('#dynamic-content').fadeIn('normal'); // если использовать отельную иконку dynamicContentloader

		initImageSorting(false);
		initDrugNDrop();
		//initLiveUpdateOnHoverOut();
		initCategoryTree(); // view/javascript/4handy/handy.js
		initCategoryAutocomplete();
		initButtonCalendar();
		initButtonCalendarWithTime();
		initRelated();
		initFilters(); // product_filter

//		$('.tinymce').each(function() {
//			tinyMCE.execCommand('mceRemoveEditor', false, $(this).attr('id'));
//		});
//
//		setTimeout(function() {
//				tinyMCEInit();
//		}, 300);

		initAttributeValueSelectors();
		liveUpdateAttributeValueSelector();
		initArrows();

		initSelect2();

		scrollCurrentProductId = largest_product_id; // for scroll arrows

		//dynamicContentloaderOff();
		loaderOff();
	});
}




/* Filter
----------------------------------------------------------------------------- */
$(document).ready(function() {
	// Wait for Get attribute data from URL && // Get option data from URL
	setTimeout(function() {
	filtering(false, <?php echo $handy_filter['page']; ?>);
	}, 1000);
});

$('#button-filter').click(function() {
  filtering();
});

function filtering(flag = false, pageTo = 1) {
	var urlQueryString = 'index.php?route=extension/module/handy/productList&user_token=<?php echo $user_token; ?>';
	var urlLoad = 'index.php?route=extension/module/handy/productListDynamicContent&user_token=<?php echo $user_token; ?>';
	var urlParams = '';

	if ('reset_filter' != flag) {
		urlParams = buildUrlParams(urlParams);
	} else {
		// Сбросить все параметры фильтров, ведь это будет стартовая страница фильтра
		$('.filter-container input').each(function() {
			$(this).val('');
		});

		$('.filter-container select').each(function() {
			$(this).val($(this).children('option:first').val());
		});
		
		// todo...
		// Сбросить мультировс поля
		// Категории, атрибуты, поции, производители...		
	}

	if ('changePageByPagination' == flag && pageTo) {
		if (debug) {
			console.log('filtering() :: сработало if (\'changePageByPagination\' == flag && pageTo)');
		}
	}
	
	// Prevent redirect from url&page=1 to url -- it is often added in .htaccess
	// this redirect can be to non SSL and then there will appear mixed content error
	if (pageTo > 1) {
		urlParams += '&page=' + pageTo;
	}

	if (debug) {
		console.log('filtering() urlParams : ' + urlParams);
	}

	// Подменяем action при удалении
	$('#form-product').attr('action', '<?php echo HTTPS_SERVER; ?>index.php?route=extension/module/handy/delete&user_token=<?php echo $user_token; ?>' + urlParams);
	$('#btn-copy').attr('formaction', '<?php echo HTTPS_SERVER; ?>index.php?route=extension/module/handy/copy&user_token=<?php echo $user_token; ?>' + urlParams);

	// Ждем, чтобы автозаполнение успело примениться - при клике на предложенный вариант
	setTimeout(function () {
		try {
			history.replaceState(null, null, location.href); // Q-A - а зачем тогда это нужно?
			history.pushState(null, null, urlQueryString + urlParams);
		} catch(e) {
			alert('Some problem with URL history.pushState');
		}

		<?php if(!$output) { ?>
		  dynamicUpdate(urlLoad + urlParams);
		<?php } ?>
	}, 200); // Задержка

};


// if push Back after filtering()
addEventListener("popstate", function(e) {
	// По факту не обновляет страницу, а только меняет URL
	// window.history.back(); Переводит прям на первую естественным образом открытую страницу, без параметров фильтрации, к-ые были заданы в history.replaceState // Q-A

	// todo...
	// Разобраться, зачем это вообще нужно??
	// Ведь у меня сейчас GET параметры не подставляются на бекенде...
	location.href = location.href; // Редирект - заставляет загрузить страницу заново, а не просто подменить URL

	//filtering(); // Обновляет только динамический контент стало быть не вписывает в фильтре данных из $_GET...
}, false);


function buildUrlParams(urlParams) {	
	<?php if ($has_main_category_column && $getProductMainCategoryIdExist) { ?>
	urlParams += buildUrlParamsItem('main_category_id');
	<?php } ?>

	if ($('#input-handy-filter--categories-notset').prop('checked')) {
		urlParams += '&category=notset';
	} else {
		let category = $("[name='handy_filter[category][]']:checked");
		if (category.length != 0) {
			category.each(function() {				
				urlParams += '&category[]=' + encodeURIComponent($(this).val());
			});
		}
		
		let category_flag = $('#input-handy-filter--category-flag').val();
		if (category_flag && category.length != 0) {
			urlParams += '&category_flag=' + encodeURIComponent(category_flag);
		}
	}		
	
	urlParams += buildUrlParamsItemArray('manufacturer');

	let name = $("[name*='handy_filter[name]']");
	let name_filled = false;
	
	if (name.length != 0) {
		name.each(function() {
			if ($(this).val()) {
				name_filled = true;
				return false;
			}
		});
		
		if (name_filled) {
			urlParams += buildUrlParamsItemArray('name', 'use_keys');
			urlParams += buildUrlParamsItem('name_flag', 'use_name', 'checked');
		}
	}
	
	urlParams += buildUrlParamsItem('product_id');
	urlParams += buildUrlParamsItem('keyword');	
	urlParams += buildUrlParamsItem('model');
	urlParams += buildUrlParamsItem('sku');
	urlParams += buildUrlParamsItem('upc');
	urlParams += buildUrlParamsItem('ean');
	urlParams += buildUrlParamsItem('jan');
	urlParams += buildUrlParamsItem('isbn');
	urlParams += buildUrlParamsItem('mpn');
	urlParams += buildUrlParamsItem('status');
	urlParams += buildUrlParamsItem('image');
	
	let doubles = $("[name='handy_filter[doubles][]']:checked");
	if (doubles.length != 0) {
		doubles.each(function() {				
			urlParams += '&doubles[]=' + encodeURIComponent($(this).val());
		});
	}
	
	urlParams += buildUrlParamsItem('date_added_from');
	urlParams += buildUrlParamsItem('date_added_before');
	urlParams += buildUrlParamsItem('date_modified_from');
	urlParams += buildUrlParamsItem('date_modified_before');
	urlParams += buildUrlParamsItem('date_available_from');
	urlParams += buildUrlParamsItem('date_available_before');
	urlParams += buildUrlParamsItem('price_min');
	urlParams += buildUrlParamsItem('price_max');
	urlParams += buildUrlParamsItem('quantity_min');
	urlParams += buildUrlParamsItem('quantity_max');
	urlParams += buildUrlParamsItem('length_min');
	urlParams += buildUrlParamsItem('length_max');
	urlParams += buildUrlParamsItem('width_min');
	urlParams += buildUrlParamsItem('width_max');
	urlParams += buildUrlParamsItem('height_min');
	urlParams += buildUrlParamsItem('height_max');
	urlParams += buildUrlParamsItem('length_class_id');
	urlParams += buildUrlParamsItem('weight_min');
	urlParams += buildUrlParamsItem('weight_max');
	urlParams += buildUrlParamsItem('weight_class_id');
	
	urlParams += buildUrlParamsItem('sort');
	
	urlParams += buildUrlParamsItemArray('attribute');
	urlParams += buildUrlParamsItemArray('attribute_value');
	urlParams += buildUrlParamsItemArray('option');
	urlParams += buildUrlParamsItemArray('option_value');

	return urlParams;
}

function buildUrlParamsItem(name, flag = 'use_id', statement = false) {
	let value;
	
	if ('use_id' == flag) {
	//let id = '#input-handy-filter--' + name.replace(/_/g, '-');
		value = $('#input-handy-filter--' + name.replace(/_/g, '-')).val();
	}
	
	if ('use_name' == flag) {
		
		if ('checked' == statement) {
			value = $("[name='handy_filter[" + name + "]']:checked").val();
		} else {
			value = $("[name='handy_filter[" + name + "]']").val();
		}		
	}
	
	console.log('buildUrlParamsItem()');
	console.log('name: ' + name);
	console.log('value: ' + value);
	console.log('flag: ' + flag);
	
	if (value) {
		return '&' + name + '=' + encodeURIComponent(value);
	}
	
	return '';
}
/*
 * if ('use_keys' == flag) then use data-key=""
 */
function buildUrlParamsItemArray(name, flag = false) {
	let value = $("[name*='handy_filter[" + name + "]']");
	let urlParams = '';
	
	if (value.length != 0) {
		value.each(function() {
			let key = '';
			
			if ('use_keys' == flag) {
				key = $(this).attr('data-key');
			}
			
			if ($(this).val()) {
				urlParams += '&' + name + '[' + key + ']=' + encodeURIComponent($(this).val());
			}
		});
	}
	
	return urlParams;
	
//	let attribute_value = $("[name*='handy_filter[" + name + ""]']");
//	if (attribute_value.length != 0) {
//		attribute_value.each(function() {
//			urlParams += '&attribute_value[]=' + encodeURIComponent($(this).val());
//		});
//	}
}


// Autocomplete Customized */
(function($) {
	$.fn.autocomplete2 = function(option) {
		return this.each(function() {
			var $this = $(this);
			var $dropdown = $('<ul class="dropdown-menu" />');

			this.timer = null;
			this.items = [];

			$.extend(this, option);

			$this.attr('autocomplete', 'off');

			// Focus
			$this.on('focus', function() {
				//this.request();
			});

			// Blur
			$this.on('blur', function() {
				setTimeout(function(object) {
					object.hide();
				}, 200, this);
			});

			// Keydown
			$this.on('keydown', function(event) {
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
				event.preventDefault();

				var value = $(event.target).parent().attr('data-value');

				if (value && this.items[value]) {
					this.select(this.items[value]);
				}
			}

			// Show
			this.show = function() {
				var pos = $this.position();

				$dropdown.css({
					top: pos.top + $this.outerHeight(),
					left: pos.left
				});

				$dropdown.show();
			}

			// Hide
			this.hide = function() {
				$dropdown.hide();
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
				var html = '';
				var category = {};
				var name;
				var i = 0, j = 0;

				if (json.length) {
					for (i = 0; i < json.length; i++) {
						// update element items
						this.items[json[i]['value']] = json[i];

						if (!json[i]['category']) {
							// ungrouped items
							html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
						} else {
							// grouped items
							name = json[i]['category'];
							if (!category[name]) {
								category[name] = [];
							}

							category[name].push(json[i]);
						}
					}

					for (name in category) {
						html += '<li class="dropdown-header">' + name + '</li>';

						for (j = 0; j < category[name].length; j++) {
							html += '<li data-value="' + category[name][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[name][j]['label'] + '</a></li>';
						}
					}
				}

				if (html) {
					this.show();
				} else {
					this.hide();
				}

				$dropdown.html(html);
			}

			$dropdown.on('click', '> li > a', $.proxy(this.click, this));
			$this.after($dropdown);
		});
	}
})(window.jQuery);

/*
$('#input-handy-filter--name').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=extension/module/handy/productAutocomplete&user_token=<?php echo $user_token; ?>&filter_name=' +  encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['name'],
						value: item['product_id']
					}
				}));
			}
		});
	},
	'select': function(item) {
		$('#input-handy-filter--name').val(item['label']);
	}
});
*/

$('#input-handy-filter--name').autocomplete2({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=extension/module/handy/productAutocomplete&user_token=<?php echo $user_token; ?>&name=' +  encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['name'],
						value: item['product_id']
					}
				}));
			}
		});
	},
	'select': function(item) {
		$('#input-handy-filter--name').val(item['label']);
		$('#input-handy-filter--name').trigger('change');
	}
});

$('#input-handy-filter--model').autocomplete2({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=extension/module/handy/productAutocomplete&user_token=<?php echo $user_token; ?>&model=' +  encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['model'],
						value: item['product_id']
					}
				}));
			}
		});
	},
	'select': function(item) {
		$('#input-handy-filter--model').val(item['label']);
    $('#input-handy-filter--model').trigger('change');

	}
});

$('#input-handy-filter--sku').autocomplete2({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=extension/module/handy/productAutocomplete&user_token=<?php echo $user_token; ?>&sku=' +  encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['sku'],
						value: item['product_id']
					}
				}));
			}
		});
	},
	'select': function(item) {
		$('#input-handy-filter--sku').val(item['label']);
    $('#input-handy-filter--sku').trigger('change');
	}
});



$('.filter-field').on('change', function(e){
  var name = $(this).attr('name');

  if ('product_id' == name || 'keyword' == name) {
    if ('name' != name) $('#input-handy-filter--name').val('');
    if ('product_id' != name) $('input[name=\'product_id\']').val('');
    if ('sku' != name) $('#input-handy-filter--sku').val('');
    if ('model' != name) $('#input-handy-filter--model').val('');
    if ('keyword' != name) $('#input-handy-filter--keyword').val('');
  }

	//filtering();

});


/* Pagination
----------------------------------------------------------------------------- */
$('body').on('click', '.handy-pagination .pagination a', function(e) {
	e.preventDefault();

	var pageTo = false;
	var link = $(this).attr('href');
	var pos = link.indexOf('page=');

	if (-1 != pos) {
		pos = pos + 5;
		pageTo = Number(link.substring(pos));
	} else {
		pageTo = 1;
	}

	console.log('.handy-pagination  .pagination a click() pageTo : ' + pageTo);

	$('html, body').animate({ scrollTop: $('#content').offset().top}, 0); // scroll to top on pagination

	filtering('changePageByPagination', pageTo);
});

</script>
