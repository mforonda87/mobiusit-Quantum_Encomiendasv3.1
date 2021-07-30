<?php
/**
 * clase que genera el bus con los asientos y los pinta de acuerdo a su estado
 * 
 */
class App_Util_ModelBean {
    private $bus;
    private $interno;
    private $viaje = null;
    private $libres = 0;
    private $vendidos = 0;
    private $reservados = 0;
    private $vacantes = 0;
    private $numeroAsientos;
    private $fechaViaje;
    private $horaViaje;
    private $forViaje;
    private $modelo;
    private $pasajeros = array();
    private $equipajes = array();
    function __construct($bus) {
        $this->bus = $bus;
    }
    /**
     *..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 29/05/2009
     */
    public function makeModel() {
        $itemModel = new App_Model_ItemModel ( );
        if (is_null ( $this->viaje )) {
            $items = $itemModel->getItemsByBus ( $this->bus );
        } else {
//        echo $this->viaje.", ".$this->interno ;
            $items = $itemModel->getItemsByViajeBus ( $this->viaje, $this->interno );
            $act = current ( $items );
            foreach ($act as $datosViajeItems) {
                if($datosViajeItems["id_bus"]!="") {
                    $newAct = $datosViajeItems;
                    break;
                }
            }
            $this->setBus ( $newAct ['id_bus'] );
            $this->setFechaViaje ( $act [0] ['fecha'] );
            $this->setHoraViaje ( $act [0] ['hora'] );
        }
        $rowsCols = $itemModel->getRowsColsByBus ( $this->bus );
        return $this->drawModel ( $rowsCols, $items );
    }
    /**
     *..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 29/05/2009
     */
    public function drawModel($rowCols, $items) {
        $columnas = $rowCols ['columnas'];
        $filas = $rowCols ['filas'];
        $model = "<div class='model'><table id='tableForModel' ><a href='#' id='helperFocus'>|</a>";
        $anteriorNum = 0;
        //		print_r($items);
        for($i = 0; $i <= $filas; $i ++) {
            $model .= "<tr>";
            for($j = 0; $j <= $columnas; $j ++) {
                if(isset ($items [$i] [$j])) {
                    $item = $items [$i] [$j];
                    if($item["descripcion"]!="") {
                        $this->setModelo($item["descripcion"]);
                    }
                    if($item ['interno']!="") {
                        $this->setInterno ( $item ['interno'] );
                    }
                    //				print_r ( $item );
                    $nombreTipo = $item ['nombre'];
                    if ($item ['numero'] > $anteriorNum) {
                        $this->setNumeroAsientos ( $item ['numero'] );
                        $anteriorNum = $item ['numero'];
                    }
                    $precioView = "";
                    switch ($nombreTipo) {
                        case "Asiento" :
                            switch ($item ['estado']) {
                                case "Venta" :
                                    $vendidoConEq="venta";
                                    if($item['equipaje']!=""){
                                        $this->equipajes[]=array('detalle'=>$item['equipaje'],'numero'=>$item['nro_ticket'],'peso'=>$item['peso']);
                                        $vendidoConEq=" vendido";
                                    }
                                    $class = 'item '.$vendidoConEq;
                                    $this->addVendidos ();
                                    $this->pasajeros[]=array('nombre'=>$item['pasajero'],'nit'=>$item['nit'],'numero'=>$item['numero'],"asiento"=>$item["idAsiento"]);
                                    $precioView ="<span style='font-size:8px;padding-bottom:1px;'>" . round($item ['pasaje'],0) . "</span>";
                                    break;
                                case "Libre" :
                                    $class = 'item libre';
                                    $this->addLibres ();
                                    $this->pasajeros[]=array('nombre'=>$item['pasajero'],"nit"=>$item['nit'],"numero"=>$item['numero'],"asiento"=>$item["idAsiento"]);
                                    break;
                                case "Reserva" :
                                    $class = 'item reserva';
                                    $this->addReservados ();
                                    break;
                                default :
                                    $class = 'item vacante';
                                    $this->addVacantes ();
                                    break;
                            }
                            $content = "<span>" . $item ['numero'] . "</span>";
                            $id = $item['idAsiento'];
                            $title = "";
                            if($this->isForViaje()) {
                                $content ="<span>" . $item ['numero'] . "</span><br/>";//<br>".$item['pasaje']."
                                $content .=$precioView;
                                $content .="<input type='hidden' name='equipaje' id='equipaje' value='".base64_encode($item['id_equipaje'])."'/>";
                                $title = "title='Datos-de-Venta //<span>Vendedor : ".$item["vendedor"]." </span><br/><span>Precio : ".$item["pasaje"]."</span>'";
                            }
                            $model .= "<td id='$id' class='$class' $title>$content</td>";
                            break;
                        case "Televisor" :
                            $model .= "<td class='item televisor'></td>";
                            break;
                        case "Direccion" :
                            $model .= "<td class='item direccion'></td>";
                            break;
                        case "Entrada" :
                            $model .= "<td class='item entrada'></td>";
                            break;
                        case "Primer Piso" :
                            $model .= "<td class='item piso'></td>";
                            break;
                        default :
                            $model .= "<td> </td>";
                            break;
                    }
                } else {
                    $model .= "<td> </td>";
                }
            }
            $model .= "</tr>";
        }
        //$model .= "<tr><td class='item'></td></tr>";
        $model .= "</table></div>";
        return $model;
    }

