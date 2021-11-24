/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//    codificado en sha1 y el valor en base64
//   identificador = f87bb64fe05086c310ccc55799c26d7123287879
//   nombreSucursal = fe5b095e2ffd3d49c668bb29d865e0e499826d45
var RECEPCION = "Recepcion";
var ARRIBO = "Arribo";
var ENTREGA = "Entrega";
var EQUIPAJE = "Equipaje";
var BUSQUEDA = "Busqueda";

var TIPO_NORMAL = "Tk9STUFM";
var TIPO_PORPAGAR = "UE9SIFBBR0FS";
var TIPO_GIRO = "R0lSTw==";

var INFORMACION_VIAJE = {};
// controles basicos equipajes
var LEFT = 1;
var RIGHT = 2;
var UP = 3;
var DOWN = 4;
var IsLoadedControl = false;
var sucursalesRestCache = {};
var clientType = {
    normal: "Normal",
    corporate: "Coorporativo",
    corporate64: "Q29vcnBvcmF0aXZv"
};

$.fn.serializeObject = function ()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

////////////////////////////////  
var contentDisplay = "Recepcion";
var events = {
    busqueda: {
        actions: [
            {
                "a": "verFactura",
                "i": "icono_factura.gif",
                "t": "Muestra informacion de la factura"
            },
            {
                "a": "verMovimiento",
                "i": "page_white_find.gif",
                "t": "Muestra seguimiento de la encomienda"
            },
            {
                "a": "verDetalle",
                "i": "page_white_edit.gif",
                "t": "Muestra detalle de la encomienda"
            }
            , {
                "a": "showFormEntrega",
                "i": "entrega.png",
                "t": "Muestra el formulario de entrega"
            }
//            , {
//                "a": "addFacturaManual",
//                "i": "facturaManual.jpeg",
//                "t": "Muestra el formulario de factura manual"
//            }
        ]
    },
    recepcion: {},
    equipaje: {},
    arribo: {},
    entrega: {
        actions: [
            {
                "a": "showFormEntrega",
                "i": "entrega.png",
                "t": "Muestra el formulario de entrega"
            }
        ]
    },
    cliente: {
        actions: [
            {
                "a": "showClientDialog",
                "i": "page_white_edit.gif",
                "t": "Edita un cliente"
            },
            {
                "a": "cancelCorporateDebt",
                "i": "money.gif",
                "t": "Cobrar Deuda"
            }
        ]
    }
};

$(window).load(function () {

    var printer = window.printer ? window.printer : document.printerSystem;

    /*
     if(printer.checkPrinter()==false){
     $("body").block();
     var content =  "<div id='messajePrinter'><h3>No se ha detectado impresoras por favor verifique tener una impresora instalada en el equipo para poder imprimir las facturas </h3>";
     content+="        <div class='menu_core'><span class='menu_item'><a href='"+ BaseUrl +"/index/logout/'>Cerrar Session</a></span></div>";
     content+='      </div>';
     $("body").append(content);
     $("#messajePrinter").css({
     position:'absolute',
     left: ($(window).width() - $('#messajePrinter').outerWidth())/2,
     top: ($(window).height() - $('#messajePrinter').outerHeight())/2
     });
     return;
     }*/
    $("li.itemtab a").click(changeContent); // cambia el contenido segun la pestaÃ±a que se selecciono

    $("#dateCalendarHelper").datepicker({
        showOn: "both",
        buttonImage: BaseUrl + "/images/event.gif",
        buttonImageOnly: true,
        dateFormat: "yy-mm-dd",
        onSelect: function (dateText, ins) {
            reloadItinerary(dateText);
            reloadManifiesto(dateText);
        }
    });
    makeRecepcion();

});

function showDialog(id, params) {
    var selector = "#" + id;
    var paramDefault = {
        width: "auto",
        autoResize: true,
        autoOpen: false,
        closeOnEscape: true,
        closeText: 'Cerrar',
        draggable: true,
        modal: true,
        position: 'center',
        resizable: true,
        dataType: 'html',
        onComplet: null,
        close: function () {
            $(this).dialog("destroy");
            // Remove the left over element (the original div element)
//            $(this).remove();
        }
    };
    $.extend(paramDefault, params);
    if ($(selector).length === 0) {
        $("body").prepend("<div id='" + id + "'></div>");
    } else {
        $(selector).html("");
    }
    $(selector).dialog(paramDefault);
    $(selector).dialog('open');
    if ($(".ui-dialog-title-help").length === 0) {
        $("div.ui-dialog-titlebar").prepend("<span class='ui-dialog-title-help'><a href='#' onclick='showHelp();'>?</a></span>");
    }
    if (paramDefault.url !== null && paramDefault.url !== undefined) {

        $(selector).ajaxStart(
                function () {
                    if (!$("#processProgress")[0]) {
                        $(selector).prepend(
                                "<img id='processProgress' src='" + BaseUrl + "/images/ajax-loader.gif' />");
                    }
                    $("div.ui-dialog-buttonpane button").each(function () {
                        $(this).addClass("ui-state-disabled");
                        $(this).attr("disabled", "true");
                    });
                });
        $.ajax({
            cache: false,
            url: BaseUrl + paramDefault.url,
            dataType: paramDefault.dataType,
            success: function (msg) {
                $(selector).html(msg);
                $("div.ui-dialog-content span.information img").tooltip({
                    delay: 500,
                    showURL: false,
                    extraClass: "information"
                });
                $(selector).append("<div class='ui-state-highlight' style='clear:left;margin-top:5px;'>Los campos marcados con (*) son obligatorios</div>");
                showHelp();
            },
            complete: paramDefault.onComplet
        });
        $(selector).ajaxStop(function () {
            $("#processProgress").remove();
            $("div.ui-dialog-buttonpane button").each(function () {
                $(this).attr("disabled", false);
                $(this).removeClass("ui-state-disabled");
            });
        });
        $(selector).ajaxError(function (event, request, settings) {
            $(this).show();
            console.log(event, request, settings);
            $(this).append("<li>Error requesting page " + settings.url + "</li>");
        });
    } else {
        $(selector).html(paramDefault.content);
    }
    return false;
}
function removeDialog(id) {
    $(id).dialog("close");
    $(id).dialog("destroy");
    $(id).remove();
}
function showHelp() {
    $("div.descripcion").each(function () {
        if ($(this).is(":visible")) {
            $(this).hide(200);
        } else {
            $(this).show(500);
        }
    });
}

function asignarEventosBotonesDerecha() {
    // asignar funciones a los botones de la derecha
    $("#registrarEncomienda").click(function () {
        showFacturaForm($("#tipoEncomienda").val(), "Computarizada");
    });
    $("#registrarEncomiendaManual").click(function () {
        if ($("#tipoEncomienda").val() != "SU5URVJOTw==") {
            var fechaCalendario = $("#dateCalendarHelper").val();
            showDialog("dataFactura", {
                url: '/recepcion/list-dosificacion/fecha/' + fechaCalendario + '/tipo/' + $("#tipoEncomienda").val(),
                title: "Datos facturacion",
                type: "POST",
                buttons: {
                    "Aceptar": function (evt) {
                        var obj = evt.currentTarget;
                        registrarEncomienda("Manual", obj);
                    },
                    "Cerrar": function () {
                        $(this).dialog("close");
                    }
                },
                onComplet: function () {
                    $("#nitFactura").select();
                }
            });
        } else {
            alert("No se puede guardar una encomienda (Por pagar/S.I.) con una factura manual ");
        }

    });
    $("#resetEncomienda").click(function () {
        $("#encomiendasForm")[0].reset();
        verificarTipo();
    });
    $("#extractoEncomienda").click(function () {
        showDialog("extractoDialog", {
            url: '/recepcion/extracto/fecha/',
            title: "Extracto del vendedor",
            type: "POST",
            buttons: {
                "Imprimir": function (evt) {
                    imprimirRecibo($("#extractoContent").html());
                },
                "Detalle": verDetalleExtracto,
                "Cerrar": function () {
                    removeDialog("#extractoDialog");
                }
            }
        });
    });

    // muestra la interfaz de encoiendas registradas en la sucursal del usuario
    $("#verEncomiendas").click(function () {
        verEncomiendasRecepcionadas($("#ciudadDest").val());
    });// idem
}
function verDetalleExtracto(evt) {
    showDialog("detalleExtractoDialog", {
        url: '/recepcion/detalle/fecha/' + $("#fechaExtracto").val(),
        title: "Extracto del vendedor",
        type: "POST",
        buttons: {
            "Imprimir": function (evt) {

                var html = $("#detalleExtractoDialog").clone();
                html.find("table tr").each(function () {
                    $(this).find("td:eq(3)").remove();
                    $(this).find("th:eq(3)").remove();
                });
                html.find("table tr:last td:first-child").attr("colspan", "3");
                imprimirRecibo(html.html());
            },
            "Cerrar": function () {
                $(this).dialog("close");
            }
        }
    });
}

function isNormalClient() {
    var val = $("#tipoCliente").text();
    return val === clientType.normal;
}
/**
 * Muestra el formulario de la factura 
 * @param tipo    String    identifica el tipo de encomienda para mostrar las dosificaciones o no 
 * @param formato String    
 * 
 */
function showFacturaForm(tipo, formato) {
    var validacion = validarForm();
    if (validacion.encomienda == false && validacion.items == false && $("#destinoSuc").val() != 0) {
        if (isNormalClient() && (tipo === TIPO_NORMAL || tipo === TIPO_GIRO)) {
            showDialog("dataFactura", {
                url: '/recepcion/show-form-factura/tipo/' + $("#tipoEncomienda").val() + "/formato/" + formato + "/nombre/" + $("#remitente").val(),
                title: "Datos facturacion",
                buttons: {
                    "Aceptar": function () {
                        previewRegister(formato, $("#registrarEncomienda"));
                    },
                    "Cerrar": function () {
                        $(this).dialog("close");
                    }
                },
                onComplet: function () {
                    $("#nitFactura").val($("#nitCliente").val());
                    $("#nitFactura").numeric().select();
                    if (tipo === TIPO_NORMAL) {
                        $("#declarado").change(function () {
                            $("#rowmontoDeclarado").show();
                        });
                        $("#montoDeclarado").numeric().select();
                    }
                }
            });
        } else {
            if (isNormalClient() === false) {
                previewRegister(clientType.corporate, $("#registrarEncomienda"));
            } else {
                previewRegister("Automatico", $("#registrarEncomienda"));
            }
        }
    } else {
        var mensaje = "Existen campos importantes que no han sido llenados, Por favor intente de nuevo";
        if (validacion.items == true) {
            mensaje = "No se han registrado items para la encomienda";
            if (tipo == TIPO_GIRO) {
                mensaje = "El monto del giro no puede ser vacio ni 0";
            }
        } else {
            if ($("#destinoSuc").val() == 0) {
                mensaje = "No existen sucursales de destino por favor solicite al administrador el registro de las misma y vuelva a intentarlo";
            }
        }
        alert(mensaje);
    }
}

