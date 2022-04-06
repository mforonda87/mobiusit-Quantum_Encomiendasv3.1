
$(function () {
    var params = {
        collapsible: true,
        autoHeight: false

    };
    INFORMACION_VIAJE = {};
    $("#numeroCopias").numeric().select();
    var $encomiendasAcc = $("#infoEncomiendasManifiesto").accordion(params);

    $("#imprimirManifiesto").click(function () {

        if (!$.isEmptyObject(INFORMACION_VIAJE)) {
            $.ajax({
                dataType: "json",
                type: "POST",
                cache: false,
                url: BaseUrl + '/recepcion/get-encomiendas/manifiesto/' + INFORMACION_VIAJE.manifiesto,
                beforeSend: function (xhr) {
                    $("#middleForm").block();
                },
                success: function (data) {
                    var info = data.info;
                    var mani = {
                        lista: data.encomiendas,
                        manifiesto: {
                            chofer: info.nombre_chofer,
                            fecha: info.fecha,
                            hora: info.hora,
                            destino: info.ciudadDestino,
                            numBus: info.numero
                        },
                        cabecera: {
                            emp: data.cabecera.empresa,
                            title: data.cabecera.title,
                            direccion: data.cabecera.direccion,
                            usuario: data.cabecera.usuario
                        }
                    };
                    var numeroCopias = $("#numeroCopias").val();
                    if (numeroCopias == "" || numeroCopias == "0" || numeroCopias == 0) {
                        numeroCopias = 1;
                    }
                    // for (i = 0; i < numeroCopias; i++) {
                    //     // imprimimirLista(mani);
                    //     imprimimirListaPrintjs(mani);
                    // }
                    imprimimirListaPrintjs(mani);
                }
            });
        } else {
            alert("Seleccione un viaje por favor");
        }
    });
    //        $("#infoEncomiendas").accordion(params);
    $("#itinerarioList").accordion({collapsible: true,
        autoHeight: false,
        active: false,
        change: function (event, ui) {
            INFORMACION_VIAJE = {};
            $("#choferesContent").html("");
            $("#busContent").html("");
            $("#fechaContent").html("");
            $(".jscrollpane").css({height: "350px"}).jScrollPane();
        }
    });


    $("#asignar").click(function () {
        var viaje = INFORMACION_VIAJE;
        if (!viaje.manifiesto) {
            alert("Seleccione un viaje para enviar las encomiendas por favor");
            return;
        }
        var selecteds = $("#infoEncomiendasManifiesto tr:not(:first-child) input[type=checkbox]");
        var enviandoA = $("#infoEncomiendasManifiesto h3.ui-state-active").text().toLowerCase();
        if (INFORMACION_VIAJE.ciudadDestino.toLowerCase() != enviandoA) {
            var resp = confirm("El viaje y el destino de la encomienda no son iguales desea continuar");
            if (!resp) {
                return;
            }
        }
        var envio = getSelectedsJSON(selecteds);

        $.ajax({
            type: "GET",
            dataType: "json",
            url: BaseUrl + "/recepcion/save-manifiesto/viaje/" +
                    viaje.viaje + "/data/" + envio + "/chofer/" + viaje.chofer + "/bus/" + viaje.idBus +
                    "/destino/" + viaje.destino,
            beforeSend: function (xhr) {
                $("#middleForm").block();
            },
            success: function (msg) {
                if (msg.error == false) {
                    $.each(selecteds, function (i) {
                        var tr = $(this).parents("tr");
                        var clone = tr.clone();

                        if ($(this).is(":checked")) {
                            $("#ListadoEncomiendasManifiesto td.notFound").remove();
                            $("#ListadoEncomiendasManifiesto").append(clone);
                            tr.remove();
                        }
                    });
                }
                alert(msg.mensaje);
            }
        });
    });
    $("#quitar").click(moverEncomienda);
});

