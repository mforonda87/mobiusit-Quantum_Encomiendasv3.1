<?php

/**
 * Asiento
 *
 * @author Administrador
 * @version
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Asiento extends Zend_Db_Table_Abstract {
/**
 * The default table name
 */
    protected $_name = 'asiento';
    public function getAsientos($id_viaje) {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ('id_asiento' ) );
        $select->joinInner ( array ('i' => 'item' ), ' a.item=i.id_item', array ('numero' ) );
        $select->joinInner ( array ('v' => 'viaje' ), ' a.viaje=v.id_viaje', null );

        $select->where ( "v.estado='Activo' AND a.estado='Vacante' AND v.id_viaje=?", $id_viaje );
        $select->order ( numero );
        $results = $db->fetchPairs ( $select );
        return $results;
    }

    public function getAsientoVacante($id_asiento) {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ('estado' ) );

        $select->where ( "a.estado='Vacante' AND a.id_asiento=?", $id_asiento );
        $results = $db->fetchOne ( $select );
        return $results;
    }

    public function insertTX($idAsiento, $idFactura, $montoFac, $viaje) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        $movimientoVM = new MovimientoVendedorModel ( );
        try {
            $factura = new Factura ( );
            $where [] = "id_factura = '$idFactura'";
            $factura->update ( array ('monto' => $montoFac ), $where );
            $datosAsiento = array ('estado' => 'Vacante' );
            $whereA [] = "id_asiento='$idAsiento'";
            $this->update ( $datosAsiento, $whereA );

            $datosAsiento = array ('factura' => null, 'numero_factura' => null );
            $this->update ( $datosAsiento, $whereA );

            $dataMVIng ['id_movimiento'] = 'nuevo';
            $dataMVIng ['vendedor'] = $viaje ['vendedor'];
            $dataMVIng ['fecha'] = $viaje ['f_salida'];
            $dataMVIng ['hora_viaje'] = $viaje ['hora_sal'];
            $dataMVIng ['ingreso'] = 0;
            $dataMVIng ['egreso'] = $viaje ['precioAsiento'];
            $dataMVIng ['detalle'] = 'Retirado al Editar Factura por ' . PersonaModel::getIdentity ()->nombres;
            $dataMVIng ['hora'] = date ( 'H:i:s' );
            $dataMVIng ['asiento'] = $viaje ['numeroAsiento'];
            $dataMVIng ['interno'] = $viaje ['n_bus'];
            $dataMVIng ['ip'] = Statics::getIp ();
            $dataMVIng ['pc'] = 0;
            $dataMVIng ['codigo'] = $viaje ['numero_factura'];
            $dataMVIng ['fecha_operacion'] = date ( 'Y-m-d' );
            $dataMVIng ['estado'] = 'Activo';
            $movimientoVM->insert ( $dataMVIng );

            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe ) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            throw new Zend_Db_Exception ( "No se pudo Quitar Asiento ", 125 );
        }
    }

    public function insertCambioAsiento($idAsiento, $idAsientoNuevo, $nombre, $nit, $idFactura, $montoFactura, $precioAsiento, $numeroFactura, $viaje) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        $movimientoVM = new MovimientoVendedorModel ( );
        try {
            $asiento = new Asiento ( );
            /**
             * Cambiar datos al asiento antiguo.
             */
            if ($idAsiento != 'nuevo') {
                $whereAsientoDeshabilitar [] = "id_asiento = '$idAsiento'";
                $asiento->update ( array ('estado' => 'Vacante', 'nit' => '"', 'nombre' => '"', 'pasaje' => 0, 'factura' => null, numero_factura => 0 ), $whereAsientoDeshabilitar );
            }
            /**
             * Cambiar datos al asiento nuevo.
             */

            $whereAsientoNuevo [] = "id_asiento = '$idAsientoNuevo'";
            $asiento->update ( array ('estado' => 'Venta', 'nombre' => $nombre, 'nit' => $nit, 'pasaje' => $precioAsiento, 'factura' => $idFactura, numero_factura => $numeroFactura, 'vendedor' => $viaje ['vendedor'] ), $whereAsientoNuevo );
            /**
             * Cambiar Factura
             */
            $factura = new Factura ( );
            $where [] = "id_factura = '$idFactura'";
            $factura->update ( array ('monto' => $montoFactura ), $where );
            /**
             * Cambiar los presios de asientos a todas los asientos
             */
            $whereAsientoActualizarPrecio [] = "factura = '$idFactura'";
            $asiento->update ( array ('pasaje' => $precioAsiento ), $whereAsientoActualizarPrecio );

            /**
             * Registrar el movimiento del vendedor en caso de que se este registrando un nuevo asiento
             */
            if ($idAsiento != $idAsientoNuevo) {
                $dataMVIng ['id_movimiento'] = 'nuevo';
                $dataMVIng ['vendedor'] = $viaje ['vendedor'];
                $dataMVIng ['fecha'] = $viaje ['f_salida'];
                $dataMVIng ['hora_viaje'] = $viaje ['hora_sal'];
                $dataMVIng ['ingreso'] = $precioAsiento;
                $dataMVIng ['egreso'] = 0;
                $dataMVIng ['detalle'] = 'Agragado al Editar Factura por ' . PersonaModel::getIdentity ()->nombres;
                $dataMVIng ['hora'] = date ( 'H:i:s' );
                $dataMVIng ['asiento'] = $viaje ['numeroAsiento'];
                $dataMVIng ['interno'] = $viaje ['n_bus'];
                $dataMVIng ['ip'] = Statics::getIp ();
                $dataMVIng ['pc'] = 0;
                $dataMVIng ['codigo'] = $viaje ['numero_factura'];
                $dataMVIng ['fecha_operacion'] = date ( 'Y-m-d' );
                $dataMVIng ['estado'] = 'Activo';
                $movimientoVM->insert ( $dataMVIng );
            }
            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe ) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            throw new Zend_Db_Exception ( "No se pudo Registrar el nuevo Asiento ", 125 );
        }
    }

    public function getAsientobyFactura($id_factura) {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ('count(*)' ) );
        $select->where ( "a.estado='Venta' AND a.factura=?", $id_factura );
        $results = $db->fetchOne ( $select );
        return $results;
    }

    public function getAsientosByFactura($id_factura) {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ('id_asiento' ) );
        $select->join ( array ('i' => 'item' ), "a.item=i.id_item",array ('numero' ) );
        $select->joinLeft ( array ('e' => 'equipaje' ), "e.asiento=a.id_asiento",array ('id_equipaje', 'detalle','COALESCE(nro_ticket,0) as ticket','COALESCE(peso,0) as peso' ) );
        $select->where ( "a.estado='Venta' AND a.factura=?", $id_factura );
        $results = $db->fetchAll ( $select );
        return $results;
    }
    public function updatePresioAsiento($idFactura, $presioAsiento) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        try {

            $datosAsiento = array ('pasaje' => $presioAsiento );
            $whereA [] = "factura='$idFactura'";
            $this->update ( $datosAsiento, $whereA );

            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe ) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            throw new Zend_Db_Exception ( "No se pudo Quitar Asiento ", 125 );
        }
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
    public function getById($idAsiento) {
        $db = $this->getAdapter ();
        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ('id_asiento', "nombre", "nit", "factura" ) );
        $select->joinInner ( array ('i' => 'item' ), ' a.item=i.id_item', array ('numero', 'posicion_x' ) );
        $select->joinInner ( array ('f' => 'factura' ), ' f.id_factura=a.factura', array ('nombre', 'nit' ) );

        $select->where ( "a.id_asiento=?", $idAsiento );
        $results = $db->fetchRow ( $select );
        return $results;
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
     * @date creation 20/07/2009
     */
    public function saveReserva($params) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        $asientos = array ();
        try {
            $idVendedor = Globals::getSession()->__get("person")->id_persona;
            foreach ( $params as $asiento ) {
                $datos = split ( ',', $asiento );
                $datosAsiento = array ('estado' => 'Reserva', 'nombre' => $datos [2], 'nit' => $datos [3], 'vendedor' => $idVendedor );
                $whereA [] = "id_asiento='" . $datos [0] . "'";
                $asientos [] = $datos [0];
                $resp = $this->update ( $datosAsiento, $whereA );
                if ($resp == 0) {
                    throw new Zend_Db_Exception ( "No se pudo actualizar el asiento" );
                }
                unset ( $whereA );
            }

            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe ) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            throw new Zend_Db_Exception ( "No se pudo Guardar La reserva ", 125 );
            $asientos = array ();
        }
        $result ['asientos'] = $asientos;
        return $result;
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
     * @date creation 20/07/2009
     */
    public function saveLibre($params) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        $asientos = array ();
        try {
            $idVendedor = Globals::getSession()->__get("person")->id_persona;
            foreach ( $params as $asiento ) {
                $datos = split ( ',', $asiento );
                $datosAsiento = array ('estado' => 'Libre', 'nombre' => $datos [2], 'nit' => $datos [3], 'vendedor' => $idVendedor );
                $whereA [] = "id_asiento='" . $datos [0] . "'";
                $asientos [] = $datos [0];
                $resp = $this->update ( $datosAsiento, $whereA );
                if ($resp == 0) {
                    throw new Zend_Db_Exception ( "No se pudo actualizar el asiento" );
                }
                unset ( $whereA );
            }

            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe ) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            throw new Zend_Db_Exception ( "No se pudo Guardar el pasaje Libre", 125 );
            $asientos = array ();
        }
        $result ['asientos'] = $asientos;
        return $result;
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
     * @date creation 23/07/2009
     */
    public function saveVenta($asientosVenta, $datosFac, $precioVenta, $nit, $viaje,$ciudadDestino="",$imprime=false) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        $asientos = array ();
        $movimientoVModel = new MovimientoVendedorModel ( );
        $facturaModel = new Factura ( );
        $dosificacionModel = new DosificacionModel ( );
        $userSession = Globals::getSession()->__get("person");
        $dosificacion = $dosificacionModel->getLastAutomaticoBySucursal ($userSession->sucursal);
        print_r($dosificacion);
        $hoy = date ( 'Y-m-d' );
        if (! isset ( $dosificacion )) {
            throw new Zend_Db_Exception ( "No existe una dosificacion Activa" );
        }
        if ($dosificacion ['fecha_limite'] < $hoy) {
            throw new Zend_Db_Exception ( " La Fecha limite de facturacion ha expirado por favor registre una nueva dosificacion" );
        }
        try {
            $datosFactJava=array();
            $idVendedor = $userSession->id_persona;
            $totalFactura = count ( $asientosVenta ) * $precioVenta;
            $asientosNumero = "";

            //Guardamos la factura
            $numeroFactura = $dosificacion ['numero_factura'];
            $datosFac [id_factura] = 'nuevo';
            $datosFac [viaje] = $viaje;
            $datosFac [vendedor] = $idVendedor;
            $datosFac [dosificacion] = $dosificacion ['id_datos_factura'];
            $datosFac [nit] = $nit;
            $datosFac [fecha] = $hoy;
            $datosFac [hora] = date ( 'H:i:s' );
            $datosFac [monto] = $totalFactura;
            $datosFac [numero_factura] = $numeroFactura;
            $datosFac [texto_factura] = 'Finalizado';
            $datosFac [asientos] = $asientosNumero;
            $facturacionBean = new Facturacion ( );
            $datosFac [codigo_control] = $facturacionBean->generarCodigoControl ( $dosificacion ['autorizacion'], $numeroFactura, $hoy, $totalFactura, $dosificacion ['llave'], $nit );
            $datosFac [fecha_limite] = $dosificacion ['fecha_limite'];
            $datosFac [tipo] = 'Automatico';
            $datosFac [estado] = 'Activo';
            $copiasFact = 0;
            if($imprime==true) {
                $copiasFact = 1;
            }
            $datosFac [impresion] = $copiasFact;

            print_r ( $datosFac );
            
            $facturaModel->insert ( $datosFac );
//            $whereFac [] = "tipo='Automatico'";
//            $whereFac [] = "viaje='$viaje'";
//            $whereFac [] = "fecha='$hoy'";
//            $whereFac [] = "numero_factura='$numeroFactura'";
//            $whereFac [] = "dosificacion='".$dosificacion ['id_datos_factura']."'";
            $whereFac [] = "numero_factura='$numeroFactura'";
            $whereFac [] = "dosificacion='".$dosificacion ['id_datos_factura']."'";
            $ultimaFactura = $facturaModel->getLast ( $whereFac );

            if (! isset ( $ultimaFactura )) {
                    throw new Zend_Db_Exception ( " No se Pudo Recuperar la Ultima Factura " );
            }

            $pasajeros = array();
            foreach ( $asientosVenta as $idAsiento=>$asiento ) {
                $datos = split ( ',', $asiento );
                $datosAsiento = array ('estado' => 'Venta', 'nombre' => strtoupper($datos [2]), 'nit' => $datos [3], 'vendedor' => $idVendedor, 'pasaje' => $precioVenta, 'factura' => $ultimaFactura->id_factura, numero_factura => $ultimaFactura->numero_factura );
                $whereA [] = "id_asiento='" . $idAsiento . "'";
                $asientos [] = $datos [0];
                $res = $this->update ( $datosAsiento, $whereA );
                //$totalFactura += $precioVenta;
                $numAsiento = $datos [1];
                $pasajeros[]=array(numero=>$numAsiento,pasajero=>$datos[2],nit=>$datos [3]);
                $asientosNumero .= $numAsiento . " ";
                $dataMovimVend [id_movimiento] = 'nuevo';
                $dataMovimVend [vendedor] = $idVendedor;
                $dataMovimVend [fecha] = $hoy;
                $dataMovimVend [hora] = date ( 'H:i:s' );
                $dataMovimVend [ingreso] = $precioVenta;
                $dataMovimVend [egreso] = 0;
                $dataMovimVend [detalle] = "Venta de Pasaje - $ciudadDestino";
                $dataMovimVend [hora_viaje] = $datosFac ['hora_viaje'];
                $dataMovimVend [asiento] = $numAsiento;
                $dataMovimVend [codigo] = $ultimaFactura->numero_factura;
                $dataMovimVend [interno] = $datosFac ['numero_bus'];
                $dataMovimVend [estado] = 'Activo';
                $dataMovimVend [ip] = Statics::getIp ();
                $dataMovimVend [pc] = gethostbyaddr ( Statics::getIp () );
                $movimientoVModel->insert ( $dataMovimVend );
                if ($res == 0) {
                    throw new Zend_Db_Exception ( "No se pudo actualizar el asiento" );
                }
                unset ( $whereA );
                unset ( $dataMovimVend );
            }
            $sucursalModel = new SucursalModel ( );
            $print = new PrintUtility ( );
            $ciudad = Statics::$SISTEM_DEFAULTS [Ciudad_Sistema];
            $suc = $sucursalModel->getById ( $userSession->sucursal );
//                        print_r($suc);
            $recibo = $print->printFacturaTermica ( $suc->numero, $dosificacion [autoimpresor],
                $suc->telefono, $suc->direccion, $ciudad,
                $ultimaFactura->numero_factura, $dosificacion ['autorizacion'],
                $totalFactura, $dosificacion ['fecha_limite'],
                $ultimaFactura->codigo_control, $datosFac [nombre], $nit,$pasajeros );
            $viajeModel = new Viaje();
            $dViaje=$viajeModel->findByIdAllData($viaje);
            $dViaje=$dViaje[0];
            $monto = number_format ( $totalFactura, 2, '.', '' );
        list ( $entera, $centavos ) = explode ( '.', $totalFactura );
        $centavos = $centavos==""?"00":$centavos;
        $centavos = "$centavos/100";
        $totalLiteral = $print->num2letras($totalFactura)." ".$centavos." ".Statics::$moneda;
            //print_r($dViaje);
            $datosFactJava=array(numeroSuc=>$suc->numero,telf=>$suc->telefono,impresor=>$dosificacion [autoimpresor],
                dir=>$suc->direccion,dir2=>$suc->direccion2,ciudad=>$ciudad,nitE=>Statics::$nitEmpresa,empresa=>Statics::$nombreEmpresa." ".Statics::$nombreEmpresa2,
                numFac=>$ultimaFactura->numero_factura,autorizacion=>$dosificacion ['autorizacion'],
                fecha=>$hoy,nombFact=>$datosFac [nombre],nitFact=>$nit,total=>$totalFactura,
                literal=>$totalLiteral,control=>$ultimaFactura->codigo_control,fechaLimite=>$dosificacion ['fecha_limite'],
                usuario=>$userSession->nombres,destino=>"La paz",fechaViaje=>$dViaje->fecha,
                horaViaje=>$datosFac ['hora_viaje'],salida=>$dViaje->numero_salida,carril=>$dViaje->carril,modelo=>$dViaje->descripcion);
            //print_r($datosFactJava);
            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            throw new Zend_Db_Exception ( "No se pudo registrar la Venta de pasajes", 125 );
            $asientos = array();
        }
        $result ['asientos'] = $asientos;
        $result ['impresion'] = $recibo;
        $result[java]=$datosFactJava;
        $result[pasajeros]=$pasajeros;
        return $result;
    }

    public function getDatosViaje($viaje, $interno) {
        $db = $this->getAdapter ();
        //		$db->getFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ('id_asiento as idAsiento', 'estado', 'pasaje', 'nombre as pasajero', nit, numero_factura, fecha_venta, hora_venta, estado ) );
        $select->joinInner ( array ('i' => 'item' ), "i.id_item=a.item", array ('numero' ) );
        $select->joinInner ( array ('pe' => 'persona' ), "pe.id_persona=a.vendedor", array ('nombres as vendedor' ) );
        $select->joinInner ( array ('s' => 'sucursal' ), "s.id_sucursal=a.sucursal", array ('nombre as sucursal' ) );
        $select->where ( "viaje=?", $viaje );
        $select->where ( "a.estado='Venta' or a.estado='Libre'" );
        $select->order ( "numero" );
        return $db->fetchAll ( $select );
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
     * @date creation 30/07/2009
     */
    public function getDetalleByVendedorFecha($vendedor, $fecha) {
        $db = $this->getAdapter ();
        $db->getFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ('b.numero', 'count(a.pasaje) as cuantos', 'a.pasaje', 'sum(a.pasaje) as total' ) );
        $select->joinInner ( array ('v' => 'viaje' ), "v.id_viaje=a.viaje and a.vendedor='$vendedor'", null );
        $select->joinInner ( array ('b' => 'bus' ), "b.id_bus=v.bus", null );
        $select->where ( "fecha_venta=?", $fecha );
        $select->where ( "a.estado='Venta' or a.estado='Libre'" );
        $select->group ( array ("b.numero", "a.pasaje" ) );
        return $db->fetchAll ( $select );
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
     * @date creation 11/08/2009
     */
    public function anularLibre($asiento, $horaViaje, $interno, $numeroAsiento) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        $movimientoVM = new MovimientoVendedorModel ( );
        try {
            $datosAsiento = array ('estado' => 'Vacante' );
            $whereA [] = "id_asiento='$asiento'";
            $this->update ( $datosAsiento, $whereA );

            $dataMVIng ['id_movimiento'] = 'nuevo';
            $dataMVIng ['vendedor'] = Globals::getSession()->__get("person")->id_persona;
            $dataMVIng ['fecha'] = date ( 'Y-m-d' );
            $dataMVIng ['hora_viaje'] = $horaViaje;
            $dataMVIng ['ingreso'] = 0;
            $dataMVIng ['egreso'] = 0;
            $dataMVIng ['detalle'] = 'Anulacion asiento Libre ';
            $dataMVIng ['hora'] = date ( 'H:i:s' );
            $dataMVIng ['asiento'] = $numeroAsiento;
            $dataMVIng ['interno'] = $interno;
            $dataMVIng ['ip'] = Statics::getIp ();
            $dataMVIng ['pc'] = 0;
            $dataMVIng ['fecha_operacion'] = date ( 'Y-m-d' );
            $dataMVIng ['estado'] = 'Activo';
            $movimientoVM->insert ( $dataMVIng );

            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe ) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            throw new Zend_Db_Exception ( "No se pudo anular el Asiento Libre ", 125 );
        }
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
    public function saveVentaManual($asientosVenta, $datosFac, $precioVenta, $nit, $viaje,$dosificacion) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        $asientos = array ();
        $movimientoVModel = new MovimientoVendedorModel ( );
        $facturaModel = new Factura ( );
        $hoy = date ( 'Y-m-d' );
        if (! isset ( $dosificacion )) {
            throw new Zend_Db_Exception ( "No existe una dosificacion Activa" );
        }
        if ($dosificacion->fecha_limite < $hoy) {
            throw new Zend_Db_Exception ( " La Fecha limite de facturacion ha expirado por favor registre una nueva dosificacion" );
        }
        try {

            $idVendedor = Globals::getSession()->__get("person")->id_persona;
            $totalFactura = count ( $asientosVenta ) * $precioVenta;
            $asientosNumero = "";

            //Guardamos la factura
            $numeroFactura = $datosFac [numero_factura];
            $datosFac [id_factura] = 'nuevo';
            $datosFac [viaje] = $viaje;
            $datosFac [vendedor] = $idVendedor;
            $datosFac [dosificacion] = $dosificacion->id_datos_factura;
            $datosFac [nit] = $nit;
            //			$datosFac [fecha] = $hoy;
            $datosFac [hora] = date ( 'H:i:s' );
            $datosFac [monto] = $totalFactura;
            //			$datosFac [numero_factura] = $numeroFactura;
            $datosFac [texto_factura] = 'Factura Manual '.$numeroFactura;
            $datosFac [asientos] = $asientosNumero;
            $datosFac [fecha_limite] = $dosificacion ->fecha_limite;
            $datosFac [tipo] = 'Manual';
            $datosFac [estado] = 'Activo';
            //						print_r ( $datosFac );
            $facturaModel->insert ( $datosFac );
            $whereFac [] = "tipo='Manual'";
            $whereFac [] = "viaje='$viaje'";
            $whereFac [] = "fecha='".$datosFac[fecha]."'";
            $whereFac [] = "numero_factura='$numeroFactura'";
            $ultimaFactura = $facturaModel->getLast ( $whereFac );

            if (! isset ( $ultimaFactura )) {
                if ($dosificacion ['fecha_limite'] < $hoy) {
                    throw new Zend_Db_Exception ( " No se Pudo Recuperar la Ultima Factura " );
                }
            }
            foreach ( $asientosVenta as $asiento ) {
                $datos = split ( ',', $asiento );
                $datosAsiento = array ('estado' => 'Venta', 'nombre' => $datos [2], 'nit' => $datos [3], 'vendedor' => $idVendedor, 'pasaje' => $precioVenta, 'factura' => $ultimaFactura->id_factura, numero_factura => $ultimaFactura->numero_factura );
                $whereA [] = "id_asiento='" . $datos [0] . "'";
                $asientos [] = $datos [0];
                $res = $this->update ( $datosAsiento, $whereA );
                //$totalFactura += $precioVenta;
                $numAsiento = $datos [1];
                $asientosNumero .= $numAsiento . " ";
                $dataMovimVend [id_movimiento] = 'nuevo';
                $dataMovimVend [vendedor] = $idVendedor;
                $dataMovimVend [fecha] = $hoy;
                $dataMovimVend [hora] = date ( 'H:i:s' );
                $dataMovimVend [ingreso] = $precioVenta;
                $dataMovimVend [egreso] = 0;
                $dataMovimVend [detalle] = 'Venta de Pasaje - ';
                $dataMovimVend [hora_viaje] = $datosFac ['hora_viaje'];
                $dataMovimVend [asiento] = $numAsiento;
                $dataMovimVend [codigo] = $ultimaFactura->numero_factura;
                $dataMovimVend [interno] = $datosFac ['numero_bus'];
                $dataMovimVend [estado] = 'Activo';
                $dataMovimVend [ip] = Statics::getIp ();
                $dataMovimVend [pc] = gethostbyaddr ( Statics::getIp () );
                $movimientoVModel->insert ( $dataMovimVend );
                if ($res == 0) {
                    throw new Zend_Db_Exception ( "No se pudo actualizar el asiento" );
                }
                unset ( $whereA );
                unset ( $dataMovimVend );
            }
            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe ) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            $mensaje="No se pudo registrar la Venta de pasajes";
            if(strstr ( $zdbe->getMessage(), "Unique violation" )==true && strstr ( $zdbe->getMessage(), "factura_dosificacion_key" )==true) {
                $mensaje = "La Factura ya fue registrada or favor verifique";
            }
            throw new Zend_Db_Exception ( $mensaje, 125 );
            $asientos = array();
        }
        $result ['asientos'] = $asientos;
        $result ['impresion'] = null;
        return $result;
    }
    /**
     *  funcion que devuele la sumatoria de la venta de asientos
     * @param date $fecha    la fecha en la que se requiere el monto de total de venta de asientos
     * @param $idPersona    el identificador de la persona para la cual se filtra la venta de asientos
     */
    public function getAllVentaByFechaPersona($fecha,$idPersona) {
        $db = $this->getAdapter ();
        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ('sum(pasaje) as monto' ) );
        $select->where ( "a.fecha_venta=?", $fecha ); //f.estado='Activo' AND
        $select->where ( "a.vendedor=?", $idPersona ); //f.estado='Activo' AND
        $select->where ( "a.estado=?", "Venta" ); //f.estado='Activo' AND
        $results = $db->fetchOne ( $select );
        return $results;
    }


    /**
     * Retorna la lista de pasajeros con los datos de
     *  hora : hora del viaje
     *  fecha : fecha del viaje
     *  bus    : interno del bus en el que viajo
     *  destino : ciudad de desrtino
     *  asiento : Numero del asiento en el que viajo
     *  nombre : nombre del pasajero
     *  ci     : carnet de identidad del pasajero
     *  pasaje : precio del pasaje por asiento
     *  factura : numero de la factura emitida para el(los) asiento(s)
     *
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function getPasajerosFecha($fecha) {
        $db = $this->getAdapter ();
        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array (nombre,nit,numero_factura,pasaje ) );
        $select->join( array ('v' => 'viaje' ), "v.id_viaje=a.viaje",array (fecha,hora) );
        $select->join( array ('b' => 'bus' ), "b.id_bus=v.bus",array (numero) );
        $select->join( array ('d' => 'destino' ), "d.id_destino=v.destino",null );
        $select->join( array ('c' => 'ciudad' ), "c.id_ciudad=d.llegada",array ("nombre as destino") );
        $select->join( array ('i' => 'item' ), "i.id_item=a.item",array ("numero as asiento") );
        $select->where ( "a.fecha_venta=?", $fecha ); //f.estado='Activo' AND
        $select->where ( "a.estado=?", "Venta" ); //f.estado='Activo' AND
        $select->order(array(h=>"hora",b=>numero,i=>numero,a=>nombre,a=>nit )); //f.estado='Activo' AND
        $results = $db->fetchAll ( $select );
        return $results;
    }


    /**
     * Recupera toda la lista de pasajeros segun un viaje
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function getPasajerosByViaje($viaje) {
        $db = $this->getAdapter ();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array (nombre,nit ) );
        $select->join( array ('i' => 'item' ), "i.id_item=a.item",array ("numero as asiento") );
        $select->where ( "a.viaje=?",$viaje );
        $select->where ( 'a.estado=?', 'Venta' );
        $select->order (array(i=>numero));
        //		echo $select->__toString();
        $results = $db->fetchAll ( $select );
        return $results;
    }

    /**
     * metodo que reguistra el movimiento de asientos de un viaje a otro
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 20/08/2009
     */
    function moverAsiento($numAsientoOrg,$numAsientoDest,$asientoO,$asientoD,$factura) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        $hoy = date ( 'Y-m-d' );
        $movimientoVModel = new MovimientoVendedorModel();
        $idVendedor = Globals::getSession()->__get("person")->id_persona;
        $asientoOrigen[factura]=null;
        $asientoOrigen[numero_factura]=0;
        $asientoOrigen[estado]="Vacante";
        $where[]="id_asiento='$asientoO'";
        $resp = $this->update($asientoOrigen, $where);
        if($resp<=0) {
            throw new Zend_Db_Exception ( " No se pudo dar de baja el asiento " );
        }
        $asientoDestino[factura]=$factura->id_factura;
        $asientoDestino[numero_factura]=$factura->numero_factura;
        $asientoDestino[pasaje]=$factura->pasaje;
        $asientoDestino[nombre]=$factura->nombre;
        $asientoDestino[nit]=$factura->carnet;
        $asientoDestino[estado]="Venta";
        $where2[] = "id_asiento='$asientoD'";
        $resp1 = $this->update($asientoDestino, $where2);
        if($resp1<=0) {
            throw new Zend_Db_Exception ( " No se pudo registrar el asiento destino " );
        }

        //$totalFactura += $precioVenta;
        $asientosNumero .= $numAsiento . " ";
        $dataMovimVend [id_movimiento] = 'nuevo';
        $dataMovimVend [vendedor] = $idVendedor;
        $dataMovimVend [fecha] = $hoy;
        $dataMovimVend [hora] = date ( 'H:i:s' );
        $dataMovimVend [ingreso] = 0;
        $dataMovimVend [egreso] = 0;
        $dataMovimVend [detalle] = "Movimiento de asiento $numAsientoOrg -> $numAsientoDest";
        $dataMovimVend [hora_viaje] = null;
        $dataMovimVend [asiento] = $numAsientoOrg;
        $dataMovimVend [codigo] = $factura->numero_factura;
        $dataMovimVend [interno] = 0;
        $dataMovimVend [estado] = 'Activo';
        $dataMovimVend [ip] = Statics::getIp ();
        $dataMovimVend [pc] = gethostbyaddr ( Statics::getIp () );
        $movimientoVModel->insert ( $dataMovimVend );
        $db->commit ();

    }

    /**
     * Funcion que recupera la sumatoria del precio de todos los asientos
     * que se vendieron en las fechas inidicadas que para un reporte seria
     * el principio y el fin de mes
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 20/08/2009
     */
    function getAllVentaFechas($inicio,$fin) {
        $db = $this->getAdapter ();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ("SUM(pasaje) as ventas" ) );
        $select->where ( "a.fecha_venta BETWEEN '$inicio' AND '$fin'" );
        $select->where ( 'a.estado=?', 'Venta' );
//        echo $select->__toString();
        $results = $db->fetchOne ( $select );
        return $results;
    }

    /**
     * ...
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date  now()
     */
    function getTotalVentaByViaje($viaje) {
         $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('a' => 'asiento' ), array ('SUM(a.pasaje) as venta') );
        $select->where ( 'a.viaje=?', $viaje);
        $results = $db->fetchOne ( $select );
        return $results;
    }
}
