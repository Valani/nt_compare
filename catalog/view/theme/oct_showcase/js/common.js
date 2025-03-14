!function(t,e){"use strict";"function"==typeof define&&define.amd?define(["jquery"],function(t){e(t)}):"object"==typeof module&&module.exports?module.exports=t.EasyZoom=e(require("jquery")):t.EasyZoom=e(t.jQuery)}(this,function(i){"use strict";var c,d,l,p,o,s,h={loadingNotice:"Loading image",errorNotice:"The image could not be loaded",errorDuration:2500,linkAttribute:"href",preventClicks:!0,beforeShow:i.noop,beforeHide:i.noop,onShow:i.noop,onHide:i.noop,onMove:i.noop};function n(t,e){this.$target=i(t),this.opts=i.extend({},h,e,this.$target.data()),void 0===this.isOpen&&this._init()}return n.prototype._init=function(){this.$link=this.$target.find("a"),this.$image=this.$target.find("img"),this.$flyout=i('<div class="easyzoom-flyout" />'),this.$notice=i('<div class="easyzoom-notice" />'),this.$target.on({"mousemove.easyzoom touchmove.easyzoom":i.proxy(this._onMove,this),"mouseleave.easyzoom touchend.easyzoom":i.proxy(this._onLeave,this),"mouseenter.easyzoom touchstart.easyzoom":i.proxy(this._onEnter,this)}),this.opts.preventClicks&&this.$target.on("click.easyzoom",function(t){t.preventDefault()})},n.prototype.show=function(t,e){var o=this;if(!1!==this.opts.beforeShow.call(this)){if(!this.isReady)return this._loadImage(this.$link.attr(this.opts.linkAttribute),function(){!o.isMouseOver&&e||o.show(t)});this.$target.append(this.$flyout);var i=this.$target.outerWidth(),s=this.$target.outerHeight(),h=this.$flyout.width(),n=this.$flyout.height(),a=this.$zoom.width(),r=this.$zoom.height();c=Math.ceil(a-h),d=Math.ceil(r-n),l=(c=c<0?0:c)/i,p=(d=d<0?0:d)/s,this.isOpen=!0,this.opts.onShow.call(this),t&&this._move(t)}},n.prototype._onEnter=function(t){var e=t.originalEvent.touches;this.isMouseOver=!0,e&&1!=e.length||(t.preventDefault(),this.show(t,!0))},n.prototype._onMove=function(t){this.isOpen&&(t.preventDefault(),this._move(t))},n.prototype._onLeave=function(){this.isMouseOver=!1,this.isOpen&&this.hide()},n.prototype._onLoad=function(t){t.currentTarget.width&&(this.isReady=!0,this.$notice.detach(),this.$flyout.html(this.$zoom),this.$target.removeClass("is-loading").addClass("is-ready"),t.data.call&&t.data())},n.prototype._onError=function(){var t=this;this.$notice.text(this.opts.errorNotice),this.$target.removeClass("is-loading").addClass("is-error"),this.detachNotice=setTimeout(function(){t.$notice.detach(),t.detachNotice=null},this.opts.errorDuration)},n.prototype._loadImage=function(t,e){var o=new Image;this.$target.addClass("is-loading").append(this.$notice.text(this.opts.loadingNotice)),this.$zoom=i(o).on("error",i.proxy(this._onError,this)).on("load",e,i.proxy(this._onLoad,this)),o.style.position="absolute",o.src=t},n.prototype._move=function(t){s=0===t.type.indexOf("touch")?(e=t.touches||t.originalEvent.touches,o=e[0].pageX,e[0].pageY):(o=t.pageX||o,t.pageY||s);var e=this.$target.offset(),t=o-e.left,e=s-e.top,t=Math.ceil(t*l),e=Math.ceil(e*p);t<0||e<0||c<t||d<e?this.hide():(e=-1*e,t=-1*t,"transform"in document.body.style?this.$zoom.css({transform:"translate("+t+"px, "+e+"px)"}):this.$zoom.css({top:e,left:t}),this.opts.onMove.call(this,e,t))},n.prototype.hide=function(){this.isOpen&&!1!==this.opts.beforeHide.call(this)&&(this.$flyout.detach(),this.isOpen=!1,this.opts.onHide.call(this))},n.prototype.swap=function(t,e,o){this.hide(),this.isReady=!1,this.detachNotice&&clearTimeout(this.detachNotice),this.$notice.parent().length&&this.$notice.detach(),this.$target.removeClass("is-loading is-ready is-error"),this.$image.attr({src:t,srcset:i.isArray(o)?o.join():o}),this.$link.attr(this.opts.linkAttribute,e)},n.prototype.teardown=function(){this.hide(),this.$target.off(".easyzoom").removeClass("is-loading is-ready is-error"),this.detachNotice&&clearTimeout(this.detachNotice),delete this.$link,delete this.$zoom,delete this.$image,delete this.$notice,delete this.$flyout,delete this.isOpen,delete this.isReady},i.fn.easyZoom=function(e){return this.each(function(){var t=i.data(this,"easyZoom");t?void 0===t.isOpen&&t._init():i.data(this,"easyZoom",new n(this,e))})},n});

