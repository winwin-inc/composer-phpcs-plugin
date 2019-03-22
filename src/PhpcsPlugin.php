<?php

namespace winwin\composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;

class PhpcsPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var IOInterface
     */
    private $io;
    
    public static function getSubscribedEvents()
    {
        return array(
            'post-install-cmd' => 'installGitPreHook',
            'post-update-cmd' => 'installGitPreHook'
        );
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
    }

    public function installGitPreHook()
    {
        $hooksDir = '.git/hooks';
        if (!is_dir($hooksDir)) {
            $this->io->isVeryVerbose() && $this->io->writeError("No .git found, not in vcs?");
            return;
        }
        if (!file_exists('.php_cs')) {
            copy(__DIR__.'/../resources/php_cs', '.php_cs');
        }
        $newHookFile = __DIR__.'/../resources/pre-commit';
        if (file_exists($hookFile = $hooksDir . '/pre-commit')
            && md5_file($hookFile) == md5_file($newHookFile)) {
            $this->io->isVeryVerbose() && $this->io->writeError("pre-commit hook file exists, skip");
            return;
        }
        copy($newHookFile, $hookFile);
        chmod($hookFile, 0755);
        $this->io->write("<info>Install git hook script $hookFile</>");
    }
}
