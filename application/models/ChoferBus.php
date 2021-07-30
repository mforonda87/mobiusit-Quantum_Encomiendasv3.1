<?php

/**
 * ChoferBus
 *  
 * @author Administrador
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ChoferBus extends Zend_Db_Table_Abstract {
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
}
