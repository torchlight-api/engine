<?php

namespace Torchlight\Engine\Generators\TokenTransformers;

class TreeConnectorGrid
{
    // Bitfield constants for connection directions
    public const HALF_HORIZONTAL_LEFT = 1;   // 1 << 0

    public const HALF_HORIZONTAL_RIGHT = 2;   // 1 << 1

    public const HALF_VERTICAL_UP = 4;   // 1 << 2

    public const HALF_VERTICAL_DOWN = 8;   // 1 << 3

    public const PHANTOM = 16;  // 1 << 4 (invisible connector for alignment)

    // Combined masks for common patterns
    public const FULL_HORIZONTAL = self::HALF_HORIZONTAL_LEFT + self::HALF_HORIZONTAL_RIGHT;  // 3

    public const FULL_VERTICAL = self::HALF_VERTICAL_UP + self::HALF_VERTICAL_DOWN;        // 12

    public const TL_CORNER = self::HALF_HORIZONTAL_RIGHT + self::HALF_VERTICAL_DOWN;   // 10 (┌)

    public const TR_CORNER = self::HALF_HORIZONTAL_LEFT + self::HALF_VERTICAL_DOWN;    // 9 (┐)

    public const BL_CORNER = self::HALF_HORIZONTAL_RIGHT + self::HALF_VERTICAL_UP;     // 6 (└)

    public const BR_CORNER = self::HALF_HORIZONTAL_LEFT + self::HALF_VERTICAL_UP;      // 5 (┘)

    public const FULL_CROSS = self::HALF_HORIZONTAL_RIGHT
        + self::HALF_HORIZONTAL_LEFT
        + self::HALF_VERTICAL_DOWN
        + self::HALF_VERTICAL_UP;

    /**
     * @param  array<int, array{depth:int, isCommentOnly:bool, isDirectory:bool, content:string}>  $lines
     * @return array<int, list<int|string>>
     */
    public function build(array $lines): array
    {
        $rows = [];
        $lineCount = count($lines);

        // Initialize grid with spaces for each row up to content depth
        for ($r = 0; $r < $lineCount; $r++) {
            $rows[$r] = [];
            for ($c = 0; $c < $lines[$r]['depth']; $c++) {
                $rows[$r][$c] = ' '; // placeholder
            }
        }

        // Draw connection lines row by row
        for ($r = 0; $r < $lineCount - 1; $r++) {
            $line = $lines[$r];

            $childDepth = null;

            if (isset($lines[$r + 1]) && $lines[$r + 1]['depth'] > $line['depth']) {
                $childDepth = $lines[$r + 1]['depth'];
            }

            // No children, no connectors needed
            if ($childDepth === null) {
                continue;
            }

            // Find the last child and last phantom line for this parent
            $lastChild = 0;
            $lastPhantom = $lineCount - 1;

            for ($r1 = $r + 1; $r1 < $lineCount; $r1++) {
                // Direct child at same child depth
                if ($lines[$r1]['depth'] === $childDepth) {
                    $lastChild = $r1;
                }

                // Shallower depth means we've left this family tree
                if ($lines[$r1]['depth'] < $childDepth) {
                    $lastPhantom = $r1 - 1;
                    break;
                }
            }

            // Draw vertical and horizontal connectors for child lines
            for ($r1 = $r + 1; $r1 <= $lastPhantom; $r1++) {
                $prime = $lines[$r1];
                $verticalIndex = $line['depth'];

                // Add vertical down connector to the line above (if not the parent line)
                if ($r1 <= $lastChild) {
                    if (isset($rows[$r1 - 1][$verticalIndex]) && ($r1 - 1) > $r) {
                        $rows[$r1 - 1][$verticalIndex] = $this->addBit(
                            $rows[$r1 - 1][$verticalIndex],
                            self::HALF_VERTICAL_DOWN
                        );
                    }
                }

                // Add vertical up connector (or phantom for alignment)
                if ($r1 <= $lastChild) {
                    $rows[$r1][$verticalIndex] = $this->addBit(
                        $rows[$r1][$verticalIndex],
                        self::HALF_VERTICAL_UP
                    );
                } else {
                    $rows[$r1][$verticalIndex] = $this->addBit(
                        $rows[$r1][$verticalIndex],
                        self::PHANTOM
                    );
                }

                // Add horizontal connector for direct children with content
                if ($prime['depth'] === $childDepth && ! empty($prime['content'])) {
                    // The half bit for intersection
                    $rows[$r1][$verticalIndex] = $this->addBit(
                        $rows[$r1][$verticalIndex],
                        self::HALF_HORIZONTAL_RIGHT
                    );

                    // The full horizontal for the connector line
                    if (isset($rows[$r1][$verticalIndex + 1])) {
                        $rows[$r1][$verticalIndex + 1] = $this->addBit(
                            $rows[$r1][$verticalIndex + 1],
                            self::FULL_HORIZONTAL
                        );
                    }
                }
            }
        }

        /** @var array<int, list<int|string>> $normalizedRows */
        $normalizedRows = array_map(
            array_values(...),
            $rows
        );

        return $normalizedRows;
    }

