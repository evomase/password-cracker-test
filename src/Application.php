<?php

namespace Evomase\PasswordCrackerTest;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;
use Evomase\PasswordCrackerTest\Commands\CrackCommand;
use Symfony\Component\Console\Application as Console;

class Application {

  private static Console $application;

  private static Connection $connection;

  /**
   * @throws \Exception
   */
  public static function run(): void {
    if (empty(self::$application)) {
      Dotenv::createImmutable(__DIR__ . '/..')->load();

      self::$application = new Console();
      self::$connection = DriverManager::getConnection(
        [
          'driver' => 'pdo_mysql',
          'host' => $_ENV['DB_HOST'],
          'dbname' => $_ENV['DB_NAME'],
          'user' => $_ENV['DB_USER'],
          'password' => $_ENV['DB_PASSWORD'],
          'charset' => 'utf8',
          'collation' => 'utf8_unicode_ci',
          'prefix' => '',
        ]
      );
    }

    $command = new CrackCommand(self::$connection);
    self::$application->add($command);
    self::$application->setDefaultCommand($command->getName(), TRUE);

    self::$application->run();
  }

}