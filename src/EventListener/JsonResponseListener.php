<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;

class JsonResponseListener implements EventSubscriberInterface
{
    private LoggerInterface $logger;
    private bool $isDebug;

    public function __construct(LoggerInterface $logger, bool $isDebug = false)
    {
        $this->logger = $logger;
        $this->isDebug = $isDebug;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // High priority to catch exceptions before other listeners
            KernelEvents::EXCEPTION => ['onKernelException', 100],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        // Log the exception
        $this->logger->error('Exception occurred', [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
        
        // Determine the status code
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        } elseif (strstr($exception->getMessage(), 'Access Denied')) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
        }

        // Create response data
        $responseData = [
            'status' => 'error',
            'code' => $statusCode,
            'message' => $exception->getMessage(),
        ];
        
        // In debug mode, add stack trace and other details
        if ($this->isDebug) {
            $responseData['exception'] = get_class($exception);
            $responseData['file'] = $exception->getFile();
            $responseData['line'] = $exception->getLine();
            $responseData['trace'] = $exception->getTraceAsString();
        }
        
        // Create JSON response
        $response = new JsonResponse($responseData, $statusCode);
        
        // Set headers from the exception if it's an HTTP exception
        if ($exception instanceof HttpExceptionInterface) {
            foreach ($exception->getHeaders() as $key => $value) {
                $response->headers->set($key, $value);
            }
        }
        
        // Set the response
        $event->setResponse($response);
    }
}