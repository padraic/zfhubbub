<?php

class BootstrapCli extends Zend_Application_Bootstrap_Bootstrap
{

    protected $_getopt = null;

    protected $_getOptRules = array(
        'environment|e-w' => 'Application environment switch (optional)',
        'module|m-w' => 'Module name (optional)',
        'controller|c=w' => 'Controller name (required)',
        'action|a=w' => 'Action name (required)'
    );

    protected function _initView()
    {
        // displaces View Resource class to prevent execution
    }

    protected function _initCliFrontController()
    {
        $this->bootstrap('FrontController');
        $front = $this->getResource('FrontController');
        $getopt = new Zend_Console_Getopt($this->getOptionRules(),
            $this->_isolateMvcArgs());
        $request = new ZFExt_Controller_Request_Cli($getopt);
        $front->setResponse(new Zend_Controller_Response_Cli)
            ->setRequest($request)
            ->setRouter(new ZFExt_Controller_Router_Cli)
            ->setParam('noViewRenderer', true);
    }

    // CLI specific methods for option management

    public function setGetOpt(Zend_Console_Getopt $getopt)
    {
        $this->_getopt = $getopt;
    }

    public function getGetOpt()
    {
        if (is_null($this->_getopt)) {
            $this->_getopt = new Zend_Console_Getopt($this->getOptionRules());
        }
        return $this->_getopt;
    }

    public function addOptionRules(array $rules)
    {
        $this->_getOptRules = $this->_getOptRules + $rules;
    }

    public function getOptionRules()
    {
        return $this->_getOptRules;
    }

    // get MVC related args only (allows later uses of Getopt class
    // to be configured for cli arguments)
    protected function _isolateMvcArgs()
    {
        $options = array($_SERVER['argv'][0]);
        foreach ($_SERVER['argv'] as $key => $value) {
            if (in_array($value, array(
            '--action', '-a', '--controller', '-c', '--module', '-m', '--environment', '-e'
            ))) {
                $options[] = $value;
                $options[] = $_SERVER['argv'][$key+1];
            }
        }
        return $options;
    }

}
