<?php // for Centos correct file association ?>

<script src="view/javascript/4handy/handy.js"></script>

<script>

//////////////////////////////////////////////////////////////////////////////
// COMMON
//////////////////////////////////////////////////////////////////////////////

// Category Tree
$(document).ready(function() {
  initCategoryTree(); // view/javascript/4handy/handy.js
});




//////////////////////////////////////////////////////////////////////////////
// EDITOR
//////////////////////////////////////////////////////////////////////////////



// Editor Discount
$('body').on('click', '.btn-remove-discount', function(e) {
  $($(this).data('target')).remove();
});

$('body').on('click', '.btn-add-discount', function(e) {
  if(debug) {
    console.log('.btn-add-discount click() is called');
  }

  var discountRow = $(this).data("discount-row");
  var identifierRow = 'discount-row' + '-' + discountRow;

  if(debug) {
    console.log('discountRow : ' + discountRow);
    console.log('identifierRow : ' + identifierRow);
  }

  // todo
  // Получаем productDiscountId
  // Добавляем форму для редактирования скидки

  var html = '';
  html += '<div id="discount-row-' + discountRow + '" class="discount-row" data-discount-row="' + discountRow + '">';
  html += '<div class="pull-right"><a type="button" class="btn-remove-discount" data-target="#discount-row-' + discountRow + '" data-toggle="tooltip" title="<?php echo $button_remove; ?>"><i class="fa fa-close"></i></a></div>';
  html += '<div class="le-row">';
  html += '<span class="le-label _customer-group"><?php echo $handy_entry_customer_group; ?>:</span>';
  html += '<select name="discount[' + discountRow + '][customer_group_id]" class="le-value _discount-value le-selector discount-customer-group">';
  <?php foreach ($customer_groups as $customer_group) { ?>
  html += '<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>';
  <?php } ?>
  html += '</select>';
  html += '</div>';
	html += '<div class="le-row">';
  html += '<span class="le-label _quantity"><?php echo $entry_quantity; ?></span>';
  html += '<input type="text" name="discount[' + discountRow + '][quantity]" value="" placeholder="<?php echo $entry_quantity; ?>" class="le-value _discount-value discount-quantity" />';
  html += '</div>';
  html += '<div class="le-row">';
  html += '<span class="le-label _priority"><?php echo $entry_priority; ?></span>';
  html += '<input type="text" name="discount[' + discountRow + '][priority]" value="" placeholder="<?php echo $entry_priority; ?>" class="le-value _discount-value discount-priority" />';
  html += '</div>';
  html += '<div class="le-row">';
  html += '<span class="le-label"><?php echo $entry_price; ?></span>';
  html += '<input type="text" name="discount[' + discountRow + '][price]" value="" placeholder="<?php echo $entry_price; ?>" class="le-value _discount-value discount-price" />';
  html += '</div>';
  html += '<div class="le-row _date">';
  html += '<span class="le-label _date"><?php echo $handy_entry_date_start; ?>:</span>';
  html += '<div class="date">';
  html += '<input type="text" name="discount[' + discountRow + '][date_start]"  value="" placeholder="<?php echo $handy_entry_date_start; ?>" data-date-format="YYYY-MM-DD" class="le-value _discount-value _date  discount-date-start" />';
  html += '<span class="input-group-btn">';
  html += '<button class="btn-calendar" type="button"><i class="fa fa-calendar"></i></button>';
  html += '</span></div>';
  html += '</div>';
  html += '<div class="le-row _date">';
  html += '<span class="le-label _date"><?php echo $handy_entry_date_end; ?>:</span>';
  html += '<div class="date">';
  html += '<input type="text" name="discount[' + discountRow + '][date_end]" value="" placeholder="<?php echo $handy_entry_date_end; ?>" data-date-format="YYYY-MM-DD" class="le-value _discount-value _date discount-date-end" />';
  html += '<span class="input-group-btn">';
  html += '<button class="btn-calendar" type="button"><i class="fa fa-calendar"></i></button>';
  html += '</span></div>';
  html += '</div>';
  html += '</div>';

  $('#discount-container').append(html);

  $(this).data("discount-row", discountRow + 1); // Сохраняем порядковый номер ряда

  setTimeout(function () {
    initButtonCalendar();
    initButtonCalendarWithTime();
  }, 100);
});


