<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Architecture;

use Composer\InstalledVersions;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * @internal
 */
final class LayerTest
{
    private const string PACKAGE = 'MathiasReker\PhpSvgOptimizer';

    #[TestRule]
    public function theRootApiDependsOnlyOnThePassXmlSvgAndFileLayers(): BuildStep
    {
        return PHPat::rule()
            ->classes($this->rootApi())
            ->canOnly()->dependOn()
            ->classes($this->rootApi(), $this->layer('Pass'), $this->layer('Xml'), $this->layer('Svg'), $this->layer('File'))
            ->because('the root namespace is the public API that wires the internal layers');
    }

    #[TestRule]
    public function passesDependOnlyOnTheSvgXmlAndExceptionLayers(): BuildStep
    {
        return PHPat::rule()
            ->classes($this->layer('Pass'))
            ->canOnly()->dependOn()
            ->classes($this->layer('Pass'), $this->layer('Svg'), $this->layer('Xml'), $this->layer(\Exception::class))
            ->because('passes transform a DOM document and know nothing of the builder or the command line tool');
    }

    #[TestRule]
    public function theSvgVocabularyDependsOnNothingElseOfThePackage(): BuildStep
    {
        return PHPat::rule()
            ->classes($this->layer('Svg'))
            ->canOnly()->dependOn()
            ->classes($this->layer('Svg'), $this->layer(\Exception::class))
            ->because('the SVG vocabulary is the lowest layer');
    }

    #[TestRule]
    public function theXmlLayerDependsOnlyOnTheSvgAndExceptionLayers(): BuildStep
    {
        return PHPat::rule()
            ->classes($this->layer('Xml'))
            ->canOnly()->dependOn()
            ->classes($this->layer('Xml'), $this->layer('Svg'), $this->layer(\Exception::class))
            ->because('parsing and serializing know nothing of passes, the builder or the command line tool');
    }

    #[TestRule]
    public function theFileLayerDependsOnlyOnTheExceptionLayer(): BuildStep
    {
        return PHPat::rule()
            ->classes($this->layer('File'))
            ->canOnly()->dependOn()
            ->classes($this->layer('File'), $this->layer(\Exception::class))
            ->because('file access knows nothing of SVG processing');
    }

    #[TestRule]
    public function theCommandLineToolDependsOnlyOnThePublicApiAndTheFileLayer(): BuildStep
    {
        return PHPat::rule()
            ->classes($this->layer('Console'))
            ->canOnly()->dependOn()
            ->classes(
                $this->layer('Console'),
                $this->rootApi(),
                $this->layer('File'),
                Selector::classname(InstalledVersions::class),
            )
            ->because('the command line tool is a client of the public API');
    }

    #[TestRule]
    public function nothingOutsideTheCommandLineToolDependsOnIt(): BuildStep
    {
        return PHPat::rule()
            ->classes($this->package())
            ->excluding($this->layer('Console'))
            ->shouldNot()->dependOn()
            ->classes($this->layer('Console'))
            ->because('the library must work without the command line tool');
    }

    private function rootApi(): SelectorInterface
    {
        return Selector::AnyOf(
            Selector::inNamespace('/^' . preg_quote(self::PACKAGE, '/') . '$/', true),
            $this->layer(\Exception::class),
        );
    }

    /**
     * @param non-empty-string $name the namespace of the layer below the package namespace
     */
    private function layer(string $name): SelectorInterface
    {
        return Selector::inNamespace(self::PACKAGE . '\\' . $name);
    }

    private function package(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::inNamespace(self::PACKAGE),
            Selector::Not($this->layer('Tests')),
        );
    }
}
