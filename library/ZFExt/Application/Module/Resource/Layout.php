<?php

class ZFExt_Application_Module_Resource_Layout
    extends Zend_Application_Resource_ResourceAbstract
{

    public function init()
    {
        $layout = $this->getBootstrap()->getResource('Layout');
        $layout->setOptions($this->getOptions());
    }

}
