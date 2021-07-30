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
class App_Rest_ServerDefault extends App_Rest_Server {

    public function getVersion($userid, $apikey) {
        $this->authenticate($userid, $apikey);

        return App_Rest_Response::Generate(array("version" => 1.0));
    }

}
?>
