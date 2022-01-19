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
    // alert(service); // ssssss hhh ee
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
                    var f = new Date();
                    console.log('ffff:: ' +f.getDate() + "-" + f.getMonth() + "-" + f.getFullYear());
                    data.info.tipo = encomiendaData.cabecera.tipo + ' POR PAGAR';
                    data.info.fechaActual = f.getDate() + "-" + f.getMonth() + "-" + f.getFullYear();
                    // data.info.fechaActual = 'cc vvv rrrr';
                    data.info.observacion = encomiendaData.encomienda.observacion;
                    data.info.items = encomiendaData.items;
                    data.info.infoEntrega = {
                        receptor: encomiendaData.encomienda.receptor,
                        carnet: encomiendaData.encomienda.carnet,
                        telefonoRemitente: encomiendaData.encomienda.telefonoRemitente
                    };
                    console.log(JSON.stringify(data));
                    console.log(JSON.stringify(data.info));
                    console.log(JSON.stringify(encomiendaData));
                    if (data.error == false) {
                        deleteRow();
                        if (data.info.tipoFactura == "Automatica") {
                            $('#f_show_print :input[name="datos"]').val(JSON.stringify(data.info));
                            $('#f_show_print').submit();
                        }
                        // $("#entregaForm")[0].reset();
                        // removeDialog("#dialogEntrega");
                        window.location.reload();
                    } else {
                        alert(data.info);
                        rollBackEntregaPorPagar(encomiendaData.encomienda.id);
                    }
                }
            });
        } else {
            //imprimir recibo de entrega
            console.log('eeee lll');
            console.log(JSON.stringify(result));
            console.log(JSON.stringify(json.response));
            var c = result.cabecera;
            var emp = result.empresa;
            var e = json.response.info.encomienda;

            encomiendaAux = {destinatario: e.destinatario, destino: e.destino, detalle: e.detalle, guia: e.guia,
                origen: e.origen, remitente: e.remitente, total: e.total, tipo: c.tipo, telefonoRemitente: e.telefonoRemitente,
                declarado: e.declarado, observacion: e.observacion, ciudadDestino: e.ciudadDestino};
            infoEntregaAux = {receptor: e.receptor, carnet: e.carnet};
            cabeceraAux = {numeroSuc: userSucursal.numero, dato1: "0", direccion: userSucursal.direccion, direccion2: userSucursal.direccion2,
                nombSuc: result.encomienda.sucursalEntrega,
                cuidad: userSucursal.ciudad, telefono: userSucursal.telefono, usuario: result.cabecera.user, title: emp.title, nombre: emp.nombre, nit: emp.nit};
            var itemsAux = [];
            for (var jki in result.items) {
                var itmi = result.items[jki];
                itemsAux.push({cantidad: itmi.cantidad, detalle: itmi.detalle, peso: itmi.peso, total:itmi.total, monto:itmi.total});
            }

            var f = new Date();
            let day = ("0"+f.getDate()).slice(-2); //("0"+date.getDate()).slice(-2);
            let month = ("0"+(f.getMonth() + 1)).slice(-2);
            let year = f.getFullYear();
            var datosImp = {
                encomienda: encomiendaAux,
                cabecera: cabeceraAux,
                empresa: emp,
                items: itemsAux,
                tipo: c.tipo,
                observacion: e.detalle,
                fechaActual: day + "-"+ month + "-" + year,
                infoEntrega: infoEntregaAux};

            $('#f_show_print :input[name="datos"]').val(JSON.stringify(datosImp));
            $('#f_show_print').submit();
            deleteRow();
            // $("#entregaForm")[0].reset();
            removeDialog("#dialogEntrega");
            window.location.reload();
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
