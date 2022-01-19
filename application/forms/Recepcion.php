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
class App_Form_Recepcion {

    var $sucursalesDestino;
    var $ciudadesDestino;
    var $baseUrl;

    public function __construct(array $destinos, array $ciudades, $baseUrl) {
        $this->sucursalesDestino = $destinos;
        $this->ciudadesDestino = $ciudades;
        $this->baseUrl = $baseUrl;
    }

    public function __toString() {
        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        $sucursalOrigen = strtoupper($session->sucursalName);
        $webServices = App_Util_Statics::$webService;
        $resp = '<form name="encomiendasForm" id="encomiendasForm" method="post" enctype="application/x-www-form-urlencoded" action="' . $this->baseUrl . '/recepcion/">
                  <div class="contentBox">
                    <label class="porciento49">
                        <span>Ciudad Origen</span>
                        ' . strtoupper($session->ciudadName) . '
                    </label>
                    <label class="porciento49">
                        <span>Sucursal Origen</span>
                        ' . $sucursalOrigen . '
                    </label>
                    <label class="porciento49">
                        <span>Ciudad Destino:</span>
                        <select name="ciudadDest" id="ciudadDest">';
        foreach ($this->ciudadesDestino as $id => $nombre) {
            $nombre = strtolower($nombre);
            $resp .= "<option value='" . $webServices["$nombre"]["ip"] . $webServices["route"] . "/id/" . base64_encode($id) . "'>" . strtoupper($nombre) . "</option>";
        }
        $resp .= '</select>
                    </label>
                    <label class="porciento49">
                        <span>Sucursal Destino:</span>
                        <select name="destinoSuc" id="destinoSuc">';
        foreach ($this->sucursalesDestino as $sucursal) {
            $resp .= "<option value='" . base64_encode($sucursal->id_sucursal) . "'>$sucursal->nombre</option>";
        }
        $resp .= '</select>
                    </label>
                  </div>  
                    <br/>
                  <div class="contentBox">
                    <label class="porciento49">
                        <span>Tipo........... :</span>
                        <select name="tipoEncomienda" id="tipoEncomienda">
                            <option value="' . base64_encode("NORMAL") . '">NORMAL</option>
                            <option value="' . base64_encode("POR PAGAR") . '">POR PAGAR</option>
                            <option value="' . base64_encode("INTERNO") . '">SERVICIO INTERNO</option>
                            <option value="' . base64_encode("GIRO") . '">GIRO</option>
                            <option value="' . base64_encode("Coorporativo") . '">Coorporativo</option>
                        </select>
                    </label>
                  </div>  
                    <br/>
                  <div class="contentBox">
                    ';
        $resp .= $this->clientRow();
        $resp .= '<label>
                        <span>Tipo Cliente:</span>
                        <span id="tipoCliente">Normal</span>
                    </label>';
        $resp .= '
                    <label class="left">
                        <span>Remitente. :</span>
                        <input type="text" id="remitente" name="remitente" maxlength="100" size="50"/>
                    </label>
                    <label>
                        <span>Telf:</span>
                        <input type="text" id="remitenteTelf" name="remitenteTelf" maxlength="10" size="10" class="numeric" value="0"/>
                    </label>
                    <label class="left">
                        <span>Destinatario:</span>
                        <input type="text" id="destinatario" name="destinatario" maxlength="100" size="50"/>
                        <a href="#" id="copyDestinatary">C</a>
                    </label>
                    <label>
                        <span>Telf:</span>
                        <input type="text" id="destinatarioTelf" name="destinatarioTelf" maxlength="10" size="10" class="numeric" value="0"/>
                    </label>
                  </div>  
                  <br/>
                  ';
        $resp .= $this->drawItemsForm();
        $resp .= $this->drawGiroForm();
        $resp .= '</form>';

        $resp .= '<br/>';
        $resp .= '<div class="contentBox functions center_align">
            <input id="registrarEncomienda" type="button" value="Guardar"/>
            <input id="registrarEncomiendaManual" type="button" value="Manual"/>
            <!--  <input id="extractoEncomienda" type="button" value="Extracto"/> -->
            <input id="resetEncomienda" type="button" value="Cancelar"/>
        </div>';
        return $resp;
    }

    private function drawGiroForm() {
        return '<div class="contentBox" id="itemsGiro">
                    <label class="left">
                        <span>Monto a Enviar:</span>
                        <input type="text" id="montoGiro" name="montoGiro" maxlength="4" size="10" class="numeric"/>
                    </label>
                    <label class="left">
                        <span>Detalle :</span>
                        <input type="text" id="detalleGiro" name="detalleGiro" maxlength="200" size="25" />
                    </label>
                    <label class="left">
                        <span>Total:</span>
                        <input type="text" id="totalGiro" name="totalGiro" maxlength="4" size="10" class="numeric"/>
                    </label>
                  </div>';
    }

    private function drawItemsForm() {
        $resp = '<div class="contentBox" id="itemsEncomienda">
                    <hr/>
                    <div id="items" class="center_align">
                        <div>
                            <span class="title1">Cantidad</span>
                            <span class="title2">Detalle</span>
                            <span class="title1">Peso/kg</span>
                            <span class="title1">Total</span>
                        </div>';
        $resp .= $this->drawItemRow();
        $resp .= $this->drawItemRow();
        $resp .= $this->drawItemRow();
        $resp .= $this->drawItemRow();

        $resp .= '<div class="row">
                            <div class="short"><a href="#"><img src="' . $this->baseUrl . '/images/217bc0_11x11_icon_plus.gif" alt="agregar" title="agregar item" /></a></div>
                            <div class="long"></div>
                            <div class="short">
                                <label for="total">Total</label>
                            </div>
                            <div class="short">
                                <input type="text" name="total" id="total" maxlength="5" size="5" class="numeric" readonly="true"/>
                            </div>
                        </div>
                    </div>
                  </div>';
        return $resp;
    }

    private function drawItemRow() {
        return '<div class="row">
                    <div class="short">
                        <input type="text" name="cantidad" maxlength="5" size="5" class="numeric"/>
                    </div>
                    <div class="long">
                         <input type="text" name="detalleEquipaje" maxlength="100" size="30"/>
                    </div>
                    <div class="short">
                         <input type="text" name="peso" maxlength="5" size="5" class="numeric"/>
                    </div>
                    <div class="short">
                          <input type="text" name="efectivo" maxlength="5" size="5" class="numeric"/>
                    </div>
                </div>';
    }

    public function clientRow() {
        return $this->inputFormRow("NIT cliente.", "50", "10", "nitCliente", "nitCliente", true);
    }

    private function inputFormRow($label, $size, $lenght, $id, $name, $isNumber, $rightComponent) {
        $classNumber ="";
        if($isNumber==true){
            $classNumber = 'class="numeric"';
        }
        
        return '<label class="left">
                        <span>' . $label . ' :</span>
                        <input type="text" id="' . $id . '" name="' . $name . '" maxlength="' . $lenght . '" size="' . $size . '" '.$classNumber.'/>
                    </label>';
    }

}

?>