    /**
     *..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 02/06/2009
     */
    public function getChoferes() {
        $choferBus = new App_Model_ChoferBus ( );
        $choferes = $choferBus->getChoferesViaje ( $this->getViaje() );
        return $choferes;
    }

    /**
     *..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 02/06/2009
     */
    public function getPropietario() {
        $personaModel = new PersonaModel ( );
        $propietario = $personaModel->getPropietarioByBus ( $this->bus );
        return $propietario;
    }

    /**
     * @return unknown
     */
    public function getBus() {
        return $this->bus;
    }

    /**
     * @return unknown
     */
    public function getInterno() {
        return $this->interno;
    }

    /**
     * @return unknown
     */
    public function getNumeroAsientos() {
        return $this->numeroAsientos;
    }

    /**
     * @param unknown_type $bus
     */
    public function setBus($bus) {
        $this->bus = $bus;
    }

    /**
     * @param unknown_type $interno
     */
    public function setInterno($interno) {
        $this->interno = $interno;
    }

    /**
     * @param unknown_type $numeroAsientos
     */
    public function setNumeroAsientos($numeroAsientos) {
        $this->numeroAsientos = $numeroAsientos;
    }
    /**
     * @return unknown
     */
    public function getViaje() {
        return $this->viaje;
    }

    /**
     * @param unknown_type $viaje
     */
    public function setViaje($viaje) {
        $this->viaje = $viaje;
    }
    /**
     * @return unknown
     */
    public function getModelo() {
        return $this->modelo;
    }

    /**
     * @param unknown_type $viaje
     */
    public function setModelo($modelo) {
        $this->modelo = $modelo;
    }
    /**
     * @return unknown
     */
    public function getLibres() {
        return $this->libres;
    }

    /**
     * @return unknown
     */
    public function getReservados() {
        return $this->reservados;
    }

    /**
     * @return unknown
     */
    public function getVacantes() {
        return $this->vacantes;
    }

    /**
     * @return unknown
     */
    public function getVendidos() {
        return $this->vendidos;
    }

    /**
     * @param unknown_type $libres
     */
    public function setLibres($libres) {
        $this->libres = $libres;
    }

    /**
     * @param unknown_type $reservados
     */
    public function setReservados($reservados) {
        $this->reservados = $reservados;
    }

    /**
     * @param unknown_type $vacantes
     */
    public function setVacantes($vacantes) {
        $this->vacantes = $vacantes;
    }

    /**
     * @param unknown_type $vendidos
     */
    public function setVendidos($vendidos) {
        $this->vendidos = $vendidos;
    }

    /**
     * @param unknown_type $libres
     */
    public function addLibres($libres = 1) {
        $this->libres += $libres;
    }

    /**
     * @param unknown_type $reservados
     */
    public function addReservados($reservados = 1) {
        $this->reservados += $reservados;
    }

    /**
     * @param unknown_type $vacantes
     */
    public function addVacantes($vacantes = 1) {
        $this->vacantes += $vacantes;
    }

    /**
     * @param unknown_type $vendidos
     */
    public function addVendidos($vendidos = 1) {
        $this->vendidos += $vendidos;
    }
    /**
     * @return unknown
     */
    public function getFechaViaje() {
        return $this->fechaViaje;
    }

    /**
     * @return unknown
     */
    public function getHoraViaje() {
        return $this->horaViaje;
    }

    /**
     * @param unknown_type $fechaViaje
     */
    public function setFechaViaje($fechaViaje) {
        $this->fechaViaje = $fechaViaje;
    }

    /**
     * @param unknown_type $horaViaje
     */
    public function setHoraViaje($horaViaje) {
        $this->horaViaje = $horaViaje;
    }
    /**
     * @return the $forViaje
     */
    public function isForViaje() {
        return $this->forViaje;
    }

    /**
     * @param boolean $forViaje the $forViaje to set
     */
    public function setForViaje($forViaje) {
        $this->forViaje = $forViaje;
    }

