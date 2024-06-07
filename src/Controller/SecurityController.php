<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Endroid\QrCode\Builder\Builder;

class SecurityController extends AbstractController
{

    #[Route(path: '/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route(path: '/2fa', name: '2fa_login')]
    public function twoFactor(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('security/2fa_login.html.twig', [
            'error' => $error,
        ]);
    }


    #[Route(path: '/login/2fa/setup', name: '2fa_setup')
    ] public function setupTwoFactor(GoogleAuthenticatorInterface $googleAuthenticator, EntityManagerInterface $em): Response
    {
        /** @var User $user */

        $user = $this->getUser();
        // Generate Google Authenticator secret if not already set
        if ($user->getGoogleAuthenticatorSecret() === null) {
            $secret = $googleAuthenticator->generateSecret();
            $user->setGoogleAuthenticatorSecret($secret);
        }
        // Set two_factor_activated to true
        $user->setTwoFactorActivated(true);
        $em->persist($user);
        $em->flush();
        $qrCodeContent = $googleAuthenticator->getQRContent($user);

        // Generate QR code
        $result = Builder::create()->data($qrCodeContent)->size(250)->margin(4)->build();

        // Save QR code to a custom writable directory
        $qrCodeFilePath = $this->getParameter('kernel.project_dir') .  '/var/qrcodes/qrcode.png';
        $result->saveToFile($qrCodeFilePath);

        return $this->render('security/2fa_setup.html.twig', [
            'qrCodePath' => $qrCodeFilePath,
            'secret' => $user->getGoogleAuthenticatorSecret(),
            'user' => $user
        ]);
    }


    #[Route(path: '/qrcode', name: 'qr_code')] public function serveQrCode(): Response
    {
        $qrCodeFilePath = $this->getParameter('kernel.project_dir') . '/var/qrcodes/qrcode.png';
        return new BinaryFileResponse($qrCodeFilePath);
    }
}
