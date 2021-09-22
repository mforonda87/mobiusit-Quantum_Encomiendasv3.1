/***********   funciones para el control de ENTREGA de encomiendas  ************/

/**
 * Muestra el formulario de entrega de encomiendas
 * @param {String} id userid in hash
 * @param {string} destinatario nombre de la persona que recibe la encomienda
 * @param {string} tipo tipo de la encomienda
 * @param {integer} monto precio de la encomienda
 * @param {string} estado estado actual de la encomienda
 */
function showFormEntrega(id, destinatario, tipo, monto, estado) {
    console.log(estado, contentDisplay, BUSQUEDA);
    if (estado === "ENTREGADO" || contentDisplay == BUSQUEDA) {
        var alertmsg = "La encomienda ya se entrego";
        if (contentDisplay == BUSQUEDA) {
            alertmsg = "Se ha desabilitado la opcion de netrega de encomiendas en busqueda \n por favor seleccione la pestaña de entrega para realizar esta operacion";
        }
        alert(alertmsg);
        return;
    }

    if (estado === "ENVIO") {
        alert("La encomienda aun no llega destino asi que no se la puede entregar");
        return;
    }

    $("#dialogEntrega").dialog({
        width: 380,
        closeOnEscape: true,
        closeText: 'Cerrar',
        draggable: true,
        modal: true,
        title: "Encomienda a entregar",
        position: 'center',
        resizable: true,
        open: function(event, ui) {
            $(this).find("#facturado").hide();
            $("#tipoFactura").change(function() {
                loadDosificacion();
            });
            $("#dosificacionControl").hide();
            $("#numerofacturaControl").hide();
            if (tipo == "POR PAGAR") {
                $(this).find("#facturado").show();
                $("#nombreFactura").val(destinatario);
                $("#nitFactura").numeric();
                $("#totalFactura").val(monto);
            }
            $("#carnet").numeric();
            $("#consignatario").val(destinatario);
        },
        close: function() {
            $(this).find("#facturado").hide();
            $('#entregaForm').get(0).reset();
            $("#dialogEntrega").dialog("close");
            $("#dialogEntrega").dialog("destroy");
        },
        buttons: {
            "Aceptar": registerEntrega,
            "Cancelar": function() {
                $('#entregaForm').get(0).reset();
                $(this).find("#facturado").hide();
                $("#dialogEntrega").dialog("close");
                $("#dialogEntrega").dialog("destroy");
            }
        }
    });
    $("#ecmedSlce").val(id);
    $("#tipoEncomienda").val(tipo);
}


function loadDosificacion() {
    if ($("#tipoFactura").val() == "computarizada") {
        $("#dosificacionControl").hide();
        $("#numerofacturaControl").hide();
    } else {
        $("#dosificacionControl").show();
        $("#numerofacturaControl").show();
        $("#dosificacion").html("<option>Cargando...</option>");
        $.ajax({
            type: "POST",
            cache: false,
            url: BaseUrl + '/recepcion/get-dosificacion-manual/',
            dataType: 'json',
            success: function(data) {
                $("#dosificacion").html(data.options);
            }
        });

    }
}