function getSelectedsJSON(selecteds) {
    var infoSend = {};
    $.each(selecteds, function (i) {
        if ($(this).is(":checked")) {
            var idEnc = $(this).next().val();
            if (infoSend[idEnc] == null) {
                infoSend[idEnc] = [];
            }
            infoSend[idEnc].push($(this).val())
        }
    });
    if ($.isEmptyObject(infoSend)) {
        return null;
    }

    var envio = '{';
    for (var i in infoSend) {
        envio += '"' + i + '":"';
        var cc = 0;
        for (var j in infoSend[i]) {
            envio += infoSend[i][j];
            if (cc < infoSend[i].length - 1) {
                envio += ',';
            }
            cc++;
        }
        envio += '",';
    }
    envio = envio.substring(0, envio.length - 1);
    envio += '}';
    return envio;
}

function moverEncomienda() {
    var viaje = INFORMACION_VIAJE;

    if (!viaje.manifiesto) {
        alert("Seleccione un viaje para enviar las encomiendas por favor");
        return;
    }


    var selecteds = $("#ListadoEncomiendasManifiesto tbody tr input[type=checkbox]");
    var envio = getSelectedsJSON(selecteds);
    if (envio == null) {
        alert("no se ha seleccionado ninguan encomienda");
        return;
    }
    $.ajax({
        type: "GET",
        dataType: "json",
        url: BaseUrl + "/recepcion/remove-encomienda/manifiesto/" + viaje.manifiesto + "/data/" + envio,
        beforeSend: function (xhr) {
            $("#middleForm").block();
        },
        success: function (msg) {
            if (msg.error == false) {
                $.each(selecteds, function (i) {
                    var tr = $(this).parents("tr");
                    var clone = tr.clone();
                    if ($(this).is(":checked")) {
                        console.log(tr);
                        //                        $("#ListadoEncomiendasManifiesto").append(clone);
                        tr.remove();
                    }
                });
            }
            alert(msg.mensaje);
        }
    });
}
function selectAllDestino(destino, obj) {
    var table = $(obj).parents("table");
    table.find("input").each(function (i) {

        if ($(obj).is(":checked")) {
            $(this).attr("checked", true);
        } else {
            $(this).attr("checked", false);
        }
    });
}
/**
 * Carga informacion del viaje seleccionado para el manifiesto
 * @param {type} viaje
 * @param {type} interno
 * @param {type} hora
 * @param {type} destino
 * @param {type} bus
 * @returns {undefined}
 */
function selectForManifiesto(viaje, interno, hora, destino, bus) {
    $("#middleForm").block();
    $.ajax({
        dataType: "json",
        type: "POST",
        cache: false,
        url: BaseUrl + '/recepcion/show-data-viaje-manifiesto/viaje/' + viaje + "/bus/" + bus,
        success: function (data) {
            clearInfoManifiesto();
            INFORMACION_VIAJE = data.viaje;
            INFORMACION_VIAJE.manifiesto = data.manifiesto.id_manifiesto;
            var i = 0;
            $.each(data.choferes, function (j, ch) {
                $("#choferesContent").append("<li>" + ch.cargo + " : " + ch.nombre_chofer + "</li>");
                if (i == 0) {
                    INFORMACION_VIAJE.chofer = ch.id_chofer;
                    INFORMACION_VIAJE.nombreChofer = ch.nombre_chofer;
                }
                i++;
            });

            $("#busContent").append("<li> Bus :" + data.viaje.interno + "</li> ");
            $("#busContent").append("<li> Placa :" + data.viaje.placa + "</li> ");
            $("#busContent").append("<li> Propietario :" + data.viaje.propietario + "</li> ");

            $("#fechaContent").append("<li> Fecha   :" + data.viaje.fecha + "</li> ");
            $("#fechaContent").append("<li> Hora    : " + data.viaje.hora + "</li> ");
            $("#fechaContent").append("<li> Destino :" + data.viaje.ciudadDestino + "</li> ");
            $("#ListadoEncomiendasManifiesto tbody").html('');
            if (data.encomiendas.length == 0) {
                $("#ListadoEncomiendasManifiesto tbody").html('<tr><td class="notFound" colspan="3"><div class="ui-state-highligth">No se encontraron encomiendas asignadas </div></td></tr>');
            } else {
                if ($("#ListadoEncomiendasManifiesto .notFound").length > 0) {
                    $("#ListadoEncomiendasManifiesto .notFound").remove();
                }
                $.each(data.encomiendas, function (i) {
                    //                        var clsTR = i%2==0?"par":"impar"; 
                    var clsTD = i % 2 == 0 ? "par" : "impar";
                    var encomienda = data.encomiendas[i];
                    var fila = "<tr>";
                    fila += '<td class="' + clsTD + '"><input type="checkbox" value="' + encomienda.id + '" name="enviarEncomienda">';
                    fila += '<input type="hidden" value="' + encomienda.idEncomienda + '"  name="idEncomienda">';
                    fila += '<input type="hidden" value="' + encomienda.puerta_puerta + '"  name="puerta_puerta"></td>';
                    fila += '<td class="' + clsTD + '">' + encomienda.guia + '</td>';
                    fila += '<td class="' + clsTD + '">' + encomienda.detalle + '</td>';
                    fila += '</tr>';
                    $("#ListadoEncomiendasManifiesto tbody").append(fila);

                });
            }
        }
    });
}


