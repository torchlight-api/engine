<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\AnnotationType;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class MacroAnnotation extends AbstractAnnotation
{
    public static string $name = '__macro__';

    public function __construct(AnnotationEngine $annotationEngine, protected string $macroName, /** @var string[] */
        protected array $componentNames)
    {
        parent::__construct($annotationEngine);
    }

    public function process(ParsedAnnotation $annotation): void
    {
        $registry = $this->annotationEngine->getRegistry();

        foreach ($this->componentNames as $component) {
            $handler = $registry->resolve($component);

            if ($handler === null) {
                continue;
            }

            $componentAnnotation = new ParsedAnnotation;
            $componentAnnotation->index = $annotation->index;
            $componentAnnotation->sourceLine = $annotation->sourceLine;
            $componentAnnotation->name = $component;
            $componentAnnotation->text = $component;
            $componentAnnotation->methodArgs = $annotation->methodArgs;
            $componentAnnotation->rawMethodArgs = $annotation->rawMethodArgs;
            $componentAnnotation->options = $annotation->options;
            $componentAnnotation->range = $annotation->range;

            $componentAnnotation->type = AnnotationType::Named;
            foreach ($registry->getRegisteredPrefixes() as $prefix) {
                if (str_starts_with($component, $prefix)) {
                    $componentAnnotation->type = AnnotationType::Prefixed;
                    $componentAnnotation->prefix = $prefix;
                    break;
                }
            }

            $handler
                ->setTorchlightOptions($this->options)
                ->setActiveRange($this->activeRange())
                ->setParsedAnnotation($componentAnnotation)
                ->process($componentAnnotation);
        }
    }
}
