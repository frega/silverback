<?php

namespace AmazeeLabs\Silverback\Commands;

use Dotenv\Dotenv;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SilverbackCommand extends Command {

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fileSystem;

  /**
   * The current project root directory.
   *
   * @var string
   */
  protected $rootDirectory;

  protected $cacheDir;

  public function __construct(Filesystem $fileSystem) {
    parent::__construct();
    $this->fileSystem = $fileSystem;
    $this->cacheDir = '/tmp/silverback/cache';
    $fileSystem->mkdir($this->cacheDir);
    if ($fileSystem->exists('.env')) {
      $env = new Dotenv(getcwd());
      $env->load();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!file_exists('composer.json')) {
      $output->writeln('<error>composer.json not found. Please run this command from composer based Drupal installations root directory.</error>');
      exit(1);
    }
    // TODO: scan upwards and detect root directory?
    $this->rootDirectory = getcwd();
  }

  protected function copyDir($source, $destination) {
    $finder = new Finder();
    $finder->files()->in($source);
    $finder->ignoreDotFiles(FALSE);
    foreach ($finder as $file) {
      $this->fileSystem->copy(
        rtrim($source, '/') . '/' . $file->getRelativePath() . '/' . $file->getFilename(),
        rtrim($destination) . '/' . $file->getRelativePath() . '/' . $file->getFilename()
      );
    }
  }

  protected function getConfigDirectory() {
    $configDir = 'config/sync';
    if (!$this->fileSystem->exists('config/sync/core.extension.yml')) {
      $this->copyDir('vendor/amazeelabs/silverback/config', 'config/sync');
    }
    return $configDir;
  }

  protected function getConfigHash() {
    $configDir = $this->getConfigDirectory();

    $finder = new Finder();
    $finder->files()->in($this->rootDirectory .'/'. $configDir);
    $files = [];
    foreach ($finder as $file) {
      $files[] = md5(file_get_contents($file->getRealPath()));
    }


    return md5(serialize($files));
  }

}
