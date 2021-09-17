<?php

/**
 * Bus
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_FacturaEncomienda extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'factura_encomienda';
    protected $_primary = 'id_factura';
    protected $_sequence = false;

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 17/06/2009
     */
    public function getEfectivoByFecha($fecha) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('f' => 'factura_equipaje'), array('sum(monto) as monto', 'vendedor'));
        $select->joinInner(array('p' => 'persona'), 'p.id_persona=f.vendedor', array('p.identificador'));
        $select->where("f.fecha=?", $fecha); //f.estado='Activo' AND
        $select->group(array("p.identificador", "f.vendedor"));
        $results = $db->fetchAll($select);

        return $results;
    }

    public function getDevolucionesByFecha($fecha) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('f' => 'factura_equipaje'), array('sum(monto) as monto', 'vendedor'));
        $select->joinInner(array('p' => 'persona'), 'p.id_persona=f.vendedor', array('p.identificador'));
        $select->where(" f.estado='Anulado' AND f.fecha=?", $fecha); //f.estado='Activo' AND
        $select->group(array("p.identificador", "f.vendedor"));
        $results = $db->fetchAll($select);

        return $results;
    }

    public function anularTX($valuesForm, $asientos) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $devolucionModel = new Devolucion ( );
            $moviModel = new MovimientoVendedorModel ( );
            $asiModel = new Asiento ( );
            $devData ['id_devolucion'] = 'nuevo';
            $devData ['vendio'] = $valuesForm ['vendio'];
            $devData ['devolvio'] = $valuesForm ['devolvio'];
            $devData ['viaje'] = $valuesForm ['viaje'];
            $devData ['nombre'] = $valuesForm ['nombre'];
            $devData ['nit'] = $valuesForm ['nit'];
            $devData ['hora_venta'] = $valuesForm ['hora_venta'];
            $devData ['fecha_venta'] = $valuesForm ['fecha_venta'];
            $devData ['hora'] = date('H:i:s');
            $fechaDev = $valuesForm ['fecha_venta'];
            if (!isset($valuesForm ['fecha_venta']))
                $fechaDev = date('Y-m-d');
            $devData ['fecha'] = $fechaDev;
            $devData ['monto'] = $valuesForm ['monto'];
            $devData ['numero_factura'] = $valuesForm ['numero_factura'];
            $devData ['factura'] = $valuesForm ['id_factura'];
            $devData ['responsable'] = PersonaModel::getIdentity()->id_persona;
            $devolucionModel->insert($devData);
            $ass = split(',', $asientos);

            $where1 [] = "factura='" . $valuesForm ['id_factura'] . "'";
            $modif ['estado'] = 'Vacante';
            $modif ['numero_factura'] = 0;
            $modif ['factura'] = null;
            $modif ['pasaje'] = 0;
            $asiModel->update($modif, $where1);

            $dataMVIng ['id_movimiento'] = 'nuevo';
            $dataMVIng ['vendedor'] = $valuesForm ['vendio'];
            $dataMVIng ['fecha'] = $valuesForm ['fecha_venta'];
            $dataMVIng ['hora_viaje'] = $valuesForm ['hora_venta'];
            $dataMVIng ['ingreso'] = 0;
            $dataMVIng ['egreso'] = $valuesForm ['monto'];
            $dataMVIng ['detalle'] = 'Anulacion de Factura (' . $valuesForm ['numero_factura'] . ') asientos (' .
                    $valuesForm ['asientos'] . ') por ' . PersonaModel::getIdentity()->nombres .
                    " Ultimo vendedor :" . $valuesForm ['vendio'];
            $dataMVIng ['hora'] = date('H:i:s');
            $dataMVIng ['asiento'] = $asiento->numero;
            $dataMVIng ['interno'] = 0;
            $dataMVIng ['ip'] = Statics::getIp();
            $dataMVIng ['pc'] = 0;
            $dataMVIng ['codigo'] = $valuesForm ['numero_factura'];
            $dataMVIng ['fecha_operacion'] = date('Y-m-d');
            $dataMVIng ['estado'] = 'Activo';
            //			$moviModel->insert ( $dataMVIng );
            if ($valuesForm ['vendio'] != $valuesForm ['devolvio']) {
                //				$dataMVIng ['id_movimiento'] = 'nuevo';
                $dataMVIng ['vendedor'] = $valuesForm ['devolvio'];
                //				$dataMVIng ['fecha'] = $valuesForm ['fecha_venta'];
                //				$dataMVIng ['hora_viaje'] = $valuesForm ['hora_venta'];
                //				$dataMVIng ['ingreso'] = 0;
                //				$dataMVIng ['egreso'] = $valuesForm ['monto'];
                //				$dataMVIng ['detalle'] = 'Edicion de Factura por ' . PersonaModel::getIdentity ()->nombres .
                //						 " Ultimo vendedor :" . $valuesForm ['oldVendedor'];
                //				$dataMVIng ['hora'] = date ( 'H:i:s' );
                //				$dataMVIng ['asiento'] = $asiento->numero;
                //				$dataMVIng ['interno'] = 0;
                //				$dataMVIng ['ip'] = Statics::getIp ();
                //				$dataMVIng ['pc'] = 0;
                //				$dataMVIng ['codigo'] = $valuesForm ['numero_factura'];
                //				$dataMVIng ['fecha_operacion'] = date ( 'Y-m-d' );
                //				$dataMVIng ['estado'] = 'Activo';
            }
            $moviModel->insert($dataMVIng);
            $where [] = "id_factura = '" . $valuesForm ['id_factura'] . "'";
            unset($valuesForm ['factura']);
            $anulada ['estado'] = 'Anulado';
            $this->update($anulada, $where);
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception("No se pudo Actualizar la factura", 125);
        }
    }

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 23/07/2009
     */
    public function getLast($whereFac) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from('factura');
        foreach ($whereFac as $value) {
            $select->where($value); //f.estado='Activo' AND
        }
        //		echo $select->__toString();
        return $db->fetchRow($select);
    }

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 23/07/2009
     */
    public function getAllByFechas($desde, $hasta) {

        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();

        $select->from(array('f' => 'factura_equipaje'), array("date_part('year',fecha) as year", "date_part('month',fecha) as mes",
            "date_part('day',fecha) as dia", nit, nombre, 'numero_factura',
            'df.autorizacion', "COALESCE(codigo_control,'-') as codigo_control",
            monto));
        $select->joinInner(array('df' => 'datos_factura'), 'df.id_datos_factura=f.dosificacion', null);
        $select->where("f.fecha  between '$desde' and '$hasta'"); //f.estado='Activo' AND
        $results = $db->fetchAll($select);
        $facturas = array();
        foreach ($results as $res) {
            $res [ice] = 0;
            $res [exento] = 0;
            $res [importe] = $res ['monto'];
            $res [iva] = $res ['monto'] * 0.13;
            $facturas [] = $res;
        }
        return $facturas;
    }

    /**
     * Recupera la informacion de la factura,
     * todos los datos de los asientos relacionados a esta factura,
     * en base al id del asiento y que el estado='Venta',
     * ademas del estado de la factura f.estado='Activo'
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 23/06/2009
     */
    public function getAllByFactura($factura) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array("f" => "factura_equipaje"), array("id_factura", "nombre", "monto", "nit", "fecha", "dosificacion", "numero_factura", "hora",
            "fecha_viaje", "codigo_control", "vendedor"));
        $select->join(array("a" => "asiento"), "a.factura=f.id_factura and f.estado='Activo' and a.estado='Venta'", array("id_asiento", "pasaje", "nombre", "nit"));
        $select->join(array("i" => "item"), "i.id_item=a.item ", array("i.numero"));
        $select->where("f.id_factura=?", $factura); //f.estado='Activo' AND
        //		echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 23/06/2009
     */
    public function getById($fatura) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();
        $select->from(array(f => "factura_equipaje"), array(id_factura, viaje, vendedor, dosificacion, nit, fecha, nombre, monto,
            hora, numero_factura, texto_factura, asientos, fecha_viaje, hora_viaje,
            destino, numero_bus, modelo, codigo_control, fecha_limite, tipo));
        $select->where("f.estado='Activo' AND f.id_factura=?", $fatura); //f.estado='Activo' AND
        //		echo $select->__toString();
        return $db->fetchRow($select);
    }

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 11/08/2009
     */
    public function anular2TX($datosFactura, $asientos) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $devolucionModel = new Devolucion ( );
            $moviModel = new MovimientoVendedorModel ( );
            $asiModel = new Asiento ( );
            $devData ['id_devolucion'] = 'nuevo';
            $devData ['vendio'] = $datosFactura ['vendedor'];
            $devData ['devolvio'] = $datosFactura ['devolvio'];
            $devData ['viaje'] = $datosFactura ['viaje'];
            $devData ['nombre'] = $datosFactura ['nombre'];
            $devData ['nit'] = $datosFactura ['nit'];
            $devData ['hora_venta'] = $datosFactura ['hora'];
            $devData ['fecha_venta'] = $datosFactura ['fecha'];
            $devData ['hora'] = date('H:i:s');
            $devData ['fecha'] = date('Y-m-d');
            $devData ['monto'] = $datosFactura ['monto'];
            $devData ['numero_factura'] = $datosFactura ['numero_factura'];
            $devData ['factura'] = $datosFactura ['id_factura'];
            $devData ['responsable'] = PersonaModel::getIdentity()->id_persona;
            $devolucionModel->insert($devData);
            $ass = split(',', $asientos);

            $where1 [] = "factura='" . $datosFactura ['id_factura'] . "'";
            $modif ['estado'] = 'Vacante';
            $modif ['numero_factura'] = 0;
            $modif ['factura'] = null;
            $modif ['pasaje'] = 0;
            $asiModel->update($modif, $where1);

            $dataMVIng ['id_movimiento'] = 'nuevo';
            $dataMVIng ['vendedor'] = $datosFactura ['vendio'];
            $dataMVIng ['fecha'] = $datosFactura ['fecha_venta'];
            $dataMVIng ['hora_viaje'] = $datosFactura ['hora_venta'];
            $dataMVIng ['ingreso'] = 0;
            $dataMVIng ['egreso'] = $datosFactura ['monto'];
            $dataMVIng ['detalle'] = 'Anulacion de Factura (' . $datosFactura ['numero_factura'] .
                    ') asientos (' . $datosFactura ['asientos'] . ') por ' . PersonaModel::getIdentity()->nombres .
                    " Ultimo vendedor :" . $datosFactura ['vendio'];
            $dataMVIng ['hora'] = date('H:i:s');
            $dataMVIng ['asiento'] = $asiento->numero;
            $dataMVIng ['interno'] = 0;
            $dataMVIng ['ip'] = Statics::getIp();
            $dataMVIng ['pc'] = 0;
            $dataMVIng ['codigo'] = $datosFactura ['numero_factura'];
            $dataMVIng ['fecha_operacion'] = date('Y-m-d');
            $dataMVIng ['estado'] = 'Activo';
            //			$moviModel->insert ( $dataMVIng );
            if ($datosFactura ['vendio'] != $datosFactura ['devolvio']) {
                //				$dataMVIng ['id_movimiento'] = 'nuevo';
                $dataMVIng ['vendedor'] = $datosFactura ['devolvio'];
                //				$dataMVIng ['fecha'] = $valuesForm ['fecha_venta'];
                //				$dataMVIng ['hora_viaje'] = $valuesForm ['hora_venta'];
                //				$dataMVIng ['ingreso'] = 0;
                //				$dataMVIng ['egreso'] = $valuesForm ['monto'];
                //				$dataMVIng ['detalle'] = 'Edicion de Factura por ' . PersonaModel::getIdentity ()->nombres .
                //						 " Ultimo vendedor :" . $valuesForm ['oldVendedor'];
                //				$dataMVIng ['hora'] = date ( 'H:i:s' );
                //				$dataMVIng ['asiento'] = $asiento->numero;
                //				$dataMVIng ['interno'] = 0;
                //				$dataMVIng ['ip'] = Statics::getIp ();
                //				$dataMVIng ['pc'] = 0;
                //				$dataMVIng ['codigo'] = $valuesForm ['numero_factura'];
                //				$dataMVIng ['fecha_operacion'] = date ( 'Y-m-d' );
                //				$dataMVIng ['estado'] = 'Activo';
            }
            $moviModel->insert($dataMVIng);
            $where [] = "id_factura = '" . $datosFactura ['id_factura'] . "'";
            unset($datosFactura ['factura']);
            $anulada ['estado'] = 'Anulado';
            $this->update($anulada, $where);
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception("No se pudo Actualizar la factura", 125);
        }
    }

    /**
     * Actualiza el campo dosificacion en la tabla factura por la nueva
     * dosificacion enviada como parametro
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 24/08/2009
     * @param array $facturas array que contiene los identificadores de
     * 							las facturas que seran actualizadas
     * @param String $dosificacion  identificador de la nueva dosificacion a la cual se cambiara la factura
     */
    public function cambiarDosificacion($facturas, $dosificacion) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $errorFactura = "";
            foreach ($facturas as $df) {
                $xx = split("_x_", $df);
                $factura = $xx [0];
                $numF = $xx [1];
                $where1 [] = "numero_factura='$numF'";
                $where1 [] = "dosificacion='$dosificacion'";
                $existe = $this->fetchRow($where1);
                unset($where1);
                $consultas = 0;
                if (!isset($existe)) {
                    $dataFactura ['dosificacion'] = $dosificacion;
                    $where [] = "id_factura='$factura'";
                    $res = $this->update($dataFactura, $where);
                    $consultas++;
                    unset($dataFactura);
                    unset($where);
                    if ($res > 0)
                        $band = true;
                    else {
                        $band = false;
                        $errorFactura .= $factura . "  error " . $res;
                        break;
                    }
                    $resultado [conerror] = false;
                } else {
                    $resultado [duplicada] .= "," . $numF;
                    $resultado [conerror] = true;
                }
            }if
            ($consultas > 0) {
                if ($band) {
                    $db->commit();
                } else {
                    $db->rollBack();
                    throw new Zend_Db_Exception(
                    "No se pudo cambiar de Dosificacion a la factura (" . $errorFactura . ")", 125);
                }
            }
        } catch (Zend_Db_Exception $zdbe) {
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception(
            "No se pudo cambiar de Dosificacion a las facturas" . $errorFactura, 125);
        }
        return $resultado;
    }

    /**
     * Recupera las facturas que no se imprimieron en una fecha y las lista
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function getByNoPrintedByDate($fecha) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();
        $select->from(array(f => factura), array(id_factura, viaje, vendedor, dosificacion, nit, fecha, nombre, monto,
            hora, numero_factura, texto_factura, asientos, fecha_viaje, hora_viaje,
            destino, numero_bus, modelo, codigo_control, fecha_limite, tipo));
        $select->joinInner(array(p => persona), "p.id_persona=f.vendedor", array(nombres));

        $select->where("f.estado='Activo' AND f.impresion=0"); //f.estado='Activo' AND
        //		echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * Recupera los datos de los pasajeros y los asientos vendidos con una factura
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function getByIdAsientosForPrint($factura) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();
        $select->from(array(f => factura), array("a.nombre", "a.nit", "i.numero"));
        $select->joinInner(array(a => asiento), "a.factura=f.id_factura AND f.id_factura='$factura'", null);
        $select->joinInner(array(i => item), "i.id_item=a.item", array(numero));
        $select->where("f.estado='Activo' AND a.estado='Venta' and f.impresion<1"); //f.estado='Activo' AND
        //		echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * Recupera los datos del viaje y la factura
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function getByIdForPrint($factura) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array(f => factura), array(monto, numero_factura, nombre, nit, fecha, codigo_control, fecha_limite, fecha_viaje, hora_viaje, numero_bus, modelo));
        $select->joinInner(array(df => datos_factura), "df.id_datos_factura=f.dosificacion", array(autorizacion, autoimpresor));
        $select->joinInner(array(v => viaje), "v.id_viaje=f.viaje", array(carril, numero_salida));
        $select->joinInner(array(p => persona), "p.id_persona=f.vendedor", array(nombres));
        $select->joinInner(array(s => sucursal), "s.id_sucursal=p.sucursal", array(direccion, direccion2, "numero as numeroSucursal", telefono));
        $select->joinInner(array(d => destino), "d.id_destino=v.destino", null);
        $select->joinInner(array(co => ciudad), "co.id_ciudad=d.salida", array("nombre as origen"));
        $select->joinInner(array(cd => ciudad), "cd.id_ciudad=d.llegada", array("nombre as destino"));
        $select->where("f.estado='Activo' AND f.id_factura=?", $factura); //f.estado='Activo' AND
        //		echo $select->__toString();
        return $db->fetchRow($select);
    }

    /**
     * Recupera la sumatoria de las facturas vendidad por un
     * vendedor en una fecha especificada sin tomar en cuenta si la factura
     * ha sido 'Anulado' o esta 'Activa'
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.0
     * @date 26/01/2010
     */
    function getByVendedorFecha($fecha, $idPersona) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array("f" => "factura_encomienda"), array("COALESCE(SUM(monto),0) AS monto"));
        $select->where("fecha=?", $fecha); //f.fecha='fecha' AND
        $select->where("vendedor=?", $idPersona);
