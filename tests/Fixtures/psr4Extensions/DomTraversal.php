<?php

namespace ExpectedNamespace;

extension DomTraversal on \DOMElement $el
{
    public function firstByClass(string $class): ?\DOMElement
    {
        return null;
    }
}
