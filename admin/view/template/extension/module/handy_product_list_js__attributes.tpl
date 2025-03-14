<?php // for Centos correct file association ?>
<script>
/* Attribute
----------------------------------------------------------------------------- */

/* Common data for different products */
// Данный массив всегда актуален, пока мы не добавляем новые атрибуты прямо во время редактирования товара.
// При реализации редактирования товара можно просто пополнять данный массив значениями.

var attributeValuesExist = [];

$.ajax({
  url: 'index.php?route=extension/module/handy/getAllAttributeValues&user_token=<?php echo $user_token; ?>',
  type: 'GET',
  dataType: 'json',
  async: false,
  success: function(json) {
    if ('success' === json['status']) {
      attributeValuesExist = json['data'];
    } else {
      alert('error on extension/module/handy/getAllAttributeValues');
    }
  },
  error: function( jqXHR, textStatus, errorThrown ){
    // Error ajax query
    console.log('AJAX query Error: ' + textStatus );
  }
});


$('body').on('click', '.btn-remove-attribute', function(e) {
  deleteAttributeFromProduct($($(this).data('target')).data('product-id'), $($(this).data('target')).data('attribute-id'));
  $($(this).data('target')).remove();
});

// при загрузке страницы - рисуем для всех селекторов
function initAttributeValueSelectors() {
  $('.attribute-value-selector').each(function() {
    var attributeId = $(this).data('attribute-id');
    var languageId = $(this).data('language-id');
    var identifier = $(this).attr('id');
    var target = $(this).data('target');

    var html = '';

		if (undefined === attributeValuesExist[attributeId]) {
			attributeValuesExist[attributeId] = {};
		}

		if (undefined === attributeValuesExist[attributeId][languageId]) {
			attributeValuesExist[attributeId][languageId] = [];
		}

		var data = attributeValuesExist[attributeId][languageId];

		var index = 0;
		data.forEach(function(item, i, data) {
			var selected = $(target).val() === item ? 'selected="selected"' : '';
			html += '<option value="' + escapeHtml(item) + '"' + selected +'>' + item + '</option>';
			i++;
			index = i;
		});

		if(0 === index) {
			html += '<option value=""><?php echo $handy_text_attribute_values_empty; ?></option>';
		} else {
			html = '<option value=""><?php echo $handy_text_attribute_values_select; ?></option>' + html;
		}

		$('#' + identifier).html(html);
  });
}


// Вызывается при добавлении нового атрибута
// С помощью identifierContainer можно задать четкий родительский блок, где будет происходить перебор селекторов
function getValuesForAttibuteValuesSelector(identifierContainer) {
  if(debug) {
    console.log('getValuesForAttibuteValuesSelector() function is called with identifierContainer : ' + identifierContainer);
  }

  $('#' + identifierContainer + ' .attribute-value-selector._live-added').each(function() {
    var attributeId = $(this).data('attribute-id');
    var languageId = $(this).data('language-id');
    var identifier = $(this).attr('id');
    var target = $(this).data('target');

    $.ajax({
      url: 'index.php?route=extension/module/handy/getAttributeValues&user_token=<?php echo $user_token; ?>&attribute_id=' + attributeId + '&language_id=' + languageId,
      type: 'GET',
      dataType: 'json',
      async: false,
      beforeSend: function() {  },
      success: function(json) {
        if ('success' === json['status']) {
          if (debug) {
            console.log('success on attribute selector get attribute values: ' + '#' + identifier);
          }

          var html = '';
          var data = json['data'];
          var i = 0;
          for (var key in data) {
            var selected = $(target).val() === data[key]['text'] ? 'selected="selected"' : '';
            html += '<option value="' + escapeHtml(data[key]['text']) + '"' + selected +'>' + data[key]['text'] + '</option>';
            i++;
          }
          if(0 === i) {
            html += '<option value=""><?php echo $handy_text_attribute_values_empty; ?></option>';
          } else {
            html = '<option value=""><?php echo $handy_text_attribute_values_select; ?></option>' + html;
          }

          $('#' + identifier).html(html);

        } else {
          if (debug) {
            console.log('error on attribute selector get attribute values: ' + '#' + identifier);
          }
        }
      },
      error: function( jqXHR, textStatus, errorThrown ){
        // Error ajax query
        console.log('AJAX query Error: ' + textStatus );
      },
      complete: function() { loaderOff(); }
    });

    $(this).removeClass('_live-added');
  });

}


