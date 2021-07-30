/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

(function($) {
    var CURSOR_POS_X;
    var CURSOR_POS_Y;
    var VACIO     = 0,
    VACANTE   = 1,
    VENTA     = 2,
    LIBRE     = 3,
    RESERVA   = 4,
    SUCURSAL  = 5,
    TELEVISOR = 6,
    ENTRADA   = 7,
    DIRECCION = 8,
    DISPONIBLES=0,

    SELECTEDS  = new Array(),

    LEFT  = 1,
    RIGHT = 2,
    UP    = 3,
    DOWN  = 4,

    MAX_ROW = 0,
    MAX_COL = 0,

    matriz = new Array();
    $.fn.extend({
        modelControl : function(init){
            var self = this;
//            $.log("Esto llega ........ "+init);
//            $.log(matriz);
            return this.each(function(obj){
                if(init){
                    CURSOR_POS_X=-1;
                    CURSOR_POS_Y=-1;
                    matriz = new Array();
                    self.create();
                }
            });
        },
        create:function(){
            var trs =$(this).find("tr");
            for(var i=0;i<trs.length;i++){
                var tr = trs[i];
                var tds= $(tr).find("td");
                matriz[i]=new Array(tds.length);
                for(var j =0;j<tds.length;j++){
                    var td =$(tds[j]);
                    var clazz = td.attr("class");
                    var estado=VACANTE;
                    if(clazz==""){
                        estado=VACIO;
                    }else if(td.hasClass("venta")){
                        estado=VENTA;
                    }else if(td.hasClass("reserva")){
                        estado=RESERVA;
                    }else if(td.hasClass("libre")){
                        estado=LIBRE;
                    }else if(td.hasClass("televisor")){
                        estado=TELEVISOR;
                    }else if(td.hasClass("entrada")){
                        estado=ENTRADA;
                    }else if(td.hasClass("direccion")){
                        estado=DIRECCION;
                    }
                    matriz[i][j]={};
                    matriz[i][j].element=td;
                    matriz[i][j].id=$(td).attr("id");
                    matriz[i][j].estado=estado;
                    matriz[i][j].selected=false;
                    matriz[i][j].x=i;
                    matriz[i][j].y=j;
                    var self = this;
                    if(this.isValid(estado)){
                        $(td).bind("click",{
                            "x":i,
                            "y":j
                        }, function(e){
                            if($(this).hasClass("cursor")||$(this).hasClass("itemSelected")){
                                $(this).removeClass("cursor");
                                $(this).removeClass("itemSelected");
                                matriz[e.data.x][e.data.y].selected=false;
                            }else{
                                $(this).addClass("cursor");
                                $(this).addClass("itemSelected");
                                matriz[e.data.x][e.data.y].selected=true;
                            }
                            self.setPosition(e.data.x,e.data.y);
                        });
                    }
                }
            }
            MAX_ROW = matriz.length;
            MAX_COL = matriz[0].length;
        },
        processItem:function(next,SHIFT_PRESSED){
            if(next != null){
                if(CURSOR_POS_X!= -1 && CURSOR_POS_Y!=-1){
                    var old = matriz[CURSOR_POS_X][CURSOR_POS_Y];
                    if(SHIFT_PRESSED){
                        old.element.removeClass("cursor");
                        old.element.addClass("itemSelected");
                        matriz[CURSOR_POS_X][CURSOR_POS_Y].selected = true;
                    //                        SELECTEDS.add(old.id);
                    }else{
                        old.element.removeClass("cursor");
                        old.element.removeClass("itemSelected");
                        old.selected = false;
                    //                        SELECTEDS.remove(old.id);
                    }
                }
                //                SELECTEDS.add(next.id);
                next.element.addClass("cursor");
                CURSOR_POS_X = next.x;
                CURSOR_POS_Y = next.y;
                next.selected = true;
            }
        },
        getSelecteds:function(){
            var sel = new Array();
            for (var i = 0; i < MAX_ROW; i++) {
                for (var j = 0; j < MAX_COL; j++) {
                    var old = matriz[i][j];
                    if(old.selected){
                        sel.push(old.id);
                    }

                }
            }
            return sel;
        },
        getDisponibles:function(){
            var sel = 0;
            for (var i = 0; i < MAX_ROW; i++) {
                for (var j = 0; j < MAX_COL; j++) {
                    var old = matriz[i][j];
                    if(old.estado==VENTA || old.estado==LIBRE){
                        sel++;
                    }

                }
            }
            return sel;
        },
        getNext:function(direccion){
            var x = CURSOR_POS_X;
            var y = CURSOR_POS_Y;
            var item = null;
            var search = true;
            if(x==-1){
                x=0;
            }
            if(this.getDisponibles()>0){
                switch (direccion) {
                    case RIGHT:
                        while(search){
                            if(y==-1){
                                y=0;
                            } else {
                                y++;
                            }
                            if(y==MAX_COL){
                                y=0;
                                x++;
                            }
                            if(x==MAX_ROW){
                                x=0;
                                y=0;
                            }
                            item = matriz[x][y];
                            if(this.isValid(item.estado) ){
                                search = false;
                            } else {
                                item =  null;
                                if(this.isIt(x,y)) {
                                    search = false;
                                }
                            }

                        }
                        break;
                    case LEFT:
                        while(search) {
                            if(y==-1){
                                y=0;
                            }
                            else{
                                y--;
                            }
                            if(y==-1){
                                y=MAX_COL-1;
                                x--;
                            }
                            if(x==-1){
                                x=MAX_ROW-1;
                                y=MAX_COL-1;
                            }
                            item = matriz[x][y];
                            if(this.isValid(item.estado) ){
                                search = false;
                            } else {
                                item =  null;
                                if(this.isIt(x,y)) {
                                    search = false;
                                }
                            }
                        }
                        break;
                    case UP:
                        while(search) {
                            if(x==-1){
                                x=0;
                            } else {
                                x--;
                            }
                            if(x==-1){
                                y--;
                                x=MAX_ROW-1;
                            }
                            if(y<=-1){
                                y=MAX_COL-1;
                                x=MAX_ROW-1;
                            }
                            item = matriz[x][y];
                            if(this.isValid(item.estado) ){
                                search = false;
                            }
                            else{
                                item =  null;
                                if(this.isIt(x,y)) {
                                    search = false;
                                }
                            }
                        }
                        break;
                    case DOWN:
                        while(search) {
                            if(x==-1){
                                x=0;
                            } else {
                                x++;
                            }
                            if(x==MAX_ROW){
                                y++;
                                x=0;
                            }
                            if(y==MAX_COL){
                                y=0;
                                x=0;
                            }
                            item = matriz[x][y];
                            if(this.isValid(item.estado) ){
                                search = false;
                            }
                            else{
                                item =  null;
                                if(this.isIt(x,y)){
                                    search = false;
                                }
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
            return item;
        },
        isIt:function(x,y){
            return (x==CURSOR_POS_X && y==CURSOR_POS_Y);
        },
        isValid: function(estado){
            return (estado==VENTA  || estado==LIBRE);
        },
        getCurrent:function(){
            return matriz[CURSOR_POS_X][CURSOR_POS_Y];
        },
        setPosition : function(x,y){
            CURSOR_POS_X=x;
            CURSOR_POS_Y=y;
        },
        getPosition : function(){
            return {
                "x":CURSOR_POS_X,
                "y":CURSOR_POS_Y
            };
        },
        setVendido:function(id){
            for (var i = 0; i < MAX_ROW; i++) {
                for (var j = 0; j < MAX_COL; j++) {
                    var old = matriz[i][j];
                    if(old.id==id)
                    {
                        old.estado=VENTA;
                        old.selected = false;
                    }

                }
            }
        },
        selectAll:function(){
            for (var i = 0; i < MAX_ROW; i++) {
                for (var j = 0; j < MAX_COL; j++) {
                    var old = matriz[i][j];
                    if(this.isValid(old.estado))
                    {
                        old.element.addClass("itemSelected");
                        old.selected = true;
                    }

                }
            }
        },
        deselectAll:function (){
            for (var i = 0; i < MAX_ROW; i++) {
                for (var j = 0; j < MAX_COL; j++) {
                    var old = matriz[i][j];
                    if(old.selected)
                    {
                        old.element.removeClass("itemSelected");
                        old.element.removeClass("cursor");
                        old.selected = false;
                    }

                }
            }
        }
    });
})(jQuery);