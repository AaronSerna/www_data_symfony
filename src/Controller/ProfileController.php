<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/profile')]
class ProfileController extends AbstractController
{

    #[Route('/', name: 'app_profile_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {

        $user = $this->getUser();

        if (!$this->getUser()) {
            return $this->redirectToRoute('/');
        }

        return $this->render('profile/index.html.twig', [
            'users' => $userRepository->findAll(),
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {

        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN'); //Only SUPER_ADMINs can create new users.


        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN'), // With this method we ensure us that the role checkboxes will only appear in the form when the user is a SUPER_ADMIN.
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            // When the user logs for the first time, these columns should be changed to 'true'.
            $user->setTwoFactorActivated(false);
            $user->setVerified(false);


            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(User $user, UserRepository $userRepository): Response
    {
        // Before deleting a user, we must check if the clicked user is a SUPER_ADMIN to garantee the DB has at least one SUPER_ADMIN.
        $superAdmins = $userRepository->countSuperAdmins();

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'superAdmins' => $superAdmins

        ]);
    }

    #[Route('/{id}/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {

        // We store the logged user data.
        $currentUser = $this->getUser();

        // We check if the current user is a super_admin.
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            // If he's not a super_admin, we verify if he's trying to edit his own profile.
            if ($currentUser !== $user) {
                // If he's trying to edit other person's profile and the logged user isn't a super_admin, we redirect him to 'show' view.
                return $this->redirectToRoute('app_profile_show', ['id' => $currentUser->getId()]);
            }
        }

        $form = $this->createForm(UserType::class, $user, [
            'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN'),  // With this method we ensure us that the role checkboxes will only appear in the form when the user is a SUPER_ADMIN.
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $entityManager->flush();

            return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $currentUser,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN'); //Only SUPER_ADMINs can delete users.

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->get('_token'))) {

            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
    }
}