//        $select->where ( "estado=?", "Vendido" ); //f.estado='Activo' OR estado='Anulado'
//        		echo $select->__toString();
        return $db->fetchOne($select);
    }

    /**
     * Recupera la informacion de todas las facturas vendidad por un
     * vendedor en una fecha especificada sin tomar en cuenta si la factura
     * ha sido 'Anulado' o esta 'Activa'
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.0
     * @date 26/01/2010
     */
    function getByAllVendedorFecha($fecha, $idPersona) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
                
        $select = $db->select();

//        $select->from(array("f" => "factura_encomienda"), array("monto AS total", nit, nombre, estado, numero_factura,tipo,fecha,vendedor));
//        $select->join(array(e=>encomienda), "e.factura=f.id_factura",array("id_encomienda","guia","is_porpagar_entregada"));
//        $select->joinLeft(array("m"=>"movimiento_encomienda"), "e.id_encomienda=m.encomienda AND movimiento='Inactivo'",array("usuario AS idDevolvio","fecha as fechaDevolucion"));
//        $select->joinLeft(array("p"=>"persona"), "m.usuario=p.id_persona",array("p.nombres as devolvio"));
//        $select->joinLeft(array("vend"=>"persona"), "f.vendedor=vend.id_persona",array("vend.nombres as vendio"));

