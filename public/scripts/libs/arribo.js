
/**
*Recupera la lista de encomiedas de un manifiesto para ser recibidas en destino
*desde un servicio web
**/
function mostrarEncomiendas(id){
    //    dialog("dialog","Lista de Encomiendas",{
    //        "Aceptar":registrarArribo
    //    });
    showDialog("dialogArribo",{
        title:"Lista de encomienda",
        content:"Cargando... ",
        buttons:{
            "Aceptar":registrarArribo,
            "Cancelar":function(){
                $(this).dialog("close");
            }
        }
    });
    
    var service=getServiceForArribo();
    var url = service.url+"/encomiendas/rest/";
    var params = {
        "method":"getEncomiendas",
        "userid":"1",
        "apikey":"1234567890",
        "format":"json",
        "callback":"getEncomiendas",
        "manif":id,
        "estado":"ENVIO"
    };
    jsonP(url,params);
}
/**
** evalua el resultado del servicio web y crea la interface para proceder con la recepcion
*/
function getEncomiendas(json){
    var resp = json.response;
    if(resp.status=="success"){
        var table = $("#listManifiestos");
        if(resp.size>0){
            var suc="";
            var countDivs=0;
            var content="<div class='mainContentDiv'>";
            content+="<input type='hidden' value='"+resp.manifiesto+"' id='manifiestoSelected'/>";
            for(var m in resp.encomiendas){
                var enc = resp.encomiendas[m];
                if(suc!=enc.sucursal){
                    if(countDivs>0){
                        content+="</table>";
                        content=content+"</div>";
                    }
                    content+="<div class='internalContentDiv'>";
                    content+="<div class='internalSubtitle'><span class='internalSubtitleName'>"+enc.nombreSucursal+"</span><span class='headCollapse'></span></div>";
                    content+="<table class='listManifiestos'><tr><th><input type='checkbox' /></th><th>Guia</th><th>Cantidad</th><th>Detalle</th></tr>";
                    //                    content+="<tr><th><input type='checkbox' /></th><th colspan='2'>"+enc.nombreSucursal+"<span class='collapsibleTable'></span></th></tr>";
                    suc=enc.sucursal;
                    countDivs++;
                }
                content+="<tr class='"+suc+"'>";
                content+="<td><input type='checkbox' value='"+enc.id+"' title='Seleccionar como recibida'/></td>";
                content+="<td>"+enc.guia+"</td>";
                content+="<td><input type='text' value='"+enc.cantidad+"' class='numeric' size='3'/></td>";
                content+="<td>"+enc.detalle+"</td>";
                content+="</tr>";
            }
            //            if(countDivs==0){
            content+="</div>";
            content+="</div>";
            //            }
            $("#dialogArribo").html(content);
            $("div.mainContentDiv span.headCollapse:not(:first)").removeClass("headCollapse").addClass("headExpand");
            makeCollapsible("headCollapse","headExpand","listManifiestos");
            $("div.mainContentDiv .listManifiestos:not(:first)").hide();
            $("div.mainContentDiv").show('slow');

        }else{
            $("#dialogArribo").html("<div class='ui-state-highlight'>Todas las encomiendas han sido registradas en destino</div>");
        }
    }
}

/*
* llamada al servicio web para registrar en base de datos las encomiendas
* que han sido recepcionadas en destino
**/
function registrarArribo(){
    var service = getServiceForArribo();
    var url=service.url+"/encomiendas/rest/";
    var received = "";
    $("div.mainContentDiv .listManifiestos tr:not(:first-children) input:checked").each(function(pos){
        var value = $(this).val();
        //        var codigo = value.split(",");
        received+=value+",";
    })
    received=received.substr(0, received.length-1);
    var params = {
        "method":"saveArribo",
        "userid":"1",
        "apikey":"1234567890",
        "format":"json",
        "callback":"successSave",
        "listReceived":received,
        "responsable":$("#f87bb64fe05086c310ccc55799c26d7123287879").val(),
        "sucReceived":$("#fe5b095e2ffd3d49c668bb29d865e0e499826d45").val(),
        "manifiesto":$("#manifiestoSelected").val()
    };
    jsonP(url,params);
}
/* muestra si la encomienda ha sido registrada satisfactoriamente o no */
function successSave(json){
    alert(json.response.message);
    if(json.response.error==false){
        $("#dialogArribo").dialog("close");
        imprimimirLista(json.response.info);
    }
}