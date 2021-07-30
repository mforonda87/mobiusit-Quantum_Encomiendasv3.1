<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/

/**
 * Description of ComponentRoundedBean
 *
 * @author Paolo
 */
class App_Form_ComponentRoundedBean {
    private $content;
    private $class;
    public function __construct($content,$clsContent,$clsComponent="") {
        $this->content=$content;
        $this->class=$clsContent;
        $this->classComponent=$clsComponent;
    }
    public function show() {
        $resp ='<div class="round  inline-block '.$this->classComponent.'">';
        $resp.='<span class="ct1"></span>';
        $resp.='<span class="ct2"></span>';
        $resp.='<span class="ct3"></span>';
        $resp.='<span class="ct4"></span>';
        $resp.='<div class="'.$this->class.' content_rounded clearfix">';
        $resp.=$this->content;
        $resp.='</div>';
        $resp.='<span class="ct4"></span>';
        $resp.='<span class="ct3"></span>';
        $resp.='<span class="ct2"></span>';
        $resp.='<span class="ct1"></span>';
        $resp.='<div></div>';
        $resp.='</div>';
        return $resp;
    }
}
?>
