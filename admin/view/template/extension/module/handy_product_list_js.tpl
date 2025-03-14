<?php // for Centos correct file association ?>

<script src="view/javascript/4handy/handy.js"></script>

<script>


/* Live Edit
----------------------------------------------------------------------------- */

// remove Enter
$(function () {
  $('.le .le-value').keypress(function(e) {
    if (e.which == '13') {
      e.preventDefault();
      $(this).blur();
      $(this).parent().next('.le-row').children('.le-value').focus();
    }
  })
});

/*
// is uncomfortable
// Live update on Hover Out
// Иногда необходимо отправить изменения на сервер до потери фокуса
// К примеру, если отправка данных товара по клику на кнопку генерации SEO URL, будет выглядеть как баг

var canUpdateSimpleValueOnHoverOut = false;
var editedFiled = false;

// Ждем подгрузки динамического контента
setTimeout(function () {
	initLiveUpdateOnHoverOut();
}, 1300);

function initLiveUpdateOnHoverOut() {
	// Определяе, что элемент менялся
	$('.identity-content .le-value._simple-value, .description-content .le-value').on("input", function(e){
		console.log($(this).val());
		canUpdateSimpleValueOnHoverOut = true;
		editedFiled = this;
	});

	// Отслеживаем любое движение мышки - и, обновляем тот элемент, который только что менялся
	$(function(){
		$(document).mousemove(function(e) {
			if (canUpdateSimpleValueOnHoverOut) {
				canUpdateSimpleValueOnHoverOut = false;
				$(editedFiled).trigger('change');
				$(editedFiled).blur();
			}
		});
	});
}
*/

function liveUpdateAjax(data, marker, noicon) {
  var result = false;

  if (undefined === marker) {
    marker = '';
  } else {
    marker = marker + ' ';
  }

  if (undefined === noicon) {
    noicon = false;
  } else {
		noicon = true;
	}

  if (debug) {
    console.log('liveUpdateAjax() is called');
    console.log('this marker : ' + marker);
    console.log('this noicon : ' + noicon);
  }

  $.ajax({
    url: 'index.php?route=extension/module/handy/productListLiveEdit&user_token=<?php echo $user_token; ?>',
    type: 'POST',
    dataType: 'json',
    data: data,
    async: false, // !important for controllability & liveUpdateImageSorting
    beforeSend: function() { if (!noicon) {loaderOn();} },
    success: function(json) {
      console.log(marker + 'request success');
      if ('success' === json['status']) {
        console.log(marker + 'answer success');
      } else {
        console.log(marker + 'answer error');
      }

      if (undefined != json['result']) {
        result = json['result'];
      } else {
				if (false != result) {
					result = true;
				}
      }
    },
    error: function( jqXHR, textStatus, errorThrown ){
      // Error ajax query
      console.log('AJAX query Error: ' + textStatus );
    },
    complete: function() {
			if (!noicon) {loaderOff();}
    }
  });

  return result;
}


// Error Modals
function reportModal() {
  if (0 < reportData.length) {
    $("#handy-report .modal-title").text('<?php echo $handy_error_report_title; ?>');

    $.each( reportData, function( key, value ){
      $("#handy-report .modal-body").append(value);
    });

    setTimeout(function () {
      $("#handy-report").modal('show');
    }, 100);
  }
}

function resetReportData(){
  reportData = [];
  $("#handy-report .modal-title").text('');
  $("#handy-report .modal-body").html('');
}


// Loading Icon
function loaderOn() {
  $('.load-bar-container').addClass('_active');
	$('.load-bar-container').fadeIn(400);
}

function loaderOff() {
  setTimeout(function () {
		$('.load-bar-container').fadeOut(400, function() {
			$('.load-bar-container').removeClass('_active');
		});
  }, 100);
}

// todo remove?...
// was followed in image uploading only
/*
function onProgressBar() {
  // Show uploader gif
  if(!$('.load-bar-container').hasClass('_active')){
    $('.load-bar-container').addClass('_active');
  }
}

function offProgressBar() {
  // Hide uploader gif
  $('.load-bar-container').removeClass('_active');
  $('#load-bar-progress').html('');
}
*/




/* Image
----------------------------------------------------------------------------- */

// Image Manager - clone from admin/view/javascript/common.js
// for customize

var filemnagerImageClicked = '';

$(document).on('click', 'a[data-toggle=\'image-upload\']', function(e) {
  var $element = $(this);
  var $popover = $element.data('bs.popover'); // element has bs popover?

  e.preventDefault();

  filemnagerImageClicked = $element.children('input').val(); // customized

  // destroy all image popovers
  $('a[data-toggle="image-upload"]').popover('destroy');

  // remove flickering (do not re-add popover when clicking for removal)
  if ($popover) {
    return;
  }

  $element.popover({
    html: true,
		sanitize: false,
    placement: 'right',
    trigger: 'manual',
    content: function() {
      return '<button type="button" id="button-image" class="btn btn-primary"><i class="fa fa-pencil"></i></button> <button type="button" id="button-clear" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>';
    }
  });

  $element.popover('show');

  $('#button-image').on('click', function() {
    var $button = $(this);
    var $icon   = $button.find('> i');

    $('#modal-image').remove();

    $.ajax({
      url: 'index.php?route=common/filemanager&user_token=' + '<?php echo $user_token; ?>' + '&target=' + $element.parent().find('input').attr('id') + '&thumb=' + $element.attr('id'),
      dataType: 'html',
      beforeSend: function() {
        $button.prop('disabled', true);
        if ($icon.length) {
          $icon.attr('class', 'fa fa-circle-o-notch fa-spin');
        }
      },
      complete: function() {
        $button.prop('disabled', false);
        if ($icon.length) {
          $icon.attr('class', 'fa fa-pencil');
        }
      },
      success: function(html) {
        $('body').append('<div id="modal-image" class="modal">' + html + '</div>');

        $('#modal-image').modal('show');
      }
    });

    $element.popover('destroy');
  });

  $('#button-clear').on('click', function() {
    $element.find('img').attr('src', $element.find('img').attr('data-placeholder'));

		let image = $element.parent().find('input').val(); // customized

    $element.parent().find('input').val('');

    $element.popover('destroy');

    deleteImage('#' + $element.parent().attr('id'), image); // customized
  });
});


// update images on standart image manager selection
function applyFilemanagerSelection(target) {
  if (debug) {
    console.log('applyFilemanagerSelection() is called: ' + target);
  }

  var imageTargetBox = '#' + $(target).parent('.img-thumbnail-box').attr('id');

  if (debug) {
    console.log('imageTargetBox : ' + imageTargetBox);
  }

  var productId = $(imageTargetBox).closest('.image-uploader-container').data('product-id');

  if (debug) {
    console.log('productId : ' + productId);
  }

  // get photoType for ajax !? not for removeImageFromDOM only where repeat this algorithm
  var photoType = 'additional';

  if ($(imageTargetBox).hasClass('upload-photo-main')) {
    photoType = 'main';
  }

  if (debug) {
      console.log('INDEX OF ELEMENT : ' + $(imageTargetBox).index());
    }

  if ('main' == photoType) {
    var data = 'product_id=' + productId;
    data += '&essence=edit_image_main';
    data += '&image=' + $(imageTargetBox + ' input').val();
    data += '&image_old=' + filemnagerImageClicked;

    liveUpdateAjax(data, 'edit_image_main');
  } else {
    if ('' == filemnagerImageClicked) {
      // может быть пустым, если сначала добавили imageRow, а потом в него поместили фото из файлового менеджера
      var data = 'product_id=' + productId;
      data += '&essence=add_image_additional';
      data += '&image=' + $(imageTargetBox + ' input').val();
      data += '&image_additional_n=' + $(imageTargetBox).index(); // а если добавили несколько imageRow и только потом начали выбирать фото в них

      liveUpdateAjax(data, 'add_image_additional');
    } else {
      var data = 'product_id=' + productId;
      data += '&essence=edit_image_additional';
      data += '&image_new=' + $(imageTargetBox + ' input').val();
      data += '&image_old=' + filemnagerImageClicked; // может быть пустым, если добавляли через файлменеджер

			liveUpdateAjax(data, 'edit_image_additional');
    }
  }
}


