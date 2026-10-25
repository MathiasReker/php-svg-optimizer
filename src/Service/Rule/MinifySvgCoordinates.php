<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MinifySvgCoordinates implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use NumberFormatterTrait;

    private const string TOKEN_NUMBER = 'number';

    private const string TOKEN_FLAG = 'flag';

    private const string TOKEN_TEXT = 'text';

    private const string DECIMAL_POINT = '.';

    private const string NUMBER_TOKEN_REGEX = '/\G(?>' . self::NUMBER_PATTERN . ')/';

    private const string ARC_FLAG_REGEX = '/\G[01]/';

    private const string PATH_TEXT_REGEX = '/\G(?:[\x20\x09\x0A\x0C\x0D]++|,|[MmLlHhVvCcSsQqTtAaZz])/';

    private const string PATH_COMMAND_LETTER_REGEX = '/^[MmLlHhVvCcSsQqTtAaZz]$/';

    private const string NON_ASCII_REGEX = '/[^\x00-\x7F]/';

    private const array ARC_COMMANDS = ['a', 'A'];

    private const int ARC_ARGUMENT_COUNT = 7;

    private const array ARC_FLAG_POSITIONS = [3, 4];

    private const array ELEMENTS_TO_ATTRIBUTES = [
        '//svg:path' => [SvgAttribute::D->value],
        '//svg:rect | //svg:circle | //svg:ellipse | //svg:line | //svg:polyline | //svg:polygon | //svg:svg' => [
            SvgAttribute::X->value,
            SvgAttribute::X1->value,
            SvgAttribute::X2->value,
            SvgAttribute::Y->value,
            SvgAttribute::Y1->value,
            SvgAttribute::Y2->value,
            SvgAttribute::Width->value,
            SvgAttribute::Height->value,
            SvgAttribute::Cx->value,
            SvgAttribute::Cy->value,
            SvgAttribute::Rx->value,
            SvgAttribute::Ry->value,
            SvgAttribute::R->value,
            SvgAttribute::Points->value,
            SvgAttribute::D->value,
        ],
        '//svg:svg' => [SvgAttribute::ViewBox->value, SvgAttribute::EnableBackground->value],
    ];

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($this->mayMatchAttributeValues($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(SvgNamespace::Svg->prefix(), SvgNamespace::Svg->value);

        foreach (self::ELEMENTS_TO_ATTRIBUTES as $query => $attributes) {
            $this->minifyElements($domXPath, $query, $attributes);
        }
    }

    /**
     * @param \DOMXPath    $domXPath   the XPath object for querying the document
     * @param string       $query      the XPath query to find elements
     * @param list<string> $attributes the attributes to minify on the found elements
     */
    private function minifyElements(\DOMXPath $domXPath, string $query, array $attributes): void
    {
        $nodes = $domXPath->query($query);

        /** @var \DOMElement $node */
        foreach (false === $nodes ? [] : $nodes as $node) {
            $this->minifyAttributesOnElement($node, $attributes);
        }
    }

    /**
     * @param \DOMElement  $domElement the element to process
     * @param list<string> $attributes the attributes to minify on the element
     */
    private function minifyAttributesOnElement(\DOMElement $domElement, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            if ($domElement->hasAttribute($attribute)) {
                $domElement->setAttribute($attribute, $this->minifyCoordinates(
                    $domElement->getAttribute($attribute),
                    SvgAttribute::D->value === $attribute,
                ));
            }
        }
    }

    /**
     * @param string $value      the string containing coordinates to minify
     * @param bool   $isPathData whether the value is path data
     *
     * @return string the minified coordinate string
     */
    private function minifyCoordinates(string $value, bool $isPathData): string
    {
        if (1 === preg_match(self::NON_ASCII_REGEX, $value)) {
            return $value;
        }

        $tokens = $this->tokenize($value, $isPathData);

        if ([] === $tokens || \in_array([self::TOKEN_TEXT, self::DECIMAL_POINT], $tokens, true)) {
            return $value;
        }

        $minifiedTokens = $this->minifyTokens($tokens);
        $minified = implode('', array_column($minifiedTokens, 1));

        if ($this->tokenize($minified, $isPathData) !== $minifiedTokens) {
            return $value;
        }

        return $minified;
    }

    /**
     * @param string $value      the attribute value
     * @param bool   $isPathData whether the value is path data, where arc flags are single characters
     *
     * @return list<array{string, string}> the tokens as [type, text], or an empty list if the value cannot be tokenized
     */
    private function tokenize(string $value, bool $isPathData): array
    {
        $tokens = [];
        $offset = 0;
        $command = '';
        $argument = 0;
        $length = mb_strlen($value);

        while ($offset < $length) {
            $token = $this->nextToken($value, $offset, $isPathData, $this->expectsArcFlag($command, $argument));

            if ([] === $token) {
                return [];
            }

            $tokens[] = $token;
            $offset += mb_strlen($token[1]);

            if (self::TOKEN_TEXT !== $token[0]) {
                ++$argument;
            } elseif (1 === preg_match(self::PATH_COMMAND_LETTER_REGEX, $token[1])) {
                $command = $token[1];
                $argument = 0;
            }
        }

        return $tokens;
    }

    /**
     * @param string $value       the attribute value
     * @param int    $offset      the byte offset to read from
     * @param bool   $isPathData  whether the value is path data
     * @param bool   $expectsFlag whether an arc flag is expected at the offset
     *
     * @return array{string, string}|array{} the token as [type, text], or an empty array if no token is valid here
     */
    private function nextToken(string $value, int $offset, bool $isPathData, bool $expectsFlag): array
    {
        if ($expectsFlag && 1 === preg_match(self::ARC_FLAG_REGEX, $value, $match, 0, $offset)) {
            return [self::TOKEN_FLAG, $match[0]];
        }

        if (1 === preg_match(self::NUMBER_TOKEN_REGEX, $value, $match, 0, $offset)) {
            return [self::TOKEN_NUMBER, $match[0]];
        }

        if (!$isPathData) {
            return [self::TOKEN_TEXT, $value[$offset]];
        }

        if (1 === preg_match(self::PATH_TEXT_REGEX, $value, $match, 0, $offset)) {
            return [self::TOKEN_TEXT, $match[0]];
        }

        return [];
    }

    /**
     * @param string $command  the path command in effect
     * @param int    $argument the number of arguments read since the command letter
     */
    private function expectsArcFlag(string $command, int $argument): bool
    {
        return \in_array($command, self::ARC_COMMANDS, true) && \in_array($argument % self::ARC_ARGUMENT_COUNT, self::ARC_FLAG_POSITIONS, true);
    }

    /**
     * @param list<array{string, string}> $tokens the tokens to minify
     *
     * @return list<array{string, string}> the minified tokens, with a space wherever two numbers would merge
     */
    private function minifyTokens(array $tokens): array
    {
        $minified = [];
        $previousType = self::TOKEN_TEXT;
        $previousText = '';

        foreach ($tokens as [$type, $text]) {
            if (self::TOKEN_NUMBER === $type) {
                $text = $this->minifyNumber($text);
            }

            if (self::TOKEN_NUMBER === $previousType && self::TOKEN_TEXT !== $type && $this->mergesWithNext($previousText, $text)) {
                $minified[] = [self::TOKEN_TEXT, ' '];
            }

            $previousType = $type;
            $previousText = $text;
            $minified[] = [$type, $text];
        }

        return $minified;
    }

    /**
     * @param string $number the number written first
     * @param string $next   the text written directly after it
     */
    private function mergesWithNext(string $number, string $next): bool
    {
        return 1 !== preg_match(self::NUMBER_TOKEN_REGEX, $number . $next, $match) || $match[0] !== $number;
    }
}
