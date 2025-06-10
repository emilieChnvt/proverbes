<?php

namespace App\Controller;

use App\Entity\Proverbe;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PdfController extends AbstractController
{
    #[Route('/pdf/{id}', name: 'app_pdf')]
    public function index(Proverbe $proverbe, \Knp\Snappy\Pdf $knpSnappyPdf)
    {
        $html = $this->renderView('pdf/index.html.twig', [
            'proverbe' => $proverbe,
            ]
        );
        $knpSnappyPdf->setOption('enable-local-file-access', true);
        // wkhtmltopdf bloque l’accès aux fichiers locaux : images stockées , images encodées en base64 dans le HTML
        // enable-local-file-access = true, autorises wkhtmltopdf à accéder aux ressources locales
        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html),
            'file.pdf'
        );
    }
}