// Editor Special
$('body').on('click', '.btn-remove-special', function(e) {
  $($(this).data('target')).remove();
});

$('body').on('click', '.btn-add-special', function(e) {
  if(debug) {
    console.log('.btn-add-special click() is called');
  }

  var specialRow = $(this).data("special-row");
  var identifierRow = 'special-row' + '-' + specialRow;

  if(debug) {
    console.log('specialRow : ' + specialRow);
    console.log('identifierRow : ' + identifierRow);
  }

  // todo
  // Получаем productSpecialId
  // Добавляем форму для редактирования скидки

  var html = '';
  html += '<div id="special-row-' + specialRow + '" class="special-row" data-special-row="' + specialRow + '">';
  html += '<div class="pull-right"><a type="button" class="btn-remove-special" data-target="#special-row-' + specialRow + '" data-toggle="tooltip" title="<?php echo $button_remove; ?>"><i class="fa fa-close"></i></a></div>';
  html += '<div class="le-row">';
  html += '<span class="le-label _customer-group"><?php echo $handy_entry_customer_group; ?>:</span>';
  html += '<select name="special[' + specialRow + '][customer_group_id]" class="le-value _special-value le-selector special-customer-group">';
  <?php foreach ($customer_groups as $customer_group) { ?>
  html += '<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>';
  <?php } ?>
  html += '</select>';
  html += '</div>';
  html += '<div class="le-row">';
  html += '<span class="le-label _priority"><?php echo $entry_priority; ?></span>';
  html += '<input type="text" name="special[' + specialRow + '][priority]" value="" placeholder="<?php echo $entry_priority; ?>" class="le-value _special-value special-priority" />';
  html += '</div>';
  html += '<div class="le-row">';
  html += '<span class="le-label"><?php echo $entry_price; ?></span>';
  html += '<input type="text" name="special[' + specialRow + '][price]" value="" placeholder="<?php echo $entry_price; ?>" class="le-value _special-value special-price" />';
  html += '</div>';
  html += '<div class="le-row _date">';
  html += '<span class="le-label _date"><?php echo $handy_entry_date_start; ?>:</span>';
  html += '<div class="date">';
  html += '<input type="text" name="special[' + specialRow + '][date_start]"  value="" placeholder="<?php echo $handy_entry_date_start; ?>" data-date-format="YYYY-MM-DD" class="le-value _special-value _date  special-date-start" />';
  html += '<span class="input-group-btn">';
  html += '<button class="btn-calendar" type="button"><i class="fa fa-calendar"></i></button>';
  html += '</span></div>';
  html += '</div>';
  html += '<div class="le-row _date">';
  html += '<span class="le-label _date"><?php echo $handy_entry_date_end; ?>:</span>';
  html += '<div class="date">';
  html += '<input type="text" name="special[' + specialRow + '][date_end]" value="" placeholder="<?php echo $handy_entry_date_end; ?>" data-date-format="YYYY-MM-DD" class="le-value _special-value _date special-date-end" />';
  html += '<span class="input-group-btn">';
  html += '<button class="btn-calendar" type="button"><i class="fa fa-calendar"></i></button>';
  html += '</span></div>';
  html += '</div>';
  html += '</div>';

  $('#special-container').append(html);

  $(this).data("special-row", specialRow + 1); // Сохраняем порядковый номер ряда

  setTimeout(function () {
    initButtonCalendar();
		initButtonCalendarWithTime();
  }, 100);
});



// Editor Related
$('#input-related').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=catalog/product/autocomplete&user_token=<?php echo $user_token; ?>&filter_name=' +  encodeURIComponent(request),
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
		$('#products-related--item-' + item['value']).remove();

		$('#products-related').append('<div id="products-related--item-' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="product_related[]" value="' + item['value'] + '" /></div>');
	}
});

