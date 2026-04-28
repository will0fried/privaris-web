<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un compte administrateur Privaris (à utiliser en prod pour le premier admin).',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'admin')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Nom affiché', null)
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'Rôle (ROLE_ADMIN ou ROLE_SUPER_ADMIN)', 'ROLE_SUPER_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = strtolower(trim($input->getArgument('email')));
        $role = $input->getOption('role');

        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
            $io->error("Un utilisateur existe déjà avec l'email : $email");
            return Command::FAILURE;
        }

        $password = $io->askHidden('Mot de passe (min 12 caractères)', function ($value) {
            if (strlen((string) $value) < 12) {
                throw new \RuntimeException('Trop court : 12 caractères minimum.');
            }
            return $value;
        });

        $user = (new User())
            ->setEmail($email)
            ->setDisplayName($input->getOption('name') ?: $email)
            ->setRoles([$role]);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success("Compte administrateur créé : $email (rôle $role)");
        $io->note('Activez le 2FA depuis l\'admin dès la première connexion.');
        return Command::SUCCESS;
    }
}
