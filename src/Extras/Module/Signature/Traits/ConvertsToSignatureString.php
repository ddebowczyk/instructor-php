<?php

namespace Cognesy\Instructor\Extras\Module\Signature\Traits;

use Cognesy\Instructor\Extras\Module\Signature\Signature;
use Cognesy\Instructor\Schema\Data\Schema\Schema;

trait ConvertsToSignatureString
{
    protected function makeShortSignatureString() : string {
        return $this->renderSignature($this->shortPropertySignature(...));
    }

    protected function makeSignatureString() : string {
        return $this->renderSignature($this->propertySignature(...));
    }

    // INTERNAL /////////////////////////////////////////////////////////////////////////

    private function renderSignature(callable $nameRenderer) : string {
        $inputs = $this->mapProperties($this->input->getPropertySchemas(), $nameRenderer);
        $outputs = $this->mapProperties($this->output->getPropertySchemas(), $nameRenderer);
        return implode('', [
            implode(', ', $inputs),
            (" " . Signature::ARROW . " "),
            implode(', ', $outputs)
        ]);
    }

    private function mapProperties(array $properties, callable $nameRenderer) : array {
        return array_map(
            fn(Schema $propertySchema) => $nameRenderer($propertySchema),
            $properties
        );
    }

    private function propertySignature(Schema $schema) : string {
        $description = '';
        if (!empty($schema->description())) {
            $description = ' (' . $schema->description() . ')';
        }
        return $schema->name() . ':' . $schema->typeDetails()->toString() . $description;
    }

    private function shortPropertySignature(Schema $schema) : string {
        return $schema->name();
    }
}