<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Support\Str;

#[Annotation(name: 'lens')]
class CodeLensAnnotation extends AbstractAnnotation
{
    /** @var list<array{line: int, items: list<string>}> */
    private array $pendingLens = [];

    protected function onLine(ParsedAnnotation $annotation): void
    {
        $text = $annotation->rawMethodArgs ?? '';

        if ($text === '') {
            return;
        }

        $items = $this->parseItems($text);
        $line = $this->activeRange()->startLine;

        $this->pendingLens[] = ['line' => $line, 'items' => $items];

        $this->addBlockClass('has-codelens');
    }

    public function beforeProcess(): void
    {
        $this->pendingLens = [];
    }

    public function afterProcess(): void
    {
        if (empty($this->pendingLens)) {
            return;
        }

        $spacer = $this->buildGutterSpacer();
        $removedLines = $this->annotationEngine->getGenerationOptions()->removedLines;

        foreach ($this->pendingLens as $entry) {
            $line = $entry['line'];

            // If the target line was removed (e.g., by word-diff merging),
            // we need to move the lens to the previous visible line.
            while (isset($removedLines[$line]) && $line > 1) {
                $line--;
            }

            $indent = $this->buildIndent($line);
            $html = $this->buildCodelensHtml($entry['items'], $spacer, $indent);
            $this->prependLine($line, $html);
        }
    }

    private function buildGutterSpacer(): string
    {
        if (! $this->options->withGutter) {
            return '';
        }

        $spacer = '';

        foreach ($this->annotationEngine->getGenerationOptions()->getSortedGutters() as $gutter) {
            if ($gutter->shouldRender()) {
                $spacer .= $gutter->renderSpacer();
            }
        }

        return $spacer;
    }

    private function buildIndent(int $line): string
    {
        // When indent guides are active, emit a placeholder that
        // HtmlGenerator will replace with guide spans after the
        // IndentGuideTransformer has run to ensure widths match
        if ($this->options->indentGuides !== false) {
            $placeholder = '<!--TL_LENS_INDENT:'.Str::random(16).'-->';
            $this->annotationEngine->getGenerationOptions()->codelensIndentPlaceholders[$placeholder] = $line;

            return $placeholder;
        }

        $lineText = $this->annotationEngine->getLineText($line);

        if ($lineText === null) {
            return '';
        }

        $indent = 0;

        for ($i = 0; $i < mb_strlen($lineText); $i++) {
            $char = mb_substr($lineText, $i, 1);

            if ($char === ' ') {
                $indent++;
            } elseif ($char === "\t") {
                $indent += 4;
            } else {
                break;
            }
        }

        if ($indent === 0) {
            return '';
        }

        return str_repeat('&nbsp;', $indent);
    }

    /**
     * @return list<string>
     */
    private function parseItems(string $text): array
    {
        $items = [];
        $current = '';
        $quote = null;
        $len = mb_strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1);

            if ($quote !== null) {
                if ($char === '\\' && $i + 1 < $len) {
                    $next = mb_substr($text, $i + 1, 1);
                    if ($next === $quote || $next === '\\') {
                        $current .= $next;
                        $i++;

                        continue;
                    }
                }

                if ($char === $quote) {
                    $quote = null;

                    continue;
                }

                $current .= $char;
            } else {
                if ($char === '"' || $char === "'") {
                    $quote = $char;

                    continue;
                }

                if ($char === ',' && $i + 1 < $len && mb_substr($text, $i + 1, 1) === ' ') {
                    $items[] = trim($current);
                    $current = '';
                    $i++;

                    continue;
                }

                $current .= $char;
            }
        }

        $trimmed = trim($current);

        if ($trimmed !== '') {
            $items[] = $trimmed;
        }

        return $items;
    }

    /**
     * @param  list<string>  $items
     */
    private function buildCodelensHtml(array $items, string $spacer = '', string $indent = ''): string
    {
        $spans = [];

        foreach ($items as $item) {
            $spans[] = $this->buildItemHtml($item);
        }

        $separator = "<span class='codelens-separator'> | </span>";
        $inner = implode($separator, $spans);

        return "<div class='codelens'>{$spacer}{$indent}{$inner}</div>";
    }

    private function buildItemHtml(string $item): string
    {
        if (str_contains($item, ': ')) {
            [$key, $value] = explode(': ', $item, 2);

            $key = htmlspecialchars($key);
            $value = htmlspecialchars($value);

            return "<span class='codelens-item'><span class='codelens-key'>{$key}</span>: <span class='codelens-value'>{$value}</span></span>";
        }

        return "<span class='codelens-item'>".htmlspecialchars($item).'</span>';
    }
}
