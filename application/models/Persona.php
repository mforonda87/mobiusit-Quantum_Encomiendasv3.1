<?php

/**
 * PersonaModel
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Persona extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'persona';
    protected $_prmary = 'id_persona';

    const NOT_IDENTITY = 'notIdentity';
    const INVALID_CREDENTIAL = 'invalidCredential';
    const INVALID_USER = 'invalidUser';
    const INVALID_LOGIN = 'invalidLogin';

    protected $funciones = array();
    protected $roles;

    /**
     * Mensaje de validaciones por defecto
     *
     * @var array
     */
    protected $_messages = array(
        self::NOT_IDENTITY => "Not existent identity. A record with the supplied identity could not be found.",
        self::INVALID_CREDENTIAL => "Invalid credential. Supplied credential is invalid.",
        self::INVALID_USER => "Invalid User. Supplied credential is invalid",
        self::INVALID_LOGIN => "Invalid Login. Fields are empty");

    /**
     * @param string $messageString
     * @param string $messageKey    OPTIONAL
     * @return UserModel
     * @throws Exception
     */
    public function setMessage($messageString, $messageKey = null) {
        if ($messageKey === null) {
            $keys = array_keys($this->_messages);
            $messageKey = current($keys);
        }
        if (!isset($this->_messages [$messageKey])) {
            throw new Exception("No message exists for key '$messageKey'");
        }
        $this->_messages [$messageKey] = $messageString;
        return $this;
    }

    /**
     * @param array $messages
     * @return UserModel
     */
    public function setMessages(array $messages) {
        foreach ($messages as $key => $message) {
            $this->setMessage($message, $key);
        }
        return $this;
    }

    public function updateTX(array $values, $id) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        
        $where[] = " id_persona='$id'";
        if ($db->update($this->_name, $values, $where)) {
            $db->commit();
            return true;
        } else {
            $db->rollBack();
            return false;
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
     * @date creation 03/06/2009
     */
    public function saveTX($personaForm) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $this->insert($personaForm);
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception("No se pudo registrar el nuevo Usuario ", 125);
        }
    }

    public function getPassword($id) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('p' => 'persona'), array('contrasenia'));
        $select->where('p.id_persona=?', $id);
        $results = $db->fetchOne($select);
        return $results;
    }

}
