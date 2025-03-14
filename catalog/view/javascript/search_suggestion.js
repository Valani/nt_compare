$(document).ready(function () {

  // use default autocomplete script if not defined ss_autocomplete
  if ($.fn.ss_autocomplete === undefined) {
    $.fn.ss_autocomplete = $.fn.autocomplete;
  }

  if (window.search_element === undefined) {
    search_element = '#search input[name="search"]';
  } else {
    search_element = window.search_element;
  }
  
  $(search_element).ss_autocomplete({
    delay: 500,
    ajax_loading: false,
    source: function (request, response) {
      if (request === '') {
        this.hide();
        return false;
      }

      const category_id = this.category_id || 0;

      let url = 'index.php?route=extension/module/search_suggestion/ajax&keyword=' + encodeURIComponent(request)
      if (category_id) {
        url += '&category_id=' + category_id
      } 

      const self = this

      $.ajax({
        url: url,
        dataType: 'json',
        beforeSend: function() {
          $('.tooltip').remove();
        },  
        success: function (json) {
          response($.map(json, function (item) {

            const elements = {
              left: [],
              center: [],
              right: []
            }

            let more = false;

            $.each(item['fields'], function (field_name, field) {
              if (field != undefined && field[field_name] !== undefined && field[field_name]) {

                if (field_name == 'more') {
                  more = true
                }

                var field_html = '';
                var class_name = '';

                if (field_name == 'image') {
                  let title = ''
                  if(item['title'] != undefined && item['title']) {
                    title = ' title="' + item['title'] + '" data-toggle="tooltip" data-placement="top" '
                    // title = ' title="' + item['name'] + '" '
                  }
                  field_html = '<img src="' + field[field_name] + '" ' + title + ' />';
                } else if (field_name == 'price') {
                  if (field.special) {
                    field_html = '<span class="price-old">' + field.price + '</span><span class="price-new">' + field.special + '</span>';
                  } else {
                    field_html = '<span class="price-base">' + field.price + '</span>';
                  }
                } else if (field_name == 'stock') {
                  if (field.class != undefined) {
                    class_name = field.class;
                  }
                  field_html = field[field_name];

                } else {
                  field_html = field[field_name];
                }

                if (field.label != undefined && field.label.show != undefined && field.label.show) {
                  field_html = '<span class="label">' + field.label.label + '</span>' + field_html;
                }
                if (field.location != undefined && field.location == 'inline') {
                  field_html = '<span class="' + field_name + ' ' + class_name  + '">' + field_html + '</span>';
                } else {
                  field_html = '<div class="' + field_name + ' ' + class_name  + '">' + field_html + '</div>';
                }

                const column = field.column || 'center'

                elements[column].push({sort: field.sort, html: field_html});
              }
            });
            
            $.each(Object.keys(elements), function (index, column) {
              elements[column].sort(function (a, b) {
                return a.sort - b.sort
              });  
            });
            
            // implode
            const elements_html = {
              left: '',
              center: '',
              right: ''
            }

            $.each(Object.keys(elements), function (index, column) {
              $.each(elements[column], function (index, element) {
                if (element != undefined) {
                  elements_html[column] = elements_html[column] + element.html;
                }
              });  
            });
            
            let columns_html = ''
            $.each(Object.keys(elements_html), function (index, column) {
              if (elements_html[column].length > 0) {
                columns_html = columns_html + '<div class="' + column + '">' + elements_html[column] + '</div>';
              } 
            });            
            
            if (item['type'] != undefined) {
              item_type = item['type'];
            } else {
              item_type = '';
            }

            const classes = []

            if (item['inline'] != undefined && item['inline']) {
              classes.push('inline')
            }              
            if (item['active'] != undefined && item['active']) {
              classes.push('active')
            }              
            if (more) {
              classes.push('more')
            }

            columns_html = '<div class="search-suggestion ' + item_type + '">' + columns_html + '</div>';            

            return {
              label: columns_html,
              value: item['href'],
              class: classes.join(' '),
              ajax: item['ajax'] || 0,
              category_id: item['category_id'] || 0
            };
          }));
        },
        complete: function() {
          self.focus()
          self.category_id = 0
          self.ajax_loading = false
        }
      });
    },
    select: function (item) {
      if (item['value'] !== '') {
        if (!item['ajax']) {
          location.href = item['value'];
        } else {

          this.category_id = item['category_id']
          this.ajax_loading = true

          this.request()
        }
      }
      return false;
    },

    // focus: function (event, ui) {
    //   return true;
    // }
  });
});