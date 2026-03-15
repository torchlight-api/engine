<?php

namespace Torchlight\Engine\Annotations\Contracts;

interface AnnotationDescriptor
{
    /**
     * Primary name for the annotation (e.g., "highlight", "collapse").
     */
    public static function getName(): string;

    /**
     * Alternative names that trigger this annotation (e.g., ["~~", "hl"]).
     *
     * @return list<string>
     */
    public static function getAliases(): array;

    /**
     * Prefix this annotation responds to (e.g., "." for classes, "#" for IDs).
     */
    public static function getPrefix(): ?string;

    /**
     * Whether this annotation accepts method arguments: annotation(args)
     */
    public static function acceptsMethodArgs(): bool;

    /**
     * Whether this annotation accepts options after the name: annotation opt1 opt2
     */
    public static function acceptsOptions(): bool;

    /**
     * Whether this annotation supports character ranges: annotation:c1,5
     */
    public static function supportsCharacterRanges(): bool;

    /**
     * Whether this annotation supports line ranges: annotation:1,5 or annotation:start/end
     */
    public static function supportsLineRanges(): bool;
}
