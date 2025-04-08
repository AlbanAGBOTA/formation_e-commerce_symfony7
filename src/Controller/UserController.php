<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/// Cette fonction permet d'afficher les utilisateurs de la base de données
final class UserController extends AbstractController
{
    // Route pour afficher la liste des utilisateurs
    #[Route('/admin/user', name: 'app_user')]
    public function index(UserRepository $userRepository): Response
    {
        // Récupère tous les utilisateurs et les passe à la vue pour affichage
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    // Cette fonction permet de changer le rôle de l'utilisateur pour un éditeur
    #[Route('/admin/user/{id}/to/editor', name: 'app_user_editor')]
    public function changeRole(EntityManagerInterface $entityManager, User $user): Response
    {
        // Définit le rôle de l'utilisateur à "ROLE_ADMIN" et "ROLE_EDITOR"
        $user->setRoles(["ROLE_ADMIN", "ROLE_EDITOR"]);
        $entityManager->flush(); // Sauvegarde les modifications en base de données

        // Affiche un message flash de succès
        $this->addFlash(type: 'success', message: 'Le rôle éditeur a été ajouté à votre utilisateur');

        // Redirige vers la liste des utilisateurs
        return $this->redirectToRoute('app_user');
    }

    // Cette fonction permet de retirer le rôle "éditeur" de l'utilisateur
    #[Route('/admin/user/{id}/remove/editor/role', name: 'app_user_remove_editor_role')]
    public function editorRoleRemove(EntityManagerInterface $entityManager, User $user): Response
    {
        // Remet le rôle de l'utilisateur à "ROLE_USER" (rôle par défaut)
        $user->setRoles(['ROLE_USER']);
        $entityManager->flush(); // Sauvegarde les modifications en base de données

        // Affiche un message flash d'avertissement
        $this->addFlash(type: 'danger', message: 'Le rôle éditeur a été retiré de votre utilisateur');

        // Redirige vers la liste des utilisateurs
        return $this->redirectToRoute('app_user');
    }

    // Cette fonction permet de supprimer un utilisateur
    #[Route('/admin/user/{id}/remove', name: 'app_user_remove')]
    public function RemoveUser(EntityManagerInterface $entityManager, $id, UserRepository $userRepository): Response
    {
        // Recherche l'utilisateur en fonction de l'ID passé en paramètre
        $userFind = $userRepository->find($id);

        // Si l'utilisateur est trouvé, on le supprime de la base de données
        if ($userFind) {
            $entityManager->remove($userFind);
            $entityManager->flush(); // Applique la suppression

            // Affiche un message flash de suppression
            $this->addFlash(type: 'danger', message: 'Votre utilisateur a été supprimé');
        }

        // Redirige vers la liste des utilisateurs après suppression
        return $this->redirectToRoute('app_user');
    }
}
