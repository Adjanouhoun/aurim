<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:admin:create', description: 'Crée ou actualise un compte administrateur AURIM.')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail du compte administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower(trim((string) $input->getArgument('email')));

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Veuillez fournir une adresse e-mail valide.');
            return Command::INVALID;
        }

        $plainPassword = $io->askHidden('Mot de passe (12 caractères minimum)');
        if (!is_string($plainPassword) || mb_strlen($plainPassword) < 12) {
            $io->error('Le mot de passe doit contenir au moins 12 caractères.');
            return Command::INVALID;
        }

        $user = $this->users->findOneBy(['email' => $email]) ?? (new User())->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Le compte administrateur %s est prêt.', $email));
        return Command::SUCCESS;
    }
}
