<?php

namespace FocusCMS\ComposerModuleInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ModulePlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        $installer = new ModuleInstaller($io, $composer);

        $composer
            ->getInstallationManager()
            ->addInstaller($installer);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // no-op
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // no-op
    }
}