    /**
     *..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 06/07/2009
     */
    public function getListPasajeros () {
        $table = "<table class='borders reporte' align='center'>";
        $table .= "<thead><tr><th>No.</th><th>Nombre</th><th>NIT</th></tr></thead>";
        $table .= "<tbody>";
        $i=0;
        uasort($this->pasajeros, array($this,"compare"));
        foreach ($this->pasajeros as $pasajero) {
            $clsTR = $i % 2 == 0 ? "par" : "impar";
            $clsTD = $i % 2 == 0 ? "alt" : "";
            $table.="<tr class='$clsTR'>"	;
            $table.=" <td class='$clsTD'>".$pasajero['numero']."</td>"	;
            $table.=" <td class='$clsTD' asiento='".$pasajero['asiento']."'>".$pasajero['nombre']."</td>"	;
            $table.=" <td class='$clsTD' style='width:47px;' asiento='".$pasajero['asiento']."'>".$pasajero['nit']."</td>"	;
            $table.="</tr>"	;
            $i++;
        }
        $table .= "</tbody>";
        $table .= "<tr>";
        $table .= "</tr>";
        $table .= "</table>";
        return $table;
    }

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 16-03-2010
     */
    public function getListEquipajes() {
        $table = "<table class='borders reporte' align='center'>";
        $table .= "<thead><tr><th>Ticket</th><th>Detalle</th><th>Peso</th></tr></thead>";
        $table .= "<tbody>";
        $i=0;
        uasort($this->equipajes, array($this,"compare"));
        foreach ($this->equipajes as $equipaje) {
            $clsTR = $i % 2 == 0 ? "par" : "impar";
            $clsTD = $i % 2 == 0 ? "alt" : "";
            $table.="<tr class='$clsTR'>"	;
            $table.=" <td class='$clsTD'>".$equipaje['numero']."</td>"	;
            $table.=" <td class='$clsTD' >".$equipaje['detalle']."</td>"	;
            $table.=" <td class='$clsTD' style='width:47px;' >".$equipaje['peso']."</td>"	;
            $table.="</tr>"	;
            $i++;
        }
        $table .= "</tbody>";
        $table .= "<tr>";
        $table .= "</tr>";
        $table .= "</table>";
        return $table;
    }

    function compare($a,$b) {
        if ($a['numero']>$b['numero']) {
            return 1;
        } elseif ($a['numero']<$b['numero']) {
            return -1;
        } else {
            return 0;
        }
    }

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function getDatosViaje() {
        $datosBus = '<div class="datosViaje">
    <table class="reporte">
        <tr>
            <th class="ui-widget-header" colspan="2">Datos del Bus</th>
        </tr>
        <tr class="par">
            <td>Interno :</td>
            <td>' . App_Util_Statics::convertNumber ( $this->getInterno () ) . '</td>
        </tr>
        <tr class="impar">
            <td>Asientos :</td>
            <td>' . $this->getNumeroAsientos () . '</td>
        </tr>
        <tr class="par"><td colspan="2" style="font-size:14px;text-align:center;font-weight:bold;">'.$this->modelo.'</td></tr>
        <tr>
            <th class="ui-widget-header" colspan="2">Datos del Viaje</th>
        </tr>
        <tr class="impar" style="font-size:14px;text-align:center;font-weight:bold;">
            <td colspan="2">' . $this->getHoraViaje () . '</td>
        </tr>
        <tr class="par">
            <td>Fecha :</td>
            <td>' . $this->getFechaViaje () . '</td>
        </tr>

        <tr class="par">
            <td class="imgData imgVenta">Vendidos :</td>
            <td>' . $this->getVendidos () . '</td>
        </tr>

        <tr class="impar">
            <td class="imgData imgLibre">Libres :</td>
            <td>' . $this->getLibres () . '</td>
        </tr>

        <tr class="par">
            <td class="imgData imgReserva">Reservas :</td>
            <td>' . $this->getReservados () . '</td>
        </tr>

        <tr class="impar">
            <td class="imgData imgVacante">Vacantes :</td>
            <td>' . $this->getVacantes () . '</td>
        </tr>
        <tr>
            <th class="ui-widget-header" colspan="2">Choferes</th>
        </tr>';

        $i = 0;
        $choferes = $this->getChoferes ();
        foreach ( $choferes as $chofer ) {
            $clsTR = $i % 2 == 0 ? "par" : "impar";
            $clsTD = $i % 2 == 0 ? "alt" : "";
            $datosBus .= "<tr class='$clsTR'><td class='$clsTD'>" . $chofer ['nombre_chofer'] . " </td>";
            if ($chofer ['numero_licencia'] != "ASISTENTE")
                $datosBus .= "<td class='$clsTD'>" . $chofer ['numero_licencia'] . "</td>";
            $datosBus .= "</tr>";
            $i ++;
        }
        $datosBus .= "</table></div>";
        return $datosBus;
    }
}

?>