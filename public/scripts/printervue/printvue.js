function loadImprEntrega(data){
    var vm = new Vue({
        el: '#print_v',
        // data: {
        //     fechaActual: '2021-10-09',
        //     empresa: {title: 'LINEA SINDICAL FLOTA BOLIVAR'},
        //     cabecera: {
        //         numeroSuc: '456',
        //         telefono: '45613255',
        //         direccion: 'Av test',
        //         nombSuc: 'Test Sucursal v',
        //         municipio: 'CERCADO',
        //         ciudad: 'COCHABAMBA',
        //         usuario: 'Angel Pedro Domingo Murillo Nava'
        //     },
        //     encomienda: {
        //         origen: 'Test de origin para mandar a llamar y no llamar y no marcar el telefono',
        //         destino: 'Destino test para no llamar por telefono y ver la pantalla',
        //         remitente: 'Remitente para no llamar y llenar datos para la prueba',
        //         telefonoDestinatario: 'Telefono destinatario',
        //         guia: 'ERc-456',
        //         observacion: 'Observacion sin v',
        //         remitente: 'Juan Carlos Esposito Tellez Tellez Ormonamnder'
        //     }
        // }
        data: data
    });

    var vm1 = new Vue({
        el: '#print_recibo_v',
        data: data
    });

    printDiv('print_v');
    printDiv('print_recibo_v');
}

function printDiv(divName){
    var printContents = document.getElementById(divName).innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    return false;
}