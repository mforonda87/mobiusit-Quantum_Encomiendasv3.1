<?php

/**
 * ChoferBus
 *  
 * @author Administrador
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ChoferViaje extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'chofer_viaje';

	public function getChoferesViaje($viaje){
		$db = $this->getAdapter ();
		$select = $db->select ();
		$select->from ( array ('ch' => 'chofer' ), array ('id_chofer','numero_licencia','nombre_chofer','fecha_exp','telefono','estado' ) );
		$select->joinInner(array ('cb' => 'chofer_viaje' ), 'ch.id_chofer=cb.chofer',array("(CASE WHEN cargo='C' THEN 'Ayudante' ELSE 'Chofer' END) AS cargo") );
		$select->where ( 'cb.viaje=?', $viaje);
		$select->where ( 'ch.estado=?', 'Activo' );
		$select->order('cb.cargo');
		$results = $db->fetchAssoc( $select );
		return $results;
	}
        
        public function insertTX($drivers, $travelId) {
            $db = $this->getAdapter ();
            $chofer = array("viaje"=>$travelId, "chofer"=>$drivers['chofer'], "cargo"=>"A");
            $this->insert($chofer);
            $relevo = array("viaje"=>$travelId, "chofer"=>$drivers['relevo'], "cargo"=>"B");
            $this->insert($relevo);
            $ayudante = array("viaje"=>$travelId, "chofer"=>$drivers['ayudante'], "cargo"=>"C");
            $this->insert($ayudante);
        }
}
