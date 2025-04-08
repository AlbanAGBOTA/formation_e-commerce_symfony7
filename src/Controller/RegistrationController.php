<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\SecuriteAuthentificationAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        // Création d'un nouvel utilisateur
        $user = new User();

        // Création du formulaire d'inscription et association avec l'entité User
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Hashage du mot de passe avant de l'enregistrer
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Persistance de l'utilisateur en base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Connexion automatique de l'utilisateur après l'inscription
            return $security->login($user, SecuriteAuthentificationAuthenticator::class, 'main');
        }

        // Affichage du formulaire d'inscription si non soumis ou invalide
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
