<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Integration;

use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigFile;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\Tests\Double\ProcessTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class SchemaTest extends TestCase
{
    use ProcessTrait;

    private const string SCHEMA = __DIR__ . '/../../resources/svg-optimizer.schema.json';

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function theCommittedSchemaIsUpToDate(): void
    {
        $result = $this->runProcess([\PHP_BINARY, 'dev/scripts/build-schema.php', '--check'], (string) realpath(__DIR__ . '/../..'));

        self::assertSame('', $result['stderr'], 'Run "composer build:schema" and commit resources/svg-optimizer.schema.json.');
        self::assertSame(0, $result['exitCode']);
    }

    /**
     * @throws \JsonException
     */
    #[Test]
    public function theSchemaDocumentsEveryRuleWithItsDescriptionAndRiskyFlag(): void
    {
        $rules = $this->child($this->child($this->readSchema(), 'properties'), 'rules');
        $properties = $this->child($rules, 'properties');

        self::assertSame(array_column(Rule::cases(), 'value'), array_keys($properties));

        foreach (Rule::cases() as $rule) {
            $property = $this->child($properties, $rule->value);

            $description = $property['description'];

            self::assertIsString($description);
            self::assertSame('boolean', $property['type']);
            self::assertStringStartsWith($rule->getDescription(), $description);
            self::assertSame($rule->isRisky(), $property['x-risky']);
            self::assertSame($rule->isRisky(), str_contains($description, 'Risky'));
        }
    }

    /**
     * @throws \JsonException
     */
    #[Test]
    public function theSchemaListsEveryRuleSetAndTheDefaultAlias(): void
    {
        $sets = $this->child($this->child($this->readSchema(), 'properties'), 'sets');

        self::assertSame(['default', ...array_column(RuleSet::cases(), 'value')], $this->child($sets, 'items')['enum']);
    }

    /**
     * @throws \JsonException
     */
    #[Test]
    public function theSchemaListsTheKeysTheConfigurationAccepts(): void
    {
        $schema = $this->readSchema();

        self::assertSame(['$schema', 'sets', 'rules', 'allowRisky'], array_keys($this->child($schema, 'properties')));
        self::assertFalse($schema['additionalProperties']);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function theConfigurationAcceptsEveryRuleAndSetOfTheSchema(): void
    {
        $configFile = ConfigFile::fromJson((string) json_encode([
            '$schema' => 'https://example.com/schema.json',
            'sets' => ['default', ...array_column(RuleSet::cases(), 'value')],
            'rules' => array_fill_keys(array_column(Rule::cases(), 'value'), true),
            'allowRisky' => true,
        ]));

        self::assertSame(Rule::cases(), $configFile->getRules());
        self::assertSame(RuleSet::cases(), $configFile->getRuleSets());
        self::assertTrue($configFile->isDefaultSelected());
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws \JsonException
     */
    private function readSchema(): array
    {
        $schema = json_decode((string) file_get_contents(self::SCHEMA), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($schema);

        return $schema;
    }

    /**
     * @param array<array-key, mixed> $node the node of the schema
     * @param string                  $key  the key of the child
     *
     * @return array<array-key, mixed> the child
     */
    private function child(array $node, string $key): array
    {
        self::assertArrayHasKey($key, $node);
        self::assertIsArray($node[$key]);

        return $node[$key];
    }
}
