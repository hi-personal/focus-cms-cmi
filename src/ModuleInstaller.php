<?php

namespace FocusCMS\ComposerModuleInstaller;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

class ModuleInstaller extends LibraryInstaller
{
    /**
     * Meghatározza a telepítési útvonalat
     */
    public function getInstallPath(PackageInterface $package): string
    {
        $moduleName = self::getModuleNameForPackage($package);

        return "Modules/{$moduleName}";
    }

    /**
     * Csak focus-module típusokat kezeli
     */
    public function supports($packageType): bool
    {
        return $packageType === 'focus-module';
    }

    /**
     * Modul név meghatározása
     *
     * prioritás:
     * 1. extra.module.name
     * 2. package name fallback (PascalCase)
     */
    public static function getModuleNameForPackage(
        PackageInterface $package
    ): string {

        $extra = $package->getExtra();

        // explicit module name
        if (isset($extra['module']['name'])) {
            return $extra['module']['name'];
        }

        // fallback: vendor/package-name → PackageName
        $packageName = $package->getPrettyName();

        if (str_contains($packageName, '/')) {
            $packageName = explode('/', $packageName)[1];
        }

        $moduleName = str_replace('-', ' ', $packageName);
        $moduleName = ucwords($moduleName);

        return str_replace(' ', '', $moduleName);
    }
}