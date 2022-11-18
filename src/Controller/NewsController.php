<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\News;

use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;

class NewsController extends AbstractController
{
    private $entityManager;
    private $newsPostRepository;
    const POST_LIMIT = 10;

    public function __construct(EntityManagerInterface $entityManager)
        {
            $this->entityManager = $entityManager;
            $this->newsPostRepository = $entityManager->getRepository(News::Class);
        
        }

    #[Route('/news', name: 'app_news')]
    public function index(NewsRepository $newsRepository, DataTableFactory $dataTableFactory): Response
    {
        //$allNews = $newsRepository->findAll();
        //dd($allNews);
        
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('news/index.html.twig', [
            'allNews' => $newsRepository->findAll(),
        ]);
    }

   

  



/**
 * @Route("/delete-entry/{entryId}", name="admin_delete_entry")
 *
 * @param $entryId
 *
 * @return \Symfony\Component\HttpFoundation\RedirectResponse
 */
public function deleteEntryAction($entryId)
{
    $this->denyAccessUnlessGranted('ROLE_USER');
    $newsPost = $this->newsPostRepository->findOneById($entryId);

    if (!$newsPost) {
        $this->addFlash('error', 'Unable to remove entry!');

        return $this->redirectToRoute('app_news');
    }

    $this->entityManager->remove($newsPost);
    $this->entityManager->flush();
    $this->addFlash('success', 'News was deleted!');

    return $this->redirectToRoute('app_news');
}

}
