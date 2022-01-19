<!-- Factura impresion -->
<div id="print_v_factura" style="display: none;">
    <div style="max-width: 302px; min-width: 302px; width: 302px; padding: 4px 8px;">
        <div class="pdf_head">
            <h5 style="font-size: 14px;">{{empresa.title}}</h5>
            <p style="font-size: 12px;">Sucursal: {{cabecera.numeroSuc}}</p>
            <p style="font-size: 12px;">Telefono: {{cabecera.telefono}}</p>
            <p style="font-size: 12px;">{{cabecera.direccion}}</p>
            <p style="font-size: 12px;">({{cabecera.nombSuc}})</p>
            <p style="font-size: 12px;">{{cabecera.municipio}} - {{cabecera.ciudad}}</p>
            <h3 style="font-size: 24px; padding: 0; margin: 4px; letter-spacing: 8px;">FACTURA</h3>
            <h4 style="font-size: 16px; padding: 0; margin: 2px;">ORIGINAL</h4>
            <p style="font-size: 12px;">{{empresa.leyendaActividad}}</p>
        </div>
        <div class="pdf_factura">
            <table style="width: 95%;">
                <tr>
                    <td style="width: 50%;">
                        <p style="font-size: 13px; padding: 0; margin: 2px;"><b>NIT:</b> {{ empresa.nit }} </p>
                    </td>
                    <td style="width: 50%;">
                       <p style="font-size: 13px; padding: 0; margin: 2px;"><b>#Factura:</b> {{ factura.numerofactura }}</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p style="font-size: 13px; padding: 0; margin: 2px;"><b>Autorización</b> {{ factura.autorizacion }}</p>
                    </td>
                </tr>
            </table>
            <hr>
            <table style="width: 95%;">
                <tr>
                    <td style="width: 50%;">
                        <p style="font-size: 13px; padding: 0; margin: 2px;"><b>{{ cabecera.ciudad }}</b></p>
                    </td>
                    <td style="width: 50%;">
                        <p style="font-size: 13px; padding: 0; margin: 2px;"><b>{{ factura.fecha }}</b></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p style="font-size: 13px; padding: 0; margin: 2px;">Señor (es): {{ factura.nombre }}</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p style="font-size: 13px; padding: 0; margin: 2px;">NIT/CI: {{ factura.nit }}</p>
                    </td>
                </tr>
            </table>
            <hr>
        </div>
        <div class="pdf_datos">
            <p style="font-size: 13px; padding: 0; margin: 2px;"><b>Remitente:</b> <span>{{encomienda.remitente}}</span></p>
            <p style="font-size: 13px; padding: 0; margin: 2px;"><b>Destinatario:</b> <span>{{encomienda.destinatario}}</span></p>
            <p style="font-size: 13px; padding: 0; margin: 2px;"><b>Telefono:</b> <span>{{encomienda.telefonoDestinatario}}</span></p>
            <hr>
            <p style="font-size: 18px; padding: 0; margin: 2px; text-align: center;"><b>Guia:</b> <span>{{encomienda.guia}}</span></p>
            <p style="font-size: 13px; padding: 0; margin: 2px;"><b>Origen:</b> <span>{{encomienda.origen}}</span></p>
            <p style="font-size: 13px; padding: 0; margin: 2px;"><b>Destino:</b> <span>{{encomienda.destino}}</span></p>
            <br>
            <table>
                <thead>
                <tr>
                    <th colspan="4"><p style="font-size: 13px; padding: 0; margin: 4px"><b>DETALLE</b></p></th>
                </tr>
                <tr style="text-align: center;">
                    <th style="font-size: 11px;">Cant</th>
                    <th style="font-size: 11px;">Detalle</th>
                    <th style="font-size: 11px;">Peso</th>
                    <th style="font-size: 11px;">Mont</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in items">
                    <td style="font-size: 11px; text-align: center;">{{ item.cantidad }}</td>
                    <td style="font-size: 11px;">{{ item.detalle }}</td>
                    <td style="font-size: 11px; text-align: right;">{{ item.peso }}</td>
                    <td style="font-size: 11px; text-align: right;" >{{ item.monto }}</td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="4" style="font-size: 11px;">{{encomienda.observacion}}</td>
                </tr>
                </tfoot>
            </table>

            <p style="font-size: 14px;">Total General: <b style="font-size: 20px;">Bs. {{ factura.total }}</b></p>
            <p style="font-size: 10px;">Son: {{ factura.totalLiteral }}</p>
            <p style="font-size: 14px;">Codigo de control: {{ factura.codigoControl }}</p>
            <p style="font-size: 14px;">Fecha limite emision {{ factura.fechaLimite }}</p>
            <p style="font-size: 14px;">Usuario: {{ cabecera.usuario }}</p>
        </div>
        <hr>
        <div id="f_qrcode" style="text-align: center;">
            <img width="150" v-bind:src="url_qr" alt="">
        </div>
        <hr>
        <div class="pdf_pie">
            <br>
            <p style="font-size: 13px"><b>"ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAIS, EL USO ILICITO DE ESTA SERA SANCIONADO DE ACUERDO A LEY"</b></p>
            <p style="font-size: 11px;">{{ cabecera.leyendaSucursal }}</p>
            <hr>
            <p style="text-align: center;">Mobius IT Solutions</p>
        </div>
    </div>
</div>