$('#products-related').delegate('.fa-minus-circle', 'click', function() {
	$(this).parent().remove();
});

// Editor Related Delete
$('#input-related-delete').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=catalog/product/autocomplete&user_token=<?php echo $user_token; ?>&filter_name=' +  encodeURIComponent(request),
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
		$('#products-related-delete--item-' + item['value']).remove();

		$('#products-related-delete').append('<div id="products-related-delete--item-' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="product_related_delete[]" value="' + item['value'] + '" /></div>');
	}
});

$('#products-related-delete').delegate('.fa-minus-circle', 'click', function() {
	$(this).parent().remove();
});


// Editor Filters
$('#input-filter').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=extension/module/handy/getFiltersAutocomplete&user_token=<?php echo $user_token; ?>&filter_name=' +  encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['name'],
						value: item['filter_id']
					}
				}));
			}
		});
	},
	'select': function(item) {
		$('#product-filters--item-' + item['value']).remove();

		$('#product-filters').append('<div id="product-filters--item-' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="product_filter[]" value="' + item['value'] + '" /></div>');
	}
});

$('#product-filters').delegate('.fa-minus-circle', 'click', function() {
	$(this).parent().remove();
});

// Editor Filter Delete
$('#input-filter-delete').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=extension/module/handy/getFiltersAutocomplete&user_token=<?php echo $user_token; ?>&filter_name=' +  encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['name'],
						value: item['filter_id']
					}
				}));
			}
		});
	},
	'select': function(item) {
		$('#product-filters-delete--item-' + item['value']).remove();

		$('#product-filters-delete').append('<div id="product-filters-delete--item-' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="product_filter_delete[]" value="' + item['value'] + '" /></div>');
	}
});

$('#product-filters-delete').delegate('.fa-minus-circle', 'click', function() {
	$(this).parent().remove();
});


// Editor Attribute
$('body').on('change', '.attribute-select', function() {
	<?php foreach ($languages as $language) { ?>
	getAttributeValues($(this).val(), $(this).attr('data-row'), <?php echo $language['language_id']; ?>);
	<?php } ?>
});

function getAttributeValues(attributeId, row, languageId) {
	var target = '#input-attribute-value-' + languageId + '-' + row;
	var html = '';

	$.ajax({
		url: 'index.php?route=extension/module/handy/getAttributeValues&user_token=<?php echo $user_token; ?>&attribute_id=' + attributeId + '&language_id=' + languageId,
		type: 'GET',
		dataType: 'json',
		success: function(json) {
			if ('success' === json['status']) {
				var data = json['data'];

				if (debug) {
					console.debug(data);
				}

				var i = 0;
				for (var key in data) {
					html += '<option value="' + escapeHtml(data[key]['text']) + '">' + data[key]['text'] + '</option>';
					i++;
				}
				if(0 === i) {
					html += '<option value="*"><?php echo $handy_text_attribute_values_empty; ?></option>';
				} else {
					html = '<option value="*"><?php echo $handy_text_attribute_values_select; ?></option>' + html;
				}

				$(target).html(html);

			} else {
				if (debug) {
					console.log('error on attribute selector get attribute values: ' + target );
				}
			}
		},
		error: function( jqXHR, textStatus, errorThrown ){
			// Error ajax query
			console.log('AJAX query Error on ' + target + ': ' + textStatus );
		},
	});
}

