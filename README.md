<picture>
  <source media="(prefers-color-scheme: dark)" srcset="./.art/banner_dark.png">
  <img alt="Torchlight Engine" src="./.art/banner.png">
</picture>

Torchlight Engine brings Torchlight's code annotation syntax to PHP, built on top of the excellent [Phiki](https://github.com/phikiphp/phiki) syntax highlighting package. **No node or API required**.

Torchlight enables you to add annotations to your code, drawing your reader's attention to specific parts, highlighting lines, visualizing diffs, and much more. Combined with the syntax highlighting provided by Phiki, Torchlight is a perfect fit for technical blogs, documentation, and so much more.

Torchlight annotations are written as comments in the language of your code sample, eliminating red squigglies and errors within your editor or IDE.

As an example, here is how we could focus our reader's attention on lines 6 and 7:

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,

        // Add Torchlight syntax highlighting. [tl! focus]
        TorchlightExtension::class, // [tl! focus]
    ]
]
```

When rendered, our readers would be presented with something like the following:

![Focus Annotation Example](./.art/readme/example_intro_focus.png)

How simple is that? We're pretty proud of it and know you'll love it, too.

* [Installation](#installation)
* [Getting Started](#getting-started)
  * [Modifying the CommonMark Extension](#modifying-the-commonmark-extension)
    * [Specifying the Extension's Default Language](#specifying-the-extensions-default-language)
    * [Caching Highlighted Code](#caching-highlighted-code)
  * [Laravel](#laravel)
  * [Statamic](#statamic)
  * [Rendering Code Manually](#rendering-code-manually)
  * [Notes on User Provided Content](#notes-on-user-provided-content)
* [Frequently Asked Questions](#frequently-asked-questions)
  * [Is the Torchlight API going away now?](#is-the-torchlight-api-going-away-now)
  * [How much does Torchlight Engine cost?](#how-much-does-torchlight-engine-cost)
  * [Does Torchlight Engine require an API key or network access?](#does-torchlight-engine-require-an-api-key-or-network-access)
  * [What about the Laravel and CommonMark packages?](#what-about-the-laravel-and-commonmark-packages)
  * [Will this package replace the existing CommonMark package?](#will-this-package-replace-the-existing-commonmark-package)
  * [Some themes are missing compared to the API version. How come?](#some-themes-are-missing-compared-to-the-api-version-how-come)
  * [Can I add custom themes to Torchlight Engine?](#can-i-add-custom-themes-to-torchlight-engine)
  * [Are the custom grammars from the API version supported?](#are-the-custom-grammars-from-the-api-version-supported)
  * [Some of my highlighting looks different now. How come?](#some-of-my-highlighting-looks-different-now-how-come)
  * [Are there breaking changes?](#are-there-breaking-changes)
* [Differences Between Torchlight Engine and Torchlight API](#differences-between-torchlight-engine-and-torchlight-api)
* [CSS and Theming](#css-and-theming)
  * [Standard CSS](#standard-css)
  * [Tailwind](#tailwind)
  * [Dark Mode](#dark-mode)
  * [Available Themes](#available-themes)
* [Annotations](#annotations)
  * [Plain Text Annotations](#plain-text-annotations)
  * [JSON Annotations](#json-annotations)
  * [Ranges](#ranges)
    * [Annotation Range Cheat Sheet](#annotation-range-cheat-sheet)
    * [Single Lines](#single-lines)
    * [N-Many Lines](#n-many-lines)
    * [Offset and Length](#offset-and-length)
    * [Applying an Annotation to All Lines](#applying-an-annotation-to-all-lines)
    * [Start and End](#start-and-end)
    * [Supported Annotations](#supported-annotations)
  * [Highlighting Lines](#highlighting-lines)
    * [Alternative Highlight Class](#alternative-highlight-class)
    * [Highlight Shorthand](#highlight-shorthand)
  * [Focusing](#focusing)
    * [Focusing Shorthand](#focusing-shorthand)
    * [Focusing CSS](#focusing-css)
  * [Collapsing](#collapsing)
    * [Customizing the Summary Text](#customizing-the-summary-text)
    * [Collapsing Required CSS](#collapsing-required-css)
    * [Default to Open](#default-to-open)
    * [Removing Summary Carets](#removing-summary-carets)
  * [Diffs](#diffs)
    * [Diff Shorthand](#diff-shorthand)
    * [Removing Diff Indicators](#removing-diff-indicators)
    * [Standalone Diff Indicators](#standalone-diff-indicators)
    * [Diff Indicators Without Line Numbers](#diff-indicators-without-line-numbers)
    * [Diff Ranges](#diff-ranges)
    * [Preserving Syntax Colors](#preserving-syntax-colors)
  * [Classes and IDs](#classes-and-ids)
    * [Using Range Modifiers](#using-range-modifiers)
    * [Character Ranges](#character-ranges)
  * [Auto-linking URLs](#auto-linking-urls)
    * [Link Requirements](#link-requirements)
    * [Link Ranges](#link-ranges)
  * [Reindexing Line Numbers](#reindexing-line-numbers)
    * [Manually Setting a New Number](#manually-setting-a-new-number)
    * [No Line Number at All](#no-line-number-at-all)
    * [Relative Line Number Changes](#relative-line-number-changes)
    * [Reindexing with Range Modifiers](#reindexing-with-range-modifiers)
    * [Reindex Differences Between Torchlight API](#reindex-differences-between-torchlight-api)
    * [Vim-style Relative Line Numbers](#vim-style-relative-line-numbers)
* [Highlighting Files and Directory Structures](#highlighting-files-and-directory-structures)
* [Options](#options)
  * [Setting Default Options Globally](#setting-default-options-globally)
  * [Setting Default Themes Globally](#setting-default-themes-globally)
  * [Setting Options Per Block](#setting-options-per-block)
  * [Line Numbers](#line-numbers)
    * [Changing the Starting Line Number](#changing-the-starting-line-number)
    * [Changing Line Number Styles](#changing-line-number-styles)
    * [Adding Line Number Right Padding](#adding-line-number-right-padding)
  * [Summary Indicator](#summary-indicator)
  * [Adding Extra Classes to the Torchlight Code Element](#adding-extra-classes-to-the-torchlight-code-element)
  * [The Copyable Option](#the-copyable-option)
  * [Disabling Torchlight Annotations](#disabling-torchlight-annotations)
* [Reporting Issues](#reporting-issues)
* [Contributing](#contributing)
* [Credits](#credits)
* [License](#license)

## Installation

You may install Torchlight Engine via Composer:

```bash
composer require torchlight/engine
```

Torchlight Engine requires at least PHP 8.2, but PHP 8.4+ is recommended.

## Getting Started

Torchlight Engine provides a `league/commonmark` extension, making it simple to start using Torchlight in your markdown content.

You may register the extension with any CommonMark `Environment` object like so:

````php
<?php

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Torchlight\Engine\CommonMark\Extension;

$environment = new Environment;
$environment
    ->addExtension(new CommonMarkCoreExtension)
    ->addExtension(new Extension('github-light'));

$converter = new MarkdownConverter($environment);
$output = $converter->convert(<<<'MD'
```php
<?php echo 'This is Torchlight'; ?>
```
MD);
````

### Modifying the CommonMark Extension

The CommonMark extension provides a few different ways to modify its behavior.

#### Specifying the Extension's Default Language

To change the extension's default language that should be used when author's omit the language on a code block, we can call the `setDefaultGrammar` on the underlying renderer:

```php
<?php

