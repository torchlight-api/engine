<?php

namespace Torchlight\Engine\Tests\Results;

use DOMDocument;
use DOMXPath;

class ResultParser
{
    public function parseResult(string $html): Result
    {
        $lines = [];

        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $lineDivs = $xpath->query("//div[contains(@class, 'line')]");

        foreach ($lineDivs as $div) {
            $line = new Line;
            $line->classes = explode(' ', $div->getAttribute('class') ?? '');
            $line->text = $div->textContent;

            $lineNumberNode = $xpath->query(".//span[contains(@class, 'line-number')]", $div)->item(0);

            if ($lineNumberNode) {
                $line->lineNumberContent = trim($lineNumberNode->textContent);
            }

            if ($id = $div->getAttribute('id')) {
                $line->id = $id;
            }

            $lines[] = $line;
        }

        return new Result($lines);
    }
}
