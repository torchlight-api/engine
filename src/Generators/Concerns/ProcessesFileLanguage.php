<?php

namespace Torchlight\Engine\Generators\Concerns;

use Phiki\Token\HighlightedToken;

trait ProcessesFileLanguage
{
    const HALF_HORIZONTAL_LEFT = 1;   // 1 << 0

    const HALF_HORIZONTAL_RIGHT = 2;   // 1 << 1

    const HALF_VERTICAL_UP = 4;   // 1 << 2

    const HALF_VERTICAL_DOWN = 8;   // 1 << 3

    const PHANTOM = 16;  // 1 << 4

    const FULL_HORIZONTAL = self::HALF_HORIZONTAL_LEFT + self::HALF_HORIZONTAL_RIGHT;  // 1 + 2 = 3

    const FULL_VERTICAL = self::HALF_VERTICAL_UP + self::HALF_VERTICAL_DOWN;        // 4 + 8 = 12

    const TL_CORNER = self::HALF_HORIZONTAL_RIGHT + self::HALF_VERTICAL_DOWN;   // 2 + 8 = 10

    const TR_CORNER = self::HALF_HORIZONTAL_LEFT + self::HALF_VERTICAL_DOWN;    // 1 + 8 = 9

    const BL_CORNER = self::HALF_HORIZONTAL_RIGHT + self::HALF_VERTICAL_UP;     // 2 + 4 = 6

    const BR_CORNER = self::HALF_HORIZONTAL_LEFT + self::HALF_VERTICAL_UP;      // 1 + 4 = 5

    const FULL_CROSS = self::HALF_HORIZONTAL_RIGHT
        + self::HALF_HORIZONTAL_LEFT
        + self::HALF_VERTICAL_DOWN
        + self::HALF_VERTICAL_UP;

    private function addBit($char, $bit): int
    {
        if ($char === ' ') {
            $char = 0;
        }

        // bitwise OR
        return $char | $bit;
    }

    private function characterForMask(mixed $mask): string
    {
        // If $mask isn't a number, just return it.
        if (! is_numeric($mask)) {
            return $mask;
        }

        switch ($mask) {
            // Any standalone half bars should be converted to fulls.
            case self::HALF_HORIZONTAL_LEFT:
            case self::HALF_HORIZONTAL_RIGHT:
            case self::FULL_HORIZONTAL:
                return '─';

                // Any standalone half bars should be converted to fulls.
            case self::HALF_VERTICAL_DOWN:
            case self::HALF_VERTICAL_UP:
            case self::FULL_VERTICAL:
                return '│';

                // Combining full horizontal + half vertical => ┬
            case self::FULL_HORIZONTAL + self::HALF_VERTICAL_DOWN:
                return '┬';

                // Combining full horizontal + half vertical => ┴
            case self::FULL_HORIZONTAL + self::HALF_VERTICAL_UP:
                return '┴';

                // Combining half horizontal + full vertical => ┤ or ├
            case self::HALF_HORIZONTAL_LEFT + self::FULL_VERTICAL:
                return '┤';

            case self::HALF_HORIZONTAL_RIGHT + self::FULL_VERTICAL:
                return '├';

                // Corners
            case self::TR_CORNER:
                return '┐';

            case self::TL_CORNER:
                return '┌';

            case self::BL_CORNER:
                return '└';

            case self::BR_CORNER:
                return '┘';
        }

        return ' ';
    }