function registerEntrega() {
    $("#dialogEntrega").block();
    var service = $("#ciudadOrigen").val() + "/encomiendas/rest/";
    var formValues = $('#entregaForm').serializeObject();//$("#entregaForm").serializeArray();

    var params = {
        "method": "saveEntrega",
        "userid": "1",
        "apikey": "1234567890",
        "format": "json",
        "callback": "resultEntrega",
        "entrego": $("#f87bb64fe05086c310ccc55799c26d7123287879").val(),
        "suc": $("#fe5b095e2ffd3d49c668bb29d865e0e499826d45").val()
    };
    params = $.extend(params, formValues);
    // alert(service);
    jsonP2(service, "resultEntrega", params);
}
function resultEntrega(json) {
    $.fn.block.close();
    if (json.response.error == false) {
        // var printer = window.printer ? window.printer : document.printerSystem;
        // printer.typeDocument = "factura";
        var result = json.response.info;
        if ($("#facturado").is(":visible")) {
            var encomiendaData = {
                encomienda: result.encomienda,
                items: result.items,
                cabecera: result.cabecera
            };
            encomiendaData.encomienda.nombreFactura = $("#nombreFactura").val();
            encomiendaData.encomienda.nit = $("#nitFactura").val();
            encomiendaData.encomienda.total = $("#totalFactura").val();
            encomiendaData.encomienda.tipoFactura = $("#tipoFactura").val();
            encomiendaData.encomienda.dosificacion = $("#dosificacion").val();
            encomiendaData.encomienda.numeroFactura = $("#numeroFactura").val();

            $.ajax({
                type: "POST",
                cache: false,
                url: BaseUrl + '/recepcion/save-entrega-porpagar/',
                data: encomiendaData,
                dataType: 'json',
                async: false,
                success: function(data) {

                    console.log(JSON.stringify(data));
                    console.log(JSON.stringify(encomiendaData));
                    if (data.error == false) {
                        deleteRow();
                        if (data.info.tipoFactura == "Automatica") {
                            try {
                                // verificamos is la impresion es desde el applet con con nuetra implementacion en javafx

                                // if (window.printer) {
                                //     printer.typeDocument = "facturaEncomienda";
                                //     printer.setJson(JSON.stringify(data.info.empresa), "empresa");
                                //     printer.setJson(JSON.stringify(data.info.encomienda), "encomienda");
                                //     printer.setJson(JSON.stringify(data.info.factura), "factura");
                                //     printer.setJson(JSON.stringify(data.info.cabecera), "sucursal");
                                //     for (var jk in data.info.items) {
                                //         var itm = data.info.items[jk];
                                //         printer.setJson(JSON.stringify(itm), "item");
                                //     }
                                //
                                // } else {
                                //
                                //     var info = json.response.info;
                                //     var c = info.cabecera;
                                //     var e = info.encomienda;
                                //
                                //     printer.setEncomienda(e.destinatario, e.destino, e.detalle, e.guia, e.origen, e.remitente, e.total, c.tipo, e.telefonoRemitente, e.declarado, e.observacion, e.ciudadDestino);
                                //     printer.addInfoEntrega(e.receptor, e.carnet);
                                //     var fact = data.info.factura;
                                //
                                //     //                        console.log(data.info);
                                //     printer.setFactura(fact.fecha, fact.hora, fact.nombre, fact.nit, fact.numerofactura, fact.autorizacion, fact.codigoControl, fact.fechaLimite, fact.total, fact.totalLiteral);
                                //     printer.setCabecera(userSucursal.numero, "0", userSucursal.direccion, userSucursal.direccion2, userSucursal.ciudad, userSucursal.telefono, c.user, info.empresa.title, info.empresa.nombre, info.empresa.nit);
                                //     var cab2 = data.info.cabecera;
                                //     printer.setInfoSucursal(cab2.municipio, cab2.leyendaActividad, cab2.tipoFactura, cab2.ciudadCapital, cab2.ciudad2);
                                //     for (var jk in info.items) {
                                //         var itm = info.items[jk];
                                //         var total = Math.round(itm.total);
                                //         printer.addItem(itm.cantidad, itm.detalle, total, total);
                                //     }
                                // }
                                // printer.imprimir();
                                // printer.typeDocument = "reciboEntregaPP";// imprimira un recibo de entrega de una encomienda por pagar
                                // printer.imprimir();
                                // printer.clean();
                            } catch (ex) {
                                alert(ex);
                                console.log("Errores al imprimir", ex);
                            }
                        }
                        $("#entregaForm")[0].reset();
                        removeDialog("#dialogEntrega");
                        // document.location.reload();
                    } else {
                        alert(data.info);
                        rollBackEntregaPorPagar(encomiendaData.encomienda.id);
                    }
                }
            });
        } else {
            //imprimir recibo de entrega
            if (window.printer) {
                printer.typeDocument = "reciboEntrega";
                printer.setJson(JSON.stringify(result.empresa), "empresa");
                printer.setJson(JSON.stringify(result.encomienda), "encomienda");
                userSucursal.usuario = result.cabecera.user;
                printer.setJson(JSON.stringify(userSucursal), "sucursal");
                for (var jk in result.items) {
                    var itm = result.items[jk];
                    printer.setJson(JSON.stringify(itm), "item");
                }

            } else {
                var c = result.cabecera;
                var emp = result.empresa;
                var e = json.response.info.encomienda;

                encomiendaAux = {destinatario: e.destinatario, destino: e.destino, detalle: e.detalle, guia: e.guia,
                    origen: e.origen, remitente: e.remitente, total: e.total, tipo: c.tipo, telefonoRemitente: e.telefonoRemitente,
                    declarado: e.declarado, observacion: e.observacion, ciudadDestino: e.ciudadDestino};
                infoEntregaAux = {receptor: e.receptor, carnet: e.carnet};
                cabeceraAux = {numero: userSucursal.numero, dato1: "0", direccion: userSucursal.direccion, direccion2: userSucursal.direccion2,
                    cuidad: userSucursal.ciudad, telefono: userSucursal.telefono, user: result.cabecera.user, title: emp.title, nombre: emp.nombre, nit: emp.nit};
                var itemsAux = [];
                for (var jki in result.items) {
                    var itmi = result.items[jki];
                    itemsAux.push({cantidad: itmi.cantidad, detalle: itmi.detalle, peso: itmi.peso, total:itmi.total});
                }
                $.ajax({
                    type: "GET",
                    cache: false,
                    url: BaseUrl + '/recepcion/generate-pdf-entrega',
                    data: {datos: {encomienda: encomiendaAux, infoEntrega: infoEntregaAux, cabecera: cabeceraAux, items: itemsAux}},
                    dataType: 'json',
                    success: function(data) {
                        // alert(JSON.stringify(data));
                        // printJS(data.pdfUrl);
                        printJS({
                            printable: data.entregaPdfUrl,
                            type: 'pdf',
                            onPrintDialogClose: function() {
                                console.log('eeeee2:: '+data.reciboClientePdfUrl);
                                if(data.reciboClientePdfUrl !== undefined){
                                    printJS(data.reciboClientePdfUrl);
                                }
                            }
                        });

                    }
                });

                /*
                printer.typeDocument = "reciboEntrega";
                var c = result.cabecera;
                var emp = result.empresa;
                var e = json.response.info.encomienda;
                printer.setEncomienda(e.destinatario, e.destino, e.detalle, e.guia, e.origen, e.remitente, e.total, c.tipo, e.telefonoRemitente, e.declarado, e.observacion, e.ciudadDestino);
                printer.addInfoEntrega(e.receptor, e.carnet);
//                console.log("cabecera", userSucursal, result.cabecera);
                printer.setCabecera(userSucursal.numero, "0", userSucursal.direccion, userSucursal.direccion2, userSucursal.ciudad, userSucursal.telefono, result.cabecera.user, emp.title, emp.nombre, emp.nit);
//                console.log(result.items);
                for (var jki in result.items) {
                    var itmi = result.items[jki];
                    printer.addItem(itmi.cantidad, itmi.detalle, itmi.peso, itmi.total);
                }
                 */
            }

            //printer.imprimir();
            //printer.clean();
            deleteRow();
            $("#entregaForm")[0].reset();
//            $("#dialogEntrega").dialog("close");
//            $("#dialogEntrega").dialog("destroy");
            //            alert(json.response.message);
            removeDialog("#dialogEntrega");


            //document.location.reload();
        }

    } else {
        alert(json.response.message);
    }
}

function deleteRow() {
    try {
        var row = "#" + $("#ecmedSlce").val();
        $(row).css("backgroundColor", "#F4F4C5").slideUp("slow", function() {
            $(this).remove();
        });
    } catch (e) {
        changeContent("Entrega");
    }
}
function rollBackEntregaPorPagar(id) {
    $("#dialogEntrega").block();
    var service = $("#ciudadOrigen").val() + "/encomiendas/rest/";
    var formValues = $('#entregaForm').serializeObject();//$("#entregaForm").serializeArray();

    var params = {
        "method": "rollbackEntregaPorPagar",
        "id": id
    };
    params = $.extend(params, formValues);
    jsonP2(service, function(data) {
        console.log("en el success", data);
    }, params);
}
/***********   funciones para el control de ENTREGA de encomiendas  ************/
