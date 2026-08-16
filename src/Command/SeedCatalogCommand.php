<?php

namespace App\Command;

use App\DataFixtures\CatalogFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:catalog:seed', description: 'Initialise le catalogue, les marchés et les entrepôts AURIM.')]
final class SeedCatalogCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CatalogFixtures $fixtures,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->fixtures->load($this->entityManager);
        $io->success('Les 9 produits du catalogue, les marchés et les entrepôts AURIM sont initialisés.');

        return Command::SUCCESS;
    }
}
