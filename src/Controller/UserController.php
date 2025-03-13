<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

///Cette fonction permet d'afficher les utilisateurs de la db
final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    //Cette fonction permet de changer le role utilisateurs et les editors
    #[Route('/admin/user/{id}/to/editor', name: 'app_user_editor')]
    public function changeRole(EntityManagerInterface $entityManager, User $user):Response
    {
        $user->setRoles(["ROLE_ADMIN", "ROLE_EDITOR"]);
        $entityManager->flush();

        $this->addFlash(type:'success', message:'le role éditor a été ajouté a votre utilisateur');

        return $this->redirectToRoute('app_user');
    }

    //Cette fonction permet de retirer le role utilisateurs et les editors
    #[Route('/admin/user/{id}/remove/editor/role', name: 'app_user_remove_editor_role')]
    public function editorRoleRemove(EntityManagerInterface $entityManager, User $user):Response
    {
        $user->setRoles(['ROLE_USER']);
        $entityManager->flush();


        $this->addFlash(type:'danger', message:'le role éditor a été retiré a votre utilisateur');

        return $this->redirectToRoute('app_user');
    }

    //Cette fonction permet de supprimer les utilisateurs
    #[Route('/admin/user/{id}/remove', name: 'app_user_remove')]
    public function RemoveUser(EntityManagerInterface $entityManager, $id, UserRepository $userRepository):Response
    {
        $userFind = $userRepository->find($id);
        $entityManager->remove($userFind);
        $entityManager->flush();

        $this->addFlash(type:'danger', message:'votre utilisateur a été supprimer');

        return $this->redirectToRoute('app_user');
    }
}