use Torchlight\Engine\CommonMark\Extension;


$extension = new Extension('github-light');

$extension->renderer()
    ->setDefaultGrammar('php');
```

````
```
This code block would now use PHP by default.
```
````

#### Caching Highlighted Code

A custom cache may be used to cache highlighted code blocks. Integrators may implement the `Torchlight\Engine\CommonMark\BlockCache` interface:

```php
<?php

namespace Torchlight\Engine\CommonMark;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;

interface BlockCache
{
    public function has(FencedCode $node): bool;

    public function get(FencedCode $node): string;

    public function set(FencedCode $node, string $result): void;
}

```

The cache implementation may be set on the extension by calling the `setBlockCache` on the underling renderer:

```php
<?php

use Torchlight\Engine\CommonMark\Extension;


$extension = new Extension('github-light');

$extension->renderer()
    ->setBlockCache(new MyCacheImplementation);
```

### Laravel

> [!NOTE]
> This section highlights using the provided CommonMark extension with Laravel. Updated versions of the [Laravel client](https://github.com/torchlight-api/torchlight-laravel) are planned for the future.

You may use the provided CommonMark extension with Laravel's `Str::markdown()` or `str()->markdown()` methods by adding the extension to your method call:

```php
<?php

use Torchlight\Engine\CommonMark\Extension;

echo str()->markdown('...your markdown content...', extensions: [
    new Extension('github-light'),
]);
```

### Statamic

To integrate Torchlight Engine with Statamic, you may [add the CommonMark extension](https://statamic.dev/extending/markdown#adding-extensions) like so:

```php
<?php
namespace App\Providers;
 
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\Markdown;
use Torchlight\Engine\CommonMark\Extension;
 
class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Add the Torchlight Engine extension
        Markdown::addExtension(function () {
            return new Extension('synthwave-84');
        });
    }
}
```

### Rendering Code Manually

You may also use the engine "manually". The following code example provides the minimum amount of code to use the Engine to render code:

```php
<?php

use Torchlight\Engine\Engine;

$engine = new Engine;

$code = <<<'PHP'
echo "Hello, world!"; // [tl! ++]
PHP;

$code->toHtml(
    $code,         // The code to highlight
    'php',         // The language
    'github-light' // The theme(s) to use
);
```

### Notes on User Provided Content

[Phiki](https://github.com/phikiphp/phiki) and Torchlight Engine, while incredibly powerful, are still early projects, and it is possible to encounter infinite loops with some grammars and input. As always, you should exercise caution when rendering any user-provided content.

If you encounter one of these scenarios please create an issue so it can be looked into.

## Frequently Asked Questions

### Is the Torchlight API going away now?

The Torchlight API will remain as-is for now. Any changes to the hosted service will be communicated ahead of time.

### How much does Torchlight Engine cost?

Torchlight Engine is free.

### Does Torchlight Engine require an API key or network access?

No. Torchlight Engine is a PHP-based, offline renderer built on top of [Phiki](https://github.com/phikiphp/phiki).

### What about the Laravel and CommonMark packages?

There are plans to upgrade both of these packages to support Torchlight Engine. More information on this topic will come in the future.

### Will this package replace the existing CommonMark package?

No, there are no immediate plans to deprecate the [existing CommonMark package](https://github.com/torchlight-api/torchlight-commonmark-php) as it provides additional features not currently available in the extension shipped with this package (notably integration with the `torchlight.php` configuration file and replacers). However, if you need a CommonMark extension that has no Laravel dependency, the extension provided by this package is what you are looking for.

### Some themes are missing compared to the API version. How come?

Some themes available via. Torchlight API are not available with Torchlight Engine; this is largely due to them not being distributed any longer, or licensing information was not readily available. More information on adding custom themes to Torchlight will be coming in the future.

### Can I add custom themes to Torchlight Engine?

Technically _yes_, but it is a slightly involved process to account for the Torchlight colors. More information on adding custom themes will be coming in the future once the process is a bit simpler.

### Are the custom grammars from the API version supported?

Yes, even the [`files`](#highlighting-files-and-directory-structures) grammar!

### Some of my highlighting looks different now. How come?

There may be differences in highlighting due to the underlying tokenizer and theme system. Please report any egregious issues and we will work to help get them resolved.

### Are there breaking changes?

Great care has been taken to avoid breaking changes, and adhere to the existing HTML structure as closely as possible. However, there are some scenarios where behavior has been changed.

Please refer to the [Differences Between Torchlight Engine and Torchlight API](#differences-between-torchlight-engine-and-torchlight-api) section for more information.

## Differences Between Torchlight Engine and Torchlight API

There are a small number of differences when comparing Torchlight Engine and the Torchlight API versions:

* Invalid JSON input for block options will throw an instance of `Torchlight\Engine\Exceptions\InvalidJsonException`. The API version may attempt to parse the invalid JSON or silently discard the error.
* The [reindex](#reindexing-line-numbers) annotation's range modifier behavior has [been adjusted](#reindex-differences-between-torchlight-api) to be more predictable and consistent with other modifiers.
* Dark mode support no longer requires duplicate code blocks.
* The `lineNumberAndDiffIndicatorRightPadding` block option applies padding more predictably.
  * When using `lineNumberAndDiffIndicatorRightPadding` and `diffIndicatorsInPlaceOfLineNumbers: false` together, the padding will be added to the _right_ of the diff indicators, instead of in-between them.

## CSS and Theming

Torchlight handles the highlighting of all of your code for you, but there are a few styles that you will likely need to add to your CSS to make it just right.

This is the CSS we prefer, which sets up some line padding, margin off of the line numbers, and overflow scrolling. Your CSS is totally up to you though!

### Standard CSS

This is the vanilla CSS version, see below for the TailwindCSS version.

```css
/*
 Margin and rounding are personal preferences,
 overflow-x-auto is recommended.
*/
pre {
    border-radius: 0.25rem;
    margin-top: 1rem;
    margin-bottom: 1rem;
    overflow-x: auto;
}

/*
 Add some vertical padding and expand the width
 to fill its container. The horizontal padding
 comes at the line level so that background
 colors extend edge to edge.
*/
pre code.torchlight {
    display: block;
    min-width: -webkit-max-content;
    min-width: -moz-max-content;
    min-width: max-content;
    padding-top: 1rem;
    padding-bottom: 1rem;
}

