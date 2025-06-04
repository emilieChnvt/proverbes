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

#[Route('/proverbe')]
final class ProverbeController extends AbstractController
{
    #[Route(name: 'app_proverbe_index', methods: ['GET'])]
    public function index(ProverbeRepository $proverbeRepository, BuilderInterface $builder, UrlGeneratorInterface $generator): Response
    {
        if(!$this->getUser()){return $this->redirectToRoute('app_login');}

        $qrCodes = [];
        foreach ($proverbeRepository->findAll() as $proverbe) {
            $url = $generator->generate('app_proverbe_show', ['id' => $proverbe->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $result = $builder->build(
                data: $url,
                size: 200,
                margin: 10
            );

            $qrCodes[$proverbe->getId()] = base64_encode($result->getString());
            //result = objet retourné par methode build
            //   on recupere chaine binaire $result->getString()
            // que l'on convertit pour le HTML grâce a  base64_encode

        }
        if(!$this->getUser()){return $this->redirectToRoute('app_login');}
        return $this->render('proverbe/index.html.twig', [
            'proverbes' => $proverbeRepository->findAll(),
            'qrCodes' => $qrCodes,
        ]);
    }

    #[Route('/new', name: 'app_proverbe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){return $this->redirectToRoute('app_login');}

        $form = $this->createForm(CsvUploadTypeForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('csvFile')->getData();

            if ($file) {
                $extension = strtolower($file->getClientOriginalExtension()); // on lit l'extension

                $records = [];
                dump($extension);

                if ($extension === 'csv') {
                    $csv = Reader::createFromPath($file->getPathname(), 'r');
                    $csv->setDelimiter(','); // ou ',' selon ton fichier
                    $csv->setHeaderOffset(0);
                    $records = iterator_to_array($csv->getRecords());
                } else { // Excel .xlsx, .xls
                    $spreadsheet = IOFactory::load($file->getPathname());//ouvrir fichier
                    $sheet = $spreadsheet->getActiveSheet();// recupere feuille active et toutes les lignes
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
                        continue; // skip ligne incomplète
                    }

                    $proverbe = new Proverbe();
                    $proverbe->setContent($record['content']);
                    $proverbe->setAuthor($record['author']);
                    $entityManager->persist($proverbe);
                }

                $entityManager->flush();

                return $this->redirectToRoute('app_proverbe_index');
            }
        }

        return $this->render('proverbe/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/show/{id}', name: 'app_proverbe_show', methods: ['GET'])]
    public function show(Proverbe $proverbe): Response
    {
        return $this->render('proverbe/show.html.twig', [
            'proverbe' => $proverbe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_proverbe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Proverbe $proverbe, EntityManagerInterface $entityManager): Response
    {
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

    #[Route('/delete/{id}', name: 'app_proverbe_delete', methods: ['POST'])]
    public function delete(Request $request, Proverbe $proverbe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$proverbe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($proverbe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_proverbe_index', [], Response::HTTP_SEE_OTHER);
    }
}
