<?php

/**
 * CiudadModel
 *  
 * @author Administrador
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_CiudadModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'ciudad';
	
	public function getCiudadForDropDown() {
		
		return $this->_db->fetchPairs ( 
				"select id_ciudad, nombre from ciudad where estado='Activo'" );
	}
	public function getCiudadDestinoForDropDown($CiudadOrigen) {
		$db = $this->getAdapter ();
		$select = $db->select ();
		$select->from ( array ('cd' => 'ciudad' ), array ('cd.id_ciudad', 'cd.nombre' ) );
		$select->joinInner ( array ('d' => 'destino' ), 'cd.id_ciudad=d.llegada', null );
		$select->joinInner ( array ('co' => 'ciudad' ), 'co.id_ciudad=d.salida', null );
		$select->where ( 'co.id_ciudad=?', $CiudadOrigen);
		$select->order ( 'cd.nombre' );
		return $this->_db->fetchPairs ($select);
	}
	
	public function findByid($idCiudad){
            $db = $this->getAdapter ();
            $db->setFetchMode(Zend_Db::FETCH_ASSOC);
		$select = $db->select ();
		$select->from ( array ('c' => 'ciudad' ), array ("id_ciudad", "nombre", "estado", "abreviacion" ) );
		$select->where ( 'c.id_ciudad=?', $idCiudad);		
		return $db->fetchRow ($select);
//		return $this->_db->fetchRow( 
//				"select id_ciudad, nombre, estado, abreviacion from ciudad where id_ciudad='$idCiudad'" );
	}


}
