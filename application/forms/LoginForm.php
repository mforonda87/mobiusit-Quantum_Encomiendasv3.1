<?php

class App_Form_LoginForm extends Zend_Form {
    public $decoradores = array ('ViewHelper',
            array ('ViewScript',
                            array ('viewScript' => 'ElementDecorator.phtml', 'placement' => false ) ) );
    public $checkboxDecorator = array(
                                    'ViewHelper',
                                    'Errors',
                                    'Description',
                                    array('HtmlTag',array('tag' => 'td')),
                                    array('Label',array('tag' => 'td','class' =>'element')),
                                    array('Description', array('tag' => 'span')),
                                    array(array('row' => 'HtmlTag'), array('tag' => 'tr')));

     public $buttonDecorators = array(
                                    'ViewHelper',
                                    array('HtmlTag',array('tag' => 'span')),
                                    //array('Label',array('tag' => 'td')), NO LABELS FOR BUTTONS
                                    array(array('row' => 'HtmlTag'), array('tag' => 'div','class'=>'center_align')));

    public function init() {
        $this->addElement ( 'text', 'username',
                array ('filters' => array ('StringTrim', 'StringToLower' ),
                'validators' => array ('Alpha',
                        array ('StringLength', false, array (3, 20 ) ) ),
                'required' => true, 'label' => 'Identificador ',
                'decorators' => $this->decoradores ) );

        $this->addElement ( 'password', 'password',
                array ('filters' => array ('StringTrim' ),
                'validators' => array ('Alnum',
                        array ('StringLength', false, array (3, 20 ) ) ),
                'required' => true, 'label' => 'Contrasenia ',
                'decorators' => $this->decoradores ) );
        $captcha = new Zend_Form_Element_Captcha ( 'captcha',
                array ('label' => "E", 'decorators' => $this->decoradores,
                        'captcha' => array ('captcha' => 'Dumb', 'wordLen' => 6,
                                'timeout' => 300 ) ) );

        $this->addElement('checkbox', 'agreement', array(
            'decorators' => $this->decoradores,
            'label'       => 'recordarme',
            'required'   => false,
        ));

        //		$this->addElement ( $captcha );
        $this->addElement ( 'submit', 'index/login',
                array ('required' => false, 'ignore' => true, 'label' => App_Util_Statics::$labelButtonLogin,
                'decorators' => $this->buttonDecorators,"onclick"=>'doLogin();', 'class' => 'botton' ) );
        $this->setName("LoginForm");

        // We want to display a 'failed authentication' message if necessary;
        // we'll do that with the form 'description', so we need to add that
        // decorator.
    }
    public function loadDefaultDecorators() {
        $this->setDecorators (
                array ('FormElements',
                    array ('HtmlTag',
                        array ('tag' => 'div', 'class' => 'login_form' ) ),
                'Form' ) );
    }
}

?>