/*
 Horizontal line padding to match the vertical
 padding from the code block above.
*/
pre code.torchlight .line {
    padding-left: 1rem;
    padding-right: 1rem;
}

/*
 Push the code away from the line numbers and
 summary caret indicators.
*/
pre code.torchlight .line-number,
pre code.torchlight .summary-caret {
    margin-right: 1rem;
}
```

### Tailwind

Here is the Tailwind version:

```css
/*
 Margin and rounding are personal preferences,
 overflow-x-auto is recommended.
*/
pre {
    @apply my-4 rounded overflow-x-auto;
}

/*
 Add some vertical padding and expand the width
 to fill its container. The horizontal padding
 comes at the line level so that background
 colors extend edge to edge.
*/
pre code.torchlight {
    @apply block py-4 min-w-max;
}

/*
 Horizontal line padding.
*/
pre code.torchlight .line {
    @apply px-4;
}

/*
 Push the code away from the line numbers and
 summary caret indicators.
*/
pre code.torchlight .line-number,
pre code.torchlight .summary-caret {
    @apply mr-4;
}
```

### Dark Mode

Torchlight Engine utilizes Phiki for syntax highlighting, and recommends using it's multi-theme support for dark mode.

When instantiating an instance of the CommonMark extension, you may supply multiple themes like so:

```php
<?php

use Torchlight\Engine\CommonMark\Extension;

$extension = new Extension([
    'light' => 'github-light',
    'dark' => 'github-dark',
]);
```

The first entry, `light` in this case, will be used as the default theme. Other themes in the array may be conditionally rendered with CSS.

**Query-based dark mode:**

```css
@media (prefers-color-scheme: dark) {
    code.torchlight {
        background-color: var(--phiki-dark-background-color) !important;
    }

    .phiki,
    .phiki span {
        color: var(--phiki-dark-color) !important;
        font-style: var(--phiki-dark-font-style) !important;
        font-weight: var(--phiki-dark-font-weight) !important;
        text-decoration: var(--phiki-dark-text-decoration) !important;
    }
}
```

**Class-based dark mode:**

```css
html.dark code.torchlight {
    background-color: var(--phiki-dark-background-color) !important;
}

html.dark .phiki,
html.dark .phiki span {
    color: var(--phiki-dark-color) !important;
    font-style: var(--phiki-dark-font-style) !important;
    font-weight: var(--phiki-dark-font-weight) !important;
    text-decoration: var(--phiki-dark-text-decoration) !important;
}
```

You can learn more about rendering multiple themes with Phiki [here](https://github.com/phikiphp/phiki?tab=readme-ov-file#multi-theme-support). The only change when rendering multiple themes with Torchlight Engine is the placement of the `background-color` property, to prevent conflicts with some annotations, such as diff add and remove.

### Available Themes

The following themes are available:

* one-dark-pro
* solarized-light
* vitesse-black
* github-light-default
* slack-dark
* everforest-dark
* rose-pine-moon
* everforest-light
* laserwave
* github-light-high-contrast
* catppuccin-mocha
* red
* material-theme-lighter
* one-light
* aurora-x
* tokyo-night
* catppuccin-macchiato
* github-dark
* rose-pine-dawn
* poimandres
* github-dark-high-contrast
* material-theme
* dracula
* github-dark-default
* github-dark-dimmed
* rose-pine
* kanagawa-lotus
* kanagawa-dragon
* dark-plus
* ayu-dark
* min-dark
* monokai
* nord
* catppuccin-frappe
* github-light
* dracula-soft
* synthwave-84
* vitesse-dark
* andromeeda
* light-plus
* slack-ochin
* solarized-dark
* material-theme-ocean
* vitesse-light
* vesper
* kanagawa-wave
* plastic
* material-theme-darker
* night-owl
* catppuccin-latte
* min-light
* snazzy-light
* houston
* material-theme-palenight
* atom-one-dark
* cobalt2
* dark-404
* fortnite
* material-theme-default
* moonlight-ii
* moonlight
* olaolu-palenight-contrast
* olaolu-palenight
* serendipity-dark
* serendipity-light
* shades-of-purple
* slack-theme-dark-mode
* slack-theme-ochin
* winter-is-coming-blue
* winter-is-coming-dark
* winter-is-coming-light

## Annotations

One of the things that makes Torchlight such a joy to author with is that you can control how your code is rendered via _comments in the code you're writing._

If you want to highlight a specific line, you can add a code comment with the magic syntax `[tl! highglight]` and that line will be highlighted.

Gone are the days of inscrutable line number definitions at the top of your file, only to have them become outdated the moment you add or remove a line.

Most other tools use a series of line numbers up front to denote highlight or focus lines:
````text
```php{3}{2,4-5}{9}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```
````

If you don't have the syntax memorized, it's hard to tell what those numbers mean. And of course when you add a line or remove a line, everything changes and you have to recalculate!

With Torchlight, you control your display with inline annotations in comments.

All inline annotations are wrapped within square brackets and start with `tl!`, leaving you with the following format: `[tl! ... ... ...]`.

For example, if you are using Torchlight to render the following block of PHP:

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

and you wanted to draw attention to lines 6 & 7, you could focus those lines by using the `focus` annotation:

```php
return [
    'extensions' => [
        // Add attributes straight from markdown. 
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting. [tl! focus]
        TorchlightExtension::class, // [tl! focus]
    ]
]
```

Resulting in the following:

![Focus Annotation Example](./.art/readme/example_intro_focus.png)

Notice that Torchlight is smart enough to not only strip the annotation from line 6, but the annotation _and_ comment syntax from line 7, leaving your code pristine.

If the entirety of the comment is Torchlight annotations, the comment will be removed from the rendered code. If there is additional content in the comment, that content will remain and the annotation will be stripped out.

Because annotations are _actual_ code comments, it doesn't mess up your authoring experience by throwing invalid characters in your code.

Inline annotations support different keywords, modifiers, and range definitions

* [Annotating a Range of Lines](#ranges)
* [Highlighting Lines](#highlighting-lines)
* [Focusing Lines](#focusing)
* [Expanding and Collapsing Sections](#collapsing)
* [Diffing Lines](#diffs)
* [Adding Custom IDs and Classes](#classes-and-ids)
* [Auto-linking URLs](#auto-linking-urls)
* [Changing Line Numbers](#reindexing-line-numbers)

Remember that the comment syntax varies based on what language you are highlighting, so be sure to use _actual_ comments.

For example if you're highlighting HTML, you would use HTML comment tags `<!-- -->`. See the example on line 5.

```html
<div class='text-7xl font-bold'>
    <span>Syntax highlighting is</span>
    <span class='font-bold'>
        <span aria-hidden="true" class="absolute inset-0 bg-yellow-100 transform -rotate-6"></span>
        <span>broken.</span> <!-- [tl! focus] --> 
    </span>
</div> 
```

![Example HTML Annotation](./.art/readme/example_html_comment.png)

Annotations can be used with plain text and JSON, despite them having no "official" comment support as a language.

### Plain Text Annotations

For plain text, everything is treated "as if" it's a comment, so you can just put the annotation on any line.

```text
spring sunshine
the smell of waters
from the stars

