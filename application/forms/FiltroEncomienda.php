<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of App_Form_Recepcion
 *
 * @author poloche
 */
class App_Form_FiltroEncomienda {

    var $ciudadesDestino;
    var $encomiendas;

    public function __construct(array $ciudades, array $encomiendas) {
        $this->ciudadesDestino = $ciudades;
        $this->encomiendas = $encomiendas;
    }

    public function __toString() {
        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        $sucursalOrigen = $session->sucursalName;
        $destinos = "<select name='filtroCiudadDest' id='filtroCiudadDest'>";
        foreach ($this->ciudadesDestino as $id => $nombre) {
            $destinos.="<option value='" . base64_encode($id) . "'>" . $nombre . "</option>";
        }
        $destinos.="</select>";
        $choferes = "<select name='choferViaje' id='choferViaje'><option value='000'>No Seleccionado</option></select>";
        $listaEncomiendas = "<table id='listaEncomiendas'>";
        $listaEncomiendas .= "<tr><th><input id='todos' type='checkbox'></th><th>Guia</th><th>Detalle</th></tr>";
        $dest = '';
        foreach ($this->encomiendas as $encomienda) {
            if ($dest != $encomienda->destino) {
                $listaEncomiendas.="<tr><th colspan='3'>$encomienda->destino</th></tr>";
                $dest = $encomienda->destino;
            }
            $listaEncomiendas.="<tr>
                <td><input type='checkbox' value='" . base64_encode($encomienda->id_encomienda)."'></td>
                <td>$encomienda->guia</td>
                <td>$encomienda->detalle</td>
                </tr>";
        }
        $listaEncomiendas.="</table>";
        $resp = '<form name="filtroForm" id="filtroForm" method="post" enctype="application/x-www-form-urlencoded" action="#">
                    <label>
                        <span>Destino</span>
                        ' . $destinos . '
                    </label>
                    <label>
                        <span>chofer</span>
                        ' . $choferes . '
                    </label>
                    <label>
                        <input id="listarEncomienda" type="button" value="Buscar"/>
                    </label>
                    <div id="contenidoEncomiendas">' . $listaEncomiendas;

        $resp .='   </div>
                </form>';
        return $resp;
    }

}
?>
