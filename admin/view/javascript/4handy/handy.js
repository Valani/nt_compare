// Category Tree
function initCategoryTree() {
  console.log('initCategoryTree()');
  
  $('.categories-selector .has-children').each(function() {
    $(this).children('ul').hide();
  });
	
	$('.categories-selector .has-children input').each(function() {
		if ($(this).is(':checked')) {			
			$(this).parents('.has-children').children('.toggle-item').html('-').removeClass('closed');			
			$(this).parents('.has-children').children('ul').show(100);			
		}
  });

	$('.categories-selector .toggle-item').click(function() {
		if($(this).hasClass('closed')) {
			$(this).html('-');
			$(this).removeClass('closed');
			$(this).parent('.has-children').children('ul').show(100);
		} else {
			$(this).html('+');
			$(this).addClass('closed');
			$(this).parent('.has-children').children('ul').hide(100);
		}

  });
  
  $('body').on('click', '.categories-selector .all-subcategories-selector', function (e) {
    if ('notchecked' == $(this).attr('data-status')) {
      $(this).attr('data-status', 'checked');
      $(this).prev('label').parent('.has-children').find('input:not(:checked)').prop('checked', true).trigger('change');
      $(this).prev('label').parent('.has-children').find('.all-subcategories-selector').attr('data-status', 'checked');
    } else {
      $(this).attr('data-status', 'notchecked');
      $(this).prev('label').parent('.has-children').find('input:checked').prop('checked', false).trigger('change');
      $(this).prev('label').parent('.has-children').find('.all-subcategories-selector').attr('data-status', 'notchecked');
    }
  });
  
}

/*
function initCategoryTree2(selector) {
  console.log('initCategoryTree2()');
  
  $(selector + ' .has-children').each(function() {
    $(this).children('ul').hide();
  });
	
	$(selector + '.has-children input').each(function() {
		if ($(this).is(':checked')) {			
			$(this).parents('.has-children').children('.toggle-item').html('-').removeClass('closed');			
			$(this).parents('.has-children').children('ul').show(100);			
		}
  });

	$(selector + ' .toggle-item').click(function() {
		if($(this).hasClass('closed')) {
			$(this).html('-');
			$(this).removeClass('closed');
			$(this).parent('.has-children').children('ul').show(100);
		} else {
			$(this).html('+');
			$(this).addClass('closed');
			$(this).parent('.has-children').children('ul').hide(100);
		}

  });
  
  $('body').on('click', selector + ' .all-subcategories-selector', function (e) {
    if ('notchecked' == $(this).attr('data-status')) {
      $(this).attr('data-status', 'checked');
      $(this).prev('label').parent('.has-children').find('input:not(:checked)').prop('checked', true).trigger('change');
      $(this).prev('label').parent('.has-children').find('.all-subcategories-selector').attr('data-status', 'checked');
    } else {
      $(this).attr('data-status', 'notchecked');
      $(this).prev('label').parent('.has-children').find('input:checked').prop('checked', false).trigger('change');
      $(this).prev('label').parent('.has-children').find('.all-subcategories-selector').attr('data-status', 'notchecked');
    }
  });
  
}
*/