// common
function getURLVar(key) {
    var value = [];

    var query = String(document.location).split('?');

    if (query[1]) {
        var part = query[1].split('&');

        for (i = 0; i < part.length; i++) {
            var data = part[i].split('=');

            if (data[0] && data[1]) {
                value[data[0]] = data[1];
            }
        }

        if (value[key]) {
            return value[key];
        } else {
            return '';
        }
    }
}

$(document).ready(function () {
    if(window.innerWidth > 768){
        var $easyzoom = $('.easyzoom').easyZoom();
        var $easyzoom2 = $('.easyzoom2').easyZoom();
    }
    // Highlight any found errors
    $('.text-danger').each(function () {
        var element = $(this).parent().parent();

        if (element.hasClass('form-group')) {
            element.addClass('has-error');
        }
    });
    
    $(document).on('click', '.sc-product-images-main .easyzoom-flyout', function(e){
        $(this).closest('.sc-product-images-slide').find('a').trigger('click');
    });
    $(document).on('change', '[name="shipping_method"]', function(e){
        if($(this).val() == 'pickup.pickup' || $(this).val() == 'free.free'){
            $('.shipp-block').addClass('hidden');
        }else $('.shipp-block').removeClass('hidden');
    });
    $('.sc-product-images-slide.easyzoom.easyzoom--overlay').on({
        mousemove: function(e){
            let parentOffset = $(this).offset();
            let parentW = $(this).width();
            let parentH = $(this).height();
            //or $(this).offset(); if you really just want the current element's offset
            let relX = e.pageX - parentOffset.left;
            let relY = e.pageY - parentOffset.top;
            let wPer = 100/parentW*100;
            let hPer = 100/parentH*100;
            relX = relX/parentW*100 - wPer;
            relY = relY/parentH*100 - hPer;
            $(this).find('.easyzoom-flyout').css('left', relX+'%').css('top', relY+'%');
        },
        mouseleave: function(){
            //stuff
        }
    });
    $('.easyzoom2.easyzoom--overlay2').on({
        mousemove: function(e){
            let parentOffset = $(this).offset();
            let parentW = $(this).width();
            let parentH = $(this).height();
            //or $(this).offset(); if you really just want the current element's offset
            let relX = e.pageX - parentOffset.left;
            let relY = e.pageY - parentOffset.top;
            let wPer = 100/parentW*100;
            let hPer = 100/parentH*100;
            relX = relX/parentW*100 - wPer;
            relY = relY/parentH*100 - hPer;
            $(this).find('.easyzoom-flyout').css('left', relX+'%').css('top', relY+'%');
        },
        mouseleave: function(){
            //stuff
        }
    });
    $(document).on('click', '#c-login-button', function(e){
        e.preventDefault();
        $.ajax({
            url: 'index.php?route=octemplates/module/oct_popup_login/login',
            type: 'post',
            data: 'email=' + $('.cFormAuthCheckout #cinputEmailLogin').val() + '&password=' + $('.cFormAuthCheckout #cinputPasswordLogin').val() + '&redirect=https://nawiteh.ua/checkout',
            dataType: 'json',
            cache: false,
            success: function (json) {
                if (json['redirect']) {
                    location = json['redirect'];
                }

                if (json['warning']) {
                    scNotify('danger', '<div class="alert-text-item">' + json['warning'] + '</div>');
                }

                if (json['error']) {
                    scNotify('danger', '<div class="alert-text-item">' + json['error'] + '</div>');
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });
    $(document).on('click', '.tab_a', function(e){
        e.preventDefault();
        if(!$(this).hasClass('active')){
            $('.tab_a').removeClass('active');
            $('.tab_cont').addClass('hidden');
            $(this).addClass('active');
            $('.tab_cont[data-tab="'+$(this).attr('data-tab')+'"]').removeClass('hidden');
        }
    });
    
    // Currency
    $('body').on('click', '#form-currency .currency-select', function (e) {
        e.preventDefault();

        $('#form-currency input[name=\'code\']').val($(this).attr('name'));

        $('#form-currency').submit();
    });

    // Language
    $('body').on('click', '#form-language .language-select', function (e) {
        e.preventDefault();

        $('#form-language input[name=\'code\']').val($(this).attr('name'));

        $('#form-language').submit();
    });

    /* Search */
    $('#search input[name=\'search\']').parent().find('button').on('click', function () {
        var url = $('base').attr('href') + 'index.php?route=product/search';

        var value = $('#search input[name=\'search\']').val();

        if (value.length > 0) {
            url += '&search=' + encodeURIComponent(value);
            location = url;
        }

    });

    $('#search input[name=\'search\']').on('keydown', function (e) {
        if (e.keyCode == 13) {
            $('#search input[name=\'search\']').parent().find('button').trigger('click');
        }
    });

    const searchForm = document.getElementById('search');
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
    });

    /* Blog Search */
    $('#oct-blog-search-button').on('click', function () {
        var url = $('base').attr('href') + 'index.php?route=octemplates/blog/oct_blogsearch';

        var value = $('#blog_search input[name=\'blog_search\']').val();

        if (value.length > 0) {
            url += '&search=' + encodeURIComponent(value);
            location = url;
        }

    });

    $('#blog_search input[name=\'blog_search\']').on('keydown', function (e) {
        if (e.keyCode == 13) {
            $('#oct-blog-search-button').trigger('click');
        }
    });

    // Menu
    $('#menu .dropdown-menu').each(function () {
        var menu = $('#menu').offset();
        var dropdown = $(this).parent().offset();

        var i = (dropdown.left + $(this).outerWidth()) - (menu.left + $('#menu').outerWidth());

        if (i > 0) {
            $(this).css('margin-left', '-' + (i + 10) + 'px');
        }
    });

    // hide tooltip after click
    $("#grid-view, #list-view").mouseleave(function () {
        $('[data-toggle="tooltip"]').tooltip("hide");
    });

    // Checkout
    $(document).on('keydown', '#collapse-checkout-option input[name=\'email\'], #collapse-checkout-option input[name=\'password\']', function (e) {
        if (e.keyCode == 13) {
            $('#collapse-checkout-option #button-login').trigger('click');
        }
    });

    // tooltips on hover
    $('[data-toggle=\'tooltip\']').tooltip({
        container: 'body',
        boundary: 'window'
    });

    // Makes tooltips work on ajax generated content
    $(document).ajaxStop(function () {
        $('[data-toggle=\'tooltip\']').tooltip({
            container: 'body',
            boundary: 'window'
        });
    });
});

// Cart add remove functions
var cart = {
    'add': function (product_id, quantity, page = 0) {
        $.ajax({
            url: 'index.php?route=checkout/cart/add',
            type: 'post',
            data: 'product_id=' + product_id + '&quantity=' + (typeof (quantity) != 'undefined' ? quantity : 1),
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $('#cart > button').button('loading');
            },
            complete: function () {
                $('#cart > button').button('reset');
            },
            success: function (json) {
                $('.alert-dismissible, .text-danger').remove();

                if (page == 1 && json['error']) {
                    scrollToElement('.sc-product-actions-middle', false, -80);
                    return;
                }

                if (json['redirect']) {
                    location = json['redirect'];
                }

                if (json['error'] && json['error']['error_warning']) {
                    scNotify('danger', '<div class="alert-text-item">' + json['error']['error_warning'] + '</div>');
                }

                if (json['success']) {
                    if (json['isPopup']) {
                        octPopupCart();
                    } else {
                        scNotify('success', json['success']);
                    }

                    let cartIdsHolder = document.querySelector("[data-cart-ids]");

                    if (json.oct_cart_ids && json.oct_cart_ids.length > 0 && cartIdsHolder) {
                        cartIdsHolder.dataset.cartIds = json.oct_cart_ids;
                        setCartBtnAdded();
                    }

                    // Need to set timeout otherwise it wont update the total
                    setTimeout(function () {
                        $('#cart .header-buttons-cart-quantity').html(json['total_products']);
                        $('.rm-header-cart-text').html(json['total_amount']);
                    }, 100);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'update': function (key, quantity) {
        $.ajax({
            url: 'index.php?route=checkout/cart/edit',
            type: 'post',
            data: 'key=' + key + '&quantity=' + (typeof (quantity) != 'undefined' ? quantity : 1),
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $('#cart > button').button('loading');
            },
            complete: function () {
                $('#cart > button').button('reset');
            },
            success: function (json) {
                // Need to set timeout otherwise it wont update the total
                setTimeout(function () {
                    $('#cart .header-buttons-cart-quantity').html(json['total_products']);
                    $('.rm-header-cart-text').html(json['total_amount']);
                }, 100);

                var now_location = String(document.location.pathname);

                if ((now_location == '/cart/') || (now_location == '/cart') || (now_location == '/checkout/') || (now_location == '/checkout') || (getURLVar('route') == 'checkout/cart') || (getURLVar('route') == 'checkout/checkout')) {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'remove': function (key) {
        $.ajax({
            url: 'index.php?route=checkout/cart/remove',
            type: 'post',
            data: 'key=' + key,
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $('#cart > button').button('loading');
            },
            complete: function () {
                $('#cart > button').button('reset');
            },
            success: function (json) {
                let cartIdsHolder = document.querySelector("[data-cart-ids]");

                if (json.oct_cart_ids && json.oct_cart_ids.length > 0 && cartIdsHolder) {
                    cartIdsHolder.dataset.cartIds = json.oct_cart_ids;
                }

                // Need to set timeout otherwise it wont update the total
                setTimeout(function () {
                    $('#cart .header-buttons-cart-quantity').html(json['total_products']);
                    $('.rm-header-cart-text').html(json['total_amount']);
                }, 100);

                var now_location = String(document.location.pathname);

                if ((now_location == '/cart/') || (now_location == '/cart') || (now_location == '/checkout/') || (now_location == '/checkout') || (getURLVar('route') == 'checkout/cart') || (getURLVar('route') == 'checkout/checkout')) {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
}

var voucher = {
    'add': function () {

    },
    'remove': function (key) {
        $.ajax({
            url: 'index.php?route=checkout/cart/remove',
            type: 'post',
            data: 'key=' + key,
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $('#cart > button').button('loading');
            },
            complete: function () {
                $('#cart > button').button('reset');
            },
            success: function (json) {
                // Need to set timeout otherwise it wont update the total
                setTimeout(function () {
                    $('#cart > button').html('<span id="cart-total"><i class="fa fa-shopping-cart"></i> ' + json['total'] + '</span>');
                }, 100);

                var now_location = String(document.location.pathname);

                if ((now_location == '/cart/') || (now_location == '/cart') || (now_location == '/checkout/') || (now_location == '/checkout') || (getURLVar('route') == 'checkout/cart') || (getURLVar('route') == 'checkout/checkout')) {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
}

var wishlist = {
    'add': function (product_id) {
        $.ajax({
            url: 'index.php?route=account/wishlist/add',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            cache: false,
            success: function (json) {
                $('.alert-dismissible').remove();

                if (json['redirect']) {
                    location = json['redirect'];
                }

                if (json['success']) {
                    scNotify('success', json['success']);
                    $('.header-buttons-wishlist .header-buttons-cart-quantity').html(json['total_wishlist']);
                }

            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'remove': function (product_id) {
        $.ajax({
            url: 'index.php?route=octemplates/events/helper/wishlistRemove',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            cache: false,
            success: function (json) {

                if (json['success']) {
                    scNotify('success', json['success']);
                    $('.header-buttons-wishlist .header-buttons-cart-quantity').html(json['total_wishlist']);
                }
            }
        });
    }
}

var compare = {
    'add': function (product_id) {
        $.ajax({
            url: 'index.php?route=product/compare/add',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            cache: false,
            success: function (json) {
                $('.alert-dismissible').remove();

                if (json['success']) {
                    scNotify('success', json['success']);
                    $('.header-buttons-compare .header-buttons-cart-quantity').html(json['total_compare']);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'remove': function (product_id) {
        $.ajax({
            url: 'index.php?route=octemplates/events/helper/compareRemove',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            cache: false,
            success: function (json) {

                if (json['success']) {
                    scNotify('success', json['success']);
                    $('.header-buttons-compare .header-buttons-cart-quantity').html(json['total_compare']);
                }
            }
        });
    }
}

/* Agree to Terms */
$(document).delegate('.agree', 'click', function (e) {
    e.preventDefault();
    masked('body', true);
    $('#modal-agree').remove();

    var element = this,
        link = '';
    var r = $(element).data('rel');

    if (r && r != 'undefined') {
        link = 'index.php?route=information/information/agree&information_id=' + r;
    } else {
        link = $(element).attr('href');
    }

    $.ajax({
        url: link,
        type: 'get',
        dataType: 'html',
        cache: false,
        success: function (data) {
            html = '<div class="modal fade" id="modal-agree" tabindex="-1" role="dialog" aria-labelledby="modal-agree" aria-hidden="true">';
            html += '  <div class="modal-dialog modal-dialog-centered wide">';
            html += '    <div class="modal-content">';
            html += '      <div class="modal-header p-4">';
            html += '        <h5 class="modal-title fsz-20 d-flex align-items-center justify-content-between">' + $(element).text() + '</h5>';
            html += '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            html += '      </div>';
            html += '      <div class="modal-body modal-body-agree p-4">' + data + '</div>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';

            $('body').append(html);
            masked('body', false);
            $('#modal-agree').modal('show');
        }
    });
});

// Autocomplete */
(function ($) {
    $.fn.autocomplete = function (option) {
        return this.each(function () {
            this.timer = null;
            this.items = new Array();

            $.extend(this, option);

            $(this).attr('autocomplete', 'off');

            // Focus
            $(this).on('focus', function () {
                this.request();
            });

            // Blur
            $(this).on('blur', function () {
                setTimeout(function (object) {
                    object.hide();
                }, 200, this);
            });

            // Keydown
            $(this).on('keydown', function (event) {
                switch (event.keyCode) {
                    case 27: // escape
                        this.hide();
                        break;
                    default:
                        this.request();
                        break;
                }
            });

            // Click
            this.click = function (event) {
                event.preventDefault();

                value = $(event.target).parent().attr('data-value');

                if (value && this.items[value]) {
                    this.select(this.items[value]);
                }
            }

            // Show
            this.show = function () {
                var pos = $(this).position();

                $(this).siblings('ul.dropdown-menu').css({
                    top: pos.top + $(this).outerHeight(),
                    left: pos.left
                });

                $(this).siblings('ul.dropdown-menu').show();
            }

            // Hide
            this.hide = function () {
                $(this).siblings('ul.dropdown-menu').hide();
            }

            // Request
            this.request = function () {
                clearTimeout(this.timer);

                this.timer = setTimeout(function (object) {
                    object.source($(object).val(), $.proxy(object.response, object));
                }, 200, this);
            }

            // Response
            this.response = function (json) {
                html = '';

                if (json.length) {
                    for (i = 0; i < json.length; i++) {
                        this.items[json[i]['value']] = json[i];
                    }

                    for (i = 0; i < json.length; i++) {
                        if (!json[i]['category']) {
                            html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
                        }
                    }

                    // Get all the ones with a categories
                    var category = new Array();

                    for (i = 0; i < json.length; i++) {
                        if (json[i]['category']) {
                            if (!category[json[i]['category']]) {
                                category[json[i]['category']] = new Array();
                                category[json[i]['category']]['name'] = json[i]['category'];
                                category[json[i]['category']]['item'] = new Array();
                            }

                            category[json[i]['category']]['item'].push(json[i]);
                        }
                    }

                    for (i in category) {
                        html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

                        for (j = 0; j < category[i]['item'].length; j++) {
                            html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
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