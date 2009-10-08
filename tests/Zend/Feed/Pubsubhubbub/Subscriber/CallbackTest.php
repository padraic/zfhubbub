<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Zend/Feed/Pubsubhubbub/Subscriber/Callback.php';
require_once 'Zend/Feed/Pubsubhubbub/StorageInterface.php';

class Zend_Feed_Pubsubhubbub_Subscriber_CallbackTest extends PHPUnit_Framework_TestCase
{

    protected $_originalServer = null;

    public function setUp()
    {
        $this->_callback = new Zend_Feed_Pubsubhubbub_Subscriber_Callback;
        $this->_callback->setStorage(new Zend_Feed_Pubsubhubbub_Subscriber_CallbackTestStorageHas);

        $this->_get = array(
            'hub_mode' => 'subscribe',
            'hub_topic' => 'http://www.example.com/topic',
            'hub_challenge' => 'abc',
            'hub_verify_token' => 'cba',
            'hub_mode' => 'subscribe',
            'hub_lease_seconds' => '1234567'
        );

        $this->_originalServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'get';
        $_SERVER['QUERY_STRING'] = 'xhub.subscription=verifytokenkey';
    }

    public function tearDown()
    {
        $_SERVER = $this->_originalServer;
    }


    public function testCanSetHttpResponseObject()
    {
        $this->_callback->setHttpResponse(new Zend_Feed_Pubsubhubbub_HttpResponse);
        $this->assertTrue($this->_callback->getHttpResponse() instanceof Zend_Feed_Pubsubhubbub_HttpResponse);
    }

    public function testCanUsesDefaultHttpResponseObject()
    {
        $this->assertTrue($this->_callback->getHttpResponse() instanceof Zend_Feed_Pubsubhubbub_HttpResponse);
    }

    public function testThrowsExceptionOnInvalidHttpResponseObjectSet()
    {
        try {
            $this->_callback->setHttpResponse(new stdClass);
            $this->fail('Should not fail as an Exception would be raised and caught');
        } catch (Zend_Feed_Pubsubhubbub_Exception $e) {}
    }

    public function testThrowsExceptionIfNonObjectSetAsHttpResponseObject()
    {
        try {
            $this->_callback->setHttpResponse('');
            $this->fail('Should not fail as an Exception would be raised and caught');
        } catch (Zend_Feed_Pubsubhubbub_Exception $e) {}
    }

    public function testCanSetSubscriberCount()
    {
        $this->_callback->setSubscriberCount('10000');
        $this->assertEquals(10000, $this->_callback->getSubscriberCount());
    }

    public function testDefaultSubscriberCountIsOne()
    {
        $this->assertEquals(1, $this->_callback->getSubscriberCount());
    }

    public function testThrowsExceptionOnSettingZeroAsSubscriberCount()
    {
        try {
            $this->_callback->setSubscriberCount(0);
            $this->fail('Should not fail as an Exception would be raised and caught');
        } catch (Zend_Feed_Pubsubhubbub_Exception $e) {}
    }

    public function testThrowsExceptionOnSettingLessThanZeroAsSubscriberCount()
    {
        try {
            $this->_callback->setSubscriberCount(-1);
            $this->fail('Should not fail as an Exception would be raised and caught');
        } catch (Zend_Feed_Pubsubhubbub_Exception $e) {}
    }

    public function testThrowsExceptionOnSettingAnyScalarTypeCastToAZeroOrLessIntegerAsSubscriberCount()
    {
        try {
            $this->_callback->setSubscriberCount('0aa');
            $this->fail('Should not fail as an Exception would be raised and caught');
        } catch (Zend_Feed_Pubsubhubbub_Exception $e) {}
    }


    public function testCanSetStorageImplementation()
    {
	$storage = $this->getMock('Zend_Feed_Pubsubhubbub_StorageInterface');
        $this->_callback->setStorage($storage);
        $this->assertThat($this->_callback->getStorage(), $this->identicalTo($storage));
    }

