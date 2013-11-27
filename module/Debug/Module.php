<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Debug;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
//use Zend\Mvc\ModuleRouteListener;
//use Zend\Module\Manager;
use Zend\ModuleManager\ModuleManager;
use Zend\EventManager\Event;
use Zend\Mvc\MvcEvent;

class Module implements AutoloaderProviderInterface
{
    public function init(ModuleManager $moduleManager)
    {
        $date = new \DateTime();
        $date = $date->format('Y-m-d H:i:s');
        
        error_log('====== BEGIN DEBUG LOGGING ['.$date.'] ======');
        $eventManager = $moduleManager->getEventManager();
        $eventManager->attach('loadModules.post',array($this,'loadedModulesInfo'));
        //array($this, 'loadedModulesInfo') is a PHP callback to the loadedModulesInfo function in this class.
    }
    
    public function loadedModulesInfo(Event $event)
    {
        $moduleManager = $event->getTarget();
        $loadedModules = $moduleManager->getLoadedModules();
        error_log('INIT Listener: '.var_export($loadedModules,true));
    }
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        //$eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR,
        //    function(MvcEvent $event) {
        //        error_log('BOOTSTRAP Killer Listener: '.$event->getParam('error'));
        //        //$event->stopPropagation(true);
        //    },
        //    100);
        
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR,array($this,'handleError'));
        
        //Get access to service manager
        $serviceManager = $e->getApplication()->getServiceManager();
        //Start Timer
        $timer = $serviceManager->get('timer');
        $timer->start('mvc-execution');
        
        //Attach listener to the finish event that has to be executed with priority 2.
        //The priority is 2 because listeners with that priority will be executed just before the actual finish event is triggered.
        $eventManager->attach(MvcEvent::EVENT_FINISH, array($this,'getMvcDuration'),2);
        
        //$moduleRouteListener = new ModuleRouteListener();
        //$moduleRouteListener->attach($eventManager);
    }
    
    public function handleError(MvcEvent $event)
    {
        $controller = $event->getController();
        $error = $event->getParam('error');
        $exception = $event->getParam('exception');
        $message = 'BOOTSTRAP Listener: Error:'.$error;
        if($exception instanceof\Exception) {
            $message .= ',Exception('.$exception->getMessage().'): '.
                $exception->getTraceAsString();
        }
        
        error_log($message);
    }
    
    public function getMvcDuration(MvcEvent $event)
    {
        //Get service manager
        $serviceManager = $event->getApplication()->getServiceManager();
        //Get the already created instance of our timer service
        $timer = $serviceManager->get('timer');
        $duration = $timer->stop('mvc-execution');
        //Finaly print the duration
        error_log("MVC Duration:".$duration." seconds");
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