// .btn-add-attribute
$('body').on('click', '.btn-add-attribute', function() {
	var row = $(this).attr('data-target-row');

	var html = '';
	html +='<div class="le-row" id="attribute-row-' + row + '" style="position: relative; margin-bottom: 10px;">';

	html +='<div style="position: absolute; top: 0; right: 0;"><a type="button" class="btn-remove-attribute-row" data-parent-row="' + row + '" data-toggle="tooltip" title="<?php echo $button_remove; ?>"><i class="fa fa-close"></i></a></div>';
	html +='<div style="width: 32%; float: left; ">';
	html +='<label class="control-label" for="input-attribute-' + row + '"><?php echo $handy_entry_attribute; ?></label><br>';
	html +='<select name="attribute[' + row + ']" id="input-attribute-' + row + '" class="le-value le-selector attribute-select" data-row="' + row + '" style="width: 100%;">';
	html +='<option value="*"><?php echo $handy_text_none; ?></option>';
	<?php foreach($attributes as $attribute) { ?>
	html +='<option value="<?php echo $attribute['attribute_id']; ?>"><?php echo htmlspecialchars($attribute['attribute_group'] . ' -- ' . $attribute['name'], ENT_QUOTES, 'UTF-8'); ?></option>';
	<?php } ?>
	html +='</select>';
	html +='</div>';
	<?php foreach($languages as $language) { ?>
	html +='<div style="width: 32%; float: right; ">';
	html +='<label class="control-label" for="input-attribute-value-' + row + '"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /> <?php echo $entry_attribute_value; ?></label><br>';
	html +='<select name="attribute_value[<?php echo $language['language_id']; ?>][' + row + ']" id="input-attribute-value-<?php echo $language['language_id']; ?>-' + row + '" class="le-value le-selector" style="width: 100%;">';
	html +='<option value="*"><?php echo $handy_text_none; ?></option>';
	html +='</select>';
	html +='</div>';
	<?php } ?>

	html +='</div>';

	// append
	$('#attributes-container').append(html);

	$(".attributes-container select[name*='attribute']").select2(); // Form - New added attribute & attribute value

	$(this).attr('data-target-row', Number(row) + 1);
});

$('body').on('click', '.btn-remove-attribute-row', function() {
	$('#attribute-row-' + $(this).data('parent-row')).remove();
});


// Editor Option
$('body').on('click', '.btn-add-option', function() {
	var row = $(this).attr('data-target-row');

	var html = '';

	html +='<div class="option-row" id="option-row-' + row + '" data-option-id="0" data-option-row="' + row + '">';

	html +='<div class="pull-right"><a type="button" class="btn-remove-option-row" data-parent-row="' + row + '" data-toggle="tooltip" title="<?php echo $button_remove; ?>"><i class="fa fa-close"></i></a></div>';
	html +='<div class="le-row">';
//	html +='<label class="control-label" for="input-option-' + row + '"><?php echo $handy_entry_option; ?></label>';
	html +='<select name="option[' + row + ']" id="input-option-' + row + '" class="le-selector option-selector" data-row="' + row + '">';
	html += '<option value="*"><?php echo $handy_text_option_select; ?></option>';
//	html +='<option value="*"><?php echo $handy_text_none; ?></option>';
	<?php foreach($options as $option) { ?>
	html +='<option value="<?php echo $option['option_id']; ?>"><?php echo $option['name']; ?></option>';
	<?php } ?>
	html +='</select>';
	html +='</div>';

	html +='</div>';

	// append
	$('#options-container').append(html);
	$(this).attr('data-target-row', Number(row) + 1);
});

