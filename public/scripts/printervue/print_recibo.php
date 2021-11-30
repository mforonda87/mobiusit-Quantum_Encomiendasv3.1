<!-- Primera impresion -->
<div id="print_v" style="display: none;">
    <div style="max-width: 302px; min-width: 302px; width: 302px; border: 1px solid #000000; padding: 4px 8px;">
        <div class="pdf_head">
            <h3>{{empresa.title}}</h3>
            <p>Sucursal: {{cabecera.numeroSuc}}</p>
            <p>Telefono: {{cabecera.telefono}}</p>
            <p>{{cabecera.direccion}}</p>
            <p>({{cabecera.nombSuc}})</p>
            <p>{{cabecera.municipio}} - {{cabecera.ciudad}}</p>
            <h3>GUIA DE ENCOMIENDAS {{tipo}}</h3>
        </div>
        <div class="pdf_datos">
            <p><b>Fecha:</b> {{fechaActual}}</p>
            <p><b>Origen:</b> <span>{{encomienda.origen}}</span></p>
            <p><b>Destino:</b> <span>{{encomienda.destino}}</span></p>
            <p><b>Remitente:</b> <span>{{encomienda.remitente}}</span></p>
            <p><b>Destinatario:</b> <span>{{encomienda.destinatario}}</span></p>
            <p><b>Telefono:</b> <span>{{encomienda.telefonoDestinatario}}</span></p>
            <p><b>Guia:</b> <span>{{encomienda.guia}}</span></p>
            <br>
            <table>
                <thead>
                <tr>
                    <th colspan="4"><b>DETALLE</b></th>
                </tr>
                <tr style="text-align: center;">
                    <th>Cant</th>
                    <th>Detalle</th>
                    <th>Peso</th>
                    <th>Mont</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in items">
                    <td style="text-align: center;">{{ item.cantidad }}</td>
                    <td>{{ item.detalle }}</td>
                    <td style="text-align: right;">{{ item.peso }}</td>
                    <td style="text-align: right;">{{ item.monto }}</td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="4">{{encomienda.observacion}}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right;"><p><b>TOTAL</b></p></td>
                    <td style="text-align: right;"><p><b>{{ total }}</b></p></td>
                </tr>
                </tfoot>
            </table>
        </div>
        <div class="pdf_pie">
            <p class="pie_usuario">Usuario: <span>{{cabecera.usuario}}</span></p>
            <br><br><br>
            <p class="pie_firma">-----------------------------------------</p>
            <p class="pie_firma"><b>{{encomienda.remitente}}</b></p>
            <br>
            <p style="text-align: center;">Mobius IT Solutions</p>
        </div>
    </div>
</div>
<!--<div id="print_recibo_v">-->
<div id="print_recibo_v" style="display: none;">
    <div style="max-width: 302px; min-width: 302px; width: 302px; border: 1px solid #000000; padding: 4px 8px;">
        <div class="pdf_head">
            <h3>{{empresa.title}}</h3>
            <p>Sucursal: {{cabecera.numeroSuc}}</p>
            <p>Telefono: {{cabecera.telefono}}</p>
            <p>{{cabecera.direccion}}</p>
            <p>({{cabecera.nombSuc}})</p>
            <p>{{cabecera.municipio}} - {{cabecera.ciudad}}</p>
            <h3>RECIBO ENCOMIENDAS {{tipo}}</h3>
        </div>
        <div class="pdf_datos">
            <p><b>Fecha:</b> {{fechaActual}}</p>
            <p><b>Origen:</b> <span>{{encomienda.origen}}</span></p>
            <p><b>Destino:</b> <span>{{encomienda.destino}}</span></p>
            <p><b>Remitente:</b> <span>{{encomienda.remitente}}</span></p>
            <p><b>Destinatario:</b> <span>{{encomienda.destinatario}}</span></p>
            <p><b>Telefono:</b> <span>{{encomienda.telefonoDestinatario}}</span></p>
            <p><b>Guia:</b> <span>{{encomienda.guia}}</span></p>
            <br>
            <table>
                <thead>
                <tr>
                    <th colspan="4"><b>DETALLE</b></th>
                </tr>
                <tr style="text-align: center;">
                    <th>Cant</th>
                    <th>Detalle</th>
                    <th>Peso</th>
                    <th>Mont</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in items">
                    <td style="text-align: center;">{{ item.cantidad }}</td>
                    <td>{{ item.detalle }}</td>
                    <td style="text-align: right;">{{ item.peso }}</td>
                    <td style="text-align: right;">{{ item.monto }}</td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="4">{{encomienda.observacion}}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right;"><p><b>TOTAL</b></p></td>
                    <td style="text-align: right;"><p><b>{{ total }}</b></p></td>
                </tr>
                </tfoot>
            </table>
        </div>
        <div class="pdf_pie">
            <p class="pie_usuario">Usuario: <span>{{cabecera.usuario}}</span></p>
            <br><br><br>
            <p class="pie_firma">-----------------------------------------</p>
            <p class="pie_firma"><b>{{encomienda.remitente}}</b></p>
        </div>
        <br>
        <p style="text-align: center;">Mobius IT Solutions</p>
    </div>
