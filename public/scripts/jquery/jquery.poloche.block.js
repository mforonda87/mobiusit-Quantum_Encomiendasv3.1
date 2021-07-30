/**
 * block plugin que bloquea un contenido con una imagen de carga
 * http://quantum.net.bo/Encomiendas/public/scripts/libs/jquery.block.js
 * $("#idContentBlock").block();
 * @Autor Poloche  Paolo lizarazu butron
 */
(function($){
    
    $.fn.block = function(options){
        var opt = $.extend({}, $.fn.block.defaults, options);        
        return this.each(function(){
            var elem = $(this);
            var height = elem.css("height");
            var width = elem.width();
            var position=elem.offset();
            var pop = "<div id='popupBlock'><div>"+opt.text+"</div></div>";
            elem.append(pop);
            $("#popupBlock").css({
                "top":position.top,
                "left":position.left,
                "height":height,
                "width":width,
                "position":"absolute",
                "background-color":opt.bgC,
                "background-image":"url("+opt.img+")",
                "backgroundPosition":"center center",
                "background-repeat":"no-repeat",
                "text-align":"center",
                "vertical-align":"middle",
                "opacity":opt.opacity,
                "zindex":opt.zindex
            }).bind("ajaxComplete", function(){
                $.fn.block.close();
            });
            $("#popupBlock div").css({
                "background-color": "white",
                "background-image": "url(../images/ui-lightness/ui-bg_glass_100_f6f6f6_1x400.png)",
                "font-size": "15px",
                "font-weight": "bold",
                "text-align": "center",
                "vertical-align": "middle",
                "width": "300px",
                "heigth": "100px",
                "margin":"auto"
//                left: ($(window).width() - $('#popupBlock div').outerWidth())/2,
//                top: ($(window).height() - $('#popupBlock div').outerHeight())/2
            });
            
        });
    };
    $.fn.block.close=function(){
        $("#popupBlock").remove();
    };
    $.fn.block.defaults = {
        bgC:"#666666",
        opacity:"0.4",
        img:"../images/ajax-loader.gif",
        text:"Cargando contenido por favor espere...",
        zindex:1000
    };
})(jQuery);