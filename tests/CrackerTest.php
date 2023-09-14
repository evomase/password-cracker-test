<?php

namespace Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Evomase\PasswordCrackerTest\Cracker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Logger\ConsoleLogger;

class CrackerTest extends TestCase {

  /**
   * @throws \Doctrine\DBAL\Exception
   * @throws \PHPUnit\Framework\MockObject\Exception
   */
  public function test__find() {
    $logger = $this->createStub(ConsoleLogger::class);

    $connection = $this->createStub(Connection::class);
    $query = $this->createStub(QueryBuilder::class)
      ->method('executeQuery');
    $query->willReturnCallback(function () use ($query) {
    });

    $connection->method('createQueryBuilder')
      ->willReturn($query);
    $progressBar = $this->createStub(ProgressBar::class);

    $cracker = new Cracker($connection, $logger, $progressBar);
    $cracker->find(1, Cracker::CRACK_TYPE_NUMBERS, 2);

  }

}
