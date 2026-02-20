<?php

namespace Istvan\ComposerFocusModuleInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Script\Event;

class ModulePlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $installer = new ModuleInstaller($io, $composer);

        $composer
            ->getInstallationManager()
            ->addInstaller($installer);
    }

    public function deactivate(Composer $composer, IOInterface $io) {}

    public function uninstall(Composer $composer, IOInterface $io) {}

    public static function getSubscribedEvents()
    {
        return [

            'post-package-install' => 'onPostPackageInstall',

            'post-package-update' => 'onPostPackageUpdate',

            'post-package-uninstall' => 'onPostPackageUninstall',

            'post-update-cmd' => 'onPostUpdate',

        ];
    }

    public function onPostPackageInstall(PackageEvent $event)
    {
        ModuleInstaller::postPackageInstall($event);
    }

    public function onPostPackageUpdate(PackageEvent $event)
    {
        ModuleInstaller::postPackageUpdate($event);
    }

    public function onPostPackageUninstall(PackageEvent $event)
    {
        ModuleInstaller::postPackageUninstall($event);
    }

    public function onPostUpdate(Event $event)
    {
        $composer = $event->getComposer();

        $io = $event->getIO();

        $packages = $composer
            ->getRepositoryManager()
            ->getLocalRepository()
            ->getPackages();

        foreach ($packages as $package) {

            if ($package->getType() === 'focus-module') {

                $moduleName =
                    ModuleInstaller::getModuleNameForPackage($package);

                ModuleInstaller::executeArtisanCommand(
                    $io,
                    "module:setup {$moduleName}"
                );

            }

        }
    }
}
