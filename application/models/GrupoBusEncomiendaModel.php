<?php

/**
 * GrupoBus
 *  
 * @author Administrador
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class GrupoBusEncomiendaModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'grupoencomienda_bus';
	protected $_sequence = false;
	
	public function getIdGrupoForBus($idBus) {
		$db = $this->getAdapter ();
		$select = $db->select ();
		$select->from ( array ('gp' => 'grupoencomienda_bus' ), array ('grupoe' ) );
		$select->where ( 'gp.bus=?', $idBus );
		$results = $db->fetchOne ( $select );
		return $results;
	}
	
	public function getAllGrupoForBus() {
		$db = $this->getAdapter ();
		$select = $db->select ();
		$select->from ( array ('gp' => 'grupoencomienda_bus' ), array ('grupoe' ) );
		$results = $db->fetchOne ( $select );
		return $results;
	}
	
	public function updateGrupoTX($datosBus) {
		$db = $this->getAdapter ();
		$db->beginTransaction ();
		try {
			$hoy = date ( 'Y-m-d' );
			$where [] = "bus='" . $datosBus ['id_bus'] . "'";
			$data ['estado'] = 'Baja';
			$data ['fecha'] = $hoy;
			$upd1 = $this->update ( $data, $where );
//			if ($upd1 == 0) {
//				throw new Zend_Db_Exception (
//						" No se pudo dar de Baja el anterior grupo[" . $datosBus ['grupo'] . "] del Bus[" .
//								 $datosBus ['id_bus'] . "]  ", 125 );
//			}
			unset($datosBus ['ultimoGrupo']);
			$insertGrupoBus ['id_grupoe_bus'] = 'nuevo';
			$insertGrupoBus ['grupoe'] = $datosBus ['grupoe'];
			$insertGrupoBus ['bus'] = $datosBus ['id_bus'];
			$insertGrupoBus ['estado'] = "Activo";
			$insertGrupoBus ['fecha'] = $hoy;
			$this->insert ( $insertGrupoBus );
			$db->commit ();
		
		} catch ( Zend_Db_Exception $zdbe ) {
			$db->rollBack ();
			Initializer::log_error ( $zdbe );
			throw new Zend_Db_Exception ( "No se pudo actualizar el grupo del Bus  ", 125 );
		}
	}
}