//        $select->order("f.tipo");
//        $select->order("f.fecha");
//        $select->order("e.guia");
//        $select->order("numero_factura");
//
//        $select->where("(f.fecha='$fecha' OR m.fecha='$fecha')");
//        $select->where("vendedor=?", $idPersona);

//        $select->from(array("f" => "factura_encomienda"), array("monto AS total", 'nit', 'nombre', 'estado', 'numero_factura','tipo','fecha','vendedor'));
        $select->from(array('e'=>'encomienda'), array("id_encomienda","guia","is_porpagar_entregada", "total"));
//        $select->joinLeft(array("m"=>"movimiento_encomienda"), "e.id_encomienda=m.encomienda AND movimiento='Inactivo'",array("usuario AS idDevolvio","fecha as fechaDevolucion", "usuario as m_usuario"));
        $select->joinLeft(array("m"=>"movimiento_encomienda"), "e.id_encomienda=m.encomienda",array("usuario AS idDevolvio","fecha as fechaDevolucion", "usuario as m_usuario"));
        $select->joinLeft(array("p"=>"persona"), "m.usuario=p.id_persona",array("p.nombres as devolvio"));
        $select->joinLeft(array("vend"=>"persona"), "e.receptor=vend.id_persona",array("vend.nombres as vendio"));

