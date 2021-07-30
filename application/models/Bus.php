<?php

/**
 * ChoferBus
 *  
 * @author Administrador
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Bus extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'bus';

	public function getPropietario($bus){
		$db = $this->getAdapter ();
                $db->setFetchMode(Zend_Db::FETCH_OBJ);
		$select = $db->select ();
		$select->from ( array ('p' => 'persona' ), array ('id_persona','nombres','apellido_paterno','apellido_materno','identificador') );
		$select->joinInner(array ('pb' => 'persona_bus' ), 'pb.persona=p.id_persona',null );
		$select->where ( 'pb.bus=?', $bus);
		$results = $db->fetchRow( $select );
		return $results;
	}
}
