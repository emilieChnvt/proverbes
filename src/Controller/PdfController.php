<?php

namespace App\Controller;

use App\Entity\Proverbe;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class PdfController extends AbstractController
{
    #[Route('/pdf/{id}', name: 'app_pdf')]
    public function index(Proverbe $proverbe, \Knp\Snappy\Pdf $knpSnappyPdf)
    {
        $html = $this->renderView('pdf/index.html.twig', ['proverbe' => $proverbe]);
        $knpSnappyPdf->setOption('enable-local-file-access', true);
        //wkhtmltopd bloque l’accès aux fichiers locaux => qrcode.
        //
        //autorise accès

        $pdfContent = $knpSnappyPdf->getOutputFromHtml($html); //génère

        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE . '; filename="QrCode.pdf"'
            ] //pas telecharge directement, pdf affiché directement
        );
    }
}