</div>
<!-- Imprimir entrega original -->
<div id="print_entrega_v" style="display: none;">
    <div style="max-width: 302px; min-width: 302px; width: 302px; border: 1px solid #000000; padding: 4px 8px;">
        <div class="pdf_head">
            <h3>{{empresa.title}}</h3>
            <p>Sucursal: {{cabecera.numeroSuc}} Telefono: {{cabecera.telefono}}</p>
            <p>{{cabecera.direccion}}</p>
            <p>{{cabecera.direccion2}}</p>
            <p>{{cabecera.ciudad}}</p>
            <h3>RECIBO {{tipo}}</h3>
        </div>
        <div class="pdf_datos">
            <p><b>Fecha:</b> {{fechaActual}}</p>
            <p><b>Origen:</b> <span>{{encomienda.origen}}</span></p>
            <p><b>Destino:</b> <span>{{encomienda.destino}}</span></p>

            <p><b>Entregado en:</b> <span>{{encomienda.destino}}</span></p>
            <p><b>Guia:</b> <span>{{encomienda.guia}}</span></p>

            <p><b>Remitente:</b> <span>{{encomienda.remitente}}</span></p>
            <p><b>Destinatario:</b> <span>{{encomienda.destinatario}}</span></p>

            <p><b>Recoje:</b> {{infoEntrega.receptor}}</p>
            <p><b>Documento:</b> {{ infoEntrega.carnet }}</p>
            <p><b>Telefono:</b> {{ encomienda.telefonoRemitente }}</p>

            <br>
            <table>
                <thead>
                <tr>
                    <th colspan="3"><b>DETALLE</b></th>
                </tr>
                <tr style="text-align: center;">
                    <th>Cant</th>
                    <th>Detalle</th>
                    <th>Mont</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item, i) in items">
                    <td style="text-align: center;">{{ item.cantidad }}</td>
                    <td>{{ item.detalle }}</td>
                    <td style="text-align: right;">{{ item.total }}</td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="4">{{observacion}}</td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: right;"><p><b>TOTAL</b></p></td>
                    <td style="text-align: right;"><p><b>{{ total }}</b></p></td>
                </tr>
                </tfoot>
            </table>
        </div>
        <div class="pdf_pie">
            <p class="pie_usuario">Usuario: <span>{{cabecera.usuario}}</span></p>
            <br><br><br>
            <p class="pie_firma">-----------------------------------------</p>
            <p class="pie_firma"><b>{{infoEntrega.receptor}}</b></p>
        </div>
        <br>
        <p style="text-align: center;">Mobius IT Solutions</p>
    </div>
</div>
<!-- Imprimir entrega copia -->
<div id="print_entrega_copia_v" style="display: none;">
    <div style="max-width: 302px; min-width: 302px; width: 302px; border: 1px solid #000000; padding: 4px 8px;">
        <div class="pdf_head">
            <h3>{{empresa.title}}</h3>
            <p>Sucursal: {{cabecera.numeroSuc}} Telefono: {{cabecera.telefono}}</p>
            <p>{{cabecera.direccion}}</p>
            <p>{{cabecera.direccion2}}</p>
            <p>{{cabecera.ciudad}}</p>
            <h3>RECIBO {{tipo}} (copia)</h3>
        </div>
        <div class="pdf_datos">
            <p><b>Fecha:</b> {{fechaActual}}</p>
            <p><b>Origen:</b> <span>{{encomienda.origen}}</span></p>
            <p><b>Destino:</b> <span>{{encomienda.destino}}</span></p>

            <p><b>Entregado en:</b> <span>{{encomienda.destino}}</span></p>
            <p><b>Guia:</b> <span>{{encomienda.guia}}</span></p>

            <p><b>Remitente:</b> <span>{{encomienda.remitente}}</span></p>
            <p><b>Destinatario:</b> <span>{{encomienda.destinatario}}</span></p>

            <p><b>Recoje:</b> {{infoEntrega.receptor}}</p>
            <p><b>Documento:</b> {{ infoEntrega.carnet }}</p>
            <p><b>Telefono:</b> {{ encomienda.telefonoRemitente }}</p>

            <br>
            <table>
                <thead>
                <tr>
                    <th colspan="3"><b>DETALLE</b></th>
                </tr>
                <tr style="text-align: center;">
                    <th>Cant</th>
                    <th>Detalle</th>
                    <th>Mont</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item, i) in items">
                    <td style="text-align: center;">{{ item.cantidad }}</td>
                    <td>{{ item.detalle }}</td>
                    <td style="text-align: right;">{{ item.total }}</td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="4">{{observacion}}</td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: right;"><p><b>TOTAL</b></p></td>
                    <td style="text-align: right;"><p><b>{{ total }}</b></p></td>
                </tr>
                </tfoot>
            </table>
        </div>
        <div class="pdf_pie">
            <p class="pie_usuario">Usuario: <span>{{cabecera.usuario}}</span></p>
            <br><br><br>
            <p class="pie_firma">-----------------------------------------</p>
            <p class="pie_firma"><b>{{infoEntrega.receptor}}</b></p>
        </div>
        <br>
        <p style="text-align: center;">Mobius IT Solutions</p>
    </div>
</div>
