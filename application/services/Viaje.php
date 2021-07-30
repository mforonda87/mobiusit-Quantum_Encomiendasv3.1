<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of App_Rest_Server
 *
 * @author poloche
 */
class App_Rest_Viaje extends App_Rest_Server {

    public function itinerario($userid, $apikey, $fecha, $origen, $destino) {
        $viajeM = new App_Model_Viaje();
        $viajes = $viajeM->getItinerarioActual($fecha, $origen, $destino);
        $lista=array();
        foreach ($viajes as $viaje) {
            $disponibles = $viajeM->asientosDisponibles($viaje["id_viaje"]);
            $lista[$viaje["id_viaje"]]=array(
                "id"=>$viaje["id_viaje"],
                "hora"=>  $viaje["hora"],
                "interno"=>$viaje["numero"],
                "modelo"=>$viaje["descripcion"],
                "disponibles"=>$disponibles
            );
        }
        $listaViajes = array("viajes"=>$lista,"horaActual"=>date("H:i"));
        return App_Rest_Response::Generate($listaViajes, true);
    }

}
?>