    public function addBit(int|string $char, int $bit): int
    {
        $char = $char === ' ' ? 0 : (int) $char;

        return $char | $bit;
    }

    public function maskToCharacter(int|string $mask): string
    {
        // If $mask isn't a number, just return it.
        if (! is_numeric($mask)) {
            return $mask;
        }

        return match ($mask) {
            // Half bars become full bars
            self::HALF_HORIZONTAL_LEFT,
            self::HALF_HORIZONTAL_RIGHT,
            self::FULL_HORIZONTAL => '─',

            // Vertical bars
            self::HALF_VERTICAL_DOWN,
            self::HALF_VERTICAL_UP,
            self::FULL_VERTICAL => '│',

            // T-junctions
            self::FULL_HORIZONTAL + self::HALF_VERTICAL_DOWN => '┬',
            self::FULL_HORIZONTAL + self::HALF_VERTICAL_UP => '┴',
            self::HALF_HORIZONTAL_LEFT + self::FULL_VERTICAL => '┤',
            self::HALF_HORIZONTAL_RIGHT + self::FULL_VERTICAL => '├',

            // Corners
            self::TR_CORNER => '┐',
            self::TL_CORNER => '┌',
            self::BL_CORNER => '└',
            self::BR_CORNER => '┘',

            default => ' ',
        };
    }

    /**
     * @return array{wrapper:list<string>, horizontal:list<string>, vertical:list<string>}
     */
    public function maskToClasses(int $mask, string $prefix = 'tl-connect'): array
    {
        $horizontal = [$prefix, "{$prefix}-h"];
        $vertical = [$prefix, "{$prefix}-v"];
        $wrapper = ["{$prefix}-wrap"];

        if (($mask & self::HALF_HORIZONTAL_LEFT) === self::HALF_HORIZONTAL_LEFT) {
            $horizontal[] = "{$prefix}-left";
        }

        if (($mask & self::HALF_HORIZONTAL_RIGHT) === self::HALF_HORIZONTAL_RIGHT) {
            $horizontal[] = "{$prefix}-right";
        }

        if (($mask & self::HALF_VERTICAL_DOWN) === self::HALF_VERTICAL_DOWN) {
            $vertical[] = "{$prefix}-down";
            $wrapper[] = "{$prefix}-x-adjust";
        }

        if (($mask & self::HALF_VERTICAL_UP) === self::HALF_VERTICAL_UP) {
            $vertical[] = "{$prefix}-up";
            $wrapper[] = "{$prefix}-x-adjust";
        }

        if (($mask & self::PHANTOM) === self::PHANTOM) {
            $wrapper[] = "{$prefix}-x-adjust";
        }

        return [
            'wrapper' => array_values(array_unique($wrapper)),
            'horizontal' => array_values(array_unique($horizontal)),
            'vertical' => array_values(array_unique($vertical)),
        ];
    }

    public function renderHtmlConnector(int|string $mask, string $prefix = 'tl-connect'): string
    {
        if ($mask === ' ') {
            return sprintf(
                "<span class='%s-wrap %s-empty'><span class='%s %s-h'></span><span class='%s %s-v'></span></span>",
                $prefix,
                $prefix,
                $prefix,
                $prefix,
                $prefix,
                $prefix
            );
        }

        $classes = $this->maskToClasses((int) $mask, $prefix);

        $inner = sprintf(
            "<span class='%s'></span><span class='%s'></span>",
            implode(' ', $classes['horizontal']),
            implode(' ', $classes['vertical'])
        );

        return sprintf("<span class='%s'> %s</span>", implode(' ', $classes['wrapper']), $inner);
    }

    /** @param list<int|string> $row */
    public function rowToAscii(array $row): string
    {
        $chars = [];

        foreach ($row as $mask) {
            $chars[] = $this->maskToCharacter($mask);
        }

        return implode('', $chars);
    }

    /** @param list<int|string> $row */
    public function rowToHtml(array $row, string $prefix = 'tl-connect'): string
    {
        $html = [];

        foreach ($row as $mask) {
            $html[] = $this->renderHtmlConnector($mask, $prefix);
        }

        return implode('', $html);
    }
}
