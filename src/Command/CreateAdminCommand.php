<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Market;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail du compte administrateur')
            ->addOption('market', null, InputOption::VALUE_REQUIRED, 'Code pays du marché géré, par exemple SN')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Autorise la gestion de tous les marchés');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower(trim((string) $input->getArgument('email')));

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Veuillez fournir une adresse e-mail valide.');
            return Command::INVALID;
        }

        $marketCode = strtoupper(trim((string) $input->getOption('market')));
        $isSuperAdmin = (bool) $input->getOption('super-admin');
        if ($isSuperAdmin && '' !== $marketCode) {
            $io->error('Choisissez soit --super-admin, soit --market=XX, mais pas les deux.');
            return Command::INVALID;
        }
        if (!$isSuperAdmin && '' === $marketCode) {
            $io->error('Indiquez le marché avec --market=XX, ou utilisez --super-admin.');
            return Command::INVALID;
        }

        $market = null;
        if (!$isSuperAdmin) {
            $market = $this->entityManager->getRepository(Market::class)->findOneBy(['countryCode' => $marketCode]);
            if (!$market instanceof Market || 'US' === $market->getCountryCode()) {
                $io->error(sprintf('Le marché « %s » est introuvable ou ne peut pas être attribué.', $marketCode));
                return Command::INVALID;
            }
        }

        $plainPassword = $io->askHidden('Mot de passe (12 caractères minimum)');
        if (!is_string($plainPassword) || mb_strlen($plainPassword) < 12) {
            $io->error('Le mot de passe doit contenir au moins 12 caractères.');
            return Command::INVALID;
        }

        $user = $this->users->findOneBy(['email' => $email]) ?? (new User())->setEmail($email);
        $user->setRoles([$isSuperAdmin ? 'ROLE_SUPER_ADMIN' : 'ROLE_ADMIN']);
        $user->setMarket($market);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf(
            'Le compte %s est prêt (%s).',
            $email,
            $isSuperAdmin ? 'tous les marchés' : 'marché '.$market?->getName(),
        ));
        return Command::SUCCESS;
    }
}
