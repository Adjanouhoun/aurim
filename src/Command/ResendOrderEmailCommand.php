<?php

namespace App\Command;

use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Notification\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:order:resend-email', description: 'Renvoie les e-mails d’une commande AURIM.')]
final class ResendOrderEmailCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderMailer $orderMailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('reference', InputArgument::REQUIRED, 'Référence de la commande');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $reference = (string) $input->getArgument('reference');
        $order = $this->entityManager->getRepository(CustomerOrder::class)->findOneBy(['reference' => $reference]);
        if (!$order instanceof CustomerOrder) {
            $io->error('Commande introuvable.');
            return Command::FAILURE;
        }
        $payment = $this->entityManager->getRepository(Payment::class)->findOneBy(['customerOrder' => $order]);
        if (!$payment instanceof Payment) {
            $io->error('Paiement associé introuvable.');
            return Command::FAILURE;
        }

        $this->orderMailer->sendOrderCreated($order, $payment);
        $io->success(sprintf('E-mails renvoyés pour %s.', $reference));

        return Command::SUCCESS;
    }
}
