<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of App_Rest_Server
 *
 * @author poloche
 */
class App_Rest_Handler extends Zend_Rest_Server {

    public function fault($exception = null, $code = null) {

        $xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><response></response>');
        $xml->addChild('status', 'fail');

        if ($exception instanceof Exception)
            $xml->addChild('error', $exception->getMessage());
        else
            $xml->addChild('error', 'Unknown error');

        if (is_null($code) || (404 != $code)) {
            $this->_headers[] = 'HTTP/1.0 400 Bad Request';
        } else {
            $this->_headers[] = 'HTTP/1.0 404 File Not Found';
        }

        return $xml;
    }

}
?>
