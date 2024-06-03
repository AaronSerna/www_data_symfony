<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class InicioController extends AbstractController
{
    #[Route('/inicio', name: 'app_inicio')]
    public function index(User $user): Response
    {

        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('/');
        }

        return $this->render('inicio/index.html.twig', [
            'user' => $user,
            'controller_name' => 'InicioController',
        ]);
    }
}
