'use strict';

(function($) {
  $.fn.customSelect = function(options){
    var settings = $.extend({
      dataOriginal : [],
      path : '',
      wrap: null,
      input: null,
      list : 'children',
      selectorContainer: null,
      id : 'id',
      name : 'name',
    }, options)
    return this.each(function(){
        var element = $(this);
        var value = null;
        if(element.val()){
            var path = _findPathById(settings.dataOriginal,element.val());
            
            if(path != undefined){
                var a = path.replace(/^\./, '');           // strip a leading dot
                var s = a.split('.')
                if(s.length > 1){
                    value = _get(path).name
                    s.pop()
                    settings.path = s.join('.')
                }
                else if(s.length == 1){
                    value = settings.dataOriginal[path].name
                }
            }
        }
        wrap(value);

        $(document).on("click", function(e){
            if(!$(e.target).is("*[class^='custom__menu']")){
                $(settings.selectorContainer).addClass('d-none')
                settings.input.removeClass('show')
            }
        });

        function wrap(value){
            element.wrap(
                $('<div>').addClass('custom__menu')
            ).attr('type','hidden');
            settings.wrap = element.parent()
            settings.wrap.append(
                $('<div>').addClass('custom__menu__input').html((value ? value : 'select' )).click(function(){
                    $(settings.selectorContainer).toggleClass('d-none')
                    settings.input.addClass('custom__menu__more')
                    draw(settings.path)
                }),
                $('<div>').addClass('custom__menu__container d-none')
            )
            settings.input = $(settings.wrap).children('.custom__menu__input');
            settings.selectorContainer = $(settings.wrap).children('.custom__menu__container');
            
            
        }
        function draw(path){
            settings.selectorContainer.html('')
            var data = _get(path);
            var ul = $('<ul>').addClass('list-group')

            if(path != ''){
                var s = settings.path;
                s = s.replace(/^\./, '');           // strip a leading dot
                var a = s.split('.')
                a.pop()
                ul.append(
                    $('<li>').append(
                        $('<span>').html('<').addClass('custom__menu__back'),
                        $('<a>').html(_get(a.join('.')).name)
                    ).addClass('list-group-item').click(function(){
                                a.pop()
                                settings.path = a.join('.')
                                draw(settings.path)
                    })
                )
            }

            data.forEach(function(value, i){
                var li = $('<li>').addClass('list-group-item');
                li.append(
                    $('<a>').html(value.name).addClass('custom__menu__selectable').click(function(){
                        settings.input.html(value.name)
                        $(element).val(value.id)
                        $(settings.selectorContainer).toggleClass('d-none')
                        settings.input.removeClass('show')
                    })
                )
                if(value.hasOwnProperty(settings.list)){
                    li.append(
                        $('<a>').html('>').click(function(){
                            settings.path += '.'+i+'.'+settings.list 
                            draw(settings.path)
                        }).addClass('custom__menu__more')
                    )
                }
                ul.append(li);
            });
            settings.selectorContainer.append(ul)
        }
      
        function _get(s) {
            var o = settings.dataOriginal
            
            if(s === ''){
                return o
            }else{
                s = s.replace(/\[(\w+)\]/g, '.$1'); 
                s = s.replace(/^\./, '');
                var a = s.split('.');
                console.log(a)
                for (var i = 0, n = a.length; i < n; ++i) {
                    var k = a[i];
                    if (k in o) {
                        o = o[k];
                    } else {
                        return;
                    }
                }
                return o;
            }
            
        }

        function _findPathById(data, id){
            var path = '';
            for(var i= 0 ;i<data.length;i++){
                if(data[i].id === parseInt(id)){
                    path = i.toString();
                    return path
                }else if(data[i].hasOwnProperty(settings.list)){
                    var newPath = _findPathById(data[i][settings.list], id);
                    if(newPath != undefined){
                        path = i+'.'+settings.list+'.'+newPath
                        return path;
                    }
                }
            }
            return undefined;
            // parse
        }
    })
  }
})(jQuery);