function validarForm() {
    var resp = false;
    var noHayItems = true;
    if ($("#tipoEncomienda").val() == TIPO_GIRO) {
        if ($("#montoGiro").val() != "" && $("#montoGiro").val() != "0" && $("#montoGiro").val() != 0) {
            noHayItems = false;
        }
    } else {
        var elements = $("#encomiendasForm").serializeArray();
        for (var index = 7; index < elements.length; index++) {
            index = parseInt(index);
            var element = elements[index];
            if (element.name == "cantidad") {
                var pos1 = index + 1;
                var pos2 = index + 2;
                var pos3 = index + 3;
                index = pos3;
                if ((element.value !== "" || element.value !== 0) && elements[pos1].value !== "" && elements[pos2].value !== "" && elements[pos3].value !== "") {
                    noHayItems = false;
                }
            } else {
                if ($("#tipoEncomienda").val() === TIPO_GIRO && element.name == "montoGiro" && element.name == "totalGiro") {
                    continue;
                } else {
                    if (element.name == "montoDeclarado" && element.value == "" && element.parents("div#rowmontoDeclarado").isVisible) {
                        resp = true;
                    }
//                    if (element.value == "") {
//                        $("#" + element.name).css("borderColor", "red");
//                        resp = true;
//                    }
                }
            }
        }
        ;
    }
    return {
        "encomienda": resp,
        "items": noHayItems
    };
}
function imprimirRecibo(contenido) {

    var ventimp = window.open('', '_blank');
    imagen_cab = new Image(100, 50);

    imagen_cab.src = BaseUrl + "/images/IconoQuantum.png";

    ventimp.document
            .write('<LINK href="' + BaseUrl + '/styles/print.css" rel="stylesheet" type="text/css" />');
    ventimp.document
            .write('<LINK href="' + BaseUrl + '/styles/factura.css" rel="stylesheet" type="text/css" />');
    ventimp.document
            .write('<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr><td class="linea">');
    ventimp.document.write("<img src=" + imagen_cab.src
            + " alt='' width='50' heigth='50'/>");
    ventimp.document.write("</td></tr>");
    ventimp.document.write('<tr><td>');
    ventimp.document.write(contenido);
    ventimp.document.write('</td></tr></table>');

    ventimp.document.close();
    ventimp.print();
    ventimp.close();
}

/****************  funciones controladoras   **********************/
function getBillData() {
    var billData = {name: $("#remitente").val(), nit: $("#nitCliente").val()};
    if (isNormalClient()) {
        billData.name = $("#dataFactura #nombreFactura").val();
        billData.nit = $("#dataFactura #nitFactura").val();
    }
    return billData;
}
/**
 * 
 * @param {string} tipo tipo que tipo de encomienda se este registrando
 * @param {form} obj  el formulario de registro 
 * @returns {undefined} void
 */
function previewRegister(tipo, obj) {
    var billData = getBillData();

    var html = "<table class='borders'>";
    html += "<caption>Vista previa de la encomienda</caption>";
    html += "<tr><th>Origen</th><td></td><th>Destino</th><td>" + $("#ciudadDest option:selected").text() + "</td></tr>";
    html += "<tr><th>Suc. Origen</th><td></td><th>Suc. Destino</th><td>" + $("#destinoSuc option:selected").text() + "</td></tr>";
    html += "<tr><th>Tipo</th><td>" + $("#tipoEncomienda option:selected").text() + "</td><td></td><td></td></tr>";
    html += "<tr><th>NIT</th><td>" + billData.nit + "</td><th>NOMBRE</th><td>" + billData.name + "</td></tr>";
    html += "<tr><th>REMITENTE</th><td>" + $("#remitente").val() + "</td><th>TELEFONO</th><td>" + $("#remitenteTelf").val() + "</td></tr>";
    html += "<tr><th>DESTINATARIO</th><td>" + $("#destinatario").val() + "</td><th>TELEFONO</th><td>" + $("#destinatarioTelf").val() + "</td></tr>";
    if (!isGiro()) {
        html += "<tr><th colspan='4'>DETALLE DE ENCOMIENDA</th></tr>";
        html += "<tr><th>CANT</th><th>DETALLE</th><th>PESO</th><th>TOTAL</th></tr>";
        var posItem = 0;
        $("#items div.row:not(:last)").each(function () {
            var cant = $(this).find("input[name=cantidad]").val();
            var peso = $(this).find("input[name=peso]").val();
            peso = Math.round((peso) * 100) / 100;
            var detalle = $(this).find("input[name=detalleEquipaje]").val();
            var efectivo = $(this).find("input[name=efectivo]").val();
            efectivo = Math.round((efectivo) * 100) / 100;
            if (cant != "" || cant != 0) {
                html += "<tr><td class='numeric'>" + cant + "</td><td>" + detalle + "</td><td class='numeric'>" + peso + "</td><td class='numeric'>" + efectivo + "</td></tr>";
                posItem++;
            }
        });
        var declarado = $("#declarado").is(":checked") ? "Valor declarado por " + $("#montoDeclarado").val() : " Valor no declarado ";
        html += "<tr><th>Obs.</th><td colspan=3>" + declarado + "</td></tr>";
    } else {
        html += "<tr><th colspan='4'>DETALLE DEL GIRO</th></tr>";
        html += "<tr><th colspan='2'>Monto</th><th colspan='2'>Importe</th></tr>";
        html += "<tr><td colspan='2' class='numeric'>" + $("#montoGiro").val() + "</td><td colspan='2' class='numeric'>" + $("#totalGiro").val() + "</td></tr>";
    }
    html += "</table>";
    showDialog("previewFacturacion", {
        content: html,
        title: "Vista previa facturacion",
        buttons: {
            "Aceptar": function (a, e) {
                $(a.target).attr("disabled", true).addClass("ui-state-disabled");
                registrarEncomienda(tipo, obj);
            },
            "Cerrar": function () {
                $(this).dialog("close");
            }
        },
        onComplet: function () {
            $("#nitFactura").select();
        }
    });
}

function registrarEncomienda(tipo, obj) {
    var idViaje = $("#selectedViaje").val();
    var idBus = $("#selectedIdBus").val();
    $(obj).val("Guardando Encomienda").attr("disabled", true);
    var textButton = "Guardar";
    var datosE = "{";
    var datosJSON = {
        items: "",
        detalle: "",
        total: $("#total").val(),
        viaje: idViaje,
        idBus: idBus
    };
    var items = "";
    var posItem = 0;
    $("#encomiendasForm label input,#encomiendasForm label select").each(function (pos) {
        var component = $(this);
        var nameC = component.attr("name");
        if (nameC != "montoGiro" && nameC != "totalGiro" && nameC != "detalleGiro") {

            datosJSON[nameC] = component.val();
            var text = "#" + component.attr("id") + " option:selected";
            if (component.attr("name") == "ciudadDest") {
                str = component.val();
                var str = str.substr(str.lastIndexOf("/") + 1);
                datosJSON[nameC] = str;
                datosJSON["nombreCiudadDestino"] = $(text).text();
            }
            if (component.attr("name") == "destinoSuc") {
                datosJSON["nombreDestino"] = $(text).text();
                var ciudadN = $("#ciudadDest option:selected").text().toLowerCase();
                var sucID = $(text).val();
                var selectedFromCache = sucursalesRestCache[ciudadN][sucID];
                datosJSON["abreviacionDestino"] = selectedFromCache.abreviacion;
            }
        }
    });
    var billData = getBillData();
    datosJSON["Nit"] = billData.nit;
    datosJSON["nombreFactura"] = billData.name;
    datosJSON["declarado"] = $("#declarado").is(":checked");
    if (tipo === "Manual") {
        datosJSON["dosificacion"] = $("#dosificacionesList").val();
        datosJSON["numeroFactura"] = $("#numeroFactura").val();
        datosJSON["fecha"] = $("#fechaFactura").val();

    }
    if (datosJSON.tipoEncomienda === TIPO_NORMAL && isNormalClient() === false) {
        datosJSON.tipoEncomienda = clientType.corporate64;
    }
    if (!isGiro()) {
        $("#items div.row:not(:last)").each(function () {
            var cant = $(this).find("input[name=cantidad]").val();
            var peso = $(this).find("input[name=peso]").val();
            var detalle = $(this).find("input[name=detalleEquipaje]").val();
            var efectivo = $(this).find("input[name=efectivo]").val();
            if (cant != "" || cant != 0) {
                datosJSON.detalle += " " + cant + " " + detalle;
                datosE += "'" + posItem + "':{'cantidad':'" + cant + "',";
                datosE += "'detalle':'" + detalle + "',";
                datosE += "'peso':'" + peso + "',";
                datosE += "'valor':'" + efectivo + "'},";
                posItem++;
            }
        });

        datosE = datosE.substr(0, datosE.length - 1) + "}";
    } else {
        var valorGiro = $("#montoGiro").val();
        var totalGiro = $("#totalGiro").val();
        var detalleGiro = $("#detalleGiro").val();
        datosJSON.detalle += " 1 Giro por valor de (" + valorGiro + ") Bs.";

        datosJSON.total = totalGiro;
        datosE += "'1':{'cantidad':'1','detalle':'" + datosJSON.detalle + " " + detalleGiro + ") Bs.','peso':'1','valor':'" + totalGiro + "'}}";
    }
    datosJSON.items = datosE;
    $.ajax({
        type: "POST",
        url: BaseUrl + "/recepcion/save/tipo/" + tipo,
        data: datosJSON,

        dataType: 'json',
        success: function (msg) {
            if (msg.error === false) {
                var e = msg.encomienda;
                if (msg.cabecera != "Manual") {
                    var printer = window.printer ? window.printer : document.printerSystem;
                    console.log("ddddddddd");
                    console.log(JSON.stringify(window.printer));
                    console.log(JSON.stringify(document.printerSystem));
                    if (window.printer) {
                        printer.setDocument("facturaEncomienda");
                        printer.setJson(JSON.stringify(msg.empresa), "empresa");
                        printer.setJson(JSON.stringify(msg.encomienda), "encomienda");
                        printer.setJson(JSON.stringify(msg.factura), "factura");
                        printer.setJson(JSON.stringify(msg.cabecera), "sucursal");
                        for (var jk in msg.items) {
                            var itm = msg.items[jk];
                            printer.setJson(JSON.stringify(itm), "item");
                        }
                    } else {
                        console.log(JSON.stringify(msg));
                        var print2 = null;
                        if(msg.pdf_encomienda_porpagar_recibo !== undefined){
                            print2 = msg.pdf_encomienda_porpagar_recibo;
                        } else {
                            print2 = msg.pdf_encomienda_recibo;
                        }

                        loadImprRecepcion(msg);

                        location.reload();

                    }
                    removeDialog("#previewFacturacion");
                }
                removeDialog("#dataFactura");

                var lastCiudad = $("#ciudadDest option:selected").val();
                $("#tipoEncomienda").val(TIPO_NORMAL);
                verificarTipo();
                $("#encomiendasForm")[0].reset();
                $("#ciudadDest").val(lastCiudad);
                cambiarCiudad();
            } else {
                var texto = msg.mensaje;
                if (msg.mensaje instanceof Object) {
                    texto = "Existen los siguientes errores por favor corrijalos \n";
                    for (var a in msg.mensaje) {
                        var id = "#" + a;
                        $(id).css("borderColor", "red");
                        texto += msg.mensaje[a] + " \n";
                    }
                }
                alert(texto);

            }
            $(obj).val(textButton).attr("disabled", false);
        },
        complete: function () {
            $(obj).val(textButton).attr("disabled", false);
            $("#previewFacturacion ui-dialog-buttonset button").attr("disabled", false).removeClass("ui-state-disabled");
        }
    });
}

function isGiro() {
    return $("#tipoEncomienda").val() === TIPO_GIRO;
}

