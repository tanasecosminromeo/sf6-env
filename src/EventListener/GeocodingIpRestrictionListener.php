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
    private LoggerInterface $logger;

    private string $ip_subnet_prefix = '';

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->ip_subnet_prefix = getenv("IP_SUBNET");
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

            $allowedIps = array_map(fn ($suffix) => $this->ip_subnet_prefix . '.' . $suffix, ['104', '105']);
            $allowedIps[] = '127.0.0.1';

            if (!in_array($clientIp, $allowedIps, true)) {
                $this->logger->warning('Unauthorized access attempt to GeocodingController', [
                    'ip' => $clientIp,
                    'allowed_prefix' => $this->ip_subnet_prefix,
                    'path' => $request->getPathInfo()
                ]);

                throw new AccessDeniedHttpException('Access denied: Your IP is not allowed to access this resource.'.$clientIp);
            }

            $this->logger->info('Authorized access to GeocodingController', [
                'ip' => $clientIp,
                'path' => $request->getPathInfo()
            ]);
        }
    }
}