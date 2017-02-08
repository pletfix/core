<?php

namespace Core\Services\Contracts;

interface ViewCompiler
{
    /**
     * Compile the given template content to the corresponding valid PHP.
     *
     * @param string $content
     * @return string
     */
    public function compile($content);
}