function crearViaje() {
    showDialog("CrearViaje", {
        title: "Registrar Viaje",
        url: '/envio/show-itinerario/',
        content: "loading ....",
        buttons: {
            "Aceptar": function (evt) {
                if ($("#fechaViaje").val() === "") {
                    alert("Fecha invalida por favor seleccione una fecha para el viaje.");
                } else {
                    $.ajax({
                        dataType: "json",
                        type: "POST",
                        cache: false,
                        url: BaseUrl + '/envio/add-viaje/',
                        data: {"viaje": {
                                "fecha": $("#fechaViaje").val(),
                                "hora": $("#horaViaje").val(),
                                "bus": $("#busViaje").val()
                            },
                            "destino": $("#destinoViaje").val(),
                            "origen": $("#origenViaje").val(),
                        },
                        success: function (data) {
                            console.log(data);
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
function changeDestino() {
    $.ajax({
        dataType: "json",
        type: "GET",
        cache: false,
        url: BaseUrl + '/envio/change-itinerario/destino/' + $("#selectDestino").val(),
        success: function (data) {
            console.log(data);
            $("#horaViaje").html("");

            $.each(data, function (i) {
                var itinerario = data[i];
                var hora = $("<option value=" + itinerario.id_hora_viaje + ">" + itinerario.hora_salida + "</option>");
                $("#horaViaje").append(hora);
            });


        }
    });
}

function editarChoferes(viaje) {
    console.log(INFORMACION_VIAJE);
    if (INFORMACION_VIAJE.viaje === undefined || INFORMACION_VIAJE.viaje === "") {
        alert("Seleccione un viaje");
        return;
    }
    showDialog("CrearViaje", {
        title: "Registrar Viaje",
        url: '/envio/edit-choferes-viaje/viaje/'+INFORMACION_VIAJE.viaje,
        content: "loading ....",
        buttons: {
            "Aceptar": function (evt) {
                if ($("#fechaViaje").val() === "") {
                    alert("Fecha invalida por favor seleccione una fecha para el viaje.");
                } else {
                    $.ajax({
                        dataType: "json",
                        type: "POST",
                        cache: false,
                        url: BaseUrl + '/envio/update-drivers/',
                        data: {"drivers": {
                                "chofer": $("#selectedChoferId").val(),
                                "relevo": $("#selectedRelevoId").val(),
                                "ayudante": $("#selectedAyudanteId").val()
                            },
                            "viaje": INFORMACION_VIAJE.viaje
                        },
                        success: function (data) {
                            console.log(data);
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