function makeRecepcion() {
    $("#nitCliente").autocomplete({
        minLength: 2,
        source: function (request, response) {
            $.ajax({
                url: BaseUrl + "/recepcion/get-clientes/",
                type: "GET",
                dataType: "json",
                data: {
                    search: request.term
                },
                success: function (data) {
                    response(data);
                }
            });
        },
        select: function (event, ui) {            
            $("#nitCliente").val(ui.item.value.nit);
            $("#remitente").val(ui.item.value.nombre);
            $("#tipoCliente").text(ui.item.value.tipo);
            return false;
        }
    });

    asignarEventosBotonesDerecha();
    $(".verManifiesto").click(verManifiesto);// muestra el contenido de un manifiesto
    $("#copyDestinatary").click(function () {
        $("#destinatario").val($("#remitente").val());
    });// muestra el contenido de un manifiesto
    $("#montoGiro").blur(function () {
        var porcentaje = parseInt(SYS_CONFIG.PORCENTAJE_GIRO) * parseInt($("#montoGiro").val()) / 100;
        porcentaje = parseInt(SYS_CONFIG.MONTO_MINIMO_GIRO) < porcentaje ? porcentaje : parseInt(SYS_CONFIG.MONTO_MINIMO_GIRO);
        $("#totalGiro").val(porcentaje);
    });

    $("#ciudadDest").change(cambiarCiudad);// carga las sucursales de una ciudad
    $("#tipoEncomienda").change(verificarTipo);// verifica las cantidades pesos y precios de las encomiendas cuando se cambia de tipo de encomienda
    $("#encomiendasForm div#items input[name='cantidad'],#encomiendasForm div#items input[name='peso']").change(verificarCantidad);
    $("#encomiendasForm div#items input[name='efectivo']").change(function () {
        var cant = $(this).parents("div.row").find("input[name='cantidad']").val();
        if (cant == "" || cant == 0) {
            $(this).parents("div.row").find("input[name='cantidad']").val(1);
            cant = 1;
        }
        var total = $(this).val();
        var peso = $(this).parents("div.row").find("input[name='peso']");
        if (defaultConfig.AUTO_AJUSTE_ENCOMIENDA == "SI") {
            var ajuste = Math.round((total / cant) * 100) / 100;
            peso.val(ajuste);
        }
        if (defaultConfig.AUTO_AJUSTE_ENCOMIENDA == "NO" && (peso.val() == "" || peso.val() == 0)) {
            peso.val(1);
        }
        verificarTipo();
    });
    $("#nombreFactura").change(function () {
        $("#remitente").val($(this).val());
    });
    makeEnter2Tab("#encomiendasForm", "efectivo");
    $("#destinoSuc").focus().select();


    //    asignarAutoComplete();

    $(".numeric").numeric();
    cambiarCiudad();
    verificarTipo();
}
function makeItinerayList() {
    $("#itinerarioList").accordion({
        header: 'h3',
        autoHeight: false,
        collapsible: true,
        change: function (event, ui) {
            $(".select-acordion option").attr('selected', false);
            $("#selectedViaje,#selectedBus,#selectedHora,#selectedDestino,#selectedIdBus").val(0);
            $("#choferViaje").html("<option>No seleccionado</option>");

            var ciudad = $(ui.newHeader).find("a").attr("ciudad");

            $("#ciudadDest option").each(function () {
                var valueOp = $(this).val();
                if (valueOp.indexOf(ciudad) != -1) {
                    $(this).attr("selected", true);
                    cambiarCiudad();
                }
            });

            $("select#filtroCiudadDest option[selected]").removeAttr("selected");
            $("select#filtroCiudadDest option[value='" + ciudad + "']").attr("selected", "selected");

            //            listarEncomienda($("#filtroCiudadDest").val());

            $numPulsate = 0;
        }
    });
}
function asignarAutoComplete() {
    $("input[name='detalleEquipaje']").autocomplete(arrayDefault, {
        minChars: 0,
        width: 310,
        matchContains: "word",
        autoFill: false,
        formatItem: function (row, i, max) {
            return  row.detalle + "[" + row.precio + "]";
        },
        formatMatch: function (row, i, max) {
            return row.detalle + " " + row.peso;
        },
        formatResult: function (row) {
            return row.detalle;
        }
    });
    $("input[name='detalleEquipaje']").result(function (event, data, formatted) {
        if (data) {
            var obj = $(this);
            var cant = obj.parent().prev().find("input:first").val();
            obj.parent().next().find("input:first").val(data.peso);
            obj.parent().next().next().find("input:first").val(cant * data.precio);
            obj.parent().next().next().find("input:first").select();
            totalizar();
        }
        //        calcularPeso();
    });
    $("input[name='Nit']").autocomplete(clientesDefault, {
        minChars: 0,
        width: 310,
        matchContains: "word",
        autoFill: false,
        formatItem: function (row, i, max) {
            return  row.nombre + "[" + row.nit + "]";
        },
        formatResult: function (row) {
            return row.nit;
        }
    });
    $("input[name='Nit']").result(function (event, data, formatted) {
        if (data) {
            var obj = $(this).parent();
            $("input[name='nombreFactura']").val(data.nombre);
            $("input[name='remitente']").val(data.nombre).select();

        }
        //        calcularPeso();
    });
}

function cambiarCiudad() {
    var obj = $("#ciudadDest");
    var urlnav = BaseUrl + "/recepcion/cambiar-ciudad/";
    var str = obj.val();
    str = str.substr(str.lastIndexOf("/") + 1);
    var params = {
        "id": str
    };
    var subSel = $("#destinoSuc");
    subSel.html("<option>cargando...</option>");
    // mandamos a cargar las sucursales de la ciudad en el servidor seleccionado
    var service = obj.val();
    service = service.substr(0, service.lastIndexOf("/") - 3) + "/ciudad/rest/id/" + str;
    //    $.log(service);
    var paramsJSON = {
        "method": "getSucursales",
        "userid": "1",
        "apikey": "1234567890",
        "format": "json",
        "callback": "recargarSucursales"
    };
    //    $.log("Antes jsonP");
    jsonP(service, paramsJSON);
    //    $.log("Despues jsonP");
    // mandamos a cargar os precios definidos segun la ciudad que se seleccione
    $.ajax({
        type: "GET",
        url: urlnav,
        data: params,
        dataType: "json",
        success: function (msg) {
            //            $.log("Cartgando precios de encomiendas predefinidas");
            arrayDefault = msg.encomiendas;
            //            $.log(arrayDefault);
            $("#encomiendasForm>div#items input").each(function (pos) {
                var component = $(this);
                var nameC = component.attr("name");
                if (component.val() != "") {
                    if (nameC == "cantidad") {
                        var cant = component.val();
                        var detalle = component.parent().next().find("input").val();
                        for (var i in arrayDefault) {
                            if (detalle == arrayDefault[i].detalle) {
                                component.parent().next().next().find("input").val(arrayDefault[i].peso);
                                component.parent().next().next().next().find("input").val(arrayDefault[i].precio * cant);
                            }
                        }
                    }
                }
            });
            totalizar();
        }
    });
}
function recargarSucursales(json) {

    $("#destinoSuc").html("");
    if (json.response.sucursales == "") {
        var opt = $("<option/>");
        opt.attr("value", 0);
        opt.text("No existen sucursales");
        $("#destinoSuc").append(opt);
    } else {
        $.each(json.response.sucursales, function (i, suc) {
            var ciudadN = suc.ciudadNombre.toLowerCase();
            if (!sucursalesRestCache[ciudadN]) {
                sucursalesRestCache[ciudadN] = {};
                sucursalesRestCache[ciudadN][suc.id] = suc;
            } else {
                if (!sucursalesRestCache[ciudadN][suc.id]) {
                    sucursalesRestCache[ciudadN][suc.id] = suc;
                }
            }
            var opt = $("<option/>");
            opt.attr("value", suc.id);
            opt.text(suc.nombre);
            $("#destinoSuc").append(opt);
        });
    }
}

function verEncomiendasRecepcionadas() {
    var buttons = {
        "Imp. lista": function () {
            var contenido = $("#infoEncomiendas div:visible").html();
            imprimirRecibo(contenido);
        },
        "registrarEnvio": registrarAManifiesto
    }
    dialog("verEncomiendas", "Lista de encomiendas recepcionadas", buttons, true);
    var destinoValue = $("#ciudadDest").val();
    var idDestino = destinoValue.substr(destinoValue.lastIndexOf("/") + 1);
    $.ajax({
        type: "POST",
        cache: false,
        url: BaseUrl + '/recepcion/show-ver-encomienda/destino/' + idDestino,
        success: function (data) {
            $("#jqmnContent").html(data);
        },
        complete: function (d) {
            $("#itineraioList #itinerarioList").accordion({
                header: 'h3',
                autoHeight: false,
                collapsible: true
            });
            $("#infoEncomiendas").accordion({
                header: 'h3',
                autoHeight: false,
                collapsible: false
            });
        }
    });

    return false;
}


/**
 *Limpia la informacion de la cabecera del manifiesto
 */
function clearInfoManifiesto() {
    $("#choferesContent").html("");
    $("#busContent").html("");
    $("#fechaContent").html("");
    INFORMACION_VIAJE = {};
}

/*
 * funcion encargada de sumar el total de las encomiendas
 */
function totalizar() {
    var total = 0;
    $("#encomiendasForm input[name='efectivo']").each(function (pos) {
        if ($(this).val() != "") {
            total += Math.round(($(this).val()));
        }
    });
    $("#total").val(total);
}

/*
 * Funcion encargada de verificar el tipo de encomienda y ajustar el precio
 */
function verificarTipo() {
    var obj = $(this);
    if (obj.attr("id") != "tipoEncomienda") {
        obj = $("#tipoEncomienda");
    }
    var color = "#DFE4EE";
    $("#itemsEncomienda").show();
    $("#itemsGiro").hide();
    if (obj.val() == TIPO_NORMAL) {//NORMAL
        color = "#DFE4EE";
    } else if (obj.val() == TIPO_PORPAGAR) {//POR PAGAR
        color = "#6D8874";
    } else if (obj.val() == TIPO_GIRO) {//GIRO
        color = "#CCFFCC";
        $("#itemsGiro").show();
        $("#itemsEncomienda").hide();
    } else {
        color = "#627F96";
    }
    $("#middleForm div.contentBox").css("background-color", color);

    if (obj.val() == TIPO_NORMAL || obj.val() == TIPO_PORPAGAR) {//Normal
        $("#items div.row:not(:last)").each(function () {
            var cant = $(this).find("input[name=cantidad]").val();
            var peso = $(this).find("input[name=peso]").val();
            var efectivo = $(this).find("input[name=efectivo]");
            if (defaultConfig.AUTO_AJUSTE_ENCOMIENDA == "SI") {
                if (cant != "" || cant != 0) {
                    var pago = Math.round((cant * peso) * 100) / 100;
                    efectivo.val(pago);
                }
            } else {
                if (efectivo.val() != "" && efectivo.val() != 0) {
                    if (cant == "" || cant == 0) {
                        $(this).find("input[name=cantidad]").val(1);
                    }
                    if (peso == "" || peso == 0) {
                        $(this).find("input[name=peso]").val(1);
                    }
                }
            }
        });
        totalizar();
    } else {
        $("#total").val(0);
        if (obj.val() == TIPO_GIRO) {

        }
    }
}

/*
 *Funcion encargada de verificar la cantidad de items que se envian y calcular el total que se paga por ese item 
 *
 */
function verificarCantidad() {
    var component = $(this);
    var cant = component.val();
    var valor = 0;
    // por pagar $("#tipoEncomienda").val()=="UE9SIFBBR0FS" ||                             // servicio interno
    if ($("#tipoEncomienda").val() == "SU5URVJOTw==") {
        component.parent().parent().find("input[name=efectivo]").val(0);
    } else {
        if (defaultConfig.AUTO_AJUSTE_ENCOMIENDA == "SI") {
            if (component.attr("name") == "cantidad") {
                if (component.val() != "" && cant != 0) {
                    valor = component.parent().next().next().find("input").val();
                    component.parent().next().next().next().find("input").val(valor * cant);
                } else {
                    if (cant == "" || cant == 0)
                        cant = 1;
                    component.val(cant);
                }
            } else {
                valor = component.parent().prev().prev().find("input").val();
                if (valor == "0" || valor == "") {
                    component.parent().prev().prev().find("input").val(1);
                    valor = 1;
                }
                component.parent().next().find("input").val(valor * cant);
            }
        } else {
            if (component.attr("name") == "cantidad") {

            } else {
                //peso   
            }
        }
    }
    totalizar();
}