$('body').on('change', '.option-selector', function() {
	var row = $(this).attr('data-row');
	var optionId = $(this).val();

	var html = '';
	html +='<div class="le-row">';

	html +='<div class="pull-right">';
	html +='<a type="button" class="btn-remove-option-row" data-parent-row="' + row + '" data-toggle="tooltip" title="<?php echo $button_remove; ?>"><i class="fa fa-close"></i></a>';
	html +='</div>';

	html += '<div class="le-label name"><a class="option-link" href="index.php?route=catalog/option/edit&user_token=<?php echo $user_token; ?>&option_id=' + optionId + '" target="_blank" data-toggle="tooltip" title="<?php echo $handy_text_option_edit; ?>">' + $(this).children('option:selected').text() + '</a></div>';

	html += '<!-- hidden Option Id -->';
	html += '<input type="hidden" name="option[' + row + '][option_id]" value="' + optionId + '" />';

	html += '<!-- reqiure -->';
	html += '<div class="option-require le-row" style="padding-left: 0; padding-top:5px;">';
	html += '<label class="le-label _left" style="text-align: left;" for="input-required-' + row + '">' + "<?php echo $entry_required; ?>" + '</label>'; // entry_required -- ua: Обов'язково: -- apostrophe affects js-code
	html += '<select name="option[' + row + '][option_require]" id="input-required-' + row + '" class="le-value _simple-value le-selector _right">';
	html += '<option value="1" selected="selected"><?php echo $text_yes; ?></option>';
	html += '<option value="0"><?php echo $text_no; ?></option>';
	html += '</select>';
	html += '</div>';

	if ('text' == optionsExist[optionId]['type']) {
		html += '<div class="row">';
		html += '<label class="control-label col-sm-12" for="input-value-' + row + '"><?php echo $entry_option_value; ?></label>';
		html += '<div class="col-sm-12">';
		html += '<input type="text" name="option[' + row + '][option_value]" value="" placeholder="<?php echo $entry_option_value; ?>" id="input-value-' + row + '" class="form-control" />';
		html += '</div>';
		html += '</div>';
	}

	if ('textarea' == optionsExist[optionId]['type']) {
		html += '<div class="row">';
		html += '<label class="control-label col-sm-12" for="input-value-' + row + '"><?php echo $entry_option_value; ?></label>';
		html += '<div class="col-sm-12">';
		html += '<textarea name=" name="option[' + row + '][option_value]" rows="5" placeholder="<?php echo $entry_option_value; ?>" id="input-value-' + row + '" class="form-control"></textarea>';
		html += '</div>';
		html += '</div>';
	}

	if ('select' == optionsExist[optionId]['type'] || 'radio' == optionsExist[optionId]['type'] || 'checkbox' == optionsExist[optionId]['type'] || 'image' == optionsExist[optionId]['type']) {
		html += '<div id="option-values-' + row + '" class="option-values">';
		html += '</div>';
    html += '<button type="button" data-toggle="tooltip" data-target="#option-values-' + row + '" title="<?php echo $button_option_value_add; ?>" class="btn btn-sm btn-primary btn-add-option-value" data-option-value-row="0"><i class="fa fa-plus-circle"></i></button>';
	}

	html +='</div>';

	$('#option-row-' + row).attr('data-option-id', optionId);

	$('#option-row-' + row).html(html);
});

$('body').on('click', '.btn-remove-option-row', function() {
	$('#option-row-' + $(this).data('parent-row')).remove();
});

