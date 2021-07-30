/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function()
{
  alert("tomalo");
    resizeContainer();
    function resizeContainer()
    {
        $(".content_rounded").each(function(){
            var obj = $(this);
            obj.parent().css("width",obj.css("width"));
        });
    }
});
