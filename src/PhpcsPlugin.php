<?php

declare(strict_types=1);

namespace winwin\composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class PhpcsPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var IOInterface
     */
    private $io;

    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'installGitPreHook',
            'post-update-cmd' => 'installGitPreHook',
        ];
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
    }

    public function installGitPreHook(): void
    {
        $gitDir = '.git';
        if (!is_dir($gitDir)) {
            $this->io->isVeryVerbose() && $this->io->writeError('No .git found, not in vcs?');

            return;
        }
        $hooksDir = $gitDir.'/hooks';
        if (!is_dir($hooksDir)) {
            $this->io->isVeryVerbose() && $this->io->write(sprintf('%s dir not found, create it..,', $hooksDir));
            if (!mkdir($hooksDir) && !is_dir($hooksDir)) {
                $this->io->writeError(sprintf('Unable to create %s directory.', $hooksDir));

                return;
            }
        }

        $newHookFile = __DIR__.'/../resources/pre-commit';
        if (!file_exists($newHookFile) && !file_exists('.php_cs.cache')) {
            // maybe removed by composer
            return;
        }
        if (!file_exists('.php-cs-fixer.dist.php')) {
            copy(__DIR__.'/../resources/php-cs-fixer.dist.php', '.php-cs-fixer.dist.php');
        }
        if (file_exists('.php_cs.cache')) {
            @unlink('.php_cs.cache');
        }
        if (file_exists('.php_cs')) {
           $this->io->info('.php_cs is now rename .php-cs-fixer.dist.php. See https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/UPGRADE-v3.md for upgrade instructions');
        }
        $hookFile = $hooksDir.'/pre-commit.phpcs';
        if (file_exists($hookFile)
            && md5_file($hookFile) === md5_file($newHookFile)) {
            $this->io->isVeryVerbose() && $this->io->writeError('pre-commit hook file exists, skip');

            return;
        }
        copy($newHookFile, $hookFile);
        chmod($hookFile, 0755);

        $preCommit = $hooksDir.'/pre-commit';
        if (!file_exists($preCommit)
            || md5_file($preCommit) === md5_file($newHookFile)) {
            file_put_contents($preCommit, '#!/bin/sh'."\n");
        }
        if (false === strpos(file_get_contents($preCommit), 'pre-commit.phpcs')) {
            file_put_contents($preCommit, "\n".'$(dirname $0)/pre-commit.phpcs'."\n", FILE_APPEND);
        }
        chmod($preCommit, 0755);
        $this->io->write("<info>Install git hook script $hookFile</>");
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }
}
