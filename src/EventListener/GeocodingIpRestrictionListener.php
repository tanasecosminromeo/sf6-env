<?php

namespace App\EventListener;

use App\Controller\GeocodingController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;

class GeocodingIpRestrictionListener implements EventSubscriberInterface
{
    private const ALLOWED_IP = '200.101.14.105';
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // When a controller class defines multiple action methods, the controller
        // is returned as an array [controllerInstance, methodName]
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof GeocodingController) {
            $request = $event->getRequest();
            $clientIp = $request->getClientIp();

            if ($clientIp !== self::ALLOWED_IP) {
                $this->logger->warning('Unauthorized access attempt to GeocodingController', [
                    'ip' => $clientIp,
                    'allowed_ip' => self::ALLOWED_IP,
                    'path' => $request->getPathInfo()
                ]);

                throw new AccessDeniedHttpException('Access denied: Your IP is not allowed to access this resource.');
            }

            $this->logger->info('Authorized access to GeocodingController', [
                'ip' => $clientIp,
                'path' => $request->getPathInfo()
            ]);
        }
    }
}