function listarEncomienda(ciudadDestino, idForTable) {
    //    var obj = $(this).val()=="Buscar"?$("#filtroCiudadDest"):$(this);
    $("#contenidoEncomiendas").block();
    $.ajax({
        type: "GET",
        url: BaseUrl + "/recepcion/filtro-encomiendas/destino/" + ciudadDestino,
        dataType: "json",
        success: function (msg) {
            var selector = idForTable + " tr:not(:first)";
            $(selector).each(function (pos) {
                var obj = $(this);
                obj.remove();
            });
            var dest = "";
            var cont = 0;
            if (msg.length > 0) {
                for (var i in msg) {
                    var cls = cont % 2 == 0 ? "par" : "impar";
                    var enc = msg[i];
                    var cab = "";
                    if (dest != enc.destino) {
                        cab = "<tr><th colspan='3'>" + enc.destino + "</th></tr>";
                        dest = enc.destino;
                    }
                    var append = "<tr><td class='" + cls + "'><input type='checkbox' value='" + enc.id_encomienda + "'/></td>";
                    append += "<td class='" + cls + "'>" + enc.guia + "</td>";
                    append += "<td class='" + cls + "'>" + enc.detalle + "</td>";
                    append += "<td class='" + cls + "'>" + enc.total + "</td></tr>";
                    $(idForTable).append(cab + append);
                    cont++;
                }
            } else {
                var append1 = "<tr><td class='par' colspan='4'>No se encontraron encomiendas con el destino </td></tr>";
                $(idForTable).append(append1);
            }
        }
    });
}

/**
 * imprime la lista de encomiendas que se le pasa como parametro o la lista de la pantalla
 */
function imprimimirLista(type) {
    var objImp = new Object();
    var i = 0;
    var printer = document.printerSystem;
    printer.typeDocument = "lista";
    var cab = {};
    var man = {
        "chofer": $("#choferViaje option:selected").text(),
        "fecha": "",
        "hora": $("#selectedHora").val(),
        "destino": $("#filtroCiudadDest option:selected").text(),
        "numBus": $("#selectedBus").val()
    };
    if (type == "actual") {
        $("#listaEncomiendas tr:not(:first) td:nth-child(2)").each(function () {
            var obj = $(this);
            objImp[i] = {
                "guia": obj.text(),
                "detalle": obj.next().text()
            };
            i++;
        });
        cab.title = "Por asignar";
        cab.emp = "Lista encomiendas";
    } else {

        objImp = type.lista;
        man = type.manifiesto;
        cab = type.cabecera;

    }
    printer.setCabecera("-", "-", cab.direccion, "-", "-", "-", cab.usuario, cab.title, cab.emp, "00");
    printer.setManifiesto(man.chofer, man.fecha, man.hora, man.destino, man.numBus);
    for (var p in objImp) {
        var enc = objImp[p];
        printer.addEncomienda(enc.guia, enc.detalle, enc.total.toString(), enc.tipo);
    }
    try {
        printer.imprimir();
        printer.clean();
    } catch (ex) {
        console.log("error en el applet de impresion", ex);
    }
}
/**
 * imprime la lista de encomiendas que se le pasa como parametro o la lista de la pantalla
 */
function imprimimirListaPrintjs(type) {
    var objImp = new Object();
    var i = 0;
    // var printer = document.printerSystem;
    // printer.typeDocument = "lista";
    var cab = {};
    var man = {
        "chofer": $("#choferViaje option:selected").text(),
        "fecha": "",
        "hora": $("#selectedHora").val(),
        "destino": $("#filtroCiudadDest option:selected").text(),
        "numBus": $("#selectedBus").val()
    };
    if (type == "actual") {
        $("#listaEncomiendas tr:not(:first) td:nth-child(2)").each(function () {
            var obj = $(this);
            objImp[i] = {
                "guia": obj.text(),
                "detalle": obj.next().text()
            };
            i++;
        });
        cab.title = "Por asignar";
        cab.emp = "Lista encomiendas";
    } else {

        objImp = type.lista;
        man = type.manifiesto;
        cab = type.cabecera;

    }
    // printer.setCabecera("-", "-", cab.direccion, "-", "-", "-", cab.usuario, cab.title, cab.emp, "00");
    // printer.setManifiesto(man.chofer, man.fecha, man.hora, man.destino, man.numBus);
    // for (var p in objImp) {
    //     var enc = objImp[p];
    //     printer.addEncomienda(enc.guia, enc.detalle, enc.total.toString(), enc.tipo);
    // }
    try {
        jsonDatosManifiesto = {
            cabecera: cab,
            manifiesto: man,
            encomiendas: objImp
        }

        $.ajax({
            type: "GET",
            dataType: "json",
            url: BaseUrl + "/recepcion/imprimir-manifiesto",
            data: {datosM: jsonDatosManifiesto},
            success: function (msg) {
                printJS(msg);
                // alert(JSON.stringify(msg));
            }
        });
        // printer.imprimir();
        // printer.clean();
    } catch (ex) {
        console.log("error en el applet de impresion", ex);
    }
}
/**
 * manda a guardar las encomiendas seleccionadas a un manifiesto de un viaje seleccioando
 */
function registrarAManifiesto() {
    var datos = "";
    $("#infoEncomiendas div table tr:not(:first-child) input[type='checkbox']").each(
            function () {
                if ($(this).is(':checked') == true) {
                    datos += $(this).val() + ",";
                }
            }
    );
    if (datos == "") {
        alert("no se ha seleccionado ninguan encomienda");
        return;
    }
    datos = datos.substr(0, datos.length - 1);
    $("#verEncomiendas").block();
    //    $.log("Enciando chofer ... ("+$("#choferViaje").val()+" )  ");
    var fechaCalendario = $("#dateCalendarHelper").val();
    var viaje = INFORMACION_VIAJE;

    if (!viaje.viaje) {
        alert("Seleccione un viaje para enviar las encomiendas por favor");
        return;

    }
    $.ajax({
        type: "GET",
        dataType: "json",
        url: BaseUrl + "/recepcion/save-manifiesto/viaje/" +
                viaje.viaje + "/data/" + datos + "/chofer/" + viaje.chofer + "/bus/" + viaje.idBus +
                "/destino/" + viaje.destino + "/fechaViaje/" + fechaCalendario,
        success: function (msg) {
            if (msg.error == false) {
                var man = {
                    "chofer": INFORMACION_VIAJE.nombreChofer,
                    "fecha": INFORMACION_VIAJE.fecha,
                    "hora": INFORMACION_VIAJE.hora,
                    "destino": INFORMACION_VIAJE.ciudadDestino,
                    "numBus": INFORMACION_VIAJE.interno
                };
                msg.manifiesto = man;
                imprimimirLista(msg);
                $("#infoEncomiendas div table tr:not(:first-child) input[type='checkbox']").each(
                        function () {
                            if ($(this).is(':checked') == true) {
                                $(this).parent().parent().remove();
                            }
                        }
                );
            }
            //            reloadManifiesto($("#dateCalendarHelper").val());
            alert(msg.mensaje);
        }
    });
}

function selectViaje(viaje, interno, hora, destino, bus) {
    $("#selectedViaje").val(viaje);
    $("#selectedBus").val(interno);
    $("#selectedHora").val(hora);
    $("#selectedDestino").val(destino);
    $("#selectedIdBus").val(bus);
    var send = "";
    var format = "json";
    var success = function () {
    };
    if (contentDisplay == EQUIPAJE) {

        send = BaseUrl + "/recepcion/get-planilla/viaje/" + viaje + "/interno/" + interno;
        format = "json";
        success = function (msg) {
            $("#planillaViaje").html(msg.modelo).parent("div").css("height", $("#planillaViaje .model").height());
            if (IsLoadedControl == true) {
                $.log("makeControlable IsLoadControl  ");
                makeControlable();
            } else {
                $.log("LoadControlable IsLoadControl  ");
                loadControlable();
                IsLoadedControl = true;
            }
        };
    } else {
        //        send=BaseUrl+"/recepcion/get-choferes/viaje/"+viaje;
        send = BaseUrl + "/recepcion/get-detalle-viaje/viaje/" + viaje;
        success = function (msg) {
            if (msg.mensaje.choferes.length == 0) {
                alert("No se han asignado choferes al viaje");
            }
            var infoV = msg.mensaje.datos;
            $("#modeloBus").val(infoV.modelo);
            $("#internoBus").val(infoV.interno);
            $("#listaChoferes").html("");
            for (var ch in msg.mensaje.choferes) {
                var cho = msg.mensaje.choferes[ch];
                $("#listaChoferes").append("<li>" + cho.nombre + "</li><br/>");
            }
        };
    }
    $.ajax({
        type: "GET",
        dataType: format,
        url: send,
        success: success
    });

}
function cargarManifiestos(viaje, bus) {
    //    var obj =$(this);
    //    $.fancybox.showActivity();
    var dataMan = {
        "lista": {},
        "manifiesto": {
            "fecha": "",
            "chofer": ""
        },
        "cabecera": {
            "emp": "MBS",
            "title": ""
        }
    }
    var arrayDM = new Array();
    //    var butons ={
    //        "Imprimir":function(){
    //            imprimimirLista(dataMan);
    //        }
    //    }
    dialog("ListaManifiestosDialog", "Manifiestos registrado para el viaje");

    //    $.log(asientos);
    $.ajax({
        dataType: "json",
        type: "POST",
        cache: false,
        url: BaseUrl + '/recepcion/show-manifiestos-viaje/viaje/' + viaje + "/bus/" + bus,
        success: function (data) {
            var dataCont = new Array();
            var count = 0;
            for (var i in data.encomiendas) {
                dataCont[count] = makeTable("Encomiendas", ["Guia", "Detalle", "Importe"], data.encomiendas[i]);
                count++;

            }
            //            $.log(dataCont);
            var dataAcordion = new Array();
            var pos = 0;
            var dataManifiestos = new Object();
            var cabecera = data.cabecera;
            for (var e in data.manifiestos) {
                var man = data.manifiestos[e];
                var print = "";
                if (man.print == true) {
                    //                    dataMan.lista = data.encomiendas[e];
                    //                    dataMan.manifiesto=man;
                    dataManifiestos[man.id] = new Object();
                    dataManifiestos[man.id].lista = data.encomiendas[e];
                    dataManifiestos[man.id].manifiesto = man;
                    dataManifiestos[man.id].cabecera = cabecera;
                    print = "<span class='inline printM' id='" + man.id + "'><img src='" + BaseUrl + "/images/print.gif'/></span>";
                }
                dataAcordion[e] = {
                    "title": "<span class='inline'>Manifiesto creado por " + man.encargado + "</span>" + print,
                    "content": dataCont[pos]
                };
                pos++;
            }
            var content = makeAccordion("accListManifiesto", dataAcordion);
            $("#ListaManifiestosDialog #jqmnContent").html(content);
            $("#accListManifiesto").accordion();
            $(".printM").click(function () {
                var posM = $(this).attr("id");
                var dm = dataManifiestos[posM];
                var idM = dm.manifiesto.id;
                var idB = dm.manifiesto.bus;
                //                $.log(dm);
                $.ajax({
                    dataType: "json",
                    type: "POST",
                    cache: false,
                    url: BaseUrl + '/recepcion/register-send/',
                    data: {
                        "manifiesto": idM,
                        "bus": idB
                    },
                    beforeSend: function (xhr) {
                        //                            $(idChange).block();
                    },
                    success: function (data4) {
                        if (data4.error == false) {
                            // imprimimirLista(dm);
                            imprimimirListaPrintjs();
                        }
                    }
                });
            });
        }
    });

    return false;
}

function makeAccordion(id, content) {
    var acc = "<div id='" + id + "'>";
    for (var i in content) {
        var ti = content[i].title;
        var cc = content[i].content;
        acc += "<h3><a href='#'>" + ti + "</a></h3>";
        acc += "<div>" + cc + "</div>";
    }
    acc += "</div>";
    return acc;
}