    public function testValidatesValidHttpGetData()
    {
        $this->assertTrue($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfHubVerificationNotAGetRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfModeMissingFromHttpGetData()
    {
        unset($this->_get['hub_mode']);
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfTopicMissingFromHttpGetData()
    {
        unset($this->_get['hub_topic']);
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfChallengeMissingFromHttpGetData()
    {
        unset($this->_get['hub_challenge']);
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfVerifyTokenMissingFromHttpGetData()
    {
        unset($this->_get['hub_verify_token']);
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsTrueIfModeSetAsUnsubscribeFromHttpGetData()
    {
        $this->_get['hub_mode'] = 'unsubscribe';
        $this->assertTrue($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfModeNotRecognisedFromHttpGetData()
    {
        $this->_get['hub_mode'] = 'abc';
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfLeaseSecondsMissedWhenModeIsSubscribeFromHttpGetData()
    {
        unset($this->_get['hub_lease_seconds']);
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfHubTopicInvalidFromHttpGetData()
    {
        $this->_get['hub_topic'] = 'http://';
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfVerifyTokenRecordDoesNotExistForConfirmRequest()
    {
        $this->_callback->setStorage(new Zend_Feed_Pubsubhubbub_Subscriber_CallbackTestStorageHasNot);
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testReturnsFalseIfVerifyTokenRecordDoesNotAgreeWithConfirmRequest()
    {
        $this->_callback->setStorage(new Zend_Feed_Pubsubhubbub_Subscriber_CallbackTestStorageHasButWrong);
        $this->assertFalse($this->_callback->isValidHubVerification($this->_get));
    }

    public function testRespondsToInvalidConfirmationWith404Response()
    {
        unset($this->_get['hub_mode']);
        $this->_callback->handle($this->_get);
        $this->assertTrue($this->_callback->getHttpResponse()->getHttpResponseCode() == 404);
    }

    public function testRespondsToValidConfirmationWith200Response()
    {
        $this->_callback->handle($this->_get);
        $this->assertTrue($this->_callback->getHttpResponse()->getHttpResponseCode() == 200);
    }

    public function testRespondsToValidConfirmationWithBodyContainingHubChallenge()
    {
        $this->_callback->handle($this->_get);
        $this->assertTrue($this->_callback->getHttpResponse()->getBody() == 'abc');
    }

    public function testRespondsToValidFeedUpdateRequestWith200Response()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/some/path/callback/verifytokenkey';
        $_SERVER['CONTENT_TYPE'] = 'application/atom+xml';
        $feedXml = file_get_contents(dirname(__FILE__) . '/_files/atom10.xml');
        $GLOBALS['HTTP_RAW_POST_DATA'] = $feedXml; // dirty  alternative to php://input
        $this->_callback->handle(array());
        $this->assertTrue($this->_callback->getHttpResponse()->getHttpResponseCode() == 200);
    }

    public function testRespondsToInvalidFeedUpdateNotPostWith404Response()
    {   // yes, this example makes no sense for GET - I know!!!
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/some/path/callback/verifytokenkey';
        $_SERVER['CONTENT_TYPE'] = 'application/atom+xml';
        $feedXml = file_get_contents(dirname(__FILE__) . '/_files/atom10.xml');
        $GLOBALS['HTTP_RAW_POST_DATA'] = $feedXml;
        $this->_callback->handle(array());
        $this->assertTrue($this->_callback->getHttpResponse()->getHttpResponseCode() == 404);
    }

    public function testRespondsToInvalidFeedUpdateWrongMimeWith404Response()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/some/path/callback/verifytokenkey';
        $_SERVER['CONTENT_TYPE'] = 'application/kml+xml';
        $feedXml = file_get_contents(dirname(__FILE__) . '/_files/atom10.xml');
        $GLOBALS['HTTP_RAW_POST_DATA'] = $feedXml;
        $this->_callback->handle(array());
        $this->assertTrue($this->_callback->getHttpResponse()->getHttpResponseCode() == 404);
    }

    /**
     * As a judgement call, we must respond to any successful request, regardless
     * of the wellformedness of any XML payload, by returning a 2xx response code.
     * The validation of feeds and their processing must occur outside the Hubbub
     * protocol.
     */
    public function testRespondsToInvalidFeedUpdateWrongFeedTypeForMimeWith200Response()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/some/path/callback/verifytokenkey';
        $_SERVER['CONTENT_TYPE'] = 'application/rss+xml';
        $feedXml = file_get_contents(dirname(__FILE__) . '/_files/atom10.xml');
        $GLOBALS['HTTP_RAW_POST_DATA'] = $feedXml;
        $this->_callback->handle(array());
        $this->assertTrue($this->_callback->getHttpResponse()->getHttpResponseCode() == 200);
    }

    public function testRespondsToValidFeedUpdateWithXHubOnBehalfOfHeader()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/some/path/callback/verifytokenkey';
        $_SERVER['CONTENT_TYPE'] = 'application/atom+xml';
        $feedXml = file_get_contents(dirname(__FILE__) . '/_files/atom10.xml');
        $GLOBALS['HTTP_RAW_POST_DATA'] = $feedXml;
        $this->_callback->handle(array());
        $this->assertTrue($this->_callback->getHttpResponse()->getHeader('X-Hub-On-Behalf-Of') == 1);
    }

}

/**
 * Stubs for storage access
 */
class Zend_Feed_Pubsubhubbub_Subscriber_CallbackTestStorageHas implements Zend_Feed_Pubsubhubbub_StorageInterface
{
    public function setToken($key, $token){}
    public function getToken($key){
        if ($key == 'verifytokenkey') return hash('sha256', 'cba');
    }
    public function hasToken($key){return true;}
    public function removeToken($key){}
    public function cleanup($type){}
}
class Zend_Feed_Pubsubhubbub_Subscriber_CallbackTestStorageHasNot implements Zend_Feed_Pubsubhubbub_StorageInterface
{
    public function setToken($key, $token){}
    public function getToken($key){}
    public function hasToken($key){return false;}
    public function removeToken($key){}
    public function cleanup($type){}
}
class Zend_Feed_Pubsubhubbub_Subscriber_CallbackTestStorageHasButWrong implements Zend_Feed_Pubsubhubbub_StorageInterface
{
    public function setToken($key, $token){}
    public function getToken($key){return 'wrong';}
    public function hasToken($key){return true;}
    public function removeToken($key){}
    public function cleanup($type){}
}