//        $select->order("e.fecha");
//        $select->order("e.guia");
//
        $select->where("m.fecha='$fecha'");
//        $select->where("m.usuario=?", $idPersona);

        return $db->fetchAll($select);
    }
    
    /**
     * Recupera la informacion de todas las facturas 
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.0
     * @date 26/01/2010
     */
    function getAll() {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array("f" => "factura_encomienda"), array("monto AS total", nit, nombre, estado, numero_factura,tipo,fecha,vendedor));
        $select->join(array(e=>encomienda), "e.factura=f.id_factura",array("id_encomienda"));
        $select->joinLeft(array("m"=>"movimiento_encomienda"), "e.id_encomienda=m.encomienda AND movimiento='Inactivo'",array("usuario AS idDevolvio","fecha as fechaDevolucion"));
        $select->joinLeft(array("p"=>"persona"), "m.usuario=p.id_persona",array("p.nombres as devolvio"));
        $select->joinLeft(array("vend"=>"persona"), "f.vendedor=vend.id_persona",array("vend.nombres as vendio"));
        
        $select->order("f.tipo");
        $select->order("f.fecha");
        $select->order("numero_factura");
        
        return $db->fetchAll($select);
    }

    /**
     * Recupera la sumatoria de las facturas anuladas por un
     * vendedor en una fecha especificada 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.0
     * @date 26/01/2010
     */
    function getDevolucionesByVendedorFecha($fecha, $idPersona) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array("f" => "factura_encomienda"), array("COALESCE(SUM(monto),0) AS monto"));
        $select->where("fecha=?", $fecha); //f.fecha='fecha' AND
        $select->where("vendedor=?", $idPersona);
        $select->where("estado=?", "Anulado"); //f.estado='Activo' OR estado='Anulado'
//        		echo $select->__toString();
        return $db->fetchOne($select);
    }

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 25-03-2010
     */
    function getDevolucionByVendedorFecha($fecha, $idPersona) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array("f" => "factura_equipaje"), array("COALESCE(SUM(monto),0) AS monto"));
        $select->where("fecha=?", $fecha); //f.fecha='fecha' AND
        $select->where("vendedor=?", $idPersona);
        $select->where("estado=?", "Anulado"); //f.estado='Activo' OR estado='Anulado'
//        		echo $select->__toString();
        return $db->fetchOne($select);
    }

}