$('body').on('change', '.attribute-value-selector', function(e) {
  if (debug) {
    console.log('--- .attribute-value-selector on change ----');
    console.log('selected value : ' + $(this).val());
    console.log('target : ' + $(this).data('target'));
  }

  $($(this).data('target')).val($(this).val());
  $($(this).data('target')).trigger('change');
});


$('body').on('click', '.btn-add-attribute', function(e) {
  var productId = $(this).data("product-id");
  var attributeRow = $(this).data("attribute-row");

  var identifierRow = 'attribute-row-' + productId + '-' + attributeRow;
  var identifierContainer = 'attribute-row-' + productId + '-' + attributeRow + '-attribute-form';
  var identifierSelector = 'attribute-row-' + productId + '-' + attributeRow + '-attribute-form-selector';

  var html = '';
  html += '<div id="' + identifierRow + '" class="attribute-row" data-product-id="' + productId + '" data-attribute-id="0" data-attribute-row="' + attributeRow + '">'; // Q? : data-attribute-id="0"
  html += '<div class="pull-right"><a type="button" class="btn-remove-attribute" data-target="#attribute-row-' + productId + '-' + attributeRow + '" data-toggle="tooltip" title="<?php echo $button_remove; ?>"><i class="fa fa-close"></i></a></div>';
  html += '<div class="le-row">';
  html += '<div class="le-label" id="' + identifierContainer + '" data-product-id="' + productId + '" data-attribute-row="' + attributeRow + '">Wait attributes selector...';

  $.ajax({
    url: 'index.php?route=extension/module/handy/getAttributeList&user_token=<?php echo $user_token; ?>&product_id=' + productId,
    type: 'GET',
    dataType: 'json',
    beforeSend: function() { loaderOn(); },
    success: function(json) {
      if ('success' === json['status']) {
        if (debug) {
          console.log('success on attribute selector get attribute list : ' + '#' + identifierSelector);
        }

        var data = json['data'];
        var html = '';
        html += '<select id="' + identifierSelector + '" class="le-selector attribute-selector" data-attribute-value-input-type="exist-in-db">';
        html += '<option value=""><?php echo $handy_text_attribute_select; ?></option>';
        for (var key in data) {
          html += '<option value="' + data[key]['attribute_id'] + '">' + escapeHtml(data[key]['name']) + '</option>';
        }
        html += '<option value="add_new_attribute">! <?php echo $handy_text_attribute_new; ?></option>';
        html += '</select>';

        $('#' + identifierContainer).html(html);

				$('.attribute-selector').select2();

        setTimeout(function () {
          liveUpdateAttributeSelector(identifierSelector, identifierContainer, identifierRow);
        }, 200);

      } else {
        if (debug) {
          console.log('error on attribute selector get attribute list: ' + '#' + identifierSelector);
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

  html +=  '</div>';
  html += '</div>';
  html += '</div>';

  $($(this).data('target')).append(html);
  $(this).data("attribute-row", attributeRow + 1); // Сохраняем порядковый номер ряда

});


function liveUpdateAttributeSelector(identifierSelector, identifierContainer, identifierRow) {
  if (debug) {
    console.log('--- liveUpdateAttributeSelector() function is called ---');
    //console.log('identifierSelector : ' + '#' + identifierSelector);
    //console.log('identifierContainer : ' + '#' + identifierContainer);
  }

  $('#' + identifierSelector).on('change', function(e){
    if (debug) {
      console.log('--- liveUpdateAttributeSelector() on change is called for ' + identifierSelector + ' ---');
    }

    if ('add_new_attribute' === $('#' + identifierSelector).val()) {
      $('#' + $(identifierSelector).parent().attr('id') + ' .le-lang-values-container').remove();

      $(this).attr('data-attribute-value-input-type', 'add-new-to-db');

      setTimeout(function () {
        buildNewAttributeInput(identifierContainer, $('#' + identifierSelector).parent().data('product-id'), $('#' + identifierSelector).parent().data('attribute-row'));
      }, 100);

    } else {
      $('#' + identifierContainer).children('.attribute-group-selector-container').remove();

      var attributeId = $(this).val();

      $('#' + identifierRow).attr('data-attribute-id', attributeId);

      if ('add-new-to-db' === $(this).data('attribute-value-input-type')) {
        $(this).attr('data-attribute-value-input-type', 'exist-in-db');
      } else {
        $( '#' + $('#' + identifierSelector).parent().attr('id') + ' .le-lang-values-container').remove();

        // save if select from exist one
        addAttributeToProduct(attributeId, $('#' + identifierContainer).data('product-id'));
      }

      buildAttributeValuesOnAddNewAttribute(identifierContainer, attributeId, identifierSelector);

      liveUpdateAttributeValueSelector();

      var newHtml = '<div class="le-label"><a href="<?php echo HTTPS_SERVER; ?>index.php?route=catalog/attribute/edit&user_token=<?php echo $user_token; ?>&attribute_id=' + attributeId + '" class="attribute-link" target="_blank">' + $('#' + identifierSelector + ' option:selected').text() + '</a>:</div>';

      $('#' + identifierContainer).prepend(newHtml);

      $('#' + identifierSelector).next('.select2').remove();
      $('#' + identifierSelector).remove();


    }
  });

}


function buildNewAttributeInput(identifierContainer, productId, attributeRow) {
  html = '<div class="attribute-group-selector-container">';
  html += '<select id="attribute-row-' + productId + '-' + attributeRow + '-attribute-form-attribute-group-selector" class="le-selector attribute-group-selector"><option value="">Wait attribute groups...</option></select>';
  html += '<div class="le-lang-values-container">';
  <?php foreach ($languages as $language) { ?>
  html += '<div class="le-lang-values-icon">';
  html += '<span class=""><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>';
  html += '</div>';
  html += '<div class="le-lang-values-content" data-product-id="' + productId + '" data-language-id="<?php echo $language['language_id']; ?>">';
  html += '<input type="text" class="le-value _attribute-name le-text" name="attribute_description[<?php echo $language['language_id']; ?>]" value="" data-field="text" id="attribute-value-' + productId + '-' + attributeRow + '-<?php echo $language['language_id']; ?>" />';
  html += '</div>';
  <?php } ?>
  html += '</div>';
  html += '<button class="btn btn-sm btn-warning btn-save-new-attribute" data-product-id="' + productId + '" data-attribute-row="' + attributeRow + '"><i class="fa fa-save"></i> &nbsp; <?php echo $handy_text_attribute_new_save; ?></button>';
  html += '</div>';

  $('#' + identifierContainer).append(html);

  $.ajax({
    url: 'index.php?route=extension/module/handy/getAttributeGroupList&user_token=<?php echo $user_token; ?>',
    type: 'GET',
    dataType: 'json',
    beforeSend: function() { loaderOn(); },
    success: function(json) {
      if ('success' === json['status']) {
        if (debug) {
          console.log('success on attribute group selector get attribute group list : ' + '#' + identifierContainer);
        }

        html = '';
        var data = json['data'];
        html += '<option value=""><?php echo $handy_text_attribute_group_select; ?></option>';
        for (var key in data) {
          html += '<option value="' + data[key]['attribute_group_id'] + '">' + escapeHtml(data[key]['name']) + '</option>';
        }

        setTimeout(function () {
          $('#attribute-row-' + productId + '-' + attributeRow + '-attribute-form-attribute-group-selector').html(html);
        }, 500);
      } else {
        if (debug) {
          console.log('error on attribute selector get attribute list: ' + '#' + identifierContainer);
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
}


$('body').on('click', '.btn-save-new-attribute', function(e) {
  e.preventDefault();

  if (debug) {
    console.log('$(\'.btn-save-new-attribute\').click(function() is called');
  }

  var productId = $(this).data('product-id');
  var attributeRow = $(this).data('attribute-row');

  setTimeout(function () {
    initRemoveErrors('attribute-row-' + productId + '-' + attributeRow + '-attribute-form');
  }, 100); // Задержка

  if (debug) {
    console.log('productId : ' + productId);
    console.log('attributeRow : ' + attributeRow);
  }

  var attributeName = $('#attribute-value-' + productId + '-' + attributeRow + '-' + config_language_id).val();

  // todo - check input data
  var hasError = false;

  if("" === $('#attribute-row-' + productId + '-' + attributeRow + '-attribute-form-attribute-group-selector').val()) {
    hasError = true;
    $('#attribute-row-' + productId + '-' + attributeRow + '-attribute-form-attribute-group-selector').addClass('is-error');
  }

  <?php foreach ($languages as $language) { ?>
  if("" === $('#attribute-value-' + productId + '-' + attributeRow + '-<?php echo $language['language_id']; ?>').val()) {
    hasError = true;
    $('#attribute-value-' + productId + '-' + attributeRow + '-<?php echo $language['language_id']; ?>').addClass('is-error');
  }
  <?php } ?>

  if (hasError) {

  } else {
    var data = 'essence=add_new_attribute' + '&attribute_group_id=' + $('#attribute-row-' + productId + '-' + attributeRow + '-attribute-form-attribute-group-selector').val();

    <?php foreach ($languages as $language) { ?>
    data += '&attribute_description[<?php echo $language['language_id']; ?>]=' + $('#attribute-value-' + productId + '-' + attributeRow + '-<?php echo $language['language_id']; ?>').val();
    <?php } ?>

    $.ajax({
      url: 'index.php?route=extension/module/handy/productListLiveEdit&user_token=<?php echo $user_token; ?>',
      type: 'POST',
      dataType: 'json',
      data: data,
      beforeSend: function() { loaderOn(); },
      success: function(json) {
        console.log('request success on addAttributeToProduct()');
        if ('success' === json['status']) {
          console.log('answer success on addAttributeToProduct()');
          // todo - remove form - insert attribute name as label
          var attributeId = json['result'];

          //$('#attribute-row-' + productId + '-' + attributeRow + '-attribute-form').html('<div class="le-label">' + attributeName + ':</div>');

          $('#attribute-row-' + productId + '-' + attributeRow + '-attribute-form').html('<div class="le-label">'
            + '<a class="attribute-link" href="index.php?route=catalog/attribute/edit&user_token=<?php echo $user_token; ?>&attribute_id=' + attributeId + '" target="_blank">' + attributeName + '</a>'
            + ':</div>');

          addAttributeToProduct(attributeId, productId);

          setTimeout(function () {
            buildAttributeValuesOnAddNewAttribute('attribute-row-' + productId + '-' + attributeRow + '-attribute-form', attributeId, 'attribute-row-' + productId + '-' + attributeRow + '-attribute-form-selector');
            liveUpdateAttributeValueSelector();
          }, 100);

        } else {
          console.log('answer error');
        }
      },
      error: function( jqXHR, textStatus, errorThrown ){
        // Error ajax query
        console.log('AJAX query Error: ' + textStatus );
      },
      complete: function() { loaderOff(); }
    });
  }

});



function initRemoveErrors(identifier) {
  if (debug) {
    console.log('initRemoveErrors() is called with identifier : ' + identifier);
  }

  $('body ' + '#' + identifier + ' .is-error').on('change', function(e){
    $(this).removeClass('is-error');
  });
}

function buildAttributeValuesOnAddNewAttribute(identifierContainer, attributeId, identifierSelector) {
  if (debug) {
    console.log('--- buildAttributeValuesOnAddNewAttribute() function is called ---');
    if ('undefined' !== identifierSelector) {
      console.log('identifierSelector : ' + '#' + identifierSelector);
    }
    console.log('identifierContainer : ' + '#' + identifierContainer);
  }

/*
  if ('undefinded' === attributeId) {
    attributeId = $('#attribute-row-' + productId + '-' + attributeRow).data('attribute-id');
  }
*/

  if (undefined !== identifierSelector) {
    $('#' + identifierSelector).data('attribute-value-input-type', 'exist-in-db');
  }

  productId = $('#' + identifierContainer).data('product-id');
  attributeRow = $('#' + identifierContainer).data('attribute-row');

  html = '<div class="le-lang-values-container">';
  <?php foreach ($languages as $language) { ?>
  html += '<div class="le-lang-values-icon">';
  html += '<span class=""><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>';
  html += '</div>';
  html += '<div class="le-lang-values-content" data-product-id="' + productId + '" data-language-id="<?php echo $language['language_id']; ?>" data-attribute-id="' + attributeId + '">';
  html += '<input type="text" class="le-value attribute-value le-text" value="" data-field="text" id="attribute-value-' + productId + '-' + attributeRow + '-<?php echo $language['language_id']; ?>" />';
  html += '<select class="le-selector attribute-value-selector _live-added" data-target="#attribute-value-' + productId + '-' + attributeRow + '-<?php echo $language['language_id']; ?>" data-attribute-id="' + attributeId + '" data-language-id="<?php echo $language['language_id']; ?>" id="attribute-value-selector-' + productId + '-' + attributeRow + '-<?php echo $language['language_id']; ?>">';
  html += '<option value="" >----</option>';
  html += '</select>';
  html += '</div>';
  <?php } ?>
  html += '</div>';

  //$('#' + identifierSelector).after(html);
  $('#' + identifierContainer).append(html);

  setTimeout(function () {
    getValuesForAttibuteValuesSelector(identifierContainer);
		
		$('.attribute-value-selector').select2();
  }, 300);
}

function liveUpdateAttributeValueSelector() {
  $('.attributes .le-value.attribute-value').on('change', function(e){
    e.preventDefault();

    if (debug) {
      console.log('this value : ' + $(this).val());
    }

		var attributeId = $(this).parent().data('attribute-id');
		var languageId = $(this).parent().data('language-id');
		var value = $(this).val();

    var data = 'essence=edit_attribute_value'
      + '&product_id=' + $(this).parent().data('product-id')
      + '&language_id=' + languageId
      + '&attribute_id=' + attributeId
      + '&field=' + $(this).data('field')
      + '&value=' + encodeURIComponent(value);

    liveUpdateAjax(data, 'edit_attribute_value');

		// save atribute value to browser memory
		if (undefined === attributeValuesExist[attributeId]) {
			attributeValuesExist[attributeId] = {};
		}

		if (undefined === attributeValuesExist[attributeId][languageId]) {
			attributeValuesExist[attributeId][languageId] = [];
		}
		
		var result = $.inArray($(this).val(), attributeValuesExist[attributeId][languageId]);

		if (debug) {
      console.log('liveUpdateAttributeValueSelector() :: result');
      console.debug(result);
    }

		if (-1 === result) {
			attributeValuesExist[attributeId][languageId].push(value);
			initAttributeValueSelectors();
			
			if (debug) {
				console.log('add this value to memory :: ' + value);
			}
		}

  });
}

//liveUpdateAttributeValueSelector(); need to be init after dynamic content loading !

function addAttributeToProduct(attributeId, productId) {
  if (debug) {
    console.log('addAttributeToProduct() is called');
  }

  var data = 'essence=add_attribute_to_product'
    + '&attribute_id=' + attributeId
    + '&product_id=' + productId;

  $.ajax({
    url: 'index.php?route=extension/module/handy/productListLiveEdit&user_token=<?php echo $user_token; ?>',
    type: 'POST',
    dataType: 'json',
    data: data,
    beforeSend: function() { loaderOn(); },
    success: function(json) {
      console.log('request success on addAttributeToProduct()');
      if ('success' === json['status']) {
        console.log('answer success on addAttributeToProduct()');
      } else {
        console.log('answer error');
      }
    },
    error: function( jqXHR, textStatus, errorThrown ){
      // Error ajax query
      console.log('AJAX query Error: ' + textStatus );
    },
    complete: function() { loaderOff(); }
  });
}

function deleteAttributeFromProduct(productId, attributeId) {
  if (debug) {
    console.log('deleteAttributeFromProduct() is called with product_id : ' + productId + ' AND attribute_id : ' + attributeId);
  }

  var data = 'essence=delete_attribute_from_product'
    + '&attribute_id=' + attributeId
    + '&product_id=' + productId;

  $.ajax({
    url: 'index.php?route=extension/module/handy/productListLiveEdit&user_token=<?php echo $user_token; ?>',
    type: 'POST',
    dataType: 'json',
    data: data,
    beforeSend: function() { loaderOn(); },
    success: function(json) {
      console.log('request success on deleteAttributeFromProduct');
      if ('success' === json['status']) {
        console.log('answer success on deleteAttributeFromProduct');
      } else {
        console.log('answer error');
      }
    },
    error: function( jqXHR, textStatus, errorThrown ){
      // Error ajax query
      console.log('AJAX query Error: ' + textStatus );
    },
    complete: function() { loaderOff(); }
  });
}



</script>