$('body').on('click', '.btn-add-option-value', function(e) {
	var optionRow = $(this).closest('.option-row').attr('data-option-row'); // this row ! not all rows!
	var optionValueRow = $(this).attr('data-option-value-row');
  var optionId = $(this).closest('.option-row').attr('data-option-id');
	var thisOptionValues = optionValuesExist[optionId];
	var optionValueId = thisOptionValues[0]['option_value_id'];

	$('#option-row-' + optionRow).attr('data-option-id', optionId);

	// check countity of options values
	if ($($(this).data('target') + ' .option-value').length >= thisOptionValues.length) {
		alert('This option have only ' + thisOptionValues.length + ' values and ' + $($(this).data('target') + ' .option-value').length + ' is already required to this product. You can\'t add more!');
		return false;
	}

  var html = '';

	html += '<div id="option-value-' + optionRow + '-' + optionValueRow + '" class="option-value le-row">';
	html += '<span class="fa fa-close btn-remove-option-value" data-toggle="tooltip" title="<?php echo $button_remove; ?>"></span>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left" for="input-product-option-value-' + optionRow + '-' + optionValueRow + '"><?php echo $entry_option_value; ?></label>';
	html += '<select name="option[' + optionRow + '][option_value][' + optionValueRow + ']" id="input-product-option-value-' + optionRow + '-' + optionValueRow + '" class="le-value _simple-value-2 le-selector _right" data-field="option_value_id">';
	var i = 0;
	var selected;
	thisOptionValues.forEach(function(item) {
		if (0 == i) selected = ' selected="selected"';
		else selected = '';
    html += '<option value="' + item['option_value_id'] + '"' + selected + '>' + item['name'] + '</option>';
		i++;
  });
  html += '</select>';
	html += '</div>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left" for="input-quantity-' + optionRow + '-' + optionValueRow + '"><?php echo $entry_quantity; ?></label>';
	html += '<input type="text" name="option[' + optionRow + '][quantity][' + optionValueRow + ']" id="input-quantity-' + optionRow + '-' + optionValueRow + '" name="" value="" placeholder="<?php echo $entry_quantity; ?>" class="le-value _simple-value-2 _right" data-field="quantity" />';
	html += '</div>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left" for="input-subtract-' + optionRow + '-' + optionValueRow + '" style="padding-top: none;"><?php echo $entry_subtract; ?></label>';
	html += '<select name="option[' + optionRow + '][subtract][' + optionValueRow + ']" id="input-subtract-' + optionRow + '-' + optionValueRow + '" class="le-value _simple-value-2 le-selector _right">';
	html += '<option value="1" selected="selected"><?php echo $text_yes; ?></option>';
	html += '<option value="0"><?php echo $text_no; ?></option>';
	html += '</select>';
	html += '</div>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left"><?php echo $entry_price; ?></label>';
	html += '<input type="text" name="option[' + optionRow + '][price][' + optionValueRow + ']" id="input-price-' + optionRow + '-' + optionValueRow + '" value="" placeholder="<?php echo $entry_price; ?>" class="le-value _simple-value-2 _right-half" />';
	html += '<select name="option[' + optionRow + '][price_prefix][' + optionValueRow + ']" id="input-price-prefix-' + optionRow + '-' + optionValueRow + '" class="le-value _simple-value-2 le-selector _right-half" data-field="price_prefix">';
	html += '<option value="+" selected="selected">+</option>';
	html += '<option value="-">-</option>';
	<?php foreach ($a_price_prefixes as $key => $symbol) { ?>
	html += '<option value="<?php echo $key; ?>"><?php echo $symbol; ?></option>';
	<?php } ?>
	html += '</select>';
	html += '</div>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left"><?php echo $entry_option_points; ?></label>';
	html += '<input type="text" name="option[' + optionRow + '][points][' + optionValueRow + ']" id="input-points-' + optionRow + '-' + optionValueRow + '"value="" placeholder="<?php echo $entry_option_points; ?>" class="le-value _simple-value-2 _right-half" data-field="points" />';
	html += '<select name="option[' + optionRow + '][points_prefix][' + optionValueRow + '] id="input-points-prefix-' + optionRow + '-' + optionValueRow + '" class="le-value _simple-value-2 le-selector _right-half">';
	html += '<option value="+" selected="selected">+</option>';
	html += '<option value="-">-</option>';
	html += '</select>';
	html += '</div>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left"><?php echo $entry_weight; ?></label>';
	html += '<input type="text" name="option[' + optionRow + '][weight][' + optionValueRow + ']" id="input-weight-' + optionRow + '-' + optionValueRow + '" value="" placeholder="<?php echo $entry_weight; ?>" class="le-value _simple-value-2 _right-half" />';
	html += '<select name="option[' + optionRow + '][weight_prefix][' + optionValueRow + '] id="input-weight-prefix-' + optionRow + '-' + optionValueRow + '" class="le-value _simple-value-2 le-selector _right-half">';
	html += '<option value="+" selected="selected">+</option>';
	html += '<option value="-">-</option>';
	html += '</select>';
	html += '</div>';

	html += '</div>';

  $($(this).data('target')).append(html);

	optionValueRow = Number(optionValueRow) + 1;
	$(this).attr('data-option-value-row', optionValueRow);
});

$('body').on('click', '.btn-remove-option-value', function(e) {
  $(this).tooltip('destroy');
  $(this).parent('.option-value').remove();
});




//////////////////////////////////////////////////////////////////////////////
// AJAX
//////////////////////////////////////////////////////////////////////////////

var i = 0;

$('#mass-edit-submit').click(function(e) {
	e.preventDefault();
	
	console.clear();
	
	i = 1;
	
	$('#request-answer').addClass('alert alert-info');
	$('#request-answer').html('<p><?php echo $text_processing; ?></p>');
	
	loaderOn();
	
	loopQueries();
});

