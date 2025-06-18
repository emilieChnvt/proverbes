<?php

namespace App\Controller;

use App\Entity\Proverbe;
use App\Form\CsvUploadTypeForm;
use App\Form\ProverbeForm;
use App\Repository\ProverbeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\BuilderInterface;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


#[Route('/')]
final class ProverbeController extends AbstractController
{
    #[Route(name: 'app_proverbe_index', methods: ['GET'])]
    public function index(ProverbeRepository $proverbeRepository, BuilderInterface $builder, UrlGeneratorInterface $generator): Response
    {
        if(!$this->getUser()){return $this->redirectToRoute('app_login');}
        return $this->render('proverbe/index.html.twig', [
            'proverbes' => $proverbeRepository->findAll(),
        ]);
    }

    #[Route('/proverbe/qr/{id}', name: 'app_proverbe_qr', methods: ['GET'])]
    public function showQr(
        Proverbe $proverbe,): Response {

        return $this->render('proverbe/qr.html.twig', [
            'proverbe' => $proverbe,
        ]);
    }

    #[Route('/proverbe/new', name: 'app_proverbe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, BuilderInterface $builder,
                        ): Response
    {
        if(!in_array('ROLE_ADMIN', $this->getUser()->getRoles())){return $this->redirectToRoute('app_login');}

        $form = $this->createForm(CsvUploadTypeForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData(); //recupere fichier

            if ($file) {
                $extension = strtolower($file->getClientOriginalExtension()); // on lit l'extension

                $records = []; //tableau associatif ['content' => '...', 'author' => '...']
                dump($extension);

                if ($extension === 'csv') {
                    $csv = Reader::createFromPath($file->getPathname(), 'r'); //lit  fichier avec league/csv
                    $csv->setDelimiter(','); //colonnes séparées par ','
                    $csv->setHeaderOffset(0); // primière ligne = content, author
                    $records = iterator_to_array($csv->getRecords()); //lit ligne suivantes et les associes aux colonnes
                } else { // Excel .xlsx, .xls
                    $spreadsheet = IOFactory::load($file->getPathname());//ouvrir fichier excel avec spreadsheet
                    $sheet = $spreadsheet->getActiveSheet();// recupere première feuille fichier et toutes les lignes
                    $rows = $sheet->toArray(null, true, true, true); // retourne un tableau avec A, B, C...

                    $headers = array_shift($rows); // première ligne = en-têtes

                    foreach ($rows as $row) {
                        $record = [];
                        foreach ($headers as $col => $title) {
                            $record[strtolower(trim($title))] = $row[$col]; // création du tableau assocsiatif
                        }
                        $records[] = $record;
                    }
                }

                foreach ($records as $record) {
                    if (!isset($record['content'], $record['author'])) {
                        continue;
                    }

                    $proverbe = new Proverbe();
                    $proverbe->setContent($record['content']);
                    $proverbe->setAuthor($record['author']);

                    $entityManager->persist($proverbe);
                    $entityManager->flush();

                    $url = $this->generateUrl('app_proverbe_show', ['id' => $proverbe->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $result = $builder->build(data: $url, size: 300, margin: 10);
                    $pngData = $result->getString();
                    $proverbe->setQrCode('data:image/png;base64,' . base64_encode($pngData));

                    $entityManager->flush();
                }

                $entityManager->flush();

                return $this->redirectToRoute('app_proverbe_index');
            }
        }

        return $this->render('proverbe/new.html.twig', [
            'form' => $form,

        ]);
    }

    #[Route('/proverbe/show/{id}', name: 'app_proverbe_show', methods: ['GET'])]
    public function show(Proverbe $proverbe): Response
    {
        return $this->render('proverbe/show.html.twig', [
            'proverbe' => $proverbe,
        ]);
    }

    #[Route('/proverbe/{id}/edit', name: 'app_proverbe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Proverbe $proverbe, EntityManagerInterface $entityManager): Response
    {
        if(!in_array('ROLE_ADMIN', $this->getUser()->getRoles())){return $this->redirectToRoute('app_login');}

        $form = $this->createForm(ProverbeForm::class, $proverbe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_proverbe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('proverbe/edit.html.twig', [
            'proverbe' => $proverbe,
            'form' => $form,
        ]);
    }

    #[Route('/proverbe/delete/{id}', name: 'app_proverbe_delete', methods: ['POST'])]
    public function delete(Request $request, Proverbe $proverbe, EntityManagerInterface $entityManager): Response
    {
        if(!in_array('ROLE_ADMIN', $this->getUser()->getRoles())){return $this->redirectToRoute('app_login');}

        if ($proverbe) {
            $entityManager->remove($proverbe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_proverbe_index', [], Response::HTTP_SEE_OTHER);
    }
}