deep winter [tl! focus:2]
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
```

![Text Annotation Example](./.art/readme/example_text_range.png)

#### JSON Annotations

JSON uses the double slash // comment style, even though it's not official spec. Forgive us.

```text
{
    "torchlightAnnotations": true,
    "lineNumbers": true, // [tl! focus:2]
    "lineNumbersStart": 1,
    "lineNumbersStyle": "text-align: right; -webkit-user-select: none; user-select: none;",
    "summaryCollapsedIndicator": "...",

    "diffIndicators": false,
    "diffIndicatorsInPlaceOfLineNumbers": true,
}
```

![JSON Annotation Example](./.art/readme/example_json_focus.png)

### Ranges

Sometimes you want to apply an annotation to a whole set of lines, without having to add dozens of comments.

We have provided several different methods to achieve this, so you may pick the one that best fits your use case.

#### Annotation Range Cheat Sheet

```text
highlight          -- This line only

highlight:start    -- The start of an open ended range
highlight:end      -- The end of an open ended range

highlight:10       -- This line, and the 10 following lines
highlight:-10      -- This line, and the 10 preceding lines

highlight:1,10     -- Start one line down, highlight 10 lines total
highlight:-1,10    -- Start one line up, highlight 10 lines total
```

#### Single Lines

By default, every annotation applies only to the line that it lives on.

For example, this will only highlight the line it is on, line number 2.

```php
return [
    'extensions' => [ // [tl! highlight]
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Single Line Example](./.art/readme/example_range_single_line.png)

#### N-Many Lines

To highlight the current line, and the next `N` lines, you may use the `:N` modifier.

In this example, we will highlight the current line (2) and the next two lines (3 & 4).

```php
return [
    'extensions' => [ // [tl! highlight:2]
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Many Lines Example](./.art/readme/example_range_many_lines.png)

This also works with negative numbers `:-N`

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,  // [tl! highlight:-2]
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Example of Negative Line Ranges](./.art/readme/example_negative_many_lines.png)

#### Offset and Length

If you have a bit of code that is hard to reach with a comment, perhaps a heredoc, you can use the `focus:M,N` syntax where `M` is the number of lines above or below the current line, and `N` is the number of lines to highlight.

Here we're going to start 6 lines down, and highlight 3 lines total.

```php
// This is a long bit of text, hard to highlight the middle. [tl! highlight:6,3]
return <<<EOT
spring sunshine
the smell of waters
from the stars

deep winter
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
EOT;
```

![HEREDOC Offset and Length Example](./.art/readme/example_offset_length_1.png)

You can also start from the bottom by using a negative offset. We'll start 7 lines up and highlight 3 lines again.

```php
// This is a long bit of text, hard to highlight the middle. 
return <<<EOT
spring sunshine
the smell of waters
from the stars

deep winter
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
EOT; // [tl! highlight:-7,3]
```

![HEREDOC Offset and Length Example](./.art/readme/example_offset_length_1.png)

#### Applying an Annotation to All Lines

You may use the `all` modifier to apply an annotation to _all_ lines. For example, the following would apply the `autolinks` annotation to every line:

```text
### Added [tl! autolink:all]
- Support for Laravel 9 [#29](https://github.com/torchlight-api/torchlight-laravel/pull/29)
- Better support for PHP 8.1 [#30](https://github.com/torchlight-api/torchlight-laravel/pull/30) 

```

#### Start and End

Sometimes you want to define a start and end line, and annotate everything in the middle.

You may do this with the `:start` and `:end` modifiers.

```php
return [
    'extensions' => [  // Start here [tl! highlight:start]
        // Add attributes straight from markdown.
        AttributesExtension::class, 
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ] // End here [tl! highlight:end]
]
```

![Start and End Modifier Examples](./.art/readme/example_start_end_modifiers.png)

#### Supported Annotations

All of them! Ranges are supported for all of the Torchlight annotation keywords:

* `highlight`
* `focus`
* `insert`
* `remove`
* `collapse`
* `autolink`
* `reindex`

Custom classes and IDs are supported as well.

* `.my-custom-class:start`
* `.my-custom-class:end`
* `.my-custom-class:1,10`
* `.my-custom-class:3`
* `.my-custom-class:-1,5`

Torchlight also plays nicely with prefixed Tailwind classes:

* `.sm:py-4:start`
* `.sm:py-4:end`
* `.sm:py-4:1,10`
* `.sm:py-4:3`
* `.sm:py-4:-1,5`

Remember that an HTML ID must be unique on the page, so while it's unlikely that you'd want to apply an ID to a _range_ of lines, you may want to apply it to a line you cannot reach.

For example, to reach four lines down and add an ID of `popover-trigger`, you could do the following:

```php
// Reach down 4 lines, add the ID to one line [tl! #popover-trigger:4,1]
return <<<EOT
spring sunshine
the smell of waters
from the stars

deep winter
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
EOT;
```

### Highlighting Lines

The `highlight` annotation will pull the line highlight background color from your chosen theme, and apply it to the background of the line, drawing focus to that specific line:

```php
return [
    'extensions' => [
        // Add attributes straight from markdown. [tl! highlight:1]
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Highlight Annotation Example](./.art/readme/example_highlight_1.png)

It also applies a `line-highlight` class to the line.

If you have _any_ lines highlighted, Torchlight will add a `has-highlight-lines` class to your `code` tag.

Every theme is different in the way that it chooses to represent highlighted lines, so be sure to try a few out.

#### Alternative Highlight Class

If you don't like the highlight color that your theme uses, you can apply a [custom class](#classes-and-ids) instead, e.g. `.highlight` or `.foobar`:

```php
return [
    'extensions' => [
        // Add attributes straight from markdown. [tl! .highlight]
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting. [tl! .foobar.bazbuz]
        TorchlightExtension::class,
    ]
]
```

#### Highlight Shorthand

If you find typing `highlight` prohibitively slow (who has the time?), you can use `~~` as a shorthand.

```php
return [
    'extensions' => [
        // Add attributes straight from markdown. [tl! ~~:1]
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

### Focusing

The `focus` annotation adds a `line-focus` class to the line, and a `has-focus-lines` class to your code tag.

Used in conjunction with the CSS below, every line that you've applied `[tl! focus]` to will be sharp and clear, and the rest will be blurry and dim. If a user hovers over the code block, everything will come into focus.

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting. [tl! focus]
        TorchlightExtension::class, // [tl! focus]
    ]
]
```

![Focus Annotation Example](./.art/readme/example_intro_focus.png)

#### Focusing Shorthand

As an alternative to `focus`, you can use `**`.

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting. [tl! **]
        TorchlightExtension::class, // [tl! **]
    ]
]
```

#### Focusing CSS

Here is the CSS required to achieve the focus effect:

```css
/* 
  Blur and dim the lines that don't have the `.line-focus` class, 
  but are within a code block that contains any focus lines. 
*/ 
.torchlight.has-focus-lines .line:not(.line-focus) {
    transition: filter 0.35s, opacity 0.35s;
    filter: blur(.095rem);
    opacity: .65;
}

/*
  When the code block is hovered, bring all the lines into focus.
*/
.torchlight.has-focus-lines:hover .line:not(.line-focus) {
    filter: blur(0px);
    opacity: 1;
}
```

### Collapsing

Sometimes in your documentation or a blog post, you want to focus the reader on a specific block of code, but allow them to see the rest of the code if they need to.

One way you can achieve that is by using the `focus` annotation to blur the irrelevant code, but you can also use Torchlight to _collapse_ blocks of code using native HTML, _no JavaScript required_.

In this example, we're going to collapse the `heading_permalink` options, as they might distract from the point of the example.

We can do this by using the `collapse` annotation:

```php
return [
    'heading_permalink' => [ // [tl! collapse:start]
        'html_class' => 'permalink',
        'id_prefix' => 'user-content',
        'insert' => 'before',
        'title' => 'Permalink',
        'symbol' => '#',
    ], // [tl! collapse:end]

    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

**Collapsed section closed:**

![Collapsed Section Closed](./.art/readme/example_summary_closed.png)

**Collapsed section open:**

![Collapsed Section Open](./.art/readme/example_summary_open.png)

These lines will now be wrapped in a `summary` / `detail` pair of tags, that allows the user to natively toggle the open and closed start of the block. Torchlight will also add a `has-summaries` class to your `code` tag anytime you define a summary range.

You can use the `start` `end` method of defining a range, or any of the other [range modifiers](#ranges).

Here's an example using the `N-many` modifier to collapse the 5 lines following the annotation:

```php
return [
    'heading_permalink' => [ // [tl! collapse:5]
        'html_class' => 'permalink',
        'id_prefix' => 'user-content',
        'insert' => 'before',
        'title' => 'Permalink',
        'symbol' => '#',
    ],

    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

#### Customizing the Summary Text

By default, Torchlight will add a subtle `...` in place of the collapsed text, but you can customize that by passing in the `summaryCollapsedIndicator` options:

```php
// torchlight! {"summaryCollapsedIndicator": "Click to show ]"}
return [
    'heading_permalink' => [ // [tl! collapse:start]
        'html_class' => 'permalink',
        'id_prefix' => 'user-content',
        'insert' => 'before',
        'title' => 'Permalink',
        'symbol' => '#',
    ], // [tl! collapse:end]

    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Customized Collapse Text](./.art/readme/example_summary_text_customized.png)

#### Collapsing Required CSS

You will need to add the following CSS to your page to accomplish the hiding:

```css
.torchlight summary:focus {
    outline: none;
}

/* Hide the default markers, as we provide our own */
.torchlight details > summary::marker,
.torchlight details > summary::-webkit-details-marker {
    display: none;
}

.torchlight details .summary-caret::after {
    pointer-events: none;
}

/* Add spaces to keep everything aligned */
.torchlight .summary-caret-empty::after,
.torchlight details .summary-caret-middle::after,
.torchlight details .summary-caret-end::after {
    content: " ";
}

/* Show a minus sign when the block is open. */    
.torchlight details[open] .summary-caret-start::after {
    content: "-"; 
}

/* And a plus sign when the block is closed. */    
.torchlight details:not([open]) .summary-caret-start::after {
    content: "+"; 
}

/* Hide the [...] indicator when open. */    
.torchlight details[open] .summary-hide-when-open {
    display: none;
}

/* Show the [...] indicator when closed. */    
.torchlight details:not([open]) .summary-hide-when-open {
    display: initial;
}
```

#### Default to Open

By default, when you define a collapse range it will be collapsed. If you want to define the range but default it to open, you can add the `open` keyword:

```php
return [
    'heading_permalink' => [ // [tl! collapse:start open]
        'html_class' => 'permalink',
        'id_prefix' => 'user-content',
        'insert' => 'before',
        'title' => 'Permalink',
        'symbol' => '#',
    ], // [tl! collapse:end]

    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class, 
    ]
]
```

#### Removing Summary Carets

You can disable summary carets by setting the `showSummaryCarets` block option:

```php
// torchlight! {"showSummaryCarets": false}
return [
    'heading_permalink' => [ // [tl! collapse:start]
        'html_class' => 'permalink',
        'id_prefix' => 'user-content',
        'insert' => 'before',
        'title' => 'Permalink',
        'symbol' => '#',
    ], // [tl! collapse:end]

    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

Setting this to `false` will disable the collapse gutter entirely:

![Disabling Summary Carets](./.art/readme/example_disabled_collapse_gutter.png)

### Diffs

To demonstrate the addition and removal of lines, you can use the `add` and `remove` keywords.

Torchlight will look through your theme to find the appropriate foreground and background colors to apply to the specific lines.

It will also apply `line-add` and `line-remove` classes to the individual lines. To the code element it will apply the `has-diff-lines` class, and potentially `has-add-lines` and `has-remove-lines`.

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add]
    ]
]
```

![Diff Annotation Example](./.art/readme/example_diff.png)

#### Diff Shorthand

You can use `++` and `--` as shorthand for `add` and `remove`.

```text
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! --]
        TorchlightExtension::class, // [tl! ++]
    ]
]
```

#### Removing Diff Indicators

Here is an example of a diff, with _no_ indicators.

```php
// torchlight! {"diffIndicators": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add]
    ]
]
```

![Example of Diff With No Indicators](./.art/readme/example_diff_no_indicators.png)

Notice that the colors of the lines just change to the standard colors you expect to see.

If you'd like to show the `+`/`-` indicators, you can do so by turning them on at the block level, or globally in your client's configuration.

For these examples we'll do it at the block level so we can see how it works.

Let's change the behavior by sending `diffIndicators: true` to the API.

```php
// torchlight! {"diffIndicators": true}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add]
    ]
]
```

Take a look where the line numbers are and notice the indicators:

![Example of Diff With Indicators](./.art/readme/example_diff_with_indicators.png)

> [!NOTE]
> If you'd like to reindex the line numbers after a diff, you [can do that](#reindexing-line-numbers).

#### Standalone Diff Indicators

By default, we swap them in place of the line numbers, but you can also disable that behavior by using the extremely descriptive, verbose option `diffIndicatorsInPlaceOfLineNumbers`.

```php
// torchlight! {"diffIndicators": true, "diffIndicatorsInPlaceOfLineNumbers": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add]
    ]
]
```

![Example of Diff Standalone Indicators](./.art/readme/example_diff_standalone.png)

Now the line numbers remain, and the indicators get their own column.

Each standalone indicator has the `diff-indicator` class applied, along with one of the following:

* `diff-indicator-add` - For lines that were added
* `diff-indicator-remove` - For lines that were removed
* `diff-indicator-empty` - For lines that were unchanged

#### Diff Indicators Without Line Numbers

In the scenario where you:

* turn _on_ diff indicators
* turn _off_ line numbers
* turn _on_ diff indicators in place of line numbers (this is the default)

Your indicators will still show up in the `line-number` classes, not the standalone classes mentioned above.

The reason we have chosen this approach is so that you don't have to add the `diff-indicator` styles _ever_ when you choose to put your indicators in the line number column.

#### Diff Ranges

The diff annotations support the entire set of [range modifiers](#ranges) to help you quickly annotate a whole set of lines.

Check out the [range docs](#ranges) for more details, but here is a quick cheat sheet.

```text
add          -- This line only

add:start    -- The start of an open ended range
add:end      -- The end of an open ended range

add:10       -- This line, and the 10 following lines
add:-10      -- This line, and the 10 preceding lines

add:1,10     -- Start one line down, highlight 10 lines total
add:-1,10    -- Start one line up, highlight 10 lines total
```

#### Preserving Syntax Colors

By default, the diff `add` and `remove` annotations will apply the corresponding text color, replacing the original token colors:

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add]
    ]
]
```

![Diff Annotation Example](./.art/readme/example_diff.png)

Notice how the text color has changed to red and green? We can disable this by setting the `diffPreserveSyntaxColors` block option:

```text
// torchlight! {"diffPreserveSyntaxColors": true}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add]
    ]
]
```

![Preserving Diff Syntax Colors](./.art/readme/example_diff_preserve_colors.png)

### Classes and IDs

You can add your own custom classes by preceding them with a `.`, or add an ID with a `#`.

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting. [tl! highlight .animate-pulse]
        TorchlightExtension::class, // [tl! highlight .font-bold .italic .animate-pulse #pulse]
    ]
]
```

You can space out your classes like we did above, or just run them all together: `.font-bold.italic.animate-pulse#pulse`