async function loopQueries() {
	var dataObj = $('#form-mass-edit').serialize();
	
	if (1 == i) {
		dataObj += '&handy_new_submit=1'; // A!
	}
	
	dataObj += '&flag_mass_edit=1'; // A!

	await $.ajax({
		url: 'index.php?route=extension/module/handy/massEditProcessing&user_token=<?php echo $user_token; ?>',
		type: 'POST',
		dataType: 'json',
		data: dataObj += '&step=' + i,
		success: function (json) {
			console.log('Success httpResponse : ' + i);
			
			console.debug(json);

			if ('Finish' == json['status']) {
				console.log('Processing Finish : ' + i);
				
				$('#request-answer').html('<p class="text-success">' + json.answer + '</p>');

				loaderOff();			

			} else if ('Continue' == json['status']) {
				console.log('Processing Continue : ' + i);

				i++;

				loopQueries();
			} else if ('Error' == json['status']) {
				loaderOff();
				
				$('#request-answer').empty();
				$('#request-answer').html('<p class="text-danger">' + json.answer + '</p>');
			}
			
		},
		error: function (jqXHR, textStatus, errorThrown) {
			console.log('AJAX query Error in loopQueries() - ' +  i + ' : ' + textStatus);
		}
	});
}


function loaderOn() {
	$('.panel-body').addClass('with-loader');
	$('.loader').show();

	$('#mass-edit-submit').attr('disabled', true);	
	$('#mass-edit-submit' + ' .load-bar').css('display', 'block');

	startStopwatch();
}

function loaderOff() {
	$('.panel-body').removeClass('with-loader');
	$('.loader').hide();

	$('#mass-edit-submit').attr('disabled', false);
	$('#mass-edit-submit' + ' .load-bar').css('display', 'none');

	resetStopwatch();
}



// https://foolishdeveloper.com/create-a-simple-stopwatch-using-javascript-tutorial-code/
let [milliseconds,seconds,minutes,hours] = [0,0,0,0];
let timerRef = document.querySelector('.timerDisplay');
let int = null;

function startStopwatch() {
	if(int!==null){
		clearInterval(int);
	}
	int = setInterval(displayStopwatch,10);
};

function resetStopwatch() {
	clearInterval(int);
	[milliseconds,seconds,minutes,hours] = [0,0,0,0];
	timerRef.innerHTML = '00 : 00 : 00 : 000 ';
};

//document.getElementById('resetStopwatch').addEventListener('click', ()=>{
//	clearInterval(int);
//	[milliseconds,seconds,minutes,hours] = [0,0,0,0];
//	timerRef.innerHTML = '00 : 00 : 00 : 000 ';
//});

//document.getElementById('startStopwatch').addEventListener('click', ()=>{
//	if(int!==null){
//		clearInterval(int);
//	}
//	int = setInterval(displayStopwatch,10);
//});
//
//document.getElementById('pauseStopwatch').addEventListener('click', ()=>{
//	clearInterval(int);
//});
//
//document.getElementById('resetStopwatch').addEventListener('click', ()=>{
//	clearInterval(int);
//	[milliseconds,seconds,minutes,hours] = [0,0,0,0];
//	timerRef.innerHTML = '00 : 00 : 00 : 000 ';
//});

function displayStopwatch() {
	milliseconds+=10;
	if(milliseconds == 1000){
		milliseconds = 0;
		seconds++;
		if(seconds == 60){
			seconds = 0;
			minutes++;
			if(minutes == 60){
				minutes = 0;
				hours++;
			}
		}
	}
	let h = hours < 10 ? '0' + hours : hours;
	let m = minutes < 10 ? '0' + minutes : minutes;
	let s = seconds < 10 ? '0' + seconds : seconds;
	let ms = milliseconds < 10 ? '00' + milliseconds : milliseconds < 100 ? '0' + milliseconds : milliseconds;
	timerRef.innerHTML = ` ${h} : ${m} : ${s} : ${ms}`;
}




// Select2 - for editor
$(document).ready(function() {
	$("select[name='main_category_id']").select2();
	$("select[name='manufacturer_id']").select2();
});



</script>