<?php

class CallbackController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $storage = new Zend_Feed_Pubsubhubbub_Storage_Filesystem;
        $storage->setDirectory(APPLICATION_ROOT . '/store/subscriptions');
        $callback = new Zend_Feed_Pubsubhubbub_Subscriber_Callback;
        $callback->setStorage($storage);
        /**
         * At time of writing, no fully PuSH 0.2 compatible Hubs exist
         * so we must detect the expected verify token (if used) and set
         * it explicitly. This is used by all callback requests and is set
         * when subscribing.
         */
        $callback->setVerifyToken($this->_getParam('subkey'));
        $callback->handle();
        /**
         * Check if a feed update was received and process it
         * asynchronously. Note: Asynchronous processing should be
         * utilised since a Hub might timeout very quickly if the
         * processing time exceeds its timeout setting (including
         * network latency).
         */
        if ($callback->hasFeedUpdate()) {
            $data = $callback->getFeedUpdate();
            $key = md5($data);
            file_put_contents(APPLICATION_ROOT . '/store/updates/' . $key, $data);
            $this->_helper->getHelper('Spawn')
                ->setScriptPath(APPLICATION_ROOT . '/scripts/zfrun.php');
            $this->_helper->spawn(
                array('--key'=>$key), 'process', 'callback'
            );
        }
        file_put_contents(
            APPLICATION_ROOT . '/log/' . microtime(true),
            print_r($callback->getHttpResponse(), true)
        );
        /**
         * Send final response to Client
         */
        $callback->sendResponse();
    }

    public function processAction()
    {
        if (!$this->getRequest() instanceof ZFExt_Controller_Request_Cli) {
            throw new Exception('Access denied from HTTP');
        }
        $this->getInvokeArg('bootstrap')->addOptionRules(
            array('key|k=s' => 'File keyname for task data (required)')
        );
        $options = $this->getInvokeArg('bootstrap')->getGetOpt();

        $path = APPLICATION_ROOT . '/store/updates/' . $options->key;
        $data = file_get_contents($path);
        $feed = Zend_Feed_Reader::importString($data);
        /**
         * TEMP: Improve when database added
         * Store update back to a file, this time using a serialized array
         * of its main data points so changes can be tracked.
         */
        $store = array(
            'title' => $feed->getTitle(),
            'id' => $feed->getId(),
            'modified_date' => $feed->getDateModified(),
            'link' => $feed->getLink()
        );
        file_put_contents(APPLICATION_ROOT . '/store/updates/' . md5($store['id']), serialize($store));
        unlink($path);
    }


}
