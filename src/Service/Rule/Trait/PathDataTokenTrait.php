<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait PathDataTokenTrait
{
    use NumberFormatterTrait;

    private const string PATH_TOKEN_NUMBER = 'number';

    private const string PATH_TOKEN_FLAG = 'flag';

    private const string PATH_TOKEN_TEXT = 'text';

    private const string PATH_TOKEN_DECIMAL_POINT = '.';

    private const string PATH_NUMBER_TOKEN_REGEX = '/\G(?>' . self::NUMBER_PATTERN . ')/';

    private const string PATH_ARC_FLAG_TOKEN_REGEX = '/\G[01]/';

    private const string PATH_TEXT_TOKEN_REGEX = '/\G(?:[\x20\x09\x0A\x0C\x0D]++|,|[MmLlHhVvCcSsQqTtAaZz])/';

    private const string PATH_COMMAND_LETTER_REGEX = '/^[MmLlHhVvCcSsQqTtAaZz]$/';

    private const array PATH_ARC_COMMAND_LETTERS = ['a', 'A'];

    private const int PATH_ARC_ARGUMENT_COUNT = 7;

    private const array PATH_ARC_FLAG_POSITIONS = [3, 4];

    /**
     * @param string $data ASCII path data, valid or not
     *
     * @return string the path data with every number written in its shortest form and every other character kept, or
     *                the path data unchanged if a number could be read differently afterwards
     */
    private function minifyPathDataNumbers(string $data): string
    {
        $tokens = $this->tokenizePathData($data);

        if ([] === $tokens || \in_array([self::PATH_TOKEN_TEXT, self::PATH_TOKEN_DECIMAL_POINT], $tokens, true)) {
            return $data;
        }

        $minifiedTokens = $this->minifyPathDataTokens($tokens);
        $minified = implode('', array_column($minifiedTokens, 1));

        return $this->tokenizePathData($minified) === $minifiedTokens ? $minified : $data;
    }

    /**
     * @param string $data the path data
     *
     * @return list<array{string, string}> the tokens as [type, text], or an empty list if the path data contains a
     *                                     character that is neither a number, a separator nor a command letter
     */
    private function tokenizePathData(string $data): array
    {
        $tokens = [];
        $offset = 0;
        $command = '';
        $argument = 0;
        $length = mb_strlen($data, '8bit');

        while ($offset < $length) {
            $token = $this->nextPathDataToken($data, $offset, $this->expectsPathArcFlag($command, $argument));

            if ([] === $token) {
                return [];
            }

            $tokens[] = $token;
            $offset += mb_strlen($token[1], '8bit');

            if (self::PATH_TOKEN_TEXT !== $token[0]) {
                ++$argument;
            } elseif (1 === preg_match(self::PATH_COMMAND_LETTER_REGEX, $token[1])) {
                $command = $token[1];
                $argument = 0;
            }
        }

        return $tokens;
    }

    /**
     * @param string $data        the path data
     * @param int    $offset      the byte offset to read from
     * @param bool   $expectsFlag whether an arc flag is expected at the offset
     *
     * @return array{string, string}|array{} the token as [type, text], or an empty array if no token is valid here
     */
    private function nextPathDataToken(string $data, int $offset, bool $expectsFlag): array
    {
        if ($expectsFlag && 1 === preg_match(self::PATH_ARC_FLAG_TOKEN_REGEX, $data, $match, 0, $offset)) {
            return [self::PATH_TOKEN_FLAG, $match[0]];
        }

        if (1 === preg_match(self::PATH_NUMBER_TOKEN_REGEX, $data, $match, 0, $offset)) {
            return [self::PATH_TOKEN_NUMBER, $match[0]];
        }

        if (1 === preg_match(self::PATH_TEXT_TOKEN_REGEX, $data, $match, 0, $offset)) {
            return [self::PATH_TOKEN_TEXT, $match[0]];
        }

        return [];
    }

    /**
     * @param string $command  the path command in effect
     * @param int    $argument the number of arguments read since the command letter
     */
    private function expectsPathArcFlag(string $command, int $argument): bool
    {
        return \in_array($command, self::PATH_ARC_COMMAND_LETTERS, true)
            && \in_array($argument % self::PATH_ARC_ARGUMENT_COUNT, self::PATH_ARC_FLAG_POSITIONS, true);
    }

    /**
     * @param list<array{string, string}> $tokens the tokens to minify
     *
     * @return list<array{string, string}> the minified tokens, with a space wherever two numbers would merge
     */
    private function minifyPathDataTokens(array $tokens): array
    {
        $minified = [];
        $previousType = self::PATH_TOKEN_TEXT;
        $previousText = '';

        foreach ($tokens as [$type, $text]) {
            if (self::PATH_TOKEN_NUMBER === $type) {
                $text = $this->minifyNumber($text);
            }

            if (self::PATH_TOKEN_NUMBER === $previousType && self::PATH_TOKEN_TEXT !== $type && $this->mergesWithNextToken($previousText, $text)) {
                $minified[] = [self::PATH_TOKEN_TEXT, ' '];
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
    private function mergesWithNextToken(string $number, string $next): bool
    {
        return 1 !== preg_match(self::PATH_NUMBER_TOKEN_REGEX, $number . $next, $match) || $match[0] !== $number;
    }
}
