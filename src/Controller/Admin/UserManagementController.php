<?php

namespace App\Controller\Admin;

use App\Entity\Market;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class UserManagementController extends AbstractController
{
    #[AdminRoute('/utilisateurs', name: 'users', options: ['methods' => ['GET', 'POST']])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $markets = $entityManager->createQueryBuilder()
            ->select('market')
            ->from(Market::class, 'market')
            ->andWhere('market.countryCode != :internalMarket')
            ->setParameter('internalMarket', 'US')
            ->orderBy('market.name', 'ASC')
            ->getQuery()
            ->getResult();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create-admin-user', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Le formulaire a expiré. Rechargez la page et réessayez.');
            }
            $email = mb_strtolower(trim((string) $request->request->get('email')));
            $password = (string) $request->request->get('password');
            $role = (string) $request->request->get('role');
            $marketCode = strtoupper(trim((string) $request->request->get('market')));

            if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('danger', 'Veuillez fournir une adresse e-mail valide.');
            } elseif ($users->findOneBy(['email' => $email]) instanceof User) {
                $this->addFlash('danger', 'Un compte utilise déjà cette adresse e-mail.');
            } elseif (mb_strlen($password) < 12) {
                $this->addFlash('danger', 'Le mot de passe doit contenir au moins 12 caractères.');
            } elseif (!in_array($role, ['admin', 'super_admin'], true)) {
                $this->addFlash('danger', 'Le niveau d’accès sélectionné est invalide.');
            } else {
                $market = 'admin' === $role
                    ? $entityManager->getRepository(Market::class)->findOneBy(['countryCode' => $marketCode])
                    : null;
                if ('admin' === $role && (!$market instanceof Market || 'US' === $market->getCountryCode())) {
                    $this->addFlash('danger', 'Sélectionnez le pays géré par cet administrateur.');
                } else {
                    $user = (new User())
                        ->setEmail($email)
                        ->setRoles(['super_admin' === $role ? 'ROLE_SUPER_ADMIN' : 'ROLE_ADMIN'])
                        ->setMarket($market);
                    $user->setPassword($passwordHasher->hashPassword($user, $password));
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $this->addFlash('success', sprintf('Le compte %s a été créé.', $email));

                    return $this->redirectToRoute('admin_users');
                }
            }
        }

        return $this->render('admin/user/index.html.twig', [
            'markets' => $markets,
            'users' => $users->findBy([], ['email' => 'ASC']),
        ]);
    }
}
