<?php // for Centos correct file association ?>
<script>
/* Option
----------------------------------------------------------------------------- */

/* Common data for different products */
// Данный массив всегда актуален, пока мы не добавляем новые опции прямо во время редактирования товара.
// При реализации редактирования товара можно просто пополнять данный массив значениями.

var optionValuesExist = [];

$.ajax({
  url: 'index.php?route=extension/module/handy/getAllOptionValues&user_token=<?php echo $user_token; ?>',
  type: 'GET',
  dataType: 'json',
  async: false,
  success: function(json) {
    if ('success' === json['status']) {
      optionValuesExist = json['data'];
    } else {
      alert('error on extension/module/handy/getAllOptionValues');
    }
  },
  error: function( jqXHR, textStatus, errorThrown ){
    // Error ajax query
    console.log('AJAX query Error: ' + textStatus );
  },
});


/* Edit */

$('body').on('change', '.options .le-value._simple-value', function(e){
  e.preventDefault();

  if (debug) {
    console.log('this value : ' + $(this).val());
  }

  var data = 'essence=edit_product_option'
    + '&product_id=' + $(this).closest('.options-container').data('product-id')
    + '&option_id=' + $(this).closest('.option-row').data('option-id')
    + '&field=' + $(this).data('field')
    + '&value=' + $(this).val();

  liveUpdateAjax(data, $(this).data('field'));
});


$('body').on('change', '.options .le-value._simple-value-2', function(e){
  e.preventDefault();

  if (debug) {
    console.log('this value : ' + $(this).val());
  }

  if (debug) {
    console.log('product-id : ' + $(this).closest('.options-container').data('product-id'));
    console.log('option-id : ' + $(this).closest('.option-row').data('option-id'));
  }

  var data = 'essence=edit_product_option_value'
    + '&product_option_value_id=' + $(this).closest('.option-value').data('product-option-value-id')
    + '&field=' + $(this).data('field')
    + '&value=' + encodeURIComponent($(this).val()); // encodeURIComponent for +

  liveUpdateAjax(data, $(this).data('field'));
});



/* Add */

