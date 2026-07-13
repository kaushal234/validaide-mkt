<?php

declare(strict_types=1);

namespace App\Controller;

use App\Csv\Exception\CsvParseException;
use App\Dataset\DatasetImporter;
use App\Dataset\Dto\DatasetUploadDto;
use App\Dataset\DatasetUploadType;
use App\Mkt\Exception\EmptyTemperatureSetException;
use App\Repository\DatasetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DatasetController extends AbstractController
{
    private const int PER_PAGE = 10;

    #[Route('/', name: 'dataset_index', methods: ['GET'])]
    public function index(Request $request, DatasetRepository $datasets): Response
    {
        $name = $request->query->get('name');
        $sort = $request->query->get('sort', 'submittedAt');
        $direction = $request->query->get('direction', 'desc');
        $page = $request->query->getInt('page', 1);

        $paginator = $datasets->paginate($name, $sort, $direction, $page, self::PER_PAGE);
        $total = \count($paginator);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));

        return $this->render('dataset/index.html.twig', [
            'datasets' => $paginator,
            'total' => $total,
            'page' => max(1, $page),
            'pages' => $pages,
            'name' => $name,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/datasets/upload', name: 'dataset_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, DatasetImporter $importer): Response
    {
        $upload = new DatasetUploadDto();
        $form = $this->createForm(DatasetUploadType::class, $upload);

        if ($this->postExceededServerLimit($request)) {
            $form->addError(new FormError('The upload was too large for the server to accept. Please upload a CSV file of at most 2 MB.'));

            return $this->render('dataset/upload.html.twig', ['form' => $form]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dataset = $importer->import(
                    $upload->name,
                    new \SplFileObject($upload->file->getPathname()),
                );

                $this->addFlash('success', \sprintf('Dataset "%s" imported.', $dataset->getName()));

                return $this->redirectToRoute('dataset_index');
            } catch (CsvParseException $exception) {
                foreach ($exception->getErrors() as $error) {
                    $form->get('file')->addError(new FormError($error));
                }
            } catch (EmptyTemperatureSetException $exception) {
                $form->get('file')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('dataset/upload.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/datasets/{id}', name: 'dataset_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, DatasetRepository $datasets): Response
    {
        $dataset = $datasets->find($id);
        if (null === $dataset) {
            throw $this->createNotFoundException();
        }

        return $this->render('dataset/show.html.twig', [
            'dataset' => $dataset,
        ]);
    }

    #[Route('/datasets/{id}/delete', name: 'dataset_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request, DatasetRepository $datasets, EntityManagerInterface $entityManager): Response
    {
        $dataset = $datasets->find($id);
        if (null === $dataset) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('delete_dataset_'.$dataset->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $name = $dataset->getName();
        $entityManager->remove($dataset);
        $entityManager->flush();

        $this->addFlash('success', \sprintf('Dataset "%s" deleted.', $name));

        return $this->redirectToRoute('dataset_index');
    }

    private function postExceededServerLimit(Request $request): bool
    {
        if (!$request->isMethod('POST')) {
            return false;
        }

        $contentLength = (int) $request->server->get('CONTENT_LENGTH', 0);
        if ($contentLength <= 0) {
            return false;
        }

        return [] === $request->request->all() && [] === $request->files->all();
    }
}