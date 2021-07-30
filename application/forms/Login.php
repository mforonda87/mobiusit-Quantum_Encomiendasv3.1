<?php
/**
 * App_Form_Login
 * 
 * @author Enrico Zimuel (enrico@zimuel.it)
 * @version 0.2
 */
require_once 'Zend/Form.php';
class App_Form_Login extends Zend_Form
{   
    public function init ($timeout=360)
    {
        $this->addElement('hash', 'token', array(
             'timeout' => $timeout
        ));
        $this->addElement('text', 'username', array(
            'label'      => 'Username',
            'required'   => true,
            'validators' => array('Alnum'),
        ));
        $this->addElement('password', 'password', array(
            'label'      => 'Password',
            'required'   => true,
            'validators' => array('Alnum'),
        ));
        $this->addElement('submit','submit', array (
            'label'      => 'Send'
        ));
    }
}