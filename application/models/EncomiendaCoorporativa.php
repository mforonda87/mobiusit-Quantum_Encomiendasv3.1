<?php

/**
 * CiudadModel
 *  
 * @author Administrador
 * @version 
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_EncomiendaCoorporativa extends Zend_Db_Table_Abstract {

    /**
     * The default table name 
     */
    protected $_name = 'encomienda_coorporativa';

    /**
     * Registra una nueva configuracion del sistema
     * 
     * 
     * @param array $encCoorp       datos de la Encomienda coorporativa     
     * @author Poloche
     * @version V1.1
     */
    public function insertTX($dataEnco, $nitCliente) {
        $log = Zend_Registry::get("log");
        try {
            $clienteModel = new App_Model_Cliente();
            $clienteModel->update(array('deuda' => new Zend_Db_Expr('deuda + ' . $dataEnco->total)), array("nit='" . $nitCliente . "'"));
            $cliente = $clienteModel->findByNIT($nitCliente);
            $coorporativa = array(
                "id_cobro_enc" => -1,
                "encomienda" => $dataEnco->id_encomienda,
                "guia" => $dataEnco->guia,
                "monto" => $dataEnco->total,
                "fecha_recepcion" => 'now()',
                "client" => $cliente->id,
                "usuario" => Zend_Registry::get(App_Util_Statics::$SESSION)->person->id_persona
            );
            $this->insert($coorporativa);
        } catch (Zend_Db_Exception $zdbe) {

            $log->err($zdbe);
            throw new Zend_Db_Exception("No se pudo guardar la Encomienda coorporativa(" . $dataEnco->id_encomienda . ") ", 125);
        }
    }

    public function getDebtsByClient($clientId) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('ec' => 'encomienda_coorporativa'), array('encomienda', 'guia', 'monto', 'fecha_recepcion'));
        $select->joinInner(array ('e' => 'encomienda' ), 'e.id_encomienda=ec.encomienda and e.factura is null',array('detalle'));
        $select->joinLeft(array('me'=>'movimiento_encomienda'), 
                "me.encomienda=e.id_encomienda and me.movimiento='".App_Util_Statics::$ESTADOS_ENCOMIENDA['Entrega']."'",
                'me.fecha as fecha_entrega');
        $select->where("ec.fecha_pago is null");
        $select->where("ec.client=?", $clientId);
        return $db->fetchAll($select);
    }

}
