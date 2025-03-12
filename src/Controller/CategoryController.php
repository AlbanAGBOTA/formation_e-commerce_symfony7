<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    ///Cette fonction permet d'afficher les elements de la db
    #[Route('/admin/category', name: 'app_category')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll(); //Cette ligne permet de recupérer toutes les categories de db
        //dd($categories);
        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    // Cette fonction permet d'enrégistrer un objet dans la db
    #[Route('/admin/category/new', name: 'app_category_new')]
    public function addCategory(EntityManagerInterface $entityManager, Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryFormType::class, $category);
        // permet de recuperer le contenu de la requette
        $form->handleRequest($request);

        //Vérifier si le formuler a été soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            //dd($category);
            $entityManager->persist($category);
            $entityManager->flush();

            //afficher le message de succéss
            $this->addFlash(type: 'success', message: 'votre catégorie a été créer');
            return $this->redirectToRoute('app_category');
        }

        return $this->render('category/new.html.twig', ['form' => $form->createView()]);
    }


    ///Permet de recupérer et de mettre a jours un objet de la db
    #[Route('/admin/category/{id}/update', name: 'app_category_update')]
    public function updateCategorie(Category $category, EntityManagerInterface $entityManager, Request $request): Response
    {
        $form = $this->createForm(CategoryFormType::class, $category);
        // permet de recuperer le contenu de la requette
        $form->handleRequest($request);

        //Vérifier si le formuler a été soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            //dd($category);
            $entityManager->flush();

            //afficher le message de succéss
            $this->addFlash(type: 'success', message: 'votre catégorie a été modifié');

            return $this->redirectToRoute('app_category');
        }

        return $this->render('category/update.html.twig', ['form' => $form->createView()]);
    }

    ///Permet de recupérer et de supprimer un objet de la db
    #[Route('/admin/category/{id}/delete', name: 'app_category_delete')]
    public function deleteCategorie(Category $category, EntityManagerInterface $entityManager, Request $request): Response
    {
        $entityManager->remove($category);
        $entityManager->flush();
        //afficher le message de succéss
        $this->addFlash(type: 'danger', message: 'votre catégorie a été supprimé');

        return $this->redirectToRoute('app_category');
    }
}