// Sortable Images
function initImageSorting(liveAdded) {
  if (debug) {
    console.log('initImageSorting() is called ');
  }

  if(liveAdded) {
    var e = '.image-uploader-container._live-added';
  } else {
    var e = '.image-uploader-container';
  }

  $(e).each(function() {
    var list = this;
    var productId = $(this).data('product-id');

    Sortable.create(list, {
      onUpdate: function (evt){
        console.debug(evt);
        var item = evt.item; // the current dragged HTMLElement

        //evt.newIndex
        //evt.oldIndex

        if (0 === evt.newIndex) {
          console.log('#image-uploader-container-' + productId + ' .img-thumbnail-box');
          $('#image-uploader-container-' + productId + ' .img-thumbnail-box').removeClass('upload-photo-main');
          $('#image-uploader-container-' + productId + ' .img-thumbnail-box:first-child').addClass('upload-photo-main');

          liveUpdateMainImageAfterSorting(productId);
        }

        liveUpdateImageSorting(productId); // async false in liveUpdateMainImageAfterSorting & liveUpdateImageSorting
      }
    });

    if(flag) {
      $(list).removeClass('_live-added');
    }
  });
}

initImageSorting(false);


// update main
function liveUpdateMainImageAfterSorting(productId) {
  // newIndex всегда будет 0!
  // При сортировке при перемещении 7 на 0, они не просто меняются местами, а также сдвигают элементы от 0 до oldIndex
  // 0->1
  // 7->0
  // 1->2
  // 2->3 и тд
  // Главное: бывшая главная фотка (0), становится (1)
  //          Новая фотка становится (0)

  if (debug) {
    console.log('liveUpdateMainImageAfterSorting() is called');
  }

  //var arrayElements = $('#image-uploader-container-' + productId).children('.img-thumbnail-box').children('.img-thumbnail').children('input');
  var arrayElements = $('#image-uploader-container-' + productId + ' input');

  var array = [];

  $.each(arrayElements, function(index, item){
    array.push($(item).val());
  });

  var data = 'product_id=' + productId;
  data += '&essence=edit_image_main_after_sorting';
  data += '&image_old=' + array[1]; // бывший 0 свдигается и становится 1
  data += '&image_new=' + array[0];

  if (debug) {
    console.debug(data);
  }

  liveUpdateAjax(data, 'edit_image_main_after_sorting');
}

// update image sorting
function liveUpdateImageSorting(productId) {
  if (debug) {
    console.log('liveUpdateImageSorting() is called');
  }

  //var arrayElements = $('#image-uploader-container-' + productId).children('.img-thumbnail-box').children('input');
  var arrayElements = $('#image-uploader-container-' + productId + ' input');

  var data = 'product_id=' + productId;
  data += '&essence=edit_image_sorting';

  $.each(arrayElements, function(index, item){
    if (0 != index) {
      data += '&images[' + index + ']=' + $(item).val();
    }
  });

  liveUpdateAjax(data, 'edit_image_sorting');
}


$('body').on('click', '.btn-add-img-thumbnail-box', function(e){
  e.preventDefault();

  var productId = $(this).data('product-id');
  var target = '#image-uploader-container-' + productId;
  var imageRow = $(target).attr('data-image-row');

  if (debug) {
    console.log('imageRow on .btn-add-img-thumbnail-box : ' + imageRow);
  }

  var html = '';
  html += '<div id="thumb-image-box-' + productId + '-' + imageRow + '" class="img-thumbnail-box">';
  html += '<span class="fa fa-close btn-remove-photo"></span>';
  html += '<a href="#" id="thumb-image-' + productId + '-' + imageRow + '" class="img-thumbnail" data-toggle="image-upload" ><img src="<?php echo $placeholder; ?>" alt="" title="" data-placeholder="<?php echo $placeholder; ?>" /><input type="hidden" name="images[]" value="" id="input-image-' + productId + '-' + imageRow + '" /></a>';
  html += '</div>';

  $(target).append(html);

  imageRow++;

  $(target).attr('data-image-row', imageRow);

  if (debug) {
    console.log('imageRow on finish .btn-add-img-thumbnail-box : ' + imageRow);
  }
});


// Image Upload
var image_max_size_in_bytes = Number(<?php echo $upload_settings['max_size_in_mb']; ?>) * 1024 * 1024;
var reportData = [];

/*
$(document).on('click', '.upload-photo-main', function(e) {
  e.preventDefault();
  var productId = $(this).parent().data('product-id');
  $('#photo-main').data('productId', productId).trigger('click');
});
*/

$(document).on('click', '.upload-photo-additional', function(e) {
  e.preventDefault();
  var productId = $(this).data('product-id');
  $('#photo-additional').data('productId', productId).trigger('click');
});

$(document).on('change', '#photo-main', function() {
	resetReportData();

	if (debug) {
		console.log('files in onchange() #photo-main');
		console.debug(this.files);
	}

	upload(this.files, $(this).data('productId'), 'main');
});

$(document).on('change', '#photo-additional', function() {
	resetReportData();

	if (debug) {
		console.log('files in onchange() #photo-additional');
		console.debug(this.files);
	}

	upload(this.files, $(this).data('productId'), 'additional');
});


