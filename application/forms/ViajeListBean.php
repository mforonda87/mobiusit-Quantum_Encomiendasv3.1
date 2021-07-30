<?php

/**
 * Clase de ayuda para poder generar un acordion de viajes en la vista desde un array de
 * viajes que contiene los datos mas relevante para un viaje
 */
class App_Form_ViajeListBean {

    private $destino;
    private $origen;
    private $idCiudadDestino;
    private $viajes = array();
    private $salidas;
    private $idDestino;
    private $modelo;
    private $baseUrl;
    private $accionScript;
    private $accions;

    function __construct($baseUrl, $javascript, $actions) {
        $this->baseUrl = $baseUrl;
        $this->accionScript = $javascript;
        $this->accions = $actions;
    }

    /**
     * @return String Retorna el nombre de la ciudad de destino del viaje
     */
    public function getDestino() {
        return $this->destino;
    }

    /**
     * @return Array Retorna el array de viajes 
     */
    public function getViajes() {
        return $this->viajes;
    }

    /**
     * @param String $destino   Nombre de la ciudad de Destino del viaje
     */
    public function setDestino($destino) {
        $this->destino = $destino;
    }

    /**
     * @param unknown_type $viajes
     */
    public function setViajes($viajes) {
        $this->viajes = $viajes;
    }

    /**
     * <p>
     *  funcion que a�ade los datos de un viaje al array de viajes del bean
     *  para luego poder mostrar un acordion de viajes en la vista
     * </p>
     * @param String $idviaje  identificador del viaje
     * @param String $hora      hora de salida del viaje
     * @param String $interno   Numero interno del bus para el viaje
     * @param String $idDestino Identificador del destino al cual sale el viaje
     * @param String $ibus      Identificador del bus que esta saliendo de viaje
     */
    public function addViaje($idViaje, $hora, $interno, $idDestino, $bus, $modelo="", $idCiudadDestino="") {
        $viajeArray['id'] = $idViaje;
        $viajeArray['hora'] = $hora;
        $viajeArray['interno'] = $interno;
        $viajeArray['idDestino'] = $idDestino;
        $viajeArray['modelo'] = $modelo;
        $viajeArray['idCiudadDestino'] = $idCiudadDestino;
        $viajeArray['bus'] = $bus;
        $this->viajes[$idViaje] = $viajeArray;
    }

    /**
     * @return origen retorna el identificadro de la ciudad de origen de un viaje
     */
    public function getOrigen() {
        return $this->origen;
    }

    /**
     * @param String $origen    identificadro de la ciudad de origen de un viaje
     */
    public function setOrigen($origen) {
        $this->origen = $origen;
    }

    public function getViajesList() {

        $selectViajes = "<div class='jscrollpane'><ul class='select-acordion'>";
        foreach ($this->viajes as $viaje) {
            $hora = substr($viaje['hora'], 0, 5);
            $idViaje = base64_encode($viaje['id']);
            $interno = $viaje['interno'];
            $idDestino = base64_encode($viaje['idDestino']);
            $idBus = base64_encode($viaje['bus']);
            $internoConCero = App_Util_Statics::convertNumber($viaje['interno']);
            $modelo = $viaje["modelo"];
            $selectViajes.="<li>";
            $selectViajes.="   <a href='#' onclick='$this->accionScript(\"" . $idViaje . "\",\"" . $interno . "\",\"" . $hora . "\",\"" . $idDestino . "\",\"" . $idBus . "\");' title='$modelo'>" . $hora . " - [" . $internoConCero . "]</a>";
            foreach ($this->accions as $acc) {
                $selectViajes.="   <a href='#' onclick='" . $acc['action'] . "(\"" . $idViaje . "\",\"" . $idBus . "\");'>
                                             <img src='" . $this->baseUrl . $acc['icon'] . "' width='16px' height='16px' alt='" . $acc['nombre'] . "' title='" . $acc['titulo'] . "'/>
                                           </a>";
            }
            $selectViajes.="</li>";
        }
        $selectViajes.="</ul></div>";
        return $selectViajes;
    }

    /**
     * @return the $idDestino Identificador del destino 
     */
    public function getIdDestino() {
        return $this->idDestino;
    }

    /**
     * @param $idDestino the $idDestino to set
     */
    public function setIdDestino($idDestino) {
        $this->idDestino = $idDestino;
    }

    public function getModelo() {
        return $this->modelo;
    }

    public function setModelo($modelo) {
        $this->modelo = $modelo;
    }

    public function getIdCiudadDestino() {
        return $this->idCiudadDestino;
    }

    public function setIdCiudadDestino($idCiudaDestino) {
        $this->idCiudadDestino = $idCiudaDestino;
    }

}

?>