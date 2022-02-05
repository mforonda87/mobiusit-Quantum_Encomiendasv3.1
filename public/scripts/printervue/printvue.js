function loadImprRecepcion(data){
    new Vue({
        el: '#print_v',
        data: data,
        computed: {
            total: function () {
                let sum = 0;
                return this.items.reduce((sum, item) => sum + parseFloat(item.monto), 0 );

            }
        }
    });

    new Vue({
        el: '#print_recibo_v',
        data: data,
        computed: {
            total: function () {
                let sum = 0;
                return this.items.reduce((sum, item) => sum + parseFloat(item.monto), 0 );

            }
        }
    });

    printDiv('print_v');
    printDiv('print_recibo_v');
}

function loadImprEntrega(data){
    var pev = new Vue({
        el: '#print_entrega_v',
        data: data,
        computed: {
            total: function () {
                let sum = 0;
                for (let value of Object.values(this.items)) {
                    console.log(JSON.stringify(value));
                    console.log('rrr:: ' + value.total);
                    sum += parseFloat(value.total);
                }
                return sum;
            }
        }
    });

    var pecv = new Vue({
        el: '#print_entrega_copia_v',
        data: data,
        computed: {
            total: function () {
                let sum = 0;
                for (let value of Object.values(this.items)) {
                    sum += parseFloat(value.total);
                }
                return sum;
            }
        }
    });

    printDivMejorado('print_entrega_v');
    printDivMejorado('print_entrega_copia_v');
    window.close();
}

function printDiv(divName){
    var printContents = document.getElementById(divName).innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    // window.close();
    return false;
}

function printDivMejorado(divName){
    var printContents = document.getElementById(divName).innerHTML;
    // var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    // document.body.innerHTML = originalContents;
    // window.close();
    return false;
}

function loadImprFactura(data){
    new Vue({
        el: '#print_v_factura',
        data: data,
        computed: {
            total: function () {
                let sum = 0;
                return this.items.reduce((sum, item) => sum + parseFloat(item.monto), 0 );

            }
        },
        mounted () {
        },
        filters: {
            numeroDec: function (value) {
                valueAux = parseFloat(value+'');
                return valueAux.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
            }
        }
    });

    new Vue({
        el: '#print_recibo_v',
        data: data,
        computed: {
            total: function () {
                let sum = 0;
                for (let [key, value] of Object.entries(this.items)) {
                    console.log('www pp mm kk');
                    console.log(`${key}: ${value}`);
                    sum += parseFloat(value.monto);
                }
                return sum;

                // return this.items.reduce((sum, item) => sum + parseFloat(item.monto), 0 );

            },
            firma: function () {
                let fir = '';
                switch (this.tipo) {
                    case 'NORMAL':
                        fir = this.encomienda.remitente;
                        break;
                    case 'POR PAGAR':
                        fir = this.encomienda.remitente;
                        break;
                    case 'DE ENTREGA':
                        // fir = this.encomienda.destinatario;
                        fir = this.infoEntrega.receptor + '(ci: ' + this.infoEntrega.carnet + ')';
                        break;
                    case 'DE ENTREGA POR PAGAR':
                        // fir = this.encomienda.destinatario;
                        fir = this.infoEntrega.receptor + '(ci: ' + this.infoEntrega.carnet + ')';
                        break;
                    case 'GIRO':
                        fir = this.encomienda.remitente;
                        break;

                }
                return fir;
            }
        },
        filters: {
            numeroDec: function (value) {
                valueAux = parseFloat(value+'');
                return valueAux.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
            }
        }
    });

    printDiv('print_v_factura');
    printDiv('print_recibo_v');
    window.close();
}

function loadImprRecepcionClose(data){
    new Vue({
        el: '#print_v',
        data: data,
        computed: {
            total: function () {
                let sum = 0;
                console.log('33 tttt uuu ppp');
                console.log(JSON.stringify(this.items));
                for (let [key, value] of Object.entries(this.items)) {
                    console.log(`${key}: ${value}`);
                    sum += parseFloat(value.monto);
                }
                return sum;
                // return this.items.reduce((sum, item) => sum + parseFloat(item.monto), 0 );

            },
            firma: function () {
                let fir = '';
                switch (this.tipo) {
                    case 'NORMAL':
                        fir = this.encomienda.remitente;
                        break;
                    case 'POR PAGAR':
                        fir = this.encomienda.remitente;
                        break;
                    case 'DE ENTREGA':
                        // fir = this.encomienda.destinatario;
                        fir = this.infoEntrega.receptor + '(ci: ' + this.infoEntrega.carnet + ')';
                        break;
                    case 'DE ENTREGA POR PAGAR':
                        fir = this.encomienda.destinatario;
                        break;

                }
                return fir;
            }
        }
    });

    new Vue({
        el: '#print_recibo_v',
        data: data,
        computed: {
            total: function () {
                let sum = 0;
                for (let [key, value] of Object.entries(this.items)) {
                    console.log('www pp mm kk');
                    console.log(`${key}: ${value}`);
                    sum += parseFloat(value.monto);
                }
                return sum;

                // return this.items.reduce((sum, item) => sum + parseFloat(item.monto), 0 );

            },
            firma: function () {
                let fir = '';
                switch (this.tipo) {
                    case 'NORMAL':
                        fir = this.encomienda.remitente;
                        break;
                    case 'POR PAGAR':
                        fir = this.encomienda.remitente;
                        break;
                    case 'DE ENTREGA':
                        // fir = this.encomienda.destinatario;
                        fir = this.infoEntrega.receptor + '(ci: ' + this.infoEntrega.carnet + ')';
                        break;
                    case 'DE ENTREGA POR PAGAR':
                        // fir = this.encomienda.destinatario;
                        fir = this.infoEntrega.receptor + '(ci: ' + this.infoEntrega.carnet + ')';
                        break;

                }
                return fir;
            }
        }
    });

    printDiv('print_v');
    printDiv('print_recibo_v');
    window.close();
}
