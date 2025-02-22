<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7c14f19059e2f8ed732f587baee6be06
{
    public static $prefixLengthsPsr4 = array (
        'a' => 
        array (
            'apimatic\\jsonmapper\\' => 20,
        ),
        'S' => 
        array (
            'Square\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'apimatic\\jsonmapper\\' => 
        array (
            0 => __DIR__ . '/..' . '/apimatic/jsonmapper/src',
        ),
        'Square\\' => 
        array (
            0 => __DIR__ . '/..' . '/square/square/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'U' => 
        array (
            'Unirest\\' => 
            array (
                0 => __DIR__ . '/..' . '/apimatic/unirest-php/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7c14f19059e2f8ed732f587baee6be06::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7c14f19059e2f8ed732f587baee6be06::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit7c14f19059e2f8ed732f587baee6be06::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit7c14f19059e2f8ed732f587baee6be06::$classMap;

        }, null, ClassLoader::class);
    }
}
