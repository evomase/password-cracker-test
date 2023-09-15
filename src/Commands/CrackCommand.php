<?php

namespace Evomase\PasswordCrackerTest\Commands;

use Doctrine\DBAL\Connection;
use Evomase\PasswordCrackerTest\Cracker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'app:crack',
  description: 'Cracks the users passwords',
  aliases: ['app:crack'],
  hidden: FALSE
)]
class CrackCommand extends Command {

  /**
   * @var \Doctrine\DBAL\Connection
   */
  private Connection $connection;

  public function __construct(Connection $connection) {
    $this->connection = $connection;

    parent::__construct();
  }


  /**
   * @throws \Doctrine\DBAL\Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);

    $progressBar = $this->createProgressBar($output);
    $progressBar->setMessage('Lets crack the users passwords');
    $progressBar->start();

    $table = $this->createTable($output);

    $this->addUsers($table, $output, (new Cracker($this->connection, new ConsoleLogger($output), $progressBar))->find(4));
    $this->addUsers($table, $output, (new Cracker($this->connection, new ConsoleLogger($output), $progressBar))->find(4, Cracker::CRACK_TYPE_UPPERCASE_NUMERIC, 4));
    $this->addUsers($table, $output, (new Cracker($this->connection, new ConsoleLogger($output), $progressBar))->find(12, Cracker::CRACK_TYPE_DICTIONARY, 6));
    $this->addUsers($table, $output, (new Cracker($this->connection, new ConsoleLogger($output), $progressBar))->find(12, Cracker::CRACK_TYPE_MIX, 6));

    $progressBar->finish();

    return 0;
  }

  /**
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *
   * @return \Symfony\Component\Console\Helper\ProgressBar
   */
  private function createProgressBar(OutputInterface $output): ProgressBar {
    $progressBar = new ProgressBar($output, 4);
    $progressBar->setBarCharacter('<fg=green>⚬</>');
    $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
    $progressBar->setProgressCharacter("<fg=green>➤</>");
    $progressBar->setFormat(
      "<fg=white;bg=cyan> %message:-45s%</>\n%current%/%max% [%bar%] %percent:3s%%\n🏁 %estimated:-20s%  %memory:20s%"
    );
    $progressBar->setRedrawFrequency(10);

    return $progressBar;
  }

  private function createTable(OutputInterface $output): Table {
    $table = new Table($output);
    $table->setHeaders(['User ID']);
    $table->setStyle('box');

    return $table;
  }

  /**
   * @param \Symfony\Component\Console\Helper\Table $table
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param array $users
   *
   * @return void
   */
  private function addUsers(Table $table, OutputInterface $output, array $users): void {
    foreach ($users as $user) {
      $table->addRow([$user]);
    }

    $output->writeln('');
    $table->render();
    $output->writeln("\n\n");
  }

}