    protected function processFileLanguage(array $lines): array
    {
        $info = [];

        $commentSettings = $this->getScopeSettings([
            'source.files',
            'comment.line.number-sign.yaml',
            'punctuation.definition.comment.yaml',
        ]);

        foreach ($lines as $lineIndex => $tokens) {
            $info[$lineIndex] = [
                'depth' => 0,
                'isCommentOnly' => false,
                'isDirectory' => false,
                'content' => '',
            ];

            if (empty($tokens)) {
                continue;
            }

            /**
             * @var int $i
             * @var HighlightedToken $token
             */
            foreach ($tokens as $i => $token) {
                $tokenText = $token->token->text;

                if (trim($tokenText) == '') {
                    $info[$lineIndex]['depth'] = mb_strlen($tokenText);

                    continue;
                }

                if (str_starts_with($tokenText, '#')) {
                    $info[$lineIndex]['isCommentOnly'] = true;
                    break; // Stop scanning tokens on this line.
                }

                $isDirectory = str_ends_with($tokenText, '/');
                $info[$lineIndex]['isDirectory'] = $isDirectory;
                $info[$lineIndex]['content'] = $tokenText;

                $attributes = [];

                if (! $isDirectory) {
                    $attributes['tl-file-extension'] = htmlspecialchars(pathinfo($token->token->text, PATHINFO_EXTENSION));
                }

                $this->tokenOptions[$token] = [
                    'classes' => [
                        $isDirectory ? 'tl-files-folder' : 'tl-files-file',
                        'tl-files-name',
                    ],
                    'attributes' => $attributes,
                ];

                break;
            }
        }

        // We need to figure out how many unique depths there are so that we can
        // add a space between the horizontal connector and the word. We can't
        // just add one, because it's additive. If we add one to a depth of e.g.
        // 2, then we need to add one to the depth of 4 *just to account*
        // for the padding at depth 2. So we figure out how many unique
        // depths there are, and then add one per depth.
        $allDepths = array_map(function ($i) {
            return $i['depth'];
        }, $info);

        $uniqueDepths = array_unique($allDepths);
        sort($uniqueDepths);

        // At this point we have an array like [0,2,4,6,8] of all the depths.
        // We want to turn it into a mapping like [0,0; 2,1; 4,2; 6,3; 8,4]
        // as this will guide how many spaces to add to each depth.
        $levels = [];

        foreach ($uniqueDepths as $i => $level) {
            $levels[$level] = $i;
        }

        // Go ahead and add the spaces.
        $info = array_map(function ($line) use ($levels) {
            $levelIndex = $levels[$line['depth']];
            // We normalize everything to two spaces per level. Having 4
            // spaces looks weird, and so does one.
            $line['depth'] = $levelIndex * 3;

            return $line;
        }, $info);

        // This is going to hold all of our vertical and horizontal connectors.
        $rows = [];

        // For every row, add spaces out to the content.
        for ($r = 0; $r < count($info); $r++) {
            $rows[$r] = [];
            for ($c = 0; $c < $info[$r]['depth']; $c++) {
                $rows[$r][$c] = ' '; // placeholder
            }
        }

        // Now we go row by row and draw the lines. Each row has a sub-loop
        // that will draw all of the vertical connectors where necessary.
        $infoLen = count($info);
        for ($r = 0; $r < $infoLen - 1; $r++) {
            $line = $info[$r];

            $childDepth = false;

            // Look at the next line and see if it's depth is deeper than ours. If
            // so, that's the child depth we're looking for. This lets us not
            // prescribe indentation size of e.g. 2 or 4, and it also
            // covers mistakes the developer might make.
            if (isset($info[$r + 1]) && $info[$r + 1]['depth'] > $line['depth']) {
                $childDepth = $info[$r + 1]['depth'];
            }

            // No children, no problem.
            if (! $childDepth) {
                continue;
            }

            // We need to find the last child that this row has, so that we
            // don't draw lines beyond it unnecessarily.
            $lastChild = 0;
            $lastPhantom = $infoLen - 1;

            // r' starts at the next row and goes to the end.
            for ($r1 = $r + 1; $r1 < $infoLen; $r1++) {
                // If r' has the same depth as the parent's children, then
                // it's a child. We'll store this index and then keep
                // looking for more.
                if ($info[$r1]['depth'] === $childDepth) {
                    $lastChild = $r1;
                }

                // If r' is *shallower* than the parent's children then it's a
                // sibling or ancestor, so we stop looking for more children
                // because we are out of our family tree.
                if ($info[$r1]['depth'] < $childDepth) {
                    $lastPhantom = $r1 - 1;
                    break;
                }
            }

            // r' again is going to be the next row. This is where we start drawing lines.
            for ($r1 = $r + 1; $r1 <= $lastPhantom; $r1++) {
                $prime = $info[$r1];

                // Run the vertical connector directly below the first character.
                $verticalIndex = $line['depth'];

                // If there is a line above the prime line and it's not the line
                // we're operating on, then we need to draw the second half of
                // the vertical connector. We can't do this when r'-1 = r
                // because that space is occupied by the name of the
                // previous file or folder.
                // resources/
                // └─ blueprints/     <--- This only has a half vertical up, set by itself.
                //    ├─ collections/ <--- This has both. The half vertical down was set by the row below it.
                //    │  └─ blog/
                //    │     └─ art_directed_post.yaml
                //    ├─ taxonomies/
                //    │  └─ tags/
                //    │     └─ tag.yaml

                if ($r1 <= $lastChild) {
                    // If there's a line above and it's > r
                    if (isset($rows[$r1 - 1][$verticalIndex]) && ($r1 - 1) > $r) {
                        $rows[$r1 - 1][$verticalIndex] =
                            $this->addBit($rows[$r1 - 1][$verticalIndex], static::HALF_VERTICAL_DOWN);
                    }
                }

                if ($r1 <= $lastChild) {
                    // Add our own half vertical up.
                    $rows[$r1][$verticalIndex] =
                        $this->addBit($rows[$r1][$verticalIndex], static::HALF_VERTICAL_UP);
                } else {
                    $rows[$r1][$verticalIndex] =
                        $this->addBit($rows[$r1][$verticalIndex], static::PHANTOM);
                }

                // If prime is a direct child and there is content (e.g.
                // not a comment) then add a horizontal connector.
                if ($prime['depth'] === $childDepth && ! empty($prime['content'])) {
                    // The half bit to make the vertical connector an intersection.
                    $rows[$r1][$verticalIndex] =
                        $this->addBit($rows[$r1][$verticalIndex], static::HALF_HORIZONTAL_RIGHT);

                    // The full bit to make the horizontal connector more prominent.
                    if (isset($rows[$r1][$verticalIndex + 1])) {
                        $rows[$r1][$verticalIndex + 1] =
                            $this->addBit($rows[$r1][$verticalIndex + 1], static::FULL_HORIZONTAL);
                    }
                }
            }
        }

        if ($this->torchlightOptions->fileStyle !== 'ascii') {
            foreach ($rows as $rowIndex => &$row) {
                foreach ($row as $charIndex => &$char) {
                    if ($char === ' ') {
                        $char = "<span class='tl-connect-wrap tl-connect-empty'><span class='tl-connect tl-connect-h'></span><span class='tl-connect tl-connect-v'></span></span>";

                        continue;
                    }

                    $horizontal = ['tl-connect', 'tl-connect-h'];
                    $vertical = ['tl-connect', 'tl-connect-v'];
                    $wrapper = ['tl-connect-wrap'];

                    $char = (int) $char;

                    if (($char & self::HALF_HORIZONTAL_LEFT) === self::HALF_HORIZONTAL_LEFT) {
                        $horizontal[] = 'tl-connect-left';
                    }

                    if (($char & self::HALF_HORIZONTAL_RIGHT) === self::HALF_HORIZONTAL_RIGHT) {
                        $horizontal[] = 'tl-connect-right';
                    }

                    if (($char & self::HALF_VERTICAL_DOWN) === self::HALF_VERTICAL_DOWN) {
                        $vertical[] = 'tl-connect-down';
                        $wrapper[] = 'tl-connect-x-adjust';
                    }

                    if (($char & self::HALF_VERTICAL_UP) === self::HALF_VERTICAL_UP) {
                        $vertical[] = 'tl-connect-up';
                        $wrapper[] = 'tl-connect-x-adjust';
                    }

                    if (($char & self::PHANTOM) === self::PHANTOM) {
                        $wrapper[] = 'tl-connect-x-adjust';
                    }

                    $horizontal = array_unique($horizontal);
                    $vertical = array_unique($vertical);
                    $wrapper = array_unique($wrapper);

                    $inner = sprintf(
                        "<span class='%s'></span><span class='%s'></span>",
                        implode(' ', $horizontal),
                        implode(' ', $vertical)
                    );

                    $char = sprintf("<span class='%s'> %s</span>", implode(' ', $wrapper), $inner);
                }
            }

            // Map back to lines and tokens.
            foreach ($lines as $lineIndex => $tokens) {
                if (empty($token) || empty($rows[$lineIndex])) {
                    continue;
                }

                /** @var HighlightedToken $token */
                foreach ($tokens as $tokenIndex => $token) {
                    if (trim($token->token->text) !== '') {
                        continue;
                    }

                    // Swap out the token to apply the desired settings.
                    $lines[$lineIndex][$tokenIndex] = new HighlightedToken(
                        $token->token,
                        $commentSettings,
                    );

                    $this->setRawContent(
                        $token->token,
                        implode('', $rows[$lineIndex])
                    );

                    break;
                }
            }

            return $lines;
        }

        foreach ($rows as $rIndex => &$row) {
            foreach ($row as $cIndex => &$char) {
                $char = $this->characterForMask($char);
            }
        }

        // Insert ASCII connectors into the indentation token
        foreach ($lines as $lineIndex => $tokens) {
            if (empty($token) || empty($rows[$lineIndex])) {
                continue;
            }

            /** @var HighlightedToken $token */
            foreach ($tokens as $tokenIndex => $token) {
                if (trim($token->token->text) !== '') {
                    continue;
                }

                $asciiContent = $rows[$lineIndex];

                $token->token->text = implode('', $asciiContent);

                // Swap out the token to apply the desired settings.
                $lines[$lineIndex][$tokenIndex] = new HighlightedToken(
                    $token->token,
                    $commentSettings,
                );

                break;
            }
        }

        return $lines;
    }
}
