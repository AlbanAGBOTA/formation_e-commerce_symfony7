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
    /// 1- Cette fonction permet d'afficher les catégories stockées dans la base de données
    #[Route('/admin/category', name: 'app_category')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        // Récupérer toutes les catégories depuis la base de données
        $categories = $categoryRepository->findAll();

        // Rendre la vue et transmettre les catégories récupérées
        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }


    /// 2- Cette fonction permet d'enregistrer une nouvelle catégorie dans la base de données
    #[Route('/admin/category/new', name: 'app_category_new')]
    public function addCategory(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Créer une nouvelle instance de catégorie
        $category = new Category();

        // Créer le formulaire associé à l'entité Category
        $form = $this->createForm(CategoryFormType::class, $category);

        // Gérer la soumission du formulaire
        $form->handleRequest($request);

        // Vérifier si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Persister l'entité en base de données
            $entityManager->persist($category);
            $entityManager->flush();

            // Ajouter un message de succès
            $this->addFlash(type: 'success', message: 'Votre catégorie a été créée');

            // Rediriger vers la liste des catégories
            return $this->redirectToRoute('app_category');
        }

        // Afficher le formulaire de création
        return $this->render('category/new.html.twig', ['form' => $form->createView()]);
    }


    /// 3- Permet de récupérer et de mettre à jour une catégorie existante
    #[Route('/admin/category/{id}/update', name: 'app_category_update')]
    public function updateCategorie(Category $category, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Créer le formulaire associé à l'entité Category existante
        $form = $this->createForm(CategoryFormType::class, $category);

        // Gérer la soumission du formulaire
        $form->handleRequest($request);

        // Vérifier si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer les modifications en base de données
            $entityManager->flush();

            // Ajouter un message de succès
            $this->addFlash(type: 'success', message: 'Votre catégorie a été modifiée');

            // Rediriger vers la liste des catégories
            return $this->redirectToRoute('app_category');
        }

        // Afficher le formulaire de mise à jour
        return $this->render('category/update.html.twig', ['form' => $form->createView()]);
    }


    /// 4- Permet de supprimer une catégorie existante
    #[Route('/admin/category/{id}/delete', name: 'app_category_delete')]
    public function deleteCategorie(Category $category, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Supprimer la catégorie de la base de données
        $entityManager->remove($category);
        $entityManager->flush();

        // Ajouter un message de confirmation de suppression
        $this->addFlash(type: 'danger', message: 'Votre catégorie a été supprimée');

        // Rediriger vers la liste des catégories
        return $this->redirectToRoute('app_category');
    }
}
