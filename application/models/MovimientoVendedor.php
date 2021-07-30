<?php

/**
 * AccionSubMenuModel
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_MovimientoVendedor extends Zend_Db_Table_Abstract {

    // The table name
    protected $_name = 'movimiento_vendedor';
    protected $_sequence = false;
    protected $_primary = 'id_movimiento';

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 28/07/2009
     */
    public function getByFechas($desde, $hasta) {
        $db = $this->getAdapter();
        //		$db->getFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('m' => 'movimiento_vendedor'), array('id_movimeinto', 'fecha', 'hora', 'ingreso', egreso, numero_factura, fecha_venta, hora_venta, estado));
        $select->joinInner(array('i' => 'item'), "i.id_item=a.item", array('numero'));
        $select->joinInner(array('pe' => 'persona'), "pe.id_persona=a.vendedor", array('nombres as vendedor'));
        $select->joinInner(array('s' => 'sucursal'), "s.id_sucursal=a.sucursal", array('nombre as sucursal'));
        $select->where("viaje=?", $viaje);
        $select->where("a.estado='Venta' or a.estado='Libre'");
        $select->order("numero");
        return $db->fetchAll($select);
    }

    /**
     * retorna el historial de una persona en la fecha especificada
     * con los siguientes datos :
     * array(
     *      hora    => Hora del movimiento
     *      ingreso => Monto de dinero que ingreso a caja
     *      egreso  => Monto de dinero que salio de caja
     *      Detalle => concepto del movimiento
     *      Codigo  => Numero de la factura que se emitio
     *      Asiento => Numero de asiento por el cual se genero el movimiento
     *      interno => Numero del bus del viaje
     *      hora viaje=> Hora en la que se realizo el viaje
     *      Maquina => nombre o ip de la maquina desde la que se realizo la transaccion
     * )
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function getByFechaVendedor($persona, $fecha) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('m' => 'movimiento_vendedor'), array('id_movimiento', 'hora', 'ingreso', detalle, egreso, codigo, asiento, interno, hora_viaje, pc, ip));
        $select->where("m.estado='Activo'");
        $select->where("m.vendedor='$persona'");
        $select->where("m.fecha='$fecha'");
        return $db->fetchAll($select);
    }

    /**
     * Funcion encargada de crear un movimiento de unvendedor para una encomienda
     *      
     * @param String  $vendedor
     * @param Date    $fechaEncomienda
     * @param Time    $horaEncomienda
     * @param String  $tipo
     * @param String  $codigo
     * @param Numeric $monto
     */
    function createMovimientoRecepcionEncomienda($vendedor, $fechaEncomienda, $horaEncomienda, $tipo, $codigo, $monto, $isManual) {
        $ingreso = 0;
        $egreso = 0;
        switch ($tipo) {
            case "NORMAL":
                $ingreso = $monto;
                $detalle = "RECEPCION $isManual ENCOMIENDA NORMAL";
                break;
            case "GIRO":
                $ingreso = $monto;
                $detalle = "RECEPCION $isManual GIRO";
                break;

            case "POR PAGAR":
                $ingreso = 0;
                $detalle = "RECEPCION $isManual ENCOMIENDA POR PAGAR";
                break;
            case "INTERNO":
                $ingreso = 0;
                $detalle = "RECEPCION $isManual SERVICIO INTERNO";
                break;
            default:
                break;
        }

        $dataMVIng ['id_movimiento'] = '-1';
        $dataMVIng ['vendedor'] = $vendedor;
        $dataMVIng ['fecha'] = $fechaEncomienda;
        $dataMVIng ['hora_viaje'] = $horaEncomienda;
        $dataMVIng ['ingreso'] = $ingreso;
        $dataMVIng ['egreso'] = $egreso;
        $dataMVIng ['detalle'] = $detalle;
        $dataMVIng ['hora'] = date('H:i:s');
        $dataMVIng ['ip'] = App_Util_Statics::getIp();
        $dataMVIng ['pc'] = 0;
        $dataMVIng ['codigo'] = $codigo;
        $dataMVIng ['fecha_operacion'] = date('Y-m-d');
        $dataMVIng ['estado'] = 'Activo';
        $this->insert($dataMVIng);
    }

    function createMovimientoAsignacion($vendedor, $fecha, $hora, $monto, $idManifiesto, $tipoMovimiento) {
        $ingreso = 0;
        $egreso = $monto;
        if ($tipoMovimiento == "Asignacion") {
            $ingreso = $monto;
            $egreso = 0;
        }
        $dataMVIng ['id_movimiento'] = '-1';
        $dataMVIng ['vendedor'] = $vendedor;
        $dataMVIng ['fecha'] = $fecha;
        $dataMVIng ['hora_viaje'] = $hora;
        $dataMVIng ['ingreso'] = $ingreso;
        $dataMVIng ['egreso'] = $egreso;
        $dataMVIng ['detalle'] = "$tipoMovimiento DE ENCOMIENDAS A MANIFIESTO $idManifiesto";
        $dataMVIng ['hora'] = date('H:i:s');
        $dataMVIng ['ip'] = App_Util_Statics::getIp();
        $dataMVIng ['pc'] = 0;
        $dataMVIng ['fecha_operacion'] = date('Y-m-d');
        $dataMVIng ['estado'] = 'Activo';
        $this->insert($dataMVIng);
    }

    function createMovimientoFacturacionCliente($vendedor, $fecha, $hora, $monto, $encomiendaList, $tipoMovimiento) {
        $ingreso = $monto;
        
        if ($tipoMovimiento != "Facturacion Cliente") {
            return null;
        }
        $dataMVIng ['id_movimiento'] = '-1';
        $dataMVIng ['vendedor'] = $vendedor;
        $dataMVIng ['fecha'] = $fecha;
        $dataMVIng ['hora_viaje'] = $hora;
        $dataMVIng ['ingreso'] = $ingreso;
        $dataMVIng ['egreso'] = 0;
        $dataMVIng ['detalle'] = "$tipoMovimiento encomiendas $encomiendaList";
        $dataMVIng ['hora'] = date('H:i:s');
        $dataMVIng ['ip'] = App_Util_Statics::getIp();
        $dataMVIng ['pc'] = 0;
        $dataMVIng ['fecha_operacion'] = date('Y-m-d');
        $dataMVIng ['estado'] = 'Activo';
        $this->insert($dataMVIng);
    }
}
