<?php

namespace Istvan\ComposerFocusModuleInstaller;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Symfony\Component\Process\Process;

class ModuleInstaller extends LibraryInstaller
{
    /**
     * Meghatározza a telepítési útvonalat
     */
    public function getInstallPath(PackageInterface $package)
    {
        $moduleName = self::getModuleNameForPackage($package);

        return "Modules/{$moduleName}";
    }

    /**
     * Csak focus-module típusokat kezeli
     */
    public function supports($packageType)
    {
        return $packageType === 'focus-module';
    }

    /**
     * Post install event
     */
    public static function postPackageInstall(PackageEvent $event)
    {
        $operation = $event->getOperation();

        if (!method_exists($operation, 'getPackage')) {
            return;
        }

        $package = $operation->getPackage();

        if ($package->getType() !== 'focus-module') {
            return;
        }

        $moduleName = self::getModuleNameForPackage($package);

        self::executeArtisanCommand(
            $event->getIO(),
            "module:setup {$moduleName}"
        );
    }

    /**
     * Post update event
     */
    public static function postPackageUpdate(PackageEvent $event)
    {
        $operation = $event->getOperation();

        if (method_exists($operation, 'getTargetPackage')) {
            $package = $operation->getTargetPackage();
        } elseif (method_exists($operation, 'getPackage')) {
            $package = $operation->getPackage();
        } else {
            return;
        }

        if ($package->getType() !== 'focus-module') {
            return;
        }

        $moduleName = self::getModuleNameForPackage($package);

        self::executeArtisanCommand(
            $event->getIO(),
            "module:setup {$moduleName}"
        );
    }

    /**
     * Post uninstall event
     */
    public static function postPackageUninstall(PackageEvent $event)
    {
        $operation = $event->getOperation();

        if (!method_exists($operation, 'getPackage')) {
            return;
        }

        $package = $operation->getPackage();

        if ($package->getType() !== 'focus-module') {
            return;
        }

        $moduleName = self::getModuleNameForPackage($package);

        self::executeArtisanCommand(
            $event->getIO(),
            "module:remove {$moduleName}",
            true
        );
    }

    /**
     * Modul név meghatározása
     *
     * prioritás:
     * 1. extra.module.name
     * 2. package name fallback
     */
    public static function getModuleNameForPackage(PackageInterface $package)
    {
        $extra = $package->getExtra();

        // prefer explicit module name
        if (isset($extra['module']['name'])) {
            return $extra['module']['name'];
        }

        // fallback: package name → PascalCase
        $packageName = $package->getPrettyName();

        if (str_contains($packageName, '/')) {
            $packageName = explode('/', $packageName)[1];
        }

        $moduleName = str_replace('-', ' ', $packageName);
        $moduleName = ucwords($moduleName);

        return str_replace(' ', '', $moduleName);
    }

    /**
     * Artisan command futtatása
     */
    public static function executeArtisanCommand(
        IOInterface $io,
        string $command,
        bool $ignoreErrors = false
    ): bool {

        $cwd = getcwd();
        $artisan = $cwd . DIRECTORY_SEPARATOR . 'artisan';

        if (!file_exists($artisan)) {
            $io->writeError(
                "<error>Artisan fájl nem található: {$artisan}</error>"
            );
            return false;
        }

        if (!file_exists($cwd . '/vendor/autoload.php')) {
            $io->write(
                "<comment>Autoload még nem elérhető, parancs kihagyva</comment>"
            );
            return true;
        }

        $process = new Process(
            array_merge(['php', $artisan], explode(' ', $command))
        );

        $process->setTimeout(300);
        $process->setWorkingDirectory($cwd);

        try {

            $io->write(
                "<comment>Executing: php artisan {$command}</comment>"
            );

            $process->mustRun(function ($type, $buffer) use ($io) {
                $io->write($buffer);
            });

            return true;

        } catch (\Throwable $e) {

            if (!$ignoreErrors) {

                $io->writeError(
                    "<error>Module installer error: {$e->getMessage()}</error>"
                );

            }

            return false;
        }
    }
}