Torchlight also supports Tailwind + the Tailwind JIT syntax, so you can do pretty much anything you can think of:

```text
torchlight! {"torchlightAnnotations": false}
ID only                   // [tl! #id]
ID + Class                // [tl! #id.pt-4]
Negative Tailwind classes // [tl! .-pt-4 .pb-8]
ID + Classes Mixed        // [tl! .-pt-4#id1.pb-8]
Tailwind Prefixes         // [tl! .sm:pb-8]
Tailwind JIT              // [tl! .sm:pb-[calc(8px-4px)]]
Tailwind JIT              // [tl! .pr-[8px]]
Tailwind JIT + ID         // [tl! .-pt-4.pb-8.pr-[8px] #id]
```

#### Using Range Modifiers

You can also apply any [range modifiers](#ranges) to custom classes.

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting. [tl! .bg-gray-900:-1,4 .animate-pulse:1]
        TorchlightExtension::class,
    ]
]
```

![Example of Pulse Class](./.art/readme//classes_pulse.gif)

Check out the [range docs](#ranges) for more details, but here is a quick cheat sheet.

```text
.class          -- This line only

.class:start    -- The start of an open ended range
.class:end      -- The end of an open ended range

.class:10       -- This line, and the 10 following lines
.class:-10      -- This line, and the 10 preceding lines

