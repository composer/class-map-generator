<?php

namespace Acme\Tricky;

// "extension" is a valid symbol name in PHP today, and the RFC's import
// statement ("use extension ...") declares nothing; none of the usages below
// may produce a class map entry except the class actually named "extension"

use extension Acme\DomKit\DomTraversal;

class extension extends \ArrayObject
{
    public function extension(string $x): string
    {
        return \pathinfo($x, \PATHINFO_EXTENSION);
    }
}

function extension(string $s): string
{
    $obj = new extension();
    $obj->extension($s);

    return 'extension Foo on Bar $b { }';
}
