/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



$(document).ready(function(){
    $(".bus-container").hide();
    
    getItinerary();
    
    
   
});

function showDialog(destino){
    $.jsonp({
        url: URLSERVICE+"/destino/"+destino,
        timeout: 9000,
        callback: "srt",
        callbackParameter: "callback",
        success: function(data) {
            var html="";
            var info = GetInfoBus(data);
            var asientos = fillMatrix(info, data);
            fillItems(asientos, info.rows, info.cols, data);
        },
        error: function(d,msg) {
            updateDataBus(errorBus());
        }
    });
    return false;
}
function fillItems(asientos, filas, columnas, data){
    //    $('.info-container').html('');
    //    $('.items-container').html('');
    for (var i=0; i<= filas; i++){
        for (var j=0; j<= columnas; j++){
            var number = getNumber(data, j, i);
            makeItem(number, parseInt(asientos[i][j]));
        }
    }
}

function fillDataTravel(origen, destino, hora, bus){

    var date = $('#actualDate').html();
    var only_date = "";

    if(date.length > 0 && date.indexOf(",") != -1){
        var splt = date.split(',');
        if(splt.length > 1){
            only_date = splt[1];
        } else {
            only_date = date;
        }
    } else {
        only_date = date;
    }

    $('#bus-start').html(names[origen]);
    $('#bus-end').html(names[destino]);
    $('#bus-time').html(hora);
    $('#bus-date').html(only_date);
    $('#bus-type').html(bus);
}
function fillMatrix(infoBus, data){
    var rows = infoBus.rows;
    var cols = infoBus.cols;
    var matrix = new Array();
    for(var i=0; i<=rows; i++){
        var item = new Array();
        for(var j=0; j<=cols; j++){
            item[j] = 0;
        }
        matrix[i] = item;
    }
    for(var asiento in data.response.modelo){
        var place = data.response.modelo[asiento];
        matrix[place.y][place.x] = place.t;
    }
    return matrix;
}
function GetInfoBus(data){
    var rows = 0;
    var cols = 0;
    for(var asiento in data.response.modelo){
        var place = data.response.modelo[asiento];
        var x = parseInt(place.x);
        var y = parseInt(place.y);
        if(x > cols)
            cols = x;
        if(y > rows)
            rows = y;
    }
    return new InfoBus(rows, cols);
}
function InfoBus(filas, columnas) {
    this.rows = filas;
    this.cols = columnas;
}
function makeItem(number, type){
    var textValue = "";
    var kind = "";
    if(!isNaN(number))
        textValue = ""+number
    switch(type){
        case 1:
            kind = "vacante";
            break;
        case 2:
            kind = "televisor";
            break;
        case 3:
            kind = "direccion";
            break;
        case 4:
            kind = "entrada";
            break;
        default:
            kind = "";
    }
    $('.items-container').append("<div class='item-bus "+kind+"'>"+textValue+"</div>");
}
function makeSeparator(){
    $('.items-container').append("<div class='item-bus separator'>&nbsp;</div>");
}
function getNumber(data, x, y){
    var numero = "";
    for(var asiento in data.response.modelo){
        var place = data.response.modelo[asiento];
        if(place.y == y && place.x == x){
            numero = parseInt(place.n);
        }
    }
    if(numero==0)
        numero = "";
    return numero;
}
function updateDialogContent(){
    $('.items-container').hide().show('blind', {
        'direction': 'vertical'
    }, 'slow');
    $(".bus-container").hide().show('blind', {
        'direction': 'vertical'
    }, 'slow');
}