function upload(files, productId, photoType, obj) {
  console.log('files in upload()');
  console.debug(files);

  if(undefined === obj) obj = false;

  var categoryId = 0;
  var productInfo = '&product_id=' + productId;

	$('#input-main-category-' + productId).removeClass('is-error');

  <?php
  // Upload in category dir
  if ('dir_for_category' == $upload_settings['upload_mode']) {
    if ($has_main_category_column && $getProductMainCategoryIdExist) { ?>
    // if has main category field
		// A! Note: Main Cat Selector - A
    categoryId = Number($('#products-list-row-' + productId).attr('data-main-category-id'));

    if (debug) {
      console.log('productId : ' + productId);
      console.log('categoryId : ' + categoryId);
    }

    if (!categoryId) {
      reportData.push('<p><?php echo $handy_upload_error_no_category_main; ?>!</p>');

			$('#input-main-category-' + productId).addClass('is-error');
    }
    <?php } else { ?>
    // if hasn't main category field - get the category of the largest nest

    var max = 0;
    var hasCheckedCats = false;
    $('#categories-selector-container-' + productId + ' input:checkbox:checked').each(function(){
      hasCheckedCats = true;

      if (max < $(this).val()) {
        max = $(this).val();
      }

    });

    if (hasCheckedCats) {
      categoryId = max;
    } else {
      reportData.push('<p><?php echo $handy_upload_error_no_category; ?>!</p>');
    }
    <?php }
  } ?>


  <?php
  // Rename mode & prepare data if by_formula
  if ('by_formula' == $upload_settings['rename_mode']) { ?>

		$('#name-' + productId + '-<?php echo $source_language; ?>').removeClass('is-error');
		$('#model-' + productId + '-<?php echo $source_language; ?>').removeClass('is-error');
		$('#sku-' + productId + '-<?php echo $source_language; ?>').removeClass('is-error');

    if ('' != $('#name-' + productId + '-<?php echo $source_language; ?>').val()) {
      productInfo += '&name=' + encodeURIComponent($('#name-' + productId + '-<?php echo $source_language; ?>').val());
    } else {
      if (-1 !== '<?php echo $upload_settings['rename_formula']; ?>'.indexOf('product_name')) {
				setTimeout(function () {
					$('#name-' + productId + '-<?php echo $source_language; ?>').addClass('is-error');
				}, 100);

        reportData.push('<p><?php echo $handy_upload_error_no_product_name; ?>!</p>');
      }
    }

    if ('' != $('#model-' + productId).val()) {
      productInfo += '&model=' + encodeURIComponent($('#model-' + productId).val());
    } else {
      if (-1 !== '<?php echo $upload_settings['rename_formula']; ?>'.indexOf('model')) {
        reportData.push('<p><?php echo $handy_upload_error_no_model; ?>!</p>');

				setTimeout(function () {
					$('#model-' + productId + '-<?php echo $source_language; ?>').addClass('is-error');
				}, 100);
      }
    }

    if ('' != $('#sku-' + productId).val()) {
      productInfo += '&sku=' + encodeURIComponent($('#sku-' + productId).val());
    } else {
      if (-1 !== '<?php echo $upload_settings['rename_formula']; ?>'.indexOf('sku')) {
        reportData.push('<p><?php echo $handy_upload_error_no_sku; ?>!</p>');

				setTimeout(function () {
					$('#sku-' + productId + '-<?php echo $source_language; ?>').addClass('is-error');
				}, 100);
      }
    }
  <?php } ?>

	console.log('UPLOAD IMAGE : productInfo');
	console.debug(productInfo);


  var imageRow;
  var productN = '&image_additional_n=' + $('#image-uploader-container-' + productId).attr('data-image-row');

	$('#name-' + productId + '-<?php echo $config_language_id; ?>').removeClass('is-error');

  if (reportData.length < 1) {
    $.each( files, function( key, value ){
      var data = new FormData(); // !!
      data.append( key, value ); // !!

      //console.log('size' + value.size);
      //console.log('name' + value.name);

      if (image_max_size_in_bytes < value.size) {
        reportData.push('<p>' + '<?php echo $handy_upload_error_max_size; ?>'.replace("[file]", value.name) + '!</p>');
      } else {
        setTimeout(function () {
          // todo ...
          $.ajax({
            url: 'index.php?route=extension/module/handy/upload&dir=products&category_id=' + categoryId + productInfo + '&photo_type=' + photoType + productN + '&user_token=<?php echo $user_token; ?>',
            type: 'POST',
            data: data,
            cache: false,
            dataType: 'json',
            processData: false, // Don't process the files
            contentType: false, // string query
            async: false,
            beforeSend: function() {
              loaderOn();
            },
            xhr: function(){
              //upload Progress
              var xhr = $.ajaxSettings.xhr();
              if (xhr.upload) {
                xhr.upload.addEventListener('progress', function(event) {
                  var percent = 0;
                  var position = event.loaded || event.position;
                  var total = event.total;
                  if (event.lengthComputable) {
                    percent = Math.ceil(position / total * 100);
                  }
                  //update progressbar
                  $('#load-bar-progress').css("width", + percent +"%");
                  $('#load-bar-progress').text(percent +"%");
                  console.log(percent +"%");
                }, true);
              }
              return xhr;
            },
            success: function( response, textStatus, jqXHR ){
              if (debug) {
                console.debug(response);
              }

              if( response.answer == 'success' ){
                if ('main' == photoType) {
                  // по факту главное фото пока не загружается вообще никак - главным фото становится только в результате сортировки
                  $('#image-uploader-container-' + productId + ' .upload-photo-main').attr('src', response.file.thumb);

                } else {

                  imageRow = $('#image-uploader-container-' + productId).attr('data-image-row');

                  var html = '';
                  html += '<div id="thumb-image-box-' + productId + '-' + imageRow + '" class="img-thumbnail-box">';
                  html += '<span class="fa fa-close btn-remove-photo"></span>';
                  html += '<a href="#" id="thumb-image-' + productId + '-' + imageRow + '" class="img-thumbnail" data-toggle="image-upload" ><img src="' + response.file.thumb + '" alt="" title="" data-placeholder="<?php echo $placeholder; ?>" /><input type="hidden" name="images[]" value="' + response.file.image + '" id="input-image-' + productId + '-' + imageRow + '" /></a>';
                  html += '</div>';

                  $('#image-uploader-container-' + productId).append(html);

                  imageRow++;
                  $('#image-uploader-container-' + productId).attr('data-image-row', imageRow);
                  productN = '&image_additional_n=' + imageRow;
                }

								checkMainPhotoContainer(productId);

              } else {
								// Если ошибка связана с тем, что нет названия товара, то response.answer == 'success'...
								// Это происходит в блоке: Rename mode & prepare data if by_formula
								// А тут тогда что??
                $('.load-bar-container').removeClass('_active');
                reportData.push('<p>' + response.answer_description + '</p>');
              }
            },
            error: function( jqXHR, textStatus, errorThrown ){
              console.log('AJAX query Error: ' + textStatus + ' on index.php?route=extension/module/handy/upload'); // Error ajax query
            },
            complete: function() {
              // Hide uploader gif
              setTimeout(function () {
                loaderOff();
                if (obj) {
                  obj.removeClass('drop');
                }
              }, 100);
            },
          });
        }, 200);
      }
    });
  }

  setTimeout(function () {
		console.log('<?php echo $handy_text_report_log; ?>');
		console.debug(reportData);
		reportModal();
	}, 800);
}

function checkMainPhotoContainer(productId) {
	// Вызывать ТОЛЬКО в случае успешной загрузки!!
	//
	// Если есть блок с доп фотографиями и при этом нет главной фотографии
	// Первую фотографию делаем главной
	// Образовавшийся пустой блок - удаляем вместе с записью о фото, которое изначально было addition

	// Помним, что фотки распределяются по своим местам в момент загрузки в uploader!

	if (debug) {
    console.log('checkMainPhoto() is called ');
  }

	var mainImageValue = $('#image-uploader-container-' + productId + ' .upload-photo-main .img-thumbnail input').attr('value');

	if (!mainImageValue) {
		firstImageAdditional = $('#image-uploader-container-' + productId + ' .upload-photo-main').next('.img-thumbnail-box .img-thumbnail input');

		if (!firstImageAdditional) return false;

		$('#image-uploader-container-' + productId + ' .img-thumbnail-box:first-child').remove();
		$('#image-uploader-container-' + productId + ' .img-thumbnail-box:first-child').addClass('upload-photo-main');

		var data = 'product_id=' + productId;
		data += '&essence=edit_image_main_from_first_item';
		data += '&image_new=' + $('#image-uploader-container-' + productId + ' .img-thumbnail-box:first-child input').attr('value');

		if (debug) {
			console.debug(data);
		}

		liveUpdateAjax(data, 'edit_image_main_from_first_item in checkMainPhoto()');

	}
}