.class:1,10     -- Start one line down, highlight 10 lines total
.class:-1,10    -- Start one line up, highlight 10 lines total
```

Remember that an HTML ID must be unique on the page, so while it's unlikely that you'd want to apply an ID to a _range_ of lines, you may want to apply it to a line you cannot reach.

For example, to reach four lines down and add an ID of `popover-trigger`, you could do the following:

```text
// Reach down 4 lines, add the ID to one line [tl! #popover-trigger:4,1]
return <<<EOT
spring sunshine
the smell of waters
from the stars

deep winter
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
EOT;
```

#### Character Ranges

You may also apply classes and IDs to character ranges on the current line by prefixing your range with the `c` character. Instead of supplying a range of line numbers, we supply the *character* range.

For example, the range `.inner-highlight:c26,34` instructs Torchlight to wrap the tokens from characters 26 through 34 with the `inner-highlight` class:

```text
<script src="//unpkg.com/alpinejs" defer></script> <!-- [tl! .inner-highlight:c26,34] -->
 
<div x-data="{ open: false }">
    <button @click="open = true">Expand</button>
 
    <span x-show="open">
        Content...
    </span>
</div>
```

![Character Range Example](./.art/readme/example_character_ranges.png)

> [!NOTE]
> You will need to add the desired CSS to style your character range classes.

### Auto-linking URLs

Sometimes your code contains URLs to other supporting documentation. It's a nice experience for the reader if those URLs were actually links instead of having to copy-paste them.

It's a little thing, but Torchlight sweats the little things so you don't have to.

Using the `autolink` annotation, Torchlight will look for URLs and turn them into links for you.

```php
/**
 * @see https://youtu.be/LEXIYgOXsRU?si=wDC7GxC1y3pNdHjZ&t=69. [tl! autolink]
 */

$link = 'https://youtu.be/LEXIYgOXsRU?si=wDC7GxC1y3pNdHjZ&t=69'; // [tl! autolink]
```

The resulting link will look like this (color will change depending on your theme):

```html
<a target="_blank" 
   rel="noopener" 
   class="torchlight-link" 
   style="color: #032F62;" 
   href="https://youtu.be/LEXIYgOXsRU?si=wDC7GxC1y3pNdHjZ&t=6">https://youtu.be/LEXIYgOXsRU?si=wDC7GxC1y3pNdHjZ&t=6</a>
