<?php

namespace Torchlight\Engine;

/**
 * @phpstan-type ConfigRange int|string
 * @phpstan-type LineRange array{0:int, 1:int}
 * @phpstan-type ThemeInput string|\Phiki\Theme\Theme|\Phiki\Theme\ParsedTheme|\Torchlight\Engine\Theme\Theme
 * @phpstan-type ThemeMap array<string, \Phiki\Theme\ParsedTheme>
 * @phpstan-type TokenLine array<int, \Phiki\Token\Token>
 * @phpstan-type TokenLines array<int, TokenLine>
 * @phpstan-type HighlightedLine array<int, \Phiki\Token\HighlightedToken>
 * @phpstan-type HighlightedLines array<int, HighlightedLine>
 * @phpstan-type RenderLine array<int, \Torchlight\Engine\Generators\RenderableToken>
 * @phpstan-type RenderLines array<int, RenderLine>
 * @phpstan-type AttributeMap array<string, string>
 * @phpstan-type TokenSettingsMap array<string, \Phiki\Theme\TokenSettings>
 * @phpstan-type LineMetadata array{depth:int, isCommentOnly:bool, isDirectory:bool, content:string}
 * @phpstan-type IndentInfo array{columns:int, levels:int, isEmpty:bool}
 * @phpstan-type ConnectorClasses array{wrapper:list<string>, horizontal:list<string>, vertical:list<string>}
 */
final class TypeAliases
{
    private function __construct() {}
}