function makeTable(title, header, content, prop) {
    var idTable = "1";
    if (prop && prop.id) {
        idTable = prop.id;
    }
    var table = "<table class='borders' id='" + idTable + "'>";
    table += "<caption>" + title + "</caption>";
    table += "<tr>";
    for (var t in header) {
        table += "<th>" + header[t] + "</th>";
    }
    table += "</tr>";
    var totalized = new Array();
    for (var c in content) {
        var clsTr = " class=''";
        if (prop && prop.clsTR) {
            clsTr = " class='" + prop.clsTR + "'";
        }
        table += "<tr" + clsTr + ">";
        for (var r in content[c]) {
            var value = content[c][r];
            var clsNumber = isInteger(value) == false ? "letter" : "numeric";
            var y = parseInt(value);
            table += "<td class='" + clsNumber + "'>" + value + "</td>";
            totalized[r] = isInteger(value) == false ? "" : (isNaN(totalized[r]) ? 0 : totalized[r]) + y;
        }
        table += "</tr>";
    }
    table += "<tfoot><tr>";
    for (var t1 in totalized) {
        table += "<td class='numeric'>" + totalized[t1] + "</td>";
    }
    table += "</tr></tfoot>";
    table += "</table>";
    return table;
}
function isInteger(s) {
    return (s.toString().search(/^-?[0-9]+$/) == 0);
}
function makeEncDrag(id, detalle, guia, cls) {
    var div = "<div id='" + id + "' class='" + cls + "'>";
    div += "<input type='hidden' value='" + id + "'>";
    div += "<span class='jqDrag spanHandleDrag'>" + guia + "</span>";
    div += "<span>" + detalle + "</span>";
    div += "</div>";
    return div;
}
function moverEncomiendas(viaje, bus) {

    dialog("moverEncomiendas", "Mover encomiendas");
    var fecha = $("#dateCalendarHelper").val();
    $.ajax({
        dataType: "json",
        type: "POST",
        cache: false,
        url: BaseUrl + '/recepcion/show-mover-encomienda/viaje/' + viaje + "/bus/" + bus + "/fecha/" + fecha,
        success: function (data) {
            for (var m in data.manifiestos) {
                data.manifiestos[m].content = "<ul>" + data.manifiestos[m].content + "</ul>";
            }
            var acc = makeAccordion("accListManif", data.manifiestos);
            if (data.encomiendas != 0) {
                var table = "";
                var alter = 1;
                for (var ee in data.encomiendas) {
                    var enc = data.encomiendas[ee];
                    var clsEnc = "encDrag impar";
                    if (alter % 2 == 0) {
                        clsEnc = "encDrag par";
                    }
                    table += makeEncDrag(enc.id_encomienda, enc.detalle, enc.guia, clsEnc);
                    alter++;
                }
            } else {
                table = data.selected;

            }
            var content = "<div><div class='inline'>Itinerario" + acc + "</div>";
            content += createDivMnf(1, table);
            content += createDivMnf(2, "Seleccione el manidiesto de destino  por favor ");
            $("#moverEncomiendas #jqmnContent").html(content);
            setSelectedMnf(1, data.selected);
            $("#accListManif").accordion();
            makeDragable(".encDrag");
            $('#mnf2').Droppable(
                    {
                        accept: 'encDrag',
                        activeclass: 'dropactive',
                        hoverclass: 'drophover',
                        tolerance: 'intersect',
                        onDrop: function (a) {
                            var manifiesto = $(this);
                            var idEnc = $(a).attr("id");
                            var idMnf = $("#idManif2").val();
                            if (idMnf != 0) {
                                $.ajax({
                                    dataType: "json",
                                    type: "POST",
                                    cache: false,
                                    url: BaseUrl + '/recepcion/move-encomienda/manifiesto/' + idMnf + "/enc/" + idEnc,
                                    beforeSend: function (xhr) {
                                        //                            $(idChange).block();
                                    },
                                    success: function (data2) {
                                        if (data2.error == false) {
                                            $(a).remove("slow");
                                            manifiesto.append(a);
                                        }
                                    }
                                });
                            } else {
                                alert("Por favor seleccione un manifiesto para mover las encomiendas");
                            }
                        }
                    }
            );
            $(".itineraryManifest").click(function () {
                var div = $('input[name=manifL][type=radio]:checked').val();
                var idChange = "#" + div;
                var id = $(this).attr("id");
                $.ajax({
                    dataType: "json",
                    type: "POST",
                    cache: false,
                    url: BaseUrl + '/recepcion/get-encomiendas/manifiesto/' + id,
                    beforeSend: function (xhr) {
                        $(idChange).block();
                    },
                    success: function (data1) {
                        var table = "";
                        var clsd = "encDrop";
                        if (div == "mnf1") {
                            clsd = "encDrag";
                        }
                        for (var ee in data1.encomiendas) {
                            var enc = data1.encomiendas[ee];
                            table += makeEncDrag(enc.id_encomienda, enc.detalle, enc.guia, clsd);
                        }
                        $(idChange).html(table);
                        $.log(data1.info);
                        if (div == "mnf1") {
                            makeDragable(".encDrag");
                            setSelectedMnf(1, data1.info)
                        } else {
                            setSelectedMnf(2, data1.info)
                        }
                    }
                });
            });
        }
    });

    return false;
}
function setSelectedMnf(id, man) {
    var idM = "#idManif" + id;
    var idBus = "#internoM" + id;
    var idFecha = "#fechaM" + id;
    $(idM).val(man.id_manifiesto);
    $(idBus).val(man.numero);
    $(idFecha).val(man.fecha);
}
function createDivMnf(id, content) {
    var title = "Destino";
    var selected = "CHECKED";
    if (id == 1) {
        title = "Origen";
        selected = "";
    }
    var resp = "<div class='inline'>";
    resp += "      <div class='titlemanifiesto'>";
    resp += "Manifiesto " + title + "         <input type='radio' name='manifL' value='mnf" + id + "' " + selected + "/>";
    resp += "         <div id='dataMNF" + id + "'>";
    resp += "         No    <input type='text' id='idManif" + id + "' value='0' disabled='disabled'/><br/>";
    resp += "         Bus   <input type='text' id='internoM" + id + "' value='0' disabled='disabled'/><br/>";
    resp += "         Fecha <input type='text' id='fechaM" + id + "' value='0' disabled='disabled'/><br/>";
    resp += "         </div>";
    resp += "      </div>";
    resp += "      <h3>Lista de encomiendas</h3>";
    resp += "      <div id='mnf" + id + "' class='listEncomiendasDrag'>" + content + "</div>";
    resp += "   </div>";
    return resp;
}
function makeDragable(cls) {
    $(cls).Draggable({
        revert: true,
        ghosting: true,
        zIndex: 4001,
        onStart: function (a) {
            $(this).css({
                "border": "1px solid #000"
            });
        }
    });
}
/******  CONTROLES DE EQUIPAJES ***************************/
function makeControlKeys(option, keyEvent) {
    $(document).bind('keydown', option, function (evt) {
        evt.preventDefault();
        if ($("#registerEquipajeForm").jqm().length <= 0) {
            processControlItem(keyEvent);
        }
    });
}
function processControlItem(KEY) {
    var next = $("#tableForModel").modelControl().getNext(KEY);
    $("#tableForModel").modelControl().processItem(next);
}
function showEquipajeForm() {
    //    var obj =$(this);
    //    $.fancybox.showActivity();
    butons = {
        "Aceptar": registrarEq
    }
    dialog("registerEquipajeForm", "Registro de equipajes", butons);
    var asientos = $("#tableForModel").modelControl().getSelecteds();
    //    $.log(asientos);
    $.ajax({
        type: "POST",
        cache: false,
        url: BaseUrl + '/recepcion/show-equipaje-form/asiento/' + asientos[0],
        //        data		: $(this).serializeArray(),
        success: function (data) {
            $("#registerEquipajeForm #jqmnContent").html(data);
            // controls 
            $(".numeric").numeric();
            $("input[name='ticketN']:first").blur(function () {
                asignarTicket();
            }).select();
            $("input[name='pesoK']").blur(function () {
                calcularPeso();
            });
            $("input[name='detalleTikect']").autocomplete(predefinidas, {
                minChars: 0,
                width: 310,
                matchContains: "word",
                autoFill: false,
                formatItem: function (row, i, max) {
                    return  row.detalle + "[" + row.peso + "]";
                },
                formatMatch: function (row, i, max) {
                    return row.detalle + " " + row.peso;
                },
                formatResult: function (row) {
                    return row.detalle;
                }
            });
            $("input[name='detalleTikect']").result(function (event, data, formatted) {
                if (data) {
                    $(this).parent().next().find("input").val(data.peso);
                    if (data.tipo != "peso") {
                        $(this).parent().next().next().find("select").val(data.tipo);
                    }
                }
                calcularPeso();
            });
        }
    });

    return false;
}

function makeControlable() {
    $("#tableForModel").modelControl(true);

    $("#helperFocus").trigger("focus");
    $("#helperFocus").remove();
    numPulsate = 0;
}

function loadControlable() {
    $.getScript(BaseUrl + "/scripts/libs/modelControl2.js", function () {
        makeControlKeys('Right', RIGHT);
        makeControlKeys('Left', LEFT);
        makeControlKeys('Up', UP);
        makeControlKeys('Down', DOWN);
        //            $(this).blur();
        $(document).bind('keydown', 'Return', function (evt) {
            evt.preventDefault();
            //                    $.log($("#registerEquipajeForm").jqm());
            if ($("#registerEquipajeForm").jqm().length <= 0) {
                showEquipajeForm();
            }
        });
        makeControlable();
    });

}
/******  CONTROLES DE EQUIPAJES ***************************/
function verManifiesto() {
    var obj = $(this);
    //    $.fancybox.showActivity();
    dialog("listEncomiendas", "Lista de Encomiendas");
    $.ajax({
        type: "POST",
        cache: false,
        url: BaseUrl + '/recepcion/list-encomienda/man/' + obj.attr("id"),
        data: $(this).serializeArray(),
        success: function (data) {
            $("#jqmnContent").html(data);
        }
    });

    return false;
}

function changeContent(dat) {

    var obj = $(this);

    $("#middleForm").block();
    $("#middleForm").load(BaseUrl + "/recepcion/change-form/content/" + obj.attr("name"), function (responseText, textStatus, XMLHttpRequest) {
        contentDisplay = obj.attr("name");
//        $.log(responseText);
        $('#fechaEnvio').datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true
        }, $.datepicker.regional['es']).focus();
        if (contentDisplay === RECEPCION) {
            if (hotkeys && hotkeys.triggersMap && hotkeys.triggersMap[HTMLDocument]) {
                hotkeys.triggersMap[HTMLDocument].keydown["return"] = null;
            }
            makeRecepcion();
        }
    });
}

function makeEnter2Tab(id, notInclude) {
    var selectContentId = ".ui-dialog";
    if (id) {
        selectContentId = id
    }
    $(selectContentId).find(':input').not('[type=hidden],[type=checkbox],[name=' + notInclude + ']').keypress(function (evt) {
        if (evt.keyCode == 13) {
            var fields = $(selectContentId).find(':input').not('[type=hidden],[type=checkbox],[name=' + notInclude + ']');
            var index = fields.index(this);
            if (index > -1 && (index + 1) < fields.length) {
                //                alert(fields.eq( index));
                fields.eq(index)[0].blur();
                fields.eq(index + 1).focus().select();
            } else {
                index = 0;
                fields.eq(index).blur()
                fields.eq(index + 1).blur().focus().select();
            }
            //            return false;
        }
    });
}

/***********   funciones para el control de arribo de encomiendas  ************/
function jsonP(service, params) {
    var url = service;
    $.ajax({
        dataType: "jsonp",
        data: params,
        jsonp: "callback",
        url: url,
        success: function (json) {
        },
        error: function (XHR, textStatus, errorThrown) {
            console.log("error JSONP:");
            console.log(JSON.stringify(service));
            console.log(JSON.stringify(XHR));
            console.log(JSON.stringify(textStatus));
            console.log(JSON.stringify(errorThrown));
        }

    });

}

