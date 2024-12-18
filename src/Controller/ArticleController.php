<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    #[Route('/article/creer', name: 'app_article_create')]
    public function create(
        EntityManagerInterface $entityManager, 
        Request $request, 
        SluggerInterface $slugger, 
        #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory
        ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $article = new Article();
        
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $article = $form->getData();
            
            $image = $form->get('image')->getData();
            if ($image){
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move($imagesDirectory, $newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
            }

            $article->setImage($newFilename);

            $entityManager->persist($article);

            $entityManager->flush();

            $this->addFlash('success', 'Article ajouter!');

            return $this->redirectToRoute('app_article_liste');
        }
        //dd($article);

        return $this->render('article/creer.html.twig', [
            'controller_name' => 'ArticleController',
            'titre' => 'Article',
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/article/liste', name: 'app_article_liste')]
    public function fetchAll(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findAll();

        if (!$articles) {
            throw $this->createNotFoundException(
                'No article found'
            );
        }

        return $this->render('article/liste.html.twig', [
            'controller_name' => 'ArticleController',
            'titre' => 'Liste d\'article',
            'articles' => $articles
        ]);
    }

    #[Route('/article/update/{id}', name: 'app_article_update')]
    public function update(
        EntityManagerInterface $entityManager, 
        int $id, 
        Request $request,
        SluggerInterface $slugger, 
        #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory
        ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No article found with id '.$id
            );
        }
        else {
            $form = $this->createForm(ArticleType::class, $article);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $article = $form->getData();

                $image = $form->get('image')->getData();
                if ($image){
                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                    try {
                        $image->move($imagesDirectory, $newFilename);
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                    }

                    $article->setImage($newFilename);
                }                

                $entityManager->persist($article);

                $entityManager->flush();

                $this->addFlash('success', 'Article n°'.$id.' modifier!');

                return $this->redirectToRoute('app_article_liste');
            }
        }

        return $this->render('article/update.html.twig', [
            'controller_name' => 'ArticleController',
            'mainTitle' => 'Article',
            'titre' => 'Modification d\'article',
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/article/delete/{id}', name: 'app_article_delete')]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No article found with id '.$id
            );
        }

        $entityManager->remove($article);

        $entityManager->flush();

        $this->addFlash('success', 'Article n°'.$id.' supprimer!');

        return $this->redirectToRoute('app_article_liste');
    }
}
