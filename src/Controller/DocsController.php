<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DocsController extends AbstractController
{
    #[Route('/docs', name: 'api_docs')]
    public function index(): Response
    {
        return new Response(file_get_contents($this->getParameter('kernel.project_dir').'/public/docs/index.html'));
    }
}