function jsonP2(service, callback, params, success, error) {
    var paramDef = {
        "userid": "1",
        "apikey": "1234567890",
        "format": "json"
    };
    $.extend(paramDef, params);
    if (!error) {
        error = function (d, msg) {
            console.log("Error jsonP2 calling ", service, d, msg);
        }
    }
    $.jsonp({
        "url": service,
        "data": paramDef,
        "success": callback,
        "error": error
    });

}
/**********  FUNCION QUE CREA UN DIALOGO PARA CARGAR CONTENIDO *************/
function dialog(id, title, buttons, clear) {
    var contentDiv = " <div style='position: absolute; margin: 15% 0 0 35%;'>";
    contentDiv += " <div id='" + id + "' class='jqmNotice'>";
    contentDiv += "   <div class='jqmnTitle jqDrag'>";
    contentDiv += "     <h1>" + title + "</h1>";
    contentDiv += "   </div>";
    contentDiv += "   <div class='jqmnMiddleContent'>";
    contentDiv += "     <div id='jqmnContent'>Cargando ...</div>";
    contentDiv += "     <a href='#' class='jqmClose'><img src='" + BaseUrl + "/images/close_icon.png' alt='close' /></a>";
    contentDiv += "     <img src='" + BaseUrl + "/images/resize.gif' alt='resize' class='jqResize' />";
    contentDiv += "     <div class='jqmFoot'></div>";
    contentDiv += "   </div>";
    contentDiv += " </div>";
    contentDiv += "</div>";
    $("body").prepend(contentDiv);
    id = "#" + id;
    $(id).jqDrag('.jqDrag').jqResize('.jqResize').jqm({
        trigger: false,
        overlay: 50,
        onShow: function (h) {
            /* callback executed when a trigger click. Show notice */
            h.w.css('opacity', 0.92).slideDown("slow");
            h.w.animate({
                width: '100%'
            });
        },
        onHide: function (h) {
            /* callback executed on window hide. Hide notice, overlay. */
            h.w.slideUp("slow", function () {
                if (h.o)
                    h.o.remove();
            });
            h.w.remove();
            if (clear) {
                INFORMACION_VIAJE = {};
            }
        }
    });
    if (buttons) {
        var buts = "";
        for (var b in buttons) {
            buts = $("<input type='button' value='" + b + "' />");
            buts.bind("click", buttons[b]);
            $(".jqmFoot").append(buts);
        }
    }
    $(id).jqmShow();
}

function imprimirRecibo(contenido) {

    var ventimp = window.open('', '_blank');
    imagen_cab = new Image(100, 50);

    imagen_cab.src = BaseUrl + "/images/IconoQuantum.png";

    ventimp.document
            .write('<LINK href="' + BaseUrl + '/styles/print.css" rel="stylesheet" type="text/css" />');
    ventimp.document
            .write('<LINK href="' + BaseUrl + '/styles/factura.css" rel="stylesheet" type="text/css" />');
    ventimp.document
            .write('<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr><td class="linea">');
    ventimp.document.write("<img src=" + imagen_cab.src
            + " alt='' width='50' heigth='50'/>");
    ventimp.document.write("</td></tr>");
    ventimp.document.write('<tr><td>');
    ventimp.document.write(contenido);
    ventimp.document.write('</td></tr></table>');

    ventimp.document.close();
    ventimp.print();
    ventimp.close();
}
/**
 * funcion encargada de crear la url y recuperar el id de origen 
 * para realizar la llamada del servicio web
 * @returns {getServiceForArribo.Anonym$39}
 */
function getServiceForArribo() {
    var origenValue = $("#ciudadOrigen").val();
    var idOrigen = origenValue.substr(origenValue.lastIndexOf("/") + 1);
    var service = origenValue.substr(0, origenValue.lastIndexOf("/"));
    return {
        id: idOrigen,
        url: service
    };
}
/****  FUNCION QUE SE ENCARGA DE RECUPERAR LA LISTA DE MANIFIESTOS ******/
function listarEncomiendas() {
    $("#middleForm").block();
    var service = getServiceForArribo();
    var url = service.url + "/viaje/rest/";
    var params = {// ?????
        "method": "manifiestos",
        "destino": $("#CiudadDestino").val(),
        "origen": service.id,
        "userid": "1",
        "apikey": "1234567890",
        "format": "json",
        "callback": "verManifiestos",
        "fecha": $("#fechaEnvio").val()
    };
    jsonP(url, params);
}

/*******  MUESTRA LA LISTA DE MANIFIESTOS QUE LLEGAN DEL SERVICIO WEB ********/
function verManifiestos(data) {
    $.fn.block.close();
    var resp = data.response;
    //    console.log(resp);
    if (resp.status == "success") {
        var table = $("#listManifiestos");
        if (resp.size > 0) {
            table.find("tbody tr").remove();
            var cls = "par";
            var row = 0;
            for (var m in resp.manifiestos) {
                var man = resp.manifiestos[m];
                row++;
                cls = row % 2 == 0 ? "par" : "impar";
                var tr = "<tr>";
                tr += "<td class='" + cls + "'>" + man.encargado + "</td>";
                tr += "<td class='" + cls + "'>" + man.fecha + "</td>";
                tr += "<td class='" + cls + "'>" + man.interno + "</td>";
                tr += "<td class='" + cls + "'>" + man.chofer + "</td>";
                tr += "<td class='" + cls + "'>" + man.destino + "</td>";
                tr += "<td class='" + cls + "'>" + man.nroEncomiendas + "</td>";
                tr += "<td class='" + cls + "'><a href='#' onclick='mostrarEncomiendas(\"" + man.id + "\")' class='accionManifiesto' title='Listar encomiendas'></a></td>";
                table.find("tbody").append(tr);

            }
        } else {
            table.find("tbody tr td:first").html("No se enviaron Manifiestos en esta fecha");
        }
    }
}

/**  funcion auxiliar para crear una interfaz tipo acordion en la lista de encomiendas  **/
function makeCollapsible(cssActive, cssCollapsed, contentHide) {
    var selector = "." + cssActive + ", ." + cssCollapsed;
    $("html").css("overflow-y", "scroll");
    $(selector).each(function () {
        $(this).click(function () {
            if ($(this).hasClass(cssActive)) {
                $(this).removeClass(cssActive).addClass(cssCollapsed);
                $(this).parent().removeClass("headInactive");
                $(this).parent().next().slideUp();
            } else {
                $(this).removeClass(cssCollapsed).addClass(cssActive);
                $(this).parent().addClass("headInactive");
                $(this).parent().next().slideDown();
            }
        });
    });
}
/**
 *  Manda a recuperar la informacion de la encomienda para mostrar
 *
 *@param id identificador de la encomienda
 *
 *
 **/
function verDetalle(id) {

    //    dialog("viewDetailEncomienda","Detalle de la encomienda");
    showDialog("viewDetailEncomienda", {
        title: "Detalle de la encomienda",
        content: "Cargando..."
    });
    var service = $("#ciudadOrigen").val() + "/encomiendas/rest/";
    var callback = "resultViewDetail";
    var params = {
        "method": "getDetail",
        "callback": "resultViewDetail",
        "id": id
    };
    //    jsonP(service,params);
    jsonP2(service, callback, params);
}

function resultViewDetail(json) {
    var resp = json.response;
    var detail = "<table class='listManifiestos'>";
    if (resp.status == "success" && resp.enc != "") {
        detail += "<tr><th>C. origen</th><td>" + resp.enc.cOrigen + "</td><th>C. destino</th><td>" + resp.enc.cDestino + "</td><tr/>";
        detail += "<tr><th>S. origen</th><td>" + resp.enc.sOrigen + "</td><th>S. destino</th><td>" + resp.enc.sDestino + "</td><tr/>";
        detail += "<tr><th>Tipo encomienda</th><td colspan='3'>" + resp.enc.tipo + "</tD><tr/>";
        detail += "<tr><th>N.I.T.</th><td colspan='3'>" + resp.enc.nit + "</td><tr/>";
        detail += "<tr><th>Nombre</th><td colspan='3'>" + resp.enc.nombre + "</td><tr/>";
        detail += "<tr><th>Remitente</th><td>" + resp.enc.remitente + "</td><td>Telf</td><td>" + resp.enc.telfR + "</td><tr/>";
        detail += "<tr><th>Destinatario</th><td>" + resp.enc.destinatario + "</td><td>Telf</td><td>" + resp.enc.telfD + "</td><tr/>";
        detail += "<tr><th colspan='4'>Detalle</th><tr/>";
        detail += "<tr><th>Cant</th><th>Detalle</th><th>Peso</th><th>Precio</th><tr/>";
        for (var pj in resp.enc.items) {
            var item = resp.enc.items[pj];
            detail += "<tr><td>" + item.cant + "</td><td>" + item.detalle + "</td><td>" + item.peso + "</td><td>" + item.precio + "</td><tr/>";
        }
    } else {
        if (resp.enc == "") {
            detail = "<tr><td colspan='6'><div class='ui-state-highlight'>No se puedo recuperar la encomienda</div></td></tr>";
        } else {
            detail = "<tr><td colspan='6'><div class='ui-state-error'>" + resp.error + "</div></td></tr>";
        }
    }
    detail += "</table>";
    //    $.log($(".jqmnMiddleContent").html());
    $("#viewDetailEncomienda").html(detail);
}

function verMovimiento(id) {
    //    $("#middleForm").block();
    //    dialog("viewMovementEncomienda","Seguimiento de la encomienda");
    showDialog("viewMovementEncomienda", {
        title: "Seguimiento de la encomienda",
        content: "Cargando..."
    });
    var service = $("#ciudadOrigen").val() + "/encomiendas/rest/";
    var callback = "resultViewMovement";
    var params = {
        "method": "getMonitoring",
        "callback": "resultViewMovement",
        "id": id
    };
    //    jsonP(service,params);
    jsonP2(service, callback, params);
}

function showMovetoCancel(id) {
    showDialog("viewMovetoCancel", {
        title: "Mover la encomienda para cancelar",
        content: "Cargando...",
        url: "/recepcion/show-move-to-cancel/id/" + id + "/ciudadOrigen/" + $("#ciudadOrigen option:selected").attr("id") + "/userCiudadId/" + userCiudadID,
        type: "POST",
        onComplet: function (data) {


        },
        buttons: [{
                id: "aceptarMoveToCancel",
                text: "Aceptar",
                click: function (evt) {
                    if ($("#activeForCancel").val() == "") {
                        $(this).dialog("close");
                        return;
                    }

                    $.ajax({
                        type: "POST",
                        cache: false,
                        dataType: 'json',
                        url: BaseUrl + '/recepcion/save-move-to-cancel/id/' + id,
                        success: function (data) {
                            alert(data.message);
                            if (data.error == false) {
                                removeDialog("#viewMovetoCancel");
                            }
                        }
                    });
                }
            }, {
                id: "cancelMoveToCancel",
                text: "Cerrar",
                click: function () {
                    $(this).dialog("close");
                }
            }
        ]

    });
}

function resultViewMovement(json) {
    var resp = json.response;
    if (resp.status == "success") {
        var table = $("<table class='listManifiestos'></table>");
        var head = $("<tr>");
        head.append("<th>Fecha</th>");
        head.append("<th>Hora</th>");
        head.append("<th>Usuario</th>");
        head.append("<th>Sucursal</th>");
        head.append("<th>Bus</th>");
        head.append("<th>Obs</th>");
        table.append(head);
        if (resp.size > 0) {
            for (var m in resp.encomiendas) {
                var enc = resp.encomiendas[m];
                var content = "<tr>";
                content += "<td class='nowrap'>" + enc.f + "</td>";
                content += "<td class='nowrap'>" + enc.h + "</td>";
                content += "<td>" + enc.u + "</td>";
                content += "<td>" + enc.s + "</td>";
                content += "<td>" + enc.b + "</td>";
                content += "<td>" + enc.o + "</td>";
                content += "</tr>";
                table.append(content);
            }

        } else {
            table.html("<tr><td colspan='6'><div class='ui-state-highlight'>No se pudo recuperar la informacion de la encomienda</div></td></tr>");
        }
    }
    $("#viewMovementEncomienda").html(table);
}

