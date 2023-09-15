<?php

namespace Evomase\PasswordCrackerTest;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Logger\ConsoleLogger;

class Cracker {

  const CRACK_TYPE_NUMBERS = '/^[\d]+$/';

  const CRACK_TYPE_UPPERCASE_NUMERIC = '/^[A-Z0-9]+$/';

  const CRACK_TYPE_DICTIONARY = '/^[a-z]+$/';

  const CRACK_TYPE_MIX = '/^[a-zA-Z0-9]+$/';

  const SALT = 'ThisIs-A-Salt123';

  const DICTIONARY_FILE = __DIR__ . '/../data/words.txt';

  const CHARACTERS = [
    self::CRACK_TYPE_NUMBERS => '0123456789',
    self::CRACK_TYPE_UPPERCASE_NUMERIC => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    self::CRACK_TYPE_DICTIONARY => 'abcdefghijklmnopqrstuvwxyz',
    self::CRACK_TYPE_MIX => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
  ];

  /**
   * @var \Doctrine\DBAL\Connection
   */
  private Connection $connection;

  /**
   * @var \Symfony\Component\Console\Logger\ConsoleLogger
   */
  private ConsoleLogger $logger;

  /**
   * @var \Symfony\Component\Console\Helper\ProgressBar
   */
  private ProgressBar $progressBar;

  private bool $startMessageSent = FALSE;

  private array $dictionary = [];

  /**
   * @param \Doctrine\DBAL\Connection $connection
   * @param \Symfony\Component\Console\Logger\ConsoleLogger $logger
   * @param \Symfony\Component\Console\Helper\ProgressBar $progressBar
   */
  public function __construct(Connection $connection, ConsoleLogger $logger, ProgressBar $progressBar) {
    $this->connection = $connection;
    $this->logger = $logger;
    $this->progressBar = $progressBar;
  }

  /**
   * @param int $total
   * @param string $type
   * @param int $length
   *
   * @return array
   * @throws \Doctrine\DBAL\Exception
   */
  public function find(int $total, string $type = self::CRACK_TYPE_NUMBERS, int $length = 5): array {
    $characters = str_split(self::CHARACTERS[$type]);
    $charLength = count($characters);
    $users = [];
    $passwords = [];
    $generatedPasswords = [];

    while (count($users) < $total) {
      while (count($passwords) < 1000) {
        $password = $this->generatePassword($type, $characters, $charLength, $length);

        if (in_array($password, $generatedPasswords)) {
          continue;
        }

        if (!preg_match($type, $password)) {
          continue;
        }

        $generatedPasswords[] = $password;
        $passwords[] = $this->hash($password);
      }

      $this->setStartMessage($type);

      if ($found = $this->findUsersByPasswords($passwords)) {
        $users = array_merge($users, $found);
        $this->logger->debug(sprintf('Found user(s).. %s', implode(', ', $found)));
      }

      $passwords = [];
      $this->logger->debug('Generated ' . count($generatedPasswords) . ' passwords');
    }

    unset($generatedPasswords);
    unset($passwords);

    $this->logger->debug(sprintf('Found users.. %s', implode(', ', $users)));
    $this->progressBar->advance();

    return $users;
  }

  /**
   * @param $password
   *
   * @return string
   */
  private function hash($password): string {
    return md5($password . self::SALT);
  }

  /**
   * @param string $type
   * @param array $characters
   * @param int $charLength
   * @param int $length
   *
   * @return string
   */
  private function generatePassword(string $type, array $characters, int $charLength, int $length): string {
    $password = '';

    if ($type === self::CRACK_TYPE_DICTIONARY && $password = $this->generateDictionaryPassword($length)) {
      return $password;
    }

    if ($type === self::CRACK_TYPE_UPPERCASE_NUMERIC) {
      $length--;
    }

    for ($i = 0; $i < $length; $i++) {
      $password .= $characters[rand(0, $charLength - 1)];
    }

    if ($type === self::CRACK_TYPE_UPPERCASE_NUMERIC) {
      $password .= rand(0, 9);
      $password = str_shuffle($password);
    }

    return $password;
  }

  /**
   * @param int $length
   *
   * @return string
   */
  private function generateDictionaryPassword(int $length): string {
    static $pointer = 0;

    if (empty($this->dictionary)) {
      $this->loadDictionary($length);
    }

    if (empty($this->dictionary[$pointer])) {
      return '';
    }

    $password = strtolower($this->dictionary[$pointer]);
    $pointer++;

    return $password;
  }

  /**
   * @param int $length
   *
   * @return void
   */
  private function loadDictionary(int $length): void {
    if (empty($this->dictionary)) {
      $this->dictionary = array_values(array_filter(explode("\n", file_get_contents(self::DICTIONARY_FILE)),
        function ($word) use ($length) {
          return !empty($word) && strlen($word) <= $length;
        }));
    }
  }

  /**
   * @param $passwords
   *
   * @return array
   * @throws \Doctrine\DBAL\Exception
   */
  private function findUsersByPasswords($passwords): array {
    $query = $this->connection->createQueryBuilder();

    $result = $query->select('user_id')
      ->from('not_so_smart_users')
      ->where($query->expr()->in('password', ':password'))
      ->setParameter('password', $passwords, ArrayParameterType::STRING)
      ->executeQuery();

    if ($result->rowCount() > 0) {
      return $result->fetchNumeric();
    }

    return [];
  }

  /**
   * @param string $type
   *
   * @return void
   */
  private function setStartMessage(string $type): void {
    if (!$this->startMessageSent) {
      $message = 'Cracking.. ';

      switch ($type) {
        case self::CRACK_TYPE_NUMBERS:
          $this->renderMessage($message . 'The 4 user IDs who used 5 numbers as their passwords.');
          break;
        case self::CRACK_TYPE_UPPERCASE_NUMERIC:
          $this->renderMessage($message . 'The 4 user IDs who used just 3 Uppercase characters and 1 number as their password.');
          break;
        case self::CRACK_TYPE_DICTIONARY:
          $this->renderMessage($message . 'The 12 user IDs who used just lowercase dictionary words (Max 6 chars) as their passwords.');
          break;
        case self::CRACK_TYPE_MIX:
          $this->renderMessage($message . 'The 2 user IDs who used a 6 character passwords using a mix of Upper, Lowercase and numbers');
          break;
      }

      $this->startMessageSent = TRUE;
    }
  }

  /**
   * @param string $message
   *
   * @return void
   */
  private function renderMessage(string $message): void {
    $this->progressBar->setMessage($message);
    $this->progressBar->display();
  }

}