```

Torchlight adds a `torchlight-link` class, and `rel` + `target` attributes.

The `rel=noopener` attribute ensures that no a malicious website doesn't have access to the `window.opener` property. Although this is less of a concern now with modern browsers, we still want you to be covered.

Read more about `rel=noopener` at [mathiasbynens.github.io/rel-noopener](https://mathiasbynens.github.io/rel-noopener/).

#### Link Requirements

Your URL must start with one of the following in order to match:

* `http:`
* `https:`
* `www.`

#### Link Ranges

The auto-link annotation supports the entire set of [range modifiers](#ranges) to help you quickly annotate a whole set of lines.

Check out the [range docs](#ranges) for more details, but here is a quick cheat sheet.

 ```text
 autolink          -- This line only
 
 autolink:start    -- The start of an open ended range
 autolink:end      -- The end of an open ended range
 
 autolink:10       -- This line, and the 10 following lines
 autolink:-10      -- This line, and the 10 preceding lines
 
 autolink:1,10     -- Start one line down, highlight 10 lines total
 autolink:-1,10    -- Start one line up, highlight 10 lines total
 ```

### Reindexing Line Numbers

Now we're really getting into the weeds, but that's exactly what Torchlight is here for.

Sometimes it really matters what the line number is that goes along with your code sample. In the case where you can't get it right, you might be tempted to turn them off altogether.

Torchlight offers a few ways to _reindex_ the lines, using the `reindex` annotation.

To reindex a line, you will add the `reindex` annotation. This annotation is a little bit different than the others, because it accepts an argument in parenthesis.

Here are a few examples:

* `reindex(-1)`: whatever this line number _would_ have been, reduce it by one
* `reindex(+1)`: whatever this line number _would_ have been, increment it by one
* `reindex(5)`: regardless of what number this should be, make it `5`
* `reindex(null)`: don't show a line number here.

#### Manually Setting a New Number

To just outright set a new number, use the `reindex(N)` style:

```text
'a';
'b';
'c';
'x'; // [tl! reindex(24)]
'y';
'z';
```

![Manually Setting a New Number](./.art/readme/example_reindex_new_number.png)

Torchlight will continue with the next number after the one you set.

#### No Line Number at All

If you want a line to have no line number, use the `reindex(null)` annotation:

```text
'a';
'b';
'c';
// Lots of letters... [tl! reindex(null)]
'x'; // [tl! reindex(24)]
'y';
'z';
```

![No Line Number](./.art/readme/example_reindex_no_line_number.png)

If you don't immediately reindex, Torchlight just treats that line as if it doesn't exist for numbering purposes.

```text
'a';
'b';
'c';
// Lots of letters... [tl! reindex(null)]
'x';
'y';
'z';
```

![No Immediate Line Reindex](./.art/readme/example_no_immediate_reindex.png)

#### Relative Line Number Changes

Often times it's easiest to think in terms of "increment" or "decrement" instead of thinking in absolutes. Especially as time goes on and your samples may change, it's nice to have the relative numbers always work.

This can be a really nice touch when showing diffs, to keep the numbering legit.

To change the numbers relatively, use the `reindex(+N)` and `reindex(-N)` styles.

```text
// torchlight! {"diffIndicatorsInPlaceOfLineNumbers": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add reindex(-1)]
    ]
]
```

![Relative Line Number Changes with Diff](./.art/readme/example_reindex_relative_changes.png)

Of course, it doesn't have to just be `1`, it could be any number.

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add reindex(+1000)]
    ]
]
```

![Reindexing with Any Number](./.art/readme/example_reindex_any_number.png)

#### Reindexing with Range Modifiers

The `reindex` annotation _does_ work with the [annotation range modifiers](ranges), so you can do some pretty wacky stuff.

If you wanted to reach down several lines and apply a reindex, you totally could!

Here we are going to reach down 6 lines, and apply a +5 reindex to 1 line only.

```text
// This is a long bit of text, hard to reindex the middle. [tl! reindex(+5):6,1]
return <<<EOT
spring sunshine
the smell of waters
from the stars

deep winter
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
EOT; // [tl! highlight:-7,3]
```

![Reindexing a Single Line with Range Modifiers](./.art/readme/example_reindex_single_line.png)

Or if you wanted to null out the second stanza, you could do that also.

```text
// This is a long bit of text, hard to reindex the middle. [tl! reindex(null):5,5]
return <<<EOT
spring sunshine
the smell of waters
from the stars

deep winter
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
EOT; // [tl! highlight:-7,3]
```

![Example of Nulling out Ranges](./.art/readme/example_reindex_stanza_voodoo.png)

Why you would ever want to do this, I have no idea. But if you want to, you can!

#### Vim-style Relative Line Numbers

> [!IMPORTANT]  
> The `vim.relative` and `vim.preserve` annotations were not designed to work in conjunction with _other_ reindex annotations. Because of this, their use in combination with other reindex annotations is considered undefined behavior.

You may reindex your line numbers similar to Vim's [relative line numbers](https://neovim.io/doc/user/options.html#'relativenumber). When you do this, the line numbers will count how far away they are from the annotation:

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class, // [tl! reindex(vim.relative)]
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Vim Relative Line Numbers](./.art/readme/example_vim_relative.png)

The annotation's line will be reindex to `0`, since that is the distance away from the annotation. If you'd like to preserve the current line number, you may use the `vim.preserve` annotation instead:

```php
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class, // [tl! reindex(vim.preserve)]
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Vim Preserve Current Line Number](./.art/readme/example_vim_preserve.png)

#### Reindex Differences Between Torchlight API

Torchlight Engine makes some breaking changes when compared to the behavior of the Torchlight API. This was done to make the behavior of reindexing with annotation ranges more predictable and consistent with the other annotations; there should be little to no impact on your code examples unless you are doing some crazy things.

Be sure to double check any reindex examples if you are migrating from the Torchlight API!

## Highlighting Files and Directory Structures

Torchlight provides a custom `files` language that can be used to highlight files and directory structures:

````
```files
// torchlight! { "lineNumbers": false, "fileStyle": "ascii" }
resources/
    name with space/
    # Full line comment
    blueprints/ # Partial comment
        collections/
            blog/
                post.yaml       # Old name [tl! --]
                basic_post.yaml # New name [tl! ++]
                art_directed_post.yaml
        taxonomies/
            tags/
                tag.yaml
        globals/
            global.yaml
            company.yaml
        assets/
            main.yaml
        forms/
            contact.yaml
        user.yaml
```
````

![ASCII Files Example](./.art/readme/example_files.png)

The `files` language supports two modes or styles:

* `ascii`: Renders connecting lines using ASCII characters
* `html`: Adds a number of HTML elements with class names that can be styled using CSS. If you'd like to use this option, you are encouraged to experiment with the generated output

## Options

Each of these is covered in detail on its own page, but here is an overview of each option and what it does.

* [lineNumbers](#line-numbers) - turn line numbers on or off
* [lineNumbersStart](#changing-the-starting-line-number) - the number of the first line
* [lineNumbersStyle](#changing-line-number-styles) - the CSS style to apply to line numbers
* [diffIndicators](#removing-diff-indicators) - turn on diff indicators (`+`/`-`)
* [diffIndicatorsInPlaceOfLineNumbers](#standalone-diff-indicators) - use the line number location for diff indicators
* [summaryCollapsedIndicator](#summary-indicator) - the text to show when a range is collapsed
* [torchlightAnnotations](#disabling-torchlight-annotations) - disable Torchlight annotation processing altogether.

### Setting Default Options Globally

When using Torchlight Engine without any clients, or helper packages, we need to tell it how to resolve any default global options. This is done by specifying a callback function that returns the default options:

```php
<?php

use Torchlight\Engine\Options;

Options::setDefaultOptionsBuilder(function () {
    return new Options(
        // Specify your options here.
    );
});
```

If you have an array of options, you may also use the static `fromArray` helper method:

```php
<?php

use Torchlight\Engine\Options;

Options::setDefaultOptionsBuilder(function () {
    return Options::fromArray([]);
});
```

As an example, if you are working in a Laravel project that already has a `torchlight.php` configuration file, you can continue using those options like so (this will become unnecessary once the Laravel client has been updated):

```php
<?php
namespace App\Providers;
 
use Illuminate\Support\ServiceProvider;
use Torchlight\Engine\Options;
 