function verFactura(id) {
    //    dialog("viewFactura","Detalle de la factura");
    showDialog("viewFactura", {
        title: "Detalle de la factura",
        content: "Cargando..."
    });
    //    $("#middleForm").block();
    var service = $("#ciudadOrigen").val() + "/encomiendas/rest/";
    var callback = "resultViewFactura";
    var params = {
        "method": "getFactura",
        "callback": "resultViewFactura",
        "id": id
    };
    //    jsonP(service,params);
    jsonP2(service, callback, params);
}
function resultViewFactura(json) {
    var resp = json.response;
    var detail = "<table class='listManifiestos'>";
    if (resp.status == "success" && resp.fac != "") {
        detail += "<tr><th>Fecha</th><td>" + resp.fac.fecha + "</th><tr/>";
        detail += "<tr><th>Hora</th><td>" + resp.fac.hora + "</th><tr/>";
        detail += "<tr><th>Usuario</th><td>" + resp.fac.usuario + "</th><tr/>";
        detail += "<tr><th>Numero</th><td>" + resp.fac.numero + "</th><tr/>";
        detail += "<tr><th>Nombre</th><td>" + resp.fac.nombre + "</th><tr/>";
        detail += "<tr><th>N.I.T.</th><td>" + resp.fac.nit + "</th><tr/>";
        detail += "<tr><th>Importe</th><td>" + resp.fac.importe + "</th><tr/>";
        detail += "<tr><th>Autorizacion</th><td>" + resp.fac.autorizacion + "</th><tr/>";
        detail += "<tr><th>Codigo control</th><td>" + resp.fac.control + "</th><tr/>";
        detail += "<tr><th>Fecha limite</th><td>" + resp.fac.limite + "</th><tr/>";
    } else {
        if (resp.fac.toString() == "") {
            detail = "<tr><td colspan='6'><div class='ui-state-highlight'>La encomienda no tiene factura</div></td></tr>";
        } else {
            detail = "<tr><td colspan='6'><div class='ui-state-error'>" + resp.error + "</div></td></tr>";
        }
    }
    detail += "</table>";
    //    $.log($(".jqmnMiddleContent").html());
    $("#viewFactura").html(detail);
}

/***********   funciones para el control de ARRIBO de encomiendas  ************/


function reloadItinerary(date) {
    $.ajax({
        type: "POST",
        cache: false,
        url: BaseUrl + '/recepcion/list-itinerary/date/' + date,
        success: function (data) {
            $("#ListItineraryContent").html(data);
            makeItinerayList();
        }
    });
}
function reloadManifiesto(date) {
    $.ajax({
        type: "POST",
        cache: false,
        url: BaseUrl + '/recepcion/list-manifest/date/' + date,
        success: function (data) {
            //            alert(data);
            //            $.log($("#contentManiestList").html());
            $("#contentManiestList").html(data);
            $(".verManifiesto").click(verManifiesto);
        }
    });
}


function asignarTicket() {
    numT = $("input[name='ticketN']:first").val();
    $("input[name='ticketN']:not(:first)").each(function (pos, obj) {
        numT++;
        $(obj).val(numT);
    });
}

function calcularPeso() {
    var total = 0;
    var nonEquipaje = 0;
    $("input[name='pesoK']").each(function () {
        var peso = parseInt($(this).val());
        var tipo = $(this).parent().next().find("select").val();
        if (tipo != "peso") {
            nonEquipaje += peso
        } else {
            total += peso;
        }
    });
    var max = parseInt($("#pesoMax").val());
    $("#total").text(total);
    var precio = parseInt($("#precio_X_kilo").val());
    var excedente = 0;
    if ((max < total) || nonEquipaje > 0) {
        if (max < total) {
            excedente = total - max;
            $("#total").css({
                'background-color': "#DF0DF0"
            });
        }
        var pagoExedente = (precio * excedente) + nonEquipaje;
        $("#datosFactura").show(100);
        $("#precioExcedente").val(pagoExedente);
    } else {
        $("#precioExcedente").val(0);
        $("#datosFactura").hide(100);
    }
}

function registrarEq() {
    var data = "{";
    $("#equipajesList tbody tr").each(function (pos) {
        data += "'" + pos + "':{";
        $(this).children("td").each(function (pos, obj) {
            var child = $(this).children("input");
            if (pos == 4) {
                child = $(this).children("*[name='tipoEquipaje']");
            }
            data += "'" + child.attr("name") + "':'" + child.val() + "',";
        });
        data = data.substring(0, data.length - 1);
        data += "},";
    });
    data = data.substring(0, data.length - 1);
    data += "}";
    var nombreF = $("#nombreFactura").val();
    var nitF = $("#nitFactura").val();
    $.ajax({
        type: "POST",
        url: BaseUrl + "/recepcion/save-equipaje/nombre/" + nombreF + "/nit/" + nitF,
        data: "&datos=" + data + "&viaje=" + $("#selectedViaje").val() + "&destino=" + $("#ciudadDest").val() + "&chofer=" + $("#choferViaje").val(),
        dataType: 'json',
        success: function (msg) {
            if (msg.error == true) {
                alert(msg.mensaje);
            } else {
                if (msg.printer.sucursal) {
                    var c = msg.printer.configuracion;
                    var printer = document.printerSystem;
                    var s = msg.printer.sucursal;
                    var items = msg.printer.items;
                    printer.setConfiguracion(c.empresa, c.nitE);
                    printer.setSucursal(s.telf, s.impresor, s.dir, s.dir2, s.ciudad, s.numFac, s.autorizacion, s.fecha, s.nombFact, s.nitFact, s.total, s.literal, s.control, s.fechaLimite, s.usuario, s.destino, s.fechaViaje, s.horaViaje, s.salida, s.carril, s.modelo);
                    for (var j in items) {
                        var a = items[j];
                        printer.addAsiento(a.cantidad, a.detalle, a.precio);
                    }
                    printer.imprimir();
                }
                $("#registerEquipajeForm").jqmHide();

                selectViaje($("#selectedViaje").val(), $("#selectedBus").val(), $("#selectedHora").val(), $("#selectedDestino").val(), $("#selectedIdBus").val());
            }
        }
    });
}
function addAsiento(obj) {
    var tr = $(obj).parent().parent();
    //    var table = tr.parent();
    tr.clone(true).insertBefore(tr);
}
function deleteEquipaje(idasiento) {
    var data = "{";
    if (idasiento == "todos") {
        $("#equipajesList tbody tr").each(function (pos) {
            data += "'" + pos + "':{";
            $(this).children("td").each(function (pos, obj) {
                var child = $(this).children("input");
                data += "'" + child.attr("name") + "':'" + child.val() + "',";
            });
            data = data.substring(0, data.length - 1);
            data += "},";
        });
        data = data.substring(0, data.length - 1);
    } else {
        data += "'0':{'idEquipaje':'" + idasiento + "'}";
    }
    data += "}";
    $.ajax({
        type: "POST",
        url: BaseUrl + "/recepcion/delete-equipaje/",
        data: "&datos=" + data,
        dataType: 'json',
        success: function (msg) {
            if (msg.error == true) {
                alert(msg.mensaje);
            } else {
                quitarDialogo("#info");
                selectViaje("mismo");
            }
        }
    });
}



/****************************************************************************/
/*       FUNCION ENCARGADA DE BUSCAR UNA ENCOMIENDA SEGUN UN TIPO 
 *       Y CARGAR LA LISTA EN UNA TABLA                                     */
/****************************************************************************/
function recibirLlistaEncomiendas(estado) {
    $("#middleForm").block();


    var service = $("#ciudadOrigen").val() + "/encomiendas/finder/";
    var callback = "resultFindEncomiendas";
    var params = {
        "method": "findEncomiendas2",
        "callback": "resultFindEncomiendas",
        "fecha": $("#fechaEnvio").val(),
        "remitente": $("#remitente").val(),
        "destinatario": $("#destinatario").val(),
        "guia": $("#guia").val(),
        "tipo": $("#tipoEncomienda").val(),
        "origen": $("#ciudadOrigen option:selected").attr("id"),
        "estado": estado
    };
    if (estado == "ARRIBO") {
        params['destino'] = userCiudadID;
    }

    jsonP2(service, callback, params);
}
function resultFindEncomiendas(json) {
    var resp = json.response;
    $.fn.block.close();
    if (resp.status == "success") {
        var tableBody = $("#listEncEntregar tbody");
        tableBody.html("");
        if (resp.size > 0) {

            console.log(events);
            for (var m in resp.encomiendas) {
                var enc = resp.encomiendas[m];
                var cls = enc.estado.toLowerCase();
                cls = cls.replace(" ", "");
                var content = "<tr id='" + enc.id + "' class='" + cls + "'>";
                content += "<td class='nowrap'>" + enc.fecha + "</td>";
                content += "<td class='nowrap'>" + enc.guia + "</td>";
                content += "<td>" + enc.remitente + "</td>";
                content += "<td>" + enc.destinatario + "</td>";
                content += "<td>" + enc.detalle + "</td>";
                content += "<td>" + enc.tipo + "</td>";
                var b = /'/g;
                var dest = enc.destinatario.replace(b, "");
                if (contentDisplay == ENTREGA) {
                    var event = "showFormEntrega(\"" + enc.id + "\",\"" + dest + "\",\"" + enc.tipo + "\",\"" + enc.monto + "\",\"" + enc.estado + "\");return false;";
                    content += "<td><a href='#' onclick='" + event + "' class='event'>";
                    content += "<img src='" + BaseUrl + "/images/entrega.png' alt='entregar' border='0' title='Entregar encomienda'/>";
                    content += "</td>";

                } else if (contentDisplay == BUSQUEDA) {
                    content += "<td class='width105'>";
                    for (var pos in events.busqueda.actions) {
                        var action = events.busqueda.actions[pos];
                        var event = action.a + "(\"" + enc.id + "\",\"" + dest + "\",\"" + enc.tipo + "\",\"" + enc.monto + "\",\"" + enc.estado + "\");return false;";
                        content += "<a href='#' onclick='" + event + "' class='event'>";
                        content += " <img src='" + BaseUrl + "/images/" + action.i + "' alt='" + action.t + "' border='0' title='" + action.t + "'/>";
                        content += "</a>";
                    }
                    content += "</td>";

                }
                content += "</tr>";
                tableBody.append(content);
            }
        } else {
            tableBody.html("<tr><td colspan='6'><div class='ui-state-highlight'>No se encontraron encomiendas para entregar</div></td></tr>");
        }
    } else {
        alert("no se ha podido recuperar la informacion de encomiendas debido a un error en el servidor");
    }
}



function changePasword() {
    //$("#buttonchange").disable();
    var current = $("#current").val();
    var tochange = $("#new").val();
    var confirm = $("#confirm").val();
    $("#errorHelper span").text("");
    if ($("#errorHelper").is(":visible")) {
        $("#errorHelper").addClass("ui-helper-hidden");
    }
    if (current === "") {
        $("#errorHelper").removeClass("ui-helper-hidden");
        $("#errorHelper span").text("El valor Actual no puede ser vacio");
        return;
    }
    if (tochange === "") {
        $("#errorHelper").removeClass("ui-helper-hidden");
        $("#errorHelper span").text("La nueva CLAVE no puede vacio");
        return;
    }
    if (confirm === "") {
        $("#errorHelper").removeClass("ui-helper-hidden").removeClass("ui-state-error");
        $("#errorHelper span").text("La confirmacion de la nueva CLAVE no puede vacio");
        return;
    }
    $.ajax({
        type: 'POST',
        url: BaseUrl + "/recepcion/update-password/",
        data: {'current': current, "new": tochange, 'confirm': confirm},
        dataType: 'json',
        success: function (msg) {
            alert(msg)
            if (msg.error == true) {
                $("#errorHelper").removeClass("ui-helper-hidden");
            } else {
                $("#errorHelper").removeClass("ui-helper-hidden");
                $("#errorHelper").addClass("ui-state-highlight");
            }
            $("#errorHelper span").text(msg.info);
        }
    });
}