// Drag & Drop Images
// based on - https://habr.com/post/125424/

function initDrugNDrop() {
  // maxFileSize = 1000000;
  $('.dropZone').each(function() {
    var dropZone = $(this);

    // Проверка поддержки браузером
    if (typeof (window.FileReader) == 'undefined') {
      dropZone.text('Не поддерживается браузером!');
      dropZone.addClass('error');
    }

    // Добавляем класс hover при наведении
    dropZone[0].ondragover = function () {
      dropZone.addClass('hover');
      return false;
    };

    // Убираем класс hover
    dropZone[0].ondragleave = function () {
      dropZone.removeClass('hover');
      return false;
    };

    // Обрабатываем событие Drop
    dropZone[0].ondrop = function (event) {
      event.preventDefault();
      dropZone.removeClass('hover');
      dropZone.addClass('drop');

      // customized . begin
      if (debug) {
        console.log('Ура! Обработчик бросателя вызван!');
      }

      resetReportData();

      //var file = event.dataTransfer.files[0];
      var files = event.dataTransfer.files;

      if (debug) {
        console.log('files in drag & drop ()');
        console.debug(files);
      }

      upload(files, $(this).data('productId'), 'additional', dropZone);

      // customized . end

      // Проверяем размер файла
      /*
      if (file.size > maxFileSize) {
        dropZone.text('Файл слишком большой!');
        dropZone.addClass('error');
        return false;
      }
      */

      // Создаем запрос
      /*
      var xhr = new XMLHttpRequest();
      xhr.upload.addEventListener('progress', uploadProgress, false);
      xhr.onreadystatechange = stateChange;
      xhr.open('POST', 'index.php?route=extension/module/handy/upload&dir=products&category_id=' + categoryId + '&user_token=<?php echo $user_token; ?>');
      xhr.setRequestHeader('X-FILE-NAME', file.name);
      xhr.send(file);
      */
    };

    // Показываем процент загрузки
    /*
    function uploadProgress(event) {
      var percent = parseInt(event.loaded / event.total * 100);
      dropZone.text('Загрузка: ' + percent + '%');
    }
    */

    // Пост обрабочик
    /*
    function stateChange(event) {
      if (event.target.readyState == 4) {
        if (event.target.status == 200) {
          dropZone.text('Загрузка успешно завершена!');
        } else {
          dropZone.text('Произошла ошибка!');
          dropZone.addClass('error');
        }
      }
    }
    */

  });

}


// Delete Images
$('body').on('click', '.btn-remove-photo', function() {
  if (debug) {
    console.log('.btn-remove-photo click() is called');
  }

  var imageTargetBox = '#' + $(this).parent().attr('id');

  deleteImage(imageTargetBox);

});

function deleteImage(imageTargetBox, image = '') {
  if (debug) {
    console.log('deleteImage() is called with imageTargetBox : ' + imageTargetBox);
  }

  var productId = $(imageTargetBox).closest('.image-uploader-container').data('product-id');

  if (debug) {
    console.log('productId : ' + productId);
  }

	// Не точний спосіб...
	if (image == '') {
	  image = $(imageTargetBox + ' input').val();
	}

  // get photoType for ajax !? not for removeImageFromDOM only where repeat this algorithm
  var photoType = 'additional';

  if ($(imageTargetBox).hasClass('upload-photo-main')) {
    photoType = 'main';
  }

  var data = 'product_id=' + productId;
  data += '&essence=delete_image_' + photoType;
  data += '&image=' + image;

  if (debug) {
    console.debug(data);
  }

  liveUpdateAjax(data, 'delete_image_' + photoType);

  removeImageFromDOM(imageTargetBox, photoType);
}

function removeImageFromDOM(imageTargetBox, photoType) {
  if (debug) {
    console.log('removeImageFromDOM() is called with imageTargetBox : ' + imageTargetBox);
  }

  if ('main' == photoType) {
    $(imageTargetBox).find('img').attr('src', $(imageTargetBox).find('img').attr('data-placeholder'));
    $(imageTargetBox).find('input').val('');
  } else {
    $(imageTargetBox).remove();
  }
}



/* Category
----------------------------------------------------------------------------- */

//initCategoryTree() is called by dynamicUpdate() !!!

// #products-list .categories-selector to separate Filter category Tree and Content Category Tree
$('body').on('change', '#products-list .categories-selector input', function(e) {
  e.preventDefault();

  if (debug) {
    console.log('liveUpdateCategories() is called');
    console.log('this value : ' + $(this).val());
  }

  var productId, actionValue;

  productId = $(this).closest('.categories-selector').data('product-id');

  if($(this).is(':checked')) {
    actionValue = 'add';
  } else {
    actionValue = 'delete';
  }
	
  if (debug) {
    console.log('productId : ' + productId);
    console.log('actionValue : ' + actionValue);
  }

  var data = 'essence=edit_categories'
    + '&product_id=' + productId
    + '&field=' + 'category'
    + '&value=' + encodeURIComponent($(this).val())
    + '&action=' + actionValue;

  liveUpdateAjax(data, 'edit_categories');

});

$('body').on('change', '.category .le-value._simple-value', function(e){
  e.preventDefault();

  if (debug) {
    console.log('this value : ' + $(this).val());
  }

  var data = 'essence=edit_main_category'
    + '&product_id=' + $(this).data('product-id')
    + '&main_category_id=' + $(this).val();

  liveUpdateAjax(data, 'edit_main_category');
	
	// A! Note: Main Cat Selector - B
	$('#products-list-row-' + $(this).data('product-id')).attr('data-main-category-id', $(this).val());
});

