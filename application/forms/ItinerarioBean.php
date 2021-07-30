<?php

class App_Form_ItinerarioBean {
	
	/**
	 * 
	 * @var array<ListaViajeBean> $listaViajes los viajes que tiene el itinerario 
	 */
	private $listaViajes = array ();
	private $isActive = false;
	/**
	 * @return the $listaViajes
	 */
	public function getListaViajes() {
		return $this->listaViajes;
	}
	
	/**
	 * @param $listaViajes the $listaViajes to set
	 */
	public function setListaViajes($listaViajes) {
		$this->listaViajes = $listaViajes;
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
	 * @date creation 23/06/2009
	 */
	public function makeItinerario() {
		$clsMain = "";
		$clsHeader = "";
		$clsContent = "";
		if ($this->isActive) {
			$clsMain = "itinerarioBean ui-accordion ui-widget ui-helper-reset";
			$clsHeader = "ui-accordion-header ui-helper-reset ui-state-active ui-corner-top";
			$clsContent = "ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom";
		
		}
		$itinerario = "<div id='itinerarioList'>";
		foreach ( $this->getListaViajes () as $viaje ) {
                        $nombre = $viaje->getOrigen () . " - " . $viaje->getDestino ();
                        $destino = base64_encode($viaje->getIdCiudadDestino());
			$itinerario .= "<h3><a href='#' ciudad='".  $destino."'>" . $nombre ."</a></h3>";
			$itinerario .= "<div>" . $viaje->getViajesList () . "</div>";
		}
		$itinerario .= "</div>";
		return $itinerario;
	}
	/**
	 * @return unknown
	 */
	public function getIsActive() {
		return $this->isActive;
	}
	
	/**
	 * @param unknown_type $isActive
	 */
	public function setIsActive($isActive) {
		$this->isActive = $isActive;
	}

}

?>