function addFacturaManual(id, receptor, tipo, monto, estado) {
    if (tipo != "POR PAGAR") {
        alert("Esta opcion esta habilitada solo para Encomiendas por pagar");
        return;
    }

    var option = true;
    if (estado == "ENTREGADO") {
        option = confirm("La encomienda puede tener una factura computarizada asociada \n desea continuar?");
    }
    if (option == false) {
        return;
    }
    showDialog("addFacturaManual", {
        url: '/recepcion/show-add-factura-manual/id/' + id + '/receptor/' + receptor + '/monto/' + monto,
        title: "Add Factura Manual",
        type: "POST",
        buttons: {
            "Aceptar": function (evt) {
                var obj = evt.currentTarget;
                saveAddManualPP(id);
            },
            "Cerrar": function () {
                $(this).dialog("close");
            }
        },
        onComplet: function () {
            $("#nitFactura").select();
        }
    });
}

function saveAddManualPP(id) {
    $.ajax({
        type: 'POST',
        url: BaseUrl + "/recepcion/add-factura-manual-encomienda/",
        data: $("#encomiendasForm").serialize(),
        dataType: 'json',
        success: function (msg) {
            alert(msg)
            if (msg.error == true) {
                $("#errorHelper").removeClass("ui-helper-hidden");
            } else {
                $("#errorHelper").removeClass("ui-helper-hidden");
                $("#errorHelper").addClass("ui-state-highlight");
            }
            $("#errorHelper span").text(msg.info);
        }
    });
}



/***   UTILITY FUNCTION ***/
/**
 * 
 * @param {type} obj1
 * @param {type} obj2
 * @returns {unresolved}
 */
function MergeRecursive(obj1, obj2) {
    obj1 = toLowerIndex(obj1);
    obj2 = toLowerIndex(obj2);
    for (var p in obj2) {
        try {
            // Property in destination object set; update its value.
            if (obj2[p].constructor == Object) {
                obj1[p] = MergeRecursive(obj1[p], obj2[p]);
            } else {
                if (obj2[p] instanceof Array) {
                    obj1[p] = obj1[p].concat(obj2[p]);
                } else {
                    obj1[p] = obj2[p];
                }
            }
        } catch (e) {
            // Property in destination object not set; create it and set its value.
            obj1[p] = obj2[p];
        }
    }

    return obj1;
}

function toLowerIndex(object) {
    var resp = {};
    for (var index in object) {
        var lowerIndex = index.toString().toLowerCase();
        resp[lowerIndex] = object[index];
    }
    return resp;
}

//************* FUNCIONES PARA CLIENTES***************
function buscarClientes() {
    $("#middleForm").block();
    $.ajax({
        cache: false,
        url: BaseUrl + '/recepcion/search-clients-by',
        data: getClientData("search"),
        dataType: "json",
        success: function (msg) {
            drawClientes(msg);

        }
    });
}

function getClientData(prefix) {
    return {'clientId': $("#clientId").val(), 'nombre': $("#" + prefix + "-clientName").val(), "nit": $("#" + prefix + "-clientNIT").val(), "tipo": $("#" + prefix + "-clientType").val()};
}
function drawClientes(clients) {
    $("#clientListBody").html("");
    if (clients.length > 0) {
        clients.forEach(function (client) {
            var row = $("<tr></tr>");
            var nombre = $("<td>" + client.nombre + "</td>");
            var nit = $("<td>" + client.nit + "</td>");
            var tipo = $("<td>" + client.tipo + "</td>");
            var deuda = $("<td>" + client.deuda + "</td>");
            var actions = "<td>";
            for (var pos in events.cliente.actions) {
                var action = events.cliente.actions[pos];
                var event = action.a + "(" + JSON.stringify(client) + ");return false;";
                actions += "<a href='#' onclick='" + event + "' class='event'>";
                actions += " <img src='" + BaseUrl + "/images/" + action.i + "' alt='" + action.t + "' border='0' title='" + action.t + "'/>";
                actions += "</a>";
            }
            actions += "</td>";
            row.append(nombre, nit, tipo, deuda, $(actions));
            $("#clientListBody").append(row);
        });
    } else {
        $("#clientListBody").append('<tr><td colspan="5">Presione Buscar para listar los clientes</td></tr>');
    }
}
function showClientDialog(client) {
    var encomiendasLink = "";
    if (client.id !== "") {
        encomiendasLink = "<hr/>";
        encomiendasLink += "<a href='#' onclick='loadEncomiendas()' class='event'>Ver encomiendas sin pagar</a>";
        encomiendasLink += "<div id='debtPackageList'></div>";
    }
    showDialog("Cliente", {
        title: "Registrar/Actualizar Clientes",
        content: "<form> <input type='hidden' value='" + client.id + "' id='clientId' name='clientId'/>"
                + addInputFormRow({name: "form-clientName", label: "Nombre/Razon Social", value: client.nombre})
                + addInputFormRow({name: "form-clientNIT", label: "NIT", value: client.nit, class: "numeric"})
                + addInputFormRow({name: "form-clientDebt", label: "Deuda", value: client.deuda, class: "numeric"})
                + addSelectFormRow({name: "form-clientType", label: "Tipo", value: client.tipo, options: [{key: clientType.corporate, value: clientType.corporate}, {key: clientType.normal, value: clientType.normal}]})
                + "</form>" + encomiendasLink,
        buttons: {
            "Aceptar": function (evt) {
                var obj = evt.currentTarget;
                console.log(obj);
                var error = false;
                if ($("#form-clientName").val() === "") {
                    $("#form-clientName").parent("div").addClass("error");
                    appendWarning($("#form-clientName").parent("div"));
                    error = true;
                }

                if ($("#form-clientNIT").val() === "") {
                    $("#form-clientNIT").parent("div").addClass("error");
                    appendWarning($("#form-clientNIT").parent("div"));
                    error = true;
                }

                if ($("#form-clientType").val() === "all") {
                    $("#form-clientType").parent("div").addClass("error");
                    appendWarning($("#form-clientType").parent("div"));
                    error = true;
                }
                if (error === false) {
                    registerClient($(this));
                }
            },
            "Cerrar": function () {
                $(this).dialog("close");
            }
        }
    });
}
function registerClient(dialogObject) {
    cleanWarnings();

    $.ajax({
        cache: false,
        url: BaseUrl + '/recepcion/save-client',
        data: getClientData("form"),
        dataType: "json",
        success: function (response) {
            if (response.error === false) {
                dialogObject.dialog("close");
                $("#search-clientNIT").val($("#form-clientNIT").val());
                buscarClientes();
            } else {
                alert(response.message);
            }

        }
    });
}

function loadEncomiendas(nit) {
    $.ajax({
        cache: false,
        url: BaseUrl + '/client/client-debt',
        data: nit,
        dataType: "json",
        success: function (response) {
            if (response.error === false) {
                var encomiendas = "";
                $("#debtPackageList").html(encomiendas);
            } else {
                $("#debtPackageList").html("No se puedo cargar datos");
            }

        }
    });
}

function appendWarning(id) {
    if ($(id).length === 0) {
        $(id).append("<span id='warning' title='requerido'></span>");
    }
}

function cleanWarnings() {
    if ($('span#warning').length >= 1) {
        $("span#warning").parent("label").removeClass("error");
        $("span#warning").remove();
    }
}

function addInputFormRow(row) {
    var data = '';
    data += '   <div class="row">';
    data += '        <label for="' + row.name + '" class="labelForm">' + row.label + '</label>';
    data += '        <input type="text" name="' + row.name + '" id="' + row.name + '" value="' + row.value + '" class="' + row.class + '"/>';
    data += '    </div>';
    return data;
}
function addSelectFormRow(row) {
    var data = '';
    data += '   <div class="row">';
    data += '        <label for="' + row.name + '" class="labelForm">' + row.label + '</label>';
    data += '        <select name="' + row.name + '" id="' + row.name + '">';
    row.options.forEach(function (option) {
        var selected = "";
        if (option.key === row.value) {
            selected = "selected";
        }
        data += '<option value="' + option.key + '" ' + selected + '>' + option.value + '</option>';
    });
    data += '        </select>';
    data += '    </div>';
    return data;
}

function cancelCorporateDebt(client) {
    showDialog("ClientDebt", {
        url: '/client/list-packages/client/' + client.id,
        title: "Registra cobro por Encomeindas Coorporativas",
        content: "<h1>cargando......</h1>",
        buttons: {
            "Facturar": function (evt) {
                var selected = [];
                $('#listPackages input:checked').each(function () {
                    selected.push($(this).attr('value'));
                });
                if (selected.length === 0) {
                    alert("Por favor seleccione alguna encomienda para facturar");
                } else {
                    $.ajax({
                        cache: false,
                        url: BaseUrl + '/client/pay-packages',
                        data: {clientID: client.id, packagesToPay: JSON.stringify(selected)},
                        type: "POST",
                        dataType: "json",
                        success: function (msg) {
                            if (msg.error === false) {
                                /*
                                var printer = window.printer ? window.printer : document.printerSystem;
                                printer.setDocument("factura");
                                var c = msg.cabecera;
                                var emp = msg.empresa;
                                var e = msg.encomienda;                                
                                printer.setEncomienda(e.destinatario, e.destino,
                                        e.detalle, e.guia, e.origen, e.remitente, e.total,
                                        e.tipo, e.telefonoDestinatario, e.declarado,
                                        e.observacion, e.ciudadDestino);
                                printer.setCabecera(c.numeroSuc, c.autoimpresor, c.direccion, c.direccion2, c.ciudad, c.telefono, c.usuario, emp.title, emp.nombre, emp.nit);
                                printer.setInfoSucursal(c.municipio, c.leyendaActividad, c.tipoFactura, c.ciudadCapital, c.ciudad2, c.leyendaSucursal);
                                for (var jki in msg.items) {
                                    var itmi = msg.items[jki];
                                    printer.addItem(itmi.cantidad, itmi.detalle, itmi.peso, itmi.total);
                                }
                                if (msg.factura) {
                                    var f = msg.factura;
                                    printer.setFactura(f.fecha, f.hora, f.nombre, f.nit, f.numerofactura, f.autorizacion, f.codigoControl, f.fechaLimite, f.total, f.totalLiteral);
                                }

                                printer.imprimir();
                                printer.setDocument("guia");
                                printer.imprimir();

                                printer.clean();
                                removeDialog("#ClientDebt");
                                */
                                var c = msg.cabecera;
                                var emp = msg.empresa;
                                var e = msg.encomienda;
                                var itemsP = [];
                                console.log(JSON.stringify(msg.items));
                                for (var jki in msg.items) {
                                    var itmi = msg.items[jki];
                                    // printer.addItem(itmi.cantidad, itmi.detalle, itmi.peso, itmi.total);
                                    itemsP.push({
                                        'cantidad': itmi.cantidad,
                                        'detalle': itmi.detalle,
                                        'total': itmi.total,
                                        'peso': itmi.peso
                                    });
                                }

                                var fe = new Date();
                                var dataPrint = {
                                    'empresa': emp.nombre,
                                    'cabecera': {
                                        'numeroSuc': c.numeroSuc,
                                        'telefono': c.telefono,
                                        'direccion': c.direccion,
                                        'direccion2': c.direccion2,
                                        'ciudad': c.ciudad,
                                        'usuario': c.usuario
                                    },
                                    'tipo': emp.title,
                                    'fechaActual': fe.getDate() + "-" + fe.getMonth() + "-" + fe.getFullYear(),
                                    'encomienda': {
                                        'origen': e.origen,
                                        'destino': e.destino,
                                        'guia': e.guia,
                                        'remitente': e.remitente,
                                        'destinatario': e.destinatario,
                                        'telefonoRemitente': e.telefonoRemitente
                                    },
                                    'infoEntrega': {
                                        'receptor': e.remitente,
                                        'carnet': msg.factura.nit
                                    },
                                    'items': itemsP,
                                    'observacion': e.observacion
                                };

                                if(printFac)
                                    loadImprEntrega(dataPrint);
                                else

                                document.location.reload();
                            } else {
                                console.log(msg);
                                alert("No se puedo facturar");
                            }

                        }
                    });
                }
            },
            "Cerrar": function () {
                $(this).dialog("close");
            }
        }
    });
}