$('body').on('click', '.btn-add-product-option-value', function(e) {
  // получить список значений данной опции
  // Проверить кол-во полученных и кол-во использованных!! ...

	var optionRow = $(this).closest('.option-row').attr('data-option-row'); // this row ! not all rows!
	var optionValueRow = $(this).prev('.option-values').attr('data-option-value-row');

  var productId = $(this).closest('.options-container').data("product-id");
  var optionId = $(this).closest('.option-row').data('option-id');
	var productOptionId = $(this).closest('.option-row').data('product-option-id');

	var thisOptionValues = optionValuesExist[optionId];

	// check countity of options values
	if ($($(this).data('target') + ' .option-value').length >= thisOptionValues.length) {
		alert('This option have only ' + thisOptionValues.length + ' values and ' + $($(this).data('target') + ' .option-value').length + ' is already required to this product. You can\'t add more!');
		return false;
	}

  var optionValueId = thisOptionValues[0]['option_value_id'];

	var data = 'essence=add_product_option_value'
    + '&product_id=' + productId
    + '&option_id=' + optionId
    + '&product_option_id=' + productOptionId
    + '&option_value_id=' + optionValueId;

  var productOptionValueId = liveUpdateAjax(data, 'add_product_option_value');

  if (debug) {
    console.log('optionId : ' + optionId);
    console.debug(optionValuesExist);
    console.debug(thisOptionValues);
  }

  var html = '';

	html += '<div id="option-value-' + productId + '-' + optionRow + '-' + optionValueRow + '" class="option-value le-row" data-product-option-value-id="' + productOptionValueId + '" data-option-value-row="' + optionValueRow + '">';
	html += '<span class="fa fa-close btn-remove-product-option-value" data-toggle="tooltip" title="<?php echo $button_remove; ?>"></span>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left" for="input-product-option-value-' + productId + '-' + optionRow + '-' + optionValueRow + '"><?php echo $entry_option_value; ?></label>';
	html += '<select id="input-product-option-value-' + productId + '-' + optionRow + '-' + optionValueRow + '" name="" class="le-value _simple-value-2 le-selector _right" data-field="option_value_id">';
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
	html += '<label class="le-label _left" for="input-quantity-' + productId + '-' + optionRow + '-' + optionValueRow + '"><?php echo $entry_quantity; ?></label>';
	html += '<input type="text" id="input-quantity-' + productId + '-' + optionRow + '-' + optionValueRow + '" name="" value="" placeholder="<?php echo $entry_quantity; ?>" class="le-value _simple-value-2 _right" data-field="quantity" />';
	html += '</div>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left" for="input-subtract-' + productId + '-' + optionRow + '-' + optionValueRow + '" style="padding-top: none;"><?php echo $entry_subtract; ?></label>';
	html += '<select id="input-subtract-' + productId + '-' + optionRow + '-' + optionValueRow + '" name="" class="le-value _simple-value-2 le-selector _right" data-field="subtract">';
	html += '<option value="1" selected="selected"><?php echo $text_yes; ?></option>';
	html += '<option value="0"><?php echo $text_no; ?></option>';
	html += '</select>';
	html += '</div>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left"><?php echo $entry_price; ?></label>';
	html += '<input id="input-price-' + productId + '-' + optionRow + '-' + optionValueRow + '" type="text" name="price" value="" placeholder="<?php echo $entry_price; ?>" class="le-value _simple-value-2 _right-half" data-field="price" />';
	html += '<select id="input-price-prefix-' + productId + '-' + optionRow + '-' + optionValueRow + '" name="price_prefix" class="le-value _simple-value-2 le-selector _right-half" data-field="price_prefix">';
	html += '<option value="+" selected="selected">+</option>';
	html += '<option value="-">-</option>';
	<?php foreach ($a_price_prefixes as $key => $symbol) { ?>
	html += '<option value="<?php echo $key; ?>"><?php echo $symbol; ?></option>';
	<?php } ?>
	html += '</select>';
	html += '</div>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left"><?php echo $entry_option_points; ?></label>';
	html += '<input id="input-points-' + productId + '-' + optionRow + '-' + optionValueRow + '" type="text" name="points" value="" placeholder="<?php echo $entry_option_points; ?>" class="le-value _simple-value-2 _right-half" data-field="points" />';
	html += '<select id="input-points-prefix-' + productId + '-' + optionRow + '-' + optionValueRow + '" name="points_prefix" class="le-value _simple-value-2 le-selector _right-half" data-field="points_prefix">';
	html += '<option value="+" selected="selected">+</option>';
	html += '<option value="-">-</option>';
	html += '</select>';
	html += '</div>';

	html += '<div class="le-row">';
	html += '<label class="le-label _left"><?php echo $entry_weight; ?></label>';
	html += '<input id="input-weight-' + productId + '-' + optionRow + '-' + optionValueRow + '" type="text" name="weight" value="" placeholder="<?php echo $entry_weight; ?>" class="le-value _simple-value-2 _right-half" data-field="weight" />';
	html += '<select id="input-weight-prefix-' + productId + '-' + optionRow + '-' + optionValueRow + '" name="weight_prefix" class="le-value _simple-value-2 le-selector _right-half" data-field="weight_prefix">';
	html += '<option value="+" selected="selected">+</option>';
	html += '<option value="-">-</option>';
	html += '</select>';
	html += '</div>';

	html += '</div>';

  $($(this).data('target')).append(html);

	optionValueRow++; // !important
	$($(this).data('target')).attr('data-option-value-row', optionValueRow);
});


var aOptions;

$('body').on('click', '.btn-add-option', function(e) {
	aOptions = [];

  var productId = $(this).data("product-id");
  var optionRow = $(this).attr("data-option-row");

	var identifierRow = 'option-row-' + productId + '-' + optionRow;
  var identifierContainer = 'option-row-' + productId + '-' + optionRow + '-option-form';
  var identifierSelector = 'option-row-' + productId + '-' + optionRow + '-option-form-selector';

	var html = '';

  html += '<div id="' + identifierRow + '" class="option-row" data-option-row="' + optionRow + '" data-option-id="0" "data-product-option-id="0">';
  html += '<div class="pull-right"><a type="button" class="btn-remove-option" data-target="#option-row-' + productId + '-' + optionRow + '" data-toggle="tooltip" title="<?php echo $button_remove; ?>"><i class="fa fa-close"></i></a></div>';
  html += '<div class="le-row">';
  html += '<div class="le-label" id="' + identifierContainer + '" data-product-id="' + productId + '" data-option-row="' + optionRow + '">Wait options selector...';

  html +=  '</div>';
  html += '</div>';
  html += '</div>';


  $($(this).data('target')).append(html);

	optionRow++;
  $(this).attr('data-option-row', optionRow);

	$.ajax({
    url: 'index.php?route=extension/module/handy/getOptionsList&user_token=<?php echo $user_token; ?>&product_id=' + productId,
    type: 'GET',
    dataType: 'json',
    beforeSend: function() { loaderOn(); },
    success: function(json) {
      if ('success' === json['status']) {
        if (debug) {
          console.log('success on option selector get option list : ' + '#' + identifierSelector);

					console.debug(json['data']);
        }

        aOptions = json['data'];

        var html = '';

        html += '<select id="' + identifierSelector + '" class="le-selector option-selector">';
        html += '<option value=""><?php echo $handy_text_option_select; ?></option>';
        for (var key in aOptions) {
          html += '<option value="' + aOptions[key]['option_id'] + '">' + aOptions[key]['name'] + '</option>';
        }
        //html += '<option value="add_new_option"><?php echo $handy_text_option_new; ?></option>';
        html += '</select>';

        $('#' + identifierContainer).html(html);

        setTimeout(function () {
          //liveUpdateOptionSelector(identifierSelector, identifierContainer, identifierRow);
        }, 200);

      } else {
        if (debug) {
          console.log('error on option selector get option list: ' + '#' + identifierSelector);
          html = 'error';
        }
      }
    },
    error: function( jqXHR, textStatus, errorThrown ){
      // Error ajax query
      console.log('AJAX query Error: ' + textStatus );
    },
    complete: function() { loaderOff(); }
  });
});