function initCategoryAutocomplete() {
	if (debug) {
    console.log('initCategoryAutocomplete() is called');
  }
	
	$('.category-autocomplete').each(function() {
    var productId = $(this).data('product-id');
		
		if (debug) {
			console.log('initCategoryAutocomplete() productId : ' + productId);
		}

    $(this).autocomplete({
			'source': function(request, response) {
				$.ajax({
					url: 'index.php?route=catalog/category/autocomplete&user_token=<?php echo $user_token; ?>&filter_name=' + encodeURIComponent(request),
					dataType: 'json',
					success: function(json) {
						response($.map(json, function(item) {
							return {
								label: item['name'],
								value: item['category_id']
							};
						}));
					}
				});
			},
			'select': function(item) {
				$(this).val('');

				$('#category-autocomplete-' + productId + ' ' + item['value']).remove();

				$('#category-autocomplete-' + productId).append('<div id="category-autocomplete-' + productId + '-' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="categories[' + productId + '][]" value="' + item['value'] + '" /></div>');
				
				var data = 'essence=edit_categories'
					+ '&product_id=' + productId
					+ '&field=' + 'category'
					+ '&value=' + encodeURIComponent(item['value'])
					+ '&action=add';

				liveUpdateAjax(data, 'edit_categories');
				
			}
		});
	});
	
	$('.category-well').on('click', '.fa-minus-circle', function() {
		var productId = $(this).parent().parent('.category-well').data('product-id');
		
		if (debug) {
			console.log('.fa-minus-circle click() is called');
		}

		var data = 'essence=edit_categories'
			+ '&product_id=' + productId
			+ '&field=' + 'category'
			+ '&value=' + encodeURIComponent($(this).next('input[type="hidden"]').val())
			+ '&action=delete';

		liveUpdateAjax(data, 'edit_categories');
	
		$(this).parent().remove();
	});	
}




/* Identity
----------------------------------------------------------------------------- */

/* Price Format */
$('body').on('input', '.identity-content .le-value._price', function() {
  str = $(this).val();
  str = str.replace(/,/g, '.');
  str = str.replace(/[^.0-9]/gim,'');
  $(this).val(str);
});


/* Simple Value Change */

// sku & model automatic changes
var oldValue = '';

$('body').on('click', '.identity-content .le-value._simple-value', function(e) {
  e.preventDefault();

	if ('sku' == $(this).data('field') || 'model' == $(this).data('field')) {
		oldValue = $(this).val();
	} else {
		oldValue = '';
	}
	//console.log('oldValue after click() :: ' + oldValue);
});


$('body').on('change', '.identity-content .le-value._simple-value', function(e) {
  e.preventDefault();

  if (debug) {
    console.log('this value : ' + $(this).val());
  }

  var data = 'essence=edit_identity'
    + '&product_id=' + $(this).closest('.identity-content').data('product-id')
    + '&field=' + $(this).data('field')
    + '&value=' + encodeURIComponent($(this).val());

  liveUpdateAjax(data, 'edit_identity');

	if ('sku' == $(this).data('field') || 'model' == $(this).data('field')) {
		replaceOldValueInDescriptions($(this));
	}
	
	//canUpdateSimpleValueOnHoverOut = false;
});

var modelAutoChange = false;
var skuAutoChange = false;
<?php if ($module_handy_model_automatic_change) { ?>modelAutoChange = true;<?php } ?>
<?php if ($module_handy_sku_automatic_change) { ?>skuAutoChange = true;<?php } ?>

function replaceOldValueInDescriptions(element) {
	if ('model' == element.data('field') && !modelAutoChange) return;
	if ('sku' == element.data('field') && !skuAutoChange) return;
	
	if (oldValue) {
		var newValue = element.val();
	
		$('#description-' + element.parent().parent().data('product-id') + ' textarea').each(function() {
			if (-1 !== $(this).val().indexOf(oldValue)) {
				$(this).val( $(this).val().split(oldValue).join(newValue) );
				$(this).change();
			}
		});
	}
}



$('body').on('change', '.product-reward-value', function() {
	if (debug) {
		console.log('product-reward-value click()');
    console.log('.product-reward-value : ' + $(this).val());
  }

  var data = 'essence=edit_product_reward'
    + '&product_id=' + $(this).closest('.identity-content').data('product-id')
    + '&customer_group_id=' + $(this).data('customer-group-id')
    + '&value=' + encodeURIComponent($(this).val());

  liveUpdateAjax(data, 'edit_product_reward');
});

/* Discount */
$('body').on('click', '.btn-remove-discount', function(e) {
  deleteDiscount($($(this).data('target')).data('product-id'), $($(this).data('target')).data('product-discount-id'));
  $($(this).data('target')).remove();
});

$('body').on('click', '.btn-add-discount', function(e) {
  if(debug) {
    console.log('.btn-add-discount click() is called');
  }

  var productId = $(this).data("product-id");
  var productDiscountId = 0;
  var discountRow = $(this).data("discount-row");
  var identifierRow = 'discount-row-' + productId + '-' + discountRow;

  if(debug) {
    console.log('productId : ' + productId);
    console.log('discountRow : ' + discountRow);
    console.log('identifierRow : ' + identifierRow);
  }

  // todo
  // Вставляем новую скидку в базу с предустановленными значениями!
  // Получаем productDiscountId
  // Добавляем форму для редактирования скидки

  var html = '';
  html += '<div id="discount-row-' + productId + '-' + discountRow + '" class="discount-row" data-product-id="' + productId + '" data-product-discount-id="' + productDiscountId + '" data-discount-row="' + discountRow + '">';
  html += '<div class="pull-right"><a type="button" class="btn-remove-discount" data-target="#discount-row-' + productId + '-' + discountRow + '" data-toggle="tooltip" title="<?php echo $button_remove; ?>"><i class="fa fa-close"></i></a></div>';
  html += '<div class="le-row">';
  html += '<span class="le-label _customer-group"><?php echo $handy_entry_customer_group; ?></span>';
  html += '<select class="le-value _discount-value le-selector discount-customer-group" data-field="customer_group_id">';
  <?php foreach ($customer_groups as $customer_group) { ?>
  html += '<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>';
  <?php } ?>
  html += '</select>';
  html += '</div>';
	html += '<div class="le-row">';
  html += '<span class="le-label _quantity"><?php echo $entry_quantity; ?></span>';
  html += '<input type="text" value="" placeholder="<?php echo $entry_quantity; ?>" class="le-value _discount-value discount-quantity" data-field="quantity" />';
  html += '</div>';
  html += '<div class="le-row">';
  html += '<span class="le-label _priority"><?php echo $entry_priority; ?></span>';
  html += '<input type="text" value="" placeholder="<?php echo $entry_priority; ?>" class="le-value _discount-value discount-priority" data-field="priority" />';
  html += '</div>';
  html += '<div class="le-row">';
  html += '<span class="le-label"><?php echo $entry_price; ?></span>';
  html += '<input type="text" value="" placeholder="<?php echo $entry_price; ?>" class="le-value _discount-value discount-price" data-field="price" />';
  html += '</div>';
  html += '<div class="le-row _date">';
  html += '<span class="le-label _date"><?php echo $handy_entry_date_start; ?></span>';
  html += '<div class="date">';
  html += '<input type="text" value="" placeholder="<?php echo $handy_entry_date_start; ?>" data-date-format="YYYY-MM-DD" class="le-value _discount-value _date  discount-date-start" data-field="date_start" />';
  html += '<span class="input-group-btn">';
  html += '<button class="btn-calendar" type="button"><i class="fa fa-calendar"></i></button>';
  html += '</span></div>';
  html += '</div>';
  html += '<div class="le-row _date">';
  html += '<span class="le-label _date"><?php echo $handy_entry_date_end; ?></span>';
  html += '<div class="date">';
  html += '<input type="text" value="" placeholder="<?php echo $handy_entry_date_end; ?>" data-date-format="YYYY-MM-DD" class="le-value _discount-value _date discount-date-end" data-field="date_end" />';
  html += '<span class="input-group-btn">';
  html += '<button class="btn-calendar" type="button"><i class="fa fa-calendar"></i></button>';
  html += '</span></div>';
  html += '</div>';
  html += '</div>';

  $('#discount-container-' + productId).append(html);

  $(this).data("discount-row", discountRow + 1); // Сохраняем порядковый номер ряда
  setTimeout(function () {
    initButtonCalendar();
		initButtonCalendarWithTime();
    liveAddDiscount(productId, discountRow);
  }, 100);
});

function liveAddDiscount(productId, discountRow) {
  if (debug) {
    console.log('liveAddNewDiscount() is called');
    console.log('productId : ' + productId);
    console.log('discountRow : ' + discountRow);
  }

  var target = '#discount-row-' + productId + '-' + discountRow;
  var discountPrice = $('#identity-content-' + productId + ' .le-value._price').val() * 0.9; // price -10%
  var discountPrice = discountPrice.toFixed(4);

  $('#discount-row-' + productId + '-' + discountRow+ ' .le-value.discount-price').val(discountPrice);

	var customerGroupId = $('#discount-row-' + productId + '-' + discountRow + ' .discount-customer-group').val();

  var data = 'essence=add_discount'
    + '&product_id=' + productId
    + '&customer_group_id=' + customerGroupId
    + '&price=' + discountPrice;

  $.ajax({
    url: 'index.php?route=extension/module/handy/productListLiveEdit&user_token=<?php echo $user_token; ?>',
    type: 'POST',
    dataType: 'json',
    data: data,
    beforeSend: function() { loaderOn(); },
    success: function(json) {
      console.log('request success on liveAddNewDiscount()');
      if ('success' === json['status']) {
        var productDiscountId = json['result'];
        console.log('productDiscountId : ' + productDiscountId);
        $(target).attr('data-product-discount-id', productDiscountId);
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

/*
 * A! Multiple fields handler!
 */
$('body').on('change', '.identity-content .discount-row .le-value', function(e) {
  e.preventDefault();

  if (debug) {
    console.log('liveUpdateDiscount() is called');
    console.log('this value : ' + $(this).val());
  }

  var productId, productDiscountId;
  var field = $(this).data('field');
	var value = $(this).val();

  if (-1 !== field.indexOf("date")) {
   productId = $(this).parent().parent().parent().data('product-id');
   productDiscountId = $(this).parent().parent().parent().data('product-discount-id');
  } else {
    productId = $(this).parent().parent().data('product-id');
    productDiscountId = $(this).parent().parent().data('product-discount-id');
  }

  var data = 'essence=edit_discount'
    + '&product_discount_id=' + productDiscountId
    + '&product_id=' + productId
    + '&field=' + field
    + '&value=' + encodeURIComponent(value);

  liveUpdateAjax(data, 'edit_discount');

});

function deleteDiscount(productId, productDiscountId) {
  if (debug) {
    console.log('deleteDiscount() is called with product_id : ' + productId + ' AND product_discount_id : ' + productDiscountId);
  }

  var data = 'essence=delete_discount'
    + '&product_discount_id=' + productDiscountId
    + '&product_id=' + productId;

  $.ajax({
    url: 'index.php?route=extension/module/handy/productListLiveEdit&user_token=<?php echo $user_token; ?>',
    type: 'POST',
    dataType: 'json',
    data: data,
    beforeSend: function() { loaderOn(); },
    success: function(json) {
      console.log('request success on deleteDiscount');
      if ('success' === json['status']) {
        console.log('answer success on deleteDiscount');
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


/* Special */
$('body').on('click', '.btn-remove-special', function(e) {
  deleteSpecial($($(this).data('target')).data('product-id'), $($(this).data('target')).data('product-special-id'));
  $($(this).data('target')).remove();
});

$('body').on('click', '.btn-add-special', function(e) {
  if(debug) {
    console.log('.btn-add-special click() is called');
  }

  var productId = $(this).data("product-id");
  var productSpecialId = 0;
  var specialRow = $(this).data("special-row");
  var identifierRow = 'special-row-' + productId + '-' + specialRow;

  if(debug) {
    console.log('productId : ' + productId);
    console.log('specialRow : ' + specialRow);
    console.log('identifierRow : ' + identifierRow);
  }

  // todo
  // Вставляем новую скидку в базу с предустановленными значениями!
  // Получаем productSpecialId
  // Добавляем форму для редактирования скидки

  var html = '';
  html += '<div id="special-row-' + productId + '-' + specialRow + '" class="special-row" data-product-id="' + productId + '" data-product-special-id="' + productSpecialId + '" data-special-row="' + specialRow + '">';
  html += '<div class="pull-right"><a type="button" class="btn-remove-special" data-target="#special-row-' + productId + '-' + specialRow + '" data-toggle="tooltip" title="<?php echo $button_remove; ?>"><i class="fa fa-close"></i></a></div>';
  html += '<div class="le-row">';
  html += '<span class="le-label _customer-group"><?php echo $handy_entry_customer_group; ?></span>';
  html += '<select class="le-value _special-value le-selector special-customer-group" data-field="customer_group_id">';
  <?php foreach ($customer_groups as $customer_group) { ?>
  html += '<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>';
  <?php } ?>
  html += '</select>';
  html += '</div>';
  html += '<div class="le-row">';
  html += '<span class="le-label _priority"><?php echo $entry_priority; ?></span>';
  html += '<input type="text" value="" placeholder="<?php echo $entry_priority; ?>" class="le-value _special-value special-priority" data-field="priority" />';
  html += '</div>';
  html += '<div class="le-row">';
  html += '<span class="le-label"><?php echo $entry_price; ?></span>';
  html += '<input type="text" value="" placeholder="<?php echo $entry_price; ?>" class="le-value _special-value special-price" data-field="price" />';
  html += '</div>';
  html += '<div class="le-row _date">';
  html += '<span class="le-label _date"><?php echo $handy_entry_date_start; ?></span>';
  html += '<div class="date">';
  html += '<input type="text" value="" placeholder="<?php echo $handy_entry_date_start; ?>" data-date-format="YYYY-MM-DD" class="le-value _special-value _date  special-date-start" data-field="date_start" />';
  html += '<span class="input-group-btn">';
  html += '<button class="btn-calendar" type="button"><i class="fa fa-calendar"></i></button>';
  html += '</span></div>';
  html += '</div>';
  html += '<div class="le-row _date">';
  html += '<span class="le-label _date"><?php echo $handy_entry_date_end; ?></span>';
  html += '<div class="date">';
  html += '<input type="text" value="" placeholder="<?php echo $handy_entry_date_end; ?>" data-date-format="YYYY-MM-DD" class="le-value _special-value _date special-date-end" data-field="date_end" />';
  html += '<span class="input-group-btn">';
  html += '<button class="btn-calendar" type="button"><i class="fa fa-calendar"></i></button>';
  html += '</span></div>';
  html += '</div>';
  html += '</div>';

  $('#special-container-' + productId).append(html);

  $(this).data("special-row", specialRow + 1); // Сохраняем порядковый номер ряда
  setTimeout(function () {
    initButtonCalendar();
		initButtonCalendarWithTime();
    liveAddSpecial(productId, specialRow);
  }, 100);
});

function liveAddSpecial(productId, specialRow) {
  if (debug) {
    console.log('liveAddNewSpecial() is called');
    console.log('productId : ' + productId);
    console.log('specialRow : ' + specialRow);
  }

  var target = '#special-row-' + productId + '-' + specialRow;
  var specialPrice = $('#identity-content-' + productId + ' .le-value._price').val() * 0.9; // price -10%
  var specialPrice = specialPrice.toFixed(4);

  $('#special-row-' + productId + '-' + specialRow+ ' .le-value.special-price').val(specialPrice);

	var customerGroupId = $('#special-row-' + productId + '-' + specialRow + ' .special-customer-group').val();

  var data = 'essence=add_special'
    + '&product_id=' + productId
    + '&customer_group_id=' + customerGroupId
    + '&price=' + specialPrice;

  $.ajax({
    url: 'index.php?route=extension/module/handy/productListLiveEdit&user_token=<?php echo $user_token; ?>',
    type: 'POST',
    dataType: 'json',
    data: data,
    beforeSend: function() { loaderOn(); },
    success: function(json) {
      console.log('request success on liveAddNewSpecial()');
      if ('success' === json['status']) {
        var productSpecialId = json['result'];
        console.log('productSpecialId : ' + productSpecialId);
        $(target).attr('data-product-special-id', productSpecialId);
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

/*
 * A! Multiple fields handler!
 */
$('body').on('change', '.identity-content .special-row .le-value', function(e) {
  e.preventDefault();

  if (debug) {
    console.log('liveUpdateSpecial() is called');
    console.log('this value : ' + $(this).val());
  }

  var productId, productSpecialId;
  var field = $(this).data('field');
	var value = $(this).val();

  if (-1 !== field.indexOf("date")) {
   productId = $(this).parent().parent().parent().data('product-id');
   productSpecialId = $(this).parent().parent().parent().data('product-special-id');
  } else {
    productId = $(this).parent().parent().data('product-id');
    productSpecialId = $(this).parent().parent().data('product-special-id');
  }

  var data = 'essence=edit_special'
    + '&product_special_id=' + productSpecialId
    + '&product_id=' + productId
    + '&field=' + field
    + '&value=' + encodeURIComponent(value);

  liveUpdateAjax(data, 'edit_special');

});

function deleteSpecial(productId, productSpecialId) {
  if (debug) {
    console.log('deleteSpecial() is called with product_id : ' + productId + ' AND product_special_id : ' + productSpecialId);
  }

  var data = 'essence=delete_special'
    + '&product_special_id=' + productSpecialId
    + '&product_id=' + productId;

  $.ajax({
    url: 'index.php?route=extension/module/handy/productListLiveEdit&user_token=<?php echo $user_token; ?>',
    type: 'POST',
    dataType: 'json',
    data: data,
    beforeSend: function() { loaderOn(); },
    success: function(json) {
      console.log('request success on deleteSpecial');
      if ('success' === json['status']) {
        console.log('answer success on deleteSpecial');
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

function initButtonCalendar() {
	console.log('initButtonCalendar() is called');

  $('.date').datetimepicker({
    pickTime: false,
  }).on('dp.change', function (e) {
    $(e.currentTarget).children('.le-value').trigger('change');
  });
}

function initButtonCalendarWithTime() {
	console.log('initButtonCalendarWithTime() is called');
	
  $('.datetime').datetimepicker({
    pickDate: true,
    pickTime: true,
  }).on('dp.change', function (e) {
    $(e.currentTarget).children('.le-value').trigger('change');
  });
}

$('body').on('change', 'input[name="product_store[]"]', function(e) {
  if(debug) {
    console.log('input[name="product_store"] change() is called');
  }

  var flag = '';

  var productId = $(this).closest('.input-store').data("product-id");

  if ($(this).prop('checked')) {
    flag = 'add_product_to_store';
  } else {
    flag = 'delete_product_from_store';
  }

  var data = 'essence=' + flag
    + '&product_id=' + productId
    + '&store_id=' + $(this).val();

  liveUpdateAjax(data, flag);

});



/* Related */
function initRelated() {
	console.log('initRelated() is called');

	$('.input-related').each(function() {
		console.log('initRelated() each handler is called');
	
		var element = $(this);

		element.autocomplete({
			'source': function(request, response) {
				console.log('initRelated() source');
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
				console.log('initRelated() select');
				element.val('');

				$('#products-related--item-' + element.data('product-id') + '-' + item['value']).remove();

				$('#products-related-' + element.data('product-id')).append('<div id="products-related--item-' + element.data('product-id') + '-' + item['value'] + '"><i class="fa fa-minus-circle" data-product-id="' + element.data('product-id') + '" data-related-id="' + item['value'] + '"></i> ' + item['label'] + '<input type="hidden" name="product_related[]" value="' + item['value'] + '" /></div>');
				
				addProductRelated(element.data('product-id'), item['value']);
				
				// delegate не работал для новых элементов, которые появились в новом элементе (весь список подгружается по ajax)
				// $('body').on('click', '.products-related--item .fa-minus-circle', function() { тоже на давал результатов
				// даже с отстрочкой не работали
				$('body').on('click', '#products-related--item-' + element.data('product-id') + '-' + item['value'] +' .fa-minus-circle', function() {
					deleteProductRelated($(this));
				});			
			}
		});		
	});
}

$('body').delegate('.products-related--item .fa-minus-circle', 'click', function() {
	deleteProductRelated($(this));
});

function addProductRelated(productId, relatedId) {
	var data = 'product_id=' + productId;
	data += '&related_id=' + relatedId;
	data += '&essence=add_product_related';

	if (debug) {
		console.debug(data);
	}

	liveUpdateAjax(data, 'add_product_related');
}

function deleteProductRelated(el) {
	var data = 'product_id=' + el.data('product-id');
	data += '&related_id=' + el.data('related-id');
	data += '&essence=delete_product_related';

	if (debug) {
		console.debug(data);
	}

	liveUpdateAjax(data, 'delete_product_related');
	
	el.parent().remove();	
}


/* Filters */
function initFilters() {
	console.log('initFilters() is called');

	$('.input-filter').each(function() {
		console.log('initFilters() each handler is called');
	
		var element = $(this);

		element.autocomplete({
			'source': function(request, response) {
				console.log('initFilters() source');
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
				console.log('initFilters() select');
				element.val('');

				$('#product-filters--item-' + element.data('product-id') + '-' + item['value']).remove();

				$('#product-filters-' + element.data('product-id')).append('<div id="product-filters--item-' + element.data('product-id') + '-' + item['value'] + '"><i class="fa fa-minus-circle" data-product-id="' + element.data('product-id') + '" data-filter-id="' + item['value'] + '"></i> ' + item['label'] + '<input type="hidden" name="product_filter[]" value="' + item['value'] + '" /></div>');
				
				addProductFilter(element.data('product-id'), item['value']);
				
				// delegate не работал для новых элементов, которые появились в новом элементе (весь список подгружается по ajax)
				// $('body').on('click', '.product-filters--item .fa-minus-circle', function() { тоже на давал результатов
				// даже с отстрочкой не работали
				$('body').on('click', '#product-filters--item-' + element.data('product-id') + '-' + item['value'] +' .fa-minus-circle', function() {
					deleteProductFilter($(this));
				});			
			}
		});		
	});
}

$('body').delegate('.product-filters--item .fa-minus-circle', 'click', function() {
	deleteProductFilter($(this));
});

function addProductFilter(productId, filterId) {
	var data = 'product_id=' + productId;
	data += '&filter_id=' + filterId;
	data += '&essence=add_product_filter';

	if (debug) {
		console.debug(data);
	}

	liveUpdateAjax(data, 'add_product_filter');
}

function deleteProductFilter(el) {
	var data = 'product_id=' + el.data('product-id');
	data += '&filter_id=' + el.data('filter-id');
	data += '&essence=delete_product_filter';

	if (debug) {
		console.debug(data);
	}

	liveUpdateAjax(data, 'delete_product_filter');
	
	el.parent().remove();	
}


/* Description
----------------------------------------------------------------------------- */

$('body').on('change', '.description-content .le-value', function(e){
//$('.description-content .le-value').on('change', function(e){
  e.preventDefault();

  if (debug) {
    console.log('this value : ' + $(this).val());
  }

  var data = 'essence=edit_description'
    + '&product_id=' + $(this).parent().parent().data('product-id')
    + '&language_id=' + $(this).parent().parent().data('language-id')
    + '&field=' + $(this).data('field')
    + '&value=' + encodeURIComponent($(this).val());

  $.ajax({
    url: 'index.php?route=extension/module/handy/productListLiveEdit&user_token=<?php echo $user_token; ?>',
    type: 'POST',
    dataType: 'json',
    data: data,
    beforeSend: function() { loaderOn() },
    success: function(json) {
      console.log('request success');
      if ('success' === json['status']) {
        console.log('answer success');
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
});


// product block title change on change name
$('body').on('change', '.description .le-value._interactive-name', function(e) {
  e.preventDefault();

  if (debug) {
    console.log('this value : ' + $(this).val());
  }

	$('#product-name-label-' + $(this).closest('.description-content').data('product-id')).html('<b>' + $(this).val() + '</b>');
});

	
	
/* Products List New Row
----------------------------------------------------------------------------- */
var cloneProductId = 0;
var flag = 'add_new';

$('body').on('click', '#btn-add-products-row', function(e){
  e.preventDefault();

  if (debug) {
    console.log('#btn-add-products-row click() is called');
  }

  var addRowsNumber = $('#add-products-row-number').val();
  var productId;
	var flag_for_filter = '';


  if (0 == addRowsNumber || '' == addRowsNumber) {
    addRowsNumber = 1;
  }

	loaderOn();

	// ---------------------------------------------------------------------------
	// Loop Begin
	// --------------------
	for (var i = 1; i <= addRowsNumber; i++) {
		if (cloneProductId > 0) {
			// Конкретный товар обозначен для клонирования
			flag = 'clone_product';

			if ($('#add-products-row-clone-images').prop('checked')) {
				flag = 'clone_product_with_image';
			}
		} else {
			// Товар не обозначен для клонирования
//			if (1 == addRowsNumber) {
//				// Для удобства клонируем последний товара или хотя бы категории, производителя и атрибуты копируем с последнего товара
//				flag = 'create_new_product_with_copy_minimum';
//				cloneProductId = largest_product_id;
//			} else {
				// При массовом добавлении рядов, мы не решаем за пользователя, потому что ему придется сносить все атрибуты, если окажется,
				// что он хотел вставить именно пустые товары, чтобы каждому из них назначить что-то отдельное

				flag = 'create_new_product';
				flag_for_filter = 'reset_filter';
//			}
		}

		if (debug) {
			console.log('#btn-add-products-row click() cloneProductId : ' + cloneProductId)
		}

		var data = 'essence=add_new_product'
			+ '&flag=' + flag
			+ '&clone_product_id=' + cloneProductId;

		productId = liveUpdateAjax(data, 'add_new_product ' + flag, true);

		// update DOM - AJAX is async !
		filtering(flag_for_filter);
	}

	// Сбрасываю, чтобы при следующем добавлении товара не прошло мимо условия if (cloneProductId > 0) {...
	// Внимание!!
	// Сброс идет только после выполнения цикла копирования - иначе клонирование выполняется только в первой иттерации -
  // а потом добавляются пустые товары
	cloneProductId = 0;

	// +
	// Если мы нулим cloneProductId, то логично бы вернуть название кнопки
	$('.depends-on-clone').hide();
	$('#btn-add-products-row').html('<i class="fa fa-plus"></i> <?php echo $handy_text_add_new_products_row; ?>');

	// --------------------
	// Loop End
	// ---------------------------------------------------------------------------

});


$('.depends-on-clone').hide();

$('body').on('click', '.clone-checkbox', function(){
  if ($(this).prop('checked')) {
    cloneProductId = $(this).data('product-id');
    $('.clone-checkbox').prop('checked', false);
    $(this).prop('checked', true);
  } else {
    // Обнулить cloneProductId
    cloneProductId = 0;
  }

  if (debug) {
    console.log('.clone-checkbox click() cloneProductId : ' + cloneProductId)
  }

  if (cloneProductId > 0) {
		$([document.documentElement, document.body]).animate({
			scrollTop: $("#form-add-products-row").offset().top
    }, 300);


		setTimeout(function () {
			$('.depends-on-clone').show();
			$('#btn-add-products-row').html('<i class="fa fa-copy"></i> <?php echo $handy_text_add_new_products_row_clone; ?>');
		}, 500);

  } else {
    $('.depends-on-clone').hide();
    $('#btn-add-products-row').html('<i class="fa fa-plus"></i> <?php echo $handy_text_add_new_products_row; ?>');
  }

});




/* Delete Product
----------------------------------------------------------------------------- */
$('body').on('click', '.btn-delete-product', function(){
	if (confirm('<?php echo $handy_button_delete_confirm; ?>')) {
		var data = 'essence=delete_product'
      + '&product_id=' + $(this).data('product-id');

    liveUpdateAjax(data, 'delete_product', true);

		$('#products-list-row-' + $(this).data('product-id')).remove();

		//filtering();
	}
});




/* Scroll Arrows
----------------------------------------------------------------------------- */
function initArrows() {

	// http://qaru.site/questions/1649008/get-the-element-closer-to-the-middle-of-the-screen-in-jquery
	document.addEventListener("scroll", function(){
		if (debug) {
			console.log('scroll event is called');
		}

		let x = window.innerWidth / 2;
		let y = window.innerHeight / 2;
		let element = document.elementFromPoint(x, y);

		scrollCurrentProductId = $(element).closest('.products-list-row').data('product-id');

		if (undefined === scrollCurrentProductId) {
			scrollCurrentProductId = largest_product_id;
		}

//		if (debug) {
//			console.debug(element);
//
//			console.log('scroll event is called');
//
//			console.log('scrollCurrentProductId : ' + scrollCurrentProductId);
//		}

		// let page = element.getAttribute("data-page");
		// document.querySelector(".middle") && document.querySelector(".middle").classList.remove("middle");

		// $(element).closest('.products-list-row').add("middle");

	});
}


$('body').on('click', '.scroll-arrow.prev', function(){
//	if (debug) {
//		console.log('.scroll-arrow.prev click() is called');
//	}

	if (scrollCurrentProductId && $('#products-list-row-' + scrollCurrentProductId).prev('.products-list-row').length) {
		$('html, body').animate({ scrollTop: $('#products-list-row-' + scrollCurrentProductId).prev('.products-list-row').offset().top - 30 }, 500);
	}

});

$('body').on('click', '.scroll-arrow.next', function(){
//	if (debug) {
//		console.log('.scroll-arrow.next click() is called');
//	}

	if (scrollCurrentProductId && $('#products-list-row-' + scrollCurrentProductId).next('.products-list-row').length) {
		$('html, body').animate({ scrollTop: $('#products-list-row-' + scrollCurrentProductId).next('.products-list-row').offset().top - 30 }, 500);
	}
});

// Select2 for filter
$(document).ready(function() {
	$('#input-attribute').select2(); // Attribute
	$('#input-attribute-value').select2(); // Attribute
	$('#input-category-main').select2(); // Main category
	$('#input-category').select2(); // category
	$('#input-manufacturer').select2(); // category

});

// Init for dynamic content
function initSelect2() {
	$('.attribute-value-selector').select2();
	$("select[name='main_category_id']").select2();
	$("select[name='manufacturer_id']").select2();
}

</script>