class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Options::setDefaultOptionsBuilder(fn () => Options::fromArray(config('torchlight.options')));
    }
}
```

### Setting Default Themes Globally

Like with global options, we need to specify a callback letting Torchlight Engine's CommonMark extension know which theme to use. This only applies if you _do not_ specify a theme when instantiating the extension instance.

For example, we can specify default themes that will be used if the extension is not configured when instantiated:

```php
<?php


use Torchlight\Engine\CommonMark\Extension;

Extension::setThemeResolver(function () {
    return [
        'light' => 'github-light',
        'dark' => 'github-dark',
    ];
});
```

As another example, we could use the `torchlight.theme` configuration value within an existing Laravel application like so:

```php
<?php
namespace App\Providers;
 
use Illuminate\Support\ServiceProvider;
use Torchlight\Engine\CommonMark\Extension;
 
class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Extension::setThemeResolver(function () {
            return config('torchlight.theme');
        });
    }
}
```

### Setting Options Per Block

Some blocks you'll want to set options individually. Any options you set on the block level will override that same option on the global level.

To set block level options, the _first_ line of your block must be a comment, in the language of the block.

The comment _must_ begin with `torchlight!` and be followed valid JSON.

Here is an example turning line numbers off for a single block:

```text
// torchlight! {"lineNumbers": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![No Line Numbers](./.art/readme/example_no_line_numbers.png)

Any option that you can set at the global level, you can set at the block level.

### Line Numbers

Torchlight add line numbers by default, but you can disable them globally or on the block level by changing the `lineNumbers` option to false.

Here's an example of the block level change:

```text
// torchlight! {"lineNumbers": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![No Line Numbers](./.art/readme/example_no_line_numbers.png)

#### Changing the Starting Line Number

To change the starting number of a block, you may use the `lineNumbersStart` option:

```text
// torchlight! {"lineNumbersStart": 99}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Changing the Starting Line Number](./.art/readme/example_custom_starting_line_number.png)

Note: we also have a [`reindex` annotation](#reindexing-line-numbers) to control the line number line by line.

#### Changing Line Number Styles

By default, Torchlight applies a reasonable set of CSS style to your line numbers:

```css
.line-number {
    text-align: right; 
    -webkit-user-select: none; 
    user-select: none;
}
```

If you want to control that, you can pass in a `lineNumbersStyle` option.

```text
// torchlight! {"lineNumbersStyle": "opacity: .5;"}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Customizing Line Number Styles](./.art/readme/example_custom_line_number_style.png)

There are a couple of things to note when using this option.

The first is that you will likely want to include the `-webkit-user-select: none; user-select: none;` styles, so that when your visitors copy your code they don't get the line numbers. Because that's the worst.

Copy the code above and notice that the line numbers will be selected, versus the block right above it (Torchlight default).

The other thing to note is that you'll need to be thoughtful when adding `color` declarations.

![Line Number Colors](./.art/readme/example_line_number_colors.png)

Torchlight uses the theme's color scheme to handle insert and remove lines, so it's probably best to leave the color declaration off altogether.

#### Adding Line Number Right Padding

You may add _right_ padding to your line numbers using the `lineNumberAndDiffIndicatorRightPadding` block option:

```php
// torchlight! {"lineNumberAndDiffIndicatorRightPadding": 10}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Example of Right Padding](./.art/readme/example_right_padding.png)

If you are using standalone diff indicators, right padding will be applied to the _right_ of those:

```php
// torchlight! {"lineNumberAndDiffIndicatorRightPadding": 10, "diffIndicatorsInPlaceOfLineNumbers": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class, // [tl! ++]
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Example of Right Padding with Standalone Diff Indicators](./.art/readme/example_right_padding_diff_indicators.png)

Right padding being applied to the right of diff indicators is a small change in behavior compared to Torchlight API.

### Summary Indicator

When using the [collapse](#collapsing) annotation, Torchlight will add an ellipses `...` to indicate where the collapsed code is.

This is the default behavior:

![Default Summary Behavior](./.art/readme/example_summary_indicator_default.png)

If you'd like to change the `...` to something else, you can do so by changing the `summaryCollapsedIndicator` option:

```text
// torchlight! {"summaryCollapsedIndicator": "Click to Show"}
return [
    'heading_permalink' => [ // [tl! collapse:start]
        'html_class' => 'permalink',
        'id_prefix' => 'user-content',
        'insert' => 'before',
        'title' => 'Permalink',
        'symbol' => '#',
    ], // [tl! collapse:end]

    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
```

![Customizing the Collapse Text](./.art/readme/example_collapse_click_to_show.png)

### Adding Extra Classes to the Torchlight Code Element

You may add extra classes to the `code` element by using the `classes` block option:

```php
// torchlight! {"classes": "some extra classes"}
return [
    // ...
];
```

When Torchlight renders the code block it will add those classes to the generated `code` block:

```html
<code ... class="phiki language-php moonlight-ii torchlight some extra classes">
```

### The Copyable Option

You can use the `copyable` block option to instruct Torchlight to add a hidden HTML element with the `torchlight-copy-target` class to the generated output. This hidden element will contain the raw text that may be used to implement a copy & paste feature:

```php
// torchlight! {"copyable": true}
<?php
namespace App\Providers;
 
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\Markdown;
use Torchlight\Engine\CommonMark\Extension;
 
class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Add the Torchlight Engine extension
        Markdown::addExtension(function () {
            return new Extension('synthwave-84');
        });
    }
}
```

### Disabling Torchlight Annotations

If for whatever reason you want to disable _all_ of the Torchlight annotations, you may do so with the `torchlightAnnotations` option.

This option was added specifically for these docs. We don't expect you'll need it unless you're trying to show how Torchlight works!

```text
// torchlight! {"torchlightAnnotations": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class, // [tl! focus]
    ]
]
```

![Disabling Torchlight Annotations](./.art/readme/example_disabling_annotations.png)

## Reporting Issues

When reporting issues, please include _all_ of the following information:

* Grammar/Language
* PHP Version
* Phiki version
* Minimum input text required to reproduce the issue

If you know an issue is related to [Phiki](https://github.com/phikiphp/phiki) and _not_ the Torchlight renderer, please create an issue [here](https://github.com/phikiphp/phiki/issues). If you are not sure, feel free to create an issue in this repository and it will eventually end up in the right place 🙂

Some issues may be difficult to resolve and take time to implement. Everyone involved thanks you in advance for your patience.

## Contributing

Community contributions are welcome! However, if you are contributing additional themes or grammars, please take care to ensure their license allows it.

If you spot a grammar or theme that is being improperly used, please create an issue and it will be addressed.

## Credits

* [Aaron Francis](https://github.com/aarondfrancis)
* [John Koster](https://github.com/JohnathonKoster)
* [Ryan Chandler](https://github.com/ryangjchandler) for building [Phiki](https://github.com/phikiphp/phiki), making this project feasible.

## License

The Torchlight Engine is free software, released under the MIT license.

Themes and grammars may be governed by their own license.