$('body').on('change', '.option-selector', function() {
	var productId = $(this).parent().attr('data-product-id');
	var optionRow = $(this).parent().attr('data-option-row');
	var optionId = $(this).val();

	var html = '';

	html += '<div class="le-label _name"><a class="option-link" href="index.php?route=catalog/option/edit&user_token=<?php echo $user_token; ?>&option_id=' + optionId + '" target="_blank" data-toggle="tooltip" title="<?php echo $handy_text_option_edit; ?>">' + $(this).children('option:selected').text() + '</a></div>';

	html += '<!-- reqiure -->';
	html += '<div class="option-require le-row">';
	html += '<label class="le-label _left" for="input-required-' + productId + '-' + optionRow + '">' + "<?php echo $entry_required; ?>" + '</label>';  // entry_required -- ua: Обов'язково: -- apostrophe affects js-code
	html += '<select id="input-required-' + productId + '-' + optionRow + '" class="le-value _simple-value le-selector _right" data-field="required">';
	html += '<option value="1" selected="selected"><?php echo $text_yes; ?></option>';
	html += '<option value="0"><?php echo $text_no; ?></option>';
	html += '</select>';
	html += '</div>';

	var data = 'essence=add_product_option'
    + '&product_id=' + productId
    + '&option_id=' + optionId;

  var productOptionId = liveUpdateAjax(data, 'add_product_option');

	$('#option-row-' + productId + '-' + optionRow).attr('data-option-id', optionId);
	$('#option-row-' + productId + '-' + optionRow).attr('data-product-option-id', productOptionId);

	if ('text' == aOptions[optionId]['type']) {
		html += '<div class="le-row">';
		html += '<label class="le-label" for="input-value-' + productId + '-' + optionRow + '"><?php echo $entry_option_value; ?></label>';
		html += '<input type="text" value="" placeholder="<?php echo $entry_option_value; ?>" id="input-value-' + productId + '-' + optionRow + '" class="le-value _simple-value" data-field="value" />';
		html += '</div>';
	}

	if ('textarea' == aOptions[optionId]['type']) {
		html += '<div class="le-row">';
		html += '<label class="le-label" for="input-value-' + productId + '-' + optionRow + '"><?php echo $entry_option_value; ?></label>';
		html += '<textarea rows="5" placeholder="<?php echo $entry_option_value; ?>" id="input-value-' + productId + '-' + optionRow + '" class="le-value _simple-value _textarea" data-field="value"></textarea>';
		html += '</div>';
	}

	if ('select' == aOptions[optionId]['type'] || 'radio' == aOptions[optionId]['type'] || 'checkbox' == aOptions[optionId]['type'] || 'image' == aOptions[optionId]['type']) {
		html += '<div id="option-values-' + productId + '-' + optionRow + '" class="option-values" data-option-value-row="0">';
		html += '</div>';
    html += '<button type="button" data-toggle="tooltip" data-target="#option-values-' + productId + '-' + optionRow + '" title="<?php echo $button_option_value_add; ?>" class="btn btn-sm btn-primary btn-add-product-option-value"><i class="fa fa-plus-circle"></i></button>';
	}

	$(this).closest('.le-row').html(html);

	optionRow++; // !important
	$('#options-container-' + productId).attr('data-option-row', optionRow);
});



/* Remove */

$('body').on('click', '.btn-remove-option', function() {
  if (debug) {
    console.log('.btn-remove-option click()');
  }

  var data = 'essence=delete_option_from_product'
    + '&product_id=' + $($(this).data('target')).parent('.options-container').data('product-id')
    + '&option_id=' + $($(this).data('target')).data('option-id');

  liveUpdateAjax(data, 'delete_option_from_product');

  $($(this).data('target')).remove();
});

$('body').on('click', '.btn-remove-product-option-value', function(e) {
  $(this).tooltip('destroy');

  var data = 'essence=delete_product_option_value'
    + '&product_id=' + $(this).closest('.options-container').data('product-id')
    + '&product_option_value_id=' + $(this).closest('.option-value').data('product-option-value-id');

  liveUpdateAjax(data, 'delete_product_option_value');

  $(this).parent('.option-value').remove();
});


</script>
