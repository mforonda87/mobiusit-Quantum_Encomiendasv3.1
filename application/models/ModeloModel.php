<?php

/**
 * AccionSubMenuModel
 *
 * @author Administrador
 * @version
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ModeloModel extends Zend_Db_Table_Abstract {
/**
 * The default table name
 */
    protected $_name = 'modelo';

    /**
     * Recupera todos los modelos de las flotas registrados en el sistema
     *
     */

    public function getAllModelosForDropDown() {
        return $this->_db->fetchPairs(
        "select distinct id_modelo, nombre from modelo m where m.estado='Activo'" );
    }
    public function getModelByBus($idBus) {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('m' => 'modelo' ), array ('id_modelo','tipo','nombre','descripcion','estado' ) );
        $select->joinInner(array('b'=>'bus'),'b.modelo=m.id_modelo',null);
        $select->where ( 'b.id_bus=?', $idBus );
        $results = $db->fetchRow( $select );
        return $results;
    }

    /**
     * Metodo encargado de registrar un nuevo modelo
     *
     * @param String $nombreM  el nombre que recibira el nuevo modelo
     * @param String $modelo   identificador del tipo de modelo que se creara (cama, semi cama, normal)
     * @param Array  $asientos los items que creara este modelo
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function saveTx($nombreM,$descripcion,$asientos) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        try {
            $pisoModel = new PisoModel ( );
            $itemModel = new ItemModel ( );

            $idPersona = PersonaModel::getIdentity ()->id_persona;
            $pisoModel = new PisoModel();
            $tipoModel = new TipoModel();

            $tipoBean[descripcion]=$descripcion;
            $tipoBean[nombre]=$nombreM;
            $tipoBean[id_tipo]="nuevo";
            $tipoBean[estado]="Activo";
            $tipoModel->insert($tipoBean);

            $idTipo = $tipoModel->getByName($nombreM);

            $modeloBean ['id_modelo'] = "nuevo";
            $modeloBean ['nombre'] = $nombreM;
            $modeloBean ['descripcion'] = $descripcion;
            $modeloBean ['tipo'] = $idTipo;
            $modeloBean ['estado'] = "Activo";
            $this->insert($modeloBean);

            $pisoB[estado]="Activo";
            $pisoB[id_piso]="nuevo";
            $pisoB[numero]=1;
            $pisoB[tipo]=$idTipo;
            $pisoModel->insert($pisoB);
            $idPiso = $pisoModel->getByTipoNumero($idTipo,1);

            foreach ( $asientos as $asiento ) {
                $newItem ['id_item'] = 'nuevo';
                $newItem ['piso'] = $idPiso;
                $newItem ['posicion_x'] = $asiento->posX;
                $newItem ['posicion_y'] = $asiento->posY;
                $newItem ['numero'] = $asiento->numero;
                $newItem ['tipo_item'] = base64_decode($asiento->item);
                $itemModel->insert ( $newItem );
                $newItem = null;
            }
            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe ) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            throw new Zend_Db_Exception ( "No se pudo registrar el Modelo ", 125 );
        }
    }
}
